<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Post extends Model
{
    use HasSlug;

    protected $table = 'posts';

    protected $fillable = [
        'user_id', 'title', 'slug', 'title_translations', 'excerpt',
        'content', 'content_translations', 'featured_image',
        'is_published', 'is_pinned', 'published_at', 'view_count',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_pinned' => 'boolean',
            'published_at' => 'datetime',
            'title_translations' => 'array',
            'content_translations' => 'array',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('title')->saveSlugsTo('slug');
    }

    public function getRouteKeyName(): string { return 'slug'; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function comments(): MorphMany { return $this->morphMany(Comment::class, 'commentable'); }
    public function tags() { return $this->morphToMany(Tag::class, 'taggable'); }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where('published_at', '<=', now());
    }

    public function scopePinned($query) { return $this->where('is_pinned', true); }
}
