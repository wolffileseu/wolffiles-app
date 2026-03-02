<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PollOption extends Model
{
    protected $fillable = ['poll_id', 'text', 'votes_count', 'sort_order'];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    public function percentage(int $totalVotes): float
    {
        if ($totalVotes === 0) return 0;
        return round(($this->votes_count / $totalVotes) * 100, 1);
    }
}
