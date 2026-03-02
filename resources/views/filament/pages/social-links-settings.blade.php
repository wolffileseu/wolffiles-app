<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex items-center gap-4">
            <x-filament::button type="submit">
                Save Social Links
            </x-filament::button>

            <a href="{{ url('/') }}" target="_blank" class="text-sm text-gray-400 hover:text-white">
                Preview on Site →
            </a>
        </div>
    </form>

    <div class="mt-8 p-4 bg-gray-800 rounded-lg border border-gray-700">
        <h3 class="text-sm font-semibold text-gray-400 mb-2">Available Platforms & Default Icons</h3>
        <div class="flex flex-wrap gap-3 text-xs text-gray-500">
            <span>Discord</span> · <span>Reddit</span> · <span>Facebook</span> · <span>Twitter/X</span> · <span>YouTube</span> · <span>Twitch</span> · <span>GitHub</span> · <span>Steam</span> · <span>Instagram</span> · <span>TikTok</span> · <span>Mastodon</span> · <span>Bluesky</span> · <span>Custom (with SVG)</span>
        </div>
    </div>
</x-filament-panels::page>
