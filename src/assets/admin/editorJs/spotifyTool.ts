/*
 * Editor.js Spotify Embed Tool
 * Lightweight tool to embed Spotify tracks/albums/playlists/episodes/shows by URL.
 * - Paste a Spotify URL or use the toolbox button to add one.
 * - No external dependencies.
 * - Works in read-only mode.
 */

// Types for Editor.js Tool API (minimal). If you already have @editorjs/editorjs types, you can remove these.
interface EditorJSTool {
    render(): HTMLElement;
    save(block: HTMLElement): any;
    validate?(data: any): boolean;
}

type SpotifyType = 'track' | 'album' | 'playlist' | 'episode' | 'show';

type SpotifyData = {
    url: string;
    type?: SpotifyType;
    title?: string;
    height?: number; // iframe height in px
};

export default class SpotifyTool implements EditorJSTool {
    static get toolbox() {
        return {
            title: 'Spotify',
            icon: '<svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 1.5C6.201 1.5 1.5 6.201 1.5 12S6.201 22.5 12 22.5 22.5 17.799 22.5 12 17.799 1.5 12 1.5Zm4.845 14.85a.937.937 0 0 1-1.287.312c-3.523-2.154-7.963-1.32-10.442-.367a.937.937 0 0 1-.664-1.754c2.887-1.094 7.83-2.035 12.02.508a.937.937 0 0 1 .373 1.301ZM17.7 12.6a1.125 1.125 0 0 1-1.543.375c-3.02-1.86-7.625-2.403-11.19-.68a1.125 1.125 0 1 1-.97-2.03c4.158-1.988 9.383-1.37 12.9.77A1.125 1.125 0 0 1 17.7 12.6Zm.232-3.17c-3.512-2.09-9.366-2.283-12.716-.692a1.312 1.312 0 1 1-1.097-2.39c4.066-1.868 10.67-1.62 14.777.79a1.312 1.312 0 0 1-1.364 2.292Z"/></svg>'
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    static get sanitize() {
        return {
            url: true,
            type: true,
            title: true,
            height: true
        } as any;
    }

    private data: SpotifyData;
    private wrapper!: HTMLElement;
    private readOnly: boolean;

    constructor({ data, readOnly, config }: { data: SpotifyData; readOnly: boolean; config: Partial<SpotifyData> }) {
        this.readOnly = !!readOnly;
        const defaults: SpotifyData = { url: '', height: 152 };
        this.data = { ...defaults, ...config, ...data };
    }

    render(): HTMLElement {
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'cdx-spotify-embed';

        if (this.data.url) {
            this._renderIframe();
        } else if (!this.readOnly) {
            this._renderInput();
        }

        return this.wrapper;
    }

    save(block: HTMLElement): SpotifyData {
        return {
            url: this.data.url?.trim() || '',
            type: this.data.type,
            height: this.data.height
        };
    }

    validate(data: SpotifyData): boolean {
        return !!data.url && SpotifyTool.extractInfo(data.url) !== null;
    }

    /**
     * Allow pasting Spotify URLs directly
     */
    static get pasteConfig() {
        return {
            patterns: {
                spotify: /https?:\/\/(?:open\.)?spotify\.com\/(?:intl-[a-z]{2}\/)?.+$/i
            }
        };
    }

    onPaste(event: any) {
        const url: string = event.detail.data;
        const info = SpotifyTool.extractInfo(url);
        if (!info) return;

        this.data.url = url;
        this.data.type = info.type;
        this._replaceWithIframe();
    }

    // --- UI helpers ---
    private _renderInput() {
        const input = document.createElement('input');
        input.type = 'url';
        input.placeholder = 'Vložte Spotify URL (track/album/playlist/episode/show)';
        input.className = 'cdx-input';

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = 'Vložit';
        btn.className = 'cdx-button';

        btn.addEventListener('click', () => {
            const url = input.value.trim();
            const info = SpotifyTool.extractInfo(url);
            if (!info) {
                alert('Neplatná Spotify URL.');
                return;
            }
            this.data.url = url;
            this.data.type = info.type;
            this._replaceWithIframe();
        });

        this.wrapper.appendChild(input);
        this.wrapper.appendChild(btn);
    }

    private _replaceWithIframe() {
        this.wrapper.innerHTML = '';
        this._renderIframe();
    }

    private _renderIframe() {
        const info = SpotifyTool.extractInfo(this.data.url);
        if (!info) return;

        const height = this.data.height ?? (info.type === 'episode' || info.type === 'show' ? 232 : 152);

        const iframe = document.createElement('iframe');
        iframe.setAttribute('allow', 'autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture');
        iframe.setAttribute('loading', 'lazy');
        iframe.width = '100%';
        iframe.height = String(height);
        iframe.style.borderRadius = '12px';
        iframe.style.border = '0';
        iframe.src = SpotifyTool.buildEmbedSrc(info.type, info.id);

        this.wrapper.appendChild(iframe);
    }

    // --- URL parsing helpers ---
    /**
     * Extracts Spotify type and id from a URL.
     * Supports: /track/:id, /album/:id, /playlist/:id, /episode/:id, /show/:id
     * Accepts URLs with query params and locale prefix like /intl-en/.
     */
    static extractInfo(rawUrl: string): { type: SpotifyType; id: string } | null {
        try {
            const url = new URL(rawUrl);
            if (!/spotify\.com$/i.test(url.hostname) && !/\.spotify\.com$/i.test(url.hostname)) return null;

            const path = url.pathname.replace(/^\/+/, ''); // remove leading slashes
            // remove optional locale prefix e.g. intl-en/
            const parts = path.split('/').filter(Boolean);
            const start = parts[0]?.startsWith('intl-') ? 1 : 0;

            const type = parts[start] as SpotifyType;
            const id = parts[start + 1]?.split('?')[0];

            if (!type || !id) return null;
            if (!['track', 'album', 'playlist', 'episode', 'show'].includes(type)) return null;

            return { type, id };
        } catch {
            return null;
        }
    }

    static buildEmbedSrc(type: SpotifyType, id: string): string {
        return `https://open.spotify.com/embed/${type}/${id}`;
    }
}

/* Optional minimal styles; you can merge into your Editor.js theme
.cdx-spotify-embed { display: grid; gap: 8px; }
.cdx-spotify-embed .cdx-input { padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px; }
.cdx-spotify-embed .cdx-button { padding: 8px 12px; border-radius: 8px; background: #0ea5e9; color: white; border: 0; cursor: pointer; }
.cdx-spotify-embed .cdx-button:hover { filter: brightness(0.95); }
*/

// --- Usage example ---
// import EditorJS from '@editorjs/editorjs';
// import SpotifyTool from './SpotifyTool';
//
// const editor = new EditorJS({
//   holder: 'editor',
//   tools: {
//     spotify: SpotifyTool,
//   },
//   // To allow paste by URL globally you can keep defaults; this tool defines its own pasteConfig
// });
