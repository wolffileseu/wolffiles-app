{{-- Skill Progression: XP per class skill over time --}}
@if(!empty($skillProgression ?? []))
    @php
        $skillNames = [
            0 => 'Battle Sense', 1 => 'Engineering', 2 => 'Medic',
            3 => 'Signals', 4 => 'Light Weapons', 5 => 'Heavy Weapons',
            6 => 'Covert Ops',
        ];
        $skillColors = [
            0 => '#f59e0b', 1 => '#10b981', 2 => '#ef4444', 3 => '#3b82f6',
            4 => '#8b5cf6', 5 => '#f97316', 6 => '#06b6d4',
        ];
        $renderable = collect($skillProgression)->filter(fn($p) => count($p) >= 2);
    @endphp
    @if($renderable->isNotEmpty())
        <div class="bg-gray-900 rounded-lg p-5 border border-gray-800 mb-6">
            <div class="flex items-center justify-center gap-2 mb-4">
                <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                <span class="text-xs uppercase tracking-wider text-gray-400">{{ __('Skill Progression') }}</span>
            </div>
            <div class="flex flex-wrap justify-center gap-3">
                @foreach($renderable as $sid => $points)
                    @php
                        $xps = array_column($points, 'xp');
                        $minV = min($xps); $maxV = max($xps);
                        $range = max(1, $maxV - $minV);
                        $w = 100; $h = 28;
                        $step = count($xps) > 1 ? $w / (count($xps) - 1) : 0;
                        $pts = [];
                        foreach ($xps as $i => $xp) {
                            $x = round($i * $step, 1);
                            $y = round($h - (($xp - $minV) / $range) * $h, 1);
                            $pts[] = "$x,$y";
                        }
                        $path = 'M' . implode(' L', $pts);
                        $current = end($xps);
                        $delta = $current - reset($xps);
                        $color = $skillColors[$sid] ?? '#9ca3af';
                    @endphp
                    <div class="bg-gray-950/60 rounded-lg p-3 border border-gray-800/50 text-center w-32">
                        <div class="text-xs text-gray-400 truncate font-medium mb-1" title="{{ $skillNames[$sid] ?? "Skill $sid" }}">
                            {{ $skillNames[$sid] ?? "Skill $sid" }}
                        </div>
                        <svg viewBox="0 0 100 28" preserveAspectRatio="none" class="w-full h-7">
                            <path d="{{ $path }}" fill="none" stroke="{{ $color }}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <div class="mt-1 flex items-baseline justify-center gap-1.5">
                            <span class="text-lg font-bold text-white">{{ number_format($current) }}</span>
                            @if($delta > 0)
                                <span class="text-xs text-green-400">+{{ number_format($delta) }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 text-center text-xs text-gray-500">{{ __('XP progression over recent matches') }}</div>
        </div>
    @endif
@endif
