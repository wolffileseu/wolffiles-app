{{-- Sort Dropdown Component --}}
{{-- Usage: @include('components.sort-dropdown', ['currentSort' => request('sort', 'newest')]) --}}
@php
    $sortOptions = [
        'newest' => __('messages.sort_newest'),
        'oldest' => __('messages.sort_oldest'),
        'downloads' => __('messages.sort_downloads'),
        'rating' => __('messages.sort_rating'),
        'name_asc' => __('messages.sort_name_az'),
        'name_desc' => __('messages.sort_name_za'),
        'size_desc' => __('messages.sort_size_large'),
        'size_asc' => __('messages.sort_size_small'),
    ];
    $current = $currentSort ?? request('sort', 'newest');
@endphp

<div class="relative" x-data="{ open: false }">
    <button @click="open = !open"
            class="flex items-center space-x-2 bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-sm text-gray-300 hover:border-gray-500 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
        </svg>
        <span>{{ $sortOptions[$current] ?? $sortOptions['newest'] }}</span>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>

    <div x-show="open" @click.away="open = false" x-cloak
         x-transition
         class="absolute right-0 mt-2 w-48 bg-gray-800 rounded-lg border border-gray-700 shadow-lg z-30 py-1">
        @foreach($sortOptions as $key => $label)
            <a href="{{ request()->fullUrlWithQuery(['sort' => $key, 'page' => 1]) }}"
               class="block px-4 py-2 text-sm {{ $current === $key ? 'text-amber-400 bg-gray-700/50' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} transition-colors">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>
