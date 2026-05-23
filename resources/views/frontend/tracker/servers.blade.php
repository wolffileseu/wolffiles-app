<x-layouts.app :title="__('messages.server_browser')">
@php
    // Sort helper for header links (toggle asc/desc on re-click)
    $currentSort = request('sort', 'players');
    $currentDir  = request('dir', 'desc');
    $sortLink = function(string $col) use ($currentSort, $currentDir) {
        $dir = ($currentSort === $col && $currentDir === 'desc') ? 'asc' : 'desc';
        return route('tracker.servers', array_merge(request()->all(), ['sort' => $col, 'dir' => $dir, 'page' => null]));
    };
    $sortIcon = function(string $col) use ($currentSort, $currentDir) {
        if ($currentSort !== $col) return '';
        return $currentDir === 'asc' ? ' ▲' : ' ▼';
    };

    // Parse current multi-filters from URL (supports comma-strings and arrays)
    $parseMulti = function($val) {
        if (is_array($val)) return array_filter($val, fn($v) => $v !== '' && $v !== null);
        if ($val === null || $val === '') return [];
        return array_filter(array_map('trim', explode(',', (string) $val)), fn($v) => $v !== '');
    };
    $selCountries = $parseMulti(request('country'));
    $selMods      = $parseMulti(request('mod'));
    $selGametypes = $parseMulti(request('gametype'));
    $selEngineFamilies = $parseMulti(request('engine_family'));
@endphp

<div class="max-w-7xl mx-auto px-4 py-8">

    <div class="flex flex-wrap items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-amber-500">{{ __('messages.server_browser') }}</h1>
            <p class="text-gray-400 mt-1">{{ __('messages.servers_found', ['count' => $servers->total()]) }}</p>
        </div>
        <a href="{{ route('tracker.index') }}" class="text-amber-400 hover:text-amber-300">{!! __('messages.back_to_tracker') !!}</a>
    </div>

    {{-- Game Tabs (multi-select: click toggles) --}}
    @php
        $selGames = $parseMulti(request('game'));
    @endphp
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('tracker.servers', request()->except(['game', 'page'])) }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ empty($selGames) ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
            {{ __('messages.all_servers') }}
        </a>
        @foreach($games as $game)
            @php
                $isSelected = in_array($game->slug, $selGames);
                // Toggle: if selected, remove from list; if not selected, add
                $newGames = $isSelected
                    ? array_values(array_diff($selGames, [$game->slug]))
                    : array_merge($selGames, [$game->slug]);
                $newParams = array_merge(request()->except(['game', 'page']), [
                    'game' => empty($newGames) ? null : implode(',', $newGames),
                ]);
            @endphp
            <a href="{{ route('tracker.servers', $newParams) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ $isSelected ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}"
               style="border-left: 3px solid {{ $game->color }}"
               title="{{ $isSelected ? 'Klick zum Abwählen' : 'Klick zum Hinzufügen' }}">
                @if($isSelected)<span class="mr-1">✓</span>@endif{{ $game->short_name }}
            </a>
        @endforeach
    </div>

    {{-- Filters --}}
    <div x-data="{ filtersOpen: {{ (!empty($selCountries) || !empty($selMods) || !empty($selGametypes) || !empty($selEngineFamilies) || request('map')) ? 'true' : 'false' }} }" class="mb-6">
        <div class="flex flex-wrap gap-3 items-center">
            <form method="GET" action="{{ route('tracker.servers') }}" class="flex-1 min-w-[200px]">
                @foreach(request()->except(['search', 'page']) as $key => $value)
                    @if(is_array($value))
                        <input type="hidden" name="{{ $key }}" value="{{ implode(',', $value) }}">
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
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
            <a href="{{ route('tracker.servers', array_merge(request()->except('page'), ['ff' => request('ff') ? null : 1])) }}"
               class="px-3 py-2 rounded-lg text-sm transition {{ request('ff') ? 'bg-green-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                {{ __('messages.tracker_filter_ff') }}
            </a>
            <a href="{{ route('tracker.servers', array_merge(request()->except('page'), ['antilag' => request('antilag') ? null : 1])) }}"
               class="px-3 py-2 rounded-lg text-sm transition {{ request('antilag') ? 'bg-green-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                {{ __('messages.tracker_filter_antilag') }}
            </a>
            <a href="{{ route('tracker.servers', array_merge(request()->except('page'), ['balanced' => request('balanced') ? null : 1])) }}"
               class="px-3 py-2 rounded-lg text-sm transition {{ request('balanced') ? 'bg-green-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                {{ __('messages.tracker_filter_balanced') }}
            </a>
            <a href="{{ route('tracker.servers', array_merge(request()->except('page'), ['anticheat' => request('anticheat') ? null : 1])) }}"
               class="px-3 py-2 rounded-lg text-sm transition {{ request('anticheat') ? 'bg-green-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                {{ __('messages.tracker_filter_anticheat') }}
            </a>
            <a href="{{ route('tracker.servers', array_merge(request()->except('page'), ['hwrestrict' => request('hwrestrict') ? null : 1])) }}"
               class="px-3 py-2 rounded-lg text-sm transition {{ request('hwrestrict') ? 'bg-green-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                {{ __('messages.tracker_filter_hwrestrict') }}
            </a>
            <a href="{{ route('tracker.servers', array_merge(request()->except('page'), ['enhanced' => request('enhanced') ? null : 1])) }}"
               class="px-3 py-2 rounded-lg text-sm transition {{ request('enhanced') ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}"
               title="{{ __('messages.tracker_filter_enhanced_title') }}">
                🛰 {{ __('messages.tracker_filter_enhanced') }}
            </a>
            <a href="{{ route('tracker.servers', array_merge(request()->except('page'), ['live_enhanced' => request('live_enhanced') ? null : 1])) }}"
               class="px-3 py-2 rounded-lg text-sm transition {{ request('live_enhanced') ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}"
               title="{{ __('messages.tracker_filter_live_enhanced_title') }}">
                📡 {{ __('messages.tracker_filter_live_enhanced') }}
            </a>
            <button @click="filtersOpen = !filtersOpen" class="px-3 py-2 rounded-lg text-sm bg-gray-700 text-gray-300 hover:bg-gray-600 transition">
                {{ __('messages.more_filters') }}
                @if(count($selCountries) + count($selMods) + count($selGametypes) + count($selEngineFamilies) > 0)
                    <span class="ml-1 bg-amber-500 text-white rounded-full px-1.5 text-xs">{{ count($selCountries) + count($selMods) + count($selGametypes) + count($selEngineFamilies) }}</span>
                @endif
            </button>
            <button id="autoRefreshBtn" onclick="toggleAutoRefresh(this)"
                class="px-3 py-2 rounded-lg text-sm text-white transition bg-green-600">
                Auto-Refresh: ON
            </button>
        </div>

        <div x-show="filtersOpen" x-collapse class="mt-3 bg-gray-800 rounded-lg p-4">
            <form method="GET" action="{{ route('tracker.servers') }}" id="filterForm">
                {{-- Preserve unrelated filters --}}
                @if(request('game'))   <input type="hidden" name="game"   value="{{ request('game') }}">   @endif
                @if(request('online')) <input type="hidden" name="online" value="1">                       @endif
                @if(request('players'))<input type="hidden" name="players" value="1">                      @endif
                @if(request('enhanced')) <input type="hidden" name="enhanced" value="1">                    @endif
                @if(request('live_enhanced')) <input type="hidden" name="live_enhanced" value="1">         @endif
                @if(request('search')) <input type="hidden" name="search" value="{{ request('search') }}"> @endif
                @if(request('sort'))   <input type="hidden" name="sort"   value="{{ request('sort') }}">   @endif
                @if(request('dir'))    <input type="hidden" name="dir"    value="{{ request('dir') }}">    @endif

                {{-- Map search (stays as text) --}}
                <div class="mb-4">
                    <label class="text-gray-400 text-xs block mb-1">{{ __('messages.map') }}</label>
                    <input type="text" name="map" value="{{ request('map') }}" placeholder="e.g. oasis"
                           class="w-full bg-gray-700 border-gray-600 rounded text-sm text-white px-3 py-1.5">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    {{-- Countries (multi, searchable) --}}
                    <div x-data="{ q: '' }">
                        <label class="text-gray-400 text-xs block mb-2">{{ __('messages.country') }}
                            @if(count($selCountries)) <span class="text-amber-400">({{ count($selCountries) }})</span> @endif
                        </label>
                        <input type="text" x-model="q" placeholder="{{ __('messages.search_in_country') }}"
                               class="w-full bg-gray-700 border border-gray-600 rounded text-xs text-white px-2 py-1 mb-1.5 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                        <div class="max-h-48 overflow-y-auto bg-gray-900/50 rounded p-2 space-y-1">
                            @foreach($countries as $c)
                                <label class="flex items-center text-sm text-gray-200 hover:bg-gray-700/50 rounded px-1.5 py-0.5 cursor-pointer"
                                       data-search="{{ strtolower($c->country . ' ' . $c->country_code) }}"
                                       x-show="q === '' || $el.dataset.search.includes(q.toLowerCase())">
                                    <input type="checkbox" name="country_multi[]" value="{{ $c->country_code }}"
                                           {{ in_array($c->country_code, $selCountries) ? 'checked' : '' }}
                                           class="mr-2 rounded bg-gray-700 border-gray-600 text-amber-500 focus:ring-amber-500">
                                    <span>{{ $c->country }} ({{ $c->country_code }})</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Mods (multi, searchable) --}}
                    <div x-data="{ q: '' }">
                        <label class="text-gray-400 text-xs block mb-2">{{ __('messages.mod') }}
                            @if(count($selMods)) <span class="text-amber-400">({{ count($selMods) }})</span> @endif
                        </label>
                        <input type="text" x-model="q" placeholder="{{ __('messages.search_in_mod') }}"
                               class="w-full bg-gray-700 border border-gray-600 rounded text-xs text-white px-2 py-1 mb-1.5 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                        <div class="max-h-48 overflow-y-auto bg-gray-900/50 rounded p-2 space-y-1">
                            @foreach($mods as $mod)
                                <label class="flex items-center text-sm text-gray-200 hover:bg-gray-700/50 rounded px-1.5 py-0.5 cursor-pointer"
                                       data-search="{{ strtolower($mod) }}"
                                       x-show="q === '' || $el.dataset.search.includes(q.toLowerCase())">
                                    <input type="checkbox" name="mod_multi[]" value="{{ $mod }}"
                                           {{ in_array($mod, $selMods) ? 'checked' : '' }}
                                           class="mr-2 rounded bg-gray-700 border-gray-600 text-amber-500 focus:ring-amber-500">
                                    <span>{{ $mod }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Gametypes (multi, searchable) --}}
                    <div x-data="{ q: '' }">
                        <label class="text-gray-400 text-xs block mb-2">Gametype
                            @if(count($selGametypes)) <span class="text-amber-400">({{ count($selGametypes) }})</span> @endif
                        </label>
                        <input type="text" x-model="q" placeholder="{{ __('messages.search_in_gametype') }}"
                               class="w-full bg-gray-700 border border-gray-600 rounded text-xs text-white px-2 py-1 mb-1.5 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                        <div class="max-h-48 overflow-y-auto bg-gray-900/50 rounded p-2 space-y-1">
                            @foreach($gametypes as $gt)
                                <label class="flex items-center text-sm text-gray-200 hover:bg-gray-700/50 rounded px-1.5 py-0.5 cursor-pointer"
                                       data-search="{{ strtolower(\App\Services\Tracker\GametypeService::label($gt) . ' ' . $gt) }}"
                                       x-show="q === '' || $el.dataset.search.includes(q.toLowerCase())">
                                    <input type="checkbox" name="gametype_multi[]" value="{{ $gt }}"
                                           {{ in_array($gt, $selGametypes) ? 'checked' : '' }}
                                           class="mr-2 rounded bg-gray-700 border-gray-600 text-amber-500 focus:ring-amber-500">
                                    <span>{{ \App\Services\Tracker\GametypeService::label($gt) }} <span class="text-gray-500">({{ $gt }})</span></span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Engine Family (multi, searchable) --}}
                    <div x-data="{ q: '' }">
                        <label class="text-gray-400 text-xs block mb-2">{{ __('messages.engine_family') }}
                            @if(count($selEngineFamilies)) <span class="text-amber-400">({{ count($selEngineFamilies) }})</span> @endif
                        </label>
                        <input type="text" x-model="q" placeholder="{{ __('messages.search_in_engine') }}"
                               class="w-full bg-gray-700 border border-gray-600 rounded text-xs text-white px-2 py-1 mb-1.5 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                        <div class="max-h-48 overflow-y-auto bg-gray-900/50 rounded p-2 space-y-1">
                            @foreach(($engineFamilies ?? collect()) as $ef)
                                <label class="flex items-center text-sm text-gray-200 hover:bg-gray-700/50 rounded px-1.5 py-0.5 cursor-pointer"
                                       data-search="{{ strtolower($ef->label . ' ' . $ef->engine_family) }}"
                                       x-show="q === '' || $el.dataset.search.includes(q.toLowerCase())">
                                    <input type="checkbox" name="engine_family_multi[]" value="{{ $ef->engine_family }}"
                                           {{ in_array($ef->engine_family, $selEngineFamilies) ? 'checked' : '' }}
                                           class="mr-2 rounded bg-gray-700 border-gray-600 text-amber-500 focus:ring-amber-500">
                                    <span>{{ $ef->label }} <span class="text-gray-500">({{ $ef->cnt }})</span></span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white rounded px-4 py-1.5 text-sm font-medium transition">
                        {{ __('messages.apply_filters') }}
                    </button>
                    <a href="{{ route('tracker.servers', request()->only(['game','online','players','search'])) }}"
                       class="bg-gray-700 hover:bg-gray-600 text-gray-300 rounded px-4 py-1.5 text-sm font-medium transition">
                        Reset
                    </a>
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
                        <th class="pl-4 pr-1 py-3 w-8"></th>
                        <th class="pl-1 pr-4 py-3">
                            <a href="{{ $sortLink('game') }}" class="hover:text-white">{{ __('messages.game') }}{!! $sortIcon('game') !!}</a>
                        </th>
                        <th class="px-4 py-3">
                            <a href="{{ $sortLink('name') }}" class="hover:text-white">{{ __('messages.server_name') }}{!! $sortIcon('name') !!}</a>
                        </th>
                        <th class="px-4 py-3">
                            <a href="{{ $sortLink('map') }}" class="hover:text-white">{{ __('messages.map') }}{!! $sortIcon('map') !!}</a>
                        </th>
                        <th class="px-4 py-3">
                            <a href="{{ $sortLink('gametype') }}" class="hover:text-white">Gametype{!! $sortIcon('gametype') !!}</a>
                        </th>
                        <th class="px-4 py-3 text-center">
                            <a href="{{ $sortLink('players') }}" class="hover:text-white">{{ __('messages.players') }}{!! $sortIcon('players') !!}</a>
                        </th>
                        <th class="px-4 py-3 text-center">
                            <a href="{{ $sortLink('mod') }}" class="hover:text-white">{{ __('messages.mod') }}{!! $sortIcon('mod') !!}</a>
                        </th>
                        <th class="px-4 py-3 text-center">{{ __('messages.tracker_info_column') }}</th>
                        <th class="px-4 py-3 text-center">
                            <a href="{{ $sortLink('country') }}" class="hover:text-white">{{ __('messages.country') }}{!! $sortIcon('country') !!}</a>
                        </th>
                        <th class="px-4 py-3 text-right">
                            <a href="{{ $sortLink('ping') }}" class="hover:text-white">Ping{!! $sortIcon('ping') !!}</a>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    @forelse($servers as $server)
                    <tr class="hover:bg-gray-750 transition {{ !$server->is_online ? 'opacity-40' : '' }}">
                        <td class="pl-4 pr-1 py-2.5">
                            <span class="inline-block w-2.5 h-2.5 rounded-full {{ $server->is_online ? 'bg-green-500' : 'bg-red-500' }}"
                                  title="{{ $server->is_online ? __('messages.online') : __('messages.offline') }}"></span>
                        </td>
                        <td class="pl-1 pr-4 py-2.5">
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-medium text-white" style="background-color: {{ $server->game->color }}">
                                {{ $server->game->short_name }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5">
                            <a href="{{ route('tracker.server.show', $server) }}" class="text-amber-400 hover:text-amber-300">
                                {!! $server->hostname_html ?: e($server->hostname_clean ?: $server->full_address) !!}
                                @if($server->is_enhanced_tracker)
                                    <x-tracker-enhanced-badge class="ml-1 align-middle" />
                                @endif
                            </a>
                            @if($server->needs_password)
                                <span class="text-yellow-500 ml-1" title="{{ __('messages.password_required') }}">🔒</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5"><x-map-link :map="$server->current_map" /></td>
                        <td class="px-4 py-2.5 text-gray-400 text-xs">{{ \App\Services\Tracker\GametypeService::label($server->gametype, $server->game_id) }}</td>
                        <td class="px-4 py-2.5 text-center">
                            @if($server->is_online)
                                @php
                                    // Server list shows HUMAN players only (no bots), so users see real activity
                                    $humans = max(0, (int) $server->current_players - (int) ($server->bot_count ?? 0));
                                    $pct = $server->max_players > 0 ? ($humans / $server->max_players) * 100 : 0;
                                    $color = $pct > 80 ? 'text-red-400' : ($pct > 50 ? 'text-yellow-400' : ($humans > 0 ? 'text-green-400' : 'text-gray-500'));
                                @endphp
                                <span class="font-medium {{ $color }}" @if(($server->bot_count ?? 0) > 0) title="{{ __($humans === 1 ? 'messages.tracker_human_plus_bots' : 'messages.tracker_humans_plus_bots', ['humans' => $humans, 'bots' => $server->bot_count]) }}" @endif>
                                    {{ $humans }}@if($server->private_slots)<span class="text-gray-500 text-xs"> +{{ $server->private_slots }}</span>@endif/{{ $server->max_players }}
                                </span>
                            @else
                                <span class="text-gray-600">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-center"><x-mod-icon :mod="$server->mod_name" size="sm" /></td>
                        <td class="px-4 py-2.5">
                            <x-server-properties :server="$server" size="xs" />
                        </td>
                        <td class="px-4 py-2.5 text-gray-400 text-center">
                            @if($server->country_code)
                                <x-country-flag :code="$server->country_code" :country="$server->country" />
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            @if($server->is_online && $server->latency_ms !== null)
                                @php
                                    $p = (int) $server->latency_ms;
                                    $pc = $p < 50 ? 'text-green-400' : ($p < 100 ? 'text-yellow-400' : 'text-red-400');
                                @endphp
                                <span class="{{ $pc }} font-medium text-xs">{{ $p }}ms</span>
                            @else
                                <span class="text-gray-600 text-xs">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-12 text-center text-gray-500">{{ __('messages.no_servers_found') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $servers->links() }}</div>
</div>

{{-- Convert multi-checkboxes to comma-string on submit (keeps URLs clean) --}}
<script>
document.getElementById('filterForm')?.addEventListener('submit', function(e) {
    const form = e.target;
    ['country', 'mod', 'gametype', 'engine_family'].forEach(field => {
        const checked = form.querySelectorAll(`input[name="${field}_multi[]"]:checked`);
        const values = Array.from(checked).map(el => el.value);
        // Remove any existing hidden input for this field
        form.querySelectorAll(`input[type="hidden"][name="${field}"]`).forEach(el => el.remove());
        if (values.length > 0) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = field;
            hidden.value = values.join(',');
            form.appendChild(hidden);
        }
        // Disable the checkbox inputs so they don't go into URL as country_multi[]
        checked.forEach(el => el.disabled = true);
        form.querySelectorAll(`input[name="${field}_multi[]"]`).forEach(el => el.disabled = true);
    });
});
</script>

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