<?php

namespace App\Enums\Support;

enum AuthorType: string
{
    case User    = 'user';
    case Staff   = 'staff';
    case Guest   = 'guest';
    case Discord = 'discord';
    case Email   = 'email';
    case System  = 'system';

    public function label(): string
    {
        return match ($this) {
            self::User    => 'User',
            self::Staff   => 'Staff',
            self::Guest   => 'Guest',
            self::Discord => 'Discord',
            self::Email   => 'E-Mail',
            self::System  => 'System',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Staff  => 'success',
            self::System => 'gray',
            default      => 'info',
        };
    }

    /** Antwort vom Support-Team (fuer first_response_at / SLA). */
    public function isStaff(): bool
    {
        return $this === self::Staff;
    }
}
