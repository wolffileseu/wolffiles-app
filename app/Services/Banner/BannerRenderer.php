<?php

namespace App\Services\Banner;

use App\Services\Tracker\ColorCodeService;
use GdImage;

/**
 * Base renderer for 560×80 PNG banners (server / player).
 * Provides GD helpers: text, ET-color text, sparkline, image compositing.
 */
abstract class BannerRenderer
{
    protected GdImage $canvas;
    protected int $width;
    protected int $height;

    protected const FONT_BOLD = '/usr/share/fonts/dejavu-sans-fonts/DejaVuSans-Bold.ttf';
    protected const FONT_CONDENSED_BOLD = '/usr/share/fonts/dejavu-sans-fonts/DejaVuSansCondensed-Bold.ttf';

    protected const THEME = [
        'bg_top'       => [20, 20, 25],
        'bg_bottom'    => [10, 10, 12],
        'border'       => [60, 60, 65],
        'accent'       => [255, 204, 0],    // yellow labels + header
        'value'        => [255, 255, 255],
        'value_muted'  => [170, 170, 175],
        'online'       => [76, 221, 76],
        'offline'      => [221, 76, 76],
        'graph_line'   => [120, 220, 120],
        'graph_fill'   => [60, 130, 60],
    ];

    public function __construct(int $width, int $height)
    {
        $this->width = $width;
        $this->height = $height;
        $this->canvas = imagecreatetruecolor($width, $height);
        imagealphablending($this->canvas, true);
        imagesavealpha($this->canvas, false);

        $this->drawBackground();

        // 1px border
        imagerectangle($this->canvas, 0, 0, $width - 1, $height - 1, $this->color(self::THEME['border']));
    }

    /**
     * Draw background. Uses public/images/banner/bg-server.jpg if present,
     * otherwise falls back to vertical gradient defined in THEME.
     */
    protected function drawBackground(): void
    {
        $bgPath = public_path('images/banner/bg-server.jpg');
        if (is_file($bgPath) && ($bg = @imagecreatefromjpeg($bgPath))) {
            imagecopyresampled(
                $this->canvas, $bg, 0, 0, 0, 0,
                $this->width, $this->height,
                imagesx($bg), imagesy($bg)
            );
            imagedestroy($bg);
            return;
        }

        $top = self::THEME['bg_top'];
        $bot = self::THEME['bg_bottom'];
        for ($y = 0; $y < $this->height; $y++) {
            $t = $y / max($this->height - 1, 1);
            $c = imagecolorallocate(
                $this->canvas,
                (int) round($top[0] + ($bot[0] - $top[0]) * $t),
                (int) round($top[1] + ($bot[1] - $top[1]) * $t),
                (int) round($top[2] + ($bot[2] - $top[2]) * $t),
            );
            imageline($this->canvas, 0, $y, $this->width, $y, $c);
        }
    }

    public function __destruct()
    {
        if (isset($this->canvas)) {
            imagedestroy($this->canvas);
        }
    }

    abstract public function render(): string;

    protected function color(array $rgb): int
    {
        return imagecolorallocate($this->canvas, $rgb[0], $rgb[1], $rgb[2]);
    }

    protected function colorAlpha(array $rgb, int $alpha = 0): int
    {
        return imagecolorallocatealpha($this->canvas, $rgb[0], $rgb[1], $rgb[2], $alpha);
    }

    protected function text(string $text, int $x, int $y, int $size, array $color, ?string $font = null): int
    {
        $font ??= self::FONT_BOLD;
        $c = $this->color($color);
        $bbox = imagettftext($this->canvas, $size, 0, $x, $y, $c, $font, $text);
        return abs($bbox[2] - $bbox[0]);
    }

    protected function textWidth(string $text, int $size, ?string $font = null): int
    {
        $font ??= self::FONT_BOLD;
        $bbox = imagettfbbox($size, 0, $font, $text);
        return abs($bbox[2] - $bbox[0]);
    }

    /**
     * Render ET-color-coded text. Returns total width drawn.
     */
    protected function coloredText(string $text, int $x, int $y, int $size, ?string $font = null, int $maxWidth = 0): int
    {
        $font ??= self::FONT_BOLD;
        $segments = ColorCodeService::toSegments($text);
        $cursor = $x;
        $total  = 0;
        $ellipsisW = $maxWidth > 0 ? $this->textWidth('…', $size, $font) : 0;

        foreach ($segments as $seg) {
            $segText = $seg['text'];
            if ($maxWidth > 0) {
                $segW = $this->textWidth($segText, $size, $font);
                if ($total + $segW > $maxWidth - $ellipsisW) {
                    while ($segText !== '' && $total + $this->textWidth($segText, $size, $font) > $maxWidth - $ellipsisW) {
                        $segText = mb_substr($segText, 0, -1);
                    }
                    if ($segText !== '') {
                        $c = $this->color($seg['color']);
                        $bbox = imagettftext($this->canvas, $size, 0, $cursor, $y, $c, $font, $segText);
                        $cursor += abs($bbox[2] - $bbox[0]);
                    }
                    $ell = $this->color(self::THEME['value_muted']);
                    imagettftext($this->canvas, $size, 0, $cursor, $y, $ell, $font, '…');
                    return $maxWidth;
                }
            }
            $c = $this->color($seg['color']);
            $bbox = imagettftext($this->canvas, $size, 0, $cursor, $y, $c, $font, $segText);
            $w = abs($bbox[2] - $bbox[0]);
            $cursor += $w;
            $total  += $w;
        }

        return $total;
    }

    /**
     * Draw a filled sparkline from data points.
     *
     * @param array<int|float> $data
     */
    protected function sparkline(array $data, int $x, int $y, int $w, int $h, ?float $maxValue = null): void
    {
        if (empty($data)) {
            return;
        }
        if (count($data) < 2) {
            $data = array_merge($data, $data);
        }
        $max = max((float) ($maxValue ?? max($data)), 1.0);
        $n = count($data);

        $points = [];
        for ($i = 0; $i < $n; $i++) {
            $px = (int) round($x + ($i / ($n - 1)) * $w);
            $py = (int) round($y + $h - (min((float) $data[$i], $max) / $max) * $h);
            $points[] = [$px, $py];
        }

        // Fill polygon
        $flat = [];
        foreach ($points as $p) {
            $flat[] = $p[0];
            $flat[] = $p[1];
        }
        $flat[] = $x + $w;
        $flat[] = $y + $h;
        $flat[] = $x;
        $flat[] = $y + $h;

        imagefilledpolygon($this->canvas, $flat, $this->colorAlpha(self::THEME['graph_fill'], 70));

        // Line
        $lineColor = $this->color(self::THEME['graph_line']);
        imagesetthickness($this->canvas, 1);
        for ($i = 1; $i < $n; $i++) {
            imageline(
                $this->canvas,
                $points[$i - 1][0], $points[$i - 1][1],
                $points[$i][0],     $points[$i][1],
                $lineColor
            );
        }
    }

    /**
     * Composite an image file onto canvas. Supports JPG/PNG/GIF/WEBP.
     */
    protected function drawImage(string $path, int $x, int $y, ?int $targetW = null, ?int $targetH = null): bool
    {
        if (!is_file($path)) {
            return false;
        }
        $info = @getimagesize($path);
        if (!$info) {
            return false;
        }
        $img = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_GIF  => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default        => null,
        };
        if (!$img) {
            return false;
        }
        $sw = imagesx($img);
        $sh = imagesy($img);
        $tw = $targetW ?? $sw;
        $th = $targetH ?? $sh;
        imagecopyresampled($this->canvas, $img, $x, $y, 0, 0, $tw, $th, $sw, $sh);
        imagedestroy($img);
        return true;
    }

    public function output(): string
    {
        ob_start();
        imagepng($this->canvas, null, 9);
        return (string) ob_get_clean();
    }
}
