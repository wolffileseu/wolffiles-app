<?php

namespace App\Console\Commands;

use App\Models\File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExtractBspFiles extends Command
{
    protected $signature = 'wolffiles:extract-bsp
        {--dry-run : Show what would be done without extracting}
        {--force : Re-extract BSPs even if already extracted}
        {--limit=0 : Limit number of files to process (0 = all)}
        {--file-id= : Process a specific file ID}
        {--with-textures : Also extract custom textures from the PK3}';

    protected $description = 'Extract BSP files from PK3/ZIP archives for 3D map preview';

    private int $extracted = 0;
    private int $skipped = 0;
    private int $errors = 0;
    private int $texturesExtracted = 0;

    public function handle(): int
    {
        $this->info('🗺️  BSP File Extractor');
        $this->info('=====================');
        $this->newLine();

        // Check required tools
        $unzip = trim(shell_exec('which unzip 2>/dev/null') ?? '');
        if (!$unzip) {
            $this->error('unzip not found! Please install it.');
            return 1;
        }

        $convert = trim(shell_exec('which convert 2>/dev/null') ?? '');
        $hasConvert = !empty($convert);
        if (!$hasConvert) {
            $this->warn('ImageMagick convert not found — TGA textures will be skipped.');
        }

        // Build query
        $query = File::whereNotNull('map_name')
            ->where('status', 'approved');

        if ($fileId = $this->option('file-id')) {
            $query = File::where('id', $fileId);
        } elseif (!$this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('bsp_path')
                  ->orWhere('bsp_path', '');
            });
        }

        if ($limit = (int) $this->option('limit')) {
            $query->limit($limit);
        }

        $files = $query->orderBy('id')->get();

        if ($files->isEmpty()) {
            $this->info('No files to process. Use --force to re-extract all.');
            return 0;
        }

        $this->info("Found {$files->count()} file(s) with maps to process.");
        $this->newLine();

        $bar = $this->output->createProgressBar($files->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('Starting...');
        $bar->start();

        foreach ($files as $file) {
            $bar->setMessage(Str::limit($file->title, 35));
            try {
                $this->processFile($file, $hasConvert);
            } catch (\Exception $e) {
                $this->errors++;
                \Log::error("BSP Extract [{$file->id}]: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->setMessage('Done!');
        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Results:");
        $this->info("   Extracted: {$this->extracted}");
        $this->info("   Skipped:   {$this->skipped}");
        $this->info("   Errors:    {$this->errors}");
        if ($this->option('with-textures')) {
            $this->info("   Textures:  {$this->texturesExtracted}");
        }

        return 0;
    }

    private function processFile(File $file, bool $hasConvert): void
    {
        $disk = Storage::disk('s3');
        $ext = strtolower($file->file_extension ?? pathinfo($file->file_name, PATHINFO_EXTENSION));

        if (!$file->file_path || !$disk->exists($file->file_path)) {
            $this->skipped++;
            return;
        }

        $tempDir = storage_path("app/temp/bsp-extract-{$file->id}");
        @mkdir($tempDir, 0755, true);

        try {
            // Download from S3
            $archivePath = $tempDir . '/archive.' . $ext;
            file_put_contents($archivePath, $disk->get($file->file_path));

            $bspPath = null;
            $pk3Path = null;
            $customTextures = [];

            if ($ext === 'pk3') {
                // Direct PK3 file
                $pk3Path = $archivePath;
            } elseif ($ext === 'zip') {
                // ZIP containing PK3(s)
                $pk3Path = $this->extractPk3FromZip($archivePath, $tempDir);
            }

            if (!$pk3Path) {
                $this->skipped++;
                return;
            }

            // Extract BSP from PK3
            $bspPath = $this->extractBspFromPk3($pk3Path, $tempDir, $file->map_name);

            if (!$bspPath) {
                $this->skipped++;
                return;
            }

            $bspSize = filesize($bspPath);
            $mapSlug = Str::slug($file->map_name, '_');

            if ($this->option('dry-run')) {
                $this->info("\n  DRY-RUN [{$file->id}]: Would extract {$file->map_name}.bsp (" . round($bspSize / 1024) . " KB)");
                $this->extracted++;
                return;
            }

            // Upload BSP to S3
            $s3BspPath = "bsp/{$file->id}/{$mapSlug}.bsp";
            $disk->put($s3BspPath, file_get_contents($bspPath), 'public');

            $updateData = ['bsp_path' => $s3BspPath];

            // Extract custom textures if requested
            if ($this->option('with-textures')) {
                $textureCount = $this->extractAndUploadTextures(
                    $pk3Path, $file, $tempDir, $hasConvert
                );
                $this->texturesExtracted += $textureCount;
            }

            $file->update($updateData);
            $this->extracted++;

            \Log::info("BSP Extract [{$file->id}]: Uploaded {$s3BspPath} (" . round($bspSize / 1024) . " KB)");

        } finally {
            $this->cleanDir($tempDir);
        }
    }

    /**
     * Extract PK3 from a ZIP archive
     */
    private function extractPk3FromZip(string $zipPath, string $tempDir): ?string
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) return null;

        $pk3Files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_ends_with(strtolower($name), '.pk3')) {
                $pk3Files[] = $name;
            }
        }
        $zip->close();

        if (empty($pk3Files)) return null;

        // Extract first PK3 (or the one matching map_name)
        $extractDir = $tempDir . '/zip_extract';
        @mkdir($extractDir, 0755, true);

        // Use shell unzip to handle special characters in filenames
        foreach ($pk3Files as $pk3Name) {
            $cmd = sprintf(
                'unzip -o %s %s -d %s 2>&1',
                escapeshellarg($zipPath),
                escapeshellarg($pk3Name),
                escapeshellarg($extractDir)
            );
            shell_exec($cmd);

            $extracted = $extractDir . '/' . $pk3Name;
            if (file_exists($extracted)) {
                return $extracted;
            }
        }

        // Fallback: try to find any pk3 in the extract dir
        $found = glob($extractDir . '/*.pk3');
        if (!empty($found)) return $found[0];

        // Deep search
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractDir)
        );
        foreach ($iterator as $file) {
            if (str_ends_with(strtolower($file->getFilename()), '.pk3')) {
                return $file->getRealPath();
            }
        }

        return null;
    }

    /**
     * Extract BSP from PK3
     */
    private function extractBspFromPk3(string $pk3Path, string $tempDir, ?string $mapName): ?string
    {
        $extractDir = $tempDir . '/pk3_extract';
        @mkdir($extractDir, 0755, true);

        // Extract maps/*.bsp
        $cmd = sprintf(
            'unzip -o %s "maps/*.bsp" -d %s 2>&1',
            escapeshellarg($pk3Path),
            escapeshellarg($extractDir)
        );
        shell_exec($cmd);

        // Find BSP files
        $bspFiles = glob($extractDir . '/maps/*.bsp');

        if (empty($bspFiles)) {
            // Try case-insensitive
            $cmd2 = sprintf('unzip -o %s -d %s 2>&1', escapeshellarg($pk3Path), escapeshellarg($extractDir));
            shell_exec($cmd2);
            $bspFiles = [];
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($extractDir)
            );
            foreach ($iterator as $file) {
                if (str_ends_with(strtolower($file->getFilename()), '.bsp')) {
                    $bspFiles[] = $file->getRealPath();
                }
            }
        }

        if (empty($bspFiles)) return null;

        // If we have a map_name, try to find the matching BSP
        if ($mapName) {
            $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $mapName));
            foreach ($bspFiles as $bsp) {
                $bspName = strtolower(pathinfo($bsp, PATHINFO_FILENAME));
                $cleanBsp = preg_replace('/[^a-zA-Z0-9_-]/', '', $bspName);
                if ($cleanBsp === $cleanName || str_contains($bspName, strtolower($mapName))) {
                    return $bsp;
                }
            }
        }

        // Return first BSP
        return $bspFiles[0];
    }

    /**
     * Extract custom textures from PK3 and upload to S3
     */
    private function extractAndUploadTextures(string $pk3Path, File $file, string $tempDir, bool $hasConvert): int
    {
        $disk = Storage::disk('s3');
        $texDir = $tempDir . '/textures_extract';
        @mkdir($texDir, 0755, true);

        // Extract textures and shaders
        $cmd = sprintf(
            'unzip -o %s "textures/*" "models/*" "scripts/*.shader" -d %s 2>/dev/null',
            escapeshellarg($pk3Path),
            escapeshellarg($texDir)
        );
        shell_exec($cmd);

        $count = 0;
        $basePath = "bsp/{$file->id}/assets";

        // Process textures
        $textures = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($texDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $texFile) {
            if ($texFile->isDir()) continue;

            $ext = strtolower($texFile->getExtension());
            $relPath = str_replace($texDir . '/', '', $texFile->getRealPath());

            // Convert TGA to JPG
            if ($ext === 'tga' && $hasConvert) {
                $jpgPath = preg_replace('/\.tga$/i', '.jpg', $texFile->getRealPath());
                exec(sprintf('convert %s -quality 85 %s 2>/dev/null',
                    escapeshellarg($texFile->getRealPath()),
                    escapeshellarg($jpgPath)
                ), $out, $ret);

                if ($ret === 0 && file_exists($jpgPath)) {
                    $relPath = preg_replace('/\.tga$/i', '.jpg', $relPath);
                    $s3Path = "{$basePath}/{$relPath}";
                    $disk->put($s3Path, file_get_contents($jpgPath), 'public');
                    $count++;
                    continue;
                }
            }

            // Upload JPG/PNG/shader files directly
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'shader'])) {
                $s3Path = "{$basePath}/{$relPath}";
                $disk->put($s3Path, file_get_contents($texFile->getRealPath()), 'public');
                $count++;
            }
        }

        return $count;
    }

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
