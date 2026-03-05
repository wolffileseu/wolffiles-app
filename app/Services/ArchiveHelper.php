<?php

namespace App\Services;

use ZipArchive;

/**
 * Unified archive handler for ZIP, PK3, RAR, and 7z files.
 * Provides a consistent interface regardless of archive type.
 */
class ArchiveHelper
{
    protected string $path;
    protected string $type;
    protected string $tempDir;
    protected ?ZipArchive $zip = null;
    protected ?array $cachedEntries = null;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->type = $this->detectType($path);
        $this->tempDir = sys_get_temp_dir() . '/archive_' . md5($path . microtime());
    }

    /**
     * Open the archive. Returns true on success.
     */
    public function open(): bool
    {
        if ($this->type === 'zip') {
            $this->zip = new ZipArchive();
            return $this->zip->open($this->path) === true;
        }

        // For RAR/7z we just check the file exists and is readable
        return file_exists($this->path) && is_readable($this->path);
    }

    /**
     * List all file entries in the archive.
     * Returns array of ['name' => string, 'size' => int, 'compressed' => int]
     */
    public function listEntries(): array
    {
        if ($this->cachedEntries !== null) return $this->cachedEntries;

        $entries = [];

        if ($this->type === 'zip' && $this->zip) {
            for ($i = 0; $i < $this->zip->numFiles; $i++) {
                $stat = $this->zip->statIndex($i);
                $name = $this->zip->getNameIndex($i);
                // Skip directories
                if (str_ends_with($name, '/')) continue;
                $entries[] = [
                    'name' => $name,
                    'size' => $stat['size'] ?? 0,
                    'compressed' => $stat['comp_size'] ?? 0,
                    'index' => $i,
                ];
            }
        } elseif ($this->type === 'rar') {
            $entries = $this->listRar();
        } elseif ($this->type === '7z') {
            $entries = $this->list7z();
        }

        $this->cachedEntries = $entries;
        return $entries;
    }

    /**
     * Get number of files in the archive.
     */
    public function numFiles(): int
    {
        return count($this->listEntries());
    }

    /**
     * Get the content of a specific file by name.
     */
    public function getFromName(string $name): ?string
    {
        if ($this->type === 'zip' && $this->zip) {
            $content = $this->zip->getFromName($name);
            return $content !== false ? $content : null;
        }

        // For RAR/7z, extract to temp and read
        return $this->extractSingleFile($name);
    }

    /**
     * Get basename of a file entry.
     */
    public function getBasename(string $entry): string
    {
        return basename($entry);
    }

    /**
     * Extract the entire archive to a directory.
     */
    public function extractTo(string $destination, ?string $specificFile = null): bool
    {
        @mkdir($destination, 0755, true);

        if ($this->type === 'zip' && $this->zip) {
            if ($specificFile) {
                return $this->zip->extractTo($destination, $specificFile);
            }
            return $this->zip->extractTo($destination);
        }

        if ($this->type === 'rar') {
            $cmd = $specificFile
                ? sprintf('unrar x -o+ %s %s %s 2>&1', escapeshellarg($this->path), escapeshellarg($specificFile), escapeshellarg($destination))
                : sprintf('unrar x -o+ %s %s 2>&1', escapeshellarg($this->path), escapeshellarg($destination));
            exec($cmd, $output, $ret);
            return $ret === 0;
        }

        if ($this->type === '7z') {
            $cmd = $specificFile
                ? sprintf('7z x -o%s -y %s %s 2>&1', escapeshellarg($destination), escapeshellarg($this->path), escapeshellarg($specificFile))
                : sprintf('7z x -o%s -y %s 2>&1', escapeshellarg($destination), escapeshellarg($this->path));
            exec($cmd, $output, $ret);
            return $ret === 0;
        }

        return false;
    }

    /**
     * Get the archive type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Check if this is a supported archive type.
     */
    public static function isSupported(string $extension): bool
    {
        return in_array(strtolower($extension), ['zip', 'pk3', 'rar', '7z']);
    }

    /**
     * Close the archive and clean up temp files.
     */
    public function close(): void
    {
        if ($this->zip) {
            $this->zip->close();
            $this->zip = null;
        }
        $this->cachedEntries = null;

        // Clean up temp extraction dir
        if (is_dir($this->tempDir)) {
            $this->deleteDirectory($this->tempDir);
        }
    }

    // ---- Private methods ----

    protected function detectType(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['zip', 'pk3'])) return 'zip';
        if ($ext === 'rar') return 'rar';
        if ($ext === '7z') return '7z';

        // Fallback: check magic bytes
        $handle = fopen($path, 'rb');
        if ($handle) {
            $magic = fread($handle, 4);
            fclose($handle);
            if ($magic === "PK\x03\x04") return 'zip';
            if (substr($magic, 0, 3) === 'Rar') return 'rar';
            if (substr($magic, 0, 2) === "7z") return '7z';
        }

        return 'zip'; // default fallback
    }

    protected function listRar(): array
    {
        $entries = [];
        exec(sprintf('unrar l %s 2>/dev/null', escapeshellarg($this->path)), $lines, $ret);
        if ($ret !== 0) return $entries;

        // unrar-free format: filename on one line, size+date on next
        $inList = false;
        $currentName = null;
        foreach ($lines as $line) {
            // Start after header separator
            if (str_starts_with($line, '------')) {
                $inList = !$inList;
                continue;
            }
            if (!$inList) continue;

            $trimmed = trim($line);
            if (empty($trimmed)) continue;

            // Size line: starts with digits
            if (preg_match('/^\s*(\d+)\s+\d{2}-\d{2}-\d{2}\s+/', $line, $m)) {
                if ($currentName !== null) {
                    $entries[] = [
                        'name' => $currentName,
                        'size' => (int)$m[1],
                        'compressed' => 0,
                        'index' => count($entries),
                    ];
                }
                $currentName = null;
            } else {
                // Filename line
                $currentName = $trimmed;
            }
        }
        return $entries;
    }

    protected function list7z(): array
    {
        $entries = [];
        exec(sprintf('7z l -slt %s 2>/dev/null', escapeshellarg($this->path)), $lines, $ret);
        if ($ret !== 0) return $entries;

        $current = [];
        foreach ($lines as $line) {
            if (preg_match('/^Path\s*=\s*(.+)$/', $line, $m)) {
                if (!empty($current['name'])) {
                    $entries[] = $current + ['index' => count($entries)];
                }
                $current = ['name' => trim($m[1]), 'size' => 0, 'compressed' => 0];
            }
            if (preg_match('/^Size\s*=\s*(\d+)/', $line, $m)) {
                $current['size'] = (int)$m[1];
            }
            if (preg_match('/^Packed Size\s*=\s*(\d+)/', $line, $m)) {
                $current['compressed'] = (int)$m[1];
            }
            if (preg_match('/^Folder\s*=\s*\+/', $line)) {
                $current = []; // skip folders
            }
        }
        if (!empty($current['name'])) {
            $entries[] = $current + ['index' => count($entries)];
        }

        // 7z lists the archive itself as first entry, skip it
        if (!empty($entries) && $entries[0]['name'] === basename($this->path)) {
            array_shift($entries);
        }

        return $entries;
    }

    protected function extractSingleFile(string $name): ?string
    {
        @mkdir($this->tempDir, 0755, true);

        if ($this->type === 'rar') {
            exec(sprintf('unrar p -inul %s %s 2>/dev/null', escapeshellarg($this->path), escapeshellarg($name)), $output, $ret);
            if ($ret === 0 && !empty($output)) {
                return implode("\n", $output);
            }
            // Fallback: extract to temp
            $this->extractTo($this->tempDir, $name);
        } elseif ($this->type === '7z') {
            $this->extractTo($this->tempDir, $name);
        }

        $extracted = $this->tempDir . '/' . $name;
        if (file_exists($extracted)) {
            $content = file_get_contents($extracted);
            return $content !== false ? $content : null;
        }

        return null;
    }

    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getRealPath()) : @unlink($item->getRealPath());
        }
        @rmdir($dir);
    }
}
