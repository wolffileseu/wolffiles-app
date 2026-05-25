<?php

namespace App\Enums\BugTracker;

enum TaskStatus: string
{
    case New        = 'new';
    case Confirmed  = 'confirmed';
    case Assigned   = 'assigned';
    case InProgress = 'in_progress';
    case Fixed      = 'fixed';
    case Closed     = 'closed';
    case WontFix    = 'wontfix';
    case Duplicate  = 'duplicate';
    case Invalid    = 'invalid';

    public function label(): string
    {
        return match($this) {
            self::New        => 'New',
            self::Confirmed  => 'Confirmed',
            self::Assigned   => 'Assigned',
            self::InProgress => 'In Progress',
            self::Fixed      => 'Fixed',
            self::Closed     => 'Closed',
            self::WontFix    => "Won't Fix",
            self::Duplicate  => 'Duplicate',
            self::Invalid    => 'Invalid',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::New        => 'gray',
            self::Confirmed  => 'blue',
            self::Assigned   => 'purple',
            self::InProgress => 'amber',
            self::Fixed      => 'green',
            self::Closed     => 'slate',
            self::WontFix    => 'rose',
            self::Duplicate  => 'slate',
            self::Invalid    => 'slate',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::New, self::Confirmed, self::Assigned, self::InProgress]);
    }

    public function isResolved(): bool
    {
        return in_array($this, [self::Fixed, self::Closed, self::WontFix, self::Duplicate, self::Invalid]);
    }
}
