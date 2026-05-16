<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Transcodes videos to universal MP4 (H.264 + AAC, +faststart).
 *
 * Encoder strategy (auto-detected at runtime):
 *   1. Intel QSV (h264_qsv) — fastest, uses iGPU
 *   2. VAAPI (h264_vaapi) — also iGPU, fallback if QSV unavailable
 *   3. libx264 (CPU) — universal fallback
 *
 * If a HW encode fails on a specific file, we automatically retry with CPU.
 */
class VideoTranscoderService
{
    private string $ffmpegPath;
    private string $ffprobePath;
    private string $vaapiDriver = 'iHD'; // iHD for Gen8+ Intel (UHD 630 is Gen 9.5)
    private string $renderDevice = '/dev/dri/renderD128';

    private const MAX_OUTPUT_SIZE = 2147483648; // 2 GB

    public function __construct()
    {
        $this->ffmpegPath = $this->findBinary('ffmpeg');
        $this->ffprobePath = $this->findBinary('ffprobe');
    }

    public function isAvailable(): bool
    {
        return $this->ffmpegPath !== '' && $this->ffprobePath !== '';
    }

    /**
     * Detect which hardware encoders are usable on this system.
     * Cached per-instance for performance.
     */
    public function detectEncoders(): array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $available = [];

        if (!$this->isAvailable() || !file_exists($this->renderDevice)) {
            $cached = ['cpu'];
            return $cached;
        }

        $encoders = shell_exec($this->ffmpegPath.' -hide_banner -encoders 2>&1') ?? '';

        // Check QSV availability (decode + encode both)
        if (str_contains($encoders, 'h264_qsv')) {
            if ($this->testHardwareEncoder('qsv')) {
                $available[] = 'qsv';
            }
        }

        if (str_contains($encoders, 'h264_vaapi')) {
            if ($this->testHardwareEncoder('vaapi')) {
                $available[] = 'vaapi';
            }
        }

        $available[] = 'cpu'; // always present

        $cached = $available;
        Log::info('VideoTranscoder: detected encoders', ['encoders' => $available]);
        return $cached;
    }

    /**
     * Sanity-check a hardware encoder by encoding 1 frame of test pattern.
     */
    private function testHardwareEncoder(string $type): bool
    {
        $tmpOut = tempnam(sys_get_temp_dir(), 'hw_test_').'.mp4';

        $env = "LIBVA_DRIVER_NAME={$this->vaapiDriver} ";

        if ($type === 'qsv') {
            $cmd = sprintf(
                '%s%s -hide_banner -y -f lavfi -i testsrc=duration=0.1:size=320x240:rate=1 '
                .'-c:v h264_qsv -preset veryfast %s 2>&1',
                $env,
                escapeshellarg($this->ffmpegPath),
                escapeshellarg($tmpOut)
            );
        } else { // vaapi
            $cmd = sprintf(
                '%s%s -hide_banner -y -vaapi_device %s -f lavfi -i testsrc=duration=0.1:size=320x240:rate=1 '
                .'-vf "format=nv12,hwupload" -c:v h264_vaapi %s 2>&1',
                $env,
                escapeshellarg($this->ffmpegPath),
                escapeshellarg($this->renderDevice),
                escapeshellarg($tmpOut)
            );
        }

        shell_exec($cmd);
        $ok = file_exists($tmpOut) && filesize($tmpOut) > 100;
        @unlink($tmpOut);
        return $ok;
    }

    public function probe(string $videoPath): ?array
    {
        if (!file_exists($videoPath) || $this->ffprobePath === '') {
            return null;
        }

        $cmd = sprintf(
            '%s -v quiet -print_format json -show_format -show_streams %s 2>&1',
            escapeshellarg($this->ffprobePath),
            escapeshellarg($videoPath)
        );

        $output = shell_exec($cmd);
        $data = json_decode($output ?? '', true);

        if (!is_array($data) || empty($data['streams'])) {
            return null;
        }

        $videoStream = null;
        $audioStream = null;
        foreach ($data['streams'] as $stream) {
            $type = $stream['codec_type'] ?? '';
            if ($type === 'video' && !$videoStream) {
                $videoStream = $stream;
            } elseif ($type === 'audio' && !$audioStream) {
                $audioStream = $stream;
            }
        }

        if (!$videoStream) {
            return null;
        }

        return [
            'video_codec' => strtolower($videoStream['codec_name'] ?? 'unknown'),
            'audio_codec' => $audioStream ? strtolower($audioStream['codec_name']) : null,
            'width' => (int)($videoStream['width'] ?? 0),
            'height' => (int)($videoStream['height'] ?? 0),
            'duration_seconds' => (int)round((float)($data['format']['duration'] ?? 0)),
            'format' => strtolower($data['format']['format_name'] ?? ''),
            'size' => (int)($data['format']['size'] ?? filesize($videoPath)),
            'bit_rate' => (int)($data['format']['bit_rate'] ?? 0),
        ];
    }

    public function determineStrategy(array $probe, string $sourceExtension): string
    {
        if (empty($probe['video_codec']) || $probe['video_codec'] === 'unknown') {
            return 'skip';
        }

        $isH264 = in_array($probe['video_codec'], ['h264', 'avc', 'avc1'], true);
        $isAAC = $probe['audio_codec'] === null
              || in_array($probe['audio_codec'], ['aac', 'mp4a'], true);
        $isMp4 = in_array(strtolower($sourceExtension), ['mp4', 'm4v'], true)
              || str_contains($probe['format'], 'mp4');

        return ($isH264 && $isAAC && $isMp4) ? 'copy' : 'transcode';
    }

    /**
     * Convert video, with automatic encoder fallback if a HW encoder fails.
     */
    public function convert(string $inputPath, string $outputPath, string $strategy): array
    {
        if (!file_exists($inputPath)) {
            return ['success' => false, 'error' => 'Input file does not exist'];
        }
        if (!$this->isAvailable()) {
            return ['success' => false, 'error' => 'ffmpeg/ffprobe not available'];
        }

        @mkdir(dirname($outputPath), 0755, true);

        // 'copy' strategy never uses a hardware encoder — it's just a remux
        if ($strategy === 'copy') {
            return $this->runCopy($inputPath, $outputPath);
        }

        // Try hardware encoders in priority order, fall back to CPU on failure
        $encoders = $this->detectEncoders();
        $errors = [];

        foreach ($encoders as $encoder) {
            $result = $this->runEncoder($encoder, $inputPath, $outputPath);
            if ($result['success']) {
                $result['encoder_used'] = $encoder;
                return $result;
            }
            $errors[$encoder] = $result['error'] ?? 'unknown';
            Log::warning("VideoTranscoder: {$encoder} encoder failed, trying next", [
                'error' => $errors[$encoder],
                'input' => basename($inputPath),
            ]);
            @unlink($outputPath); // clean up partial output before retry
        }

        return [
            'success' => false,
            'error' => 'All encoders failed: '.json_encode($errors),
            'strategy' => $strategy,
        ];
    }

    private function runCopy(string $input, string $output): array
    {
        $cmd = sprintf(
            'nice -n 19 %s -y -i %s -c copy -movflags +faststart %s 2>&1',
            escapeshellarg($this->ffmpegPath),
            escapeshellarg($input),
            escapeshellarg($output)
        );
        return $this->execute($cmd, 'copy', $output);
    }

    private function runEncoder(string $encoder, string $input, string $output): array
    {
        $env = "LIBVA_DRIVER_NAME={$this->vaapiDriver} ";
        $ffmpeg = escapeshellarg($this->ffmpegPath);
        $in = escapeshellarg($input);
        $out = escapeshellarg($output);
        $device = escapeshellarg($this->renderDevice);

        switch ($encoder) {
            case 'qsv':
                // Decode + encode both on GPU. -global_quality 23 ~ CRF 23 for QSV.
                $cmd = "{$env}nice -n 19 {$ffmpeg} -y "
                    ."-init_hw_device qsv=hw:{$device} -filter_hw_device hw "
                    ."-i {$in} "
                    ."-vf 'format=nv12,hwupload=extra_hw_frames=64,scale_qsv=w=min(1920\\,iw):h=-1' "
                    .'-c:v h264_qsv -preset medium -global_quality 23 -look_ahead 0 '
                    .'-c:a aac -b:a 128k -ac 2 '
                    ."-movflags +faststart -max_muxing_queue_size 1024 {$out} 2>&1";
                break;

            case 'vaapi':
                $cmd = "{$env}nice -n 19 {$ffmpeg} -y "
                    ."-vaapi_device {$device} "
                    ."-i {$in} "
                    ."-vf 'scale_vaapi=w=min(1920,iw):h=-1:format=nv12' "
                    .'-c:v h264_vaapi -qp 23 '
                    .'-c:a aac -b:a 128k -ac 2 '
                    ."-movflags +faststart -max_muxing_queue_size 1024 {$out} 2>&1";
                break;

            case 'cpu':
            default:
                $cmd = "nice -n 19 {$ffmpeg} -y -i {$in} "
                    .'-c:v libx264 -preset medium -crf 23 '
                    .'-c:a aac -b:a 128k -ac 2 '
                    ."-vf \"scale='min(1920,iw)':-2\" "
                    .'-movflags +faststart -max_muxing_queue_size 1024 '
                    ."{$out} 2>&1";
                break;
        }

        return $this->execute($cmd, $encoder, $output);
    }

    private function execute(string $cmd, string $label, string $output): array
    {
        $startTime = microtime(true);
        Log::info("VideoTranscoder: starting {$label}");

        $result = shell_exec($cmd);
        $elapsed = round(microtime(true) - $startTime, 1);

        if (!file_exists($output) || filesize($output) < 1024) {
            return [
                'success' => false,
                'error' => 'ffmpeg failed ('.$label.'): '.substr(trim($result ?? ''), -400),
                'elapsed' => $elapsed,
            ];
        }

        if (filesize($output) > self::MAX_OUTPUT_SIZE) {
            @unlink($output);
            return [
                'success' => false,
                'error' => 'Output file too large (> 2GB)',
            ];
        }

        Log::info("VideoTranscoder: completed {$label} in {$elapsed}s", [
            'output_size_mb' => round(filesize($output) / 1048576, 1),
        ]);

        return [
            'success' => true,
            'output_path' => $output,
            'output_size' => filesize($output),
            'codec' => 'h264',
            'strategy' => $label,
            'elapsed' => $elapsed,
        ];
    }

    private function findBinary(string $name): string
    {
        $configured = config("app.{$name}_path");
        if ($configured && file_exists($configured)) {
            return $configured;
        }
        // Prefer system ffmpeg (RPM Fusion, has VAAPI/QSV) over static fallback
        foreach (["/usr/bin/{$name}", "/usr/local/bin/{$name}"] as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        $which = trim(shell_exec("which {$name} 2>/dev/null") ?? '');
        return ($which && file_exists($which)) ? $which : '';
    }
}
