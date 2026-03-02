<x-layouts.app title="Tutorials">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-white">🎓 Tutorials</h1>
            @auth
                <a href="{{ route('tutorials.create') }}" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    + Tutorial erstellen
                </a>
            @endauth
        </div>

        {{-- Search & Filters --}}
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-4 mb-8">
            <form action="{{ route('tutorials.index') }}" method="GET" class="flex flex-wrap gap-4">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Tutorials durchsuchen..."
                       class="flex-1 min-w-[200px] bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                <select name="category" class="bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                    <option value="">Alle Kategorien</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->slug }}" {{ request('category') == $cat->slug ? 'selected' : '' }}>
                            {{ $cat->name }} ({{ $cat->published_tutorials_count }})
                        </option>
                    @endforeach
                </select>
                <select name="difficulty" class="bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                    <option value="">Alle Schwierigkeiten</option>
                    <option value="beginner" {{ request('difficulty') == 'beginner' ? 'selected' : '' }}>🟢 Anfänger</option>
                    <option value="intermediate" {{ request('difficulty') == 'intermediate' ? 'selected' : '' }}>🟡 Fortgeschritten</option>
                    <option value="advanced" {{ request('difficulty') == 'advanced' ? 'selected' : '' }}>🔴 Experte</option>
                </select>
                <select name="sort" class="bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                    <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Neueste</option>
                    <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>Beliebteste</option>
                    <option value="helpful" {{ request('sort') == 'helpful' ? 'selected' : '' }}>Am hilfreichsten</option>
                </select>
                <button class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg text-sm font-medium">Suchen</button>
            </form>
        </div>

        {{-- Tutorial Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($tutorials as $tutorial)
                <article class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden hover:border-gray-600 transition-colors flex flex-col">
                    {{-- Video Thumbnail --}}
                    @if($tutorial->youtube_url)
                        @php
                            preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|v\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $tutorial->youtube_url, $matches);
                            $ytId = $matches[1] ?? null;
                        @endphp
                        @if($ytId)
                            <div class="relative">
                                <img src="https://img.youtube.com/vi/{{ $ytId }}/mqdefault.jpg" alt="{{ $tutorial->title }}" class="w-full h-40 object-cover">
                                <div class="absolute inset-0 flex items-center justify-center bg-black/40">
                                    <svg class="w-12 h-12 text-white opacity-80" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                </div>
                            </div>
                        @endif
                    @endif

                    <div class="p-5 flex-1 flex flex-col">
                        {{-- Badges --}}
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-xs px-2 py-0.5 rounded font-medium
                                {{ $tutorial->difficulty === 'beginner' ? 'bg-green-900/50 text-green-400' : '' }}
                                {{ $tutorial->difficulty === 'intermediate' ? 'bg-amber-900/50 text-amber-400' : '' }}
                                {{ $tutorial->difficulty === 'advanced' ? 'bg-red-900/50 text-red-400' : '' }}">
                                {{ $tutorial->difficulty === 'beginner' ? '🟢 Anfänger' : ($tutorial->difficulty === 'intermediate' ? '🟡 Fortgeschritten' : '🔴 Experte') }}
                            </span>
                            @if($tutorial->category)
                                <span class="text-xs px-2 py-0.5 rounded bg-gray-700 text-gray-400">{{ $tutorial->category->name }}</span>
                            @endif
                        </div>

                        <h2 class="text-lg font-semibold text-white mb-2">
                            <a href="{{ route('tutorials.show', $tutorial->slug) }}" class="hover:text-amber-400">{{ $tutorial->title }}</a>
                        </h2>

                        <p class="text-gray-400 text-sm mb-4 flex-1">{{ Str::limit(strip_tags($tutorial->excerpt ?? $tutorial->content), 120) }}</p>

                        {{-- Meta --}}
                        <div class="flex items-center justify-between text-xs text-gray-500 mt-auto pt-3 border-t border-gray-700">
                            <div class="flex items-center gap-3">
                                <span>{{ $tutorial->user?->name }}</span>
                                @if($tutorial->estimated_minutes)
                                    <span>⏱ {{ $tutorial->estimated_minutes }} min</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-3">
                                <span>👁 {{ $tutorial->view_count }}</span>
                                @if($tutorial->helpful_count + $tutorial->not_helpful_count > 0)
                                    <span>👍 {{ $tutorial->helpful_percentage }}%</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-3 text-center py-12 text-gray-500">
                    <p class="text-lg mb-2">Noch keine Tutorials vorhanden.</p>
                    @auth
                        <a href="{{ route('tutorials.create') }}" class="text-amber-400 hover:underline">Erstelle das erste Tutorial!</a>
                    @endauth
                </div>
            @endforelse
        </div>

        <div class="mt-8">{{ $tutorials->links() }}</div>
    </div>
</x-layouts.app>
