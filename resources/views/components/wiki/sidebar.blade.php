@props(['article' => null])

@php
    $availableLocales = $article && method_exists($article, 'availableLocales')
        ? $article->availableLocales()
        : [];
    $localeNames = ['de'=>'Deutsch','en'=>'English','fr'=>'Français','nl'=>'Nederlands','pl'=>'Polski','tr'=>'Türkçe'];
@endphp

{{-- Search --}}
<div class="wiki-search">
    <form method="GET" action="{{ route('wiki.index') }}">
        <input type="search" name="q" placeholder="Wiki durchsuchen…" value="{{ request('q') }}">
    </form>
</div>

{{-- Navigation --}}
<h6>Navigation</h6>
<ul>
    <li><a href="{{ route('wiki.index') }}">📖 Hauptseite</a></li>
    <li><a href="{{ route('wiki.special.recent') }}">🕒 Letzte Änderungen</a></li>
    <li><a href="{{ route('wiki.special.random') }}">🎲 Zufällige Seite</a></li>
    <li><a href="{{ route('wiki.special.all') }}">📚 Alle Seiten</a></li>
</ul>

{{-- Tools (article-context) --}}
@if($article)
    <h6>Werkzeuge</h6>
    <ul>
        <li><a href="{{ route('wiki.special.whatlinkshere', $article->slug) }}">🔗 Was linkt hierher</a></li>
        <li><a href="{{ route('wiki.history', $article) }}">📜 Versionsgeschichte</a></li>
        <li><a href="#" title="Permalink">🔗 Permalink</a></li>
        @auth
            @can('update', $article)
                <li><a href="/admin/wiki-articles/{{ $article->slug }}/edit">✏️ Bearbeiten (Admin)</a></li>
            @endcan
        @endauth
    </ul>
@endif

{{-- Sprachen --}}
@if(!empty($availableLocales))
    <h6>In anderen Sprachen</h6>
    <ul>
        @foreach($availableLocales as $loc)
            @if($loc !== app()->getLocale())
                <li><a href="?locale={{ $loc }}">{{ $localeNames[$loc] ?? $loc }}</a></li>
            @endif
        @endforeach
    </ul>
@endif

{{-- Theme Toggle --}}
<h6>Anzeige</h6>
<button type="button" class="wiki-theme-toggle">☀️ Light Mode</button>

{{-- Custom slot content --}}
{{ $slot }}
