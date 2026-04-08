/**
 * Editor.js Tool: Partner (with image upload)
 *
 * config:
 * {
 *   endpoints: { byFile: "https://..." },
 *   field: "image", // optional: form field name (default "image")
 *   additionalRequestHeaders: { Authorization: "Bearer ..." } // optional
 * }
 *
 * expected response (like Image tool):
 * { success: 1, file: { url: "https://..." } }
 */

export default class Partner {
    static get toolbox() {
        return {
            title: "Partner",
            icon:
                '<svg width="18" height="18" viewBox="0 0 24 24"><path d="M16 11c1.66 0 3-1.34 3-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3Zm-8 0c1.66 0 3-1.34 3-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3Zm0 2c-2.33 0-7 1.17-7 3.5V20h14v-3.5C15 14.17 10.33 13 8 13Zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.93 1.97 3.45V20h7v-3.5c0-2.33-4.67-3.5-7-3.5Z"/></svg>',
        };
    }

    static get sanitize() {
        return {
            name: true,
            title: true,
            description: true,
            link: true,
            image: { url: true },
            imageAlt: true,
        };
    }

    constructor({ data, api, config }) {
        this.api = api;
        this.config = config || {};

        this.data = {
            name: data?.name || "",
            title: data?.title || "",
            description: data?.description || "",
            link: data?.link || "",
            image: data?.image?.url ? { url: data.image.url } : { url: "" },
            imageAlt: data?.imageAlt || "",
        };

        this.nodes = {};
        this.uploading = false;
    }

    render() {
        const wrapper = document.createElement("div");
        wrapper.classList.add("partner-tool");

        // Text fields
        const nameInput = this._makeInput("Název", "Název partnera", this.data.name);
        nameInput.addEventListener("input", (e) => (this.data.name = e.target.value));

        const titleInput = this._makeInput("Titulek", "např. Partner soutěže", this.data.title);
        titleInput.addEventListener("input", (e) => (this.data.title = e.target.value));

        const descInput = this._makeTextarea("Popis", "Krátký popis…", this.data.description);
        descInput.addEventListener("input", (e) => (this.data.description = e.target.value));

        const linkInput = this._makeInput("Odkaz", "https://…", this.data.link, "url");
        linkInput.addEventListener("input", (e) => (this.data.link = e.target.value));

        const altInput = this._makeInput("Alt text", "Popis obrázku…", this.data.imageAlt);
        altInput.addEventListener("input", (e) => (this.data.imageAlt = e.target.value));

        // Image uploader UI
        const uploader = this._makeUploader();

        // Preview
        const preview = document.createElement("div");
        preview.classList.add("partner-tool__preview");
        this.nodes.preview = preview;
        this._updatePreview();

        wrapper.append(
            this._makeField("Název", nameInput),
            this._makeField("Titulek", titleInput),
            this._makeField("Popis", descInput),
            this._makeField("Odkaz", linkInput),
            this._makeField("Obrázek", uploader),
            this._makeField("Alt", altInput),
            preview
        );

        return wrapper;
    }

    save() {
        return {
            name: (this.data.name || "").trim(),
            title: (this.data.title || "").trim(),
            description: (this.data.description || "").trim(),
            link: (this.data.link || "").trim(),
            image: { url: (this.data.image?.url || "").trim() },
            imageAlt: (this.data.imageAlt || "").trim(),
        };
    }

    validate(savedData) {
        return true;
        // minimálně aspoň něco vyplněné
        if (!savedData.name && !savedData.title) return false;

        // pokud je link, musí být URL
        if (savedData.link && !this._looksLikeUrl(savedData.link)) return false;

        // pokud je image url, musí být URL
        if (savedData.image?.url && !this._looksLikeUrl(savedData.image.url)) return false;

        return true;
    }

    // ---------------- UI helpers ----------------

    _makeField(labelText, element) {
        const field = document.createElement("div");
        field.classList.add("partner-tool__field");

        const label = document.createElement("div");
        label.classList.add("partner-tool__label");
        label.textContent = labelText;

        field.append(label, element);
        return field;
    }

    _makeInput(aria, placeholder, value, type = "text") {
        const input = document.createElement("input");
        input.type = type;
        input.placeholder = placeholder;
        input.value = value || "";
        input.classList.add("partner-tool__input", "cdx-input");
        input.setAttribute("aria-label", aria);
        return input;
    }

    _makeTextarea(aria, placeholder, value) {
        const ta = document.createElement("textarea");
        ta.placeholder = placeholder;
        ta.value = value || "";
        ta.rows = 3;
        ta.classList.add("partner-tool__textarea", "cdx-input");
        ta.setAttribute("aria-label", aria);
        return ta;
    }

    _makeUploader() {
        const endpoints = this.config.endpoints || {};
        const byFile = endpoints.byFile;

        const box = document.createElement("div");
        box.classList.add("partner-tool__uploader");

        const hint = document.createElement("div");
        hint.classList.add("partner-tool__uploaderHint");
        hint.textContent = byFile
            ? "Přetáhni sem obrázek nebo klikni pro výběr."
            : "Chybí config.endpoints.byFile (upload endpoint).";

        const buttonRow = document.createElement("div");
        buttonRow.classList.add("partner-tool__uploaderButtons");

        const pickBtn = document.createElement("button");
        pickBtn.type = "button";
        pickBtn.classList.add("partner-tool__btn");
        pickBtn.textContent = "Vybrat soubor ( 150 x 150px )";

        const removeBtn = document.createElement("button");
        removeBtn.type = "button";
        removeBtn.classList.add("partner-tool__btn", "partner-tool__btn--danger");
        removeBtn.textContent = "Odebrat";
        removeBtn.disabled = !this.data.image?.url;

        const fileInput = document.createElement("input");
        fileInput.type = "file";
        fileInput.accept = "image/*";
        fileInput.style.display = "none";

        const status = document.createElement("div");
        status.classList.add("partner-tool__status");

        const progressWrap = document.createElement("div");
        progressWrap.classList.add("partner-tool__progressWrap");

        const progressBar = document.createElement("div");
        progressBar.classList.add("partner-tool__progressBar");
        progressBar.style.width = "0%";

        progressWrap.append(progressBar);

        this.nodes.status = status;
        this.nodes.progressBar = progressBar;
        this.nodes.removeBtn = removeBtn;

        const openPicker = () => {
            if (!byFile) return;
            fileInput.click();
        };

        pickBtn.addEventListener("click", openPicker);
        hint.addEventListener("click", openPicker);

        fileInput.addEventListener("change", async (e) => {
            const file = e.target.files?.[0];
            if (!file) return;
            await this._uploadFile(file);
            fileInput.value = ""; // allow re-select same file
        });

        removeBtn.addEventListener("click", () => {
            this.data.image = { url: "" };
            removeBtn.disabled = true;
            this._setStatus("");
            this._setProgress(0);
            this._updatePreview();
        });

        // Drag & drop
        const prevent = (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
        };

        ["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
            box.addEventListener(eventName, prevent);
        });

        box.addEventListener("dragover", () => box.classList.add("is-dragover"));
        box.addEventListener("dragleave", () => box.classList.remove("is-dragover"));

        box.addEventListener("drop", async (e) => {
            box.classList.remove("is-dragover");
            if (!byFile) return;
            const file = e.dataTransfer?.files?.[0];
            if (!file) return;
            if (!file.type?.startsWith("image/")) {
                this._setStatus("Soubor není obrázek.", true);
                return;
            }
            await this._uploadFile(file);
        });

        buttonRow.append(pickBtn, removeBtn);

        box.append(hint, buttonRow, progressWrap, status, fileInput);

        // initial state
        if (this.data.image?.url) {
            this._setStatus("Obrázek je nastaven.", false);
            this._setProgress(100);
        }

        return box;
    }

    // ---------------- Upload logic ----------------

    async _uploadFile(file) {
        const byFile = this.config?.endpoints?.byFile;
        if (!byFile) {
            this._setStatus("Chybí endpoints.byFile.", true);
            return;
        }

        if (this.uploading) return;

        this.uploading = true;
        this._setStatus(`Nahrávám: ${file.name}`, false);
        this._setProgress(0);

        try {
            const result = await this._xhrUpload(byFile, file);

            // Expect { success: 1, file: { url } }
            if (!result || result.success !== 1 || !result.file?.url) {
                throw new Error("Neplatná odpověď serveru (čekám {success:1,file:{url}}).");
            }

            this.data.image = { url: result.file.url };
            this.nodes.removeBtn.disabled = false;

            this._setStatus("Nahráno.", false);
            this._setProgress(100);
            this._updatePreview();
        } catch (err) {
            this._setStatus(err?.message || "Upload selhal.", true);
            this._setProgress(0);
        } finally {
            this.uploading = false;
        }
    }

    _xhrUpload(url, file) {
        const fieldName = this.config.field || "image";
        const headers = this.config.additionalRequestHeaders || {};

        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", url, true);

            Object.entries(headers).forEach(([k, v]) => xhr.setRequestHeader(k, v));

            xhr.responseType = "json";

            xhr.upload.onprogress = (e) => {
                if (!e.lengthComputable) return;
                const pct = Math.round((e.loaded / e.total) * 100);
                this._setProgress(pct);
            };

            xhr.onerror = () => reject(new Error("Síťová chyba při uploadu."));
            xhr.onload = () => {
                const statusOk = xhr.status >= 200 && xhr.status < 300;
                if (!statusOk) {
                    reject(new Error(`Upload selhal (HTTP ${xhr.status}).`));
                    return;
                }

                // some backends return text; fallback parse
                const resp = xhr.response || this._safeJsonParse(xhr.responseText);
                resolve(resp);
            };

            const formData = new FormData();
            formData.append(fieldName, file);
            xhr.send(formData);
        });
    }

    _safeJsonParse(text) {
        try {
            return JSON.parse(text);
        } catch {
            return null;
        }
    }

    // ---------------- Preview ----------------

    _updatePreview() {
        if (!this.nodes.preview) return;
        this.nodes.preview.innerHTML = this._previewHtml();
    }

    _previewHtml() {
        const esc = (s = "") =>
            String(s)
                .replaceAll("&", "&amp;")
                .replaceAll("<", "&lt;")
                .replaceAll(">", "&gt;")
                .replaceAll('"', "&quot;")
                .replaceAll("'", "&#039;");

        const name = esc(this.data.name);
        const title = esc(this.data.title);
        const desc = esc(this.data.description);
        const link = esc(this.data.link);
        const img = esc(this.data.image?.url || "");
        const alt = esc(this.data.imageAlt);

        const imgHtml = img
            ? `<div class="partner-tool__imgWrap"><img src="${img}" alt="${alt}" loading="lazy"></div>`
            : `<div class="partner-tool__imgWrap partner-tool__imgWrap--empty">Bez obrázku</div>`;

        const linkHtml = link
            ? `<a class="partner-tool__link" href="${link}" target="_blank" rel="noopener noreferrer">${link}</a>`
            : "";

        return `
      <div class="partner-tool__card">
        ${imgHtml}
        <div class="partner-tool__meta">
          ${title ? `<div class="partner-tool__title">${title}</div>` : ""}
          ${name ? `<div class="partner-tool__name">${name}</div>` : ""}
          ${desc ? `<div class="partner-tool__desc">${desc}</div>` : ""}
          ${linkHtml}
        </div>
      </div>
    `;
    }

    // ---------------- Status helpers ----------------

    _setStatus(text, isError = false) {
        if (!this.nodes.status) return;
        this.nodes.status.textContent = text || "";
        this.nodes.status.classList.toggle("is-error", !!isError);
    }

    _setProgress(pct) {
        if (!this.nodes.progressBar) return;
        const val = Math.max(0, Math.min(100, Number(pct) || 0));
        this.nodes.progressBar.style.width = `${val}%`;
    }

    _looksLikeUrl(url) {
        if (!url) return true;

        // povol relativní /cesta nebo ./cesta
        if (url.startsWith("/") || url.startsWith("./") || url.startsWith("../")) return true;

        try {
            new URL(url); // absolutní
            return true;
        } catch {
            return false;
        }
    }
}
