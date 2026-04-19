<x-layouts.app :title="($player->name_clean ?: 'Player') . ' - Player Profile'">
<div class="max-w-7xl mx-auto px-4 py-8">

    <a href="{{ route('tracker.players') }}" class="text-amber-400 hover:text-amber-300 text-sm">&larr; {{ __('messages.back_to_players') }}</a>

    {{-- Player Header --}}
    <div class="bg-gray-800 rounded-lg p-6 mt-4 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-white flex items-center gap-2 flex-wrap">
                        <span>{!! $player->name_html ?: e($player->name_clean ?: 'Unknown') !!}</span>
                        @if($player->has_enhanced_data)
                            <x-tracker-enhanced-badge size="md" />
                        @endif
                        @if($player->is_bot)
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-gray-500/20 text-gray-300 border border-gray-400/30 uppercase tracking-wider font-semibold">Bot</span>
                        @endif
                    </h1>
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
                <div class="text-gray-400 text-sm" title="{{ __('Session-based ELO rating from the legacy Poller system (default: 1000)') }}">ELO Rating ({{ __('messages.elo_peak') }}: {{ number_format($player->elo_peak) }})</div>

                @if($enhancedRating !== null)
                    <div class="mt-3 pt-3 border-t border-gray-700/50">
                        <div class="text-2xl font-bold text-emerald-400">{{ number_format($enhancedRating, 2) }}</div>
                        <div class="text-gray-400 text-sm" title="{{ __('Per-match skill rating from Enhanced Tracker ws-packets (TrueSkill-based mu - 3*sigma)') }}">
                            {{ __('Enhanced Rating') }}
                            @if($enhancedRatingPeak !== null && $enhancedRatingPeak != $enhancedRating)
                                <span class="text-xs">({{ __('messages.elo_peak') }}: {{ number_format($enhancedRatingPeak, 2) }})</span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ $enhancedRatingMatches }} {{ __('rated matches') }}</div>
                    </div>
                @endif

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
            {{-- Total XP — now properly stored in total_xp after the Poller fix
                 (previously total_kills held this value due to a legacy misuse). --}}
            <div class="text-2xl font-bold text-green-400">{{ number_format($player->total_xp) }}</div>
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

{{-- Enhanced Tracker Section --}}
@if($player->has_enhanced_data)
<div class="mt-8 space-y-6">
    {{-- Status Box --}}
    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between flex-wrap gap-2">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span>{{ __('Enhanced Tracker') }}</span>
                <x-tracker-enhanced-badge size="sm" />
            </h2>
            @if($enhancedMatchesCount > 0)
                <span class="text-xs text-gray-400">
                    {{ $enhancedMatchesCount }} {{ __('matches recorded') }}
                </span>
            @endif
        </div>
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <div class="text-xs text-gray-500 uppercase tracking-wider">{{ __('First Enhanced') }}</div>
                <div class="mt-1 text-sm text-gray-200">
                    @if($player->enhanced_first_seen_at)
                        <span title="{{ $player->enhanced_first_seen_at }}">
                            {{ \Carbon\Carbon::parse($player->enhanced_first_seen_at)->diffForHumans() }}
                        </span>
                    @else
                        <span class="text-gray-500">—</span>
                    @endif
                </div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase tracking-wider">{{ __('Last Seen (Enhanced)') }}</div>
                <div class="mt-1 text-sm text-gray-200">
                    @if($player->enhanced_last_seen_at)
                        <span title="{{ $player->enhanced_last_seen_at }}">
                            {{ \Carbon\Carbon::parse($player->enhanced_last_seen_at)->diffForHumans() }}
                        </span>
                    @else
                        <span class="text-gray-500">—</span>
                    @endif
                </div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase tracking-wider">{{ __('Enhanced Matches') }}</div>
                <div class="mt-1 text-lg font-semibold text-emerald-400">{{ $enhancedMatchesCount }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase tracking-wider">{{ __('Account Type') }}</div>
                <div class="mt-1 text-sm text-gray-200">
                    @if($player->is_bot)
                        <span class="inline-flex items-center gap-1 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            {{ __('Bot') }}
                        </span>
                    @else
                        <span class="text-emerald-400">{{ __('Human Player') }}</span>
                    @endif
                </div>
            </div>
        </div>
        @if($latestMatchWeapons->isNotEmpty())
            <div class="px-6 py-5 border-t border-gray-700/50 bg-gray-900/30">
                <div class="flex items-baseline justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-200">
                        {{ __('Weapon Breakdown') }}
                    </h3>
                    @if($latestMatch)
                        <span class="text-xs text-gray-500">
                            {{ __('last match') }}: <span class="text-gray-300">{{ $latestMatch->map_name }}</span>
                        </span>
                    @endif
                </div>

                @if($latestMatchStats)
                    {{-- Aggregate stats line --}}
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs mb-4 pb-4 border-b border-gray-800">
                        @php $classCfg = config('tracker-classes.'.($latestMatchStats->class ?? -1)); @endphp
                        @if($classCfg)
                            <div class="flex items-center gap-2">
                                <img src="/img/tracker/classes/{{ $classCfg['icon'] }}"
                                     alt="{{ $classCfg['name'] }}"
                                     class="w-6 h-6 object-contain"
                                     loading="lazy">
                                <span class="font-semibold" style="color: {{ $classCfg['color'] }}">{{ $classCfg['name'] }}</span>
                            </div>
                        @endif
                        <div>
                            <span class="text-gray-500">{{ __('K/D') }}:</span>
                            <span class="text-gray-200 font-semibold ml-1">{{ $latestMatchStats->kills }}/{{ $latestMatchStats->deaths }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">{{ __('Headshots') }}:</span>
                            <span class="text-gray-200 font-semibold ml-1">{{ $latestMatchStats->headshots }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">{{ __('Accuracy') }}:</span>
                            <span class="text-emerald-400 font-semibold ml-1">{{ number_format($latestMatchStats->accuracy_pct, 2) }}%</span>
                        </div>
                        <div>
                            <span class="text-gray-500">{{ __('Damage') }}:</span>
                            <span class="text-gray-200 font-semibold ml-1">{{ number_format($latestMatchStats->damage_given) }}</span>
                            <span class="text-gray-500">↑ / {{ number_format($latestMatchStats->damage_received) }} ↓</span>
                        </div>
                        @if($latestMatchStats->time_played_pct !== null)
                            <div>
                                <span class="text-gray-500">{{ __('Time played') }}:</span>
                                <span class="text-gray-200 font-semibold ml-1">{{ number_format($latestMatchStats->time_played_pct, 1) }}%</span>
                            </div>
                        @endif
                        @if($latestMatchStats->skill_rating !== null)
                            <div>
                                <span class="text-gray-500">{{ __('Rating') }}:</span>
                                <span class="text-gray-200 font-semibold ml-1">{{ number_format($latestMatchStats->skill_rating, 2) }}</span>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Per-weapon grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach($latestMatchWeapons as $w)
                        @php $cfg = config('tracker-weapons.'.$w->weapon_bit); @endphp
                        @if($cfg)
                        <div class="flex items-center gap-3 bg-gray-800/60 rounded-lg p-2.5 hover:bg-gray-800 transition">
                            <img src="/img/tracker/weapons/{{ $cfg['icon'] }}"
                                 alt="{{ $cfg['name'] }}"
                                 class="w-10 h-10 object-contain flex-shrink-0"
                                 loading="lazy">
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-200 truncate">{{ $cfg['name'] }}</div>
                                <div class="text-xs text-gray-400 flex flex-wrap gap-x-2">
                                    <span>{{ $w->kills }}K/{{ $w->deaths }}D</span>
                                    @if($w->headshots > 0)
                                        <span class="text-amber-400" title="{{ __('Headshots') }}">🎯 {{ $w->headshots }}</span>
                                    @endif
                                    @if($w->atts > 0)
                                        <span class="text-emerald-400">{{ number_format($w->accuracy_bp / 100, 1) }}%</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @else
            <div class="px-6 py-3 bg-gray-900/40 border-t border-gray-700/50">
                <p class="text-xs text-gray-400 flex items-start gap-2">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ __('No weapon stats yet for this player. Play on an Enhanced Tracker enabled server to see detailed weapon breakdown.') }}</span>
                </p>
            </div>
        @endif
    </div>

    {{-- Match History --}}
    @if($enhancedMatches->count() > 0)
    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ __('Recent Enhanced Matches') }}</span>
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-900/50 text-gray-400 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-left">{{ __('Map') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Server') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Started') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Duration') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('End') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    @foreach($enhancedMatches as $m)
                    <tr class="hover:bg-gray-700/20 transition">
                        <td class="px-4 py-3 font-medium text-gray-200">{{ $m->map_name }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('tracker.server.show', $m->server_id) }}" class="text-amber-400 hover:text-amber-300 text-xs">
                                {!! $m->hostname_html ?: e($m->hostname_clean ?: 'Server #'.$m->server_id) !!}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-400 font-mono text-xs" title="{{ $m->started_at }}">
                            {{ \Carbon\Carbon::parse($m->started_at)->diffForHumans() }}
                        </td>
                        <td class="px-4 py-3 text-right text-gray-300 font-mono text-xs">
                            @if(is_null($m->ended_at))
                                <span class="inline-flex items-center gap-1 text-emerald-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    {{ __('In progress') }}
                                </span>
                            @else
                                @php
                                    $d = (int) $m->duration_seconds;
                                    $mm = intdiv($d, 60);
                                    $ss = $d % 60;
                                @endphp
                                {{ $mm }}m {{ str_pad($ss, 2, '0', STR_PAD_LEFT) }}s
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($m->end_reason)
                                @php
                                    $colors = [
                                        'mapend' => 'bg-blue-500/20 text-blue-300',
                                        'mapchange' => 'bg-purple-500/20 text-purple-300',
                                        'maprestart' => 'bg-yellow-500/20 text-yellow-300',
                                        'timeout' => 'bg-gray-500/20 text-gray-300',
                                    ];
                                    $c = $colors[$m->end_reason] ?? 'bg-gray-500/20 text-gray-300';
                                @endphp
                                <span class="px-2 py-0.5 rounded text-[10px] uppercase tracking-wider font-semibold {{ $c }}">
                                    {{ $m->end_reason }}
                                </span>
                            @else
                                <span class="text-gray-500">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endif
</div>
</x-layouts.app>
