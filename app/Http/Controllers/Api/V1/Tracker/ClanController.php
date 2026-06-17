<?php

namespace App\Http\Controllers\Api\V1\Tracker;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Tracker\Concerns\ResolvesTrackerPlayer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tracker API -- Phase 3: clans (read-only, keyless).
 *
 * The clan list already lives in TrackerExtendedController@apiClans; this
 * controller adds detail / members / squads only.
 *
 * Privacy: clan_email, claimed_by_user_id, is_locked and auto_join_enabled
 * are intentionally omitted from public output.
 */
class ClanController extends Controller
{
    use ResolvesTrackerPlayer; // limit()

    /** GET /api/v1/tracker/clans/{id} */
    public function show(int $id): JsonResponse
    {
        $c = DB::table('tracker_clans')->where('id', $id)->first([
            'id', 'tag', 'tag_clean', 'name', 'description', 'website', 'discord',
            'country', 'country_code', 'member_count', 'active_member_count',
            'avg_elo', 'total_play_time_minutes', 'status', 'is_verified',
            'first_seen_at', 'last_seen_at',
        ]);

        if (! $c) {
            return response()->json(['error' => 'clan_not_found'], 404);
        }

        $squadCount = DB::table('tracker_clan_squads')->where('clan_id', $id)->count();

        return response()->json([
            'data' => [
                'id'          => (int) $c->id,
                'tag'         => $c->tag,
                'tag_clean'   => $c->tag_clean,
                'name'        => $c->name,
                'description' => $c->description,
                'website'     => $c->website,
                'discord'     => $c->discord,
                'country'     => $c->country,
                'country_code'=> $c->country_code,
                'members' => [
                    'total'  => (int) $c->member_count,
                    'active' => (int) $c->active_member_count,
                ],
                'squad_count'             => $squadCount,
                'avg_elo'                 => (float) $c->avg_elo,
                'total_play_time_minutes' => (int) $c->total_play_time_minutes,
                'status'                  => $c->status,
                'is_verified'             => (bool) $c->is_verified,
                'first_seen_at'           => $c->first_seen_at,
                'last_seen_at'            => $c->last_seen_at,
            ],
        ]);
    }

    /** GET /api/v1/tracker/clans/{id}/members */
    public function members(int $id, Request $request): JsonResponse
    {
        if (! DB::table('tracker_clans')->where('id', $id)->exists()) {
            return response()->json(['error' => 'clan_not_found'], 404);
        }

        $limit  = $this->limit($request, 100, 500);
        $offset = max(0, (int) $request->query('offset', 0));

        $query = DB::table('tracker_clan_members as cm')
            ->join('tracker_players as p', 'p.id', '=', 'cm.player_id')
            ->leftJoin('tracker_clan_squads as sq', 'sq.id', '=', 'cm.squad_id')
            ->where('cm.clan_id', $id);

        if (! $request->boolean('include_inactive')) {
            $query->where('cm.is_active', 1);
        }
        if ($request->filled('squad_id')) {
            $query->where('cm.squad_id', (int) $request->query('squad_id'));
        }

        $rows = $query
            ->orderByRaw("FIELD(cm.role,'founder','leader','officer','member')")
            ->orderBy('cm.sort_order')
            ->orderBy('p.name_clean')
            ->offset($offset)->limit($limit + 1)
            ->get([
                'cm.player_id', 'cm.role', 'cm.role_label', 'cm.squad_id',
                'sq.name as squad_name', 'cm.joined_at', 'cm.left_at', 'cm.is_active',
                'p.name', 'p.name_clean', 'p.name_html', 'p.country_code', 'p.elo_rating',
            ]);

        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit);

        $data = $rows->map(fn ($m) => [
            'player' => [
                'id'           => (int) $m->player_id,
                'name'         => $m->name,
                'name_clean'   => $m->name_clean,
                'name_html'    => $m->name_html,
                'country_code' => $m->country_code,
                'elo'          => $m->elo_rating !== null ? (float) $m->elo_rating : null,
            ],
            'role'       => $m->role,
            'role_label' => $m->role_label,
            'squad'      => $m->squad_id !== null
                ? ['id' => (int) $m->squad_id, 'name' => $m->squad_name]
                : null,
            'joined_at' => $m->joined_at,
            'left_at'   => $m->left_at,
            'is_active' => (bool) $m->is_active,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'clan_id'  => $id,
                'count'    => $data->count(),
                'limit'    => $limit,
                'offset'   => $offset,
                'has_more' => $hasMore,
            ],
        ]);
    }

    /** GET /api/v1/tracker/clans/{id}/squads */
    public function squads(int $id): JsonResponse
    {
        if (! DB::table('tracker_clans')->where('id', $id)->exists()) {
            return response()->json(['error' => 'clan_not_found'], 404);
        }

        $counts = [];
        $rowsC = DB::table('tracker_clan_members')
            ->where('clan_id', $id)
            ->where('is_active', 1)
            ->whereNotNull('squad_id')
            ->selectRaw('squad_id, COUNT(*) as c')
            ->groupBy('squad_id')
            ->get();
        foreach ($rowsC as $r) {
            $counts[(int) $r->squad_id] = (int) $r->c;
        }

        $rows = DB::table('tracker_clan_squads')
            ->where('clan_id', $id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'sort_order']);

        $data = $rows->map(fn ($s) => [
            'id'           => (int) $s->id,
            'name'         => $s->name,
            'description'  => $s->description,
            'member_count' => $counts[(int) $s->id] ?? 0,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => ['clan_id' => $id, 'count' => $data->count()],
        ]);
    }
}
