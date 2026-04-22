<x-layouts.app :title="$weapon['name'].' — Weapon Mastery'">
<div class="max-w-7xl mx-auto px-4 py-8">

    {{-- Breadcrumb --}}
    <nav class="text-sm text-gray-500 mb-6 flex items-center gap-2">
        <a href="{{ route('tracker.rankings') }}" class="hover:text-amber-400 transition">Rankings</a>
        <span>/</span>
        <a href="{{ route('tracker.weapons.index') }}" class="hover:text-amber-400 transition">Weapons</a>
        <span>/</span>
        <span class="text-gray-300">{{ $weapon['name'] }}</span>
    </nav>

    {{-- Weapon Header --}}
    <div class="flex flex-col md:flex-row items-center gap-8 mb-10 p-6 md:p-8 rounded-lg bg-gray-800">

        <div class="flex-shrink-0 w-36 h-36 flex items-center justify-center bg-gray-900/60 rounded-lg p-4">
            <img src="{{ asset('img/tracker/weapons/'.$weapon['icon']) }}"
                 alt="{{ $weapon['name'] }}"
                 class="w-full h-full object-contain drop-shadow-[0_0_20px_rgba(245,158,11,0.4)]"
                 style="filter: brightness(0.9) sepia(1) hue-rotate(5deg) saturate(4);">
        </div>

        <div class="flex-1 text-center md:text-left">
            <div class="flex items-center gap-3 justify-center md:justify-start flex-wrap mb-2">
                <h1 class="text-4xl md:text-5xl font-bold text-white">{{ $weapon['name'] }}</h1>
                @if($weapon['side'] !== 'both')
                    <span class="px-2 py-0.5 text-xs uppercase tracking-wider rounded
                                 {{ $weapon['side'] === 'axis' ? 'bg-red-900/40 text-red-300' : 'bg-blue-900/40 text-blue-300' }}">
                        {{ $weapon['side'] }}
                    </span>
                @endif
                <span class="px-2 py-0.5 text-xs uppercase tracking-wider rounded bg-neutral-800 text-gray-400">
                    {{ \App\Services\Tracker\WeaponRegistry::categoryLabel($weapon['category']) }}
                </span>
            </div>
            <p class="text-gray-400 italic text-sm md:text-base mb-5 max-w-2xl">"{{ $weapon['lore'] }}"</p>

            {{-- Global totals --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 max-w-2xl">
                <div class="bg-gray-900/60 rounded-lg px-4 py-3">
                    <div class="text-2xl font-bold text-amber-500">{{ number_format($totals->kills ?? 0) }}</div>
                    <div class="text-[10px] uppercase tracking-widest text-gray-500">Total Kills</div>
                </div>
                <div class="bg-gray-900/60 rounded-lg px-4 py-3">
                    <div class="text-2xl font-bold text-red-400">{{ number_format($totals->headshots ?? 0) }}</div>
                    <div class="text-[10px] uppercase tracking-widest text-gray-500">Headshots</div>
                </div>
                <div class="bg-gray-900/60 rounded-lg px-4 py-3">
                    <div class="text-2xl font-bold text-emerald-400">
                        {{ ($totals->atts ?? 0) > 0 ? round(($totals->hits / $totals->atts) * 100, 1) : 0 }}%
                    </div>
                    <div class="text-[10px] uppercase tracking-widest text-gray-500">Global Accuracy</div>
                </div>
                <div class="bg-gray-900/60 rounded-lg px-4 py-3">
                    <div class="text-2xl font-bold text-white">{{ number_format($totals->players ?? 0) }}</div>
                    <div class="text-[10px] uppercase tracking-widest text-gray-500">Players</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Single-Match Record (if exists) --}}
    @if($matchRecord && $matchRecord->kills > 0)
    <div class="mb-10 rounded-lg bg-gray-800 p-5 flex flex-wrap items-center gap-4">
        <div class="text-2xl">🏆</div>
        <div class="flex-1 min-w-[200px]">
            <div class="text-xs uppercase tracking-widest text-amber-500 mb-1">Single-Match Record</div>
            <div class="text-white">
                <a href="{{ route('tracker.player.show', $matchRecord->player_id) }}" class="font-semibold hover:text-amber-400 transition">
                    {!! $matchRecord->name_html ?: e($matchRecord->name_clean) !!}
                </a>
                <span class="text-gray-400">on</span>
                <span class="font-mono text-gray-300">{{ $matchRecord->map_name }}</span>
            </div>
        </div>
        <div class="flex gap-4 text-sm">
            <div class="text-center">
                <div class="text-2xl font-bold text-amber-500">{{ $matchRecord->kills }}</div>
                <div class="text-[10px] uppercase text-gray-500">Kills</div>
            </div>
            @if($matchRecord->atts > 0)
            <div class="text-center">
                <div class="text-2xl font-bold text-emerald-400">{{ round(($matchRecord->hits / $matchRecord->atts) * 100, 1) }}%</div>
                <div class="text-[10px] uppercase text-gray-500">Accuracy</div>
            </div>
            @endif
            @if($matchRecord->headshots > 0)
            <div class="text-center">
                <div class="text-2xl font-bold text-red-400">{{ $matchRecord->headshots }}</div>
                <div class="text-[10px] uppercase text-gray-500">HS</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- 3 Leaderboards side by side --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Top by Kills --}}
        <div class="bg-gray-800 rounded-lg overflow-hidden">
            <div class="px-5 py-4 bg-gray-900/60 border-b border-gray-700 flex items-center justify-between">
                <h2 class="text-amber-500 font-semibold uppercase tracking-wider text-sm">◆ Most Kills</h2>
                <span class="text-xs text-gray-500">Top {{ min(50, $byKills->count()) }}</span>
            </div>
            @if($byKills->isEmpty())
                <div class="p-8 text-center text-gray-500 text-sm">No kills recorded yet.</div>
            @else
                <div class="divide-y divide-gray-700/50 max-h-[600px] overflow-y-auto">
                    @foreach($byKills as $i => $row)
                        <a href="{{ route('tracker.player.show', $row->player_id) }}"
                           class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-700/50 transition group">
                            <div class="w-7 text-right font-mono text-xs
                                        {{ $i === 0 ? 'text-amber-400 font-bold' : ($i < 3 ? 'text-amber-600' : 'text-gray-600') }}">
                                #{{ $i + 1 }}
                            </div>
                            <div class="flex-1 min-w-0 truncate text-sm group-hover:text-amber-400 transition">
                                {!! $row->name_html ?: e($row->name_clean) !!}
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="font-mono text-amber-500 font-semibold">{{ number_format($row->total_kills) }}</div>
                                @if($row->total_atts > 0)
                                <div class="text-[10px] text-gray-600">{{ round($row->accuracy_bp / 100, 1) }}% acc</div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Top by Accuracy --}}
        <div class="bg-gray-800 rounded-lg overflow-hidden">
            <div class="px-5 py-4 bg-gray-900/60 border-b border-gray-700 flex items-center justify-between">
                <h2 class="text-emerald-400 font-semibold uppercase tracking-wider text-sm">◆ Most Accurate</h2>
                <span class="text-xs text-gray-500">Min {{ 50 }} shots</span>
            </div>
            @if($byAccuracy->isEmpty())
                <div class="p-8 text-center text-gray-500 text-sm">
                    Not enough shots yet.<br>
                    <span class="text-xs">Players need 50+ attempts to qualify.</span>
                </div>
            @else
                <div class="divide-y divide-gray-700/50 max-h-[600px] overflow-y-auto">
                    @foreach($byAccuracy as $i => $row)
                        <a href="{{ route('tracker.player.show', $row->player_id) }}"
                           class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-700/50 transition group">
                            <div class="w-7 text-right font-mono text-xs
                                        {{ $i === 0 ? 'text-emerald-400 font-bold' : ($i < 3 ? 'text-emerald-600' : 'text-gray-600') }}">
                                #{{ $i + 1 }}
                            </div>
                            <div class="flex-1 min-w-0 truncate text-sm group-hover:text-emerald-400 transition">
                                {!! $row->name_html ?: e($row->name_clean) !!}
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="font-mono text-emerald-400 font-semibold">{{ round($row->accuracy_bp / 100, 1) }}%</div>
                                <div class="text-[10px] text-gray-600">{{ number_format($row->total_hits) }}/{{ number_format($row->total_atts) }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Top by Headshots --}}
        <div class="bg-gray-800 rounded-lg overflow-hidden">
            <div class="px-5 py-4 bg-gray-900/60 border-b border-gray-700 flex items-center justify-between">
                <h2 class="text-red-400 font-semibold uppercase tracking-wider text-sm">◆ Headshot Kings</h2>
                <span class="text-xs text-gray-500">Top {{ min(20, $byHeadshots->count()) }}</span>
            </div>
            @if($byHeadshots->isEmpty())
                <div class="p-8 text-center text-gray-500 text-sm">No headshots recorded yet.</div>
            @else
                <div class="divide-y divide-gray-700/50 max-h-[600px] overflow-y-auto">
                    @foreach($byHeadshots as $i => $row)
                        <a href="{{ route('tracker.player.show', $row->player_id) }}"
                           class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-700/50 transition group">
                            <div class="w-7 text-right font-mono text-xs
                                        {{ $i === 0 ? 'text-red-400 font-bold' : ($i < 3 ? 'text-red-600' : 'text-gray-600') }}">
                                #{{ $i + 1 }}
                            </div>
                            <div class="flex-1 min-w-0 truncate text-sm group-hover:text-red-400 transition">
                                {!! $row->name_html ?: e($row->name_clean) !!}
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="font-mono text-red-400 font-semibold">{{ number_format($row->total_headshots) }}</div>
                                @if($row->total_kills > 0)
                                <div class="text-[10px] text-gray-600">{{ round(($row->total_headshots / $row->total_kills) * 100, 1) }}% hs-ratio</div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

</div>
</x-layouts.app>
