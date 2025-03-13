import naja from 'naja';
import Dropzone from "dropzone";
import EditorJS from '@editorjs/editorjs';
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

naja.initialize();

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
    initDatagrid();
    initFlashes();
    initDropzone();
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
            let modalEl = document.getElementById('datagrid-inline-edit');
            modalEl.getElementsByClassName('textarea')[0].value = td.getAttribute('data-value');
            modalEl.getElementsByClassName('dataGrid-inline-submit')[0].addEventListener('click', function(){
                let url = td.getAttribute('data-inline-edit-url').replace('xxxx', modalEl.getElementsByClassName('textarea')[0].value);
                naja.makeRequest('GET', url, {}, {history: false, notShowLoader: true});
            });
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

class CustomHTML {
    static get toolbox() {
        return {
            title: 'HTML',
            icon: '<svg></svg>',
        };
    }

    constructor({ data }) {
        this.data = data || { html: '' };
    }

    render() {
        this.textarea = document.createElement('textarea');
        this.textarea.value = this.data.html;
        return this.textarea;
    }

    save() {
        return {
            html: this.textarea.value,
        };
    }
}

const editors = {};
function generateUniqueId() {
    return Date.now() + Math.floor(Math.random() * 10000);
}

Array.from(document.getElementsByClassName('editorJsText')).forEach((element) => {
    const editorDiv = document.createElement('div');
    editorDiv.classList.add('editorJsHolder');
    element.after(editorDiv);

    const id = generateUniqueId();

    let data = '';
    if(element.value !== ''){
        data = JSON.parse(element.value);
    }

    editors[id] = new EditorJS({
        holder: editorDiv,
        data: data,
        tools: {
            list: {
                class: List,
                inlineToolbar: true, // povolí panel pro úpravy přímo v seznamu
            },
            customHTML: CustomHTML,
        }
    });
    editorDiv.setAttribute('data-editor-id', id);
    element.setAttribute('data-for-editor-id', id);
    editorDiv.setAttribute('data-language-id', element.getAttribute('data-language-id'));
    if(element.getAttribute('langchange') !== null){
        editorDiv.toggleAttribute('langchange');
        if (formLanguageSelect) {
            if(formLanguageSelect.value !== element.getAttribute('data-language-id')){
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

function truncateText(text, maxLength) {
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

function inlineEdit(event){
    if(event.target.tagName === 'TD' || event.target.tagName === 'SPAN') {
        let elementText;
        let element;
        if(event.target.tagName === 'SPAN'){
            elementText = event.target;
            element = event.target.parentElement;
        }else{
            elementText = event.target.getElementsByClassName('text')[0];
            element = event.target;
        }
        element.removeEventListener('click', inlineEdit);

        let value = element.getAttribute('data-value');
        if (value.length <= 50) {
            elementText.innerHTML = '<input type="text" class="input" value="' + value + '" />';
            let input = element.getElementsByClassName('input')[0];
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
        }
    }
}

// netteForms.initOnLoad();

function initDropzone(){
    Array.from(document.getElementsByClassName('dropzone')).forEach((element) => {
        if(!element.dropzone) {
            let dropzone = new Dropzone(element, {
                url: element.getAttribute('data-upload-url'),
                maxFiles: 1,
                addRemoveLinks: true,
            });
            dropzone.on('success', (file, response) => {
                element.parentElement.getElementsByTagName('input')[0].value = Number(response.imageId);
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

