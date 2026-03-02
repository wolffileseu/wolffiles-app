<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackerMap extends Model
{
    protected $table = 'tracker_maps';

    protected $fillable = [
        'name', 'name_clean', 'file_id',
        'total_servers', 'total_time_played_minutes',
        'total_unique_players', 'total_sessions',
        'peak_concurrent_players',
        'first_seen_at', 'last_seen_at', 'screenshot_path',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function file(): BelongsTo { return $this->belongsTo(\App\Models\File::class, 'file_id'); }
}
