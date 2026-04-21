{{-- Combat Overview: Headshot %, Damage Ratio, Team Preference --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

    {{-- Headshot Ratio --}}
    <div class="bg-gray-900 rounded-lg p-5 border border-gray-800 text-center">
        <div class="flex items-center justify-center gap-2 mb-3">
            <svg class="w-3.5 h-3.5 text-red-500" viewBox="0 0 20 20"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="10" cy="10" r="3" fill="currentColor"/></svg>
            <span class="text-xs uppercase tracking-wider text-gray-400">{{ __('Headshot Ratio') }}</span>
        </div>
        @if(($headshotRatio ?? null) !== null)
            <div class="text-3xl font-bold text-red-400">
                {{ $headshotRatio }}<span class="text-xl text-gray-500">%</span>
            </div>
            <div class="mt-3 mx-auto max-w-[160px] h-1.5 bg-gray-800 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-red-600 to-orange-500 rounded-full" style="width: {{ min($headshotRatio, 100) }}%"></div>
            </div>
            <div class="mt-2 text-xs text-gray-500">{{ __('across all weapons') }}</div>
        @else
            <div class="text-2xl font-bold text-gray-600">—</div>
            <div class="mt-2 text-xs text-gray-500">{{ __('No data yet') }}</div>
        @endif
    </div>

    {{-- Damage Ratio --}}
    <div class="bg-gray-900 rounded-lg p-5 border border-gray-800 text-center">
        <div class="flex items-center justify-center gap-2 mb-3">
            <svg class="w-3.5 h-3.5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span class="text-xs uppercase tracking-wider text-gray-400">{{ __('Damage Ratio') }}</span>
        </div>
        @if(($damageRatio ?? null) !== null)
            <div class="text-3xl font-bold {{ $damageRatio >= 1.5 ? 'text-green-400' : ($damageRatio >= 1.0 ? 'text-yellow-400' : 'text-red-400') }}">
                {{ number_format($damageRatio, 2) }}
            </div>
            <div class="mt-2 text-xs text-gray-500">{{ __('given / received') }}</div>
            <div class="mt-3 flex items-center justify-center gap-4 text-xs">
                <span class="text-green-500/80">↑ {{ number_format($damageGiven) }}</span>
                <span class="text-gray-700">·</span>
                <span class="text-red-500/80">↓ {{ number_format($damageReceived) }}</span>
            </div>
        @else
            <div class="text-2xl font-bold text-gray-600">—</div>
            <div class="mt-2 text-xs text-gray-500">{{ __('No data yet') }}</div>
        @endif
    </div>

    {{-- Team Preference --}}
    <div class="bg-gray-900 rounded-lg p-5 border border-gray-800 text-center">
        <div class="flex items-center justify-center gap-2 mb-3">
            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span class="text-xs uppercase tracking-wider text-gray-400">{{ __('Team Preference') }}</span>
        </div>
        @php
            $totalTeam = ($axisMatches ?? 0) + ($alliesMatches ?? 0);
            $axisPct = $totalTeam > 0 ? round($axisMatches / $totalTeam * 100, 1) : 0;
            $alliesPct = $totalTeam > 0 ? round($alliesMatches / $totalTeam * 100, 1) : 0;
        @endphp
        @if($totalTeam > 0)
            <div class="flex items-baseline justify-center gap-3">
                <div>
                    <span class="text-2xl font-bold text-red-400">{{ $axisPct }}<span class="text-base text-gray-500">%</span></span>
                    <div class="text-[10px] uppercase tracking-wider text-red-500/70 mt-0.5">Axis</div>
                </div>
                <span class="text-gray-700 text-lg">·</span>
                <div>
                    <span class="text-2xl font-bold text-blue-400">{{ $alliesPct }}<span class="text-base text-gray-500">%</span></span>
                    <div class="text-[10px] uppercase tracking-wider text-blue-500/70 mt-0.5">Allies</div>
                </div>
            </div>
            <div class="mt-3 mx-auto max-w-[160px] flex h-1.5 rounded-full overflow-hidden bg-gray-800">
                <div class="bg-red-600" style="width: {{ $axisPct }}%"></div>
                <div class="bg-blue-600" style="width: {{ $alliesPct }}%"></div>
            </div>
            <div class="mt-2 text-xs text-gray-500">{{ $totalTeam }} {{ __('matches') }}</div>
        @else
            <div class="text-2xl font-bold text-gray-600">—</div>
            <div class="mt-2 text-xs text-gray-500">{{ __('No data yet') }}</div>
        @endif
    </div>

</div>
