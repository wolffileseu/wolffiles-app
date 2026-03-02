<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackerScrim extends Model
{
    protected $table = 'tracker_scrims';

    protected $fillable = [
        'created_by_user_id', 'clan_id', 'title', 'description',
        'game_type', 'map_preference', 'mod_preference', 'region',
        'skill_level', 'scheduled_at', 'status', 'contact_discord', 'server_ip',
    ];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }

    public function createdBy(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'created_by_user_id'); }
    public function clan(): BelongsTo { return $this->belongsTo(TrackerClan::class, 'clan_id'); }

    public function scopeOpen($query) { return $query->where('status', 'open'); }
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'open')->where(function ($q) {
            $q->whereNull('scheduled_at')->orWhere('scheduled_at', '>=', now());
        });
    }
}
