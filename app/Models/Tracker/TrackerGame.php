<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrackerGame extends Model
{
    protected $table = 'tracker_games';

    protected $fillable = [
        'name', 'slug', 'short_name', 'protocol_version',
        'default_port', 'query_type', 'icon', 'color',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function masterServers(): HasMany { return $this->hasMany(TrackerMasterServer::class, 'game_id'); }
    public function servers(): HasMany { return $this->hasMany(TrackerServer::class, 'game_id'); }
    public function sessions(): HasMany { return $this->hasMany(TrackerPlayerSession::class, 'game_id'); }

    public function scopeActive($query) { return $query->where('is_active', true); }

    public function getOnlineServerCountAttribute(): int
    {
        return $this->servers()->where('is_online', true)->count();
    }

    public function getOnlinePlayerCountAttribute(): int
    {
        return $this->servers()->where('is_online', true)->sum('current_players');
    }
}
