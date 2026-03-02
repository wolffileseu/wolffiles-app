{{-- Trending Files --}}
{{-- Usage: @include('components.trending') --}}
@php
    $trending = \App\Services\StatisticsService::getTrending(8);
@endphp

@if($trending->isNotEmpty())
<section class="mb-12">
    <div class="flex items-center space-x-2 mb-6">
        <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17.66 11.2C17.43 10.9 17.16 10.64 16.85 10.43C16.52 10.2 16.17 10.01 15.82 9.88C15.38 10.62 14.76 11.26 14 11.72C13.16 12.22 12.2 12.5 11.2 12.5C10.5 12.5 9.81 12.35 9.18 12.07C8.55 11.79 8 11.39 7.54 10.89C7.09 10.39 6.74 9.8 6.52 9.15C6.3 8.5 6.21 7.81 6.26 7.13C5.35 8.09 4.61 9.22 4.09 10.45C3.56 11.68 3.26 13 3.2 14.33C3.14 15.67 3.31 17 3.72 18.27C4.12 19.53 4.74 20.69 5.55 21.71H18.44C19.25 20.69 19.87 19.53 20.27 18.27C20.68 17 20.85 15.67 20.79 14.33C20.73 13 20.43 11.68 19.91 10.45C19.55 9.6 19.08 8.81 18.5 8.11C18.27 8.89 17.91 9.63 17.44 10.28C17.48 10.45 17.53 10.62 17.58 10.79L17.66 11.2Z"/>
        </svg>
        <h2 class="text-2xl font-bold text-white">{{ __('messages.trending') }}</h2>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        @foreach($trending as $i => $file)
            <a href="{{ route('files.show', $file) }}"
               class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden hover:border-amber-500/50 transition-colors group relative">
                {{-- Rank badge --}}
                <div class="absolute top-2 left-2 z-10 bg-red-600 text-white text-xs font-bold w-6 h-6 rounded-full flex items-center justify-center">
                    {{ $i + 1 }}
                </div>
                {{-- Thumbnail --}}
                <div class="aspect-video bg-gray-700 overflow-hidden">
                    @if($file->screenshots->isNotEmpty())
                        <img src="{{ $file->screenshots->first()->thumbnail_url ?? $file->screenshots->first()->url }}"
                             alt="{{ $file->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    @endif
                </div>
                <div class="p-3">
                    <h3 class="text-sm font-medium text-gray-200 group-hover:text-amber-400 truncate">{{ $file->title }}</h3>
                    <div class="flex items-center justify-between mt-1 text-xs text-gray-500">
                        <span>{{ $file->category?->name }}</span>
                        <span>↓ {{ number_format($file->download_count) }}</span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</section>
@endif
