Array.from(document.getElementsByClassName('performanceLanguageSelect')).forEach((element) => {
    element.addEventListener('change', function(event){
        location.href = element.getAttribute('data-url').replace('0', element.value);
    });
});