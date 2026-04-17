<?php

namespace App\Livewire\Frontend;

use App\Models\File;
use App\Services\Tracker\MapServerActivityService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MapServerActivity extends Component
{
    public File $file;
    public bool $hasAnyMapData = false;

    /**
     * Category IDs that represent actual playable maps.
     * Excludes bots, prefabs, scripts, mods, etc. that may share map names
     * but should not show server activity.
     *
     * 10 = ET Maps, 50 = ETFortress Maps, 56 = RtCW Maps MP,
     * 57 = RtCW Maps SP, 64 = TCE Maps, 39 = ETQW Maps, 47 = ET-Domination Maps
     */
    private const MAP_CATEGORY_IDS = [10, 39, 47, 50, 56, 57, 64];

    public function mount(File $file): void
    {
        $this->file = $file;
        $this->hasAnyMapData = in_array($file->category_id, self::MAP_CATEGORY_IDS, true)
            && $file->trackerMaps()->exists();
    }

    #[Computed]
    public function currentServers()
    {
        if (! $this->hasAnyMapData) {
            return collect();
        }
        return app(MapServerActivityService::class)->currentlyPlayedOn($this->file);
    }

    #[Computed]
    public function recentServers()
    {
        if (! $this->hasAnyMapData) {
            return collect();
        }
        return app(MapServerActivityService::class)->recentlyPlayedOn($this->file);
    }

    public function render()
    {
        return view('livewire.frontend.map-server-activity');
    }
}
