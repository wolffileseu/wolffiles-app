<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Services\FileAnalyzerService;
use App\Services\FileUploadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ReanalyzeFiles extends Command
{
    protected $signature = 'wolffiles:reanalyze
        {--dry-run : Show what would be done}
        {--force : Re-analyze files that already have metadata}
        {--limit=0 : Limit number of files (0 = all)}
        {--file-id= : Analyze a specific file ID}
        {--no-images : Skip image extraction, only update metadata}';

    protected $description = 'Re-analyze uploaded files to populate extracted_metadata (file list, BSP info, etc.)';

    public function handle(): int
    {
        $this->info('🔍 File Re-Analyzer');
        $this->info('===================');

        $query = File::where('status', 'approved');

        if ($fileId = $this->option('file-id')) {
            $query = File::where('id', $fileId);
        } elseif (!$this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('extracted_metadata')
                  ->orWhere('extracted_metadata', '[]')
                  ->orWhere('extracted_metadata', '{}');
            });
        }

        if ($limit = (int) $this->option('limit')) {
            $query->limit($limit);
        }

        $files = $query->orderBy('id')->get();
        $this->info("Found {$files->count()} file(s) to analyze.");

        if ($files->isEmpty()) {
            $this->info('Nothing to do. Use --force to re-analyze all.');
            return 0;
        }

        $bar = $this->output->createProgressBar($files->count());
        $bar->start();

        $success = 0;
        $errors = 0;

        foreach ($files as $file) {
            try {
                $this->analyzeFile($file);
                $success++;
            } catch (\Exception $e) {
                $errors++;
                $this->warn("\n  Error [{$file->id}]: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done! Success: {$success}, Errors: {$errors}");

        return 0;
    }

    private function analyzeFile(File $file): void
    {
        $disk = Storage::disk('s3');

        if (!$file->file_path || !$disk->exists($file->file_path)) {
            return;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'wf_reanalyze_');
        file_put_contents($tempPath, $disk->get($file->file_path));

        $analyzer = new FileAnalyzerService();

        try {
            $result = $analyzer->analyze($tempPath, $file->file_name);

            $updateData = ['extracted_metadata' => $result['extracted_metadata'] ?? []];

            if (!empty($result['map_name']) && !$file->map_name) {
                $updateData['map_name'] = $result['map_name'];
            }
            if (!empty($result['game']) && !$file->game) {
                $updateData['game'] = $result['game'];
            }
            if (!empty($result['readme_content']) && !$file->readme_content) {
                $updateData['readme_content'] = $result['readme_content'];
            }

            $file->update($updateData);

            // Optionally extract images
            if (!$this->option('no-images') && !empty($result['extracted_images']) && $file->screenshots()->count() === 0) {
                $uploadService = app(FileUploadService::class);
                foreach ($result['extracted_images'] as $image) {
                    if (file_exists($image['path'])) {
                        try {
                            $uploadService->storeExtractedImage(
                                $file,
                                $image['path'],
                                $image['original_name'] ?? 'screenshot.png',
                                $image['source'] ?? 'extracted'
                            );
                        } catch (\Exception $e) {
                            // Skip
                        }
                    }
                }
            }
        } finally {
            $analyzer->cleanup();
            @unlink($tempPath);
        }
    }
}
