<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackerPlayerAlias extends Model
{
    protected $table = 'tracker_player_aliases';
    public $timestamps = false;

    protected $fillable = [
        'player_id', 'name', 'name_clean', 'name_html',
        'times_used', 'first_seen_at', 'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function player(): BelongsTo { return $this->belongsTo(TrackerPlayer::class, 'player_id'); }
}
