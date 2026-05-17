<x-layouts.wiki :title="'Diff: '.$article->title" :article="$article" active-tab="history" page-type="article">
    <h1 class="wiki-firstheading">Diff: {{ $article->title }}</h1>
    <div class="wiki-subtitle">
        Vergleich Revision #{{ $left->revision_number }} ↔ #{{ $right->revision_number }}
    </div>

    <div class="wiki-bodycontent">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; font-size: 13px;">
            <div style="background: var(--wiki-bg-alt); padding: 0.75rem; border-radius: 3px; border-left: 3px solid #b91c1c;">
                <strong>Revision #{{ $left->revision_number }}</strong> (alt)<br>
                {{ $left->created_at->format('d.m.Y H:i') }} — {{ $left->user?->name ?? '?' }}<br>
                @if($left->change_summary)
                    <em style="color: var(--wiki-text-muted);">{{ $left->change_summary }}</em>
                @endif
            </div>
            <div style="background: var(--wiki-bg-alt); padding: 0.75rem; border-radius: 3px; border-left: 3px solid #15803d;">
                <strong>Revision #{{ $right->revision_number }}</strong> (neu)<br>
                {{ $right->created_at->format('d.m.Y H:i') }} — {{ $right->user?->name ?? '?' }}<br>
                @if($right->change_summary)
                    <em style="color: var(--wiki-text-muted);">{{ $right->change_summary }}</em>
                @endif
            </div>
        </div>

        <div style="margin-bottom: 1rem; padding: 0.5rem 0.75rem; background: var(--wiki-bg-alt); border-radius: 3px; font-size: 13px;">
            <span style="color: #86efac;">+{{ $stats['added'] }} hinzugefügt</span>
            &nbsp;·&nbsp;
            <span style="color: #fca5a5;">−{{ $stats['removed'] }} entfernt</span>
            &nbsp;·&nbsp;
            <span style="color: var(--wiki-text-muted);">{{ $stats['unchanged'] }} unverändert</span>
            &nbsp;·&nbsp;
            <a href="{{ route('wiki.history', $article->slug) }}">← Zurück zur Versionsgeschichte</a>
        </div>

        <div style="overflow-x: auto;">
            {!! $diffHtml !!}
        </div>
    </div>
</x-layouts.wiki>
