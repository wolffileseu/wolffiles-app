<?php

namespace App\Jobs;

use App\Models\File;
use App\Models\FileScreenshot;
use App\Services\BspExtractorService;
use App\Services\FileAnalyzerService;
use App\Services\FileUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AnalyzeUploadedFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600; // increased for video processing

    private static array $videoExtensions = ['mkv', 'mp4', 'avi', 'wmv', 'mpg', 'mpeg', 'flv', 'mov', 'webm'];

    public function __construct(public File $file) {}

    public function handle(FileAnalyzerService $analyzer, FileUploadService $uploadService): void
    {
        // Download file from S3 to temp location
        $tempPath = tempnam(sys_get_temp_dir(), 'wf_analyze_');
        $stream = Storage::disk('s3')->readStream($this->file->file_path);

        if (!$stream) {
            $this->fail(new \Exception('Could not read file from S3'));
            return;
        }

        $fp = fopen($tempPath, 'w');
        stream_copy_to_stream($stream, $fp);
        fclose($fp);
        fclose($stream);

        try {
            $result = $analyzer->analyze($tempPath, $this->file->file_name);

            // Update file with extracted metadata
            $updateData = [
                'file_hash' => $result['file_hash'] ?? $this->file->file_hash,
                'mime_type' => $result['mime_type'] ?? $this->file->mime_type,
                'extracted_metadata' => $result['extracted_metadata'] ?? null,
            ];

            if (!empty($result['map_name']) && !$this->file->map_name) {
                $updateData['map_name'] = $result['map_name'];
            }

            if (!empty($result['game']) && !$this->file->game) {
                $updateData['game'] = $result['game'];
            }

            if (!empty($result['readme_content']) && !$this->file->description) {
                $updateData['readme_content'] = $result['readme_content'];
                $updateData['description'] = $result['readme_content'];
            }

            $this->file->update($updateData);

            // Store extracted images as screenshots BEFORE cleanup
            if (!empty($result['extracted_images'])) {
                foreach ($result['extracted_images'] as $image) {
                    if (file_exists($image['path'])) {
                        try {
                            $uploadService->storeExtractedImage(
                                $this->file,
                                $image['path'],
                                $image['original_name'] ?? 'screenshot.png',
                                $image['source'] ?? 'extracted'
                            );
                        } catch (\Exception $e) {
                            \Log::warning("Failed to store extracted image: {$e->getMessage()}");
                        }
                    }
                }
            }

            // Generate video thumbnails if applicable
            $this->processVideoThumbnails($tempPath, $uploadService);

            // Extract BSP for 3D map preview
            $this->extractBspForPreview($tempPath);

        } finally {
            // Clean up AFTER images are processed
            $analyzer->cleanup();
            @unlink($tempPath);
        }
    }

    /**
     * Extract BSP file for 3D map preview
     */
    private function extractBspForPreview(string $tempPath): void
    {
        // Only process if the file has a map_name (set during analysis)
        if (empty($this->file->map_name)) {
            return;
        }

        // Already has BSP extracted
        if (!empty($this->file->bsp_path)) {
            return;
        }

        try {
            $extractor = app(BspExtractorService::class);
            $extractor->extract($this->file, $tempPath);
        } catch (\Exception $e) {
            \Log::warning("BSP extraction in upload job [{$this->file->id}]: {$e->getMessage()}");
        }
    }

    /**
     * Check for videos in archive or direct video files and generate contact sheets
     */
    private function processVideoThumbnails(string $tempPath, FileUploadService $uploadService): void
    {
        $ffmpeg = $this->findFfmpeg();
        if (!$ffmpeg) {
            return; // ffmpeg not available, skip silently
        }

        $ext = strtolower(pathinfo($this->file->file_name, PATHINFO_EXTENSION));

        // Case 1: File itself is a video
        if (in_array($ext, self::$videoExtensions)) {
            $this->generateAndStoreContactSheet($ffmpeg, $tempPath, $this->file->file_name, $uploadService);
            return;
        }

        // Case 2: Archive containing videos
        if (!in_array($ext, ['zip', 'pk3', 'rar', '7z'])) {
            return;
        }

        $zip = new \ZipArchive();
        if ($zip->open($tempPath) !== true) {
            return;
        }

        $videos = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $fileExt = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($fileExt, self::$videoExtensions)) {
                $videos[] = $name;
            }
        }

        if (empty($videos)) {
            $zip->close();
            return;
        }

        \Log::info("VideoThumbnails: Found " . count($videos) . " video(s) in [{$this->file->id}] {$this->file->title}");

        $tempDir = storage_path("app/temp/video-extract-{$this->file->id}");
        @mkdir($tempDir, 0755, true);

        try {
            // Process max 3 videos
            $count = 0;
            foreach (array_slice($videos, 0, 3) as $videoName) {
                $zip->extractTo($tempDir, $videoName);
                $videoPath = $tempDir . '/' . $videoName;

                if (file_exists($videoPath)) {
                    $this->generateAndStoreContactSheet($ffmpeg, $videoPath, $videoName, $uploadService);
                    $count++;
                }
            }

            \Log::info("VideoThumbnails: Generated {$count} contact sheet(s) for [{$this->file->id}]");
        } finally {
            $zip->close();
            $this->cleanDir($tempDir);
        }
    }

    /**
     * Generate a contact sheet and upload it as a screenshot
     */
    private function generateAndStoreContactSheet(string $ffmpeg, string $videoPath, string $videoName, FileUploadService $uploadService): void
    {
        $tempDir = storage_path("app/temp/ffmpeg-{$this->file->id}-" . Str::random(6));
        @mkdir($tempDir, 0755, true);
        $outputPath = $tempDir . '/sheet.jpg';

        try {
            // Get video duration to set appropriate fps
            $durationCmd = sprintf('%s -i %s 2>&1', escapeshellarg($ffmpeg), escapeshellarg($videoPath));
            $durationOutput = shell_exec($durationCmd);

            $fps = '1/30';
            if ($durationOutput && preg_match('/Duration:\s*(\d+):(\d+):(\d+)/', $durationOutput, $m)) {
                $totalSeconds = ($m[1] * 3600) + ($m[2] * 60) + $m[3];
                if ($totalSeconds < 60) {
                    $fps = '1/5';
                } elseif ($totalSeconds < 300) {
                    $fps = '1/15';
                } elseif ($totalSeconds < 900) {
                    $fps = '1/30';
                } else {
                    $fps = '1/60';
                }
            }

            // Generate 5x5 contact sheet
            $cmd = sprintf(
                '%s -y -i %s -vf "fps=%s,scale=320:-1,tile=5x5" -frames:v 1 -q:v 3 %s 2>&1',
                escapeshellarg($ffmpeg),
                escapeshellarg($videoPath),
                $fps,
                escapeshellarg($outputPath)
            );
            shell_exec($cmd);

            // Fallback: single frame at 10 seconds
            if (!file_exists($outputPath) || filesize($outputPath) < 1000) {
                $fallbackCmd = sprintf(
                    '%s -y -ss 10 -i %s -vf "scale=640:-1" -frames:v 1 -q:v 3 %s 2>&1',
                    escapeshellarg($ffmpeg),
                    escapeshellarg($videoPath),
                    escapeshellarg($outputPath)
                );
                shell_exec($fallbackCmd);
            }

            // Upload if successful
            if (file_exists($outputPath) && filesize($outputPath) > 500) {
                $disk = Storage::disk('s3');
                $filename = Str::random(10) . '_video_sheet.jpg';
                $s3Path = "screenshots/{$this->file->id}/{$filename}";

                $disk->put($s3Path, file_get_contents($outputPath), 'public');

                $sortOrder = ($this->file->screenshots()->max('sort_order') ?? 0) + 1;
                $hasPrimary = $this->file->screenshots()->where('is_primary', true)->exists();

                FileScreenshot::create([
                    'file_id' => $this->file->id,
                    'path' => $s3Path,
                    'disk' => 's3',
                    'sort_order' => $sortOrder,
                    'is_primary' => !$hasPrimary,
                    'original_name' => 'Video: ' . pathinfo($videoName, PATHINFO_FILENAME),
                    'source' => 'video_extract',
                ]);

                \Log::info("VideoThumbnails: Uploaded contact sheet for [{$this->file->id}]: {$s3Path}");
            } else {
                \Log::warning("VideoThumbnails: Failed to generate sheet for [{$this->file->id}] video: {$videoName}");
            }
        } catch (\Exception $e) {
            \Log::warning("VideoThumbnails: Error for [{$this->file->id}]: {$e->getMessage()}");
        } finally {
            $this->cleanDir($tempDir);
        }
    }

    /**
     * Find ffmpeg binary
     */
    private function findFfmpeg(): ?string
    {
        // Check config first
        $configured = config('app.ffmpeg_path');
        if ($configured && file_exists($configured)) {
            return $configured;
        }

        // Check common locations
        foreach (['/usr/local/bin/ffmpeg', '/usr/bin/ffmpeg'] as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Try which
        $which = trim(shell_exec('which ffmpeg 2>/dev/null') ?? '');
        if ($which && file_exists($which)) {
            return $which;
        }

        return null;
    }

    /**
     * Recursively clean up a temp directory
     */
    private function cleanDir(string $dir): void
    {
        if (!is_dir($dir)) return;

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir() ? @rmdir($file->getRealPath()) : @unlink($file->getRealPath());
        }
        @rmdir($dir);
    }
}