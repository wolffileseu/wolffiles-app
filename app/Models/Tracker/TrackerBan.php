<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property \Carbon\Carbon|null $expires_at
 * @property bool $is_active
 * @property int $id
 * @property int $player_id
 */
class TrackerBan extends Model
{
    protected $table = 'tracker_bans';

    protected $fillable = [
        'player_id', 'guid_hash', 'ip_address',
        'reason', 'source', 'banned_by',
        'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function player(): BelongsTo { return $this->belongsTo(TrackerPlayer::class, 'player_id'); }
    public function bannedBy(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'banned_by'); }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }
}
