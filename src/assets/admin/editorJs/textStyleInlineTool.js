/*
 * Editor.js inline tool — velikost písma a barva vybraného textu.
 * Převzato z projektu Impuls a zobecněno: velikosti i paleta barev jdou
 * přepsat přes config nástroje (viz editorJs.js a window.incoreEditorJsTextStyle).
 *
 * Výstupem je <span data-text-style="1" data-text-style-size=".." data-text-style-color=".."
 * style="font-size:..;line-height:..;color:..">, takže na frontendu funguje bez dalšího CSS.
 *
 * Editor.js 2.30 vytváří instance inline nástrojů při každém otevření lišty,
 * proto jsou globální posluchače a sledování výběru sdílené na úrovni modulu
 * a panel se vytváří až při otevření a po zavření se z DOM odstraní.
 */

export const DEFAULT_SIZES = [
    { label: 'XXS', key: 'xxs', value: '0.625rem', leading: '0.75rem', preview: '7px' },
    { label: 'XS', key: 'xs', value: '0.75rem', leading: '0.875rem', preview: '8px' },
    { label: 'SM', key: 'sm', value: '0.875rem', leading: '1rem', preview: '9px' },
    { label: 'Base', key: 'base', value: '1rem', leading: '1.5rem', preview: '10px' },
    { label: 'LG', key: 'lg', value: '1.125rem', leading: '1.625rem', preview: '11px' },
    { label: 'XL', key: 'xl', value: '1.25rem', leading: '2rem', preview: '12px' },
    { label: '2XL', key: '2xl', value: '1.75rem', leading: '2.5rem', preview: '14px' },
    { label: '3XL', key: '3xl', value: '2.25rem', leading: '3.5rem', preview: '16px' },
    { label: '4XL', key: '4xl', value: '3rem', leading: '3.5rem', preview: '18px' },
];

export const DEFAULT_COLOR_GROUPS = [
    {
        label: 'Základní',
        colors: [
            { label: 'Bílá', value: '#ffffff', border: true },
            { label: 'Černá', value: '#000000' },
            { label: 'Šedá 200', value: '#e5e7eb', border: true },
            { label: 'Šedá 400', value: '#9ca3af' },
            { label: 'Šedá 600', value: '#4b5563' },
            { label: 'Šedá 900', value: '#111827' },
        ],
    },
    {
        label: 'Barvy',
        colors: [
            { label: 'Červená', value: '#dc2626' },
            { label: 'Oranžová', value: '#ea580c' },
            { label: 'Žlutá', value: '#ca8a04' },
            { label: 'Zelená', value: '#16a34a' },
            { label: 'Modrá', value: '#2563eb' },
            { label: 'Fialová', value: '#7c3aed' },
        ],
    },
];

const SPAN_SELECTOR = 'span[data-text-style]';

// Stav sdílený všemi instancemi nástroje
const shared = {
    savedRange: null,
    panelOpen: false,
    initialized: false,
};

function elementOf(node) {
    return node && node.nodeType === Node.TEXT_NODE ? node.parentElement : node;
}

function closest(node, selector) {
    const el = elementOf(node);
    return el && el.closest ? el.closest(selector) : null;
}

function isRangeDetached(range) {
    try {
        return !range.startContainer.isConnected;
    } catch (e) {
        return true;
    }
}

function rememberSelection() {
    // Při práci v panelu drží výběr nástroj sám, výběr v prohlížeči ukazuje jinam
    if (shared.panelOpen) {
        return;
    }
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0 || sel.isCollapsed) {
        return;
    }
    const range = sel.getRangeAt(0);
    if (!range.collapsed && closest(range.commonAncestorContainer, '.ce-block')) {
        shared.savedRange = range.cloneRange();
    }
}

/**
 * Stylovaný span, který výběr obsahuje celý jako jediný uzel (range.selectNode(span)).
 */
function selectedSpan(range) {
    const container = range.startContainer;
    if (container !== range.endContainer || container.nodeType !== Node.ELEMENT_NODE || range.endOffset - range.startOffset !== 1) {
        return null;
    }
    const node = container.childNodes[range.startOffset];
    return node && node.nodeType === Node.ELEMENT_NODE && node.matches(SPAN_SELECTOR) ? node : null;
}

function unwrap(span) {
    const parent = span.parentNode;
    if (!parent) {
        return;
    }
    while (span.firstChild) {
        parent.insertBefore(span.firstChild, span);
    }
    parent.removeChild(span);
}

function copyStyleData(from, to) {
    if (from.dataset.textStyleSize) {
        to.dataset.textStyleSize = from.dataset.textStyleSize;
        to.dataset.textStyleLeading = from.dataset.textStyleLeading || '';
    }
    if (from.dataset.textStyleColor) {
        to.dataset.textStyleColor = from.dataset.textStyleColor;
    }
}

function createStyleSpan(linked = false) {
    const span = document.createElement('span');
    span.dataset.textStyle = '1';
    if (linked) {
        span.dataset.textStyleLinked = '1';
    }
    return span;
}

/**
 * Obalí celý obsah elementu (položky seznamu) jedním spanem, vnořené spany zploští.
 */
function wrapEntireContent(contentEl) {
    const children = Array.from(contentEl.childNodes);
    if (children.length === 1 && children[0].nodeType === Node.ELEMENT_NODE && children[0].hasAttribute('data-text-style')) {
        return children[0];
    }

    const span = createStyleSpan();
    while (contentEl.firstChild) {
        const child = contentEl.firstChild;
        if (child.nodeType === Node.ELEMENT_NODE && child.hasAttribute('data-text-style')) {
            copyStyleData(child, span);
            while (child.firstChild) {
                span.appendChild(child.firstChild);
            }
            contentEl.removeChild(child);
        } else {
            span.appendChild(child);
        }
    }
    contentEl.appendChild(span);
    return span;
}

function getTextNodesInRange(range) {
    const root = elementOf(range.commonAncestorContainer);
    const nodes = [];
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
        acceptNode: (node) => range.intersectsNode(node) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT,
    });

    let node;
    while ((node = walker.nextNode())) {
        const nodeRange = document.createRange();
        nodeRange.selectNode(node);
        if (range.compareBoundaryPoints(Range.END_TO_START, nodeRange) < 0
            && range.compareBoundaryPoints(Range.START_TO_END, nodeRange) > 0) {
            nodes.push(node);
        }
    }
    return nodes;
}

/**
 * Náhrada za range.surroundContents(), když výběr přesahuje hranice elementů
 * (např. část tučného a část obyčejného textu).
 */
function wrapRangeAcrossElements(range, wrapperSpan) {
    const nodes = getTextNodesInRange(range);
    if (nodes.length === 0) {
        return;
    }

    if (nodes.length === 1) {
        const node = nodes[0];
        const start = node === range.startContainer ? range.startOffset : 0;
        const end = node === range.endContainer ? range.endOffset : node.length;
        if (end < node.length) {
            node.splitText(end);
        }
        const middle = start > 0 ? node.splitText(start) : node;
        middle.parentNode.insertBefore(wrapperSpan, middle);
        wrapperSpan.appendChild(middle);
        return;
    }

    let firstDone = false;
    nodes.forEach((node) => {
        let target = node;
        if (node === range.endContainer && range.endOffset < node.length) {
            node.splitText(range.endOffset);
        }
        if (node === range.startContainer && range.startOffset > 0) {
            target = node.splitText(range.startOffset);
        }

        const span = firstDone ? createStyleSpan(true) : wrapperSpan;
        target.parentNode.insertBefore(span, target);
        span.appendChild(target);
        firstDone = true;
    });
}

/**
 * Nová položka seznamu (Enter) převezme styl z předchozí položky,
 * jakmile do ní uživatel začne psát.
 */
function observeListInheritance(recomputeStyle) {
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType !== Node.ELEMENT_NODE || !node.classList.contains('cdx-list__item')) {
                    return;
                }
                const prev = node.previousElementSibling;
                if (!prev || !prev.classList.contains('cdx-list__item')) {
                    return;
                }
                const prevContent = prev.querySelector('.cdx-list__item-content') || prev;
                const prevSpan = prevContent.querySelector(SPAN_SELECTOR);
                if (!prevSpan || (!prevSpan.dataset.textStyleSize && !prevSpan.dataset.textStyleColor)) {
                    return;
                }

                const contentEl = node.querySelector('.cdx-list__item-content') || node;
                const watcher = new MutationObserver((_m, innerObserver) => {
                    if ((contentEl.textContent || '').trim() === '') {
                        return;
                    }
                    innerObserver.disconnect();

                    contentEl.querySelectorAll(SPAN_SELECTOR).forEach((empty) => {
                        if ((empty.textContent || '').trim() === '') {
                            unwrap(empty);
                        }
                    });

                    const span = wrapEntireContent(contentEl);
                    copyStyleData(prevSpan, span);
                    recomputeStyle(span);

                    // Kurzor zpět na konec textu, obalení ho jinak přesune
                    const walker = document.createTreeWalker(span, NodeFilter.SHOW_TEXT, null);
                    let lastTextNode = null;
                    let n;
                    while ((n = walker.nextNode())) {
                        lastTextNode = n;
                    }
                    if (lastTextNode) {
                        const range = document.createRange();
                        range.setStart(lastTextNode, lastTextNode.length);
                        range.collapse(true);
                        const sel = window.getSelection();
                        sel.removeAllRanges();
                        sel.addRange(range);
                    }
                });
                watcher.observe(contentEl, { childList: true, subtree: true, characterData: true });
            });
        });
    });
    observer.observe(document.body, { childList: true, subtree: true });
}

function initShared(recomputeStyle) {
    if (shared.initialized) {
        return;
    }
    shared.initialized = true;

    // Výběr si pamatujeme průběžně — klik do panelu ho v prohlížeči zruší
    let debounce = null;
    document.addEventListener('selectionchange', () => {
        if (shared.panelOpen) {
            return;
        }
        clearTimeout(debounce);
        debounce = setTimeout(rememberSelection, 20);
    });

    document.addEventListener('mouseup', (e) => {
        if (shared.panelOpen || !e.target || !e.target.closest || !e.target.closest('.codex-editor')) {
            return;
        }
        rememberSelection();
    });

    document.addEventListener('mousedown', (e) => {
        if (shared.panelOpen) {
            return;
        }
        const inEditor = e.target && e.target.closest && e.target.closest('.codex-editor');
        if (!inEditor) {
            shared.savedRange = null;
        }
    });

    observeListInheritance(recomputeStyle);
}

export default class TextStyleInlineTool {
    static get isInline() {
        return true;
    }

    static get title() {
        return 'Styl textu';
    }

    // Bez tohoto by Editor.js span i jeho atributy při ukládání odstranil
    static get sanitize() {
        return {
            span: {
                style: true,
                'data-text-style': true,
                'data-text-style-size': true,
                'data-text-style-color': true,
                'data-text-style-leading': true,
                'data-text-style-linked': true,
            },
        };
    }

    constructor({ api, config }) {
        this.api = api;
        this.sizes = (config && config.sizes) || DEFAULT_SIZES;
        this.colorGroups = (config && config.colorGroups) || DEFAULT_COLOR_GROUPS;

        this.button = null;
        this.panel = null;
        this.anchorBlock = null;
        this.rafId = null;

        this.onDocumentClick = (e) => {
            if (this.panel && !this.panel.contains(e.target) && !this.button.contains(e.target)) {
                this.hidePanel();
            }
        };
        this.onDocumentKeyDown = (e) => {
            if (e.key === 'Escape') {
                this.hidePanel();
            }
        };

        initShared((span) => this.recomputeStyle(span));
    }

    render() {
        this.button = document.createElement('button');
        this.button.type = 'button';
        this.button.classList.add(this.api.styles.inlineToolButton);
        this.button.setAttribute('aria-label', 'Styl textu');
        this.button.setAttribute('aria-haspopup', 'true');
        this.button.innerHTML = this.iconSvg();

        // Nesmí vzít fokus, jinak se ztratí výběr
        this.button.addEventListener('mousedown', (e) => e.preventDefault());
        this.button.addEventListener('click', (e) => {
            e.stopPropagation();
            if (shared.savedRange) {
                this.anchorBlock = closest(shared.savedRange.commonAncestorContainer, '.ce-block');
            }
            this.panel ? this.hidePanel() : this.showPanel();
        });

        return this.button;
    }

    surround() {
        // Styl se aplikuje až z panelu
    }

    checkState(selection) {
        if (!selection || selection.isCollapsed) {
            this.button.classList.remove(this.api.styles.inlineToolButtonActive);
            return false;
        }
        rememberSelection();
        const active = closest(selection.anchorNode, SPAN_SELECTOR) !== null;
        this.button.classList.toggle(this.api.styles.inlineToolButtonActive, active);
        return active;
    }

    showPanel() {
        this.panel = this.buildPanel();
        document.body.appendChild(this.panel);
        shared.panelOpen = true;
        this.button.setAttribute('aria-expanded', 'true');

        this.updatePanelPosition();
        const loop = () => {
            if (this.panel === null) {
                return;
            }
            this.updatePanelPosition();
            this.rafId = requestAnimationFrame(loop);
        };
        this.rafId = requestAnimationFrame(loop);

        setTimeout(() => document.addEventListener('click', this.onDocumentClick), 0);
        document.addEventListener('keydown', this.onDocumentKeyDown);
    }

    hidePanel() {
        if (this.panel === null) {
            return;
        }
        this.panel.remove();
        this.panel = null;
        shared.panelOpen = false;
        if (this.button) {
            this.button.setAttribute('aria-expanded', 'false');
        }
        if (this.rafId) {
            cancelAnimationFrame(this.rafId);
            this.rafId = null;
        }
        document.removeEventListener('click', this.onDocumentClick);
        document.removeEventListener('keydown', this.onDocumentKeyDown);
    }

    updatePanelPosition() {
        const width = this.panel.offsetWidth || 260;
        let left;
        let top;
        if (this.anchorBlock && this.anchorBlock.isConnected) {
            const r = this.anchorBlock.getBoundingClientRect();
            left = r.right + 12;
            top = r.top;
            if (left + width > window.innerWidth - 8) {
                left = r.left - width - 12;
            }
            const height = this.panel.offsetHeight || 480;
            if (top + height > window.innerHeight - 8) {
                top = window.innerHeight - height - 8;
            }
            top = Math.max(top, 8);
        } else {
            const r = this.button.getBoundingClientRect();
            left = r.left + r.width / 2 - width / 2;
            top = r.bottom + 8;
        }
        left = Math.max(8, Math.min(left, window.innerWidth - width - 8));
        this.panel.style.left = Math.round(left) + 'px';
        this.panel.style.top = Math.round(top) + 'px';
    }

    buildPanel() {
        const current = shared.savedRange ? closest(shared.savedRange.startContainer, SPAN_SELECTOR) : null;
        const currentSize = current ? current.dataset.textStyleSize || '' : '';
        const currentColor = current ? current.dataset.textStyleColor || '' : '';

        const panel = document.createElement('div');
        panel.className = 'tst-panel';
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-label', 'Styl textu');
        panel.addEventListener('mousedown', (e) => {
            e.preventDefault();
            e.stopPropagation();
        });

        const header = document.createElement('div');
        header.className = 'tst-panel-header';
        header.innerHTML = this.iconSvg() + '<span>Styl textu</span>';
        panel.appendChild(header);

        panel.appendChild(this.makeLabel('Velikost písma'));
        const sizeGrid = document.createElement('div');
        sizeGrid.className = 'tst-size-grid';
        this.sizes.forEach((size) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'tst-size-btn';
            btn.dataset.sizeKey = size.key;
            btn.setAttribute('aria-label', 'Velikost ' + size.label);
            btn.classList.toggle('tst-size-btn--active', size.key === currentSize);

            const preview = document.createElement('span');
            preview.className = 'tst-size-preview';
            preview.style.fontSize = size.preview || '10px';
            preview.textContent = 'Aa';

            const name = document.createElement('span');
            name.className = 'tst-size-name';
            name.textContent = size.label;

            btn.append(preview, name);
            btn.addEventListener('click', () => {
                this.applyStyle({ size: size });
                this.markActive(panel, '.tst-size-btn', btn, 'tst-size-btn--active');
            });
            sizeGrid.appendChild(btn);
        });
        panel.appendChild(sizeGrid);

        this.colorGroups.forEach((group) => {
            panel.appendChild(this.makeLabel(group.label));
            const row = document.createElement('div');
            row.className = 'tst-color-row';
            group.colors.forEach((color) => {
                const swatch = document.createElement('button');
                swatch.type = 'button';
                swatch.className = 'tst-color-swatch';
                swatch.classList.toggle('tst-color-swatch--bordered', !!color.border);
                swatch.classList.toggle('tst-color-swatch--active', color.value === currentColor);
                swatch.dataset.color = color.value;
                swatch.style.setProperty('--tst-swatch', color.value);
                swatch.title = color.label;
                swatch.setAttribute('aria-label', color.label);
                swatch.addEventListener('click', () => {
                    this.applyStyle({ color: color.value });
                    this.markActive(panel, '.tst-color-swatch', swatch, 'tst-color-swatch--active');
                });
                row.appendChild(swatch);
            });
            panel.appendChild(row);
        });

        const divider = document.createElement('div');
        divider.className = 'tst-divider';
        panel.appendChild(divider);

        if (this.findList() !== null) {
            const applyListBtn = this.makeActionButton('Použít na celý seznam', 'tst-action-btn--primary');
            applyListBtn.addEventListener('click', () => {
                const activeSize = panel.querySelector('.tst-size-btn--active');
                const activeColor = panel.querySelector('.tst-color-swatch--active');
                const size = activeSize ? this.sizes.find((s) => s.key === activeSize.dataset.sizeKey) : null;
                const color = activeColor ? activeColor.dataset.color : null;
                if (size || color) {
                    this.applyToWholeList({ size: size, color: color });
                }
            });
            panel.appendChild(applyListBtn);
        }

        const clearBtn = this.makeActionButton('Odstranit formátování', 'tst-action-btn--danger');
        clearBtn.addEventListener('click', () => {
            this.clearStyle();
            panel.querySelectorAll('.tst-size-btn--active, .tst-color-swatch--active').forEach((el) => {
                el.classList.remove('tst-size-btn--active', 'tst-color-swatch--active');
            });
        });
        panel.appendChild(clearBtn);

        return panel;
    }

    makeLabel(text) {
        const el = document.createElement('p');
        el.className = 'tst-section-label';
        el.textContent = text;
        return el;
    }

    makeActionButton(text, modifier) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'tst-action-btn ' + modifier;
        btn.textContent = text;
        return btn;
    }

    markActive(panel, selector, active, activeClass) {
        panel.querySelectorAll(selector).forEach((el) => el.classList.remove(activeClass));
        active.classList.add(activeClass);
    }

    applyStyle({ size = null, color = null }) {
        const spans = this.getOrCreateSpans();
        if (spans === null) {
            return;
        }
        spans.forEach((span) => {
            this.setStyleData(span, size, color);
            this.recomputeStyle(span);
        });
    }

    setStyleData(span, size, color) {
        if (size) {
            span.dataset.textStyleSize = size.key;
            span.dataset.textStyleLeading = size.leading || '';
        }
        if (color) {
            span.dataset.textStyleColor = color;
        }
    }

    recomputeStyle(span) {
        const size = this.sizes.find((s) => s.key === span.dataset.textStyleSize);
        const color = span.dataset.textStyleColor || '';
        const parts = [];
        if (size) {
            parts.push('font-size:' + size.value);
            if (size.leading) {
                parts.push('line-height:' + size.leading);
            }
        }
        if (color) {
            parts.push('color:' + color);
        }
        span.style.cssText = parts.join(';');
    }

    findList() {
        let list = this.anchorBlock ? this.anchorBlock.querySelector('.cdx-list') : null;
        if (list === null && shared.savedRange) {
            list = closest(shared.savedRange.commonAncestorContainer, '.cdx-list');
        }
        return list;
    }

    applyToWholeList({ size, color }) {
        const list = this.findList();
        if (list === null) {
            return;
        }
        list.querySelectorAll('.cdx-list__item').forEach((item) => {
            const span = wrapEntireContent(item.querySelector('.cdx-list__item-content') || item);
            this.setStyleData(span, size, color);
            this.recomputeStyle(span);
        });
    }

    clearStyle() {
        const range = shared.savedRange;
        if (!range || isRangeDetached(range)) {
            return;
        }
        const block = closest(range.commonAncestorContainer, '.ce-block');
        if (!block) {
            return;
        }

        block.querySelectorAll('span[data-text-style-linked]').forEach(unwrap);

        block.querySelectorAll(SPAN_SELECTOR).forEach((span) => {
            let intersects = false;
            try {
                intersects = range.intersectsNode(span)
                    || span.contains(range.startContainer)
                    || span.contains(range.endContainer);
            } catch (e) {
                // Uzel mezitím zmizel z DOM
            }
            if (intersects) {
                unwrap(span);
            }
        });

        shared.savedRange = null;
    }

    /**
     * Vrátí spany, na které se má styl použít. V odstavci a nadpisu obalí výběr,
     * v seznamu vždy celou položku (jeden styl na položku).
     */
    getOrCreateSpans() {
        const range = shared.savedRange;
        if (!range || range.collapsed) {
            return null;
        }
        if (isRangeDetached(range)) {
            shared.savedRange = null;
            return null;
        }

        const block = closest(range.commonAncestorContainer, '.ce-block');
        const isList = block !== null && block.querySelector('.cdx-list') !== null;

        if (isList) {
            const startItem = closest(range.startContainer, '.cdx-list__item');
            const endItem = closest(range.endContainer, '.cdx-list__item');
            if (startItem === null) {
                return null;
            }

            let items = [startItem];
            if (endItem !== null && endItem !== startItem) {
                const list = startItem.closest('.cdx-list') || startItem.parentNode;
                const all = Array.from(list.querySelectorAll('.cdx-list__item'));
                items = all.slice(all.indexOf(startItem), all.indexOf(endItem) + 1);
            }
            return items.map((li) => wrapEntireContent(li.querySelector('.cdx-list__item-content') || li));
        }

        const mainSpan = this.getOrCreateSpan();
        if (mainSpan === null) {
            return null;
        }
        if (block !== null) {
            const affected = Array.from(block.querySelectorAll(SPAN_SELECTOR))
                .filter((span) => range.intersectsNode(span));
            if (affected.length > 0) {
                return affected;
            }
        }
        return [mainSpan];
    }

    getOrCreateSpan() {
        const range = shared.savedRange;
        if (!closest(range.commonAncestorContainer, '.ce-block')) {
            shared.savedRange = null;
            return null;
        }

        const startSpan = closest(range.startContainer, SPAN_SELECTOR);
        const endSpan = closest(range.endContainer, SPAN_SELECTOR);

        // Výběr začíná ve stylovaném textu a pokračuje mimo něj — dostylujeme zbytek
        if (startSpan && startSpan !== endSpan) {
            getTextNodesInRange(range).forEach((node) => {
                if (closest(node, SPAN_SELECTOR)) {
                    return;
                }
                const span = createStyleSpan(true);
                copyStyleData(startSpan, span);
                node.parentNode.insertBefore(span, node);
                span.appendChild(node);
                this.recomputeStyle(span);
            });
            return startSpan;
        }

        const existing = closest(range.commonAncestorContainer, SPAN_SELECTOR) || startSpan || endSpan || selectedSpan(range);
        if (existing) {
            return existing;
        }

        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);

        const span = createStyleSpan();
        try {
            range.surroundContents(span);
        } catch (e) {
            wrapRangeAcrossElements(range, span);
        }

        shared.savedRange = document.createRange();
        shared.savedRange.selectNodeContents(span);
        return span;
    }

    iconSvg() {
        return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
            + '<polyline points="4 7 4 4 20 4 20 7"/>'
            + '<line x1="9" y1="20" x2="15" y2="20"/>'
            + '<line x1="12" y1="4" x2="12" y2="20"/>'
            + '</svg>';
    }
}
