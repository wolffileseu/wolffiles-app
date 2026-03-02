<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanupTemp extends Command
{
    protected $signature = 'cleanup:temp';
    protected $description = 'Clean up temporary files older than 24 hours';

    public function handle(): int
    {
        $tempDir = storage_path('app/temp');
        $count = 0;

        if (!is_dir($tempDir)) {
            $this->info('No temp directory found.');
            return 0;
        }

        foreach (File::directories($tempDir) as $dir) {
            if (filemtime($dir) < now()->subHours(24)->timestamp) {
                File::deleteDirectory($dir);
                $count++;
            }
        }

        foreach (File::files($tempDir) as $file) {
            if ($file->getMTime() < now()->subHours(24)->timestamp) {
                File::delete($file->getRealPath());
                $count++;
            }
        }

        $this->info("Cleaned up {$count} temp items.");
        return 0;
    }
}
