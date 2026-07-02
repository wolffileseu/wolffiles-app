<?php

namespace App\Services\Tracker;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Aggregates RtCW obituary kills (tracker_rtcw_kills) into display-ready stats.
 *
 * RtCW has no ET-style weapon stats (accuracy/hits/damage); all we have is
 * individual kills. From those we derive Kills, Deaths, K/D ratio and the
 * favourite weapon per player. Bots (killer_player_id IS NULL) are excluded
 * from the player scoreboard but still counted in raw totals if asked.
 *
 * Frags vs. deaths:
 *   - a "kill" counts for the killer only when is_frag = 1 (weapon kill,
 *     not world/suicide, not self-kill)
 *   - a "death" counts for the victim on ANY kill row where they are the
 *     victim (including world/suicide deaths)
 */
class RtcwKillStatsService
{
    /**
     * Per-player scoreboard for one server.
     *
     * @return Collection<int,object{
     *   player_id:int, kills:int, deaths:int, kd:float,
     *   top_weapon:?string, top_weapon_kills:int
     * }>
     */
    public function serverScoreboard(int $serverId, int $limit = 50): Collection
    {
        // Kills per player (frags only).
        $kills = DB::table('tracker_rtcw_kills')
            ->select('killer_player_id as player_id', DB::raw('COUNT(*) as kills'))
            ->where('server_id', $serverId)
            ->where('is_frag', 1)
            ->whereNotNull('killer_player_id')
            ->groupBy('killer_player_id')
            ->pluck('kills', 'player_id'); // [player_id => kills]

        // Deaths per player (any row where they are the victim).
        $deaths = DB::table('tracker_rtcw_kills')
            ->select('victim_player_id as player_id', DB::raw('COUNT(*) as deaths'))
            ->where('server_id', $serverId)
            ->whereNotNull('victim_player_id')
            ->groupBy('victim_player_id')
            ->pluck('deaths', 'player_id');

        // Favourite weapon per player (most frags with it).
        $topWeapons = $this->favouriteWeapons($serverId);

        // Union of all player ids that appear as killer or victim.
        $playerIds = $kills->keys()->merge($deaths->keys())->unique();

        $rows = $playerIds->map(function ($pid) use ($kills, $deaths, $topWeapons) {
            $k = (int) ($kills[$pid] ?? 0);
            $d = (int) ($deaths[$pid] ?? 0);
            $tw = $topWeapons[$pid] ?? null;

            return (object) [
                'player_id'        => (int) $pid,
                'kills'            => $k,
                'deaths'           => $d,
                'kd'               => $d === 0 ? (float) $k : round($k / $d, 2),
                'top_weapon'       => $tw->weapon_key ?? null,
                'top_weapon_kills' => (int) ($tw->kills ?? 0),
            ];
        });

        return $rows
            ->sortByDesc('kills')
            ->values()
            ->take($limit);
    }

    /**
     * Favourite weapon (highest frag count) per player on a server.
     *
     * @return array<int,object{weapon_key:string,kills:int}>
     */
    private function favouriteWeapons(int $serverId): array
    {
        $byWeapon = DB::table('tracker_rtcw_kills')
            ->select('killer_player_id as player_id', 'weapon_key', DB::raw('COUNT(*) as kills'))
            ->where('server_id', $serverId)
            ->where('is_frag', 1)
            ->whereNotNull('killer_player_id')
            ->groupBy('killer_player_id', 'weapon_key')
            ->orderByDesc('kills')
            ->get();

        $top = [];
        foreach ($byWeapon as $row) {
            // first seen per player = highest kills (already ordered desc)
            if (!isset($top[$row->player_id])) {
                $top[$row->player_id] = (object) [
                    'weapon_key' => $row->weapon_key,
                    'kills'      => (int) $row->kills,
                ];
            }
        }

        return $top;
    }

    /**
     * Weapon usage totals for one player across all servers (or one server).
     *
     * @return Collection<int,object{weapon_key:string,label:string,kills:int}>
     */
    public function playerWeaponBreakdown(int $playerId, ?int $serverId = null): Collection
    {
        $q = DB::table('tracker_rtcw_kills')
            ->select('weapon_key', DB::raw('COUNT(*) as kills'))
            ->where('is_frag', 1)
            ->where('killer_player_id', $playerId);

        if ($serverId !== null) {
            $q->where('server_id', $serverId);
        }

        return $q->groupBy('weapon_key')
            ->orderByDesc('kills')
            ->get()
            ->map(function ($row) {
                // decode label from the key via the registry's reverse lookup
                $row->label = ucfirst(str_replace('_', ' ', $row->weapon_key));
                return $row;
            });
    }

    /**
     * Lifetime K/D for one player (all RtCW servers).
     *
     * @return object{kills:int,deaths:int,kd:float}
     */
    public function playerTotals(int $playerId): object
    {
        $kills = DB::table('tracker_rtcw_kills')
            ->where('is_frag', 1)
            ->where('killer_player_id', $playerId)
            ->count();

        $deaths = DB::table('tracker_rtcw_kills')
            ->where('victim_player_id', $playerId)
            ->count();

        return (object) [
            'kills'  => $kills,
            'deaths' => $deaths,
            'kd'     => $deaths === 0 ? (float) $kills : round($kills / $deaths, 2),
        ];
    }
}
