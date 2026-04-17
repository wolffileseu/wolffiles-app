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

    public function mount(File $file): void
    {
        $this->file = $file;
        $this->hasAnyMapData = $file->trackerMaps()->exists();
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
