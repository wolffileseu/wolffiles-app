<?php

namespace App\Services\Tracker;

use RuntimeException;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use stdClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class TrackerServerExportService
{
    private string $headerFill = 'FF1F2937';
    private string $headerFont = 'FFFFFFFF';

    /** Build the workbook and save to $out. Returns the path. */
    public function export(int $id, string $out, int $limitStats = 0): string
    {
        @ini_set('memory_limit', '2048M');

        $server = DB::table('tracker_servers')->where('id', $id)->first();
        if (! $server) {
            throw new RuntimeException("Server {$id} not found.");
        }

        $ss = new Spreadsheet();
        $ss->getProperties()
            ->setCreator('Wolffiles Tracker')
            ->setTitle('Server ' . $id . ' export')
            ->setDescription('Generated ' . now()->toDateTimeString());

        $this->buildOverview($ss, $server, $id);
        $this->buildMatches($ss, $id);
        $this->buildMaps($ss, $id);
        $this->buildPlayers($ss, $id);
        $this->buildMatchStats($ss, $id, $limitStats);
        $this->buildSessions($ss, $id);
        $this->buildActivity($ss, $id);

        $ss->setActiveSheetIndex(0);

        $dir = dirname($out);
        if (! is_dir($dir)) { mkdir($dir, 0775, true); }

        (new Xlsx($ss))->save($out);
        @chown($out, 'wolffiles.eu_lkiogmaiktl');
        @chgrp($out, 'psacln');

        return $out;
    }

    /** Suggested clean filename for a server export. */
    public function filename(int $id): string
    {
        return 'wolffiles-server-' . $id . '-' . now()->format('Ymd') . '.xlsx';
    }

    private function newSheet(Spreadsheet $ss, string $title): Worksheet
    {
        $sheet = ($ss->getSheetCount() === 1 && $ss->getSheet(0)->getTitle() === 'Worksheet')
            ? $ss->getActiveSheet()
            : $ss->createSheet();
        $sheet->setTitle(substr($title, 0, 31));
        return $sheet;
    }

    private function writeHeaders(Worksheet $sheet, array $headers): void
    {
        $c = 1;
        foreach ($headers as $h) { $sheet->setCellValue([$c++, 1], $h); }
        $lastCol = Coordinate::stringFromColumnIndex(max(1, count($headers)));
        $style = $sheet->getStyle('A1:' . $lastCol . '1');
        $style->getFont()->setBold(true)->getColor()->setARGB($this->headerFont);
        $style->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->headerFill);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $lastCol . '1');
    }

    private function setCell(Worksheet $sheet, int $c, int $r, $val): void
    {
        if ($val === null || $val === '') { $sheet->setCellValue([$c, $r], ''); return; }
        if (is_int($val) || is_float($val)) { $sheet->setCellValue([$c, $r], $val); return; }
        $s = (string) $val;
        if (preg_match('/^-?\d+$/', $s) && strlen($s) <= 15) { $sheet->setCellValue([$c, $r], (int) $s); return; }
        if (preg_match('/^-?\d+\.\d+$/', $s)) { $sheet->setCellValue([$c, $r], (float) $s); return; }
        $sheet->setCellValueExplicit([$c, $r], $s, DataType::TYPE_STRING);
    }

    private function writeRows(Worksheet $sheet, array $headers, iterable $rows, bool $autosize = true): void
    {
        $this->writeHeaders($sheet, $headers);
        $r = 2;
        foreach ($rows as $row) {
            $c = 1;
            foreach ($row as $v) { $this->setCell($sheet, $c++, $r, $v); }
            $r++;
        }
        if ($autosize) { $this->autosize($sheet, count($headers)); }
    }

    private function autosize(Worksheet $sheet, int $cols): void
    {
        for ($i = 1; $i <= $cols; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
    }

    private function widths(Worksheet $sheet, array $map): void
    {
        foreach ($map as $i => $w) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth($w);
        }
    }

    private function hms(int $sec): string
    {
        $h = intdiv($sec, 3600); $m = intdiv($sec % 3600, 60); $s = $sec % 60;
        return $h > 0 ? sprintf('%dh%02dm%02ds', $h, $m, $s) : sprintf('%dm%02ds', $m, $s);
    }

    private function buildOverview(Spreadsheet $ss, stdClass $server, int $id): void
    {
        $sheet = $this->newSheet($ss, 'Overview');
        $rows = [];
        foreach ((array) $server as $k => $v) {
            if ($k === 'final_scoreboard') { continue; }
            $rows[] = [$k, (is_scalar($v) || $v === null) ? $v : json_encode($v)];
        }
        $rows[] = ['', ''];
        $rows[] = ['=== Tracker totals ===', ''];
        $rows[] = ['matches_total', DB::table('tracker_matches')->where('server_id', $id)->count()];
        $rows[] = ['matches_valid_ge30s', DB::table('tracker_matches')->where('server_id', $id)->whereNotNull('ended_at')->where('duration_seconds', '>=', 30)->count()];
        $rows[] = ['matches_open', DB::table('tracker_matches')->where('server_id', $id)->whereNull('ended_at')->count()];
        $rows[] = ['distinct_players', DB::table('tracker_player_match_stats')->where('server_id', $id)->distinct()->count('player_id')];
        $rows[] = ['distinct_maps', DB::table('tracker_matches')->where('server_id', $id)->distinct()->count('map_name')];
        $rows[] = ['total_match_time_hours', round(((int) DB::table('tracker_matches')->where('server_id', $id)->sum('duration_seconds')) / 3600, 1)];
        $rows[] = ['peak_players', (int) DB::table('tracker_matches')->where('server_id', $id)->max('player_count_max')];
        $rows[] = ['total_kills', (int) DB::table('tracker_matches')->where('server_id', $id)->sum('total_kills')];
        $rows[] = ['total_deaths', (int) DB::table('tracker_matches')->where('server_id', $id)->sum('total_deaths')];
        $rows[] = ['first_match_at', DB::table('tracker_matches')->where('server_id', $id)->min('started_at')];
        $rows[] = ['last_match_at', DB::table('tracker_matches')->where('server_id', $id)->max('started_at')];
        $rows[] = ['poller_sessions_total', DB::table('tracker_player_sessions')->where('server_id', $id)->count()];

        // Live team distribution (latest snapshot per open session, non-bots)
        $live = $this->liveTeamCounts($id);
        $rows[] = ['', ''];
        $rows[] = ['=== Currently connected (live) ===', ''];
        $rows[] = ['currently_allies', $live['allies']];
        $rows[] = ['currently_axis', $live['axis']];
        $rows[] = ['currently_spectators', $live['spec']];
        $rows[] = ['currently_playing', $live['playing']];

        $rows[] = ['exported_at', now()->toDateTimeString()];
        $this->writeRows($sheet, ['Field', 'Value'], $rows, true);
    }

    private function liveTeamCounts(int $id): array
    {
        $out = ['allies' => 0, 'axis' => 0, 'spec' => 0, 'playing' => 0];
        $sessionIds = DB::table('tracker_player_sessions')->where('server_id', $id)->whereNull('ended_at')->pluck('id')->all();
        if (empty($sessionIds)) { return $out; }
        $rows = DB::table('tracker_player_snapshots as s')
            ->join('tracker_player_sessions as ses', 'ses.id', '=', 's.session_id')
            ->leftJoin('tracker_players as p', 'p.id', '=', 'ses.player_id')
            ->whereIn('s.session_id', $sessionIds)
            ->where(function ($q) { $q->where('p.is_bot', 0)->orWhereNull('p.is_bot'); })
            ->whereRaw('s.polled_at = (SELECT MAX(polled_at) FROM tracker_player_snapshots WHERE session_id = s.session_id)')
            ->get(['s.team']);
        foreach ($rows as $r) {
            switch ((string) $r->team) {
                case 'allies':    $out['allies']++; break;
                case 'axis':      $out['axis']++;   break;
                case 'spectator': $out['spec']++;   break;
            }
        }
        $out['playing'] = $out['allies'] + $out['axis'];
        return $out;
    }

    private function buildMatches(Spreadsheet $ss, int $id): void
    {
        $sheet = $this->newSheet($ss, 'Matches');
        $headers = ['id', 'map_name', 'started_at', 'ended_at', 'duration_seconds', 'duration_hms',
            'end_reason', 'players_at_start', 'players_at_end',
            'allies_at_start', 'axis_at_start', 'spec_at_start',
            'allies_at_end', 'axis_at_end', 'spec_at_end',
            'player_count_max', 'player_count_avg',
            'participants', 'total_kills', 'total_deaths', 'winner'];
        $this->writeHeaders($sheet, $headers);

        $participants = DB::table('tracker_player_match_stats')->where('server_id', $id)
            ->select('match_id', DB::raw('COUNT(DISTINCT player_id) as c'))
            ->groupBy('match_id')->pluck('c', 'match_id');

        $r = 2;
        DB::table('tracker_matches')->where('server_id', $id)->orderBy('started_at')
            ->chunk(2000, function ($chunk) use ($sheet, &$r, $participants) {
                foreach ($chunk as $m) {
                    $winner = null;
                    if (! empty($m->final_scoreboard)) {
                        $d = json_decode($m->final_scoreboard, true);
                        $winner = $d['winner'] ?? null;
                    }
                    $dur = (int) ($m->duration_seconds ?? 0);
                    $vals = [(int) $m->id, $m->map_name, $m->started_at, $m->ended_at, $dur, $this->hms($dur),
                        $m->end_reason, $m->players_at_start, $m->players_at_end,
                        $m->allies_at_start, $m->axis_at_start, $m->spec_at_start,
                        $m->allies_at_end, $m->axis_at_end, $m->spec_at_end,
                        $m->player_count_max, $m->player_count_avg,
                        (int) ($participants[$m->id] ?? 0), $m->total_kills, $m->total_deaths, $winner];
                    $c = 1;
                    foreach ($vals as $v) { $this->setCell($sheet, $c++, $r, $v); }
                    $r++;
                }
            });
        $this->autosize($sheet, count($headers));
    }

    private function buildMaps(Spreadsheet $ss, int $id): void
    {
        $sheet = $this->newSheet($ss, 'Maps');
        $rows = DB::table('tracker_matches')->where('server_id', $id)
            ->select('map_name',
                DB::raw('COUNT(*) as match_count'),
                DB::raw('SUM(duration_seconds) as total_sec'),
                DB::raw('ROUND(AVG(duration_seconds)) as avg_sec'),
                DB::raw('MAX(player_count_max) as peak_players'),
                DB::raw('ROUND(AVG(player_count_avg),1) as avg_players'),
                DB::raw('SUM(total_kills) as kills'))
            ->groupBy('map_name')->orderByDesc('match_count')->get();
        $out = [];
        foreach ($rows as $m) {
            $out[] = [$m->map_name, (int) $m->match_count, $this->hms((int) $m->total_sec),
                (int) $m->total_sec, (int) $m->avg_sec, (int) $m->peak_players, $m->avg_players, (int) $m->kills];
        }
        $this->writeRows($sheet, ['map_name', 'matches', 'total_time', 'total_seconds', 'avg_seconds', 'peak_players', 'avg_players', 'total_kills'], $out, true);
    }

    private function buildPlayers(Spreadsheet $ss, int $id): void
    {
        $sheet = $this->newSheet($ss, 'Players');
        $agg = DB::table('tracker_player_match_stats as pms')
            ->join('tracker_matches as m', 'm.id', '=', 'pms.match_id')
            ->where('pms.server_id', $id)
            ->select('pms.player_id',
                DB::raw('COUNT(DISTINCT pms.match_id) as matches'),
                DB::raw('SUM(pms.kills) as kills'),
                DB::raw('SUM(pms.deaths) as deaths'),
                DB::raw('SUM(pms.headshots) as headshots'),
                DB::raw('SUM(pms.damage_given) as dmg'),
                DB::raw('SUM(pms.score) as score'),
                DB::raw('MIN(m.started_at) as first_seen'),
                DB::raw('MAX(m.started_at) as last_seen'))
            ->groupBy('pms.player_id')->orderByDesc('matches')->get();

        $ids = $agg->pluck('player_id')->all();
        $players = empty($ids) ? collect()
            : DB::table('tracker_players')->whereIn('id', $ids)
                ->get(['id', 'name_clean', 'country_code', 'is_bot', 'elo_rating', 'level', 'status', 'total_play_time_minutes'])
                ->keyBy('id');

        $out = [];
        foreach ($agg as $a) {
            $p = $players[$a->player_id] ?? null;
            $k = (int) $a->kills; $d = (int) $a->deaths;
            $out[] = [(int) $a->player_id, $p->name_clean ?? '?', $p->country_code ?? '', (int) ($p->is_bot ?? 0),
                (int) $a->matches, $k, $d, $d > 0 ? round($k / $d, 2) : $k, (int) $a->headshots, (int) $a->dmg,
                (int) $a->score, $p->elo_rating ?? '', $p->level ?? '', $p->total_play_time_minutes ?? '',
                $a->first_seen, $a->last_seen];
        }
        $this->writeRows($sheet, ['player_id', 'name_clean', 'country', 'is_bot', 'matches', 'kills', 'deaths', 'kd', 'headshots', 'damage_given', 'score', 'elo_rating', 'level', 'play_time_min', 'first_seen', 'last_seen'], $out, true);
    }

    private function buildMatchStats(Spreadsheet $ss, int $id, int $limit = 0): void
    {
        $sheet = $this->newSheet($ss, 'MatchStats');
        $headers = ['match_id', 'map_name', 'match_started_at', 'player_id', 'name_clean', 'team', 'class', 'slot',
            'kills', 'deaths', 'headshots', 'gibs', 'kill_assists', 'team_kills', 'suicides', 'self_kills',
            'damage_given', 'damage_received', 'team_damage_given', 'team_damage_received', 'accuracy_pct', 'score',
            'ping_avg', 'playtime_seconds', 'time_played_pct', 'skill_rating', 'skill_rating_delta', 'prestige',
            'revives_given', 'revives_received', 'objectives_taken', 'weapon_bitmask', 'weapons_used', 'raw_skills'];
        $this->writeHeaders($sheet, $headers);

        $r = 2; $count = 0;
        DB::table('tracker_player_match_stats as pms')
            ->join('tracker_matches as m', 'm.id', '=', 'pms.match_id')
            ->where('pms.server_id', $id)
            ->select('pms.*', 'm.map_name as _map', 'm.started_at as _mstart')
            ->orderBy('pms.id')
            ->chunkById(2000, function ($chunk) use ($sheet, &$r, &$count, $limit) {
                foreach ($chunk as $row) {
                    $wu = $row->weapons_used !== null ? mb_substr((string) $row->weapons_used, 0, 32000) : '';
                    $rs = $row->raw_skills !== null ? mb_substr((string) $row->raw_skills, 0, 32000) : '';
                    $vals = [(int) $row->match_id, $row->_map, $row->_mstart, (int) $row->player_id, $row->name_clean_snapshot,
                        $row->team, $row->class, $row->slot, $row->kills, $row->deaths, $row->headshots, $row->gibs,
                        $row->kill_assists, $row->team_kills, $row->suicides, $row->self_kills, $row->damage_given,
                        $row->damage_received, $row->team_damage_given, $row->team_damage_received, $row->accuracy_pct,
                        $row->score, $row->ping_avg, $row->playtime_seconds, $row->time_played_pct, $row->skill_rating,
                        $row->skill_rating_delta, $row->prestige, $row->revives_given, $row->revives_received,
                        $row->objectives_taken, $row->weapon_bitmask, $wu, $rs];
                    $c = 1;
                    foreach ($vals as $v) { $this->setCell($sheet, $c++, $r, $v); }
                    $r++; $count++;
                    if ($limit > 0 && $count >= $limit) { return false; }
                }
            }, 'pms.id', 'id');

        $this->widths($sheet, [1 => 10, 2 => 18, 3 => 20, 4 => 9, 5 => 22, 33 => 40, 34 => 40]);
    }

    private function buildSessions(Spreadsheet $ss, int $id): void
    {
        $sheet = $this->newSheet($ss, 'Sessions (Poller)');
        $headers = ['player_id', 'started_at', 'ended_at', 'duration_minutes', 'map_name', 'kills', 'deaths', 'xp', 'score', 'team'];
        $this->writeHeaders($sheet, $headers);
        $r = 2;
        DB::table('tracker_player_sessions')->where('server_id', $id)->orderBy('id')
            ->chunkById(3000, function ($chunk) use ($sheet, &$r) {
                foreach ($chunk as $s) {
                    $vals = [(int) $s->player_id, $s->started_at, $s->ended_at, $s->duration_minutes, $s->map_name,
                        $s->kills, $s->deaths, $s->xp, $s->score, $s->team];
                    $c = 1;
                    foreach ($vals as $v) { $this->setCell($sheet, $c++, $r, $v); }
                    $r++;
                }
            });
        $this->widths($sheet, [1 => 10, 2 => 20, 3 => 20, 5 => 18]);
    }

    private function buildActivity(Spreadsheet $ss, int $id): void
    {
        $table = null;
        foreach (['tracker_server_snapshots', 'tracker_server_history', 'tracker_server_player_counts'] as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, 'server_id') && Schema::hasColumn($t, 'players') && Schema::hasColumn($t, 'polled_at')) {
                $table = $t; break;
            }
        }
        if ($table === null) { return; }
        $sheet = $this->newSheet($ss, 'Activity');
        $this->writeHeaders($sheet, ['polled_at', 'players']);
        $r = 2;
        DB::table($table)->where('server_id', $id)->orderBy('polled_at')
            ->chunkById(5000, function ($chunk) use ($sheet, &$r) {
                foreach ($chunk as $row) {
                    $this->setCell($sheet, 1, $r, $row->polled_at);
                    $this->setCell($sheet, 2, $r, $row->players);
                    $r++;
                }
            });
        $this->widths($sheet, [1 => 22, 2 => 10]);
    }
}
