<?php

namespace App\Services\Tracker;

class ColorCodeService
{
    /**
     * ET:Legacy g_color_table (src/qcommon/q_math.c), 32 Eintraege, Index 0-31.
     * Floats -> Hex via round(v * 255). Lookup-Index = (ord($c) - ord('0')) & 31
     * (engine ColorIndex()). Ein Farbcode ist '^' + alphanumerisches Zeichen
     * ([0-9A-Za-z]), gemaess Q_IsColorString().
     */
    private static array $colorTable = [
        '#000000', // 0  black
        '#FF0000', // 1  red
        '#00FF00', // 2  green
        '#FFFF00', // 3  yellow
        '#0000FF', // 4  blue
        '#00FFFF', // 5  cyan
        '#FF00FF', // 6  purple
        '#FFFFFF', // 7  white
        '#FF8000', // 8  orange
        '#808080', // 9  md.grey
        '#BFBFBF', // 10 lt.grey
        '#BFBFBF', // 11 lt.grey
        '#008000', // 12 md.green
        '#808000', // 13 md.yellow
        '#000080', // 14 md.blue
        '#800000', // 15 md.red
        '#804000', // 16 md.orange
        '#FF991A', // 17 lt.orange
        '#008080', // 18 md.cyan
        '#800080', // 19 md.purple
        '#0080FF', // 20
        '#8000FF', // 21
        '#3399CC', // 22
        '#CCFFCC', // 23
        '#006633', // 24
        '#FF0033', // 25
        '#B31A1A', // 26
        '#993300', // 27
        '#CC9933', // 28
        '#999933', // 29
        '#FFFFBF', // 30
        '#FFFF80', // 31
    ];

    /**
     * Resolve an ET color code character to a hex color (#RRGGBB).
     * Returns null if the character is not a valid color code ([0-9A-Za-z]).
     */
    private static function colorForChar(string $c): ?string
    {
        if ($c === '' || $c[0] === '^') {
            return null;
        }
        return self::$colorTable[(ord($c[0]) - 48) & 31];
    }

    /**
     * Convert ET color codes (^1, ^2, etc.) to HTML spans.
     */
    public static function toHtml(string $text): string
    {
        $result = '';
        $len    = strlen($text);
        $inSpan = false;

        for ($i = 0; $i < $len; $i++) {
            if ($text[$i] === '^' && $i + 1 < $len && ($hex = self::colorForChar($text[$i + 1])) !== null) {
                if ($inSpan) {
                    $result .= '</span>';
                }
                $result .= '<span style="color:' . $hex . '">';
                $inSpan  = true;
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
     * Canonical identity key: strip colors, collapse whitespace,
     * lowercase, trim. Used ONLY for guid_hash computation, never for display.
     */
    public static function normalizeKey(string $name): string
    {
        // toClean removes color codes (game-accurate). The result may still
        // contain literal carets from ^^ escapes; strip those for identity only.
        $clean = self::toClean($name);
        $clean = str_replace('^', '', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        return mb_strtolower(trim($clean), 'UTF-8');
    }

    /**
     * Remove all ET color codes from text.
     */
    public static function toClean(string $text): string
    {
        return preg_replace('/\^[^\^]/', '', $text);
    }

    /**
     * Get the color hex for a given code character, or null if not a color code.
     */
    public static function getColor(string $code): ?string
    {
        return self::colorForChar($code);
    }

    /**
     * Convert hex color (#RRGGBB) to [r, g, b] int tuple.
     *
     * @return array{0:int,1:int,2:int}
     */
    public static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Get RGB tuple for a color code character, or null if not a color code.
     *
     * @return array{0:int,1:int,2:int}|null
     */
    public static function getRgb(string $code): ?array
    {
        $hex = self::colorForChar($code);
        return $hex === null ? null : self::hexToRgb($hex);
    }

    /**
     * Parse ET-color-coded text into segments with RGB colors.
     *
     * @param array{0:int,1:int,2:int} $defaultColor Starting color (default white).
     * @return list<array{text:string, color:array{0:int,1:int,2:int}}>
     */
    public static function toSegments(string $text, array $defaultColor = [255, 255, 255]): array
    {
        $segments = [];
        $current  = $defaultColor;
        $buffer   = '';
        $len      = strlen($text);

        for ($i = 0; $i < $len; $i++) {
            if ($text[$i] === '^' && $i + 1 < $len && ($hex = self::colorForChar($text[$i + 1])) !== null) {
                if ($buffer !== '') {
                    $segments[] = ['text' => $buffer, 'color' => $current];
                    $buffer = '';
                }
                $current = self::hexToRgb($hex);
                $i++;
                continue;
            }
            $buffer .= $text[$i];
        }

        if ($buffer !== '') {
            $segments[] = ['text' => $buffer, 'color' => $current];
        }

        return $segments;
    }
}
