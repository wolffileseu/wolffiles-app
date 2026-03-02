<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BspExtractorService
{
    /**
     * Extract BSP from a file's archive and upload to S3
     * Returns the S3 path to the BSP or null if not found
     */
    public function extract(File $file, ?string $localArchivePath = null): ?string
    {
        $disk = Storage::disk('s3');
        $ext = strtolower($file->file_extension ?? pathinfo($file->file_name, PATHINFO_EXTENSION));

        // Only process archives that could contain maps
        if (!in_array($ext, ['pk3', 'zip'])) {
            return null;
        }

        // Must have a map_name (detected during analysis)
        if (empty($file->map_name)) {
            return null;
        }

        $tempDir = storage_path("app/temp/bsp-{$file->id}-" . Str::random(6));
        @mkdir($tempDir, 0755, true);

        try {
            // Get the archive file
            if ($localArchivePath && file_exists($localArchivePath)) {
                $archivePath = $localArchivePath;
            } else {
                if (!$file->file_path || !$disk->exists($file->file_path)) {
                    return null;
                }
                $archivePath = $tempDir . '/archive.' . $ext;
                file_put_contents($archivePath, $disk->get($file->file_path));
            }

            $pk3Path = null;

            if ($ext === 'pk3') {
                $pk3Path = $archivePath;
            } elseif ($ext === 'zip') {
                $pk3Path = $this->findPk3InZip($archivePath, $tempDir);
            }

            if (!$pk3Path) return null;

            // Extract BSP
            $bspLocalPath = $this->extractBsp($pk3Path, $tempDir, $file->map_name);
            if (!$bspLocalPath) return null;

            // Upload BSP to S3
            $mapSlug = Str::slug($file->map_name, '_');
            if (empty($mapSlug)) {
                $mapSlug = 'map_' . $file->id;
            }
            $s3BspPath = "bsp/{$file->id}/{$mapSlug}.bsp";

            $disk->put($s3BspPath, file_get_contents($bspLocalPath), 'public');

            // Update file record
            $file->update(['bsp_path' => $s3BspPath]);

            // Also extract and upload custom textures
            $this->extractTextures($pk3Path, $file, $tempDir);

            \Log::info("BSP extracted [{$file->id}]: {$s3BspPath} (" . round(filesize($bspLocalPath) / 1024) . " KB)");

            return $s3BspPath;

        } catch (\Exception $e) {
            \Log::warning("BSP extraction failed [{$file->id}]: {$e->getMessage()}");
            return null;
        } finally {
            // Only clean up if we created the temp dir
            if ($localArchivePath !== ($tempDir . '/archive.' . $ext)) {
                $this->cleanDir($tempDir);
            } else {
                $this->cleanDir($tempDir);
            }
        }
    }

    /**
     * Find and extract PK3 from a ZIP file
     */
    private function findPk3InZip(string $zipPath, string $tempDir): ?string
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) return null;

        $pk3Names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_ends_with(strtolower($name), '.pk3')) {
                $pk3Names[] = $name;
            }
        }
        $zip->close();

        if (empty($pk3Names)) return null;

        $extractDir = $tempDir . '/zip_pk3';
        @mkdir($extractDir, 0755, true);

        // Try extracting with shell (handles special chars better)
        foreach ($pk3Names as $pk3Name) {
            $cmd = sprintf('unzip -o %s %s -d %s 2>&1',
                escapeshellarg($zipPath),
                escapeshellarg($pk3Name),
                escapeshellarg($extractDir)
            );
            shell_exec($cmd);
        }

        // Find extracted PK3
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (str_ends_with(strtolower($file->getFilename()), '.pk3')) {
                return $file->getRealPath();
            }
        }

        return null;
    }

    /**
     * Extract BSP file from PK3
     */
    private function extractBsp(string $pk3Path, string $tempDir, ?string $mapName): ?string
    {
        $extractDir = $tempDir . '/bsp_extract';
        @mkdir($extractDir, 0755, true);

        // Extract maps/*.bsp
        $cmd = sprintf('unzip -o %s "maps/*.bsp" "maps/*.BSP" -d %s 2>/dev/null',
            escapeshellarg($pk3Path),
            escapeshellarg($extractDir)
        );
        shell_exec($cmd);

        // Find BSP files
        $bspFiles = [];
        if (is_dir($extractDir . '/maps')) {
            foreach (scandir($extractDir . '/maps') as $f) {
                if (str_ends_with(strtolower($f), '.bsp')) {
                    $bspFiles[] = $extractDir . '/maps/' . $f;
                }
            }
        }

        // Fallback: extract everything and search
        if (empty($bspFiles)) {
            $cmd2 = sprintf('unzip -o %s -d %s 2>/dev/null', escapeshellarg($pk3Path), escapeshellarg($extractDir));
            shell_exec($cmd2);

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($extractDir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if (str_ends_with(strtolower($file->getFilename()), '.bsp')) {
                    $bspFiles[] = $file->getRealPath();
                }
            }
        }

        if (empty($bspFiles)) return null;

        // Match by map_name
        if ($mapName) {
            $cleanName = strtolower(preg_replace('/[^a-z0-9_-]/i', '', $mapName));
            foreach ($bspFiles as $bsp) {
                $bspName = strtolower(pathinfo($bsp, PATHINFO_FILENAME));
                $cleanBsp = preg_replace('/[^a-z0-9_-]/', '', $bspName);
                if ($cleanBsp === $cleanName) {
                    return $bsp;
                }
            }
        }

        return $bspFiles[0];
    }

    /**
     * Extract custom textures from PK3 and upload to S3
     */
    private function extractTextures(string $pk3Path, File $file, string $tempDir): void
    {
        $disk = Storage::disk('s3');
        $texDir = $tempDir . '/tex_extract';
        @mkdir($texDir, 0755, true);

        $cmd = sprintf('unzip -o %s "textures/*" "models/*" "scripts/*.shader" -d %s 2>/dev/null',
            escapeshellarg($pk3Path),
            escapeshellarg($texDir)
        );
        shell_exec($cmd);

        $hasConvert = !empty(trim(shell_exec('which convert 2>/dev/null') ?? ''));
        $basePath = "bsp/{$file->id}/assets";

        if (!is_dir($texDir)) return;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($texDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $texFile) {
            if ($texFile->isDir()) continue;
            if ($texFile->getSize() === 0) continue;

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
                    $disk->put("{$basePath}/{$relPath}", file_get_contents($jpgPath), 'public');
                    continue;
                }
            }

            if (in_array($ext, ['jpg', 'jpeg', 'png', 'shader'])) {
                $disk->put("{$basePath}/{$relPath}", file_get_contents($texFile->getRealPath()), 'public');
            }
        }
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
