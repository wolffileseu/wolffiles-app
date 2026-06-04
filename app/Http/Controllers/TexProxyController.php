<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\MissingTexture;
use App\Services\GameAssetMapper;
use App\Services\ShaderResolver;
use App\Services\SkyboxResolver;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves texture files for the 3D BSP viewer with a layered fallback chain.
 *
 * Fallback order:
 *   1. S3 map-specific assets: bsp/{file_id}/assets/{path} (case-insensitive)
 *   2. Game-specific local asset pool: public/{et|rtcw}-assets/{path}
 *      with extension probing (.jpg .png .tga)
 *   3. Skybox cubemap detection: try {path}_ft.jpg etc. (Q3 sky convention)
 *   4. Shader resolver: parse .shader files, map shader-name to real texture
 *   5. Placeholder PNG (HTTP 200, X-Texture-Missing: 1) + missing_textures log
 */
class TexProxyController extends Controller
{
    private const HIT_CACHE_TTL = 604800;      // 1 week for found textures
    private const MISS_LOG_DEDUP_TTL = 86400;  // log each (file,path) miss only once per day

    private const MIME = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'tga'  => 'image/x-tga',
        'webp' => 'image/webp',
    ];

    public function __construct(
        private SkyboxResolver $skyboxResolver,
        private ShaderResolver $shaderResolver,
    ) {}

    public function __invoke(int $fileId, string $path)
    {
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            abort(400, 'invalid path');
        }
        $path = ltrim($path, '/');

        // 1. S3 (map-specific)
        if ($response = $this->tryS3($fileId, $path)) {
            return $response;
        }

        // 2. Game pool
        if ($response = $this->tryGamePool($fileId, $path)) {
            return $response;
        }

        $game = $this->resolveGameForFile($fileId);

        // 3. Skybox cubemap detection (returns one of the 6 faces)
        if ($response = $this->trySkybox($fileId, $path, $game)) {
            return $response;
        }

        // 4. Shader resolver (parses .shader files)
        if ($response = $this->tryShader($fileId, $path, $game)) {
            return $response;
        }

        // 5. Fuzzy-Resolver: Format-/Schreibweise-tolerant (jpg/png/tga; tga->png)
        if ($response = $this->tryFuzzy($fileId, $path)) {
            $this->autoResolveMiss($fileId, $path, 'fuzzy');
            return $response;
        }

        // 6. Placeholder + log
        $this->logMissing($fileId, $path, $game);
        return $this->placeholder();
    }

    private function tryS3(int $fileId, string $path): ?Response
    {
        try {
            $s3 = Storage::disk('s3');
            $s3Path = "bsp/{$fileId}/assets/{$path}";

            if (!$s3->exists($s3Path)) {
                $allFiles = $s3->allFiles("bsp/{$fileId}/assets");
                $searchPath = strtolower($path);
                $found = null;
                foreach ($allFiles as $f) {
                    $relative = substr($f, strlen("bsp/{$fileId}/assets/"));
                    if (strtolower($relative) === $searchPath) {
                        $found = $f;
                        break;
                    }
                }
                if (!$found) return null;
                $s3Path = $found;
            }

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = self::MIME[$ext] ?? 'application/octet-stream';

            $this->autoResolveMiss($fileId, $path, 's3');


            return response($s3->get($s3Path), 200, [
                'Content-Type'     => $mime,
                'Cache-Control'    => 'public, max-age=' . self::HIT_CACHE_TTL,
                'X-Texture-Source' => 's3',
            ]);
        } catch (\Throwable $e) {
            Log::warning("TexProxy S3 lookup failed [{$fileId}/{$path}]: {$e->getMessage()}");
            return null;
        }
    }

    private function tryGamePool(int $fileId, string $path): ?BinaryFileResponse
    {
        $game = $this->resolveGameForFile($fileId);

        $fullPath = GameAssetMapper::filesystemPath($game, $path);
        if (!$fullPath) return null;

        $resolved = is_file($fullPath) ? $fullPath : null;

        if (!$resolved) {
            $base = preg_replace('/\.[^.\/]+$/', '', $fullPath);
            foreach (['jpg', 'png', 'tga'] as $ext) {
                $try = $base . '.' . $ext;
                if (is_file($try)) {
                    $resolved = $try;
                    break;
                }
            }
        }

        if (!$resolved) return null;

        $ext = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
        $this->autoResolveMiss($fileId, $path, 'pool-');

        if ($ext === 'tga') {
            return $this->serveImageData(file_get_contents($resolved), 'tga', $resolved, 'pool-' . GameAssetMapper::folderFor($game));
        }

        $mime = self::MIME[$ext] ?? 'application/octet-stream';
        return response()->file($resolved, [
            'Content-Type'     => $mime,
            'Cache-Control'    => 'public, max-age=' . self::HIT_CACHE_TTL,
            'X-Texture-Source' => 'pool-' . GameAssetMapper::folderFor($game),
        ]);
    }

    private function tryFuzzy(int $fileId, string $path): ?Response
    {
        $pathNoExt = strtolower(preg_replace('/\\.[^.\\/]+$/', '', $path));
        $exts = ['png', 'jpg', 'jpeg', 'tga'];

        // a) S3 (map-spezifisch) – Endung & Schreibweise egal
        try {
            $s3 = Storage::disk('s3');
            $prefix = "bsp/{$fileId}/assets/";
            foreach ($s3->allFiles("bsp/{$fileId}/assets") as $f) {
                $rel = substr($f, strlen($prefix));
                $relNoExt = strtolower(preg_replace('/\\.[^.\\/]+$/', '', $rel));
                $relExt = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
                if ($relNoExt === $pathNoExt && in_array($relExt, $exts, true)) {
                    $data = $s3->get($f);
                    if ($data === null) continue;
                    return $this->serveImageData($data, $relExt, $f, 's3-fuzzy');
                }
            }
        } catch (\Throwable $e) {
            Log::warning("TexProxy fuzzy S3 [{$fileId}/{$path}]: {$e->getMessage()}");
        }

        // b) Game-Pool (Basis-Texturen) – case-insensitive
        try {
            $game = $this->resolveGameForFile($fileId);
            $fullPath = GameAssetMapper::filesystemPath($game, $path);
            if ($fullPath) {
                $dir = dirname($fullPath);
                $baseLower = strtolower(pathinfo($fullPath, PATHINFO_FILENAME));
                if (is_dir($dir)) {
                    foreach (scandir($dir) as $entry) {
                        if ($entry === '.' || $entry === '..') continue;
                        $entryNoExt = strtolower(pathinfo($entry, PATHINFO_FILENAME));
                        $entryExt = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
                        if ($entryNoExt === $baseLower && in_array($entryExt, $exts, true)) {
                            $abs = $dir . '/' . $entry;
                            return $this->serveImageData(file_get_contents($abs), $entryExt, $abs, 'pool-fuzzy');
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("TexProxy fuzzy pool [{$fileId}/{$path}]: {$e->getMessage()}");
        }

        return null;
    }

    private function serveImageData(string $data, string $ext, string $cacheKey, string $source): Response
    {
        if ($ext === 'tga') {
            $png = $this->tgaToPngCached($cacheKey, $data);
            if ($png !== null) {
                return response($png, 200, [
                    'Content-Type'     => 'image/png',
                    'Cache-Control'    => 'public, max-age=' . self::HIT_CACHE_TTL,
                    'X-Texture-Source' => $source . '-tga2png',
                ]);
            }
        }
        $mime = self::MIME[$ext] ?? 'application/octet-stream';
        return response($data, 200, [
            'Content-Type'     => $mime,
            'Cache-Control'    => 'public, max-age=' . self::HIT_CACHE_TTL,
            'X-Texture-Source' => $source,
        ]);
    }

    private function tgaToPngCached(string $cacheKey, string $tgaData): ?string
    {
        $cacheFile = storage_path('app/texcache/' . md5($cacheKey) . '.png');
        if (!is_file($cacheFile)) {
            @mkdir(dirname($cacheFile), 0775, true);
            $tmp = tempnam(sys_get_temp_dir(), 'tga');
            file_put_contents($tmp, $tgaData);
            @exec('convert ' . escapeshellarg($tmp) . ' PNG32:' . escapeshellarg($cacheFile) . ' 2>/dev/null', $o, $rc);
            if (($rc ?? 1) !== 0 || !is_file($cacheFile)) {
                @exec('ffmpeg -y -i ' . escapeshellarg($tmp) . ' -pix_fmt rgba -frames:v 1 ' . escapeshellarg($cacheFile) . ' 2>/dev/null');
            }
            @unlink($tmp);
            if (!is_file($cacheFile)) return null;
        }
        $bytes = @file_get_contents($cacheFile);
        return $bytes !== false ? $bytes : null;
    }

    private function trySkybox(int $fileId, string $path, ?string $game)
    {
        $result = $this->skyboxResolver->resolve($fileId, $path, $game);
        if (!$result) return null;

        $mime = self::MIME[$result['ext']] ?? 'application/octet-stream';

        if ($result['source'] === 's3') {
            $data = Storage::disk('s3')->get($result['path']);
            if ($data === null) return null;
            $this->autoResolveMiss($fileId, $path, 'skybox-s3');

            return response($data, 200, [
                'Content-Type'     => $mime,
                'Cache-Control'    => 'public, max-age=' . self::HIT_CACHE_TTL,
                'X-Texture-Source' => 'skybox-s3',
            ]);
        }

        $this->autoResolveMiss($fileId, $path, 'skybox-pool');


        return response()->file($result['path'], [
            'Content-Type'     => $mime,
            'Cache-Control'    => 'public, max-age=' . self::HIT_CACHE_TTL,
            'X-Texture-Source' => 'skybox-pool',
        ]);
    }

    private function tryShader(int $fileId, string $path, ?string $game)
    {
        $resolvedStem = $this->shaderResolver->resolve($fileId, $path, $game);
        if (!$resolvedStem) return null;

        $resolvedStem = ltrim($resolvedStem, '/');
        $requestStem  = preg_replace('/\.[^.\/]+$/', '', $path);
        $isSelfRef    = (strtolower($resolvedStem) === strtolower($requestStem));

        // Try S3 + pool with all common extensions (case-insensitive on S3)
        $candidates = [];
        foreach (['jpg', 'jpeg', 'png', 'tga'] as $ext) {
            $candidates[] = $resolvedStem . '.' . $ext;
        }

        // 1) S3 case-insensitive lookup
        try {
            $s3 = Storage::disk('s3');
            $assets = Cache::remember(
                "s3-list:{$fileId}",
                300,
                fn () => $s3->allFiles("bsp/{$fileId}/assets")
            );
            $lowerMap = [];
            foreach ($assets as $f) {
                $rel = substr($f, strlen("bsp/{$fileId}/assets/"));
                $lowerMap[strtolower($rel)] = $f;
            }

            foreach ($candidates as $cand) {
                $key = strtolower($cand);
                if (isset($lowerMap[$key])) {
                    $ext = strtolower(pathinfo($lowerMap[$key], PATHINFO_EXTENSION));
                    $mime = self::MIME[$ext] ?? 'application/octet-stream';
                    return response($s3->get($lowerMap[$key]), 200, [
                        'Content-Type'     => $mime,
                        'Cache-Control'    => 'public, max-age=' . self::HIT_CACHE_TTL,
                        'X-Texture-Source' => $isSelfRef ? 'shader-s3-selfref' : 'shader-s3',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // continue to pool
        }

        // 2) Game pool with extension probing
        foreach ($candidates as $cand) {
            $full = GameAssetMapper::filesystemPath($game, $cand);
            if ($full && is_file($full)) {
                $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
                $mime = self::MIME[$ext] ?? 'application/octet-stream';
                return response()->file($full, [
                    'Content-Type'     => $mime,
                    'Cache-Control'    => 'public, max-age=' . self::HIT_CACHE_TTL,
                    'X-Texture-Source' => $isSelfRef ? 'shader-pool-selfref' : 'shader-pool',
                ]);
            }
        }

        return null;
    }

    private function placeholder(): BinaryFileResponse
    {
        return response()->file(public_path('img/missing-texture.png'), [
            'Content-Type'      => 'image/png',
            'Cache-Control'     => 'public, max-age=300',
            'X-Texture-Missing' => '1',
        ]);
    }

    private function logMissing(int $fileId, string $path, ?string $game): void
    {
        $cacheKey = "miss:{$fileId}:" . sha1($path);
        if (Cache::has($cacheKey)) return;
        Cache::put($cacheKey, 1, self::MISS_LOG_DEDUP_TTL);

        try {
            $row = MissingTexture::firstOrNew(['file_id' => $fileId, 'texture_path' => $path]);
            if (!$row->exists) {
                $row->game = $game;
                $row->first_seen_at = now();
                $row->request_count = 1;
            } else {
                $row->request_count = $row->request_count + 1;
            }
            $row->last_seen_at = now();
            $row->save();
        } catch (\Throwable $e) {
            Log::warning("TexProxy miss-log failed [{$fileId}/{$path}]: {$e->getMessage()}");
        }
    }

    private function resolveGameForFile(int $fileId): ?string
    {
        return Cache::remember("file-game:{$fileId}", 3600, function () use ($fileId) {
            return File::where('id', $fileId)->value('game');
        });
    }

    /**
     * If a previously-logged MissingTexture entry exists for this (file,path),
     * mark it as resolved with a note about which fallback succeeded.
     */
    private function autoResolveMiss(int $fileId, string $path, string $sourceLabel): void
    {
        try {
            $m = \App\Models\MissingTexture::where('file_id', $fileId)
                ->where('texture_path', $path)
                ->where('resolved', false)
                ->first();
            if ($m) {
                $m->resolved = true;
                $m->notes = trim(($m->notes ?? '') . ' [auto-resolved via ' . $sourceLabel . ' on ' . now()->toDateTimeString() . ']');
                $m->save();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("TexProxy auto-resolve failed [{$fileId}/{$path}]: {$e->getMessage()}");
        }
    }
}
