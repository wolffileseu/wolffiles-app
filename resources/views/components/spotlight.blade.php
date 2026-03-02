{{-- Map of the Week / Spotlight --}}
{{-- Include on homepage: @include('components.spotlight') --}}
@php
    // Featured file with label, or most downloaded this week
    $spotlight = \App\Models\File::where('status', 'approved')
        ->where('is_featured', true)
        ->orderByDesc('featured_at')
        ->first();

    if (!$spotlight) {
        $spotlight = \App\Models\File::where('status', 'approved')
            ->where('created_at', '>=', now()->subWeek())
            ->orderByDesc('download_count')
            ->first();
    }
@endphp

@if($spotlight)
<div class="bg-gradient-to-r from-amber-900/30 to-gray-800 rounded-lg border border-amber-600/30 p-6 mb-8">
    <div class="flex items-center space-x-2 mb-4">
        <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
        </svg>
        <h3 class="text-lg font-bold text-amber-400">{{ __('messages.map_of_the_week') }}</h3>
    </div>
    <div class="flex flex-col sm:flex-row gap-4">
        @if($spotlight->screenshots->isNotEmpty())
            <a href="{{ route('files.show', $spotlight) }}" class="flex-shrink-0">
                <img src="{{ $spotlight->screenshots->first()->thumbnail_url ?? $spotlight->screenshots->first()->url }}"
                     alt="{{ $spotlight->title }}"
                     class="w-full sm:w-48 h-32 object-cover rounded-lg border border-gray-700 hover:border-amber-500 transition-colors"
                     loading="lazy">
            </a>
        @endif
        <div class="flex-1">
            <a href="{{ route('files.show', $spotlight) }}" class="text-xl font-semibold text-white hover:text-amber-400 transition-colors">
                {{ $spotlight->title }}
            </a>
            @if($spotlight->featured_label)
                <span class="ml-2 text-xs bg-amber-600 text-white px-2 py-0.5 rounded">{{ $spotlight->featured_label }}</span>
            @endif
            <p class="text-gray-400 text-sm mt-2 break-words" style="overflow-wrap: anywhere;">{{ \Illuminate\Support\Str::limit(strip_tags($spotlight->description ?? ''), 150) }}</p>
            <div class="flex items-center space-x-4 mt-3 text-sm text-gray-500">
                <span>{{ $spotlight->category?->name }}</span>
                <span>↓ {{ number_format($spotlight->download_count) }}</span>
                <span>★ {{ number_format($spotlight->average_rating, 1) }}</span>
            </div>
        </div>
    </div>
</div>
@endif
