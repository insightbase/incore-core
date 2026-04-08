export default class UploadLinkInlineTool {
    static get isInline() {
        return true;
    }

    static get title() {
        return 'Upload file';
    }

    // Editor.js může sanitizovat HTML — tady explicitně povolíme <a> a bezpečné atributy
    static get sanitize() {
        return {
            a: {
                href: true,
                target: true,
                rel: true
            }
        };
    }

    constructor({ api, config }) {
        this.api = api;
        this.config = config;

        this.button = null;
        this.tag = 'A';

        // volitelné: typy souborů
        this.accept = config?.accept ?? '*/*';
        this.field = config?.field ?? 'file';
    }

    render() {
        this.button = document.createElement('button');
        this.button.type = 'button';
        this.button.innerHTML = '📎';
        this.button.classList.add(this.api.styles.inlineToolButton);
        this.button.title = UploadLinkInlineTool.title;
        return this.button;
    }

    /**
     * Editor.js volá, když klikneš na tool.
     * range je aktuální selection.
     */
    surround(range) {
        // Pokud není nic vybráno, můžeš buď:
        // A) nic nedělat, nebo
        // B) vložit default text "ZDE" a ten zlinkovat.
        // Tady uděláme B), aby to bylo pohodlné.
        if (range.collapsed) {
            const textNode = document.createTextNode('ZDE');
            range.insertNode(textNode);

            // označ právě vložený text
            range.setStart(textNode, 0);
            range.setEnd(textNode, textNode.textContent.length);
        }

        this.openPickerAndUpload(range).catch((e) => {
            console.error(e);
            // můžeš nahradit vlastní notifikací
            this.api.notifier.show({
                message: 'Upload se nezdařil',
                style: 'error'
            });
        });
    }

    async openPickerAndUpload(range) {
        const file = await this.pickFile();
        if (!file) return;

        const url = await this.uploadFile(file);

        // Pokud už je selection uvnitř odkazu, jen přepiš href
        const parentA = this.api.selection.findParentTag(this.tag);
        if (parentA) {
            parentA.href = url;
            parentA.target = '_blank';
            parentA.rel = 'noopener noreferrer';
            return;
        }

        // Vytvoř nový <a> a obal selected text
        const a = document.createElement('a');
        a.href = url;
        a.target = '_blank';
        a.rel = 'noopener noreferrer';

        a.appendChild(range.extractContents());
        range.insertNode(a);

        // uprav selection na nový link
        this.api.selection.expandToTag(a);
    }

    pickFile() {
        return new Promise((resolve) => {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = this.accept;

            input.onchange = () => resolve(input.files?.[0] ?? null);
            input.click();
        });
    }

    async uploadFile(file) {
        if (!this.config?.endpoint) {
            throw new Error('Upload endpoint is not configured.');
        }

        const fd = new FormData();
        fd.append(this.field, file);

        const headers = this.config?.additionalRequestHeaders ?? {};

        const res = await fetch(this.config.endpoint, {
            method: 'POST',
            headers,
            body: fd,
            credentials: this.config?.credentials ?? 'same-origin'
        });

        if (!res.ok) {
            throw new Error(`Upload failed: HTTP ${res.status}`);
        }

        const json = await res.json();

        // očekáváme { success: 1, file: { url: "..." } }
        if (!json || json.success !== 1 || !json.file?.url) {
            throw new Error(`Upload failed: invalid response: ${JSON.stringify(json)}`);
        }

        return json.file.url;
    }

    /**
     * zvýraznění tlačítka, když je kurzor v <a>
     */
    checkState() {
        const isActive = !!this.api.selection.findParentTag(this.tag);
        this.button.classList.toggle(this.api.styles.inlineToolButtonActive, isActive);
    }
}
