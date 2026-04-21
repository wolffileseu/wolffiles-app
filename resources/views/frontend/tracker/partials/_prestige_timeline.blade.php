{{-- Prestige Timeline: level-up milestones --}}
@if(!empty($prestigeMilestones ?? []))
    <div class="bg-gray-900 rounded-lg p-5 border border-amber-900/30 mb-6">
        <div class="flex items-center justify-center gap-2 mb-4">
            <svg class="w-3.5 h-3.5 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            <span class="text-xs uppercase tracking-wider text-amber-500">{{ __('Prestige Timeline') }}</span>
            <span class="text-xs text-gray-500">· {{ count($prestigeMilestones) }} {{ __('level-ups') }}</span>
        </div>
        <div class="relative py-4 px-4">
            <div class="absolute top-1/2 left-8 right-8 h-0.5 bg-gradient-to-r from-amber-600/40 via-amber-500 to-amber-600/40 -translate-y-1/2"></div>
            <div class="flex justify-between items-center relative">
                @foreach($prestigeMilestones as $ms)
                    <div class="flex flex-col items-center">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 border-2 border-amber-300 shadow-lg shadow-amber-500/40 flex items-center justify-center relative z-10">
                            <span class="text-sm font-bold text-black">{{ $ms['level'] }}</span>
                        </div>
                        <span class="mt-2 text-[10px] text-gray-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($ms['date'])->format('d.m.y') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
