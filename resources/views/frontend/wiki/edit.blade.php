<x-layouts.app title="{{ isset($wikiArticle) ? 'Wiki bearbeiten' : 'Wiki Artikel erstellen' }}">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-white mb-8">
            {{ isset($wikiArticle) ? '✏️ Artikel bearbeiten' : '📖 Neuen Artikel erstellen' }}
        </h1>

        <form action="{{ isset($wikiArticle) ? route('wiki.update', $wikiArticle) : route('wiki.store') }}" method="POST"
              class="space-y-6">
            @csrf
            @if(isset($wikiArticle)) @method('PUT') @endif

            <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 space-y-4">
                <div>
                    <label class="block text-gray-300 text-sm font-medium mb-2">Titel *</label>
                    <input type="text" name="title" value="{{ old('title', $wikiArticle->title ?? '') }}" required
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2">
                </div>

                <div>
                    <label class="block text-gray-300 text-sm font-medium mb-2">Kategorie *</label>
                    <select name="wiki_category_id" required
                            class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2">
                        <option value="">— Kategorie wählen —</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('wiki_category_id', $wikiArticle->wiki_category_id ?? '') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-gray-300 text-sm font-medium mb-2">Inhalt *</label>
                    <div id="editor-container">
                        <textarea name="content" id="wiki-content" rows="20"
                                  class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 font-mono text-sm">{{ old('content', $wikiArticle->content ?? '') }}</textarea>
                    </div>
                    <p class="text-gray-500 text-xs mt-1">HTML und Markdown werden unterstützt. Bilder per URL einfügen.</p>
                </div>

                <div>
                    <label class="block text-gray-300 text-sm font-medium mb-2">Tags</label>
                    <input type="text" name="tags" value="{{ old('tags', isset($wikiArticle) && $wikiArticle->tags ? implode(', ', $wikiArticle->tags) : '') }}"
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2"
                           placeholder="z.B. ET, Mapping, ETPub (kommagetrennt)">
                </div>

                @if(isset($wikiArticle))
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-2">Änderungsbeschreibung</label>
                        <input type="text" name="change_summary"
                               class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2"
                               placeholder="Was wurde geändert?">
                    </div>
                @endif
            </div>

            @if($errors->any())
                <div class="bg-red-900/50 border border-red-700 rounded-lg p-4">
                    @foreach($errors->all() as $error)
                        <p class="text-red-400 text-sm">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="flex items-center justify-between">
                <a href="{{ route('wiki.index') }}" class="text-gray-400 hover:text-white text-sm">← Zurück zum Wiki</a>
                <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg font-medium">
                    {{ isset($wikiArticle) ? 'Änderungen speichern' : 'Artikel einreichen' }}
                </button>
            </div>

            <p class="text-gray-500 text-xs text-center">
                Dein Artikel wird nach Überprüfung durch einen Moderator veröffentlicht.
            </p>
        </form>
    </div>
</x-layouts.app>
