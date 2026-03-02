<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackerServerHistory extends Model
{
    protected $table = 'tracker_server_history';
    public $timestamps = false;

    protected $fillable = ['server_id', 'map', 'players', 'max_players', 'gametype', 'polled_at'];

    protected function casts(): array
    {
        return ['polled_at' => 'datetime'];
    }

    public function server(): BelongsTo { return $this->belongsTo(TrackerServer::class, 'server_id'); }
}
