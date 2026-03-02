<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackerServerRating extends Model
{
    protected $table = 'tracker_server_ratings';

    protected $fillable = [
        'server_id', 'user_id', 'rating', 'comment', 'is_approved',
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'rating' => 'integer',
        ];
    }

    public function server(): BelongsTo { return $this->belongsTo(TrackerServer::class, 'server_id'); }
    public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class); }
}
