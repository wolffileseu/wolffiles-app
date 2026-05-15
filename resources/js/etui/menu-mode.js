/**
 * CodeMirror 6 StreamLanguage definition for the id Tech 3 .menu DSL.
 * Phase 3 scope: syntax highlighting only. Lezer-based grammar (with
 * code folding, auto-complete, indentation) is Phase 5.
 */
import { StreamLanguage } from '@codemirror/language';

const KEYWORDS = new Set([
    'menuDef', 'itemDef', 'assetGlobalDef',
    'name', 'rect', 'text', 'textfont', 'textscale', 'textalign',
    'textalignx', 'textaligny', 'textstyle',
    'forecolor', 'backcolor', 'bordercolor', 'border',
    'style', 'visible', 'fullscreen', 'popup', 'decoration',
    'autowrapped', 'wrapped', 'group', 'type', 'cvar', 'cvarTest',
    'background', 'foreground', 'maxChars', 'tooltip',
    'onOpen', 'onClose', 'onESC', 'onEsc', 'action', 'mouseEnter', 'mouseExit',
    'fadeClamp', 'fadeAmount', 'fadeCycle',
    'origin', 'ownerdraw', 'ownerdrawFlag', 'feeder', 'columns', 'elementheight',
]);

const MACROS = new Set([
    'WINDOW', 'WINDOW_FUI', 'WINDOW_INGAME',
    'SUBWINDOW', 'SUBWINDOWBLACK',
    'BUTTON', 'BUTTONEXT', 'NAMEDBUTTON', 'NAMEDBUTTONEXT',
    'LABEL', 'LABELWHITE', 'CVARLABEL', 'CVARFLOATLABEL', 'CVARINTLABEL',
    'EDITFIELD', 'EDITFIELDLEFT', 'NUMERICFIELD', 'NUMERICFIELDLEFTEXT',
    'YESNO', 'YESNOALIGNX', 'YESNOACTION',
    'CHECKBOX', 'CHECKBOXACTION', 'CHECKBOXALIGNX', 'CHECKBOXALIGNXACTION',
    'CHECKBOXNOTEXT', 'CHECKBOXNOTEXTACTION', 'TRICHECKBOXACTION',
    'MULTI', 'MULTILEFT', 'MULTIACTION', 'MULTIACTIONLEFT',
    'SLIDER', 'BIND',
]);

const CONSTANT_PREFIX = /^(ITEM_ALIGN_|WINDOW_STYLE_|WINDOW_BORDER_|ITEM_TYPE_|UI_FONT_|ITEM_TEXTSTYLE_)/;

export const menuMode = StreamLanguage.define({
    startState() {
        return { inBlockComment: false };
    },

    token(stream, state) {
        // Multi-line block comment continues across lines via state.
        if (state.inBlockComment) {
            if (stream.match(/[\s\S]*?\*\//)) {
                state.inBlockComment = false;
                return 'comment';
            }
            stream.skipToEnd();
            return 'comment';
        }

        if (stream.eatSpace()) return null;

        // Comments
        if (stream.match('//')) {
            stream.skipToEnd();
            return 'comment';
        }
        if (stream.match('/*')) {
            state.inBlockComment = true;
            if (stream.match(/[\s\S]*?\*\//)) {
                state.inBlockComment = false;
            } else {
                stream.skipToEnd();
            }
            return 'comment';
        }

        // Preprocessor directives + id-Tech-3 eval directives
        if (stream.match(/^#\w+/)) return 'meta';
        if (stream.match(/^\$evalint|^\$evalfloat/)) return 'macroName';

        // Strings — no escape handling, .menu strings are simple
        if (stream.match(/^"[^"]*"/)) return 'string';

        // i18n wrapper _("...") — show the underscore as meta
        if (stream.match(/^_(?=\()/)) return 'macroName';

        // Numbers (incl. leading-dot floats and unary minus)
        if (stream.match(/^-?\.?\d[\d.]*/)) return 'number';

        // Punctuation that's lexically significant
        if (stream.match(/^[{}()]/)) return 'bracket';
        if (stream.match(/^[,;]/)) return 'punctuation';
        if (stream.match(/^[+\-*\/]/)) return 'operator';

        // Identifier — keyword / macro / constant / other
        const idMatch = stream.match(/^[A-Za-z_]\w*/);
        if (idMatch) {
            const w = idMatch[0];
            if (KEYWORDS.has(w)) return 'keyword';
            if (MACROS.has(w)) return 'macroName';
            if (CONSTANT_PREFIX.test(w)) return 'atom';
            return 'variableName';
        }

        // Unknown char — eat and don't highlight
        stream.next();
        return null;
    },

    languageData: {
        commentTokens: { line: '//', block: { open: '/*', close: '*/' } },
    },
});
