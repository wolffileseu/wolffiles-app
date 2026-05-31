<x-layouts.app :title="'Clans'">
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-wrap items-center justify-between mb-6">
        <div><h1 class="text-3xl font-bold text-amber-500">Clans</h1><p class="text-gray-400 mt-1">Community clans and teams</p></div>
        <a href="{{ route('tracker.index') }}" class="text-amber-400 hover:text-amber-300">&larr; Back to Tracker</a>
    </div>

    <div class="flex flex-wrap gap-3 mb-6">
        <form method="GET" action="{{ route('tracker.clans') }}" class="flex-1 min-w-[200px]">
            @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search clan tag or name..."
                   class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-sm text-white placeholder-gray-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
        </form>
        <div class="flex gap-2">
            @foreach(['members'=>'Members','elo'=>'ELO','active'=>'Active','playtime'=>'Playtime','recent'=>'Recent'] as $key=>$label)
            <a href="{{ route('tracker.clans', array_merge(request()->except('page'),['sort'=>$key])) }}"
               class="px-3 py-2 rounded-lg text-sm transition {{ ($sort ?? 'members')===$key ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    {{-- Filter row --}}
    <div class="flex flex-wrap gap-2 mb-4">
        <a href="{{ route('tracker.clans', array_merge(request()->except(['filter','page']))) }}"
           class="px-3 py-1.5 rounded-lg text-xs transition {{ empty($filter) ? 'bg-amber-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">All clans</a>
        <a href="{{ route('tracker.clans', array_merge(request()->except('page'),['filter'=>'registered'])) }}"
           class="px-3 py-1.5 rounded-lg text-xs transition {{ ($filter ?? '')==='registered' ? 'bg-amber-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">&#10003; Registered only</a>
        <a href="{{ route('tracker.clans', array_merge(request()->except('page'),['filter'=>'recruiting'])) }}"
           class="px-3 py-1.5 rounded-lg text-xs transition {{ ($filter ?? '')==='recruiting' ? 'bg-amber-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">&#127919; Recruiting now</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($clans as $clan)
        <a href="{{ route('tracker.clan.show', $clan) }}" class="bg-gray-800 rounded-lg p-5 hover:bg-gray-750 transition group border border-gray-700/50 hover:border-amber-500/30">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-gray-700 rounded-lg flex items-center justify-center text-amber-400 font-bold text-sm">{{ strtoupper(substr($clan->tag_clean, 0, 3)) }}</div>
                <div>
                    <div class="text-white font-semibold group-hover:text-amber-400">{{ $clan->registeredClan?->display_tag ?? '[' . $clan->tag_clean . ']' }} {{ $clan->registeredClan?->name ?? $clan->name ?? '' }}</div>
                    @if($clan->registeredClan)
                    <div class="flex gap-1 mt-1">
                        <span class="px-1.5 py-0.5 rounded text-[10px] uppercase tracking-wide bg-green-900/30 text-green-400 border border-green-500/30">&#10003; Registered</span>
                        @if($clan->registeredClan->is_recruiting && $clan->registeredClan->is_published)
                        <span class="px-1.5 py-0.5 rounded text-[10px] uppercase tracking-wide bg-amber-900/30 text-amber-400 border border-amber-500/30">Recruiting</span>
                        @endif
                    </div>
                    @endif
                    @if($clan->country_code)
                    <div class="flex items-center gap-1 mt-0.5">
                        <img src="https://flagcdn.com/{{ strtolower($clan->country_code) }}.svg" class="w-3 h-2" alt="{{ strtoupper($clan->country_code) }} flag">
                        <span class="text-gray-500 text-xs">{{ $clan->country ?? strtoupper($clan->country_code) }}</span>
                    </div>
                    @endif
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2 text-center">
                <div><div class="text-white font-bold">{{ $clan->active_member_count ?? $clan->member_count }}</div><div class="text-gray-500 text-xs">Active</div></div>
                <div><div class="text-amber-400 font-bold">{{ number_format($clan->avg_elo) }}</div><div class="text-gray-500 text-xs">ELO</div></div>
                <div><div class="text-gray-300 font-bold">{{ round(($clan->total_play_time_minutes ?? 0) / 60, 1) }}h</div><div class="text-gray-500 text-xs">Playtime</div></div>
            </div>
            @if($clan->last_seen_at)
            <div class="mt-3 pt-3 border-t border-gray-700/50 text-gray-500 text-xs">Last active: {{ $clan->last_seen_at->diffForHumans() }}</div>
            @endif
        </a>
        @empty
        <div class="col-span-full text-center py-12 text-gray-500">No clans match your filters.</div>
        @endforelse
    </div>
    @if($clans->hasPages()) <div class="mt-6">{{ $clans->links() }}</div> @endif
</div>
</x-layouts.app>
