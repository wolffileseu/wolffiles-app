<?php

namespace App\Services\Tracker;

class GametypeService
{
    /**
     * ET/ETL gametype numbers to human-readable names.
     */
    private const ET_GAMETYPES = [
        0 => 'Single Player',
        1 => 'Cooperative',
        2 => 'Objective',
        3 => 'Stopwatch',
        4 => 'Campaign',
        5 => 'Last Man Standing',
        6 => 'Map Voting',
        7 => 'Team Death Match',
        8 => 'Death Match (Free For All)',
    ];

    /**
     * RtCW gametype numbers. Intentionally sparse — we only list values
     * we are confident about. Unknown values fall through to "Gametype N".
     */
    private const RTCW_GAMETYPES = [
        2 => 'Single Player',
        5 => 'Multiplayer',
        // Other values (3, 4, 6, 7, 11) are mod-specific and not documented
        // consistently - fall through to "Gametype N".
    ];

    public static function label(?string $gametype, ?int $gameId = null): string
    {
        if ($gametype === null || $gametype === '') {
            return 'Unknown';
        }

        if (ctype_digit((string) $gametype)) {
            $num = (int) $gametype;
            $map = $gameId === 3 ? self::RTCW_GAMETYPES : self::ET_GAMETYPES;
            return $map[$num] ?? "Gametype {$num}";
        }

        return ucfirst((string) $gametype);
    }
}
