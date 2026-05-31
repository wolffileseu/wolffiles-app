<x-layouts.app :title="'Recruiting Clans'">
<div class="max-w-7xl mx-auto px-4 py-8">

    <div class="flex flex-wrap items-center justify-between mb-6 gap-3">
        <div>
            <h1 class="text-3xl font-bold text-amber-500">Recruiting Clans</h1>
            <p class="text-gray-400 mt-1">Clans currently looking for new members</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('tracker.clans') }}" class="text-amber-400 hover:text-amber-300 text-sm">All clans &rarr;</a>
        </div>
    </div>

    @if($clans->total() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($clans as $clan)
        @php
            $tc = $clan->trackerClan;
            $logoUrl = $clan->logo ? (\Illuminate\Support\Str::startsWith($clan->logo, ['http://','https://']) ? $clan->logo : asset('storage/'.$clan->logo)) : null;
            $latestNews = $clan->news->first();
            $memberCount = $tc?->member_count ?? 0;
            $activeCount = $tc?->active_member_count ?? 0;
            $lastSeen = $tc?->last_seen_at;
        @endphp
        <div class="bg-gray-800 rounded-lg p-5 border border-gray-700/50 hover:border-amber-500/40 transition flex flex-col">

            {{-- Header: Logo + Tag/Name --}}
            <div class="flex items-center gap-3 mb-3">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="" class="w-12 h-12 rounded-lg object-cover bg-gray-700">
                @else
                    <div class="w-12 h-12 bg-gray-700 rounded-lg flex items-center justify-center text-amber-400 font-bold text-sm">{{ strtoupper(substr($clan->tag ?? '?', 0, 3)) }}</div>
                @endif
                <div class="flex-1 min-w-0">
                    <div class="text-white font-semibold truncate">{{ $clan->display_tag }} {{ $clan->name }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">
                        @if($clan->location){{ $clan->location }}@endif
                        @if($clan->location && $clan->founded) · @endif
                        @if($clan->founded)since {{ $clan->founded }}@endif
                    </div>
                </div>
            </div>

            {{-- Recruitment summary --}}
            @if($clan->recruitment_summary)
            <p class="text-sm text-gray-300 mb-3 line-clamp-3">{{ $clan->recruitment_summary }}</p>
            @endif

            {{-- Stats row --}}
            <div class="grid grid-cols-3 gap-2 text-center text-xs mb-3 pt-3 border-t border-gray-700/50">
                <div>
                    <div class="text-white font-bold">{{ $activeCount }}<span class="text-gray-500">/{{ $memberCount }}</span></div>
                    <div class="text-gray-500">Active</div>
                </div>
                <div>
                    <div class="text-amber-400 font-bold">{{ $clan->news_count }}</div>
                    <div class="text-gray-500">News</div>
                </div>
                <div>
                    <div class="text-gray-300 font-bold text-[11px]">{{ $lastSeen ? $lastSeen->diffForHumans(null, true) : '—' }}</div>
                    <div class="text-gray-500">Last seen</div>
                </div>
            </div>

            {{-- Latest news teaser --}}
            @if($latestNews)
            <div class="text-xs text-gray-400 mb-3 pb-3 border-b border-gray-700/50">
                <span class="text-gray-500 uppercase tracking-wide">Latest:</span>
                <span class="text-gray-300">{{ \Illuminate\Support\Str::limit($latestNews->title, 50) }}</span>
                <span class="text-gray-600">· {{ $latestNews->published_at?->diffForHumans() }}</span>
            </div>
            @endif

            {{-- Actions: pinned to bottom --}}
            <div class="mt-auto flex gap-2">
                <a href="{{ route('tracker.clan.show', $tc?->id ?? $clan->id) }}" class="flex-1 px-3 py-2 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold text-sm rounded text-center transition">Apply &rarr;</a>
                <a href="{{ route('tracker.clan.show', $tc?->id ?? $clan->id) }}" class="px-3 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 text-sm rounded text-center transition">Details</a>
            </div>
        </div>
        @endforeach
    </div>

    @if($clans->hasPages())
    <div class="mt-6">{{ $clans->links() }}</div>
    @endif

    @else
    <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-12 text-center">
        <p class="text-gray-400 text-lg">No clans are currently recruiting.</p>
        <p class="text-gray-500 text-sm mt-2">Check back later or browse <a href="{{ route('tracker.clans') }}" class="text-amber-400 hover:text-amber-300">all clans</a>.</p>
    </div>
    @endif

</div>
</x-layouts.app>
