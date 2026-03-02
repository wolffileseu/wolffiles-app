<x-layouts.app :title="'Player Compare'">
<div class="max-w-5xl mx-auto px-4 py-8">
    <div class="flex flex-wrap items-center justify-between mb-6">
        <div><h1 class="text-3xl font-bold text-amber-500">Player Compare</h1><p class="text-gray-400 mt-1">Compare two players side by side</p></div>
        <a href="{{ route('tracker.players') }}" class="text-amber-400 hover:text-amber-300">&larr; Back to Players</a>
    </div>

    {{-- Player Selection --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        {{-- Player 1 --}}
        <div class="bg-gray-800 rounded-lg p-4">
            <label class="text-gray-400 text-sm mb-2 block">Player 1</label>
            @if($player1)
            <div class="flex items-center justify-between bg-gray-700 rounded-lg p-3">
                <a href="{{ route('tracker.player.show', $player1) }}" class="text-amber-400 hover:text-amber-300 font-medium">{!! $player1->name_html ?? e($player1->name_clean ?? 'Unknown') !!}</a>
                <a href="{{ route('tracker.compare', $player2 ? ['p2' => $player2->id] : []) }}" class="text-gray-500 hover:text-red-400 text-sm">✕</a>
            </div>
            @else
            <form method="GET" action="{{ route('tracker.compare') }}" class="flex gap-2">
                @if($player2)<input type="hidden" name="p2" value="{{ $player2->id }}">@endif
                <input type="text" name="search" placeholder="Search player..." class="flex-1 bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-amber-500">
                <button type="submit" class="px-3 py-2 bg-amber-600 text-white rounded-lg text-sm hover:bg-amber-500">Search</button>
            </form>
            @endif
        </div>

        {{-- Player 2 --}}
        <div class="bg-gray-800 rounded-lg p-4">
            <label class="text-gray-400 text-sm mb-2 block">Player 2</label>
            @if($player2)
            <div class="flex items-center justify-between bg-gray-700 rounded-lg p-3">
                <a href="{{ route('tracker.player.show', $player2) }}" class="text-blue-400 hover:text-blue-300 font-medium">{!! $player2->name_html ?? e($player2->name_clean ?? 'Unknown') !!}</a>
                <a href="{{ route('tracker.compare', $player1 ? ['p1' => $player1->id] : []) }}" class="text-gray-500 hover:text-red-400 text-sm">✕</a>
            </div>
            @else
            <form method="GET" action="{{ route('tracker.compare') }}" class="flex gap-2">
                @if($player1)<input type="hidden" name="p1" value="{{ $player1->id }}">@endif
                <input type="text" name="search" placeholder="Search player..." class="flex-1 bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-amber-500">
                <button type="submit" class="px-3 py-2 bg-amber-600 text-white rounded-lg text-sm hover:bg-amber-500">Search</button>
            </form>
            @endif
        </div>
    </div>

    {{-- Search Results --}}
    @if($searchResults && $searchResults->count())
    <div class="bg-gray-800 rounded-lg p-4 mb-8">
        <h3 class="text-white font-semibold mb-3">Select a player</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
            @foreach($searchResults as $p)
            @php
                $link = $player1
                    ? route('tracker.compare', ['p1' => $player1->id, 'p2' => $p->id])
                    : route('tracker.compare', ['p1' => $p->id, 'p2' => $player2?->id]);
            @endphp
            <a href="{{ $link }}" class="flex items-center gap-2 bg-gray-700 rounded-lg px-3 py-2 hover:bg-gray-600 transition">
                @if($p->country_code)<img src="https://flagcdn.com/{{ strtolower($p->country_code) }}.svg" class="w-4 h-3 rounded-sm" alt="">@endif
                <span class="text-white text-sm truncate">{!! $p->name_html ?? e($p->name_clean ?? 'Unknown') !!}</span>
                <span class="text-gray-500 text-xs ml-auto">ELO {{ number_format($p->elo_rating) }}</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Comparison Table --}}
    @if($comparison)
    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <div class="grid grid-cols-3 bg-gray-900/50 border-b border-gray-700">
            <div class="px-4 py-3 text-amber-400 font-semibold text-right">{!! $player1->name_html ?? e($player1->name_clean ?? 'Unknown') !!}</div>
            <div class="px-4 py-3 text-center text-gray-500 text-sm">vs</div>
            <div class="px-4 py-3 text-blue-400 font-semibold">{!! $player2->name_html ?? e($player2->name_clean ?? 'Unknown') !!}</div>
        </div>
        @foreach($comparison['metrics'] as $m)
        <div class="grid grid-cols-3 items-center border-b border-gray-700/50 hover:bg-gray-750">
            <div class="px-4 py-3 text-right">
                <span class="font-bold {{ $m['winner'] === 1 ? 'text-green-400' : 'text-white' }}">
                    @if($m['fmt'] === 'time')
                        {{ $m['v1'] >= 60 ? round($m['v1']/60, 1).'h' : $m['v1'].'m' }}
                    @elseif($m['fmt'] === 'kd')
                        {{ number_format($m['v1']/100, 2) }}
                    @else
                        {{ number_format($m['v1']) }}
                    @endif
                </span>
                @if($m['winner'] === 1) <span class="text-green-400 text-xs ml-1">✓</span> @endif
            </div>
            <div class="px-4 py-3 text-center">
                <span class="text-gray-400 text-sm">{{ $m['label'] }}</span>
                @php $max = max($m['v1'], $m['v2'], 1); @endphp
                <div class="flex gap-1 mt-1">
                    <div class="flex-1 h-1.5 rounded-full bg-gray-700 overflow-hidden"><div class="h-full bg-amber-500 rounded-full float-right" style="width:{{ round(($m['v1']/$max)*100) }}%"></div></div>
                    <div class="flex-1 h-1.5 rounded-full bg-gray-700 overflow-hidden"><div class="h-full bg-blue-500 rounded-full" style="width:{{ round(($m['v2']/$max)*100) }}%"></div></div>
                </div>
            </div>
            <div class="px-4 py-3">
                @if($m['winner'] === 2) <span class="text-green-400 text-xs mr-1">✓</span> @endif
                <span class="font-bold {{ $m['winner'] === 2 ? 'text-green-400' : 'text-white' }}">
                    @if($m['fmt'] === 'time')
                        {{ $m['v2'] >= 60 ? round($m['v2']/60, 1).'h' : $m['v2'].'m' }}
                    @elseif($m['fmt'] === 'kd')
                        {{ number_format($m['v2']/100, 2) }}
                    @else
                        {{ number_format($m['v2']) }}
                    @endif
                </span>
            </div>
        </div>
        @endforeach
        <div class="grid grid-cols-2 gap-4 p-4 bg-gray-900/30">
            <div class="text-center"><div class="text-lg font-bold text-purple-400">{{ $comparison['shared_servers'] }}</div><div class="text-gray-500 text-xs">Shared Servers</div></div>
            <div class="text-center"><div class="text-lg font-bold text-purple-400">{{ $comparison['shared_maps'] }}</div><div class="text-gray-500 text-xs">Shared Maps</div></div>
        </div>
    </div>
    @elseif(!$player1 && !$player2 && !$searchResults)
    <div class="text-center py-16 bg-gray-800 rounded-lg">
        <div class="text-4xl mb-4">⚔️</div>
        <p class="text-gray-400">Search for two players to compare their stats</p>
    </div>
    @endif
</div>
</x-layouts.app>
