<x-layouts.app>

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

<div class="max-w-5xl mx-auto px-4 py-8" x-data="{
    search: '',
    filter: 'all',
    page: 1,
    perPage: 50,
    maps: @js($maps),
    game: '{{ $game }}',
    get filteredMaps() {
        let m = this.maps;
        let s = this.search.toLowerCase();
        if (s) m = m.filter(x => x.name.includes(s));
        if (this.filter === 'complete') m = m.filter(x => x.status === 'complete');
        if (this.filter === 'incomplete') m = m.filter(x => x.status === 'incomplete');
        return m;
    },
    get totalPages() { return Math.max(1, Math.ceil(this.filteredMaps.length / this.perPage)); },
    get pagedMaps() { return this.filteredMaps.slice((this.page-1)*this.perPage, this.page*this.perPage); }
}">
    {{-- Header --}}
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">🤖 {{ __('messages.ob_title') }}</h1>
        <p class="text-gray-400">{{ __('messages.ob_subtitle') }}</p>
    </div>

    {{-- Game Tabs --}}
    <div class="flex justify-center gap-4 mb-6">
        <a href="{{ route('tools.omnibot', ['game' => 'et']) }}"
           class="px-6 py-3 rounded-lg font-bold text-lg transition {{ $game === 'et' ? 'bg-emerald-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
            🎮 Enemy Territory
            <span class="ml-2 text-sm opacity-75">({{ $etCount }})</span>
        </a>
        <a href="{{ route('tools.omnibot', ['game' => 'rtcw']) }}"
           class="px-6 py-3 rounded-lg font-bold text-lg transition {{ $game === 'rtcw' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
            🏰 Return to Castle Wolfenstein
            <span class="ml-2 text-sm opacity-75">({{ $rtcwCount }})</span>
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-800 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-white">{{ $totalMaps }}</div>
            <div class="text-xs text-gray-400">{{ __('messages.ob_total_maps') }}</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-emerald-400">{{ $totalComplete }}</div>
            <div class="text-xs text-gray-400">{{ __('messages.ob_complete') }}</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-yellow-400">r{{ $svnRevision }}</div>
            <div class="text-xs text-gray-400">{{ __('messages.ob_revision') }}</div>
        </div>
    </div>

    {{-- Search + Filter --}}
    <div class="flex gap-4 mb-4">
        <input type="text" x-model="search" @input="page=1" placeholder="{{ __('messages.ob_search_placeholder') }}"
               class="flex-1 bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:border-yellow-500 focus:outline-none">
        <select x-model="filter" @change="page=1" class="bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white">
            <option value="all">{{ __('messages.ob_all') }}</option>
            <option value="complete">✓ {{ __('messages.ob_complete') }}</option>
            <option value="incomplete">◐ {{ __('messages.ob_incomplete') }}</option>
        </select>
    </div>

    {{-- Download All + Info --}}
    <div class="flex justify-between items-center mb-4">
        <span class="text-xs text-gray-500"><span x-text="filteredMaps.length"></span> {{ __('messages.ob_maps_shown') }}</span>
        <div class="flex gap-3">
            <a href="https://github.com/wolffileseu/omnibot-waypoints" target="_blank"
               class="text-xs px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition">
                📂 {{ __('messages.ob_github') }}
            </a>
            <a href="{{ route('tools.omnibot.download-all', ['game' => $game]) }}"
               class="text-xs px-3 py-1.5 bg-yellow-700 hover:bg-yellow-600 text-white rounded-lg transition">
                📦 {{ __('messages.ob_download_full') }}
            </a>
        </div>
    </div>

    {{-- Table Header --}}
    <div class="hidden md:grid grid-cols-12 gap-2 text-xs text-gray-500 uppercase tracking-wider px-4 py-2 border-b border-gray-700">
        <div class="col-span-5">{{ __('messages.ob_map_name') }}</div>
        <div class="col-span-2">{{ __('messages.ob_status') }}</div>
        <div class="col-span-3">{{ __('messages.ob_files') }}</div>
        <div class="col-span-2 text-right">{{ __('messages.ob_action') }}</div>
    </div>

    {{-- Map List --}}
    <template x-for="map in pagedMaps" :key="map.name">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center px-4 py-3 border-b border-gray-800 hover:bg-gray-800/50 transition">
            <div class="md:col-span-5">
                <span class="font-mono text-sm text-gray-200" x-text="map.name"></span>
            </div>
            <div class="md:col-span-2">
                <span x-show="map.status==='complete'" class="text-emerald-400 text-xs font-semibold">✓ {{ __('messages.ob_complete') }}</span>
                <span x-show="map.status==='incomplete'" class="text-yellow-400 text-xs font-semibold">◐ {{ __('messages.ob_incomplete') }}</span>
            </div>
            <div class="md:col-span-3 flex gap-1 flex-wrap">
                <span x-show="map.has_way" class="text-xs px-1.5 py-0.5 rounded bg-emerald-900/50 text-emerald-300 border border-emerald-800">.way</span>
                <span x-show="map.has_gm" class="text-xs px-1.5 py-0.5 rounded bg-blue-900/50 text-blue-300 border border-blue-800">.gm</span>
                <span x-show="map.has_goals" class="text-xs px-1.5 py-0.5 rounded bg-purple-900/50 text-purple-300 border border-purple-800">_goals.gm</span>
            </div>
            <div class="md:col-span-2 text-right">
                <a :href="'/tools/omni-bot/download/' + encodeURIComponent(map.name) + '?game={{ $game }}'"
                   class="inline-flex items-center gap-1 text-xs px-3 py-1.5 bg-gray-700 hover:bg-yellow-700 text-gray-300 hover:text-white rounded-lg transition">
                    ⬇ {{ __('messages.ob_download') }}
                </a>
            </div>
        </div>
    </template>

    {{-- Pagination --}}
    <div class="flex justify-center items-center gap-4 mt-6" x-show="totalPages > 1">
        <button @click="page = Math.max(1, page-1)" :disabled="page===1"
                class="px-3 py-1 bg-gray-700 text-white rounded disabled:opacity-30">←</button>
        <span class="text-xs text-gray-500 font-mono" x-text="page+' / '+totalPages"></span>
        <button @click="page = Math.min(totalPages, page+1)" :disabled="page===totalPages"
                class="px-3 py-1 bg-gray-700 text-white rounded disabled:opacity-30">→</button>
    </div>

    {{-- Footer Info --}}
    <div class="text-center mt-8 text-xs text-gray-600">
        {{ __('messages.ob_last_sync') }}: {{ $lastSync ? \Carbon\Carbon::parse($lastSync)->format('d.m.Y H:i') : 'N/A' }}
        · {{ __('messages.ob_source') }}: <a href="https://github.com/wolffileseu/omnibot-waypoints" class="text-yellow-600 hover:underline" target="_blank">GitHub Repository</a>
    </div>
</div>

</x-layouts.app>
