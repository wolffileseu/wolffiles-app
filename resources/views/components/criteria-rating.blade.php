{{-- Multi-Criteria Rating Component --}}
{{-- Usage: @include('components.criteria-rating', ['file' => $file]) --}}
@php
    $criteria = \App\Models\RatingCriteria::where('is_active', true)->orderBy('sort_order')->get();
    $userRatings = [];
    if (auth()->check() && $criteria->isNotEmpty()) {
        $userRatings = \Illuminate\Support\Facades\DB::table('file_criteria_ratings')
            ->where('file_id', $file->id)
            ->where('user_id', auth()->id())
            ->pluck('score', 'rating_criteria_id')
            ->toArray();
    }
    $avgRatings = [];
    $ratingCounts = [];
    if ($criteria->isNotEmpty()) {
        $stats = \Illuminate\Support\Facades\DB::table('file_criteria_ratings')
            ->where('file_id', $file->id)
            ->select('rating_criteria_id', \Illuminate\Support\Facades\DB::raw('AVG(score) as avg_score'), \Illuminate\Support\Facades\DB::raw('COUNT(*) as cnt'))
            ->groupBy('rating_criteria_id')
            ->get();
        foreach ($stats as $s) {
            $avgRatings[$s->rating_criteria_id] = $s->avg_score;
            $ratingCounts[$s->rating_criteria_id] = $s->cnt;
        }
    }
@endphp

@if($criteria->isNotEmpty())
<div class="bg-gray-800 rounded-lg border border-gray-700 p-5"
     x-data="{
        ratings: {{ json_encode(array_map('intval', $userRatings)) }},
        hovers: {},
        async rate(criterionId, score) {
            this.ratings[criterionId] = score;
            try {
                const res = await fetch('{{ route("files.rateCriterion", $file) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ criteria_id: criterionId, score: score })
                });
                if (res.ok) {
                    setTimeout(() => location.reload(), 500);
                }
            } catch(e) { console.error(e); }
        }
     }">
    <h3 class="text-sm font-semibold text-amber-400 uppercase tracking-wider mb-4">{{ __('messages.detailed_rating') }}</h3>

    <div class="space-y-3">
        @foreach($criteria as $criterion)
            @php
                $avg = $avgRatings[$criterion->id] ?? 0;
                $count = $ratingCounts[$criterion->id] ?? 0;
            @endphp
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-gray-300">{{ $criterion->name_translations[app()->getLocale()] ?? $criterion->name }}</span>
                    <span class="text-xs text-gray-500">{{ number_format($avg, 1) }}/5 ({{ $count }})</span>
                </div>
                <div class="flex items-center space-x-2">
                    {{-- Average bar --}}
                    <div class="flex-1 bg-gray-700 rounded-full h-2">
                        <div class="bg-amber-500 rounded-full h-2 transition-all" style="width: {{ ($avg / 5) * 100 }}%"></div>
                    </div>
                    {{-- User rating stars --}}
                    @auth
                    <div class="flex items-center space-x-0.5">
                        @for($s = 1; $s <= 5; $s++)
                            <button type="button"
                                @mouseenter="hovers[{{ $criterion->id }}] = {{ $s }}"
                                @mouseleave="hovers[{{ $criterion->id }}] = 0"
                                @click="rate({{ $criterion->id }}, {{ $s }})"
                                class="text-sm cursor-pointer transition-colors"
                                :class="(hovers[{{ $criterion->id }}] || 0) >= {{ $s }}
                                    ? 'text-amber-300'
                                    : ((ratings[{{ $criterion->id }}] || 0) >= {{ $s }} ? 'text-amber-400' : 'text-gray-600')">
                                ★
                            </button>
                        @endfor
                    </div>
                    @endauth
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif
