<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\MissingTexture;
use App\Services\GameAssetMapper;
use Illuminate\Http\Request;
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
 *   3. Placeholder PNG (HTTP 200, X-Texture-Missing: 1) + missing_textures log
 */
class TexProxyController extends Controller
{
    /** Cache TTLs in seconds */
    private const HIT_CACHE_TTL = 604800;      // 1 week for found textures
    private const MISS_LOG_DEDUP_TTL = 86400;  // log each (file,path) miss only once per day

    private const MIME = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'tga'  => 'image/x-tga',
        'webp' => 'image/webp',
    ];

    public function __invoke(int $fileId, string $path)
    {
        // Path safety
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

        // 3. Log + Placeholder
        $this->logMissing($fileId, $path);
        return $this->placeholder();
    }

    private function tryS3(int $fileId, string $path): ?Response
    {
        try {
            $s3 = Storage::disk('s3');
            $s3Path = "bsp/{$fileId}/assets/{$path}";

            if (!$s3->exists($s3Path)) {
                // Case-insensitive fallback (slow path)
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
                if (!$found) {
                    return null;
                }
                $s3Path = $found;
            }

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = self::MIME[$ext] ?? 'application/octet-stream';

            return response($s3->get($s3Path), 200, [
                'Content-Type'  => $mime,
                'Cache-Control' => 'public, max-age=' . self::HIT_CACHE_TTL,
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
        if (!$fullPath) {
            return null;
        }

        // Try exact path first
        $resolved = is_file($fullPath) ? $fullPath : null;

        // Extension probing: textures often referenced without .jpg suffix
        // or as .jpg but only .png/.tga exists
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

        if (!$resolved) {
            return null;
        }

        $ext = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
        $mime = self::MIME[$ext] ?? 'application/octet-stream';

        return response()->file($resolved, [
            'Content-Type'  => $mime,
            'Cache-Control' => 'public, max-age=' . self::HIT_CACHE_TTL,
            'X-Texture-Source' => 'pool-' . GameAssetMapper::folderFor($game),
        ]);
    }

    private function placeholder(): BinaryFileResponse
    {
        return response()->file(public_path('img/missing-texture.png'), [
            'Content-Type'      => 'image/png',
            'Cache-Control'     => 'public, max-age=300', // short cache so re-resolve works after upload
            'X-Texture-Missing' => '1',
        ]);
    }

    private function logMissing(int $fileId, string $path): void
    {
        // Dedup: only DB-touch once per (file,path) per day
        $cacheKey = "miss:{$fileId}:" . sha1($path);
        if (Cache::has($cacheKey)) {
            return;
        }
        Cache::put($cacheKey, 1, self::MISS_LOG_DEDUP_TTL);

        try {
            $game = $this->resolveGameForFile($fileId);
            $row = MissingTexture::firstOrNew(
                ['file_id' => $fileId, 'texture_path' => $path]
            );
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
}
