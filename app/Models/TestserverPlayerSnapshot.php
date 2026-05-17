<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestserverPlayerSnapshot extends Model
{
    public $timestamps = false; // wir haben nur snapshot_at

    protected $fillable = [
        'testserver_session_id',
        'snapshot_at',
        'player_count',
        'player_names',
        'player_scores',
        'current_map',
        'current_mod',
        'ping_ms',
    ];

    protected $casts = [
        'snapshot_at'   => 'datetime',
        'player_count'  => 'integer',
        'player_names'  => 'array',
        'player_scores' => 'array',
        'ping_ms'       => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(TestserverSession::class, 'testserver_session_id');
    }
}
