<x-layouts.app :title="$server->hostname_clean ?: $server->full_address">
@php
$canManageServer = auth()->check() && (
    $server->claimed_by_user_id === auth()->id()
    || ($server->claimed_by_clan_id && \App\Models\ClanManager::where('clan_id', $server->claimed_by_clan_id)
        ->where('user_id', auth()->id())
        ->whereIn('role', ['leader', 'owner'])
        ->exists())
);
@endphp
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
                    <h1 class="text-2xl font-bold text-white flex items-center gap-2 flex-wrap">
                        <span>{!! $server->hostname_html ?: e($server->hostname_clean ?: $server->full_address) !!}</span>
                        @if($server->is_enhanced_tracker)
                            <x-tracker-enhanced-badge size="md" />
                        @endif
                    </h1>
                </div>
                <div class="flex flex-wrap gap-4 mt-3 text-sm text-gray-400">
                    <span>{{ $server->ip }}:{{ $server->port }}</span>
                    <span style="border-left: 3px solid {{ $server->game->color }}" class="pl-2">{{ $server->game->short_name }}</span>
                    @if($server->mod_name)<span class="inline-flex items-center gap-1"><x-mod-icon :mod="$server->mod_name" size="xs" />@if($server->mod_version)<span class="text-gray-500 text-xs">{{ $server->mod_version }}</span>@endif</span>@endif
                    @if($server->country_code)<x-country-flag :code="$server->country_code" :country="$server->country" /> {{ $server->country }}@endif
                    @if($server->clan)
                        <span class="inline-flex items-center gap-1">Operator:
                            <a href="{{ route('clan.show', $server->clan->tracker_clan_id ?: $server->clan->slug) }}" class="text-amber-400 hover:text-amber-300 font-medium">{{ $server->clan->display_tag }} {{ $server->clan->name }}</a>
                        </span>
                    @elseif($server->claimedByUser)
                        <span class="inline-flex items-center gap-1">Operator:
                            <a href="{{ route('profile.show', $server->claimedByUser->id) }}" class="text-amber-400 hover:text-amber-300 font-medium">{{ $server->claimedByUser->name }}</a>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1">Operator: <span class="text-gray-500 italic">Unknown</span></span>
                    @endif
                    @if($server->latency_ms !== null)
                        @php
                            $ping = (int) $server->latency_ms;
                            $pingColor = $ping < 50 ? 'text-green-400' : ($ping < 100 ? 'text-yellow-400' : 'text-red-400');
                        @endphp
                        <span class="flex items-center gap-1" title="{{ __('messages.tracker_ping_from') }}">
                            <svg class="w-3 h-3 {{ $pingColor }}" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
                            <span class="{{ $pingColor }} font-medium">{{ $ping }}ms</span>
                        </span>
                    @endif
                </div>

                {{-- Server Properties (Bots, FF, Antilag, Balanced Teams, Heavy Weapons, Anticheat, OS) --}}
                <div class="mt-3">
                    <x-server-properties :server="$server" size="md" />
                </div>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold" :class="currentPlayers > 0 ? 'text-green-400' : 'text-gray-500'">
                    <span x-text="currentPlayers + ($privateSlots ? '+' + $privateSlots : '') + '/' + maxPlayers"
                          x-data="{ $privateSlots: {{ (int) ($server->private_slots ?? 0) }} }">
                        {{ $server->current_players }}@if($server->private_slots) +{{ $server->private_slots }}@endif/{{ $server->max_players }}
                    </span>
                </div>
                <div class="text-gray-400 text-sm">{{ __('messages.players') }}</div>
                @if(($server->bot_count ?? 0) > 0)
                    @php $humans = max(0, (int) $server->current_players - (int) $server->bot_count); @endphp
                    <div class="text-gray-500 text-xs mt-0.5">{{ __($humans === 1 ? 'messages.tracker_human_plus_bots' : 'messages.tracker_humans_plus_bots', ['humans' => $humans, 'bots' => $server->bot_count]) }}</div>
                @endif
                @if($server->is_online)
                    <a href="{{ $server->connect_url }}" class="mt-2 inline-block bg-amber-600 hover:bg-amber-500 text-white px-4 py-1.5 rounded text-sm font-medium transition">
                        {{ __('messages.tracker_connect') }}
                    </a>
                @endif
                {{-- Phase 2: Force-poll button (only for offline servers, authenticated users) --}}
                @auth
                    @if(!$server->is_online)
                        @php
                            $forcePollConfig = [
                                'url' => route('tracker.server.force_poll', $server),
                                'csrf' => csrf_token(),
                                'labels' => [
                                    'idle' => __('messages.tracker_force_poll'),
                                    'hint' => __('messages.tracker_force_poll_hint'),
                                    'queued' => __('messages.tracker_force_poll_queued'),
                                    'cooldown' => __('messages.tracker_force_poll_cooldown'),
                                    'error' => __('messages.tracker_force_poll_error'),
                                ],
                            ];
                        @endphp
                        <div x-data="forcePoll({{ json_encode($forcePollConfig) }})" class="mt-2">
                            <button @click="trigger()"
                                    :disabled="state !== 'idle'"
                                    :title="labels.hint"
                                    :class="state === 'idle' ? 'bg-blue-600 hover:bg-blue-500 text-white' : 'bg-gray-700 text-gray-400 cursor-not-allowed'"
                                    class="inline-flex items-center gap-2 px-4 py-1.5 rounded text-sm font-medium transition">
                                <svg x-show="state === 'queued'" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                <svg x-show="state === 'idle' || state === 'cooldown' || state === 'error'" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <span x-text="buttonLabel"></span>
                            </button>
                        </div>
                    @endif
                @endauth
                @auth
                    @if(!$server->claimed_by_user_id)
                        <a href="{{ route('tracker.claim.server', $server) }}" class="mt-2 inline-block bg-gray-700 hover:bg-gray-600 text-gray-300 px-4 py-1.5 rounded text-sm font-medium transition">
                            🖥️ {{ __('Claim Server') }}
                        </a>
                    @elseif($server->claimed_by_user_id === auth()->id())
                        <div class="mt-2 flex gap-2 flex-wrap">
                            <span class="inline-block bg-green-900/30 text-green-400 px-4 py-1.5 rounded text-sm font-medium">✓ {{ __('Your Server') }}</span>
                            <a href="{{ route('server.manage', $server) }}" class="inline-flex items-center gap-1 bg-amber-500 hover:bg-amber-400 text-gray-900 px-4 py-1.5 rounded text-sm font-semibold transition">⚙️ Manage</a>
                        </div>
                    @else
                        @if($canManageServer)
                        <div class="mt-2 flex gap-2 flex-wrap">
                            <span class="inline-block bg-gray-700/50 text-gray-500 px-4 py-1.5 rounded text-sm font-medium">✓ {{ __('Claimed') }}</span>
                            <a href="{{ route('server.manage', $server) }}" class="inline-flex items-center gap-1 bg-amber-500 hover:bg-amber-400 text-gray-900 px-4 py-1.5 rounded text-sm font-semibold transition">⚙️ Manage</a>
                        </div>
                        @else
                        <span class="mt-2 inline-block bg-gray-700/50 text-gray-500 px-4 py-1.5 rounded text-sm font-medium">✓ {{ __('Claimed') }}</span>
                        @endif
                    @endif
                @endauth
            </div>
        </div>
    </div>

    @if($server->server_banner_url || $server->server_logo_url || $server->description || $server->rules || $server->server_website || $server->server_discord || $server->server_email)
    <div class="mb-6">
        @if($server->server_banner_url)
        <div class="relative bg-gray-800 rounded-lg overflow-hidden mb-4">
            <img src="{{ $server->server_banner_url }}" alt="banner" class="w-full h-32 md:h-48 object-cover">
        </div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @if($server->server_logo_url)
            <div class="md:col-span-1">
                <img src="{{ $server->server_logo_url }}" alt="logo" class="w-full max-w-[200px] mx-auto bg-gray-900 rounded p-3">
            </div>
            @endif
            <div class="md:col-span-{{ $server->server_logo_url ? '2' : '3' }} space-y-4">
                @if($server->description)
                <div class="bg-gray-800 rounded-lg p-4">
                    <h2 class="text-white font-semibold text-sm uppercase tracking-wide mb-2">About</h2>
                    <div class="prose prose-invert prose-sm max-w-none text-gray-300">{!! \Illuminate\Support\Str::markdown($server->description) !!}</div>
                </div>
                @endif
                @if($server->rules)
                <div class="bg-gray-800 rounded-lg p-4">
                    <h2 class="text-white font-semibold text-sm uppercase tracking-wide mb-2">Server Rules</h2>
                    <div class="prose prose-invert prose-sm max-w-none text-gray-300">{!! \Illuminate\Support\Str::markdown($server->rules) !!}</div>
                </div>
                @endif
                @if($server->server_website || $server->server_discord || $server->server_email)
                <div class="flex flex-wrap gap-2">
                    @if($server->server_website)<a href="{{ $server->server_website }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 bg-gray-800 hover:bg-gray-700 text-gray-300 px-3 py-1.5 rounded text-sm">🌐 Website</a>@endif
                    @if($server->server_discord)<a href="{{ \Illuminate\Support\Str::startsWith($server->server_discord, ['http://', 'https://']) ? $server->server_discord : 'https://' . $server->server_discord }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 bg-gray-800 hover:bg-gray-700 text-gray-300 px-3 py-1.5 rounded text-sm">💬 Discord</a>@endif
                    @if($server->server_email)<a href="mailto:{{ $server->server_email }}" class="inline-flex items-center gap-1 bg-gray-800 hover:bg-gray-700 text-gray-300 px-3 py-1.5 rounded text-sm">✉️ Contact</a>@endif
                </div>
                @endif
                {{-- Per-server Excel export (all tracked data, multi-tab; cached 6h) --}}
                <div class="flex flex-wrap gap-2 mt-2">
                    <a href="{{ route('tracker.server.export', $server) }}"
                       class="inline-flex items-center gap-1 bg-emerald-700 hover:bg-emerald-600 text-white px-3 py-1.5 rounded text-sm font-medium transition"
                       title="Alle Tracker-Daten dieses Servers als Excel (.xlsx)">
                        📊 Export Excel
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

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
                <div class="text-gray-400 text-sm mt-1">{{ \App\Services\Tracker\GametypeService::label($server->gametype, $server->game_id) }}</div>

                {{-- Phase 1b: Map progress (elapsed / remaining / timelimit / percent) --}}
                <template x-if="isOnline && mapElapsedSeconds !== null">
                    <div class="mt-3 pt-3 border-t border-gray-700/50">
                        <div class="text-sm text-gray-300 mb-2" x-text="formatPlayingFor(mapElapsedSeconds)"></div>
                        <template x-if="timelimitMinutes">
                            <div>
                                <div class="w-full h-1.5 bg-gray-900 rounded-full overflow-hidden">
                                    <div class="h-full transition-all duration-1000 ease-linear"
                                         :class="mapProgressPercent >= 95 ? 'bg-red-500' : 'bg-amber-500'"
                                         :style="'width: ' + Math.min(100, mapProgressPercent) + '%'"></div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-400 mt-1.5 font-mono">
                                    <span x-text="mapProgressPercent >= 95 ? i18n.endingSoon : formatRemaining(mapElapsedSeconds, timelimitMinutes)"></span>
                                    <span x-text="formatLengthAndPercent(timelimitMinutes, mapProgressPercent)"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
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
                            <th class="px-4 py-2 w-6"></th>
                            <th class="px-4 py-2">{{ __('messages.players') }}</th>
                            <th class="px-4 py-2 text-center">Class</th>
                            <th class="px-4 py-2 text-center text-green-400">K</th>
                            <th class="px-4 py-2 text-center text-red-400">D</th>
                            <th class="px-4 py-2 text-center">{{ __('messages.score') }}</th>
                            <th class="px-4 py-2 text-center">Ping</th>
                            <th class="px-4 py-2 text-center">{{ __('messages.duration') }}</th>
                        </tr>
                    </thead>
                    <template x-for="group in [
                        {key: 'allies', label: 'Allies', color: 'text-blue-400', bg: 'bg-blue-900/30'},
                        {key: 'axis', label: 'Axis', color: 'text-red-400', bg: 'bg-red-900/30'},
                        {key: 'spectator', label: 'Spectators', color: 'text-gray-400', bg: 'bg-gray-700/30'},
                        {key: null, label: 'Unknown', color: 'text-gray-500', bg: 'bg-gray-800/30'}
                    ]" :key="group.key || 'unknown'">
                    <template x-if="players.filter(p => (p.team || null) === group.key).length > 0">
                    <tbody class="divide-y divide-gray-700/50">
                        <tr :class="group.bg">
                            <td :colspan="8" class="px-4 py-1.5">
                                <span class="font-semibold text-xs uppercase tracking-wider" :class="group.color"
                                      x-text="group.label + ' (' + players.filter(p => (p.team || null) === group.key).length + ')'"></span>
                            </td>
                        </tr>
                        <template x-for="p in players.filter(p => (p.team || null) === group.key)" :key="p.player_name">
                        <tr class="hover:bg-gray-750">
                            <td class="px-4 py-2">
                                <template x-if="p.country_code">
                                    <img :src="'https://flagcdn.com/20x15/' + p.country_code.toLowerCase() + '.png'"
                                         :alt="p.country"
                                         :title="p.country"
                                         class="inline-block align-middle"
                                         width="20" height="15"
                                         loading="lazy">
                                </template>
                            </td>
                            <td class="px-4 py-2">
                                <a x-show="p.player_url" :href="p.player_url" class="text-amber-400 hover:text-amber-300" x-html="p.player_name"></a>
                                <span x-show="!p.player_url" class="text-gray-400" x-html="p.player_name"></span>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <template x-if="p.class !== null && p.class !== undefined && [0,1,2,3,4].includes(p.class)">
                                    <img :src="'/images/classes/class-' + {0:'soldier',1:'medic',2:'engineer',3:'fieldops',4:'covertops'}[p.class] + '.png'"
                                         :alt="{0:'Soldier',1:'Medic',2:'Engineer',3:'Field Ops',4:'Covert Ops'}[p.class]"
                                         :title="{0:'Soldier',1:'Medic',2:'Engineer',3:'Field Ops',4:'Covert Ops'}[p.class]"
                                         class="inline-block w-4 h-4 opacity-80" loading="lazy">
                                </template>
                                <template x-if="p.class === null || p.class === undefined || ![0,1,2,3,4].includes(p.class)">
                                    <span class="text-gray-600">-</span>
                                </template>
                            </td>
                            <td class="px-4 py-2 text-center text-gray-300">
                                <span x-show="p.kills !== null && p.kills !== undefined" class="text-green-400 font-medium" x-text="p.kills"></span>
                                <span x-show="p.kills === null || p.kills === undefined" class="text-gray-600">-</span>
                            </td>
                            <td class="px-4 py-2 text-center text-gray-300">
                                <span x-show="p.deaths !== null && p.deaths !== undefined" class="text-red-400 font-medium" x-text="p.deaths"></span>
                                <span x-show="p.deaths === null || p.deaths === undefined" class="text-gray-600">-</span>
                            </td>
                            <td class="px-4 py-2 text-center text-gray-300" x-text="p.score"></td>
                            <td class="px-4 py-2 text-center text-xs">
                                <template x-if="p.is_bot">
                                    <span class="inline-flex items-center gap-1 text-orange-400 font-semibold" title="Bot">
                                        <img src="/images/server-properties/filter_bots.png" class="w-3.5 h-3.5" style="image-rendering: pixelated;" alt="Bot">
                                        BOT
                                    </span>
                                </template>
                                <template x-if="!p.is_bot && p.ping !== null">
                                    <span :class="p.ping < 50 ? 'text-green-400' : (p.ping < 100 ? 'text-yellow-400' : 'text-red-400')"
                                          x-text="p.ping + 'ms'"></span>
                                </template>
                                <template x-if="!p.is_bot && (p.ping === null || p.ping === undefined)">
                                    <span class="text-gray-600">-</span>
                                </template>
                            </td>
                            <td class="px-4 py-2 text-center text-gray-400" x-text="p.duration"></td>
                        </tr>
                        </template>
                    </tbody>
                    </template>
                    </template>
                </table>
                </template>
                <template x-if="players.length === 0">
                <div class="px-4 py-8 text-center text-gray-500">{{ __('messages.no_players_now') }}</div>
                </template>
            </div>

            {{-- RtCW kill scoreboard (renders only for RtCW servers) --}}
            @include('frontend.tracker.partials.rtcw-scoreboard', ['rtcwScoreboard' => $rtcwScoreboard ?? null])

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
                        <dd class="text-gray-200">
                            <span class="inline-flex items-center gap-1.5">
                                <x-mod-icon :mod="$server->mod_name" size="sm" />
                                @if($server->mod_version)<span class="text-gray-500 text-xs">{{ $server->mod_version }}</span>@endif
                            </span>
                        </dd>
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

    {{-- Banner embed box (full grid width) --}}
    <div class="lg:col-span-3">
        @include('frontend.tracker.partials.server-banner-embed')
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


{{-- =====================================================================
     Enhanced Tracker: Server Stats (Live Match, Last Match, Hall of Fame,
     Per-Map Best, Weapon Meta) — renders only when server has sv_tracker.
     ===================================================================== --}}
@if($server->is_enhanced_tracker)
<div class="max-w-7xl mx-auto px-4 pb-6">

{{-- === RECENT ACTIVITY (Commit 3) === --}}
<div class="max-w-7xl mx-auto px-4 mb-8" x-data="{}">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h2 class="text-xl font-bold text-white">{{ __('tracker.recent_activity') ?? 'Recent Activity' }}</h2>
        <div class="flex gap-2 text-xs">
            @foreach(['24h' => '24h', '7d' => '7 days', '30d' => '30 days'] as $key => $label)
                <a href="{{ request()->fullUrlWithQuery(['recent' => $key]) }}"
                   class="px-3 py-1.5 rounded {{ ($recentRange ?? '7d') === $key ? 'bg-amber-500 text-black font-medium' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Recent Players --}}
        <div class="bg-gray-800 rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-white">{{ __('tracker.recent_players') ?? 'Recent Players' }}</h3>
                <span class="text-xs text-gray-400">{{ $recentPlayers->count() }}</span>
            </div>
            @if($recentPlayers->count() === 0)
                <div class="p-6 text-center text-gray-500 text-sm">
                    {{ __('tracker.no_recent_players') ?? 'No players in this time range.' }}
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-900 text-gray-400 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-2 text-left">{{ __('tracker.player') ?? 'Player' }}</th>
                                <th class="px-4 py-2 text-right">{{ __('tracker.sessions_short') ?? 'Sess' }}</th>
                                <th class="px-4 py-2 text-right">{{ __('tracker.playtime_short') ?? 'Time' }}</th>
                                <th class="px-4 py-2 text-right">K/D</th>
                                <th class="px-4 py-2 text-right">{{ __('tracker.last_seen') ?? 'Last' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @foreach($recentPlayers as $rp)
                                <tr class="hover:bg-gray-700/40">
                                    <td class="px-4 py-2">
                                        @if($rp->player_id)
                                            <a href="{{ route('tracker.player.show', $rp->player_id) }}" class="text-white hover:text-amber-300">
                                                {!! $rp->name_html ?: e($rp->name_clean ?: 'unknown') !!}
                                            </a>
                                        @else
                                            <span class="text-gray-400">{{ $rp->name_clean ?: 'unknown' }}</span>
                                        @endif
                                        @if($rp->country_code)<x-country-flag :code="$rp->country_code" />@endif
                                        @if($rp->has_enhanced_data)
                                            <span class="text-xs text-amber-400 ml-1" title="Enhanced Tracker">&#9733;</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right text-gray-300">{{ $rp->sess_count }}</td>
                                    <td class="px-4 py-2 text-right text-gray-300">{{ round($rp->total_min / 60, 1) }}h</td>
                                    <td class="px-4 py-2 text-right text-xs">
                                        <span class="text-green-400">{{ number_format($rp->kills) }}</span>
                                        /
                                        <span class="text-red-400">{{ number_format($rp->deaths) }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-right text-gray-400 text-xs whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($rp->last_seen)->diffForHumans() }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Recent Maps --}}
        <div class="bg-gray-800 rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-white">{{ __('tracker.recent_maps') ?? 'Recently Played Maps' }}</h3>
                <span class="text-xs text-gray-400">{{ $recentMaps->count() }}</span>
            </div>
            @if($recentMaps->count() === 0)
                <div class="p-6 text-center text-gray-500 text-sm">
                    {{ __('tracker.no_recent_maps') ?? 'No maps played in this time range.' }}
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-900 text-gray-400 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-2 text-left">{{ __('tracker.map') ?? 'Map' }}</th>
                                <th class="px-4 py-2 text-right">{{ __('tracker.sessions_short') ?? 'Sess' }}</th>
                                <th class="px-4 py-2 text-right">{{ __('tracker.unique_players') ?? 'Players' }}</th>
                                <th class="px-4 py-2 text-right">{{ __('tracker.time_short') ?? 'Time' }}</th>
                                <th class="px-4 py-2 text-right">{{ __('tracker.last_played') ?? 'Last' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @foreach($recentMaps as $rm)
                                <tr class="hover:bg-gray-700/40">
                                    <td class="px-4 py-2 text-amber-400 font-mono text-xs">{{ $rm->map_name }}</td>
                                    <td class="px-4 py-2 text-right text-gray-300">{{ $rm->session_count }}</td>
                                    <td class="px-4 py-2 text-right text-gray-300">{{ $rm->unique_players }}</td>
                                    <td class="px-4 py-2 text-right text-gray-300">{{ round($rm->total_time / 60, 1) }}h</td>
                                    <td class="px-4 py-2 text-right text-gray-400 text-xs whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($rm->last_played_at)->diffForHumans() }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
{{-- === /RECENT ACTIVITY === --}}

    {{-- E) LIVE MATCH (running right now) -------------------------------- --}}
    @if($liveMatch && $liveMatchPlayers->count() > 0)
    <div class="bg-gradient-to-r from-emerald-900/30 to-gray-800 border border-emerald-500/40 rounded-lg p-5 mb-6">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </span>
                {{ __('Live Match') }}
                <span class="text-xs text-gray-400 font-normal">
                    {{ $liveMatch->map_name }} · {{ \Carbon\Carbon::parse($liveMatch->started_at)->diffForHumans(null, true) }}
                </span>
            </h2>
            <span class="text-xs text-emerald-400">🔴 {{ __('Running now') }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-400 uppercase border-b border-gray-700">
                    <tr>
                        <th class="text-left py-2 pr-2">{{ __('Player') }}</th>
                        <th class="text-right px-2">K</th>
                        <th class="text-right px-2">D</th>
                        <th class="text-right px-2 hidden sm:table-cell">HS</th>
                        <th class="text-right px-2 hidden sm:table-cell">{{ __('Acc') }}</th>
                        <th class="text-right px-2">{{ __('DMG') }}</th>
                        <th class="text-right pl-2 hidden md:table-cell">{{ __('Score') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($liveMatchPlayers as $lp)
                    <tr class="border-b border-gray-800/50 hover:bg-gray-800/40">
                        <td class="py-2 pr-2">
                            @if($lp->p_id)
                                <a href="{{ route('tracker.player.show', $lp->p_id) }}" class="hover:text-amber-400">
                                    {!! $lp->name_html ?: e($lp->name_clean ?: $lp->name_snapshot ?: '?') !!}
                                </a>
                            @else
                                {!! $lp->name_html ?: e($lp->name_clean ?: $lp->name_snapshot ?: '?') !!}
                            @endif
                            @if($lp->is_bot ?? false)
                                <span class="text-[9px] px-1 py-0 ml-1 rounded bg-gray-700 text-gray-400 uppercase">Bot</span>
                            @endif
                        </td>
                        <td class="text-right px-2 text-emerald-400 font-semibold">{{ $lp->kills }}</td>
                        <td class="text-right px-2 text-rose-400">{{ $lp->deaths }}</td>
                        <td class="text-right px-2 hidden sm:table-cell text-amber-400">{{ $lp->headshots }}</td>
                        <td class="text-right px-2 hidden sm:table-cell text-gray-300">{{ number_format($lp->accuracy_pct ?? 0, 1) }}%</td>
                        <td class="text-right px-2 text-gray-200">{{ number_format($lp->damage_given) }}</td>
                        <td class="text-right pl-2 hidden md:table-cell text-gray-300">{{ $lp->score }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- B) LAST COMPLETED MATCH (scoreboard) ----------------------------- --}}
    @if($lastMatch && $lastMatchPlayers->count() > 0)
    <div class="bg-gray-800 rounded-lg p-5 mb-6">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                🏁 {{ __('Last Match') }}
                <span class="text-xs text-gray-500 font-normal">
                    {{ $lastMatch->map_name }} ·
                    @php
                        $dur = $lastMatch->duration_seconds;
                        $mins = intdiv($dur, 60); $secs = $dur % 60;
                    @endphp
                    {{ $mins }}m {{ $secs }}s ·
                    {{ \Carbon\Carbon::parse($lastMatch->ended_at)->diffForHumans() }}
                </span>
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-400 uppercase border-b border-gray-700">
                    <tr>
                        <th class="text-left py-2 pr-2">#</th>
                        <th class="text-left py-2 pr-2">{{ __('Player') }}</th>
                        <th class="text-right px-2">K</th>
                        <th class="text-right px-2">D</th>
                        <th class="text-right px-2 hidden sm:table-cell">HS</th>
                        <th class="text-right px-2 hidden sm:table-cell">{{ __('Acc') }}</th>
                        <th class="text-right px-2">{{ __('DMG') }}</th>
                        <th class="text-right pl-2 hidden md:table-cell">{{ __('Score') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($lastMatchPlayers as $i => $lp)
                    <tr class="border-b border-gray-800/50 hover:bg-gray-800/40">
                        <td class="py-2 pr-2 text-gray-500 text-xs">{{ $i + 1 }}</td>
                        <td class="py-2 pr-2">
                            @if($lp->p_id)
                                <a href="{{ route('tracker.player.show', $lp->p_id) }}" class="hover:text-amber-400">
                                    {!! $lp->name_html ?: e($lp->name_clean ?: $lp->name_snapshot ?: '?') !!}
                                </a>
                            @else
                                {!! $lp->name_html ?: e($lp->name_clean ?: $lp->name_snapshot ?: '?') !!}
                            @endif
                            @if($lp->is_bot ?? false)
                                <span class="text-[9px] px-1 py-0 ml-1 rounded bg-gray-700 text-gray-400 uppercase">Bot</span>
                            @endif
                        </td>
                        <td class="text-right px-2 text-emerald-400 font-semibold">{{ $lp->kills }}</td>
                        <td class="text-right px-2 text-rose-400">{{ $lp->deaths }}</td>
                        <td class="text-right px-2 hidden sm:table-cell text-amber-400">{{ $lp->headshots }}</td>
                        <td class="text-right px-2 hidden sm:table-cell text-gray-300">{{ number_format($lp->accuracy_pct ?? 0, 1) }}%</td>
                        <td class="text-right px-2 text-gray-200">{{ number_format($lp->damage_given) }}</td>
                        <td class="text-right pl-2 hidden md:table-cell text-gray-300">{{ $lp->score }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- A) HALL OF FAME (5 leaderboards, tabbed) ------------------------ --}}
    @if(!empty($hallOfFame) && collect($hallOfFame)->contains(fn($list) => $list->isNotEmpty()))
    <div x-data="{ tab: 'killers' }" class="bg-gray-800 rounded-lg p-5 mb-6">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                🏆 {{ __('Hall of Fame') }}
                <span class="text-xs text-gray-500 font-normal">{{ __('lifetime, this server') }}</span>
            </h2>
        </div>
        <div class="flex flex-wrap gap-2 mb-4 border-b border-gray-700 pb-3">
            <button @click="tab = 'killers'" :class="tab === 'killers' ? 'bg-amber-500 text-gray-900' : 'bg-gray-900 text-gray-300 hover:bg-gray-700'" class="px-3 py-1.5 rounded text-xs font-semibold transition">⚔️ {{ __('Top Killers') }}</button>
            <button @click="tab = 'accuracy'" :class="tab === 'accuracy' ? 'bg-amber-500 text-gray-900' : 'bg-gray-900 text-gray-300 hover:bg-gray-700'" class="px-3 py-1.5 rounded text-xs font-semibold transition">🎯 {{ __('Best Aim') }}</button>
            <button @click="tab = 'objectives'" :class="tab === 'objectives' ? 'bg-amber-500 text-gray-900' : 'bg-gray-900 text-gray-300 hover:bg-gray-700'" class="px-3 py-1.5 rounded text-xs font-semibold transition">🚩 {{ __('Objectives') }}</button>
            <button @click="tab = 'revivers'" :class="tab === 'revivers' ? 'bg-amber-500 text-gray-900' : 'bg-gray-900 text-gray-300 hover:bg-gray-700'" class="px-3 py-1.5 rounded text-xs font-semibold transition">🩹 {{ __('Top Medics') }}</button>
            <button @click="tab = 'teamkillers'" :class="tab === 'teamkillers' ? 'bg-rose-500 text-white' : 'bg-gray-900 text-gray-300 hover:bg-gray-700'" class="px-3 py-1.5 rounded text-xs font-semibold transition">⚠️ {{ __('Wall of Shame') }}</button>
        </div>

        @foreach(['killers','accuracy','objectives','revivers','teamkillers'] as $hofKey)
            @php
                $list = $hallOfFame[$hofKey] ?? collect();
                $labels = [
                    'killers'     => ['v_label'=>__('Kills'),       'fmt' => fn($v) => number_format($v), 'color'=>'text-emerald-400'],
                    'accuracy'    => ['v_label'=>__('Accuracy'),    'fmt' => fn($v) => number_format($v,2).'%', 'color'=>'text-amber-400'],
                    'objectives'  => ['v_label'=>__('Objectives'),  'fmt' => fn($v) => number_format($v), 'color'=>'text-amber-400'],
                    'revivers'    => ['v_label'=>__('Revives'),     'fmt' => fn($v) => number_format($v), 'color'=>'text-emerald-400'],
                    'teamkillers' => ['v_label'=>__('Team Kills'),  'fmt' => fn($v) => number_format($v), 'color'=>'text-rose-400'],
                ];
                $meta = $labels[$hofKey];
            @endphp
            <div x-show="tab === '{{ $hofKey }}'" x-cloak>
                @if($list->isEmpty())
                    <p class="text-sm text-gray-500 italic py-6 text-center">{{ __('Not enough data yet') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-xs text-gray-400 uppercase border-b border-gray-700">
                                <tr>
                                    <th class="text-left py-2 pr-2 w-8">#</th>
                                    <th class="text-left py-2 pr-2">{{ __('Player') }}</th>
                                    <th class="text-right px-2">{{ $meta['v_label'] }}</th>
                                    <th class="text-right pl-2 hidden sm:table-cell">{{ __('Matches') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($list as $i => $row)
                                <tr class="border-b border-gray-800/50 hover:bg-gray-800/40">
                                    <td class="py-2 pr-2 text-gray-500 text-xs">
                                        @if($i === 0) 🥇
                                        @elseif($i === 1) 🥈
                                        @elseif($i === 2) 🥉
                                        @else {{ $i + 1 }}
                                        @endif
                                    </td>
                                    <td class="py-2 pr-2">
                                        <a href="{{ route('tracker.player.show', $row->id) }}" class="hover:text-amber-400">
                                            {!! $row->name_html ?: e($row->name_clean ?: '?') !!}
                                        </a>
                                    </td>
                                    <td class="text-right px-2 {{ $meta['color'] }} font-semibold">{{ $meta['fmt']($row->v) }}</td>
                                    <td class="text-right pl-2 hidden sm:table-cell text-gray-500">{{ $row->matches_played }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- C) PER-MAP BEST PERFORMERS ----------------------------------- --}}
        @if($serverMapBest->isNotEmpty())
        <div class="bg-gray-800 rounded-lg p-5">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2 mb-4">
                🗺️ {{ __('Map Champions') }}
                <span class="text-xs text-gray-500 font-normal">{{ __('best killer per map') }}</span>
            </h2>
            <div class="space-y-2">
                @foreach($serverMapBest as $mb)
                <div class="flex items-center gap-3 bg-gray-900/40 rounded p-2.5 hover:bg-gray-900/70 transition">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm text-gray-200 font-medium truncate">{{ $mb->map_name }}</div>
                        <div class="text-xs text-gray-500">
                            <a href="{{ route('tracker.player.show', $mb->player_id) }}" class="hover:text-amber-400">
                                {!! $mb->name_html ?: e($mb->name_clean ?: '?') !!}
                            </a>
                            · {{ $mb->times_played }} {{ __('matches') }}
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-emerald-400 font-bold text-sm">{{ number_format($mb->total_kills) }}K</div>
                        <div class="text-xs text-gray-500">{{ number_format($mb->total_deaths) }}D</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- D) SERVER WEAPON META --------------------------------------- --}}
        @if($serverWeaponMeta->isNotEmpty())
        <div class="bg-gray-800 rounded-lg p-5">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2 mb-4">
                🔫 {{ __('Weapon Meta') }}
                <span class="text-xs text-gray-500 font-normal">{{ __('top 6 on this server') }}</span>
            </h2>
            <div class="space-y-2">
                @foreach($serverWeaponMeta as $wm)
                    @php $cfg = config('tracker-weapons.'.$wm->weapon_bit); @endphp
                    @if($cfg)
                    <div class="flex items-center gap-3 bg-gray-900/40 rounded p-2.5 hover:bg-gray-900/70 transition">
                        <img src="/img/tracker/weapons/{{ $cfg['icon'] }}"
                             alt="{{ $cfg['name'] }}"
                             class="w-10 h-10 object-contain flex-shrink-0"
                             loading="lazy">
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-gray-200 font-medium truncate">{{ $cfg['name'] }}</div>
                            <div class="text-xs text-gray-500 flex flex-wrap gap-x-2">
                                <span>{{ number_format($wm->total_kills) }}K / {{ number_format($wm->total_deaths) }}D</span>
                                @if($wm->total_headshots > 0)
                                    <span class="text-amber-400">🎯 {{ number_format($wm->total_headshots) }}</span>
                                @endif
                                @if($wm->total_atts > 0)
                                    <span class="text-emerald-400">{{ number_format(min(10000, ($wm->total_hits / $wm->total_atts) * 10000) / 100, 1) }}%</span>
                                @endif
                                <span class="text-gray-600">· {{ $wm->users }} {{ __('users') }}</span>
                            </div>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endif
    </div>

</div>
@endif

{{-- Enhanced Matches Section (only when server has sv_tracker active) --}}
@if($server->is_enhanced_tracker && $recentMatches->count() > 0)
<div class="max-w-7xl mx-auto px-4">
<div class="mt-8 bg-gray-800 rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between flex-wrap gap-2">
        <h2 class="text-lg font-semibold text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span>{{ __('Recent Matches') }}</span>
            <x-tracker-enhanced-badge size="sm" />
        </h2>
        <span class="text-xs text-gray-400">
            {{ number_format($server->enhanced_event_count ?? 0) }} {{ __('events received') }}
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-900/50 text-gray-400 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">#</th>
                    <th class="px-4 py-3 text-left">{{ __('Map') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Started') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Duration') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('Players') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('End') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Total') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700/50">
                @foreach($recentMatches as $match)
                <tr class="hover:bg-gray-700/20 transition">
                    <td class="px-4 py-3 text-gray-500 font-mono text-xs">#{{ $match->id }}</td>
                    <td class="px-4 py-3">
                        <span class="font-medium text-gray-200">{{ $match->map_name }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-400 font-mono text-xs" title="{{ $match->started_at }}">
                        {{ \Carbon\Carbon::parse($match->started_at)->diffForHumans() }}
                    </td>
                    <td class="px-4 py-3 text-right text-gray-300 font-mono text-xs">
                        @if(is_null($match->ended_at))
                            <span class="inline-flex items-center gap-1 text-emerald-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                {{ __('In progress') }}
                            </span>
                        @else
                            @php
                                $d = (int) $match->duration_seconds;
                                $m = intdiv($d, 60);
                                $s = $d % 60;
                            @endphp
                            {{ $m }}m {{ str_pad($s, 2, '0', STR_PAD_LEFT) }}s
                        @endif
                    </td>
                    {{-- Players at map start -> finish, team split (allies v axis, +spec). Populated for matches after the handler update. --}}
                    <td class="px-4 py-3 text-center font-mono text-xs whitespace-nowrap">
                        @php
                            $isLive = is_null($match->ended_at);
                            // Build a side: "AvX" with optional "+S spec", or null if no data
                            $fmtSide = function ($allies, $axis, $spec, $playingFallback) {
                                if (!is_null($allies) || !is_null($axis)) {
                                    $a = (int) ($allies ?? 0);
                                    $x = (int) ($axis ?? 0);
                                    $s = (int) ($spec ?? 0);
                                    return ['main' => $a.'v'.$x, 'spec' => $s > 0 ? $s : null];
                                }
                                if (!is_null($playingFallback)) {
                                    return ['main' => (string) (int) $playingFallback, 'spec' => null];
                                }
                                return null;
                            };
                            $start = $fmtSide($match->allies_at_start ?? null, $match->axis_at_start ?? null, $match->spec_at_start ?? null, $match->players_at_start ?? null);
                            $end   = $fmtSide($match->allies_at_end ?? null, $match->axis_at_end ?? null, $match->spec_at_end ?? null, $match->players_at_end ?? null);
                        @endphp
                        @if(is_null($start))
                            <span class="text-gray-600">—</span>
                        @else
                            <span class="text-gray-200">{{ $start['main'] }}</span>@if($start['spec'])<span class="text-gray-500 text-[10px] ml-0.5">+{{ $start['spec'] }}</span>@endif
                        @endif
                        <span class="text-gray-600 mx-1">&rarr;</span>
                        @if($isLive)
                            <span class="inline-flex items-center gap-1 text-emerald-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>live
                            </span>
                        @elseif(is_null($end))
                            <span class="text-gray-600">—</span>
                        @else
                            <span class="text-gray-200">{{ $end['main'] }}</span>@if($end['spec'])<span class="text-gray-500 text-[10px] ml-0.5">+{{ $end['spec'] }}</span>@endif
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $reasonColors = [
                                'mapend' => 'bg-blue-500/20 text-blue-300',
                                'mapchange' => 'bg-purple-500/20 text-purple-300',
                                'maprestart' => 'bg-yellow-500/20 text-yellow-300',
                                'timeout' => 'bg-gray-500/20 text-gray-300',
                            ];
                            $reasonColor = $reasonColors[$match->end_reason ?? ''] ?? 'bg-gray-500/20 text-gray-300';
                        @endphp
                        @if($match->end_reason)
                            <span class="px-2 py-0.5 rounded text-[10px] uppercase tracking-wider font-semibold {{ $reasonColor }}">
                                {{ $match->end_reason }}
                            </span>
                        @else
                            <span class="text-gray-500">—</span>
                        @endif
                    </td>
                    {{-- Total distinct participants (from match_stats; available for all matches) --}}
                    <td class="px-4 py-3 text-right text-gray-300 font-mono text-xs">
                        {{ $matchParticipants[$match->id] ?? 0 }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
</div>
@endif

<script>
function serverLive() {
    return {
        isOnline: {{ $server->is_online ? 'true' : 'false' }},
        currentPlayers: {{ $server->current_players }},
        maxPlayers: {{ $server->max_players }},
        currentMap: '{{ $server->current_map }}',
        currentMapSlug: '{{ $server->current_map ? \App\Services\Tracker\MapLinkService::findFile($server->current_map)?->slug : "" }}',
        gametype: '{{ $server->gametype ?? '' }}',
        players: [],
        polling: null,
        // Phase 1b: map progress state, i18n strings, computed getter, formatters
        mapElapsedSeconds: @json($server->current_map_started_at ? max(0, now()->timestamp - $server->current_map_started_at->timestamp) : null),
        timelimitMinutes: @json(((int) ($server->settings->firstWhere('key', 'timelimit')?->value ?? 0)) ?: null),
        tickInterval: null,
        i18n: {
            playingFor: @json(__('messages.tracker_map_playing_for')),
            remaining: @json(__('messages.tracker_map_remaining')),
            length: @json(__('messages.tracker_map_length')),
            endingSoon: @json(__('messages.tracker_map_ending_soon')),
        },
        get mapProgressPercent() {
            if (!this.timelimitMinutes || this.mapElapsedSeconds === null) return 0;
            return (this.mapElapsedSeconds / (this.timelimitMinutes * 60)) * 100;
        },
        formatPlayingFor(seconds) {
            return this.i18n.playingFor.replace(':min', Math.floor(seconds / 60));
        },
        formatRemaining(elapsedSec, limitMin) {
            const remainingMin = Math.max(0, limitMin - Math.floor(elapsedSec / 60));
            return this.i18n.remaining.replace(':min', remainingMin);
        },
        formatLengthAndPercent(limitMin, percent) {
            return this.i18n.length.replace(':min', limitMin) + ' · ' + Math.min(100, Math.round(percent)) + '%';
        },
        startPolling() {
            setTimeout(() => this.refresh(), 500);
            this.polling = setInterval(() => this.refresh(), 30000);
            // Phase 1b: local 1s tick advances elapsed between server fetches.
            // refresh() resyncs from server, so client clock drift is irrelevant.
            this.tickInterval = setInterval(() => {
                if (this.isOnline && this.mapElapsedSeconds !== null) {
                    this.mapElapsedSeconds++;
                }
            }, 1000);
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
                // Phase 1b: resync map progress from server (drift correction)
                this.mapElapsedSeconds = d.map_elapsed_seconds;
                this.timelimitMinutes = d.timelimit_minutes;
            } catch(e) {}
        },
        destroy() {
            clearInterval(this.polling);
            clearInterval(this.tickInterval);
        }
    }
}

// Phase 2: Force-poll Alpine component
function forcePoll(config) {
    return {
        url: config.url,
        csrf: config.csrf,
        labels: config.labels,
        state: 'idle',         // idle | queued | cooldown | error
        countdown: 0,
        countdownInterval: null,
        get buttonLabel() {
            if (this.state === 'queued')   return this.labels.queued;
            if (this.state === 'cooldown') return this.labels.cooldown.replace(':sec', this.countdown);
            if (this.state === 'error')    return this.labels.error;
            return this.labels.idle;
        },
        async trigger() {
            if (this.state !== 'idle') return;
            this.state = 'queued';
            try {
                const res = await fetch(this.url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json().catch(() => ({}));
                if (res.status === 429 || data.reason === 'cooldown') {
                    this.startCooldown(data.retry_after || 30);
                    return;
                }
                if (!res.ok || !data.queued) {
                    this.state = 'error';
                    setTimeout(() => { this.state = 'idle'; }, 3000);
                    return;
                }
                // Queued OK. Reload page after 5s so the user sees the fresh state.
                setTimeout(() => { window.location.reload(); }, 5000);
            } catch (e) {
                this.state = 'error';
                setTimeout(() => { this.state = 'idle'; }, 3000);
            }
        },
        startCooldown(seconds) {
            this.state = 'cooldown';
            this.countdown = Math.max(1, Math.ceil(seconds));
            this.countdownInterval = setInterval(() => {
                this.countdown--;
                if (this.countdown <= 0) {
                    clearInterval(this.countdownInterval);
                    this.state = 'idle';
                }
            }, 1000);
        },
        destroy() {
            clearInterval(this.countdownInterval);
        },
    };
}
</script>

</x-layouts.app>