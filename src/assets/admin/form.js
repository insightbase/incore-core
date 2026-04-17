import naja from "naja";

const optionsToggleBound = new WeakSet();
const optionsWidgetBound = new WeakSet();

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

    //najdu vsechny co generuji slug v edit modu
    document.querySelectorAll('input[data-source-input-editmode]').forEach((element) => {
        element.addEventListener('change', function(event){
            element.value = slugify(element.value);
        });
        element.addEventListener('paste', function(event){
            event.preventDefault();
            element.value = slugify((event.clipboardData || window.clipboardData).getData('text'));
            element.dispatchEvent(new Event('input', { bubbles: true }));
        });
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

    initOptionsWidget(document);
    initOptionsToggle(document);

    Array.from(document.getElementsByClassName('formLanguageSelect')).forEach((formLanguageSelect) => {
        formLanguageSelect.addEventListener('change', function () {
            updateFormLanguageSelect(formLanguageSelect.value);
        });
        updateFormLanguageSelect(formLanguageSelect.value);
    });
}

function initOptionsWidget(root){
    root.querySelectorAll('input[data-options-widget]').forEach((input) => {
        if (optionsWidgetBound.has(input)) return;
        optionsWidgetBound.add(input);

        const widget = document.createElement('div');
        widget.className = 'options-widget flex flex-wrap gap-1.5 items-center';

        const chipsWrap = document.createElement('div');
        chipsWrap.className = 'flex flex-wrap gap-1.5';
        widget.appendChild(chipsWrap);

        const addGroup = document.createElement('div');
        addGroup.className = 'input-group flex items-center gap-1';
        addGroup.style.flex = '1 1 160px';

        const addInput = document.createElement('input');
        addInput.type = 'text';
        addInput.className = 'input';
        addInput.style.flex = '1 1 auto';
        addInput.placeholder = 'Přidat hodnotu…';
        addGroup.appendChild(addInput);

        const addBtn = document.createElement('button');
        addBtn.type = 'button';
        addBtn.className = 'btn btn-icon btn-primary';
        addBtn.setAttribute('aria-label', 'Přidat');
        addBtn.innerHTML = '<i class="ki-filled ki-plus fs-5"></i>';
        addGroup.appendChild(addBtn);

        widget.appendChild(addGroup);

        const sync = () => {
            const vals = [...chipsWrap.querySelectorAll('.options-chip')].map(c => c.dataset.value);
            input.value = vals.join(';');
            input.dispatchEvent(new Event('change', { bubbles: true }));
        };

        const addChip = (value) => {
            value = value.trim();
            if (value === '') return;
            if ([...chipsWrap.querySelectorAll('.options-chip')].some(c => c.dataset.value === value)) return;
            const chip = document.createElement('span');
            chip.className = 'options-chip badge badge-outline badge-primary inline-flex items-center gap-1';
            chip.dataset.value = value;
            chip.textContent = value;
            const x = document.createElement('button');
            x.type = 'button';
            x.className = 'options-chip-remove';
            x.setAttribute('aria-label', 'Odstranit');
            x.innerHTML = '<i class="ki-filled ki-cross fs-7"></i>';
            x.addEventListener('click', () => { chip.remove(); sync(); });
            chip.appendChild(x);
            chipsWrap.appendChild(chip);
            sync();
        };

        (input.value || '').split(';').forEach(v => addChip(v));

        addInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ',' || e.key === ';') {
                e.preventDefault();
                addChip(addInput.value);
                addInput.value = '';
            }
        });
        addInput.addEventListener('blur', () => {
            if (addInput.value.trim() !== '') {
                addChip(addInput.value);
                addInput.value = '';
            }
        });
        addBtn.addEventListener('click', () => {
            addChip(addInput.value);
            addInput.value = '';
            addInput.focus();
        });

        input.type = 'hidden';
        input.insertAdjacentElement('afterend', widget);
    });
}

function initOptionsToggle(root){
    root.querySelectorAll('[data-options-widget]').forEach((optionsInput) => {
        if (optionsToggleBound.has(optionsInput)) return;
        const fieldset = optionsInput.closest('fieldset');
        if (!fieldset) return;
        const typeSelect = fieldset.querySelector('select[data-original-name="type"]');
        if (!typeSelect) return;
        const allowed = (optionsInput.dataset.optionsFor || '').split(',').map((s) => s.trim());
        const wrapper = optionsInput.closest('.form-group, .input-group, label, div') || optionsInput;
        const toggle = () => {
            wrapper.style.display = allowed.includes(typeSelect.value) ? '' : 'none';
        };
        typeSelect.addEventListener('change', toggle);
        optionsToggleBound.add(optionsInput);
        toggle();
    });
}

document.addEventListener('repeater:added', (e) => {
    const root = e.detail?.fieldset || e.target;
    // new fieldset cloned from template — drop any inherited widget DOM and re-init
    root.querySelectorAll('.options-widget').forEach(w => w.remove());
    root.querySelectorAll('input[data-options-widget][type="hidden"]').forEach(i => {
        i.type = 'text';
        i.value = '';
    });
    initOptionsWidget(root);
    initOptionsToggle(root);
});

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