import naja from "naja";

Array.from(document.getElementsByClassName('remove-fieldset')).forEach((element) => {
    element.addEventListener('click', function(event){
        event.preventDefault();
    });
});


document.addEventListener('DOMContentLoaded', function() {
    window.repeater = new FormRepeater({
        container: document.querySelector('[data-repeater="contact"]'),
        // namePattern: 'row[{name}][{index}]',
    });
});

naja.addEventListener('success', (event) => {
   initForms();
});

initForms();
function initForms() {
    //najdu vsechny co generuji slug
    document.querySelectorAll('input[data-source-input]').forEach((element) => {
        var sourceInput = document.getElementById(element.getAttribute('data-source-input'));
        if(slugify(sourceInput.value) === element.value || element.value === '') {
            sourceInput.addEventListener('keyup', function (event) {
                element.value = slugify(sourceInput.value);
            });
        }
    });

    //najdu vsechny co kopiruji text
    document.querySelectorAll('input[data-source-copy-input]').forEach((element) => {
        var sourceInput = document.getElementById(element.getAttribute('data-source-copy-input'));
        sourceInput.addEventListener('beforeinput', () => {
            sourceInput._wasSameBefore =
                sourceInput.value === element.value;
        });
        sourceInput.addEventListener('input', function (event) {
            if(sourceInput._wasSameBefore || element.value === '') {
                element.value = sourceInput.value;
            }
        });
    });
    document.querySelectorAll('textArea[data-source-copy-input]').forEach((element) => {
        var sourceInput = document.getElementById(element.getAttribute('data-source-copy-input'));
        sourceInput.addEventListener('beforeinput', () => {
            sourceInput._wasSameBefore =
                sourceInput.value === element.value;
        });
        sourceInput.addEventListener('input', function (event) {
            if(sourceInput._wasSameBefore || element.value === '') {
                element.value = sourceInput.value;
            }
        });
    });

// najdeme na stránce všechny podřízené selectboxy
    document.querySelectorAll('select[data-depends]').forEach((childSelect) => {
        let parentSelect = childSelect.form[childSelect.dataset.depends]; // nadřízený <select>
        let url = childSelect.dataset.url; // atribut data-url
        let items = JSON.parse(childSelect.dataset.items || 'null'); // atribut data-items

        // když uživatel změní vybranou položku v nadřízeném selectu...
        parentSelect.addEventListener('change', () => {
            // pokud existuje atribut data-items...
            if (items) {
                // nahrajeme rovnou do podřízeného selectboxu nové položky
                updateSelectbox(childSelect, items[parentSelect.value]);
            }

            // pokud existuje atribut data-url...
            if (url) {
                // uděláme AJAXový požadavek na endpoint s vybranou položkou místo placeholderu
                fetch(url.replace(encodeURIComponent('#'), encodeURIComponent(parentSelect.value)))
                    .then((response) => response.json())
                    // a nahrajeme do podřízeného selectboxu nové položky
                    .then((data) => updateSelectbox(childSelect, data));
            }
        });
    });

    Array.from(document.getElementsByClassName('formLanguageSelect')).forEach((formLanguageSelect) => {
        formLanguageSelect.addEventListener('change', function () {
            updateFormLanguageSelect(formLanguageSelect.value);
        });
        updateFormLanguageSelect(formLanguageSelect.value);
    });
}

function updateFormLanguageSelect(value){
    Array.from(document.querySelectorAll('[langchange]')).forEach((element) => {
        element.style.display = 'none';
    });
    Array.from(document.querySelectorAll('[data-language-id="' + value + '"]')).forEach((element) => {
        element.style.display = 'inline';
    });
}

// přepíše <options> v <select>
function updateSelectbox(select, items) {
    select.innerHTML = ''; // odstraníme vše
    for (let id in items) { // vložíme nové
        let el = document.createElement('option');
        el.setAttribute('value', id);
        el.innerText = items[id];
        select.appendChild(el);
    }
}

function slugify(text) {
    return text
        .toString()
        .normalize('NFD')                  // rozloží diakritiku
        .replace(/[\u0300-\u036f]/g, '')   // odstraní diakritiku
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')       // nahradí vše kromě písmen/číslic pomlčkou
        .replace(/^-+|-+$/g, '');          // odstraní pomlčky na začátku/konci
}