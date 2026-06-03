import { EditorView, basicSetup } from 'codemirror';
import { StreamLanguage, HighlightStyle, syntaxHighlighting } from '@codemirror/language';
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

function wikitextEditor({ state }) {
    return {
        view: null,
        init() {
            this.view = new EditorView({
                doc: state ?? '',
                extensions: [
                    basicSetup,
                    EditorView.lineWrapping,
                    wikitextMode,
                    syntaxHighlighting(wikiHighlight),
                    wikiTheme,
                    EditorView.updateListener.of((u) => {
                        if (u.docChanged) { state = this.view.state.doc.toString(); }
                    }),
                ],
                parent: this.$refs.editor,
            });
            // extern -> Editor (z.B. Form-Reset)
            this.$watch('state', (value) => {
                const current = this.view.state.doc.toString();
                if ((value ?? '') !== current) {
                    this.view.dispatch({ changes: { from: 0, to: current.length, insert: value ?? '' } });
                }
            });
        },
        destroy() { this.view?.destroy(); },
    };
}

const register = () => window.Alpine.data('wikitextEditor', wikitextEditor);
if (window.Alpine) { register(); } else { document.addEventListener('alpine:init', register); }
