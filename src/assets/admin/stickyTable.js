function updateStickyShadows() {
    const scrollContainer = document.querySelector('.dataGrid .scrollable-x-auto');
    if (!scrollContainer) return;

    const table = scrollContainer.querySelector('table');
    if (!table) return;

    const stickyCells = scrollContainer.querySelectorAll('.dataGrid thead th.sticky, .dataGrid tbody td.sticky');
    const hasOverflow = table.scrollWidth > scrollContainer.clientWidth;

    stickyCells.forEach(cell => {
        if (hasOverflow) {
            cell.classList.add('has-shadow');
        } else {
            cell.classList.remove('has-shadow');
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', updateStickyShadows);
} else {
    updateStickyShadows();
}

let resizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(updateStickyShadows, 100);
});
