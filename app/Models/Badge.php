<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Badge extends Model
{
    use HasSlug;

    protected $fillable = [
        'name', 'slug', 'description', 'icon', 'color',
        'criteria_type', 'criteria_value', 'is_active', 'sort_order',
    ];

    protected function casts(): array { return ['is_active' => 'boolean']; }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_badges')->withPivot('earned_at')->withTimestamps();
    }
}
