<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Tracker\TrackerGame;
use App\Models\Tracker\TrackerServer;
use App\Jobs\Tracker\PollServerJob;
use Illuminate\Support\Facades\Log;
use App\Models\Tracker\TrackerPlayer;
use App\Models\Tracker\TrackerClan;
use App\Models\Tracker\TrackerMap;
use App\Services\Tracker\ColorCodeService;
use Illuminate\Http\Request;
use App\Services\Tracker\RtcwKillStatsService;

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
    private function buildServerQuery(\Illuminate\Http\Request $request)
    {
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

        // Engine Family filter (multi)
        $engineFamilyFilter = $asArray($request->input('engine_family'));
        if (!empty($engineFamilyFilter)) {
            $query->whereIn('engine_family', $engineFamilyFilter);
        }

        // Search (supports plain text, IP, or IP:port)
        if ($request->filled('search')) {
            $search = trim($request->search);

            if (str_contains($search, ':')) {
                [$ipPart, $portPart] = array_pad(explode(':', $search, 2), 2, '');
                $ipPart = trim($ipPart);
                $portPart = trim($portPart);

                $query->where(function ($q) use ($ipPart, $portPart) {
                    if ($ipPart !== '') {
                        $q->where('ip', 'like', "%{$ipPart}%");
                    }
                    if ($portPart !== '' && ctype_digit($portPart)) {
                        $q->where('port', (int) $portPart);
                    }
                });
            } else {
                $query->where(function ($q) use ($search) {
                    $q->where('hostname_clean', 'like', "%{$search}%")
                      ->orWhere('ip', 'like', "%{$search}%")
                      ->orWhere('current_map', 'like', "%{$search}%");

                    if (ctype_digit($search)) {
                        $q->orWhere('port', (int) $search);
                    }
                });
            }
        }

        // No password
        if ($request->boolean('no_password', false)) {
            $query->where('needs_password', false);
        }

        // #13 Cvar filters
        if ($request->boolean('ff', false)) {
            $query->where('friendly_fire', 1);
        }
        if ($request->boolean('antilag', false)) {
            $query->where('antilag', 1);
        }
        if ($request->boolean('balanced', false)) {
            $query->where('balanced_teams', 1);
        }
        if ($request->boolean('anticheat', false)) {
            $query->whereNotNull('anticheat');
        }
        if ($request->boolean('hwrestrict', false)) {
            $query->where('heavy_weapon_restriction', '>', 0);
        }

        // Enhanced Tracker (boolean): currently enabled and not disabled
        if ($request->boolean('enhanced', false)) {
            $query->where('is_enhanced_tracker', true)
                  ->where(function ($q) {
                      $q->whereNull('enhanced_disabled')->orWhere('enhanced_disabled', false);
                  });
        }

        // Live Enhanced Events: last enhanced event received within 24h
        if ($request->boolean('live_enhanced', false)) {
            $query->where('enhanced_last_event_at', '>=', now()->subDay());
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
            'engine'   => $query->orderBy('engine_family', $dir)->orderBy('engine_version', $dir),
            'ping'     => $query->orderByRaw('latency_ms IS NULL, latency_ms ' . $dir),
            'players'  => $query->orderByRaw('(current_players - COALESCE(bot_count, 0)) ' . $dir),
            default    => $query->orderByRaw('(current_players - COALESCE(bot_count, 0)) DESC'),
        };

        // Secondary sort: online first
        $query->orderByDesc('is_online');
        return $query;
    }

    public function exportServers(\Illuminate\Http\Request $request)
    {
        $servers = $this->buildServerQuery($request)->get([
            'id','game_id','hostname_clean','ip','port','country','country_code',
            'mod_name','mod_version','engine_version','engine_platform',
            'os','current_players','max_players','is_online','is_enhanced_tracker',
            'enhanced_event_count','enhanced_last_event_at','last_seen_at',
        ]);

        \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new \PhpOffice\PhpSpreadsheet\Cell\StringValueBinder());

        $groups = [];
        foreach ($servers as $sv) {
            $key = ($sv->game_id ?? '?')."\x1f".($sv->os ?? '')."\x1f".($sv->mod_name ?? '')."\x1f".($sv->mod_version ?? '');
            if (!isset($groups[$key])) {
                $groups[$key] = ['game_id'=>$sv->game_id,'os'=>$sv->os,'mod'=>$sv->mod_name,
                    'version'=>$sv->mod_version,'count'=>0,'online'=>0,'enhanced'=>0,'players_now'=>0];
            }
            $g =& $groups[$key];
            $g['count']++;
            $g['online']      += $sv->is_online ? 1 : 0;
            $g['enhanced']    += $sv->is_enhanced_tracker ? 1 : 0;
            $g['players_now'] += (int) $sv->current_players;
            unset($g);
        }
        usort($groups, fn($a,$b) => $b['count'] <=> $a['count']);

        $sumHeader = ['game_id','os','mod','version','count','online','enhanced','players_now'];
        $sumRows   = array_map(fn($g) => array_values($g), $groups);

        $detHeader = ['id','game_id','hostname','ip','port','country','mod','mod_version',
            'engine_version','platform','os','players','max','online','enhanced',
            'enh_events','last_enhanced_event','last_seen','url'];
        $detRows = [];
        foreach ($servers as $sv) {
            $detRows[] = [$sv->id,$sv->game_id,$sv->hostname_clean,$sv->ip,$sv->port,$sv->country_code,
                $sv->mod_name,$sv->mod_version,$sv->engine_version,$sv->engine_platform,$sv->os,
                $sv->current_players,$sv->max_players,$sv->is_online?1:0,$sv->is_enhanced_tracker?1:0,
                $sv->enhanced_event_count,(string)$sv->enhanced_last_event_at,(string)$sv->last_seen_at,
                'https://wolffiles.eu/servers/'.$sv->id];
        }

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $a = $ss->getActiveSheet(); $a->setTitle('Summary');
        $a->fromArray($sumHeader,null,'A1'); $a->fromArray($sumRows,null,'A2');
        $b = $ss->createSheet(); $b->setTitle('Servers');
        $b->fromArray($detHeader,null,'A1'); $b->fromArray($detRows,null,'A2');

        foreach ([$a,$b] as $sh) {
            $highest = $sh->getHighestColumn();
            $sh->getStyle("A1:{$highest}1")->getFont()->setBold(true);
            $colIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highest);
            for ($i = 1; $i <= $colIdx; $i++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                $sh->getColumnDimension($col)->setAutoSize(true);
            }
            $sh->freezePane('A2');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'srvexp_').'.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $writer->setPreCalculateFormulas(false);
        $writer->save($tmp);

        $filename = 'wolffiles_servers_'.date('Ymd_His').'.xlsx';
        return response()->download($tmp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function servers(Request $request)
    {
        $cacheKey = 'tracker:web:servers:' . md5(json_encode([
            'game'        => $request->input('game'),
            'online'      => $request->boolean('online'),
            'players'     => $request->boolean('players'),
            'country'     => $request->input('country'),
            'map'         => $request->input('map'),
            'mod'         => $request->input('mod'),
            'gametype'    => $request->input('gametype'),
            'search'      => $request->input('search'),
            'no_password' => $request->boolean('no_password'),
            'ff'          => $request->boolean('ff'),
            'antilag'     => $request->boolean('antilag'),
            'balanced'    => $request->boolean('balanced'),
            'anticheat'   => $request->boolean('anticheat'),
            'hwrestrict'  => $request->boolean('hwrestrict'),
            'enhanced'    => $request->boolean('enhanced'),
            'live_enhanced' => $request->boolean('live_enhanced'),
            'engine_family' => $request->input('engine_family'),
            'sort'        => $request->get('sort', 'players'),
            'dir'         => $request->get('dir', 'desc'),
            'page'        => $request->get('page', 1),
        ]));

        $payload = Cache::remember($cacheKey, 25, function () use ($request) {
        $games = TrackerGame::active()->orderBy('sort_order')->get();

        $query = $this->buildServerQuery($request);

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

        // Engine families with counts (sorted by popularity, label from parser)
        $engineFamilies = TrackerServer::active()
            ->whereNotNull('engine_family')
            ->selectRaw('engine_family, COUNT(*) as cnt')
            ->groupBy('engine_family')
            ->orderByDesc('cnt')
            ->get()
            ->map(function ($row) {
                $row->label = \App\Services\Tracker\EngineVersionParser::FAMILY_LABELS[$row->engine_family] ?? $row->engine_family;
                return $row;
            });

            return compact('servers', 'games', 'countries', 'mods', 'gametypes', 'engineFamilies');
        });

        return view('frontend.tracker.servers', $payload);
    }

    /**
     * Server Detail
     */
    /**
     * Per-server Excel export (all tracked data, multi-tab).
     * Cached 6h on disk so repeated/public hits don't rebuild.
     */
    public function serverExport($identifier, \App\Services\Tracker\TrackerServerExportService $svc)
    {
        // Resolve: numeric -> tracker_servers.id, string -> tracker_servers.slug
        if (ctype_digit((string) $identifier)) {
            $server = TrackerServer::find((int) $identifier);
        } else {
            $server = TrackerServer::where('slug', $identifier)->first();
        }
        abort_unless($server, 404);

        $id = (int) $server->id;
        $dir = storage_path('app/exports');
        if (! is_dir($dir)) { mkdir($dir, 0775, true); }

        $filename = $svc->filename($id); // wolffiles-server-{id}-{Ymd}.xlsx
        $path = $dir . '/' . $filename;

        $fresh = is_file($path) && (time() - filemtime($path)) < 6 * 3600;

        if (! $fresh) {
            try {
                $svc->export($id, $path);
            } catch (\Throwable $e) {
                \Log::error('serverExport failed', ['server_id' => $id, 'err' => $e->getMessage()]);
                abort(500, 'Export konnte nicht erstellt werden.');
            }
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function serverShow($identifier)
    {
        // Resolve: numeric -> tracker_servers.id, string -> tracker_servers.slug
        if (ctype_digit((string) $identifier)) {
            $server = TrackerServer::find((int) $identifier);
        } else {
            $server = TrackerServer::where('slug', $identifier)->first();
        }
        abort_unless($server, 404);

        $server->load(['game', 'settings', 'clan', 'claimedByUser', 'addedBy']);

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
        $matchParticipants = collect();
        $hallOfFame = [];
        $lastMatch = null;
        $lastMatchPlayers = collect();
        $liveMatch = null;
        $liveMatchPlayers = collect();
        $serverMapBest = collect();
        $serverWeaponMeta = collect();

        if ($server->is_enhanced_tracker) {
            // Match history for existing section below (sub-30s fragments hidden)
            $recentMatches = \DB::table('tracker_matches')
                ->where('server_id', $server->id)
                ->where(function ($q) {
                    $q->whereNull('ended_at')
                      ->orWhere('duration_seconds', '>=', 30);
                })
                ->orderByDesc('started_at')
                ->limit(15)
                ->get();

            // Participants per match (distinct players from match_stats) for the
            // history table — batched over the loaded IDs to avoid N+1 in Blade.
            $rmIds = $recentMatches->pluck('id')->all();
            $matchParticipants = empty($rmIds) ? collect()
                : \DB::table('tracker_player_match_stats')
                    ->whereIn('match_id', $rmIds)
                    ->select('match_id', \DB::raw('COUNT(DISTINCT player_id) as c'))
                    ->groupBy('match_id')
                    ->pluck('c', 'match_id');

            // === E) LIVE MATCH (currently running) ===
            $liveMatch = \DB::table('tracker_matches')
                ->where('server_id', $server->id)
                ->whereNull('ended_at')
                ->orderByDesc('started_at')
                ->first();

            if ($liveMatch) {
                $liveMatchPlayers = \DB::table('tracker_player_match_stats as ms')
                    ->leftJoin('tracker_players as p', 'p.id', '=', 'ms.player_id')
                    ->where('ms.match_id', $liveMatch->id)
                    ->orderByDesc('ms.kills')
                    ->orderByDesc('ms.damage_given')
                    ->select(
                        'ms.*',
                        'p.name_clean', 'p.name_html', 'p.country_code', 'p.is_bot', 'p.id as p_id'
                    )
                    ->get();
            }

            // === B) LAST COMPLETED MATCH (>= 30s) ===
            $lastMatch = \DB::table('tracker_matches')
                ->where('server_id', $server->id)
                ->whereNotNull('ended_at')
                ->where('duration_seconds', '>=', 30)
                ->orderByDesc('ended_at')
                ->first();

            if ($lastMatch) {
                $lastMatchPlayers = \DB::table('tracker_player_match_stats as ms')
                    ->leftJoin('tracker_players as p', 'p.id', '=', 'ms.player_id')
                    ->where('ms.match_id', $lastMatch->id)
                    ->orderByDesc('ms.kills')
                    ->orderByDesc('ms.damage_given')
                    ->select(
                        'ms.*',
                        'p.name_clean', 'p.name_html', 'p.country_code', 'p.is_bot', 'p.id as p_id'
                    )
                    ->get();
            }

            // === A) HALL OF FAME — 5 leaderboards (lifetime, this server only) ===
            $serverMatchIds = \DB::table('tracker_matches')
                ->where('server_id', $server->id)
                ->where('duration_seconds', '>=', 30)
                ->pluck('id');

            if ($serverMatchIds->isNotEmpty()) {
                $hofBase = \DB::table('tracker_player_match_stats as ms')
                    ->join('tracker_players as p', 'p.id', '=', 'ms.player_id')
                    ->whereIn('ms.match_id', $serverMatchIds)
                    ->where('p.is_bot', false);

                $hallOfFame['killers'] = (clone $hofBase)
                    ->select('p.id','p.name_clean','p.name_html','p.country_code',
                        \DB::raw('SUM(ms.kills) as v'),
                        \DB::raw('SUM(ms.deaths) as deaths_total'),
                        \DB::raw('COUNT(*) as matches_played'))
                    ->groupBy('p.id','p.name_clean','p.name_html','p.country_code')
                    ->having('v','>',0)
                    ->orderByDesc('v')
                    ->limit(10)->get();

                $hallOfFame['accuracy'] = (clone $hofBase)
                    ->select('p.id','p.name_clean','p.name_html','p.country_code',
                        \DB::raw('AVG(ms.accuracy_pct) as v'),
                        \DB::raw('COUNT(*) as matches_played'))
                    ->whereNotNull('ms.accuracy_pct')
                    ->where('ms.accuracy_pct','>',0)
                    ->groupBy('p.id','p.name_clean','p.name_html','p.country_code')
                    ->havingRaw('COUNT(*) >= 3')
                    ->orderByDesc('v')
                    ->limit(10)->get();

                $hallOfFame['objectives'] = (clone $hofBase)
                    ->select('p.id','p.name_clean','p.name_html','p.country_code',
                        \DB::raw('SUM(ms.objectives_taken) as v'),
                        \DB::raw('COUNT(*) as matches_played'))
                    ->groupBy('p.id','p.name_clean','p.name_html','p.country_code')
                    ->having('v','>',0)
                    ->orderByDesc('v')
                    ->limit(10)->get();

                $hallOfFame['revivers'] = (clone $hofBase)
                    ->select('p.id','p.name_clean','p.name_html','p.country_code',
                        \DB::raw('SUM(ms.revives_given) as v'),
                        \DB::raw('COUNT(*) as matches_played'))
                    ->groupBy('p.id','p.name_clean','p.name_html','p.country_code')
                    ->having('v','>',0)
                    ->orderByDesc('v')
                    ->limit(10)->get();

                $hallOfFame['teamkillers'] = (clone $hofBase)
                    ->select('p.id','p.name_clean','p.name_html','p.country_code',
                        \DB::raw('SUM(ms.team_kills) as v'),
                        \DB::raw('COUNT(*) as matches_played'))
                    ->groupBy('p.id','p.name_clean','p.name_html','p.country_code')
                    ->having('v','>',0)
                    ->orderByDesc('v')
                    ->limit(10)->get();

                // === C) PER-MAP BEST PERFORMERS ===
                $serverMapBest = \DB::table('tracker_player_match_stats as ms')
                    ->join('tracker_matches as m', 'm.id', '=', 'ms.match_id')
                    ->join('tracker_players as p', 'p.id', '=', 'ms.player_id')
                    ->where('m.server_id', $server->id)
                    ->where('m.duration_seconds', '>=', 30)
                    ->where('p.is_bot', false)
                    ->whereNotNull('m.map_name')
                    ->select(
                        'm.map_name',
                        'p.id as player_id', 'p.name_clean', 'p.name_html', 'p.country_code',
                        \DB::raw('SUM(ms.kills) as total_kills'),
                        \DB::raw('SUM(ms.deaths) as total_deaths'),
                        \DB::raw('COUNT(DISTINCT m.id) as times_played')
                    )
                    ->groupBy('m.map_name','p.id','p.name_clean','p.name_html','p.country_code')
                    ->orderByDesc('total_kills')
                    ->get()
                    ->groupBy('map_name')
                    ->map(fn($rows) => $rows->first())
                    ->sortByDesc('total_kills')
                    ->take(8)
                    ->values();

                // === D) SERVER WEAPON META (most used weapons on this server) ===
                $serverWeaponMeta = \DB::table('tracker_match_player_weapon_stats as w')
                    ->join('tracker_matches as m', 'm.id', '=', 'w.match_id')
                    ->where('m.server_id', $server->id)
                    ->select(
                        'w.weapon_bit',
                        \DB::raw('SUM(w.kills) as total_kills'),
                        \DB::raw('SUM(w.deaths) as total_deaths'),
                        \DB::raw('SUM(w.hits) as total_hits'),
                        \DB::raw('SUM(w.atts) as total_atts'),
                        \DB::raw('SUM(w.headshots) as total_headshots'),
                        \DB::raw('COUNT(DISTINCT w.player_id) as users')
                    )
                    ->groupBy('w.weapon_bit')
                    ->orderByDesc('total_kills')
                    ->limit(6)
                    ->get();
            }
        }

        // === RECENT ACTIVITY (Commit 3) ===
        $recentRange = request()->input('recent', '7d');
        $rangeMap = ['24h' => 1, '7d' => 7, '30d' => 30];
        $days = $rangeMap[$recentRange] ?? 7;
        $sinceRecent = now()->subDays($days);

        $recentPlayers = \DB::table('tracker_player_sessions as sess')
            ->leftJoin('tracker_players as p', 'p.id', '=', 'sess.player_id')
            ->where('sess.server_id', $server->id)
            ->where('sess.started_at', '>=', $sinceRecent)
            ->select([
                'sess.player_id',
                'p.name_clean', 'p.name_html', 'p.country_code',
                'p.has_enhanced_data', 'p.elo_rating',
                \DB::raw('COUNT(*) as sess_count'),
                \DB::raw('SUM(sess.duration_minutes) as total_min'),
                \DB::raw('SUM(sess.kills) as kills'),
                \DB::raw('SUM(sess.deaths) as deaths'),
                \DB::raw('MAX(sess.started_at) as last_seen'),
            ])
            ->groupBy('sess.player_id', 'p.name_clean', 'p.name_html', 'p.country_code', 'p.has_enhanced_data', 'p.elo_rating')
            ->orderByDesc('last_seen')
            ->limit(30)
            ->get();

        // Latest known raw (coloured) name per player for this list, so the
        // recent-players table shows current ^x colours instead of the frozen
        // name_html. One batched query over the listed player_ids (no N+1).
        // Keyed by player_id; Blade renders it via ColorCodeService::toHtml().
        $recentRawNames = [];
        $rpIds = $recentPlayers->pluck('player_id')->filter()->all();
        if (!empty($rpIds)) {
            $rawRows = \DB::table('tracker_player_snapshots as s')
                ->select('s.player_id', 's.name')
                ->whereIn('s.player_id', $rpIds)
                ->whereNotNull('s.name')->where('s.name', '!=', '')
                ->whereRaw('s.polled_at = (SELECT MAX(polled_at) FROM tracker_player_snapshots WHERE player_id = s.player_id AND name IS NOT NULL AND name != \'\')')
                ->get();
            foreach ($rawRows as $r) {
                if (!array_key_exists($r->player_id, $recentRawNames)) {
                    $recentRawNames[$r->player_id] = $r->name;
                }
            }
        }

        $recentMaps = \DB::table('tracker_player_sessions')
            ->where('server_id', $server->id)
            ->whereNotNull('map_name')
            ->where('started_at', '>=', $sinceRecent)
            ->select([
                'map_name',
                \DB::raw('MAX(started_at) as last_played_at'),
                \DB::raw('COUNT(DISTINCT player_id) as unique_players'),
                \DB::raw('COUNT(*) as session_count'),
                \DB::raw('SUM(duration_minutes) as total_time'),
            ])
            ->groupBy('map_name')
            ->orderByDesc('last_played_at')
            ->limit(15)
            ->get();

        // RtCW kill scoreboard (game_id >= 6 = RtCW family; ET is 1..5).
        // Built from tracker_rtcw_kills; null for ET servers.
        $rtcwScoreboard = null;
        if ($server->game_id >= 6) {
            $rtcwScoreboard = (new RtcwKillStatsService())->serverScoreboard($server->id);
        }

        return view('frontend.tracker.server-show', compact(
            'rtcwScoreboard',
            'server', 'activeSessions', 'history', 'topMaps', 'recentMatches', 'matchParticipants',
            'hallOfFame', 'lastMatch', 'lastMatchPlayers',
            'liveMatch', 'liveMatchPlayers', 'serverMapBest', 'serverWeaponMeta',
            'recentPlayers', 'recentRawNames', 'recentMaps', 'recentRange'
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

        // Enhanced-only filter
        if ($request->boolean('enhanced')) {
            $query->where('has_enhanced_data', true);
        }

        // Flagged-only filter (public flags with public evidence)
        if ($request->boolean('flagged')) {
            $query->whereHas('bans', fn($q) => $q->where('is_public', true)
                ->where('status', 'active')->whereHas('publicEvidence'));
        }

        // Marker for the flagged badge (single EXISTS subquery, no N+1)
        $query->withExists(['bans as is_flagged' => fn($q) => $q->where('is_public', true)
            ->where('status', 'active')->whereHas('publicEvidence')]);

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
        // Merged player → redirect to canonical target (follow chains, max 5 hops).
        if ($player->merged_into) {
            $target = (int) $player->merged_into;
            for ($i = 0; $i < 5; $i++) {
                $next = \App\Models\Tracker\TrackerPlayer::where('id', $target)->value('merged_into');
                if (!$next) break;
                $target = (int) $next;
            }
            return redirect()->route('tracker.player.show', $target, 301);
        }

        $player->load(['aliases', 'clanMemberships.clan']);

        // Live name colours: if this player is on a server right now (snapshot
        // fresher than 60s, ~3-4 poll cycles), render their CURRENT coloured name
        // from the raw snapshot. Falls back to the frozen name_html when offline.
        // Does NOT touch name/name_clean/name_html on the record (Decision A).
        $liveNameHtml = null;
        if (!$player->is_bot) {
            $liveSnap = \DB::table('tracker_player_snapshots')
                ->where('player_id', $player->id)
                ->where('polled_at', '>=', now()->subSeconds(60))
                ->whereNotNull('name')
                ->where('name', '!=', '')
                ->orderByDesc('polled_at')
                ->value('name');
            if ($liveSnap !== null) {
                $liveNameHtml = \App\Services\Tracker\ColorCodeService::toHtml($liveSnap);
            }
        }

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

        // Favorite servers (Commit 2: added last_played_at + total count)
        $favoriteServers = $player->sessions()
            ->select('server_id')
            ->selectRaw('COUNT(*) as session_count, SUM(duration_minutes) as total_time, MAX(started_at) as last_played_at')
            ->groupBy('server_id')
            ->orderByDesc('total_time')
            ->limit(5)
            ->with('server')
            ->get();

        $favoriteServersTotal = (int) \DB::table('tracker_player_sessions')
            ->where('player_id', $player->id)
            ->distinct()
            ->count('server_id');

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

        // Prestige level — MAX across all matches. Prestige is a post-max-XP
        // reset mechanic in some ET mods (ETJump, NoQuarter). 0 = no prestige,
        // higher values mean the player has reset their XP at MAX level N times.
        $prestigeLevel = 0;

        // === Combat Overview (global headshot %, damage ratio, team preference) ===
        $hsAgg = DB::table('tracker_player_weapon_stats')
            ->where('player_id', $player->id)
            ->selectRaw('SUM(total_headshots) as hs, SUM(total_hits) as h')
            ->first();
        $headshotRatio = ($hsAgg && $hsAgg->h > 0)
            ? round(($hsAgg->hs / $hsAgg->h) * 100, 1) : null;

        $dmgAgg = DB::table('tracker_player_match_stats')
            ->where('player_id', $player->id)
            ->selectRaw('SUM(damage_given) as given, SUM(damage_received) as received')
            ->first();
        $damageGiven = (int) ($dmgAgg?->given ?? 0);
        $damageReceived = (int) ($dmgAgg?->received ?? 0);
        $damageRatio = ($damageReceived > 0)
            ? round($damageGiven / $damageReceived, 2) : null;

        $teamAgg = DB::table('tracker_player_match_stats')
            ->where('player_id', $player->id)
            ->whereIn('team', [1, 2])
            ->selectRaw('team, COUNT(*) as c')
            ->groupBy('team')
            ->pluck('c', 'team')
            ->toArray();
        $axisMatches = (int) ($teamAgg[1] ?? 0);
        $alliesMatches = (int) ($teamAgg[2] ?? 0);

        // === Skill Progression (XP per class skill, last 100 matches) ===
        $skillRaws = DB::table('tracker_player_match_stats as ms')
            ->join('tracker_matches as m', 'm.id', '=', 'ms.match_id')
            ->where('ms.player_id', $player->id)
            ->whereNotNull('ms.raw_skills')
            ->orderBy('m.started_at')
            ->limit(100)
            ->get(['m.started_at as date', 'ms.raw_skills']);

        $skillProgression = [];
        foreach ($skillRaws as $row) {
            $data = json_decode($row->raw_skills, true);
            if (!is_array($data) || !isset($data['skills'])) continue;
            foreach ($data['skills'] as $sid => $sdata) {
                if (!isset($sdata['current'])) continue;
                $skillProgression[(int) $sid][] = [
                    'date' => $row->date,
                    'xp'   => (int) $sdata['current'],
                ];
            }
        }
        ksort($skillProgression);

        // === Prestige Timeline (level-up milestones) ===
        $prestigeEvents = DB::table('tracker_player_match_stats as ms')
            ->join('tracker_matches as m', 'm.id', '=', 'ms.match_id')
            ->where('ms.player_id', $player->id)
            ->where('ms.prestige', '>', 0)
            ->orderBy('m.started_at')
            ->get(['m.started_at as date', 'ms.prestige']);

        $prestigeMilestones = [];
        $lastP = 0;
        foreach ($prestigeEvents as $e) {
            if ($e->prestige > $lastP) {
                $prestigeMilestones[] = ['date' => $e->date, 'level' => (int) $e->prestige];
                $lastP = (int) $e->prestige;
            }
        }
        if ($player->has_enhanced_data) {
            $prestigeLevel = (int) \DB::table('tracker_player_match_stats')
                ->where('player_id', $player->id)
                ->max('prestige');
        }

        // Class distribution (Enhanced players only — match_stats carry the class).
        // Kills/deaths/headshots come from weapon_stats, pre-aggregated per match
        // to avoid the one-row-per-weapon fan-out, then summed per class.
        $wsAgg = \DB::raw('(SELECT match_id, player_id, SUM(kills) k, SUM(deaths) d, SUM(headshots) h '
            . 'FROM tracker_match_player_weapon_stats GROUP BY match_id, player_id) w');
        $classStats = \DB::table('tracker_player_match_stats as m')
            ->leftJoin($wsAgg, function ($j) {
                $j->on('w.match_id', '=', 'm.match_id')->on('w.player_id', '=', 'm.player_id');
            })
            ->where('m.player_id', $player->id)
            ->selectRaw('m.class, COUNT(*) matches, COALESCE(SUM(w.k),0) kills, COALESCE(SUM(w.d),0) deaths, COALESCE(SUM(w.h),0) headshots')
            ->groupBy('m.class')->orderByDesc('matches')->get()
            ->map(fn($r) => [
                'name'      => \App\Services\Tracker\TrackerClass::name((int) $r->class) ?? 'Unknown',
                'matches'   => (int) $r->matches,
                'kills'     => (int) $r->kills,
                'deaths'    => (int) $r->deaths,
                'headshots' => (int) $r->headshots,
            ]);
        $classTotal = $classStats->sum('matches');

        // Public cheat flags: only active, public, with >=1 public evidence.
        $publicFlags = $player->bans()
            ->where('is_public', true)
            ->where('status', 'active')
            ->whereHas('publicEvidence')
            ->with(['publicEvidence', 'servers'])
            ->get();

        return view('frontend.tracker.player-show', compact(
            'liveNameHtml',
            'publicFlags',
            'classStats', 'classTotal',
            'player', 'sessions', 'eloHistory', 'favoriteServers', 'favoriteServersTotal', 'favoriteMaps',
            'enhancedMatches', 'enhancedMatchesCount',
            'latestMatch', 'latestMatchStats', 'latestMatchWeapons',
            'enhancedRating', 'enhancedRatingPeak', 'enhancedRatingAvg', 'enhancedRatingMatches',
            'lifetimeWeapons',
            'playerTimeline', 'xpSkills',
            'prestigeLevel', 'headshotRatio', 'damageGiven', 'damageReceived', 'damageRatio', 'axisMatches', 'alliesMatches', 'skillProgression', 'prestigeMilestones'));
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
        $gameSlug = (string) $request->input('game', '');
        $cacheKey = 'tracker:api:servers:' . md5($gameSlug);

        $data = Cache::remember($cacheKey, 15, function () use ($gameSlug) {
            $query = TrackerServer::active()->where('is_online', true)->with('game');

            if ($gameSlug !== '') {
                $game = TrackerGame::where('slug', $gameSlug)->first();
                if ($game) {
                    $query->where('game_id', $game->id);
                }
            }

            return $query->orderByDesc('current_players')->limit(2000)->get([
                'id', 'game_id', 'hostname_html', 'hostname_clean', 'ip', 'port',
                'current_map', 'current_players', 'max_players', 'gametype',
                'mod_name', 'country_code', 'is_online', 'needs_password',
            ]);
        });

        return response()->json($data);
    }

    /**
     * Phase 2: Authenticated users can force-poll an offline server.
     *
     * - auth + throttle:5,1 middleware on the route
     * - 30s cooldown per (user, server) tuple via cache
     * - Dispatches PollServerJob on tracker-high queue (same as Filament admin action)
     */
    public function forcePoll(\Illuminate\Http\Request $request, TrackerServer $server)
    {
        $userId = $request->user()->id;
        $cooldownKey = "force_poll:user:{$userId}:server:{$server->id}";
        $cooldownSeconds = 30;

        if (Cache::has($cooldownKey)) {
            return response()->json([
                'queued' => false,
                'reason' => 'cooldown',
                'retry_after' => Cache::get($cooldownKey) - now()->timestamp,
            ], 429);
        }

        Cache::put($cooldownKey, now()->addSeconds($cooldownSeconds)->timestamp, $cooldownSeconds);

        PollServerJob::dispatch($server->id)->onQueue('tracker-high');

        Log::info('Force-poll dispatched by user', [
            'user_id' => $userId,
            'server_id' => $server->id,
            'server_address' => "{$server->ip}:{$server->port}",
        ]);

        return response()->json([
            'queued' => true,
            'cooldown_seconds' => $cooldownSeconds,
        ]);
    }

    public function serverLiveData(TrackerServer $server)
    {
        $server->load(['game', 'settings']);

        // ---- Real human players: from tracker_player_sessions + snapshots ----
        $sessions = $server->sessions()
            ->whereNull('ended_at')
            ->with('player:id,name_clean,name_html,country,country_code')
            ->orderByDesc('score')
            ->get();

        $sessionIds = $sessions->pluck('id')->all();
        $latestPings = [];
        $latestTeams = [];
        $latestNames = [];
        if (!empty($sessionIds)) {
            $latestRows = \DB::table('tracker_player_snapshots as s')
                ->select('s.session_id', 's.ping', 's.team', 's.name')
                ->whereIn('s.session_id', $sessionIds)
                ->whereRaw('s.polled_at = (SELECT MAX(polled_at) FROM tracker_player_snapshots WHERE session_id = s.session_id)')
                ->get();
            foreach ($latestRows as $r) {
                $latestPings[$r->session_id] = $r->ping;
                $latestTeams[$r->session_id] = $r->team;
                $latestNames[$r->session_id] = $r->name;
            }
        }

        // Live class per player from the open slot rows (ET enhanced only).
        // Slot rows carry the most recent class from ws packets; RtCW/non-
        // enhanced servers never set it, so this map stays empty there.
        $latestClasses = [];
        $playerIds = $sessions->pluck('player_id')->filter()->all();
        if (!empty($playerIds)) {
            $slotClassRows = \DB::table('tracker_server_slots')
                ->where('server_id', $server->id)
                ->whereNull('disconnected_at')
                ->whereIn('player_id', $playerIds)
                ->whereNotNull('class')
                ->orderByDesc('connected_at')
                ->get(['player_id', 'class']);
            foreach ($slotClassRows as $r) {
                // first wins = most recent open slot for that player
                if (!array_key_exists($r->player_id, $latestClasses)) {
                    $latestClasses[$r->player_id] = (int) $r->class;
                }
            }
        }

        // Live K/D (+ class fallback) from the open match's per-player stats
        // (ET enhanced only). Keyed by clean-name because match_stats.player_id
        // can diverge from the session player_id (Poller vs Enhanced identity).
        // Map: cleanName => ['class'=>int|null,'kills'=>int,'deaths'=>int]
        $matchStatsByName = [];
        if ($server->is_enhanced_tracker) {
            $openMatch = \DB::table('tracker_matches')
                ->where('server_id', $server->id)
                ->whereNull('ended_at')
                ->orderByDesc('started_at')
                ->first(['id']);
            if ($openMatch) {
                $msRows = \DB::table('tracker_player_match_stats')
                    ->where('match_id', $openMatch->id)
                    ->get(['name_clean_snapshot', 'class', 'kills', 'deaths']);
                foreach ($msRows as $r) {
                    $key = mb_strtolower(\App\Services\Tracker\ColorCodeService::toClean($r->name_clean_snapshot ?? ''));
                    if ($key === '') continue;
                    // last write wins (rows share one ws-sync timestamp anyway)
                    $matchStatsByName[$key] = [
                        'class'  => $r->class !== null ? (int) $r->class : null,
                        'kills'  => (int) $r->kills,
                        'deaths' => (int) $r->deaths,
                    ];
                }
            }
        }

        $humanPlayers = $sessions->map(function (\App\Models\Tracker\TrackerPlayerSession $s) use ($latestPings, $latestTeams, $latestClasses, $matchStatsByName, $latestNames) {
            $nameKey = mb_strtolower(\App\Services\Tracker\ColorCodeService::toClean($s->player?->name_clean ?? ''));
            $ms = $matchStatsByName[$nameKey] ?? null;
            return [
                // Live coloured name from the freshest snapshot (raw ^x string).
                // Falls back to the frozen name_html (Decision A) when no snapshot
                // name is present for this session.
                'player_name'  => (!empty($latestNames[$s->id]))
                    ? \App\Services\Tracker\ColorCodeService::toHtml($latestNames[$s->id])
                    : ($s->player?->name_html ?: e($s->player->name_clean ?? 'Unknown')),
                'player_url'   => $s->player ? route('tracker.player.show', $s->player) : null,
                'country_code' => $s->player?->country_code,
                'country'      => $s->player?->country,
                'score'        => (int) $s->score,
                'ping'         => $latestPings[$s->id] ?? null,
                'team'         => $latestTeams[$s->id] ?? null,
                // class: prefer match_stats (consistent), fall back to slot-class
                // Spectators have no class
                'class'        => (($latestTeams[$s->id] ?? null) === 'spectator')
                    ? null
                    : ($ms['class'] ?? $latestClasses[$s->player_id] ?? null),
                'kills'        => $ms['kills'] ?? null,
                'deaths'       => $ms['deaths'] ?? null,
                'duration'     => $s->duration_minutes . 'm',
                'is_bot'       => false,
            ];
        })->all();

        // ---- Bots: pulled live from the server, cached 60s to avoid UDP-spamming ----
        // Bots don't get tracked in the DB (generic names, no aliases, no statistics).
        // We poll the server on page load (cached) to show a live list of who's bot-fighting.
        $botPlayers = \Illuminate\Support\Facades\Cache::remember(
            "tracker:live_bots:{$server->id}",
            now()->addSeconds(60),
            function () use ($server) {
                try {
                    // Kurzer Timeout (1s) und keine Retries fuer Frontend-Live-Polling.
                    // Tote Server bremsen sonst Worker bis zu 9 Sekunden.
                    $q = new \App\Services\Tracker\ServerQueryService(timeout: 1, retries: 0);
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
                            'class'        => null,
                            'kills'        => null,
                            'deaths'       => null,
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
            // Phase 1b: map progress for live elapsed/remaining/timelimit display.
            // Sending elapsed seconds (server-computed) instead of ISO string lets the
            // client tick locally between fetches without depending on client clock accuracy.
            'map_elapsed_seconds' => $server->current_map_started_at
                ? max(0, now()->getTimestamp() - $server->current_map_started_at->getTimestamp())
                : null,
            'timelimit_minutes' => ((int) ($server->settings->firstWhere('key', 'timelimit')?->value ?? 0)) ?: null,
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


    // === PLAYER SERVERS (Commit 2) ===

    /**
     * Deep-dive page: show ALL servers a player has played on, with
     * detailed stats (sessions, playtime, K/D, names used, top maps).
     * Used for cross-server activity tracking, incl. problematic user detection.
     */
    public function playerServers(TrackerPlayer $player)
    {
        $sortBy = request()->input('sort', 'playtime');
        $validSorts = ['playtime', 'sessions', 'last_played', 'first_played', 'kills'];
        if (!in_array($sortBy, $validSorts, true)) {
            $sortBy = 'playtime';
        }
        $sortColumn = [
            'playtime'     => 'total_time',
            'sessions'     => 'session_count',
            'last_played'  => 'last_played_at',
            'first_played' => 'first_played_at',
            'kills'        => 'total_kills',
        ][$sortBy];

        // Alle Server mit Sessions + aggregierten Stats
        $servers = \DB::table('tracker_player_sessions as sess')
            ->leftJoin('tracker_servers as s', 's.id', '=', 'sess.server_id')
            ->leftJoin('tracker_games as g', 'g.id', '=', 's.game_id')
            ->where('sess.player_id', $player->id)
            ->select([
                'sess.server_id',
                \DB::raw('COUNT(*) as session_count'),
                \DB::raw('SUM(sess.duration_minutes) as total_time'),
                \DB::raw('MIN(sess.started_at) as first_played_at'),
                \DB::raw('MAX(sess.started_at) as last_played_at'),
                \DB::raw('SUM(sess.kills) as total_kills'),
                \DB::raw('SUM(sess.deaths) as total_deaths'),
                \DB::raw('MAX(sess.score) as max_score'),
                \DB::raw('SUM(sess.xp) as total_xp'),
                's.hostname_clean', 's.hostname_html', 's.country_code', 's.country',
                's.ip', 's.port', 's.is_enhanced_tracker', 's.is_online',
                'g.short_name as game_short', 'g.color as game_color',
            ])
            ->groupBy(
                'sess.server_id', 's.hostname_clean', 's.hostname_html',
                's.country_code', 's.country', 's.ip', 's.port',
                's.is_enhanced_tracker', 's.is_online',
                'g.short_name', 'g.color'
            )
            ->orderByDesc($sortColumn)
            ->get();

        // Namen pro Server (aus Enhanced match_stats)
        $namesByServer = \DB::table('tracker_player_match_stats')
            ->where('player_id', $player->id)
            ->whereNotNull('name_clean_snapshot')
            ->select('server_id', 'name_clean_snapshot', \DB::raw('COUNT(*) as used'))
            ->groupBy('server_id', 'name_clean_snapshot')
            ->orderByDesc('used')
            ->get()
            ->groupBy('server_id');

        // Top-Map pro Server
        $topMapByServer = \DB::table('tracker_player_sessions')
            ->where('player_id', $player->id)
            ->whereNotNull('map_name')
            ->select('server_id', 'map_name', \DB::raw('COUNT(*) as c'))
            ->groupBy('server_id', 'map_name')
            ->orderByDesc('c')
            ->get()
            ->groupBy('server_id')
            ->map(function ($rows) {
                return $rows->take(3);
            });

        return view('frontend.tracker.player-servers', compact(
            'player', 'servers', 'namesByServer', 'topMapByServer', 'sortBy'
        ));
    }

}