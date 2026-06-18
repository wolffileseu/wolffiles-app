<x-layouts.app :title="__('messages.player_search')">
<div class="max-w-7xl mx-auto px-4 py-8">

    <div class="flex flex-wrap items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-amber-500">{{ __('messages.player_search') }}</h1>
            <p class="text-gray-400 mt-1">{{ __('messages.players_tracked_total', ['count' => $players->total()]) }}</p>
        </div>
        <a href="{{ route('tracker.index') }}" class="text-amber-400 hover:text-amber-300">{!! __('messages.back_to_tracker') !!}</a>
    </div>

    {{-- Search & Sort --}}
    <div class="flex flex-wrap gap-3 mb-6">
        <form method="GET" action="{{ route('tracker.players') }}" class="flex-1 min-w-[200px]">
            @if(request('sort')) <input type="hidden" name="sort" value="{{ request('sort') }}"> @endif
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="{{ __('messages.search_player') }}"
                   class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-sm text-white placeholder-gray-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
        </form>
        <div class="flex gap-2">
            @foreach(['last_seen' => __('messages.recent'), 'score' => 'XP', 'playtime' => __('messages.play_time'), 'sessions' => __('messages.sessions')] as $key => $label)
            <a href="{{ route('tracker.players', array_merge(request()->except('page'), ['sort' => $key])) }}"
               class="px-3 py-2 rounded-lg text-sm transition {{ request('sort', 'last_seen') === $key ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                {{ $label }}
            </a>
            @endforeach

            {{-- Toggle filters: Enhanced / Flagged --}}
            <a href="{{ route('tracker.players', array_merge(request()->except(['page','enhanced']), request('enhanced') ? [] : ['enhanced' => 1])) }}"
               class="px-3 py-2 rounded-lg text-sm transition {{ request('enhanced') ? 'bg-emerald-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                &#9889; {{ __('Enhanced') }}
            </a>
            <a href="{{ route('tracker.players', array_merge(request()->except(['page','flagged']), request('flagged') ? [] : ['flagged' => 1])) }}"
               class="px-3 py-2 rounded-lg text-sm transition {{ request('flagged') ? 'bg-red-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                &#9888; {{ __('Flagged') }}
            </a>
        </div>
    </div>

    {{-- Player Table --}}
    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-gray-400 text-left bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">{{ __('messages.players') }}</th>
                        <th class="px-4 py-3">{{ __('messages.country') }}</th>
                        <th class="px-4 py-3 text-center">XP</th>
                        <th class="px-4 py-3 text-center">{{ __('messages.play_time') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('messages.sessions') }}</th>
                        <th class="px-4 py-3">{{ __('messages.last_seen') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    @forelse($players as $player)
                    <tr class="hover:bg-gray-750 transition">
                        <td class="px-4 py-2.5 text-gray-500">{{ $players->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-2.5">
                            <a href="{{ route('tracker.player.show', $player) }}" class="text-amber-400 hover:text-amber-300">
                                {!! (!empty($rawNames[$player->id] ?? null) ? \App\Services\Tracker\ColorCodeService::toHtml($rawNames[$player->id]) : ($player->name_html ?: e($player->name_clean ?: 'Unknown'))) !!}
                                @if($player->has_enhanced_data)
                                    <x-tracker-enhanced-badge class="ml-1 align-middle" />
                                @endif
                                @if($player->is_flagged)
                                    <span class="ml-1 align-middle inline-flex items-center text-[10px] px-1.5 py-0.5 rounded-full bg-red-500/20 text-red-300 border border-red-400/50 uppercase tracking-wider font-bold" title="{{ __('Flagged') }}">&#9888;</span>
                                @endif
                            </a>
                        </td>
                        <td class="px-4 py-2.5 text-gray-400">
                            @if($player->country_code)
                                <x-country-flag :code="$player->country_code" :country="$player->country" /> {{ strtoupper($player->country_code) }}
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-center text-amber-400 font-medium">{{ number_format($player->total_kills) }}</td>
                        <td class="px-4 py-2.5 text-center text-gray-300">{{ $player->play_time_formatted }}</td>
                        <td class="px-4 py-2.5 text-center text-gray-400">{{ $player->total_sessions }}</td>
                        <td class="px-4 py-2.5 text-gray-400 text-xs">{{ $player->last_seen_at?->diffForHumans() ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-500">
                            @if(request('search'))
                                {{ __('messages.no_players_found', ['search' => request('search')]) }}
                            @else
                                {{ __('messages.no_players_tracked') }}
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $players->links() }}</div>
</div>
</x-layouts.app>
