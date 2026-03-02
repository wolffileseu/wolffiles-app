<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $content
 * @property string|null $excerpt
 * @property int|null $wiki_category_id
 * @property int|null $user_id
 * @property string $status
 * @property \Carbon\Carbon|null $published_at
 * @property-read \App\Models\WikiCategory|null $category
 * @property-read \App\Models\User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Comment[] $comments
 */
class WikiArticle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'content', 'excerpt', 'wiki_category_id', 'user_id',
        'approved_by', 'status', 'tags', 'title_translations', 'view_count',
        'revision_count', 'is_locked', 'is_featured', 'attachments', 'published_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'title_translations' => 'array',
        'attachments' => 'array',
        'is_locked' => 'boolean',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
                $base = $article->slug;
                $counter = 1;
                while (static::where('slug', $article->slug)->exists()) {
                    $article->slug = $base . '-' . $counter++;
                }
            }
            if (empty($article->excerpt) && !empty($article->content)) {
                $article->excerpt = Str::limit(strip_tags($article->content), 300);
            }
        });
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Relationships
    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WikiCategory::class, 'wiki_category_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function revisions()
    {
        return $this->hasMany(WikiRevision::class)->orderByDesc('revision_number');
    }

    public function media()
    {
        return $this->hasMany(WikiMedia::class);
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    // Methods
    public function createRevision(int $userId, ?string $changeSummary = null): WikiRevision
    {
        $this->increment('revision_count');

        return $this->revisions()->create([
            'user_id' => $userId,
            'title' => $this->title,
            'content' => $this->content,
            'change_summary' => $changeSummary,
            'revision_number' => $this->revision_count,
        ]);
    }

    public function restoreRevision(WikiRevision $revision): void
    {
        $this->update([
            'title' => $revision->title,
            'content' => $revision->content,
        ]);
    }

    public function getUrlAttribute(): string
    {
        return route('wiki.show', $this->slug);
    }

    public function getLocalizedTitleAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->title_translations[$locale] ?? $this->title;
    }
}
