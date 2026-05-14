<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WikiArticle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'namespace', 'content', 'wikitext', 'excerpt',
        'wiki_category_id', 'user_id', 'approved_by', 'status', 'tags',
        'title_translations', 'view_count', 'revision_count', 'is_locked',
        'is_redirect', 'redirect_target',
        'is_featured', 'attachments', 'published_at',
    ];

    protected $casts = [
        'tags'               => 'array',
        'title_translations' => 'array',
        'attachments'        => 'array',
        'is_locked'          => 'boolean',
        'is_redirect'        => 'boolean',
        'is_featured'        => 'boolean',
        'published_at'       => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($article) {
            if (empty($article->namespace)) {
                $article->namespace = 'main';
            }
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
                $base = $article->slug;
                $counter = 1;
                while (static::where('namespace', $article->namespace)
                             ->where('slug', $article->slug)->exists()) {
                    $article->slug = $base . '-' . $counter++;
                }
            }
            if (empty($article->excerpt) && !empty($article->content)) {
                $article->excerpt = Str::limit(strip_tags($article->content), 300);
            }
        });
    }

    protected function content(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value ? \Mews\Purifier\Facades\Purifier::clean($value) : null,
        );
    }

    // ===== Scopes =====

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInNamespace($query, string $namespace)
    {
        return $query->where('namespace', $namespace);
    }

    public function scopeMain($query)
    {
        return $query->where('namespace', 'main');
    }

    // ===== Relationships =====

    public function category()
    {
        return $this->belongsTo(WikiCategory::class, 'wiki_category_id');
    }

    public function categoriesM2M()
    {
        return $this->belongsToMany(
            WikiCategory::class,
            'wiki_article_category',
            'wiki_article_id',
            'wiki_category_id'
        );
    }

    public function user()
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

    public function translations()
    {
        return $this->hasMany(WikiArticleTranslation::class);
    }

    public function talkThreads()
    {
        return $this->hasMany(WikiTalkThread::class)
                    ->orderByDesc('is_pinned')
                    ->orderByDesc('last_reply_at');
    }

    public function incomingLinks()
    {
        return $this->hasMany(WikiLink::class, 'to_article_id');
    }

    public function outgoingLinks()
    {
        return $this->hasMany(WikiLink::class, 'from_article_id');
    }

    public function redirectsHere()
    {
        return $this->hasMany(WikiRedirect::class, 'to_article_id');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    // ===== Methods =====

    public function createRevision(int $userId, ?string $changeSummary = null): WikiRevision
    {
        $this->increment('revision_count');

        return $this->revisions()->create([
            'user_id'         => $userId,
            'title'           => $this->title,
            'content'         => $this->content,
            'change_summary'  => $changeSummary,
            'revision_number' => $this->revision_count,
        ]);
    }

    public function restoreRevision(WikiRevision $revision): void
    {
        $this->update([
            'title'   => $revision->title,
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

        if ($this->relationLoaded('translations')) {
            $t = $this->translations->firstWhere('locale', $locale);
            if ($t && !empty($t->title)) {
                return $t->title;
            }
        }

        return $this->title_translations[$locale] ?? $this->title;
    }

    public function localizedTitle(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $trans = $this->relationLoaded('translations')
            ? $this->translations
            : $this->translations()->get();
        $t = $trans->firstWhere('locale', $locale);
        if ($t && !empty($t->title)) {
            return $t->title;
        }
        return $this->title_translations[$locale] ?? $this->title;
    }

    public function localizedHtml(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        $trans = $this->relationLoaded('translations')
            ? $this->translations
            : $this->translations()->get();
        $t = $trans->firstWhere('locale', $locale);
        if ($t && !empty($t->content_html)) {
            return $t->content_html;
        }
        return $this->content;
    }

    public function localizedWikitext(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        $trans = $this->relationLoaded('translations')
            ? $this->translations
            : $this->translations()->get();
        $t = $trans->firstWhere('locale', $locale);
        if ($t && !empty($t->wikitext)) {
            return $t->wikitext;
        }
        return $this->wikitext;
    }

    public function translation(string $locale): ?WikiArticleTranslation
    {
        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('locale', $locale);
        }
        return $this->translations()->where('locale', $locale)->first();
    }

    public function availableLocales(): array
    {
        if ($this->relationLoaded('translations')) {
            return $this->translations->pluck('locale')->all();
        }
        return $this->translations()->pluck('locale')->all();
    }
}
