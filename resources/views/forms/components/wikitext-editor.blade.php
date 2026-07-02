@php($statePath = $getStatePath())
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            ...wikitextEditor(@js((string)($getState() ?? '')), @js($statePath)),
            tableModalOpen: false,
            tableRows: 3,
            tableCols: 3,
            tableHeader: true,
        }"
        x-modelable="state"
        wire:model.live.debounce.800ms="{{ $statePath }}"
        wire:ignore
        style="border:1px solid rgba(255,255,255,0.1);border-radius:8px;background:rgba(0,0,0,0.2);overflow:hidden;"
    >
        {{-- ===== Toolbar ===== --}}
        <div class="wt-toolbar" role="toolbar" aria-label="Wikitext-Toolbar">
            <button type="button" class="wt-btn" title="Fett (Strg+B)" @click.prevent="cmdBold()">
                <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path d="M5 3h6.5a3.5 3.5 0 0 1 2.4 6 3.75 3.75 0 0 1-2.4 7.5H5V3zm2.5 2.5v3.5h3.5a1.75 1.75 0 0 0 0-3.5H7.5zm0 6v4h4a2 2 0 0 0 0-4h-4z"/></svg>
            </button>
            <button type="button" class="wt-btn" title="Kursiv (Strg+I)" @click.prevent="cmdItalic()">
                <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path d="M8 3h7v2h-2.5l-3 10H12v2H5v-2h2.5l3-10H8V3z"/></svg>
            </button>
            <span class="wt-sep"></span>

            <div class="wt-group" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" class="wt-btn" title="Überschrift" @click.prevent="open = !open">
                    <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path d="M3 3h2.5v6h6V3H14v14h-2.5v-6h-6v6H3V3z"/></svg>
                    <svg viewBox="0 0 12 12" width="10" height="10" fill="currentColor" style="margin-left:2px;"><path d="M2 4l4 4 4-4"/></svg>
                </button>
                <div x-show="open" x-transition class="wt-menu" @click="open = false">
                    <button type="button" @click.prevent="cmdHeading(1)" class="wt-menu-item"><strong style="font-size:18px;">H1</strong> &mdash; Haupttitel</button>
                    <button type="button" @click.prevent="cmdHeading(2)" class="wt-menu-item"><strong style="font-size:16px;">H2</strong> &mdash; Abschnitt</button>
                    <button type="button" @click.prevent="cmdHeading(3)" class="wt-menu-item"><strong style="font-size:14px;">H3</strong> &mdash; Unterabschnitt</button>
                    <button type="button" @click.prevent="cmdHeading(4)" class="wt-menu-item"><strong style="font-size:13px;">H4</strong> &mdash; Detail</button>
                </div>
            </div>

            <button type="button" class="wt-btn" title="Aufzählungsliste" @click.prevent="cmdList()">
                <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><circle cx="3" cy="5" r="1.3"/><circle cx="3" cy="10" r="1.3"/><circle cx="3" cy="15" r="1.3"/><rect x="6" y="4" width="12" height="2" rx="1"/><rect x="6" y="9" width="12" height="2" rx="1"/><rect x="6" y="14" width="12" height="2" rx="1"/></svg>
            </button>
            <button type="button" class="wt-btn" title="Nummerierte Liste" @click.prevent="cmdNumList()">
                <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><text x="0" y="7" font-size="6" font-weight="bold" fill="currentColor">1.</text><text x="0" y="13" font-size="6" font-weight="bold" fill="currentColor">2.</text><text x="0" y="19" font-size="6" font-weight="bold" fill="currentColor">3.</text><rect x="6" y="4" width="12" height="2" rx="1"/><rect x="6" y="9" width="12" height="2" rx="1"/><rect x="6" y="14" width="12" height="2" rx="1"/></svg>
            </button>
            <span class="wt-sep"></span>

            <button type="button" class="wt-btn" title="Wiki-Link (Strg+K)" @click.prevent="cmdInternalLink()">
                <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path d="M4 10a4 4 0 0 1 4-4h2v2H8a2 2 0 0 0 0 4h2v2H8a4 4 0 0 1-4-4zm6 0a2 2 0 0 1 2-2h2v-2h-2a4 4 0 1 0 0 8h2v-2h-2a2 2 0 0 1-2-2zm-2 0h4v-2H8v2z"/></svg>
            </button>
            <button type="button" class="wt-btn" title="Externer Link" @click.prevent="cmdExternalLink()">
                <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path d="M11 3v2h2.586l-7.293 7.293 1.414 1.414L15 6.414V9h2V3h-6zm-6 4v8h8v-5h-2v3H7V9h3V7H5z"/></svg>
            </button>
            <span class="wt-sep"></span>

            <button type="button" class="wt-btn" title="Tabelle einfügen" @click.prevent="tableModalOpen = true">
                <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path d="M3 4v12h14V4H3zm2 2h4v3H5V6zm0 5h4v3H5v-3zm6-5h4v3h-4V6zm0 5h4v3h-4v-3z"/></svg>
            </button>
            <button type="button" class="wt-btn" title="Code-Inline" @click.prevent="cmdCode()">
                <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path d="M6.5 4L1 10l5.5 6 1.5-1.4L4 10l4-4.6L6.5 4zm7 0L12 5.4 16 10l-4 4.6L13.5 16 19 10l-5.5-6z"/></svg>
            </button>
            <button type="button" class="wt-btn" title="Zitat" @click.prevent="cmdQuote()">
                <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path d="M5 4h3v6c0 2-1 3-3 3v2c3 0 5-2 5-5V4H5zm7 0h3v6c0 2-1 3-3 3v2c3 0 5-2 5-5V4h-5z"/></svg>
            </button>
            <button type="button" class="wt-btn" title="Horizontale Linie" @click.prevent="cmdHR()">
                <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><rect x="2" y="9" width="16" height="2" rx="1"/></svg>
            </button>
            <button type="button" class="wt-btn" title="Inhaltsverzeichnis (TOC) einfügen" @click.prevent="cmdTOC()">
                <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><rect x="3" y="4" width="2" height="2"/><rect x="3" y="9" width="2" height="2"/><rect x="3" y="14" width="2" height="2"/><rect x="7" y="4" width="10" height="2"/><rect x="7" y="9" width="10" height="2"/><rect x="7" y="14" width="10" height="2"/></svg>
            </button>
            <span class="wt-sep"></span>
            <div class="wt-group" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" class="wt-btn" title="Hinweis-Box einfügen" @click.prevent="open = !open">
                    <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path d="M10 2a6 6 0 0 0-3.5 10.9c.3.2.5.5.5.9V15h6v-1.2c0-.4.2-.7.5-.9A6 6 0 0 0 10 2zM8 17h4v1a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1v-1z"/></svg>
                    <svg viewBox="0 0 12 12" width="10" height="10" fill="currentColor" style="margin-left:2px;"><path d="M2 4l4 4 4-4"/></svg>
                </button>
                <div x-show="open" x-transition class="wt-menu" @click="open = false">
                    <button type="button" @click.prevent="cmdCallout('info')"    class="wt-menu-item"><span style="display:inline-block;width:10px;height:10px;background:#3b82f6;border-radius:2px;margin-right:8px;vertical-align:middle;"></span>Info (blau)</button>
                    <button type="button" @click.prevent="cmdCallout('hinweis')" class="wt-menu-item"><span style="display:inline-block;width:10px;height:10px;background:#fbbf24;border-radius:2px;margin-right:8px;vertical-align:middle;"></span>Hinweis (gelb)</button>
                    <button type="button" @click.prevent="cmdCallout('warnung')" class="wt-menu-item"><span style="display:inline-block;width:10px;height:10px;background:#f97316;border-radius:2px;margin-right:8px;vertical-align:middle;"></span>Warnung (orange)</button>
                    <button type="button" @click.prevent="cmdCallout('achtung')" class="wt-menu-item"><span style="display:inline-block;width:10px;height:10px;background:#ef4444;border-radius:2px;margin-right:8px;vertical-align:middle;"></span>Achtung (rot)</button>
                </div>
            </div>
        </div>

        {{-- ===== CodeMirror Editor ===== --}}
        <div x-ref="editor" style="min-height:360px;max-height:70vh;overflow:auto;"></div>

        {{-- ===== Tabellen-Modal ===== --}}
        <div x-show="tableModalOpen" x-transition.opacity
             style="position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; display:flex; align-items:center; justify-content:center;"
             @click.self="tableModalOpen = false"
             @keydown.escape.window="tableModalOpen = false">
            <div style="background:#1f2937; border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:1.5rem; min-width:320px; max-width:400px;">
                <h3 style="color:#f3f4f6; font-size:16px; font-weight:600; margin:0 0 1rem;">Tabelle einfügen</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.75rem; margin-bottom:1rem;">
                    <label style="color:#d1d5db; font-size:13px;">
                        Zeilen
                        <input type="number" x-model.number="tableRows" min="1" max="20"
                               style="width:100%; padding:0.5rem; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); border-radius:4px; color:#f3f4f6; margin-top:0.25rem;">
                    </label>
                    <label style="color:#d1d5db; font-size:13px;">
                        Spalten
                        <input type="number" x-model.number="tableCols" min="1" max="10"
                               style="width:100%; padding:0.5rem; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); border-radius:4px; color:#f3f4f6; margin-top:0.25rem;">
                    </label>
                </div>
                <label style="color:#d1d5db; font-size:13px; display:flex; align-items:center; gap:0.5rem; margin-bottom:1.25rem;">
                    <input type="checkbox" x-model="tableHeader">
                    Mit Header-Zeile (Spaltentitel)
                </label>
                <div style="display:flex; gap:0.5rem; justify-content:flex-end;">
                    <button type="button" @click.prevent="tableModalOpen = false"
                            style="padding:0.5rem 1rem; background:transparent; border:1px solid rgba(255,255,255,0.2); color:#d1d5db; border-radius:4px; cursor:pointer;">
                        Abbrechen
                    </button>
                    <button type="button" @click.prevent="cmdTable(tableRows, tableCols, tableHeader); tableModalOpen = false;"
                            style="padding:0.5rem 1rem; background:#f59e0b; color:#000; font-weight:600; border:none; border-radius:4px; cursor:pointer;">
                        Einfügen
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Toolbar-Styles ===== --}}
    <style>
        .wt-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:2px; padding:6px 8px; background:rgba(0,0,0,0.35); border-bottom:1px solid rgba(255,255,255,0.08); }
        .wt-btn { display:inline-flex; align-items:center; justify-content:center; min-width:30px; height:30px; padding:0 6px; background:transparent; border:1px solid transparent; border-radius:4px; color:#cbd5e1; cursor:pointer; transition:all .15s; }
        .wt-btn:hover { background:rgba(251,191,36,0.15); border-color:rgba(251,191,36,0.3); color:#fbbf24; }
        .wt-btn:active { transform:translateY(1px); }
        .wt-sep { width:1px; height:20px; background:rgba(255,255,255,0.1); margin:0 4px; }
        .wt-group { position:relative; }
        .wt-menu { position:absolute; top:100%; left:0; margin-top:2px; background:#1f2937; border:1px solid rgba(255,255,255,0.1); border-radius:4px; min-width:180px; z-index:50; box-shadow:0 4px 12px rgba(0,0,0,0.4); padding:4px 0; }
        .wt-menu-item { display:block; width:100%; padding:6px 10px; background:transparent; border:none; color:#d1d5db; font-size:13px; text-align:left; cursor:pointer; }
        .wt-menu-item:hover { background:rgba(251,191,36,0.15); color:#fbbf24; }
    </style>
</x-dynamic-component>
