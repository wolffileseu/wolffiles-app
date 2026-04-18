<?php

namespace App\Services\Tracker\Handlers;

use App\Models\Tracker\TrackerRawEvent;
use App\Services\Tracker\WeaponStatsParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles 'ws' (weapon stats) packets from ETLegacy.
 *
 * Phase 3.5 — TEST MODE scope:
 *   - Parse payload via WeaponStatsParser
 *   - Resolve which player is on the reporting slot (tracker_server_slots)
 *   - Resolve the currently open match for the server
 *   - Upsert per-match per-weapon snapshots (tracker_match_player_weapon_stats)
 *   - Upsert aggregated per-match player stats (tracker_player_match_stats)
 *
 * NOT YET DONE in this phase:
 *   - Cumulative tracker_player_weapon_stats aggregation (needs delta logic)
 *   - Cumulative tracker_players.enhanced_total_* totals
 *
 * Why test-mode first: match snapshots are idempotent-ish (upsert with
 * packet values), while delta-based cumulative totals risk double-counting
 * on reprocess / restart. We get real per-match data flowing first, then
 * build aggregation on top with confidence.
 *
 * Also handled:
 *   - wsc <count> header — acknowledged but no action needed. It tells the
 *     server-side tracker how many ws packets to expect in this batch, but
 *     we don't correlate batches; each ws is processed independently.
 */
class WeaponStatsHandler extends AbstractHandler
{
    private WeaponStatsParser $parser;

    public function __construct(?WeaponStatsParser $parser = null)
    {
        $this->parser = $parser ?? new WeaponStatsParser();
    }

    public function supports(): array
    {
        return ['wsc', 'ws'];
    }

    public function handle(TrackerRawEvent $event): void
    {
        if ($event->cmd === 'wsc') {
            // Header packet — ignore.
            return;
        }

        $serverId = $event->server_id ?? $this->resolveServerId($event);
        if ($serverId === null) {
            return;
        }

        $parsed = $this->parser->parse($event->payload);
        if ($parsed === null) {
            Log::warning('WeaponStatsHandler: unparsable ws payload', [
                'event_id' => $event->id,
                'payload' => substr($event->payload, 0, 200),
            ]);
            return;
        }

        // Look up which player is currently on this slot.
        // If no open slot row: likely a bot or untracked player — skip silently.
        $slotRow = DB::table('tracker_server_slots')
            ->where('server_id', $serverId)
            ->where('slot', $parsed['slot'])
            ->whereNull('disconnected_at')
            ->orderByDesc('connected_at')
            ->first(['player_id']);

        if ($slotRow === null) {
            // Not an error — bots are intentionally not in this table.
            Log::debug('WeaponStatsHandler: no slot mapping', [
                'server_id' => $serverId,
                'slot' => $parsed['slot'],
            ]);
            return;
        }

        $playerId = (int) $slotRow->player_id;

        // Find the currently-open match for this server. Without an open
        // match we can't attribute per-match stats — ws packets during
        // warmup or between maps are discarded.
        $match = DB::table('tracker_matches')
            ->where('server_id', $serverId)
            ->whereNull('ended_at')
            ->orderByDesc('started_at')
            ->first(['id', 'map_name']);

        if ($match === null) {
            Log::debug('WeaponStatsHandler: no open match for server', [
                'server_id' => $serverId,
                'player_id' => $playerId,
            ]);
            return;
        }

        DB::transaction(function () use ($event, $parsed, $match, $playerId, $serverId) {
            $this->writeMatchWeaponSnapshots($match->id, $playerId, $parsed, $event->received_at);
            $this->writeMatchPlayerStats($match->id, $serverId, $playerId, $parsed, $event->received_at);
        });
    }

    /**
     * Write one row per weapon in the payload into
     * tracker_match_player_weapon_stats. Idempotent via (match, player, weapon)
     * unique key — later ws packets in the same match overwrite with newer
     * totals (the game reports cumulative values for the current match).
     */
    private function writeMatchWeaponSnapshots(int $matchId, int $playerId, array $parsed, \Illuminate\Support\Carbon $receivedAt): void
    {
        $nowMs = $receivedAt->format('Y-m-d H:i:s.v');

        foreach ($parsed['weapons'] as $weaponBit => $w) {
            $accuracyBp = $this->computeAccuracyBp($w['hits'], $w['atts']);

            DB::table('tracker_match_player_weapon_stats')->upsert(
                [[
                    'match_id' => $matchId,
                    'player_id' => $playerId,
                    'weapon_bit' => $weaponBit,
                    'hits' => $w['hits'],
                    'atts' => $w['atts'],
                    'kills' => $w['kills'],
                    'deaths' => $w['deaths'],
                    'headshots' => $w['headshots'],
                    'accuracy_bp' => $accuracyBp,
                    'recorded_at' => $nowMs,
                    'created_at' => $nowMs,
                    'updated_at' => $nowMs,
                ]],
                ['match_id', 'player_id', 'weapon_bit'],
                ['hits', 'atts', 'kills', 'deaths', 'headshots', 'accuracy_bp', 'recorded_at', 'updated_at']
            );
        }
    }

    /**
     * Write / update the aggregated per-match player stats.
     * Summarizes damage section + skill tail. Kills/deaths/headshots are
     * summed from per-weapon stats for consistency with match_player_weapon_stats.
     */
    private function writeMatchPlayerStats(int $matchId, int $serverId, int $playerId, array $parsed, \Illuminate\Support\Carbon $receivedAt): void
    {
        $nowMs = $receivedAt->format('Y-m-d H:i:s.v');

        // Fetch required guid_hash from player record (column is NOT NULL in schema)
        $player = DB::table('tracker_players')
            ->where('id', $playerId)
            ->first(['guid_hash']);
        $guidHash = $player?->guid_hash ?? '';

        // Aggregate per-weapon into totals
        $totalHits = 0;
        $totalAtts = 0;
        $totalKills = 0;
        $totalDeaths = 0;
        $totalHeadshots = 0;
        foreach ($parsed['weapons'] as $w) {
            $totalHits += $w['hits'];
            $totalAtts += $w['atts'];
            $totalKills += $w['kills'];
            $totalDeaths += $w['deaths'];
            $totalHeadshots += $w['headshots'];
        }

        $accuracyPct = $totalAtts > 0
            ? round($totalHits * 100 / $totalAtts, 2)
            : 0.00;

        $damage = $parsed['damage'] ?? [];

        // Tail interpretation: [rating, rating_delta, prestige]
        // Only set when we have exactly that shape. Otherwise leave null.
        $skillRating = null;
        $skillRatingDelta = null;
        $prestige = null;
        if (count($parsed['tail']) >= 3) {
            $r = $parsed['tail'][0];
            $rd = $parsed['tail'][1];
            $pr = $parsed['tail'][2];
            if (is_numeric($r) && is_numeric($rd)) {
                $skillRating = (float) $r;
                $skillRatingDelta = (float) $rd;
            }
            if (is_numeric($pr) && !str_contains($pr, '.')) {
                $prestige = (int) $pr;
            }
        }

        $rawSkills = json_encode([
            'mask' => $parsed['skill_mask'],
            'mode' => $parsed['skill_mode'] ?? 'single',
            'skills' => $parsed['skills'],
        ]);

        $client = $parsed['client'] ?? [];

        DB::table('tracker_player_match_stats')->upsert(
            [[
                'match_id' => $matchId,
                'server_id' => $serverId,
                'player_id' => $playerId,
                'guid_hash' => $guidHash,
                'slot' => $parsed['slot'],
                'class' => $client['class'] ?? 0,
                'name_snapshot' => $client['name'] ?? '',
                'name_clean_snapshot' => $this->stripColorCodes($client['name'] ?? ''),

                'kills' => $totalKills,
                'deaths' => $totalDeaths,
                'headshots' => $totalHeadshots,
                'accuracy_pct' => $accuracyPct,
                'weapon_bitmask' => $parsed['weapon_mask'],

                'damage_given' => $damage['given'] ?? 0,
                'damage_received' => $damage['received'] ?? 0,
                'team_damage_given' => $damage['team_given'] ?? 0,
                'team_damage_received' => $damage['team_received'] ?? 0,
                'team_kills' => $damage['team_kills'] ?? 0,
                'gibs' => $damage['gibs'] ?? 0,
                'kill_assists' => $damage['kill_assists'] ?? 0,
                'self_kills' => $damage['self_kills'] ?? 0,
                'team_gibs' => $damage['team_gibs'] ?? 0,

                'time_played_pct' => $parsed['time_played_pct'],
                'ping_avg' => $client['ping'] ?? 0,
                'score' => $client['score'] ?? 0,

                'skill_rating' => $skillRating,
                'skill_rating_delta' => $skillRatingDelta,
                'prestige' => $prestige,
                'raw_skills' => $rawSkills,

                'created_at' => $nowMs,
                'updated_at' => $nowMs,
            ]],
            ['match_id', 'slot'],
            [
                'player_id', 'class', 'name_snapshot', 'name_clean_snapshot',
                'kills', 'deaths', 'headshots', 'accuracy_pct', 'weapon_bitmask',
                'damage_given', 'damage_received', 'team_damage_given', 'team_damage_received',
                'team_kills', 'gibs', 'kill_assists', 'self_kills', 'team_gibs',
                'time_played_pct', 'ping_avg', 'score',
                'skill_rating', 'skill_rating_delta', 'prestige', 'raw_skills',
                'updated_at',
            ]
        );
    }

    /**
     * Accuracy in basis points: 4250 = 42.50%.
     * Avoids float arithmetic in DB indexes.
     */
    private function computeAccuracyBp(int $hits, int $atts): int
    {
        if ($atts <= 0) {
            return 0;
        }
        return (int) floor($hits * 10000 / $atts);
    }

    /**
     * Strip ET color codes (^1, ^2, ...) from a name.
     */
    protected function stripColorCodes(string $name): string
    {
        return preg_replace('/\^[0-9a-zA-Z]/', '', $name) ?? $name;
    }

    private function resolveServerId(TrackerRawEvent $event): ?int
    {
        $server = DB::table('tracker_servers')
            ->where('enhanced_source_ip', $event->source_ip)
            ->orWhere('ip', $event->source_ip)
            ->where('enhanced_disabled', false)
            ->first(['id']);

        return $server?->id;
    }
}
