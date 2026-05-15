/**
 * Pure DOM renderer for an ETUI AST. Public viewer + editor preview both
 * call renderMenu(ast, container).
 *
 * The 640x480 reference frame from id Tech 3 maps 1:1 to absolute-positioned
 * children of the container. CSS scales the whole canvas afterwards.
 *
 * PHASE 5 TODO: replace the cheap "first N NUMBER tokens" extractor in
 * propNumbers() with a real expression evaluator that survives macro-
 * substituted IDENTs and arithmetic. Today this nails ~80% of fixtures
 * (rect/color literals) and degrades silently otherwise.
 */

const TEXT_ALIGN = { 0: 'left', 1: 'center', 2: 'right' };

export function renderMenu(ast, container) {
    container.innerHTML = '';
    if (!ast || !ast.menus) return;

    for (const menu of ast.menus) {
        renderNode(menu, container);
    }
    fitCanvas(container);
}

function renderNode(node, parent) {
    if (node.kind === 'menu') {
        for (const c of node.children || []) renderNode(c, parent);
        return;
    }
    if (node.kind === 'item') {
        renderItem(node, parent);
        return;
    }
    // properties at menu level, events, macros — not rendered.
}

function renderItem(item, parent) {
    const el = document.createElement('div');
    el.className = 'etui-item';

    const props = collectProperties(item);

    // visibility — `visible 0` hides the item
    if (props.visible && Number(propFirstNumber(props.visible)) === 0) {
        el.style.display = 'none';
    }

    // rect X Y W H — absolute positioning in the 640x480 canvas
    const rect = propNumbers(props.rect, 4);
    if (rect) {
        el.style.left = rect[0] + 'px';
        el.style.top = rect[1] + 'px';
        el.style.width = rect[2] + 'px';
        el.style.height = rect[3] + 'px';
    }

    // forecolor / backcolor / bordercolor R G B A floats 0-1
    if (props.forecolor) el.style.color = quakeColor(props.forecolor);
    if (props.backcolor) el.style.backgroundColor = quakeColor(props.backcolor);

    if (props.bordercolor) {
        el.classList.add('has-border');
        el.style.borderColor = quakeColor(props.bordercolor);
    }
    const borderW = propNumbers(props.border, 1);
    if (borderW && borderW[0] > 0) {
        el.classList.add('has-border');
        el.style.borderWidth = borderW[0] + 'px';
    }

    // style WINDOW_STYLE_FILLED / WINDOW_STYLE_GRADIENT — used by CSS [data-style]
    if (props.style) {
        const styleId = propIdent(props.style);
        if (styleId === 'WINDOW_STYLE_FILLED') el.dataset.style = 'filled';
        else if (styleId === 'WINDOW_STYLE_GRADIENT') el.dataset.style = 'gradient';
    }

    // text content + alignment + scale
    const textValue = propString(props.text);
    if (textValue !== null) {
        const inner = document.createElement('div');
        inner.className = 'etui-text';
        inner.textContent = textValue;
        const alignIdent = propIdent(props.textalign);
        if (alignIdent === 'ITEM_ALIGN_CENTER') inner.style.justifyContent = 'center';
        else if (alignIdent === 'ITEM_ALIGN_RIGHT') inner.style.justifyContent = 'flex-end';
        const alignNumber = propNumbers(props.textalign, 1);
        if (alignNumber) {
            inner.style.justifyContent = TEXT_ALIGN[alignNumber[0]] === 'center' ? 'center'
                : TEXT_ALIGN[alignNumber[0]] === 'right' ? 'flex-end' : 'flex-start';
        }
        const scale = propNumbers(props.textscale, 1);
        if (scale) inner.style.fontSize = Math.max(8, scale[0] * 32) + 'px';
        el.appendChild(inner);
    }

    parent.appendChild(el);
}

function collectProperties(node) {
    const out = {};
    for (const c of node.children || []) {
        if (c.kind === 'property') out[c.name] = c;
    }
    return out;
}

/**
 * Cheap-and-cheerful expression eval: grab the first `n` NUMBER tokens
 * out of a property's value list. Misses arithmetic like `WINDOW_WIDTH-12`,
 * which Phase 5's AST-level evaluator will fix.
 */
function propNumbers(prop, n) {
    if (!prop) return null;
    const nums = (prop.values || [])
        .filter(t => t.type === 'NUMBER')
        .slice(0, n)
        .map(t => Number(t.value));
    return nums.length === n ? nums : null;
}

function propFirstNumber(prop) {
    if (!prop) return null;
    const t = (prop.values || []).find(t => t.type === 'NUMBER');
    return t ? Number(t.value) : null;
}

function propString(prop) {
    if (!prop) return null;
    const t = (prop.values || []).find(t => t.type === 'STRING');
    if (t) return String(t.value);
    // Fall back to the first IDENT — happens for unsubstituted macro params.
    const i = (prop.values || []).find(t => t.type === 'IDENT');
    return i ? String(i.value) : null;
}

function propIdent(prop) {
    if (!prop) return null;
    const t = (prop.values || []).find(t => t.type === 'IDENT');
    return t ? String(t.value) : null;
}

function quakeColor(prop) {
    const c = propNumbers(prop, 4) ?? propNumbers(prop, 3);
    if (!c) return null;
    const r = Math.round((c[0] ?? 0) * 255);
    const g = Math.round((c[1] ?? 0) * 255);
    const b = Math.round((c[2] ?? 0) * 255);
    const a = c[3] ?? 1;
    return `rgba(${r},${g},${b},${a})`;
}

/**
 * Scale the 640x480 canvas to fit its parent's width while keeping the
 * aspect ratio. Hooked on window resize so the preview stays correct
 * when the editor's split changes.
 */
function fitCanvas(container) {
    const apply = () => {
        const parent = container.parentElement;
        if (!parent) return;
        const w = parent.clientWidth - 8;
        const scale = Math.min(1, w / 640);
        container.style.transform = `scale(${scale})`;
        // Reserve laid-out height equal to scaled canvas so the parent
        // doesn't collapse around the now-shorter element.
        container.parentElement.style.minHeight = (480 * scale) + 'px';
    };
    apply();
    window.addEventListener('resize', apply);
}

// Vite tree-shakes named exports from entry points that no other module statically imports.
// Blade-template <script type="module"> imports are invisible to Vite. Expose globally so the view can call it.
if (typeof window !== 'undefined') {
    window.renderMenu = renderMenu;
}
