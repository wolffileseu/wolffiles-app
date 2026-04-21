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
                        @if(!empty($prestigeLevel) && $prestigeLevel > 0)
                            <span class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full bg-gradient-to-r from-amber-500/20 to-yellow-500/20 text-amber-300 border border-amber-400/40 uppercase tracking-wider font-bold"
                                  title="{{ __('Prestige level :n', ['n' => $prestigeLevel]) }}">
                                ⭐ P{{ $prestigeLevel }}
                            </span>
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
                @if($player->elo_rating !== null)
                    <div class="text-3xl font-bold text-amber-400">{{ number_format($player->elo_rating) }}</div>
                    <div class="text-gray-400 text-sm" title="{{ __('Classic Percentile ELO from Poller data (XP/min rate mapped to a 0-2000 range, median = 1000). Recomputed daily and on profile view if stale.') }}">
                        {{ __('ELO Rating') }}
                        @if($player->elo_peak !== null && $player->elo_peak != $player->elo_rating)
                            <span class="text-xs">({{ __('messages.elo_peak') }}: {{ number_format($player->elo_peak) }})</span>
                        @endif
                    </div>
                @else
                    <div class="text-3xl font-bold text-gray-600">—</div>
                    <div class="text-gray-500 text-sm" title="{{ __('A player needs at least 60 minutes of total playtime and some XP to receive an ELO rating.') }}">
                        {{ __('Unrated') }}
                    </div>
                @endif

                @if($enhancedRating !== null)
                    <div class="mt-3 pt-3 border-t border-gray-700/50">
                        <div class="text-2xl font-bold text-emerald-400">{{ number_format($enhancedRating, 2) }}</div>
                        <div class="text-gray-400 text-sm" title="{{ __('Per-match skill rating from Enhanced Tracker ws-packets (TrueSkill-based mu - 3*sigma)') }}">
                            {{ __('Enhanced Rating') }}
                        </div>
                        <div class="text-xs text-gray-500 mt-0.5 space-x-2">
                            <span>{{ $enhancedRatingMatches }} {{ __('rated matches') }}</span>
                            @if($enhancedRatingAvg !== null && $enhancedRatingMatches > 1)
                                <span>·</span>
                                <span title="{{ __('Weighted average across all rated matches (weight = time_played × match duration).') }}">
                                    {{ __('avg') }}: <span class="text-gray-300">{{ number_format($enhancedRatingAvg, 2) }}</span>
                                </span>
                            @endif
                            @if($enhancedRatingPeak !== null && $enhancedRatingPeak != $enhancedRating)
                                <span>·</span>
                                <span>{{ __('peak') }}: <span class="text-gray-300">{{ number_format($enhancedRatingPeak, 2) }}</span></span>
                            @endif
                        </div>
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
    {{-- =========================================== --}}
    {{-- Combat Overview: HS%, Dmg Ratio, Team Pref --}}
    {{-- =========================================== --}}
    @include('frontend.tracker.partials._combat_overview')

    {{-- Skill XP progression per class --}}
    @include('frontend.tracker.partials._skill_progression')

    {{-- Prestige milestones --}}
    @include('frontend.tracker.partials._prestige_timeline')

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

        {{-- Full-width: Banner Embed --}}
        <div class="lg:col-span-3">
            @include('frontend.tracker.partials.player-banner-embed')
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
        {{-- ELO Trend Chart (SVG sparkline — renders only if we have >1 history point) --}}
        @if(!empty($eloHistory) && $eloHistory->count() > 1)
        @php
            $eh = $eloHistory->pluck('elo_after')->map(fn($v) => (float) $v)->all();
            $ehMax = max($eh); $ehMin = min($eh);
            $ehRange = max($ehMax - $ehMin, 1.0);
            $chartW = 900; $chartH = 160; $padTop = 20; $padBottom = 20; $padX = 10;
            $usableH = $chartH - $padTop - $padBottom;
            $usableW = $chartW - (2 * $padX);
            $step = $usableW / max(count($eh) - 1, 1);
            $coords = [];
            foreach ($eh as $i => $v) {
                $coords[] = [
                    round($padX + ($i * $step), 2),
                    round($chartH - $padBottom - (($v - $ehMin) / $ehRange) * $usableH, 2),
                ];
            }
            $linePath = '';
            foreach ($coords as $i => [$x, $y]) {
                $linePath .= ($i === 0 ? 'M' : ' L') . "$x,$y";
            }
            $fillPath = 'M' . $coords[0][0] . ',' . $chartH . ' L' . $coords[0][0] . ',' . $coords[0][1];
            foreach (array_slice($coords, 1) as [$x, $y]) {
                $fillPath .= " L$x,$y";
            }
            $fillPath .= ' L' . end($coords)[0] . ',' . $chartH . ' Z';
            $ehFirst = $eh[0]; $ehLast = end($eh);
            $ehChange = $ehLast - $ehFirst;
            $ehChangeColor = $ehChange >= 0 ? 'text-emerald-400' : 'text-rose-400';
            $ehChangeSign = $ehChange >= 0 ? '+' : '';
        @endphp
        <div class="px-6 py-5 border-t border-gray-700/50 bg-gray-900/30">
            <div class="flex items-baseline justify-between mb-3 flex-wrap gap-2">
                <h3 class="text-sm font-semibold text-gray-200 flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    {{ __('ELO Trend') }}
                </h3>
                <div class="text-xs">
                    <span class="text-gray-500">{{ number_format($ehFirst, 0) }}</span>
                    <span class="text-gray-600 mx-1">→</span>
                    <span class="text-gray-200 font-semibold">{{ number_format($ehLast, 0) }}</span>
                    <span class="{{ $ehChangeColor }} font-semibold ml-2">{{ $ehChangeSign }}{{ number_format($ehChange, 0) }}</span>
                    <span class="text-gray-600 ml-2">{{ $eloHistory->count() }} {{ __('matches') }}</span>
                </div>
            </div>
            <svg viewBox="0 0 {{ $chartW }} {{ $chartH }}" class="w-full h-32 sm:h-40" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="eloGrad_{{ $player->id }}" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.45"/>
                        <stop offset="100%" stop-color="#f59e0b" stop-opacity="0.02"/>
                    </linearGradient>
                </defs>
                <path d="{{ $fillPath }}" fill="url(#eloGrad_{{ $player->id }})"/>
                <path d="{{ $linePath }}" fill="none" stroke="#f59e0b" stroke-width="2"
                      stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/>
                <text x="{{ $padX }}" y="12" fill="#6b7280" font-size="10" font-family="monospace">{{ number_format($ehMax, 0) }}</text>
                <text x="{{ $padX }}" y="{{ $chartH - 4 }}" fill="#6b7280" font-size="10" font-family="monospace">{{ number_format($ehMin, 0) }}</text>
            </svg>
        </div>
        @endif

        @if($lifetimeWeapons->isNotEmpty())
            <div class="px-6 py-5 border-t border-gray-700/50 bg-gray-900/30">
                <div class="flex items-baseline justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-200">
                        {{ __('Weapon Breakdown') }}
                    </h3>
                    <span class="text-xs text-gray-500">
                        {{ __('lifetime across :count matches', ['count' => $enhancedMatchesCount]) }}
                    </span>
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

                {{-- Extended Combat Stats (Teamplay, Support, Misfires) --}}
                @if($latestMatchStats && (
                    ($latestMatchStats->team_damage_given ?? 0) > 0 ||
                    ($latestMatchStats->team_damage_received ?? 0) > 0 ||
                    ($latestMatchStats->gibs ?? 0) > 0 ||
                    ($latestMatchStats->kill_assists ?? 0) > 0 ||
                    ($latestMatchStats->team_kills ?? 0) > 0 ||
                    ($latestMatchStats->team_gibs ?? 0) > 0 ||
                    ($latestMatchStats->self_kills ?? 0) > 0 ||
                    ($latestMatchStats->suicides ?? 0) > 0 ||
                    ($latestMatchStats->revives_given ?? 0) > 0 ||
                    ($latestMatchStats->revives_received ?? 0) > 0 ||
                    ($latestMatchStats->objectives_taken ?? 0) > 0
                ))
                <div class="mb-4 bg-gray-900/30 rounded-lg p-4">
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        {{ __('Extended Combat') }}
                        <span class="text-gray-600 font-normal normal-case">({{ __('latest match') }})</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 text-xs">
                        @if(($latestMatchStats->team_damage_given ?? 0) > 0 || ($latestMatchStats->team_damage_received ?? 0) > 0)
                        <div class="bg-gray-800/60 rounded p-2">
                            <div class="text-gray-500 uppercase tracking-wide text-[10px]">{{ __('Team DMG') }}</div>
                            <div class="mt-0.5">
                                <span class="text-rose-400 font-semibold">{{ number_format($latestMatchStats->team_damage_given ?? 0) }}</span><span class="text-gray-600">↑ / </span><span class="text-gray-400">{{ number_format($latestMatchStats->team_damage_received ?? 0) }}</span><span class="text-gray-600">↓</span>
                            </div>
                        </div>
                        @endif
                        @if(($latestMatchStats->revives_given ?? 0) > 0 || ($latestMatchStats->revives_received ?? 0) > 0)
                        <div class="bg-gray-800/60 rounded p-2">
                            <div class="text-gray-500 uppercase tracking-wide text-[10px]">{{ __('Revives') }}</div>
                            <div class="mt-0.5">
                                <span class="text-emerald-400 font-semibold">{{ number_format($latestMatchStats->revives_given ?? 0) }}</span><span class="text-gray-600">↑ / </span><span class="text-gray-400">{{ number_format($latestMatchStats->revives_received ?? 0) }}</span><span class="text-gray-600">↓</span>
                            </div>
                        </div>
                        @endif
                        @if(($latestMatchStats->objectives_taken ?? 0) > 0)
                        <div class="bg-gray-800/60 rounded p-2">
                            <div class="text-gray-500 uppercase tracking-wide text-[10px]">🎯 {{ __('Objectives') }}</div>
                            <div class="mt-0.5 text-amber-400 font-bold">{{ number_format($latestMatchStats->objectives_taken ?? 0) }}</div>
                        </div>
                        @endif
                        @if(($latestMatchStats->kill_assists ?? 0) > 0)
                        <div class="bg-gray-800/60 rounded p-2">
                            <div class="text-gray-500 uppercase tracking-wide text-[10px]">{{ __('Assists') }}</div>
                            <div class="mt-0.5 text-gray-200 font-semibold">{{ number_format($latestMatchStats->kill_assists ?? 0) }}</div>
                        </div>
                        @endif
                        @if(($latestMatchStats->gibs ?? 0) > 0)
                        <div class="bg-gray-800/60 rounded p-2">
                            <div class="text-gray-500 uppercase tracking-wide text-[10px]">{{ __('Gibs') }}</div>
                            <div class="mt-0.5 text-gray-200 font-semibold">{{ number_format($latestMatchStats->gibs ?? 0) }}</div>
                        </div>
                        @endif
                        @if(($latestMatchStats->team_kills ?? 0) > 0)
                        <div class="bg-gray-800/60 rounded p-2">
                            <div class="text-gray-500 uppercase tracking-wide text-[10px]">⚠️ {{ __('Team Kills') }}</div>
                            <div class="mt-0.5 text-rose-400 font-semibold">{{ number_format($latestMatchStats->team_kills ?? 0) }}</div>
                        </div>
                        @endif
                        @if(($latestMatchStats->team_gibs ?? 0) > 0)
                        <div class="bg-gray-800/60 rounded p-2">
                            <div class="text-gray-500 uppercase tracking-wide text-[10px]">⚠️ {{ __('Team Gibs') }}</div>
                            <div class="mt-0.5 text-rose-400 font-semibold">{{ number_format($latestMatchStats->team_gibs ?? 0) }}</div>
                        </div>
                        @endif
                        @if(($latestMatchStats->self_kills ?? 0) > 0)
                        <div class="bg-gray-800/60 rounded p-2">
                            <div class="text-gray-500 uppercase tracking-wide text-[10px]">{{ __('Self Kills') }}</div>
                            <div class="mt-0.5 text-gray-300 font-semibold">{{ number_format($latestMatchStats->self_kills ?? 0) }}</div>
                        </div>
                        @endif
                        @if(($latestMatchStats->suicides ?? 0) > 0)
                        <div class="bg-gray-800/60 rounded p-2">
                            <div class="text-gray-500 uppercase tracking-wide text-[10px]">{{ __('Suicides') }}</div>
                            <div class="mt-0.5 text-gray-300 font-semibold">{{ number_format($latestMatchStats->suicides ?? 0) }}</div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Per-weapon grid (lifetime totals across all enhanced matches) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach($lifetimeWeapons as $w)
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
                                    <span>{{ $w->total_kills }}K/{{ $w->total_deaths }}D</span>
                                    @if($w->total_headshots > 0)
                                        <span class="text-amber-400" title="{{ __('Headshots') }}">🎯 {{ $w->total_headshots }}</span>
                                    @endif
                                    @if($w->total_atts > 0)
                                        <span class="text-emerald-400">{{ number_format(min(10000, $w->accuracy_bp) / 100, 1) }}%</span>
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

    {{-- XP Skills Card --}}
    @if(!empty($xpSkills))
    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700 flex items-baseline justify-between flex-wrap gap-2">
            <h3 class="text-sm font-semibold text-gray-200 flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
                {{ __('XP Skills') }}
            </h3>
            <span class="text-xs text-gray-500">{{ __('highest level reached per skill') }}</span>
        </div>
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($xpSkills as $skill)
                @php
                    $c = $skill['color'];
                    $dotClass  = 'bg-'.$c.'-500';
                    $barClass  = 'bg-gradient-to-r from-'.$c.'-600 to-'.$c.'-400';
                    $lvlClass  = $skill['level'] > 0 ? 'text-'.$c.'-400' : 'text-gray-600';
                @endphp
                <div class="bg-gray-900/40 rounded-lg p-3 hover:bg-gray-900/60 transition">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-2 h-8 rounded-full {{ $dotClass }} flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-200 truncate">{{ __($skill['name']) }}</div>
                            <div class="text-xs text-gray-500">
                                {{ $skill['current'] }} XP
                                @if($skill['delta'] > 0)
                                    <span class="text-emerald-400 ml-1">+{{ $skill['delta'] }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <div class="text-[10px] text-gray-500 uppercase tracking-wider leading-none">{{ __('Level') }}</div>
                            <div class="text-xl font-bold leading-tight {{ $lvlClass }}">{{ $skill['level'] }}</div>
                        </div>
                    </div>
                    <div class="h-1.5 bg-gray-800 rounded-full overflow-hidden">
                        <div class="h-full {{ $barClass }} rounded-full transition-all"
                             style="width: {{ number_format($skill['progress'], 1) }}%"></div>
                    </div>
                    <div class="flex justify-between text-[10px] text-gray-600 mt-1">
                        <span>{{ $skill['prev_threshold'] }}</span>
                        @if($skill['next_threshold'])
                            <span>{{ $skill['next_threshold'] }} XP</span>
                        @else
                            <span class="text-amber-400 font-semibold">MAX</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Match History --}}
    @if(!empty($playerTimeline))
    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700 flex items-baseline justify-between">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ __('Activity Timeline') }}</span>
            </h2>
            <span class="text-xs text-gray-500">
                {{ $enhancedMatchesCount }} {{ __('enhanced matches total') }}
            </span>
        </div>
        <div class="divide-y divide-gray-700/50">
            @foreach($playerTimeline as $entry)
                @php
                    $classCfg = config('tracker-classes.'.$entry['class']);
                    $durationMin = intdiv($entry['total_duration'], 60);
                    $ratingDelta = $entry['latest_rating_delta'] ?? 0;
                    $ratingClass = $ratingDelta > 0.01 ? 'text-emerald-400' : ($ratingDelta < -0.01 ? 'text-rose-400' : 'text-gray-400');
                    $hasAction = $entry['total_kills'] > 0 || $entry['total_deaths'] > 0;
                @endphp
                <div class="px-6 py-4 hover:bg-gray-700/20 transition">
                    <div class="flex items-start gap-4">
                        {{-- Class icon column --}}
                        <div class="flex-shrink-0 w-12 flex flex-col items-center pt-1">
                            @if($classCfg)
                                <img src="/img/tracker/classes/{{ $classCfg['icon'] }}"
                                     alt="{{ $classCfg['name'] }}"
                                     title="{{ $classCfg['name'] }}"
                                     class="w-10 h-10 object-contain opacity-90"
                                     loading="lazy">
                                <span class="text-[10px] font-medium mt-1 {{ $classCfg['color_class'] ?? 'text-gray-400' }}">
                                    {{ $classCfg['name'] }}
                                </span>
                            @else
                                <div class="w-10 h-10 rounded bg-gray-700/50"></div>
                            @endif
                        </div>

                        {{-- Main content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline justify-between gap-2 mb-1">
                                <h3 class="text-base font-semibold text-gray-100 truncate">
                                    🗺️ {{ $entry['map_name'] }}
                                    @if($entry['match_count'] > 1)
                                        <span class="text-xs text-gray-500 font-normal ml-2">
                                            · {{ $entry['match_count'] }} {{ __('rounds') }}
                                        </span>
                                    @endif
                                </h3>
                                <span class="text-xs text-gray-500 flex-shrink-0" title="{{ $entry['ended_at'] ?? $entry['started_at'] }}">
                                    {{ \Carbon\Carbon::parse($entry['ended_at'] ?? $entry['started_at'])->diffForHumans() }}
                                </span>
                            </div>

                            <div class="text-xs text-gray-400 mb-2">
                                @if($entry['server_id'])
                                    <a href="{{ route('tracker.server.show', $entry['server_id']) }}" class="text-amber-400 hover:text-amber-300">
                                        {!! $entry['server_html'] ?: e($entry['server_name'] ?: 'Server #'.$entry['server_id']) !!}
                                    </a>
                                @endif
                                @if($durationMin > 0)
                                    <span class="text-gray-600 mx-1">·</span>
                                    <span>{{ $durationMin }}m {{ __('played') }}</span>
                                @endif
                            </div>

                            @if($hasAction)
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                                    <span class="text-gray-300" title="{{ __('Kills / Deaths') }}">
                                        <span class="text-emerald-400 font-semibold">{{ $entry['total_kills'] }}</span>K /
                                        <span class="text-rose-400 font-semibold">{{ $entry['total_deaths'] }}</span>D
                                    </span>

                                    @if($entry['total_headshots'] > 0)
                                        <span class="text-amber-400" title="{{ __('Headshots') }}">
                                            🎯 {{ $entry['total_headshots'] }}
                                        </span>
                                    @endif

                                    @if($entry['total_damage_given'] > 0 || $entry['total_damage_received'] > 0)
                                        <span class="text-gray-400" title="{{ __('Damage given / received') }}">
                                            💥 <span class="text-emerald-400">{{ number_format($entry['total_damage_given']) }}</span>↑ /
                                            <span class="text-rose-400">{{ number_format($entry['total_damage_received']) }}</span>↓
                                        </span>
                                    @endif

                                    @if($entry['latest_rating'] !== null)
                                        <span class="ml-auto {{ $ratingClass }} font-mono text-xs">
                                            ⭐ {{ number_format($entry['latest_rating'], 2) }}
                                            @if(abs($ratingDelta) > 0.01)
                                                ({{ $ratingDelta > 0 ? '+' : '' }}{{ number_format($ratingDelta, 2) }})
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            @else
                                <div class="text-xs text-gray-500 italic">
                                    {{ __('No scoring activity (warmup/spectator)') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
</div>




@endif
</div>
</x-layouts.app>
