/**
 * Modal pro úpravu metadat obrázku (alt, popis, autor) v nástrojích Editor.js.
 *
 *   const result = await openImageMetaModal({ alt, caption, author });
 *   if (result) { ... } // null = zrušeno
 */

const STYLE_ID = 'editorjs-image-meta-modal-styles';
const KEY_EVENTS = ['keydown', 'keyup', 'keypress'];

export const editIcon = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
  <path d="M12 20h9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
</svg>`;

export function openImageMetaModal({ alt = '', caption = '', author = '' } = {}) {
    ensureStyles();

    return new Promise((resolve) => {
        const backdrop = document.createElement('div');
        backdrop.className = 'image-meta-modal';
        backdrop.innerHTML = `
          <div class="image-meta-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="image-meta-modal-title">
            <div class="image-meta-modal__header">
              <h3 class="image-meta-modal__title" id="image-meta-modal-title">Upravit obrázek</h3>
              <button type="button" class="image-meta-modal__close" aria-label="Zavřít">&times;</button>
            </div>
            <div class="image-meta-modal__body">
              <label class="image-meta-modal__field">
                <span class="image-meta-modal__label">Alt text</span>
                <input type="text" name="alt" class="image-meta-modal__input" placeholder="Co je na obrázku (pro nevidomé a vyhledávače)">
              </label>
              <label class="image-meta-modal__field">
                <span class="image-meta-modal__label">Popis</span>
                <textarea name="caption" rows="3" class="image-meta-modal__input" placeholder="Zobrazí se pod obrázkem"></textarea>
              </label>
              <label class="image-meta-modal__field">
                <span class="image-meta-modal__label">Autor</span>
                <input type="text" name="author" class="image-meta-modal__input" placeholder="Autor fotografie">
              </label>
            </div>
            <div class="image-meta-modal__footer">
              <button type="button" class="image-meta-modal__btn" data-action="cancel">Zrušit</button>
              <button type="button" class="image-meta-modal__btn image-meta-modal__btn--primary" data-action="save">Uložit</button>
            </div>
          </div>`;

        const altInput = backdrop.querySelector('[name="alt"]');
        const captionInput = backdrop.querySelector('[name="caption"]');
        const authorInput = backdrop.querySelector('[name="author"]');
        altInput.value = alt;
        captionInput.value = caption;
        authorInput.value = author;

        const previousFocus = document.activeElement;
        const close = (result) => {
            KEY_EVENTS.forEach((type) => window.removeEventListener(type, onKey, true));
            backdrop.remove();
            previousFocus?.focus?.({ preventScroll: true });
            resolve(result);
        };
        const save = () => close({
            alt: altInput.value.trim(),
            caption: captionInput.value.trim(),
            author: authorInput.value.trim(),
        });

        // Klávesy zachytáváme na window v capture fázi, tedy dřív než globální listenery
        // Editor.js na document – ten by jinak Tabem přesunul fokus do bloku a Enterem založil nový blok.
        // Výchozí chování (psaní, Tab mezi poli) necháváme prohlížeči.
        const onKey = (event) => {
            event.stopPropagation();
            if (event.type !== 'keydown') return;

            if (event.key === 'Escape') {
                event.preventDefault();
                close(null);
            } else if (event.key === 'Enter' && event.target.tagName === 'INPUT') {
                // V textarea dělá Enter nový řádek, na tlačítku ho aktivuje.
                event.preventDefault();
                save();
            } else if (event.key === 'Tab') {
                trapFocus(event, backdrop);
            }
        };
        KEY_EVENTS.forEach((type) => window.addEventListener(type, onKey, true));

        backdrop.addEventListener('mousedown', (event) => {
            if (event.target === backdrop) close(null);
        });
        backdrop.querySelector('.image-meta-modal__close').addEventListener('click', () => close(null));
        backdrop.querySelector('[data-action="cancel"]').addEventListener('click', () => close(null));
        backdrop.querySelector('[data-action="save"]').addEventListener('click', save);

        document.body.appendChild(backdrop);
        altInput.focus();
    });
}

/** Tab z posledního prvku skočí na první a naopak, fokus neopustí modal. */
function trapFocus(event, container) {
    const focusable = Array.from(container.querySelectorAll('input, textarea, button'));
    if (focusable.length === 0) return;
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    const inside = container.contains(document.activeElement);

    if (event.shiftKey && (document.activeElement === first || !inside)) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && (document.activeElement === last || !inside)) {
        event.preventDefault();
        first.focus();
    }
}

function ensureStyles() {
    if (document.getElementById(STYLE_ID)) return;
    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = `
      .image-meta-modal {
        position: fixed;
        inset: 0;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        background: rgba(0, 0, 0, .3);
        font-family: inherit;
      }
      .image-meta-modal__dialog {
        width: 100%;
        max-width: 500px;
        background: var(--tw-light, #fff);
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .15);
      }
      .image-meta-modal__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid var(--tw-gray-200, #f1f1f4);
      }
      .image-meta-modal__title {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: var(--tw-gray-900, #071437);
      }
      .image-meta-modal__close {
        border: 0;
        background: none;
        font-size: 22px;
        line-height: 1;
        color: var(--tw-gray-500, #99a1b7);
        cursor: pointer;
        padding: 0 4px;
      }
      .image-meta-modal__close:hover { color: var(--tw-gray-900, #071437); }
      .image-meta-modal__body {
        display: flex;
        flex-direction: column;
        gap: 14px;
        padding: 20px;
      }
      .image-meta-modal__field {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin: 0;
      }
      .image-meta-modal__label {
        font-size: 13px;
        font-weight: 500;
        color: var(--tw-gray-900, #071437);
      }
      .image-meta-modal__input {
        width: 100%;
        box-sizing: border-box;
        padding: 8px 12px;
        font-size: 13px;
        font-family: inherit;
        color: var(--tw-gray-700, #4b5675);
        background: var(--tw-light-active, #fcfcfc);
        border: 1px solid var(--tw-gray-300, #dbdfe9);
        border-radius: 6px;
        outline: none;
        resize: vertical;
      }
      .image-meta-modal__input:focus { border-color: #1b84ff; }
      .image-meta-modal__footer {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        padding: 16px 20px;
        border-top: 1px solid var(--tw-gray-200, #f1f1f4);
      }
      .image-meta-modal__btn {
        height: 34px;
        padding: 0 14px;
        font-size: 13px;
        font-weight: 500;
        font-family: inherit;
        color: var(--tw-gray-700, #4b5675);
        background: #fff;
        border: 1px solid var(--tw-gray-300, #dbdfe9);
        border-radius: 6px;
        cursor: pointer;
      }
      .image-meta-modal__btn:hover { border-color: #1b84ff; color: #1b84ff; }
      .image-meta-modal__btn--primary {
        color: #fff;
        background: #1b84ff;
        border-color: #1b84ff;
      }
      .image-meta-modal__btn--primary:hover { color: #fff; background: #056ee9; }

      /* Ikonka pro otevření modalu nad obrázkem */
      .image-meta-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        padding: 0;
        color: #4b5675;
        background: #fff;
        border: 1px solid #dbdfe9;
        border-radius: 6px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .1);
        cursor: pointer;
        transition: color .15s, border-color .15s;
      }
      .image-meta-btn:hover { color: #1b84ff; border-color: #1b84ff; }
      .image-meta-btn--filled { color: #1b84ff; }
    `;
    document.head.appendChild(style);
}

/** Má obrázek vyplněné nějaké metadata? (ikonka se pak zvýrazní) */
export function hasImageMeta(data) {
    return Boolean((data?.alt || '').trim() || (data?.caption || '').trim() || (data?.author || '').trim());
}

/** Vytvoří tlačítko s ikonkou pro otevření modalu. */
export function createImageMetaButton(onClick) {
    ensureStyles();
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'image-meta-btn';
    button.title = 'Upravit alt, popis a autora';
    button.setAttribute('aria-label', 'Upravit alt, popis a autora');
    button.innerHTML = editIcon;
    button.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        // Fokus na tlačítko, aby se na něj po zavření modalu vrátil (Sortable v galerii ho jinak potlačí).
        button.focus({ preventScroll: true });
        onClick();
    });
    return button;
}
