<?php

namespace App\Filament\Pages;

use App\Services\Tracker\PlayerMergeService;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PlayerMerge extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Tracker';
    protected static ?string $navigationLabel = 'Spieler zusammenführen';
    protected static ?int $navigationSort = 20;
    protected static string $view = 'filament.pages.player-merge';

    /** Manual merge form (free-form IDs for pairs the auto-detector misses). */
    public ?int $manualKeepId = null;
    public ?int $manualMergeId = null;

    /** Suspect-pair scan is expensive (~3 min); only run it on demand. */
    public bool $showSuspects = false;

    public function loadSuspects(): void
    {
        $this->showSuspects = true;
    }


    /**
     * Suspect duplicate pairs: an Enhanced player (real_guid_hash set) whose
     * match_stats name maps to exactly one Poller-only player (real NULL).
     * Ranked by server overlap (strong "same person" signal). Cached 10 min.
     */
    public function getSuspectPairs(): array
    {
        return Cache::remember('player_merge:suspects', now()->addMinutes(10), function () {
            $enh = DB::table('tracker_players')
                ->whereNotNull('real_guid_hash')
                ->whereNull('merged_into')
                ->where('is_bot', 0)
                ->pluck('id')->all();

            $pairs = [];
            foreach ($enh as $eid) {
                $nm = DB::table('tracker_player_match_stats')
                    ->where('player_id', $eid)
                    ->whereNotNull('name_clean_snapshot')->where('name_clean_snapshot', '!=', '')
                    ->select('name_clean_snapshot', DB::raw('count(*) as n'))
                    ->groupBy('name_clean_snapshot')->orderByDesc('n')->first();
                if (!$nm) continue;

                $key = strtolower(preg_replace('/\s+/', '', preg_replace('/\^./', '', $nm->name_clean_snapshot)));
                if ($key === '' || strlen($key) < 3) continue;

                $cands = DB::table('tracker_players')
                    ->whereNull('real_guid_hash')->whereNull('merged_into')->where('is_bot', 0)
                    ->whereRaw('LOWER(REPLACE(REPLACE(name_clean," ",""),"^","")) = ?', [$key])
                    ->pluck('id')->all();
                if (count($cands) !== 1) continue;
                $pid = $cands[0];

                $es = DB::table('tracker_player_sessions')->where('player_id', $eid)->distinct()->pluck('server_id')->all();
                $ps = DB::table('tracker_player_sessions')->where('player_id', $pid)->distinct()->pluck('server_id')->all();
                $common = count(array_intersect($es, $ps));
                $enhSrv = count($es);

                $keepRow  = DB::table('tracker_players')->where('id', $pid)->first(['name_clean', 'total_sessions', 'total_kills']);
                $mergeRow = DB::table('tracker_players')->where('id', $eid)->first(['total_sessions']);

                // Confidence: full overlap + distinct (longer) name = safer
                $ratio = $enhSrv > 0 ? $common / $enhSrv : 0;
                $confidence = 'low';
                if ($ratio >= 0.999 && strlen($key) >= 4) $confidence = 'high';
                elseif ($common > 0) $confidence = 'medium';

                $pairs[] = [
                    'keep_id'      => $pid,
                    'merge_id'     => $eid,
                    'name'         => $nm->name_clean_snapshot,
                    'keep_name'    => $keepRow->name_clean ?? '',
                    'keep_sessions'=> (int) ($keepRow->total_sessions ?? 0),
                    'keep_kills'   => (int) ($keepRow->total_kills ?? 0),
                    'merge_sessions'=> (int) ($mergeRow->total_sessions ?? 0),
                    'overlap'      => $common,
                    'enh_servers'  => $enhSrv,
                    'confidence'   => $confidence,
                ];
            }

            usort($pairs, function ($a, $b) {
                $rank = ['high' => 0, 'medium' => 1, 'low' => 2];
                return [$rank[$a['confidence']], -$a['overlap']] <=> [$rank[$b['confidence']], -$b['overlap']];
            });
            return $pairs;
        });
    }

    public function previewManual(): void
    {
        if (!$this->validManualIds()) return;
        $this->previewMerge((int) $this->manualKeepId, (int) $this->manualMergeId);
    }

    public function doManual(): void
    {
        if (!$this->validManualIds()) return;
        $this->doMerge((int) $this->manualKeepId, (int) $this->manualMergeId);
    }

    private function validManualIds(): bool
    {
        $keep = (int) $this->manualKeepId;
        $merge = (int) $this->manualMergeId;
        if ($keep <= 0 || $merge <= 0) {
            Notification::make()->warning()->title('IDs fehlen')->body('Bitte beide IDs angeben.')->send();
            return false;
        }
        if ($keep === $merge) {
            Notification::make()->warning()->title('Gleiche ID')->body('Keep und Merge dürfen nicht identisch sein.')->send();
            return false;
        }
        foreach ([$keep, $merge] as $id) {
            if (!DB::table('tracker_players')->where('id', $id)->exists()) {
                Notification::make()->danger()->title('Unbekannte ID')->body("Spieler #$id existiert nicht.")->send();
                return false;
            }
        }
        return true;
    }

    public function previewMerge(int $keepId, int $mergeId): void
    {
        $r = (new PlayerMergeService())->merge($keepId, $mergeId, true);
        if (!$r['ok']) {
            Notification::make()->danger()->title('Vorschau fehlgeschlagen')->body($r['error'])->send();
            return;
        }
        $lines = array_map(fn($a) => "{$a['table']}: {$a['action']} ({$a['rows']})", $r['actions']);
        Notification::make()->info()->title("Vorschau Merge $mergeId → $keepId")->body(implode("\n", $lines))->persistent()->send();
    }

    public function doMerge(int $keepId, int $mergeId): void
    {
        // Backup before write
        $this->backupPair($keepId, $mergeId);

        $r = (new PlayerMergeService())->merge($keepId, $mergeId, false);
        if (!$r['ok']) {
            Notification::make()->danger()->title('Merge fehlgeschlagen')->body($r['error'])->send();
            return;
        }
        Cache::forget('player_merge:suspects');
        Notification::make()->success()->title('Zusammengeführt')->body("Spieler $mergeId → $keepId. Backup gespeichert.")->send();
    }

    private function backupPair(int $keepId, int $mergeId): void
    {
        // Context-independent backup: snapshot affected rows as JSON into a
        // backup table (no shell/mysqldump dependency, works under FPM).
        \Illuminate\Support\Facades\Schema::hasTable('player_merge_backups') || \Illuminate\Support\Facades\Schema::create('player_merge_backups', function ($t) {
            $t->id();
            $t->unsignedBigInteger('keep_id');
            $t->unsignedBigInteger('merge_id');
            $t->string('table_name');
            $t->longText('rows_json');
            $t->timestamp('created_at')->nullable();
        });

        $tables = ['tracker_player_sessions','tracker_player_snapshots','tracker_player_match_stats','tracker_match_player_weapon_stats','tracker_player_weapon_stats','tracker_server_slots','tracker_player_aliases','tracker_player_rankings_30d'];

        foreach ($tables as $t) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($t)) continue;
            $rows = DB::table($t)->whereIn('player_id', [$keepId, $mergeId])->get();
            if ($rows->isEmpty()) continue;
            DB::table('player_merge_backups')->insert([
                'keep_id' => $keepId, 'merge_id' => $mergeId, 'table_name' => $t,
                'rows_json' => $rows->toJson(), 'created_at' => now(),
            ]);
        }
        // Also snapshot both tracker_players rows
        $players = DB::table('tracker_players')->whereIn('id', [$keepId, $mergeId])->get();
        DB::table('player_merge_backups')->insert([
            'keep_id' => $keepId, 'merge_id' => $mergeId, 'table_name' => 'tracker_players',
            'rows_json' => $players->toJson(), 'created_at' => now(),
        ]);
    }
}
