<?php

namespace App\Enums\Support;

enum TicketPriority: string
{
    case Low      = 'low';
    case Normal   = 'normal';
    case High     = 'high';
    case Urgent   = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low    => 'Low',
            self::Normal => 'Normal',
            self::High   => 'High',
            self::Urgent => 'Urgent',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low    => 'gray',
            self::Normal => 'info',
            self::High   => 'warning',
            self::Urgent => 'danger',
        };
    }

    /** Stunden ohne Antwort bis zur Eskalation. */
    public function escalationHours(): int
    {
        return match ($this) {
            self::Low    => 72,
            self::Normal => 24,
            self::High   => 8,
            self::Urgent => 2,
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
