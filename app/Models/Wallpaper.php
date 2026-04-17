<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Wallpaper extends Model
{
    protected $fillable = [
        'name', 'image_path', 'display_areas',
        'overlay_color', 'overlay_opacity', 'overlay_blur',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'display_areas' => 'array',
        'is_active' => 'boolean',
        'overlay_opacity' => 'integer',
        'overlay_blur' => 'integer',
        'sort_order' => 'integer',
    ];

    public const AREAS = [
        'home' => 'Homepage',
        'files' => 'Files (Listings & Detail)',
        'tracker' => 'Tracker',
        'wiki' => 'Wiki',
        'forum' => 'Forum',
        'all' => 'Layout-wide (everywhere)',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) return null;
        return Storage::disk('s3')->url($this->image_path);
    }

    public function appliesTo(string $area): bool
    {
        $areas = $this->display_areas ?? [];
        return in_array('all', $areas, true) || in_array($area, $areas, true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForArea($query, string $area)
    {
        return $query->where(function ($q) use ($area) {
            $q->whereJsonContains('display_areas', 'all')
              ->orWhereJsonContains('display_areas', $area);
        });
    }

    protected static function booted(): void
    {
        $flush = fn () => app(\App\Services\WallpaperService::class)->flushCache();
        static::saved($flush);
        static::deleted($flush);
    }
}
