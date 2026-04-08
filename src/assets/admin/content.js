import naja from "naja";
import {initEditorJs} from "./editorJs";

naja.addEventListener('success', (event) => {
    initBLockItemValueEdit();
    initInlineEditorJs();
});

let contentDetail = document.getElementsByClassName('contentDetail')[0];
if (contentDetail) {
    let formLanguageSelect = contentDetail.getElementsByClassName('formLanguageSelect')[0];
    if(formLanguageSelect){
        formLanguageSelect.addEventListener('change', function () {
            let url = contentDetail.getAttribute('data-change-language-url').replace('0', formLanguageSelect.value);
            naja.makeRequest('GET', url)
        });
    }
}

initBLockItemValueEdit();
initInlineEditorJs();

function initBLockItemValueEdit() {
    Array.from(document.getElementsByClassName('blockItem-value-edit')).forEach((element) => {
        Array.from(element.getElementsByTagName('input')).forEach((input) => {
            input.addEventListener('change', function () {
                blockItemValueEdit(input);
            });
            input.addEventListener('keydown', function () {
                if (event.key === 'Enter') {
                    blockItemValueEdit(input);
                }
            });
        });
        Array.from(element.getElementsByTagName('textarea')).forEach((input) => {
            if (input.classList.contains('editorJs-inline-input')) {
                return;
            }
            input.addEventListener('change', function () {
                blockItemValueEdit(input);
            });
        });
    });
}

function blockItemValueEdit(input){
    let url = input.getAttribute('data-url-change').replace('xxxx', encodeURIComponent(input.value));
    naja.makeRequest('GET', url, {}, {history: false});
}

function initInlineEditorJs() {
    Array.from(document.getElementsByClassName('editorJs-inline-wrapper')).forEach((wrapper) => {
        if (wrapper.dataset.inlineEditorInitialized) {
            return;
        }
        wrapper.dataset.inlineEditorInitialized = '1';

        const card = wrapper.closest('.card');
        if (!card) return;

        const textarea = wrapper.querySelector('.editorJs-inline-input');
        if (!textarea) return;

        // Hide the show div, always show the edit div for EditorJs blocks
        const editDiv = wrapper.closest('.blockItem-value-edit');
        const showDiv = editDiv ? editDiv.previousElementSibling : null;
        if (showDiv && showDiv.classList.contains('blockItem-value-show')) {
            showDiv.style.display = 'none';
        }
        if (editDiv) {
            editDiv.style.display = 'block';
            card.classList.add('has-editorJs');
        }

        // Initialize EditorJs immediately
        initEditorJs();

        const urlChange = textarea.getAttribute('data-url-change');

        const editorHolder = wrapper.querySelector('.editorJsHolder');
        if (editorHolder) {
            let saveTimeout = null;
            editorHolder.addEventListener('focusout', function () {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(function () {
                    if (editorHolder.contains(document.activeElement)) return;
                    if (!urlChange) return;
                    const editorId = editorHolder.getAttribute('data-editor-id');
                    if (editorId) {
                        saveInlineEditorJs(editorId, urlChange);
                    }
                }, 200);
            });
            editorHolder.addEventListener('focusin', function () {
                clearTimeout(saveTimeout);
            });
        }
    });
}

function saveInlineEditorJs(editorId, urlChange) {
    if (!window.editors || !window.editors[editorId]) return;

    window.editors[editorId].save().then((data) => {
        const json = JSON.stringify(data);
        const url = urlChange.replace('xxxx', encodeURIComponent(json));
        naja.makeRequest('GET', url, {}, {history: false, notShowLoader: true});
    });
}
