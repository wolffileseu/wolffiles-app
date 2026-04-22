<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Tracker\WeaponRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Global weapon leaderboards — the "Weapon Mastery" pages.
 *
 * Data pipeline: tracker_player_weapon_stats (lifetime aggregates) is the
 * source. We join against tracker_players for display names/avatars and
 * compute ranks on the fly with cache.
 */
class WeaponLeaderboardController extends Controller
{
    /**
     * Minimum attempts required to rank on accuracy boards.
     * Prevents 1-hit-1-shot players from topping the accuracy chart.
     */
    private const MIN_ATTEMPTS_FOR_ACCURACY = 50;

    /**
     * Cache duration for global leaderboard queries (seconds).
     */
    private const CACHE_TTL = 300;

    /**
     * Index: Weapon Mastery overview. Shows Wall of Fame + weapon grid.
     */
    public function index()
    {
        $cacheKey = 'weapon-leaderboard:index:v1';

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () {
            // Global weapon totals (across all players)
            $totals = DB::table('tracker_player_weapon_stats')
                ->selectRaw('weapon_bit,
                             SUM(total_kills) as global_kills,
                             SUM(total_hits) as global_hits,
                             SUM(total_atts) as global_atts,
                             SUM(total_headshots) as global_headshots,
                             COUNT(DISTINCT player_id) as active_players')
                ->groupBy('weapon_bit')
                ->get()
                ->keyBy('weapon_bit');

            // Top 1 player per weapon (by kills) for the Wall of Fame
            $kings = [];
            foreach (WeaponRegistry::all() as $bit => $_) {
                if (!isset($totals[$bit]) || $totals[$bit]->global_kills < 1) continue;

                $king = DB::table('tracker_player_weapon_stats as w')
                    ->join('tracker_players as p', 'p.id', '=', 'w.player_id')
                    ->where('w.weapon_bit', $bit)
                    ->orderByDesc('w.total_kills')
                    ->limit(1)
                    ->first([
                        'p.id as player_id',
                        'p.name_clean',
                        'p.name_html',
                        'w.total_kills',
                        'w.total_headshots',
                        'w.accuracy_bp',
                    ]);

                if ($king) $kings[$bit] = $king;
            }

            return compact('totals', 'kings');
        });

        return view('frontend.tracker.weapon-leaderboard-index', [
            'totals' => $data['totals'],
            'kings'  => $data['kings'],
            'groups' => WeaponRegistry::groupedByCategory(),
        ]);
    }

    /**
     * Drilldown: top players for a specific weapon.
     */
    public function show(string $slug)
    {
        $weapon = WeaponRegistry::findBySlug($slug);
        abort_if(!$weapon, 404);

        $bit = $weapon['bit'];
        $cacheKey = "weapon-leaderboard:show:{$slug}:v1";

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($bit) {
            // Top 50 by kills
            $byKills = DB::table('tracker_player_weapon_stats as w')
                ->join('tracker_players as p', 'p.id', '=', 'w.player_id')
                ->where('w.weapon_bit', $bit)
                ->where('w.total_kills', '>', 0)
                ->orderByDesc('w.total_kills')
                ->limit(50)
                ->get([
                    'p.id as player_id', 'p.name_clean', 'p.name_html',
                    'w.total_kills', 'w.total_deaths', 'w.total_hits',
                    'w.total_atts', 'w.total_headshots', 'w.accuracy_bp',
                    'w.last_updated_at',
                ]);

            // Top 20 by accuracy (minimum attempts gate)
            $byAccuracy = DB::table('tracker_player_weapon_stats as w')
                ->join('tracker_players as p', 'p.id', '=', 'w.player_id')
                ->where('w.weapon_bit', $bit)
                ->where('w.total_atts', '>=', self::MIN_ATTEMPTS_FOR_ACCURACY)
                ->orderByDesc('w.accuracy_bp')
                ->limit(20)
                ->get([
                    'p.id as player_id', 'p.name_clean', 'p.name_html',
                    'w.total_kills', 'w.total_hits', 'w.total_atts',
                    'w.accuracy_bp',
                ]);

            // Top 20 by headshots
            $byHeadshots = DB::table('tracker_player_weapon_stats as w')
                ->join('tracker_players as p', 'p.id', '=', 'w.player_id')
                ->where('w.weapon_bit', $bit)
                ->where('w.total_headshots', '>', 0)
                ->orderByDesc('w.total_headshots')
                ->limit(20)
                ->get([
                    'p.id as player_id', 'p.name_clean', 'p.name_html',
                    'w.total_kills', 'w.total_headshots',
                ]);

            // Single-match record
            $matchRecord = DB::table('tracker_match_player_weapon_stats as mw')
                ->join('tracker_players as p', 'p.id', '=', 'mw.player_id')
                ->join('tracker_matches as m', 'm.id', '=', 'mw.match_id')
                ->where('mw.weapon_bit', $bit)
                ->orderByDesc('mw.kills')
                ->limit(1)
                ->first([
                    'p.id as player_id', 'p.name_clean', 'p.name_html',
                    'mw.kills', 'mw.hits', 'mw.atts', 'mw.headshots',
                    'm.map_name', 'm.started_at',
                ]);

            // Global totals
            $totals = DB::table('tracker_player_weapon_stats')
                ->where('weapon_bit', $bit)
                ->selectRaw('SUM(total_kills) as kills,
                             SUM(total_hits) as hits,
                             SUM(total_atts) as atts,
                             SUM(total_headshots) as headshots,
                             COUNT(DISTINCT player_id) as players')
                ->first();

            return compact('byKills', 'byAccuracy', 'byHeadshots', 'matchRecord', 'totals');
        });

        return view('frontend.tracker.weapon-leaderboard-show', [
            'weapon'      => $weapon,
            'byKills'     => $data['byKills'],
            'byAccuracy'  => $data['byAccuracy'],
            'byHeadshots' => $data['byHeadshots'],
            'matchRecord' => $data['matchRecord'],
            'totals'      => $data['totals'],
        ]);
    }
}
