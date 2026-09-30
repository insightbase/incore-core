// Lišta s průběhem překladů odeslaných do DropCore (kontejner #translation_jobs v @layout.latte).
// Stav úloh vrací TranslationJobPresenter::actionStatus(); běžící úlohy se dotazují dokola,
// dokud DropCore callbacky neoznačí všechny dávky jako hotové.

const POLL_INTERVAL = 3000;

const container = document.getElementById('translation_jobs');

if (container !== null) {
    let pollTimer = null;

    const fetchJson = (url) => fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then((response) => response.ok ? response.json() : null);

    const createIcon = (done) => {
        const icon = document.createElement('i');
        icon.className = done ? 'ki-filled ki-check-circle text-success text-lg' : 'ki-filled ki-arrows-circle text-primary text-lg animate-spin';
        return icon;
    };

    const dismiss = (job, card) => {
        card.remove();
        fetchJson(container.dataset.dismissUrl.replace('__id__', encodeURIComponent(job.id))).catch(() => {});
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
        header.appendChild(createIcon(job.done));

        const label = document.createElement('span');
        label.className = 'text-sm font-medium text-gray-900 grow';
        label.textContent = job.label;
        header.appendChild(label);

        const counter = document.createElement('span');
        counter.className = 'text-2sm text-gray-600';
        counter.textContent = job.done
            ? container.dataset.textDone + (job.itemCount > 0 ? ' (' + job.itemCount + ')' : '')
            : job.finished + '/' + job.total;
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

        if (job.done) {
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
            bar.style.width = (job.total > 0 ? Math.round(job.finished / job.total * 100) : 0) + '%';
            progress.appendChild(bar);
            body.appendChild(progress);
        }

        card.appendChild(body);
        return card;
    };

    const refresh = () => {
        fetchJson(container.dataset.statusUrl)
            .then((data) => {
                const jobs = data?.jobs ?? [];
                container.replaceChildren(...jobs.map(renderJob));

                clearTimeout(pollTimer);
                if (jobs.some((job) => !job.done)) {
                    pollTimer = setTimeout(refresh, POLL_INTERVAL);
                }
            })
            .catch(() => {});
    };

    refresh();
}
