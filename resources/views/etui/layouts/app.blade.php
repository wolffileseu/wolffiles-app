<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ETUI Builder') — Wolffiles</title>
    @vite(['resources/css/app.css', 'resources/css/etui.css'])
    @stack('head')
</head>
<body class="bg-gray-900 text-gray-200 min-h-screen font-sans antialiased">
    <header class="border-b border-gray-800 bg-gray-950">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-6">
            <a href="{{ route('etui.index') }}" class="text-amber-400 font-bold tracking-tight">
                ETUI <span class="text-gray-400 font-normal">Builder</span>
            </a>
            <nav class="flex gap-4 text-sm text-gray-300">
                <a href="{{ route('etui.index') }}" class="hover:text-amber-300">Mods</a>
                @auth
                    <a href="{{ route('etui.edit.new') }}" class="hover:text-amber-300">New Project</a>
                @endauth
            </nav>
            <div class="flex-1"></div>
            <div class="text-sm text-gray-400">
                @auth
                    {{ auth()->user()->name }}
                @else
                    <a href="{{ route('login') }}" class="hover:text-amber-300">Login</a>
                @endauth
            </div>
        </div>
    </header>

    @if (session('status'))
        <div class="bg-emerald-900 text-emerald-200 px-4 py-2 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    <footer class="border-t border-gray-800 mt-12 py-4 text-center text-xs text-gray-500">
        ETUI Builder — Phase 3+4 preview. Renderer is an approximate ET visualisation.
    </footer>

    @stack('scripts')
</body>
</html>
