<?php

namespace App\Services;

use Throwable;
use App\Models\File;
use App\Models\MissingTexture;
use App\Services\GameAssetMapper;
use App\Services\ShaderResolver;
use App\Services\SkyboxResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Probes whether a previously-missing texture can now be resolved via any of
 * the fallback chains (S3 / pool / skybox / shader). Used to mark old
 * MissingTexture entries as resolved after pool updates or asset uploads.
 */
class TextureResolutionChecker
{
    public function __construct(
        private SkyboxResolver $skybox,
        private ShaderResolver $shader,
    ) {}

    public function canResolve(int $fileId, string $path, ?string $game = null): ?string
    {
        $game ??= Cache::remember("file-game:{$fileId}", 3600,
            fn () => File::where("id", $fileId)->value("game"));

        if ($this->existsInS3($fileId, $path)) return "s3";
        if ($this->existsInPool($game, $path))  return "pool";
        if ($this->skybox->resolve($fileId, $path, $game)) return "skybox";

        $shaderStem = $this->shader->resolve($fileId, $path, $game);
        if ($shaderStem) {
            // verify it actually points to existing file
            foreach (["jpg","jpeg","png","tga"] as $ext) {
                if ($this->existsInS3($fileId, $shaderStem . "." . $ext)) return "shader-s3";
                if ($this->existsInPool($game, $shaderStem . "." . $ext)) return "shader-pool";
            }
        }
        return null;
    }

    public function recheckMissingTexture(MissingTexture $m): bool
    {
        $source = $this->canResolve($m->file_id, $m->texture_path, $m->game);
        if ($source !== null) {
            $m->resolved = true;
            $m->notes = trim(($m->notes ?? "") . " [auto-resolved via " . $source . " on " . now()->toDateTimeString() . "]");
            $m->save();
            return true;
        }
        return false;
    }

    /**
     * Bulk recheck. Returns ["checked" => N, "resolved" => M].
     */
    public function recheckAll(?int $fileId = null): array
    {
        $query = MissingTexture::where("resolved", false);
        if ($fileId !== null) $query->where("file_id", $fileId);

        $checked = 0; $resolved = 0;
        $query->chunkById(200, function ($batch) use (&$checked, &$resolved) {
            foreach ($batch as $m) {
                $checked++;
                if ($this->recheckMissingTexture($m)) $resolved++;
            }
        });
        return ["checked" => $checked, "resolved" => $resolved];
    }

    private function existsInS3(int $fileId, string $path): bool
    {
        try {
            $s3 = Storage::disk("s3");
            $assets = Cache::remember(
                "s3-list:{$fileId}",
                300,
                fn () => $s3->allFiles("bsp/{$fileId}/assets")
            );
            $key = strtolower($path);
            foreach ($assets as $f) {
                $rel = substr($f, strlen("bsp/{$fileId}/assets/"));
                if (strtolower($rel) === $key) return true;
            }
            return false;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function existsInPool(?string $game, string $path): bool
    {
        $full = GameAssetMapper::filesystemPath($game, $path);
        if (!$full) return false;
        if (is_file($full)) return true;

        $base = preg_replace("/\\.[^.\/]+$/", "", $full);
        foreach (["jpg","jpeg","png","tga"] as $ext) {
            if (is_file($base . "." . $ext)) return true;
        }
        return false;
    }
}
