<?php

namespace App\Services\Banner;

use App\Models\Tracker\TrackerServer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates all server data needed by the vertical embed banner.
 * Single place that knows how to compute rank, fetch players, top players,
 * and hydrate map/history data. Cached at caller.
 */
class ServerEmbedDataService
{
    public function collect(TrackerServer $server): array
    {
        return [
            'server'          => $this->serverBasics($server),
            'rank'            => $this->computeRank($server),
            'current_players' => $this->currentPlayers($server),
            'top_players'     => $this->topPlayers($server),
            'map_thumb'       => $this->mapThumbPath($server->current_map),
            'history'         => $this->playerHistory24h($server),
        ];
    }

    private function serverBasics(TrackerServer $s): array
    {
        return [
            'id'              => $s->id,
            'hostname'        => $s->hostname,
            'hostname_html'   => $s->hostname_html,
            'hostname_clean'  => $s->hostname_clean,
            'ip'              => $s->ip,
            'port'            => $s->port,
            'country_code'    => $s->country_code,
            'current_players' => (int) $s->current_players,
            'max_players'     => (int) $s->max_players,
            'current_map'     => $s->current_map,
            'gametype'        => $s->gametype,
            'mod_name'        => $s->mod_name,
            'is_online'       => (bool) $s->is_online,
            'last_poll_at'    => $s->last_poll_at,
        ];
    }

    /**
     * Rank by 30-day avg players within the same game. Requires >=10 polls.
     */
    private function computeRank(TrackerServer $s): array
    {
        // Read from the materialized ranking snapshot (tracker:rebuild-rankings).
        // Single indexed lookup — ~1ms. If the snapshot hasn't been computed yet
        // (fresh install / after migration), returns nulls — view handles that.
        $row = DB::table('tracker_server_rankings')
            ->where('server_id', $s->id)
            ->first(['rank', 'total_in_game']);

        return [
            'position' => $row?->rank,
            'total'    => $row?->total_in_game ?? 0,
        ];
    }

    /**
     * Players currently on the server (sessions with no ended_at).
     * Sorted by score desc, limit 8.
     */
    private function currentPlayers(TrackerServer $s): array
    {
        // Subquery: pick the team from the latest snapshot per session
        $latestTeamSub = DB::table('tracker_player_snapshots as snap')
            ->select('snap.session_id', 'snap.team')
            ->whereRaw('snap.polled_at = (SELECT MAX(polled_at) FROM tracker_player_snapshots WHERE session_id = snap.session_id)');

        return DB::table('tracker_player_sessions as sess')
            ->where('sess.server_id', $s->id)
            ->whereNull('sess.ended_at')
            ->join('tracker_players', 'tracker_players.id', '=', 'sess.player_id')
            ->leftJoinSub($latestTeamSub, 'latest_snap', 'latest_snap.session_id', '=', 'sess.id')
            ->select(
                'tracker_players.id',
                'tracker_players.name_clean',
                'tracker_players.name_html',
                'sess.score',
                DB::raw('COALESCE(latest_snap.team, sess.team) as team'),
                'sess.started_at',
            )
            ->orderBy('sess.started_at')
            ->limit(8)
            ->get()
            ->map(fn ($p) => [
                'id'     => $p->id,
                'name'   => $p->name_clean,
                'html'   => $p->name_html,
                'score'  => (int) $p->score,
                'team'   => $p->team,
            ])
            ->values()
            ->all();
    }

    /**
     * Top 8 all-time players on this server by accumulated XP.
     * NOTE: intentionally uses SUM(xp) from sessions, not kills
     * (known data issue with session.kills field).
     */
    private function topPlayers(TrackerServer $s): array
    {
        // Read from materialized snapshot (tracker:rebuild-top-players).
        // Single indexed lookup — ~0.5ms, no aggregation at request time.
        return DB::table('tracker_server_top_players')
            ->where('server_id', $s->id)
            ->orderBy('rank')
            ->get(['player_id', 'name_clean', 'name_html', 'total_xp', 'total_minutes'])
            ->map(fn ($p) => [
                'id'   => $p->player_id,
                'name' => $p->name_clean,
                'html' => $p->name_html,
                'xp'   => (int) $p->total_xp,
                'min'  => (int) $p->total_minutes,
            ])
            ->values()
            ->all();
    }

    /**
     * Resolves current_map to a public path to its thumbnail,
     * or null if none exists (view shows placeholder).
     */
    private function mapThumbPath(?string $mapName): ?string
    {
        if (!$mapName) {
            return null;
        }
        $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $mapName);
        $path = public_path("images/map-thumbs/{$safe}.jpg");
        if (is_file($path)) {
            return asset("images/map-thumbs/{$safe}.jpg");
        }
        $png = public_path("images/map-thumbs/{$safe}.png");
        if (is_file($png)) {
            return asset("images/map-thumbs/{$safe}.png");
        }
        return null;
    }

    /**
     * Player count history (last 24h, downsampled to ≤48 points).
     */
    private function playerHistory24h(TrackerServer $s): array
    {
        $points = DB::table('tracker_server_history')
            ->where('server_id', $s->id)
            ->where('polled_at', '>=', now()->subHours(24))
            ->orderBy('polled_at')
            ->pluck('players')
            ->toArray();

        if (count($points) > 48) {
            $chunks = array_chunk($points, (int) ceil(count($points) / 48));
            $points = array_map(fn ($c) => (int) round(array_sum($c) / count($c)), $chunks);
        }
        return array_map('intval', $points);
    }
}
