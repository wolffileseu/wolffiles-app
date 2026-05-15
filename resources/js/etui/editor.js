/**
 * ETUI editor entry — CodeMirror 6 on the left, live AST renderer on the
 * right. Wired up via Alpine.js for state bookkeeping (status text,
 * project name, auto-save indicator).
 *
 * Live-parse: 300ms debounce → POST /api/etui/parse → renderMenu.
 * Auto-save:  5s debounce, only for existing projects, → POST /api/etui/autosave.
 */
import { EditorView, lineNumbers, drawSelection, highlightActiveLine, keymap } from '@codemirror/view';
import { EditorState } from '@codemirror/state';
import { defaultKeymap, history, historyKeymap } from '@codemirror/commands';
import { searchKeymap } from '@codemirror/search';
import { syntaxHighlighting, defaultHighlightStyle } from '@codemirror/language';
import { oneDark } from '@codemirror/theme-one-dark';
import axios from 'axios';
import { menuMode } from './menu-mode.js';
import { renderMenu } from './renderer.js';

/** Tiny debounce — local impl, not worth a lodash dep. */
function debounce(fn, ms) {
    let t = null;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), ms);
    };
}

function bootEtuiEditor(opts) {
    const editorMount = document.getElementById('etui-editor');
    const preview = document.getElementById('etui-preview');
    const statusBar = document.getElementById('etui-status');
    const saveStatus = document.getElementById('etui-save-status');

    if (!editorMount || !preview) return;

    // Cross-Site-Request-Forgery token for fetch — meta in the layout.
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf;
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    const setStatus = (text, cls) => {
        if (!statusBar) return;
        statusBar.textContent = text;
        statusBar.className = 'status ' + (cls || '');
    };

    const setSaveStatus = (text, cls) => {
        if (!saveStatus) return;
        saveStatus.textContent = text;
        saveStatus.className = 'status ' + (cls || '');
    };

    const debouncedParse = debounce(async (source) => {
        try {
            const res = await axios.post('/api/etui/parse', {
                source,
                mod: opts.modSlug,
            });
            renderMenu(res.data, preview);
            const errCount = (res.data.errors || []).filter(e => e.severity === 'error').length;
            if (errCount === 0) {
                setStatus('✓ parsed cleanly · ' + (res.data.menus?.length || 0) + ' menu(s)', 'status-ok');
            } else {
                const first = res.data.errors[0];
                setStatus(`✗ ${errCount} error${errCount === 1 ? '' : 's'} · L${first.line}: ${first.message}`, 'status-err');
            }
        } catch (e) {
            setStatus('parse request failed', 'status-err');
        }
    }, 300);

    const debouncedAutosave = debounce(async (source) => {
        if (!opts.projectId) return;
        setSaveStatus('saving...', 'status-saving');
        try {
            const res = await axios.post('/api/etui/autosave', {
                project_id: opts.projectId,
                content: source,
            });
            const at = res.data.saved_at
                ? new Date(res.data.saved_at).toLocaleTimeString()
                : 'just now';
            setSaveStatus('saved at ' + at, 'status-ok');
        } catch (e) {
            setSaveStatus('save failed', 'status-err');
        }
    }, 5000);

    const initialContent = opts.initialContent || '';

    const view = new EditorView({
        state: EditorState.create({
            doc: initialContent,
            extensions: [
                lineNumbers(),
                drawSelection(),
                highlightActiveLine(),
                history(),
                keymap.of([...defaultKeymap, ...historyKeymap, ...searchKeymap]),
                menuMode,
                syntaxHighlighting(defaultHighlightStyle),
                oneDark,
                EditorView.updateListener.of((u) => {
                    if (u.docChanged) {
                        const text = u.state.doc.toString();
                        debouncedParse(text);
                        debouncedAutosave(text);
                    }
                }),
                EditorView.theme({
                    '&': { height: '100%', fontSize: '13px' },
                    '.cm-scroller': { fontFamily: 'ui-monospace, Menlo, monospace' },
                }),
            ],
        }),
        parent: editorMount,
    });

    // Fire the first parse + render after mount so the preview shows
    // something immediately instead of waiting for the first keystroke.
    debouncedParse(initialContent);

    // Expose to a global so the Save button / Alpine state can read content.
    window.etuiEditor = {
        view,
        getContent: () => view.state.doc.toString(),
    };
}

// Auto-boot when the editor mount exists. opts come from <script
// type="application/json" id="etui-opts"> in the Blade view.
document.addEventListener('DOMContentLoaded', () => {
    const optsEl = document.getElementById('etui-opts');
    if (!optsEl) return;
    try {
        const opts = JSON.parse(optsEl.textContent || '{}');
        bootEtuiEditor(opts);
    } catch (e) {
        console.error('etui editor failed to boot', e);
    }
});
