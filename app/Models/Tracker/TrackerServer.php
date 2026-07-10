<?php

namespace App\Models\Tracker;

use App\Models\User;
use App\Models\Clan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Carbon|null $last_seen_at
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
        'current_map', 'current_map_started_at', 'current_players', 'max_players', 'gametype',
        'mod_name', 'mod_version', 'is_private', 'needs_password',
        'os', 'sv_pure', 'punkbuster', 'is_ranked',
        'is_online', 'is_manually_added', 'added_by', 'status',
        'claimed_by_user_id', 'claimed_by_clan_id', 'is_visible_for_clan', 'is_verified', 'is_locked',
        'description', 'rules', 'server_website', 'server_discord', 'server_email', 'server_banner_url', 'server_logo_url', 'slug', 'slug_changed_at',
        'total_players_tracked', 'total_unique_players', 'uptime_percentage',
        'last_seen_at', 'first_seen_at', 'last_poll_at', 'next_poll_at', 'poll_failures',
        'latency_ms', 'private_slots', 'bot_count',
        'friendly_fire', 'antilag', 'balanced_teams',
        'heavy_weapon_restriction', 'anticheat', 'os_support',
        'custom_poll_interval', 'polling_paused',
        'engine_family', 'engine_version', 'engine_platform', 'engine_build_date', 'engine_is_dev_build', 'engine_display',
    ];

    protected $casts = [
        'first_seen_at'      => 'datetime',
        'last_seen_at'       => 'datetime',
        'last_poll_at'       => 'datetime',
        'next_poll_at'       => 'datetime',
        'slug_changed_at'    => 'datetime',
        'enhanced_first_seen_at' => 'datetime',
        'enhanced_last_event_at' => 'datetime',
        'is_online'          => 'boolean',
        'is_verified'        => 'boolean',
        'is_locked'          => 'boolean',
        'is_visible_for_clan'=> 'boolean',
        'is_manually_added'  => 'boolean',
        'polling_paused'     => 'boolean',
        'is_private'         => 'boolean',
        'needs_password'     => 'boolean',
        'is_ranked'          => 'boolean',
        'is_enhanced_tracker'=> 'boolean',
        'enhanced_disabled'  => 'boolean',
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
            'is_verified' => 'boolean',
            'is_locked' => 'boolean',
            'polling_paused' => 'boolean',
            'engine_is_dev_build' => 'boolean',
            'engine_build_date' => 'date',
            'last_seen_at' => 'datetime',
            'first_seen_at' => 'datetime',
            'last_poll_at' => 'datetime',
            'next_poll_at' => 'datetime',
            'current_map_started_at' => 'datetime',
            'is_enhanced_tracker' => 'boolean',
            'enhanced_disabled' => 'boolean',
            'enhanced_first_seen_at' => 'datetime',
            'enhanced_last_event_at' => 'datetime',
        ];
    }

    public function game(): BelongsTo { return $this->belongsTo(TrackerGame::class, 'game_id'); }
    public function addedBy(): BelongsTo { return $this->belongsTo(User::class, 'added_by'); }
    public function claimedByUser(): BelongsTo { return $this->belongsTo(User::class, 'claimed_by_user_id'); }
    public function clan(): BelongsTo { return $this->belongsTo(Clan::class, 'claimed_by_clan_id'); }
    public function settings(): HasMany { return $this->hasMany(TrackerServerSetting::class, 'server_id'); }
    public function history(): HasMany { return $this->hasMany(TrackerServerHistory::class, 'server_id'); }
    public function mapStats(): HasMany { return $this->hasMany(TrackerServerMapStat::class, 'server_id'); }
    public function sessions(): HasMany { return $this->hasMany(TrackerPlayerSession::class, 'server_id'); }
    public function snapshots(): HasMany { return $this->hasMany(TrackerPlayerSnapshot::class, 'server_id'); }

    public function scopeOnline($query) { return $query->where('is_online', true); }
    public function scopeActive($query) { return $query->whereIn('status', ['active', 'pending']); }

    /**
     * Servers that should still be polled, including established servers
     * that went offline. Established = first_seen_at older than 1 day AND
     * was ever successfully polled (last_seen_at is set).
     */
    public function scopePollable($query)
    {
        // Banned servers are never polled, regardless of how they would
        // otherwise qualify (active/pending status OR a live enhanced
        // tracker stream). This outer guard wraps both arms below.
        return $query->where('status', '!=', 'banned')->where(function ($q) {
            // Normal active / pending servers
            $q->whereIn('status', ['active', 'pending'])
              // NOTE: 'inactive' servers are intentionally NOT polled.
              // Cleanup deactivates dead servers; they only return to the
              // poll loop when a human re-adds them (status -> active) or
              // discovery sees a 'removed' server re-register. This keeps
              // long-dead servers out of the rotation permanently.
              // OR servers currently sending enhanced tracker events
              ->orWhere(function ($q3) {
                  $q3->where('is_enhanced_tracker', true)
                     ->where('enhanced_disabled', false)
                     ->where('enhanced_last_event_at', '>=', now()->subMinutes(10));
              });
        });
    }

    /**
     * True if this server is considered "established" (>=1 day old + was online at least once).
     */
    public function getIsEstablishedAttribute(): bool
    {
        return $this->last_seen_at !== null
            && $this->first_seen_at !== null
            && $this->first_seen_at->lessThanOrEqualTo(now()->subDay());
    }

    /**
     * Effective online state — also true if enhanced tracker saw an event recently,
     * even if the regular getstatus query currently fails.
     */
    public function getIsEffectivelyOnlineAttribute(): bool
    {
        if ($this->is_online) return true;
        if ($this->is_enhanced_tracker
            && !$this->enhanced_disabled
            && $this->enhanced_last_event_at
            && $this->enhanced_last_event_at->greaterThanOrEqualTo(now()->subMinutes(5))) {
            return true;
        }
        return false;
    }

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
