/**
 * notifications module (SPA)
 * Axios against /api/v1/notifications — list, mark read, mark all read
 */
import http from '../../utils/http';
import { toastError, toastSuccess } from '../../utils/toast';
import { createServerTable, escapeHtml, rowActionsCell } from '../../utils/server-table';
import { openModal, closeModal } from '../../utils/modal';
import { initTopbarNotifications } from './topbar';

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

export function initNotificationsModule() {
    const root = document.querySelector('[data-module="notifications"]');
    if (!root) {
        return;
    }

    const panel = root.querySelector('[data-spa-module="user-notifications"]');
    const modal = document.getElementById('notification-detail-modal');
    const detail = modal?.querySelector('[data-notif-detail]');
    const openLink = modal?.querySelector('[data-notif-open-link]');
    const searchInput = panel?.querySelector('[data-filter="search"]');
    const typeSelect = panel?.querySelector('[data-filter="type"]');
    const unreadSelect = panel?.querySelector('[data-filter="unread"]');
    const unreadLabel = root.querySelector('[data-notif-unread-label]');
    const markAllBtn = panel?.querySelector('[data-action="mark-all-read"]');

    if (!panel) {
        return;
    }

    function setUnreadLabel(count) {
        if (!unreadLabel) {
            return;
        }
        const n = Number(count) || 0;
        unreadLabel.textContent = n === 1 ? '1 unread' : `${n} unread`;
    }

    const table = createServerTable({
        root: panel,
        endpoint: '/notifications',
        columns: 5,
        perPage: 15,
        extraParams: () => ({
            search: searchInput?.value?.trim() || '',
            type: typeSelect?.value || '',
            unread: unreadSelect?.value || '',
        }),
        mapRow: (row) => {
            const actions = [
                { key: 'view', label: 'View' },
                ...(!row.is_read ? [{ key: 'read', label: 'Mark read' }] : []),
            ];
            const status = row.is_read
                ? '<span class="inline-flex items-center h-7 px-2.5 rounded-lg bg-subtle border border-border text-xs font-semibold text-muted">Read</span>'
                : '<span class="inline-flex items-center h-7 px-2.5 rounded-lg bg-danger-soft text-danger text-xs font-semibold">Unread</span>';

            return `
                <tr class="hover:bg-subtle/60 ${row.is_read ? '' : 'bg-primary/5'}" data-row-id="${escapeHtml(row.id)}" data-actions='${JSON.stringify(actions)}'>
                    <td class="px-4 py-3 whitespace-nowrap text-text-secondary">${escapeHtml(formatDate(row.created_at))}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center h-7 px-2.5 rounded-lg bg-subtle border border-border text-xs font-semibold text-heading">
                            ${escapeHtml(row.type)}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-heading">${escapeHtml(row.title)}</div>
                        <div class="text-xs text-muted line-clamp-1">${escapeHtml(row.body || '')}</div>
                    </td>
                    <td class="px-4 py-3">${status}</td>
                    ${rowActionsCell(actions)}
                </tr>
            `;
        },
        onRowAction: async (action, row) => {
            if (action === 'read') {
                try {
                    const { data } = await http.post(`/notifications/${row.id}/read`);
                    setUnreadLabel(data?.data?.unread_count);
                    window.dispatchEvent(new CustomEvent('pcspc:notifications-refresh'));
                    toastSuccess('Marked as read');
                    table.reload();
                } catch (error) {
                    toastError(error.response?.data?.message || 'Unable to mark as read');
                }
                return;
            }

            if (action !== 'view') {
                return;
            }

            try {
                if (!row.is_read) {
                    await http.post(`/notifications/${row.id}/read`);
                    window.dispatchEvent(new CustomEvent('pcspc:notifications-refresh'));
                }
                const { data } = await http.get(`/notifications/${row.id}`);
                const item = data?.data?.notification || row;
                if (detail) {
                    detail.innerHTML = `
                        <div><span class="text-muted">Type</span><div class="font-medium text-heading mt-0.5">${escapeHtml(item.type)}</div></div>
                        <div><span class="text-muted">When</span><div class="mt-0.5">${escapeHtml(formatDate(item.created_at))}</div></div>
                        <div><span class="text-muted">Title</span><div class="mt-0.5 font-medium text-heading">${escapeHtml(item.title)}</div></div>
                        <div><span class="text-muted">Message</span><div class="mt-0.5 whitespace-pre-wrap">${escapeHtml(item.body || '—')}</div></div>
                    `;
                }
                if (openLink) {
                    if (item.action_url) {
                        openLink.href = item.action_url;
                        openLink.classList.remove('hidden');
                    } else {
                        openLink.classList.add('hidden');
                        openLink.removeAttribute('href');
                    }
                }
                openModal(modal);
                table.reload();
            } catch (error) {
                toastError(error.response?.data?.message || 'Unable to load notification');
            }
        },
        onLoaded: (payload) => {
            setUnreadLabel(payload?.meta?.unread_count);
        },
    });

    async function loadTypes() {
        if (!typeSelect) {
            return;
        }
        try {
            const { data } = await http.get('/notifications/types');
            const types = data?.data?.types || [];
            const current = typeSelect.value;
            typeSelect.innerHTML = '<option value="">All types</option>' + types
                .map((type) => `<option value="${escapeHtml(type)}">${escapeHtml(type)}</option>`)
                .join('');
            typeSelect.value = current;
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to load notification types');
        }
    }

    modal?.querySelectorAll('[data-modal-dismiss]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });

    markAllBtn?.addEventListener('click', async () => {
        try {
            await http.post('/notifications/mark-all-read');
            toastSuccess('All notifications marked as read');
            setUnreadLabel(0);
            window.dispatchEvent(new CustomEvent('pcspc:notifications-refresh'));
            table.reload(true);
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to mark all as read');
        }
    });

    let searchTimer = null;
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => table.reload(true), 300);
    });
    typeSelect?.addEventListener('change', () => table.reload(true));
    unreadSelect?.addEventListener('change', () => table.reload(true));

    window.addEventListener('pcspc:notifications-updated', (event) => {
        setUnreadLabel(event.detail?.unread_count);
    });

    loadTypes().then(() => table.reload(true));
}

export { initTopbarNotifications };
