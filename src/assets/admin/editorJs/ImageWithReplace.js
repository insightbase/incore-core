// ImageWithReplace.js
import ImageTool from '@editorjs/image';

/**
 * Rozšíření @editorjs/image, které přidá do settings menu tlačítko „Vyměnit obrázek“.
 * Nepoužívá tune ani actions → stabilní mount, žádné classList chyby.
 */
export default class ImageWithReplace extends ImageTool {
    renderSettings() {
        const base = super.renderSettings?.(); // může vrátit pole settings objektů

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

        if (Array.isArray(base)) return [...base, replaceItem];
        return [replaceItem];
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
                    await safeUpdateImageBlock(this.api, url, this.data?.caption);

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

async function safeUpdateImageBlock(api, newUrl, caption = '') {
    // najdeme aktuálně vybraný blok a pošleme jen hodnoty, které jsou bezpečné
    const i = api.blocks.getCurrentBlockIndex();
    const block = api.blocks.getBlockByIndex(i);

    await api.blocks.update(block.id, {
        file: { url: newUrl },
        url: newUrl,           // pro zpětnou kompatibilitu
        caption: caption ?? ''
    });
}
