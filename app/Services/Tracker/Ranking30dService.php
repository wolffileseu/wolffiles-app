<?php

namespace App\Services\Tracker;

use Illuminate\Support\Facades\DB;

class Ranking30dService
{
    /** Game-family mapping from game_id to family code. */
    private const FAMILY_MAP = [
        1 => 'et',  2 => 'et',  3 => 'et',  4 => 'et',  5 => 'et',
        6 => 'rtcw', 7 => 'rtcw', 8 => 'rtcw', 9 => 'rtcw', 10 => 'rtcw',
    ];

    private const EXCLUDED_NAME_PATTERNS = [
        'TV',           // ETTV streaming clients
        'spectator',    // Spectator clients
        'twitch.tv',    // Twitch streamers
    ];

    private const EXCLUDED_NAME_PREFIXES = [
        '[BOT]',        // Omni-Bot AI players
    ];

    private const EXCLUDED_EXACT_NAMES = [
        'ETPlayer', 'WolfPlayer', 'Player', 'UnnamedPlayer', 'unknown',
    ];

    /**
     * Map a game_id to its family (et / rtcw), or null if unmapped.
     */
    private function familyFor(int $gameId): ?string
    {
        return self::FAMILY_MAP[$gameId] ?? null;
    }

    /**
     * Rebuild tracker_server_rankings — one ranked row per server, ranked within its family.
     * Uses the server\'s primary game_id to bucket into a family.
     */
    public function recalculateServers(): int
    {
        $since = now()->subDays(30);
        $computedAt = now();

        $historyRows = DB::select('
            SELECT server_id, AVG(players) AS avg_players, MAX(players) AS peak_players,
                   COUNT(*) AS total_polls, SUM(CASE WHEN players > 0 THEN 1 ELSE 0 END) AS online_polls
            FROM tracker_server_history FORCE INDEX (tracker_server_history_polled_at_server_id_index)
            WHERE polled_at >= ?
            GROUP BY server_id
        ', [$since]);
        $historyStats = collect($historyRows)->keyBy('server_id');

        $sessionStats = DB::table('tracker_player_sessions')
            ->where('started_at', '>=', $since)
            ->selectRaw('server_id, SUM(duration_minutes) as total_playtime, COUNT(DISTINCT player_id) as unique_players')
            ->groupBy('server_id')->get()->keyBy('server_id');

        $servers = DB::table('tracker_servers')->select('id', 'game_id')->get();

        $familyBuckets = [];
        foreach ($servers as $srv) {
            $family = $this->familyFor((int) $srv->game_id);
            if ($family === null) {
                continue;
            }

            $h = $historyStats->get($srv->id);
            $s = $sessionStats->get($srv->id);

            $avg = $h ? (float) $h->avg_players : 0.0;
            $playtime = $s ? (int) $s->total_playtime : 0;

            if ($avg < 0.01 && $playtime === 0) {
                continue;
            }

            $familyBuckets[$family][] = [
                'server_id'                  => $srv->id,
                'game_id'                    => $srv->game_id,
                'game_family'                => $family,
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
        foreach ($familyBuckets as $family => $rows) {
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
     * Rebuild tracker_player_rankings_30d — one row per (player, family).
     * Playtime / sessions / unique_servers / unique_maps are aggregated ACROSS
     * all games in the family, so a player who spans ET 2.55 and 2.60b is
     * ranked once with combined stats.
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

        foreach (self::EXCLUDED_NAME_PATTERNS as $pattern) {
            $query->where('p.name_clean', 'NOT LIKE', '%' . $pattern . '%');
        }
        foreach (self::EXCLUDED_NAME_PREFIXES as $prefix) {
            $query->where('p.name_clean', 'NOT LIKE', $prefix . '%');
        }

        // Still aggregate per game_id first, then merge to family in PHP (cheaper than CASE in SQL).
        $stats = $query->selectRaw('
                s.player_id, s.game_id, p.elo_rating,
                SUM(s.duration_minutes) as playtime,
                COUNT(*) as sessions,
                s.server_id, s.map_name
            ')
            ->groupBy('s.player_id', 's.game_id', 'p.elo_rating', 's.server_id', 's.map_name')
            ->havingRaw('SUM(s.duration_minutes) > 0')
            ->get();

        // Merge per (player_id, family): sum playtime+sessions, distinct servers/maps.
        // Key: "player_id:family" → row
        $merged = [];
        foreach ($stats as $r) {
            $family = $this->familyFor((int) $r->game_id);
            if ($family === null) {
                continue;
            }
            $key = $r->player_id . ':' . $family;
            if (! isset($merged[$key])) {
                $merged[$key] = [
                    'player_id'            => $r->player_id,
                    'game_family'          => $family,
                    'game_id'              => (int) $r->game_id,
                    'playtime_minutes_30d' => 0,
                    'sessions_count_30d'   => 0,
                    'elo_rating'           => $r->elo_rating !== null ? (int) $r->elo_rating : null,
                    '_servers'             => [],
                    '_maps'                => [],
                ];
            }
            $merged[$key]['playtime_minutes_30d'] += (int) $r->playtime;
            $merged[$key]['sessions_count_30d']   += (int) $r->sessions;
            if ($r->server_id) {
                $merged[$key]['_servers'][$r->server_id] = true;
            }
            if ($r->map_name) {
                $merged[$key]['_maps'][$r->map_name] = true;
            }
        }

        // Bucket per family & rank
        $familyBuckets = [];
        foreach ($merged as $row) {
            $row['unique_servers_30d'] = count($row['_servers']);
            $row['unique_maps_30d']    = count($row['_maps']);
            unset($row['_servers'], $row['_maps']);

            $row['kills_30d']   = 0; // data unreliable
            $row['deaths_30d']  = 0; // always 0 in source
            $row['xp_30d']      = 0;
            $row['computed_at'] = $computedAt;

            $familyBuckets[$row['game_family']][] = $row;
        }

        $allRows = [];
        foreach ($familyBuckets as $family => $rows) {
            // Rank by ELO (highest first). Players with no ELO or below the
            // minimum playtime threshold are 'unrated' and sorted to the end,
            // ordered by playtime among themselves. This keeps low-sample
            // ELO spikes from outranking established regulars.
            $minPlaytime = 300; // minutes within the 30d window to be ELO-ranked
            usort($rows, function ($a, $b) use ($minPlaytime) {
                $aRated = $a['elo_rating'] !== null && $a['playtime_minutes_30d'] >= $minPlaytime;
                $bRated = $b['elo_rating'] !== null && $b['playtime_minutes_30d'] >= $minPlaytime;
                if ($aRated && $bRated) {
                    return ($b['elo_rating'] <=> $a['elo_rating'])
                        ?: ($b['playtime_minutes_30d'] <=> $a['playtime_minutes_30d']);
                }
                if ($aRated) return -1; // rated players ahead of unrated
                if ($bRated) return 1;
                // both unrated -> fall back to playtime
                return $b['playtime_minutes_30d'] <=> $a['playtime_minutes_30d'];
            });
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
