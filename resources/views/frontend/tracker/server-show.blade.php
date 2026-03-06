<x-layouts.app :title="$server->hostname_clean ?: $server->full_address">
<div class="max-w-7xl mx-auto px-4 py-8"
     x-data="serverLive()"
     x-init="startPolling()">

    {{-- Back --}}
    <a href="{{ route('tracker.servers') }}" class="text-amber-400 hover:text-amber-300 text-sm">← {{ __('messages.back_to_servers') }}</a>

    {{-- Server Header --}}
    <div class="bg-gray-800 rounded-lg p-6 mt-4 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <span class="inline-block w-3 h-3 rounded-full" :class="isOnline ? 'bg-green-500' : 'bg-red-500'"></span>
                    <h1 class="text-2xl font-bold text-white">{!! $server->hostname_html ?: e($server->hostname_clean ?: $server->full_address) !!}</h1>
                </div>
                <div class="flex flex-wrap gap-4 mt-3 text-sm text-gray-400">
                    <span>{{ $server->ip }}:{{ $server->port }}</span>
                    <span style="border-left: 3px solid {{ $server->game->color }}" class="pl-2">{{ $server->game->short_name }}</span>
                    @if($server->mod_name)<span>{{ $server->mod_name }} {{ $server->mod_version }}</span>@endif
                    @if($server->country_code)<x-country-flag :code="$server->country_code" :country="$server->country" /> {{ $server->country }}@endif
                </div>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold" :class="currentPlayers > 0 ? 'text-green-400' : 'text-gray-500'">
                    <span x-text="currentPlayers + '/' + maxPlayers">{{ $server->current_players }}/{{ $server->max_players }}</span>
                </div>
                <div class="text-gray-400 text-sm">{{ __('messages.players') }}</div>
                @if($server->is_online)
                    <a href="{{ $server->connect_url }}" class="mt-2 inline-block bg-amber-600 hover:bg-amber-500 text-white px-4 py-1.5 rounded text-sm font-medium transition">
                        Connect
                    </a>
                @endif
                @auth
                    @if(!$server->claimed_by_user_id)
                        <a href="{{ route('tracker.claim.server', $server) }}" class="mt-2 inline-block bg-gray-700 hover:bg-gray-600 text-gray-300 px-4 py-1.5 rounded text-sm font-medium transition">
                            🖥️ {{ __('Claim Server') }}
                        </a>
                    @elseif($server->claimed_by_user_id === auth()->id())
                        <span class="mt-2 inline-block bg-green-900/30 text-green-400 px-4 py-1.5 rounded text-sm font-medium">✓ {{ __('Your Server') }}</span>
                    @else
                        <span class="mt-2 inline-block bg-gray-700/50 text-gray-500 px-4 py-1.5 rounded text-sm font-medium">✓ {{ __('Claimed') }}</span>
                    @endif
                @endauth
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: Current info --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Current Map --}}
            <div class="bg-gray-800 rounded-lg p-4">
                <h2 class="text-lg font-semibold text-white mb-3">{{ __('messages.current_map') }}</h2>
                <x-levelshot :map="$server->current_map" x-ref="levelshot" />
                <div class="text-2xl font-medium mt-2">
                    <template x-if="currentMapSlug"><a :href="'/files/' + currentMapSlug" class="text-amber-400 hover:text-amber-300" x-text="currentMap"></a></template><template x-if="currentMapSlug === ''"><span class="text-gray-300" x-text="currentMap"></span></template>
                </div>
                <div class="text-gray-400 text-sm mt-1" x-text="gametype || 'Unknown gametype'">{{ $server->gametype ?? 'Unknown gametype' }}</div>
            </div>

            {{-- Current Players --}}
            <div class="bg-gray-800 rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-700">
                    <h2 class="text-lg font-semibold text-white">{{ __('messages.current_players') }} (<span x-text="players.length">{{ $activeSessions->count() }}</span>)</h2>
                </div>
                <template x-if="players.length > 0">
                <table class="w-full text-sm">
                    <thead class="text-gray-400 text-left bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-2">{{ __('messages.players') }}</th>
                            <th class="px-4 py-2 text-center">{{ __('messages.score') }}</th>
                            <th class="px-4 py-2 text-center">{{ __('messages.duration') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700/50">
                        <template x-for="p in players" :key="p.player_name">
                        <tr class="hover:bg-gray-750">
                            <td class="px-4 py-2">
                                <a x-show="p.player_url" :href="p.player_url" class="text-amber-400 hover:text-amber-300" x-html="p.player_name"></a>
                                <span x-show="!p.player_url" class="text-gray-400" x-html="p.player_name"></span>
                            </td>
                            <td class="px-4 py-2 text-center text-gray-300" x-text="p.score"></td>
                            <td class="px-4 py-2 text-center text-gray-400" x-text="p.duration"></td>
                        </tr>
                        </template>
                    </tbody>
                </table>
                </template>
                <template x-if="players.length === 0">
                <div class="px-4 py-8 text-center text-gray-500">{{ __('messages.no_players_now') }}</div>
                </template>
            </div>

            {{-- Player Count History (24h) --}}
            @if($history->count() > 1)
            <div class="bg-gray-800 rounded-lg p-4">
                <h2 class="text-lg font-semibold text-white mb-3">{{ __('messages.player_count_24h') }}</h2>
                <div x-data="playerChart()" x-init="init()" class="h-48">
                    <canvas id="playerChart"></canvas>
                </div>
            </div>
            @endif

        </div>

        {{-- Right: Sidebar --}}
        <div class="space-y-6">

            {{-- Server Info --}}
            <div class="bg-gray-800 rounded-lg p-4">
                <h2 class="text-lg font-semibold text-white mb-3">{{ __('messages.server_info') }}</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-400">{{ __('messages.status') }}</dt>
                        <dd class="{{ $server->is_online ? 'text-green-400' : 'text-red-400' }}">{{ $server->is_online ? __('messages.online') : __('messages.offline') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-400">{{ __('messages.game') }}</dt>
                        <dd class="text-gray-200">{{ $server->game->short_name }}</dd>
                    </div>
                    @if($server->mod_name)
                    <div class="flex justify-between">
                        <dt class="text-gray-400">{{ __('messages.mod') }}</dt>
                        <dd class="text-gray-200">{{ $server->mod_name }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-400">{{ __('messages.password_required') }}</dt>
                        <dd class="text-gray-200">{{ $server->needs_password ? 'Yes' : 'No' }}</dd>
                    </div>
                    @if($server->punkbuster !== null)
                    <div class="flex justify-between">
                        <dt class="text-gray-400">PunkBuster</dt>
                        <dd class="text-gray-200">{{ $server->punkbuster ? 'Yes' : 'No' }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-400">{{ __('messages.first_seen') }}</dt>
                        <dd class="text-gray-200">{{ $server->first_seen_at?->format('M j, Y') ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-400">{{ __('messages.last_seen') }}</dt>
                        <dd class="text-gray-200">{{ $server->last_seen_at?->diffForHumans() ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Top Maps --}}
            @if($topMaps->count() > 0)
            <div class="bg-gray-800 rounded-lg p-4">
                <h2 class="text-lg font-semibold text-white mb-3">{{ __('messages.top_maps') }}</h2>
                <div class="space-y-2">
                    @foreach($topMaps as $map)
                    <div class="flex justify-between text-sm">
                        <x-map-link :map="$map->map_name" />
                        <span class="text-gray-500">{{ round($map->total_time_minutes / 60) }}h</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Server Settings --}}
            @if($server->settings->count() > 0)
            <div class="bg-gray-800 rounded-lg p-4" x-data="{ open: false }">
                <button @click="open = !open" class="flex justify-between items-center w-full">
                    <h2 class="text-lg font-semibold text-white">{{ __('messages.server_cvars') }}</h2>
                    <span class="text-gray-400" x-text="open ? '−' : '+'"></span>
                </button>
                <div x-show="open" x-collapse class="mt-3 space-y-1 max-h-64 overflow-y-auto">
                    @foreach($server->settings->sortBy('key') as $setting)
                    <div class="flex justify-between text-xs font-mono">
                        <span class="text-gray-400">{{ $setting->key }}</span>
                        <span class="text-gray-300 ml-2 truncate max-w-[200px]">{{ $setting->value }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </div>

</div>

@if($history->count() > 1)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function playerChart() {
    return {
        init() {
            const ctx = document.getElementById('playerChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($history->pluck('polled_at')->map(fn($d) => $d->format('H:i'))) !!},
                    datasets: [{
                        label: 'Players',
                        data: {!! json_encode($history->pluck('players')) !!},
                        borderColor: '#F59E0B',
                        backgroundColor: 'rgba(245,158,11,0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#374151' }, ticks: { color: '#9CA3AF' } },
                        x: { grid: { display: false }, ticks: { color: '#9CA3AF', maxTicksLimit: 12 } }
                    }
                }
            });
        }
    }
}
</script>
@endif

<script>
function serverLive() {
    return {
        isOnline: {{ $server->is_online ? 'true' : 'false' }},
        currentPlayers: {{ $server->current_players }},
        maxPlayers: {{ $server->max_players }},
        currentMap: '{{ $server->current_map }}',
        currentMapSlug: '{{ \App\Services\Tracker\MapLinkService::findFile($server->current_map)?->slug ?? "" }}',
        gametype: '{{ $server->gametype ?? '' }}',
        players: [],
        polling: null,
        startPolling() {
            setTimeout(() => this.refresh(), 500);
            this.polling = setInterval(() => this.refresh(), 30000);
        },
        async refresh() {
            try {
                const res = await fetch('{{ route("tracker.server.live", $server) }}');
                if (!res.ok) return;
                const d = await res.json();
                this.isOnline = d.is_online;
                this.currentPlayers = d.current_players;
                this.maxPlayers = d.max_players;
                this.currentMap = d.current_map;
                this.currentMapSlug = d.map_file_slug || "";
                this.gametype = d.gametype || '';
                this.players = d.players;
            } catch(e) {}
        },
        destroy() { clearInterval(this.polling); }
    }
}
</script>
</x-layouts.app>
