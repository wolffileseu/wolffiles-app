<?php

namespace App\Console\Commands;

use App\Models\File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class VirusScanFiles extends Command
{
    protected $signature = 'wolffiles:virus-scan
        {--dry-run : Show what would be scanned without scanning}
        {--force : Re-scan files that were already scanned}
        {--limit=0 : Limit number of files (0 = all)}
        {--file-id= : Scan a specific file ID}
        {--infected-only : Only show infected files in output}';

    protected $description = 'Scan uploaded files with ClamAV and log results (never deletes files)';

    private string $clamPath;
    private int $scanned = 0;
    private int $clean = 0;
    private int $infected = 0;
    private int $errors = 0;
    private array $infectedFiles = [];

    public function handle(): int
    {
        $this->info('🛡️  Wolffiles Virus Scanner');
        $this->info('==========================');
        $this->warn('Mode: SCAN ONLY — no files will be deleted.');
        $this->newLine();

        // Find ClamAV
        $this->clamPath = $this->findClam();
        if (!$this->clamPath) {
            $this->error('ClamAV not found! Install with: sudo dnf install clamav');
            return 1;
        }
        $this->info("Using: {$this->clamPath}");

        // Check virus definitions
        $versionOutput = shell_exec($this->clamPath . ' --version 2>&1');
        $this->info("Version: " . trim($versionOutput ?? 'unknown'));
        $this->newLine();

        // Build query
        $query = File::query();

        if ($fileId = $this->option('file-id')) {
            $query->where('id', $fileId);
        } elseif (!$this->option('force')) {
            $query->where(function ($q) {
                $q->where('virus_scanned', false)
                  ->orWhereNull('virus_scanned');
            });
        }

        if ($limit = (int) $this->option('limit')) {
            $query->limit($limit);
        }

        $query->orderBy('id');
        $files = $query->get();

        if ($files->isEmpty()) {
            $this->info('No files to scan. Use --force to re-scan all files.');
            return 0;
        }

        $this->info("Found {$files->count()} file(s) to scan.");
        $this->newLine();

        $bar = $this->output->createProgressBar($files->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('Starting...');
        $bar->start();

        foreach ($files as $file) {
            $bar->setMessage(Str($file->title)->limit(40));
            try {
                $this->scanFile($file);
            } catch (\Exception $e) {
                $this->errors++;
                \Log::error("VirusScan: Error scanning [{$file->id}]: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->setMessage('Done!');
        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('╔══════════════════════════════╗');
        $this->info('║       SCAN RESULTS           ║');
        $this->info('╠══════════════════════════════╣');
        $this->info("║  Scanned:  {$this->scanned}");
        $this->info("║  Clean:    {$this->clean}");
        if ($this->infected > 0) {
            $this->error("║  INFECTED: {$this->infected}");
        } else {
            $this->info("║  Infected: {$this->infected}");
        }
        $this->info("║  Errors:   {$this->errors}");
        $this->info('╚══════════════════════════════╝');

        // List infected files
        if (!empty($this->infectedFiles)) {
            $this->newLine();
            $this->error('⚠️  INFECTED FILES:');
            $this->table(
                ['ID', 'Title', 'File', 'Threat'],
                $this->infectedFiles
            );
        }

        // Write log file
        $this->writeLog();

        return $this->infected > 0 ? 2 : 0;
    }

    private function scanFile(File $file): void
    {
        $disk = Storage::disk('s3');

        if (!$file->file_path || !$disk->exists($file->file_path)) {
            $this->errors++;
            $this->updateFile($file, true, false, 'File not found on S3');
            return;
        }

        // Download to temp
        $tempDir = storage_path("app/temp/scan-{$file->id}");
        @mkdir($tempDir, 0755, true);
        $tempFile = $tempDir . '/' . ($file->file_name ?? 'file');

        try {
            file_put_contents($tempFile, $disk->get($file->file_path));

            if ($this->option('dry-run')) {
                $this->scanned++;
                $this->clean++;
                return;
            }

            // Run ClamAV scan (--no-summary for clean output, --infected to only show threats)
            // Use different flags for clamdscan vs clamscan
            $isClamdscan = str_contains($this->clamPath, 'clamdscan');
            if ($isClamdscan) {
                $cmd = sprintf('%s --no-summary --infected %s 2>&1',
                    escapeshellarg($this->clamPath),
                    escapeshellarg($tempFile)
                );
            } else {
                $cmd = sprintf('%s --no-summary --infected --max-filesize=500M --max-scansize=500M %s 2>&1',
                    escapeshellarg($this->clamPath),
                    escapeshellarg($tempFile)
                );
            }

            $output = '';
            $exitCode = 0;
            exec($cmd, $outputLines, $exitCode);
            $output = implode("\n", $outputLines);

            $this->scanned++;

            // Exit codes: 0 = clean, 1 = infected, 2 = error
            if ($exitCode === 0) {
                $this->clean++;
                $this->updateFile($file, true, true, 'Clean');
            } elseif ($exitCode === 1) {
                $this->infected++;

                // Extract threat name
                $threat = 'Unknown threat';
                if (preg_match('/:\s*(.+)\s+FOUND/', $output, $m)) {
                    $threat = trim($m[1]);
                }

                $this->infectedFiles[] = [
                    $file->id,
                    \Illuminate\Support\Str::limit($file->title, 30),
                    $file->file_name,
                    $threat,
                ];

                $this->updateFile($file, true, false, "INFECTED: {$threat}");

                \Log::warning("VirusScan: INFECTED [{$file->id}] {$file->title}: {$threat}");
            } else {
                $this->errors++;
                $scanError = \Illuminate\Support\Str::limit($output, 200);
                $this->updateFile($file, true, null, "Scan error: {$scanError}");

                \Log::error("VirusScan: Error [{$file->id}]: {$output}");
            }
        } finally {
            @unlink($tempFile);
            @rmdir($tempDir);
        }
    }

    private function updateFile(File $file, bool $scanned, ?bool $clean, string $result): void
    {
        if ($this->option('dry-run')) return;

        $file->update([
            'virus_scanned' => $scanned,
            'virus_clean' => $clean,
            'virus_scan_result' => $result,
        ]);
    }

    private function findClam(): ?string
    {
        // Prefer clamscan (clamdscan needs running daemon)
        foreach (['clamscan', 'clamdscan'] as $binary) {
            $path = trim(shell_exec("which {$binary} 2>/dev/null") ?? '');
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        // Check common paths
        foreach (['/usr/bin/clamscan', '/usr/local/bin/clamscan', '/usr/bin/clamdscan'] as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function writeLog(): void
    {
        $logDir = storage_path('logs');
        $logFile = $logDir . '/virus-scan-' . now()->format('Y-m-d_H-i-s') . '.log';

        $log = "Wolffiles Virus Scan Report\n";
        $log .= "Date: " . now()->format('Y-m-d H:i:s') . "\n";
        $log .= "Scanner: {$this->clamPath}\n";
        $log .= str_repeat('=', 50) . "\n\n";
        $log .= "Total Scanned: {$this->scanned}\n";
        $log .= "Clean: {$this->clean}\n";
        $log .= "Infected: {$this->infected}\n";
        $log .= "Errors: {$this->errors}\n\n";

        if (!empty($this->infectedFiles)) {
            $log .= "INFECTED FILES:\n";
            $log .= str_repeat('-', 50) . "\n";
            foreach ($this->infectedFiles as $inf) {
                $log .= "  ID: {$inf[0]} | {$inf[1]} | {$inf[2]} | {$inf[3]}\n";
            }
        } else {
            $log .= "No infected files found.\n";
        }

        file_put_contents($logFile, $log);

        $this->info("Log saved: {$logFile}");
    }
}