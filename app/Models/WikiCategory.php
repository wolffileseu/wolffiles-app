<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WikiCategory extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'icon', 'name_translations', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($cat) {
            if (empty($cat->slug)) {
                $cat->slug = Str::slug($cat->name);
            }
        });
    }

    public function articles()
    {
        return $this->hasMany(WikiArticle::class);
    }

    public function publishedArticles()
    {
        return $this->hasMany(WikiArticle::class)->published();
    }

    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->name_translations[$locale] ?? $this->name;
    }
}
