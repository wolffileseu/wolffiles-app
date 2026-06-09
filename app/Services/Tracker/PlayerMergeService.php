<?php

namespace App\Services\Tracker;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Merges a duplicate tracker_players row (typically an Enhanced-created row
 * with a real GUID) INTO the canonical Poller row that holds the history.
 *
 * Direction: $keepId survives (history, identity), $mergeId is folded in and
 * marked merged_into=$keepId (NOT deleted — fully reversible).
 *
 * After merge, $keepId inherits the real_guid_hash, so the Presence handler's
 * Stage 1 will route all future connects to $keepId — no more splits.
 *
 * Lifetime counters (total_*, elo) stay on $keepId. We do NOT add the merge
 * player's totals, because its sessions are re-pointed to $keepId and the
 * Poller's totals are the authoritative history. Enhanced_* mirror fields are
 * copied over since only the Enhanced row carries them.
 */
class PlayerMergeService
{
    /**
     * Tables with a player_id column that must be re-pointed merge -> keep.
     * Excludes tracker_player_aliases (special dedup handling below).
     */
    private const PLAYER_ID_TABLES = [
        'tracker_player_sessions',
        'tracker_player_snapshots',
        'tracker_player_match_stats',
        'tracker_match_player_weapon_stats',
        'tracker_player_weapon_stats',
        'tracker_server_slots',
        'tracker_player_daily_stats',
        'tracker_elo_history',
        'tracker_player_screenshots',
        'tracker_clan_members',
        'tracker_bans',
    ];

    /**
     * Derived / aggregated tables: rows are recomputed periodically and carry
     * UNIQUE(player_id, ...) indexes, so re-pointing would violate them. On
     * merge we DELETE the merge player's rows; they regenerate on next compute.
     */
    private const DERIVED_TABLES = [
        'tracker_rankings',
        'tracker_player_rankings_30d',
        'tracker_server_top_players',
    ];

    /**
     * @return array{ok:bool, dry_run:bool, actions:array, error:?string}
     */
    public function merge(int $keepId, int $mergeId, bool $dryRun = true): array
    {
        $actions = [];

        if ($keepId === $mergeId) {
            return ['ok' => false, 'dry_run' => $dryRun, 'actions' => [], 'error' => 'keep and merge are the same id'];
        }

        $keep  = DB::table('tracker_players')->where('id', $keepId)->first();
        $merge = DB::table('tracker_players')->where('id', $mergeId)->first();

        if (!$keep || !$merge) {
            return ['ok' => false, 'dry_run' => $dryRun, 'actions' => [], 'error' => 'player not found'];
        }

        // Idempotency / safety guards
        if ($keep->merged_into !== null) {
            return ['ok' => false, 'dry_run' => $dryRun, 'actions' => [], 'error' => "keep ($keepId) is itself merged into {$keep->merged_into}"];
        }
        if ($merge->merged_into !== null) {
            return ['ok' => false, 'dry_run' => $dryRun, 'actions' => [], 'error' => "merge ($mergeId) already merged into {$merge->merged_into}"];
        }

        // Never merge two distinct user accounts together.
        $keepUser  = $keep->user_id ?? $keep->claimed_by_user_id ?? null;
        $mergeUser = $merge->user_id ?? $merge->claimed_by_user_id ?? null;
        if ($keepUser !== null && $mergeUser !== null && $keepUser !== $mergeUser) {
            return ['ok' => false, 'dry_run' => $dryRun, 'actions' => [], 'error' => "both players are claimed by different users ($keepUser vs $mergeUser)"];
        }

        $run = function () use ($keepId, $mergeId, $keep, $merge, $dryRun, &$actions) {
            // 1) Re-point all player_id tables
            foreach (self::PLAYER_ID_TABLES as $t) {
                if (!DB::getSchemaBuilder()->hasTable($t)) {
                    $actions[] = ['table' => $t, 'action' => 'skip (missing)', 'rows' => 0];
                    continue;
                }
                $count = DB::table($t)->where('player_id', $mergeId)->count();
                if ($count > 0 && !$dryRun) {
                    DB::table($t)->where('player_id', $mergeId)->update(['player_id' => $keepId]);
                }
                $actions[] = ['table' => $t, 'action' => 'repoint player_id', 'rows' => $count];
            }

            // 1b) Derived tables: delete merge player's rows (recomputed later)
            foreach (self::DERIVED_TABLES as $t) {
                if (!DB::getSchemaBuilder()->hasTable($t)) {
                    $actions[] = ['table' => $t, 'action' => 'skip (missing)', 'rows' => 0];
                    continue;
                }
                $count = DB::table($t)->where('player_id', $mergeId)->count();
                if ($count > 0 && !$dryRun) {
                    DB::table($t)->where('player_id', $mergeId)->delete();
                }
                $actions[] = ['table' => $t, 'action' => 'delete derived rows', 'rows' => $count];
            }

            // 2) Aliases: re-point, but dedup by name_clean
            if (DB::getSchemaBuilder()->hasTable('tracker_player_aliases')) {
                $mergeAliases = DB::table('tracker_player_aliases')->where('player_id', $mergeId)->get();
                $keepNames = DB::table('tracker_player_aliases')->where('player_id', $keepId)->pluck('name_clean')->all();
                $moved = 0; $dropped = 0;
                foreach ($mergeAliases as $a) {
                    if (in_array($a->name_clean, $keepNames, true)) {
                        if (!$dryRun) DB::table('tracker_player_aliases')->where('id', $a->id)->delete();
                        $dropped++;
                    } else {
                        if (!$dryRun) DB::table('tracker_player_aliases')->where('id', $a->id)->update(['player_id' => $keepId]);
                        $moved++;
                    }
                }
                $actions[] = ['table' => 'tracker_player_aliases', 'action' => "moved=$moved dropped_dupe=$dropped", 'rows' => $moved + $dropped];
            }

            // 3) Copy enhanced identity + mirror fields to keep
            $keepUpdate = [];
            if ($keep->real_guid_hash === null && $merge->real_guid_hash !== null) {
                $keepUpdate['real_guid_hash'] = $merge->real_guid_hash;
            }
            if (!$keep->has_enhanced_data && $merge->has_enhanced_data) {
                $keepUpdate['has_enhanced_data'] = 1;
            }
            foreach (['enhanced_first_seen_at','enhanced_last_seen_at','enhanced_total_kills','enhanced_total_deaths','enhanced_total_headshots','enhanced_total_damage','enhanced_match_count'] as $f) {
                if (isset($merge->$f) && $merge->$f !== null && ($keep->$f === null || $keep->$f === 0)) {
                    $keepUpdate[$f] = $merge->$f;
                }
            }
            if (!empty($keepUpdate)) {
                if (!$dryRun) DB::table('tracker_players')->where('id', $keepId)->update($keepUpdate);
                $actions[] = ['table' => 'tracker_players(keep)', 'action' => 'set ' . implode(',', array_keys($keepUpdate)), 'rows' => 1];
            }

            // 4) Mark merge player as merged (reversible)
            if (!$dryRun) {
                DB::table('tracker_players')->where('id', $mergeId)->update([
                    'merged_into' => $keepId,
                    'merged_at'   => now(),
                ]);
            }
            $actions[] = ['table' => 'tracker_players(merge)', 'action' => "mark merged_into=$keepId", 'rows' => 1];
        };

        if ($dryRun) {
            $run(); // no transaction needed, nothing writes
        } else {
            DB::transaction($run);
            Log::info('PlayerMergeService: merged', ['keep' => $keepId, 'merge' => $mergeId, 'actions' => $actions]);
        }

        return ['ok' => true, 'dry_run' => $dryRun, 'actions' => $actions, 'error' => null];
    }
}
