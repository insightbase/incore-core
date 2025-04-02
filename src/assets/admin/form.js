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