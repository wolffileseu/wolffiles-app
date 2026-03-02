<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property int $files_count
 * @property int $total_files
 * @property int $approved_files_count
 * @property bool $is_active
 */
class Category extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'parent_id', 'name', 'slug', 'description', 'icon', 'image',
        'sort_order', 'is_active', 'type', 'name_translations',
        'description_translations', 'files_count',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'name_translations' => 'array',
            'description_translations' => 'array',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    public function luaScripts(): HasMany
    {
        return $this->hasMany(LuaScript::class);
    }

    public function approvedFiles(): HasMany
    {
        return $this->files()->where('status', 'approved');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function getTranslatedName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        return $this->name_translations[$locale] ?? $this->name;
    }

    public function getBreadcrumb(): array
    {
        $breadcrumb = [['name' => $this->name, 'slug' => $this->slug]];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($breadcrumb, ['name' => $parent->name, 'slug' => $parent->slug]);
            $parent = $parent->parent;
        }

        return $breadcrumb;
    }

    public function getTotalFilesCount(): int
    {
        $count = $this->files_count;
        foreach ($this->children as $child) {
            /** @var \App\Models\Category $child */
            $count += $child->getTotalFilesCount();
        }
        return $count;
    }
}
