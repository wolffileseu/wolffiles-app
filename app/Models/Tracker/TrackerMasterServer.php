<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackerMasterServer extends Model
{
    protected $table = 'tracker_master_servers';

    protected $fillable = [
        'game_id', 'address', 'port', 'is_active',
        'last_queried_at', 'last_success_at',
        'servers_found', 'failures_count', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_queried_at' => 'datetime',
            'last_success_at' => 'datetime',
        ];
    }

    public function game(): BelongsTo { return $this->belongsTo(TrackerGame::class, 'game_id'); }

    public function getFullAddressAttribute(): string
    {
        return $this->address . ':' . $this->port;
    }
}
