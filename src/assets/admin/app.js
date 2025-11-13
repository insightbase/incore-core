import naja from 'naja';
import Dropzone from "dropzone";
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
import { initEditorJs } from './editorJs';
import './performance';
import './tabs';
import './editorJs.css';

naja.initialize();
netteForms.initOnLoad();

let loaderId = null;
window.editors = {};

// Přepíšeme globální callback pro zobrazování chyb
netteForms.showFormErrors = function (form, errors) {
    var invalidClass = 'border-danger';
    var hintAttr = 'data-nette-error';
    var hintSelector = '.form-hint.text-danger[' + hintAttr + '="1"]';

    if (!form) {
        return;
    }

    // 1) Smazat staré označení a staré hlášky
    form.querySelectorAll('.' + invalidClass).forEach(function (el) {
        el.classList.remove(invalidClass);
    });

    form.querySelectorAll(hintSelector).forEach(function (el) {
        if (el.parentNode) {
            el.parentNode.removeChild(el);
        }
    });

    form.querySelectorAll('.tab-error-indicator').forEach(function (el) {
        el.remove();
    });
    form.querySelectorAll('[data-tab-toggle]').forEach(function (toggle) {
        toggle.classList.remove('tab-has-error');
    });

    var tabsWithError = new Set();

    // 2) Přidat classu a span ke každému chybovému prvku
    errors.forEach(function (error) {
        if (!error.element) {
            return;
        }

        var el = error.element;

        // classa na input
        el.classList.add(invalidClass);

        // span s hláškou
        var span = document.createElement('span');
        span.className = 'form-hint text-danger';
        span.textContent = error.message;
        span.setAttribute(hintAttr, '1'); // abychom je příště bezpečně smazali

        // vložit hned za input
        if (el.nextSibling) {
            el.parentNode.insertBefore(span, el.nextSibling);
        } else {
            el.parentNode.appendChild(span);
        }

        // >>> NOVÉ: projdeme VŠECHNY nadřazené taby (vnořené)
        var tab = el.closest('[data-form-tab]');
        while (tab) {
            var tabId = tab.getAttribute('id');
            if (tabId && !tabsWithError.has(tabId)) {
                tabsWithError.add(tabId);

                // najdeme toggle pro tenhle tab
                var toggle = form.querySelector('[data-tab-toggle="#' + tabId + '"]');
                if (toggle) {
                    // přidáme classu a vykřičník
                    toggle.classList.add('tab-has-error');

                    var badge = document.createElement('span');
                    badge.className = 'tab-error-indicator';
                    badge.textContent = '!'; // nebo "⚠" podle chuti

                    toggle.appendChild(badge);
                }
            }

            // posunout se na NADŘAZENÝ tab (vnořené taby)
            tab = tab.parentElement ? tab.parentElement.closest('[data-form-tab]') : null;
        }
    });

    // 3) Otevřít tab s první chybou (včetně všech nadřazených) + focusnout první chybné pole
    if (errors.length && errors[0].element) {
        var firstEl = errors[0].element;

        // řetěz id vnořených tabů od NEJVRCHNĚJŠÍHO po NEJVNITŘNĚJŠÍ
        var tabChain = [];
        var t = firstEl.closest('[data-form-tab]');
        while (t) {
            var id = t.getAttribute('id');
            if (id) {
                // cpeme na začátek, aby pořadí bylo outer → inner
                tabChain.unshift(id);
            }
            t = t.parentElement ? t.parentElement.closest('[data-form-tab]') : null;
        }

        // postupně otevřeme všechny taby v řetězu
        tabChain.forEach(function (tabId) {
            var toggle = form.querySelector('[data-tab-toggle="#' + tabId + '"]');
            if (toggle) {
                toggle.click();
            }
        });

        if (typeof firstEl.focus === 'function') {
            firstEl.focus();
        }
    }
};


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
        let dataGrid = element.closest('.dataGrid');
        element.addEventListener('keyup', function (event) {
            if (event.key === 'Enter') {
                clearTimeout(globalSearchTimeout);
                let url = element.getAttribute('data-url').replace('xxxxxx', element.value);
                naja.makeRequest('GET', url);
            } else {
                clearTimeout(globalSearchTimeout);
                globalSearchTimeout = setTimeout(function () {
                    let url = element.getAttribute('data-url').replace('xxxxxx', element.value);
                    naja.makeRequest('GET', url).then((payload) => {
                        const input = dataGrid.getElementsByClassName('globalSearch')[0];
                        const len = input.value.length;
                        input.focus();
                        input.setSelectionRange(len, len);
                    });
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
            window.editors[input.getAttribute('data-for-editor-id')].save().then((outputData) => {
                let url = dataHolder.getAttribute('data-inline-edit-url').replace('xxxx', JSON.stringify(outputData));
                naja.makeRequest('GET', url, {}, { history: false, notShowLoader: true });
            });
        });
    }
    input.focus();
    input.selectionStart = input.selectionEnd = input.value.length;

    input.addEventListener('blur', function () {
        let url = dataHolder.getAttribute('data-inline-edit-url').replace('xxxx', encodeURIComponent(input.value));
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
                chunkSize: element.getAttribute('data-chunksize'),
            });
            dropzone.on('success', (file, response) => {
                if (element.getAttribute('data-multiple') == null) {
                    element.parentElement.getElementsByTagName('input')[0].value = Number(response.imageId);
                    element.parentElement.getElementsByTagName('input')[0].dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    uploadedImageIds[dzKey].push(response.imageId);
                }
            });
            dropzone.on('queuecomplete', (file, response) => {
                if (element.getAttribute('data-multiple') != null) {
                    element.parentElement.getElementsByTagName('input')[0].value = uploadedImageIds[dzKey].join(';');
                    element.parentElement.getElementsByTagName('input')[0].dispatchEvent(new Event('change', { bubbles: true }));
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
                chunking: true,
                chunkSize: element.getAttribute('data-chunksize'),
            });
            dropzone.on('success', (file, response) => {
                if (element.getAttribute('data-multiple') == null) {
                    element.parentElement.getElementsByTagName('input')[0].value = Number(response.fileId);
                    element.parentElement.getElementsByTagName('input')[0].dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    uploadedFileIds[dzKey].push(response.fileId);
                }
            });
            dropzone.on('queuecomplete', (file, response) => {
                if (element.getAttribute('data-multiple') != null) {
                    element.parentElement.getElementsByTagName('input')[0].value = uploadedFileIds[dzKey].join(';');
                    element.parentElement.getElementsByTagName('input')[0].dispatchEvent(new Event('change', { bubbles: true }));
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