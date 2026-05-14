<x-layouts.wiki :title="'Letzte Änderungen'" :article="null" page-type="article">
    <h1 class="wiki-firstheading">Letzte Änderungen</h1>
    <div class="wiki-subtitle">Alle Bearbeitungen der letzten {{ $days }} Tage</div>

    <div class="wiki-bodycontent">
        <form method="GET" style="margin-bottom: 1rem;">
            <label>Zeitraum:
                <select name="days" onchange="this.form.submit()">
                    @foreach([7, 14, 30, 60, 90, 365] as $d)
                        <option value="{{ $d }}" {{ $days == $d ? 'selected' : '' }}>{{ $d }} Tage</option>
                    @endforeach
                </select>
            </label>
        </form>

        @if($revisions->isEmpty())
            <p>Keine Änderungen im gewählten Zeitraum.</p>
        @else
            <table class="wiki-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 8rem;">Datum</th>
                        <th>Artikel</th>
                        <th>Bearbeiter</th>
                        <th>Zusammenfassung</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($revisions as $rev)
                        <tr>
                            <td style="white-space: nowrap; font-size: 12px;">
                                {{ $rev->created_at->format('d.m.Y H:i') }}
                            </td>
                            <td>
                                @if($rev->article)
                                    <a href="{{ route('wiki.show', $rev->article->slug) }}">{{ $rev->title }}</a>
                                    <small>(Rev #{{ $rev->revision_number }})</small>
                                @else
                                    <em>{{ $rev->title }} (gelöscht)</em>
                                @endif
                            </td>
                            <td>{{ $rev->user?->name ?? '?' }}</td>
                            <td style="font-size: 12px; color: var(--wiki-text-muted);">
                                {{ $rev->change_summary ?: '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-layouts.wiki>
