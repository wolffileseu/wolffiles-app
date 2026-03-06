<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ZipArchive;

class OmnibotController extends Controller
{
    public function index(Request $request)
    {
        $game = $request->get('game', 'et');
        if (!in_array($game, ['et', 'rtcw'])) $game = 'et';

        $index = $this->getIndex($game);
        $maps = collect($index['maps'] ?? [])->sortBy('name')->values();

        $etIndex = $this->getIndex('et');
        $rtcwIndex = $this->getIndex('rtcw');

        return view('frontend.tools.omnibot', [
            'maps' => $maps,
            'game' => $game,
            'totalMaps' => count($index['maps'] ?? []),
            'totalComplete' => collect($index['maps'] ?? [])->where('status', 'complete')->count(),
            'totalIncomplete' => collect($index['maps'] ?? [])->where('status', 'incomplete')->count(),
            'etCount' => count($etIndex['maps'] ?? []),
            'rtcwCount' => count($rtcwIndex['maps'] ?? []),
            'lastSync' => $index['last_sync'] ?? null,
            'svnRevision' => $index['svn_revision'] ?? 'unknown',
            'seo' => [
                'title' => __('messages.ob_seo_title'),
                'description' => __('messages.ob_seo_description'),
                'canonical' => route('tools.omnibot'),
            ],
        ]);
    }

    public function download(Request $request, string $mapName)
    {
        $mapName = urldecode($mapName);
        $game = $request->get('game', 'et');
        if (!in_array($game, ['et', 'rtcw'])) $game = 'et';

        $index = $this->getIndex($game);
        $map = collect($index['maps'] ?? [])->firstWhere('name', $mapName);
        if (!$map) abort(404);

        $basePath = storage_path("app/omnibot/{$game}/nav");

        $files = [];
        foreach ($map['files'] as $file) {
            // Prevent path traversal - only allow basenames
            $file = basename($file);			
            $fp = $basePath . '/' . $file;
            $realPath = realpath($fp);
            if ($realPath && str_starts_with($realPath, realpath($basePath) . DIRECTORY_SEPARATOR) && file_exists($fp)) {
                $files[] = $fp;
            }
        }
        if (empty($files)) abort(404);

        $safeName = preg_replace('/[^a-z0-9_\-]/', '_', $mapName);
        $zipPath = storage_path('app/temp/omnibot_' . $game . '_' . $safeName . '.zip');
        if (!is_dir(storage_path('app/temp'))) mkdir(storage_path('app/temp'), 0755, true);

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($files as $f) $zip->addFile($f, 'nav/' . basename($f));
        $zip->close();

        return response()->download($zipPath, "omnibot_{$game}_{$safeName}.zip")->deleteFileAfterSend(true);
    }

    public function downloadAll(Request $request)
    {
        $game = $request->get('game', 'et');
        if (!in_array($game, ['et', 'rtcw'])) $game = 'et';

        $navPath = storage_path("app/omnibot/{$game}/nav");
        if (!is_dir($navPath)) abort(404);

        $zipPath = storage_path("app/temp/omnibot-{$game}-waypoints-full.zip");
        if (!is_dir(storage_path('app/temp'))) mkdir(storage_path('app/temp'), 0755, true);

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (scandir($navPath) as $f) {
            if ($f === '.' || $f === '..' || is_dir("$navPath/$f")) continue;
            $ext = pathinfo($f, PATHINFO_EXTENSION);
            if (in_array($ext, ['way', 'gm'])) {
                $zip->addFile("$navPath/$f", "nav/$f");
            }
        }
        $zip->close();

        return response()->download($zipPath, "omnibot-{$game}-waypoints-full.zip")->deleteFileAfterSend(true);
    }

    protected function getIndex(string $game): array
    {
        $file = $game === 'rtcw' ? 'rtcw-waypoint-index.json' : 'waypoint-index.json';
        $path = storage_path('app/omnibot/' . $file);
        if (!file_exists($path)) return ['maps' => [], 'last_sync' => null, 'svn_revision' => 'unknown'];
        return json_decode(file_get_contents($path), true) ?: [];
    }
}
