<x-layouts.app title="Wiki">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-white">📖 Wiki</h1>
            @auth
                <a href="{{ route('wiki.create') }}" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    + Artikel erstellen
                </a>
            @endauth
        </div>

        {{-- Search & Filter --}}
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-4 mb-8">
            <form action="{{ route('wiki.index') }}" method="GET" class="flex flex-wrap gap-4">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Wiki durchsuchen..."
                       class="flex-1 min-w-[200px] bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                <select name="category" class="bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                    <option value="">Alle Kategorien</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->slug }}" {{ request('category') == $cat->slug ? 'selected' : '' }}>
                            {{ $cat->name }} ({{ $cat->published_articles_count }})
                        </option>
                    @endforeach
                </select>
                <button class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg text-sm font-medium">Suchen</button>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            {{-- Categories Sidebar --}}
            <aside class="lg:col-span-1">
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-4">
                    <h3 class="text-sm font-bold text-amber-400 uppercase tracking-wider mb-3">Kategorien</h3>
                    <div class="space-y-1">
                        <a href="{{ route('wiki.index') }}" class="block px-3 py-2 rounded text-sm {{ !request('category') ? 'bg-gray-700 text-white' : 'text-gray-400 hover:text-white' }}">
                            Alle Artikel
                        </a>
                        @foreach($categories as $cat)
                            <a href="{{ route('wiki.index', ['category' => $cat->slug]) }}"
                               class="flex justify-between items-center px-3 py-2 rounded text-sm {{ request('category') == $cat->slug ? 'bg-gray-700 text-white' : 'text-gray-400 hover:text-white' }}">
                                <span>{{ $cat->name }}</span>
                                <span class="text-xs text-gray-500">{{ $cat->published_articles_count }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </aside>

            {{-- Articles --}}
            <div class="lg:col-span-3">
                <div class="space-y-4">
                    @forelse($articles as $article)
                        <article class="bg-gray-800 rounded-lg border border-gray-700 p-6 hover:border-gray-600 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <h2 class="text-lg font-semibold text-amber-400 mb-1">
                                        <a href="{{ route('wiki.show', $article->slug) }}" class="hover:underline">{{ $article->title }}</a>
                                    </h2>
                                    <p class="text-gray-400 text-sm mb-3">{{ Str::limit(strip_tags($article->excerpt ?? $article->content), 200) }}</p>
                                    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                        @if($article->category)
                                            <span class="bg-gray-700 px-2 py-0.5 rounded">{{ $article->category->name }}</span>
                                        @endif
                                        <span>von {{ $article->user?->name }}</span>
                                        <span>{{ $article->updated_at->format('d.m.Y') }}</span>
                                        <span>{{ $article->view_count }} Views</span>
                                        @if($article->tags)
                                            @foreach(array_slice($article->tags, 0, 3) as $tag)
                                                <a href="{{ route('wiki.index', ['tag' => $tag]) }}" class="text-amber-400 hover:underline">#{{ $tag }}</a>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                                @if($article->is_featured)
                                    <span class="text-amber-400 text-xs font-bold">⭐ Featured</span>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="text-center py-12 text-gray-500">
                            <p class="text-lg mb-2">Noch keine Wiki-Artikel.</p>
                            @auth
                                <a href="{{ route('wiki.create') }}" class="text-amber-400 hover:underline">Erstelle den ersten!</a>
                            @endauth
                        </div>
                    @endforelse
                </div>

                <div class="mt-8">{{ $articles->links() }}</div>
            </div>
        </div>
    </div>
</x-layouts.app>
