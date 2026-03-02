<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrackerClan extends Model
{
    protected $table = 'tracker_clans';

    protected $fillable = [
        'tag', 'tag_clean', 'name', 'website',
        'country', 'country_code',
        'member_count', 'avg_elo', 'total_play_time_minutes',
        'first_seen_at', 'last_seen_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'avg_elo' => 'decimal:2',
        ];
    }

    public function members(): HasMany { return $this->hasMany(TrackerClanMember::class, 'clan_id'); }
    public function activeMembers(): HasMany { return $this->members()->where('is_active', true); }

    public function scopeActive($query) { return $query->where('status', 'active'); }
}
