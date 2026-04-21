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

        // "Top Servers" shows real human activity, so filter + sort by humans only
        $topServers = TrackerServer::where('is_online', true)
            ->whereRaw('(current_players - COALESCE(bot_count, 0)) > 0')
            ->with('game')
            ->orderByRaw('(current_players - COALESCE(bot_count, 0)) DESC')
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

        // Helper: parse ?key=A, ?key=A,B, or ?key[]=A&key[]=B into a clean array
        $asArray = function ($value) {
            if (is_array($value)) {
                return array_values(array_filter($value, fn($v) => $v !== '' && $v !== null));
            }
            if ($value === null || $value === '') {
                return [];
            }
            // Support comma-separated values: "DE,FR" -> ["DE", "FR"]
            return array_values(array_filter(array_map('trim', explode(',', (string) $value)), fn($v) => $v !== ''));
        };

        // Game filter (multi)
        $gameSlugs = $asArray($request->input('game'));
        if (!empty($gameSlugs)) {
            $gameIds = TrackerGame::whereIn('slug', $gameSlugs)->pluck('id');
            if ($gameIds->isNotEmpty()) {
                $query->whereIn('game_id', $gameIds);
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

        // Country filter (multi)
        $countryCodes = $asArray($request->input('country'));
        if (!empty($countryCodes)) {
            $query->whereIn('country_code', $countryCodes);
        }

        // Map filter (substring)
        if ($request->filled('map')) {
            $query->where('current_map', 'like', '%' . $request->map . '%');
        }

        // Mod filter (multi)
        $mods = $asArray($request->input('mod'));
        if (!empty($mods)) {
            $query->whereIn('mod_name', $mods);
        }

        // Gametype filter (multi) — values in DB are strings like "2", "3", "war"
        $gametypes = $asArray($request->input('gametype'));
        if (!empty($gametypes)) {
            $query->whereIn('gametype', $gametypes);
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
        $dir  = $request->get('dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $query = match($sort) {
            'name'     => $query->orderBy('hostname_clean', $dir),
            'map'      => $query->orderBy('current_map', $dir),
            'country'  => $query->orderBy('country_code', $dir),
            'game'     => $query->orderBy('game_id', $dir),
            'mod'      => $query->orderBy('mod_name', $dir),
            'gametype' => $query->orderBy('gametype', $dir),
            'ping'     => $query->orderByRaw('latency_ms IS NULL, latency_ms ' . $dir),
            'players'  => $query->orderByRaw('(current_players - COALESCE(bot_count, 0)) ' . $dir),
            default    => $query->orderByRaw('(current_players - COALESCE(bot_count, 0)) DESC'),
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

        // Distinct gametypes from DB for filter checkboxes
        $gametypes = TrackerServer::active()
            ->whereNotNull('gametype')
            ->where('gametype', '!=', '')
            ->select('gametype')
            ->distinct()
            ->orderBy('gametype')
            ->pluck('gametype');

        return view('frontend.tracker.servers', compact('servers', 'games', 'countries', 'mods', 'gametypes'));
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

        // Activity timeline — built below when we have enhanced data.
        $playerTimeline = [];

        // Refresh ELO if stale (> 24h old or never computed).
        // Bots are excluded from ELO entirely — they never get a rating.
        // Score-less servers (trickjump/fun) produce NULL on purpose.
        if (!$player->is_bot) {
            $stale = $player->elo_updated_at === null
                || \Carbon\Carbon::parse($player->elo_updated_at)->lt(now()->subDay());
            if ($stale) {
                app(\App\Services\Tracker\EloService::class)
                    ->calculateForPlayer($player->id);
                $player->refresh();
            }
        }

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
        if ($player->has_enhanced_data) {
            $playerTimeline = $this->buildPlayerTimeline($player->id, 20);
        }

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

        // Lifetime per-weapon totals (cumulative across ALL matches the
        // player has ever played on Enhanced Tracker servers). This is the
        // main weapon-profile — shows true skill with each weapon across
        // everything, not just the latest match.
        $lifetimeWeapons = collect();
        if ($player->has_enhanced_data) {
            $lifetimeWeapons = \DB::table('tracker_player_weapon_stats')
                ->where('player_id', $player->id)
                ->orderByDesc('total_kills')
                ->orderByDesc('total_atts')
                ->get();
        }

        // Enhanced Rating overview:
        //   - current: skill_rating of the most recent rated match
        //   - peak:    highest single-match rating ever
        //   - weighted avg: mean across all rated matches,
        //       weighted by (time_played_pct * match_duration_seconds).
        //     Short/warmup matches contribute little; long/full-participation
        //     matches contribute proportionally more.
        //
        // Why a fallback of 300s for duration: matches still in progress have
        // duration_seconds = 0. We don't want those dropped entirely, but we
        // also don't want them weighted as if they were the average length.
        // 300s (5 min) is a conservative "assumed partial match" weight.
        $enhancedRating = null;
        $enhancedRatingPeak = null;
        $enhancedRatingAvg = null;
        $enhancedRatingMatches = 0;
        if ($player->has_enhanced_data) {
            $ratingStats = \DB::selectOne(
                'SELECT
                    MAX(ms.skill_rating) AS peak,
                    COUNT(*) AS cnt,
                    SUM(ms.skill_rating
                        * COALESCE(ms.time_played_pct, 100)
                        * GREATEST(COALESCE(m.duration_seconds, 0), 300))
                      /
                    SUM(COALESCE(ms.time_played_pct, 100)
                        * GREATEST(COALESCE(m.duration_seconds, 0), 300))
                      AS weighted
                 FROM tracker_player_match_stats ms
                 JOIN tracker_matches m ON m.id = ms.match_id
                 WHERE ms.player_id = ?
                   AND ms.skill_rating IS NOT NULL',
                [$player->id]
            );

            if ($ratingStats && (int) $ratingStats->cnt > 0) {
                $enhancedRatingPeak = (float) $ratingStats->peak;
                $enhancedRatingMatches = (int) $ratingStats->cnt;
                $enhancedRatingAvg = $ratingStats->weighted !== null
                    ? (float) $ratingStats->weighted
                    : null;

                $current = \DB::table('tracker_player_match_stats')
                    ->where('player_id', $player->id)
                    ->whereNotNull('skill_rating')
                    ->orderByDesc('match_id')
                    ->value('skill_rating');
                $enhancedRating = $current !== null ? (float) $current : null;
            }
        }

        // XP Skills breakdown: aggregate per-skill XP from raw_skills JSON.
        // ET Legacy has 7 skill categories (index 0-6). Each match records
        // current + delta per active skill. We take MAX(current) as the
        // player's highest reached level, SUM(delta) as the XP earned on
        // Enhanced Tracker servers. Level thresholds: 20 / 50 / 90 / 140 XP.
        $xpSkills = [];
        if ($player->has_enhanced_data) {
            $rawSkillsRows = \DB::table('tracker_player_match_stats')
                ->where('player_id', $player->id)
                ->whereNotNull('raw_skills')
                ->where('raw_skills', '!=', '')
                ->pluck('raw_skills');

            $perSkill = [];
            foreach ($rawSkillsRows as $json) {
                $decoded = json_decode($json, true);
                if (!is_array($decoded) || !isset($decoded['skills']) || !is_array($decoded['skills'])) {
                    continue;
                }
                foreach ($decoded['skills'] as $idx => $data) {
                    $idx = (int) $idx;
                    if (!isset($perSkill[$idx])) {
                        $perSkill[$idx] = ['max_current' => 0, 'total_delta' => 0];
                    }
                    if (is_array($data)) {
                        $cur = (int) ($data['current'] ?? 0);
                        $del = (int) ($data['delta'] ?? 0);
                    } else {
                        $cur = (int) $data;
                        $del = 0;
                    }
                    if ($cur > $perSkill[$idx]['max_current']) {
                        $perSkill[$idx]['max_current'] = $cur;
                    }
                    $perSkill[$idx]['total_delta'] += $del;
                }
            }

            $skillMeta = [
                0 => ['name' => 'Battle Sense',              'color' => 'violet'],
                1 => ['name' => 'Explosives & Construction', 'color' => 'amber'],
                2 => ['name' => 'First Aid',                 'color' => 'rose'],
                3 => ['name' => 'Signals',                   'color' => 'yellow'],
                4 => ['name' => 'Light Weapons',             'color' => 'sky'],
                5 => ['name' => 'Heavy Weapons',             'color' => 'red'],
                6 => ['name' => 'Covert Ops',                'color' => 'emerald'],
            ];
            $thresholds = [20, 50, 90, 140];

            foreach ($skillMeta as $idx => $meta) {
                $cur   = $perSkill[$idx]['max_current'] ?? 0;
                $delta = $perSkill[$idx]['total_delta'] ?? 0;
                $level = 0;
                foreach ($thresholds as $t) {
                    if ($cur >= $t) { $level++; }
                }
                $prev = $level > 0 ? $thresholds[$level - 1] : 0;
                $next = $thresholds[$level] ?? null;
                if ($next !== null) {
                    $progress = ($cur - $prev) / max(1, ($next - $prev)) * 100;
                    $progress = max(0.0, min(100.0, (float) $progress));
                } else {
                    $progress = 100.0;
                }
                $xpSkills[$idx] = [
                    'index'          => $idx,
                    'name'           => $meta['name'],
                    'color'          => $meta['color'],
                    'current'        => $cur,
                    'delta'          => $delta,
                    'level'          => $level,
                    'progress'       => $progress,
                    'prev_threshold' => $prev,
                    'next_threshold' => $next,
                ];
            }
        }

        return view('frontend.tracker.player-show', compact(
            'player', 'sessions', 'eloHistory', 'favoriteServers', 'favoriteMaps',
            'enhancedMatches', 'enhancedMatchesCount',
            'latestMatch', 'latestMatchStats', 'latestMatchWeapons',
            'enhancedRating', 'enhancedRatingPeak', 'enhancedRatingAvg', 'enhancedRatingMatches',
            'lifetimeWeapons',
            'playerTimeline', 'xpSkills'));
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

        // ---- Real human players: from tracker_player_sessions + snapshots ----
        $sessions = $server->sessions()
            ->whereNull('ended_at')
            ->with('player:id,name_clean,name_html,country,country_code')
            ->orderByDesc('score')
            ->get();

        $sessionIds = $sessions->pluck('id')->all();
        $latestPings = [];
        $latestTeams = [];
        if (!empty($sessionIds)) {
            $latestRows = \DB::table('tracker_player_snapshots as s')
                ->select('s.session_id', 's.ping', 's.team')
                ->whereIn('s.session_id', $sessionIds)
                ->whereRaw('s.polled_at = (SELECT MAX(polled_at) FROM tracker_player_snapshots WHERE session_id = s.session_id)')
                ->get();
            foreach ($latestRows as $r) {
                $latestPings[$r->session_id] = $r->ping;
                $latestTeams[$r->session_id] = $r->team;
            }
        }

        $humanPlayers = $sessions->map(fn(\App\Models\Tracker\TrackerPlayerSession $s) => [
            'player_name'  => $s->player?->name_html ?: e($s->player->name_clean ?? 'Unknown'),
            'player_url'   => $s->player ? route('tracker.player.show', $s->player) : null,
            'country_code' => $s->player?->country_code,
            'country'      => $s->player?->country,
            'score'        => (int) $s->score,
            'ping'         => $latestPings[$s->id] ?? null,
            'team'         => $latestTeams[$s->id] ?? null,
            'duration'     => $s->duration_minutes . 'm',
            'is_bot'       => false,
        ])->all();

        // ---- Bots: pulled live from the server, cached 60s to avoid UDP-spamming ----
        // Bots don't get tracked in the DB (generic names, no aliases, no statistics).
        // We poll the server on page load (cached) to show a live list of who's bot-fighting.
        $botPlayers = \Illuminate\Support\Facades\Cache::remember(
            "tracker:live_bots:{$server->id}",
            now()->addSeconds(60),
            function () use ($server) {
                try {
                    $q = app(\App\Services\Tracker\ServerQueryService::class);
                    $data = $q->queryServer($server->ip, $server->port);
                    if ($data === null || empty($data['players'])) {
                        return [];
                    }

                    $bots = [];
                    foreach ($data['players'] as $p) {
                        $ping = (int) ($p['ping'] ?? 0);
                        if ($ping !== 0) continue; // not a bot

                        $rawName = (string) ($p['name'] ?? 'Bot');
                        $clean = \App\Services\Tracker\ColorCodeService::toClean($rawName);
                        $html  = \App\Services\Tracker\ColorCodeService::toHtml($rawName);

                        $bots[] = [
                            'player_name'  => $html ?: e($clean),
                            'player_url'   => null,           // bots don't get profile pages
                            'country_code' => null,           // bots don't have countries
                            'country'      => null,
                            'score'        => (int) ($p['score'] ?? 0),
                            'ping'         => 0,              // render as "BOT" badge in UI
                            'team'         => $p['team'] ?? null,
                            'duration'     => '-',
                            'is_bot'       => true,
                        ];
                    }
                    return $bots;
                } catch (\Throwable $e) {
                    // If live poll fails, just show humans (no bots)
                    return [];
                }
            }
        );

        // Merge humans + bots, sort by score desc for display
        $allPlayers = array_merge($humanPlayers, $botPlayers);
        usort($allPlayers, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

        return response()->json([
            'is_online' => $server->is_online,
            'current_players' => $server->current_players,
            'max_players' => $server->max_players,
            'current_map' => $server->current_map,
            'gametype' => $server->gametype,
            'players' => $allPlayers,
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
    /**
     * Build a grouped activity timeline for a player's enhanced-tracker matches.
     *
     * Consecutive matches with the same (server_id, map_name) are collapsed
     * into a single timeline entry. Useful because manual map_restart events
     * create many short matches that would otherwise clutter the UI — a
     * kerkyra session with 10 restarts becomes one "kerkyra · 10 rounds"
     * entry rather than ten repetitive rows.
     *
     * Each entry aggregates kills/deaths/headshots/damage/duration across
     * the group and carries the latest skill rating (for the jump-in value)
     * and the class taken during the session. match_ids are kept for
     * drill-down navigation later.
     *
     * Returns at most $limit timeline entries, newest first.
     */
    private function buildPlayerTimeline(int $playerId, int $limit = 20): array
    {
        // Pull more raw matches than $limit — many collapse into fewer groups.
        $matches = \DB::table('tracker_player_match_stats as ms')
            ->join('tracker_matches as m', 'm.id', '=', 'ms.match_id')
            ->leftJoin('tracker_servers as s', 's.id', '=', 'm.server_id')
            ->where('ms.player_id', $playerId)
            ->orderByDesc('m.started_at')
            ->limit($limit * 15)
            ->select([
                'm.id as match_id',
                'm.server_id',
                'm.map_name',
                'm.started_at',
                'm.ended_at',
                'm.duration_seconds',
                'ms.kills', 'ms.deaths', 'ms.headshots', 'ms.class',
                'ms.skill_rating', 'ms.skill_rating_delta',
                'ms.time_played_pct',
                'ms.damage_given', 'ms.damage_received',
                's.hostname_clean as server_name',
                's.hostname_html as server_html',
            ])
            ->get();

        $groups = [];
        $currentGroup = null;

        foreach ($matches as $m) {
            $key = $m->server_id . ':' . $m->map_name;

            if ($currentGroup === null || $currentGroup['key'] !== $key) {
                if ($currentGroup !== null) {
                    $groups[] = $currentGroup;
                    if (count($groups) >= $limit) {
                        $currentGroup = null;
                        break;
                    }
                }
                $currentGroup = [
                    'key' => $key,
                    'server_id' => $m->server_id,
                    'server_name' => $m->server_name,
                    'server_html' => $m->server_html,
                    'map_name' => $m->map_name,
                    'ended_at' => $m->ended_at,
                    'started_at' => $m->started_at,
                    'match_count' => 0,
                    'total_kills' => 0,
                    'total_deaths' => 0,
                    'total_headshots' => 0,
                    'total_duration' => 0,
                    'total_damage_given' => 0,
                    'total_damage_received' => 0,
                    'latest_rating' => $m->skill_rating !== null ? (float) $m->skill_rating : null,
                    'latest_rating_delta' => $m->skill_rating_delta !== null ? (float) $m->skill_rating_delta : null,
                    'class' => $m->class,
                    'match_ids' => [],
                ];
            }

            $currentGroup['match_count']++;
            $currentGroup['total_kills'] += (int) $m->kills;
            $currentGroup['total_deaths'] += (int) $m->deaths;
            $currentGroup['total_headshots'] += (int) $m->headshots;
            $currentGroup['total_duration'] += (int) ($m->duration_seconds ?? 0);
            $currentGroup['total_damage_given'] += (int) $m->damage_given;
            $currentGroup['total_damage_received'] += (int) $m->damage_received;
            $currentGroup['started_at'] = $m->started_at;
            $currentGroup['match_ids'][] = $m->match_id;
        }

        if ($currentGroup !== null && count($groups) < $limit) {
            $groups[] = $currentGroup;
        }

        return $groups;
    }

    /**
     * GET /api/v1/tracker/servers/{id}
     * Full server detail with banner/embed URLs.
     */
    public function apiServerDetail(int $id)
    {
        $s = \App\Models\Tracker\TrackerServer::find($id);
        if (!$s) {
            return response()->json(['error' => 'server_not_found'], 404);
        }

        return response()->json([
            'id'              => $s->id,
            'game_id'         => $s->game_id,
            'hostname'        => $s->hostname,
            'hostname_clean'  => $s->hostname_clean,
            'hostname_html'   => $s->hostname_html,
            'ip'              => $s->ip,
            'port'            => (int) $s->port,
            'country_code'    => $s->country_code,
            'city'            => $s->city,
            'current_map'     => $s->current_map,
            'current_players' => (int) $s->current_players,
            'max_players'     => (int) $s->max_players,
            'gametype'        => $s->gametype,
            'mod_name'        => $s->mod_name,
            'is_online'       => (bool) $s->is_online,
            'needs_password'  => (bool) $s->needs_password,
            'last_poll_at'    => $s->last_poll_at,
            'banner_url'      => url("/tracker/server/{$s->id}/banner.png"),
            'embed_url'       => url("/tracker/server/{$s->id}/embed"),
        ]);
    }

    /**
     * GET /api/v1/tracker/servers/{id}/rank
     * Materialized ranking position within the same game (30d avg players).
     */
    public function apiServerRank(int $id)
    {
        $row = \DB::table('tracker_server_rankings')
            ->where('server_id', $id)
            ->first(['server_id', 'game_id', 'rank', 'total_in_game', 'avg_players_30d', 'polls_counted', 'computed_at']);

        if (!$row) {
            return response()->json(['error' => 'not_ranked', 'reason' => 'insufficient_data_or_snapshot_pending'], 404);
        }

        return response()->json([
            'server_id'       => (int) $row->server_id,
            'game_id'         => (int) $row->game_id,
            'rank'            => (int) $row->rank,
            'total_in_game'   => (int) $row->total_in_game,
            'avg_players_30d' => (float) $row->avg_players_30d,
            'polls_counted'   => (int) $row->polls_counted,
            'computed_at'     => $row->computed_at,
        ]);
    }

    /**
     * GET /api/v1/tracker/servers/{id}/top-players
     * Materialized top-8 all-time players by cumulative XP.
     */
    public function apiServerTopPlayers(int $id)
    {
        $rows = \DB::table('tracker_server_top_players')
            ->where('server_id', $id)
            ->orderBy('rank')
            ->get(['rank', 'player_id', 'name_clean', 'name_html', 'total_xp', 'total_minutes', 'computed_at']);

        return response()->json([
            'server_id' => $id,
            'count'     => $rows->count(),
            'players'   => $rows->map(fn ($p) => [
                'rank'          => (int) $p->rank,
                'player_id'     => (int) $p->player_id,
                'name'          => $p->name_clean,
                'name_html'     => $p->name_html,
                'total_xp'      => (int) $p->total_xp,
                'total_minutes' => (int) $p->total_minutes,
            ])->values(),
            'computed_at' => optional($rows->first())->computed_at,
        ]);
    }

    /**
     * GET /api/v1/tracker/servers/{id}/online
     * Current online players on this server (open sessions).
     */
    public function apiServerOnline(int $id)
    {
        $rows = \DB::table('tracker_player_sessions')
            ->where('tracker_player_sessions.server_id', $id)
            ->whereNull('tracker_player_sessions.ended_at')
            ->join('tracker_players', 'tracker_players.id', '=', 'tracker_player_sessions.player_id')
            ->orderBy('tracker_player_sessions.started_at')
            ->select([
                'tracker_players.id as player_id',
                'tracker_players.name_clean',
                'tracker_players.name_html',
                'tracker_players.country_code',
                'tracker_player_sessions.score',
                'tracker_player_sessions.team',
                'tracker_player_sessions.map_name',
                'tracker_player_sessions.started_at',
            ])
            ->get();

        return response()->json([
            'server_id' => $id,
            'count'     => $rows->count(),
            'players'   => $rows->map(fn ($p) => [
                'player_id'    => (int) $p->player_id,
                'name'         => $p->name_clean,
                'name_html'    => $p->name_html,
                'country_code' => $p->country_code,
                'score'        => (int) $p->score,
                'team'         => $p->team,
                'map'          => $p->map_name,
                'started_at'   => $p->started_at,
            ])->values(),
        ]);
    }

    /**
     * GET /api/v1/tracker/servers/{id}/history?hours=24
     * Time-series poll data (up to 168h = 1 week).
     */
    public function apiServerHistory(int $id, \Illuminate\Http\Request $request)
    {
        $hours = max(1, min(168, (int) $request->query('hours', 24)));

        $points = \DB::table('tracker_server_history')
            ->where('server_id', $id)
            ->where('polled_at', '>=', now()->subHours($hours))
            ->orderBy('polled_at')
            ->get(['polled_at', 'players', 'max_players', 'map', 'gametype']);

        return response()->json([
            'server_id' => $id,
            'hours'     => $hours,
            'count'     => $points->count(),
            'points'    => $points->map(fn ($p) => [
                'polled_at'   => $p->polled_at,
                'players'     => (int) $p->players,
                'max_players' => (int) $p->max_players,
                'map'         => $p->map,
                'gametype'    => $p->gametype,
            ])->values(),
        ]);
    }

}