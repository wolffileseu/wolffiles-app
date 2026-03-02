<x-layouts.app :title="'Match Finder'">
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-wrap items-center justify-between mb-6">
        <div><h1 class="text-3xl font-bold text-amber-500">Match Finder</h1><p class="text-gray-400 mt-1">Find scrims, wars and pickup games</p></div>
        <div class="flex gap-3">
            <a href="{{ route('tracker.index') }}" class="text-amber-400 hover:text-amber-300 text-sm">&larr; Back</a>
            @auth<a href="{{ route('tracker.scrims.create') }}" class="px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-500">+ Create Match</a>@endauth
        </div>
    </div>
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('tracker.scrims') }}" class="px-3 py-1.5 rounded-lg text-sm {{ !request('type') ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">All</a>
        @foreach(['1v1','2v2','3v3','5v5','6v6','mix'] as $t)
        <a href="{{ route('tracker.scrims',['type'=>$t]) }}" class="px-3 py-1.5 rounded-lg text-sm {{ request('type')===$t ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">{{ $t }}</a>
        @endforeach
    </div>
    <div class="space-y-4">
        @forelse($scrims as $scrim)
        <div class="bg-gray-800 rounded-lg p-5 border border-gray-700/50 hover:border-amber-500/20 transition">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-2 py-0.5 bg-amber-600/20 text-amber-400 rounded text-xs font-bold">{{ $scrim->game_type }}</span>
                        @if($scrim->skill_level)<span class="px-2 py-0.5 bg-gray-700 text-gray-300 rounded text-xs">{{ ucfirst($scrim->skill_level) }}</span>@endif
                        @if($scrim->region)<span class="px-2 py-0.5 bg-gray-700 text-gray-400 rounded text-xs">{{ $scrim->region }}</span>@endif
                        <span class="px-2 py-0.5 bg-green-600/20 text-green-400 rounded text-xs font-medium">{{ ucfirst($scrim->status) }}</span>
                    </div>
                    <h3 class="text-white font-semibold text-lg">{{ $scrim->title }}</h3>
                    @if($scrim->description)<p class="text-gray-400 text-sm mt-1">{{ Str::limit($scrim->description, 200) }}</p>@endif
                    <div class="flex flex-wrap gap-4 mt-3 text-sm text-gray-500">
                        @if($scrim->clan)<span><a href="{{ route('tracker.clan.show', $scrim->clan) }}" class="text-amber-400 hover:text-amber-300">[{{ $scrim->clan->tag_clean }}]</a></span>@endif
                        @if($scrim->map_preference)<span>Map: {{ $scrim->map_preference }}</span>@endif
                        <span>{{ $scrim->createdBy->name ?? 'Unknown' }}</span>
                        <span>{{ $scrim->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                <div class="text-right">
                    @if($scrim->scheduled_at)
                    <div class="text-amber-400 font-semibold">{{ $scrim->scheduled_at->format('M d, H:i') }}</div>
                    <div class="text-gray-500 text-xs">{{ $scrim->scheduled_at->diffForHumans() }}</div>
                    @else<div class="text-gray-400 text-sm">ASAP</div>@endif
                    @if($scrim->contact_discord)<div class="mt-2"><span class="inline-block px-3 py-1 bg-indigo-600/30 text-indigo-300 rounded text-xs">Discord: {{ $scrim->contact_discord }}</span></div>@endif
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-16">
            <div class="text-4xl mb-4">⚔️</div>
            <div class="text-gray-400 text-lg">No matches posted yet</div>
            <p class="text-gray-500 mt-2">Be the first to create a match request!</p>
            @auth<a href="{{ route('tracker.scrims.create') }}" class="inline-block mt-4 px-6 py-2 bg-amber-600 text-white rounded-lg font-medium hover:bg-amber-500">+ Create Match</a>@endauth
        </div>
        @endforelse
    </div>
    @if($scrims->hasPages())<div class="mt-6">{{ $scrims->links() }}</div>@endif
</div>
</x-layouts.app>
