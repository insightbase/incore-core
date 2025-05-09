import naja from "naja";

naja.addEventListener('success', (event) => {
    initBLockItemValueEdit();
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
function initBLockItemValueEdit() {
    Array.from(document.getElementsByClassName('blockItem-value-edit')).forEach((element) => {
        Array.from(document.getElementsByTagName('input')).forEach((input) => {
            input.addEventListener('change', function () {
                blockItemValueEdit(input.value);
            });
            input.addEventListener('keydown', function () {
                if (event.key === 'Enter') {
                    blockItemValueEdit(input.value);
                }
            });
        });
    });
}
function blockItemValueEdit(input){
    let url = input.getAttribute('data-url-change').replace('xxxx', value);
    naja.makeRequest('GET', url, {}, {history: false});
}