<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property \Carbon\Carbon|null $last_seen_at
 * @property int $id
 * @property string $ip
 * @property int $port
 * @property string|null $hostname
 * @property string|null $current_map
 * @property int $current_players
 * @property int $max_players
 * @property string|null $gametype
 * @property bool $is_online
 * @property string|null $short_name
 * @property string|null $full_address
 * @property string|null $address
 * @property int|null $protocol_version
 * @property int|null $peak_players
 * @property string|null $map
 */
class TrackerServer extends Model
{
    protected $table = 'tracker_servers';

    protected $fillable = [
        'game_id', 'ip', 'port', 'hostname', 'hostname_clean', 'hostname_html',
        'country', 'country_code', 'city', 'latitude', 'longitude',
        'current_map', 'current_players', 'max_players', 'gametype',
        'mod_name', 'mod_version', 'is_private', 'needs_password',
        'os', 'sv_pure', 'punkbuster', 'is_ranked',
        'is_online', 'is_manually_added', 'added_by', 'status',
        'total_players_tracked', 'total_unique_players', 'uptime_percentage',
        'last_seen_at', 'first_seen_at', 'last_poll_at', 'poll_failures',
    ];

    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'is_private' => 'boolean',
            'needs_password' => 'boolean',
            'sv_pure' => 'boolean',
            'punkbuster' => 'boolean',
            'is_ranked' => 'boolean',
            'is_manually_added' => 'boolean',
            'last_seen_at' => 'datetime',
            'first_seen_at' => 'datetime',
            'last_poll_at' => 'datetime',
        ];
    }

    public function game(): BelongsTo { return $this->belongsTo(TrackerGame::class, 'game_id'); }
    public function addedBy(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'added_by'); }
    public function settings(): HasMany { return $this->hasMany(TrackerServerSetting::class, 'server_id'); }
    public function history(): HasMany { return $this->hasMany(TrackerServerHistory::class, 'server_id'); }
    public function mapStats(): HasMany { return $this->hasMany(TrackerServerMapStat::class, 'server_id'); }
    public function sessions(): HasMany { return $this->hasMany(TrackerPlayerSession::class, 'server_id'); }
    public function snapshots(): HasMany { return $this->hasMany(TrackerPlayerSnapshot::class, 'server_id'); }

    public function scopeOnline($query) { return $query->where('is_online', true); }
    public function scopeActive($query) { return $query->where('status', 'active'); }

    public function getFullAddressAttribute(): string
    {
        return $this->ip . ':' . $this->port;
    }

    public function getConnectUrlAttribute(): string
    {
        return 'et://' . $this->ip . ':' . $this->port;
    }

    public function getSetting(string $key): ?string
    {
        return $this->settings()->where('key', $key)->value('value');
    }
}
