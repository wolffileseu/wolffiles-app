<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackerServerMapStat extends Model
{
    protected $table = 'tracker_server_map_stats';

    protected $fillable = [
        'server_id', 'map_name', 'times_played', 'total_time_minutes',
        'last_played_at', 'avg_players', 'peak_players',
    ];

    protected function casts(): array
    {
        return ['last_played_at' => 'datetime'];
    }

    public function server(): BelongsTo { return $this->belongsTo(TrackerServer::class, 'server_id'); }
}
