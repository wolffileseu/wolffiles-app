<?php

namespace App\Services;

use Throwable;
use RuntimeException;
use App\Models\MissingTexture;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Handles uploading replacement texture files for missing textures.
 *
 * Two destinations:
 *   - "pool": writes to public/{game}-assets/{path} (helps all maps)
 *   - "s3":   writes to bsp/{file_id}/assets/{path} on S3 (this map only)
 *
 * After upload, the corresponding MissingTexture row is marked resolved
 * and relevant caches are flushed.
 */
class MissingTextureUploader
{
    /**
     * Determine the smart-default destination for a missing texture.
     * If 2+ MissingTexture rows share this exact path → suggest pool.
     * Otherwise → suggest s3 (map-specific).
     */
    public function suggestDestination(MissingTexture $miss): string
    {
        $sharedCount = MissingTexture::where("texture_path", $miss->texture_path)
            ->where("game", $miss->game)
            ->where("id", "!=", $miss->id)
            ->count();
        return $sharedCount >= 1 ? "pool" : "s3";
    }

    /**
     * Upload a file to fix a missing texture.
     *
     * @param  MissingTexture  $miss
     * @param  UploadedFile    $file        The replacement texture
     * @param  string          $destination "pool" | "s3"
     * @return array{success: bool, target: string, error: ?string}
     */
    public function upload(MissingTexture $miss, UploadedFile $file, string $destination): array
    {
        try {
            $contents = file_get_contents($file->getRealPath());

            if ($destination === "pool") {
                $target = $this->writeToPool($miss, $contents);
            } else {
                $target = $this->writeToS3($miss, $contents);
            }

            // Mark resolved with audit note
            $miss->resolved = true;
            $note = "[uploaded to {$destination}: {$target} on " . now()->toDateTimeString() . "]";
            $miss->notes = trim(($miss->notes ?? "") . " " . $note);
            $miss->save();

            // Flush caches so the new file is served
            $this->flushCaches($miss);

            Log::info("MissingTextureUploader: uploaded {$miss->texture_path} -> {$target}");

            return ["success" => true, "target" => $target, "error" => null];
        } catch (Throwable $e) {
            Log::error("MissingTextureUploader failed: {$e->getMessage()}", [
                "miss_id" => $miss->id,
                "destination" => $destination,
            ]);
            return ["success" => false, "target" => "", "error" => $e->getMessage()];
        }
    }

    private function writeToPool(MissingTexture $miss, string $contents): string
    {
        $folder = GameAssetMapper::folderFor($miss->game);
        if (!$folder) {
            throw new RuntimeException("No pool folder for game={$miss->game}");
        }

        $path = ltrim($miss->texture_path, "/");
        if (str_contains($path, "..")) {
            throw new RuntimeException("Invalid path");
        }

        $full = public_path("{$folder}/{$path}");
        $dir = dirname($full);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_put_contents($full, $contents) === false) {
            throw new RuntimeException("Failed to write {$full}");
        }
        @chown($full, "wolffiles.eu_lkiogmaiktl");
        @chgrp($full, "psacln");
        @chmod($full, 0644);

        return $full;
    }

    private function writeToS3(MissingTexture $miss, string $contents): string
    {
        $path = ltrim($miss->texture_path, "/");
        if (str_contains($path, "..")) {
            throw new RuntimeException("Invalid path");
        }

        $s3Path = "bsp/{$miss->file_id}/assets/{$path}";
        $s3 = Storage::disk("s3");
        $s3->put($s3Path, $contents, "public");

        return $s3Path;
    }

    /**
     * Resolve siblings: if the uploaded file would also fix OTHER missing
     * textures (same path, same game), mark them as resolved too.
     * Returns the count of additional resolved entries.
     */
    public function resolveSiblings(MissingTexture $miss, string $destination): int
    {
        // Pool uploads help all maps; S3 uploads only this file
        if ($destination !== "pool") return 0;

        $siblings = MissingTexture::where("texture_path", $miss->texture_path)
            ->where("game", $miss->game)
            ->where("id", "!=", $miss->id)
            ->where("resolved", false)
            ->get();

        foreach ($siblings as $s) {
            $s->resolved = true;
            $s->notes = trim(($s->notes ?? "") . " [auto-resolved via sibling pool-upload on " . now()->toDateTimeString() . "]");
            $s->save();
        }
        return $siblings->count();
    }

    private function flushCaches(MissingTexture $miss): void
    {
        Cache::forget("s3-list:{$miss->file_id}");

        $folder = GameAssetMapper::folderFor($miss->game);
        if ($folder) {
            Cache::forget("shaders:pool:{$folder}");
        }
        Cache::forget("shaders:map:{$miss->file_id}");
    }
}
