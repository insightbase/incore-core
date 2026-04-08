/*
 * Editor.js Highlight Tune
 * - Jednoduchý Block Tune s checkboxem, který označí libovolný blok jako „highlighted“.
 * - Uloží do výstupu: { tunes: { highlight: { value: boolean } } }
 * - Volitelně přidá/odebere CSS třídu na holderu bloku (default: 'is-highlighted') a/nebo data atribut.
 * - Bez závislostí, TypeScript, funguje v read-only (UI se nerenedruje, ale třída/atribut zůstane).
 */

interface HighlightTuneConstructorArgs {
    api: any;
    data: HighlightTuneData | undefined;
    config?: HighlightTuneConfig;
    block: any;
    readOnly?: boolean;
}

type HighlightTuneData = { value?: boolean };

type HighlightTuneConfig = {
    className?: string;          // CSS třída, která se přidá na holder (když je zapnuto)
    setDataAttr?: boolean;       // zapisovat data-highlighted="true|false" na holder
    datasetKey?: string;         // klíč pro dataset (default 'highlighted')
};

export default class HighlightTune {
    private api: any;
    private block: any;
    private data: HighlightTuneData;
    private readOnly: boolean;
    private config: Required<HighlightTuneConfig>;

    constructor({ api, data, config, block, readOnly }: HighlightTuneConstructorArgs) {
        this.api = api;
        this.block = block;
        this.data = data || {};
        this.readOnly = !!readOnly;

        const defaults: Required<HighlightTuneConfig> = {
            className: 'is-highlighted',
            setDataAttr: true,
            datasetKey: 'highlighted'
        };

        this.config = { ...defaults, ...(config || {}) };

        // Při inicializaci promítnout stav do DOM holderu
        this.applyToDom();
    }

    static get isTune() { return true; }

    static get toolbox() {
        return {
            title: 'Highlight',
            icon: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path d="M3 17.25 17.25 3a2.121 2.121 0 0 1 3 3L6 20.25 3 21l.75-3Z"/><path d="M14 7l3 3"/></svg>`
        };
    }

    render() {
        if (this.readOnly) return undefined; // v read-only žádné UI

        const wrap = document.createElement('div');
        wrap.className = 'cdx-highlight-tune';

        const label = document.createElement('label');
        label.style.display = 'flex';
        label.style.alignItems = 'center';
        label.style.gap = '8px';

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.checked = !!this.data.value;
        checkbox.addEventListener('change', () => {
            this.data.value = checkbox.checked;
            this.applyToDom();
        });

        const text = document.createElement('span');
        text.textContent = 'Highlighted';
        text.style.fontSize = '12px';

        label.appendChild(checkbox);
        label.appendChild(text);
        wrap.appendChild(label);
        return wrap;
    }

    save(): HighlightTuneData {
        return { value: !!this.data.value };
    }

    /** Přidá/odebere CSS class a data atribut na holderu bloku podle stavu. */
    private applyToDom() {
        try {
            const idx = this.api.blocks.getCurrentBlockIndex?.();
            const holder = typeof idx === 'number'
                ? this.api.blocks.getBlockByIndex?.(idx)?.holder
                : this.block?.holder || undefined;
            if (!holder) return;

            const on = !!this.data.value;

            // CSS class
            if (this.config.className) {
                holder.classList.toggle(this.config.className, on);
            }

            // data atribut
            if (this.config.setDataAttr) {
                const key = this.config.datasetKey || 'highlighted';
                if (on) {
                    holder.dataset[key] = 'true';
                } else {
                    // lepší je nastavit na 'false' než mazat, ale dle potřeby
                    delete holder.dataset[key];
                }
            }
        } catch {
            // ignore
        }
    }
}

/* Použití v Editor.js:
import EditorJS from '@editorjs/editorjs';
import Paragraph from '@editorjs/paragraph';
import Header from '@editorjs/header';
import HighlightTune from './HighlightTune';

const editor = new EditorJS({
  holder: 'editor',
  tools: {
    paragraph: { class: Paragraph, tunes: ['highlight'] },
    header: { class: Header, tunes: ['highlight'] },
    // ...přidej tune i k dalším blokům
    highlight: {
      class: HighlightTune,
      config: {
        className: 'is-highlighted',  // přidá třídu na DOM holder
        setDataAttr: true,            // zapíše data-highlighted
        datasetKey: 'highlighted'
      }
    }
  }
});

// Výstup bloku (příklad):
// {
//   type: 'paragraph',
//   data: { text: 'Text bloku' },
//   tunes: { highlight: { value: true } }
// }

// CSS příklad:
// .is-highlighted { background: rgba(255, 235, 59, .25); border-radius: 8px; }
*/
