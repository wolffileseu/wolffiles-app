<?php

namespace App\Jobs;

use App\Models\File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ScanFileForViruses implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600;

    public function __construct(public File $file) {}

    public function handle(): void
    {
        if (!config('services.clamav.enabled', false)) {
            $this->file->update([
                'virus_scanned' => true,
                'virus_clean' => true,
                'virus_scan_result' => 'ClamAV not enabled - skipped',
            ]);
            return;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'wf_scan_');
        $stream = Storage::disk('s3')->readStream($this->file->file_path);
        file_put_contents($tempPath, $stream);

        try {
            $socket = config('services.clamav.socket', '/var/run/clamav/clamd.sock');

            // Use clamscan command line tool
            exec("clamscan --no-summary " . escapeshellarg($tempPath) . " 2>&1", $output, $returnCode);

            $scanResult = implode("\n", $output);

            $this->file->update([
                'virus_scanned' => true,
                'virus_clean' => $returnCode === 0,
                'virus_scan_result' => $scanResult,
            ]);

            // If virus found, change status
            if ($returnCode !== 0) {
                $this->file->update([
                    'status' => 'rejected',
                    'rejection_reason' => 'Virus detected: ' . $scanResult,
                ]);
            }
        } catch (\Exception $e) {
            $this->file->update([
                'virus_scanned' => true,
                'virus_clean' => null,
                'virus_scan_result' => 'Scan error: ' . $e->getMessage(),
            ]);
        } finally {
            @unlink($tempPath);
        }
    }
}
