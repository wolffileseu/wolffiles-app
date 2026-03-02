<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Poll extends Model
{
    protected $fillable = ['question', 'is_active', 'multiple_choice', 'ends_at', 'created_by'];
    protected $casts = ['is_active' => 'boolean', 'multiple_choice' => 'boolean', 'ends_at' => 'datetime'];

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class)->orderBy('sort_order');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpen(): bool
    {
        if (!$this->is_active) return false;
        if ($this->ends_at && $this->ends_at->isPast()) return false;
        return true;
    }

    public function totalVotes(): int
    {
        return $this->votes()->count();
    }

    public function hasVoted(?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();
        if (!$userId) return false;
        return $this->votes()->where('user_id', $userId)->exists();
    }
}
