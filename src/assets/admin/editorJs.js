import EditorJS from '@editorjs/editorjs';
import EditorJsRaw from '@editorjs/raw';
import EditorJsList from '@editorjs/list';
import EditorJsParagraph from '@editorjs/paragraph';
import EditorJsHeader from '@editorjs/header';
import EditorJsTable from '@editorjs/table';
import List from '@editorjs/list';
import naja from "naja";
import UploadLinkInlineTool from './editorJs/uploadLinkInlineTool.js';
import ImageGallery from '@kiberpro/editorjs-gallery';
import Sortable from 'sortablejs';
import FAQ from './editorJs/faq';
import Citation from './editorJs/citation';
import SpotifyTool from './editorJs/spotifyTool';
import YouTubeTool from './editorJs/youTubeTool';
import AnchorTune from './editorJs/AnchorTune.ts';
import HighlightTune from './editorJs/HighlightTune';
import ImageWithReplace from './editorJs/ImageWithReplace';
import AudioTool from '@furison-tech/editorjs-audio';
import Partner from './editorJs/Partner.js';


let editor = undefined;
if (!window.editors) window.editors = {};

export function initEditorJs() {
    Array.from(document.getElementsByClassName('editorJsText')).forEach((element) => {
        if (element.hasAttribute('data-for-editor-id')) {
            return;
        }

        const editorDiv = document.createElement('div');
        editorDiv.classList.add('editorJsHolder');
        if (element.classList.contains('hidden')) {
            editorDiv.classList.add('hidden');
        }
        element.after(editorDiv);

        const id = generateUniqueId();

        let data = '';
        if (element.value !== '') {
            data = JSON.parse(element.value);
        }

        let types = element.getAttribute('data-type').split(";");;

        const tools = {};

        if (types.includes("raw")) {
            tools.raw = EditorJsRaw;
        }
        if (types.includes("paragraph")) {
            tools.paragraph = {
                class: EditorJsParagraph,
                config: {
                    placeholder: 'Add paragraph',
                    preserveBlank: true,
                }
            };
        }
        if (types.includes("list")) {
            tools.list = {
                class: EditorJsList,
                inlineToolbar: true,
            };
        }
        if (types.includes("header")) {
            tools.header = {
                class: EditorJsHeader,
                inlineToolbar: true,
                config: {
                    placeholder: 'Add list',
                    levels: [2, 3, 4],
                    defaultLevel: 2
                }
            };
        }
        if (types.includes("table")) {
            tools.table = EditorJsTable;
        }
        if (types.includes("image")) {
            tools.image = {
                class: ImageWithReplace,
                    config: {
                    endpoints: {
                        byFile: element.getAttribute('data-upload-url'), // Your backend file uploader endpoint
                    }
                },
            }
        }
        if (types.includes("faq")) {
            tools.faq = {
                class: FAQ,
            }
        }

        tools.uploadLink = {
            class: UploadLinkInlineTool,
            config: {
                endpoint: element.getAttribute('data-upload-url'),
            }
        }

        window.editors[id] = new EditorJS({
            holder: editorDiv,
            data: data,
            tools: tools
        });
        editorDiv.setAttribute('data-editor-id', id);
        element.setAttribute('data-for-editor-id', id);
        editorDiv.setAttribute('data-language-id', element.getAttribute('data-language-id'));
        if (element.getAttribute('langchange') !== null) {
            editorDiv.toggleAttribute('langchange');
        }
    });
    Array.from(document.getElementsByTagName('form')).forEach((element) => {
        element.onsubmit = function (event) {
            Array.from(document.getElementsByClassName('editorJsText')).forEach((elementEditor) => {
                window.editors[elementEditor.getAttribute('data-for-editor-id')].save().then((data) => {
                    elementEditor.value = JSON.stringify(data);
                })
            });
        };
    });
}

function generateUniqueId() {
    return Date.now() + Math.floor(Math.random() * 10000);
}