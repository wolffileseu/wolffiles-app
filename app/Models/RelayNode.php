<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RelayNode extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'hostname',
        'region',
        'ws_url',
        'ipv6_prefix',
        'ipv4_pool',
        'agent_secret',
        'max_sessions',
        'active_sessions',
        'enabled',
        'status',
        'load_avg',
        'agent_rtt_ms',
        'agent_version',
        'last_heartbeat_at',
        'notes',
    ];

    protected $hidden = [
        'agent_secret',
    ];

    protected $casts = [
        'enabled'           => 'boolean',
        'max_sessions'      => 'integer',
        'active_sessions'   => 'integer',
        'load_avg'          => 'float',
        'agent_rtt_ms'      => 'integer',
        'last_heartbeat_at' => 'datetime',
    ];

    /**
     * A heartbeat older than this means the agent is considered gone.
     */
    public const HEARTBEAT_TIMEOUT_SECONDS = 60;

    public function sessions(): HasMany
    {
        return $this->hasMany(RelaySession::class);
    }

    public function activeSessions(): HasMany
    {
        return $this->sessions()->whereNull('ended_at');
    }

    public function hasFreshHeartbeat(): bool
    {
        return $this->last_heartbeat_at !== null
            && $this->last_heartbeat_at->gt(now()->subSeconds(self::HEARTBEAT_TIMEOUT_SECONDS));
    }

    public function hasCapacity(): bool
    {
        return $this->active_sessions < $this->max_sessions;
    }

    public function isAvailable(): bool
    {
        return $this->enabled
            && $this->status === 'online'
            && $this->hasFreshHeartbeat()
            && $this->hasCapacity();
    }

    public function getLoadPercentAttribute(): int
    {
        if ($this->max_sessions <= 0) {
            return 0;
        }

        return (int) round($this->active_sessions / $this->max_sessions * 100);
    }

    /**
     * Nodes that may be handed out to clients right now.
     */
    public function scopeAvailable($query)
    {
        return $query->where('enabled', true)
            ->where('status', 'online')
            ->whereColumn('active_sessions', '<', 'max_sessions')
            ->where('last_heartbeat_at', '>', now()->subSeconds(self::HEARTBEAT_TIMEOUT_SECONDS));
    }

    public function scopeInRegion($query, ?string $region)
    {
        return $region ? $query->where('region', $region) : $query;
    }
}
