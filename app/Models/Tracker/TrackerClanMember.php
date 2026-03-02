<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackerClanMember extends Model
{
    protected $table = 'tracker_clan_members';
    public $timestamps = false;

    protected $fillable = [
        'clan_id', 'player_id', 'role',
        'joined_at', 'left_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function clan(): BelongsTo { return $this->belongsTo(TrackerClan::class, 'clan_id'); }
    public function player(): BelongsTo { return $this->belongsTo(TrackerPlayer::class, 'player_id'); }
}
