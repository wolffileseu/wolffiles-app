<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackerRanking extends Model
{
    protected $table = 'tracker_rankings';

    protected $fillable = [
        'player_id', 'period', 'period_date', 'rank',
        'elo_rating', 'elo_change', 'total_xp',
        'playtime_minutes', 'sessions_count', 'kills', 'deaths',
        'servers_played', 'maps_played',
    ];

    protected function casts(): array
    {
        return [
            'period_date' => 'date',
            'elo_rating' => 'decimal:2',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(TrackerPlayer::class, 'player_id');
    }
}
