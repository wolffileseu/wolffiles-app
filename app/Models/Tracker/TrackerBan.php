<?php

namespace App\Models\Tracker;

use App\Models\User;
use App\Models\Report;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Carbon|null $expires_at
 * @property bool $is_active
 * @property int $id
 * @property int $player_id
 */
class TrackerBan extends Model
{
    protected $table = 'tracker_bans';

    protected $fillable = [
        'player_id', 'guid_hash', 'guid_snapshot', 'ip_address',
        'reason', 'public_reason', 'source', 'type', 'status',
        'is_public', 'banned_by', 'source_report_id',
        'expires_at', 'occurred_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'expires_at' => 'datetime',
            'occurred_at' => 'datetime',
        ];
    }

    public function player(): BelongsTo { return $this->belongsTo(TrackerPlayer::class, 'player_id'); }
    public function bannedBy(): BelongsTo { return $this->belongsTo(User::class, 'banned_by'); }

    public function sourceReport(): BelongsTo { return $this->belongsTo(Report::class, 'source_report_id'); }

    public function evidence(): HasMany
    {
        return $this->hasMany(TrackerBanEvidence::class, 'ban_id');
    }

    public function publicEvidence(): HasMany
    {
        return $this->hasMany(TrackerBanEvidence::class, 'ban_id')->where('is_public', true);
    }

    public function servers(): BelongsToMany
    {
        return $this->belongsToMany(TrackerServer::class, 'tracker_ban_servers', 'ban_id', 'server_id');
    }

    /** A ban is publicly visible only if flagged public, active, and has >=1 public evidence. */
    public function isPubliclyVisible(): bool
    {
        return $this->is_public && $this->status === 'active' && $this->publicEvidence()->exists();
    }

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
