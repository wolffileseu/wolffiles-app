<?php

namespace App\Services\Tracker\Handlers;

use App\Models\Tracker\TrackerRawEvent;
use App\Services\Tracker\WeaponStatsParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Tracker\Handlers\PlayerPresenceHandler;

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

        // jaymod sends 6 fields per weapon (extra 'subshots'); stock/silEnT/
        // nitmod/legacy send 5. Derive from the reporting server's mod_name.
        $modName = (string) (DB::table('tracker_servers')
            ->where('id', $serverId)
            ->value('mod_name') ?? '');
        $fieldsPerWeapon = str_contains(strtolower($modName), 'jaymod') ? 6 : 5;

        $parsed = $this->parser->parse($event->payload, $fieldsPerWeapon);
        if ($parsed === null) {
            Log::warning('WeaponStatsHandler: unparsable ws payload', [
                'event_id' => $event->id,
                'payload' => substr($event->payload, 0, 200),
            ]);
            return;
        }

        // === BOT SKIP (Commit 6: early return) ===
        // Defensive check: if the parsed client name looks like a bot,
        // skip everything. Prevents slot-recycling from attaching bot
        // stats to the previous real player on that slot.
        $clientName = $parsed['client']['name'] ?? null;
        if (PlayerPresenceHandler::looksLikeBot(null, $clientName)) {
            Log::debug('WeaponStatsHandler: skipping bot ws event', [
                'server_id' => $serverId,
                'slot' => $parsed['slot'] ?? null,
                'name' => $clientName,
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

        // Persist the player's current class onto the open slot row so the
        // live player list can show it. class comes from the ws clientinfo
        // (\ping\score\P\class\name). Done BEFORE the match check below
        // because class is valid even during warmup / between maps, when
        // there is no open match. ET only — RtCW never sends ws.
        $wsClass = $parsed['client']['class'] ?? null;
        if ($wsClass !== null && $wsClass !== '') {
            DB::table('tracker_server_slots')
                ->where('server_id', $serverId)
                ->where('slot', $parsed['slot'])
                ->whereNull('disconnected_at')
                ->update(['class' => (int) $wsClass, 'updated_at' => now()]);
        }

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
            // ORDER MATTERS: Lifetime delta must be computed from the OLD
            // per-match snapshot BEFORE we overwrite it with the new values.
            $this->updateLifetimeWeaponStats($match->id, $playerId, $parsed, $event->received_at);
            $this->writeMatchWeaponSnapshots($match->id, $playerId, $parsed, $event->received_at);
            $this->writeMatchPlayerStats($match->id, $serverId, $playerId, $parsed, $event->received_at);
        });
    }

    /**
     * Update cumulative lifetime weapon stats in tracker_player_weapon_stats.
     *
     * The ws-packet contains cumulative values for the CURRENT match. To
     * keep player-lifetime totals correct we compute a delta against the
     * last snapshot we wrote for this (match, player, weapon) combo.
     *
     * Delta rules:
     *   1. If a previous snapshot exists and new values are >= old values
     *      -> delta = new - old (normal growth within same match)
     *   2. Else (no prior snapshot OR new < old)
     *      -> delta = new values themselves
     *         (new match, or a maprestart reset the game-side counters)
     *
     * Rule 2 is also the safe fallback for missed packets.
     *
     * MUST be called BEFORE writeMatchWeaponSnapshots(), which overwrites
     * the snapshot we read here.
     */
    private function updateLifetimeWeaponStats(int $matchId, int $playerId, array $parsed, \Illuminate\Support\Carbon $receivedAt): void
    {
        $nowMs = $receivedAt->format('Y-m-d H:i:s.v');

        foreach ($parsed['weapons'] as $weaponBit => $w) {
            // Read the previous per-match snapshot (before we overwrite it).
            $previous = DB::table('tracker_match_player_weapon_stats')
                ->where('match_id', $matchId)
                ->where('player_id', $playerId)
                ->where('weapon_bit', $weaponBit)
                ->first(['hits', 'atts', 'kills', 'deaths', 'headshots']);

            // Compute delta. We use 'hits' as the growth-direction sentinel
            // (in ET it monotonically increases within a match).
            if ($previous !== null && $previous->hits <= $w['hits']) {
                // Clamp each delta at 0: a cumulative lifetime total must
                // never shrink from a single ws packet. A negative delta
                // (counter regression / packet anomaly) would otherwise be
                // written into an UNSIGNED column -> MySQL error 1264.
                $deltaHits      = max(0, $w['hits']      - $previous->hits);
                $deltaAtts      = max(0, $w['atts']      - $previous->atts);
                $deltaKills     = max(0, $w['kills']     - $previous->kills);
                $deltaDeaths    = max(0, $w['deaths']    - $previous->deaths);
                $deltaHeadshots = max(0, $w['headshots'] - $previous->headshots);
            } else {
                // No prior snapshot OR values regressed -> treat as fresh growth.
                $deltaHits      = $w['hits'];
                $deltaAtts      = $w['atts'];
                $deltaKills     = $w['kills'];
                $deltaDeaths    = $w['deaths'];
                $deltaHeadshots = $w['headshots'];
            }

            // Skip if this packet added nothing (duplicate or idle).
            if ($deltaHits === 0 && $deltaAtts === 0 && $deltaKills === 0
                && $deltaDeaths === 0 && $deltaHeadshots === 0) {
                continue;
            }

            // Upsert into lifetime totals. Using a raw statement here for atomic
            // accuracy_bp computation — MySQL's ON DUPLICATE KEY UPDATE lets us
            // reference both the existing column and the incoming VALUES().
            DB::statement(
                'INSERT INTO tracker_player_weapon_stats
                    (player_id, weapon_bit,
                     total_hits, total_atts, total_kills, total_deaths, total_headshots,
                     accuracy_bp, first_seen_at, last_updated_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    total_hits      = total_hits      + VALUES(total_hits),
                    total_atts      = total_atts      + VALUES(total_atts),
                    total_kills     = total_kills     + VALUES(total_kills),
                    total_deaths    = total_deaths    + VALUES(total_deaths),
                    total_headshots = total_headshots + VALUES(total_headshots),
                    accuracy_bp     = IF(
                        (total_atts + VALUES(total_atts)) > 0,
                        FLOOR((total_hits + VALUES(total_hits)) * 10000 /
                              (total_atts + VALUES(total_atts))),
                        0
                    ),
                    last_updated_at = VALUES(last_updated_at),
                    updated_at      = VALUES(updated_at)',
                [
                    $playerId, $weaponBit,
                    $deltaHits, $deltaAtts, $deltaKills, $deltaDeaths, $deltaHeadshots,
                    $nowMs, $nowMs, $nowMs, $nowMs,
                ]
            );
        }
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

        // Clamp to 0..100: a percentage > 100 means the parser fed us
        // garbage (atts < hits / shifted field). Must never reach the
        // decimal(5,2) column unbounded -> MySQL error 1264.
        $accuracyPct = $totalAtts > 0
            ? min(100, max(0, round($totalHits * 100 / $totalAtts, 2)))
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
                // Prestige is realistically 0..~50. Out-of-range values mean the
                // tail tokens were mis-ordered for this server's compilation
                // (a rating/score leaked into the prestige slot). Treat as null
                // rather than writing garbage that overflows the SMALLINT column
                // (MySQL 1264). Same defensive pattern as the damage-block clamps.
                $prCandidate = (int) $pr;
                $prestige = ($prCandidate >= 0 && $prCandidate <= 1000) ? $prCandidate : null;
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

                // Clamp all damage-block counters at 0: parser anomalies
                // produced negative/garbage values written into UNSIGNED
                // columns -> MySQL error 1264 (first seen on 'gibs').
                'damage_given' => max(0, (int) ($damage['given'] ?? 0)),
                'damage_received' => max(0, (int) ($damage['received'] ?? 0)),
                'team_damage_given' => max(0, (int) ($damage['team_given'] ?? 0)),
                'team_damage_received' => max(0, (int) ($damage['team_received'] ?? 0)),
                'team_kills' => max(0, (int) ($damage['team_kills'] ?? 0)),
                'gibs' => max(0, (int) ($damage['gibs'] ?? 0)),
                'kill_assists' => max(0, (int) ($damage['kill_assists'] ?? 0)),
                'self_kills' => max(0, (int) ($damage['self_kills'] ?? 0)),
                'team_gibs' => max(0, (int) ($damage['team_gibs'] ?? 0)),

                'time_played_pct' => min(100, max(0, (float) $parsed['time_played_pct'])),
                'ping_avg' => $client['ping'] ?? 0,
                // Score can legitimately be NEGATIVE (teamkills/self-kills).
                // Clamp to signed int range only -- never max(0,...).
                'score' => max(-2147483648, min(2147483647, (int) ($client['score'] ?? 0))),

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
