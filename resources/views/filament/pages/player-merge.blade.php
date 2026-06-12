<x-filament-panels::page>
    @php($pairs = $this->getSuspectPairs())

    <div class="space-y-4">
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
        </div>
    </div>
</x-filament-panels::page>
