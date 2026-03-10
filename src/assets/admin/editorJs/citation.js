// editorJs/citation.js
export default class Citation {
    static get toolbox() {
        return {
            title: 'Citace',
            icon: `<svg width="17" height="17" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none">
        <path d="M5.5 6c-1.93 0-3.5 1.57-3.5 3.5S3.57 13 5.5 13c.53 0 1.03-.12 1.48-.33C6.38 15.2 4.77 17 2 18v2c4.42-1.12 7-4.26 7-8.5C9 8.57 7.43 6 5.5 6zm12 0C15.57 6 14 7.57 14 9.5S15.57 13 17.5 13c.53 0 1.03-.12 1.48-.33-.6 2.53-2.21 4.33-5 5.33v2c4.42-1.12 7-4.26 7-8.5C21 8.57 19.43 6 17.5 6z" fill="currentColor"/>
      </svg>`
        };
    }

    constructor({ data, readOnly }) {
        this.readOnly = !!readOnly;
        this.data = {
            text: data?.text || '',
            author: data?.author || ''
        };

        this.nodes = {
            wrapper: null,
            textarea: null,
            author: null
        };
    }

    render() {
        const w = document.createElement('div');
        w.className = 'citation-block';

        const style = document.createElement('style');
        style.textContent = `
      .citation-block { display: grid; gap: .6rem; }
      .citation-block__row { display: grid; gap: .3rem; }
      .citation-block__label { font-size: .8rem; color: #6b7280; }
      .citation-block__textarea, .citation-block__input {
        width: 100%; padding: .6rem .8rem; border: 1px solid #e5e7eb; border-radius: .5rem; font: inherit; box-sizing: border-box;
      }
      .citation-block__textarea { min-height: 96px; resize: vertical; }
      .citation-block [disabled] { background: #f9fafb; }
    `;

        const rowText = document.createElement('div');
        rowText.className = 'citation-block__row';
        const labelText = document.createElement('div');
        labelText.className = 'citation-block__label';
        labelText.textContent = 'Citace';
        const textarea = document.createElement('textarea');
        textarea.className = 'citation-block__textarea';
        textarea.placeholder = '„Sem napište citaci…“';
        textarea.value = this.data.text;
        textarea.disabled = this.readOnly;

        const rowAuthor = document.createElement('div');
        rowAuthor.className = 'citation-block__row';
        const labelAuthor = document.createElement('div');
        labelAuthor.className = 'citation-block__label';
        labelAuthor.textContent = 'Autor (volitelné)';
        const inputAuthor = document.createElement('input');
        inputAuthor.type = 'text';
        inputAuthor.className = 'citation-block__input';
        inputAuthor.placeholder = 'Jméno autora';
        inputAuthor.value = this.data.author;
        inputAuthor.disabled = this.readOnly;

        rowText.append(labelText, textarea);
        rowAuthor.append(labelAuthor, inputAuthor);
        w.append(style, rowText, rowAuthor);

        this.nodes.wrapper = w;
        this.nodes.textarea = textarea;
        this.nodes.author = inputAuthor;

        return w;
    }

    save(blockContent) {
        // vezmeme hodnoty z referencí (fallback pro případ)
        const text = (this.nodes.textarea?.value ?? '').trim();
        const author = (this.nodes.author?.value ?? '').trim();

        return { text, author };
    }

    validate(savedData) {
        // Požadujeme aspoň nějaký text citace
        return typeof savedData?.text === 'string' && savedData.text.trim().length > 0;
    }

    static get sanitize() {
        // čistý text bez HTML
        return {
            text: {},
            author: {}
        };
    }

    static get isReadOnlySupported() {
        return true;
    }
}
