<x-layouts.app title="Server Tracker">
<div class="max-w-7xl mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-amber-500">{{ __('messages.tracker') }}</h1>
        <p class="text-gray-400 mt-1">{{ __('messages.tracker_subtitle') }}</p>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-gray-800 rounded-lg p-4 text-center">
            <div class="text-3xl font-bold text-green-400">{{ $stats['servers_online'] }}</div>
            <div class="text-gray-400 text-sm">{{ __('messages.servers_online') }}</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 text-center">
            <div class="text-3xl font-bold text-amber-400">{{ $stats['players_online'] }}</div>
            <div class="text-gray-400 text-sm">{{ __('messages.players_online') }}</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 text-center">
            <div class="text-3xl font-bold text-blue-400">{{ $stats['servers_total'] }}</div>
            <div class="text-gray-400 text-sm">{{ __('messages.servers_tracked') }}</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 text-center">
            <div class="text-3xl font-bold text-purple-400">{{ $stats['players_total'] }}</div>
            <div class="text-gray-400 text-sm">{{ __('messages.players_tracked') }}</div>
        </div>
    </div>

    {{-- Game Tabs --}}
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('tracker.servers') }}"
           class="px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-500 text-white font-medium transition">
            {{ __('messages.all_servers') }}
        </a>
        @foreach($games as $game)
        <a href="{{ route('tracker.servers', ['game' => $game->slug]) }}"
           class="px-4 py-2 rounded-lg bg-gray-700 hover:bg-gray-600 text-gray-200 font-medium transition"
           style="border-left: 3px solid {{ $game->color }}">
            {{ $game->short_name }}
        </a>
        @endforeach
    </div>

    {{-- Top Servers --}}
    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-700">
            <h2 class="text-lg font-semibold text-white">{{ __('messages.top_servers_now') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-gray-400 text-left bg-gray-750">
                    <tr>
                        <th class="px-4 py-2">{{ __('messages.game') }}</th>
                        <th class="px-4 py-2">{{ __('messages.server_name') }}</th>
                        <th class="px-4 py-2">{{ __('messages.map') }}</th>
                        <th class="px-4 py-2 text-center">{{ __('messages.players') }}</th>
                        <th class="px-4 py-2">{{ __('messages.country') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($topServers as $server)
                    <tr class="hover:bg-gray-750 transition">
                        <td class="px-4 py-2">
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-medium text-white" style="background-color: {{ $server->game->color }}">
                                {{ $server->game->short_name }}
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            <a href="{{ route('tracker.server.show', $server) }}" class="text-amber-400 hover:text-amber-300">
                                {!! $server->hostname_html ?: e($server->hostname_clean ?: $server->ip) !!}
                            </a>
                        </td>
                        <td class="px-4 py-2 text-gray-300">{{ $server->current_map }}</td>
                        <td class="px-4 py-2 text-center">
                            <span class="font-medium {{ $server->current_players > 0 ? 'text-green-400' : 'text-gray-500' }}">
                                {{ $server->current_players }}/{{ $server->max_players }}
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            @if($server->country_code)
                                <x-country-flag :code="$server->country_code" :country="$server->country" /> {{ strtoupper($server->country_code) }}
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('messages.no_servers_with_players') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
        <a href="{{ route('tracker.servers') }}" class="bg-gray-800 rounded-lg p-6 hover:bg-gray-750 transition group">
            <h3 class="text-lg font-semibold text-white group-hover:text-amber-400">{{ __('messages.server_browser') }}</h3>
            <p class="text-gray-400 text-sm mt-1">{{ __('messages.browse_servers') }}</p>
        </a>
        <a href="{{ route('tracker.players') }}" class="bg-gray-800 rounded-lg p-6 hover:bg-gray-750 transition group">
            <h3 class="text-lg font-semibold text-white group-hover:text-amber-400">{{ __('messages.player_search') }}</h3>
            <p class="text-gray-400 text-sm mt-1">{{ __('messages.find_players') }}</p>
        </a>
        <a href="{{ route('tracker.servers', ['online' => 1, 'players' => 1]) }}" class="bg-gray-800 rounded-lg p-6 hover:bg-gray-750 transition group">
            <h3 class="text-lg font-semibold text-white group-hover:text-amber-400">{{ __('messages.active_servers') }}</h3>
            <p class="text-gray-400 text-sm mt-1">{{ __('messages.active_servers_desc') }}</p>
        </a>
    </div>

</div>
{{-- Auto-Refresh alle 30 Sekunden --}}
<script>
    setTimeout(() => window.location.reload(), 30000);
</script>
</x-layouts.app>
