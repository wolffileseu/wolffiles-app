<?php

namespace App\Http\Controllers\Api\V1\Tracker;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Tracker\WeaponRegistry;

/**
 * Tracker API -- Phase 1: player combat depth (read-only, keyless).
 *
 * Endpoints (all under /api/v1/tracker/players/{id}):
 *   GET /weapons      per-weapon stats (hits/atts/kills/deaths/headshots, accuracy, HS%)
 *   GET /stats        aggregate combat totals + enhanced block + elo
 *   GET /elo-history  ELO change log over time
 *   GET /daily        per-day XP/kills/deaths/playtime
 *   GET /aliases      name history
 *
 * Envelope convention for new endpoints: { "data": ..., "meta": ... }.
 */
class PlayerStatsController extends Controller
{
    public function weapons(int $id): JsonResponse
    {
        $player = $this->resolvePlayer($id);
        if (! $player) {
            return $this->notFound();
        }

        $rows = DB::table('tracker_player_weapon_stats')
            ->where('player_id', $player->id)
            ->orderByDesc('total_kills')
            ->get([
                'weapon_bit',
                'total_hits',
                'total_atts',
                'total_kills',
                'total_deaths',
                'total_headshots',
                'accuracy_bp',
            ]);

        $weapons = $rows->map(function ($w) {
            $bit  = (int) $w->weapon_bit;
            $meta = WeaponRegistry::get($bit);

            // Accuracy from raw hits/attempts (authoritative);
            // fall back to stored accuracy_bp only when no attempts recorded.
            $accuracy = $w->total_atts > 0
                ? round($w->total_hits / $w->total_atts * 100, 2)
                : ($w->accuracy_bp > 0 ? round($w->accuracy_bp / 100, 2) : 0.0);

            return [
                'weapon_bit'   => $bit,
                'weapon'       => $meta ? [
                    'name'     => $meta['name'],
                    'slug'     => $meta['slug'],
                    'category' => $meta['category'],
                    'side'     => $meta['side'],
                    'icon'     => WeaponRegistry::iconUrl($bit),
                ] : null,
                'hits'         => (int) $w->total_hits,
                'attempts'     => (int) $w->total_atts,
                'kills'        => (int) $w->total_kills,
                'deaths'       => (int) $w->total_deaths,
                'headshots'    => (int) $w->total_headshots,
                'accuracy'     => $accuracy,
            ];
        });

        return response()->json([
            'data' => $weapons,
            'meta' => [
                'player_id' => $player->id,
                'count'     => $weapons->count(),
            ],
        ]);
    }

    public function stats(int $id): JsonResponse
    {
        $p = $this->resolvePlayer($id);
        if (! $p) {
            return $this->notFound();
        }

        $kills  = (int) $p->total_kills;
        $deaths = (int) $p->total_deaths;

        $eKills  = (int) $p->enhanced_total_kills;
        $eDeaths = (int) $p->enhanced_total_deaths;
        $eHs     = (int) $p->enhanced_total_headshots;

        return response()->json([
            'data' => [
                'player_id' => $p->id,
                'totals' => [
                    'kills'             => $kills,
                    'deaths'            => $deaths,
                    'kd_ratio'          => round($kills / max($deaths, 1), 2),
                    'xp'                => (int) $p->total_xp,
                    'play_time_minutes' => (int) $p->total_play_time_minutes,
                    'sessions'          => (int) $p->total_sessions,
                ],
                'enhanced' => [
                    'available'    => (bool) $p->has_enhanced_data,
                    'kills'        => $eKills,
                    'deaths'       => $eDeaths,
                    'headshots'    => $eHs,
                    'damage'       => (int) $p->enhanced_total_damage,
                    'matches'      => (int) $p->enhanced_match_count,
                    'kd_ratio'     => round($eKills / max($eDeaths, 1), 2),
                    'headshot_pct' => $eKills > 0 ? round($eHs / $eKills * 100, 2) : 0.0,
                ],
                'elo' => [
                    'rating' => isset($p->elo_rating) ? (float) $p->elo_rating : null,
                    'peak'   => isset($p->elo_peak) ? (float) $p->elo_peak : null,
                    'games'  => (int) $p->elo_games,
                    'level'  => (int) $p->level,
                ],
            ],
        ]);
    }

    public function eloHistory(int $id, Request $request): JsonResponse
    {
        $p = $this->resolvePlayer($id);
        if (! $p) {
            return $this->notFound();
        }

        $limit  = $this->limit($request, 100, 500);
        $offset = max(0, (int) $request->query('offset', 0));

        $base = DB::table('tracker_elo_history')->where('player_id', $p->id);
        if ($request->filled('game_id')) {
            $base->where('game_id', (int) $request->query('game_id'));
        }

        $total = (clone $base)->count();

        $rows = $base->orderByDesc('recorded_at')
            ->offset($offset)
            ->limit($limit)
            ->get(['game_id', 'elo_before', 'elo_after', 'change', 'reason', 'recorded_at']);

        $data = $rows->map(fn ($r) => [
            'game_id'     => (int) $r->game_id,
            'elo_before'  => (float) $r->elo_before,
            'elo_after'   => (float) $r->elo_after,
            'change'      => (float) $r->change,
            'reason'      => $r->reason,
            'recorded_at' => $r->recorded_at,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'player_id' => $p->id,
                'total'     => $total,
                'count'     => $data->count(),
                'limit'     => $limit,
                'offset'    => $offset,
            ],
        ]);
    }

    public function daily(int $id, Request $request): JsonResponse
    {
        $p = $this->resolvePlayer($id);
        if (! $p) {
            return $this->notFound();
        }

        $limit = $this->limit($request, 90, 365);

        $query = DB::table('tracker_player_daily_stats')->where('player_id', $p->id);
        if ($request->filled('game_id')) {
            $query->where('game_id', (int) $request->query('game_id'));
        }

        $rows = $query->orderByDesc('date')
            ->limit($limit)
            ->get([
                'game_id', 'date', 'play_time_minutes', 'sessions',
                'kills', 'deaths', 'xp', 'servers_played', 'maps_played',
            ]);

        $data = $rows->map(fn ($r) => [
            'date'              => $r->date,
            'game_id'           => (int) $r->game_id,
            'play_time_minutes' => (int) $r->play_time_minutes,
            'sessions'          => (int) $r->sessions,
            'kills'             => (int) $r->kills,
            'deaths'            => (int) $r->deaths,
            'xp'                => (int) $r->xp,
            'servers_played'    => (int) $r->servers_played,
            'maps_played'       => (int) $r->maps_played,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'player_id' => $p->id,
                'count'     => $data->count(),
                'limit'     => $limit,
            ],
        ]);
    }

    public function aliases(int $id, Request $request): JsonResponse
    {
        $p = $this->resolvePlayer($id);
        if (! $p) {
            return $this->notFound();
        }

        $limit = $this->limit($request, 100, 500);

        $rows = DB::table('tracker_player_aliases')
            ->where('player_id', $p->id)
            ->orderByDesc('times_used')
            ->limit($limit)
            ->get(['name', 'name_clean', 'name_html', 'times_used', 'first_seen_at', 'last_seen_at']);

        $data = $rows->map(fn ($r) => [
            'name'          => $r->name,
            'name_clean'    => $r->name_clean,
            'name_html'     => $r->name_html,
            'times_used'    => (int) $r->times_used,
            'first_seen_at' => $r->first_seen_at,
            'last_seen_at'  => $r->last_seen_at,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'player_id' => $p->id,
                'count'     => $data->count(),
                'limit'     => $limit,
            ],
        ]);
    }

    private function resolvePlayer(int $id): ?object
    {
        $player = DB::table('tracker_players')->where('id', $id)->first();
        if (! $player) {
            return null;
        }

        if (! is_null($player->merged_into)) {
            $target = DB::table('tracker_players')->where('id', $player->merged_into)->first();
            if ($target) {
                $player = $target;
            }
        }

        if (($player->status ?? null) === 'hidden') {
            return null;
        }

        return $player;
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['error' => 'player_not_found'], 404);
    }

    private function limit(Request $request, int $default, int $max): int
    {
        $n = (int) $request->query('limit', $default);

        return max(1, min($n, $max));
    }
}
