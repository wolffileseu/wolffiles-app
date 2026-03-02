<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id', 'parent_id', 'title', 'title_translations',
        'url', 'route', 'linkable_type', 'linkable_id',
        'target', 'icon', 'css_class', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'title_translations' => 'array'];
    }

    public function menu(): BelongsTo { return $this->belongsTo(Menu::class); }
    public function parent(): BelongsTo { return $this->belongsTo(MenuItem::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order'); }
    public function linkable(): MorphTo { return $this->morphTo(); }

    public function getResolvedUrlAttribute(): string
    {
        if ($this->url) return $this->url;
        if ($this->route) return route($this->route);
        if ($this->linkable) {
            if ($this->linkable instanceof Page) return route('pages.show', $this->linkable);
            if ($this->linkable instanceof Category) return route('categories.show', $this->linkable);
        }
        return '#';
    }
}
