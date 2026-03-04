<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Services\EmbeddingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class IndexAllFiles extends Command
{
    protected $signature   = 'wolffiles:index-all {--missing : Nur fehlende indexieren}';
    protected $description = 'Indexiert alle approved Dateien in Qdrant';

    public function handle(EmbeddingService $embedding): void
    {
        $embedding->ensureCollection();
        $files = File::where('status', 'approved')->with(['category', 'tags'])->get();

        if ($this->option('missing')) {
            $this->info("Checking which files are already indexed...");
            $ids = $files->pluck('id')->toArray();

            // In Batches von 100 prüfen
            $indexedIds = [];
            foreach (array_chunk($ids, 100) as $chunk) {
                $response = Http::post('http://localhost:6333/collections/wolffiles/points/get', [
                    'ids'          => $chunk,
                    'with_payload' => false,
                    'with_vector'  => false,
                ]);
                $found = collect($response->json('result', []))->pluck('id')->toArray();
                $indexedIds = array_merge($indexedIds, $found);
            }

            $files = $files->filter(fn($f) => !in_array($f->id, $indexedIds));
            $this->info("Already indexed: " . count($indexedIds) . ", Missing: " . $files->count());
        }

        $total = $files->count();
        $bar   = $this->output->createProgressBar($total);
        $this->info("Indexing {$total} files...");
        $bar->start();

        $success = 0;
        $failed  = 0;

        foreach ($files->chunk(10) as $batch) {
            foreach ($batch as $file) {
                $embedding->indexFile($file) ? $success++ : $failed++;
                $bar->advance();
            }
            usleep(500000);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done! ✅ Success: {$success}, ❌ Failed: {$failed}");
    }
}
