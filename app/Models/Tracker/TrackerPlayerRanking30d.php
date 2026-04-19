<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackerPlayerRanking30d extends Model
{
    protected $table = 'tracker_player_rankings_30d';
    public $timestamps = false;

    protected $fillable = [
        'player_id', 'game_id', 'rank', 'total_in_game',
        'playtime_minutes_30d', 'sessions_count_30d',
        'kills_30d', 'deaths_30d', 'xp_30d',
        'unique_servers_30d', 'unique_maps_30d',
        'elo_rating', 'computed_at',
    ];

    protected $casts = [
        'computed_at' => 'datetime',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(TrackerPlayer::class, 'player_id');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(TrackerGame::class, 'game_id');
    }

    public function getKdRatioAttribute(): float
    {
        if ((int) $this->deaths_30d === 0) {
            return (float) $this->kills_30d;
        }
        return round($this->kills_30d / $this->deaths_30d, 2);
    }
}
