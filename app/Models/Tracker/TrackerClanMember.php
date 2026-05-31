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
        'role_label', 'squad_id', 'is_manual', 'sort_order',
        'joined_at', 'left_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_manual' => 'boolean',
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function clan(): BelongsTo { return $this->belongsTo(TrackerClan::class, 'clan_id'); }
    public function player(): BelongsTo { return $this->belongsTo(TrackerPlayer::class, 'player_id'); }
    public function squad(): BelongsTo { return $this->belongsTo(TrackerClanSquad::class, 'squad_id'); }
}
