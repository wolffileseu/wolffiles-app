<x-filament-panels::page>

    <div class="space-y-4">
        {{-- Manual merge: for pairs the auto-detector misses (e.g. punctuation-only name diffs). --}}
        <div class="rounded-xl border border-primary-200 dark:border-primary-500/30 bg-primary-50/50 dark:bg-primary-500/5 p-4">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Manuell zusammenführen</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                Für Paare, die die Auto-Erkennung verpasst (z.&nbsp;B. nur ein Satzzeichen Unterschied).
                <strong>Behalten-ID</strong> bleibt die Hauptidentität &amp; behält die ID; GUID und Enhanced-Daten
                wandern automatisch vom zusammengeführten Datensatz herüber. Zuerst <em>Vorschau</em>, dann zusammenführen.
            </p>
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Behalten-ID (Poller/Haupt)</label>
                    <input type="number" wire:model.blur="manualKeepId" placeholder="z.B. 20445"
                           class="w-36 rounded-lg border-gray-300 dark:border-white/10 dark:bg-white/5 text-sm">
                </div>
                <div class="text-gray-400 pb-2">&larr;</div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Zusammenführen-ID (wird aufgelöst)</label>
                    <input type="number" wire:model.blur="manualMergeId" placeholder="z.B. 30427"
                           class="w-36 rounded-lg border-gray-300 dark:border-white/10 dark:bg-white/5 text-sm">
                </div>
                <button type="button" wire:click="previewManual"
                        class="px-3 py-2 rounded-lg text-sm font-medium bg-gray-200 hover:bg-gray-300 dark:bg-white/10 dark:hover:bg-white/20 text-gray-800 dark:text-gray-100">
                    Vorschau
                </button>
                <button type="button" wire:click="doManual"
                        wire:confirm="Wirklich zusammenführen? Ein Backup wird vorher gespeichert."
                        class="px-3 py-2 rounded-lg text-sm font-medium bg-primary-600 hover:bg-primary-500 text-white">
                    Zusammenführen
                </button>
            </div>
        </div>

        {{-- Suspect scan is expensive; load on demand to avoid Cloudflare 524. --}}
        @if(! $showSuspects)
            <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4 flex items-center justify-between gap-4">
                <div class="text-sm text-gray-600 dark:text-gray-300">
                    Die automatische Verdachtspaar-Suche durchsucht alle Enhanced-Spieler und dauert <strong>~3&nbsp;Minuten</strong>.
                    Für Einzelfälle nutze besser die manuelle Zusammenführung oben.
                </div>
                <button type="button" wire:click="loadSuspects" wire:loading.attr="disabled"
                        class="shrink-0 px-3 py-2 rounded-lg text-sm font-medium bg-primary-600 hover:bg-primary-500 text-white disabled:opacity-50">
                    <span wire:loading.remove wire:target="loadSuspects">Verdachtspaare laden</span>
                    <span wire:loading wire:target="loadSuspects">Lädt… (kann dauern)</span>
                </button>
            </div>
        @else
        @php($pairs = $this->getSuspectPairs())
        <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ count($pairs) }} Verdachtspaare gefunden. <strong>Grün</strong> = sicher (volle Server-Überlappung, eindeutiger Name).
            <strong>Rot</strong> = Vorsicht (keine gemeinsamen Server / generischer Name — evtl. verschiedene Personen).
            Der Poller-Spieler (mit History) bleibt erhalten, der Enhanced-Spieler wird zusammengeführt.
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-white/5 text-left">
                    <tr>
                        <th class="px-3 py-2">Name</th>
                        <th class="px-3 py-2 text-center">Behalten (Poller)</th>
                        <th class="px-3 py-2 text-center">Zusammenführen (Enhanced)</th>
                        <th class="px-3 py-2 text-center">Server-Overlap</th>
                        <th class="px-3 py-2 text-center">Konfidenz</th>
                        <th class="px-3 py-2 text-center">Aktion</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse($pairs as $p)
                        <tr>
                            <td class="px-3 py-2 font-medium">{{ $p['name'] }}</td>
                            <td class="px-3 py-2 text-center">
                                <a href="/players/{{ $p['keep_id'] }}" target="_blank" class="text-primary-600 hover:underline">#{{ $p['keep_id'] }}</a>
                                <div class="text-xs text-gray-500">{{ $p['keep_sessions'] }} Sess. / {{ number_format($p['keep_kills']) }} Kills</div>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <a href="/players/{{ $p['merge_id'] }}" target="_blank" class="text-primary-600 hover:underline">#{{ $p['merge_id'] }}</a>
                                <div class="text-xs text-gray-500">{{ $p['merge_sessions'] }} Sess.</div>
                            </td>
                            <td class="px-3 py-2 text-center">{{ $p['overlap'] }} / {{ $p['enh_servers'] }}</td>
                            <td class="px-3 py-2 text-center">
                                @if($p['confidence'] === 'high')
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400">sicher</span>
                                @elseif($p['confidence'] === 'medium')
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700 dark:bg-yellow-500/20 dark:text-yellow-400">mittel</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400">Vorsicht</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center whitespace-nowrap">
                                <x-filament::button size="xs" color="gray"
                                    wire:click="previewMerge({{ $p['keep_id'] }}, {{ $p['merge_id'] }})">
                                    Vorschau
                                </x-filament::button>
                                <x-filament::button size="xs" color="success"
                                    wire:click="doMerge({{ $p['keep_id'] }}, {{ $p['merge_id'] }})"
                                    wire:confirm="Spieler #{{ $p['merge_id'] }} in #{{ $p['keep_id'] }} zusammenführen? {{ $p['confidence']==='low' ? 'ACHTUNG: niedrige Konfidenz – evtl. verschiedene Personen! ' : '' }}Ein Backup wird automatisch erstellt.">
                                    Merge
                                </x-filament::button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">Keine Verdachtspaare gefunden.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif
        </div>
    </div>
</x-filament-panels::page>