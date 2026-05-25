<?php

namespace App\Enums\BugTracker;

enum TaskPriority: string
{
    case VeryLow  = 'very_low';
    case Low      = 'low';
    case Normal   = 'normal';
    case High     = 'high';
    case Urgent   = 'urgent';

    public function label(): string
    {
        return match($this) {
            self::VeryLow => 'Very Low',
            self::Low     => 'Low',
            self::Normal  => 'Normal',
            self::High    => 'High',
            self::Urgent  => 'Urgent',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::VeryLow => 'gray',
            self::Low     => 'blue',
            self::Normal  => 'green',
            self::High    => 'amber',
            self::Urgent  => 'red',
        };
    }

    public function weight(): int
    {
        return match($this) {
            self::VeryLow => 1,
            self::Low     => 2,
            self::Normal  => 3,
            self::High    => 4,
            self::Urgent  => 5,
        };
    }
}
