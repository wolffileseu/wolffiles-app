<?php

namespace App\Enums\Support;

enum TicketSource: string
{
    case Web     = 'web';
    case Discord = 'discord';
    case Email   = 'email';
    case Admin   = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Web     => 'Website',
            self::Discord => 'Discord',
            self::Email   => 'E-Mail',
            self::Admin   => 'Admin panel',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Web     => 'heroicon-o-globe-alt',
            self::Discord => 'heroicon-o-chat-bubble-oval-left-ellipsis',
            self::Email   => 'heroicon-o-envelope',
            self::Admin   => 'heroicon-o-wrench-screwdriver',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Web     => 'info',
            self::Discord => 'primary',
            self::Email   => 'warning',
            self::Admin   => 'gray',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
