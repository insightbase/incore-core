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
        addBtn.textContent = 'Přidat otázku';
        addBtn.className = 'faq-block__add';
        addBtn.disabled = this.readOnly;
        addBtn.addEventListener('click', () => {
            list.appendChild(this._createItem({ question: '', answer: '' }, true));
        });

        controls.appendChild(addBtn);

        const style = document.createElement('style');
        style.textContent = `
      .faq-block { display: grid; gap: .75rem; }
      .faq-block__item { border: 1px solid #e5e7eb; border-radius: .5rem; padding: .75rem; display: grid; gap: .5rem; background: #fff; }
      .faq-block__row { display: grid; gap: .25rem; }
      .faq-block__label { font-size: .8rem; color: #6b7280; }
      .faq-block__input, .faq-block__answer {
        width: 100%; padding: .5rem .75rem; border: 1px solid #e5e7eb; border-radius: .5rem; font: inherit; box-sizing: border-box;
      }
      .faq-block__answer { min-height: 96px; }
      .faq-block__answer[contenteditable="true"]:empty:before { content: attr(data-placeholder); color: #9ca3af; }
      .faq-block__itemActions { display: flex; justify-content: space-between; align-items: center; gap: .5rem; }
      .faq-block__moveHint { font-size: .75rem; color: #9ca3af; user-select: none; }
      .faq-block__remove { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
      .faq-block__remove:disabled { opacity: .6; }
      .faq-block__btn { padding: .35rem .6rem; border-radius: .5rem; border: 1px solid #e5e7eb; background: #f9fafb; cursor: pointer; font: inherit; }
      .faq-block__add { padding: .45rem .8rem; border-radius: .6rem; border: 1px solid #d1d5db; background: #f3f4f6; cursor: pointer; }
      .faq-block__list { display: grid; gap: .75rem; }
      .faq-block__controls { display: flex; justify-content: flex-start; }
      .faq-block [disabled] { background: #f9fafb; cursor: not-allowed; }
      .faq-block__answer a{ text-decoration: underline; }
      /* Plovoucí bublina */
      .faq-bubble { position: absolute; z-index: 9999; display:none; background:#111827; color:#fff; border-radius:.5rem; padding:.25rem; box-shadow:0 8px 20px rgba(0,0,0,.2); }
      .faq-bubble__btn { border:none; background:transparent; color:inherit; padding:.35rem .45rem; border-radius:.4rem; font:inherit; cursor:pointer; }
      .faq-bubble__btn:hover { background: rgba(255,255,255,.1); }
    `;

        w.append(style, list, controls);

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
        item.style.position = 'relative'; // pro bublinu

        // Otázka
        const rowQ = document.createElement('div');
        rowQ.className = 'faq-block__row';

        const labelQ = document.createElement('div');
        labelQ.className = 'faq-block__label';
        labelQ.textContent = 'Otázka';

        const inputQ = document.createElement('input');
        inputQ.type = 'text';
        inputQ.className = 'faq-block__input';
        inputQ.placeholder = 'Např. Jak funguje doprava?';
        inputQ.value = data.question || '';
        inputQ.disabled = this.readOnly;

        // Odpověď
        const rowA = document.createElement('div');
        rowA.className = 'faq-block__row';

        const labelA = document.createElement('div');
        labelA.className = 'faq-block__label';
        labelA.textContent = 'Odpověď';

        const answer = document.createElement('div');
        answer.className = 'faq-block__answer';
        answer.setAttribute('data-placeholder', 'Krátká odpověď…');
        if (!this.readOnly) answer.setAttribute('contenteditable', 'true');

        const safeHTML = this._sanitizeIncomingHTML(data.answer || '');
        answer.innerHTML = safeHTML;

        // Bublina nástrojů (skrytá, objevuje se u výběru)
        const bubble = this._createBubble(answer);

        if (!this.readOnly) {
            // ➋ čistý paste s respektováním zalomení řádků
            answer.addEventListener('paste', (e) => {
                e.preventDefault();
                const raw = (e.clipboardData || window.clipboardData).getData('text/plain');
                const lines = raw.split(/\r?\n/);
                lines.forEach((ln, i) => {
                    if (ln) document.execCommand('insertText', false, ln);
                    if (i < lines.length - 1) document.execCommand('insertLineBreak');
                });
            });

            // ➌ Enter = nový řádek uvnitř odpovědi (ne nový blok)
            answer.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();              // nedovol EditorJS vytvořit nový blok
                    document.execCommand('insertLineBreak'); // vloží <br>
                }
            });

            // zobrazit bublinu při výběru
            const showMaybe = () => this._maybeShowBubble(answer, bubble);
            answer.addEventListener('mouseup', showMaybe);
            answer.addEventListener('keyup', (e) => {
                if (["ArrowLeft","ArrowRight","ArrowUp","ArrowDown","a","A"," ","Backspace","Delete"].includes(e.key) || e.key.length === 1) {
                    showMaybe();
                }
            });
            answer.addEventListener('scroll', () => this._hideBubble(bubble));
            answer.addEventListener('blur', () => {
                // pročistit HTML a zavřít bublinu
                answer.innerHTML = this._sanitizeIncomingHTML(answer.innerHTML);
                this._hideBubble(bubble);
            });
        }

        // Akce
        const actions = document.createElement('div');
        actions.className = 'faq-block__itemActions';

        const moveHint = document.createElement('div');
        moveHint.className = 'faq-block__moveHint';

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.textContent = 'Smazat';
        removeBtn.className = 'faq-block__btn faq-block__remove';
        removeBtn.disabled = this.readOnly;

        removeBtn.addEventListener('click', () => {
            if (this.nodes.list.children.length > 1) {
                item.remove();
            } else {
                inputQ.value = '';
                answer.innerHTML = '';
            }
        });

        rowQ.append(labelQ, inputQ);
        rowA.append(labelA, answer, bubble);
        actions.append(moveHint, removeBtn);
        item.append(rowQ, rowA, actions);

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
