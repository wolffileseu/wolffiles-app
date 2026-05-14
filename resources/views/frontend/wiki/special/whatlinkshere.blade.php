<x-layouts.wiki :title="'Was linkt auf '.$article->title" :article="$article" page-type="article">
    <h1 class="wiki-firstheading">Was verlinkt auf "{{ $article->title }}"?</h1>
    <div class="wiki-subtitle">
        Alle Wiki-Seiten die auf
        <a href="{{ route('wiki.show', $article->slug) }}">{{ $article->title }}</a>
        verweisen
    </div>

    <div class="wiki-bodycontent">
        @if($redirects->isNotEmpty())
            <h3 class="wiki-heading">Redirects auf diese Seite</h3>
            <ul class="wiki-list">
                @foreach($redirects as $r)
                    <li><code>{{ $r->namespace }}:{{ $r->from_slug }}</code></li>
                @endforeach
            </ul>
        @endif

        <h3 class="wiki-heading">Eingehende Links</h3>
        @if($incoming->isEmpty())
            <p>Bisher verlinkt keine Seite auf diesen Artikel.</p>
            <p style="font-size: 12px; color: var(--wiki-text-muted);">
                <em>Hinweis: Die Link-Tabelle wird beim Speichern eines Artikels gefüllt.
                Bestehende Artikel müssen einmal neu gespeichert werden, damit ihre Links indexiert werden.</em>
            </p>
        @else
            <ul class="wiki-list">
                @foreach($incoming as $link)
                    <li>
                        @if($link->fromArticle)
                            <a href="{{ route('wiki.show', $link->fromArticle->slug) }}">
                                {{ $link->fromArticle->title }}
                            </a>
                            <small style="color: var(--wiki-text-muted);">
                                — {{ $link->fromArticle->user?->name }}
                            </small>
                        @endif
                    </li>
                @endforeach
            </ul>
            {{ $incoming->links() }}
        @endif
    </div>
</x-layouts.wiki>
