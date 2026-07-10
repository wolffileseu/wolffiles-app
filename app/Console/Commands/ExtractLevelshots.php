<?php

namespace App\Console\Commands;

use Throwable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Imagick;
use ZipArchive;

/**
 * Extract map levelshots from .pk3 / .zip files stored on S3, resize to 320x180,
 * write to public/images/map-thumbs/{mapname}.jpg.
 *
 * Handles two layouts:
 *   A) file_extension='pk3' — read levelshots/ directly
 *   B) file_extension='zip' — extract inner .pk3 first, then read levelshots/
 *
 * Priority (per archive):
 *   1. levelshots/{mapname}.tga
 *   2. levelshots/{mapname}.jpg
 *   3. levelshots/{mapname}.png
 *
 * Usage:
 *   php artisan banner:extract-levelshots
 *   php artisan banner:extract-levelshots --force
 *   php artisan banner:extract-levelshots --map=river_port
 *   php artisan banner:extract-levelshots --limit=10
 *   php artisan banner:extract-levelshots --tracker-only
 */
class ExtractLevelshots extends Command
{
    protected $signature = 'banner:extract-levelshots
        {--force : Re-extract even if thumbnail exists}
        {--map= : Process only this specific map name}
        {--limit=0 : Process at most N maps (0 = no limit)}
        {--tracker-only : Only maps currently seen on the tracker}';

    protected $description = 'Extract map levelshots from .pk3/.zip on S3 into public/images/map-thumbs/';

    private string $thumbDir;
    private int $maxSize = 100 * 1024 * 1024; // skip files >100MB

    public function handle(): int
    {
        $this->thumbDir = public_path('images/map-thumbs');
        if (!is_dir($this->thumbDir)) {
            mkdir($this->thumbDir, 0755, true);
        }

        $query = DB::table('files')
            ->whereIn('file_extension', ['pk3', 'zip'])
            ->whereNotNull('map_name')
            ->whereNotNull('file_path');

        if ($map = $this->option('map')) {
            $query->where('map_name', $map);
        }

        if ($this->option('tracker-only')) {
            $trackerMaps = DB::table('tracker_servers')
                ->whereNotNull('current_map')
                ->distinct()
                ->pluck('current_map');
            $query->whereIn('map_name', $trackerMaps);
        }

        // One row per map_name — smallest archive wins
        $files = $query
            ->select(['id', 'map_name', 'file_name', 'file_extension', 'file_path', 'file_size'])
            ->orderBy('file_size')
            ->get()
            ->groupBy('map_name')
            ->map(fn ($group) => $group->first())
            ->values();

        if ($limit = (int) $this->option('limit')) {
            $files = $files->take($limit);
        }

        $this->info("Processing {$files->count()} maps...");

        $stats = [
            'ok'           => 0,
            'skip_exists'  => 0,
            'skip_size'    => 0,
            'no_pk3'       => 0,   // zip without inner pk3
            'no_levelshot' => 0,   // pk3 without levelshot
            'error'        => 0,
        ];

        $bar = $this->output->createProgressBar($files->count());
        $bar->setFormat(' %current%/%max% [%bar%] %message%');

        foreach ($files as $file) {
            $bar->setMessage($file->map_name);
            $bar->advance();

            $target = $this->thumbDir . '/' . $this->safeName($file->map_name) . '.jpg';

            if (!$this->option('force') && file_exists($target)) {
                $stats['skip_exists']++;
                continue;
            }

            if ((int) $file->file_size > $this->maxSize) {
                $stats['skip_size']++;
                continue;
            }

            try {
                $result = $this->extractOne($file, $target);
                $stats[$result]++;
            } catch (Throwable $e) {
                $this->newLine();
                $this->warn("  {$file->map_name}: " . $e->getMessage());
                $stats['error']++;
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Done.');
        $this->table(
            ['Result', 'Count'],
            collect($stats)->map(fn ($v, $k) => [$k, $v])->values()
        );

        return self::SUCCESS;
    }

    /**
     * Returns: 'ok' | 'no_pk3' | 'no_levelshot' | 'error'
     */
    private function extractOne(object $file, string $target): string
    {
        $localArchive = tempnam(sys_get_temp_dir(), 'arch_');

        try {
            // Download archive from S3
            $in = Storage::disk('s3')->readStream($file->file_path);
            if (!$in) return 'error';
            $out = fopen($localArchive, 'wb');
            stream_copy_to_stream($in, $out);
            fclose($out);
            fclose($in);

            // Find the .pk3 to read
            $pk3File = null;
            $tempPk3 = null;

            if ($file->file_extension === 'pk3') {
                $pk3File = $localArchive;
            } else {
                // ZIP: extract inner pk3
                $tempPk3 = $this->extractPk3FromZip($localArchive, $file->map_name);
                if (!$tempPk3) return 'no_pk3';
                $pk3File = $tempPk3;
            }

            // Read levelshot from pk3
            $blob = $this->readLevelshotFromPk3($pk3File, $file->map_name);
            if (!$blob) return 'no_levelshot';

            // Convert to JPG + resize
            $this->writeJpegThumb($blob['data'], $blob['ext'], $target);

            return 'ok';
        } finally {
            @unlink($localArchive);
            if (!empty($tempPk3)) @unlink($tempPk3);
        }
    }

    /**
     * Open ZIP, find the most relevant .pk3 inside, extract to temp file.
     * Returns path to extracted pk3, or null if none found.
     */
    private function extractPk3FromZip(string $zipPath, string $mapName): ?string
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return null;
        }

        $mapLower = strtolower($mapName);
        $bestIndex = -1;
        $bestScore = -1;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $lower = strtolower($name);

            // Must end in .pk3, must not be in __MACOSX or similar junk
            if (!str_ends_with($lower, '.pk3')) continue;
            if (str_contains($lower, '__macosx')) continue;
            if (str_contains($lower, 'waypoints')) continue; // omnibot waypoints are often .pk3

            $base = basename($lower);
            // Prefer files whose name contains the map name
            $score = str_contains($base, $mapLower) ? 100 : 0;
            // Prefer shorter names (less likely to be a fixed/patched variant)
            $score -= strlen($base);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIndex = $i;
            }
        }

        if ($bestIndex < 0) {
            $zip->close();
            return null;
        }

        $stream = $zip->getStream($zip->getNameIndex($bestIndex));
        if (!$stream) {
            $zip->close();
            return null;
        }

        $outPath = tempnam(sys_get_temp_dir(), 'pk3_');
        $out = fopen($outPath, 'wb');
        stream_copy_to_stream($stream, $out);
        fclose($out);
        fclose($stream);
        $zip->close();

        return $outPath;
    }

    /**
     * Read the best-matching levelshot file from a pk3.
     *
     * Strategy:
     *   1. Exact match: levelshots/{mapname}.{tga,jpg,png}
     *   2. Fallback: any file in levelshots/ except *_cc.* (command map)
     *      — .tga preferred over .jpg preferred over .png
     *      — shortest filename wins (usually the primary loading screen)
     *
     * @return array{data:string, ext:string, source:string}|null
     */
    private function readLevelshotFromPk3(string $pk3Path, string $mapName): ?array
    {
        $zip = new ZipArchive();
        if ($zip->open($pk3Path) !== true) {
            return null;
        }

        $mapLower = strtolower($mapName);
        $exactCandidates = [
            "levelshots/{$mapLower}.tga",
            "levelshots/{$mapLower}.jpg",
            "levelshots/{$mapLower}.png",
        ];

        $allLevelshots = []; // [index => ['name', 'ext', 'score']]

        try {
            // Pass 1: index every levelshots/* entry + check for exact match
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $nameOrig = $zip->getNameIndex($i);
                $entry = strtolower($nameOrig);

                // Exact match wins immediately
                foreach ($exactCandidates as $c) {
                    if ($entry === $c) {
                        $data = $zip->getFromIndex($i);
                        if ($data === false) return null;
                        return [
                            'data'   => $data,
                            'ext'    => pathinfo($c, PATHINFO_EXTENSION),
                            'source' => 'exact',
                        ];
                    }
                }

                // Collect fallback candidates
                if (!str_starts_with($entry, 'levelshots/')) continue;
                if (str_contains($entry, '/')) {
                    $base = basename($entry);
                    if ($base === '' || str_ends_with($entry, '/')) continue;
                }

                $ext = pathinfo($entry, PATHINFO_EXTENSION);
                if (!in_array($ext, ['tga', 'jpg', 'jpeg', 'png'], true)) continue;

                // Skip command-map variants
                $baseNoExt = pathinfo($entry, PATHINFO_FILENAME);
                if (str_ends_with($baseNoExt, '_cc')) continue;

                // Score: prefer tga > jpg > png, shorter names win
                $extScore  = ['tga' => 30, 'jpg' => 20, 'jpeg' => 20, 'png' => 10][$ext] ?? 0;
                $lenScore  = 100 - min(strlen($baseNoExt), 100);
                $mapScore  = str_contains($baseNoExt, $mapLower) ? 50 : 0;

                $allLevelshots[$i] = [
                    'name'  => $entry,
                    'ext'   => $ext === 'jpeg' ? 'jpg' : $ext,
                    'score' => $extScore + $lenScore + $mapScore,
                ];
            }

            if (empty($allLevelshots)) return null;

            // Pick highest scoring fallback
            uasort($allLevelshots, fn ($a, $b) => $b['score'] <=> $a['score']);
            $best = array_key_first($allLevelshots);
            $info = $allLevelshots[$best];

            $data = $zip->getFromIndex($best);
            if ($data === false) return null;

            return [
                'data'   => $data,
                'ext'    => $info['ext'],
                'source' => 'fallback:' . $info['name'],
            ];
        } finally {
            $zip->close();
        }
    }

    /**
     * Convert a raw image blob (TGA/JPG/PNG) to a 320x180 JPEG on disk.
     */
    private function writeJpegThumb(string $blob, string $ext, string $target): void
    {
        $img = new Imagick();

        if ($ext === 'tga') {
            // Imagick autodetect fails on raw TGA blobs — write tmp + readImage
            $tmp = tempnam(sys_get_temp_dir(), 'tga_') . '.tga';
            file_put_contents($tmp, $blob);
            try {
                $img->setFormat('TGA');
                $img->readImage($tmp);
            } finally {
                @unlink($tmp);
            }
        } else {
            $img->readImageBlob($blob);
        }

        // Strip alpha before JPEG (TGA often has alpha, JPEG can't)
        $img->setImageBackgroundColor('#000000');
        if ($img->getImageAlphaChannel()) {
            $img->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
            $img = $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
        }

        $img->cropThumbnailImage(320, 180);
        $img->setImageFormat('jpeg');
        $img->setFormat('jpeg');
        $img->setImageCompressionQuality(82);
        $img->stripImage();

        file_put_contents($target, $img->getImageBlob());
        $img->clear();
        $img->destroy();
    }

    private function safeName(string $mapName): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-]/', '_', $mapName);
    }
}
