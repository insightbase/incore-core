const balanceElement = document.getElementById('credit_balance');
const balanceValueElement = document.getElementById('credit_balance_value');

if (balanceElement !== null && balanceValueElement !== null) {
    fetch(balanceElement.dataset.url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then((response) => response.ok ? response.json() : null)
        .then((data) => {
            if (data === null || data.value === null || data.value === undefined) {
                return;
            }
            balanceValueElement.textContent = Number(data.value).toLocaleString('cs-CZ');
            balanceElement.classList.remove('hidden');
        })
        .catch(() => {});
}
