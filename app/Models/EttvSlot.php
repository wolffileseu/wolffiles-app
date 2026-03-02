<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EttvSlot extends Model
{
    protected $fillable = [
        'slot_number', 'port', 'pterodactyl_uuid', 'status', 'mode',
        'demo_id', 'demo_name', 'map_name',
        'match_server_ip', 'match_server_port',
        'event_id', 'user_id',
        'started_at', 'expires_at',
        'spectator_count', 'hostname',
        'reservation_reason',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function demo(): BelongsTo
    {
        return $this->belongsTo(File::class, 'demo_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'idle');
    }

    public function scopeRunning($query)
    {
        return $query->whereIn('status', ['playing', 'relay']);
    }

    public function scopeShowcase($query)
    {
        return $query->where('mode', 'showcase');
    }

    public function isAvailable(): bool
    {
        return $this->status === 'idle';
    }

    public function isRunning(): bool
    {
        return in_array($this->status, ['playing', 'relay', 'starting']);
    }

    public function getConnectString(): string
    {
        return '/connect ettv.wolffiles.eu:' . $this->port;
    }

    public function getConnectUrl(): string
    {
        return 'et://ettv.wolffiles.eu:' . $this->port;
    }

    public function getStatusBadge(): string
    {
        return match ($this->status) {
            'idle' => '⚫',
            'starting' => '🟡',
            'playing' => '🔵',
            'relay' => '🟢',
            'reserved' => '🟡',
            'error' => '🔴',
            default => '⚪',
        };
    }

    public function release(): void
    {
        $this->update([
            'status' => 'idle',
            'mode' => null,
            'demo_id' => null,
            'demo_name' => null,
            'map_name' => null,
            'match_server_ip' => null,
            'match_server_port' => null,
            'event_id' => null,
            'user_id' => null,
            'started_at' => null,
            'expires_at' => null,
            'spectator_count' => 0,
            'hostname' => null,
            'reservation_reason' => null,
        ]);
    }
}
