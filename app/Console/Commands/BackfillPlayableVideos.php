<?php

namespace App\Console\Commands;

use App\Jobs\TranscodeVideoJob;
use App\Models\File;
use App\Services\VideoTranscoderService;
use Illuminate\Console\Command;

class BackfillPlayableVideos extends Command
{
    protected $signature = 'wolffiles:backfill-playable-videos
        {--dry-run : Show what would be processed without actually transcoding}
        {--limit=0 : Limit number of files (0 = all)}
        {--category-id= : Only process a specific category}
        {--file-id= : Only process a specific file ID}
        {--force : Re-transcode files that already have a playable version}
        {--sync : Run synchronously (block until done) instead of dispatching to queue}
        {--queue=transcode : Queue name when not using --sync}';

    protected $description = 'Backfill playable MP4 versions for video files (Movies category, etc.)';

    public function handle(VideoTranscoderService $transcoder): int
    {
        $this->info('🎬 Wolffiles Playable Video Backfill');
        $this->info('====================================');

        if (!$transcoder->isAvailable()) {
            $this->error('❌ ffmpeg/ffprobe not available');
            return 1;
        }

        $encoders = $transcoder->detectEncoders();
        $this->info('Encoders available: '.implode(' → ', $encoders));
        $this->newLine();

        $query = File::query()->where('status', 'approved');

        if ($fileId = $this->option('file-id')) {
            $query->where('id', $fileId);
        } elseif ($categoryId = $this->option('category-id')) {
            $query->where(function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId)
                    ->orWhereHas('category', fn ($c) => $c->where('parent_id', $categoryId));
            });
        } else {
            // Default: all Movies categories (parent_id=6 or id=6)
            $query->whereHas('category', function ($q) {
                $q->where('id', 6)->orWhere('parent_id', 6);
            });
        }

        if (!$this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('playable_status')
                    ->orWhereIn('playable_status', ['failed']); // retry failed
            });
        }

        if ($limit = (int) $this->option('limit')) {
            $query->limit($limit);
        }

        $files = $query->orderBy('id')->get();
        $total = $files->count();

        if ($total === 0) {
            $this->info('Nothing to process.');
            return 0;
        }

        $this->info("Found {$total} file(s) to process.");
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->table(
                ['ID', 'Slug', 'Title', 'Ext', 'Size', 'Current Status'],
                $files->map(fn ($f) => [
                    $f->id,
                    $f->slug,
                    \Illuminate\Support\Str::limit($f->title, 40),
                    $f->file_extension,
                    round($f->file_size / 1048576, 1).' MB',
                    $f->playable_status ?? '—',
                ])->toArray()
            );
            $this->info('🟡 Dry-run complete — no transcoding performed.');
            return 0;
        }

        $sync = $this->option('sync');
        $queue = $this->option('queue');

        if ($sync) {
            $this->warn('Running synchronously — this will block until all files are processed.');
            $this->warn('Expected duration: ~3 min/file with QSV = ~'.round($total * 3).' min total.');
            if (!$this->confirm('Continue?', true)) {
                return 0;
            }
        }

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('starting…');
        $bar->start();

        $dispatched = 0;
        $synced = 0;
        $failed = 0;

        foreach ($files as $file) {
            $bar->setMessage("file #{$file->id}: ".\Illuminate\Support\Str::limit($file->title, 30));

            if ($sync) {
                try {
                    $job = new TranscodeVideoJob($file->id);
                    $job->handle($transcoder);
                    $file->refresh();
                    if ($file->playable_status === 'ready') {
                        $synced++;
                    } else {
                        $failed++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $this->newLine();
                    $this->warn("  Error on #{$file->id}: ".$e->getMessage());
                }
            } else {
                $file->update(['playable_status' => 'pending']);
                TranscodeVideoJob::dispatch($file->id)->onQueue($queue);
                $dispatched++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($sync) {
            $this->info("✅ Synced: {$synced}");
            if ($failed > 0) {
                $this->warn("⚠️  Failed: {$failed} — check storage/logs/laravel.log");
            }
        } else {
            $this->info("✅ Dispatched {$dispatched} jobs to queue '{$queue}'");
            $this->info('   Workers will process them in the background.');
            $this->info('   Monitor: tail -f storage/logs/laravel.log | grep TranscodeVideo');
        }

        return 0;
    }
}
