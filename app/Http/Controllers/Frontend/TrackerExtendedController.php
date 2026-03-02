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
    public function rankings(Request $request, RankingService $rankingService)
    {
        $period = $request->get('period', 'alltime');
        if (!in_array($period, ['daily','weekly','monthly','alltime'])) $period = 'alltime';

        $rankings = $rankingService->getLeaderboard($period, 50);
        $top3 = $rankings->getCollection()->take(3);

        return view('frontend.tracker.rankings', compact('rankings', 'period', 'top3'));
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

    public function clanShow(TrackerClan $clan)
    {
        $clan->load(['activeMembers.player']);

        $topPlayers = TrackerPlayer::whereIn('id', $clan->activeMembers->pluck('player_id'))
            ->where('status', 'active')
            ->orderByDesc('elo_rating')
            ->limit(20)->get();

        $recentActivity = DB::table('tracker_player_sessions')
            ->whereIn('player_id', $clan->activeMembers->pluck('player_id'))
            ->where('started_at', '>=', now()->subDays(7))
            ->select(DB::raw('DATE(started_at) as date'), DB::raw('COUNT(*) as sessions'), DB::raw('SUM(duration_minutes) as minutes'))
            ->groupBy('date')->orderBy('date')->get();

        return view('frontend.tracker.clan-show', compact('clan', 'topPlayers', 'recentActivity'));
    }

    // ── Player Compare ──
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
}
