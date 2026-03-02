<x-layouts.app :title="'Rankings'">
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-wrap items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-amber-500">Rankings</h1>
            <p class="text-gray-400 mt-1">Player Leaderboard</p>
        </div>
        <a href="{{ route('tracker.index') }}" class="text-amber-400 hover:text-amber-300">&larr; Back to Tracker</a>
    </div>

    <div class="flex flex-wrap gap-2 mb-8">
        @foreach(['alltime'=>'All Time','monthly'=>'Monthly','weekly'=>'Weekly','daily'=>'Daily'] as $key=>$label)
        <a href="{{ route('tracker.rankings',['period'=>$key]) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $period===$key ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if($top3->count() >= 3)
    <div class="flex items-end justify-center gap-4 mb-12 px-4">
        {{-- Silver #2 --}}
        @php $entry = $top3[1]; @endphp
        <div class="flex flex-col items-center">
            <a href="{{ route('tracker.player.show', $entry->player) }}" class="text-center group">
                <div class="w-16 h-16 rounded-full bg-gray-700 border-2 border-gray-400 flex items-center justify-center mb-2 text-2xl group-hover:scale-110 transition">🥈</div>
                <div class="text-white font-semibold text-sm group-hover:text-amber-400 truncate max-w-[130px]">{!! $entry->player->name_html ?? e($entry->player->name_clean ?? 'Unknown') !!}</div>
                <div class="text-gray-400 text-xs">{{ number_format($entry->elo_rating) }} ELO</div>
            </a>
            <div class="w-24 h-20 bg-gradient-to-t from-gray-600 to-gray-500 rounded-t-lg mt-2 flex items-center justify-center">
                <span class="text-3xl font-black text-gray-200">2</span>
            </div>
        </div>

        {{-- Gold #1 --}}
        @php $entry = $top3[0]; @endphp
        <div class="flex flex-col items-center">
            <a href="{{ route('tracker.player.show', $entry->player) }}" class="text-center group">
                <div class="w-20 h-20 rounded-full bg-gray-700 border-2 border-amber-500 flex items-center justify-center mb-2 text-2xl group-hover:scale-110 transition">👑</div>
                <div class="text-white font-semibold text-sm group-hover:text-amber-400 truncate max-w-[130px]">{!! $entry->player->name_html ?? e($entry->player->name_clean ?? 'Unknown') !!}</div>
                <div class="text-gray-400 text-xs">{{ number_format($entry->elo_rating) }} ELO</div>
            </a>
            <div class="w-24 h-28 bg-gradient-to-t from-amber-700 to-amber-500 rounded-t-lg mt-2 flex items-center justify-center">
                <span class="text-3xl font-black text-gray-200">1</span>
            </div>
        </div>

        {{-- Bronze #3 --}}
        @php $entry = $top3[2]; @endphp
        <div class="flex flex-col items-center">
            <a href="{{ route('tracker.player.show', $entry->player) }}" class="text-center group">
                <div class="w-16 h-16 rounded-full bg-gray-700 border-2 border-orange-600 flex items-center justify-center mb-2 text-2xl group-hover:scale-110 transition">🥉</div>
                <div class="text-white font-semibold text-sm group-hover:text-amber-400 truncate max-w-[130px]">{!! $entry->player->name_html ?? e($entry->player->name_clean ?? 'Unknown') !!}</div>
                <div class="text-gray-400 text-xs">{{ number_format($entry->elo_rating) }} ELO</div>
            </a>
            <div class="w-24 h-14 bg-gradient-to-t from-orange-800 to-orange-600 rounded-t-lg mt-2 flex items-center justify-center">
                <span class="text-3xl font-black text-gray-200">3</span>
            </div>
        </div>
    </div>
    @endif

    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-gray-400 text-left bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 w-16">#</th>
                        <th class="px-4 py-3">Player</th>
                        <th class="px-4 py-3">Country</th>
                        <th class="px-4 py-3 text-center">ELO</th>
                        <th class="px-4 py-3 text-center">XP</th>
                        <th class="px-4 py-3 text-center">K/D</th>
                        <th class="px-4 py-3 text-center">Play Time</th>
                        <th class="px-4 py-3 text-center">Sessions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    @forelse($rankings as $entry)
                    <tr class="hover:bg-gray-750 transition {{ $entry->rank <= 3 ? 'bg-gray-700/30' : '' }}">
                        <td class="px-4 py-2.5">
                            @if($entry->rank <= 3)
                                <span>{{ ['','🥇','🥈','🥉'][$entry->rank] }}</span>
                            @else
                                <span class="text-gray-500">{{ $entry->rank }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5">
                            <a href="{{ route('tracker.player.show', $entry->player) }}" class="text-amber-400 hover:text-amber-300 font-medium">
                                {!! $entry->player->name_html ?? e($entry->player->name_clean ?? 'Unknown') !!}
                            </a>
                        </td>
                        <td class="px-4 py-2.5">
                            @if($entry->player->country_code)
                            <span class="flex items-center gap-1.5">
                                <img src="https://flagcdn.com/{{ strtolower($entry->player->country_code) }}.svg" class="w-4 h-3 rounded-sm" alt="">
                                <span class="text-gray-400 text-xs">{{ strtoupper($entry->player->country_code) }}</span>
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-center font-bold text-white">{{ number_format($entry->elo_rating) }}</td>
                        <td class="px-4 py-2.5 text-center text-gray-300">{{ number_format($entry->total_xp) }}</td>
                        <td class="px-4 py-2.5 text-center text-gray-300">
                            {{ $entry->deaths > 0 ? round($entry->kills / $entry->deaths, 2) : $entry->kills }}
                        </td>
                        <td class="px-4 py-2.5 text-center text-gray-400">
                            {{ $entry->playtime_minutes >= 60 ? round($entry->playtime_minutes/60,1).'h' : $entry->playtime_minutes.'m' }}
                        </td>
                        <td class="px-4 py-2.5 text-center text-gray-400">{{ $entry->sessions_count }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-4 py-12 text-center text-gray-500">No rankings calculated yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($rankings->hasPages()) <div class="mt-4">{{ $rankings->links() }}</div> @endif
</div>
</x-layouts.app>
