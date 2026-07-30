import http from '../../utils/http';
import { toastError } from '../../utils/toast';
import { createServerTable, escapeHtml } from '../../utils/server-table';
import { openModal, closeModal } from '../../utils/modal';

function formatDate(value) {
    if (!value) {
        return '—';
    }
    try {
        return new Date(value).toLocaleString();
    } catch {
        return value;
    }
}

export function initAuditModule() {
    const root = document.querySelector('[data-module="audit"]');
    if (!root) {
        return;
    }

    const panel = root.querySelector('[data-spa-module="audit-logs"]');
    const modal = document.getElementById('audit-log-modal');
    const detail = modal.querySelector('[data-audit-detail]');
    const searchInput = panel.querySelector('[data-filter="search"]');
    const eventSelect = panel.querySelector('[data-filter="event"]');
    const fromInput = panel.querySelector('[data-filter="from"]');
    const toInput = panel.querySelector('[data-filter="to"]');

    const table = createServerTable({
        root: panel,
        endpoint: '/audit/logs',
        columns: 5,
        perPage: 15,
        extraParams: () => ({
            search: searchInput?.value?.trim() || '',
            event: eventSelect?.value || '',
            from: fromInput?.value || '',
            to: toInput?.value || '',
        }),
        mapRow: (row) => {
            const actions = [{ key: 'view', label: 'View' }];
            const userLabel = row.user?.name || row.email || '—';
            const metaPreview = row.meta && Object.keys(row.meta).length
                ? JSON.stringify(row.meta).slice(0, 48) + (JSON.stringify(row.meta).length > 48 ? '…' : '')
                : '—';

            return `
                <tr class="hover:bg-subtle/60" data-row-id="${escapeHtml(row.id)}" data-actions='${JSON.stringify(actions)}'>
                    <td class="px-4 py-3 whitespace-nowrap text-text-secondary">${escapeHtml(formatDate(row.created_at))}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center h-7 px-2.5 rounded-lg bg-subtle border border-border text-xs font-semibold text-heading">
                            ${escapeHtml(row.event)}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-heading">${escapeHtml(userLabel)}</div>
                        <div class="text-xs text-muted">${escapeHtml(row.email || row.user?.email || '')}</div>
                    </td>
                    <td class="px-4 py-3 text-text-secondary">${escapeHtml(row.ip_address || '—')}</td>
                    <td class="px-4 py-3 text-xs text-muted font-mono">${escapeHtml(metaPreview)}</td>
                </tr>
            `;
        },
        onRowAction: async (action, row) => {
            if (action !== 'view') {
                return;
            }
            try {
                const { data } = await http.get(`/audit/logs/${row.id}`);
                const log = data?.data?.log || row;
                detail.innerHTML = `
                    <div><span class="text-muted">Event</span><div class="font-medium text-heading mt-0.5">${escapeHtml(log.event)}</div></div>
                    <div><span class="text-muted">When</span><div class="mt-0.5">${escapeHtml(formatDate(log.created_at))}</div></div>
                    <div><span class="text-muted">User</span><div class="mt-0.5">${escapeHtml(log.user?.name || log.email || '—')}</div></div>
                    <div><span class="text-muted">Email</span><div class="mt-0.5">${escapeHtml(log.email || log.user?.email || '—')}</div></div>
                    <div><span class="text-muted">IP</span><div class="mt-0.5">${escapeHtml(log.ip_address || '—')}</div></div>
                    <div><span class="text-muted">User agent</span><div class="mt-0.5 break-all text-xs">${escapeHtml(log.user_agent || '—')}</div></div>
                    <div><span class="text-muted">Meta</span><pre class="mt-1 p-3 rounded-xl bg-subtle border border-border text-xs overflow-x-auto">${escapeHtml(JSON.stringify(log.meta || {}, null, 2))}</pre></div>
                `;
                openModal(modal);
            } catch (error) {
                toastError(error.response?.data?.message || 'Unable to load event');
            }
        },
    });

    async function loadEvents() {
        try {
            const { data } = await http.get('/audit/events');
            const events = data?.data?.events || [];
            const current = eventSelect.value;
            eventSelect.innerHTML = '<option value="">All events</option>' + events
                .map((event) => `<option value="${escapeHtml(event)}">${escapeHtml(event)}</option>`)
                .join('');
            eventSelect.value = current;
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to load event types');
        }
    }

    modal.querySelectorAll('[data-modal-dismiss]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });

    let searchTimer = null;
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => table.reload(true), 300);
    });
    eventSelect?.addEventListener('change', () => table.reload(true));
    fromInput?.addEventListener('change', () => table.reload(true));
    toInput?.addEventListener('change', () => table.reload(true));

    loadEvents().then(() => table.reload(true));
}
