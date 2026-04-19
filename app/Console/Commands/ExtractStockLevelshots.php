<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Imagick;
use ZipArchive;

/**
 * Extract stock-map levelshots from ET Vanilla (pak0.pk3) and ETLegacy (legacy_v*.pk3).
 *
 * Default paths checked (first existing wins per source):
 *   Vanilla:   storage/app/pak0.pk3
 *              storage/app/stock-maps/pak0.pk3
 *   ETLegacy:  /var/www/vhosts/wolffiles.eu/fastdl.wolffiles.eu/et/legacy/*.pk3 (picks latest)
 *              storage/app/stock-maps/pak3.pk3
 *
 * Extracts levelshots/{mapname}.{tga,jpg,png} (excluding *_cc), resizes to 320x180,
 * writes to public/images/map-thumbs/{mapname}.jpg.
 *
 * By default, existing thumbs are NOT overwritten. Use --force to override.
 */
class ExtractStockLevelshots extends Command
{
    protected $signature = 'banner:extract-stock-levelshots
        {--force : Overwrite existing thumbs}
        {--pak0= : Path to ET Vanilla pak0.pk3}
        {--legacy= : Path to ETLegacy pk3}';

    protected $description = 'Extract stock ET + ETLegacy map levelshots from vanilla pk3 files';

    private string $thumbDir;

    public function handle(): int
    {
        $this->thumbDir = public_path('images/map-thumbs');
        if (!is_dir($this->thumbDir)) {
            mkdir($this->thumbDir, 0755, true);
        }

        // Resolve sources
        $pak0 = $this->option('pak0') ?: $this->findVanilla();
        $legacy = $this->option('legacy') ?: $this->findLegacy();

        $sources = array_filter([
            'ET Vanilla' => $pak0,
            'ETLegacy'   => $legacy,
        ]);

        if (empty($sources)) {
            $this->error('No source pk3s found. Supply --pak0 and/or --legacy paths.');
            return self::FAILURE;
        }

        $totalOk = 0; $totalSkip = 0; $totalFail = 0;

        foreach ($sources as $label => $path) {
            $this->info("\n=== $label → " . basename($path) . ' ===');
            [$ok, $skip, $fail] = $this->processPk3($path);
            $totalOk   += $ok;
            $totalSkip += $skip;
            $totalFail += $fail;
        }

        $this->newLine();
        $this->info("Total: $totalOk extracted, $totalSkip skipped (exist), $totalFail failed");
        return self::SUCCESS;
    }

    private function findVanilla(): ?string
    {
        foreach ([
            storage_path('app/pak0.pk3'),
            storage_path('app/stock-maps/pak0.pk3'),
        ] as $p) {
            if (is_file($p)) return $p;
        }
        return null;
    }

    private function findLegacy(): ?string
    {
        $fastdl = '/var/www/vhosts/wolffiles.eu/fastdl.wolffiles.eu/et/legacy';
        if (is_dir($fastdl)) {
            $pk3s = glob($fastdl . '/*.pk3');
            if (!empty($pk3s)) {
                // Latest version wins (filename sort, e.g. legacy_v2.83.1.pk3)
                usort($pk3s, fn($a, $b) => strcmp($b, $a));
                return $pk3s[0];
            }
        }
        foreach ([
            storage_path('app/stock-maps/pak3.pk3'),
        ] as $p) {
            if (is_file($p)) return $p;
        }
        return null;
    }

    /**
     * @return array{0:int,1:int,2:int}  [ok, skipped, failed]
     */
    private function processPk3(string $pk3Path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($pk3Path) !== true) {
            $this->warn("  cannot open $pk3Path");
            return [0, 0, 1];
        }

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = strtolower($zip->getNameIndex($i));
            if (!str_starts_with($name, 'levelshots/')) continue;

            $base = basename($name);
            if ($base === '' || str_ends_with($name, '/')) continue;

            $ext = pathinfo($name, PATHINFO_EXTENSION);
            if (!in_array($ext, ['tga', 'jpg', 'jpeg', 'png'], true)) continue;

            $noExt = pathinfo($name, PATHINFO_FILENAME);
            if (str_ends_with($noExt, '_cc')) continue; // skip command maps
            if ($noExt === 'unknownmap') continue;       // skip generic fallback

            $entries[$noExt][] = ['index' => $i, 'ext' => $ext];
        }

        if (empty($entries)) {
            $this->warn('  no levelshots found in this pk3');
            $zip->close();
            return [0, 0, 0];
        }

        $this->line('  found ' . count($entries) . ' maps with levelshots');

        $ok = 0; $skipped = 0; $failed = 0;

        foreach ($entries as $mapName => $variants) {
            $target = $this->thumbDir . '/' . $this->safeName($mapName) . '.jpg';

            if (!$this->option('force') && file_exists($target)) {
                $this->line("  · <fg=gray>$mapName</> (exists)");
                $skipped++;
                continue;
            }

            // Pick best variant: tga > jpg > png
            usort($variants, function ($a, $b) {
                $order = ['tga' => 3, 'jpg' => 2, 'jpeg' => 2, 'png' => 1];
                return ($order[$b['ext']] ?? 0) <=> ($order[$a['ext']] ?? 0);
            });
            $pick = $variants[0];

            $blob = $zip->getFromIndex($pick['index']);
            if ($blob === false) {
                $this->warn("  ✗ $mapName (read failed)");
                $failed++;
                continue;
            }

            try {
                $this->writeJpegThumb($blob, $pick['ext'] === 'jpeg' ? 'jpg' : $pick['ext'], $target);
                $this->line("  ✓ <fg=green>$mapName</> (from .{$pick['ext']})");
                $ok++;
            } catch (\Throwable $e) {
                $this->warn("  ✗ $mapName (" . $e->getMessage() . ')');
                $failed++;
            }
        }

        $zip->close();
        return [$ok, $skipped, $failed];
    }

    private function writeJpegThumb(string $blob, string $ext, string $target): void
    {
        $img = new Imagick();

        if ($ext === 'tga') {
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
