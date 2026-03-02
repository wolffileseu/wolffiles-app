<x-layouts.app :title="__('messages.server_browser')">
<div class="max-w-7xl mx-auto px-4 py-8">

    <div class="flex flex-wrap items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-amber-500">{{ __('messages.server_browser') }}</h1>
            <p class="text-gray-400 mt-1">{{ __('messages.servers_found', ['count' => $servers->total()]) }}</p>
        </div>
        <a href="{{ route('tracker.index') }}" class="text-amber-400 hover:text-amber-300">{!! __('messages.back_to_tracker') !!}</a>
    </div>

    {{-- Game Tabs --}}
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('tracker.servers', request()->except('game', 'page')) }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ !request('game') ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
            {{ __('messages.all_servers') }}
        </a>
        @foreach($games as $game)
        <a href="{{ route('tracker.servers', array_merge(request()->except('page'), ['game' => $game->slug])) }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ request('game') === $game->slug ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}"
           style="border-left: 3px solid {{ $game->color }}">
            {{ $game->short_name }}
        </a>
        @endforeach
    </div>

    {{-- Filters --}}
    <div x-data="{ filtersOpen: false }" class="mb-6">
        <div class="flex flex-wrap gap-3 items-center">
            <form method="GET" action="{{ route('tracker.servers') }}" class="flex-1 min-w-[200px]">
                @foreach(request()->except(['search', 'page']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="{{ __('messages.search_server') }}"
                       class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-sm text-white placeholder-gray-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
            </form>
            <a href="{{ route('tracker.servers', array_merge(request()->except('page'), ['online' => request('online') ? null : 1])) }}"
               class="px-3 py-2 rounded-lg text-sm transition {{ request('online') ? 'bg-green-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                {{ __('messages.online_only') }}
            </a>
            <a href="{{ route('tracker.servers', array_merge(request()->except('page'), ['players' => request('players') ? null : 1])) }}"
               class="px-3 py-2 rounded-lg text-sm transition {{ request('players') ? 'bg-green-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                {{ __('messages.has_players') }}
            </a>
            <button @click="filtersOpen = !filtersOpen" class="px-3 py-2 rounded-lg text-sm bg-gray-700 text-gray-300 hover:bg-gray-600 transition">
                {{ __('messages.more_filters') }}
            </button>
            <button id="autoRefreshBtn" onclick="toggleAutoRefresh(this)"
                class="px-3 py-2 rounded-lg text-sm text-white transition bg-green-600">
                Auto-Refresh: ON
            </button>
        </div>

        <div x-show="filtersOpen" x-collapse class="mt-3 bg-gray-800 rounded-lg p-4">
            <form method="GET" action="{{ route('tracker.servers') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                @if(request('game')) <input type="hidden" name="game" value="{{ request('game') }}"> @endif
                @if(request('online')) <input type="hidden" name="online" value="1"> @endif
                @if(request('players')) <input type="hidden" name="players" value="1"> @endif
                @if(request('search')) <input type="hidden" name="search" value="{{ request('search') }}"> @endif
                <div>
                    <label class="text-gray-400 text-xs">{{ __('messages.country') }}</label>
                    <select name="country" class="w-full bg-gray-700 border-gray-600 rounded text-sm text-white mt-1">
                        <option value="">{{ __('messages.all_countries') }}</option>
                        @foreach($countries as $c)
                            <option value="{{ $c->country_code }}" {{ request('country') === $c->country_code ? 'selected' : '' }}>{{ $c->country }} ({{ $c->country_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-gray-400 text-xs">{{ __('messages.map') }}</label>
                    <input type="text" name="map" value="{{ request('map') }}" placeholder="e.g. oasis"
                           class="w-full bg-gray-700 border-gray-600 rounded text-sm text-white mt-1 px-3 py-1.5">
                </div>
                <div>
                    <label class="text-gray-400 text-xs">{{ __('messages.mod') }}</label>
                    <select name="mod" class="w-full bg-gray-700 border-gray-600 rounded text-sm text-white mt-1">
                        <option value="">{{ __('messages.all_mods') }}</option>
                        @foreach($mods as $mod)
                            <option value="{{ $mod }}" {{ request('mod') === $mod ? 'selected' : '' }}>{{ $mod }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-amber-600 hover:bg-amber-500 text-white rounded px-4 py-1.5 text-sm font-medium transition">
                        {{ __('messages.apply_filters') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Server Table --}}
    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-gray-400 text-left bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 w-8"></th>
                        <th class="px-4 py-3">
                            <a href="{{ route('tracker.servers', array_merge(request()->all(), ['sort' => 'game'])) }}" class="hover:text-white">{{ __('messages.game') }}</a>
                        </th>
                        <th class="px-4 py-3">
                            <a href="{{ route('tracker.servers', array_merge(request()->all(), ['sort' => 'name'])) }}" class="hover:text-white">{{ __('messages.server_name') }}</a>
                        </th>
                        <th class="px-4 py-3">
                            <a href="{{ route('tracker.servers', array_merge(request()->all(), ['sort' => 'map'])) }}" class="hover:text-white">{{ __('messages.map') }}</a>
                        </th>
                        <th class="px-4 py-3 text-center">
                            <a href="{{ route('tracker.servers', array_merge(request()->all(), ['sort' => 'players'])) }}" class="hover:text-white">{{ __('messages.players') }}</a>
                        </th>
                        <th class="px-4 py-3">{{ __('messages.mod') }}</th>
                        <th class="px-4 py-3">
                            <a href="{{ route('tracker.servers', array_merge(request()->all(), ['sort' => 'country'])) }}" class="hover:text-white">{{ __('messages.country') }}</a>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    @forelse($servers as $server)
                    <tr class="hover:bg-gray-750 transition {{ !$server->is_online ? 'opacity-40' : '' }}">
                        <td class="px-4 py-2.5">
                            <span class="inline-block w-2.5 h-2.5 rounded-full {{ $server->is_online ? 'bg-green-500' : 'bg-red-500' }}"
                                  title="{{ $server->is_online ? __('messages.online') : __('messages.offline') }}"></span>
                        </td>
                        <td class="px-4 py-2.5">
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-medium text-white" style="background-color: {{ $server->game->color }}">
                                {{ $server->game->short_name }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5">
                            <a href="{{ route('tracker.server.show', $server) }}" class="text-amber-400 hover:text-amber-300">
                                {!! $server->hostname_html ?: e($server->hostname_clean ?: $server->full_address) !!}
                            </a>
                            @if($server->needs_password)
                                <span class="text-yellow-500 ml-1" title="{{ __('messages.password_required') }}">🔒</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5"><x-map-link :map="$server->current_map" /></td>
                        <td class="px-4 py-2.5 text-center">
                            @if($server->is_online)
                                @php
                                    $pct = $server->max_players > 0 ? ($server->current_players / $server->max_players) * 100 : 0;
                                    $color = $pct > 80 ? 'text-red-400' : ($pct > 50 ? 'text-yellow-400' : ($server->current_players > 0 ? 'text-green-400' : 'text-gray-500'));
                                @endphp
                                <span class="font-medium {{ $color }}">{{ $server->current_players }}/{{ $server->max_players }}</span>
                            @else
                                <span class="text-gray-600">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-gray-400 text-xs">{{ $server->mod_name ?: '-' }}</td>
                        <td class="px-4 py-2.5 text-gray-400">
                            @if($server->country_code)
                                <x-country-flag :code="$server->country_code" :country="$server->country" /> {{ strtoupper($server->country_code) }}
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-500">{{ __('messages.no_servers_found') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $servers->links() }}</div>
</div>
{{-- Auto-Refresh --}}
<script>
    let refreshTimer;
    let refreshEnabled = localStorage.getItem("tracker_autorefresh") !== "false";
    
    function toggleAutoRefresh(btn) {
        refreshEnabled = !refreshEnabled;
        localStorage.setItem("tracker_autorefresh", refreshEnabled);
        updateRefreshButton(btn);
        if (refreshEnabled) {
            startRefresh();
        } else {
            clearTimeout(refreshTimer);
        }
    }
    
    function updateRefreshButton(btn) {
        if (refreshEnabled) {
            btn.classList.remove("bg-gray-700");
            btn.classList.add("bg-green-600");
            btn.textContent = "Auto-Refresh: ON";
        } else {
            btn.classList.remove("bg-green-600");
            btn.classList.add("bg-gray-700");
            btn.textContent = "Auto-Refresh: OFF";
        }
    }
    
    function startRefresh() {
        if (!refreshEnabled) return;
        refreshTimer = setTimeout(() => {
            window.location.reload();
        }, 30000);
    }
    
    document.addEventListener("DOMContentLoaded", function() {
        const btn = document.getElementById("autoRefreshBtn");
        if (btn) updateRefreshButton(btn);
        startRefresh();
    });
</script>
</x-layouts.app>
