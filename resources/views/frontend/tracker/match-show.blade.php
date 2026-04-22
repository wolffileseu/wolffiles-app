<x-layouts.app :title="'Match #'.$match->id.' - '.$match->map_name">
<div class="max-w-7xl mx-auto px-4 py-8">

    <a href="{{ url()->previous() }}" class="text-amber-400 hover:text-amber-300 text-sm">&larr; {{ __('tracker.back_to_matches') ?? 'Back to matches' }}</a>

    <div class="bg-gray-800 rounded-lg p-6 mt-4 mb-6">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white">
                    {{ $match->map_name }}
                    <span class="text-gray-500 text-sm font-normal">#{{ $match->id }}</span>
                </h1>
                <div class="flex flex-wrap gap-4 mt-2 text-sm text-gray-400">
                    <span>{{ \Carbon\Carbon::parse($match->started_at)->format('Y-m-d H:i:s') }}</span>
                    @if($match->duration_seconds)
                        <span>{{ gmdate($match->duration_seconds >= 3600 ? 'H:i:s' : 'i:s', (int)$match->duration_seconds) }}</span>
                    @endif
                    <span>{{ $match->player_count_max }} players (&oslash; {{ number_format($match->player_count_avg, 1) }})</span>
                    @if($match->end_reason)
                        <span class="text-gray-500">{{ $match->end_reason }}</span>
                    @endif
                </div>
                <div class="mt-3">
                    <a href="{{ route('tracker.server.show', $match->server_id) }}" class="text-amber-400 hover:text-amber-300">
                        {!! $match->hostname_html ?: e($match->hostname_clean ?: '#'.$match->server_id) !!}
                    </a>
                </div>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold text-white">{{ $match->total_kills }}</div>
                <div class="text-xs text-gray-400 uppercase">{{ __('tracker.total_kills') ?? 'Total Kills' }}</div>
            </div>
        </div>
    </div>

    @php
        $teamColors = [
            'axis' => ['bg' => 'bg-red-900/30', 'border' => 'border-red-700', 'label' => 'text-red-400'],
            'allies' => ['bg' => 'bg-blue-900/30', 'border' => 'border-blue-700', 'label' => 'text-blue-400'],
            'spec' => ['bg' => 'bg-gray-800', 'border' => 'border-gray-700', 'label' => 'text-gray-400'],
            'unknown' => ['bg' => 'bg-gray-800', 'border' => 'border-gray-700', 'label' => 'text-gray-400'],
        ];
        $teamLabels = [
            'axis' => __('tracker.team_axis') ?? 'Axis',
            'allies' => __('tracker.team_allies') ?? 'Allies',
            'spec' => __('tracker.team_spec') ?? 'Spectators',
            'unknown' => __('tracker.team_unknown') ?? 'Players',
        ];
    @endphp

    <div class="space-y-4">
        @foreach(['axis', 'allies', 'spec', 'unknown'] as $teamKey)
            @if($teams[$teamKey]->count() > 0)
                <div class="bg-gray-800 rounded-lg overflow-hidden border-l-4 {{ $teamColors[$teamKey]['border'] }}">
                    <div class="px-6 py-3 {{ $teamColors[$teamKey]['bg'] }} flex items-center justify-between">
                        <h2 class="font-bold {{ $teamColors[$teamKey]['label'] }}">
                            {{ $teamLabels[$teamKey] }}
                            <span class="text-xs text-gray-400 font-normal ml-2">({{ $teams[$teamKey]->count() }})</span>
                        </h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-900 text-gray-400 text-xs uppercase">
                                <tr>
                                    <th class="px-4 py-2 text-left">{{ __('tracker.player') ?? 'Player' }}</th>
                                    <th class="px-4 py-2 text-right">{{ __('tracker.score') ?? 'Score' }}</th>
                                    <th class="px-4 py-2 text-right">K</th>
                                    <th class="px-4 py-2 text-right">D</th>
                                    <th class="px-4 py-2 text-right">K/D</th>
                                    <th class="px-4 py-2 text-right">HS</th>
                                    <th class="px-4 py-2 text-right">{{ __('tracker.assists') ?? 'Assists' }}</th>
                                    <th class="px-4 py-2 text-right">{{ __('tracker.revives') ?? 'Revives' }}</th>
                                    <th class="px-4 py-2 text-right">{{ __('tracker.objectives') ?? 'Obj' }}</th>
                                    <th class="px-4 py-2 text-right">{{ __('tracker.dmg') ?? 'DMG' }}</th>
                                    <th class="px-4 py-2 text-right">{{ __('tracker.acc') ?? 'Acc%' }}</th>
                                    <th class="px-4 py-2 text-right">{{ __('tracker.ping') ?? 'Ping' }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                @foreach($teams[$teamKey] as $p)
                                    <tr class="hover:bg-gray-700/40">
                                        <td class="px-4 py-2">
                                            @if($p->player_id)
                                                <a href="{{ route('tracker.player.show', $p->player_id) }}" class="text-white hover:text-amber-300">
                                                    {!! $p->name_clean_snapshot ? e($p->name_clean_snapshot) : '<em class="text-gray-500">unknown</em>' !!}
                                                </a>
                                            @else
                                                <span class="text-gray-400">{{ $p->name_clean_snapshot ?: 'unknown' }}</span>
                                            @endif
                                            @if($p->country_code)<x-country-flag :code="$p->country_code" />@endif
                                        </td>
                                        <td class="px-4 py-2 text-right text-amber-300 font-medium">{{ $p->score }}</td>
                                        <td class="px-4 py-2 text-right text-green-400">{{ $p->kills }}</td>
                                        <td class="px-4 py-2 text-right text-red-400">{{ $p->deaths }}</td>
                                        <td class="px-4 py-2 text-right text-gray-300">
                                            {{ $p->deaths > 0 ? number_format($p->kills / $p->deaths, 2) : ($p->kills > 0 ? 'inf' : '0.00') }}
                                        </td>
                                        <td class="px-4 py-2 text-right text-gray-400">{{ $p->headshots }}</td>
                                        <td class="px-4 py-2 text-right text-gray-400">{{ $p->kill_assists }}</td>
                                        <td class="px-4 py-2 text-right text-gray-400">{{ $p->revives_given }}</td>
                                        <td class="px-4 py-2 text-right text-gray-400">{{ $p->objectives_taken }}</td>
                                        <td class="px-4 py-2 text-right text-gray-400">{{ number_format($p->damage_given) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-400">{{ number_format((float)$p->accuracy_pct, 1) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-500">{{ $p->ping_avg }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    @if($teams['unknown']->count() > 0 && $teams['axis']->count() === 0 && $teams['allies']->count() === 0)
        <div class="mt-4 bg-gray-800/50 border border-gray-700 rounded p-4 text-sm text-gray-400">
            {{ __('tracker.no_team_data_note') ?? 'No team data available for this match (server may not have reported team assignments).' }}
        </div>
    @endif

</div>
</x-layouts.app>
