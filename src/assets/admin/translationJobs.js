// Lišta s průběhem překladů (kontejner #translation_jobs v @layout.latte).
// Dávky úlohy leží v language_translate; lišta je po jedné odesílá do DropCore
// (TranslationJob:send) a potom se ptá na stav (TranslationJob:status), dokud DropCore
// callbacky neoznačí všechny dávky jako hotové. Chyba odeslání úlohu zastaví
// a nabídne „Zkusit znovu“ nebo „Zrušit“.

const POLL_INTERVAL = 3000;

const container = document.getElementById('translation_jobs');

if (container !== null) {
    let pollTimer = null;
    let sending = false;

    const fetchJson = (url) => fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then((response) => response.ok ? response.json() : null);

    const jobUrl = (template, job) => template.replace('__id__', encodeURIComponent(job.id));

    const isSending = (job) => job.error === null && job.sent < job.total;

    const createIcon = (job) => {
        const icon = document.createElement('i');
        if (job.error !== null) {
            icon.className = 'ki-filled ki-information-2 text-danger text-lg';
        } else if (job.done) {
            icon.className = 'ki-filled ki-check-circle text-success text-lg';
        } else {
            icon.className = 'ki-filled ki-arrows-circle text-primary text-lg animate-spin';
        }
        return icon;
    };

    const createButton = (text, className, onClick) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = className;
        button.textContent = text;
        button.addEventListener('click', onClick);
        return button;
    };

    const dismiss = (job, card) => {
        card.remove();
        fetchJson(jobUrl(container.dataset.dismissUrl, job)).catch(() => {});
    };

    const counterText = (job) => {
        if (job.done) {
            return container.dataset.textDone + (job.itemCount > 0 ? ' (' + job.itemCount + ')' : '');
        }
        if (job.error !== null) {
            return '';
        }
        return isSending(job)
            ? container.dataset.textSending + ' ' + job.sent + '/' + job.total
            : container.dataset.textTranslating + ' ' + job.finished + '/' + job.total;
    };

    const renderError = (job, body) => {
        const message = document.createElement('div');
        message.className = 'text-2sm text-danger';
        message.textContent = job.error === 'notEnoughCredits'
            ? container.dataset.textErrorCredits
            : container.dataset.textErrorApi;
        body.appendChild(message);

        const actions = document.createElement('div');
        actions.className = 'flex items-center gap-2';
        actions.appendChild(createButton(container.dataset.textRetry, 'btn btn-xs btn-primary', () => {
            fetchJson(jobUrl(container.dataset.retryUrl, job)).then((data) => handle(data)).catch(() => {});
        }));
        actions.appendChild(createButton(container.dataset.textCancel, 'btn btn-xs btn-light', () => {
            fetchJson(jobUrl(container.dataset.cancelUrl, job)).then((data) => handle(data)).catch(() => {});
        }));
        if (job.error === 'notEnoughCredits' && container.dataset.creditUrl) {
            const link = document.createElement('a');
            link.href = container.dataset.creditUrl;
            link.className = 'text-2sm link';
            link.textContent = container.dataset.textCredits;
            actions.appendChild(link);
        }
        body.appendChild(actions);
    };

    const renderJob = (job) => {
        const card = document.createElement('div');
        card.className = 'card shadow-default';
        card.dataset.jobId = job.id;

        const body = document.createElement('div');
        body.className = 'card-body flex flex-col gap-2';
        body.style.padding = '0.75rem 1rem';

        const header = document.createElement('div');
        header.className = 'flex items-center gap-2';
        header.appendChild(createIcon(job));

        const label = document.createElement('span');
        label.className = 'text-sm font-medium text-gray-900 grow';
        label.textContent = job.label;
        header.appendChild(label);

        const counter = document.createElement('span');
        counter.className = 'text-2sm text-gray-600';
        counter.textContent = counterText(job);
        header.appendChild(counter);

        if (job.done) {
            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'btn btn-xs btn-icon btn-light';
            close.title = container.dataset.textClose;
            close.innerHTML = '<i class="ki-filled ki-cross"></i>';
            close.addEventListener('click', () => dismiss(job, card));
            header.appendChild(close);
        }
        body.appendChild(header);

        if (job.error !== null) {
            renderError(job, body);
        } else if (job.done) {
            const reload = document.createElement('a');
            reload.href = '#';
            reload.className = 'text-2sm link';
            reload.textContent = container.dataset.textReload;
            reload.addEventListener('click', (event) => {
                event.preventDefault();
                dismiss(job, card);
                window.location.reload();
            });
            body.appendChild(reload);
        } else {
            const progress = document.createElement('div');
            progress.className = 'progress progress-primary';
            const bar = document.createElement('div');
            bar.className = 'progress-bar';
            const current = isSending(job) ? job.sent : job.finished;
            bar.style.width = (job.total > 0 ? Math.round(current / job.total * 100) : 0) + '%';
            progress.appendChild(bar);
            body.appendChild(progress);
        }

        card.appendChild(body);
        return card;
    };

    // Vykreslí úlohy a rozhodne, co dál: hned odeslat další dávku, nebo za chvíli zjistit stav.
    // sendImmediately = false, když poslední send nic neodeslal (dávku drží jiná záložka) -
    // pak se čeká, aby se lišta s druhou záložkou nepřetahovala naprázdno.
    const handle = (data, sendImmediately = true) => {
        clearTimeout(pollTimer);
        if (data === null) {
            pollTimer = setTimeout(refresh, POLL_INTERVAL);
            return;
        }

        const jobs = data.jobs ?? [];
        container.replaceChildren(...jobs.map(renderJob));

        const toSend = jobs.find(isSending);
        if (toSend !== undefined && sendImmediately) {
            send(toSend);
            return;
        }
        if (jobs.some((job) => !job.done && job.error === null)) {
            pollTimer = setTimeout(refresh, POLL_INTERVAL);
        }
    };

    const send = (job) => {
        if (sending) {
            return;
        }
        sending = true;
        fetchJson(jobUrl(container.dataset.sendUrl, job))
            .then((data) => {
                sending = false;
                handle(data, data?.sent === true);
            })
            .catch(() => {
                sending = false;
                clearTimeout(pollTimer);
                pollTimer = setTimeout(refresh, POLL_INTERVAL);
            });
    };

    const refresh = () => {
        fetchJson(container.dataset.statusUrl)
            .then((data) => handle(data))
            .catch(() => {});
    };

    refresh();
}
