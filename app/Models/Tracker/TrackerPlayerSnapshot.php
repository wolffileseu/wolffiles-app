<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackerPlayerSnapshot extends Model
{
    protected $table = 'tracker_player_snapshots';
    public $timestamps = false;

    protected $fillable = [
        'session_id', 'server_id', 'player_id',
        'name', 'score', 'ping', 'team', 'polled_at',
    ];

    protected function casts(): array
    {
        return ['polled_at' => 'datetime'];
    }

    public function session(): BelongsTo { return $this->belongsTo(TrackerPlayerSession::class, 'session_id'); }
    public function server(): BelongsTo { return $this->belongsTo(TrackerServer::class, 'server_id'); }
    public function player(): BelongsTo { return $this->belongsTo(TrackerPlayer::class, 'player_id'); }
}
