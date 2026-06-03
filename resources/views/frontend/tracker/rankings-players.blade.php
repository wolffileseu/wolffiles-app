<x-layouts.app :title="'Player Rankings'">
<div class="max-w-7xl mx-auto px-4 py-8">

    <div class="flex flex-wrap items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-amber-500">Player Rankings</h1>
            <p class="text-gray-400 mt-1">Top players over the last 30 days · {{ number_format($rankings->total()) }} ranked players</p>
        </div>
        <a href="{{ route('tracker.rankings') }}" class="text-amber-400 hover:text-amber-300">&larr; Rankings Overview</a>
    </div>

    {{-- Sub-Nav: Servers / Players --}}
    <div class="flex gap-2 mb-4 border-b border-gray-700 pb-4">
        <a href="{{ route('tracker.rankings.servers', ['game' => $game]) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-700 text-gray-300 hover:bg-gray-600">🖥️ Servers</a>
        <a href="{{ route('tracker.rankings.players', ['game' => $game]) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium bg-amber-600 text-white">🎮 Players</a>
    </div>

    {{-- Game Family Tabs --}}
    <div class="flex gap-2 mb-6">
        <a href="{{ route('tracker.rankings.players', ['game' => 'et']) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $game === 'et' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
           Enemy Territory
        </a>
        <a href="{{ route('tracker.rankings.players', ['game' => 'rtcw']) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $game === 'rtcw' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
           Return to Castle Wolfenstein
        </a>
    </div>

    @php
        $sortHeader = function($column, $label) use ($sort, $dir) {
            $ascFirst = in_array($column, ['rank', 'name']);
            $defaultDir = $ascFirst ? 'asc' : 'desc';
            if ($sort === $column) {
                $nextDir = $dir === 'asc' ? 'desc' : 'asc';
                $arrow = $dir === 'asc' ? ' ↑' : ' ↓';
                $activeClass = 'text-amber-400 font-semibold';
            } else {
                $nextDir = $defaultDir;
                $arrow = '';
                $activeClass = '';
            }
            $url = request()->fullUrlWithQuery(['sort' => $column, 'dir' => $nextDir, 'page' => null]);
            return '<a href="' . e($url) . '" class="hover:text-amber-300 ' . $activeClass . '">' . e($label) . $arrow . '</a>';
        };
    @endphp

    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-gray-400 text-left bg-gray-900/50">
                    <tr>
                        <th class="px-3 py-3 w-16">{!! $sortHeader('rank', '#') !!}</th>
                        <th class="px-3 py-3">{!! $sortHeader('name', 'Player') !!}</th>
                        <th class="px-3 py-3">Country</th>
                        <th class="px-3 py-3 text-center">{!! $sortHeader('playtime', 'Playtime') !!}</th>
                        <th class="px-3 py-3 text-center">{!! $sortHeader('sessions', 'Sessions') !!}</th>
                        <th class="px-3 py-3 text-center">{!! $sortHeader('unique_servers', 'Servers') !!}</th>
                        <th class="px-3 py-3 text-center">{!! $sortHeader('unique_maps', 'Maps') !!}</th>
                        <th class="px-3 py-3 text-center">{!! $sortHeader('elo', 'ELO') !!}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    @forelse($rankings as $p)
                    <tr class="hover:bg-gray-700/30 transition">
                        <td class="px-3 py-2.5 text-gray-500 font-bold">{{ $p->rank }}</td>
                        <td class="px-3 py-2.5">
                            <a href="{{ route('tracker.player.show', $p->player_id) }}" class="text-amber-400 hover:text-amber-300 font-medium">
                                {!! $p->name_html ?? e($p->name_clean ?? 'Unknown') !!}
                            </a>
                        </td>
                        <td class="px-3 py-2.5">
                            @if($p->country_code)
                            <span class="flex items-center gap-1.5">
                                <img src="https://flagcdn.com/{{ strtolower($p->country_code) }}.svg" class="w-4 h-3 rounded-sm" alt="{{ strtoupper($p->country_code) }} flag">
                                <span class="text-gray-400 text-xs">{{ strtoupper($p->country_code) }}</span>
                            </span>
                            @else
                                <span class="text-gray-600 text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-center font-bold text-white">{{ number_format((int)($p->playtime_minutes_30d / 60)) }}h</td>
                        <td class="px-3 py-2.5 text-center text-gray-300">{{ number_format($p->sessions_count_30d) }}</td>
                        <td class="px-3 py-2.5 text-center text-gray-300">{{ $p->unique_servers_30d }}</td>
                        <td class="px-3 py-2.5 text-center text-gray-300">{{ $p->unique_maps_30d }}</td>
                        <td class="px-3 py-2.5 text-center text-gray-400">
                            @if($p->elo_rating !== null)
                                {{ number_format($p->elo_rating) }}
                            @else
                                <span class="text-gray-600">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-4 py-12 text-center text-gray-500">No player rankings computed yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($rankings->hasPages()) <div class="mt-4">{{ $rankings->links() }}</div> @endif

    @if($lastComputed)
    <p class="text-xs text-gray-500 mt-6 text-center">Last computed: {{ \Carbon\Carbon::parse($lastComputed)->diffForHumans() }}</p>
    @endif
</div>
</x-layouts.app>
