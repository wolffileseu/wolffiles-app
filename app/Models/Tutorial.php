<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $content
 * @property string|null $excerpt
 * @property int|null $tutorial_category_id
 * @property int|null $user_id
 * @property string $status
 * @property string|null $difficulty
 * @property \Carbon\Carbon|null $published_at
 * @property-read \App\Models\TutorialCategory|null $category
 * @property-read \App\Models\User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Comment[] $comments
 */
class Tutorial extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'content', 'excerpt', 'tutorial_category_id', 'user_id',
        'approved_by', 'status', 'difficulty', 'estimated_minutes', 'prerequisites',
        'tags', 'title_translations', 'youtube_url', 'video_path', 'attachments',
        'view_count', 'helpful_count', 'not_helpful_count', 'is_featured',
        'is_series', 'series_parent_id', 'series_order', 'published_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'title_translations' => 'array',
        'attachments' => 'array',
        'is_featured' => 'boolean',
        'is_series' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($tutorial) {
            if (empty($tutorial->slug)) {
                $tutorial->slug = Str::slug($tutorial->title);
                $base = $tutorial->slug;
                $counter = 1;
                while (static::where('slug', $tutorial->slug)->exists()) {
                    $tutorial->slug = $base . '-' . $counter++;
                }
            }
            if (empty($tutorial->excerpt) && !empty($tutorial->content)) {
                $tutorial->excerpt = Str::limit(strip_tags($tutorial->content), 300);
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

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    // Relationships
    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TutorialCategory::class, 'tutorial_category_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function steps()
    {
        return $this->hasMany(TutorialStep::class)->orderBy('step_number');
    }

    public function media()
    {
        return $this->hasMany(WikiMedia::class);
    }

    public function votes()
    {
        return $this->hasMany(TutorialVote::class);
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function seriesParent()
    {
        return $this->belongsTo(Tutorial::class, 'series_parent_id');
    }

    public function seriesParts()
    {
        return $this->hasMany(Tutorial::class, 'series_parent_id')->orderBy('series_order');
    }

    // Attributes
    public function getVideoUrlAttribute(): ?string
    {
        if ($this->video_path) {
            return Storage::disk('s3')->url($this->video_path);
        }
        return null;
    }

    public function getYoutubeEmbedUrlAttribute(): ?string
    {
        if (!$this->youtube_url) return null;

        // Extract video ID from various YouTube URL formats
        preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|v\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $this->youtube_url, $matches);

        return isset($matches[1]) ? "https://www.youtube-nocookie.com/embed/{$matches[1]}" : null;
    }

    public function getDifficultyBadgeColorAttribute(): string
    {
        return match ($this->difficulty) {
            'beginner' => 'green',
            'intermediate' => 'amber',
            'advanced' => 'red',
            default => 'gray',
        };
    }

    public function getDifficultyLabelAttribute(): string
    {
        return match ($this->difficulty) {
            'beginner' => __('messages.beginner') ?: 'Beginner',
            'intermediate' => __('messages.intermediate') ?: 'Intermediate',
            'advanced' => __('messages.advanced') ?: 'Advanced',
            default => ucfirst($this->difficulty),
        };
    }

    public function getHelpfulPercentageAttribute(): int
    {
        $total = $this->helpful_count + $this->not_helpful_count;
        if ($total === 0) return 0;
        return (int) round(($this->helpful_count / $total) * 100);
    }

    public function getUrlAttribute(): string
    {
        return route('tutorials.show', $this->slug);
    }
}
