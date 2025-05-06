import naja from "naja";

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