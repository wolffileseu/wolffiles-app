<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileValidationService
{
    /**
     * #16 - Check for duplicate files by SHA256 hash.
     * Returns the existing file if a duplicate is found.
     */
    public static function findDuplicate(string $hash): ?File
    {
        return File::where('file_hash', $hash)
            ->where('status', '!=', 'rejected')
            ->first();
    }

    /**
     * Calculate SHA256 hash of an uploaded file.
     */
    public static function calculateHash($filePath): string
    {
        if (Storage::disk('s3')->exists($filePath)) {
            // For S3 files, download temp and hash
            $tempPath = tempnam(sys_get_temp_dir(), 'wf_');
            file_put_contents($tempPath, Storage::disk('s3')->get($filePath));
            $hash = hash_file('sha256', $tempPath);
            unlink($tempPath);
            return $hash;
        }

        return hash_file('sha256', $filePath);
    }

    /**
     * #25 - Scan file with ClamAV.
     * Returns ['clean' => bool, 'result' => string]
     */
    public static function virusScan(string $localPath): array
    {
        // Check if clamdscan is available
        $clamPath = trim(shell_exec('which clamdscan 2>/dev/null') ?? '');

        if (empty($clamPath)) {
            return [
                'clean' => true,
                'result' => 'ClamAV not installed - skipped',
            ];
        }

        $escapedPath = escapeshellarg($localPath);
        $output = shell_exec("clamdscan --no-summary {$escapedPath} 2>&1");
        $exitCode = -1;
        exec("clamdscan --no-summary {$escapedPath} 2>&1", $outputLines, $exitCode);

        $result = implode("\n", $outputLines);

        if ($exitCode === 0) {
            return ['clean' => true, 'result' => 'OK'];
        } elseif ($exitCode === 1) {
            Log::warning("Virus detected in file: {$localPath}", ['output' => $result]);
            return ['clean' => false, 'result' => $result];
        } else {
            Log::error("ClamAV scan error for: {$localPath}", ['output' => $result]);
            return ['clean' => true, 'result' => 'Scan error - ' . $result];
        }
    }

    /**
     * #26 - Validate PK3 file structure.
     * Returns ['valid' => bool, 'errors' => array, 'info' => array]
     */
    public static function validatePk3(string $localPath): array
    {
        $errors = [];
        $info = [];

        try {
            $zip = new \ZipArchive();
            $result = $zip->open($localPath);

            if ($result !== true) {
                return ['valid' => false, 'errors' => ['Not a valid ZIP/PK3 file'], 'info' => []];
            }

            $totalFiles = $zip->numFiles;
            $info['total_files'] = $totalFiles;
            $hasBsp = false;
            $hasMapscript = false;
            $hasArena = false;
            $bspNames = [];
            $suspiciousFiles = [];

            for ($i = 0; $i < $totalFiles; $i++) {
                $name = $zip->getNameIndex($i);
                $lower = strtolower($name);

                // Check for BSP files
                if (preg_match('/^maps\/(.+)\.bsp$/i', $name, $matches)) {
                    $hasBsp = true;
                    $bspNames[] = $matches[1];
                }

                // Check for mapscripts
                if (str_contains($lower, 'mapscript')) {
                    $hasMapscript = true;
                }

                // Check for arena files
                if (str_contains($lower, '.arena')) {
                    $hasArena = true;
                }

                // Check for suspicious files (executables, etc.)
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (in_array($ext, ['exe', 'dll', 'bat', 'cmd', 'sh', 'ps1', 'vbs'])) {
                    $suspiciousFiles[] = $name;
                }
            }

            $info['has_bsp'] = $hasBsp;
            $info['bsp_names'] = $bspNames;
            $info['has_mapscript'] = $hasMapscript;
            $info['has_arena'] = $hasArena;

            // Validate
            if (!empty($suspiciousFiles)) {
                $errors[] = 'Contains suspicious executable files: ' . implode(', ', $suspiciousFiles);
            }

            if ($totalFiles > 5000) {
                $errors[] = 'PK3 contains too many files (' . $totalFiles . '). Max: 5000';
            }

            $zip->close();

        } catch (\Exception $e) {
            $errors[] = 'Failed to read PK3: ' . $e->getMessage();
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'info' => $info,
        ];
    }
}
