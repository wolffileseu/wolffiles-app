<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $content
 * @property string|null $content_translations
 * @property string|null $title_translations
 * @property bool $is_published
 * @property string|null $meta
 * @property mixed $_rendered
 */
class Page extends Model
{
    use SoftDeletes, HasSlug;

    protected $fillable = [
        'user_id', 'title', 'slug', 'title_translations', 'content',
        'content_translations', 'type', 'template', 'pdf_path',
        'is_published', 'sort_order', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'title_translations' => 'array',
            'content_translations' => 'array',
            'meta' => 'array',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('title')->saveSlugsTo('slug');
    }

    public function getRouteKeyName(): string { return 'slug'; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function scopePublished($query) { return $query->where('is_published', true); }

    public function getTranslatedTitle(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        return $this->title_translations[$locale] ?? $this->title;
    }

    public function getTranslatedContent(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        return $this->content_translations[$locale] ?? $this->content;
    }
}
