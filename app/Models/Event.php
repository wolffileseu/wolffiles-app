<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'description', 'starts_at', 'ends_at',
        'is_recurring', 'recurrence_rule',
        'team_axis', 'team_allies', 'map_name', 'match_type',
        'gametype', 'mod_name', 'match_server_ip', 'match_server_port',
        'ettv_enabled', 'ettv_slot',
        'status', 'submitted_by', 'approved_by', 'approved_at', 'rejection_reason',
        'score_axis', 'score_allies', 'demo_id',
        'slug', 'image_url', 'is_featured',
        'title_translations', 'description_translations',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'approved_at' => 'datetime',
        'is_recurring' => 'boolean',
        'ettv_enabled' => 'boolean',
        'is_featured' => 'boolean',
        'title_translations' => 'array',
        'description_translations' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($event) {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->title . '-' . now()->format('Y-m-d'));
            }
        });
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function demo(): BelongsTo
    {
        return $this->belongsTo(File::class, 'demo_id');
    }

    public function ettvSlot(): BelongsTo
    {
        return $this->belongsTo(EttvSlot::class, 'ettv_slot', 'slot_number');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('starts_at', '>', now())
                     ->whereIn('status', ['approved', 'live']);
    }

    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    public function approve(User $approver): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
    }

    public function reject(string $reason, User $approver): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'rejection_reason' => $reason,
        ]);
    }

    public function goLive(): void
    {
        $this->update(['status' => 'live']);
    }

    public function complete(int $scoreAxis = null, int $scoreAllies = null): void
    {
        $this->update([
            'status' => 'completed',
            'score_axis' => $scoreAxis,
            'score_allies' => $scoreAllies,
        ]);
    }

    public function getMatchLabel(): string
    {
        $parts = [];
        if ($this->team_axis && $this->team_allies) {
            $parts[] = "{$this->team_axis} vs {$this->team_allies}";
        }
        if ($this->map_name) {
            $parts[] = $this->map_name;
        }
        if ($this->match_type) {
            $parts[] = $this->match_type;
        }
        return implode(' · ', $parts) ?: $this->title;
    }

    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'info',
            'rejected' => 'danger',
            'live' => 'success',
            'completed' => 'gray',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }
}
