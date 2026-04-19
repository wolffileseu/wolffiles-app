<?php

namespace App\Services\Tracker;

use Illuminate\Support\Facades\DB;

class Ranking30dService
{
    /**
     * Name patterns that indicate spectator bots, streaming clients, or placeholder names.
     * These players are excluded from the leaderboard.
     */
    private const EXCLUDED_NAME_PATTERNS = [
        'TV',           // ETTV streaming clients (ETLTV#1, E T c|TV, Ets|TV, etc.)
        'spectator',    // Spectator clients
        'twitch.tv',    // Twitch streamers
    ];

    /**
     * Name prefixes to exclude (case-sensitive).
     */
    private const EXCLUDED_NAME_PREFIXES = [
        '[BOT]',        // Omni-Bot AI players
    ];

    private const EXCLUDED_EXACT_NAMES = [
        'ETPlayer',
        'WolfPlayer',
        'Player',
        'UnnamedPlayer',
        'unknown',
    ];

    /**
     * Rebuild tracker_server_rankings (incl. all 30d metrics).
     */
    public function recalculateServers(): int
    {
        $since = now()->subDays(30);
        $computedAt = now();

        // Raw SQL with FORCE INDEX hint to ensure MySQL uses the (polled_at, server_id)
        // composite index instead of full table scan. Essential at 1.8M+ rows.
        $historyRows = DB::select('
            SELECT
                server_id,
                AVG(players) AS avg_players,
                MAX(players) AS peak_players,
                COUNT(*) AS total_polls,
                SUM(CASE WHEN players > 0 THEN 1 ELSE 0 END) AS online_polls
            FROM tracker_server_history FORCE INDEX (tracker_server_history_polled_at_server_id_index)
            WHERE polled_at >= ?
            GROUP BY server_id
        ', [$since]);

        $historyStats = collect($historyRows)->keyBy('server_id');

        $sessionStats = DB::table('tracker_player_sessions')
            ->where('started_at', '>=', $since)
            ->selectRaw('
                server_id,
                SUM(duration_minutes) as total_playtime,
                COUNT(DISTINCT player_id) as unique_players
            ')
            ->groupBy('server_id')
            ->get()
            ->keyBy('server_id');

        $servers = DB::table('tracker_servers')
            ->select('id', 'game_id')
            ->get();

        $gameBuckets = [];
        foreach ($servers as $srv) {
            $h = $historyStats->get($srv->id);
            $s = $sessionStats->get($srv->id);

            $avg = $h ? (float) $h->avg_players : 0.0;
            $playtime = $s ? (int) $s->total_playtime : 0;

            if ($avg < 0.01 && $playtime === 0) {
                continue;
            }

            $gameBuckets[$srv->game_id][] = [
                'server_id'                  => $srv->id,
                'game_id'                    => $srv->game_id,
                'avg_players_30d'            => round($avg, 2),
                'peak_players_30d'           => $h ? (int) $h->peak_players : 0,
                'polls_counted'              => $h ? (int) $h->total_polls : 0,
                'total_polls_30d'            => $h ? (int) $h->total_polls : 0,
                'online_polls_30d'           => $h ? (int) $h->online_polls : 0,
                'total_playtime_minutes_30d' => $playtime,
                'unique_players_30d'         => $s ? (int) $s->unique_players : 0,
                'computed_at'                => $computedAt,
            ];
        }

        $allRows = [];
        foreach ($gameBuckets as $gameId => $rows) {
            usort($rows, fn ($a, $b) => $b['avg_players_30d'] <=> $a['avg_players_30d']);
            $total = count($rows);
            foreach ($rows as $i => $row) {
                $row['rank']          = $i + 1;
                $row['total_in_game'] = $total;
                $allRows[]            = $row;
            }
        }

        DB::table('tracker_server_rankings')->truncate();
        foreach (array_chunk($allRows, 500) as $chunk) {
            DB::table('tracker_server_rankings')->insert($chunk);
        }

        return count($allRows);
    }

    /**
     * Rebuild tracker_player_rankings_30d.
     *
     * Note: tracker_player_sessions.kills / deaths / xp are currently unreliable
     * (0% of sessions have deaths > 0; kills have extreme outliers up to 259M per player).
     * Only playtime, session count, unique servers, unique maps, and elo_rating (from
     * tracker_players, not sessions) are used.
     */
    public function recalculatePlayers(): int
    {
        $since = now()->subDays(30);
        $computedAt = now();

        $query = DB::table('tracker_player_sessions as s')
            ->join('tracker_players as p', 'p.id', '=', 's.player_id')
            ->where('s.started_at', '>=', $since)
            ->where('p.status', 'active')
            ->where(function ($q) {
                $q->where('p.is_bot', 0)->orWhereNull('p.is_bot');
            })
            ->whereNotIn('p.name_clean', self::EXCLUDED_EXACT_NAMES);

        // Exclude TV/Spectator/Twitch patterns (contains)
        foreach (self::EXCLUDED_NAME_PATTERNS as $pattern) {
            $query->where('p.name_clean', 'NOT LIKE', '%' . $pattern . '%');
        }

        // Exclude [BOT] prefix (Omni-Bot AI)
        foreach (self::EXCLUDED_NAME_PREFIXES as $prefix) {
            $query->where('p.name_clean', 'NOT LIKE', $prefix . '%');
        }

        $stats = $query->selectRaw('
                s.player_id,
                s.game_id,
                p.elo_rating,
                SUM(s.duration_minutes) as playtime,
                COUNT(*) as sessions,
                COUNT(DISTINCT s.server_id) as unique_servers,
                COUNT(DISTINCT s.map_name) as unique_maps
            ')
            ->groupBy('s.player_id', 's.game_id', 'p.elo_rating')
            ->havingRaw('SUM(s.duration_minutes) > 0')
            ->get();

        $gameBuckets = [];
        foreach ($stats as $r) {
            $gameBuckets[$r->game_id][] = [
                'player_id'            => $r->player_id,
                'game_id'              => $r->game_id,
                'playtime_minutes_30d' => (int) $r->playtime,
                'sessions_count_30d'   => (int) $r->sessions,
                'kills_30d'            => 0, // data unreliable, kept for schema compat
                'deaths_30d'           => 0, // always 0 in source data
                'xp_30d'               => 0, // data unreliable, kept for schema compat
                'unique_servers_30d'   => (int) $r->unique_servers,
                'unique_maps_30d'      => (int) $r->unique_maps,
                'elo_rating'           => $r->elo_rating !== null ? (int) $r->elo_rating : null,
                'computed_at'          => $computedAt,
            ];
        }

        $allRows = [];
        foreach ($gameBuckets as $gameId => $rows) {
            usort($rows, fn ($a, $b) => $b['playtime_minutes_30d'] <=> $a['playtime_minutes_30d']);
            $total = count($rows);
            foreach ($rows as $i => $row) {
                $row['rank']          = $i + 1;
                $row['total_in_game'] = $total;
                $allRows[]            = $row;
            }
        }

        DB::table('tracker_player_rankings_30d')->truncate();
        foreach (array_chunk($allRows, 500) as $chunk) {
            DB::table('tracker_player_rankings_30d')->insert($chunk);
        }

        return count($allRows);
    }
}
