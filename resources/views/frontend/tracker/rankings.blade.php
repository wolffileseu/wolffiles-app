<x-layouts.app :title="'Rankings'">
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-wrap items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-amber-500">Rankings</h1>
            <p class="text-gray-400 mt-1">Top servers & players over the last 30 days</p>
        </div>
        <a href="{{ route('tracker.index') }}" class="text-amber-400 hover:text-amber-300">&larr; Back to Tracker</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- ============ SERVERS CARD ============ --}}
        <div class="bg-gray-800 rounded-lg overflow-hidden">
            <div class="px-5 py-4 bg-gray-900/60 border-b border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-white">🖥️ Top Servers</h2>
                    <a href="{{ route('tracker.rankings.servers') }}"
                       class="text-sm text-amber-400 hover:text-amber-300">View all &rarr;</a>
                </div>
            </div>

            @foreach(['et' => 'Enemy Territory', 'rtcw' => 'Return to Castle Wolfenstein'] as $fam => $famLabel)
            <div class="p-5 {{ $fam === 'et' ? 'border-b border-gray-700/50' : '' }}">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-amber-400 uppercase tracking-wider">{{ $famLabel }}</h3>
                    <a href="{{ route('tracker.rankings.servers', ['game' => $fam]) }}"
                       class="text-xs text-gray-400 hover:text-amber-300">More &rarr;</a>
                </div>
                @forelse($data[$fam]['servers'] as $s)
                <a href="{{ route('tracker.server.show', $s->server_id) }}"
                   class="flex items-center gap-3 py-2 px-2 rounded hover:bg-gray-700/50 transition">
                    <span class="text-gray-500 w-6 text-center text-sm font-bold">#{{ $s->rank }}</span>
                    <span class="flex-1 text-sm text-gray-200 truncate">{{ $s->hostname_clean ?? 'Unknown Server' }}</span>
                    <span class="text-xs text-gray-400 whitespace-nowrap">Ø {{ number_format($s->avg_players_30d, 1) }} / peak {{ $s->peak_players_30d }}</span>
                </a>
                @empty
                <p class="text-sm text-gray-500 italic py-2">No data yet.</p>
                @endforelse
            </div>
            @endforeach
        </div>

        {{-- ============ PLAYERS CARD ============ --}}
        <div class="bg-gray-800 rounded-lg overflow-hidden">
            <div class="px-5 py-4 bg-gray-900/60 border-b border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-white">🎮 Top Players</h2>
                    <a href="{{ route('tracker.rankings.players') }}"
                       class="text-sm text-amber-400 hover:text-amber-300">View all &rarr;</a>
                </div>
            </div>

            @foreach(['et' => 'Enemy Territory', 'rtcw' => 'Return to Castle Wolfenstein'] as $fam => $famLabel)
            <div class="p-5 {{ $fam === 'et' ? 'border-b border-gray-700/50' : '' }}">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-amber-400 uppercase tracking-wider">{{ $famLabel }}</h3>
                    <a href="{{ route('tracker.rankings.players', ['game' => $fam]) }}"
                       class="text-xs text-gray-400 hover:text-amber-300">More &rarr;</a>
                </div>
                @forelse($data[$fam]['players'] as $p)
                <a href="{{ route('tracker.player.show', $p->player_id) }}"
                   class="flex items-center gap-3 py-2 px-2 rounded hover:bg-gray-700/50 transition">
                    <span class="text-gray-500 w-6 text-center text-sm font-bold">#{{ $p->rank }}</span>
                    @if($p->country_code)
                    <img src="https://flagcdn.com/{{ strtolower($p->country_code) }}.svg" class="w-4 h-3 rounded-sm" alt="">
                    @endif
                    <span class="flex-1 text-sm text-gray-200 truncate">{!! $p->name_html ?? e($p->name_clean ?? 'Unknown') !!}</span>
                    <span class="text-xs text-gray-400 whitespace-nowrap">
                        {{ (int)($p->playtime_minutes_30d / 60) }}h
                        @if($p->elo_rating) · ELO {{ $p->elo_rating }} @endif
                    </span>
                </a>
                @empty
                <p class="text-sm text-gray-500 italic py-2">No data yet.</p>
                @endforelse
            </div>
            @endforeach
        </div>
    </div>

    @if($lastComputed)
    <p class="text-xs text-gray-500 mt-6 text-center">Last computed: {{ \Carbon\Carbon::parse($lastComputed)->diffForHumans() }}</p>
    @endif
</div>
</x-layouts.app>
