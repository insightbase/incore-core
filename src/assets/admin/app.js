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

naja.addEventListener('start', (event) => {
    loader.hide(loaderId);
    if(!event.detail.options.notShowLoader) {
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
    initDatagrid();
    initFlashes();
    initDropzone();
    initEditorJs();
    initSortable();
    loader.hide(loaderId);
});
let globalSearchTimeout;

let formLanguageSelect = document.getElementsByClassName('formLanguageSelect')[0];
if (formLanguageSelect) {
    formLanguageSelect.addEventListener('change', function () {
        Array.from(document.querySelectorAll('[langchange]')).forEach((element) => {
            element.style.display = 'none';
        });
        Array.from(document.querySelectorAll('[data-language-id="' + formLanguageSelect.value + '"]')).forEach((element) => {
            element.style.display = 'inline';
        });
    });
}

initSortable();
function initSortable() {
    const container = document.querySelector('.draggable-zone');
    if(container !== null) {
        new Draggable.Sortable(container, {
            draggable: '.draggable',
            handle: '.draggable-handle'
        })
            .on('sortable:stop', () => {
                const sortedIds = getSortedIds(container);
                let url = container.getAttribute('data-url-sort').replace('xxxxxx', sortedIds.join(','));
                naja.makeRequest('GET', url, {}, {history: false})
            });
    }
}
function getSortedIds(container) {
    let ret = [];
    Array.from(container.querySelectorAll('.draggable')).forEach((element) => {
        if(!element.classList.contains('draggable--original') && !element.classList.contains('draggable-mirror')) {
            ret.push(element.getAttribute('data-id'));
        }
    });
    return ret;
}

function initDatagrid() {

    Array.from(document.getElementsByClassName('globalSearch')).forEach((element) => {
        element.addEventListener('keyup', function () {
            clearTimeout(globalSearchTimeout);
            globalSearchTimeout = setTimeout(function () {
                let url = element.getAttribute('data-url').replace('xxxxxx', element.value);
                naja.makeRequest('GET', url)
            }, 300);
        });
    });

    Array.from(document.getElementsByClassName('inlineEdit')).forEach((element) => {
        element.addEventListener('click', inlineEdit);
    });

    Array.from(document.getElementsByClassName('inlineEditOpenModal')).forEach((element) => {
        element.addEventListener('click', function(){
            let td = element.parentElement;
            let urlRefresh = td.getAttribute('data-inline-edit-url-refresh');
            naja.makeRequest('GET', urlRefresh, {}, {history: false});
        });
    });

    Array.from(document.getElementsByClassName('dataGridFilter')).forEach((element) => {
       var input = element.getElementsByClassName('filterInput')[0];
       input.addEventListener('change', function(){
           let value = input.value;
           if(input.getAttribute('type') === 'checkbox'){
               value = input.checked;
           }
           let url = element.getAttribute('data-url').replace('xxxxxx', value);
           naja.makeRequest('GET', url);
       });
    });
}
initDatagrid();

const editors = {};
function generateUniqueId() {
    return Date.now() + Math.floor(Math.random() * 10000);
}

initEditorJs();
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

        editors[id] = new EditorJS({
            holder: editorDiv,
            data: data,
            tools: {
                raw: EditorJsRaw,
                paragraph: {
                    class: EditorJsParagraph,
                    config: {
                        placeholder: 'Add paragraph',
                        preserveBlank: true,
                    }
                },
                list: {
                    class: EditorJsList,
                    inlineToolbar: true,
                },
                header: {
                    class: EditorJsHeader,
                    inlineToolbar: true,
                    config: {
                        placeholder: 'Add list',
                        levels: [2, 3, 4],
                        defaultLevel: 2
                    }
                },
                table: EditorJsTable
            }
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
        element.onsubmit = function(event) {
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

function inlineEdit(event){
    let element;
    if(event.target.tagName !== 'TD'){
        element = event.target.closest('td');
    }else{
        element = event.target;
    }
    let elementText;
    elementText = element.getElementsByClassName('text')[0];
    element.removeEventListener('click', inlineEdit);

    let value = element.getAttribute('data-value');
    const escaped = value.replace(/"/g, '&quot;');
    elementText.innerHTML = element.getAttribute('data-inline-input').replace('xxxx', escaped);
    var input = element.getElementsByClassName('input')[0];

    let saveEditor = element.getElementsByClassName('saveEditor');
    if(saveEditor.length > 0){
        saveEditor = saveEditor[0];
        saveEditor.addEventListener('click', function(event){
            event.preventDefault();
            editors[input.getAttribute('data-for-editor-id')].save().then((outputData) =>{
                let url = element.getAttribute('data-inline-edit-url').replace('xxxx', JSON.stringify(outputData));
                naja.makeRequest('GET', url, {}, {history: false, notShowLoader: true});
            });
        });
    }
    input.focus();
    input.selectionStart = input.selectionEnd = input.value.length;

    input.addEventListener('blur', function () {
        let url = element.getAttribute('data-inline-edit-url').replace('xxxx', input.value);
        naja.makeRequest('GET', url, {}, {history: false, notShowLoader: true});
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            const event = new Event('blur');
            input.dispatchEvent(event);
        }
    });

    initEditorJs();
}

function initDropzone(){
    let uploadedImageIds = {};
    Array.from(document.getElementsByClassName('dropzoneImage')).forEach((element, index) => {
        if(!element.dropzone) {
            let dzKey = `dropzone-${index}`;
            uploadedImageIds[dzKey] = [];

            let dropzone = new Dropzone(element, {
                url: element.getAttribute('data-upload-url'),
                maxFiles: element.getAttribute('data-multiple') === null ? 1 : null,
                addRemoveLinks: true,
            });
            dropzone.on('success', (file, response) => {
                if(element.getAttribute('data-multiple') == null) {
                    element.parentElement.getElementsByTagName('input')[0].value = Number(response.imageId);
                }else{
                    uploadedImageIds[dzKey].push(response.imageId);
                }
            });
            dropzone.on('queuecomplete', (file, response) => {
                if(element.getAttribute('data-multiple') != null) {
                    element.parentElement.getElementsByTagName('input')[0].value = uploadedImageIds[dzKey].join(';');
                }
            });
        }
    });

    let uploadedFileIds = {};
    Array.from(document.getElementsByClassName('dropzoneFile')).forEach((element, index) => {
        if(!element.dropzone) {
            let dzKey = `dropzone-${index}`;
            uploadedFileIds[dzKey] = [];

            let dropzone = new Dropzone(element, {
                url: element.getAttribute('data-upload-url'),
                maxFiles: element.getAttribute('data-multiple') === null ? 1 : null,
                addRemoveLinks: true,
            });
            dropzone.on('success', (file, response) => {
                if(element.getAttribute('data-multiple') == null) {
                    element.parentElement.getElementsByTagName('input')[0].value = Number(response.fileId);
                }else{
                    uploadedFileIds[dzKey].push(response.fileId);
                }
            });
            dropzone.on('queuecomplete', (file, response) => {
                if(element.getAttribute('data-multiple') != null) {
                    element.parentElement.getElementsByTagName('input')[0].value = uploadedFileIds[dzKey].join(';');
                }
            });
        }
    });
}
initDropzone();

function initFlashes(){
    const flashes = document.getElementsByClassName('flashes')[0];
    Array.from(flashes.getElementsByClassName('flash')).forEach((element) => {
        toast.show({type: element.getAttribute('data-toast'), message: element.innerHTML})
    });
}
initFlashes();

