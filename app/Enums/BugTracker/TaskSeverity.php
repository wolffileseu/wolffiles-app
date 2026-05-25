<?php

namespace App\Enums\BugTracker;

enum TaskSeverity: string
{
    case Cosmetic = 'cosmetic';
    case Minor    = 'minor';
    case Major    = 'major';
    case Critical = 'critical';
    case Blocker  = 'blocker';

    public function label(): string
    {
        return match($this) {
            self::Cosmetic => 'Cosmetic',
            self::Minor    => 'Minor',
            self::Major    => 'Major',
            self::Critical => 'Critical',
            self::Blocker  => 'Blocker',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Cosmetic => 'gray',
            self::Minor    => 'blue',
            self::Major    => 'amber',
            self::Critical => 'red',
            self::Blocker  => 'rose',
        };
    }
}
