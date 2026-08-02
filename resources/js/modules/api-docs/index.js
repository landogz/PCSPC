import { toastError, toastSuccess } from '../../utils/toast';

export function initApiDocsModule() {
    const root = document.querySelector('[data-module="api-docs"]');
    if (!root) {
        return;
    }

    const search = root.querySelector('[data-api-docs-search]');
    const empty = root.querySelector('[data-api-docs-empty]');
    const groups = [...root.querySelectorAll('[data-api-docs-group]')];
    const langTabs = [...root.querySelectorAll('[data-api-docs-lang]')];
    const storageKey = 'pcspc-api-docs-lang';

    function applyFilter() {
        const q = (search?.value || '').trim().toLowerCase();
        let visibleEndpoints = 0;

        groups.forEach((group) => {
            const endpoints = [...group.querySelectorAll('[data-api-docs-endpoint]')];
            let groupVisible = 0;
            endpoints.forEach((endpoint) => {
                const hay = endpoint.getAttribute('data-search') || '';
                const show = !q || hay.includes(q);
                endpoint.classList.toggle('hidden', !show);
                if (show) {
                    groupVisible += 1;
                    visibleEndpoints += 1;
                }
            });
            group.classList.toggle('hidden', groupVisible === 0);
        });

        empty?.classList.toggle('hidden', visibleEndpoints > 0);
    }

    function setLanguage(lang) {
        try {
            localStorage.setItem(storageKey, lang);
        } catch {
            // ignore
        }

        langTabs.forEach((tab) => {
            const active = tab.getAttribute('data-api-docs-lang') === lang;
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
            tab.classList.toggle('border-primary', active);
            tab.classList.toggle('bg-primary-soft', active);
            tab.classList.toggle('text-primary', active);
            tab.classList.toggle('border-border', !active);
            tab.classList.toggle('bg-surface', !active);
            tab.classList.toggle('text-heading', !active);
            tab.classList.toggle('hover:bg-subtle', !active);
        });

        root.querySelectorAll('[data-api-docs-snippet]').forEach((snippet) => {
            snippet.classList.toggle('hidden', snippet.getAttribute('data-lang') !== lang);
        });
    }

    search?.addEventListener('input', applyFilter);

    langTabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            setLanguage(tab.getAttribute('data-api-docs-lang') || 'curl');
        });
    });

    root.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-api-docs-copy]');
        if (!button || !root.contains(button)) {
            return;
        }

        const panel = button.closest('[data-api-docs-snippet]');
        const code = panel?.querySelector('[data-api-docs-code]')?.textContent || '';
        const label = button.querySelector('[data-api-docs-copy-label]');

        try {
            await navigator.clipboard.writeText(code);
            if (label) {
                label.textContent = 'Copied';
                window.setTimeout(() => {
                    label.textContent = 'Copy';
                }, 1500);
            }
            toastSuccess('Example copied to clipboard.');
        } catch {
            toastError('Could not copy example.');
        }
    });

    let initialLang = 'curl';
    try {
        const saved = localStorage.getItem(storageKey);
        if (saved && langTabs.some((tab) => tab.getAttribute('data-api-docs-lang') === saved)) {
            initialLang = saved;
        }
    } catch {
        // ignore
    }
    setLanguage(initialLang);
}
