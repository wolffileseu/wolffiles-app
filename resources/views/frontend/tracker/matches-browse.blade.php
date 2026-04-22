<x-layouts.app :title="__('tracker.match_browser') . ' - Wolffiles'">
<div class="max-w-7xl mx-auto px-4 py-8">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-white">{{ __('tracker.match_browser') ?? 'Match Browser' }}</h1>
            <p class="text-gray-400 text-sm mt-1">{{ __('tracker.match_browser_subtitle') ?? 'Search past matches by date range, server, or map.' }}</p>
        </div>
        <a href="{{ route('tracker.index') }}" class="text-amber-400 hover:text-amber-300 text-sm">&larr; {{ __('messages.back') ?? 'Back' }}</a>
    </div>

    <form method="GET" action="{{ route('tracker.matches.browse') }}" class="bg-gray-800 rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs text-gray-400 mb-1">{{ __('tracker.from') ?? 'From' }}</label>
                <input type="datetime-local" name="from" value="{{ $from }}"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">{{ __('tracker.to') ?? 'To' }}</label>
                <input type="datetime-local" name="to" value="{{ $to }}"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">{{ __('tracker.server') ?? 'Server' }}</label>
                <select name="server_id" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm">
                    <option value="">{{ __('tracker.all_servers') ?? 'All servers' }}</option>
                    @foreach($servers as $s)
                        <option value="{{ $s->id }}" @selected((string)$serverId === (string)$s->id)>
                            {{ $s->hostname_clean ?: 'Server #'.$s->id }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">{{ __('tracker.map') ?? 'Map' }}</label>
                <select name="map_name" class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm">
                    <option value="">{{ __('tracker.all_maps') ?? 'All maps' }}</option>
                    @foreach($maps as $m)
                        <option value="{{ $m->map_name }}" @selected($mapName === $m->map_name)>
                            {{ $m->map_name }} ({{ $m->cnt }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">{{ __('tracker.min_players') ?? 'Min players' }}</label>
                <input type="number" name="min_players" value="{{ $minPlayers }}" min="0" max="64"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2 text-white text-sm">
            </div>
        </div>
        <div class="flex gap-2 mt-4">
            <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-black font-medium px-4 py-2 rounded text-sm">
                {{ __('tracker.search') ?? 'Search' }}
            </button>
            <a href="{{ route('tracker.matches.browse') }}" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm">
                {{ __('tracker.reset') ?? 'Reset' }}
            </a>
        </div>
    </form>

    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <div class="px-6 py-3 border-b border-gray-700 text-sm text-gray-400">
            {{ $matches->total() }} {{ __('tracker.matches_found') ?? 'matches found' }}
        </div>

        @if($matches->count() === 0)
            <div class="p-8 text-center text-gray-500">
                {{ __('tracker.no_matches_found') ?? 'No matches found for these filters.' }}
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-900 text-gray-400 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">{{ __('tracker.time') ?? 'Time' }}</th>
                            <th class="px-4 py-3 text-left">{{ __('tracker.map') ?? 'Map' }}</th>
                            <th class="px-4 py-3 text-left">{{ __('tracker.server') ?? 'Server' }}</th>
                            <th class="px-4 py-3 text-right">{{ __('tracker.duration') ?? 'Duration' }}</th>
                            <th class="px-4 py-3 text-right">{{ __('tracker.players') ?? 'Players' }}</th>
                            <th class="px-4 py-3 text-right">{{ __('tracker.kills') ?? 'Kills' }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @foreach($matches as $match)
                            <tr class="hover:bg-gray-700/40">
                                <td class="px-4 py-3 text-gray-300 whitespace-nowrap">
                                    <div>{{ \Carbon\Carbon::parse($match->started_at)->format('Y-m-d') }}</div>
                                    <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($match->started_at)->format('H:i:s') }}</div>
                                </td>
                                <td class="px-4 py-3 text-amber-400 font-mono text-xs">{{ $match->map_name }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('tracker.server.show', $match->server_id) }}" class="text-white hover:text-amber-300">
                                        {!! $match->hostname_html ?: e($match->hostname_clean ?: '#'.$match->server_id) !!}
                                    </a>
                                    @if($match->country_code)
                                        <x-country-flag :code="$match->country_code" />
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-gray-400 whitespace-nowrap">
                                    @if($match->duration_seconds)
                                        {{ gmdate($match->duration_seconds >= 3600 ? 'H:i:s' : 'i:s', (int)$match->duration_seconds) }}
                                    @else
                                        &mdash;
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-gray-300">
                                    {{ $match->player_count_max }}
                                    <span class="text-xs text-gray-500">(&oslash; {{ number_format($match->player_count_avg, 1) }})</span>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-300">{{ $match->total_kills }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('tracker.matches.show', $match->id) }}" class="text-amber-400 hover:text-amber-300 text-xs">
                                        {{ __('tracker.view_details') ?? 'Details &rarr;' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-700">
                {{ $matches->links() }}
            </div>
        @endif
    </div>
</div>
</x-layouts.app>
