@props([
    'article'   => null,
    'activeTab' => 'read',
    'pageType'  => 'article',
])

<div class="wiki-tabs">
    {{-- Linke Gruppe: Article / Talk --}}
    <div class="wiki-tab-group">
        @if($article)
            <a href="{{ route('wiki.show', $article->slug) }}"
               class="wiki-tab {{ $pageType === 'article' ? 'is-active' : '' }}">
                Artikel
            </a>
            <a href="{{ route('wiki.talk', $article->slug) }}"
               class="wiki-tab {{ $pageType === 'talk' ? 'is-active' : '' }}">
                Diskussion
                @if($article->talkThreads->count() > 0)
                    <small>({{ $article->talkThreads->count() }})</small>
                @endif
            </a>
        @else
            <span class="wiki-tab is-active">Spezialseite</span>
        @endif
    </div>

    {{-- Rechte Gruppe: Read / Edit / History --}}
    @if($article)
        <div class="wiki-tab-group">
            <a href="{{ route('wiki.show', $article->slug) }}"
               class="wiki-tab {{ $activeTab === 'read' ? 'is-active' : '' }}">
                Lesen
            </a>
            @auth
                @can('update', $article)
                    <a href="/admin/wiki-articles/{{ $article->slug }}/edit"
                       class="wiki-tab {{ $activeTab === 'edit' ? 'is-active' : '' }}">
                        Bearbeiten
                    </a>
                @endcan
            @endauth
            <a href="{{ route('wiki.history', $article->slug) }}"
               class="wiki-tab {{ $activeTab === 'history' ? 'is-active' : '' }}">
                Versionsgeschichte
            </a>
        </div>
    @endif
</div>
