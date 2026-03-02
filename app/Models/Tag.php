<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Tag extends Model
{
    use HasSlug;

    protected $fillable = ['name', 'slug'];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug');
    }

    public function files() { return $this->morphedByMany(File::class, 'taggable'); }
    public function luaScripts() { return $this->morphedByMany(LuaScript::class, 'taggable'); }
    public function posts() { return $this->morphedByMany(Post::class, 'taggable'); }
}