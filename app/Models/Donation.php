<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string|null $name
 * @property float $amount
 * @property string $source
 */
class Donation extends Model
{
    protected $fillable = [
        'user_id', 'donor_name', 'donor_email', 'amount', 'currency',
        'message', 'source', 'transaction_id', 'status', 'is_anonymous',
        'show_on_wall', 'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_anonymous' => 'boolean',
        'show_on_wall' => 'boolean',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->is_anonymous) return 'Anonymous';
        if ($this->user) return $this->user->name;
        return $this->donor_name ?: 'Anonymous';
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    public function scopeVisible($query)
    {
        return $query->where('show_on_wall', true)->where('status', 'completed');
    }
}
