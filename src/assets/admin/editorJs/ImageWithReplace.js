// ImageWithReplace.js
import ImageTool from '@editorjs/image';
import { openImageMetaModal, createImageMetaButton, hasImageMeta, editIcon } from './imageMetaModal.js';

const STYLE_ID = 'editorjs-image-with-replace-styles';

/**
 * Rozšíření @editorjs/image, které přidá do settings menu tlačítko „Vyměnit obrázek“
 * a modal pro alt, popis (caption) a autora. Popis se edituje jen v modalu, vestavěné
 * caption pole pod obrázkem je skryté (zůstává ale zdrojem hodnoty pro save()).
 * Nepoužívá tune ani actions → stabilní mount, žádné classList chyby.
 */
export default class ImageWithReplace extends ImageTool {
    constructor(params) {
        super(params);
        this._readOnly = !!params.readOnly;
        this._meta = {
            alt: params.data?.alt || '',
            author: params.data?.author || '',
        };
    }

    render() {
        const wrapper = super.render();
        ensureStyles();
        wrapper.classList.add('image-with-meta');

        if (!this._readOnly) {
            this._metaButton = createImageMetaButton(() => this.handleEditMeta());
            this._metaButton.classList.add('image-with-meta__btn');
            this.ui.nodes.imageContainer.appendChild(this._metaButton);
            this._refreshMetaButton();
        }

        return wrapper;
    }

    save() {
        return {
            ...super.save(),
            alt: this._meta.alt,
            author: this._meta.author,
        };
    }

    renderSettings() {
        const base = super.renderSettings?.(); // může vrátit pole settings objektů

        const metaItem = {
            name: 'imageMeta',
            label: 'Alt, popis, autor',
            title: 'Alt, popis, autor',
            icon: editIcon,
            closeOnActivate: true,
            onActivate: () => this.handleEditMeta(),
        };

        const replaceItem = {
            name: 'replaceImage',
            label: 'Vyměnit obrázek',   // některé verze čtou 'label'
            title: 'Vyměnit obrázek',   // jiné zas 'title' – necháme obojí
            icon: `
      <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 6v-3l-4 4 4 4v-3c3.3 0 6 2.7 6 6 0 .7-.1 1.4-.3 2l1.9 1.1c.3-.7.4-1.5.4-2.3 0-4.4-3.6-8-8-8zm-6 4c0-.7.1-1.4.3-2L4.4 6.9c-.3.7-.4 1.5-.4 2.3 0 4.4 3.6 8 8 8v3l4-4-4-4v3c-3.3 0-6-2.7-6-6z"/>
      </svg>
    `,
            closeOnActivate: true,
            onActivate: () => this.handleReplace(), // otevře file-picker a vymění URL
        };

        if (Array.isArray(base)) return [...base, metaItem, replaceItem];
        return [metaItem, replaceItem];
    }

    async handleEditMeta() {
        const captionNode = this.ui.nodes.caption;
        const captionText = htmlToText(captionNode.innerHTML);

        const result = await openImageMetaModal({
            alt: this._meta.alt,
            caption: captionText,
            author: this._meta.author,
        });
        if (!result) return;

        this._meta.alt = result.alt;
        this._meta.author = result.author;
        // Nezměněný popis necháme jak je, ať se neztratí případné inline formátování.
        if (result.caption !== captionText.trim()) {
            captionNode.textContent = result.caption;
        }
        this._refreshMetaButton();
        this.block?.dispatchChange?.();
    }

    _refreshMetaButton() {
        if (!this._metaButton) return;
        const filled = hasImageMeta({ ...this._meta, caption: this.ui.nodes.caption.textContent });
        this._metaButton.classList.toggle('image-meta-btn--filled', filled);
    }

    async handleReplace() {
        if (this._busy) return;
        this._busy = true;

        try {
            // otevřeme systémový file-picker
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';

            input.onchange = async () => {
                const file = input.files?.[0];
                if (!file) { this._busy = false; return; }

                try {
                    this.api.notifier.show({ message: 'Nahrávám obrázek…' });

                    // Stejné endpointy/field/hlavičky jako používá Image Tool (přebírá z configu)
                    const byFile  = this.config?.endpoints?.byFile;
                    const field   = this.config?.field || 'image';
                    const headers = this.config?.additionalRequestHeaders || {};
                    const extra   = this.config?.additionalRequestData || {};

                    const url = await uploadByFile(byFile, field, file, { headers, extra });

                    // Bezpečný update – neposíláme withBorder/withBackground/stretched
                    const { caption, alt, author } = this.save();
                    await safeUpdateImageBlock(this.api, url, { caption, alt, author });

                    this.api.toolbar.close();
                    this.api.notifier.show({ message: 'Obrázek vyměněn.', style: 'success' });
                } catch (e) {
                    console.error(e);
                    this.api.notifier.show({ message: 'Chyba při nahrávání.', style: 'error' });
                } finally {
                    this._busy = false;
                }
            };

            input.click();
        } catch (e) {
            console.error(e);
            this._busy = false;
        }
    }
}

/* ===== Pomocné funkce ===== */

async function uploadByFile(endpoint, field, file, { headers = {}, extra = {} } = {}) {
    if (!endpoint) throw new Error('Chybí endpoints.byFile v configu Image Toolu');
    const form = new FormData();
    form.append(field, file);
    Object.entries(extra).forEach(([k, v]) => form.append(k, v));

    const resp = await fetch(endpoint, { method: 'POST', body: form, headers });
    const json = await resp.json();
    if (json?.success !== 1) throw new Error('Upload selhal');
    return json?.file?.url || json?.data?.file?.url;
}

async function safeUpdateImageBlock(api, newUrl, { caption = '', alt = '', author = '' } = {}) {
    // najdeme aktuálně vybraný blok a pošleme jen hodnoty, které jsou bezpečné
    const i = api.blocks.getCurrentBlockIndex();
    const block = api.blocks.getBlockByIndex(i);

    await api.blocks.update(block.id, {
        file: { url: newUrl },
        url: newUrl,           // pro zpětnou kompatibilitu
        caption: caption ?? '',
        alt,
        author,
    });
}

function htmlToText(html) {
    const div = document.createElement('div');
    div.innerHTML = html || '';
    return div.textContent || '';
}

function ensureStyles() {
    if (document.getElementById(STYLE_ID)) return;
    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = `
      .image-with-meta .image-tool__caption { display: none !important; }
      .image-with-meta .image-tool__image { position: relative; }
      .image-with-meta__btn { position: absolute; top: 8px; right: 8px; z-index: 2; }
      .image-with-meta:not(.image-tool--filled) .image-with-meta__btn { display: none; }
    `;
    document.head.appendChild(style);
}
