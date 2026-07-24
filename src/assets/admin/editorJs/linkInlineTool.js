/*
 * Editor.js inline tool — odkaz s volbou otevření v novém okně.
 * Registruje se pod jménem `link`, čímž přepíše vestavěný nástroj Editor.js
 * (ten target neumí a jeho sanitizer ho zahazuje).
 */
export default class LinkInlineTool {
    static get isInline() {
        return true;
    }

    static get title() {
        return 'Odkaz';
    }

    // Bez tohoto by Editor.js target i rel při ukládání odstranil
    static get sanitize() {
        return {
            a: {
                href: true,
                target: true,
                rel: true,
            },
        };
    }

    constructor({ api }) {
        this.api = api;
        this.tag = 'A';

        this.button = null;
        this.panel = null;
        this.input = null;
        this.checkbox = null;
        this.removeButton = null;

        // Výběr odložený z surround() — klik do inputu ho v prohlížeči zruší
        this.savedRange = null;

        // Odkaz, ve kterém stojí kurzor. Držíme si element, ne jen selection,
        // protože klik na tlačítko v panelu selection ztratí.
        this.currentAnchor = null;
    }

    render() {
        this.button = document.createElement('button');
        this.button.type = 'button';
        this.button.innerHTML = '🔗';
        this.button.classList.add(this.api.styles.inlineToolButton);
        this.button.title = LinkInlineTool.title;
        return this.button;
    }

    renderActions() {
        this.panel = document.createElement('div');
        this.panel.style.display = 'none';
        this.panel.style.padding = '8px';
        this.panel.style.minWidth = '260px';

        this.input = document.createElement('input');
        this.input.type = 'text';
        this.input.placeholder = 'https://…';
        this.input.className = 'cdx-input';
        this.input.style.width = '100%';
        this.input.style.marginBottom = '6px';
        this.input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                this.applyLink();
            }
        });

        const label = document.createElement('label');
        label.style.display = 'flex';
        label.style.alignItems = 'center';
        label.style.gap = '6px';
        label.style.fontSize = '12px';
        label.style.marginBottom = '8px';

        this.checkbox = document.createElement('input');
        this.checkbox.type = 'checkbox';

        label.appendChild(this.checkbox);
        label.appendChild(document.createTextNode('Otevřít v novém okně'));

        const buttons = document.createElement('div');
        buttons.style.display = 'flex';
        buttons.style.gap = '6px';

        const saveButton = document.createElement('button');
        saveButton.type = 'button';
        saveButton.textContent = 'Uložit';
        saveButton.style.flex = '1';
        saveButton.addEventListener('click', () => this.applyLink());

        this.removeButton = document.createElement('button');
        this.removeButton.type = 'button';
        this.removeButton.textContent = 'Odebrat odkaz';
        this.removeButton.style.display = 'none';
        this.removeButton.addEventListener('click', () => this.removeLink());

        buttons.appendChild(saveButton);
        buttons.appendChild(this.removeButton);

        this.panel.appendChild(this.input);
        this.panel.appendChild(label);
        this.panel.appendChild(buttons);

        return this.panel;
    }

    /**
     * Editor.js volá při kliknutí na tlačítko nástroje.
     */
    surround(range) {
        if (this.isPanelOpen()) {
            this.closePanel();
            return;
        }

        this.savedRange = range.cloneRange();

        const anchor = this.api.selection.findParentTag(this.tag);
        if (anchor !== null) {
            this.fillFromAnchor(anchor);
        } else {
            this.resetFields();
        }

        this.openPanel();

        // Editor.js si v tomto okamžiku ještě sahá na focus, proto až na konci fronty
        setTimeout(() => this.input.focus(), 0);
    }

    /**
     * Editor.js volá při každé změně výběru, dokud je inline lišta otevřená.
     */
    checkState() {
        const anchor = this.api.selection.findParentTag(this.tag);

        this.button.classList.toggle(this.api.styles.inlineToolButtonActive, anchor !== null);

        if (anchor !== null) {
            this.fillFromAnchor(anchor);
            this.openPanel();
        } else {
            // Hodnotu v poli nemažeme — uživatel může být rozepsaný u nového odkazu
            this.currentAnchor = null;
            if (this.removeButton !== null) {
                this.removeButton.style.display = 'none';
            }
        }

        return anchor !== null;
    }

    /**
     * Editor.js volá při zavření inline lišty.
     */
    clear() {
        this.savedRange = null;
        this.currentAnchor = null;
        this.closePanel();
    }

    applyLink() {
        const url = this.input.value.trim();
        if (url === '') {
            // Není co nastavit, panel necháme otevřený
            return;
        }

        const newWindow = this.checkbox.checked;

        // Kurzor uvnitř existujícího odkazu — jen přepíšeme atributy
        if (this.currentAnchor !== null) {
            this.decorateAnchor(this.currentAnchor, url, newWindow);
            this.finish();
            return;
        }

        // Bez označeného textu není co obalit
        if (this.savedRange === null || this.savedRange.collapsed) {
            return;
        }

        this.restoreSelection();

        const anchor = document.createElement('a');
        this.decorateAnchor(anchor, url, newWindow);
        anchor.appendChild(this.savedRange.extractContents());
        this.savedRange.insertNode(anchor);
        this.api.selection.expandToTag(anchor);

        this.finish();
    }

    removeLink() {
        if (this.currentAnchor === null) {
            return;
        }

        const anchor = this.currentAnchor;
        const parent = anchor.parentNode;
        while (anchor.firstChild !== null) {
            parent.insertBefore(anchor.firstChild, anchor);
        }
        parent.removeChild(anchor);
        parent.normalize();

        this.finish();
    }

    decorateAnchor(anchor, url, newWindow) {
        anchor.setAttribute('href', url);
        if (newWindow) {
            anchor.setAttribute('target', '_blank');
            anchor.setAttribute('rel', 'noopener noreferrer');
        } else {
            anchor.removeAttribute('target');
            anchor.removeAttribute('rel');
        }
    }

    fillFromAnchor(anchor) {
        this.currentAnchor = anchor;

        // Pojistka pro případ, že by Editor.js zavolal checkState() dřív než renderActions()
        if (this.input === null) {
            return;
        }

        this.input.value = anchor.getAttribute('href') || '';
        this.checkbox.checked = anchor.getAttribute('target') === '_blank';
        this.removeButton.style.display = 'inline-block';
    }

    resetFields() {
        this.currentAnchor = null;

        if (this.input === null) {
            return;
        }

        this.input.value = '';
        this.checkbox.checked = false;
        this.removeButton.style.display = 'none';
    }

    restoreSelection() {
        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(this.savedRange);
    }

    openPanel() {
        if (this.panel !== null) {
            this.panel.style.display = 'block';
        }
    }

    closePanel() {
        if (this.panel !== null) {
            this.panel.style.display = 'none';
        }
    }

    isPanelOpen() {
        return this.panel !== null && this.panel.style.display !== 'none';
    }

    finish() {
        this.closePanel();
        this.api.inlineToolbar.close();
    }
}
