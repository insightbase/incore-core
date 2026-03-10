/*
 * Editor.js Anchor Tune — raw input + auto-fill pro Header
 * - Uživatel může zadat cokoliv; hodnota se ukládá beze změn (kromě auto-fill z headeru)
 * - U Header bloku se kotva automaticky vyplňuje podle textu nadpisu (RAW text)
 * - ŽÁDNÁ automatická normalizace (slug si děláš až při renderu)
 * - Volitelně lze zapsat hodnotu i na DOM holder (config.setDomId)
 */

interface AnchorTuneConstructorArgs {
    api: any;
    data?: AnchorTuneData;
    config?: AnchorTuneConfig;
    block: any;
    onChange?: () => void;
    readOnly?: boolean;
}

type AnchorTuneData = { id?: string };

type AnchorTuneConfig = {
    setDomId?: boolean; // default false — pokud zapnete, hodnota se nastaví přímo jako element.id
};

export default class AnchorTune {
    private api: any;
    private block: any;
    private data: AnchorTuneData;
    private config: Required<AnchorTuneConfig>;
    private readOnly: boolean;

    /** DOM "holder" bloku (typicky .ce-block). Nastavuje se v wrap(blockContent). */
    private holderEl?: HTMLElement;

    /** Input v bočním panelu tunu (Kotva ID), abychom ho mohli aktualizovat z headeru. */
    private inputEl?: HTMLInputElement;

    /** Contenteditable element nadpisu (header), pokud jde o Header blok. */
    private headerTextEl?: HTMLElement;

    /**
     * Pokud uživatel někdy ručně upravil kotvu v tunu,
     * automatické generování z headeru přestane, aby mu to nepřepisovalo hodnotu.
     */
    private manualOverride: boolean = false;

    constructor({ api, data, config, block, readOnly }: AnchorTuneConstructorArgs) {
        this.api = api;
        this.block = block;
        this.data = data || {};
        this.readOnly = !!readOnly;

        const defaults: Required<AnchorTuneConfig> = { setDomId: false };
        this.config = { ...defaults, ...(config || {}) };

        // V tomto okamžiku ještě neznáme DOM holder (ten dostaneme až v wrap),
        // proto zde NEvoláme applyToDom().
    }

    static get isTune() {
        return true;
    }

    static get toolbox() {
        return {
            title: 'Anchor',
            icon:
                '<svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">\
                  <path d="M12 2a3 3 0 0 1 3 3 3 3 0 1 1-4 2.83V20a8 8 0 0 0 8-8h2a10 10 0 0 1-10 10A10 10 0 0 1 2 12h2a8 8 0 0 0 8 8V7.83A3 3 0 0 1 12 2Z"/>\
                </svg>'
        };
    }

    /**
     * EditorJS zavolá wrap() a předá obsah bloku.
     * Tady si najdeme "holder" (typicky .ce-block) a případně header text.
     */
    wrap(blockContent: HTMLElement): HTMLElement {
        const holder =
            (blockContent.closest('.ce-block') as HTMLElement | null) ||
            blockContent.parentElement ||
            blockContent;

        this.holderEl = holder;

        // Zjistíme, jestli jde o Header blok podle názvu nástroje
        const isHeader = this.block && this.block.name === 'header';

        if (isHeader) {
            // Najdeme první contenteditable element v rámci bloku/headeru
            const editable =
                (holder.querySelector('[contenteditable="true"]') as HTMLElement | null) ||
                (blockContent.querySelector('[contenteditable="true"]') as HTMLElement | null);

            if (editable) {
                this.headerTextEl = editable;
                this.attachHeaderListener(editable);
            }
        }

        // Po prvním wrapnutí aplikujeme stav kotvy (pokud už nějaká existuje).
        this.applyToDom();

        return blockContent;
    }

    /**
     * Boční panel tunu (nastavení) — input pro ID kotvy.
     */
    render(): HTMLElement | undefined {
        if (this.readOnly) return undefined;

        const wrap = document.createElement('div');
        wrap.className = 'cdx-anchor-tune';

        const label = document.createElement('label');
        label.textContent = 'Kotva (ID):';
        label.style.display = 'block';
        label.style.fontSize = '12px';
        label.style.marginBottom = '4px';

        const input = document.createElement('input');
        input.type = 'text';
        input.placeholder = 'libovolný text';
        input.value = this.data.id || '';
        input.className = 'cdx-input';
        input.style.width = '100%';

        this.inputEl = input;

        input.addEventListener('input', () => {
            // Uživatel ručně upravil kotvu → vypneme automatiku z headeru
            this.manualOverride = true;
            this.data.id = input.value;
            this.applyToDom();
        });

        wrap.appendChild(label);
        wrap.appendChild(input);
        return wrap;
    }

    /**
     * Uložit data tunu do output JSONu.
     */
    save(): AnchorTuneData {
        return { id: this.data.id };
    }

    /**
     * Připojí listener na psaní do nadpisu (header),
     * aby se automaticky vyplňovala kotva = TEXT NADPISU (bez slugování).
     */
    private attachHeaderListener(editable: HTMLElement) {
        editable.addEventListener('input', () => {
            if (this.manualOverride) {
                // Uživatel si kotvu upravuje ručně, nebudeme mu do toho sahat.
                return;
            }

            const text = editable.textContent || '';
            const raw = this.autoGenerateIdFromHeader(text);

            // Pokud je po otrimování prázdný, smažeme kotvu
            this.data.id = raw || undefined;

            // Synchronizace s inputem v tunu (pokud existuje)
            if (this.inputEl) {
                this.inputEl.value = this.data.id || '';
            }

            this.applyToDom();
        });
    }

    /**
     * Vrátí přímo text nadpisu (jen oříznutý zleva/zprava).
     * ŽÁDNÉ slugování, žádná diakritika pryč, žádná náhrada mezer.
     * Slug si uděláš až při renderu mimo editor.
     */
    private autoGenerateIdFromHeader(text: string): string {
        return text.trim();
    }

    /**
     * Aplikace stavu kotvy do DOM:
     * - data-anchor-id
     * - volitelně element.id
     * - zvýrazňovací classa pro CSS
     */
    private applyToDom() {
        try {
            const holder = this.holderEl;
            if (!holder) return;

            const rawId = this.data.id ?? '';
            const trimmedId = rawId.trim();
            const hasId = trimmedId.length > 0;

            if (hasId) {
                holder.dataset.anchorId = trimmedId;
                holder.classList.add('ce-block--has-anchor');

                if (this.config.setDomId) {
                    // POZOR: pokud hodnota není validní pro HTML id, je to na spotřebiteli.
                    holder.id = trimmedId;
                }
            } else {
                delete holder.dataset.anchorId;
                holder.classList.remove('ce-block--has-anchor');

                if (this.config.setDomId) {
                    holder.id = '';
                }
            }
        } catch {
            /* noop */
        }
    }
}

/*
Použití v EditorJS:

import EditorJS from '@editorjs/editorjs';
import AnchorTune from './AnchorTune';

new EditorJS({
  holder: 'editor',
  tools: {
    paragraph: {
      class: require('@editorjs/paragraph').default,
      tunes: ['anchor']
    },
    header: {
      class: require('@editorjs/header').default,
      tunes: ['anchor']
    },
    anchor: {
      class: AnchorTune,
      config: { setDomId: false }
    }
  }
});

Render fáze (mimo editor):
- Hodnota kotvy = block.tunes?.anchor?.id  (např. "Nadpis sekce 1")
- Tady si uděláš SLUG podle svých pravidel a ten teprve použiješ jako HTML id / fragment.

CSS příklad pro vizuální označení bloku s kotvou:

.ce-block--has-anchor {
  border-left: 3px solid #0b7285;
  padding-left: 8px;
  position: relative;
}

.ce-block--has-anchor::before {
  content: "⚓ " attr(data-anchor-id);
  position: absolute;
  top: 4px;
  right: 8px;
  font-size: 11px;
  opacity: 0.7;
}
*/
