<?php

namespace App\Models\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffSetting extends Model
{
    protected $table = 'sup_staff_settings';

    protected $fillable = [
        'user_id', 'quiet_from', 'quiet_to', 'timezone', 'digest_enabled',
    ];

    protected $casts = ['digest_enabled' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Waehrend der Ruhezeit werden Push-Kanaele uebersprungen (Panel bleibt aktiv). */
    public function isQuietNow(): bool
    {
        if (! $this->quiet_from || ! $this->quiet_to) {
            return false;
        }

        $now  = now($this->timezone)->format('H:i:s');
        $from = (string) $this->quiet_from;
        $to   = (string) $this->quiet_to;

        // Fenster ueber Mitternacht, z.B. 23:00 -> 07:00
        return $from <= $to
            ? ($now >= $from && $now <= $to)
            : ($now >= $from || $now <= $to);
    }
}
