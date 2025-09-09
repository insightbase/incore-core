import naja from 'naja';
import Dropzone from "dropzone";
import EditorJS from '@editorjs/editorjs';
import EditorJsRaw from '@editorjs/raw';
import EditorJsList from '@editorjs/list';
import EditorJsParagraph from '@editorjs/paragraph';
import EditorJsHeader from '@editorjs/header';
import EditorJsTable from '@editorjs/table';
import List from '@editorjs/list';
// import './../../frontend/assets/js/core.bundle';
// import './../../frontend/assets/css/styles.css';
// import './../../frontend/assets/js/widgets/general';
// import './../../frontend/assets/js/layouts/demo1';
// import './../../frontend/assets/vendors/keenicons/styles.bundle.css';
// import './../../frontend/assets/css/styles.css';
import 'dropzone/dist/dropzone.css';
import './form';
import './app.css';
import './content.css';
import netteForms from 'nette-forms';
import './content.js';

naja.initialize();
netteForms.initOnLoad();

let loaderId = null;
let editor = undefined;
const editors = {};

naja.addEventListener('start', (event) => {
    loader.hide(loaderId);
    if (!event.detail.options.notShowLoader) {
        loaderId = loader.show({
            text: '',
            variant: 'dots',
            color: '#fff',
            target: document.querySelector('.my-container'),
            backgroundColor: 'rgba(75,72,72,0.5)'
        });
    }
});
naja.redirectHandler.addEventListener('redirect', (event) => {
    event.detail.setHardRedirect(true);
});
naja.addEventListener('success', (event) => {
    netteForms.initOnLoad();
    initSystem();
});
naja.addEventListener('complete', (event) => {
    loader.hide(loaderId);
});
let globalSearchTimeout;

function initSystem(){
    initConfirmDelete();
    initSortable();
    initDatagrid();
    initEditorJs();
    initDropzone();
    initFlashes();
    initMenu();
}
initSystem();

function initConfirmDelete(){
    Array.from(document.getElementsByClassName('confirmDelete')).forEach((element) => {
        element.addEventListener('click', function(event){
            event.preventDefault();
            document.getElementsByClassName('confirmDeleteLink')[0].setAttribute('href', element.getAttribute('href'));
        });
    });
}

function initSortable() {
    Array.from(document.querySelectorAll('.draggable-zone')).forEach((container) => {
        new Draggable.Sortable(container, {
            draggable: '.draggable',
            handle: '.draggable-handle'
        })
            .on('sortable:stop', () => {
                const sortedIds = getSortedIds(container);
                let url = container.getAttribute('data-url-sort').replace('xxxxxx', sortedIds.join(','));
                naja.makeRequest('GET', url, {}, {history: false})
            });
    });
}
function getSortedIds(container) {
    let ret = [];
    Array.from(container.querySelectorAll('.draggable')).forEach((element) => {
        if (!element.classList.contains('draggable--original') && !element.classList.contains('draggable-mirror')) {
            ret.push(element.getAttribute('data-id'));
        }
    });
    return ret;
}

function initDatagrid() {

    Array.from(document.getElementsByClassName('globalSearch')).forEach((element) => {
        element.addEventListener('keyup', function (event) {
            if (event.key === 'Enter') {
                clearTimeout(globalSearchTimeout);
                let url = element.getAttribute('data-url').replace('xxxxxx', element.value);
                naja.makeRequest('GET', url);
            } else {
                console.log('event.key');
                clearTimeout(globalSearchTimeout);
                globalSearchTimeout = setTimeout(function () {
                    let url = element.getAttribute('data-url').replace('xxxxxx', element.value);
                    naja.makeRequest('GET', url);
                }, 1000);
            }
        });
    });

    Array.from(document.getElementsByClassName('inlineEdit')).forEach((element) => {
        element.addEventListener('click', inlineEdit);
    });

    Array.from(document.getElementsByClassName('inlineEditOpenModal')).forEach((element) => {
        element.addEventListener('click', function () {
            let td = element.parentElement;
            let dataHolder = td.getElementsByClassName('dataHolder')[0];
            let urlRefresh = dataHolder.getAttribute('data-inline-edit-url-refresh');
            naja.makeRequest('GET', urlRefresh, {}, { history: false });
        });
    });

    Array.from(document.getElementsByClassName('dataGridFilter')).forEach((element) => {
        var input = element.getElementsByClassName('filterInput')[0];
        input.addEventListener('change', function () {
            let value = input.value;
            if (input.getAttribute('type') === 'checkbox') {
                value = input.checked;
            }
            let url = element.getAttribute('data-url').replace('xxxxxx', value);
            naja.makeRequest('GET', url);
        });
    });
}

function generateUniqueId() {
    return Date.now() + Math.floor(Math.random() * 10000);
}

function initEditorJs() {
    Array.from(document.getElementsByClassName('editorJsText')).forEach((element) => {
        const editorDiv = document.createElement('div');
        editorDiv.classList.add('editorJsHolder');
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

        editors[id] = new EditorJS({
            holder: editorDiv,
            data: data,
            tools: tools
        });
        editorDiv.setAttribute('data-editor-id', id);
        element.setAttribute('data-for-editor-id', id);
        editorDiv.setAttribute('data-language-id', element.getAttribute('data-language-id'));
        if (element.getAttribute('langchange') !== null) {
            editorDiv.toggleAttribute('langchange');
            if (formLanguageSelect) {
                if (formLanguageSelect.value !== element.getAttribute('data-language-id')) {
                    editorDiv.style.display = 'none';
                }
            }
        }
    });
    Array.from(document.getElementsByTagName('form')).forEach((element) => {
        element.onsubmit = function (event) {
            Array.from(document.getElementsByClassName('editorJsText')).forEach((elementEditor) => {
                editors[elementEditor.getAttribute('data-for-editor-id')].save().then((data) => {
                    elementEditor.value = JSON.stringify(data);
                })
            });
        };
    });
}

function truncateText(text, maxLength) {
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

function inlineEdit(event) {
    let element;
    if (event.target.tagName !== 'TD') {
        element = event.target.closest('td');
    } else {
        element = event.target;
    }
    let elementText;
    elementText = element.getElementsByClassName('text')[0];
    element.removeEventListener('click', inlineEdit);

    let dataHolder = element.getElementsByClassName('dataHolder')[0];
    let value = dataHolder.getAttribute('data-value');
    const escaped = value.replace(/"/g, '&quot;');
    elementText.innerHTML = dataHolder.getAttribute('data-inline-input').replace('xxxx', escaped);
    var input = element.getElementsByClassName('input')[0];

    let saveEditor = element.getElementsByClassName('saveEditor');
    if (saveEditor.length > 0) {
        saveEditor = saveEditor[0];
        saveEditor.addEventListener('click', function (event) {
            event.preventDefault();
            editors[input.getAttribute('data-for-editor-id')].save().then((outputData) => {
                let url = dataHolder.getAttribute('data-inline-edit-url').replace('xxxx', JSON.stringify(outputData));
                naja.makeRequest('GET', url, {}, { history: false, notShowLoader: true });
            });
        });
    }
    input.focus();
    input.selectionStart = input.selectionEnd = input.value.length;

    input.addEventListener('blur', function () {
        let url = dataHolder.getAttribute('data-inline-edit-url').replace('xxxx', input.value);
        input.remove();
        naja.makeRequest('GET', url, {}, { history: false, notShowLoader: true });
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            const event = new Event('blur');
            input.dispatchEvent(event);
        }
    });

    initEditorJs();
}

function initDropzone() {
    let uploadedImageIds = {};
    Array.from(document.getElementsByClassName('dropzoneImage')).forEach((element, index) => {
        if (!element.dropzone) {
            let dzKey = `dropzone-${index}`;
            uploadedImageIds[dzKey] = [];

            let dropzone = new Dropzone(element, {
                url: element.getAttribute('data-upload-url'),
                maxFiles: element.getAttribute('data-multiple') === null ? 1 : null,
                addRemoveLinks: true,
                chunking: true,
                chunkSize: 2000000,
            });
            dropzone.on('success', (file, response) => {
                if (element.getAttribute('data-multiple') == null) {
                    element.parentElement.getElementsByTagName('input')[0].value = Number(response.imageId);
                } else {
                    uploadedImageIds[dzKey].push(response.imageId);
                }
            });
            dropzone.on('queuecomplete', (file, response) => {
                if (element.getAttribute('data-multiple') != null) {
                    element.parentElement.getElementsByTagName('input')[0].value = uploadedImageIds[dzKey].join(';');
                }
            });
        }
    });

    let uploadedFileIds = {};
    Array.from(document.getElementsByClassName('dropzoneFile')).forEach((element, index) => {
        if (!element.dropzone) {
            let dzKey = `dropzone-${index}`;
            uploadedFileIds[dzKey] = [];

            let dropzone = new Dropzone(element, {
                url: element.getAttribute('data-upload-url'),
                maxFiles: element.getAttribute('data-multiple') === null ? 1 : null,
                addRemoveLinks: true,
            });
            dropzone.on('success', (file, response) => {
                if (element.getAttribute('data-multiple') == null) {
                    element.parentElement.getElementsByTagName('input')[0].value = Number(response.fileId);
                } else {
                    uploadedFileIds[dzKey].push(response.fileId);
                }
            });
            dropzone.on('queuecomplete', (file, response) => {
                if (element.getAttribute('data-multiple') != null) {
                    element.parentElement.getElementsByTagName('input')[0].value = uploadedFileIds[dzKey].join(';');
                }
            });
        }
    });
    Array.from(document.getElementsByClassName('avatarContainer')).forEach((element, index) => {
        const inputChanged = element.getElementsByClassName('avatarInput')[0];
        const input = element.getElementsByTagName('input')[0];
        inputChanged.addEventListener('change', function(event){
            const file = event.target.files[0];
            const url = inputChanged.getAttribute('data-upload-url');
            const formData = new FormData();
            formData.append('file', file);
            naja.makeRequest('POST', url, formData, {history: false}).then(function(payload){
                input.value = Number(payload.imageId);
            });
        });
    });
}

function initFlashes() {
    const flashes = document.getElementsByClassName('flashes')[0];
    Array.from(flashes.getElementsByClassName('flash')).forEach((element) => {
        toast.show({ type: element.getAttribute('data-toast'), message: element.innerHTML })
    });
}

document.addEventListener('DOMContentLoaded', function () {
    
    const clickableRows = document.querySelectorAll('.clickable-row');

    if (clickableRows.length === 0) {
        return;
    }

    clickableRows.forEach((row, index) => {
        
        row.addEventListener('click', function (e) {
            
            const excludedElement = e.target.closest('button, a, .menu, .dropdown, .menu-dropdown, .menu-item, .menu-link');
            if (excludedElement) {
                return;
            }

            const href = this.dataset.href;
            
            if (href) {
                window.location.href = href;
            }
        });

        row.addEventListener('mouseenter', function () {
            this.classList.add('selectedRow');
        });

        row.addEventListener('mouseleave', function () {
            this.classList.remove('selectedRow');
        });

    });
});

function initMenu(){
    document.querySelectorAll('[data-role="toggle"]').forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const menuItem = toggle.closest('[data-menu-item-toggle="accordion"]');
            const rootMenu = toggle.closest('[data-kt-menu="true"]');

            if (rootMenu && menuItem) {
                const ktMenu = KTMenu.getInstance(rootMenu);
                if (ktMenu) {
                    ktMenu.toggle(menuItem); // použij Metronic API správně
                }
            }
        });
    });
}