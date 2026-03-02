<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackerPlayerDailyStat extends Model
{
    protected $table = 'tracker_player_daily_stats';
    public $timestamps = false;

    protected $fillable = [
        'player_id', 'game_id', 'date',
        'play_time_minutes', 'sessions', 'kills', 'deaths',
        'xp', 'servers_played', 'maps_played',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function player(): BelongsTo { return $this->belongsTo(TrackerPlayer::class, 'player_id'); }
    public function game(): BelongsTo { return $this->belongsTo(TrackerGame::class, 'game_id'); }
}
