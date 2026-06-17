<?php

namespace App\Http\Controllers\Api\V1\Tracker;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tracker API -- Phase 4: maps (read-only, keyless).
 *
 * Play stats are computed live from tracker_server_map_stats (SUM/COUNT/MAX)
 * because the rollup columns on tracker_maps (total_time_played_minutes,
 * total_unique_players, peak_concurrent_players, total_sessions) are never
 * populated -- no job/service writes them. unique_players and sessions are
 * therefore not exposed; avg_players is only meaningful per-server.
 *
 * Note: GET /tracker/maps/{mapName} (live "who plays it now") already exists
 * in TrackerController@apiMapStats and is unaffected.
 *
 * screenshot_path is returned raw (as stored); a full screenshot_url can be
 * added additively once the frontend URL builder is wired in.
 */
class MapController extends Controller
{
    /** sort key => aggregate alias from the tracker_server_map_stats rollup */
    private const SORTS = [
        'time'    => 'time_minutes',
        'played'  => 'times_played',
        'servers' => 'servers',
        'peak'    => 'peak_players',
        'recent'  => 'last_played_at',
    ];

    /** GET /api/v1/tracker/maps */
    public function index(Request $request): JsonResponse
    {
        $limit  = $this->limit($request, 50, 200);
        $offset = max(0, (int) $request->query('offset', 0));

        $sortKey = (string) $request->query('sort', 'time');
        $sortCol = self::SORTS[$sortKey] ?? self::SORTS['time'];

        $agg = DB::table('tracker_server_map_stats')
            ->selectRaw(
                'map_name, COUNT(*) as servers, SUM(times_played) as times_played, '
                .'SUM(total_time_minutes) as time_minutes, MAX(peak_players) as peak_players, '
                .'MAX(last_played_at) as last_played_at'
            )
            ->groupBy('map_name');

        $query = DB::table('tracker_maps as m')
            ->joinSub($agg, 'a', 'a.map_name', '=', 'm.name');

        if ($request->filled('q')) {
            $query->where('m.name_clean', 'like', '%'.((string) $request->query('q')).'%');
        }

        $rows = $query
            ->orderByDesc("a.$sortCol")
            ->offset($offset)->limit($limit + 1)
            ->get([
                'm.id', 'm.name', 'm.name_clean', 'm.file_id', 'm.screenshot_path',
                'm.first_seen_at', 'm.last_seen_at',
                'a.servers', 'a.times_played', 'a.time_minutes', 'a.peak_players', 'a.last_played_at',
            ]);

        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit);

        $data = $rows->map(fn ($m) => $this->mapShape($m));

        return response()->json([
            'data' => $data,
            'meta' => [
                'count'    => $data->count(),
                'limit'    => $limit,
                'offset'   => $offset,
                'sort'     => array_key_exists($sortKey, self::SORTS) ? $sortKey : 'time',
                'has_more' => $hasMore,
            ],
        ]);
    }

    /** GET /api/v1/tracker/maps/{name}/stats */
    public function show(string $name, Request $request): JsonResponse
    {
        $map = DB::table('tracker_maps')
            ->where(function ($q) use ($name) {
                $q->where('name', $name)->orWhere('name_clean', $name);
            })
            ->first([
                'id', 'name', 'name_clean', 'file_id', 'screenshot_path',
                'first_seen_at', 'last_seen_at',
            ]);

        if (! $map) {
            return response()->json(['error' => 'map_not_found'], 404);
        }

        $agg = DB::table('tracker_server_map_stats')
            ->where('map_name', $map->name)
            ->selectRaw(
                'COUNT(*) as servers, SUM(times_played) as times_played, '
                .'SUM(total_time_minutes) as time_minutes, MAX(peak_players) as peak_players, '
                .'MAX(last_played_at) as last_played_at'
            )
            ->first();

        $row = (object) array_merge((array) $map, (array) $agg);

        $serverLimit = $this->limit($request, 10, 50);

        $servers = DB::table('tracker_server_map_stats as sms')
            ->leftJoin('tracker_servers as s', 's.id', '=', 'sms.server_id')
            ->where('sms.map_name', $map->name)
            ->orderByDesc('sms.total_time_minutes')
            ->limit($serverLimit)
            ->get([
                'sms.server_id', 's.hostname_clean as server_name',
                'sms.times_played', 'sms.total_time_minutes', 'sms.avg_players',
                'sms.peak_players', 'sms.last_played_at',
            ])
            ->map(fn ($r) => [
                'server'             => ['id' => (int) $r->server_id, 'name' => $r->server_name],
                'times_played'       => (int) $r->times_played,
                'total_time_minutes' => (int) $r->total_time_minutes,
                'avg_players'        => (float) $r->avg_players,
                'peak_players'       => (int) $r->peak_players,
                'last_played_at'     => $r->last_played_at,
            ]);

        return response()->json([
            'data' => [
                'map'         => $this->mapShape($row),
                'top_servers' => $servers,
            ],
            'meta' => ['server_count' => $servers->count()],
        ]);
    }

    /** GET /api/v1/tracker/servers/{id}/maps */
    public function serverMaps(int $id, Request $request): JsonResponse
    {
        if (! DB::table('tracker_servers')->where('id', $id)->exists()) {
            return response()->json(['error' => 'server_not_found'], 404);
        }

        $limit  = $this->limit($request, 50, 200);
        $offset = max(0, (int) $request->query('offset', 0));

        $rows = DB::table('tracker_server_map_stats')
            ->where('server_id', $id)
            ->orderByDesc('total_time_minutes')
            ->offset($offset)->limit($limit + 1)
            ->get(['map_name', 'times_played', 'total_time_minutes', 'avg_players', 'peak_players', 'last_played_at']);

        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit);

        $data = $rows->map(fn ($r) => [
            'map_name'           => $r->map_name,
            'times_played'       => (int) $r->times_played,
            'total_time_minutes' => (int) $r->total_time_minutes,
            'avg_players'        => (float) $r->avg_players,
            'peak_players'       => (int) $r->peak_players,
            'last_played_at'     => $r->last_played_at,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'server_id' => $id,
                'count'     => $data->count(),
                'limit'     => $limit,
                'offset'    => $offset,
                'has_more'  => $hasMore,
            ],
        ]);
    }

    private function mapShape(object $m): array
    {
        return [
            'id'              => (int) $m->id,
            'name'            => $m->name,
            'name_clean'      => $m->name_clean,
            'file_id'         => $m->file_id !== null ? (int) $m->file_id : null,
            'screenshot_path' => $m->screenshot_path,
            'stats' => [
                'servers'             => (int) $m->servers,
                'times_played'        => (int) $m->times_played,
                'time_played_minutes' => (int) $m->time_minutes,
                'peak_players'        => (int) $m->peak_players,
                'last_played_at'      => $m->last_played_at,
            ],
            'first_seen_at' => $m->first_seen_at,
            'last_seen_at'  => $m->last_seen_at,
        ];
    }

    private function limit(Request $request, int $default, int $max): int
    {
        $n = (int) $request->query('limit', $default);

        return max(1, min($n, $max));
    }
}
