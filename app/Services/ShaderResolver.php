<?php

namespace App\Services;

use Throwable;
use App\Services\GameAssetMapper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Parses Q3-engine .shader files and resolves shader-name references to
 * the underlying texture asset.
 *
 * Q3 BSPs reference logical "shader names" like:
 *     textures/et_austria_lights/light_xlight_1000
 *
 * The shader name does NOT map to a file. Instead, a .shader file contains:
 *
 *     textures/et_austria_lights/light_xlight_1000
 *     {
 *         qer_editorimage textures/effects/light_glow.jpg
 *         {
 *             map textures/effects/xlight_1000.tga
 *             ...
 *         }
 *     }
 *
 * We pick the first `map` directive in the first stage block, or fall back
 * to qer_editorimage. This is "good enough for 80%" — full shader semantics
 * (animMap, multi-stage blending, alphaGen, etc.) would require a real
 * shader renderer in the viewer JS.
 *
 * Caching strategy:
 *   - Per file_id: parse all shaders in S3 (bsp/{id}/assets/scripts/) ONCE,
 *     cache the resulting map in Redis for 1 hour.
 *   - Per game: parse pool's scripts/ folder ONCE, cache for 24 hours.
 *
 * On miss, returns null - caller falls through to placeholder.
 */
class ShaderResolver
{
    private const MAP_CACHE_TTL = 3600;
    private const POOL_CACHE_TTL = 86400;

    /**
     * Resolve a shader-name path to a real texture path.
     *
     * @return string|null  Resolved texture path (without extension), or null
     */
    public function resolve(int $fileId, string $path, ?string $game): ?string
    {
        // Strip extension from requested path
        $stem = preg_replace('/\.[^.\/]+$/', '', $path);

        // 1. Try map-specific shaders
        $mapShaders = $this->loadMapShaders($fileId);
        if (isset($mapShaders[$stem])) {
            return $mapShaders[$stem];
        }

        // 2. Try game pool shaders
        $poolShaders = $this->loadPoolShaders($game);
        if (isset($poolShaders[$stem])) {
            return $poolShaders[$stem];
        }

        return null;
    }

    /**
     * Load and parse all shader files in S3 for a given file.
     */
    private function loadMapShaders(int $fileId): array
    {
        return Cache::remember(
            "shaders:map:{$fileId}",
            self::MAP_CACHE_TTL,
            function () use ($fileId) {
                try {
                    $s3 = Storage::disk('s3');
                    $files = $s3->allFiles("bsp/{$fileId}/assets/scripts");
                    $merged = [];
                    foreach ($files as $f) {
                        if (!str_ends_with(strtolower($f), '.shader')) continue;
                        $content = $s3->get($f);
                        if ($content === null) continue;
                        $merged = array_merge($merged, $this->parseShaderFile($content));
                    }
                    return $merged;
                } catch (Throwable $e) {
                    Log::warning("ShaderResolver loadMapShaders failed [{$fileId}]: {$e->getMessage()}");
                    return [];
                }
            }
        );
    }

    /**
     * Load and parse all shader files in the game's local pool.
     */
    private function loadPoolShaders(?string $game): array
    {
        $folder = GameAssetMapper::folderFor($game);
        if (!$folder) return [];

        return Cache::remember(
            "shaders:pool:{$folder}",
            self::POOL_CACHE_TTL,
            function () use ($folder) {
                $dir = public_path($folder . '/scripts');
                if (!is_dir($dir)) return [];

                $merged = [];
                $files = glob($dir . '/*.shader');
                if (!$files) return [];

                foreach ($files as $f) {
                    $content = @file_get_contents($f);
                    if ($content === false) continue;
                    $merged = array_merge($merged, $this->parseShaderFile($content));
                }
                return $merged;
            }
        );
    }

    /**
     * Parse a single .shader file. Returns [shaderName => texturePath].
     *
     * Q3 shader grammar (simplified):
     *
     *     shaderName
     *     {
     *         // outer directives (qer_editorimage, surfaceparm, ...)
     *         {
     *             map textures/effects/glow.tga
     *             // ...stage stuff
     *         }
     *         {
     *             map ...
     *         }
     *     }
     */
    private function parseShaderFile(string $content): array
    {
        // Strip C-style comments
        $content = preg_replace('!/\*.*?\*/!s', '', $content);
        // Strip // line comments
        $content = preg_replace('!//[^\n]*!', '', $content);

        $shaders = [];
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $i = 0;
        $n = count($lines);

        while ($i < $n) {
            $line = trim($lines[$i]);
            $i++;
            if ($line === '' || $line[0] === '{' || $line[0] === '}') continue;

            // Should be a shader name. Next non-empty line should be `{`.
            $shaderName = $line;
            // Find opening brace
            while ($i < $n && trim($lines[$i]) === '') $i++;
            if ($i >= $n || trim($lines[$i]) !== '{') {
                continue; // malformed, skip
            }
            $i++; // consume `{`

            $depth = 1;
            $editorImage = null;
            $firstStageMap = null;

            while ($i < $n && $depth > 0) {
                $l = trim($lines[$i]);
                $i++;
                if ($l === '') continue;

                if ($l === '{') { $depth++; continue; }
                if ($l === '}') { $depth--; continue; }

                // Outer-level directive: qer_editorimage
                if ($depth === 1) {
                    if (preg_match('/^qer_editorimage\s+(\S+)/i', $l, $m)) {
                        $editorImage = $m[1];
                    }
                    if (preg_match('/^skyparms\s+(\S+)/i', $l, $m)) {
                        // skyparms references a skybox cubemap base path
                        // Use it as the resolved name (SkyboxResolver will then handle 6-face logic)
                        $editorImage = $editorImage ?? $m[1];
                    }
                }
                // Stage-level (depth=2): first `map` directive wins
                if ($depth === 2 && $firstStageMap === null) {
                    if (preg_match('/^(?:clamp)?map\s+(\S+)/i', $l, $m)) {
                        $tex = $m[1];
                        // Ignore special tokens like $lightmap, $whiteimage
                        if ($tex[0] !== '$') {
                            $firstStageMap = $tex;
                        }
                    }
                    if (preg_match('/^animmap\s+\S+\s+(\S+)/i', $l, $m)) {
                        // animMap <freq> <tex1> <tex2> ... -> take first texture
                        $firstStageMap = $m[1];
                    }
                }
            }

            // Prefer stage map, fall back to editor image
            $resolved = $firstStageMap ?? $editorImage;
            if ($resolved !== null) {
                // Normalize: strip extension so caller can append .jpg/.png/.tga
                $resolved = preg_replace('/\.[^.\/]+$/', '', $resolved);
                $stem = preg_replace('/\.[^.\/]+$/', '', $shaderName);
                $shaders[$stem] = $resolved;
            }
        }

        return $shaders;
    }
}
