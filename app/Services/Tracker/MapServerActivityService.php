<?php

namespace App\Services\Tracker;

use App\Models\File;
use App\Models\Tracker\TrackerServer;
use App\Models\Tracker\TrackerServerMapStat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MapServerActivityService
{
    private const CACHE_TTL = 30; // seconds (matches tracker poll interval)
    private const DEFAULT_RECENT_HOURS = 168; // 7 days

    public function mapNamesFor(File $file): array
    {
        return $file->trackerMaps()
            ->whereNotNull('name')
            ->pluck('name')
            ->all();
    }

    public function currentlyPlayedOn(File $file): Collection
    {
        $mapNames = $this->mapNamesFor($file);
        if (empty($mapNames)) {
            return collect();
        }

        return Cache::remember(
            "map-activity:current:{$file->id}",
            self::CACHE_TTL,
            fn () => TrackerServer::query()
                ->whereIn('current_map', $mapNames)
                ->where('is_online', true)
                ->where('is_private', false)
                ->orderByDesc('current_players')
                ->orderByDesc('last_seen_at')
                ->limit(20)
                ->get()
        );
    }

    public function recentlyPlayedOn(File $file, int $hours = self::DEFAULT_RECENT_HOURS): Collection
    {
        $mapNames = $this->mapNamesFor($file);
        if (empty($mapNames)) {
            return collect();
        }

        $excludeIds = $this->currentlyPlayedOn($file)->pluck('id')->all();

        return Cache::remember(
            "map-activity:recent:{$file->id}:{$hours}",
            self::CACHE_TTL,
            function () use ($mapNames, $hours, $excludeIds) {
                $serverLastPlayed = TrackerServerMapStat::query()
                    ->whereIn('map_name', $mapNames)
                    ->where('last_played_at', '>=', now()->subHours($hours))
                    ->when(!empty($excludeIds), fn ($q) => $q->whereNotIn('server_id', $excludeIds))
                    ->select('server_id', DB::raw('MAX(last_played_at) as last_played_at'))
                    ->groupBy('server_id')
                    ->orderByDesc('last_played_at')
                    ->limit(10)
                    ->get()
                    ->keyBy('server_id');

                if ($serverLastPlayed->isEmpty()) {
                    return collect();
                }

                $servers = TrackerServer::query()
                    ->whereIn('id', $serverLastPlayed->keys())
                    ->where('is_private', false)
                    ->get();

                return $servers
                    ->each(function ($s) use ($serverLastPlayed) {
                        $s->setAttribute('map_last_played_at', $serverLastPlayed[$s->id]->last_played_at);
                    })
                    ->sortByDesc('map_last_played_at')
                    ->values();
            }
        );
    }
}
