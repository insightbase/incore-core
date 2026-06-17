export default class MultiImageRowTool {
  static get styleId() {
    return "multi-image-row-tool-styles";
  }

  static get toolbox() {
    return {
      title: "Image Row",
      icon: '<svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1m0 2v10h16V7zm3 2.5a1.5 1.5 0 1 1 0 3a1.5 1.5 0 0 1 0-3M6 16h12l-3.75-5l-3 4l-2.25-3z"/></svg>',
    };
  }

  static get isReadOnlySupported() {
    return true;
  }

  constructor({ data, readOnly, config }) {
    MultiImageRowTool._ensureStyles();

    this.readOnly = readOnly;
    this.config = config || {};
    const safeData = data || {};
    this.data = {
      images: Array.isArray(safeData.images) ? safeData.images : [],
      perRow: this._clamp(safeData.perRow, 1, 6, 3),
    };
    this._normalizeAllRows();

    this.wrapper = null;
    this.grid = null;
    this.perRowInput = null;
    this.fileInput = null;
  }

  render() {
    this.wrapper = document.createElement("div");
    this.wrapper.className = "multi-image-row-tool";

    if (!this.readOnly) {
      this.wrapper.appendChild(this._createControls());
    }

    this.grid = document.createElement("div");
    this.grid.className = "multi-image-row-grid";
    this.wrapper.appendChild(this.grid);

    this._renderGrid();

    return this.wrapper;
  }

  save() {
    return {
      images: this.data.images,
      perRow: this.data.perRow,
    };
  }

  validate(savedData) {
    return Array.isArray(savedData.images) && savedData.images.length > 0;
  }

  _createControls() {
    const controls = document.createElement("div");
    controls.className = "multi-image-row-controls";

    this.perRowInput = document.createElement("input");
    this.perRowInput.type = "text";
    this.perRowInput.inputMode = "numeric";
    this.perRowInput.pattern = "[0-9]*";
    this.perRowInput.value = String(this.data.perRow);
    this.perRowInput.className = "multi-image-row-input";

    this.perRowInput.addEventListener("change", () => {
      this.data.perRow = this._clamp(this.perRowInput.value, 1, 6, 3);
      this.perRowInput.value = String(this.data.perRow);
      this._normalizeAllRows();
      this._renderGrid();
    });

    this.fileInput = document.createElement("input");
    this.fileInput.type = "file";
    this.fileInput.accept = "image/*";
    this.fileInput.multiple = true;
    this.fileInput.className = "multi-image-row-file";

    this.fileInput.addEventListener("change", async (event) => {
      const files = Array.from(event.target.files || []);
      if (!files.length) {
        return;
      }

      const uploaded = await Promise.all(files.map((file) => this._uploadFile(file)));
      const newImages = uploaded
        .filter((result) => result !== null)
        .map((result, index) => ({
          url: result.url,
          alt: files[index].name,
        }));

      this.data.images = [...this.data.images, ...newImages];
      this._normalizeAllRows();
      this.fileInput.value = "";
      this._renderGrid();
    });

    const fileButton = document.createElement("button");
    fileButton.type = "button";
    fileButton.className = "multi-image-row-button";
    fileButton.textContent = "Přidat obrázky";
    fileButton.addEventListener("click", () => this.fileInput.click());

    const equalizeButton = document.createElement("button");
    equalizeButton.type = "button";
    equalizeButton.className = "multi-image-row-button";
    equalizeButton.textContent = "Rozdělit rovnoměrně";
    equalizeButton.addEventListener("click", () => {
      this._equalizeAllRows();
      this._renderGrid();
    });

    const perRowField = this._field("Počet na řádek", this.perRowInput);

    controls.appendChild(perRowField);
    controls.appendChild(fileButton);
    controls.appendChild(equalizeButton);
    controls.appendChild(this.fileInput);

    return controls;
  }

  _field(labelText, control) {
    const field = document.createElement("label");
    field.className = "multi-image-row-field";

    const text = document.createElement("span");
    text.className = "multi-image-row-label";
    text.textContent = labelText;

    field.appendChild(text);
    field.appendChild(control);

    return field;
  }

  _renderGrid() {
    if (!this.grid) {
      return;
    }

    this.grid.innerHTML = "";

    if (!this.data.images.length) {
      const empty = document.createElement("p");
      empty.className = "multi-image-row-empty";
      empty.textContent = "Zatím nejsou přidané žádné obrázky.";
      this.grid.appendChild(empty);
      return;
    }

    const rowsCount = Math.ceil(this.data.images.length / this.data.perRow);
    for (let row = 0; row < rowsCount; row += 1) {
      const rowStart = row * this.data.perRow;
      const rowEnd = Math.min(rowStart + this.data.perRow, this.data.images.length);
      const rowImages = this.data.images.slice(rowStart, rowEnd);

      const rowEl = document.createElement("div");
      rowEl.className = "multi-image-row-line";
      rowEl.style.gridTemplateColumns = rowImages.map((image) => `${image.width}fr`).join(" ");

      rowImages.forEach((image, rowIndex) => {
        const index = rowStart + rowIndex;
        const item = document.createElement("figure");
        item.className = "multi-image-row-item";

        const img = document.createElement("img");
        img.src = image.url;
        img.alt = image.alt || "";
        img.className = "multi-image-row-image";
        item.appendChild(img);

        if (!this.readOnly) {
          const toolbar = document.createElement("div");
          toolbar.className = "multi-image-row-item-toolbar";

          const widthWrap = document.createElement("div");
          widthWrap.className = "multi-image-row-range-wrap";

          const widthInput = document.createElement("input");
          widthInput.type = "text";
          widthInput.inputMode = "numeric";
          widthInput.pattern = "[0-9]*";
          widthInput.value = String(this.data.images[index].width);
          widthInput.className = "multi-image-row-width-input";

          const widthValue = document.createElement("span");
          widthValue.className = "multi-image-row-value";
          widthValue.textContent = "%";

          const updateWidth = () => {
            this._rebalanceRow(rowStart, index, Number(widthInput.value));
            this._renderGrid();
          };
          widthInput.addEventListener("change", updateWidth);
          widthInput.addEventListener("blur", updateWidth);

          widthWrap.appendChild(widthInput);
          widthWrap.appendChild(widthValue);

          const altInput = document.createElement("input");
          altInput.type = "text";
          altInput.placeholder = "Alt text";
          altInput.value = image.alt || "";
          altInput.className = "multi-image-row-alt";
          altInput.addEventListener("input", (event) => {
            this.data.images[index].alt = event.target.value;
          });

          const linkInput = document.createElement("input");
          linkInput.type = "url";
          linkInput.placeholder = "Odkaz (URL)";
          linkInput.value = image.link || "";
          linkInput.className = "multi-image-row-link";
          linkInput.addEventListener("input", (event) => {
            this.data.images[index].link = event.target.value;
          });
          linkInput.addEventListener("blur", (event) => {
            const normalized = MultiImageRowTool._normalizeLink(event.target.value);
            if (normalized !== event.target.value) {
              event.target.value = normalized;
              this.data.images[index].link = normalized;
            }
          });

          const removeBtn = document.createElement("button");
          removeBtn.type = "button";
          removeBtn.className = "multi-image-row-remove";
          removeBtn.textContent = "Odebrat";
          removeBtn.addEventListener("click", () => {
            this.data.images.splice(index, 1);
            this._normalizeAllRows();
            this._renderGrid();
          });

          toolbar.appendChild(widthWrap);
          toolbar.appendChild(altInput);
          toolbar.appendChild(linkInput);
          toolbar.appendChild(removeBtn);
          item.appendChild(toolbar);
        }

        rowEl.appendChild(item);
      });

      this.grid.appendChild(rowEl);
    }
  }

  async _uploadFile(file) {
    const endpoint = this.config?.endpoints?.byFile;
    if (!endpoint) {
      console.error("MultiImageRowTool: upload endpoint not configured");
      return null;
    }

    const formData = new FormData();
    formData.append("image", file);

    try {
      const response = await fetch(endpoint, { method: "POST", body: formData });
      const result = await response.json();
      if (result.success === 1 && result.file?.url) {
        return { url: result.file.url };
      }
      return null;
    } catch (e) {
      console.error("MultiImageRowTool: upload failed", e);
      return null;
    }
  }

  _clamp(value, min, max, fallback) {
    const numeric = Number(value);
    if (!Number.isFinite(numeric)) {
      return fallback;
    }
    return Math.min(max, Math.max(min, Math.round(numeric)));
  }

  static _normalizeLink(value) {
    const trimmed = (value || "").trim();
    if (trimmed === "") {
      return "";
    }
    if (/^[a-z][a-z0-9+.-]*:/i.test(trimmed)) {
      return trimmed;
    }
    if (trimmed.startsWith("//") || trimmed.startsWith("/") || trimmed.startsWith("#")) {
      return trimmed;
    }
    return "https://" + trimmed;
  }

  _normalizeAllRows() {
    if (!this.data.images.length) {
      return;
    }

    for (let rowStart = 0; rowStart < this.data.images.length; rowStart += this.data.perRow) {
      const rowEnd = Math.min(rowStart + this.data.perRow, this.data.images.length);
      const rowIndices = [];
      let assignedSum = 0;
      const missingIndices = [];

      for (let i = rowStart; i < rowEnd; i += 1) {
        rowIndices.push(i);
        const width = Number(this.data.images[i].width);
        if (Number.isFinite(width) && width >= 0) {
          this.data.images[i].width = this._clamp(width, 0, 100, 0);
          assignedSum += this.data.images[i].width;
        } else {
          missingIndices.push(i);
        }
      }

      if (missingIndices.length > 0) {
        const available = Math.max(0, 100 - assignedSum);
        const even = missingIndices.length > 0 ? Math.floor(available / missingIndices.length) : 0;
        missingIndices.forEach((idx) => {
          this.data.images[idx].width = even;
        });
        assignedSum = rowIndices.reduce((sum, idx) => sum + this.data.images[idx].width, 0);
      }

      if (assignedSum > 100) {
        const factor = 100 / assignedSum;
        let scaledSum = 0;
        rowIndices.forEach((idx, order) => {
          if (order === rowIndices.length - 1) {
            this.data.images[idx].width = Math.max(0, 100 - scaledSum);
            return;
          }
          const scaled = Math.max(0, Math.round(this.data.images[idx].width * factor));
          this.data.images[idx].width = scaled;
          scaledSum += scaled;
        });
      }
    }
  }

  _rebalanceRow(rowStart, changedIndex, desiredWidth) {
    const rowEnd = Math.min(rowStart + this.data.perRow, this.data.images.length);
    let othersSum = 0;

    for (let i = rowStart; i < rowEnd; i += 1) {
      if (i === changedIndex) {
        continue;
      }
      const width = Number(this.data.images[i].width);
      if (Number.isFinite(width) && width > 0) {
        othersSum += width;
      }
    }

    const maxAllowed = Math.max(0, 100 - othersSum);
    this.data.images[changedIndex].width = this._clamp(desiredWidth, 0, maxAllowed, 0);
  }

  _equalizeAllRows() {
    if (!this.data.images.length) {
      return;
    }

    for (let rowStart = 0; rowStart < this.data.images.length; rowStart += this.data.perRow) {
      const rowEnd = Math.min(rowStart + this.data.perRow, this.data.images.length);
      const count = rowEnd - rowStart;
      if (count <= 0) {
        continue;
      }

      const even = Math.floor(100 / count);
      for (let i = rowStart; i < rowEnd; i += 1) {
        this.data.images[i].width = even;
      }
    }
  }

  static _ensureStyles() {
    if (typeof document === "undefined" || document.getElementById(MultiImageRowTool.styleId)) {
      return;
    }

    const style = document.createElement("style");
    style.id = MultiImageRowTool.styleId;
    style.textContent = `
      .multi-image-row-tool {
        --multi-image-row-line: #e5e7eb;
        --multi-image-row-muted: #6b7280;
        border: 1px solid var(--multi-image-row-line);
        border-radius: 12px;
        padding: 12px;
        background: #fcfcfd;
      }

      .multi-image-row-controls {
        display: flex;
        flex-wrap: wrap;
        align-items: end;
        gap: 10px;
        margin-bottom: 12px;
      }

      .multi-image-row-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 180px;
      }

      .multi-image-row-label {
        font-size: 12px;
        color: var(--multi-image-row-muted);
      }

      .multi-image-row-input,
      .multi-image-row-alt,
      .multi-image-row-link {
        border: 1px solid var(--multi-image-row-line);
        border-radius: 8px;
        min-height: 34px;
        padding: 6px 10px;
        width: 100%;
        font: inherit;
      }

      .multi-image-row-range-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
      }

      .multi-image-row-value {
        min-width: 16px;
        color: var(--multi-image-row-muted);
        font-size: 13px;
      }

      .multi-image-row-width-input {
        border: 1px solid var(--multi-image-row-line);
        border-radius: 8px;
        min-height: 34px;
        padding: 6px 10px;
        width: 84px;
        min-width: 0;
        font: inherit;
      }

      .multi-image-row-file {
        display: none;
      }

      .multi-image-row-button,
      .multi-image-row-remove {
        border: 1px solid var(--multi-image-row-line);
        border-radius: 8px;
        background: #fff;
        padding: 8px 10px;
        cursor: pointer;
        color: inherit;
        font: inherit;
      }

      .multi-image-row-grid {
        display: grid;
        gap: 12px;
      }

      .multi-image-row-line {
        display: grid;
        gap: 12px;
        align-items: start;
      }

      .multi-image-row-item {
        margin: 0;
        border: 1px solid var(--multi-image-row-line);
        border-radius: 10px;
        padding: 8px;
        background: #fff;
        min-width: 0;
        overflow: hidden;
      }

      .multi-image-row-image {
        display: block;
        max-width: 100%;
        height: auto;
        margin: 0 auto;
        border-radius: 8px;
      }

      .multi-image-row-item-toolbar {
        display: grid;
        gap: 8px;
        margin-top: 8px;
        min-width: 0;
      }

      .multi-image-row-alt {
        min-width: 0;
      }

      .multi-image-row-remove {
        white-space: normal;
      }

      .multi-image-row-empty {
        margin: 0;
        color: var(--multi-image-row-muted);
        font-size: 14px;
      }
    `;

    document.head.appendChild(style);
  }
}
