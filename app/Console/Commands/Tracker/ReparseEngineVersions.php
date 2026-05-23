<?php

namespace App\Console\Commands\Tracker;

use App\Services\Tracker\EngineVersionParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReparseEngineVersions extends Command
{
    protected $signature = 'tracker:reparse-engine
                            {--dry-run : Show what would change without writing}
                            {--limit=0 : Limit number of rows (0 = all)}
                            {--show-unknown : Print os strings that resolve to unknown}';

    protected $description = 'Re-parse tracker_servers.os into engine_family/version/platform/build_date columns';

    public function handle(EngineVersionParser $parser): int
    {
        $query = DB::table('tracker_servers')
            ->select('id', 'os')
            ->whereNotNull('os')
            ->where('os', '!=', '');

        if ($limit = (int) $this->option('limit')) {
            $query->limit($limit);
        }

        $total = $query->count();
        $this->info("Processing {$total} rows" . ($this->option('dry-run') ? ' (DRY RUN)' : ''));

        $bar = $this->output->createProgressBar($total);
        $updated = 0;
        $skipped = 0;
        $families = [];
        $unknownExamples = [];

        $query->orderBy('id')->chunkById(500, function ($rows) use ($parser, $bar, &$updated, &$skipped, &$families, &$unknownExamples) {
            foreach ($rows as $row) {
                $parsed = $parser->parse($row->os);
                $key = $parsed['engine_family'] ?? 'null';
                $families[$key] = ($families[$key] ?? 0) + 1;

                if ($key === 'unknown' && count($unknownExamples) < 30) {
                    $unknownExamples[$row->os] = ($unknownExamples[$row->os] ?? 0) + 1;
                }

                if ($parsed['engine_family'] === null) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                if (! $this->option('dry-run')) {
                    DB::table('tracker_servers')->where('id', $row->id)->update([
                        'engine_family'       => $parsed['engine_family'],
                        'engine_version'      => $parsed['engine_version'],
                        'engine_platform'     => $parsed['engine_platform'],
                        'engine_build_date'   => $parsed['engine_build_date'],
                        'engine_is_dev_build' => $parsed['engine_is_dev_build'],
                        'engine_display'      => $parsed['engine_display'],
                    ]);
                }

                $updated++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Updated: {$updated}, Skipped (null os): {$skipped}");
        $this->newLine();
        $this->table(['Family', 'Count'],
            collect($families)->map(fn ($c, $f) => [$f, $c])->sortByDesc(1)->values()->all()
        );

        if ($this->option('show-unknown') && $unknownExamples) {
            $this->newLine();
            $this->warn('Unknown os strings (top 30):');
            $this->table(['os', 'count'],
                collect($unknownExamples)->map(fn ($c, $os) => [$os, $c])->sortByDesc(1)->values()->all()
            );
        }

        return self::SUCCESS;
    }
}
