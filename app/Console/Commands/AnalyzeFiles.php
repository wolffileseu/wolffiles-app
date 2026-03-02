<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Services\FileAnalyzerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AnalyzeFiles extends Command
{
    protected $signature = 'wolffiles:analyze
        {--all : Re-analyze ALL files, even those already analyzed}
        {--missing-screenshots : Only analyze files without screenshots}
        {--game= : Only analyze files from a specific game}
        {--category= : Only analyze files from a specific category ID}
        {--limit=0 : Limit number of files to analyze (0 = no limit)}
        {--max-size=500 : Max file size in MB to analyze}
        {--force : Force re-analyze even if already has screenshots}';

    protected $description = 'Analyze files for screenshots, map names, README content etc.';

    public function handle(FileAnalyzerService $analyzer): int
    {
        $all = $this->option('all');
        $missingScreenshots = $this->option('missing-screenshots');
        $game = $this->option('game');
        $categoryId = $this->option('category');
        $limit = (int) $this->option('limit');
        $maxSize = (int) $this->option('max-size') * 1024 * 1024;
        $force = $this->option('force');

        $this->info('===========================================');
        $this->info('  Wolffiles.eu — File Analyzer');
        $this->info('===========================================');
        $this->newLine();

        // Build query
        $query = File::query();

        if (!$all && !$force) {
            // Only files without screenshots
            $query->whereDoesntHave('screenshots');
        }

        if ($missingScreenshots) {
            $query->whereDoesntHave('screenshots');
        }

        if ($game) {
            $query->where('game', $game);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        // Only analyzable extensions
        $query->whereIn('file_extension', ['pk3', 'zip', 'rar', '7z']);

        // Max file size
        if ($maxSize > 0) {
            $query->where(function ($q) use ($maxSize) {
                $q->where('file_size', '<=', $maxSize)
                  ->orWhereNull('file_size')
                  ->orWhere('file_size', 0);
            });
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $files = $query->get();

        $this->info("Found {$files->count()} files to analyze.");

        if ($files->isEmpty()) {
            $this->info('Nothing to do!');
            return 0;
        }

        $bar = $this->output->createProgressBar($files->count());
        $analyzed = 0;
        $screenshotsFound = 0;
        $errors = 0;

        foreach ($files as $file) {
            $bar->advance();

            try {
                $filePath = $file->file_path;

                if (!Storage::disk('s3')->exists($filePath)) {
                    $errors++;
                    continue;
                }

                // Get actual file size if missing
                if (!$file->file_size || $file->file_size === 0) {
                    try {
                        $file->update(['file_size' => Storage::disk('s3')->size($filePath)]);
                    } catch (\Exception $e) {}
                }

                // Skip if too large
                if ($file->file_size > $maxSize && $maxSize > 0) {
                    continue;
                }

                // Download to temp
                $tempPath = tempnam(sys_get_temp_dir(), 'wf_analyze_');
                file_put_contents($tempPath, Storage::disk('s3')->get($filePath));

                // Analyze
                $analysis = $analyzer->analyze($tempPath, $file->file_name);

                // Update file data
                $updateData = [];
                if (!empty($analysis['map_name']) && !$file->map_name) {
                    $updateData['map_name'] = $analysis['map_name'];
                }
                if (!empty($analysis['readme_content']) && !$file->readme_content) {
                    $updateData['readme_content'] = $analysis['readme_content'];
                }
                if (!empty($analysis['extracted_metadata'])) {
    $updateData['extracted_metadata'] = json_decode(
        json_encode($analysis['extracted_metadata'], JSON_INVALID_UTF8_SUBSTITUTE), true
    );
}                if (!empty($analysis['file_hash']) && !$file->file_hash) {
                    $updateData['file_hash'] = $analysis['file_hash'];
                }
                // Always update hash if missing
                if (!$file->file_hash) {
                    $updateData['file_hash'] = hash_file('sha256', $tempPath);
                }

                if (!empty($updateData)) {
                    $file->update($updateData);
                }

                // Store extracted images
                if (!empty($analysis['extracted_images'])) {
                    // Delete old screenshots if force re-analyze
                    if ($force && $file->screenshots()->count() > 0) {
                        foreach ($file->screenshots as $oldScreenshot) {
                            try {
                                Storage::disk('s3')->delete($oldScreenshot->path);
                            } catch (\Exception $e) {}
                        }
                        $file->screenshots()->delete();
                    }

                    $isFirst = $file->screenshots()->count() === 0;

                    foreach ($analysis['extracted_images'] as $img) {
                        $imgName = $img['name'] ?? 'screenshot.jpg';
                        $imgPath = "screenshots/{$file->id}/" . Str::random(10) . '_' . $imgName;

                        try {
                            Storage::disk('s3')->put($imgPath, file_get_contents($img['path']));
                            $file->screenshots()->create([
                                'path' => $imgPath,
                                'source' => $img['source'] ?? 'extracted',
                                'is_primary' => $isFirst,
                            ]);
                            $isFirst = false;
                            $screenshotsFound++;
                        } catch (\Exception $e) {
                            // Skip this image
                        }
                    }
                }

                @unlink($tempPath);
                $analyzed++;

            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->warn("  ✗ {$file->file_name}: " . Str::limit($e->getMessage(), 100));
            }

            // Clean up temp files periodically
            if ($analyzed % 50 === 0) {
                gc_collect_cycles();
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('===========================================');
        $this->info('  Analysis Complete!');
        $this->info('===========================================');
        $this->table(
            ['Analyzed', 'Screenshots Found', 'Errors'],
            [[$analyzed, $screenshotsFound, $errors]]
        );

        return 0;
    }
}