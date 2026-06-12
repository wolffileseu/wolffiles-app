<?php

namespace App\Console\Commands;

use App\Services\Tracker\PlayerClusterMergeService;
use Illuminate\Console\Command;

class TrackerMergeDupes extends Command
{
    protected $signature = 'tracker:merge-dupes {--execute : Actually merge (default is dry-run)} {--min-key-len=3} {--limit=0 : Limit number of clusters (0 = all)} {--show=40 : How many detail lines to print}';
    protected $description = 'Merge duplicate tracker_players by normalized name key (color-code dupes).';

    public function handle(PlayerClusterMergeService $svc): int
    {
        $dryRun = !$this->option('execute');
        $this->info($dryRun ? 'DRY-RUN (no writes). Use --execute to apply.' : 'EXECUTE MODE — writing changes.');

        $r = $svc->run($dryRun, (int) $this->option('min-key-len'), (int) $this->option('limit'));

        $show = (int) $this->option('show');
        $shown = 0;
        foreach ($r['details'] as $d) {
            if ($d['action'] === 'MERGE' && $shown < $show) {
                $this->line(sprintf('  MERGE  %2d rows  %dG  keep=%s  removed=%d  key=%s',
                    $d['rows'], $d['guids'], $d['keep'] ?? '-', $d['removed'] ?? 0, $d['key']));
                $shown++;
            }
        }
        foreach ($r['details'] as $d) {
            if (in_array($d['action'], ['REVIEW','SKIP'], true) && $shown < $show) {
                $this->line(sprintf('  %-6s %2d rows  %dG  key=%s', $d['action'], $d['rows'], $d['guids'], $d['key']));
                $shown++;
            }
        }

        $this->newLine();
        $this->table(
            ['clusters','merged','skipped','review','rows_removed','mode'],
            [[ $r['clusters'], $r['merged'], $r['skipped'], $r['review'], $r['rows_removed'], $dryRun ? 'dry-run' : 'EXECUTED' ]]
        );

        return self::SUCCESS;
    }
}
