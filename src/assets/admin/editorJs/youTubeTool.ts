/*
 * Editor.js YouTube Embed Tool (TypeScript)
 * - Přijme libovolnou YouTube URL (youtube.com / youtu.be) a vloží responzivní embed.
 * - Podporuje typy: video (watch, youtu.be, shorts, embed) a playlist (list=...)
 * - Bez závislostí, funguje i v read-only.
 */

interface EditorJSTool {
    render(): HTMLElement;
    save(block: HTMLElement): any;
    validate?(data: any): boolean;
}

type YtType = 'video' | 'playlist';

type YtData = {
    url: string;
    type?: YtType;
    id?: string; // videoId nebo playlistId (list)
    start?: number; // start v sekundách (volitelné, pouze pro video)
    title?: string;
    height?: number; // fallback výška v px, pokud nepoužijeme responsivní obálku
};

export default class YouTubeTool implements EditorJSTool {
    static get toolbox() {
        return {
            title: 'YouTube',
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path d="M23.498 6.186a3.002 3.002 0 0 0-2.113-2.123C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.385.563A3.002 3.002 0 0 0 .502 6.186C0 8.083 0 12 0 12s0 3.917.502 5.814a3.002 3.002 0 0 0 2.113 2.123C4.5 20.5 12 20.5 12 20.5s7.5 0 9.385-.563a3.002 3.002 0 0 0 2.113-2.123C24 15.917 24 12 24 12s0-3.917-.502-5.814ZM9.75 15.568V8.432L15.818 12 9.75 15.568Z"/></svg>'
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    static get sanitize() {
        return { url: true, type: true, id: true, start: true, title: true, height: true } as any;
    }

    private data: YtData;
    private wrapper!: HTMLElement;
    private readOnly: boolean;

    constructor({ data, readOnly, config }: { data: YtData; readOnly: boolean; config: Partial<YtData> }) {
        this.readOnly = !!readOnly;
        const defaults: YtData = { url: '', height: 315 };
        this.data = { ...defaults, ...config, ...data };
    }

    render(): HTMLElement {
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'cdx-youtube-embed';

        if (this.data.url) {
            this._renderEmbed();
        } else if (!this.readOnly) {
            this._renderInput();
        }

        return this.wrapper;
    }

    save(): YtData {
        return {
            url: this.data.url?.trim() || '',
            type: this.data.type,
            id: this.data.id,
            start: this.data.start,
            title: this.data.title,
            height: this.data.height
        };
    }

    validate(data: YtData): boolean {
        return !!data.url && YouTubeTool.extractInfo(data.url) !== null;
    }

    // Povolíme vkládání URL paste-m způsobem
    static get pasteConfig() {
        return {
            patterns: {
                youtube: /https?:\/\/(?:www\.)?(?:youtube\.com|youtu\.be)\/.+$/i
            }
        };
    }

    onPaste(event: any) {
        const url: string = event.detail.data;
        const info = YouTubeTool.extractInfo(url);
        if (!info) return;
        this.data.url = url;
        this.data.type = info.type;
        this.data.id = info.id;
        if (typeof info.start === 'number') this.data.start = info.start;
        this._replaceWithEmbed();
    }

    // --- UI helpers ---
    private _renderInput() {
        const input = document.createElement('input');
        input.type = 'url';
        input.placeholder = 'Vložte YouTube URL (watch, youtu.be, shorts, playlist)';
        input.className = 'cdx-input';

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = 'Vložit';
        btn.className = 'cdx-button';

        btn.addEventListener('click', () => {
            const url = input.value.trim();
            const info = YouTubeTool.extractInfo(url);
            if (!info) {
                alert('Neplatná YouTube URL.');
                return;
            }
            this.data.url = url;
            this.data.type = info.type;
            this.data.id = info.id;
            this.data.start = info.start;
            this._replaceWithEmbed();
        });

        this.wrapper.appendChild(input);
        this.wrapper.appendChild(btn);
    }

    private _replaceWithEmbed() {
        this.wrapper.innerHTML = '';
        this._renderEmbed();
    }

    private _renderEmbed() {
        const info = YouTubeTool.extractInfo(this.data.url);
        if (!info) return;

        const iframe = document.createElement('iframe');
        const src = YouTubeTool.buildEmbedSrc(info.type, info.id, info.start);

        // Responsivní rámeček 16:9
        const ratioWrap = document.createElement('div');
        ratioWrap.style.position = 'relative';
        ratioWrap.style.width = '100%';
        ratioWrap.style.paddingTop = '56.25%'; // 16:9
        ratioWrap.style.borderRadius = '12px';
        ratioWrap.style.overflow = 'hidden';

        iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
        iframe.setAttribute('allowfullscreen', '');
        iframe.setAttribute('loading', 'lazy');
        iframe.style.position = 'absolute';
        iframe.style.inset = '0';
        iframe.style.width = '100%';
        iframe.style.height = '100%';
        iframe.style.border = '0';
        iframe.src = src;

        ratioWrap.appendChild(iframe);
        this.wrapper.appendChild(ratioWrap);
    }

    // --- URL parsing helpers ---
    /**
     * Vrátí { type: 'video'|'playlist', id: string, start?: number }
     * Podporované formáty:
     * - https://www.youtube.com/watch?v=VIDEO_ID(&t=1m30s|&start=90)
     * - https://youtu.be/VIDEO_ID?t=90
     * - https://www.youtube.com/shorts/VIDEO_ID
     * - https://www.youtube.com/embed/VIDEO_ID?start=90
     * - playlist: https://www.youtube.com/playlist?list=PLAYLIST_ID
     */
    static extractInfo(rawUrl: string): { type: YtType; id: string; start?: number } | null {
        try {
            const url = new URL(rawUrl);
            const host = url.hostname.replace(/^www\./i, '');
            if (!/(^|\.)youtube\.com$/.test(host) && host !== 'youtu.be') return null;

            // playlist detekce přes list param
            const list = url.searchParams.get('list');
            if (list) {
                return { type: 'playlist', id: list };
            }

            let videoId: string | null = null;
            // watch?v=
            if (url.pathname === '/watch') {
                videoId = url.searchParams.get('v');
            }
            // youtu.be/ID
            if (!videoId && host === 'youtu.be') {
                videoId = url.pathname.split('/').filter(Boolean)[0] || null;
            }
            // /shorts/ID
            if (!videoId && url.pathname.startsWith('/shorts/')) {
                videoId = url.pathname.split('/')[2] || null;
            }
            // /embed/ID
            if (!videoId && url.pathname.startsWith('/embed/')) {
                videoId = url.pathname.split('/')[2] || null;
            }
            // /live/ID (občas)
            if (!videoId && url.pathname.startsWith('/live/')) {
                videoId = url.pathname.split('/')[2] || null;
            }

            if (!videoId) return null;

            const start = YouTubeTool.parseStartSeconds(url);
            return { type: 'video', id: videoId, start: start ?? undefined };
        } catch {
            return null;
        }
    }

    /**
     * Podporuje t= (např. 1m30s, 90s, 90) i start=90
     */
    static parseStartSeconds(url: URL): number | null {
        const startParam = url.searchParams.get('start') || url.searchParams.get('t');
        if (!startParam) return null;
        const str = startParam.toString();

        // 1h2m3s / 2m / 90s / 90
        const re = /^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$/i;
        if (/^\d+$/.test(str)) return parseInt(str, 10);
        if (/^\d+s$/.test(str)) return parseInt(str, 10);
        const m = re.exec(str);
        if (!m) return null;
        const h = m[1] ? parseInt(m[1], 10) : 0;
        const mn = m[2] ? parseInt(m[2], 10) : 0;
        const s = m[3] ? parseInt(m[3], 10) : 0;
        return h * 3600 + mn * 60 + s;
    }

    static buildEmbedSrc(type: YtType, id: string, start?: number | null): string {
        if (type === 'playlist') {
            // YouTube playlist embed
            return `https://www.youtube.com/embed/videoseries?list=${encodeURIComponent(id)}`;
        }
        const params = new URLSearchParams();
        if (start && start > 0) params.set('start', String(start));
        const query = params.toString();
        return `https://www.youtube.com/embed/${encodeURIComponent(id)}${query ? `?${query}` : ''}`;
    }
}

/* Optional CSS
.cdx-youtube-embed { display: grid; gap: 8px; }
.cdx-youtube-embed .cdx-input { padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px; }
.cdx-youtube-embed .cdx-button { padding: 8px 12px; border-radius: 8px; background: #ef4444; color: white; border: 0; cursor: pointer; }
.cdx-youtube-embed .cdx-button:hover { filter: brightness(0.95); }
*/

// --- Usage example ---
// import EditorJS from '@editorjs/editorjs';
// import YouTubeTool from './YouTubeTool';
//
// const editor = new EditorJS({
//   holder: 'editor',
//   tools: {
//     youtube: YouTubeTool,
//   },
// });
