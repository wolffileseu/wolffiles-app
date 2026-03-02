{{-- Language Switcher with SVG Flags --}}
@php
    $currentLocale = app()->getLocale();
    $allLanguages = config('languages', []);
    $availableDirs = array_map('basename', glob(lang_path('*'), GLOB_ONLYDIR));
    $available = array_filter($allLanguages, fn($code) => in_array($code, $availableDirs), ARRAY_FILTER_USE_KEY);
    $currentLang = $available[$currentLocale] ?? ['name' => 'English', 'country' => 'gb'];
@endphp

<div class="relative" x-data="{ open: false }" @click.away="open = false">
    <button @click="open = !open" class="flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-gray-700/50 transition-colors">
        <img src="https://flagcdn.com/{{ $currentLang['country'] ?? 'gb' }}.svg"
             alt="{{ $currentLang['name'] }}"
             width="22" height="16"
             class="inline-block rounded-sm shadow-sm"
             style="width: 22px; height: auto;">
        <svg class="w-3 h-3 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-2 w-48 bg-gray-800 border border-gray-700 rounded-xl shadow-xl z-50 py-1 max-h-64 overflow-y-auto">

        @foreach($available as $code => $lang)
        <a href="{{ request()->fullUrlWithQuery(['lang' => $code]) }}"
           class="flex items-center gap-3 px-4 py-2 text-sm transition-colors {{ $code === $currentLocale ? 'bg-amber-600/20 text-amber-400' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
            <img src="https://flagcdn.com/{{ $lang['country'] ?? $code }}.svg"
                 alt="{{ $lang['name'] }}"
                 width="20" height="15"
                 class="inline-block rounded-sm shadow-sm"
                 style="width: 20px; height: auto;">
            <span>{{ $lang['name'] }}</span>
            @if($code === $currentLocale)
                <svg class="w-4 h-4 ml-auto text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
            @endif
        </a>
        @endforeach
    </div>
</div>
