/**
 * Global search mega menu (topbar command palette)
 * Axios: GET /api/v1/search?q=
 */
import http from '../../utils/http';
import { escapeHtml } from '../../utils/server-table';
import { avatarMarkup } from '../../utils/avatar';
import { toastError } from '../../utils/toast';

let open = false;
let loading = false;
let activeIndex = -1;
let flatItems = [];
let debounceTimer = null;
let lastQuery = null;

function isEditableTarget(target) {
    if (!target || !(target instanceof Element)) {
        return false;
    }
    const tag = target.tagName;
    return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || target.isContentEditable;
}

function setOpen(next) {
    const root = document.getElementById('search-mega');
    const input = document.getElementById('search-mega-input');
    if (!root) {
        return;
    }

    open = next;
    if (open) {
        root.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        if (input) {
            input.value = '';
            requestAnimationFrame(() => input.focus());
        }
        lastQuery = null;
        load('');
    } else {
        root.classList.add('hidden');
        document.body.style.overflow = '';
        activeIndex = -1;
        flatItems = [];
    }
}

function setActive(index) {
    const body = document.querySelector('[data-search-mega-body]');
    if (!body) {
        return;
    }
    const buttons = [...body.querySelectorAll('[data-search-item]')];
    if (!buttons.length) {
        activeIndex = -1;
        return;
    }
    activeIndex = ((index % buttons.length) + buttons.length) % buttons.length;
    buttons.forEach((btn, i) => {
        const on = i === activeIndex;
        btn.classList.toggle('ring-2', on);
        btn.classList.toggle('ring-primary/40', on);
        btn.classList.toggle('bg-primary/10', on);
        if (on) {
            btn.scrollIntoView({ block: 'nearest' });
        }
    });
}

function activateItem(item) {
    if (!item) {
        return;
    }
    setOpen(false);

    if (item.action === 'edit-profile') {
        document.getElementById('edit-profile-btn')?.click();
        return;
    }
    if (item.action === 'change-password') {
        document.getElementById('change-password-btn')?.click();
        return;
    }
    if (item.url) {
        window.location.href = item.url;
    }
}

function resultButton(item, metaHtml = '') {
    const isPerson = item.kind === 'person';
    const icon = item.icon || (isPerson ? 'ph-user' : 'ph-arrow-right');
    const title = isPerson
        ? (item.full_name || item.label || 'Untitled')
        : (item.label || item.full_name || 'Untitled');
    const subtitle = isPerson
        ? [item.employee_number, item.email].filter(Boolean).join(' · ')
        : (item.summary || item.description || item.section || '');

    const leading = isPerson
        ? avatarMarkup({
            url: item.photo_url,
            name: item.full_name || item.label,
            email: item.email,
            sizeClass: 'w-9 h-9',
            textClass: 'text-xs',
        })
        : `
            <span class="w-9 h-9 rounded-lg bg-primary-soft text-primary flex items-center justify-center flex-shrink-0 mt-0.5">
                <i class="ph ${escapeHtml(icon)} text-lg"></i>
            </span>
        `;

    return `
        <button
            type="button"
            data-search-item
            class="w-full flex items-start gap-3 p-2.5 min-h-[44px] rounded-xl text-left hover:bg-subtle transition-colors"
        >
            <span class="flex-shrink-0 mt-0.5">${leading}</span>
            <span class="min-w-0 flex-1">
                <span class="block text-sm font-medium text-heading truncate">${escapeHtml(title)}</span>
                ${subtitle ? `<span class="block text-xs text-muted mt-0.5 line-clamp-2">${escapeHtml(subtitle)}</span>` : ''}
                ${metaHtml}
            </span>
            <i class="ph ph-arrow-up-right text-faint text-sm mt-1 flex-shrink-0" aria-hidden="true"></i>
        </button>
    `;
}

function column(title, icon, items, emptyLabel) {
    const list = items.length
        ? items.map((item) => resultButton(item)).join('')
        : `<p class="text-xs text-muted px-2 py-4">${escapeHtml(emptyLabel)}</p>`;

    return `
        <section class="min-w-0">
            <h3 class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-faint mb-2 px-1">
                <i class="ph ${escapeHtml(icon)} text-sm"></i>
                ${escapeHtml(title)}
                <span class="font-medium normal-case tracking-normal text-muted">(${items.length})</span>
            </h3>
            <div class="space-y-1">${list}</div>
        </section>
    `;
}

function renderBrowse(payload) {
    const sections = payload.sections || [];
    if (!sections.length) {
        return `<p class="text-sm text-muted text-center py-10">No modules available for your role.</p>`;
    }

    const cards = sections.map((section) => {
        const links = (section.items || []).map((item) => `
            <button
                type="button"
                data-search-item
                class="flex items-center gap-2.5 p-2.5 min-h-[44px] rounded-xl border border-border hover:border-primary/40 hover:bg-primary-soft transition-colors text-left"
            >
                <span class="w-8 h-8 rounded-lg bg-subtle text-heading flex items-center justify-center flex-shrink-0">
                    <i class="ph ${escapeHtml(item.icon || 'ph-squares-four')} text-base"></i>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-medium text-heading truncate">${escapeHtml(item.label)}</span>
                    ${item.phase ? `<span class="block text-[11px] text-faint">${escapeHtml(item.phase)}</span>` : ''}
                </span>
            </button>
        `).join('');

        return `
            <section class="min-w-0">
                <h3 class="text-xs font-bold uppercase tracking-wide text-faint mb-2 px-1">${escapeHtml(section.label)}</h3>
                <div class="grid grid-cols-1 gap-1.5">${links}</div>
            </section>
        `;
    }).join('');

    const shortcuts = column('Shortcuts', 'ph-lightning', payload.shortcuts || [], 'No shortcuts');
    const people = (payload.people || []).length
        ? column('People', 'ph-users', payload.people, 'No people')
        : '';

    return `
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-5">
            ${cards}
            ${people}
            ${shortcuts}
        </div>
    `;
}

function renderFiltered(payload) {
    return `
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-5">
            ${column('Modules', 'ph-squares-four', payload.modules || [], 'No matching modules')}
            ${column('People', 'ph-users', payload.people || [], 'No matching people')}
            ${column('Shortcuts', 'ph-lightning', payload.shortcuts || [], 'No matching shortcuts')}
        </div>
    `;
}

function bindItemClicks(payload) {
    flatItems = [];
    const body = document.querySelector('[data-search-mega-body]');
    if (!body) {
        return;
    }

    const ordered = [];
    const query = (payload.query || '').trim();
    if (query === '') {
        (payload.sections || []).forEach((section) => {
            (section.items || []).forEach((item) => ordered.push(item));
        });
        (payload.people || []).forEach((item) => ordered.push(item));
        (payload.shortcuts || []).forEach((item) => ordered.push(item));
    } else {
        (payload.modules || []).forEach((item) => ordered.push(item));
        (payload.people || []).forEach((item) => ordered.push(item));
        (payload.shortcuts || []).forEach((item) => ordered.push(item));
    }

    flatItems = ordered;
    const buttons = [...body.querySelectorAll('[data-search-item]')];
    buttons.forEach((btn, index) => {
        btn.addEventListener('click', () => activateItem(flatItems[index]));
        btn.addEventListener('mouseenter', () => setActive(index));
    });
    activeIndex = buttons.length ? 0 : -1;
    if (activeIndex >= 0) {
        setActive(activeIndex);
    }
}

async function load(query) {
    if (loading && lastQuery === query) {
        return;
    }
    loading = true;
    lastQuery = query;
    const body = document.querySelector('[data-search-mega-body]');
    if (body && query === '') {
        // keep previous content while refreshing empty browse if present
    }

    try {
        const { data } = await http.get('/search', { params: { q: query, limit: 8 } });
        const payload = data?.data || {};
        if (body) {
            body.innerHTML = (payload.query || '').trim() === ''
                ? renderBrowse(payload)
                : renderFiltered(payload);
        }
        bindItemClicks(payload);
    } catch (error) {
        if (body) {
            body.innerHTML = `<p class="text-sm text-muted text-center py-10">${escapeHtml(error.response?.data?.message || 'Unable to search')}</p>`;
        }
        if (open) {
            toastError(error.response?.data?.message || 'Unable to search');
        }
    } finally {
        loading = false;
    }
}

export function initSearchMega() {
    const root = document.getElementById('search-mega');
    if (!root) {
        return;
    }

    const input = document.getElementById('search-mega-input');
    const triggers = document.querySelectorAll('[data-search-mega-open]');

    triggers.forEach((el) => {
        el.addEventListener('click', (event) => {
            event.preventDefault();
            setOpen(true);
        });
        el.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                setOpen(true);
            }
        });
    });

    root.querySelectorAll('[data-search-mega-dismiss]').forEach((el) => {
        el.addEventListener('click', () => setOpen(false));
    });

    input?.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => load(input.value.trim()), 200);
    });

    input?.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActive(activeIndex + 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActive(activeIndex - 1);
        } else if (event.key === 'Enter') {
            event.preventDefault();
            if (activeIndex >= 0) {
                activateItem(flatItems[activeIndex]);
            }
        } else if (event.key === 'Escape') {
            event.preventDefault();
            setOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        const meta = event.metaKey || event.ctrlKey;
        if (meta && (event.key === 'k' || event.key === 'K')) {
            event.preventDefault();
            setOpen(!open);
            return;
        }
        if (event.key === 'Escape' && open) {
            event.preventDefault();
            setOpen(false);
            return;
        }
        if (!open && event.key === '/' && !meta && !event.altKey && !isEditableTarget(event.target)) {
            event.preventDefault();
            setOpen(true);
        }
    });
}
