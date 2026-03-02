<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property \Carbon\Carbon|null $first_seen_at
 * @property \Carbon\Carbon|null $last_seen_at
 * @property float $elo_rating
 * @property float $elo_peak
 * @property int $id
 * @property string $name
 * @property string $name_clean
 * @property string|null $country_code
 * @property string|null $clan
 */
class TrackerPlayer extends Model
{
    protected $table = 'tracker_players';

    protected $fillable = [
        'guid_hash', 'user_id', 'name', 'name_clean', 'name_html',
        'country', 'country_code',
        'first_seen_at', 'last_seen_at',
        'total_play_time_minutes', 'total_kills', 'total_deaths',
        'total_sessions', 'total_xp',
        'elo_rating', 'elo_peak', 'elo_games', 'level', 'status',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'elo_rating' => 'decimal:2',
            'elo_peak' => 'decimal:2',
        ];
    }

    public function aliases(): HasMany { return $this->hasMany(TrackerPlayerAlias::class, 'player_id'); }
    public function sessions(): HasMany { return $this->hasMany(TrackerPlayerSession::class, 'player_id'); }
    public function snapshots(): HasMany { return $this->hasMany(TrackerPlayerSnapshot::class, 'player_id'); }
    public function dailyStats(): HasMany { return $this->hasMany(TrackerPlayerDailyStat::class, 'player_id'); }
    public function eloHistory(): HasMany { return $this->hasMany(TrackerEloHistory::class, 'player_id'); }
    public function clanMemberships(): HasMany { return $this->hasMany(TrackerClanMember::class, 'player_id'); }
    public function bans(): HasMany { return $this->hasMany(TrackerBan::class, 'player_id'); }
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(\App\Models\User::class, 'user_id'); }
    public function isClaimedBy(?\App\Models\User $user): bool { return $user && $this->user_id === $user->id; }
    public function isClaimed(): bool { return $this->user_id !== null; }

    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopeTopElo($query) { return $query->orderByDesc('elo_rating'); }

    public function getKdRatioAttribute(): float
    {
        return $this->total_deaths > 0 ? round($this->total_kills / $this->total_deaths, 2) : $this->total_kills;
    }

    public function getPlayTimeFormattedAttribute(): string
    {
        $hours = floor($this->total_play_time_minutes / 60);
        $mins = $this->total_play_time_minutes % 60;
        return $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
    }

    public function getActiveClanAttribute(): ?TrackerClan
    {
        $membership = $this->clanMemberships()->where('is_active', true)->with('clan')->first();
        return $membership?->clan;
    }

    public function isBanned(): bool
    {
        return $this->status === 'banned' || $this->bans()->where('is_active', true)->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        })->exists();
    }
}
