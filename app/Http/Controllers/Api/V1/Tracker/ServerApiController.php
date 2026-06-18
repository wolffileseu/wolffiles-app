<?php

namespace App\Http\Controllers\Api\V1\Tracker;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tracker API -- server discovery (read-only, keyless).
 *
 * Unlike /tracker/servers (online-only), this searches ALL tracked servers
 * including offline ones -- it is the picker source for external stats apps
 * (e.g. the community installer). Only active/inactive servers are returned;
 * removed/banned/pending are excluded. Internal columns (poll control,
 * enhanced_source_ip, claimed_by_*, email, geo) are never exposed.
 */
class ServerApiController extends Controller
{
    private const MIN_QUERY = 2;

    /** GET /api/v1/tracker/servers/search */
    public function search(Request $request): JsonResponse
    {
        $limit  = $this->limit($request, 25, 100);
        $offset = max(0, (int) $request->query('offset', 0));

        $query = DB::table('tracker_servers as s')
            ->whereIn('s.status', ['active', 'inactive']);

        // optional text search on clean hostname OR ip (min length guarded)
        $q = trim((string) $request->query('q', ''));
        $applied_q = null;
        if ($q !== '' && mb_strlen($q) >= self::MIN_QUERY) {
            $applied_q = $q;
            $like = '%'.$q.'%';
            $query->where(function ($w) use ($like) {
                $w->where('s.hostname_clean', 'like', $like)
                  ->orWhere('s.ip', 'like', $like);
            });
        }

        // optional game filter: numeric = game_id, else match games.slug/name
        if ($request->filled('game')) {
            $game = (string) $request->query('game');
            if (ctype_digit($game)) {
                $query->where('s.game_id', (int) $game);
            } else {
                $query->join('tracker_games as g', 'g.id', '=', 's.game_id')
                      ->where(function ($w) use ($game) {
                          $w->where('g.slug', $game)->orWhere('g.name', $game);
                      });
            }
        }

        $rows = $query
            ->orderByDesc('s.is_online')
            ->orderByDesc('s.last_seen_at')
            ->offset($offset)->limit($limit + 1)
            ->get([
                's.id', 's.game_id', 's.hostname_clean', 's.hostname_html',
                's.ip', 's.port', 's.country', 's.country_code',
                's.gametype', 's.mod_name', 's.mod_version', 's.engine_display',
                's.current_map', 's.current_players', 's.max_players',
                's.is_online', 's.needs_password', 's.last_seen_at',
            ]);

        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit);

        $data = $rows->map(fn ($s) => [
            'id'             => (int) $s->id,
            'game_id'        => (int) $s->game_id,
            'hostname'       => $s->hostname_clean,
            'hostname_html'  => $s->hostname_html,
            'ip'             => $s->ip,
            'port'           => (int) $s->port,
            'address'        => $s->ip.':'.$s->port,
            'country'        => $s->country,
            'country_code'   => $s->country_code,
            'gametype'       => $s->gametype,
            'mod'            => $s->mod_name,
            'mod_version'    => $s->mod_version,
            'engine'         => $s->engine_display,
            'current_map'    => $s->current_map,
            'current_players'=> (int) $s->current_players,
            'max_players'    => (int) $s->max_players,
            'is_online'      => (bool) $s->is_online,
            'needs_password' => (bool) $s->needs_password,
            'last_seen_at'   => $s->last_seen_at,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'query'     => $applied_q,
                'min_query' => self::MIN_QUERY,
                'count'     => $data->count(),
                'limit'     => $limit,
                'offset'    => $offset,
                'has_more'  => $hasMore,
            ],
        ]);
    }

    private function limit(Request $request, int $default, int $max): int
    {
        $n = (int) $request->query('limit', $default);

        return max(1, min($n, $max));
    }
}
