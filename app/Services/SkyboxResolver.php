<?php

namespace App\Services;

use Throwable;
use App\Services\GameAssetMapper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Detects Q3-engine skybox references and returns one of the 6 cube faces
 * as a flat texture fallback.
 *
 * Quake 3 / RtCW / ET skyboxes reference a "master" name like:
 *     textures/darkness_sky/darkness_sky
 *
 * The actual files on disk are 6 faces with suffixes:
 *     darkness_sky_bk, _dn, _ft, _lf, _rt, _up   (.jpg/.png/.tga)
 *
 * The BSP viewer fetches the master name with .jpg appended. We detect this,
 * verify the 6 faces exist (S3 or pool), and return the front face (_ft) as
 * a stand-in. Not a true cubemap render, but visually much better than the
 * missing-texture placeholder.
 */
class SkyboxResolver
{
    private const FACES = ['ft', 'bk', 'lf', 'rt', 'up', 'dn'];
    private const PREFERRED_FACE = 'ft'; // front face = most useful flat fallback
    private const CACHE_TTL = 3600;

    /**
     * Try to resolve a path that looks like a skybox master to an actual face file.
     *
     * @param int    $fileId  File ID for S3 lookups
     * @param string $path    Original requested path (e.g. "textures/darkness_sky/darkness_sky.jpg")
     * @param string|null $game  Game string for pool lookup
     * @return array|null  ['source' => 's3'|'pool', 'path' => string, 'ext' => string] or null
     */
    public function resolve(int $fileId, string $path, ?string $game): ?array
    {
        // Strip extension
        $stem = preg_replace('/\.[^.\/]+$/', '', $path);

        // Build the 6 candidate face paths
        $facePaths = [];
        foreach (self::FACES as $face) {
            $facePaths[$face] = $stem . '_' . $face;
        }

        // Check S3 first (case-insensitive)
        $cacheKey = "skybox:s3:{$fileId}:" . sha1($stem);
        $s3Result = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($fileId, $facePaths) {
            return $this->probeS3($fileId, $facePaths);
        });
        if ($s3Result) {
            return ['source' => 's3'] + $s3Result;
        }

        // Then check the game pool
        $poolResult = $this->probePool($game, $facePaths);
        if ($poolResult) {
            return ['source' => 'pool'] + $poolResult;
        }

        return null;
    }

    /**
     * Probe S3 for the 6 face files. Returns face data only if ALL 6 exist
     * (we want to know it's a real skybox, not just coincidental matches).
     */
    private function probeS3(int $fileId, array $facePaths): ?array
    {
        try {
            $s3 = Storage::disk('s3');
            $facesFound = [];

            // List all assets once (cached implicitly within request)
            $allFiles = $s3->allFiles("bsp/{$fileId}/assets");
            $lowerMap = [];
            foreach ($allFiles as $f) {
                $rel = substr($f, strlen("bsp/{$fileId}/assets/"));
                $base = preg_replace('/\.[^.\/]+$/', '', $rel);
                $lowerMap[strtolower($base)][] = ['full' => $f, 'rel' => $rel];
            }

            foreach ($facePaths as $face => $facePath) {
                $key = strtolower($facePath);
                if (!isset($lowerMap[$key])) {
                    return null; // Missing face - not a complete skybox
                }
                $facesFound[$face] = $lowerMap[$key][0];
            }

            // All 6 faces present - return the preferred one
            $chosen = $facesFound[self::PREFERRED_FACE] ?? reset($facesFound);
            return [
                'path' => $chosen['full'],
                'rel'  => $chosen['rel'],
                'ext'  => strtolower(pathinfo($chosen['rel'], PATHINFO_EXTENSION)),
            ];
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Probe the game asset pool for the 6 face files on local disk.
     */
    private function probePool(?string $game, array $facePaths): ?array
    {
        $facesFound = [];
        foreach ($facePaths as $face => $facePath) {
            $resolved = null;
            foreach (['jpg', 'png', 'tga'] as $ext) {
                $full = GameAssetMapper::filesystemPath($game, $facePath . '.' . $ext);
                if ($full && is_file($full)) {
                    $resolved = ['full' => $full, 'ext' => $ext];
                    break;
                }
            }
            if (!$resolved) {
                return null; // Missing face
            }
            $facesFound[$face] = $resolved;
        }

        $chosen = $facesFound[self::PREFERRED_FACE] ?? reset($facesFound);
        return [
            'path' => $chosen['full'],
            'ext'  => $chosen['ext'],
        ];
    }
}
