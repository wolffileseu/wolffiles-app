<x-layouts.wiki :title="'Neue Diskussion: '.$article->title" :article="$article" active-tab="read" page-type="talk">
    <h1 class="wiki-firstheading">Neue Diskussion zu "{{ $article->title }}"</h1>
    <div class="wiki-subtitle">
        Starte einen neuen Diskussions-Thread.
        <a href="{{ route('wiki.talk', $article->slug) }}">← Zurück zur Diskussionsübersicht</a>
    </div>

    <div class="wiki-bodycontent">
        @if($errors->any())
            <div style="padding: 0.6rem 1rem; background: #7f1d1d; color: #fecaca; border-radius: 4px; margin-bottom: 1rem;">
                @foreach($errors->all() as $err)
                    <div>{{ $err }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('wiki.talk.store', $article->slug) }}">
            @csrf

            <div style="margin-bottom: 1rem;">
                <label for="title" style="display: block; margin-bottom: 0.25rem; font-weight: bold;">Titel</label>
                <input type="text" name="title" id="title" required maxlength="255"
                       value="{{ old('title') }}"
                       placeholder="Worum geht es?"
                       style="width: 100%; padding: 0.5rem; background: var(--wiki-bg); border: 1px solid var(--wiki-border); color: var(--wiki-text); border-radius: 3px;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="wikitext" style="display: block; margin-bottom: 0.25rem; font-weight: bold;">Inhalt</label>
                <textarea name="wikitext" id="wikitext" required rows="10"
                          placeholder="Wikitext-Syntax wird unterstützt: '''bold''', ''italic'', [[Link]], * Listen…"
                          style="width: 100%; padding: 0.5rem; background: var(--wiki-bg); border: 1px solid var(--wiki-border); color: var(--wiki-text); border-radius: 3px; font-family: ui-monospace, monospace; font-size: 13px;">{{ old('wikitext') }}</textarea>
            </div>

            <button type="submit"
                    style="padding: 0.5rem 1.5rem; background: var(--wiki-link); color: var(--wiki-bg); border: none; border-radius: 3px; cursor: pointer; font-weight: bold;">
                Diskussion starten
            </button>
            <a href="{{ route('wiki.talk', $article->slug) }}"
               style="margin-left: 0.5rem; padding: 0.5rem 1rem; color: var(--wiki-text-muted); text-decoration: none;">
                Abbrechen
            </a>
        </form>
    </div>
</x-layouts.wiki>
