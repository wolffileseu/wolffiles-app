<x-layouts.app :title="$article->title">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-400 mb-6">
            <a href="{{ route('wiki.index') }}" class="hover:text-amber-400">Wiki</a>
            @if($article->category)
                / <a href="{{ route('wiki.index', ['category' => $article->category->slug]) }}" class="hover:text-amber-400">{{ $article->category->name }}</a>
            @endif
            / <span class="text-gray-300">{{ $article->title }}</span>
        </nav>

        {{-- Article Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-white mb-3">{{ $article->title }}</h1>
            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                <span>Von <strong class="text-gray-300">{{ $article->user?->name }}</strong></span>
                <span>Letzte Änderung: {{ $article->updated_at->format('d.m.Y H:i') }}</span>
                <span>{{ $article->view_count }} Views</span>
                <span>{{ $article->revision_count }} Revisionen</span>
                @if($article->tags)
                    <div class="flex gap-2">
                        @foreach($article->tags as $tag)
                            <a href="{{ route('wiki.index', ['tag' => $tag]) }}" class="text-amber-400 hover:underline text-xs">#{{ $tag }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Article Content --}}
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-8 mb-8">
            <div class="prose prose-invert max-w-none prose-headings:text-amber-400 prose-a:text-amber-400 prose-code:text-green-400 prose-img:rounded-lg">
                {!! $article->content !!}
            </div>

            {{-- Attachments --}}
            @if($article->attachments && count($article->attachments) > 0)
                <div class="mt-8 pt-6 border-t border-gray-700">
                    <h3 class="text-sm font-bold text-amber-400 uppercase tracking-wider mb-3">Anhänge</h3>
                    <div class="space-y-2">
                        @foreach($article->attachments as $attachment)
                            <a href="{{ Storage::disk('s3')->url($attachment) }}" target="_blank"
                               class="flex items-center space-x-2 text-gray-300 hover:text-amber-400 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>{{ basename($attachment) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between mb-8">
            <div class="flex gap-3">
                @auth
                    @if(!$article->is_locked)
                        <a href="{{ route('wiki.edit', $article) }}" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm">
                            ✏️ Bearbeiten
                        </a>
                    @endif
                @endauth
                <a href="{{ route('wiki.history', $article) }}" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm">
                    📜 Versionshistorie
                </a>
            </div>
        </div>

        {{-- Related Articles --}}
        @if($related->isNotEmpty())
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-white mb-4">Verwandte Artikel</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($related as $rel)
                        <a href="{{ route('wiki.show', $rel->slug) }}" class="bg-gray-800 rounded-lg border border-gray-700 p-4 hover:border-amber-600 transition-colors">
                            <h4 class="text-amber-400 font-medium">{{ $rel->title }}</h4>
                            <p class="text-gray-400 text-sm mt-1">{{ Str::limit(strip_tags($rel->excerpt), 100) }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Comments --}}
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Kommentare ({{ $article->comments->count() }})</h3>
            @auth
                <form action="{{ route('comments.store') }}" method="POST" class="mb-6">
                    @csrf
                    <input type="hidden" name="commentable_type" value="App\Models\WikiArticle">
                    <input type="hidden" name="commentable_id" value="{{ $article->id }}">
                    <textarea name="content" rows="3" required
                              class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm" placeholder="Kommentar schreiben..."></textarea>
                    <button class="mt-2 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm">Kommentieren</button>
                </form>
            @endauth
            <div class="space-y-4">
                @foreach($article->comments as $comment)
                    <div class="flex space-x-3">
                        <div class="w-8 h-8 rounded-full bg-gray-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                            {{ strtoupper(substr($comment->user?->name ?? '?', 0, 2)) }}
                        </div>
                        <div>
                            <div class="text-sm">
                                <strong class="text-gray-300">{{ $comment->user?->name }}</strong>
                                <span class="text-gray-500 text-xs ml-2">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-gray-400 text-sm mt-1">{{ $comment->content }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.app>
