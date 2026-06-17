/**
 * ImageGallery — Editor.js block tool
 *
 * Replaces the old @editorjs/gallery tool.
 * Identical save output: { files: [{url},...], caption: string, style: string }
 *
 * Register as:
 *   gallery: {
 *     class: ImageGallery,
 *     config: {
 *       sortableJs: Sortable,
 *       endpoints: { byFile: '/upload' },
 *       // optional:
 *       field: 'image',
 *       additionalRequestHeaders: {},
 *       additionalRequestData: {},
 *       maxElementCount: null,
 *     }
 *   }
 */
export default class ImageGallery {

  // ─── Static ──────────────────────────────────────────────────────────────────

  static get toolbox() {
    return {
      title: 'Galerie',
      icon: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="3" y="3" width="8" height="8" rx="2" stroke="currentColor" stroke-width="2"/>
        <rect x="13" y="3" width="8" height="8" rx="2" stroke="currentColor" stroke-width="2"/>
        <rect x="3" y="13" width="8" height="8" rx="2" stroke="currentColor" stroke-width="2"/>
        <rect x="13" y="13" width="8" height="8" rx="2" stroke="currentColor" stroke-width="2"/>
      </svg>`,
    };
  }

  static get isReadOnlySupported() {
    return true;
  }

  // ─── Constructor ──────────────────────────────────────────────────────────────

  constructor({ data, config, api, readOnly }) {
    this.api     = api;
    this.config  = config || {};
    this.readOnly = !!readOnly;

    this.data = {
      files:   Array.isArray(data?.files)  ? data.files  : [],
      caption: data?.caption ?? '',
      style:   data?.style   ?? '',
    };

    this.nodes    = {};
    this.sortable = null;
  }

  // ─── Render ───────────────────────────────────────────────────────────────────

  render() {
    this._injectStyles();

    // Outer block
    const block = document.createElement('div');
    block.className = 'gallery-block';

    const title = document.createElement('p');
    title.className = 'gallery-block-title';
    title.textContent = 'Galerie';

    // Content wrapper
    const content = document.createElement('div');
    content.className = 'gallery-block-content';
    this.nodes.content = content;

    block.append(title, content);
    this.nodes.block = block;

    // Render initial state
    this._renderState();

    // Init Sortable after DOM is attached (onRendered-style)
    requestAnimationFrame(() => this._initSortable());

    return block;
  }

  // ─── State rendering ──────────────────────────────────────────────────────────

  _renderState() {
    const content = this.nodes.content;
    content.innerHTML = '';

    if (this.data.files.length === 0 && !this.readOnly) {
      // Empty state with Partner-style uploader
      content.appendChild(this._buildEmptyState());
    } else {
      // Grid + button state
      const grid = document.createElement('div');
      grid.className = 'gallery-grid';
      this.nodes.grid = grid;

      this.data.files.forEach((file) => this._addItem(file.url));
      content.appendChild(grid);

      if (!this.readOnly) {
        const selectBtn = document.createElement('button');
        selectBtn.type = 'button';
        selectBtn.className = 'gallery-select-btn';
        selectBtn.textContent = 'Vybrat fotky';
        selectBtn.addEventListener('click', () => this._openPicker());
        this.nodes.selectBtn = selectBtn;
        content.appendChild(selectBtn);
        this._checkLimit();
      }
    }
  }

  _buildEmptyState() {
    const dropZone = document.createElement('div');
    dropZone.className = 'gallery-empty-state';

    dropZone.innerHTML = `
      <div class="gallery-empty-left">
        <svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M18 2.31a8 8 0 0 1 8 0l11.053 6.38a8 8 0 0 1 4 6.929V28.38a8 8 0 0 1-4 6.928L26 41.691a8 8 0 0 1-8 0L6.947 35.309a8 8 0 0 1-4-6.928V15.62a8 8 0 0 1 4-6.928z" fill="#fff"/>
          <path d="M18.25 2.742a7.5 7.5 0 0 1 7.5 0l11.053 6.382a7.5 7.5 0 0 1 3.75 6.495v12.762a7.5 7.5 0 0 1-3.75 6.495L25.75 41.258a7.5 7.5 0 0 1-7.281.12l-.219-.12-11.053-6.382a7.5 7.5 0 0 1-3.75-6.495V15.619a7.5 7.5 0 0 1 3.75-6.495z" stroke="#1b84ff" stroke-opacity=".2"/>
          <g clip-path="url(#gallery-img-clip)" fill="#1b84ff">
            <path opacity=".1" d="M25.12 14.125h-6.165a5.07 5.07 0 0 0-3.533 1.488 4.95 4.95 0 0 0-1.447 3.515v5.93a4.94 4.94 0 0 0 .66 2.469l.075.133c.097.156.195.297.3.438l.127.163q.171.2.353.378l.217.2.285.222c.105.082.218.156.33.23s.233.141.345.2l.24.119c.158.067.315.133.48.185l.158.052q.273.08.555.126h.18q.331.049.667.052h6.165a5.07 5.07 0 0 0 3.525-1.472A4.95 4.95 0 0 0 30.1 25.06v-5.93a4.95 4.95 0 0 0-1.447-3.516 5.07 5.07 0 0 0-3.533-1.488"/>
            <path d="M25.083 13.938h-6.165A5.04 5.04 0 0 0 13.938 19v6a5.05 5.05 0 0 0 .66 2.497l.075.136c.097.157.195.3.3.442l.127.165q.171.203.353.383l.217.202.285.225a5 5 0 0 0 .675.435l.24.12c.158.067.315.135.48.188l.158.052q.273.081.555.128h.18q.331.048.667.052h6.165A5.04 5.04 0 0 0 30.063 25v-6a5.04 5.04 0 0 0-4.98-5.062M28.938 25a3.895 3.895 0 0 1-3.855 3.93h-6.165a4 4 0 0 1-.668-.067h-.157a3.8 3.8 0 0 1-1.268-.548l-.075-.09a4 4 0 0 1-.502-.428 4.4 4.4 0 0 1-.503-.547l-.075-.128c-.052-.082-.097-.18-.142-.27l2.25-1.822a1.78 1.78 0 0 1 2.332.067 2.25 2.25 0 0 0 3.195-.157l1.5-1.635a1.79 1.79 0 0 1 2.61-.06l1.545 1.567zm0-1.823-.75-.75a2.92 2.92 0 0 0-2.138-.87 2.87 2.87 0 0 0-2.092.968l-1.5 1.628a1.13 1.13 0 0 1-1.613.082 2.895 2.895 0 0 0-3.75-.113L15.13 25.69a3.5 3.5 0 0 1-.067-.69v-6a3.893 3.893 0 0 1 3.855-3.915h6.165A3.893 3.893 0 0 1 28.938 19zM18.843 16.99a2.101 2.101 0 1 0 2.062 2.1 2.08 2.08 0 0 0-2.062-2.1m0 3.06a.96.96 0 1 1 .907-.96.953.953 0 0 1-.907.96"/>
          </g>
          <defs><clipPath id="gallery-img-clip"><path fill="#fff" d="M13 13h18v18H13z"/></clipPath></defs>
        </svg>
        <div class="gallery-empty-text">
          <p class="gallery-empty-hint">Kliknutím nebo přetažení nahrajete soubory</p>
          <p class="gallery-empty-support">SVG, PNG, JPG (max. 800×400)</p>
        </div>
      </div>
      <button class="gallery-empty-btn" type="button">Vybrat fotky</button>
    `;

    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.multiple = true;
    fileInput.accept = this.config.types || 'image/*';
    fileInput.hidden = true;
    dropZone.appendChild(fileInput);

    const openPicker = () => fileInput.click();

    dropZone.querySelector('.gallery-empty-btn').addEventListener('click', (e) => {
      e.stopPropagation();
      openPicker();
    });

    dropZone.addEventListener('click', (e) => {
      if (!e.target.closest('.gallery-empty-btn')) openPicker();
    });

    fileInput.addEventListener('change', (e) => {
      const files = Array.from(e.target.files || []);
      if (files.length > 0) this._handleFiles(files);
    });

    // Drag & drop
    dropZone.addEventListener('dragover', (e) => {
      e.preventDefault();
      dropZone.classList.add('gallery-empty-state--drag');
    });
    dropZone.addEventListener('dragleave', () => {
      dropZone.classList.remove('gallery-empty-state--drag');
    });
    dropZone.addEventListener('drop', (e) => {
      e.preventDefault();
      dropZone.classList.remove('gallery-empty-state--drag');
      const files = Array.from(e.dataTransfer?.files || []).filter(f => f.type.startsWith('image/'));
      if (files.length > 0) this._handleFiles(files);
    });

    return dropZone;
  }

  _handleFiles(files) {
    const max = this.config.maxElementCount;
    const remaining = max != null ? max - this.data.files.length : Infinity;
    const toUpload = files.slice(0, remaining > 0 ? remaining : 0);

    // If in empty state, transition to grid state before uploading
    if (!this.nodes.grid && toUpload.length > 0) {
      this._transitionToGridState();
    }

    toUpload.forEach((file) => this._uploadFile(file));
  }

  _transitionToGridState() {
    const content = this.nodes.content;
    content.innerHTML = '';

    // Create grid
    const grid = document.createElement('div');
    grid.className = 'gallery-grid';
    this.nodes.grid = grid;
    content.appendChild(grid);

    // Create select button
    const selectBtn = document.createElement('button');
    selectBtn.type = 'button';
    selectBtn.className = 'gallery-select-btn';
    selectBtn.textContent = 'Vybrat fotky';
    selectBtn.addEventListener('click', () => this._openPicker());
    this.nodes.selectBtn = selectBtn;
    content.appendChild(selectBtn);
  }

  // ─── Sortable ─────────────────────────────────────────────────────────────────

  _initSortable() {
    if (this.readOnly || !this.config.sortableJs || !this.nodes.grid) return;
    if (this.sortable) { this.sortable.destroy(); this.sortable = null; }

    this.sortable = new this.config.sortableJs(this.nodes.grid, {
      animation: 150,
      ghostClass: 'gallery-item--ghost',
      filter: '.gallery-item-delete',
      onStart: () => this.nodes.grid.classList.add('gallery-grid--dragging'),
      onEnd: (evt) => {
        this.nodes.grid.classList.remove('gallery-grid--dragging');
        const { oldIndex, newIndex } = evt;
        if (oldIndex !== newIndex) {
          const moved = this.data.files.splice(oldIndex, 1)[0];
          this.data.files.splice(newIndex, 0, moved);
        }
      },
    });
  }

  // ─── Item DOM ─────────────────────────────────────────────────────────────────

  _addItem(url) {
    const item = document.createElement('div');
    item.className = 'gallery-item';
    item.draggable = !this.readOnly;

    // Thumbnail
    const img = document.createElement('img');
    img.src = url;
    img.alt = 'Gallery image';
    img.className = 'gallery-item-img';
    img.loading = 'lazy';

    // Drag overlay
    const overlay = document.createElement('div');
    overlay.className = 'gallery-item-overlay';
    overlay.innerHTML = `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M12 6L14 8M14 8L12 10M14 8H10M4 6L2 8M2 8L4 10M2 8H6M6 12L8 14M8 14L10 12M8 14V10M10 4L8 2M8 2L6 4M8 2V6" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>`;

    item.append(img, overlay);

    // Delete button (edit mode only)
    if (!this.readOnly) {
      const deleteBtn = document.createElement('button');
      deleteBtn.type = 'button';
      deleteBtn.className = 'gallery-item-delete';
      deleteBtn.setAttribute('aria-label', 'Odstranit obrázek');
      deleteBtn.innerHTML = `<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M7.8216 7.00005L9.91576 4.92338C10.0272 4.81044 10.0891 4.65788 10.088 4.49926C10.0869 4.34063 10.0229 4.18894 9.90993 4.07755C9.79699 3.96616 9.64443 3.90419 9.48581 3.90529C9.32718 3.90638 9.17549 3.97044 9.0641 4.08338L6.9991 6.17755L4.9341 4.12422C4.8248 4.01557 4.67696 3.95459 4.52285 3.95459C4.36874 3.95459 4.22089 4.01557 4.1116 4.12422C4.05692 4.17844 4.01353 4.24296 3.98391 4.31405C3.9543 4.38513 3.93905 4.46138 3.93905 4.53838C3.93905 4.61539 3.9543 4.69163 3.98391 4.76272C4.01353 4.8338 4.05692 4.89832 4.1116 4.95255L6.1766 7.00005L4.08243 9.07672C3.97104 9.18965 3.90908 9.34222 3.91017 9.50084C3.91126 9.65946 3.97533 9.81116 4.08827 9.92255C4.2012 10.0339 4.35377 10.0959 4.51239 10.0948C4.67101 10.0937 4.82271 10.0297 4.9341 9.91672L6.9991 7.82255L9.0641 9.87588C9.17339 9.98453 9.32124 10.0455 9.47535 10.0455C9.62946 10.0455 9.7773 9.98453 9.8866 9.87588C9.94127 9.82165 9.98467 9.75714 10.0143 9.68605C10.0439 9.61497 10.0591 9.53872 10.0591 9.46171C10.0591 9.38471 10.0439 9.30846 10.0143 9.23738C9.98467 9.16629 9.94127 9.10178 9.8866 9.04755L7.8216 7.00005Z" fill="#99A1B7"/>
      </svg>`;

      deleteBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const idx = this._indexOfItem(item);
        if (idx !== -1) this.data.files.splice(idx, 1);

        item.style.transition = 'opacity .2s, transform .2s';
        item.style.opacity    = '0';
        item.style.transform  = 'scale(0.8)';
        setTimeout(() => {
          item.remove();
          this._checkLimit();
          // If no files left, switch back to empty state
          if (this.data.files.length === 0) {
            this._renderState();
          }
        }, 200);
      });

      item.appendChild(deleteBtn);
    }

    this.nodes.grid.appendChild(item);
    this._checkLimit();
    return item;
  }

  // Skeleton placeholder shown while uploading
  _addSkeleton() {
    const item = document.createElement('div');
    item.className = 'gallery-item gallery-item--skeleton';
    item.innerHTML = `<div class="gallery-item-skeleton-inner"></div>`;
    this.nodes.grid.appendChild(item);
    this._checkLimit();
    return item;
  }

  _indexOfItem(itemEl) {
    const items = Array.from(this.nodes.grid.querySelectorAll('.gallery-item:not(.gallery-item--skeleton)'));
    return items.indexOf(itemEl);
  }

  // ─── File picker ──────────────────────────────────────────────────────────────

  _openPicker() {
    const input = document.createElement('input');
    input.type     = 'file';
    input.multiple = true;
    input.accept   = this.config.types || 'image/*';
    input.style.display = 'none';
    document.body.appendChild(input);

    input.addEventListener('change', () => {
      const files = Array.from(input.files || []);
      document.body.removeChild(input);
      if (files.length > 0) this._handleFiles(files);
    });

    input.click();
  }

  // ─── Upload ───────────────────────────────────────────────────────────────────

  _uploadFile(file) {
    const skeleton = this._addSkeleton();

    const byFile = this.config?.endpoints?.byFile;

    if (!byFile) {
      // Dev fallback: base64 preview
      const reader = new FileReader();
      reader.onload = (e) => {
        const url = e.target.result;
        skeleton.remove();
        this.data.files.push({ url });
        this._addItem(url);
      };
      reader.onerror = () => {
        skeleton.remove();
        this._checkLimit();
      };
      reader.readAsDataURL(file);
      return;
    }

    const fieldName = this.config.field || 'image';
    const headers   = this.config.additionalRequestHeaders || {};
    const extraData = this.config.additionalRequestData || {};

    const fd = new FormData();
    fd.append(fieldName, file, file.name);
    Object.entries(extraData).forEach(([k, v]) => fd.append(k, v));

    const xhr = new XMLHttpRequest();
    xhr.open('POST', byFile, true);
    xhr.responseType = 'json';
    Object.entries(headers).forEach(([k, v]) => xhr.setRequestHeader(k, v));

    xhr.onload = () => {
      skeleton.remove();
      if (xhr.status >= 200 && xhr.status < 300) {
        const res = xhr.response || this._safeParse(xhr.responseText);
        if (res?.success === 1 && res?.file?.url) {
          this.data.files.push({ url: res.file.url });
          this._addItem(res.file.url);
        } else {
          this._onUploadError(`Bad response: ${JSON.stringify(res)}`);
        }
      } else {
        this._onUploadError(`HTTP ${xhr.status}`);
      }
      this._checkLimit();
    };

    xhr.onerror = () => {
      skeleton.remove();
      this._onUploadError('Network error');
      this._checkLimit();
    };

    xhr.send(fd);
  }

  _onUploadError(reason) {
    console.warn('[ImageGallery] Upload failed:', reason);
    if (this.api?.notifier) {
      this.api.notifier.show({ message: 'Nahrávání selhalo. Zkuste to znovu.', style: 'error' });
    }
  }

  // ─── Limit guard ──────────────────────────────────────────────────────────────

  _checkLimit() {
    if (!this.nodes.selectBtn) return;
    const max = this.config.maxElementCount;
    if (max != null && this.data.files.length >= max) {
      this.nodes.selectBtn.style.display = 'none';
    } else {
      this.nodes.selectBtn.style.display = '';
    }
  }

  // ─── Save / Validate ──────────────────────────────────────────────────────────

  save() {
    return {
      files:   this.data.files,
      caption: this.data.caption || '',
      style:   this.data.style   || '',
    };
  }

  validate(saved) {
    return Array.isArray(saved.files);
  }

  // ─── Helpers ─────────────────────────────────────────────────────────────────

  _safeParse(text) {
    try { return JSON.parse(text); } catch { return null; }
  }

  // ─── Styles ───────────────────────────────────────────────────────────────────

  _injectStyles() {
    const id = 'editorjs-image-gallery-styles';
    if (document.getElementById(id)) return;
    const style = document.createElement('style');
    style.id = id;
    style.textContent = `
      .gallery-block {
        border: 1px solid var(--tw-gray-200, #e5e7eb);
        background-color: #fff;
        padding: 12px;
        border-radius: 8px;
        max-width: 475px;
        font-family: inherit;
        margin-top: .4em;
        margin-bottom: .4em;
      }
      .gallery-block-title {
        font-size: 13px;
        color: var(--tw-gray-700, #4b5675);
        font-weight: 500;
        margin: 0 0 12px;
        letter-spacing: -0.01em;
        line-height: normal;
      }
      .gallery-block-content {
        display: flex;
        align-items: flex-end;
        gap: 12px;
      }

      /* ── Empty state (Partner-style uploader) ── */
      .gallery-empty-state {
        border: 1px dashed var(--tw-gray-300, #dbdfe9);
        padding: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border-radius: 12px;
        cursor: pointer;
        transition: border-color .18s, background .18s;
        user-select: none;
        box-sizing: border-box;
      }
      .gallery-empty-state:hover {
        border-color: #1b84ff;
        background: #f5f9ff;
      }
      .gallery-empty-state--drag {
        border-color: #1b84ff;
        background: #eef5ff;
      }
      .gallery-empty-left {
        display: flex;
        align-items: center;
        gap: 6px;
        min-width: 0;
      }
      .gallery-empty-text {
        display: flex;
        flex-direction: column;
        gap: 5px;
      }
      .gallery-empty-hint {
        color: #071437;
        font-weight: 500;
        font-size: 12px;
        line-height: 1;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 260px;
      }
      .gallery-empty-support {
        color: #4b5675;
        font-size: 11px;
        font-weight: 400;
        line-height: 1;
        margin: 0;
      }
      .gallery-empty-btn {
        flex-shrink: 0;
        height: 32px;
        padding-inline: 12px;
        padding-block: 10px;
        font-size: 12px;
        font-weight: 500;
        color: #4b5675;
        border: 1px solid var(--tw-gray-300, #dbdfe9);
        border-radius: 6px;
        background: #fff;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        white-space: nowrap;
        line-height: 1;
        transition: border-color .15s, background .15s, color .15s;
        font-family: inherit;
      }
      .gallery-empty-btn:hover {
        border-color: #1b84ff;
        color: #1b84ff;
        background: #f5f9ff;
      }
      .gallery-grid {
        flex: 1;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
      }
      .gallery-grid--dragging .gallery-item {
        cursor: grabbing;
      }
      .gallery-select-btn {
        flex-shrink: 0;
        padding: 8px 12px;
        background: #fff;
        border: 1px solid var(--tw-gray-300, #dbdfe9);
        border-radius: 8px;
        color: var(--tw-gray-700, #4b5675);
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: background .15s, border-color .15s, color .15s;
        white-space: nowrap;
        height: fit-content;
        font-family: inherit;
        line-height: normal;
      }
      .gallery-select-btn:hover {
        background: #f9fafb;
        border-color: #1b84ff;
        color: #1b84ff;
      }

      /* ── Items ── */
      .gallery-item {
        position: relative;
        width: 40px;
        height: 40px;
        flex-shrink: 0;
        cursor: move;
        transition: transform .18s;
      }
      .gallery-item:hover {
        transform: scale(1.04);
      }
      .gallery-item--ghost {
        opacity: .45;
      }
      .gallery-item-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 8px;
        display: block;
        pointer-events: none;
        user-select: none;
      }
      .gallery-item-delete {
        position: absolute;
        top: -3px;
        right: -3px;
        width: 16px;
        height: 16px;
        background: #fff;
        border: 1px solid var(--tw-gray-300, #dbdfe9);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 2;
        padding: 0;
        transition: background .15s, color .15s, transform .15s;
        color: #6b7280;
      }
      .gallery-item-delete svg {
        width: 10px;
        height: 10px;
        pointer-events: none;
      }
      .gallery-item-delete:hover {
        background: #fff1f1;
        border-color: #f64e60;
        transform: scale(1.15);
      }
      .gallery-item-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,.48);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity .18s;
        pointer-events: none;
        border-radius: 8px;
      }
      .gallery-item:hover .gallery-item-overlay {
        opacity: 1;
      }

      /* ── Skeleton (upload in progress) ── */
      .gallery-item--skeleton {
        cursor: default;
      }
      .gallery-item--skeleton:hover {
        transform: none;
      }
      .gallery-item-skeleton-inner {
        width: 100%;
        height: 100%;
        border-radius: 8px;
        background: linear-gradient(
          90deg,
          var(--tw-gray-100, #f3f4f6) 25%,
          var(--tw-gray-200, #e5e7eb) 50%,
          var(--tw-gray-100, #f3f4f6) 75%
        );
        background-size: 200% 100%;
        animation: gallery-shimmer 1.4s ease-in-out infinite;
      }
      @keyframes gallery-shimmer {
        0%   { background-position: -200% 0; }
        100% { background-position:  200% 0; }
      }
    `;
    document.head.appendChild(style);
  }
}