<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackerEloHistory extends Model
{
    protected $table = 'tracker_elo_history';
    public $timestamps = false;

    protected $fillable = [
        'player_id', 'game_id',
        'elo_before', 'elo_after', 'change',
        'reason', 'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'elo_before' => 'decimal:2',
            'elo_after' => 'decimal:2',
            'change' => 'decimal:2',
        ];
    }

    public function player(): BelongsTo { return $this->belongsTo(TrackerPlayer::class, 'player_id'); }
    public function game(): BelongsTo { return $this->belongsTo(TrackerGame::class, 'game_id'); }
}
