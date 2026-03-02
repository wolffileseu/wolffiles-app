<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $ended_at
 * @property int $id
 * @property int $player_id
 * @property int $server_id
 * @property int|null $score
 * @property int|null $duration_minutes
 * @property string|null $player_name
 * @property \Carbon\Carbon|null $last_seen
 */
class TrackerPlayerSession extends Model
{
    protected $table = 'tracker_player_sessions';
    public $timestamps = false;

    protected $fillable = [
        'player_id', 'server_id', 'game_id',
        'started_at', 'ended_at', 'duration_minutes',
        'map_name', 'kills', 'deaths', 'xp', 'score', 'team',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function player(): BelongsTo { return $this->belongsTo(TrackerPlayer::class, 'player_id'); }
    public function server(): BelongsTo { return $this->belongsTo(TrackerServer::class, 'server_id'); }
    public function game(): BelongsTo { return $this->belongsTo(TrackerGame::class, 'game_id'); }
    public function snapshots(): HasMany { return $this->hasMany(TrackerPlayerSnapshot::class, 'session_id'); }

    public function scopeActive($query) { return $query->whereNull('ended_at'); }

    public function getKdRatioAttribute(): float
    {
        return $this->deaths > 0 ? round($this->kills / $this->deaths, 2) : $this->kills;
    }
}
