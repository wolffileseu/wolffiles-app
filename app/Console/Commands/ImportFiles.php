<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Models\Category;
use App\Services\FileAnalyzerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportFiles extends Command
{
    protected $signature = 'wolffiles:import
        {path : S3 path to scan, e.g. "import/" or "ET/Maps/"}
        {--category= : Category ID to assign files to}
        {--game= : Game name (ET, RtCW, etc.)}
        {--user=1 : User ID for the uploader}
        {--auto-approve : Automatically approve imported files}
        {--analyze : Run file analysis (extract images, map name, etc.)}
        {--dry-run : Show what would be imported without doing it}';

    protected $description = 'Bulk import files from S3 storage into the Wolffiles database';

    public function handle(FileAnalyzerService $analyzer): int
    {
        $path = rtrim($this->argument('path'), '/');
        $categoryId = $this->option('category');
        $game = $this->option('game');
        $userId = $this->option('user');
        $autoApprove = $this->option('auto-approve');
        $analyze = $this->option('analyze');
        $dryRun = $this->option('dry-run');

        // Validate category
        if ($categoryId) {
            $category = Category::find($categoryId);
            if (!$category) {
                $this->error("Category ID {$categoryId} not found.");
                $this->info("\nAvailable categories:");
                Category::whereNotNull('parent_id')->orderBy('parent_id')->get()->each(function ($cat) {
                    $parent = $cat->parent ? $cat->parent->name . ' → ' : '';
                    $this->line("  [{$cat->id}] {$parent}{$cat->name}");
                });
                return 1;
            }
        }

        // List files in S3
        $this->info("Scanning S3 path: {$path}/");
        $files = Storage::disk('s3')->files($path);

        if (empty($files)) {
            // Try listing directories
            $dirs = Storage::disk('s3')->directories($path);
            if (!empty($dirs)) {
                $this->info("Found directories instead of files:");
                foreach ($dirs as $dir) {
                    $this->line("  {$dir}/");
                }
                $this->info("\nUse a more specific path, e.g.: wolffiles:import \"{$dirs[0]}\"");
            } else {
                $this->warn("No files found at: {$path}/");
            }
            return 0;
        }

        // Filter to supported extensions
        $supported = ['pk3', 'zip', 'rar', '7z', 'lua', 'cfg', 'txt', 'pk3.zip'];
        $files = collect($files)->filter(function ($file) use ($supported) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return in_array($ext, $supported);
        });

        $this->info("Found {$files->count()} files to import.");

        if ($files->isEmpty()) {
            return 0;
        }

        $bar = $this->output->createProgressBar($files->count());
        $imported = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($files as $filePath) {
            $fileName = basename($filePath);
            $bar->advance();

            // Check if already imported
            if (File::where('file_path', $filePath)->exists()) {
                $skipped++;
                continue;
            }

            // Also check by filename
            if (File::where('file_name', $fileName)->exists()) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->newLine();
                $this->line("  [DRY] Would import: {$fileName}");
                $imported++;
                continue;
            }

            try {
                $title = pathinfo($fileName, PATHINFO_FILENAME);
                $title = str_replace(['_', '-'], ' ', $title);
                $title = Str::title($title);

                $fileData = [
                    'user_id' => $userId,
                    'title' => $title,
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                    'file_extension' => strtolower(pathinfo($fileName, PATHINFO_EXTENSION)),
                    'status' => $autoApprove ? 'approved' : 'pending',
                    'published_at' => $autoApprove ? now() : null,
                ];

                if ($categoryId) $fileData['category_id'] = $categoryId;
                if ($game) $fileData['game'] = $game;

                // Get file size from S3
                try {
                    $fileData['file_size'] = Storage::disk('s3')->size($filePath);
                    $fileData['mime_type'] = Storage::disk('s3')->mimeType($filePath);
                } catch (\Exception $e) {
                    $fileData['file_size'] = 0;
                    $fileData['mime_type'] = 'application/octet-stream';
                }

                $file = File::create($fileData);

                // Run analysis if requested
                if ($analyze) {
                    try {
                        $tempPath = tempnam(sys_get_temp_dir(), 'wf_');
                        file_put_contents($tempPath, Storage::disk('s3')->get($filePath));

                        $analysis = $analyzer->analyze($tempPath, $fileName);

                        $updateData = [];
                        if (!empty($analysis['map_name'])) $updateData['map_name'] = $analysis['map_name'];
                        if (!empty($analysis['game'])) $updateData['game'] = $analysis['game'];
                        if (!empty($analysis['readme_content'])) $updateData['readme_content'] = $analysis['readme_content'];
                        if (!empty($analysis['extracted_metadata'])) $updateData['extracted_metadata'] = $analysis['extracted_metadata'];
                        if (!empty($analysis['file_hash'])) $updateData['file_hash'] = $analysis['file_hash'];

                        if (!empty($updateData)) {
                            $file->update($updateData);
                        }

                        // Store extracted images
                        if (!empty($analysis['extracted_images'])) {
                            foreach ($analysis['extracted_images'] as $img) {
                                $imgName = $img['name'] ?? 'screenshot.jpg';
                                $imgPath = "screenshots/{$file->id}/" . Str::random(10) . '_' . $imgName;
                                Storage::disk('s3')->put($imgPath, file_get_contents($img['path']));
                                $file->screenshots()->create([
                                    'path' => $imgPath,
                                    'source' => $img['source'] ?? 'extracted',
                                    'is_primary' => $file->screenshots()->count() === 0,
                                ]);
                            }
                        }

                        @unlink($tempPath);
                    } catch (\Exception $e) {
                        $this->newLine();
                        $this->warn("  Analysis failed for {$fileName}: {$e->getMessage()}");
                    }
                }

                $imported++;

                // Update category count
                if ($autoApprove && $file->category) {
                    $file->category->increment('files_count');
                }

            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("  Failed: {$fileName} - {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Import complete!");
        $this->table(
            ['Imported', 'Skipped (duplicate)', 'Errors'],
            [[$imported, $skipped, $errors]]
        );

        if ($dryRun) {
            $this->warn("This was a dry run. No files were actually imported.");
        }

        return 0;
    }
}
