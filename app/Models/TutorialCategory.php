<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TutorialCategory extends Model
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

    public function tutorials()
    {
        return $this->hasMany(Tutorial::class);
    }

    public function publishedTutorials()
    {
        return $this->hasMany(Tutorial::class)->published();
    }
}
