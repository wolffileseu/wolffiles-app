<?php

namespace App\Services\Banner;

use App\Models\Tracker\TrackerPlayer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data provider for the vertical HTML player embed iframe.
 * Analogous to ServerEmbedDataService, reads rank from the same
 * tracker_player_rankings_30d table the public Ranking page uses.
 */
class PlayerEmbedDataService
{
    public function collect(TrackerPlayer $player): array
    {
        return [
            'player'          => $this->playerInfo($player),
            'rank'            => $this->rank($player),
            'stats'           => $this->stats($player),
            'favorite_server' => $this->favoriteServer($player),
            'current_server'  => $this->currentServer($player),
            'elo_history'     => $this->eloHistory24h($player),
        ];
    }

    protected function playerInfo(TrackerPlayer $p): array
    {
        $isOnline = DB::table('tracker_player_sessions')
            ->where('player_id', $p->id)
            ->whereNull('ended_at')
            ->exists();

        return [
            'id'           => $p->id,
            'name_clean'   => $p->name_clean,
            'name_html'    => $p->name_html,
            'country_code' => $p->country_code,
            'is_online'    => $isOnline,
        ];
    }

    protected function rank(TrackerPlayer $p): array
    {
        $row = DB::table('tracker_player_rankings_30d')
            ->where('player_id', $p->id)
            ->orderBy('rank')
            ->first(['rank', 'total_in_game', 'game_family']);

        return [
            'position' => $row?->rank,
            'total'    => $row?->total_in_game ?? 0,
            'family'   => $row?->game_family,
        ];
    }

    protected function stats(TrackerPlayer $p): array
    {
        $minutes = (int) ($p->total_play_time_minutes ?? 0);
        return [
            'playtime_minutes' => $minutes,
            'playtime_hours'   => (int) round($minutes / 60),
            'elo'              => $p->elo_rating !== null ? (int) round((float) $p->elo_rating) : null,
            'elo_peak'         => $p->elo_peak !== null ? (int) round((float) $p->elo_peak) : null,
            'xp'               => (int) ($p->total_xp ?? 0),
            'sessions'         => (int) ($p->total_sessions ?? 0),
        ];
    }

    protected function favoriteServer(TrackerPlayer $p): ?array
    {
        $row = DB::table('tracker_player_sessions as s')
            ->leftJoin('tracker_servers as srv', 'srv.id', '=', 's.server_id')
            ->selectRaw('srv.id as id, srv.hostname as hostname, SUM(s.duration_minutes) as total_min')
            ->where('s.player_id', $p->id)
            ->whereNotNull('srv.hostname')
            ->groupBy('s.server_id', 'srv.id', 'srv.hostname')
            ->orderByDesc('total_min')
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'id'       => $row->id,
            'hostname' => $row->hostname,
            'minutes'  => (int) $row->total_min,
        ];
    }

    protected function currentServer(TrackerPlayer $p): ?array
    {
        $row = DB::table('tracker_player_sessions as s')
            ->leftJoin('tracker_servers as srv', 'srv.id', '=', 's.server_id')
            ->where('s.player_id', $p->id)
            ->whereNull('s.ended_at')
            ->whereNotNull('srv.hostname')
            ->orderByDesc('s.started_at')
            ->first(['srv.id', 'srv.hostname']);

        if (! $row) {
            return null;
        }

        return [
            'id'       => $row->id,
            'hostname' => $row->hostname,
        ];
    }

    protected function eloHistory24h(TrackerPlayer $p): array
    {
        if (! Schema::hasTable('tracker_elo_history')) {
            return [];
        }

        return DB::table('tracker_elo_history')
            ->where('player_id', $p->id)
            ->where('recorded_at', '>=', now()->subHours(24))
            ->orderBy('recorded_at')
            ->pluck('elo_after')
            ->map(fn ($v) => (float) $v)
            ->toArray();
    }
}
