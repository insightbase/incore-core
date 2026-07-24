/*
 * Editor.js inline tool — odkaz s volbou otevření v novém okně.
 * Registruje se pod jménem `link`, čímž přepíše vestavěný nástroj Editor.js
 * (ten target neumí a jeho sanitizer ho zahazuje).
 *
 * ZÁMĚRNĚ neimplementuje renderActions(). Editor.js 2.30 z vráceného panelu
 * udělá vnořený popover zavěšený pod inline lištou (editorjs.mjs, větev
 * `if (isFunction(tool.renderActions))`), takže by nad panelem musela zůstat
 * viditelná vodorovná lišta s tlačítky. Místo toho si po kliknutí lištu
 * zavřeme a vykreslíme vlastní dialog do document.body.
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

        this.dialog = null;
        this.input = null;
        this.checkbox = null;
        this.removeButton = null;

        // Výběr odložený z surround() — zavření lišty i klik do inputu ho zruší
        this.savedRange = null;

        // Odkaz, ve kterém stojí kurzor. Držíme si element, ne jen selection,
        // protože klik na tlačítko v dialogu selection ztratí.
        this.currentAnchor = null;

        this.onDocumentMouseDown = (event) => {
            if (this.dialog !== null && !this.dialog.contains(event.target)) {
                this.closeDialog();
            }
        };

        this.onDocumentKeyDown = (event) => {
            if (event.key === 'Escape') {
                this.closeDialog();
            }
        };
    }

    render() {
        this.button = document.createElement('button');
        this.button.type = 'button';
        this.button.innerHTML = '🔗';
        this.button.classList.add(this.api.styles.inlineToolButton);
        this.button.title = LinkInlineTool.title;
        return this.button;
    }

    /**
     * Editor.js volá při kliknutí na tlačítko nástroje.
     */
    surround(range) {
        // Pořadí je důležité — obojí čte aktuální výběr, který zavření lišty zruší
        const anchor = this.api.selection.findParentTag(this.tag);
        const rect = range.getBoundingClientRect();

        this.savedRange = range.cloneRange();
        this.currentAnchor = anchor;

        this.api.inlineToolbar.close();

        this.openDialog(rect, anchor);
    }

    /**
     * Editor.js volá při každé změně výběru, dokud je inline lišta otevřená.
     */
    checkState() {
        const anchor = this.api.selection.findParentTag(this.tag);

        if (this.button !== null) {
            this.button.classList.toggle(this.api.styles.inlineToolButtonActive, anchor !== null);
        }

        return anchor !== null;
    }

    openDialog(rect, anchor) {
        // Případný dřívější dialog nejdřív zlikvidujeme, ať jich nezůstane víc
        this.closeDialog();

        this.dialog = this.buildDialog();
        document.body.appendChild(this.dialog);

        if (anchor !== null) {
            this.input.value = anchor.getAttribute('href') || '';
            this.checkbox.checked = anchor.getAttribute('target') === '_blank';
            this.removeButton.style.display = 'inline-block';
        }

        // Až po vložení do DOM — dřív nezná offsetWidth
        this.positionDialog(rect);

        document.addEventListener('mousedown', this.onDocumentMouseDown);
        document.addEventListener('keydown', this.onDocumentKeyDown);

        this.input.focus();
        this.input.select();
    }

    closeDialog() {
        if (this.dialog === null) {
            return;
        }

        document.removeEventListener('mousedown', this.onDocumentMouseDown);
        document.removeEventListener('keydown', this.onDocumentKeyDown);

        this.dialog.remove();
        this.dialog = null;
        this.input = null;
        this.checkbox = null;
        this.removeButton = null;
    }

    buildDialog() {
        const dialog = document.createElement('div');
        dialog.style.position = 'absolute';
        dialog.style.zIndex = '1000';
        dialog.style.width = '280px';
        dialog.style.padding = '12px';
        dialog.style.background = '#fff';
        dialog.style.border = '1px solid #e8e8eb';
        dialog.style.borderRadius = '8px';
        dialog.style.boxShadow = '0 3px 15px -3px rgba(13, 20, 33, .13)';

        const title = document.createElement('div');
        title.textContent = 'URL';
        title.style.fontSize = '12px';
        title.style.marginBottom = '4px';

        this.input = document.createElement('input');
        this.input.type = 'text';
        this.input.placeholder = 'https://…';
        this.input.className = 'cdx-input';
        this.input.style.width = '100%';
        this.input.style.marginBottom = '8px';
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
        label.style.marginBottom = '10px';
        label.style.cursor = 'pointer';

        this.checkbox = document.createElement('input');
        this.checkbox.type = 'checkbox';

        label.appendChild(this.checkbox);
        label.appendChild(document.createTextNode('Otevřít v novém okně'));

        const buttons = document.createElement('div');
        buttons.style.display = 'flex';
        buttons.style.gap = '6px';

        this.removeButton = document.createElement('button');
        this.removeButton.type = 'button';
        this.removeButton.textContent = 'Odebrat odkaz';
        this.removeButton.style.display = 'none';
        this.removeButton.style.marginRight = 'auto';
        this.removeButton.addEventListener('click', () => this.removeLink());

        const cancelButton = document.createElement('button');
        cancelButton.type = 'button';
        cancelButton.textContent = 'Zrušit';
        cancelButton.addEventListener('click', () => this.closeDialog());

        const saveButton = document.createElement('button');
        saveButton.type = 'button';
        saveButton.textContent = 'Uložit';
        saveButton.addEventListener('click', () => this.applyLink());

        buttons.appendChild(this.removeButton);
        buttons.appendChild(cancelButton);
        buttons.appendChild(saveButton);

        dialog.appendChild(title);
        dialog.appendChild(this.input);
        dialog.appendChild(label);
        dialog.appendChild(buttons);

        return dialog;
    }

    /**
     * Umístí dialog pod označený text a udrží ho v okně.
     */
    positionDialog(rect) {
        const margin = 8;
        const top = rect.bottom + window.scrollY + margin;
        let left = rect.left + window.scrollX;

        const maxLeft = window.scrollX + document.documentElement.clientWidth - this.dialog.offsetWidth - margin;
        if (left > maxLeft) {
            left = Math.max(window.scrollX + margin, maxLeft);
        }

        this.dialog.style.top = `${top}px`;
        this.dialog.style.left = `${left}px`;
    }

    applyLink() {
        const url = this.input.value.trim();
        if (url === '') {
            // Není co nastavit, dialog necháme otevřený
            return;
        }

        const newWindow = this.checkbox.checked;

        // Kurzor uvnitř existujícího odkazu — jen přepíšeme atributy
        if (this.currentAnchor !== null) {
            this.decorateAnchor(this.currentAnchor, url, newWindow);
            this.closeDialog();
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

        this.closeDialog();
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

        this.currentAnchor = null;
        this.closeDialog();
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

    restoreSelection() {
        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(this.savedRange);
    }
}
