const balanceElement = document.getElementById('credit_balance');

if (balanceElement !== null) {
    fetch(balanceElement.dataset.url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then((response) => response.ok ? response.json() : null)
        .then((data) => {
            if (data === null || data.value === null || data.value === undefined) {
                return;
            }
            balanceElement.textContent = Number(data.value).toLocaleString('cs-CZ');
            balanceElement.classList.remove('hidden');
        })
        .catch(() => {});
}
