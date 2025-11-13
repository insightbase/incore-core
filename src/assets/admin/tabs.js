document.addEventListener('DOMContentLoaded', () => {
    initTabs();
});

function initTabs() {
    // každá sada tabů má data-tabs="true"
    document.querySelectorAll('[data-tabs="true"]').forEach((tabsEl) => {
        const toggles = Array.from(
            tabsEl.querySelectorAll('[data-tab-toggle]')
        );

        if (!toggles.length) {
            return;
        }

        // namapujeme si panely podle selektoru z data-tab-toggle
        const panelsMap = {};
        toggles.forEach((toggle) => {
            const selector = toggle.getAttribute('data-tab-toggle');
            if (!selector) {
                return;
            }
            const panel = document.querySelector(selector);
            if (panel && panel.hasAttribute('data-form-tab')) {
                panelsMap[selector] = panel;
            }
        });

        const panels = Object.values(panelsMap);

        // handler pro klik na tab
        const onToggleClick = (event) => {
            event.preventDefault();
            const toggle = event.currentTarget;
            const selector = toggle.getAttribute('data-tab-toggle');
            const panel = panelsMap[selector];

            if (!selector || !panel) {
                return;
            }

            // deaktivovat všechny toggly
            toggles.forEach((t) => t.classList.remove('active'));

            // skrýt všechny panely této sady
            panels.forEach((p) => p.classList.add('hidden'));

            // aktivovat zvolený toggle + panel
            toggle.classList.add('active');
            panel.classList.remove('hidden');
        };

        // navěsit listener na všechny toggly
        toggles.forEach((toggle) => {
            toggle.addEventListener('click', onToggleClick);
        });

        // inicializační stav – aktivní tab + panel
        let activeToggle =
            toggles.find((t) => t.classList.contains('active')) || toggles[0];

        if (activeToggle) {
            const selector = activeToggle.getAttribute('data-tab-toggle');

            // nejdřív všechno schovat
            toggles.forEach((t) => t.classList.remove('active'));
            panels.forEach((p) => p.classList.add('hidden'));

            // pak nastavit aktivní
            activeToggle.classList.add('active');
            if (selector && panelsMap[selector]) {
                panelsMap[selector].classList.remove('hidden');
            }
        }
    });
}
