<?php

namespace App\Services\Tracker;

/**
 * ET player classes (PC_* enum from the ws clientinfo feed: \ping\score\P\class\name).
 * Verified against tracker_player_match_stats distribution (values 0-4).
 * Single source of truth for class number -> name across live list + profiles.
 */
class TrackerClass
{
    public const NAMES = [
        0 => 'Soldier',
        1 => 'Medic',
        2 => 'Engineer',
        3 => 'Field Ops',
        4 => 'Covert Ops',
    ];

    /** Short labels for compact UI (e.g. live player list). */
    public const SHORT = [
        0 => 'Sol',
        1 => 'Med',
        2 => 'Eng',
        3 => 'FOps',
        4 => 'COps',
    ];

    public static function name(?int $class): ?string
    {
        return $class === null ? null : (self::NAMES[$class] ?? null);
    }

    public static function short(?int $class): ?string
    {
        return $class === null ? null : (self::SHORT[$class] ?? null);
    }
}
