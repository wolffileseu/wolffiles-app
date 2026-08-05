<?php

namespace App\Enums\Support;

enum TicketStatus: string
{
    case New        = 'new';
    case Open       = 'open';
    case Pending    = 'pending';
    case OnHold     = 'on_hold';
    case Resolved   = 'resolved';
    case Closed     = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New      => 'New',
            self::Open     => 'Open',
            self::Pending  => 'Waiting on user',
            self::OnHold   => 'On hold',
            self::Resolved => 'Resolved',
            self::Closed   => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New      => 'danger',
            self::Open     => 'warning',
            self::Pending  => 'info',
            self::OnHold   => 'gray',
            self::Resolved => 'success',
            self::Closed   => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::New      => 'heroicon-o-sparkles',
            self::Open     => 'heroicon-o-chat-bubble-left-right',
            self::Pending  => 'heroicon-o-clock',
            self::OnHold   => 'heroicon-o-pause-circle',
            self::Resolved => 'heroicon-o-check-circle',
            self::Closed   => 'heroicon-o-archive-box',
        };
    }

    /** Zaehlt als offen fuer Badges, Eskalation und Rate-Limit. */
    public function isOpen(): bool
    {
        return in_array($this, [self::New, self::Open, self::Pending, self::OnHold], true);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
