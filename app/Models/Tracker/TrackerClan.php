<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TrackerClan extends Model
{
    protected $table = 'tracker_clans';

    protected $fillable = [
        'tag', 'tag_clean', 'name', 'website',
        'country', 'country_code',
        'member_count', 'avg_elo', 'total_play_time_minutes',
        'first_seen_at', 'last_seen_at', 'status',
        'description', 'discord', 'is_verified', 'claimed_by_user_id', 'is_locked', 'auto_join_enabled', 'active_member_count',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'avg_elo' => 'decimal:2',
            'is_verified' => 'boolean',
            'is_locked' => 'boolean',
            'auto_join_enabled' => 'boolean',
        ];
    }

    public function members(): HasMany { return $this->hasMany(TrackerClanMember::class, 'clan_id'); }

    public function recalcMemberCounts(): void
    {
        $this->forceFill([
            'member_count'        => $this->members()->count(),
            'active_member_count' => $this->members()->where('is_active', true)->count(),
        ])->saveQuietly();
    }
    public function activeMembers(): HasMany { return $this->members()->where('is_active', true); }
    public function squads(): HasMany { return $this->hasMany(TrackerClanSquad::class, 'clan_id'); }
    public function registeredClan(): HasOne { return $this->hasOne(\App\Models\Clan::class, 'tracker_clan_id'); }
    public function claimedByUser(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'claimed_by_user_id'); }

    public function scopeActive($query) { return $query->where('status', 'active'); }
}
