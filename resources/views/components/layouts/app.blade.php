<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Wolffiles.eu' }} - {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/favicon.ico') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- #30 Dynamic SEO Meta Tags --}}
    @include('components.seo-meta', ['seo' => $seo ?? []])

    {{-- JSON-LD Structured Data --}}
    @include('components.json-ld', $jsonLd ?? ['type' => 'website'])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/easter-egg.css') }}">
    {{-- Performance: Preconnect to S3 for faster image loading --}}
    <link rel="preconnect" href="https://wolffiles.fsn1.your-objectstorage.com" crossorigin>
    <link rel="dns-prefetch" href="https://wolffiles.fsn1.your-objectstorage.com">
    
    {{-- Preload critical font if used --}}
    <meta name="theme-color" content="#1f2937">
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex flex-col">

    {{-- Load header menu --}}
    @php
        $headerMenu = \App\Models\Menu::where('location', 'header')->with(['items' => function($q) {
            $q->whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->with(['children' => function($q2) {
                $q2->where('is_active', true)->orderBy('sort_order');
            }]);
        }])->first();
    @endphp

    {{-- Navigation --}}
    <nav class="bg-gray-800 border-b border-gray-700 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-8">
                    <a href="{{ route('home') }}" class="flex-shrink-0">
                        <img src="{{ asset('images/wolffiles_logo.png') }}" alt="Wolffiles.eu" class="h-10" width="200" height="40">
                    </a>
                    <div class="hidden md:flex space-x-1">
                        @if($headerMenu)
                            @foreach($headerMenu->items as $item)
                                @if($item->children->isNotEmpty())
                                    <div class="relative" x-data="{ dropOpen: false }" @mouseenter="dropOpen = true" @mouseleave="dropOpen = false">
                                        <a href="{{ $item->resolved_url }}"
                                           class="text-gray-300 hover:text-amber-400 px-3 py-2 text-sm font-medium transition-colors flex items-center space-x-1">
                                            <span>{{ $item->title_translations[app()->getLocale()] ?? $item->title }}</span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </a>
                                        <div x-show="dropOpen" x-cloak
                                             x-transition:enter="transition ease-out duration-150"
                                             x-transition:enter-start="opacity-0 -translate-y-1"
                                             x-transition:enter-end="opacity-100 translate-y-0"
                                             x-transition:leave="transition ease-in duration-100"
                                             x-transition:leave-start="opacity-100"
                                             x-transition:leave-end="opacity-0"
                                             class="absolute left-0 mt-0 w-48 bg-gray-800 rounded-md shadow-lg border border-gray-700 py-1 z-50">
                                            @foreach($item->children as $child)
                                                <a href="{{ $child->resolved_url }}"
                                                   target="{{ $child->target ?? '_self' }}"
                                                   class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-amber-400 transition-colors">
                                                    {{ $child->title_translations[app()->getLocale()] ?? $child->title }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <a href="{{ $item->resolved_url }}"
                                       target="{{ $item->target ?? '_self' }}"
                                       class="text-gray-300 hover:text-amber-400 px-3 py-2 text-sm font-medium transition-colors">
                                        {{ $item->title_translations[app()->getLocale()] ?? $item->title }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <form action="{{ route('files.index') }}" method="GET" class="hidden sm:block">
                        <input type="text" name="search" placeholder="{{ __('messages.search') }}..."
                               value="{{ request('search') }}"
                               class="bg-gray-700 border-gray-600 text-gray-100 rounded-lg px-4 py-2 text-sm focus:ring-amber-500 focus:border-amber-500 w-48 lg:w-64">
                    </form>

                    @include('components.locale-switcher')

                    {{-- #21 Theme Toggle --}}
                    @include('components.theme-toggle')

                    {{-- #19 Notification Bell --}}
                    @include('components.notification-bell')

                    @auth
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="text-gray-300 hover:text-white flex items-center space-x-2">
                                <img src="{{ auth()->user()->avatar_url }}" class="w-8 h-8 rounded-full" width="32" height="32" loading="lazy">
                                <span class="text-sm hidden sm:inline">{{ auth()->user()->name }}</span>
                            </button>
                            <div x-show="open" @click.away="open = false" x-cloak
                                 class="absolute right-0 mt-2 w-48 bg-gray-800 rounded-md shadow-lg border border-gray-700 py-1">
                                <a href="{{ route('profile.show', auth()->user()) }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">{{ __('messages.profile') }}</a>
                                <a href="{{ route('profile.uploads') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">{{ __('messages.my_uploads') }}</a>
                                <a href="{{ route('profile.favorites') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">{{ __('messages.my_favorites') }}</a>
                                <a href="{{ route('profile.settings') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">{{ __('messages.settings') }}</a>
                                <hr class="border-gray-700 my-1">
                                <a href="{{ route('files.upload') }}" class="block px-4 py-2 text-sm text-amber-400 hover:bg-gray-700">{{ __('messages.upload_file') }}</a>
                                @if(auth()->user()->hasRole("clan_leader") || auth()->user()->hasRole("admin"))
                                    <a href="{{ route('clan.fastdl') }}" class="block px-4 py-2 text-sm text-amber-400 hover:bg-gray-700">🖥️ My Fast Download</a>
                                @endif
                                @if(auth()->user()->isModerator())
                                    <a href="/admin" class="block px-4 py-2 text-sm text-amber-400 hover:bg-gray-700">Admin Panel</a>
                                @endif
                                <hr class="border-gray-700 my-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">{{ __('messages.logout') }}</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-300 hover:text-white text-sm">{{ __('messages.login') }}</a>
                        <a href="{{ route('register') }}" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">{{ __('messages.register') }}</a>
                    @endauth
                </div>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div class="border-t border-gray-700"
             x-data="{ mobileOpen: false, isMobile: window.innerWidth < 768 }"
             x-init="window.addEventListener('resize', () => { isMobile = window.innerWidth < 768 })"
             x-show="isMobile" x-cloak>
            <button @click="mobileOpen = !mobileOpen" class="w-full px-4 py-2 text-gray-400 text-sm flex items-center justify-between">
                <span>Menu</span>
                <svg class="w-4 h-4 transition-transform" :class="mobileOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="mobileOpen" x-cloak class="px-4 pb-3 space-y-1">
                <form action="{{ route('files.index') }}" method="GET" class="mb-2">
                    <input type="text" name="search" placeholder="{{ __('messages.search') }}..."
                           class="w-full bg-gray-700 border-gray-600 text-gray-100 rounded-lg px-4 py-2 text-sm">
                </form>
                @if($headerMenu)
                    @foreach($headerMenu->items as $item)
                        <a href="{{ $item->resolved_url }}" target="{{ $item->target ?? '_self' }}"
                           class="block text-gray-300 hover:text-amber-400 py-2 text-sm">
                            {{ $item->title_translations[app()->getLocale()] ?? $item->title }}
                        </a>
                        @foreach($item->children as $child)
                            <a href="{{ $child->resolved_url }}" target="{{ $child->target ?? '_self' }}"
                               class="block text-gray-400 hover:text-amber-400 py-1.5 text-sm pl-4">
                                ↳ {{ $child->title_translations[app()->getLocale()] ?? $child->title }}
                            </a>
                        @endforeach
                    @endforeach
                @endif
            </div>
        </div>
    </nav>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="bg-green-800 border border-green-600 text-green-100 px-4 py-3 text-center" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-800 border border-red-600 text-red-100 px-4 py-3 text-center" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
            {{ session('error') }}
        </div>
    @endif

    {{-- Content --}}
    <main class="flex-grow">
        {{ $slot }}
    </main>

    {{-- Social Media Sidebar --}}
    @include('components.social-sidebar')

    {{-- Footer --}}
    <footer class="bg-gray-800 border-t border-gray-700 mt-12">
        <div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
            @include('components.partner-sidebar')

            <div class="flex flex-col md:flex-row justify-between items-center mt-6 pt-6 border-t border-gray-700">
                <div class="text-gray-400 text-sm">
                    &copy; {{ date('Y') }} Wolffiles.eu &mdash; {{ __('messages.footer_text') }}
                </div>
                <div class="flex flex-wrap justify-center gap-4 mt-4 md:mt-0">
                    @php $footerMenu = \App\Models\Menu::where('location', 'footer')->with(['items' => function($q) { $q->where('is_active', true)->orderBy('sort_order'); }])->first(); @endphp
                    @if($footerMenu)
                        @foreach($footerMenu->items as $item)
                            <a href="{{ $item->resolved_url }}" target="{{ $item->target ?? '_self' }}" class="text-gray-400 hover:text-gray-300 text-sm">
                                {{ $item->title_translations[app()->getLocale()] ?? $item->title }}
                            </a>
                        @endforeach
                    @endif
                </div>
            </div>

            {{-- Online Counter --}}
            <div class="text-center mt-4 text-gray-500 text-xs">
                @php
                    $onlineTotal = \Illuminate\Support\Facades\Cache::get('online_count', 0);
                    $onlineUsers = \App\Models\User::where('last_activity_at', '>=', now()->subMinutes(15))->count();
                    $onlineGuests = max(0, $onlineTotal - $onlineUsers);
                    $totalVisitors = \Illuminate\Support\Facades\DB::table('site_stats')->where('key', 'total_visitors')->value('value') ?? 0;
                    $totalPageviews = \Illuminate\Support\Facades\DB::table('site_stats')->where('key', 'total_pageviews')->value('value') ?? 0;
                @endphp
                👥 {{ $onlineTotal }} online ({{ $onlineUsers }} {{ trans_choice('messages.users_online', $onlineUsers) }}, {{ $onlineGuests }} Guests)
                · 👤 {{ number_format($totalVisitors) }} total visitors · 📄 {{ number_format($totalPageviews) }} pageviews
            </div>

            {{-- ET Quote --}}
            <div class="text-center mt-2 text-gray-600 text-xs italic">
                @php
                    $etQuotes = [
                        '"Enemy weakened!" — Announcer',
                        '"Dynamite planted!" — Engineer',
                        '"I\'m a medic!" — Medic',
                        '"Fire in the hole!" — Engineer',
                        '"Objective reached!" — Announcer',
                        '"We\'ve secured the objective!" — Announcer',
                        '"Need a medic!" — Soldier',
                        '"Defend our objective!" — Commander',
                        '"We need an engineer!" — Commander',
                        '"You have been revived." — Medic',
                        '"Command acknowledged!" — Soldier',
                        '"Mines cleared!" — Engineer',
                        '"Hold your positions!" — Commander',
                        '"Great shot!" — Spotter',
                    ];
                @endphp
                {{ $etQuotes[array_rand($etQuotes)] }}
            </div>
        </div>
    </footer>

    {{-- Cookie Consent --}}
    @include('components.cookie-consent')

    {{-- Easter Egg --}}
    <script src="{{ asset('js/easter-egg.js') }}" defer></script>

    {{-- Scripts --}}
    @stack('scripts')
    @include('components.heatmap-tracker')
</body>
</html>
