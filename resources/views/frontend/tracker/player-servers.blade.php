<x-layouts.app :title="($player->name_clean ?: 'Player').' - Server Activity'">
<div class="max-w-7xl mx-auto px-4 py-8">

    <a href="{{ route('tracker.player.show', $player) }}" class="text-amber-400 hover:text-amber-300 text-sm">
        &larr; {{ __('tracker.back_to_player') ?? 'Back to player' }}
    </a>

    <div class="bg-gray-800 rounded-lg p-6 mt-4 mb-6">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white">
                    {!! $player->name_html ?: e($player->name_clean) !!}
                </h1>
                <p class="text-gray-400 text-sm mt-1">
                    {{ __('tracker.server_activity_subtitle') ?? 'All servers this player has been seen on' }}
                    &mdash; {{ $servers->count() }} {{ __('tracker.servers') ?? 'servers' }}
                </p>
            </div>
            <div class="flex flex-wrap gap-4 text-sm">
                <div>
                    <div class="text-xs text-gray-400 uppercase">{{ __('tracker.total_servers') ?? 'Servers' }}</div>
                    <div class="text-white font-bold">{{ $servers->count() }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-400 uppercase">{{ __('tracker.total_sessions') ?? 'Sessions' }}</div>
                    <div class="text-white font-bold">{{ number_format($servers->sum('session_count')) }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-400 uppercase">{{ __('tracker.total_playtime') ?? 'Playtime' }}</div>
                    <div class="text-white font-bold">{{ round($servers->sum('total_time') / 60) }}h</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sort tabs --}}
    <div class="flex gap-2 mb-4 text-xs flex-wrap">
        @foreach(['playtime' => 'Playtime', 'sessions' => 'Sessions', 'last_played' => 'Last Played', 'first_played' => 'First Played', 'kills' => 'Kills'] as $key => $label)
            <a href="{{ route('tracker.player.servers', [$player, 'sort' => $key]) }}"
               class="px-3 py-1.5 rounded {{ $sortBy === $key ? 'bg-amber-500 text-black font-medium' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                {{ __('tracker.sort_'.$key) ?? $label }}
            </a>
        @endforeach
    </div>

    @if($servers->count() === 0)
        <div class="bg-gray-800 rounded-lg p-8 text-center text-gray-500">
            {{ __('tracker.no_server_activity') ?? 'No server activity recorded yet.' }}
        </div>
    @else
        <div class="space-y-3">
            @foreach($servers as $srv)
                <div class="bg-gray-800 rounded-lg overflow-hidden">
                    <div class="p-4">
                        <div class="flex items-start justify-between flex-wrap gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    @if($srv->is_online)
                                        <span class="w-2 h-2 rounded-full bg-green-500" title="Online"></span>
                                    @else
                                        <span class="w-2 h-2 rounded-full bg-gray-500" title="Offline"></span>
                                    @endif
                                    <a href="{{ route('tracker.server.show', $srv->server_id) }}" class="text-white hover:text-amber-300 font-medium">
                                        {!! $srv->hostname_html ?: e($srv->hostname_clean ?: ('Server #'.$srv->server_id)) !!}
                                    </a>
                                    @if($srv->country_code)
                                        <x-country-flag :code="$srv->country_code" />
                                    @endif
                                    @if($srv->game_short)
                                        <span style="border-left: 3px solid {{ $srv->game_color }}" class="pl-2 text-xs text-gray-400">{{ $srv->game_short }}</span>
                                    @endif
                                    @if($srv->is_enhanced_tracker)
                                        <span class="text-xs px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-400 border border-amber-500/40">Enhanced</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 mt-1">{{ $srv->ip }}:{{ $srv->port }}</div>
                            </div>

                            {{-- Stats Grid --}}
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                                <div class="text-right">
                                    <div class="text-xs text-gray-400 uppercase">{{ __('tracker.sessions') ?? 'Sessions' }}</div>
                                    <div class="text-white font-bold">{{ number_format($srv->session_count) }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-400 uppercase">{{ __('tracker.playtime') ?? 'Playtime' }}</div>
                                    <div class="text-white font-bold">{{ round($srv->total_time / 60) }}h</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-400 uppercase">K / D</div>
                                    <div class="text-white font-bold">
                                        <span class="text-green-400">{{ number_format($srv->total_kills) }}</span>
                                        /
                                        <span class="text-red-400">{{ number_format($srv->total_deaths) }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-400 uppercase">{{ __('tracker.first') ?? 'First' }}</div>
                                    <div class="text-gray-300 text-xs">
                                        @if(!empty($srv->first_played_at))
                                            {{ \Carbon\Carbon::parse($srv->first_played_at)->format('Y-m-d') }}
                                        @else &mdash; @endif
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-400 uppercase">{{ __('tracker.last') ?? 'Last' }}</div>
                                    <div class="text-gray-300 text-xs">
                                        @if(!empty($srv->last_played_at))
                                            {{ \Carbon\Carbon::parse($srv->last_played_at)->diffForHumans() }}
                                        @else &mdash; @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Extra Info: Names used + Top Maps --}}
                        @php
                            $names = $namesByServer->get($srv->server_id, collect());
                            $maps = $topMapByServer->get($srv->server_id, collect());
                        @endphp

                        @if($names->count() > 0 || $maps->count() > 0)
                            <div class="mt-3 pt-3 border-t border-gray-700 grid grid-cols-1 md:grid-cols-2 gap-4">
                                @if($names->count() > 0)
                                    <div>
                                        <div class="text-xs text-gray-400 uppercase mb-1">{{ __('tracker.names_used_here') ?? 'Names used on this server' }}</div>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($names as $n)
                                                <span class="text-xs bg-gray-900 text-amber-300 px-2 py-1 rounded">
                                                    {{ $n->name_clean_snapshot }}
                                                    <span class="text-gray-500">({{ $n->used }}x)</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                @if($maps->count() > 0)
                                    <div>
                                        <div class="text-xs text-gray-400 uppercase mb-1">{{ __('tracker.top_maps') ?? 'Top maps' }}</div>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($maps as $m)
                                                <span class="text-xs bg-gray-900 text-gray-300 px-2 py-1 rounded font-mono">
                                                    {{ $m->map_name }}
                                                    <span class="text-gray-500">({{ $m->c }}x)</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
</x-layouts.app>
