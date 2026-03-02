<x-layouts.app :title="'History: ' . $wikiArticle->title">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <nav class="text-sm text-gray-400 mb-6">
            <a href="{{ route('wiki.index') }}" class="hover:text-amber-400">Wiki</a>
            / <a href="{{ route('wiki.show', $wikiArticle->slug) }}" class="hover:text-amber-400">{{ $wikiArticle->title }}</a>
            / <span class="text-gray-300">Versionshistorie</span>
        </nav>

        <h1 class="text-3xl font-bold text-white mb-2">📜 Versionshistorie</h1>
        <p class="text-gray-400 mb-8">{{ $wikiArticle->title }}</p>

        <div class="space-y-4">
            @forelse($revisions as $revision)
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-amber-400 font-bold">v{{ $revision->revision_number }}</span>
                            <span class="text-gray-400 text-sm ml-3">{{ $revision->user?->name ?? 'Unknown' }}</span>
                            <span class="text-gray-500 text-sm ml-3">{{ $revision->created_at->format('d.m.Y H:i') }}</span>
                        </div>
                        <span class="text-gray-500 text-xs">{{ $revision->created_at->diffForHumans() }}</span>
                    </div>
                    @if($revision->change_summary)
                        <p class="text-gray-300 text-sm mt-2">💬 {{ $revision->change_summary }}</p>
                    @endif
                </div>
            @empty
                <p class="text-gray-500 text-center py-8">Keine Revisionen vorhanden.</p>
            @endforelse
        </div>

        <div class="mt-8">{{ $revisions->links() }}</div>

        <div class="mt-6">
            <a href="{{ route('wiki.show', $wikiArticle->slug) }}" class="text-gray-400 hover:text-amber-400 text-sm">← Zurück zum Artikel</a>
        </div>
    </div>
</x-layouts.app>
