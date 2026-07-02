{{--
    RtCW kill scoreboard partial.
    Expects $rtcwScoreboard : Collection|null  (null/empty => renders nothing)
    Rows: ->player_id, ->kills, ->deaths, ->kd, ->top_weapon, ->top_weapon_kills

    Include from server-show.blade.php, e.g.:
        @include('frontend.tracker.partials.rtcw-scoreboard', ['rtcwScoreboard' => $rtcwScoreboard])
--}}
@if(!empty($rtcwScoreboard) && $rtcwScoreboard->count() > 0)
    <div class="mt-8 rounded-lg border border-zinc-700 bg-zinc-900/60 overflow-hidden">
        <div class="px-4 py-3 border-b border-zinc-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-100">
                RtCW Scoreboard
            </h2>
            <span class="text-xs text-zinc-400">
                Kills aus Obituary-Daten &middot; kein Team / keine Accuracy (RtCW)
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-zinc-400 border-b border-zinc-800">
                        <th class="px-4 py-2 font-medium w-10">#</th>
                        <th class="px-4 py-2 font-medium">Spieler</th>
                        <th class="px-4 py-2 font-medium text-right">Kills</th>
                        <th class="px-4 py-2 font-medium text-right">Deaths</th>
                        <th class="px-4 py-2 font-medium text-right">K/D</th>
                        <th class="px-4 py-2 font-medium">Lieblingswaffe</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rtcwScoreboard as $i => $row)
                        <tr class="border-b border-zinc-800/60 hover:bg-zinc-800/40">
                            <td class="px-4 py-2 text-zinc-500">{{ $i + 1 }}</td>
                            <td class="px-4 py-2">
                                <a href="{{ route('tracker.player.show', $row->player_id) }}"
                                   class="text-zinc-100 hover:text-amber-400 transition-colors">
                                    {{ optional(\App\Models\Tracker\TrackerPlayer::find($row->player_id))->name_clean
                                        ?? ('Player #' . $row->player_id) }}
                                </a>
                            </td>
                            <td class="px-4 py-2 text-right tabular-nums text-emerald-400">{{ $row->kills }}</td>
                            <td class="px-4 py-2 text-right tabular-nums text-rose-400">{{ $row->deaths }}</td>
                            <td class="px-4 py-2 text-right tabular-nums text-zinc-200">{{ number_format($row->kd, 2) }}</td>
                            <td class="px-4 py-2 text-zinc-300">
                                @if($row->top_weapon)
                                    {{ ucfirst(str_replace('_', ' ', $row->top_weapon)) }}
                                    <span class="text-zinc-500 text-xs">({{ $row->top_weapon_kills }})</span>
                                @else
                                    <span class="text-zinc-600">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
