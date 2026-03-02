<?php

namespace App\Console\Commands;

use App\Models\FastDl\FastDlDirectory;
use App\Models\FastDl\FastDlFile;
use App\Models\FastDl\FastDlGame;
use App\Models\File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class FastDlExtractPk3s extends Command
{
    protected $signature = 'fastdl:extract-pk3s 
        {--game=et : Game slug to extract for}
        {--batch=50 : Number of files per batch}
        {--offset=0 : Skip this many files}
        {--dry-run : Show what would be extracted without doing it}
        {--category=Maps : Only extract from this category}';

    protected $description = 'Extract PK3 files from ZIPs and upload to S3 for Fast Download';

    public function handle(): void
    {
        $gameSlug = $this->option('game');
        $batchSize = (int) $this->option('batch');
        $offset = (int) $this->option('offset');
        $dryRun = $this->option('dry-run');

        $game = FastDlGame::where('slug', $gameSlug)->first();
        if (!$game) {
            $this->error("Game '{$gameSlug}' not found!");
            return;
        }

        $baseDir = $game->directories()->where('is_base', true)->first();
        if (!$baseDir) {
            $this->error("No base directory for {$game->name}!");
            return;
        }

        // Map game slug to Wolffiles game string
        $gameMap = ['et' => 'ET', 'rtcw' => 'RtCW'];
        $gameString = $gameMap[$gameSlug] ?? $gameSlug;

        // Category filter
        $categoryName = $this->option('category');
        $categoryId = null;
        if ($categoryName) {
            $categoryId = \DB::table('categories')->where('name', $categoryName)->value('id');
            if (!$categoryId) {
                $this->error("Category '$categoryName' not found!");
                return;
            }
            $this->info("Category filter: {$categoryName} (ID: {$categoryId})");
        }

        // Get ZIP files that haven't been extracted yet
        $alreadySynced = FastDlFile::where('directory_id', $baseDir->id)->pluck('wolffiles_file_id')->toArray();

        $files = File::where('status', 'approved')
            ->where('game', $gameString)
            ->where('file_extension', 'zip')
            ->whereNotIn('id', $alreadySynced)
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->orderBy('id')
            ->offset($offset)
            ->limit($batchSize)
            ->get();

        $remaining = File::where('status', 'approved')
            ->where('game', $gameString)
            ->where('file_extension', 'zip')
            ->whereNotIn('id', $alreadySynced)
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->count();

        $this->info("Game: {$game->name}");
        $this->info("Remaining: {$remaining} ZIPs to process");
        $this->info("Batch: {$files->count()} files (offset: {$offset})");
        $this->newLine();

        if ($dryRun) {
            foreach ($files as $file) {
                $this->line("Would extract: {$file->file_name} ({$file->file_path})");
            }
            return;
        }

        $extracted = 0;
        $errors = 0;
        $skipped = 0;
        $bar = $this->output->createProgressBar($files->count());

        foreach ($files as $file) {
            $bar->advance();

            try {
                $result = $this->extractPk3FromZip($file, $baseDir);
                if ($result > 0) {
                    $extracted += $result;
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->warn("Error [{$file->file_name}]: " . $e->getMessage());
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done! Extracted: {$extracted} PK3s, Skipped: {$skipped}, Errors: {$errors}");
        $this->info("Remaining after this batch: " . ($remaining - $files->count()));

        $total = FastDlFile::where('directory_id', $baseDir->id)->count();
        $this->info("Total FastDL files: {$total}");
    }

    private function extractPk3FromZip(File $file, FastDlDirectory $baseDir): int
    {
        $tmpDir = '/tmp/fastdl_extract';
        if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);

        $tmpZip = $tmpDir . '/' . uniqid() . '.zip';
        $count = 0;

        try {
            // Download ZIP from S3
            $stream = Storage::disk('s3')->readStream($file->file_path);
            if (!$stream) {
                throw new \Exception("Cannot read from S3: {$file->file_path}");
            }

            file_put_contents($tmpZip, $stream);
            if (is_resource($stream)) fclose($stream);

            // Open ZIP
            $zip = new ZipArchive();
            $result = $zip->open($tmpZip);
            if ($result !== true) {
                throw new \Exception("Cannot open ZIP (error: {$result})");
            }

            // Find PK3 files
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (!str_ends_with(strtolower($name), '.pk3')) continue;

                // Get just the filename (no subdirectories)
                $pk3Name = basename($name);
                if (empty($pk3Name)) continue;

                // Skip if already exists
                $exists = FastDlFile::where('directory_id', $baseDir->id)
                    ->where('filename', $pk3Name)
                    ->exists();
                if ($exists) continue;

                // Extract PK3
                $tmpPk3 = $tmpDir . '/' . $pk3Name;
                
                // Extract entire ZIP to tmpDir
                $zip->extractTo($tmpDir);
                
                // Find the PK3 file (could be in subfolder)
                $found = null;
                $search = array_merge(
                    glob($tmpDir . '/*' . $pk3Name),
                    glob($tmpDir . '/*/*' . $pk3Name),
                    glob($tmpDir . '/*/*/*' . $pk3Name)
                );
                foreach ($search as $f) {
                    if (basename($f) === $pk3Name && is_file($f)) {
                        $found = $f;
                        break;
                    }
                }
                
                // Also check exact path
                if (!$found && file_exists($tmpPk3)) {
                    $found = $tmpPk3;
                }
                
                if (!$found) continue;
                
                // Move to tmpDir root if needed
                if ($found !== $tmpPk3) {
                    rename($found, $tmpPk3);
                }

                $pk3Size = filesize($tmpPk3);
                $s3Path = "fastdl/{$baseDir->game->slug}/{$baseDir->slug}/{$pk3Name}";

                // Upload to S3
                Storage::disk('s3')->put($s3Path, file_get_contents($tmpPk3));

                // Create DB record (skip if duplicate filename)
                FastDlFile::firstOrCreate(
                    ['directory_id' => $baseDir->id, 'filename' => $pk3Name],
                    [
                        's3_path' => $s3Path,
                        'file_size' => $pk3Size,
                        'checksum' => file_exists($tmpPk3) ? md5_file($tmpPk3) : null,
                        'source' => 'auto_sync',
                        'wolffiles_file_id' => $file->id,
                        'is_active' => true,
                    ]
                );

                $count++;

                // Cleanup PK3
                @unlink($tmpPk3);
            }

            $zip->close();
        } finally {
            // Cleanup
            @unlink($tmpZip);
            // Clean subdirectories
            $this->cleanDir($tmpDir);
        }

        return $count;
    }

    private function cleanDir(string $dir): void
    {
        $items = glob($dir . '/*');
        foreach ($items as $item) {
            if (is_dir($item)) {
                $this->cleanDir($item);
                @rmdir($item);
            }
        }
    }
}
