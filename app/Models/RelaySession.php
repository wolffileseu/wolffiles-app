<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelaySession extends Model
{
    use HasFactory;

    protected $fillable = [
        'relay_node_id',
        'tracker_server_id',
        'user_id',
        'game',
        'target_ip',
        'target_port',
        'source_addr',
        'client_ip',
        'client_country',
        'ticket_id',
        'ticket_expires_at',
        'bytes_in',
        'bytes_out',
        'started_at',
        'ended_at',
        'ended_reason',
    ];

    protected $casts = [
        'target_port'       => 'integer',
        'bytes_in'          => 'integer',
        'bytes_out'         => 'integer',
        'ticket_expires_at' => 'datetime',
        'started_at'        => 'datetime',
        'ended_at'          => 'datetime',
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(RelayNode::class, 'relay_node_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tracker\TrackerServer::class, 'tracker_server_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isActive(): bool
    {
        return $this->ended_at === null && $this->started_at !== null;
    }

    public function getTargetAttribute(): string
    {
        return $this->target_ip . ':' . $this->target_port;
    }

    public function getDurationSecondsAttribute(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->ended_at ?? now());
    }

    public function scopeActive($query)
    {
        return $query->whereNull('ended_at')->whereNotNull('started_at');
    }
}
