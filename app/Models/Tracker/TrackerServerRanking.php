<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackerServerRanking extends Model
{
    protected $table = 'tracker_server_rankings';
    public $timestamps = false;

    protected $fillable = [
        'server_id', 'game_id', 'rank', 'total_in_game',
        'avg_players_30d', 'peak_players_30d',
        'polls_counted', 'total_polls_30d', 'online_polls_30d',
        'total_playtime_minutes_30d', 'unique_players_30d',
        'computed_at',
    ];

    protected $casts = [
        'avg_players_30d' => 'decimal:2',
        'computed_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(TrackerServer::class, 'server_id');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(TrackerGame::class, 'game_id');
    }

    public function getUptimePercentAttribute(): float
    {
        if ((int) $this->total_polls_30d === 0) {
            return 0.0;
        }
        return round($this->online_polls_30d / $this->total_polls_30d * 100, 1);
    }
}
