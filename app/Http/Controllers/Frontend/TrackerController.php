<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Tracker\TrackerGame;
use App\Models\Tracker\TrackerServer;
use App\Models\Tracker\TrackerPlayer;
use App\Models\Tracker\TrackerClan;
use App\Models\Tracker\TrackerMap;
use App\Services\Tracker\ColorCodeService;
use Illuminate\Http\Request;

class TrackerController extends Controller
{
    /**
     * Live Dashboard / Overview
     */
    public function index()
    {
        $games = TrackerGame::active()->orderBy('sort_order')->get();

        $stats = [
            'servers_online' => TrackerServer::where('is_online', true)->count(),
            'servers_total' => TrackerServer::active()->count(),
            'players_online' => TrackerServer::where('is_online', true)->sum('current_players'),
            'players_total' => TrackerPlayer::count(),
        ];

        $topServers = TrackerServer::where('is_online', true)
            ->where('current_players', '>', 0)
            ->with('game')
            ->orderByDesc('current_players')
            ->limit(10)
            ->get();

        return view('frontend.tracker.index', compact('games', 'stats', 'topServers'));
    }

    /**
     * Server List
     */
    public function servers(Request $request)
    {
        $games = TrackerGame::active()->orderBy('sort_order')->get();

        $query = TrackerServer::active()->with('game');

        // Game filter
        if ($request->filled('game')) {
            $game = TrackerGame::where('slug', $request->game)->first();
            if ($game) {
                $query->where('game_id', $game->id);
            }
        }

        // Only online
        if ($request->boolean('online', false)) {
            $query->where('is_online', true);
        }

        // Has players
        if ($request->boolean('players', false)) {
            $query->where('current_players', '>', 0);
        }

        // Country filter
        if ($request->filled('country')) {
            $query->where('country_code', $request->country);
        }

        // Map filter
        if ($request->filled('map')) {
            $query->where('current_map', 'like', '%' . $request->map . '%');
        }

        // Mod filter
        if ($request->filled('mod')) {
            $query->where('mod_name', 'like', '%' . $request->mod . '%');
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('hostname_clean', 'like', "%{$search}%")
                  ->orWhere('ip', 'like', "%{$search}%")
                  ->orWhere('current_map', 'like', "%{$search}%");
            });
        }

        // No password
        if ($request->boolean('no_password', false)) {
            $query->where('needs_password', false);
        }

        // Sort
        $sort = $request->get('sort', 'players');
        $query = match($sort) {
            'name' => $query->orderBy('hostname_clean'),
            'map' => $query->orderBy('current_map'),
            'country' => $query->orderBy('country_code'),
            'game' => $query->orderBy('game_id'),
            default => $query->orderByDesc('current_players'),
        };

        // Secondary sort: online first
        $query->orderByDesc('is_online');

        $servers = $query->paginate(50)->withQueryString();

        // Get unique countries for filter
        $countries = TrackerServer::active()
            ->whereNotNull('country_code')
            ->select('country_code', 'country')
            ->distinct()
            ->orderBy('country')
            ->get();

        // Get unique mods for filter
        $mods = TrackerServer::active()
            ->whereNotNull('mod_name')
            ->where('mod_name', '!=', '')
            ->select('mod_name')
            ->distinct()
            ->orderBy('mod_name')
            ->pluck('mod_name');

        return view('frontend.tracker.servers', compact('servers', 'games', 'countries', 'mods'));
    }

    /**
     * Server Detail
     */
    public function serverShow(TrackerServer $server)
    {
        $server->load(['game', 'settings']);

        // Current players from latest snapshots
        $activeSessions = $server->sessions()
            ->whereNull('ended_at')
            ->with('player')
            ->get();

        // Player count history (last 24h)
        $history = $server->history()
            ->where('polled_at', '>=', now()->subHours(48))
            ->orderBy('polled_at')
            ->get(['players', 'polled_at']);

        // Top maps on this server
        $topMaps = $server->mapStats()
            ->orderByDesc('total_time_minutes')
            ->limit(10)
            ->get();

        // Enhanced Tracker: recent matches (only if server reports via sv_tracker2)
        $recentMatches = collect();
        if ($server->is_enhanced_tracker) {
            $recentMatches = \DB::table('tracker_matches')
                ->where('server_id', $server->id)
                // Hide sub-30s maprestart fragments, keep real matches + open ones
                ->where(function ($q) {
                    $q->whereNull('ended_at')
                      ->orWhere('duration_seconds', '>=', 30);
                })
                ->orderByDesc('started_at')
                ->limit(15)
                ->get();
        }

        return view('frontend.tracker.server-show', compact(
            'server', 'activeSessions', 'history', 'topMaps', 'recentMatches'
        ));
    }

    /**
     * Player Search
     */
    public function players(Request $request)
    {
        $query = TrackerPlayer::active();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_clean', 'like', "%{$search}%")
                  ->orWhereHas('aliases', fn($aq) => $aq->where('name_clean', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('country')) {
            $query->where('country_code', $request->country);
        }

        $sort = $request->get('sort', 'last_seen');
        $query = match($sort) {
            'elo' => $query->orderByDesc('elo_rating'),
            'score' => $query->orderByDesc('total_kills'),
            'playtime' => $query->orderByDesc('total_play_time_minutes'),
            'name' => $query->orderBy('name_clean'),
            'kd' => $query->orderByRaw('CASE WHEN total_deaths > 0 THEN total_kills / total_deaths ELSE total_kills END DESC'),
            default => $query->orderByDesc('last_seen_at'),
        };

        $players = $query->paginate(50)->withQueryString();

        return view('frontend.tracker.players', compact('players'));
    }

    /**
     * Player Profile
     */
    public function playerShow(TrackerPlayer $player)
    {
        $player->load(['aliases', 'clanMemberships.clan']);

        // Recent sessions
        $sessions = $player->sessions()
            ->with(['server.game'])
            ->orderByDesc('started_at')
            ->limit(20)
            ->get();

        // ELO history
        $eloHistory = $player->eloHistory()
            ->orderBy('recorded_at')
            ->limit(90)
            ->get(['elo_after', 'recorded_at']);

        // Favorite servers
        $favoriteServers = $player->sessions()
            ->select('server_id')
            ->selectRaw('COUNT(*) as session_count, SUM(duration_minutes) as total_time')
            ->groupBy('server_id')
            ->orderByDesc('total_time')
            ->limit(5)
            ->with('server')
            ->get();

        // Favorite maps
        $favoriteMaps = $player->sessions()
            ->whereNotNull('map_name')
            ->select('map_name')
            ->selectRaw('COUNT(*) as times_played, SUM(duration_minutes) as total_time')
            ->groupBy('map_name')
            ->orderByDesc('total_time')
            ->limit(10)
            ->get();

        // Enhanced Tracker: matches on servers where this player had enhanced sessions
        $enhancedMatches = collect();
        $enhancedMatchesCount = 0;
        if ($player->has_enhanced_data && $player->enhanced_first_seen_at) {
            // Find all servers where the player was tracked via enhanced
            $enhancedServerIds = \DB::table('tracker_player_sessions')
                ->where('player_id', $player->id)
                ->where('started_at', '>=', $player->enhanced_first_seen_at)
                ->distinct()
                ->pluck('server_id');

            if ($enhancedServerIds->isNotEmpty()) {
                $enhancedMatchesCount = \DB::table('tracker_matches')
                    ->whereIn('server_id', $enhancedServerIds)
                    ->where('started_at', '>=', $player->enhanced_first_seen_at)
                    ->where(function ($q) {
                        $q->whereNull('ended_at')
                          ->orWhere('duration_seconds', '>=', 30);
                    })
                    ->count();

                $enhancedMatches = \DB::table('tracker_matches')
                    ->join('tracker_servers', 'tracker_matches.server_id', '=', 'tracker_servers.id')
                    ->whereIn('tracker_matches.server_id', $enhancedServerIds)
                    ->where('tracker_matches.started_at', '>=', $player->enhanced_first_seen_at)
                    ->where(function ($q) {
                        $q->whereNull('tracker_matches.ended_at')
                          ->orWhere('tracker_matches.duration_seconds', '>=', 30);
                    })
                    ->orderByDesc('tracker_matches.started_at')
                    ->limit(10)
                    ->select(
                        'tracker_matches.id',
                        'tracker_matches.map_name',
                        'tracker_matches.started_at',
                        'tracker_matches.ended_at',
                        'tracker_matches.duration_seconds',
                        'tracker_matches.end_reason',
                        'tracker_servers.id as server_id',
                        'tracker_servers.hostname_clean',
                        'tracker_servers.hostname_html'
                    )
                    ->get();
            }
        }

        // Enhanced Tracker: weapon stats from the player's most recent match
        // (where we have ws-packet data). Shows the weapon breakdown in the
        // Enhanced section — what did they use, how accurate, headshot rate.
        $latestMatch = null;
        $latestMatchStats = null;
        $latestMatchWeapons = collect();
        if ($player->has_enhanced_data) {
            $latestMatchStats = \DB::table('tracker_player_match_stats')
                ->where('player_id', $player->id)
                ->orderByDesc('match_id')
                ->first();

            if ($latestMatchStats !== null) {
                $latestMatch = \DB::table('tracker_matches')
                    ->where('id', $latestMatchStats->match_id)
                    ->first();

                $latestMatchWeapons = \DB::table('tracker_match_player_weapon_stats')
                    ->where('match_id', $latestMatchStats->match_id)
                    ->where('player_id', $player->id)
                    ->orderByDesc('kills')
                    ->orderByDesc('atts')
                    ->get();
            }
        }

        // Enhanced Rating overview: current (from most recent match with a rating)
        // and peak (max across all matches).
        $enhancedRating = null;
        $enhancedRatingPeak = null;
        $enhancedRatingMatches = 0;
        if ($player->has_enhanced_data) {
            $ratingStats = \DB::table('tracker_player_match_stats')
                ->where('player_id', $player->id)
                ->whereNotNull('skill_rating')
                ->selectRaw('MAX(skill_rating) as peak, COUNT(*) as cnt')
                ->first();

            if ($ratingStats && $ratingStats->cnt > 0) {
                $enhancedRatingPeak = (float) $ratingStats->peak;
                $enhancedRatingMatches = (int) $ratingStats->cnt;

                $current = \DB::table('tracker_player_match_stats')
                    ->where('player_id', $player->id)
                    ->whereNotNull('skill_rating')
                    ->orderByDesc('match_id')
                    ->value('skill_rating');
                $enhancedRating = $current !== null ? (float) $current : null;
            }
        }

        return view('frontend.tracker.player-show', compact(
            'player', 'sessions', 'eloHistory', 'favoriteServers', 'favoriteMaps',
            'enhancedMatches', 'enhancedMatchesCount',
            'latestMatch', 'latestMatchStats', 'latestMatchWeapons',
            'enhancedRating', 'enhancedRatingPeak', 'enhancedRatingMatches'
        ));
    }

    /**
     * Claim a player profile as yours.
     */
    public function claimPlayer(Request $request, TrackerPlayer $player)
    {
        $user = $request->user();

        // Check if user already has a claimed player
        $existing = TrackerPlayer::where('user_id', $user->id)->first();
        if ($existing && $existing->id !== $player->id) {
            return back()->with('error', __('messages.already_claimed_other'));
        }

        // Check if player is already claimed by someone else
        if ($player->isClaimed() && $player->user_id !== $user->id) {
            return back()->with('error', __('messages.player_already_claimed'));
        }

        $player->update(['user_id' => $user->id]);
        return back()->with('success', __('messages.player_claimed'));
    }

    /**
     * Unclaim a player profile.
     */
    public function unclaimPlayer(Request $request, TrackerPlayer $player)
    {
        $user = $request->user();

        if ($player->user_id !== $user->id) {
            return back()->with('error', __('messages.not_your_player'));
        }

        $player->update(['user_id' => null]);
        return back()->with('success', __('messages.player_unclaimed'));
    }

    /**
     * API: Tracker Stats
     */
    public function apiStats()
    {
        return response()->json([
            'servers_online' => TrackerServer::where('is_online', true)->count(),
            'servers_tracked' => TrackerServer::active()->count(),
            'players_online' => TrackerServer::where('is_online', true)->sum('current_players'),
            'players_tracked' => TrackerPlayer::count(),
            'games' => TrackerGame::active()->withCount(['servers' => function ($q) {
                $q->where('is_online', true);
            }])->get()->map(fn ($g) => [
                'name' => $g->short_name,
                'servers_online' => $g->servers_count,
            ]),
        ]);
    }

    /**
     * API: Top servers by player count
     */
    public function apiTopServers(Request $request)
    {
        $limit = min((int) $request->input('limit', 10), 25);
        $servers = TrackerServer::with('game')
            ->where('is_online', true)
            ->where('current_players', '>', 0)
            ->orderByDesc('current_players')
            ->limit($limit)
            ->get()
            ->map(fn (\App\Models\Tracker\TrackerServer $s) => [
                'id' => $s->id,
                'name' => $s->hostname_clean,
                'ip' => $s->ip,
                'port' => $s->port,
                'game' => $s->game->short_name,
                'map' => $s->current_map,
                'players' => $s->current_players,
                'max_players' => $s->max_players,
                'country' => $s->country_code,
                'mod' => $s->mod_name,
                'url' => route('tracker.server.show', $s),
            ]);

        return response()->json(['servers' => $servers]);
    }

    /**
     * API: Top players by ELO
     */
    public function apiTopPlayers(Request $request)
    {
        $limit = min((int) $request->input('limit', 10), 25);
        $sort = $request->input('sort', 'elo');

        $query = TrackerPlayer::query();
        match ($sort) {
            'kills' => $query->orderByDesc('total_kills'),
            'playtime' => $query->orderByDesc('total_play_time_minutes'),
            'kd' => $query->orderByRaw('CASE WHEN total_deaths > 0 THEN total_kills / total_deaths ELSE total_kills END DESC'),
            default => $query->orderByDesc('elo_rating'),
        };

        $players = $query->limit($limit)->get()->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name_clean,
            'country' => $p->country_code,
            'elo' => $p->elo_rating,
            'kills' => $p->total_kills,
            'deaths' => $p->total_deaths,
            'kd' => $p->kd_ratio,
            'play_time_hours' => round($p->total_play_time_minutes / 60, 1),
            'url' => route('tracker.player.show', $p),
        ]);

        return response()->json(['players' => $players]);
    }

    /**
     * API: Player search
     */
    public function apiPlayerSearch(Request $request)
    {
        $q = $request->input('q', '');
        if (strlen($q) < 2) {
            return response()->json(['players' => []]);
        }

        $players = TrackerPlayer::where('name_clean', 'LIKE', "%{$q}%")
            ->orderByDesc('last_seen_at')
            ->limit(10)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name_clean,
                'country' => $p->country_code,
                'elo' => $p->elo_rating,
                'last_seen' => $p->last_seen_at?->diffForHumans(),
                'url' => route('tracker.player.show', $p),
            ]);

        return response()->json(['players' => $players]);
    }

    /**
     * API: Server list for auto-refresh
     */
    public function apiServers(Request $request)
    {
        $query = TrackerServer::active()->where('is_online', true)->with('game');

        if ($request->filled('game')) {
            $game = TrackerGame::where('slug', $request->game)->first();
            if ($game) {
                $query->where('game_id', $game->id);
            }
        }

        return response()->json(
            $query->orderByDesc('current_players')->limit(200)->get([
                'id', 'game_id', 'hostname_html', 'hostname_clean', 'ip', 'port',
                'current_map', 'current_players', 'max_players', 'gametype',
                'mod_name', 'country_code', 'is_online', 'needs_password',
            ])
        );
    }

    public function serverLiveData(TrackerServer $server)
    {
        $server->load('game');

        $activeSessions = $server->sessions()
            ->whereNull('ended_at')
            ->with('player')
            ->get()
            ->sortByDesc('score')
            ->values()
            ->map(fn(\App\Models\Tracker\TrackerPlayerSession $s) => [
                'player_name' => $s->player?->name_html ?: e($s->player->name_clean ?? 'Unknown'),
                'player_url' => $s->player ? route('tracker.player.show', $s->player) : null,
                'score' => $s->score,
                'duration' => $s->duration_minutes . 'm',
            ]);

        return response()->json([
            'is_online' => $server->is_online,
            'current_players' => $server->current_players,
            'max_players' => $server->max_players,
            'current_map' => $server->current_map,
            'gametype' => $server->gametype,
            'players' => $activeSessions,
            'last_seen' => $server->last_seen_at?->diffForHumans(),
            'map_file_slug' => \App\Services\Tracker\MapLinkService::findFile($server->current_map)?->slug ?? null,
        ]);
    }

    /**
     * API: Player profile by ID
     */
    public function apiPlayer($id)
    {
        $player = \App\Models\Tracker\TrackerPlayer::findOrFail($id);
        $recentSessions = $player->sessions()
            ->with('server:id,hostname_clean,ip,port')
            ->orderByDesc('started_at')
            ->limit(5)
            ->get()
            ->map(fn($s) => [
                'server' => $s->server?->hostname_clean,
                'map' => $s->map_name,
                'duration' => $s->duration_minutes . 'm',
                'kills' => $s->kills,
                'deaths' => $s->deaths,
                'started_at' => $s->started_at?->toIso8601String(),
            ]);

        return response()->json([
            'id' => $player->id,
            'name' => $player->name_clean,
            'country' => $player->country_code,
            'elo' => $player->elo_rating,
            'elo_peak' => $player->elo_peak,
            'kills' => $player->total_kills,
            'deaths' => $player->total_deaths,
            'kd' => $player->kd_ratio,
            'total_xp' => $player->total_xp,
            'play_time_hours' => round($player->total_play_time_minutes / 60, 1),
            'total_sessions' => $player->total_sessions,
            'first_seen' => $player->first_seen_at?->toIso8601String(),
            'last_seen' => $player->last_seen_at?->toIso8601String(),
            'recent_sessions' => $recentSessions,
            'url' => route('tracker.player.show', $player),
        ]);
    }

    /**
     * API: Global online count
     */
    public function apiOnline()
    {
        return response()->json([
            'players_online' => \App\Models\Tracker\TrackerServer::where('is_online', true)->sum('current_players'),
            'servers_online' => \App\Models\Tracker\TrackerServer::where('is_online', true)->count(),
        ]);
    }

    /**
     * API: Map stats — which servers currently play this map + history
     */
    public function apiMapStats($mapName)
    {
        $serversNow = \App\Models\Tracker\TrackerServer::with('game')
            ->where('is_online', true)
            ->where('current_map', $mapName)
            ->orderByDesc('current_players')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->hostname_clean,
                'ip' => $s->ip,
                'port' => $s->port,
                'game' => $s->game->short_name,
                'players' => $s->current_players,
                'max_players' => $s->max_players,
                'country' => $s->country_code,
                'mod' => $s->mod_name,
                'connect' => 'et://' . $s->ip . ':' . $s->port,
                'url' => route('tracker.server.show', $s),
            ]);

        $stats = \App\Models\Tracker\TrackerServerMapStat::where('map_name', $mapName)
            ->selectRaw('SUM(times_played) as total_played, SUM(total_time_minutes) as total_minutes, AVG(avg_players) as avg_players, MAX(peak_players) as peak_players, MAX(last_played_at) as last_played_at')
            ->first();

        return response()->json([
            'map_name' => $mapName,
            'servers_playing_now' => $serversNow->count(),
            'servers' => $serversNow,
            'total_times_played' => (int) ($stats->total_played ?? 0),
            'avg_players' => round($stats->avg_players ?? 0, 1),
            'peak_players' => (int) ($stats->peak_players ?? 0),
            'last_played_at' => $stats->last_played_at ?? null,
        ]);
    }

}
