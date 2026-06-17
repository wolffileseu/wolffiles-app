<?php

namespace App\Http\Controllers\Api\V1\Tracker;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tracker API -- Phase 2b: leaderboards (read-only, keyless).
 *
 * alltime boards come from lifetime totals on tracker_players (complete,
 * indexed). Time-windowed boards (daily/weekly/monthly) come from
 * tracker_rankings, scoped to the latest period_date for that period --
 * because alltime rows in tracker_rankings only carry the date a player was
 * last recomputed, so filtering alltime by MAX(period_date) would drop the
 * actual top players who were not active that day.
 *
 * Only metrics the aggregates cleanly support are exposed.
 */
class LeaderboardController extends Controller
{
    /** metric => [players column, rankings column|null (null = alltime only)] */
    private const METRICS = [
        'elo'       => ['players' => 'elo_rating',               'rankings' => 'elo_rating'],
        'xp'        => ['players' => 'total_xp',                 'rankings' => 'total_xp'],
        'kills'     => ['players' => 'total_kills',              'rankings' => 'kills'],
        'deaths'    => ['players' => 'total_deaths',             'rankings' => 'deaths'],
        'playtime'  => ['players' => 'total_play_time_minutes',  'rankings' => 'playtime_minutes'],
        'headshots' => ['players' => 'enhanced_total_headshots', 'rankings' => null],
    ];

    private const PERIODS = ['alltime', 'daily', 'weekly', 'monthly'];

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'metrics' => array_keys(self::METRICS),
                'periods' => self::PERIODS,
                'notes'   => [
                    'headshots'      => 'available for the alltime period only',
                    'default_period' => 'alltime',
                ],
            ],
        ]);
    }

    public function leaderboard(string $metric, Request $request): JsonResponse
    {
        if (! isset(self::METRICS[$metric])) {
            return response()->json([
                'error'   => 'unknown_metric',
                'allowed' => array_keys(self::METRICS),
            ], 400);
        }

        $period = (string) $request->query('period', 'alltime');
        if (! in_array($period, self::PERIODS, true)) {
            return response()->json([
                'error'   => 'unknown_period',
                'allowed' => self::PERIODS,
            ], 400);
        }

        $limit  = $this->limit($request, 25, 100);
        $offset = max(0, (int) $request->query('offset', 0));

        if ($period === 'alltime') {
            [$data, $hasMore] = $this->alltime($metric, $limit, $offset);
            $periodDate = null;
        } else {
            if (self::METRICS[$metric]['rankings'] === null) {
                return response()->json([
                    'error'  => 'metric_not_available_for_period',
                    'detail' => "metric '{$metric}' is only available for period 'alltime'",
                ], 400);
            }
            [$data, $hasMore, $periodDate] = $this->windowed($metric, $period, $limit, $offset);
        }

        return response()->json([
            'data' => $data,
            'meta' => [
                'metric'      => $metric,
                'period'      => $period,
                'period_date' => $periodDate,
                'count'       => count($data),
                'limit'       => $limit,
                'offset'      => $offset,
                'has_more'    => $hasMore,
            ],
        ]);
    }

    private function alltime(string $metric, int $limit, int $offset): array
    {
        $col = self::METRICS[$metric]['players'];

        $q = DB::table('tracker_players as p')
            ->where('p.is_bot', 0)
            ->where('p.status', '!=', 'hidden')
            ->whereNull('p.merged_into');

        if ($metric === 'elo') {
            $q->whereNotNull('p.elo_rating')->where('p.elo_games', '>', 0);
        } elseif ($metric === 'headshots') {
            $q->where('p.has_enhanced_data', 1);
        }

        $rows = $q->orderByDesc("p.$col")
            ->offset($offset)->limit($limit + 1)
            ->get([
                'p.id', 'p.name', 'p.name_clean', 'p.name_html', 'p.country_code',
                'p.elo_rating', 'p.total_xp', 'p.total_kills', 'p.total_deaths',
                'p.total_play_time_minutes', 'p.enhanced_total_headshots', 'p.has_enhanced_data',
            ]);

        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit)->values();

        $data = $rows->map(function ($r, $i) use ($metric, $offset) {
            $value = match ($metric) {
                'elo'       => $r->elo_rating !== null ? (float) $r->elo_rating : null,
                'xp'        => (int) $r->total_xp,
                'kills'     => (int) $r->total_kills,
                'deaths'    => (int) $r->total_deaths,
                'playtime'  => (int) $r->total_play_time_minutes,
                'headshots' => (int) $r->enhanced_total_headshots,
                default     => null,
            };

            return [
                'position' => $offset + $i + 1,
                'player'   => [
                    'id'           => (int) $r->id,
                    'name'         => $r->name,
                    'name_clean'   => $r->name_clean,
                    'name_html'    => $r->name_html,
                    'country_code' => $r->country_code,
                ],
                'value' => $value,
                'stats' => [
                    'elo'              => $r->elo_rating !== null ? (float) $r->elo_rating : null,
                    'xp'               => (int) $r->total_xp,
                    'kills'            => (int) $r->total_kills,
                    'deaths'           => (int) $r->total_deaths,
                    'playtime_minutes' => (int) $r->total_play_time_minutes,
                    'headshots'        => $r->has_enhanced_data ? (int) $r->enhanced_total_headshots : null,
                ],
            ];
        })->all();

        return [$data, $hasMore];
    }

    private function windowed(string $metric, string $period, int $limit, int $offset): array
    {
        $col = self::METRICS[$metric]['rankings'];

        $latest = DB::table('tracker_rankings')->where('period', $period)->max('period_date');
        if (! $latest) {
            return [[], false, null];
        }

        $rows = DB::table('tracker_rankings as r')
            ->join('tracker_players as p', 'p.id', '=', 'r.player_id')
            ->where('r.period', $period)
            ->where('r.period_date', $latest)
            ->where('p.is_bot', 0)
            ->where('p.status', '!=', 'hidden')
            ->whereNull('p.merged_into')
            ->orderByDesc("r.$col")
            ->offset($offset)->limit($limit + 1)
            ->get([
                'r.player_id as id', 'p.name', 'p.name_clean', 'p.name_html', 'p.country_code',
                'r.elo_rating', 'r.total_xp', 'r.kills', 'r.deaths', 'r.playtime_minutes', 'r.sessions_count',
            ]);

        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit)->values();

        $data = $rows->map(function ($r, $i) use ($metric, $offset) {
            $value = match ($metric) {
                'elo'      => $r->elo_rating !== null ? (float) $r->elo_rating : null,
                'xp'       => (int) $r->total_xp,
                'kills'    => (int) $r->kills,
                'deaths'   => (int) $r->deaths,
                'playtime' => (int) $r->playtime_minutes,
                default    => null,
            };

            return [
                'position' => $offset + $i + 1,
                'player'   => [
                    'id'           => (int) $r->id,
                    'name'         => $r->name,
                    'name_clean'   => $r->name_clean,
                    'name_html'    => $r->name_html,
                    'country_code' => $r->country_code,
                ],
                'value' => $value,
                'stats' => [
                    'elo'              => $r->elo_rating !== null ? (float) $r->elo_rating : null,
                    'xp'               => (int) $r->total_xp,
                    'kills'            => (int) $r->kills,
                    'deaths'           => (int) $r->deaths,
                    'playtime_minutes' => (int) $r->playtime_minutes,
                    'sessions'         => (int) $r->sessions_count,
                ],
            ];
        })->all();

        return [$data, $hasMore, $latest];
    }

    private function limit(Request $request, int $default, int $max): int
    {
        $n = (int) $request->query('limit', $default);

        return max(1, min($n, $max));
    }
}
