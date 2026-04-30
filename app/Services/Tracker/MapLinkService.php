<?php

namespace App\Services\Tracker;

use App\Models\File;
use App\Models\Tracker\TrackerMap;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MapLinkService
{
    /**
     * Find a Wolffiles file for a given server map name.
     * Returns the best matching File or null.
     */
    private static array $stockMaps = [
        'oasis', 'battery', 'goldrush', 'radar', 'fueldump', 'supply',
        'railgun', 'tc_base', 'mp_beach', 'mp_ice', 'mp_sub', 'mp_rocket',
        'mp_assault', 'mp_village', 'mp_dam', 'mp_castle', 'mp_depot',
        'mp_base', 'mp_tram', 'mp_chateau', 'mp_trenchtoast',
        'et_ice', 'et_beach', 'et_mor2',
    ];

    public static function findFile(?string $mapName): ?File
    {
        $mapName = strtolower(trim((string) $mapName));
        if (empty($mapName)) return null;
        if (in_array($mapName, self::$stockMaps, true)) return null;

        return Cache::remember("map_link:{$mapName}", 3600, function () use ($mapName) {
            // 1. Exact match on generated column (uses index, instant)
            $file = File::where('status', 'approved')
                ->where('map_name_clean', $mapName)
                ->orderByDesc('download_count')
                ->first();

            if ($file) return $file;

            // 2. Slug match (separate indexed column)
            $slugified = str_replace(['_', ' '], '-', $mapName);
            $file = File::where('status', 'approved')
                ->where('slug', $slugified)
                ->first();

            if ($file) return $file;

            // 3. Slug starts-with match (last resort, indexed)
            return File::where('status', 'approved')
                ->where('slug', 'LIKE', $mapName . '%')
                ->orderByDesc('download_count')
                ->first();
        });
    }

    public static function getUrl(string $mapName): ?string
    {
        $file = self::findFile($mapName);
        return $file ? route('files.show', $file) : null;
    }

    public static function syncMaps(): int
    {
        $maps = DB::table('tracker_servers')
            ->where('status', 'active')
            ->whereNotNull('current_map')
            ->where('current_map', '!=', '')
            ->select('current_map')
            ->distinct()
            ->pluck('current_map');

        $synced = 0;

        foreach ($maps as $mapName) {
            $cleanName = strtolower(trim($mapName));
            if (empty($cleanName)) continue;

            $file = self::findFile($cleanName);

            $serverCount = DB::table('tracker_servers')
                ->where('status', 'active')
                ->where('is_online', true)
                ->where('current_map', $mapName)
                ->count();

            $existing = TrackerMap::where('name_clean', $cleanName)->first();
            if ($existing) {
                $existing->update([
                    'file_id' => $file->id ?? $existing->file_id,
                    'last_seen_at' => now(),
                    'total_servers' => $serverCount,
                ]);
            } else {
                TrackerMap::create([
                    'name' => $mapName,
                    'name_clean' => $cleanName,
                    'file_id' => $file?->id,
                    'first_seen_at' => now(),
                    'last_seen_at' => now(),
                    'total_servers' => $serverCount,
                ]);
            }
            $synced++;
        }

        return $synced;
    }
}
