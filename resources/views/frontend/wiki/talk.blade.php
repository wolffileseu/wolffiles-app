<x-layouts.wiki :title="'Diskussion: '.$article->title" :article="$article" active-tab="read" page-type="talk">
    <h1 class="wiki-firstheading">Diskussion: {{ $article->title }}</h1>
    <div class="wiki-subtitle">
        Diskussionen zum Artikel
        <a href="{{ route('wiki.show', $article->slug) }}">{{ $article->title }}</a>
    </div>

    <div class="wiki-bodycontent">
        @if(session('success'))
            <div style="padding: 0.6rem 1rem; background: #14532d; color: #bbf7d0; border-radius: 4px; margin-bottom: 1rem;">
                ✅ {{ session('success') }}
            </div>
        @endif

        <div style="margin-bottom: 1.5rem;">
            @auth
                <a href="{{ route('wiki.talk.new', $article->slug) }}"
                   style="display: inline-block; padding: 0.5rem 1rem; background: var(--wiki-link); color: var(--wiki-bg); text-decoration: none; border-radius: 3px; font-weight: bold;">
                    💬 Neue Diskussion starten
                </a>
            @else
                <em>Du musst <a href="{{ route('login') }}">eingeloggt</a> sein um eine Diskussion zu starten.</em>
            @endauth
        </div>

        @if($threads->isEmpty())
            <p style="color: var(--wiki-text-muted);">Noch keine Diskussionen zu diesem Artikel.</p>
        @else
            @foreach($threads as $thread)
                <article id="thread-{{ $thread->id }}"
                         style="margin-bottom: 1.5rem; border: 1px solid var(--wiki-border); background: var(--wiki-bg-alt); border-radius: 4px;">

                    <header style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--wiki-border-soft); display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                        @if($thread->is_pinned)
                            <span title="Angeheftet" style="color: var(--wiki-link);">📌</span>
                        @endif
                        @if($thread->is_resolved)
                            <span title="Erledigt" style="color: #86efac;">✓</span>
                        @endif
                        <strong style="flex: 1; {{ $thread->is_resolved ? 'text-decoration: line-through; opacity: 0.6;' : '' }}">
                            {{ $thread->title }}
                        </strong>
                        <small style="color: var(--wiki-text-muted);">
                            {{ $thread->messages->count() }} {{ $thread->messages->count() === 1 ? 'Nachricht' : 'Nachrichten' }}
                            · zuletzt {{ $thread->last_reply_at?->diffForHumans() ?? '?' }}
                        </small>

                        @auth
                            @if(auth()->user()->hasRole('admin') || auth()->id() === $thread->created_by)
                                <form method="POST" action="{{ route('wiki.talk.resolve', [$article->slug, $thread->id]) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" title="Erledigt-Status umschalten"
                                            style="background: transparent; border: 1px solid var(--wiki-border); color: var(--wiki-link); padding: 2px 8px; cursor: pointer; border-radius: 2px; font-size: 12px;">
                                        {{ $thread->is_resolved ? '↩ Wieder öffnen' : '✓ Erledigt' }}
                                    </button>
                                </form>
                            @endif
                            @if(auth()->user()->hasRole('admin'))
                                <form method="POST" action="{{ route('wiki.talk.pin', [$article->slug, $thread->id]) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" style="background: transparent; border: 1px solid var(--wiki-border); color: var(--wiki-link); padding: 2px 8px; cursor: pointer; border-radius: 2px; font-size: 12px;">
                                        {{ $thread->is_pinned ? '📌 Lösen' : '📌 Pin' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('wiki.talk.delete', [$article->slug, $thread->id]) }}"
                                      style="display: inline;" onsubmit="return confirm('Thread löschen?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="background: transparent; border: 1px solid #b91c1c; color: #fca5a5; padding: 2px 8px; cursor: pointer; border-radius: 2px; font-size: 12px;">
                                        🗑 Löschen
                                    </button>
                                </form>
                            @endif
                        @endauth
                    </header>

                    <div style="padding: 0;">
                        @foreach($thread->messages as $msg)
                            <div id="msg-{{ $msg->id }}"
                                 style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--wiki-border-soft); {{ $msg->reply_to_id ? 'margin-left: 2rem; background: rgba(0,0,0,0.1);' : '' }}">
                                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.4rem; font-size: 12px;">
                                    <div>
                                        <strong style="color: var(--wiki-link);">{{ $msg->user?->name ?? 'Unbekannt' }}</strong>
                                        <span style="color: var(--wiki-text-muted); margin-left: 0.5rem;">{{ $msg->created_at->diffForHumans() }}</span>
                                    </div>
                                    @auth
                                        @if(auth()->user()->hasRole('admin'))
                                            <form method="POST" action="{{ route('wiki.talk.delete_msg', [$article->slug, $msg->id]) }}"
                                                  style="display: inline;" onsubmit="return confirm('Nachricht löschen?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" style="background: transparent; border: none; color: #fca5a5; cursor: pointer; font-size: 11px;">🗑</button>
                                            </form>
                                        @endif
                                    @endauth
                                </div>
                                <div class="wiki-bodycontent">{!! $msg->content_html ?: e($msg->wikitext) !!}</div>
                            </div>
                        @endforeach

                        @auth
                            @if(!$thread->is_resolved)
                                <form method="POST" action="{{ route('wiki.talk.reply', [$article->slug, $thread->id]) }}"
                                      style="padding: 0.75rem 1rem; background: rgba(0,0,0,0.15);">
                                    @csrf
                                    <textarea name="wikitext" required rows="3"
                                              placeholder="Antwort schreiben (Wikitext unterstützt)…"
                                              style="width: 100%; background: var(--wiki-bg); border: 1px solid var(--wiki-border); color: var(--wiki-text); padding: 0.5rem; border-radius: 3px; font-family: ui-monospace, monospace; font-size: 13px;"></textarea>
                                    <button type="submit"
                                            style="margin-top: 0.4rem; background: var(--wiki-link); color: var(--wiki-bg); border: none; padding: 0.3rem 0.9rem; border-radius: 3px; cursor: pointer; font-size: 13px; font-weight: bold;">
                                        Antworten
                                    </button>
                                </form>
                            @endif
                        @endauth
                    </div>
                </article>
            @endforeach

            {{ $threads->links() }}
        @endif
    </div>
</x-layouts.wiki>
