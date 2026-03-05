<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ForumThread extends Model
{
    protected $fillable = [
        'forum_category_id', 'user_id', 'title', 'slug',
        'is_pinned', 'is_locked', 'views_count', 'posts_count',
        'last_post_at', 'last_post_user_id',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
        'last_post_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'forum_category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(ForumPost::class);
    }

    public function firstPost(): HasOne
    {
        return $this->hasOne(ForumPost::class)->oldestOfMany();
    }

    public function lastPost(): HasOne
    {
        return $this->hasOne(ForumPost::class)->latestOfMany();
    }

    public function lastPostUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_post_user_id');
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function refreshPostsCount(): void
    {
        $this->update(['posts_count' => $this->posts()->count()]);
    }

    public function refreshLastPost(): void
    {
        $lastPost = $this->posts()->latest()->first();
        $this->update([
            'last_post_at' => $lastPost?->created_at,
            'last_post_user_id' => $lastPost?->user_id,
        ]);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeSorted($query)
    {
        return $query->orderByDesc('is_pinned')->orderByDesc('last_post_at');
    }
}
