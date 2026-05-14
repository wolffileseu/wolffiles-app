<x-layouts.wiki :title="'Alle Seiten'" :article="null" page-type="article">
    <h1 class="wiki-firstheading">Alle Seiten</h1>
    <div class="wiki-subtitle">
        Alphabetisch geordnete Liste aller Wiki-Artikel
        @if($namespace !== 'main') (Namespace: {{ $namespace }}) @endif
    </div>

    <div class="wiki-bodycontent">
        {{-- Alphabet-Navi --}}
        <div style="margin-bottom: 1rem; font-size: 13px;">
            <a href="{{ route('wiki.special.all') }}">Alle</a> ·
            @foreach($letters as $l)
                <a href="{{ route('wiki.special.all', ['from' => $l]) }}"
                   style="margin: 0 2px; {{ $startsWith === $l ? 'font-weight: bold; color: var(--wiki-heading);' : '' }}">{{ $l }}</a>
            @endforeach
        </div>

        @if($articles->isEmpty())
            <p>
                Keine Seiten gefunden
                @if($startsWith) (beginnend mit "{{ $startsWith }}") @endif.
            </p>
        @else
            <ul class="wiki-list" style="column-count: 3; column-gap: 2rem;">
                @foreach($articles as $a)
                    <li>
                        <a href="{{ route('wiki.show', $a->slug) }}">{{ $a->title }}</a>
                        @if($a->is_redirect)
                            <small style="color: var(--wiki-text-muted);">(Redirect)</small>
                        @endif
                    </li>
                @endforeach
            </ul>

            {{ $articles->links() }}

            <p style="margin-top: 1rem; color: var(--wiki-text-muted); font-size: 12px;">
                {{ $articles->total() }} Seiten gesamt
            </p>
        @endif
    </div>
</x-layouts.wiki>
