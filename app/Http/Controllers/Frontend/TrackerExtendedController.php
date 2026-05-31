<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Tracker\TrackerClan;
use App\Models\Tracker\TrackerClanMember;
use App\Models\Tracker\TrackerPlayer;
use App\Models\Tracker\TrackerServer;
use App\Models\Tracker\TrackerServerHistory;
use App\Models\Tracker\TrackerRanking;
use App\Models\Tracker\TrackerServerRating;
use App\Models\Tracker\TrackerScrim;
use App\Models\Tracker\TrackerGame;
use App\Services\Tracker\RankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TrackerExtendedController extends Controller
{
    // ── World Map ──
    public function worldMap()
    {
        $servers = Cache::remember('tracker:map:servers', 120, function () {
            return TrackerServer::where('is_online', true)
                ->whereNotNull('latitude')->whereNotNull('longitude')
                ->select(['id','hostname_clean','ip','port','current_players','max_players',
                    'current_map','latitude','longitude','country_code','country','mod_name'])
                ->get();
        });

        $stats = Cache::remember('tracker:map:stats', 120, function () {
            return [
                'total_servers' => TrackerServer::where('is_online', true)->count(),
                'total_players' => TrackerServer::where('is_online', true)->sum('current_players'),
                'countries' => TrackerServer::where('is_online', true)->whereNotNull('country_code')
                    ->select('country_code','country', DB::raw('COUNT(*) as count'), DB::raw('SUM(current_players) as players'))
                    ->groupBy('country_code','country')->orderByDesc('players')->get(),
            ];
        });

        return view('frontend.tracker.world-map', compact('servers', 'stats'));
    }

    // ── Rankings ──
    // Game family mapping for ET/RtCW ranking tabs
    private const GAME_FAMILY_ET = [1, 2, 3, 4, 5];
    private const GAME_FAMILY_RTCW = [6, 7, 8, 9, 10];

    /**
     * Rankings landing page - teasers per game family.
     */
    public function rankings()
    {
        $data = [];
        foreach (['et', 'rtcw'] as $fam) {
            $data[$fam]['servers'] = DB::table('tracker_server_rankings as r')
                ->join('tracker_servers as s', 's.id', '=', 'r.server_id')
                ->where('r.game_family', $fam)
                ->orderBy('r.rank')
                ->limit(5)
                ->get([
                    'r.rank', 'r.avg_players_30d', 'r.peak_players_30d',
                    's.id as server_id', 's.hostname_clean', 's.country',
                ]);

            $data[$fam]['players'] = DB::table('tracker_player_rankings_30d as r')
                ->join('tracker_players as p', 'p.id', '=', 'r.player_id')
                ->where('r.game_family', $fam)
                ->orderBy('r.rank')
                ->limit(5)
                ->get([
                    'r.rank', 'r.playtime_minutes_30d', 'r.elo_rating',
                    'p.id as player_id', 'p.name_clean', 'p.name_html', 'p.country_code',
                ]);
        }

        $lastComputed = DB::table('tracker_server_rankings')->max('computed_at');

        return view('frontend.tracker.rankings', compact('data', 'lastComputed'));
    }

    /**
     * Detailed server rankings with ET/RtCW tabs and sortable columns.
     */
    public function serverRankings(Request $request)
    {
        $game = $request->get('game') === 'rtcw' ? 'rtcw' : 'et';

        $sortable = [
            'rank'           => 'r.rank',
            'hostname'       => 's.hostname_clean',
            'avg_players'    => 'r.avg_players_30d',
            'peak_players'   => 'r.peak_players_30d',
            'playtime'       => 'r.total_playtime_minutes_30d',
            'unique_players' => 'r.unique_players_30d',
            'uptime'         => '(r.online_polls_30d / GREATEST(r.total_polls_30d, 1))',
        ];

        $sort = $request->get('sort', 'rank');
        if (! array_key_exists($sort, $sortable)) {
            $sort = 'rank';
        }

        $requestedDir = $request->get('dir');
        if (in_array($requestedDir, ['asc', 'desc'], true)) {
            $dir = $requestedDir;
        } else {
            $dir = in_array($sort, ['rank', 'hostname'], true) ? 'asc' : 'desc';
        }

        $query = DB::table('tracker_server_rankings as r')
            ->join('tracker_servers as s', 's.id', '=', 'r.server_id')
            ->where('r.game_family', $game)
            ->select([
                'r.rank', 'r.avg_players_30d', 'r.peak_players_30d',
                'r.total_playtime_minutes_30d', 'r.unique_players_30d',
                'r.total_polls_30d', 'r.online_polls_30d',
                's.id as server_id', 's.hostname_clean', 's.country',
                's.current_players', 's.max_players', 's.is_online',
            ]);

        if ($sort === 'uptime') {
            $query->orderByRaw($sortable['uptime'] . ' ' . $dir);
        } else {
            $query->orderBy($sortable[$sort], $dir);
        }

        if ($sort !== 'rank') {
            $query->orderBy('r.rank', 'asc');
        }

        $rankings = $query->paginate(50)->withQueryString();
        $lastComputed = DB::table('tracker_server_rankings')->max('computed_at');

        return view('frontend.tracker.rankings-servers', compact('rankings', 'game', 'sort', 'dir', 'lastComputed'));
    }

    /**
     * Detailed player rankings with ET/RtCW tabs and sortable columns.
     */
    public function playerRankings(Request $request)
    {
        $game = $request->get('game') === 'rtcw' ? 'rtcw' : 'et';

        $sortable = [
            'rank'           => 'r.rank',
            'name'           => 'p.name_clean',
            'playtime'       => 'r.playtime_minutes_30d',
            'sessions'       => 'r.sessions_count_30d',
            'unique_servers' => 'r.unique_servers_30d',
            'unique_maps'    => 'r.unique_maps_30d',
            'elo'            => 'r.elo_rating',
        ];

        $sort = $request->get('sort', 'rank');
        if (! array_key_exists($sort, $sortable)) {
            $sort = 'rank';
        }

        $requestedDir = $request->get('dir');
        if (in_array($requestedDir, ['asc', 'desc'], true)) {
            $dir = $requestedDir;
        } else {
            $dir = in_array($sort, ['rank', 'name'], true) ? 'asc' : 'desc';
        }

        $query = DB::table('tracker_player_rankings_30d as r')
            ->join('tracker_players as p', 'p.id', '=', 'r.player_id')
            ->where('r.game_family', $game)
            ->select([
                'r.rank', 'r.playtime_minutes_30d', 'r.sessions_count_30d',
                'r.unique_servers_30d', 'r.unique_maps_30d', 'r.elo_rating',
                'p.id as player_id', 'p.name_clean', 'p.name_html', 'p.country_code',
            ]);

        if ($sort === 'elo') {
            // Push NULL elo to the end
            $query->orderByRaw('r.elo_rating IS NULL, r.elo_rating ' . $dir);
        } else {
            $query->orderBy($sortable[$sort], $dir);
        }

        if ($sort !== 'rank') {
            $query->orderBy('r.rank', 'asc');
        }

        $rankings = $query->paginate(50)->withQueryString();
        $lastComputed = DB::table('tracker_player_rankings_30d')->max('computed_at');

        return view('frontend.tracker.rankings-players', compact('rankings', 'game', 'sort', 'dir', 'lastComputed'));
    }

    // ── Clans ──
    public function clans(Request $request)
    {
        $query = TrackerClan::where('status', 'active');

        if ($search = $request->get('search')) {
            $query->where(fn($q) => $q->where('tag_clean', 'LIKE', "%{$search}%")->orWhere('name', 'LIKE', "%{$search}%"));
        }

        $sort = $request->get('sort', 'members');
        $query = match ($sort) {
            'elo'      => $query->orderByDesc('avg_elo'),
            'active'   => $query->orderByDesc('active_member_count'),
            'playtime' => $query->orderByDesc('total_play_time_minutes'),
            'recent'   => $query->orderByDesc('last_seen_at'),
            default    => $query->orderByDesc('member_count'),
        };

        $clans = $query->where('member_count', '>', 0)->paginate(50)->withQueryString();
        return view('frontend.tracker.clans', compact('clans', 'sort'));
    }

    /**
     * Clan detail page.
     *
     * Two states:
     *   - Auto clan (no registered clans-row yet)  -> lean auto view + Claim button
     *   - Claimed clan (has registered clans-row)  -> full editable page
     */
    public function recruiting()
    {
        $clans = \App\Models\Clan::query()
            ->where('is_published', true)
            ->where('is_recruiting', true)
            ->with(['trackerClan', 'news' => fn($q) => $q->limit(1)])
            ->withCount('news')
            ->orderByDesc('updated_at')
            ->paginate(12);

        return view('frontend.clan.recruiting', compact('clans'));
    }

    public function clanShow(TrackerClan $clan)
    {
        $clan->load(['activeMembers.player', 'activeMembers.squad', 'squads']);

        // Top players by ELO (used by the lean/auto roster)
        $topPlayers = TrackerPlayer::whereIn('id', $clan->activeMembers->pluck('player_id'))
            ->where('status', 'active')
            ->orderByDesc('elo_rating')
            ->limit(50)->get();

        $recentActivity = DB::table('tracker_player_sessions')
            ->whereIn('player_id', $clan->activeMembers->pluck('player_id'))
            ->where('started_at', '>=', now()->subDays(7))
            ->select(DB::raw('DATE(started_at) as date'), DB::raw('COUNT(*) as sessions'), DB::raw('SUM(duration_minutes) as minutes'))
            ->groupBy('date')->orderBy('date')->get();

        // --- Registered clan (clans table) = the editable page owner ---
        $registered = \App\Models\Clan::where('tracker_clan_id', $clan->id)->first();

        $news = collect();
        $recruitmentPost = null;
        $clanServers = collect();
        $membersBySquad = collect();
        $unassignedMembers = collect();
        $managerRole = null;
        $userHasApplied = false;

        if ($registered) {
            // Published news posts
            $news = \App\Models\Post::where('clan_id', $registered->id)
                ->where('type', \App\Models\Post::TYPE_NEWS)
                ->where('is_published', true)
                ->latest('published_at')
                ->limit(10)->get();

            // Latest published recruitment post (for the Apply tab body)
            $recruitmentPost = \App\Models\Post::where('clan_id', $registered->id)
                ->where('type', \App\Models\Post::TYPE_RECRUITMENT)
                ->where('is_published', true)
                ->latest('published_at')->first();

            // Servers claimed by this clan
            $clanServers = TrackerServer::where('claimed_by_clan_id', $registered->id)
                ->orderByDesc('is_online')
                ->orderByDesc('current_players')
                ->get();

            // Roster grouped by squad (uses role_label + squad)
            $allMembers = $clan->activeMembers->load('player', 'squad')
                ->sortBy([['sort_order', 'asc']])
                ->filter(fn ($m) => $m->player !== null);

            $membersBySquad = $allMembers->filter(fn ($m) => $m->squad_id)
                ->groupBy(fn ($m) => optional($m->squad)->name ?? 'Squad');
            $unassignedMembers = $allMembers->filter(fn ($m) => ! $m->squad_id);

            // Current viewer management role (owner/admin/editor) for the manage button
            if (auth()->check()) {
                $managerRole = \App\Models\ClanManager::where('clan_id', $registered->id)
                    ->where('user_id', auth()->id())->value('role');

                $userHasApplied = \App\Models\ClanApplication::where('clan_id', $registered->id)
                    ->where('applicant_user_id', auth()->id())
                    ->where('status', \App\Models\ClanApplication::STATUS_PENDING)
                    ->exists();
            }

            // View counter (registered clans only)
            $registered->increment('view_count');
        }

        return view('frontend.tracker.clan-show', compact(
            'clan', 'topPlayers', 'recentActivity',
            'registered', 'news', 'recruitmentPost', 'clanServers',
            'membersBySquad', 'unassignedMembers', 'managerRole', 'userHasApplied'
        ));
    }
    public function playerCompare(Request $request)
    {
        $player1 = $request->has('p1') ? TrackerPlayer::find($request->get('p1')) : null;
        $player2 = $request->has('p2') ? TrackerPlayer::find($request->get('p2')) : null;

        $searchResults = null;
        if ($search = $request->get('search')) {
            $searchResults = TrackerPlayer::where('status', 'active')
                ->where('name_clean', 'LIKE', "%{$search}%")
                ->orderByDesc('total_play_time_minutes')
                ->limit(20)->get();
        }

        $comparison = null;
        if ($player1 && $player2) {
            $metrics = [
                ['label' => 'ELO Rating', 'v1' => $player1->elo_rating, 'v2' => $player2->elo_rating, 'fmt' => 'number'],
                ['label' => 'Total XP', 'v1' => $player1->total_xp, 'v2' => $player2->total_xp, 'fmt' => 'number'],
                ['label' => 'Play Time', 'v1' => $player1->total_play_time_minutes, 'v2' => $player2->total_play_time_minutes, 'fmt' => 'time'],
                ['label' => 'Kills', 'v1' => $player1->total_kills, 'v2' => $player2->total_kills, 'fmt' => 'number'],
                ['label' => 'Deaths', 'v1' => $player1->total_deaths, 'v2' => $player2->total_deaths, 'fmt' => 'number_low'],
                ['label' => 'K/D Ratio', 'v1' => $player1->kd_ratio * 100, 'v2' => $player2->kd_ratio * 100, 'fmt' => 'kd'],
                ['label' => 'Sessions', 'v1' => $player1->total_sessions, 'v2' => $player2->total_sessions, 'fmt' => 'number'],
                ['label' => 'Peak ELO', 'v1' => $player1->elo_peak, 'v2' => $player2->elo_peak, 'fmt' => 'number'],
            ];

            foreach ($metrics as &$m) {
                $m['winner'] = $m['fmt'] === 'number_low'
                    ? ($m['v1'] < $m['v2'] ? 1 : ($m['v2'] < $m['v1'] ? 2 : 0))
                    : ($m['v1'] > $m['v2'] ? 1 : ($m['v2'] > $m['v1'] ? 2 : 0));
            }

            $p1Servers = DB::table('tracker_player_sessions')->where('player_id', $player1->id)->distinct()->pluck('server_id')->toArray();
            $p2Servers = DB::table('tracker_player_sessions')->where('player_id', $player2->id)->distinct()->pluck('server_id')->toArray();
            $p1Maps = DB::table('tracker_player_sessions')->where('player_id', $player1->id)->distinct()->pluck('map_name')->toArray();
            $p2Maps = DB::table('tracker_player_sessions')->where('player_id', $player2->id)->distinct()->pluck('map_name')->toArray();

            $comparison = [
                'metrics' => $metrics,
                'shared_servers' => count(array_intersect($p1Servers, $p2Servers)),
                'shared_maps' => count(array_intersect($p1Maps, $p2Maps)),
            ];
        }

        return view('frontend.tracker.player-compare', compact('player1', 'player2', 'comparison', 'searchResults'));
    }

    // ── Server Ratings ──
    public function rateServer(Request $request, TrackerServer $server)
    {
        $request->validate(['rating' => 'required|integer|min:1|max:5', 'comment' => 'nullable|string|max:500']);
        TrackerServerRating::updateOrCreate(
            ['server_id' => $server->id, 'user_id' => auth()->id()],
            ['rating' => $request->rating, 'comment' => $request->comment]
        );
        return back()->with('success', 'Rating saved!');
    }

    // ── Scrims / Match Finder ──
    public function scrims(Request $request)
    {
        $query = TrackerScrim::with(['createdBy', 'clan'])->upcoming();
        if ($type = $request->get('type')) $query->where('game_type', $type);
        if ($region = $request->get('region')) $query->where('region', $region);

        $scrims = $query->orderBy('scheduled_at')->paginate(20)->withQueryString();
        return view('frontend.tracker.scrims', compact('scrims'));
    }

    public function scrimCreate()
    {
        $clans = TrackerClan::where('claimed_by_user_id', auth()->id())->get();
        return view('frontend.tracker.scrim-create', compact('clans'));
    }

    public function scrimStore(Request $request)
    {
        $v = $request->validate([
            'title' => 'required|string|max:255', 'description' => 'nullable|string|max:1000',
            'game_type' => 'required|in:1v1,2v2,3v3,5v5,6v6,mix',
            'map_preference' => 'nullable|string|max:100', 'mod_preference' => 'nullable|string|max:50',
            'region' => 'nullable|string|max:50', 'skill_level' => 'nullable|in:beginner,intermediate,advanced',
            'scheduled_at' => 'nullable|date|after:now', 'contact_discord' => 'nullable|string|max:100',
            'clan_id' => 'nullable|exists:tracker_clans,id',
        ]);
        $v['created_by_user_id'] = auth()->id();
        TrackerScrim::create($v);
        return redirect()->route('tracker.scrims')->with('success', 'Match created!');
    }

    // ── API Endpoints ──
    public function apiRankings(Request $request)
    {
        $period = $request->get('period', 'alltime');
        $latestDate = TrackerRanking::where('period', $period)->max('period_date');
        if (!$latestDate) return response()->json(['data' => []]);

        return response()->json(
            TrackerRanking::with('player:id,name,name_clean,country_code,elo_rating')
                ->where('period', $period)->where('period_date', $latestDate)
                ->orderBy('rank')->paginate($request->get('limit', 50))
        );
    }

    public function apiClans(Request $request)
    {
        $query = TrackerClan::where('status', 'active')->where('member_count', '>', 0);
        if ($q = $request->get('q')) $query->where(fn($qr) => $qr->where('tag_clean', 'LIKE', "%{$q}%")->orWhere('name', 'LIKE', "%{$q}%"));
        return response()->json($query->orderByDesc('member_count')->paginate($request->get('limit', 50)));
    }

    // === MATCH BROWSER (Commit 1) ===

    /**
     * Browse all past matches with time-range filter.
     * Solves the Trackbase pain point: "find a match from 8-12 hours ago".
     */
    public function matchesBrowse(Request $request)
    {
        $defaultFrom = now()->subDay()->format('Y-m-d\TH:i');
        $defaultTo = now()->format('Y-m-d\TH:i');

        $from = $request->input('from', $defaultFrom);
        $to = $request->input('to', $defaultTo);
        $serverId = $request->input('server_id');
        $mapName = $request->input('map_name');
        $minPlayers = (int) $request->input('min_players', 0);
        $perPage = min(100, max(10, (int) $request->input('per_page', 25)));

        $query = DB::table('tracker_matches as m')
            ->leftJoin('tracker_servers as s', 's.id', '=', 'm.server_id')
            ->whereNotNull('m.ended_at')
            ->select([
                'm.id', 'm.server_id', 'm.map_name', 'm.started_at', 'm.ended_at',
                'm.duration_seconds', 'm.player_count_max', 'm.player_count_avg',
                'm.total_kills', 'm.end_reason',
                's.hostname_clean', 's.hostname_html', 's.country_code', 's.ip', 's.port',
            ]);

        if ($from) {
            try { $query->where('m.started_at', '>=', \Carbon\Carbon::parse($from)); } catch (\Throwable $e) {}
        }
        if ($to) {
            try { $query->where('m.started_at', '<=', \Carbon\Carbon::parse($to)); } catch (\Throwable $e) {}
        }
        if ($serverId) {
            $query->where('m.server_id', (int) $serverId);
        }
        if ($mapName) {
            $query->where('m.map_name', $mapName);
        }
        if ($minPlayers > 0) {
            $query->where('m.player_count_max', '>=', $minPlayers);
        }

        $matches = $query->orderByDesc('m.started_at')->paginate($perPage)->withQueryString();

        $servers = Cache::remember('tracker:matches:servers', 600, function () {
            return DB::table('tracker_matches as m')
                ->join('tracker_servers as s', 's.id', '=', 'm.server_id')
                ->select('s.id', 's.hostname_clean', 's.country_code')
                ->groupBy('s.id', 's.hostname_clean', 's.country_code')
                ->orderBy('s.hostname_clean')
                ->get();
        });

        $maps = Cache::remember('tracker:matches:maps', 600, function () {
            return DB::table('tracker_matches')
                ->select('map_name', DB::raw('COUNT(*) as cnt'))
                ->whereNotNull('ended_at')
                ->groupBy('map_name')
                ->orderByDesc('cnt')
                ->limit(200)
                ->get();
        });

        return view('frontend.tracker.matches-browse', compact(
            'matches', 'servers', 'maps', 'from', 'to', 'serverId', 'mapName', 'minPlayers'
        ));
    }

    /**
     * Show single match with all participating players and their stats.
     */
    public function matchShow($matchId)
    {
        $match = DB::table('tracker_matches as m')
            ->leftJoin('tracker_servers as s', 's.id', '=', 'm.server_id')
            ->where('m.id', (int) $matchId)
            ->select([
                'm.*',
                's.hostname_clean', 's.hostname_html', 's.country_code', 's.country',
                's.ip', 's.port', 's.mod_name', 's.game_id',
            ])
            ->first();

        if (!$match) {
            abort(404, 'Match not found');
        }

        $players = DB::table('tracker_player_match_stats as ms')
            ->leftJoin('tracker_players as p', 'p.id', '=', 'ms.player_id')
            ->where('ms.match_id', (int) $matchId)
            ->select([
                'ms.player_id', 'ms.name_snapshot', 'ms.name_clean_snapshot',
                'ms.team', 'ms.class', 'ms.kills', 'ms.deaths', 'ms.headshots',
                'ms.team_kills', 'ms.gibs', 'ms.kill_assists', 'ms.suicides',
                'ms.damage_given', 'ms.damage_received', 'ms.accuracy_pct',
                'ms.score', 'ms.ping_avg', 'ms.playtime_seconds',
                'ms.revives_given', 'ms.revives_received', 'ms.objectives_taken',
                'p.country_code', 'p.name_clean as current_name', 'p.name_html as current_name_html',
            ])
            ->orderByDesc('ms.score')
            ->orderByDesc('ms.kills')
            ->get();

        $teams = [
            'axis' => $players->filter(fn($p) => in_array(strtolower((string)$p->team), ['axis', 'r', '1', 'red'])),
            'allies' => $players->filter(fn($p) => in_array(strtolower((string)$p->team), ['allies', 'b', '2', 'blue'])),
            'spec' => $players->filter(fn($p) => in_array(strtolower((string)$p->team), ['spectator', 'spec', 's', '3'])),
            'unknown' => $players->filter(fn($p) => $p->team === null || !in_array(strtolower((string)$p->team), ['axis', 'r', '1', 'red', 'allies', 'b', '2', 'blue', 'spectator', 'spec', 's', '3'])),
        ];

        return view('frontend.tracker.match-show', compact('match', 'players', 'teams'));
    }

}
