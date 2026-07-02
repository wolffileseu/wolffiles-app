import { EditorView, basicSetup } from 'codemirror';
import { StreamLanguage, HighlightStyle, syntaxHighlighting } from '@codemirror/language';
import { keymap } from '@codemirror/view';
import { tags as t } from '@lezer/highlight';

// --- Leichtgewichtiger Wikitext-Modus ---
const wikitextMode = StreamLanguage.define({
    startState: () => ({}),
    token(stream) {
        if (stream.sol()) {
            if (stream.match(/={1,6}[^=].*?={1,6}\s*$/)) return 'heading';
            if (stream.match(/[*#:;]+/)) return 'list';
            if (stream.match(/----+/)) return 'hr';
        }
        if (stream.match("'''''")) return 'strong';
        if (stream.match("'''")) return 'strong';
        if (stream.match("''")) return 'emphasis';
        if (stream.match(/\[\[(?:File|Datei|Bild):/i)) { stream.match(/[^\]]*\]\]/); return 'image'; }
        if (stream.match(/\[\[/)) { stream.match(/[^\]]*\]\]/); return 'link'; }
        if (stream.match(/\[https?:\/\//i)) { stream.match(/[^\]]*\]/); return 'urllink'; }
        if (stream.match(/\{\{/)) { stream.match(/[^}]*\}\}/); return 'template'; }
        if (stream.match(/<\/?(?:ref|nowiki|code|pre)[^>]*>/i)) return 'tag';
        stream.next();
        return null;
    },
    tokenTable: {
        heading: t.heading, list: t.list, hr: t.contentSeparator,
        strong: t.strong, emphasis: t.emphasis,
        image: t.special(t.link), link: t.link, urllink: t.url,
        template: t.meta, tag: t.tagName,
    },
});

const wikiHighlight = HighlightStyle.define([
    { tag: t.heading, color: '#fbbf24', fontWeight: '700' },
    { tag: t.strong, fontWeight: '700', color: '#f3f4f6' },
    { tag: t.emphasis, fontStyle: 'italic', color: '#f3f4f6' },
    { tag: t.link, color: '#60a5fa' },
    { tag: t.special(t.link), color: '#34d399' },
    { tag: t.url, color: '#38bdf8' },
    { tag: t.meta, color: '#a78bfa' },
    { tag: t.tagName, color: '#f472b6' },
    { tag: t.list, color: '#9ca3af' },
    { tag: t.contentSeparator, color: '#6b7280' },
]);

const wikiTheme = EditorView.theme({
    '&': { fontSize: '14px', backgroundColor: 'transparent', color: '#e5e7eb' },
    '.cm-content': { fontFamily: 'ui-monospace, SFMono-Regular, Menlo, monospace', padding: '12px 0' },
    '.cm-gutters': { backgroundColor: 'transparent', color: '#4b5563', border: 'none' },
    '.cm-activeLine': { backgroundColor: 'rgba(255,255,255,0.03)' },
    '.cm-activeLineGutter': { backgroundColor: 'transparent' },
    '&.cm-focused': { outline: 'none' },
    '.cm-scroller': { lineHeight: '1.6' },
}, { dark: true });

function wikitextEditor(initial, statePath) {
    return {
        view: null,
        state: typeof initial === 'string' ? initial : '',
        statePath: typeof statePath === 'string' ? statePath : null,
        _t: null,
        _syncing: false,
        init() {
            const self = this;
            this.view = new EditorView({
                doc: this.state,
                extensions: [
                    basicSetup,
                    EditorView.lineWrapping,
                    wikitextMode,
                    syntaxHighlighting(wikiHighlight),
                    wikiTheme,
                    keymap.of([
                        { key: 'Mod-b', run: () => { self.cmdBold();   return true; } },
                        { key: 'Mod-i', run: () => { self.cmdItalic(); return true; } },
                        { key: 'Mod-k', run: () => { self.cmdInternalLink(); return true; } },
                    ]),
                    EditorView.updateListener.of((u) => {
                        if (!u.docChanged) return;
                        const val = self.view.state.doc.toString();
                        self._syncing = true;
                        self.state = val;
                        self._syncing = false;
                        clearTimeout(self._t);
                        self._t = setTimeout(() => {
                            if (self.statePath) {
                                try { self.$wire?.set(self.statePath, val, false); } catch (e) {}
                            }
                            try { self.$wire?.$refresh?.(); } catch (e) {}
                        }, 800);
                    }),
                ],
                parent: this.$refs.editor,
            });
            // Extern -> Editor (Form-Reset, Livewire-Roundtrip)
            this.$watch('state', (value) => {
                if (self._syncing) return;
                const next = typeof value === 'string' ? value : '';
                const current = self.view.state.doc.toString();
                if (next !== current) {
                    self.view.dispatch({ changes: { from: 0, to: current.length, insert: next } });
                }
            });
            // Globaler Event-Bridge: andere Komponenten (Filament-Modal) koennen
            // einen Snippet via Event dispatchen.  detail = { snippet: '[[File:...]]' }
            this._onInsert = (e) => {
                if (e?.detail?.snippet) { self.insertSnippet(e.detail.snippet); }
            };
            window.addEventListener('wikitext-insert', this._onInsert);
        },
        // ===== Toolbar-Helpers =====
        _wrapSelection(prefix, suffix, placeholder = '') {
            if (!this.view) return;
            const sel = this.view.state.selection.main;
            const selected = this.view.state.doc.sliceString(sel.from, sel.to);
            const text = selected || placeholder;
            const insert = prefix + text + suffix;
            this.view.dispatch({
                changes: { from: sel.from, to: sel.to, insert },
                selection: selected
                    ? { anchor: sel.from + insert.length }
                    : { anchor: sel.from + prefix.length, head: sel.from + prefix.length + text.length },
                scrollIntoView: true,
            });
            this.view.focus();
            this._syncAfterEdit();
        },
        _insertAtCursor(text) {
            if (!this.view) return;
            const sel = this.view.state.selection.main;
            this.view.dispatch({
                changes: { from: sel.from, to: sel.to, insert: text },
                selection: { anchor: sel.from + text.length },
                scrollIntoView: true,
            });
            this.view.focus();
            this._syncAfterEdit();
        },
        _insertLineStart(prefix) {
            if (!this.view) return;
            const sel = this.view.state.selection.main;
            const line = this.view.state.doc.lineAt(sel.from);
            this.view.dispatch({
                changes: { from: line.from, to: line.from, insert: prefix },
                selection: { anchor: sel.from + prefix.length },
                scrollIntoView: true,
            });
            this.view.focus();
            this._syncAfterEdit();
        },
        _syncAfterEdit() {
            const val = this.view.state.doc.toString();
            this._syncing = true;
            this.state = val;
            this._syncing = false;
            clearTimeout(this._t);
            this._t = setTimeout(() => {
                if (this.statePath) {
                    try { this.$wire?.set(this.statePath, val, false); } catch (e) {}
                }
                try { this.$wire?.$refresh?.(); } catch (e) {}
            }, 300);
        },

        // ===== Toolbar-Aktionen =====
        cmdBold()       { this._wrapSelection("'''", "'''", 'fetter Text'); },
        cmdItalic()     { this._wrapSelection("''",  "''",  'kursiver Text'); },
        cmdCode()       { this._wrapSelection('<code>', '</code>', 'code'); },
        cmdQuote()      { this._insertLineStart('> '); },
        cmdHR()         { this._insertAtCursor('\n----\n'); },
        cmdTOC()        { this._insertAtCursor('\n__TOC__\n'); },
        cmdCallout(type) {
            const map = { info: 'Info', hinweis: 'Hinweis', warnung: 'Warnung', achtung: 'Achtung' };
            const label = map[type] || 'Hinweis';
            this._insertAtCursor('\n{{' + label + '|Dein Hinweistext hier}}\n');
        },
        cmdHeading(level) {
            const eq = '='.repeat(level);
            this._insertLineStart(eq + ' ');
            // Suffix muss am Zeilenende dazu - zweiter Dispatch
            const sel = this.view.state.selection.main;
            const line = this.view.state.doc.lineAt(sel.from);
            this.view.dispatch({
                changes: { from: line.to, to: line.to, insert: ' ' + eq },
            });
            this._syncAfterEdit();
        },
        cmdList()       { this._insertLineStart('* '); },
        cmdNumList()    { this._insertLineStart('# '); },
        cmdInternalLink() {
            const sel = this.view.state.selection.main;
            const selected = this.view.state.doc.sliceString(sel.from, sel.to);
            if (selected) {
                this._wrapSelection('[[', ']]');
            } else {
                const target = prompt('Wiki-Seite (Slug oder Titel):');
                if (target) {
                    const label = prompt('Linktext (leer = Slug verwenden):', '');
                    const snippet = label ? ('[[' + target + '|' + label + ']]') : ('[[' + target + ']]');
                    this._insertAtCursor(snippet);
                }
            }
        },
        cmdExternalLink() {
            const url = prompt('URL (https://...):', 'https://');
            if (!url || url === 'https://') return;
            const label = prompt('Linktext (leer = URL anzeigen):', '');
            const snippet = label ? ('[' + url + ' ' + label + ']') : ('[' + url + ']');
            this._insertAtCursor(snippet);
        },
        cmdTable(rows, cols, withHeader) {
            const r = Math.max(1, Math.min(20, parseInt(rows) || 3));
            const c = Math.max(1, Math.min(10, parseInt(cols) || 3));
            const lines = ['{| class="wikitable"'];
            if (withHeader) {
                const headers = Array.from({length: c}, (_, i) => 'Header ' + (i+1)).join(' !! ');
                lines.push('|-', '! ' + headers);
            }
            for (let i = 0; i < r; i++) {
                const cells = Array.from({length: c}, (_, j) => 'Zelle ' + (i+1) + '-' + (j+1)).join(' || ');
                lines.push('|-', '| ' + cells);
            }
            lines.push('|}');
            this._insertAtCursor('\n' + lines.join('\n') + '\n');
        },

        insertSnippet(snippet) {
            if (!this.view || typeof snippet !== 'string') return;
            const sel = this.view.state.selection.main;
            // Wenn am Zeilen-Start eingefuegt wird ist alles ok;
            // sonst sicherstellen dass das Snippet auf eigener Zeile landet (saubere Block-Wirkung)
            const docText = this.view.state.doc.toString();
            const before  = sel.from > 0 ? docText[sel.from - 1] : '\n';
            const after   = sel.to < docText.length ? docText[sel.to] : '\n';
            const prefix  = (before === '\n' || before === '') ? '' : '\n';
            const suffix  = (after  === '\n' || after  === '') ? '' : '\n';
            const insert  = prefix + snippet + suffix;
            this.view.dispatch({
                changes: { from: sel.from, to: sel.to, insert },
                selection: { anchor: sel.from + insert.length },
                scrollIntoView: true,
            });
            this.view.focus();
            // Livewire-Sync triggern (sonst wird Server-State nicht aktualisiert bis user nochmal tippt)
            const val = this.view.state.doc.toString();
            this._syncing = true;
            this.state = val;
            this._syncing = false;
            if (this.statePath) {
                try { this.$wire?.set(this.statePath, val, false); } catch (e) {}
            }
            try { this.$wire?.$refresh?.(); } catch (e) {}
        },
        destroy() {
            window.removeEventListener('wikitext-insert', this._onInsert);
            this.view?.destroy();
        },
    };
}

const register = () => window.Alpine.data('wikitextEditor', wikitextEditor);
if (window.Alpine) { register(); } else { document.addEventListener('alpine:init', register); }
