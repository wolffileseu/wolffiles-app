<a href="{{ route('tracker.server.show', $server) }}"
   class="group flex items-center justify-between gap-3 p-3 bg-gray-900/40 hover:bg-gray-900/80 border border-gray-700/40 hover:border-amber-600/40 rounded-md transition-all">
    <div class="flex items-center gap-3 min-w-0 flex-1">
        @if($server->country_code)
            <img src="https://flagcdn.com/24x18/{{ strtolower($server->country_code) }}.png"
                 alt="{{ $server->country_code }}"
                 class="w-6 h-[18px] flex-shrink-0 rounded-sm shadow"
                 loading="lazy" width="24" height="18"
                 title="{{ $server->country }}">
        @endif
        <div class="min-w-0 flex-1">
            <div class="text-sm font-medium text-gray-100 group-hover:text-amber-400 truncate">
                {!! $server->hostname_html ?: e($server->hostname_clean ?: $server->hostname) !!}
            </div>
            <div class="text-xs text-gray-500 truncate">
                {{ $server->ip }}:{{ $server->port }}
                @if($server->mod_name)
                    <span class="text-gray-600">·</span> {{ $server->mod_name }}
                @endif
                @if(!$isLive && $server->getAttribute('map_last_played_at'))
                    <span class="text-gray-600">·</span> {{ \Carbon\Carbon::parse($server->getAttribute('map_last_played_at'))->diffForHumans() }}
                @endif
            </div>
        </div>
    </div>
    <div class="flex items-center gap-3 flex-shrink-0">
        @if($isLive)
            <div class="text-right">
                <div class="text-sm font-semibold {{ $server->current_players > 0 ? 'text-green-400' : 'text-gray-500' }}">
                    {{ $server->current_players }}/{{ $server->max_players }}
                </div>
                <div class="text-[10px] text-gray-500 uppercase tracking-wide">{{ __('messages.players') }}</div>
            </div>
        @else
            <div class="text-right">
                <div class="text-xs {{ $server->is_online ? 'text-gray-300' : 'text-gray-600' }}">
                    {{ $server->current_players ?? 0 }}/{{ $server->max_players ?? '?' }}
                </div>
                <div class="text-[10px] {{ $server->is_online ? 'text-green-500' : 'text-gray-600' }} uppercase tracking-wide">
                    {{ $server->is_online ? __('messages.online') : __('messages.offline') }}
                </div>
            </div>
        @endif
    </div>
</a>
