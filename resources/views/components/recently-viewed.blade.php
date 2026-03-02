{{-- Recently Viewed Files --}}
{{-- Usage: @include('components.recently-viewed') --}}
@php
    $recentlyViewed = \App\Http\Middleware\TrackRecentlyViewed::getRecentFiles(5);
@endphp

@if($recentlyViewed->isNotEmpty())
<div class="bg-gray-800 rounded-lg border border-gray-700 p-5">
    <h3 class="text-sm font-semibold text-amber-400 uppercase tracking-wider mb-4 flex items-center space-x-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>{{ __('messages.recently_viewed') }}</span>
    </h3>
    <div class="space-y-3">
        @foreach($recentlyViewed as $file)
            <a href="{{ route('files.show', $file) }}" class="flex items-center space-x-3 hover:bg-gray-700/50 rounded p-2 -mx-2 transition-colors">
                <div class="w-12 h-8 bg-gray-700 rounded overflow-hidden flex-shrink-0">
                    @if($file->screenshots->isNotEmpty())
                        <img src="{{ $file->screenshots->first()->thumbnail_url ?? $file->screenshots->first()->url }}"
                             class="w-full h-full object-cover" loading="lazy">
                    @endif
                </div>
                <span class="text-sm text-gray-300 truncate">{{ $file->title }}</span>
            </a>
        @endforeach
    </div>
</div>
@endif
