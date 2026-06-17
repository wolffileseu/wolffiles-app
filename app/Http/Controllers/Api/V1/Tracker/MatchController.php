<?php

namespace App\Http\Controllers\Api\V1\Tracker;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Tracker\Concerns\ResolvesTrackerPlayer;
use App\Services\Tracker\WeaponRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tracker API -- Phase 2a: matches (read-only, keyless).
 *
 * Scoreboard data comes from the relational tracker_player_match_stats
 * table (not the final_scoreboard JSON blob). team/class are returned as
 * raw ET enum ints; labels can be wired to a canonical registry later.
 */
class MatchController extends Controller
{
    use ResolvesTrackerPlayer;

    /** GET /api/v1/tracker/matches */
    public function index(Request $request): JsonResponse
    {
        $limit  = $this->limit($request, 25, 100);
        $offset = max(0, (int) $request->query('offset', 0));

        $query = DB::table('tracker_matches as m')
            ->leftJoin('tracker_servers as s', 's.id', '=', 'm.server_id')
            ->orderByDesc('m.started_at');

        if ($request->filled('server_id')) {
            $query->where('m.server_id', (int) $request->query('server_id'));
        }
        if ($request->filled('map')) {
            $query->where('m.map_name', $request->query('map'));
        }

        // fetch limit + 1 to compute has_more without an expensive COUNT
        $rows = $query->offset($offset)->limit($limit + 1)->get([
            'm.id', 'm.server_id', 's.hostname_clean as server_name',
            'm.map_name', 'm.started_at', 'm.ended_at', 'm.duration_seconds',
            'm.end_reason', 'm.player_count_max', 'm.player_count_avg',
            'm.players_at_start', 'm.players_at_end', 'm.total_kills', 'm.total_deaths',
        ]);

        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit);

        $data = $rows->map(fn ($m) => [
            'id'       => (int) $m->id,
            'server'   => ['id' => (int) $m->server_id, 'name' => $m->server_name],
            'map_name' => $m->map_name,
            'started_at'       => $m->started_at,
            'ended_at'         => $m->ended_at,
            'duration_seconds' => $m->duration_seconds !== null ? (int) $m->duration_seconds : null,
            'end_reason'       => $m->end_reason,
            'players' => [
                'max'      => (int) $m->player_count_max,
                'avg'      => (float) $m->player_count_avg,
                'at_start' => $m->players_at_start !== null ? (int) $m->players_at_start : null,
                'at_end'   => $m->players_at_end !== null ? (int) $m->players_at_end : null,
            ],
            'totals' => ['kills' => (int) $m->total_kills, 'deaths' => (int) $m->total_deaths],
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'count'    => $data->count(),
                'limit'    => $limit,
                'offset'   => $offset,
                'has_more' => $hasMore,
            ],
        ]);
    }

    /** GET /api/v1/tracker/matches/{id} */
    public function show(int $id): JsonResponse
    {
        $m = DB::table('tracker_matches as m')
            ->leftJoin('tracker_servers as s', 's.id', '=', 'm.server_id')
            ->where('m.id', $id)
            ->first([
                'm.id', 'm.server_id', 's.hostname_clean as server_name',
                'm.map_name', 'm.started_at', 'm.ended_at', 'm.duration_seconds',
                'm.end_reason', 'm.player_count_max', 'm.player_count_avg',
                'm.players_at_start', 'm.players_at_end',
                'm.allies_at_start', 'm.axis_at_start', 'm.spec_at_start',
                'm.allies_at_end', 'm.axis_at_end', 'm.spec_at_end',
                'm.total_kills', 'm.total_deaths',
            ]);

        if (! $m) {
            return response()->json(['error' => 'match_not_found'], 404);
        }

        $players = DB::table('tracker_player_match_stats')
            ->where('match_id', $id)
            ->orderByDesc('score')
            ->orderByDesc('kills')
            ->get([
                'player_id', 'name_snapshot', 'name_clean_snapshot', 'slot', 'team', 'class',
                'kills', 'deaths', 'headshots', 'gibs', 'suicides', 'team_kills',
                'kill_assists', 'revives_given', 'revives_received', 'objectives_taken',
                'damage_given', 'damage_received', 'accuracy_pct', 'score',
                'ping_avg', 'playtime_seconds', 'time_played_pct',
            ])
            ->map(fn ($p) => [
                'player_id'  => (int) $p->player_id,
                'name'       => $p->name_snapshot,
                'name_clean' => $p->name_clean_snapshot,
                'slot'       => $p->slot !== null ? (int) $p->slot : null,
                'team'       => $p->team !== null ? (int) $p->team : null,
                'class'      => $p->class !== null ? (int) $p->class : null,
                'kills'      => (int) $p->kills,
                'deaths'     => (int) $p->deaths,
                'headshots'  => (int) $p->headshots,
                'gibs'       => (int) $p->gibs,
                'suicides'   => (int) $p->suicides,
                'team_kills' => (int) $p->team_kills,
                'kill_assists'     => (int) $p->kill_assists,
                'revives_given'    => (int) $p->revives_given,
                'revives_received' => (int) $p->revives_received,
                'objectives_taken' => (int) $p->objectives_taken,
                'damage_given'     => (int) $p->damage_given,
                'damage_received'  => (int) $p->damage_received,
                'accuracy_pct'     => $p->accuracy_pct !== null ? (float) $p->accuracy_pct : null,
                'score'            => (int) $p->score,
                'ping_avg'         => (int) $p->ping_avg,
                'playtime_seconds' => (int) $p->playtime_seconds,
                'time_played_pct'  => $p->time_played_pct !== null ? (float) $p->time_played_pct : null,
            ]);

        return response()->json([
            'data' => [
                'match' => [
                    'id'       => (int) $m->id,
                    'server'   => ['id' => (int) $m->server_id, 'name' => $m->server_name],
                    'map_name' => $m->map_name,
                    'started_at'       => $m->started_at,
                    'ended_at'         => $m->ended_at,
                    'duration_seconds' => $m->duration_seconds !== null ? (int) $m->duration_seconds : null,
                    'end_reason'       => $m->end_reason,
                    'players' => [
                        'max' => (int) $m->player_count_max,
                        'avg' => (float) $m->player_count_avg,
                        'at_start' => [
                            'total'     => $m->players_at_start !== null ? (int) $m->players_at_start : null,
                            'allies'    => $m->allies_at_start !== null ? (int) $m->allies_at_start : null,
                            'axis'      => $m->axis_at_start !== null ? (int) $m->axis_at_start : null,
                            'spectator' => $m->spec_at_start !== null ? (int) $m->spec_at_start : null,
                        ],
                        'at_end' => [
                            'total'     => $m->players_at_end !== null ? (int) $m->players_at_end : null,
                            'allies'    => $m->allies_at_end !== null ? (int) $m->allies_at_end : null,
                            'axis'      => $m->axis_at_end !== null ? (int) $m->axis_at_end : null,
                            'spectator' => $m->spec_at_end !== null ? (int) $m->spec_at_end : null,
                        ],
                    ],
                    'totals' => ['kills' => (int) $m->total_kills, 'deaths' => (int) $m->total_deaths],
                ],
                'scoreboard' => $players,
            ],
            'meta' => ['player_count' => $players->count()],
        ]);
    }

    /** GET /api/v1/tracker/matches/{id}/weapons */
    public function matchWeapons(int $id): JsonResponse
    {
        if (! DB::table('tracker_matches')->where('id', $id)->exists()) {
            return response()->json(['error' => 'match_not_found'], 404);
        }

        $names = DB::table('tracker_player_match_stats')
            ->where('match_id', $id)
            ->get(['player_id', 'name_snapshot', 'name_clean_snapshot', 'kills'])
            ->keyBy('player_id');

        $rows = DB::table('tracker_match_player_weapon_stats')
            ->where('match_id', $id)
            ->orderByDesc('kills')
            ->get(['player_id', 'weapon_bit', 'hits', 'atts', 'kills', 'deaths', 'headshots', 'accuracy_bp']);

        $byPlayer = [];
        foreach ($rows as $w) {
            $pid = (int) $w->player_id;
            if (! isset($byPlayer[$pid])) {
                $info = $names->get($pid);
                $byPlayer[$pid] = [
                    'player_id'  => $pid,
                    'name'       => $info?->name_snapshot,
                    'name_clean' => $info?->name_clean_snapshot,
                    'kills'      => $info !== null ? (int) $info->kills : null,
                    'weapons'    => [],
                ];
            }

            $bit  = (int) $w->weapon_bit;
            $meta = WeaponRegistry::get($bit);
            $accuracy = $w->atts > 0
                ? round($w->hits / $w->atts * 100, 2)
                : ($w->accuracy_bp > 0 ? round($w->accuracy_bp / 100, 2) : 0.0);

            $byPlayer[$pid]['weapons'][] = [
                'weapon_bit' => $bit,
                'weapon'     => $meta ? [
                    'name'     => $meta['name'],
                    'slug'     => $meta['slug'],
                    'category' => $meta['category'],
                    'side'     => $meta['side'],
                    'icon'     => WeaponRegistry::iconUrl($bit),
                ] : null,
                'hits'      => (int) $w->hits,
                'attempts'  => (int) $w->atts,
                'kills'     => (int) $w->kills,
                'deaths'    => (int) $w->deaths,
                'headshots' => (int) $w->headshots,
                'accuracy'  => $accuracy,
            ];
        }

        $data = array_values($byPlayer);
        usort($data, fn ($a, $b) => ($b['kills'] ?? 0) <=> ($a['kills'] ?? 0));

        return response()->json([
            'data' => $data,
            'meta' => ['match_id' => $id, 'player_count' => count($data)],
        ]);
    }

    /** GET /api/v1/tracker/players/{id}/matches */
    public function playerMatches(int $id, Request $request): JsonResponse
    {
        $player = $this->resolvePlayer($id);
        if (! $player) {
            return response()->json(['error' => 'player_not_found'], 404);
        }

        $limit  = $this->limit($request, 25, 100);
        $offset = max(0, (int) $request->query('offset', 0));

        $rows = DB::table('tracker_player_match_stats as ps')
            ->leftJoin('tracker_matches as m', 'm.id', '=', 'ps.match_id')
            ->leftJoin('tracker_servers as s', 's.id', '=', 'ps.server_id')
            ->where('ps.player_id', $player->id)
            ->orderByDesc('ps.created_at')
            ->offset($offset)->limit($limit + 1)
            ->get([
                'ps.match_id', 'ps.server_id', 's.hostname_clean as server_name',
                'm.map_name', 'm.started_at',
                'ps.team', 'ps.class', 'ps.kills', 'ps.deaths', 'ps.headshots',
                'ps.gibs', 'ps.score', 'ps.accuracy_pct', 'ps.playtime_seconds',
            ]);

        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit);

        $data = $rows->map(fn ($r) => [
            'match_id'   => (int) $r->match_id,
            'map_name'   => $r->map_name,
            'server'     => ['id' => (int) $r->server_id, 'name' => $r->server_name],
            'started_at' => $r->started_at,
            'team'       => $r->team !== null ? (int) $r->team : null,
            'class'      => $r->class !== null ? (int) $r->class : null,
            'kills'      => (int) $r->kills,
            'deaths'     => (int) $r->deaths,
            'headshots'  => (int) $r->headshots,
            'gibs'       => (int) $r->gibs,
            'score'      => (int) $r->score,
            'accuracy_pct'     => $r->accuracy_pct !== null ? (float) $r->accuracy_pct : null,
            'playtime_seconds' => (int) $r->playtime_seconds,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'player_id' => $player->id,
                'count'     => $data->count(),
                'limit'     => $limit,
                'offset'    => $offset,
                'has_more'  => $hasMore,
            ],
        ]);
    }
}
