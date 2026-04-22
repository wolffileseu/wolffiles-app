<?php

namespace App\Console\Commands\Tracker;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Cleanup bot pollution in tracker data.
 *
 * Bot detection rules (any match):
 *   1. tracker_players.real_guid_hash points at a GUID containing 'BOT'
 *      (OmniBot sends GUIDs like 000000000000000000000000BOT00018)
 *   2. tracker_players.name_clean starts with '[BOT]'
 *   3. tracker_players.name_clean starts with 'OmniBot'
 *   4. tracker_player_match_stats.name_clean_snapshot starts with '[BOT]' or 'OmniBot'
 *
 * What we do:
 *   A. Mark player-rows as is_bot=1 (additive, never clears)
 *   B. Delete match_stats rows where name_snapshot looks like a bot
 *   C. Delete weapon_stats rows for those match_stats
 *   D. Delete aliases with bot names
 *   E. Recompute totals on affected real players
 */
class CleanupBotsCommand extends Command
{
    protected $signature = 'tracker:cleanup-bots
                            {--dry-run : Show what would be deleted without touching anything}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Mark bots and remove bot pollution from match/weapon/alias stats';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if ($dry) {
            $this->warn('=== DRY-RUN MODE (nothing will be written) ===');
        }

        // ── Phase A: mark bot players ────────────────────────────────
        $this->info('Phase A: Identifying bot players...');

        $botPlayerIds = DB::table('tracker_players')
            ->where(function ($q) {
                $q->where('name_clean', 'LIKE', '[BOT]%')
                  ->orWhere('name_clean', 'LIKE', 'OmniBot%');
            })
            ->pluck('id');

        $this->line('  Bot-name players:        ' . $botPlayerIds->count());

        // Collect all match_stats rows with bot name_snapshot — these are
        // the "pollution" rows wrongly attached to real players.
        $pollutedStats = DB::table('tracker_player_match_stats')
            ->where(function ($q) {
                $q->where('name_clean_snapshot', 'LIKE', '[BOT]%')
                  ->orWhere('name_clean_snapshot', 'LIKE', 'OmniBot%');
            })
            ->select('id', 'match_id', 'player_id', 'name_clean_snapshot')
            ->get();

        $affectedPlayerIds = $pollutedStats->pluck('player_id')->unique();
        $this->line('  Polluted match_stats:    ' . $pollutedStats->count());
        $this->line('  Affected real players:   ' . $affectedPlayerIds->count());

        // Bot aliases
        $botAliases = DB::table('tracker_player_aliases')
            ->where(function ($q) {
                $q->where('name_clean', 'LIKE', '[BOT]%')
                  ->orWhere('name_clean', 'LIKE', 'OmniBot%');
            })
            ->select('id', 'player_id', 'name_clean')
            ->get();

        $this->line('  Bot aliases:             ' . $botAliases->count());

        if (!$force && !$dry) {
            if (!$this->confirm('Proceed with cleanup?', false)) {
                $this->warn('Aborted.');
                return self::SUCCESS;
            }
        }

        // ── Phase B: mark is_bot = 1 on bot players ───────────────────
        if (!$dry && $botPlayerIds->isNotEmpty()) {
            $updated = DB::table('tracker_players')
                ->whereIn('id', $botPlayerIds)
                ->update(['is_bot' => 1, 'updated_at' => now()]);
            $this->info("  Phase B: Marked {$updated} players as is_bot=1");
        } else {
            $this->line("  Phase B: Would mark " . $botPlayerIds->count() . " players as is_bot=1");
        }

        // ── Phase C: delete weapon_stats for polluted match_stats ────
        $matchStatIds = $pollutedStats->pluck('id');
        if ($matchStatIds->isNotEmpty()) {
            // weapon_stats are keyed by (match_id, player_id) — find them by that
            $pollutedPairs = $pollutedStats->map(fn($r) => [
                'match_id'  => $r->match_id,
                'player_id' => $r->player_id,
                'name'      => $r->name_clean_snapshot,
            ]);

            $weaponDeleted = 0;
            foreach ($pollutedPairs->chunk(500) as $chunk) {
                $q = DB::table('tracker_match_player_weapon_stats')->where('id', 0);
                foreach ($chunk as $pair) {
                    $q->orWhere(function ($sub) use ($pair) {
                        $sub->where('match_id', $pair['match_id'])
                            ->where('player_id', $pair['player_id']);
                    });
                }
                $found = (clone $q)->count();
                if (!$dry && $found > 0) {
                    $weaponDeleted += $q->delete();
                } else {
                    $weaponDeleted += $found;
                }
            }
            $msg = $dry ? "  Phase C: Would delete {$weaponDeleted} weapon_stats rows"
                        : "  Phase C: Deleted {$weaponDeleted} weapon_stats rows";
            $this->info($msg);
        }

        // ── Phase D: delete polluted match_stats rows ────────────────
        if ($matchStatIds->isNotEmpty()) {
            if (!$dry) {
                $deleted = DB::table('tracker_player_match_stats')
                    ->whereIn('id', $matchStatIds)
                    ->delete();
                $this->info("  Phase D: Deleted {$deleted} match_stats rows");
            } else {
                $this->line("  Phase D: Would delete " . $matchStatIds->count() . " match_stats rows");
            }
        }

        // ── Phase E: delete bot aliases ──────────────────────────────
        if ($botAliases->isNotEmpty()) {
            $aliasIds = $botAliases->pluck('id');
            if (!$dry) {
                $deleted = DB::table('tracker_player_aliases')
                    ->whereIn('id', $aliasIds)
                    ->delete();
                $this->info("  Phase E: Deleted {$deleted} aliases");
            } else {
                $this->line("  Phase E: Would delete " . $aliasIds->count() . " aliases");
            }
        }

        // ── Phase F: recompute enhanced totals for affected real players ──
        if (!$dry && $affectedPlayerIds->isNotEmpty()) {
            $this->info("  Phase F: Recomputing enhanced_* totals for {$affectedPlayerIds->count()} real players...");
            $bar = $this->output->createProgressBar($affectedPlayerIds->count());
            $bar->start();
            foreach ($affectedPlayerIds as $pid) {
                $sums = DB::table('tracker_player_match_stats')
                    ->where('player_id', $pid)
                    ->selectRaw('
                        COUNT(*) as match_count,
                        COALESCE(SUM(kills),0) as kills,
                        COALESCE(SUM(deaths),0) as deaths,
                        COALESCE(SUM(headshots),0) as headshots,
                        COALESCE(SUM(damage_given),0) as damage
                    ')
                    ->first();

                DB::table('tracker_players')
                    ->where('id', $pid)
                    ->update([
                        'enhanced_match_count'      => (int) ($sums->match_count ?? 0),
                        'enhanced_total_kills'      => (int) ($sums->kills ?? 0),
                        'enhanced_total_deaths'     => (int) ($sums->deaths ?? 0),
                        'enhanced_total_headshots'  => (int) ($sums->headshots ?? 0),
                        'enhanced_total_damage'     => (int) ($sums->damage ?? 0),
                        'updated_at'                => now(),
                    ]);
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        } else {
            $this->line("  Phase F: Would recompute totals for " . $affectedPlayerIds->count() . " players");
        }

        $this->newLine();
        $this->info($dry ? '=== DRY-RUN complete — nothing was changed ===' : '=== Cleanup complete ===');
        return self::SUCCESS;
    }
}
