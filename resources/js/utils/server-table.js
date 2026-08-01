/**
 * Lightweight server-driven table helper (Axios + pagination + context menu).
 * Avoids full DataTables SSR wiring while keeping the same UX contract.
 *
 * Row actions: always render an Actions column via `rowActionsCell(actions)` when
 * the row has actions. Keep `data-actions` + right-click context menu in parallel.
 *
 * Optional list/grid: pass `mapCard` and enable `view-toggle` on `<x-ui.data-panel>`.
 */
import http from './http';
import { toastError } from './toast';

const ACTION_ICONS = {
    view: 'ph-eye',
    preview: 'ph-eye',
    edit: 'ph-pencil-simple',
    delete: 'ph-trash',
    deactivate: 'ph-user-minus',
    unlock: 'ph-lock-key-open',
    create: 'ph-plus',
    download: 'ph-download-simple',
};

/**
 * @param {Array<{ key: string, label: string, danger?: boolean, icon?: string }>} actions
 */
export function rowActionsCell(actions = []) {
    if (!actions.length) {
        return `<td class="px-4 py-3 text-right whitespace-nowrap"><span class="text-muted text-xs">—</span></td>`;
    }

    const buttons = actions
        .map((action) => {
            const icon = action.icon || ACTION_ICONS[action.key] || 'ph-dots-three';
            const tone = action.danger
                ? 'text-danger border-danger/30 hover:bg-danger/10'
                : 'text-heading border-border hover:bg-subtle';

            return `
                <button
                    type="button"
                    data-row-action="${escapeHtml(action.key)}"
                    title="${escapeHtml(action.label)}"
                    aria-label="${escapeHtml(action.label)}"
                    class="inline-flex items-center justify-center gap-1 h-9 min-w-9 px-2.5 rounded-lg border text-xs font-medium transition-colors ${tone}"
                >
                    <i class="ph ${icon} text-base" aria-hidden="true"></i>
                    <span class="hidden xl:inline">${escapeHtml(action.label)}</span>
                </button>
            `;
        })
        .join('');

    return `
        <td class="px-4 py-3 text-right whitespace-nowrap">
            <div class="inline-flex items-center justify-end gap-1.5 flex-wrap">${buttons}</div>
        </td>
    `;
}

/**
 * Compact action buttons for grid cards (same handlers as list Actions column).
 *
 * @param {Array<{ key: string, label: string, danger?: boolean, icon?: string }>} actions
 */
export function cardActions(actions = []) {
    if (!actions.length) {
        return '';
    }

    return `
        <div class="flex flex-wrap items-center gap-1.5 pt-3 border-t border-border">
            ${actions.map((action) => {
                const icon = action.icon || ACTION_ICONS[action.key] || 'ph-dots-three';
                const tone = action.danger
                    ? 'text-danger border-danger/30 hover:bg-danger/10'
                    : 'text-heading border-border hover:bg-subtle';

                return `
                    <button
                        type="button"
                        data-row-action="${escapeHtml(action.key)}"
                        title="${escapeHtml(action.label)}"
                        aria-label="${escapeHtml(action.label)}"
                        class="inline-flex items-center justify-center gap-1 h-9 min-h-[44px] sm:min-h-9 min-w-9 px-2.5 rounded-lg border text-xs font-medium transition-colors ${tone}"
                    >
                        <i class="ph ${icon} text-base" aria-hidden="true"></i>
                        <span>${escapeHtml(action.label)}</span>
                    </button>
                `;
            }).join('')}
        </div>
    `;
}

export function createServerTable({
    root,
    endpoint,
    columns,
    mapRow,
    mapCard = null,
    perPage = 10,
    extraParams = () => ({}),
    onRowAction = null,
    onLoaded = null,
    storageKey = null,
    defaultView = 'list',
}) {
    if (!root) {
        return null;
    }

    const body = root.querySelector('[data-table-body]');
    const tableView = root.querySelector('[data-table-view]');
    const gridView = root.querySelector('[data-grid-view]');
    const viewToggle = root.querySelector('[data-view-toggle]');
    const metaLabel = root.querySelector('[data-meta-label]');
    const pageLabel = root.querySelector('[data-page-label]');
    const prevBtn = root.querySelector('[data-page="prev"]');
    const nextBtn = root.querySelector('[data-page="next"]');
    const menuId = `${root.dataset.spaModule}-context-menu`;
    const menu = document.getElementById(menuId);
    const persistKey = storageKey || (root.dataset.spaModule ? `spa-view:${root.dataset.spaModule}` : null);

    let page = 1;
    let lastPage = 1;
    let rows = [];
    let loading = false;
    let viewMode = defaultView === 'grid' ? 'grid' : 'list';

    if (persistKey) {
        try {
            const saved = localStorage.getItem(persistKey);
            if (saved === 'list' || saved === 'grid') {
                viewMode = saved;
            }
        } catch {
            // ignore storage errors
        }
    }

    if (!mapCard || !gridView) {
        viewMode = 'list';
        viewToggle?.classList.add('hidden');
    }

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

    function resolveRow(el) {
        const host = el?.closest?.('[data-row-id]');
        return rows.find((item) => item.id === host?.dataset?.rowId) || null;
    }

    function syncViewToggle() {
        if (!viewToggle) {
            return;
        }
        viewToggle.querySelectorAll('[data-view-mode]').forEach((btn) => {
            const active = btn.dataset.viewMode === viewMode;
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
            btn.classList.toggle('bg-surface', active);
            btn.classList.toggle('text-heading', active);
            btn.classList.toggle('shadow-sm', active);
            btn.classList.toggle('text-muted', !active);
        });
    }

    function applyViewMode() {
        const useGrid = viewMode === 'grid' && typeof mapCard === 'function' && gridView;
        tableView?.classList.toggle('hidden', useGrid);
        gridView?.classList.toggle('hidden', !useGrid);
        syncViewToggle();
        if (persistKey && mapCard) {
            try {
                localStorage.setItem(persistKey, viewMode);
            } catch {
                // ignore
            }
        }
    }

    function renderRows() {
        const useGrid = viewMode === 'grid' && typeof mapCard === 'function' && gridView;

        if (!rows.length) {
            if (body) {
                body.innerHTML = `<tr><td colspan="${columns}" class="px-4 py-10 text-center text-muted">No records found.</td></tr>`;
            }
            if (gridView) {
                gridView.innerHTML = `
                    <div class="sm:col-span-2 xl:col-span-3 2xl:col-span-4 rounded-xl border border-dashed border-border bg-subtle/50 px-4 py-10 text-center text-sm text-muted">
                        No records found.
                    </div>
                `;
            }
            return;
        }

        if (body) {
            body.innerHTML = rows.map((row) => mapRow(row)).join('');
        }
        if (gridView && typeof mapCard === 'function') {
            gridView.innerHTML = rows.map((row) => mapCard(row)).join('');
        }

        // Keep both surfaces populated so toggle is instant; visibility via applyViewMode.
        void useGrid;
    }

    function setLoadingState() {
        if (body) {
            body.innerHTML = `<tr><td colspan="${columns}" class="px-4 py-10 text-center text-muted">Loading…</td></tr>`;
        }
        if (gridView) {
            gridView.innerHTML = `
                <div class="sm:col-span-2 xl:col-span-3 2xl:col-span-4 rounded-xl border border-dashed border-border bg-subtle/50 px-4 py-10 text-center text-sm text-muted">
                    Loading…
                </div>
            `;
        }
    }

    async function load(targetPage = page) {
        if (loading) {
            return;
        }
        loading = true;
        page = targetPage;
        setLoadingState();

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

            renderRows();
            applyViewMode();
            onLoaded?.(data?.data || {});

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
            if (body) {
                body.innerHTML = `<tr><td colspan="${columns}" class="px-4 py-10 text-center text-danger">Unable to load records.</td></tr>`;
            }
            if (gridView) {
                gridView.innerHTML = `
                    <div class="sm:col-span-2 xl:col-span-3 2xl:col-span-4 rounded-xl border border-dashed border-danger/30 bg-danger-soft/40 px-4 py-10 text-center text-sm text-danger">
                        Unable to load records.
                    </div>
                `;
            }
            toastError(error.response?.data?.message || 'Unable to load records');
        } finally {
            loading = false;
        }
    }

    function setView(mode) {
        if (!mapCard || (mode !== 'list' && mode !== 'grid')) {
            return;
        }
        viewMode = mode;
        applyViewMode();
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

    viewToggle?.querySelectorAll('[data-view-mode]').forEach((btn) => {
        btn.addEventListener('click', () => {
            setView(btn.dataset.viewMode);
        });
    });

    function bindActionClicks(container) {
        container?.addEventListener('click', (event) => {
            const btn = event.target.closest('[data-row-action]');
            if (!btn || !container.contains(btn)) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            hideMenu();
            const row = resolveRow(btn);
            if (!row) {
                return;
            }
            onRowAction?.(btn.dataset.rowAction, row);
        });
    }

    function bindContextMenu(container) {
        container?.addEventListener('contextmenu', (event) => {
            if (event.target.closest('[data-row-action]')) {
                return;
            }
            const host = event.target.closest('[data-row-id]');
            if (!host || !container.contains(host)) {
                return;
            }
            event.preventDefault();
            const row = resolveRow(host);
            if (!row) {
                return;
            }
            const actions = JSON.parse(host.dataset.actions || '[]');
            showMenu(event.clientX, event.clientY, row, actions);
        });
    }

    bindActionClicks(body);
    bindActionClicks(gridView);
    bindContextMenu(body);
    bindContextMenu(gridView);

    document.addEventListener('click', hideMenu);
    document.addEventListener('scroll', hideMenu, true);

    applyViewMode();

    return {
        reload: (reset = false) => load(reset ? 1 : page),
        getRows: () => rows,
        getViewMode: () => viewMode,
        setView,
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
