<?php

namespace App\Services;

use App\Models\Demo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DemoUploadService
{
    protected array $demoExtensions = ['dm_84', 'dm_83', 'dm_82', 'dm_60', 'tv_84', 'dm_68', 'dm_73'];
    protected array $archiveExtensions = ['zip', 'rar', '7z', 'gz'];

    public function upload(UploadedFile $uploadedFile, array $data, int $userId): Demo
    {
        $originalName = $uploadedFile->getClientOriginalName();
        $tempDir = sys_get_temp_dir() . '/demo_upload_' . Str::random(12);

        try {
            mkdir($tempDir, 0755, true);
            $tempFile = $tempDir . '/' . $originalName;
            copy($uploadedFile->getRealPath(), $tempFile);

            $isArchive = in_array($this->getSimpleExt($originalName), $this->archiveExtensions);
            $demoFiles = [];
            $extractedDir = $tempDir . '/extracted';

            if ($isArchive) {
                mkdir($extractedDir, 0755, true);
                $this->extractArchive($tempFile, $extractedDir);
				$this->validateExtractedSize($extractedDir, 500 * 1024 * 1024); // 500MB max
                $demoFiles = $this->findDemoFiles($extractedDir);
            } elseif ($this->isDemoFile($originalName)) {
                $demoFiles = [$tempFile];
            }

            $parsedMeta = [];
            if (!empty($demoFiles)) {
                $parsedMeta = $this->parseDemoFile($demoFiles[0]);
            }

            $filenameMeta = $this->parseFilename($originalName);
            $merged = $this->mergeMetadata($data, $parsedMeta, $filenameMeta);

            $demoFormat = null;
            if (!empty($demoFiles)) {
                $demoFormat = $this->getFullExt(basename($demoFiles[0]));
            } elseif ($this->isDemoFile($originalName)) {
                $demoFormat = $this->getFullExt($originalName);
            }

            $s3Path = $this->storeOnS3($uploadedFile, $originalName);

            $demo = Demo::create([
                'user_id' => $userId,
                'title' => $merged['title'],
                'description' => $merged['description'] ?? null,
                'category_id' => $merged['category_id'],
                'game' => $merged['game'] ?? 'ET',
                'map_name' => $merged['map_name'] ?? null,
                'mod_name' => $merged['mod_name'] ?? null,
                'gametype' => $merged['gametype'] ?? null,
                'match_format' => $merged['match_format'] ?? null,
                'team_axis' => $merged['team_axis'] ?? null,
                'team_allies' => $merged['team_allies'] ?? null,
                'match_date' => $merged['match_date'] ?? null,
                'match_source' => $merged['match_source'] ?? null,
                'match_source_url' => $merged['match_source_url'] ?? null,
                'recorder_name' => $merged['recorder_name'] ?? null,
                'server_name' => $parsedMeta['server_name'] ?? ($merged['server_name'] ?? null),
                'player_list' => $parsedMeta['player_list'] ?? null,
                'file_path' => $s3Path,
                'file_name' => $originalName,
                'file_extension' => $this->getSimpleExt($originalName),
                'file_size' => $uploadedFile->getSize(),
                'file_hash' => hash_file('sha256', $uploadedFile->getRealPath()),
                'demo_format' => $demoFormat,
                'duration_seconds' => $parsedMeta['duration_seconds'] ?? null,
                'status' => 'pending',
            ]);

            if ($isArchive && count($demoFiles) > 1) {
                $fileList = array_map(fn($f) => basename($f), $demoFiles);
                $demo->update([
                    'description' => ($demo->description ? $demo->description . "\n\n" : '')
                        . "📦 Archive contains " . count($demoFiles) . " demo files:\n"
                        . implode("\n", array_map(fn($f) => "• {$f}", $fileList)),
                ]);
            }

            return $demo;
        } finally {
            $this->cleanupDir($tempDir);
        }
    }

    /**
     * Analyze a demo from S3 - used by the viewer.
     */
    public function analyzeFromS3(Demo $demo): array
    {
        $tempDir = sys_get_temp_dir() . '/demo_analyze_' . Str::random(12);
        mkdir($tempDir, 0755, true);

        try {
            $content = Storage::disk('s3')->get($demo->file_path);
            if (!$content) return ['error' => 'Could not download file from storage'];

            $tempFile = $tempDir . '/' . $demo->file_name;
            file_put_contents($tempFile, $content);

            $isArchive = in_array($this->getSimpleExt($demo->file_name), $this->archiveExtensions);
            $result = [
                'file_name' => $demo->file_name,
                'file_size' => $demo->file_size,
                'file_hash' => $demo->file_hash,
                'is_archive' => $isArchive,
                'archive_contents' => [],
                'demo_files' => [],
                'parsed_data' => [],
                'hex_preview' => '',
                'config_strings' => [],
            ];

            if ($isArchive) {
                $extractedDir = $tempDir . '/extracted';
                mkdir($extractedDir, 0755, true);
                $this->extractArchive($tempFile, $extractedDir);

                // List all archive contents
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($extractedDir, \RecursiveDirectoryIterator::SKIP_DOTS)
                );
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $relPath = str_replace($extractedDir . '/', '', $file->getPathname());
                        $result['archive_contents'][] = [
                            'path' => $relPath,
                            'size' => $file->getSize(),
                            'is_demo' => $this->isDemoFile($file->getFilename()),
                        ];
                    }
                }

                $demoFiles = $this->findDemoFiles($extractedDir);
                foreach ($demoFiles as $df) {
                    $parsed = $this->parseDemoFile($df);
                    $parsed['file_name'] = basename($df);
                    $parsed['file_size'] = filesize($df);
                    $parsed['demo_format'] = $this->getFullExt(basename($df));
                    $parsed['hex_preview'] = $this->hexPreview($df, 256);
                    $result['demo_files'][] = $parsed;
                }

                if (!empty($demoFiles)) {
                    $result['parsed_data'] = $this->parseDemoFile($demoFiles[0]);
                    $result['config_strings'] = $this->extractConfigStrings($demoFiles[0]);
                    $result['hex_preview'] = $this->hexPreview($demoFiles[0], 512);
                }
            } else {
                $result['parsed_data'] = $this->parseDemoFile($tempFile);
                $result['config_strings'] = $this->extractConfigStrings($tempFile);
                $result['hex_preview'] = $this->hexPreview($tempFile, 512);
                $result['demo_files'][] = array_merge($result['parsed_data'], [
                    'file_name' => $demo->file_name,
                    'file_size' => $demo->file_size,
                    'demo_format' => $demo->demo_format,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("DemoUploadService::analyzeFromS3 failed", ['demo' => $demo->id, 'error' => $e->getMessage()]);
            return ['error' => 'Analysis failed: ' . $e->getMessage()];
        } finally {
            $this->cleanupDir($tempDir);
        }
    }

    /**
     * Extract all configstrings from a demo file for display.
     */
    public function extractConfigStrings(string $path): array
    {
        $configs = [];
        try {
            $handle = fopen($path, 'rb');
            if (!$handle) return $configs;

            $data = fread($handle, min(filesize($path), 131072)); // Read 128KB
            fclose($handle);

            $strings = $this->extractStrings($data, 4);

            foreach ($strings as $str) {
                // Look for key\value patterns
                if (preg_match_all('/([a-zA-Z_][a-zA-Z0-9_]*)\\\\([^\\\\]+)/', $str, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $m) {
                        $key = $m[1];
                        $value = $this->cleanQ3ColorCodes(trim($m[2]));
                        if (strlen($key) >= 2 && strlen($key) <= 50 && strlen($value) >= 1 && strlen($value) <= 500) {
                            $configs[$key] = $value;
                        }
                    }
                }
            }

            // Sort by key
            ksort($configs);

        } catch (\Exception $e) {
            Log::warning("extractConfigStrings failed", ['path' => $path, 'error' => $e->getMessage()]);
        }

        return $configs;
    }

    /**
     * Get hex preview of a file.
     */
    protected function hexPreview(string $path, int $bytes = 256): string
    {
        $handle = fopen($path, 'rb');
        if (!$handle) return '';
        $data = fread($handle, $bytes);
        fclose($handle);

        $lines = [];
        for ($i = 0; $i < strlen($data); $i += 16) {
            $chunk = substr($data, $i, 16);
            $hex = implode(' ', array_map(fn($c) => sprintf('%02X', ord($c)), str_split($chunk)));
            $hex = str_pad($hex, 47);
            $ascii = preg_replace('/[^\x20-\x7E]/', '.', $chunk);
            $lines[] = sprintf('%08X  %s  |%s|', $i, $hex, $ascii);
        }

        return implode("\n", $lines);
    }

    // ── Archive extraction ──────────────────────────────

    protected function extractArchive(string $path, string $to): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'zip' => $this->run("unzip -o -q %s -d %s", $path, $to),
            'rar' => $this->run("unrar x -o+ -y %s %s", $path, $to),
            '7z'  => $this->run("7z x -y -o%s %s", $to, $path),
            'gz'  => $this->extractGz($path, $to),
            default => false,
        };
    }

    protected function run(string $fmt, string ...$args): bool
    {
        $escaped = array_map('escapeshellarg', $args);
        exec(vsprintf(str_replace('%s', '%s', $fmt), $escaped) . ' 2>&1', $out, $code);
        return $code === 0;
    }

    protected function extractGz(string $path, string $to): bool
    {
        $outFile = $to . '/' . basename($path, '.gz');
        $gz = gzopen($path, 'rb');
        if (!$gz) return false;
        $out = fopen($outFile, 'wb');
        while (!gzeof($gz)) fwrite($out, gzread($gz, 65536));
        gzclose($gz);
        fclose($out);
        return true;
    }

    // ── Demo file discovery ─────────────────────────────

    protected function findDemoFiles(string $dir): array
    {
        $found = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && $this->isDemoFile($f->getFilename())) $found[] = $f->getPathname();
        }
        usort($found, fn($a, $b) => filesize($b) - filesize($a));
        return $found;
    }

    protected function isDemoFile(string $filename): bool
    {
        return in_array($this->getFullExt($filename), $this->demoExtensions);
    }

    // ── Binary parsing ──────────────────────────────────

    public function parseDemoFile(string $path): array
    {
        $meta = ['server_name' => null, 'map_name' => null, 'mod_name' => null,
                 'gametype' => null, 'player_list' => null, 'duration_seconds' => null,
                 'chat_log' => []];

        try {
            $handle = fopen($path, 'rb');
            if (!$handle) return $meta;

            $fileSize = filesize($path);
            $data = fread($handle, min($fileSize, 131072)); // 128KB
            fclose($handle);

            if (empty($data)) return $meta;

            $strings = $this->extractStrings($data, 3);
            $blob = implode("\n", $strings);

            // Server hostname
            if (preg_match('/sv_hostname\\\\([^\\\\"\n]+)/i', $blob, $m))
                $meta['server_name'] = $this->cleanQ3ColorCodes(trim($m[1]));

            // Map name
            if (preg_match('/mapname\\\\([^\\\\"\n]+)/i', $blob, $m))
                $meta['map_name'] = strtolower(trim($m[1]));

            // Gametype
            if (preg_match('/g_gametype\\\\(\d+)/i', $blob, $m))
                $meta['gametype'] = $this->resolveGametype((int)$m[1]);

            // Mod
            if (preg_match('/gamename\\\\([^\\\\"\n]+)/i', $blob, $m))
                $meta['mod_name'] = strtolower(trim($m[1]));
            if (!$meta['mod_name'] && preg_match('/fs_game\\\\([^\\\\"\n]+)/i', $blob, $m))
                $meta['mod_name'] = strtolower(trim($m[1]));

            // Players
            $players = [];
            if (preg_match_all('/n\\\\([^\\\\]+)\\\\t\\\\(\d+)/i', $blob, $matches, PREG_SET_ORDER)) {
                $seen = [];
                foreach ($matches as $pm) {
                    $name = $this->cleanQ3ColorCodes(trim($pm[1]));
                    $team = match ((int)$pm[2]) { 1 => 'axis', 2 => 'allies', 3 => 'spectator', default => 'unknown' };
                    if ($name && strlen($name) < 50 && !in_array($name, $seen)) {
                        $players[] = ['name' => $name, 'team' => $team];
                        $seen[] = $name;
                    }
                }
            }
            if (!empty($players)) $meta['player_list'] = $players;

            // Chat messages (look for "say:" or "sayteam:" patterns)
            $chatMessages = [];
            foreach ($strings as $str) {
                // ET chat patterns
                if (preg_match('/^(?:\x19|\x1e)?(?:\^.)?(.+?)\x19?: (.+)$/', $str, $cm)) {
                    $chatMessages[] = ['player' => $this->cleanQ3ColorCodes($cm[1]), 'message' => $this->cleanQ3ColorCodes($cm[2])];
                }
                // Fallback: look for common chat patterns
                elseif (preg_match('/^(.{2,25}): (.{1,200})$/', $str, $cm)) {
                    $name = $this->cleanQ3ColorCodes(trim($cm[1]));
                    $msg = $this->cleanQ3ColorCodes(trim($cm[2]));
                    // Filter out configstring-like patterns
                    if (!str_contains($name, '\\') && !str_contains($msg, '\\') && strlen($name) <= 25) {
                        $chatMessages[] = ['player' => $name, 'message' => $msg];
                    }
                }
            }
            if (!empty($chatMessages)) $meta['chat_log'] = array_slice($chatMessages, 0, 200);

            // Duration estimate
            if ($fileSize > 0) {
                $ext = $this->getFullExt(basename($path));
                $bps = match ($ext) { 'tv_84' => 30000, 'dm_60' => 12000, default => 18000 };
                $meta['duration_seconds'] = (int) round($fileSize / $bps);
            }

        } catch (\Exception $e) {
            Log::warning("parseDemoFile failed", ['path' => $path, 'error' => $e->getMessage()]);
        }

        return $meta;
    }

    protected function extractStrings(string $data, int $minLen = 4): array
    {
        $strings = [];
        $cur = '';
        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $o = ord($data[$i]);
            if ($o >= 32 && $o <= 126) { $cur .= $data[$i]; }
            else { if (strlen($cur) >= $minLen) $strings[] = $cur; $cur = ''; }
        }
        if (strlen($cur) >= $minLen) $strings[] = $cur;
        return $strings;
    }

    // ── Filename parsing ────────────────────────────────

    protected function parseFilename(string $filename): array
    {
        $meta = [];
        $name = strtolower(pathinfo($filename, PATHINFO_FILENAME));

        $maps = ['supply','goldrush','radar','oasis','railgun','fuel_dump','frostbite','adlernest',
                 'braundorf','bremen','caen','erdenberg','karsiah','missile','battery','ice',
                 'venice','dubrovnik','eltz','delivery','bergen','reactor','beach'];
        foreach ($maps as $map) { if (str_contains($name, $map)) { $meta['map_name'] = $map; break; } }

        if (preg_match('/(\d+on\d+|\d+v\d+|\d+vs\d+)/i', $name, $m))
            $meta['match_format'] = strtolower(str_replace(['vs','v'], 'on', $m[1]));

        if (preg_match('/([a-z0-9_.]+?)[\s_-]+vs[\s_-]+([a-z0-9_.]+)/i', $name, $m)) {
            $t1 = trim($m[1], ' _-.'); $t2 = trim($m[2], ' _-.');
            if (strlen($t1) >= 2 && strlen($t1) <= 30 && !in_array($t1, $maps)) $meta['team_axis'] = $m[1];
            if (strlen($t2) >= 2 && strlen($t2) <= 30 && !in_array($t2, $maps)) $meta['team_allies'] = $m[2];
        }

        if (str_contains($name, '_sw') || str_contains($name, 'stopwatch')) $meta['gametype'] = 'stopwatch';
        elseif (str_contains($name, '_obj')) $meta['gametype'] = 'objective';

        return $meta;
    }

    protected function mergeMetadata(array $user, array $parsed, array $filename): array
    {
        foreach (['map_name','mod_name','gametype','match_format','team_axis','team_allies','server_name'] as $f) {
            if (empty($user[$f])) $user[$f] = $parsed[$f] ?? $filename[$f] ?? null;
        }
        return $user;
    }

    // ── Helpers ─────────────────────────────────────────

    protected function cleanQ3ColorCodes(string $t): string { return preg_replace('/\^[0-9a-zA-Z]/', '', $t); }

    protected function resolveGametype(int $t): string
    {
        return match ($t) { 2 => 'objective', 3 => 'stopwatch', 4 => 'lms', 5 => 'ctf', 6 => 'dm', default => 'objective' };
    }

    protected function getFullExt(string $f): string
    {
        if (preg_match('/\.(dm_\d{2}|tv_\d{2})$/i', $f, $m)) return strtolower($m[1]);
        return strtolower(pathinfo($f, PATHINFO_EXTENSION));
    }

    protected function getSimpleExt(string $f): string { return strtolower(pathinfo($f, PATHINFO_EXTENSION)); }

    protected function storeOnS3(UploadedFile $file, string $name): string
    {
        $path = "demos/" . now()->format('Y/m') . "/" . Str::uuid() . "/" . Str::slug(pathinfo($name, PATHINFO_FILENAME)) . "." . strtolower(pathinfo($name, PATHINFO_EXTENSION));
        Storage::disk('s3')->putFileAs(dirname($path), $file, basename($path));
        return $path;
    }

    protected function validateExtractedSize(string $dir, int $maxBytes): void
    {
        $totalSize = 0;
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile()) {
                $totalSize += $f->getSize();
                if ($totalSize > $maxBytes) {
                    throw new \RuntimeException('Extracted archive exceeds maximum allowed size of ' . round($maxBytes / 1024 / 1024) . 'MB');
                }
            }
        }
    }
	
    protected function cleanupDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $f) { $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname()); }
        rmdir($dir);
    }
}
