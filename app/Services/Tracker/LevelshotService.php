<?php

namespace App\Services\Tracker;

use App\Models\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class LevelshotService
{
    /**
     * Get levelshot URL for a map. Extracts from PK3 if needed.
     * Returns public URL or null.
     */
    public static function getUrl(string $mapName): ?string
    {
        $mapName = strtolower(trim($mapName));
        if (empty($mapName)) return null;

        // Check if already extracted on S3
        $s3Path = "levelshots/{$mapName}.jpg";
        $disk = Storage::disk('s3');

        if ($disk->exists($s3Path)) {
            return $disk->url($s3Path);
        }

        // Check cache for "not found" to avoid repeated lookups
        if (Cache::get("levelshot_missing:{$mapName}")) {
            return null;
        }

        // Try to extract from file
        $url = self::extractFromFile($mapName);

        if (!$url) {
            // Cache "not found" for 6 hours
            Cache::put("levelshot_missing:{$mapName}", true, 21600);
        }

        return $url;
    }

    /**
     * Extract levelshot from PK3/ZIP file and upload to S3.
     */
    private static function extractFromFile(string $mapName): ?string
    {
        // Try direct slug match first (most accurate)
        $file = File::where('status', 'approved')
            ->where(function ($q) use ($mapName) {
                $q->where('slug', $mapName)
                  ->orWhere('slug', str_replace('_', '-', $mapName));
            })
            ->first();

        // Try MapLinkService
        if (!$file) {
            $file = MapLinkService::findFile($mapName);
        }

        // Try tracker_maps table (manually linked)
        if (!$file) {
            $trackerMap = \App\Models\Tracker\TrackerMap::where('name_clean', $mapName)->whereNotNull('file_id')->first();
            if ($trackerMap) {
                $file = $trackerMap->file;
            }
        }

        if (!$file) return null;

        $disk = Storage::disk('s3');

        try {
            if (!$disk->exists($file->file_path)) return null;

            $tmpFile = tempnam(sys_get_temp_dir(), 'ls_');
            file_put_contents($tmpFile, $disk->get($file->file_path));

            $imageData = null;
            $ext = 'jpg';

            $zip = new ZipArchive();
            if ($zip->open($tmpFile) !== true) {
                unlink($tmpFile);
                return null;
            }

            // Collect all levelshot candidates from ZIP and nested PK3s
            $candidates = self::findAllLevelshots($zip, $mapName);

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_ends_with(strtolower($name), '.pk3')) {
                    $pk3Data = $zip->getFromIndex($i);
                    $pk3Tmp = tempnam(sys_get_temp_dir(), 'pk3_');
                    file_put_contents($pk3Tmp, $pk3Data);

                    $pk3 = new ZipArchive();
                    if ($pk3->open($pk3Tmp) === true) {
                        $candidates = array_merge($candidates, self::findAllLevelshots($pk3, $mapName));
                        $pk3->close();
                    }
                    unlink($pk3Tmp);
                }
            }

            $zip->close();
            unlink($tmpFile);

            if (empty($candidates)) return null;

            // Try each candidate, prefer JPG/PNG over TGA
            $imageData = null;
            foreach ($candidates as $candidate) {
                if ($candidate['ext'] === 'tga') {
                    $converted = self::convertTgaToJpg($candidate['data']);
                    if ($converted) {
                        $imageData = $converted;
                        break;
                    }
                } else {
                    $imageData = $candidate['data'];
                    break;
                }
            }

            if (!$imageData) return null;

            // Upload to S3
            $s3Path = "levelshots/{$mapName}.jpg";
            $disk->put($s3Path, $imageData, 'public');

            Log::info("Levelshot extracted for {$mapName}");
            return $disk->url($s3Path);

        } catch (\Exception $e) {
            Log::warning("Levelshot extraction failed for {$mapName}: {$e->getMessage()}");
            if (isset($tmpFile) && file_exists($tmpFile)) unlink($tmpFile);
            return null;
        }
    }

    /**
     * Find levelshot image inside a ZIP/PK3.
     */
    /**
     * Find all levelshot candidates, sorted by preference (JPG first).
     */
    private static function findAllLevelshots(ZipArchive $zip, string $mapName): array
    {
        $paths = [
            "levelshots/{$mapName}.jpg" => 1,
            "levelshots/{$mapName}_cc.jpg" => 2,
            "levelshots/{$mapName}.png" => 3,
            "levelshots/{$mapName}_cc.tga" => 4,
            "levelshots/{$mapName}.tga" => 5,
        ];

        $found = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = strtolower($zip->getNameIndex($i));
            if (isset($paths[$name])) {
                $data = $zip->getFromIndex($i);
                if (strlen($data) > 0) {
                    $found[] = [
                        'data' => $data,
                        'ext' => pathinfo($name, PATHINFO_EXTENSION),
                        'priority' => $paths[$name],
                    ];
                }
            }
        }

        usort($found, fn($a, $b) => $a['priority'] <=> $b['priority']);
        return $found;
    }

    /**
     * Convert TGA image data to JPG.
     */
    private static function convertTgaToJpg(string $tgaData): ?string
    {
        $tmpTga = tempnam(sys_get_temp_dir(), 'tga_');
        file_put_contents($tmpTga, $tgaData);

        try {
            // Try ImageMagick
            $tmpJpg = tempnam(sys_get_temp_dir(), 'jpg_');
            $cmd = "convert " . escapeshellarg($tmpTga) . " -quality 85 " . escapeshellarg($tmpJpg) . " 2>&1";
            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && file_exists($tmpJpg) && filesize($tmpJpg) > 0) {
                $data = file_get_contents($tmpJpg);
                unlink($tmpTga);
                unlink($tmpJpg);
                return $data;
            }

            // Fallback: try GD with imagecreatefromstring
            $img = @imagecreatefromstring($tgaData);
            if ($img) {
                ob_start();
                imagejpeg($img, null, 85);
                $data = ob_get_clean();
                imagedestroy($img);
                unlink($tmpTga);
                if (isset($tmpJpg)) unlink($tmpJpg);
                return $data;
            }

        } catch (\Exception $e) {
            Log::warning("TGA conversion failed: {$e->getMessage()}");
        }

        unlink($tmpTga);
        if (isset($tmpJpg) && file_exists($tmpJpg)) unlink($tmpJpg);
        return null;
    }
}
