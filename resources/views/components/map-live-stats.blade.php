@props(['stats' => null])

@if($stats)
<div class="bg-gray-800 border border-gray-700 rounded-lg p-4 mb-4">
    <div class="flex items-center gap-2 mb-3">
        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
        <h3 class="text-sm font-mono uppercase tracking-wider text-gray-300">
            {{ __('messages.map_live_stats_title') }}
        </h3>
    </div>

    {{-- Stat-Grid --}}
    <div class="grid grid-cols-3 gap-3 mb-4">
        <div class="bg-gray-900/50 rounded-lg p-3 border border-gray-700/50">
            <div class="text-2xl font-bold text-amber-400 tabular-nums">
                {{ number_format($stats['total_plays']) }}
            </div>
            <div class="text-[10px] font-mono uppercase tracking-wider text-gray-500 mt-1">
                {{ __('messages.map_live_stats_total_plays') }}
            </div>
        </div>
        <div class="bg-gray-900/50 rounded-lg p-3 border border-gray-700/50">
            <div class="text-2xl font-bold text-blue-400 tabular-nums">
                {{ $stats['active_servers'] }}
            </div>
            <div class="text-[10px] font-mono uppercase tracking-wider text-gray-500 mt-1">
                {{ __('messages.map_live_stats_active_servers') }}
            </div>
        </div>
        <div class="bg-gray-900/50 rounded-lg p-3 border border-gray-700/50">
            <div class="text-2xl font-bold text-green-400 tabular-nums">
                {{ $stats['peak_players'] }}
            </div>
            <div class="text-[10px] font-mono uppercase tracking-wider text-gray-500 mt-1">
                {{ __('messages.map_live_stats_peak_players') }}
            </div>
        </div>
    </div>

    {{-- Last played --}}
    @if($stats['last_played_at'])
        <div class="text-xs text-gray-400 mb-3 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ __('messages.map_live_stats_last_played') }}: {{ $stats['last_played_at']->diffForHumans() }}</span>
        </div>
    @endif

    {{-- Top servers --}}
    @if($stats['top_servers']->count() > 0)
        <div class="mt-3 pt-3 border-t border-gray-700/50">
            <div class="text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-2">
                🏆 {{ __('messages.map_live_stats_top_servers') }}
            </div>
            <div class="space-y-1.5">
                @foreach($stats['top_servers'] as $idx => $server)
                    <a href="{{ route('tracker.servers.show', $server->server_id) }}"
                       class="flex items-center gap-2 px-2 py-1.5 bg-gray-900/30 hover:bg-gray-900/60 rounded border border-gray-700/30 hover:border-amber-500/40 transition text-xs group">
                        <span class="w-5 text-center font-mono text-gray-500 group-hover:text-amber-400">
                            {{ $idx + 1 }}.
                        </span>
                        @if($server->country_code)
                            <img src="https://flagcdn.com/{{ strtolower($server->country_code) }}.svg"
                                 alt="{{ $server->country_code }}"
                                 class="w-4 h-3 rounded-sm flex-shrink-0"
                                 loading="lazy">
                        @endif
                        <span class="flex-1 truncate text-gray-300 group-hover:text-amber-400">
                            {{ $server->hostname_clean ?: __('messages.map_live_stats_unnamed_server') }}
                        </span>
                        <span class="text-gray-500 font-mono text-[10px] flex-shrink-0">
                            {{ number_format($server->times_played) }} {{ __('messages.map_live_stats_plays_short') }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endif
