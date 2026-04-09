// editorJs/faq.js – verze s plovoucí kontextovou nabídkou (tučně/kurzíva/odkaz)
export default class FAQ {
    static get toolbox() {
        return {
            title: 'FAQ',
            icon: `<svg width="17" height="17" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 5.92 2 10.5c0 2.55 1.5 4.84 3.94 6.28-.14.54-.49 1.93-.56 2.25-.09.41.3.75.7.57.37-.17 2.33-1.22 3.28-1.74.87.19 1.78.3 2.64.3 5.52 0 10-3.92 10-8.5S17.52 2 12 2zm.25 12.25a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5zm1.53-6.77c-.43-.37-.95-.53-1.53-.53-1.24 0-2.25.83-2.25 1.85 0 .41.34.75.75.75s.75-.34.75-.75c0-.22.32-.35.75-.35.24 0 .47.07.63.21.2.18.32.46.32.79 0 .35-.13.6-.55.95-.61.51-.95 1.09-.95 1.73v.17a.75.75 0 0 0 1.5 0v-.12c0-.27.14-.53.54-.86.65-.54.96-1.15.96-1.87 0-.68-.28-1.28-.77-1.7z" fill="currentColor"/></svg>`
        };
    }

    // ➊ dovolí používat Enter uvnitř jednoho bloku místo vytváření nového bloku EditorJS
    static get enableLineBreaks() { return true; }

    constructor({ data, readOnly }) {
        this.readOnly = !!readOnly;
        this.data = {
            items: Array.isArray(data?.items) ? data.items : []
        };

        this.nodes = {
            wrapper: null,
            list: null,
            addBtn: null
        };

        // sdílené: zavřít bubblinu při kliknutí mimo
        this._outsideClickHandler = (e) => {
            if (!this.nodes.wrapper) return;
            const bubble = this.nodes.wrapper.querySelector('.faq-bubble');
            if (!bubble) return;
            if (!bubble.contains(e.target)) this._hideBubble(bubble);
        };
    }

    render() {
        const w = document.createElement('div');
        w.className = 'faq-block';
        w.style.cssText = 'border: 1px solid #F1F1F4; padding: 12px; gap: 12px; border-radius: 6px;';

        // FAQ Title
        const title = document.createElement('span');
        title.style.cssText = 'color: #4B5675; font-weight: 500; font-size: 16px; letter-spacing: -1px;';
        title.textContent = 'FAQ';

        const list = document.createElement('div');
        list.className = 'faq-block__list';

        if (this.data.items.length) {
            this.data.items.forEach((it) => list.appendChild(this._createItem(it)));
        } else {
            list.appendChild(this._createItem({ question: '', answer: '' }));
        }

        const controls = document.createElement('div');
        controls.className = 'faq-block__controls';

        const addBtn = document.createElement('button');
        addBtn.type = 'button';
        addBtn.className = 'faq-block__add';
        addBtn.disabled = this.readOnly;
        addBtn.setAttribute('data-empty', 'false');
        
        const plusIcon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        plusIcon.setAttribute('width', '20');
        plusIcon.setAttribute('height', '20');
        plusIcon.setAttribute('viewBox', '0 0 20 20');
        plusIcon.setAttribute('fill', 'none');
        plusIcon.innerHTML = `<path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>`;
        
        const btnText = document.createTextNode('Přidat otázku');
        addBtn.append(plusIcon, btnText);
        
        addBtn.addEventListener('click', () => {
            list.appendChild(this._createItem({ question: '', answer: '' }, true));
        });

        controls.appendChild(addBtn);

        const style = document.createElement('style');
        style.textContent = `
      .faq-block { display: grid; }
      .faq-block__item { 
        background: var(--tw-card-background-color); 
        border: var(--tw-card-border); 
        border-radius: 0.5rem; 
        margin-bottom: 1rem; 
        transition: all 0.2s ease;
      }
      .faq-block__header {
        display: flex;
        align-items: center;
        cursor: pointer;
      }
      .faq-block__drag-icon {
        color: var(--tw-gray-400);
        cursor: grab;
        flex-shrink: 0;
      }
      .faq-block__input {
        flex: 1;
        border: none;
        background: transparent;
        outline: none;
        font-family: inherit;
      }
      .faq-block__input::placeholder {
        color: var(--tw-gray-400);
      }
      .faq-block__arrow {
        transition: transform 0.2s ease;
        color: var(--tw-gray-600);
        flex-shrink: 0;
      }
      .faq-block__arrow.rotate {
        transform: rotate(180deg);
      }
      .faq-block__content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
      }
      .faq-block__content.show {
        max-height: 500px;
      }
      .faq-block__answer {
        color: var(--tw-gray-600);
        background: var(--tw-light-active);
        border-radius: 0.5rem;
        outline: none;
      }
      .faq-block__answer[contenteditable="true"]:empty:before { 
        content: attr(data-placeholder); 
        color: var(--tw-gray-400); 
      }
      .faq-block__itemActions { 
        display: flex; 
        justify-content: flex-end; 
        align-items: center; 
        gap: 0.5rem; 
        padding: 0 1.5rem 1rem 1.5rem;
      }
      .faq-block__remove { 
        background: var(--tw-danger-light); 
        border: 1px solid var(--tw-danger-clarity); 
        color: var(--tw-danger); 
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 500;
      }
      .faq-block__remove:hover {
        background: var(--tw-danger);
        color: var(--tw-danger-inverse);
      }
      .faq-block__remove:disabled { opacity: 0.6; cursor: not-allowed; }
      .faq-block__add { 
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--tw-gray-600);
        font-size: 1rem;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0.75rem 0;
      }
      .faq-block__add:hover {
        color: var(--tw-gray-700);
      }
      .faq-block__list { display: grid; gap: 0; }
      .faq-block__controls { display: flex; justify-content: flex-start; margin-top: 0.5rem; }
      .faq-block [disabled] { cursor: not-allowed; }
      .faq-block__answer a { text-decoration: underline; }
      /* Plovoucí bublina */
      .faq-bubble { position: absolute; z-index: 9999; display:none; background: var(--tw-tooltip-background-color); color:#fff; border-radius:0.5rem; padding:0.25rem; box-shadow: var(--tw-tooltip-box-shadow); }
      .faq-bubble__btn { border:none; background:transparent; color:inherit; padding:0.35rem 0.45rem; border-radius:0.4rem; font:inherit; cursor:pointer; }
      .faq-bubble__btn:hover { background: rgba(255,255,255,0.1); }
    `;

        w.append(style, title, list, controls);

        this.nodes.wrapper = w;
        this.nodes.list = list;
        this.nodes.addBtn = addBtn;

        // globální handler pro klik mimo bublinu
        document.addEventListener('mousedown', this._outsideClickHandler);

        return w;
    }

    _createItem(data = { question: '', answer: '' }, focus = false) {
        const item = document.createElement('div');
        item.className = 'faq-block__item';
        item.style.cssText = 'position: relative; border-color: #F1F1F4;';

        // Header with drag icon, question input, and arrow
        const header = document.createElement('div');
        header.className = 'faq-block__header';
        header.style.cssText = 'padding-right: 8px; gap: 0 !important;';

        // Drag icon (28x28 with white background)
        const dragIcon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        dragIcon.setAttribute('width', '28');
        dragIcon.setAttribute('height', '28');
        dragIcon.setAttribute('viewBox', '0 0 28 28');
        dragIcon.setAttribute('fill', 'none');
        dragIcon.style.cssText = 'width: 28px; height: 28px;';
        dragIcon.innerHTML = `
            <rect width="28" height="28" rx="4" fill="white"></rect>
            <path d="M11.334 18.0003C11.334 17.6321 11.6325 17.3337 12.0007 17.3337C12.3688 17.3337 12.6673 17.6321 12.6673 18.0003C12.6673 18.3685 12.3688 18.667 12.0007 18.667C11.6325 18.667 11.334 18.3685 11.334 18.0003Z" fill="#78829D"></path>
            <path d="M12.0007 17.8337C11.9086 17.8337 11.834 17.9083 11.834 18.0003C11.834 18.0924 11.9086 18.167 12.0007 18.167C12.0927 18.167 12.1673 18.0924 12.1673 18.0003C12.1673 17.9083 12.0927 17.8337 12.0007 17.8337ZM12.0007 16.8337C12.645 16.8337 13.1673 17.356 13.1673 18.0003C13.1673 18.6447 12.645 19.167 12.0007 19.167C11.3563 19.167 10.834 18.6447 10.834 18.0003C10.834 17.356 11.3563 16.8337 12.0007 16.8337Z" fill="#78829D"></path>
            <path d="M15.334 18.0003C15.334 17.6321 15.6325 17.3337 16.0007 17.3337C16.3689 17.3337 16.6673 17.6321 16.6673 18.0003C16.6673 18.3685 16.3689 18.667 16.0007 18.667C15.6325 18.667 15.334 18.3685 15.334 18.0003Z" fill="#78829D"></path>
            <path d="M16.0007 17.8337C15.9086 17.8337 15.834 17.9083 15.834 18.0003C15.834 18.0924 15.9086 18.167 16.0007 18.167C16.0927 18.167 16.1673 18.0924 16.1673 18.0003C16.1673 17.9083 16.0927 17.8337 16.0007 17.8337ZM16.0007 16.8337C16.645 16.8337 17.1673 17.356 17.1673 18.0003C17.1673 18.6446 16.645 19.167 16.0007 19.167C15.3563 19.167 14.834 18.6446 14.834 18.0003C14.834 17.356 15.3563 16.8337 16.0007 16.8337Z" fill="#78829D"></path>
            <path d="M11.334 10.0003C11.334 9.63213 11.6325 9.33366 12.0007 9.33366C12.3688 9.33366 12.6673 9.63213 12.6673 10.0003C12.6673 10.3685 12.3688 10.667 12.0007 10.667C11.6325 10.667 11.334 10.3685 11.334 10.0003Z" fill="#78829D"></path>
            <path d="M12.0007 9.83366C11.9086 9.83366 11.834 9.90827 11.834 10.0003C11.834 10.0924 11.9086 10.167 12.0007 10.167C12.0927 10.167 12.1673 10.0924 12.1673 10.0003C12.1673 9.90827 12.0927 9.83366 12.0007 9.83366ZM12.0007 8.83366C12.645 8.83366 13.1673 9.35598 13.1673 10.0003C13.1673 10.6447 12.645 11.167 12.0007 11.167C11.3563 11.167 10.834 10.6447 10.834 10.0003C10.834 9.35598 11.3563 8.83366 12.0007 8.83366Z" fill="#78829D"></path>
            <path d="M11.334 14.0003C11.334 13.6321 11.6325 13.3337 12.0007 13.3337C12.3688 13.3337 12.6673 13.6321 12.6673 14.0003C12.6673 14.3685 12.3688 14.667 12.0007 14.667C11.6325 14.667 11.334 14.3685 11.334 14.0003Z" fill="#78829D"></path>
            <path d="M12.0007 13.8337C11.9086 13.8337 11.834 13.9083 11.834 14.0003C11.834 14.0924 11.9086 14.167 12.0007 14.167C12.0927 14.167 12.1673 14.0924 12.1673 14.0003C12.1673 13.9083 12.0927 13.8337 12.0007 13.8337ZM12.0007 12.8337C12.645 12.8337 13.1673 13.356 13.1673 14.0003C13.1673 14.6447 12.645 15.167 12.0007 15.167C11.3563 15.167 10.834 14.6447 10.834 14.0003C10.834 13.356 11.3563 12.8337 12.0007 12.8337Z" fill="#78829D"></path>
            <path d="M15.334 10.0003C15.334 9.63213 15.6325 9.33366 16.0007 9.33366C16.3689 9.33366 16.6673 9.63213 16.6673 10.0003C16.6673 10.3685 16.3689 10.667 16.0007 10.667C15.6325 10.667 15.334 10.3685 15.334 10.0003Z" fill="#78829D"></path>
            <path d="M16.0007 9.83366C15.9086 9.83366 15.834 9.90827 15.834 10.0003C15.834 10.0924 15.9086 10.167 16.0007 10.167C16.0927 10.167 16.1673 10.0924 16.1673 10.0003C16.1673 9.90827 16.0927 9.83366 16.0007 9.83366ZM16.0007 8.83366C16.645 8.83366 17.1673 9.35598 17.1673 10.0003C17.1673 10.6447 16.645 11.167 16.0007 11.167C15.3563 11.167 14.834 10.6447 14.834 10.0003C14.834 9.35598 15.3563 8.83366 16.0007 8.83366Z" fill="#78829D"></path>
            <path d="M15.334 14.0003C15.334 13.6321 15.6325 13.3337 16.0007 13.3337C16.3689 13.3337 16.6673 13.6321 16.6673 14.0003C16.6673 14.3685 16.3689 14.667 16.0007 14.667C15.6325 14.667 15.334 14.3685 15.334 14.0003Z" fill="#78829D"></path>
            <path d="M16.0007 13.8337C15.9086 13.8337 15.834 13.9083 15.834 14.0003C15.834 14.0924 15.9086 14.167 16.0007 14.167C16.0927 14.167 16.1673 14.0924 16.1673 14.0003C16.1673 13.9083 16.0927 13.8337 16.0007 13.8337ZM16.0007 12.8337C16.645 12.8337 17.1673 13.356 17.1673 14.0003C17.1673 14.6447 16.645 15.167 16.0007 15.167C15.3563 15.167 14.834 14.6447 14.834 14.0003C14.834 13.356 15.3563 12.8337 16.0007 12.8337Z" fill="#78829D"></path>
        `;

        // Question input
        const inputQ = document.createElement('input');
        inputQ.type = 'text';
        inputQ.className = 'faq-block__input';
        inputQ.placeholder = 'Otázka Text';
        inputQ.value = data.question || '';
        inputQ.disabled = this.readOnly;
        inputQ.setAttribute('data-empty', 'false');
        inputQ.style.cssText = 'padding-block: 11px; color: #4B5675; font-weight: 500; font-size: 16px; height: unset; min-height: unset;';

        // Arrow icon (16x16)
        const arrow = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        arrow.setAttribute('width', '16');
        arrow.setAttribute('height', '16');
        arrow.setAttribute('viewBox', '0 0 16 16');
        arrow.setAttribute('fill', 'none');
        arrow.innerHTML = `<path fill-rule="evenodd" clip-rule="evenodd" d="M13.9581 4.76752C14.2817 5.07994 14.2817 5.58647 13.9581 5.89889L8.4333 11.2322C8.10967 11.5446 7.58496 11.5446 7.26133 11.2322L2.04351 6.19518C1.71987 5.88277 1.71987 5.37623 2.04351 5.06381C2.36714 4.75139 2.89185 4.75139 3.21548 5.06381L7.84732 9.53517L12.7861 4.76752C13.1097 4.4551 13.6344 4.4551 13.9581 4.76752Z" fill="#4B5675"></path>`;

        header.append(dragIcon, inputQ, arrow);

        // Collapsible content
        const content = document.createElement('div');
        content.className = 'faq-block__content show';
        content.style.cssText = 'max-width: 100%; width: 100%;';

        // Answer
        const answer = document.createElement('div');
        answer.className = 'faq-block__answer input';
        answer.setAttribute('data-placeholder', 'Odpověď');
        answer.setAttribute('data-empty', 'false');
        answer.style.cssText = 'padding: 10px; min-height: unset; font-size: 14px; color: #78829D; min-height: 42px; width: auto;';
        if (!this.readOnly) answer.setAttribute('contenteditable', 'true');

        const safeHTML = this._sanitizeIncomingHTML(data.answer || '');
        answer.innerHTML = safeHTML;

        content.appendChild(answer);

        // Bublina nástrojů (skrytá, objevuje se u výběru)
        const bubble = this._createBubble(answer);
        content.appendChild(bubble);

        if (!this.readOnly) {
            // Toggle collapse on header click
            header.addEventListener('click', (e) => {
                // Don't toggle if clicking on input
                if (e.target === inputQ) return;
                content.classList.toggle('show');
                arrow.classList.toggle('rotate');
            });

            // Paste handler
            answer.addEventListener('paste', (e) => {
                e.preventDefault();
                const raw = (e.clipboardData || window.clipboardData).getData('text/plain');
                const lines = raw.split(/\r?\n/);
                lines.forEach((ln, i) => {
                    if (ln) document.execCommand('insertText', false, ln);
                    if (i < lines.length - 1) document.execCommand('insertLineBreak');
                });
            });

            // Enter = new line
            answer.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    document.execCommand('insertLineBreak');
                }
            });

            // Show bubble on selection
            const showMaybe = () => this._maybeShowBubble(answer, bubble);
            answer.addEventListener('mouseup', showMaybe);
            answer.addEventListener('keyup', (e) => {
                if (["ArrowLeft","ArrowRight","ArrowUp","ArrowDown","a","A"," ","Backspace","Delete"].includes(e.key) || e.key.length === 1) {
                    showMaybe();
                }
            });
            answer.addEventListener('scroll', () => this._hideBubble(bubble));
            answer.addEventListener('blur', () => {
                answer.innerHTML = this._sanitizeIncomingHTML(answer.innerHTML);
                this._hideBubble(bubble);
            });
        }

        item.append(header, content);

        if (focus && !this.readOnly) setTimeout(() => inputQ.focus(), 0);

        return item;
    }

    _createBubble(scopeEl) {
        const bubble = document.createElement('div');
        bubble.className = 'faq-bubble';
        if (this.readOnly) return bubble;

        const mkBtn = (label, title, onClick) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'faq-bubble__btn';
            b.textContent = label;
            b.title = title;
            b.addEventListener('mousedown', (e) => e.preventDefault()); // zachovat selection
            b.addEventListener('click', (e) => { e.preventDefault(); onClick(); scopeEl.focus(); this._hideBubble(bubble); });
            return b;
        };

        const btnB = mkBtn('B', 'Tučné (Ctrl/Cmd+B)', () => document.execCommand('bold'));
        const btnI = mkBtn('I', 'Kurzíva (Ctrl/Cmd+I)', () => document.execCommand('italic'));
        const btnL = mkBtn('🔗', 'Vložit odkaz (Ctrl/Cmd+K)', () => this._promptLink(scopeEl));

        bubble.append(btnB, btnI, btnL);
        return bubble;
    }

    _maybeShowBubble(scopeEl, bubble) {
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) { this._hideBubble(bubble); return; }

        // jen pokud je výběr uvnitř našeho scope a není prázdný
        const range = sel.getRangeAt(0);
        if (!scopeEl.contains(range.commonAncestorContainer) || sel.isCollapsed) { this._hideBubble(bubble); return; }

        // pozice podle bounding rect výběru
        const rect = range.getBoundingClientRect();
        if (!rect || (rect.width === 0 && rect.height === 0)) { this._hideBubble(bubble); return; }

        const hostRect = scopeEl.getBoundingClientRect();
        const top = rect.top - hostRect.top - 40; // nad výběr
        const left = rect.left - hostRect.left + rect.width/2;

        bubble.style.display = 'block';
        // centrovat a udržet v rámci itemu
        bubble.style.top = `${Math.max(4, top)}px`;
        bubble.style.left = `${Math.max(4, Math.min(left - bubble.offsetWidth/2, hostRect.width - bubble.offsetWidth - 4))}px`;
    }

    _hideBubble(bubble){ if (bubble) bubble.style.display = 'none'; }

    _promptLink(scopeEl) {
        const url = window.prompt('Vložte URL (včetně http/https):');
        if (!url) return;
        try {
            const u = new URL(url);
            document.execCommand('createLink', false, u.href);
            scopeEl.querySelectorAll('a').forEach(a => {
                a.setAttribute('rel', 'noopener nofollow');
                a.setAttribute('target', '_blank');
            });
        } catch (e) {
            alert('Neplatná URL.');
        }
    }

    _sanitizeIncomingHTML(html) {
        const tmp = document.createElement('div');
        tmp.innerHTML = html;
        // ➍ whitelist včetně BR
        const whitelist = new Set(['B', 'STRONG', 'I', 'EM', 'A', 'BR']);
        const walk = (node) => {
            const children = Array.from(node.childNodes);
            for (const ch of children) {
                if (ch.nodeType === 1) {
                    if (!whitelist.has(ch.nodeName)) {
                        ch.replaceWith(document.createTextNode(ch.textContent));
                    } else {
                        if (ch.nodeName === 'A') {
                            const href = ch.getAttribute('href') || '';
                            try {
                                const url = new URL(href, window.location.origin);
                                ch.setAttribute('href', url.href);
                            } catch {
                                ch.replaceWith(document.createTextNode(ch.textContent));
                                continue;
                            }
                            ch.setAttribute('target', '_blank');
                            ch.setAttribute('rel', 'noopener nofollow');
                            Array.from(ch.attributes).forEach(attr => {
                                if (!['href', 'target', 'rel'].includes(attr.name)) ch.removeAttribute(attr.name);
                            });
                        } else {
                            Array.from(ch.attributes).forEach(attr => ch.removeAttribute(attr.name));
                        }
                        walk(ch);
                    }
                }
            }
        };
        walk(tmp);
        return tmp.innerHTML;
    }

    save(blockContent) {
        const items = [];
        const itemEls = blockContent.querySelectorAll('.faq-block__item');

        itemEls.forEach((el) => {
            const q = el.querySelector('.faq-block__input')?.value?.trim() || '';
            const answerEl = el.querySelector('.faq-block__answer');
            const aHTML = (answerEl?.innerHTML || '').trim();
            const aText = (answerEl?.innerText || '').trim();
            if (q.length || aText.length) {
                items.push({ question: q, answer: this._sanitizeIncomingHTML(aHTML) });
            }
        });

        return { items };
    }

    validate(savedData) {
        if (!savedData || !Array.isArray(savedData.items)) return false;
        return savedData.items.some(it => (it.question?.trim()?.length || (it.answer && this._stripHTML(it.answer).trim().length)));
    }

    _stripHTML(html) {
        const d = document.createElement('div');
        d.innerHTML = html || '';
        return d.textContent || d.innerText || '';
    }

    static get sanitize() {
        return {
            items: {
                question: {},
                answer: {
                    b: {},
                    strong: {},
                    i: {},
                    em: {},
                    a: { href: true, target: true, rel: true },
                    br: {} // ➎ povolit <br> v uložených datech
                }
            }
        };
    }

    static get isReadOnlySupported() {
        return true;
    }
}
