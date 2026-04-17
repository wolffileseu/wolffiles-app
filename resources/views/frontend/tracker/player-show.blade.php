<x-layouts.app :title="($player->name_clean ?: 'Player') . ' - Player Profile'">
<div class="max-w-7xl mx-auto px-4 py-8">

    <a href="{{ route('tracker.players') }}" class="text-amber-400 hover:text-amber-300 text-sm">&larr; {{ __('messages.back_to_players') }}</a>

    {{-- Player Header --}}
    <div class="bg-gray-800 rounded-lg p-6 mt-4 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-white">{!! $player->name_html ?: e($player->name_clean ?: 'Unknown') !!}</h1>
                    @if($player->active_clan)
                        <span class="bg-gray-700 px-2 py-0.5 rounded text-sm text-gray-300">{{ $player->active_clan->tag }}</span>
                    @endif
                </div>
                <div class="flex flex-wrap gap-4 mt-2 text-sm text-gray-400">
                    @if($player->country)
                        <x-country-flag :code="$player->country_code" :country="$player->country" /> {{ $player->country }}
                    @endif
                    <span>First seen {{ $player->first_seen_at?->format('M j, Y') ?? 'Unknown' }}</span>
                    <span>Last seen {{ $player->last_seen_at?->diffForHumans() ?? 'Unknown' }}</span>
                </div>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold text-amber-400">{{ number_format($player->elo_rating) }}</div>
                <div class="text-gray-400 text-sm">ELO Rating ({{ __('messages.elo_peak') }}: {{ number_format($player->elo_peak) }})</div>
                @auth
                <div class="mt-2">
                @if(!$player->claimed_by_user_id)
                <a href="{{ route('tracker.claim.player', $player) }}" class="px-3 py-1.5 bg-gray-700 text-amber-400 rounded-lg text-xs hover:bg-gray-600 transition border border-amber-500/30">&#x1f6e1;&#xfe0f; Claim Profile</a>
                @elseif($player->claimed_by_user_id === auth()->id())
                <span class="px-3 py-1.5 bg-green-900/30 text-green-400 rounded-lg text-xs border border-green-500/30">&#x2713; Your Profile</span>
                @else
                <span class="px-3 py-1.5 bg-gray-700 text-gray-500 rounded-lg text-xs">&#x2713; Claimed</span>
                @endif
                </div>
                @endauth
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-gray-800 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-white">{{ $player->play_time_formatted }}</div>
            <div class="text-gray-400 text-xs">{{ __('messages.play_time') }}</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 text-center">
            {{-- total_kills column currently stores XP (see TrackerPlayer model) --}}
            <div class="text-2xl font-bold text-green-400">{{ number_format($player->total_kills) }}</div>
            <div class="text-gray-400 text-xs">{{ __('messages.total_xp') }}</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-cyan-400">{{ number_format($player->xp_per_hour) }}</div>
            <div class="text-gray-400 text-xs">{{ __('messages.xp_per_hour') }}</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-purple-400">{{ number_format($player->avg_xp_per_session) }}</div>
            <div class="text-gray-400 text-xs">{{ __('messages.avg_xp_session') }}</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-400">{{ number_format($player->total_sessions) }}</div>
            <div class="text-gray-400 text-xs">{{ __('messages.sessions') }}</div>
        </div>
    </div>


    {{-- Phase 2: Extended Activity Stats --}}
    @php
        $act = $player->activity_stats;
        $dayNames = [];
        for ($i = 0; $i < 7; $i++) {
            $dayNames[$i] = \Carbon\Carbon::now()
                ->startOfWeek(\Carbon\Carbon::MONDAY)
                ->addDays($i)
                ->locale(app()->getLocale())
                ->shortDayName;
        }
        $peakLabel = $act['peak']['count'] > 0
            ? $dayNames[$act['peak']['dow']] . ', ' . sprintf('%02d:00', $act['peak']['hour'])
            : '—';
    @endphp

    {{-- Second stats row --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-800 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-emerald-400">{{ $act['streaks']['longest'] }}</div>
            <div class="text-gray-400 text-xs">{{ __('messages.longest_streak') }}</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold {{ $act['streaks']['current'] > 0 ? 'text-orange-400' : 'text-gray-500' }}">{{ $act['streaks']['current'] }}</div>
            <div class="text-gray-400 text-xs">{{ __('messages.current_streak') }}</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-pink-400">{{ number_format($act['distinct_maps']) }}</div>
            <div class="text-gray-400 text-xs">{{ __('messages.distinct_maps') }}</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-yellow-400">{{ $peakLabel }}</div>
            <div class="text-gray-400 text-xs">{{ __('messages.peak_activity') }}</div>
        </div>
    </div>

    {{-- Activity Heatmap --}}
    @if($act['peak']['count'] > 0)
    <div class="bg-gray-800 rounded-lg p-4 mb-6">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold text-white">{{ __('messages.activity_heatmap') }}</h2>
            <span class="text-xs text-gray-500">{{ $act['active_days'] }} {{ __('messages.active_days') }}</span>
        </div>
        <div class="overflow-x-auto">
            <div class="inline-block">
                {{-- Hour labels row --}}
                <div class="flex items-center gap-0.5 mb-1">
                    <div class="w-10"></div>
                    @for ($h = 0; $h < 24; $h++)
                        <div class="w-3 text-[9px] text-gray-500 text-center">
                            {{ $h % 6 === 0 ? $h : '' }}
                        </div>
                    @endfor
                </div>
                {{-- Grid rows --}}
                @foreach ($act['heatmap'] as $dow => $hours)
                    <div class="flex items-center gap-0.5 mb-0.5">
                        <div class="w-10 text-[11px] text-gray-400 pr-2 text-right">{{ $dayNames[$dow] }}</div>
                        @foreach ($hours as $hr => $count)
                            @php
                                $intensity = $act['peak']['count'] > 0 ? ($count / $act['peak']['count']) : 0;
                                if ($count === 0) {
                                    $bg = 'rgb(17, 24, 39)';
                                } else {
                                    $alpha = 0.25 + $intensity * 0.75;
                                    $bg = 'rgba(251, 191, 36, ' . number_format($alpha, 2) . ')';
                                }
                            @endphp
                            <div
                                class="w-3 h-3 rounded-sm border border-gray-900/50"
                                style="background: {{ $bg }};"
                                title="{{ $dayNames[$dow] }} {{ sprintf('%02d:00', $hr) }} — {{ $count }} {{ __('messages.sessions') }}"
                            ></div>
                        @endforeach
                    </div>
                @endforeach
                {{-- Legend --}}
                <div class="flex items-center gap-2 mt-3 text-[10px] text-gray-500">
                    <span>{{ __('messages.less') }}</span>
                    <div class="w-3 h-3 rounded-sm" style="background: rgb(17, 24, 39);"></div>
                    <div class="w-3 h-3 rounded-sm" style="background: rgba(251, 191, 36, 0.35);"></div>
                    <div class="w-3 h-3 rounded-sm" style="background: rgba(251, 191, 36, 0.6);"></div>
                    <div class="w-3 h-3 rounded-sm" style="background: rgba(251, 191, 36, 0.85);"></div>
                    <div class="w-3 h-3 rounded-sm" style="background: rgba(251, 191, 36, 1);"></div>
                    <span>{{ __('messages.more') }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Recent Sessions --}}
            <div class="bg-gray-800 rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-700">
                    <h2 class="text-lg font-semibold text-white">{{ __('messages.recent_sessions') }}</h2>
                </div>
                @if($sessions->count() > 0)
                <table class="w-full text-sm">
                    <thead class="text-gray-400 text-left bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-2">{{ __('messages.server_name') }}</th>
                            <th class="px-4 py-2">{{ __('messages.map') }}</th>
                            <th class="px-4 py-2 text-center">{{ __('messages.score') }}</th>
                            <th class="px-4 py-2 text-center">{{ __('messages.duration') }}</th>
                            <th class="px-4 py-2">{{ __('messages.when') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700/50">
                        @foreach($sessions as $session)
                        <tr class="hover:bg-gray-750">
                            <td class="px-4 py-2">
                                @if($session->server)
                                    <a href="{{ route('tracker.server.show', $session->server) }}" class="text-amber-400 hover:text-amber-300 text-xs">
                                        {!! $session->server->hostname_html ?: e($session->server->hostname_clean ?: $session->server->full_address) !!}
                                    </a>
                                @else
                                    <span class="text-gray-500">Deleted</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-gray-300">{{ $session->map_name ?: '-' }}</td>
                            <td class="px-4 py-2 text-center text-gray-300">{{ $session->score }}</td>
                            <td class="px-4 py-2 text-center text-gray-400">{{ $session->duration_minutes }}m</td>
                            <td class="px-4 py-2 text-gray-400 text-xs">
                                {{ $session->started_at->diffForHumans() }}
                                @if(!$session->ended_at)
                                    <span class="text-green-400 ml-1">{{ __('messages.live') }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="px-4 py-8 text-center text-gray-500">{{ __('messages.no_sessions_yet') }}</div>
                @endif
            </div>

        </div>

        {{-- Right Sidebar --}}
        <div class="space-y-6">

            {{-- Aliases --}}
            @if($player->aliases->count() > 0)
            <div class="bg-gray-800 rounded-lg p-4">
                <h2 class="text-lg font-semibold text-white mb-3">{{ __('messages.known_aliases') }}</h2>
                <div class="space-y-1.5">
                    @foreach($player->aliases->sortByDesc('times_used')->take(15) as $alias)
                    <div class="flex justify-between text-sm">
                        <span>{!! $alias->name_html ?: e($alias->name_clean) !!}</span>
                        <span class="text-gray-500 text-xs">{{ $alias->times_used }}x</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Favorite Servers --}}
            @if($favoriteServers->count() > 0)
            <div class="bg-gray-800 rounded-lg p-4">
                <h2 class="text-lg font-semibold text-white mb-3">{{ __('messages.favorite_servers') }}</h2>
                <div class="space-y-2">
                    @foreach($favoriteServers as $fav)
                    @if($fav->server)
                    <div class="text-sm">
                        <a href="{{ route('tracker.server.show', $fav->server) }}" class="text-amber-400 hover:text-amber-300 text-xs">
                            {!! $fav->server->hostname_html ?: e($fav->server->hostname_clean) !!}
                        </a>
                        <div class="text-gray-500 text-xs">{{ $fav->session_count }} sessions &middot; {{ round($fav->total_time / 60) }}h</div>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Favorite Maps --}}
            @if($favoriteMaps->count() > 0)
            <div class="bg-gray-800 rounded-lg p-4">
                <h2 class="text-lg font-semibold text-white mb-3">{{ __('messages.favorite_maps') }}</h2>
                <div class="space-y-2">
                    @foreach($favoriteMaps as $map)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-300">{{ $map->map_name }}</span>
                        <span class="text-gray-500 text-xs">{{ $map->times_played }}x &middot; {{ round($map->total_time / 60) }}h</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </div>
</div>
</x-layouts.app>
