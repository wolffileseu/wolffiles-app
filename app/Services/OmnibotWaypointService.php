<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class OmnibotWaypointService
{
    protected string $basePath;
    protected string $githubRepo;
    protected string $githubToken;

    public function __construct()
    {
        $this->basePath = storage_path('app/omnibot');
        $this->githubRepo = config('services.omnibot.github_repo', 'wolffileseu/omnibot-waypoints');
        $this->githubToken = config('services.omnibot.github_token', '');
    }

    protected function navPath(string $game = 'et'): string
    {
        $g = strtolower($game) === 'rtcw' ? 'rtcw' : 'et';
        $path = $this->basePath . "/{$g}/nav";
        if (!is_dir($path)) mkdir($path, 0755, true);
        return $path;
    }

    protected function indexPath(string $game = 'et'): string
    {
        return $game === 'rtcw'
            ? $this->basePath . '/rtcw-waypoint-index.json'
            : $this->basePath . '/waypoint-index.json';
    }

    /**
     * Scan a single uploaded file for bot waypoints
     */
    public function scanFile(File $file): array
    {
        $results = ['new' => [], 'existing' => [], 'errors' => []];

        if (!in_array($file->file_extension, ['zip', 'pk3'])) {
            return $results;
        }

        $game = strtolower($file->game ?? 'ET') === 'rtcw' ? 'rtcw' : 'et';
        $navPath = $this->navPath($game);

        try {
            $tmp = tempnam(sys_get_temp_dir(), 'ob_scan_');
            $stream = Storage::disk('s3')->readStream($file->file_path);
            if (!$stream) return $results;

            $fp = fopen($tmp, 'w');
            stream_copy_to_stream($stream, $fp);
            fclose($fp);
            fclose($stream);

            $zip = new ZipArchive();
            if ($zip->open($tmp) !== true) {
                unlink($tmp);
                return $results;
            }

            $botFiles = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = basename($zip->getNameIndex($i));
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                if (in_array($ext, ['way', 'gm'])) {
                    $botFiles[] = ['name' => $name, 'index' => $i];
                }
            }

            if (empty($botFiles)) {
                $zip->close();
                unlink($tmp);
                return $results;
            }

            // Group by map name
            $mapGroups = [];
            foreach ($botFiles as $bf) {
                $base = pathinfo($bf['name'], PATHINFO_FILENAME);
                $mapName = strtolower(preg_replace('/_goals$/', '', $base));
                if (preg_match('/^(goal_|gui|docs|weapon_)/', $mapName)) continue;
                if (preg_match('/_(test|stuckages)$/', $mapName)) continue;
                if (!isset($mapGroups[$mapName])) $mapGroups[$mapName] = [];
                $mapGroups[$mapName][] = $bf;
            }

            foreach ($mapGroups as $mapName => $files) {
                $hasWay = false;
                foreach ($files as $f) {
                    if (preg_match('/\.way$/', $f['name'])) $hasWay = true;
                }
                if (!$hasWay) continue;

                if (file_exists($navPath . '/' . $mapName . '.way')) {
                    $results['existing'][] = $mapName;
                    continue;
                }

                foreach ($files as $f) {
                    $content = $zip->getFromIndex($f['index']);
                    if ($content !== false) {
                        file_put_contents($navPath . '/' . strtolower($f['name']), $content);
                    }
                }
                $results['new'][] = $mapName;
            }

            $zip->close();
            unlink($tmp);

            if (!empty($results['new'])) {
                $this->rebuildIndex($game);
                $this->pushToGitHub($results['new'], $game);
                Log::info("OmnibotSync: " . count($results['new']) . " new {$game} maps from {$file->file_name}", $results['new']);
            }

        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            Log::error('OmnibotSync error: ' . $e->getMessage());
            if (isset($tmp) && file_exists($tmp)) unlink($tmp);
        }

        return $results;
    }

    /**
     * Scan all existing bot files
     */
    public function scanAll(): array
    {
        $total = ['new' => [], 'existing' => [], 'errors' => []];
        $files = File::whereIn('file_extension', ['zip', 'pk3'])->get();
        foreach ($files as $file) {
            $result = $this->scanFile($file);
            $total['new'] = array_merge($total['new'], $result['new']);
            $total['existing'] = array_merge($total['existing'], $result['existing']);
            $total['errors'] = array_merge($total['errors'], $result['errors']);
        }
        return $total;
    }

    /**
     * Rebuild the waypoint index JSON
     */
    public function rebuildIndex(string $game = 'et'): void
    {
        $navPath = $this->navPath($game);
        $maps = [];
        foreach (scandir($navPath) as $f) {
            if ($f === '.' || $f === '..' || is_dir("$navPath/$f")) continue;
            $ext = pathinfo($f, PATHINFO_EXTENSION);
            if (!in_array($ext, ['way', 'gm'])) continue;
            $base = pathinfo($f, PATHINFO_FILENAME);
            $name = strtolower(preg_replace('/_goals$/', '', $base));
            if (!isset($maps[$name])) {
                $maps[$name] = ['name' => $name, 'files' => [], 'has_way' => false, 'has_gm' => false, 'has_goals' => false, 'status' => 'complete'];
            }
            $maps[$name]['files'][] = $f;
            if (preg_match('/\.way$/', $f)) $maps[$name]['has_way'] = true;
            elseif (preg_match('/_goals\.gm$/', $f)) $maps[$name]['has_goals'] = true;
            else $maps[$name]['has_gm'] = true;
        }
        foreach ($maps as &$m) $m['file_count'] = count($m['files']);
        ksort($maps);

        $index = [
            'maps' => array_values($maps),
            'last_sync' => date('c'),
            'svn_revision' => $this->getCurrentRevision(),
            'source' => 'https://github.com/' . $this->githubRepo,
        ];
        file_put_contents($this->indexPath($game), json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Push new waypoint files to GitHub
     */
    protected function pushToGitHub(array $newMapNames, string $game = 'et'): void
    {
        if (empty($this->githubToken) || empty($this->githubRepo)) return;

        $navPath = $this->navPath($game);
        $gitDir = $game === 'rtcw' ? 'rtcw/nav' : 'et/nav';

        foreach ($newMapNames as $mapName) {
            $extensions = ['.way', '.gm', '_goals.gm'];
            foreach ($extensions as $ext) {
                $fileName = $mapName . $ext;
                $filePath = $navPath . '/' . $fileName;
                if (!file_exists($filePath)) continue;

                $content = base64_encode(file_get_contents($filePath));
                $apiUrl = "https://api.github.com/repos/{$this->githubRepo}/contents/{$gitDir}/{$fileName}";

                try {
                    $response = Http::withToken($this->githubToken)
                        ->put($apiUrl, [
                            'message' => "Add {$game} waypoints: {$mapName}",
                            'content' => $content,
                        ]);

                    if ($response->successful()) {
                        Log::info("GitHub push: {$gitDir}/{$fileName}");
                    } else {
                        Log::warning("GitHub push failed: {$fileName} - " . $response->body());
                    }
                } catch (\Exception $e) {
                    Log::warning("GitHub push error: {$fileName} - " . $e->getMessage());
                }
            }
        }

        $this->createGitHubRelease($game, $newMapNames);
    }

    /**
     * Create a GitHub release
     */
    protected function createGitHubRelease(string $game, array $newMaps): void
    {
        $rev = $this->incrementRevision();
        $apiUrl = "https://api.github.com/repos/{$this->githubRepo}/releases";
        $mapList = implode(', ', array_slice($newMaps, 0, 10));
        if (count($newMaps) > 10) $mapList .= ' +' . (count($newMaps) - 10) . ' more';

        try {
            Http::withToken($this->githubToken)->post($apiUrl, [
                'tag_name' => "r{$rev}",
                'name' => "r{$rev} - " . strtoupper($game) . " waypoints",
                'body' => count($newMaps) . " new " . strtoupper($game) . " maps: {$mapList}\n\nAutomated sync from https://wolffiles.eu",
                'draft' => false,
                'prerelease' => false,
            ]);
        } catch (\Exception $e) {
            Log::warning("GitHub release error: " . $e->getMessage());
        }
    }

    /**
     * Pull latest from GitHub repo
     */
    public function pullFromGitHub(): array
    {
        $repoPath = '/tmp/omnibot-export';
        $results = ['new_et' => 0, 'new_rtcw' => 0];

        if (!is_dir($repoPath)) {
            exec("git clone https://{$this->githubToken}@github.com/{$this->githubRepo}.git {$repoPath} 2>&1", $output);
        } else {
            exec("cd {$repoPath} && git pull 2>&1", $output);
        }

        // Sync ET
        $etNav = $this->navPath('et');
        if (is_dir("{$repoPath}/et/nav")) {
            foreach (scandir("{$repoPath}/et/nav") as $f) {
                if ($f === '.' || $f === '..') continue;
                if (!file_exists("{$etNav}/{$f}")) {
                    copy("{$repoPath}/et/nav/{$f}", "{$etNav}/{$f}");
                    $results['new_et']++;
                }
            }
        }

        // Sync RTCW
        $rtcwNav = $this->navPath('rtcw');
        if (is_dir("{$repoPath}/rtcw/nav")) {
            foreach (scandir("{$repoPath}/rtcw/nav") as $f) {
                if ($f === '.' || $f === '..') continue;
                if (!file_exists("{$rtcwNav}/{$f}")) {
                    copy("{$repoPath}/rtcw/nav/{$f}", "{$rtcwNav}/{$f}");
                    $results['new_rtcw']++;
                }
            }
        }

        if ($results['new_et'] > 0) $this->rebuildIndex('et');
        if ($results['new_rtcw'] > 0) $this->rebuildIndex('rtcw');

        return $results;
    }

    protected function getCurrentRevision(): string
    {
        $revFile = $this->basePath . '/revision.txt';
        return file_exists($revFile) ? trim(file_get_contents($revFile)) : '4035';
    }

    protected function incrementRevision(): string
    {
        $revFile = $this->basePath . '/revision.txt';
        $current = intval($this->getCurrentRevision());
        $next = $current + 1;
        file_put_contents($revFile, $next);
        return (string)$next;
    }
}
