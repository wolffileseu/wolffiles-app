<x-layouts.app :title="'Server Rankings'">
<div class="max-w-7xl mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-amber-500">Server Rankings</h1>
            <p class="text-gray-400 mt-1">Top servers over the last 30 days · {{ $rankings->total() }} active servers</p>
        </div>
        <a href="{{ route('tracker.rankings') }}" class="text-amber-400 hover:text-amber-300">&larr; Rankings Overview</a>
    </div>

    {{-- Sub-Nav: Servers / Players --}}
    <div class="flex gap-2 mb-4 border-b border-gray-700 pb-4">
        <a href="{{ route('tracker.rankings.servers', ['game' => $game]) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium bg-amber-600 text-white">🖥️ Servers</a>
        <a href="{{ route('tracker.rankings.players', ['game' => $game]) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-700 text-gray-300 hover:bg-gray-600">🎮 Players</a>
    </div>

    {{-- Game Family Tabs --}}
    <div class="flex gap-2 mb-6">
        <a href="{{ route('tracker.rankings.servers', ['game' => 'et']) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $game === 'et' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
           Enemy Territory
        </a>
        <a href="{{ route('tracker.rankings.servers', ['game' => 'rtcw']) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $game === 'rtcw' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
           Return to Castle Wolfenstein
        </a>
    </div>

    @php
        $sortHeader = function($column, $label, $align = 'left') use ($sort, $dir) {
            $ascFirst = in_array($column, ['rank', 'hostname']);
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

    {{-- Table --}}
    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-gray-400 text-left bg-gray-900/50">
                    <tr>
                        <th class="px-3 py-3 w-16">{!! $sortHeader('rank', '#') !!}</th>
                        <th class="px-3 py-3">{!! $sortHeader('hostname', 'Server') !!}</th>
                        <th class="px-3 py-3">Country</th>
                        <th class="px-3 py-3 text-center">{!! $sortHeader('avg_players', 'Ø Players') !!}</th>
                        <th class="px-3 py-3 text-center">{!! $sortHeader('peak_players', 'Peak') !!}</th>
                        <th class="px-3 py-3 text-center">{!! $sortHeader('playtime', 'Playtime') !!}</th>
                        <th class="px-3 py-3 text-center">{!! $sortHeader('unique_players', 'Unique') !!}</th>
                        <th class="px-3 py-3 text-center">{!! $sortHeader('uptime', 'Uptime') !!}</th>
                        <th class="px-3 py-3 text-center">Now</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    @forelse($rankings as $r)
                    <tr class="hover:bg-gray-700/30 transition">
                        <td class="px-3 py-2.5 text-gray-500 font-bold">{{ $r->rank }}</td>
                        <td class="px-3 py-2.5">
                            <a href="{{ route('tracker.server.show', $r->server_id) }}" class="text-amber-400 hover:text-amber-300 font-medium">
                                {{ $r->hostname_clean ?? 'Unknown Server' }}
                            </a>
                        </td>
                        <td class="px-3 py-2.5 text-gray-400 text-xs">{{ $r->country ?? '-' }}</td>
                        <td class="px-3 py-2.5 text-center font-bold text-white">{{ number_format($r->avg_players_30d, 2) }}</td>
                        <td class="px-3 py-2.5 text-center text-gray-300">{{ $r->peak_players_30d }}</td>
                        <td class="px-3 py-2.5 text-center text-gray-400">{{ number_format((int)($r->total_playtime_minutes_30d / 60)) }}h</td>
                        <td class="px-3 py-2.5 text-center text-gray-300">{{ number_format($r->unique_players_30d) }}</td>
                        <td class="px-3 py-2.5 text-center text-gray-400">
                            @if($r->total_polls_30d > 0)
                                {{ round($r->online_polls_30d / $r->total_polls_30d * 100, 1) }}%
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-center">
                            @if($r->is_online)
                                <span class="text-green-400 text-xs">● {{ $r->current_players }}/{{ $r->max_players }}</span>
                            @else
                                <span class="text-gray-600 text-xs">offline</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-4 py-12 text-center text-gray-500">No server rankings computed yet.</td></tr>
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
