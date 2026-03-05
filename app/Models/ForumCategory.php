<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ForumCategory extends Model
{
    protected $fillable = [
        'parent_id', 'name', 'slug', 'description',
        'icon', 'icon_image', 'color', 'sort_order', 'is_locked',
        'name_translations', 'description_translations',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'name_translations' => 'array',
        'description_translations' => 'array',
    ];

    // Übersetzter Name (fallback auf name)
    public function getTranslatedNameAttribute(): string
    {
        $locale = app()->getLocale();
        $translations = $this->name_translations;

        if ($translations && isset($translations[$locale]) && !empty($translations[$locale])) {
            return $translations[$locale];
        }

        return $this->name;
    }

    // Übersetzte Beschreibung (fallback auf description)
    public function getTranslatedDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        $translations = $this->description_translations;

        if ($translations && isset($translations[$locale]) && !empty($translations[$locale])) {
            return $translations[$locale];
        }

        return $this->description;
    }

    // Icon URL (eigenes Bild oder null)
    public function getIconImageUrlAttribute(): ?string
    {
        if ($this->icon_image) {
            return Storage::disk('s3')->url($this->icon_image);
        }

        return null;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ForumCategory::class, 'parent_id')->orderBy('sort_order');
    }

    public function threads(): HasMany
    {
        return $this->hasMany(ForumThread::class);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id')->orderBy('sort_order');
    }

    public function getThreadsCountAttribute(): int
    {
        $count = $this->threads()->count();
        foreach ($this->children as $child) {
            $count += $child->threads()->count();
        }
        return $count;
    }

    public function getPostsCountAttribute(): int
    {
        $count = ForumPost::whereIn('forum_thread_id', $this->threads()->pluck('id'))->count();
        foreach ($this->children as $child) {
            $count += ForumPost::whereIn('forum_thread_id', $child->threads()->pluck('id'))->count();
        }
        return $count;
    }

    public function getLatestPostAttribute()
    {
        $threadIds = $this->threads()->pluck('id');
        foreach ($this->children as $child) {
            $threadIds = $threadIds->merge($child->threads()->pluck('id'));
        }
        return ForumPost::whereIn('forum_thread_id', $threadIds)
            ->latest()->with('user', 'thread')->first();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
