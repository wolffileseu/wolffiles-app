<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Wallpaper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class WallpaperService
{
    private const CACHE_TTL = 300; // 5 min
    private const CACHE_KEY = 'wallpapers:active';

    /** Maps Laravel route name prefixes to wallpaper area keys. */
    private const ROUTE_AREA_MAP = [
        'home'         => 'home',
        'files.'       => 'files',
        'categories.'  => 'files',
        'tracker.'     => 'tracker',
        'wiki.'        => 'wiki',
        'forum.'       => 'forum',
    ];

    public function allActive(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return Wallpaper::active()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        });
    }

    public function forCurrentRoute(): Collection
    {
        $area = $this->detectAreaFromRoute();
        if ($area === null) {
            return collect();
        }
        return $this->allActive()->filter(fn (Wallpaper $w) => $w->appliesTo($area))->values();
    }

    public function pick(): ?array
    {
        $candidates = $this->forCurrentRoute();
        if ($candidates->isEmpty()) {
            return null;
        }

        $slideshow = (bool) Setting::get('wallpaper_slideshow_enabled', false);
        $randomPick = (bool) Setting::get('wallpaper_random_per_pageload', true);
        $interval = (int) Setting::get('wallpaper_slideshow_interval', 10);

        if ($slideshow) {
            $list = $candidates;
        } elseif ($randomPick && $candidates->count() > 1) {
            $list = collect([$candidates->random()]);
        } else {
            $list = collect([$candidates->first()]);
        }

        return [
            'wallpapers'        => $list->map(fn (Wallpaper $w) => [
                'id'              => $w->id,
                'url'             => $w->image_url,
                'overlay_color'   => $w->overlay_color,
                'overlay_opacity' => $w->overlay_opacity,
                'overlay_blur'    => $w->overlay_blur,
            ])->values()->all(),
            'slideshow_enabled' => $slideshow,
            'interval_seconds'  => max(3, $interval),
        ];
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function detectAreaFromRoute(): ?string
    {
        $routeName = optional(request()->route())->getName() ?? '';
        if ($routeName === '') {
            return null;
        }
        foreach (self::ROUTE_AREA_MAP as $prefix => $area) {
            if ($routeName === $prefix || str_starts_with($routeName, $prefix)) {
                return $area;
            }
        }
        return null;
    }
}
