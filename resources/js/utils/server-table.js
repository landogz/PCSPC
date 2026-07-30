/**
 * Lightweight server-driven table helper (Axios + pagination + context menu).
 * Avoids full DataTables SSR wiring while keeping the same UX contract.
 */
import http from './http';
import { toastError } from './toast';

export function createServerTable({
    root,
    endpoint,
    columns,
    mapRow,
    perPage = 10,
    extraParams = () => ({}),
    onRowAction = null,
}) {
    if (!root) {
        return null;
    }

    const body = root.querySelector('[data-table-body]');
    const metaLabel = root.querySelector('[data-meta-label]');
    const pageLabel = root.querySelector('[data-page-label]');
    const prevBtn = root.querySelector('[data-page="prev"]');
    const nextBtn = root.querySelector('[data-page="next"]');
    const menuId = `${root.dataset.spaModule}-context-menu`;
    const menu = document.getElementById(menuId);

    let page = 1;
    let lastPage = 1;
    let rows = [];
    let loading = false;

    function hideMenu() {
        if (!menu) {
            return;
        }
        menu.classList.add('hidden');
        menu.innerHTML = '';
    }

    function showMenu(x, y, row, actions) {
        if (!menu || !actions?.length) {
            return;
        }

        menu.innerHTML = actions
            .map(
                (action) => `
            <button
                type="button"
                class="w-full text-left px-3 py-2.5 text-sm hover:bg-subtle transition-colors ${action.danger ? 'text-danger' : 'text-heading'}"
                data-menu-action="${action.key}"
            >
                ${action.label}
            </button>`,
            )
            .join('');

        menu.classList.remove('hidden');
        const pad = 8;
        const rect = menu.getBoundingClientRect();
        const left = Math.min(x, window.innerWidth - rect.width - pad);
        const top = Math.min(y, window.innerHeight - rect.height - pad);
        menu.style.left = `${Math.max(pad, left)}px`;
        menu.style.top = `${Math.max(pad, top)}px`;

        menu.querySelectorAll('[data-menu-action]').forEach((btn) => {
            btn.addEventListener('click', () => {
                hideMenu();
                onRowAction?.(btn.dataset.menuAction, row);
            });
        });
    }

    async function load(targetPage = page) {
        if (loading) {
            return;
        }
        loading = true;
        page = targetPage;
        body.innerHTML = `<tr><td colspan="${columns}" class="px-4 py-10 text-center text-muted">Loading…</td></tr>`;

        try {
            const { data } = await http.get(endpoint, {
                params: {
                    page,
                    per_page: perPage,
                    ...extraParams(),
                },
            });

            rows = data?.data?.items || [];
            const meta = data?.data?.meta || {};
            lastPage = meta.last_page || 1;
            page = meta.current_page || page;

            if (!rows.length) {
                body.innerHTML = `<tr><td colspan="${columns}" class="px-4 py-10 text-center text-muted">No records found.</td></tr>`;
            } else {
                body.innerHTML = rows.map((row) => mapRow(row)).join('');
            }

            if (metaLabel) {
                metaLabel.textContent = `Showing ${rows.length} of ${meta.total ?? rows.length} records`;
            }
            if (pageLabel) {
                pageLabel.textContent = `Page ${page} of ${lastPage}`;
            }
            if (prevBtn) {
                prevBtn.disabled = page <= 1;
            }
            if (nextBtn) {
                nextBtn.disabled = page >= lastPage;
            }
        } catch (error) {
            body.innerHTML = `<tr><td colspan="${columns}" class="px-4 py-10 text-center text-danger">Unable to load records.</td></tr>`;
            toastError(error.response?.data?.message || 'Unable to load records');
        } finally {
            loading = false;
        }
    }

    prevBtn?.addEventListener('click', () => {
        if (page > 1) {
            load(page - 1);
        }
    });

    nextBtn?.addEventListener('click', () => {
        if (page < lastPage) {
            load(page + 1);
        }
    });

    body.addEventListener('contextmenu', (event) => {
        const tr = event.target.closest('tr[data-row-id]');
        if (!tr) {
            return;
        }
        event.preventDefault();
        const row = rows.find((item) => item.id === tr.dataset.rowId);
        if (!row) {
            return;
        }
        const actions = JSON.parse(tr.dataset.actions || '[]');
        showMenu(event.clientX, event.clientY, row, actions);
    });

    document.addEventListener('click', hideMenu);
    document.addEventListener('scroll', hideMenu, true);

    return {
        reload: (reset = false) => load(reset ? 1 : page),
        getRows: () => rows,
    };
}

export function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

export function statusBadge(active, locked = false) {
    const parts = [];

    if (locked) {
        parts.push(
            '<span class="inline-flex items-center gap-1 h-7 px-2.5 rounded-lg bg-warning-soft border border-warning/30 text-heading text-xs font-semibold"><i class="ph ph-lock-key text-sm"></i>Locked</span>',
        );
    }

    if (active) {
        parts.push(
            '<span class="inline-flex items-center h-7 px-2.5 rounded-lg bg-success-soft text-success text-xs font-semibold">Active</span>',
        );
    } else {
        parts.push(
            '<span class="inline-flex items-center h-7 px-2.5 rounded-lg bg-subtle text-muted text-xs font-semibold">Inactive</span>',
        );
    }

    return `<div class="flex flex-wrap items-center gap-1.5">${parts.join('')}</div>`;
}

export function lockedBadge(locked, lockedUntil = null) {
    if (!locked) {
        return '<span class="text-sm text-muted">No</span>';
    }

    const until = lockedUntil
        ? `<div class="text-[11px] text-muted mt-0.5">Until ${escapeHtml(new Date(lockedUntil).toLocaleString())}</div>`
        : '';

    return `
        <div>
            <span class="inline-flex items-center gap-1 h-7 px-2.5 rounded-lg bg-warning-soft border border-warning/30 text-heading text-xs font-semibold">
                <i class="ph ph-lock-key text-sm"></i>Yes
            </span>
            ${until}
        </div>
    `;
}
