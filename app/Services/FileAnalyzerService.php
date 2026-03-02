<?php

namespace App\Services;

use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class FileAnalyzerService
{
    protected string $tempDir;
    protected array $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tga', 'webp'];

    public function __construct()
    {
        $this->tempDir = storage_path('app/temp/' . Str::uuid());
    }

    public function analyze(string $filePath, string $originalName): array
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        $result = [
            'file_extension' => $extension,
            'file_size' => filesize($filePath),
            'file_hash' => hash_file('sha256', $filePath),
            'mime_type' => mime_content_type($filePath) ?: 'application/octet-stream',
            'map_name' => null,
            'game' => $this->detectGame($originalName, $extension),
            'readme_content' => null,
            'extracted_images' => [],
            'extracted_metadata' => [],
        ];

        @mkdir($this->tempDir, 0755, true);

        try {
            if ($extension === 'pk3') {
                $result = array_merge($result, $this->analyzePk3($filePath));
            } elseif ($extension === 'zip') {
                $result = array_merge($result, $this->analyzeZip($filePath));
            } elseif ($extension === 'lua') {
                $result = array_merge($result, $this->analyzeLua($filePath));
            }
        } catch (\Exception $e) {
            $result['extracted_metadata']['analysis_error'] = $this->sanitizeString($e->getMessage());
        }

        return $result;
    }

    public function cleanup(): void
    {
        if (is_dir($this->tempDir)) {
            $this->deleteDirectory($this->tempDir);
        }
    }

    protected function analyzePk3(string $filePath): array
    {
        $result = ['extracted_images' => [], 'extracted_metadata' => []];
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) return $result;

        $bspFiles = [];
        $levelshotFiles = [];
        $readmeFiles = [];
        $allFiles = [];
        $fileList = [];
        $totalUncompressed = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            $stat = $zip->statIndex($i);
            $safeName = $this->sanitizeString($entry);
            $allFiles[] = $safeName;
            $lower = strtolower($safeName);

            $fileList[] = [
                'name' => $safeName,
                'size' => $stat['size'] ?? 0,
                'compressed' => $stat['comp_size'] ?? 0,
            ];
            $totalUncompressed += ($stat['size'] ?? 0);

            if (str_ends_with($lower, '.bsp')) {
                $bspFiles[] = $this->sanitizeString(pathinfo($entry, PATHINFO_FILENAME));
            }
            if (str_contains($lower, 'levelshots/') && $this->isImage($entry)) {
                $levelshotFiles[] = $entry;
            }
            if (preg_match('/readme|changelog/i', basename($entry)) && preg_match('/\.(txt|md|nfo)$/i', $entry)) {
                $readmeFiles[] = $entry;
            }
        }

        if (!empty($bspFiles)) {
            $result['map_name'] = $bspFiles[0];
            $result['extracted_metadata']['bsp_files'] = $bspFiles;
        }

        // Extract levelshot images
        foreach ($levelshotFiles as $lsFile) {
            $imageData = $zip->getFromName($lsFile);
            if (!$imageData) continue;

            $ext = strtolower(pathinfo($lsFile, PATHINFO_EXTENSION));
            $tempFile = $this->tempDir . '/' . Str::uuid() . '.' . $ext;
            file_put_contents($tempFile, $imageData);

            if ($ext === 'tga') {
                $converted = $this->convertTgaToPng($tempFile);
                if ($converted) {
                    @unlink($tempFile);
                    $tempFile = $converted;
                    $ext = 'png';
                }
            }

            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $result['extracted_images'][] = [
                    'path' => $tempFile,
                    'original_name' => $this->sanitizeString(basename($lsFile)),
                    'source' => 'levelshot',
                ];
            }
        }

        // Extract README
        foreach ($readmeFiles as $rf) {
            $content = $zip->getFromName($rf);
            if ($content) {
                $result['readme_content'] = $this->sanitizeString(substr($content, 0, 10000));
                break;
            }
        }

        // Parse arena files
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (str_ends_with(strtolower($entry), '.arena')) {
                $content = $zip->getFromName($entry);
                if ($content) {
                    $parsed = $this->parseArenaFile($content);
                    if ($parsed) $result['extracted_metadata']['arena'] = $parsed;
                }
            }
        }

        // Store comprehensive metadata
        $result['extracted_metadata']['total_files'] = $zip->numFiles;
        $result['extracted_metadata']['total_uncompressed_size'] = $totalUncompressed;
        $result['extracted_metadata']['has_bots'] = $this->hasBotFiles($allFiles);
        $result['extracted_metadata']['file_list'] = $fileList;
        $result['extracted_metadata']['file_types'] = $this->categorizeFiles($allFiles);

        $zip->close();

        return $result;
    }

    protected function analyzeZip(string $filePath): array
    {
        $result = ['extracted_images' => [], 'extracted_metadata' => []];
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) return $result;

        $pk3Files = [];
        $imageFiles = [];
        $readmeFiles = [];
        $tgaFiles = [];
        $allFiles = [];
        $fileList = [];
        $totalUncompressed = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            $stat = $zip->statIndex($i);
            $safeName = $this->sanitizeString($entry);
            $lower = strtolower($safeName);
            $allFiles[] = $safeName;

            $fileList[] = [
                'name' => $safeName,
                'size' => $stat['size'] ?? 0,
                'compressed' => $stat['comp_size'] ?? 0,
            ];
            $totalUncompressed += ($stat['size'] ?? 0);

            // Keep original $entry for getFromName() calls
            if (str_ends_with($lower, '.pk3')) $pk3Files[] = $entry;
            if ($this->isImage($entry)) $imageFiles[] = $entry;
            if (str_ends_with($lower, '.tga')) $tgaFiles[] = $entry;
            if (preg_match('/readme/i', basename($entry)) && preg_match('/\.(txt|md|nfo)$/i', $entry)) {
                $readmeFiles[] = $entry;
            }
        }

        // Store ZIP-level metadata
        $result['extracted_metadata']['total_files'] = $zip->numFiles;
        $result['extracted_metadata']['total_uncompressed_size'] = $totalUncompressed;
        $result['extracted_metadata']['file_list'] = $fileList;
        $result['extracted_metadata']['file_types'] = $this->categorizeFiles($allFiles);

        // Analyze contained PK3
        if (!empty($pk3Files)) {
            $result['extracted_metadata']['contained_pk3s'] = array_map([$this, 'sanitizeString'], $pk3Files);
            foreach ($pk3Files as $pk3Name) {
                $pk3Data = $zip->getFromName($pk3Name);
                if ($pk3Data) {
                    $tempPk3 = $this->tempDir . '/temp_' . Str::uuid() . '.pk3';
                    file_put_contents($tempPk3, $pk3Data);
                    $pk3Result = $this->analyzePk3($tempPk3);
                    @unlink($tempPk3);

                    if (empty($result['map_name']) && !empty($pk3Result['map_name'])) {
                        $result['map_name'] = $pk3Result['map_name'];
                    }
                    $result['extracted_images'] = array_merge($result['extracted_images'], $pk3Result['extracted_images'] ?? []);

                    // Merge PK3 metadata (bsp, arena, bots)
                    foreach (['bsp_files', 'arena', 'has_bots'] as $key) {
                        if (!empty($pk3Result['extracted_metadata'][$key])) {
                            $result['extracted_metadata'][$key] = $pk3Result['extracted_metadata'][$key];
                        }
                    }

                    // Add PK3 file list as sub-listing
                    if (!empty($pk3Result['extracted_metadata']['file_list'])) {
                        $safePk3Name = $this->sanitizeString($pk3Name);
                        $result['extracted_metadata']['pk3_contents'][$safePk3Name] = $pk3Result['extracted_metadata']['file_list'];
                    }

                    if (!empty($pk3Result['readme_content']) && empty($result['readme_content'])) {
                        $result['readme_content'] = $pk3Result['readme_content'];
                    }
                }
            }
        }

        // Extract TGA files
        foreach ($tgaFiles as $tgaFile) {
            $data = $zip->getFromName($tgaFile);
            if (!$data) continue;
            $tempTga = $this->tempDir . '/' . Str::uuid() . '.tga';
            file_put_contents($tempTga, $data);
            $converted = $this->convertTgaToPng($tempTga);
            if ($converted) {
                @unlink($tempTga);
                $result['extracted_images'][] = [
                    'path' => $converted,
                    'original_name' => $this->sanitizeString(pathinfo($tgaFile, PATHINFO_FILENAME)) . '.png',
                    'source' => 'extracted',
                ];
            } else {
                @unlink($tempTga);
            }
        }

        // Extract regular images
        $regularImages = array_filter($imageFiles, fn($f) => !str_ends_with(strtolower($f), '.tga'));
        foreach (array_slice($regularImages, 0, 5) as $img) {
            $data = $zip->getFromName($img);
            if (!$data) continue;
            $ext = strtolower(pathinfo($img, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $tempFile = $this->tempDir . '/' . Str::uuid() . '.' . $ext;
                file_put_contents($tempFile, $data);
                $result['extracted_images'][] = [
                    'path' => $tempFile,
                    'original_name' => $this->sanitizeString(basename($img)),
                    'source' => 'extracted',
                ];
            }
        }

        if (empty($result['readme_content'])) {
            foreach ($readmeFiles as $rf) {
                $content = $zip->getFromName($rf);
                if ($content) {
                    $result['readme_content'] = $this->sanitizeString(substr($content, 0, 10000));
                    break;
                }
            }
        }

        $zip->close();
        return $result;
    }

    protected function analyzeLua(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $meta = [];

        foreach (['Name' => 'script_name', 'Author' => 'author', 'Version' => 'version', 'Description' => 'description'] as $key => $field) {
            if (preg_match('/--\s*' . $key . ':\s*(.+)/i', $content, $m)) {
                $meta[$field] = $this->sanitizeString(trim($m[1]));
            }
        }

        $meta['line_count'] = substr_count($content, "\n") + 1;

        return ['extracted_metadata' => $meta];
    }

    /**
     * Sanitize string for safe JSON encoding (fix broken UTF-8)
     */
    protected function sanitizeString(string $str): string
    {
        // Try to detect and convert from common encodings
        $detected = @mb_detect_encoding($str, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
        if ($detected && $detected !== 'UTF-8') {
            $converted = @mb_convert_encoding($str, 'UTF-8', $detected);
            if ($converted !== false) {
                $str = $converted;
            }
        }

        // Remove invalid UTF-8 bytes
        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $str);
        if ($clean === false) {
            $clean = preg_replace('/[\x80-\xFF]/', '?', $str);
        }

        // Final check: verify it's JSON-encodable
        $test = @json_encode($clean);
        if ($test === false) {
            $clean = preg_replace('/[^\x20-\x7E]/', '?', $str);
        }

        return $clean;
    }

    /**
     * Categorize files by extension type
     */
    protected function categorizeFiles(array $files): array
    {
        $types = [];
        foreach ($files as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (empty($ext)) continue;
            $types[$ext] = ($types[$ext] ?? 0) + 1;
        }
        arsort($types);
        return $types;
    }

    protected function detectGame(string $filename, string $extension): ?string
    {
        $lower = strtolower($filename);
        if (str_contains($lower, 'rtcw') || str_contains($lower, 'wolfsp')) return 'RtCW';
        if (str_contains($lower, 'etqw')) return 'ET Quake Wars';
        if ($extension === 'pk3') return 'ET';
        return null;
    }

    protected function isImage(string $filename): bool
    {
        return in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), $this->imageExtensions);
    }

    protected function parseArenaFile(string $content): ?array
    {
        $content = $this->sanitizeString($content);
        $result = [];
        if (preg_match('/\{([^}]+)\}/s', $content, $match)) {
            $block = $match[1];
            foreach (['map' => '/map\s+"?([^"\n]+)"?/i', 'longname' => '/longname\s+"([^"]+)"/i', 'type' => '/type\s+"?([^"\n]+)"?/i'] as $key => $pattern) {
                if (preg_match($pattern, $block, $m)) $result[$key] = trim($m[1]);
            }
        }
        return $result ?: null;
    }

    protected function hasBotFiles(array $files): bool
    {
        foreach ($files as $f) {
            if (preg_match('/\.(nav|way)$/i', $f) || str_contains(strtolower($f), '/nav/')) return true;
        }
        return false;
    }

    protected function convertTgaToPng(string $tgaPath): ?string
    {
        $pngPath = preg_replace('/\.tga$/i', '.png', $tgaPath);
        try {
            $needsFlip = false;
            $header = file_get_contents($tgaPath, false, null, 0, 18);
            if ($header && strlen($header) >= 18) {
                $descriptor = ord($header[17]);
                $needsFlip = (($descriptor & 0x20) === 0);
            }

            $flipArg = $needsFlip ? '-flip' : '';

            if (function_exists('exec')) {
                exec("convert " . escapeshellarg($tgaPath) . " {$flipArg} " . escapeshellarg($pngPath) . " 2>&1", $out, $ret);
                if ($ret === 0 && file_exists($pngPath)) return $pngPath;
            }
            $image = Image::read($tgaPath);
            if ($needsFlip) $image->flip();
            $image->toPng()->save($pngPath);
            return file_exists($pngPath) ? $pngPath : null;
        } catch (\Exception) {
            return null;
        }
    }

    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}