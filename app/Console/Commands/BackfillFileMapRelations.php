<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Services\FileRelationMatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillFileMapRelations extends Command
{
    protected $signature = 'files:backfill-map-relations
                            {--dry-run : Show matches without writing}
                            {--reset : Delete all auto relations before running (keeps manual)}
                            {--bot-id= : Only process a single bot file id (for testing)}';

    protected $description = 'Match bot/waypoint files to maps by filename token analysis.';

    public function handle(FileRelationMatcher $matcher): int
    {
        if ($this->option('reset') && !$this->option('dry-run')) {
            $deleted = DB::table('file_map_relations')->where('is_manual', false)->delete();
            $this->warn("Deleted {$deleted} auto relations (manual entries kept).");
        }

        $query = File::where('category_id', FileRelationMatcher::CATEGORY_BOT);
        if ($botId = $this->option('bot-id')) {
            $query->where('id', $botId);
        }
        $bots = $query->get(['id', 'file_name', 'title', 'category_id']);

        $this->info("Processing {$bots->count()} bot files...");
        $bar = $this->output->createProgressBar($bots->count());
        $bar->start();

        $stats = ['matched' => 0, 'skipped' => 0, 'no_match' => 0, 'multi_match' => 0, 'inserted' => 0];
        $samples = [];
        $isDry = (bool) $this->option('dry-run');

        // Snapshot row count before to compute insertions
        $rowsBefore = DB::table('file_map_relations')->count();

        foreach ($bots as $bot) {
            $bar->advance();
            $haystackRaw = trim(($bot->file_name ?? '') . ' ' . ($bot->title ?? ''));

            // Skip generic installers (mirror service behaviour)
            $isSkip = false;
            foreach ($matcher->skipPatterns as $p) {
                if (preg_match($p, $haystackRaw)) { $isSkip = true; break; }
            }
            if ($isSkip) {
                $stats['skipped']++;
                continue;
            }

            $matches = $matcher->findMatches($haystackRaw);
            if (empty($matches)) {
                $stats['no_match']++;
                if (count($samples) < 15) $samples[] = "NO MATCH: [{$bot->id}] {$haystackRaw}";
                continue;
            }
            if (count($matches) > 1) $stats['multi_match']++;

            if (! $isDry) {
                $matcher->matchBot($bot, deleteOldAuto: false);
            }

            $stats['matched']++;
            if (count($samples) < 15) {
                $first = array_key_first($matches);
                $conf = $matcher->scoreConfidence($first, count($matches) > 1);
                $samples[] = sprintf('MATCH (%.2f): [%d] %s -> %s%s',
                    $conf, $bot->id, $haystackRaw, $first,
                    count($matches) > 1 ? ' [AMBIGUOUS x' . count($matches) . ']' : ''
                );
            }
        }
        $bar->finish();
        $this->newLine(2);

        $stats['inserted'] = $isDry ? 0 : (DB::table('file_map_relations')->count() - $rowsBefore);

        $this->table(['metric', 'count'], [
            ['bot files processed', $bots->count()],
            ['matched',             $stats['matched']],
            ['skipped (generic)',   $stats['skipped']],
            ['no match',            $stats['no_match']],
            ['multi-match (ambig)', $stats['multi_match']],
            ['rows added',          $stats['inserted']],
        ]);

        $this->info('Sample results:');
        foreach ($samples as $s) $this->line('  ' . $s);

        if ($isDry) {
            $this->warn('DRY RUN - no rows written. Use without --dry-run to apply.');
        }

        return self::SUCCESS;
    }
}
