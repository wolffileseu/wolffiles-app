<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Models\FileScreenshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateVideoThumbnails extends Command
{
    protected $signature = 'wolffiles:video-thumbnails
        {--dry-run : Show what would be done without doing it}
        {--force : Process files even if they already have screenshots}
        {--limit=0 : Limit number of files to process (0 = all)}
        {--file-id= : Process a specific file ID}';

    protected $description = 'Extract video thumbnails (contact sheets) from ZIP/PK3 files using ffmpeg';

    private string $ffmpegPath = '/usr/local/bin/ffmpeg';
    private array $videoExtensions = ['mkv', 'mp4', 'avi', 'wmv', 'mpg', 'mpeg', 'flv', 'mov', 'webm'];
    private int $processed = 0;
    private int $skipped = 0;
    private int $errors = 0;

    public function handle(): int
    {
        $this->info('🎬 Video Thumbnail Generator');
        $this->info('============================');

        // Check ffmpeg
        if (!file_exists($this->ffmpegPath)) {
            // Try fallback
            $which = trim(shell_exec('which ffmpeg 2>/dev/null') ?? '');
            if ($which && file_exists($which)) {
                $this->ffmpegPath = $which;
            } else {
                $this->error('ffmpeg not found! Expected at: ' . $this->ffmpegPath);
                return 1;
            }
        }
        $this->info("Using ffmpeg: {$this->ffmpegPath}");

        $query = File::where('status', 'approved');

        if ($fileId = $this->option('file-id')) {
            $query = File::where('id', $fileId);
        }

        if (!$this->option('force')) {
            $query->whereDoesntHave('screenshots');
        }

        if ($limit = (int) $this->option('limit')) {
            $query->limit($limit);
        }

        $files = $query->get();
        $this->info("Found {$files->count()} files to check.");

        $bar = $this->output->createProgressBar($files->count());
        $bar->start();

        foreach ($files as $file) {
            try {
                $this->processFile($file);
            } catch (\Exception $e) {
                $this->errors++;
                $this->warn("\n  Error [{$file->id}] {$file->title}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Done! Processed: {$this->processed}, Skipped: {$this->skipped}, Errors: {$this->errors}");

        return 0;
    }

    private function processFile(File $file): void
    {
        $disk = Storage::disk('s3');
        $path = $file->file_path;

        if (!$path || !$disk->exists($path)) {
            $this->skipped++;
            return;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // Check if file itself is a video
        if (in_array($ext, $this->videoExtensions)) {
            $this->processVideoFile($file, $path, $disk);
            return;
        }

        // Check if it's a ZIP/PK3 that might contain videos
        if (!in_array($ext, ['zip', 'pk3', '7z', 'rar'])) {
            $this->skipped++;
            return;
        }

        // Download to temp
        $tempDir = storage_path("app/temp/video-{$file->id}");
        @mkdir($tempDir, 0755, true);
        $tempArchive = $tempDir . '/archive.' . $ext;

        try {
            file_put_contents($tempArchive, $disk->get($path));

            // Try to list contents with ZipArchive
            $videos = [];
            if (in_array($ext, ['zip', 'pk3'])) {
                $zip = new \ZipArchive();
                if ($zip->open($tempArchive) === true) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $name = $zip->getNameIndex($i);
                        $fileExt = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        if (in_array($fileExt, $this->videoExtensions)) {
                            $videos[] = $name;
                        }
                    }

                    // Extract videos
                    if (!empty($videos)) {
                        foreach ($videos as $videoName) {
                            $zip->extractTo($tempDir, $videoName);
                        }
                    }
                    $zip->close();
                }
            }

            if (empty($videos)) {
                $this->skipped++;
                return;
            }

            $this->info("\n  [{$file->id}] {$file->title}: Found " . count($videos) . " video(s)");

            // Process each video (max 3)
            $count = 0;
            foreach (array_slice($videos, 0, 3) as $videoName) {
                $videoPath = $tempDir . '/' . $videoName;
                if (!file_exists($videoPath)) continue;

                $screenshotPath = $this->generateContactSheet($videoPath, $tempDir, $count);
                if ($screenshotPath && file_exists($screenshotPath)) {
                    if ($this->option('dry-run')) {
                        $this->info("    DRY-RUN: Would upload contact sheet for: {$videoName}");
                    } else {
                        $this->uploadScreenshot($file, $screenshotPath, $videoName);
                    }
                    $count++;
                }
            }

            if ($count > 0) {
                $this->processed++;
            } else {
                $this->skipped++;
            }
        } finally {
            // Cleanup
            $this->cleanDir($tempDir);
        }
    }

    private function processVideoFile(File $file, string $s3Path, $disk): void
    {
        $tempDir = storage_path("app/temp/video-{$file->id}");
        @mkdir($tempDir, 0755, true);
        $tempVideo = $tempDir . '/video.' . pathinfo($s3Path, PATHINFO_EXTENSION);

        try {
            file_put_contents($tempVideo, $disk->get($s3Path));

            $screenshotPath = $this->generateContactSheet($tempVideo, $tempDir, 0);
            if ($screenshotPath && file_exists($screenshotPath)) {
                if ($this->option('dry-run')) {
                    $this->info("\n  DRY-RUN: Would upload contact sheet for [{$file->id}] {$file->title}");
                } else {
                    $this->uploadScreenshot($file, $screenshotPath, $file->file_name);
                }
                $this->processed++;
            } else {
                $this->skipped++;
            }
        } finally {
            $this->cleanDir($tempDir);
        }
    }

    private function generateContactSheet(string $videoPath, string $outputDir, int $index): ?string
    {
        $outputPath = $outputDir . "/sheet_{$index}.jpg";

        // Get video duration first
        $durationCmd = sprintf(
            '%s -i %s 2>&1 | grep "Duration"',
            escapeshellarg($this->ffmpegPath),
            escapeshellarg($videoPath)
        );
        $durationOutput = shell_exec($durationCmd);

        // Determine frame interval based on duration
        $fps = '1/30'; // Default: 1 frame every 30 seconds
        if ($durationOutput && preg_match('/Duration:\s*(\d+):(\d+):(\d+)/', $durationOutput, $m)) {
            $totalSeconds = ($m[1] * 3600) + ($m[2] * 60) + $m[3];
            if ($totalSeconds < 60) {
                $fps = '1/5';      // Short video: every 5 sec
            } elseif ($totalSeconds < 300) {
                $fps = '1/15';     // < 5 min: every 15 sec
            } elseif ($totalSeconds < 900) {
                $fps = '1/30';     // < 15 min: every 30 sec
            } else {
                $fps = '1/60';     // Long: every 60 sec
            }
            $this->line("    Duration: {$m[1]}:{$m[2]}:{$m[3]} → fps={$fps}");
        }

        // Generate contact sheet: 5x5 grid, 320px wide per tile
        $cmd = sprintf(
            '%s -y -i %s -vf "fps=%s,scale=320:-1,tile=5x5" -frames:v 1 -q:v 3 %s 2>&1',
            escapeshellarg($this->ffmpegPath),
            escapeshellarg($videoPath),
            $fps,
            escapeshellarg($outputPath)
        );

        $output = shell_exec($cmd);

        if (file_exists($outputPath) && filesize($outputPath) > 1000) {
            $this->info("    ✓ Contact sheet: " . round(filesize($outputPath) / 1024) . " KB");
            return $outputPath;
        }

        // Fallback: single frame at 10% of video
        $fallbackCmd = sprintf(
            '%s -y -ss 10 -i %s -vf "scale=640:-1" -frames:v 1 -q:v 3 %s 2>&1',
            escapeshellarg($this->ffmpegPath),
            escapeshellarg($videoPath),
            escapeshellarg($outputPath)
        );
        shell_exec($fallbackCmd);

        if (file_exists($outputPath) && filesize($outputPath) > 500) {
            $this->info("    ✓ Fallback single frame: " . round(filesize($outputPath) / 1024) . " KB");
            return $outputPath;
        }

        $this->warn("    ✗ Failed to generate screenshot");
        return null;
    }

    private function uploadScreenshot(File $file, string $localPath, string $videoName): void
    {
        $disk = Storage::disk('s3');
        $filename = Str::random(10) . '_video_sheet.jpg';
        $s3Path = "screenshots/{$file->id}/{$filename}";

        $disk->put($s3Path, file_get_contents($localPath), 'public');

        $sortOrder = ($file->screenshots()->max('sort_order') ?? 0) + 1;
        $hasPrimary = $file->screenshots()->where('is_primary', true)->exists();

        FileScreenshot::create([
            'file_id' => $file->id,
            'path' => $s3Path,
            'disk' => 's3',
            'sort_order' => $sortOrder,
            'is_primary' => !$hasPrimary,
            'original_filename' => 'Video: ' . pathinfo($videoName, PATHINFO_FILENAME),
        ]);

        $this->info("    ✓ Uploaded to S3: {$s3Path}");
    }

    private function cleanDir(string $dir): void
    {
        if (!is_dir($dir)) return;

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }
        @rmdir($dir);
    }
}