<?php

namespace App\Enums\Support;

enum SyncStatus: string
{
    case Pending = 'pending';
    case Synced  = 'synced';
    case Failed  = 'failed';
    // Interne Notizen werden bewusst nie ausgeliefert
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Synced  => 'Synced',
            self::Failed  => 'Failed',
            self::Skipped => 'Not sent',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Synced  => 'success',
            self::Failed  => 'danger',
            self::Skipped => 'gray',
        };
    }
}
