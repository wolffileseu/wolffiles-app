<?php

namespace App\Jobs;

use Throwable;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use App\Models\File;
use App\Services\VideoTranscoderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Extracts a video from a ZIP/PK3 archive (or uses a direct video file),
 * transcodes it to universal MP4 (H.264 + AAC, +faststart), uploads it to S3,
 * and updates the File model with playable_path metadata.
 */
class TranscodeVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 3600; // 1h hard cap per video
    public int $backoff = 300;

    private const SUPPORTED_VIDEO_EXTS = [
        'mkv', 'mp4', 'avi', 'wmv', 'mpg', 'mpeg', 'flv', 'mov', 'webm', 'm4v',
    ];

    private const MAX_INPUT_VIDEO_SIZE = 3221225472; // 3 GB raw video cap

    public function __construct(public int $fileId)
    {
        $this->onQueue('transcode');
    }

    /**
     * Unique lock prevents concurrent transcodes of the same file.
     */
    public function uniqueId(): string
    {
        return 'transcode_'.$this->fileId;
    }

    public function handle(VideoTranscoderService $transcoder): void
    {
        $file = File::find($this->fileId);
        if (!$file) {
            Log::warning("TranscodeVideoJob: file {$this->fileId} not found");
            return;
        }

        if (!$transcoder->isAvailable()) {
            $this->markFailed($file, 'ffmpeg/ffprobe not available on server');
            return;
        }

        // Already ready? Skip.
        if ($file->playable_status === 'ready' && !empty($file->playable_path)) {
            Log::info("TranscodeVideoJob: file {$file->id} already ready, skipping");
            return;
        }

        $file->update([
            'playable_status' => 'processing',
            'playable_error' => null,
        ]);

        $tempDir = storage_path('app/temp/transcode-'.$file->id.'-'.Str::random(6));
        @mkdir($tempDir, 0755, true);

        try {
            $videoPath = $this->prepareVideoFile($file, $tempDir);
            if (!$videoPath) {
                $this->markSkipped($file, 'No playable video file found in archive');
                return;
            }

            if (filesize($videoPath) > self::MAX_INPUT_VIDEO_SIZE) {
                $this->markSkipped($file, 'Video too large (> 3GB raw)');
                return;
            }

            $probe = $transcoder->probe($videoPath);
            if (!$probe) {
                $this->markFailed($file, 'ffprobe failed — corrupt or unsupported video');
                return;
            }

            $strategy = $transcoder->determineStrategy($probe, pathinfo($videoPath, PATHINFO_EXTENSION));
            if ($strategy === 'skip') {
                $this->markSkipped($file, 'Unsupported codec');
                return;
            }

            $outputPath = $tempDir.'/output.mp4';
            $result = $transcoder->convert($videoPath, $outputPath, $strategy);

            if (!$result['success']) {
                $this->markFailed($file, $result['error'] ?? 'ffmpeg conversion failed');
                return;
            }

            // Upload to S3
            $s3Path = sprintf('playable/%d/%s.mp4', $file->id, $file->slug);
            $uploaded = Storage::disk('s3')->put(
                $s3Path,
                fopen($outputPath, 'r'),
                ['visibility' => 'private', 'ContentType' => 'video/mp4']
            );

            if (!$uploaded) {
                $this->markFailed($file, 'S3 upload failed');
                return;
            }

            $file->update([
                'playable_path' => $s3Path,
                'playable_mime' => 'video/mp4',
                'playable_size' => $result['output_size'],
                'playable_duration_seconds' => $probe['duration_seconds'],
                'playable_codec' => 'h264+aac',
                'playable_status' => 'ready',
                'playable_error' => null,
                'playable_processed_at' => now(),
            ]);

            Log::info("TranscodeVideoJob: ready for file {$file->id}", [
                'strategy' => $strategy,
                'elapsed' => $result['elapsed'],
                'output_size_mb' => round($result['output_size'] / 1048576, 1),
                's3_path' => $s3Path,
            ]);
        } catch (Throwable $e) {
            Log::error("TranscodeVideoJob: exception for file {$file->id}", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->markFailed($file, 'Exception: '.substr($e->getMessage(), 0, 400));
            throw $e;
        } finally {
            $this->cleanDir($tempDir);
        }
    }

    /**
     * Download archive/video from S3 and return path to the playable video file.
     */
    private function prepareVideoFile(File $file, string $tempDir): ?string
    {
        $disk = Storage::disk('s3');
        $sourcePath = $file->file_path;

        if (!$disk->exists($sourcePath)) {
            Log::warning("TranscodeVideoJob: source missing for file {$file->id}: {$sourcePath}");
            return null;
        }

        $ext = strtolower($file->file_extension ?? pathinfo($sourcePath, PATHINFO_EXTENSION));

        // Stream-Download (handles large files without OOM)
        $sourceLocal = $tempDir.'/source.'.$ext;
        $stream = $disk->readStream($sourcePath);
        $fp = fopen($sourceLocal, 'w');
        stream_copy_to_stream($stream, $fp);
        fclose($fp);
        fclose($stream);

        // Direct video file?
        if (in_array($ext, self::SUPPORTED_VIDEO_EXTS, true)) {
            return $sourceLocal;
        }

        // ZIP/PK3 → find and extract largest video
        if (!in_array($ext, ['zip', 'pk3'], true)) {
            return null;
        }

        $zip = new ZipArchive();
        if ($zip->open($sourceLocal) !== true) {
            return null;
        }

        $candidates = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $stat = $zip->statIndex($i);
            $fileExt = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($fileExt, self::SUPPORTED_VIDEO_EXTS, true)) {
                $candidates[] = ['name' => $name, 'size' => $stat['size'] ?? 0];
            }
        }

        if (empty($candidates)) {
            $zip->close();
            return null;
        }

        // Pick largest video (most likely the actual movie, not a sample/teaser)
        usort($candidates, fn($a, $b) => $b['size'] <=> $a['size']);
        $picked = $candidates[0]['name'];

        $zip->extractTo($tempDir, $picked);
        $zip->close();

        $extractedPath = $tempDir.'/'.$picked;
        return file_exists($extractedPath) ? $extractedPath : null;
    }

    private function markSkipped(File $file, string $reason): void
    {
        $file->update([
            'playable_status' => 'skipped',
            'playable_error' => $reason,
            'playable_processed_at' => now(),
        ]);
        Log::info("TranscodeVideoJob: skipped file {$file->id} — {$reason}");
    }

    private function markFailed(File $file, string $reason): void
    {
        $file->update([
            'playable_status' => 'failed',
            'playable_error' => $reason,
            'playable_processed_at' => now(),
        ]);
        Log::warning("TranscodeVideoJob: failed file {$file->id} — {$reason}");
    }

    public function failed(Throwable $e): void
    {
        $file = File::find($this->fileId);
        if ($file) {
            $file->update([
                'playable_status' => 'failed',
                'playable_error' => 'Job exhausted retries: '.substr($e->getMessage(), 0, 400),
                'playable_processed_at' => now(),
            ]);
        }
    }

    private function cleanDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getRealPath()) : @unlink($item->getRealPath());
        }
        @rmdir($dir);
    }
}
