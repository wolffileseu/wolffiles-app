<?php

namespace App\Http\Controllers\Api\V1\Tracker;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Tracker\Concerns\ResolvesTrackerPlayer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tracker API -- Phase 5: kill feed (read-only, keyless).
 *
 * Sourced from tracker_rtcw_kills, which is RtCW-only (ET kills are not
 * stored as individual events, only in the 7GB raw_events firehose and in
 * aggregate weapon stats). All responses carry meta.source = "rtcw"; ET
 * players/servers/matches simply return an empty feed.
 *
 * weapon_key is already a human-readable label as stored; mod_index is the
 * raw RtCW means-of-death enum, passed through untouched.
 */
class KillController extends Controller
{
    use ResolvesTrackerPlayer;

    /** GET /api/v1/tracker/players/{id}/kills  (?role=kills|deaths, ?exclude_bots=1) */
    public function playerKills(int $id, Request $request): JsonResponse
    {
        $player = $this->resolvePlayer($id);
        if (! $player) {
            return response()->json(['error' => 'player_not_found'], 404);
        }

        $role = (string) $request->query('role', 'kills');
        if (! in_array($role, ['kills', 'deaths'], true)) {
            return response()->json(['error' => 'unknown_role', 'allowed' => ['kills', 'deaths']], 400);
        }
        $col = $role === 'deaths' ? 'k.victim_player_id' : 'k.killer_player_id';

        $limit  = $this->limit($request, 50, 200);
        $offset = max(0, (int) $request->query('offset', 0));

        $query = $this->baseQuery()->where($col, $player->id);
        $this->applyBotFilter($query, $request);

        $rows = $query->orderByDesc('k.killed_at')
            ->offset($offset)->limit($limit + 1)
            ->get($this->columns());

        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit);

        return response()->json([
            'data' => $rows->map(fn ($r) => $this->killShape($r)),
            'meta' => [
                'player_id' => $player->id,
                'role'      => $role,
                'source'    => 'rtcw',
                'count'     => $rows->count(),
                'limit'     => $limit,
                'offset'    => $offset,
                'has_more'  => $hasMore,
            ],
        ]);
    }

    /** GET /api/v1/tracker/servers/{id}/kills  (?exclude_bots=1) */
    public function serverKills(int $id, Request $request): JsonResponse
    {
        if (! DB::table('tracker_servers')->where('id', $id)->exists()) {
            return response()->json(['error' => 'server_not_found'], 404);
        }

        $limit  = $this->limit($request, 50, 200);
        $offset = max(0, (int) $request->query('offset', 0));

        $query = $this->baseQuery()->where('k.server_id', $id);
        $this->applyBotFilter($query, $request);

        $rows = $query->orderByDesc('k.killed_at')
            ->offset($offset)->limit($limit + 1)
            ->get($this->columns());

        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit);

        return response()->json([
            'data' => $rows->map(fn ($r) => $this->killShape($r)),
            'meta' => [
                'server_id' => $id,
                'source'    => 'rtcw',
                'count'     => $rows->count(),
                'limit'     => $limit,
                'offset'    => $offset,
                'has_more'  => $hasMore,
            ],
        ]);
    }

    /** GET /api/v1/tracker/matches/{id}/kills  (?exclude_bots=1) -- chronological */
    public function matchKills(int $id, Request $request): JsonResponse
    {
        if (! DB::table('tracker_matches')->where('id', $id)->exists()) {
            return response()->json(['error' => 'match_not_found'], 404);
        }

        $limit  = $this->limit($request, 200, 1000);
        $offset = max(0, (int) $request->query('offset', 0));

        $query = $this->baseQuery()->where('k.match_id', $id);
        $this->applyBotFilter($query, $request);

        $rows = $query->orderBy('k.killed_at')->orderBy('k.id')
            ->offset($offset)->limit($limit + 1)
            ->get($this->columns());

        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit);

        return response()->json([
            'data' => $rows->map(fn ($r) => $this->killShape($r)),
            'meta' => [
                'match_id' => $id,
                'source'   => 'rtcw',
                'count'    => $rows->count(),
                'limit'    => $limit,
                'offset'   => $offset,
                'has_more' => $hasMore,
            ],
        ]);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function baseQuery()
    {
        return DB::table('tracker_rtcw_kills as k')
            ->leftJoin('tracker_players as kp', 'kp.id', '=', 'k.killer_player_id')
            ->leftJoin('tracker_players as vp', 'vp.id', '=', 'k.victim_player_id');
    }

    private function columns(): array
    {
        return [
            'k.id', 'k.server_id', 'k.match_id',
            'k.killer_slot', 'k.victim_slot',
            'k.killer_player_id', 'k.victim_player_id',
            'k.killer_is_bot', 'k.victim_is_bot',
            'k.mod_index', 'k.weapon_key', 'k.category',
            'k.is_frag', 'k.is_world', 'k.killed_at',
            'kp.name as killer_name', 'kp.name_clean as killer_name_clean',
            'vp.name as victim_name', 'vp.name_clean as victim_name_clean',
        ];
    }

    private function applyBotFilter($query, Request $request): void
    {
        if ($request->boolean('exclude_bots')) {
            $query->where('k.killer_is_bot', 0)->where('k.victim_is_bot', 0);
        }
    }

    private function killShape(object $r): array
    {
        return [
            'id' => (int) $r->id,
            'killer' => [
                'player_id'  => $r->killer_player_id !== null ? (int) $r->killer_player_id : null,
                'slot'       => (int) $r->killer_slot,
                'name'       => $r->killer_name,
                'name_clean' => $r->killer_name_clean,
                'is_bot'     => (bool) $r->killer_is_bot,
            ],
            'victim' => [
                'player_id'  => $r->victim_player_id !== null ? (int) $r->victim_player_id : null,
                'slot'       => (int) $r->victim_slot,
                'name'       => $r->victim_name,
                'name_clean' => $r->victim_name_clean,
                'is_bot'     => (bool) $r->victim_is_bot,
            ],
            'weapon_key' => $r->weapon_key,
            'category'   => $r->category,
            'mod_index'  => (int) $r->mod_index,
            'is_frag'    => (bool) $r->is_frag,
            'is_world'   => (bool) $r->is_world,
            'killed_at'  => $r->killed_at,
            'server_id'  => (int) $r->server_id,
            'match_id'   => $r->match_id !== null ? (int) $r->match_id : null,
        ];
    }
}
