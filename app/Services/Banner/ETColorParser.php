<?php

namespace App\Services\Banner;

use App\Services\Tracker\ColorCodeService;

/**
 * @deprecated since banner refactor. Use ColorCodeService::toSegments() directly.
 * This shim stays for any callers that still reference it; all parsing now
 * lives in App\Services\Tracker\ColorCodeService (single source of truth).
 */
class ETColorParser
{
    /** @return list<array{text:string, color:array{0:int,1:int,2:int}}> */
    public static function parse(string $text, array $defaultColor = [255, 255, 255]): array
    {
        return ColorCodeService::toSegments($text, $defaultColor);
    }

    public static function strip(string $text): string
    {
        return ColorCodeService::toClean($text);
    }
}
