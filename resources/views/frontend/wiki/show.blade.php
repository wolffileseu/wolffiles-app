@php
    use App\Services\Wiki\WikitextParser;

    // Parsen: bevorzugt cached HTML aus translation, sonst frisch parsen
    $locale       = app()->getLocale();
    $translation  = $article->translation($locale) ?? $article->translation('de');
    $cachedHtml   = $translation?->content_html;
    $sourceText   = $translation?->wikitext ?? $article->wikitext ?? $article->content;

    $parser  = WikitextParser::make([
        'locale'     => $locale,
        'article_id' => $article->id,
        'namespace'  => $article->namespace,
    ]);
    $parsed  = $parser->parse((string) $sourceText);
    $bodyHtml = $cachedHtml ?: $parsed->html;
    $title    = $article->localizedTitle($locale);
@endphp

<x-layouts.wiki :title="$title" :article="$article" active-tab="read" page-type="article">
    <h1 class="wiki-firstheading">{{ $title }}</h1>
    <div class="wiki-subtitle">
        Aus Wolffiles Wiki — der freien Datenbank für Wolfenstein:ET &amp; RtCW
    </div>

    @if($parsed->redirectTo)
        <div class="wiki-redirect-notice">
            ↪ Redirect: <strong>{{ $parsed->redirectTo }}</strong>
        </div>
    @endif

    <div class="wiki-bodycontent">
        {!! $bodyHtml !!}
    </div>

    {{-- Categories Footer --}}
    @if(!empty($parsed->categories) || $article->categoriesM2M->isNotEmpty())
        <div class="wiki-catfoot">
            <strong>Kategorien:</strong>
            @foreach($article->categoriesM2M as $cat)
                <a href="{{ route('wiki.index', ['category' => $cat->slug]) }}">{{ $cat->name }}</a>
            @endforeach
            @foreach($parsed->categories as $catSlug)
                @if(!$article->categoriesM2M->contains('slug', $catSlug))
                    <a href="{{ route('wiki.index', ['category' => $catSlug]) }}" class="wikilink-new">{{ $catSlug }}</a>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Last edited footer --}}
    <div style="margin-top: 1rem; font-size: 12px; color: var(--wiki-text-muted);">
        Diese Seite wurde zuletzt am {{ $article->updated_at->format('d. F Y') }}
        um {{ $article->updated_at->format('H:i') }} Uhr von
        <strong>{{ $article->user?->name ?? 'unbekannt' }}</strong> bearbeitet.
        ({{ $article->view_count }} Aufrufe, {{ $article->revision_count }} Revisionen)
    </div>
</x-layouts.wiki>
