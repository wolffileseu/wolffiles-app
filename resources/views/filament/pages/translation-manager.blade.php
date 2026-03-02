<x-filament-panels::page>
    @php
        $languages = $this->getLanguages();
        $stats = $this->getLanguageStats();
        $rows = $this->getTranslationRows();
        $langNames = [
            'de' => '🇩🇪 Deutsch', 'fr' => '🇫🇷 Français', 'es' => '🇪🇸 Español',
            'nl' => '🇳🇱 Nederlands', 'pl' => '🇵🇱 Polski', 'it' => '🇮🇹 Italiano',
            'pt' => '🇵🇹 Português', 'ru' => '🇷🇺 Русский', 'ja' => '🇯🇵 日本語',
            'zh' => '🇨🇳 中文', 'ko' => '🇰🇷 한국어', 'sv' => '🇸🇪 Svenska',
            'fi' => '🇫🇮 Suomi', 'da' => '🇩🇰 Dansk', 'no' => '🇳🇴 Norsk',
            'tr' => '🇹🇷 Türkçe', 'hu' => '🇭🇺 Magyar', 'cs' => '🇨🇿 Čeština',
            'ro' => '🇷🇴 Română', 'bg' => '🇧🇬 Български', 'hr' => '🇭🇷 Hrvatski',
        ];
    @endphp

    {{-- Language Overview --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-6">
        @foreach($stats as $lang => $s)
        <button wire:click="$set('selectedLang', '{{ $lang }}')"
            class="rounded-xl p-4 text-left transition {{ $selectedLang === $lang ? 'bg-primary-500/20 border-2 border-primary-500' : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-primary-400' }}">
            <div class="font-bold text-lg">{{ $langNames[$lang] ?? strtoupper($lang) }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ $s['translated'] }}/{{ $s['total'] }} translated</div>
            <div style="width:100%;background:#374151;border-radius:9999px;height:6px;margin-top:8px;overflow:hidden;">
                <div style="width:{{ $s['percent'] }}%;height:100%;border-radius:9999px;background:{{ $s['percent'] === 100 ? '#22c55e' : ($s['percent'] > 50 ? '#f59e0b' : '#ef4444') }};"></div>
            </div>
            <div class="text-xs mt-1 {{ $s['percent'] === 100 ? 'text-green-500' : 'text-gray-500' }}">{{ $s['percent'] }}%</div>
        </button>
        @endforeach

        {{-- Add Language --}}
        <div class="rounded-xl p-4 bg-white dark:bg-gray-800 border border-dashed border-gray-300 dark:border-gray-600">
            <div class="text-sm font-medium text-gray-500 mb-2">Add Language</div>
            <div class="flex gap-2">
                <input type="text" wire:model="newLangCode" placeholder="e.g. it"
                    class="w-16 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded px-2 py-1 text-sm" maxlength="3">
                <button wire:click="addLanguage" class="px-3 py-1 bg-primary-500 text-white rounded text-sm hover:bg-primary-600">+</button>
            </div>
        </div>
    </div>

    {{-- Actions Bar --}}
    <div class="flex flex-wrap items-center gap-3 mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search keys..."
            class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 text-sm w-64">

        <select wire:model.live="filter" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm">
            <option value="all">All Keys</option>
            <option value="missing">Missing / TODO</option>
            <option value="translated">Translated</option>
        </select>

        <button wire:click="syncAll" class="px-4 py-2 bg-amber-500 text-white rounded-lg text-sm hover:bg-amber-600">
            🔄 Sync All Languages
        </button>

        @if($selectedLang)
        <a href="#" wire:click.prevent="exportCsv('{{ $selectedLang }}')"
            class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">
            📥 Export CSV
        </a>
        <a href="#" wire:click.prevent="exportJson('{{ $selectedLang }}')"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
            📥 Export JSON
        </a>

        {{-- Import --}}
        <div class="flex items-center gap-2 flex-wrap" x-data="{ csvName: '', jsonName: '' }">
            <label class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm hover:bg-purple-700 cursor-pointer">
                📤 Import CSV
                <input type="file" wire:model="importFile" accept=".csv" class="hidden"
                    @change="csvName = $event.target.files[0]?.name || ''">
            </label>
            <template x-if="csvName">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400" x-text="csvName"></span>
                    @if($importFile)
                    <button wire:click="importCsv" class="px-3 py-1.5 bg-purple-800 text-white rounded-lg text-xs hover:bg-purple-900">✓ CSV Import</button>
                    @endif
                </div>
            </template>

            <label class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 cursor-pointer">
                📤 Import JSON
                <input type="file" wire:model="importJsonFile" accept=".json" class="hidden"
                    @change="jsonName = $event.target.files[0]?.name || ''">
            </label>
            <span class="text-xs text-gray-400" x-show="jsonName" x-text="jsonName"></span>
            <span class="text-xs text-yellow-400" wire:loading wire:target="importJsonFile">⏳ Uploading...</span>
            @if($importJsonFile)
                <button wire:click="importJson"
                    class="px-3 py-1.5 bg-indigo-800 text-white rounded-lg text-xs hover:bg-indigo-900 animate-pulse">
                    ✓ JSON Import
                </button>
            @endif
        </div>
        @endif

        @if($selectedLang && $selectedLang !== 'de')
        <button wire:click="deleteLanguage('{{ $selectedLang }}')" wire:confirm="Are you sure you want to delete this language?"
            class="px-4 py-2 bg-red-500 text-white rounded-lg text-sm hover:bg-red-600 ml-auto">
            🗑️ Delete {{ strtoupper($selectedLang) }}
        </button>
        @endif
    </div>

    {{-- Translation Table --}}
    @if($selectedLang)
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 flex justify-between">
            <span class="font-semibold">{{ $langNames[$selectedLang] ?? strtoupper($selectedLang) }} — {{ count($rows) }} keys</span>
            <span class="text-sm text-gray-500">🇬🇧 English → {{ $langNames[$selectedLang] ?? strtoupper($selectedLang) }}</span>
        </div>

        <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-[70vh] overflow-y-auto">
            @forelse($rows as $key => $row)
            <div class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50" x-data="{ editing: false }">
                <div class="flex items-start gap-3">
                    {{-- Status --}}
                    <div class="mt-1">
                        @if($row['status'] === 'translated')
                            <span class="text-green-500 text-lg">✅</span>
                        @elseif($row['status'] === 'todo')
                            <span class="text-amber-500 text-lg">⚠️</span>
                        @else
                            <span class="text-red-500 text-lg">❌</span>
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-mono text-gray-400 mb-1">{{ $row['key'] }}</div>
                        <div class="text-sm text-gray-500 mb-2">🇬🇧 {{ Str::limit($row['en'], 120) }}</div>

                        {{-- Edit field --}}
                        <div x-show="!editing" @click="editing = true" class="cursor-pointer">
                            @if($row['value'])
                                <div class="text-sm {{ $row['status'] === 'todo' ? 'text-amber-400 italic' : 'text-white' }}">
                                    {{ Str::limit($row['value'], 120) }}
                                </div>
                            @else
                                <div class="text-sm text-red-400 italic">Click to translate...</div>
                            @endif
                        </div>

                        <div x-show="editing" x-cloak class="flex gap-2">
                            <input type="text"
                                x-ref="input_{{ str_replace('.', '_', $row['key']) }}"
                                x-init="$watch('editing', v => { if(v) $nextTick(() => $refs.input_{{ str_replace('.', '_', $row['key']) }}.focus()) })"
                                value="{{ $row['value'] }}"
                                @keydown.enter="$wire.saveTranslation('{{ $row['key'] }}', $el.value); editing = false"
                                @keydown.escape="editing = false"
                                class="flex-1 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded px-3 py-1 text-sm">
                            <button @click="$wire.saveTranslation('{{ $row['key'] }}', $refs.input_{{ str_replace('.', '_', $row['key']) }}.value); editing = false"
                                class="px-3 py-1 bg-green-500 text-white rounded text-sm">✓</button>
                            <button @click="editing = false" class="px-3 py-1 bg-gray-500 text-white rounded text-sm">✕</button>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="px-4 py-8 text-center text-gray-500">
                No translations found matching your filter.
            </div>
            @endforelse
        </div>
    </div>
    @endif
</x-filament-panels::page>
