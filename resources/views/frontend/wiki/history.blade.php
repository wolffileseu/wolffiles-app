<x-layouts.wiki :title="'Versionsgeschichte: '.$article->title" :article="$article" active-tab="history" page-type="article">
    <h1 class="wiki-firstheading">Versionsgeschichte: {{ $article->title }}</h1>
    <div class="wiki-subtitle">
        Alle Bearbeitungen — wähle 2 Revisionen aus für Vergleich, oder klicke eine an um sie anzusehen.
    </div>

    <div class="wiki-bodycontent">
        @if($revisions->isEmpty())
            <p>Keine Revisionen vorhanden.</p>
        @else
            <form id="diff-form" method="GET" action="" style="margin-bottom: 1rem;">
                <button type="button" id="diff-submit"
                        style="background: var(--wiki-link); color: var(--wiki-bg); border: none; padding: 0.4rem 1rem; cursor: pointer; border-radius: 3px; font-weight: bold;">
                    📊 Ausgewählte Versionen vergleichen
                </button>
                <span style="margin-left: 1rem; font-size: 12px; color: var(--wiki-text-muted);">
                    Wähle genau 2 Checkboxes
                </span>
            </form>

            <table class="wiki-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 3rem;">Diff</th>
                        <th style="width: 5rem;">Version</th>
                        <th style="width: 11rem;">Datum</th>
                        <th>Bearbeiter</th>
                        <th>Änderungs-Zusammenfassung</th>
                        <th style="width: 6rem;">Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($revisions as $rev)
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" class="diff-check" value="{{ $rev->revision_number }}">
                            </td>
                            <td><strong>#{{ $rev->revision_number }}</strong></td>
                            <td>{{ $rev->created_at->format('d.m.Y H:i') }}</td>
                            <td>{{ $rev->user?->name ?? 'Unbekannt' }}</td>
                            <td>
                                @if($rev->change_summary)
                                    <em>{{ $rev->change_summary }}</em>
                                @else
                                    <span style="color: var(--wiki-text-muted);">—</span>
                                @endif
                            </td>
                            <td>
                                @auth
                                    @can('update', $article)
                                        <form method="POST" action="{{ route('wiki.restore', [$article->slug, $rev->revision_number]) }}"
                                              style="display: inline;"
                                              onsubmit="return confirm('Diese Revision als aktuelle Version setzen?')">
                                            @csrf
                                            <button type="submit"
                                                    style="background: transparent; color: var(--wiki-link); border: 1px solid var(--wiki-border); padding: 2px 8px; cursor: pointer; border-radius: 2px; font-size: 12px;">
                                                ↶ Restore
                                            </button>
                                        </form>
                                    @endcan
                                @endauth
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{ $revisions->links() }}
        @endif
    </div>

    <script>
        document.getElementById('diff-submit')?.addEventListener('click', function() {
            const checked = Array.from(document.querySelectorAll('.diff-check:checked'));
            if (checked.length !== 2) {
                alert('Bitte genau 2 Revisionen auswählen.');
                return;
            }
            const nums = checked.map(c => parseInt(c.value)).sort((a, b) => a - b);
            const slug = @json($article->slug);
            window.location.href = `/wiki/${slug}/diff/${nums[0]}/${nums[1]}`;
        });

        // Max 2 Checkboxes
        document.querySelectorAll('.diff-check').forEach(cb => {
            cb.addEventListener('change', function() {
                const checked = document.querySelectorAll('.diff-check:checked');
                if (checked.length > 2) {
                    this.checked = false;
                    alert('Maximal 2 Versionen vergleichbar.');
                }
            });
        });
    </script>
</x-layouts.wiki>
