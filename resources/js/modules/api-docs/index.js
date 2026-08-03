import { toastError, toastSuccess } from '../../utils/toast';

const NAV_ACTIVE = ['bg-primary-soft', 'text-primary'];
const NAV_IDLE = ['text-heading', 'hover:bg-subtle'];

export function initApiDocsModule() {
    const root = document.querySelector('[data-module="api-docs"]');
    if (!root) {
        return;
    }

    const search = root.querySelector('[data-api-docs-search]');
    const empty = root.querySelector('[data-api-docs-empty]');
    const groups = [...root.querySelectorAll('[data-api-docs-group]')];
    const langTabs = [...root.querySelectorAll('[data-api-docs-lang]')];
    const navLinks = [...root.querySelectorAll('[data-api-docs-nav-link]')];
    const examplesSection = root.querySelector('[data-api-docs-examples]');
    const storageKey = 'pcspc-api-docs-lang';
    let scrollSpyPausedUntil = 0;

    function setNavActive(key) {
        navLinks.forEach((link) => {
            const active = link.getAttribute('data-api-docs-nav-link') === key;
            link.setAttribute('aria-current', active ? 'true' : 'false');
            NAV_ACTIVE.forEach((cls) => link.classList.toggle(cls, active));
            NAV_IDLE.forEach((cls) => link.classList.toggle(cls, !active));
            const icon = link.querySelector('i.ph');
            if (icon) {
                icon.classList.toggle('text-muted', !active);
            }
        });
    }

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

            const key = group.getAttribute('data-group-key');
            const navLink = navLinks.find((link) => link.getAttribute('data-api-docs-nav-link') === key);
            if (navLink) {
                navLink.classList.toggle('hidden', groupVisible === 0);
                const countEl = navLink.querySelector('[data-api-docs-nav-count]');
                if (countEl && q) {
                    countEl.textContent = String(groupVisible);
                } else if (countEl && !q) {
                    countEl.textContent = String(endpoints.length);
                }
            }
        });

        empty?.classList.toggle('hidden', visibleEndpoints > 0 || !q);
        if (!q && empty) {
            empty.classList.add('hidden');
        }
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

    function scrollToTarget(hash) {
        const id = (hash || '').replace(/^#/, '');
        if (!id) {
            return;
        }
        const target = root.querySelector(`#${CSS.escape(id)}`);
        if (!target) {
            return;
        }
        scrollSpyPausedUntil = Date.now() + 800;
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        const key = id === 'api-docs-examples'
            ? 'examples'
            : (target.getAttribute('data-group-key') || id.replace(/^api-group-/, ''));
        setNavActive(key);
    }

    search?.addEventListener('input', applyFilter);

    langTabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            setLanguage(tab.getAttribute('data-api-docs-lang') || 'curl');
        });
    });

    navLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            const href = link.getAttribute('href') || '';
            if (!href.startsWith('#')) {
                return;
            }
            event.preventDefault();
            history.replaceState(null, '', href);
            scrollToTarget(href);
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

    const spyTargets = [
        ...(examplesSection ? [examplesSection] : []),
        ...groups,
    ];

    if (spyTargets.length && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            if (Date.now() < scrollSpyPausedUntil) {
                return;
            }
            const visible = entries
                .filter((entry) => entry.isIntersecting)
                .sort((a, b) => b.intersectionRatio - a.intersectionRatio);
            const top = visible[0]?.target;
            if (!top) {
                return;
            }
            if (top === examplesSection) {
                setNavActive('examples');
                return;
            }
            const key = top.getAttribute('data-group-key');
            if (key) {
                setNavActive(key);
            }
        }, {
            rootMargin: '-20% 0px -55% 0px',
            threshold: [0.05, 0.2, 0.4],
        });

        spyTargets.forEach((el) => observer.observe(el));
    }

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
    applyFilter();

    if (window.location.hash) {
        window.requestAnimationFrame(() => scrollToTarget(window.location.hash));
    } else {
        setNavActive('examples');
    }
}
