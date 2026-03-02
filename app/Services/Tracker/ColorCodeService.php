<?php

namespace App\Services\Tracker;

class ColorCodeService
{
    private static array $colorMap = [
        '0' => '#000000', // black
        '1' => '#FF0000', // red
        '2' => '#00FF00', // green
        '3' => '#FFFF00', // yellow
        '4' => '#0000FF', // blue
        '5' => '#00FFFF', // cyan
        '6' => '#FF00FF', // magenta
        '7' => '#FFFFFF', // white
        '8' => '#FF8800', // orange
        '9' => '#AAAAAA', // grey
        'a' => '#FF4444', // light red
        'b' => '#44FF44', // light green
        'c' => '#4444FF', // light blue
        'd' => '#44FFFF', // light cyan
        'e' => '#FF44FF', // light magenta
        'f' => '#EEEEEE', // light grey
        'g' => '#CCAA00', // dark yellow / gold
        'h' => '#996600', // dark orange / brown
        'i' => '#CCCCCC', // silver
        'j' => '#333333', // dark grey
        'k' => '#666600', // olive
        'l' => '#336633', // dark green
        'm' => '#660000', // dark red
        'n' => '#993300', // brown
        'o' => '#FF6600', // bright orange
        'p' => '#FF9900', // light orange
        'q' => '#FFCC00', // gold
        'r' => '#669900', // yellow-green
        's' => '#009966', // teal
        't' => '#0099CC', // steel blue
        'u' => '#3366CC', // medium blue
        'v' => '#6633CC', // purple
        'w' => '#FFFFFF', // white
        'x' => '#CC0000', // dark red
        'y' => '#00CC00', // dark green
        'z' => '#3399FF', // sky blue
        '*' => '#FFFFFF', // white (reset)
        '-' => '#FFFFFF', // white (reset)
        '+' => '#FFFFFF', // white (reset)
    ];

    /**
     * Convert ET color codes (^1, ^2, etc.) to HTML spans.
     */
    public static function toHtml(string $text): string
    {
        $result = '';
        $len = strlen($text);
        $inSpan = false;

        for ($i = 0; $i < $len; $i++) {
            if ($text[$i] === '^' && $i + 1 < $len) {
                $code = strtolower($text[$i + 1]);
                if (isset(self::$colorMap[$code])) {
                    if ($inSpan) {
                        $result .= '</span>';
                    }
                    $result .= '<span style="color:' . self::$colorMap[$code] . '">';
                    $inSpan = true;
                    $i++;
                    continue;
                }
                // Unknown ^X code - skip both chars
                $i++;
                continue;
            }
            $result .= htmlspecialchars($text[$i], ENT_QUOTES, 'UTF-8');
        }

        if ($inSpan) {
            $result .= '</span>';
        }

        return $result;
    }

    /**
     * Remove all ET color codes from text.
     */
    public static function toClean(string $text): string
    {
        return preg_replace('/\^[^\s]/', '', $text);
    }

    /**
     * Get the color hex for a given code.
     */
    public static function getColor(string $code): ?string
    {
        return self::$colorMap[strtolower($code)] ?? null;
    }
}
