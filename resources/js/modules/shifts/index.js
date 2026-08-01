import http from '../../utils/http';
import { setButtonLoading } from '../../utils/button-loading';
import { toastSuccess, toastError, confirmAction } from '../../utils/toast';
import { createServerTable, escapeHtml, statusBadge, rowActionsCell } from '../../utils/server-table';
import { openModal, closeModal } from '../../utils/modal';

function clearErrors(form) {
    form.querySelectorAll('[data-error]').forEach((el) => {
        el.textContent = '';
        el.classList.add('hidden');
    });
}

function showErrors(form, errors = {}) {
    Object.entries(errors).forEach(([field, messages]) => {
        const el = form.querySelector(`[data-error="${field}"]`);
        if (!el) {
            return;
        }
        el.textContent = Array.isArray(messages) ? messages[0] : String(messages);
        el.classList.remove('hidden');
    });
}

function scheduleLabel(row) {
    const night = row.crosses_midnight
        ? ' <span class="inline-flex items-center h-6 px-2 rounded-md bg-subtle text-text-secondary text-[11px] font-semibold">Night</span>'
        : '';
    return `${escapeHtml(row.time_in)} – ${escapeHtml(row.time_out)}${night}`;
}

export function initShiftsModule() {
    const root = document.querySelector('[data-module="shifts"]');
    if (!root) {
        return;
    }

    const panel = root.querySelector('[data-spa-module="shifts-table"]');
    const modal = document.getElementById('shift-modal');
    const form = document.getElementById('shift-form');
    const title = modal?.querySelector('[data-modal-title]');
    const searchInput = panel?.querySelector('[data-filter="search"]');
    const statusSelect = panel?.querySelector('[data-filter="status"]');
    let editingId = null;

    if (!panel || !modal || !form) {
        return;
    }

    const table = createServerTable({
        root: panel,
        endpoint: '/shifts',
        columns: 6,
        perPage: 10,
        extraParams: () => ({
            search: searchInput?.value?.trim() || '',
            status: statusSelect?.value || '',
        }),
        mapRow: (row) => {
            const actions = [
                { key: 'edit', label: 'Edit' },
                { key: 'delete', label: 'Delete', danger: true },
            ];
            return `
                <tr class="hover:bg-subtle/60" data-row-id="${escapeHtml(row.id)}" data-actions='${JSON.stringify(actions)}'>
                    <td class="px-4 py-3 font-semibold text-heading">${escapeHtml(row.code)}</td>
                    <td class="px-4 py-3 text-heading">${escapeHtml(row.name)}</td>
                    <td class="px-4 py-3 text-text-secondary whitespace-nowrap">${scheduleLabel(row)}</td>
                    <td class="px-4 py-3 text-text-secondary">${escapeHtml(String(row.work_hours ?? '—'))}h</td>
                    <td class="px-4 py-3">${statusBadge(row.is_active)}</td>
                    ${rowActionsCell(actions)}
                </tr>
            `;
        },
        onRowAction: async (action, row) => {
            if (action === 'edit') {
                openEdit(row);
                return;
            }
            if (action === 'delete') {
                const confirmed = await confirmAction({
                    title: 'Delete shift?',
                    text: `${row.name} (${row.code}) will be removed.`,
                    confirmButtonText: 'Delete',
                });
                if (!confirmed.isConfirmed) {
                    return;
                }
                try {
                    const { data } = await http.delete(`/shifts/${row.id}`);
                    toastSuccess(data.message || 'Shift deleted');
                    table.reload();
                } catch (error) {
                    toastError(error.response?.data?.message || 'Unable to delete shift');
                }
            }
        },
    });

    function resetForm() {
        form.reset();
        form.id.value = '';
        editingId = null;
        clearErrors(form);
        form.break_minutes.value = '60';
        form.grace_minutes.value = '0';
        form.is_active.checked = true;
        form.crosses_midnight.checked = false;
    }

    function openCreate() {
        resetForm();
        if (title) {
            title.textContent = 'Add shift';
        }
        openModal(modal);
    }

    function openEdit(row) {
        resetForm();
        editingId = row.id;
        if (title) {
            title.textContent = 'Edit shift';
        }
        form.id.value = row.id;
        form.code.value = row.code || '';
        form.name.value = row.name || '';
        form.time_in.value = row.time_in || '';
        form.time_out.value = row.time_out || '';
        form.break_minutes.value = row.break_minutes ?? 60;
        form.grace_minutes.value = row.grace_minutes ?? 0;
        form.description.value = row.description || '';
        form.crosses_midnight.checked = Boolean(row.crosses_midnight);
        form.is_active.checked = Boolean(row.is_active);
        openModal(modal);
    }

    panel.querySelector('[data-action="create"]')?.addEventListener('click', openCreate);
    modal.querySelectorAll('[data-modal-dismiss]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });

    let searchTimer = null;
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => table.reload(true), 300);
    });
    statusSelect?.addEventListener('change', () => table.reload(true));

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(form);

        const payload = {
            code: form.code.value.trim(),
            name: form.name.value.trim(),
            time_in: form.time_in.value,
            time_out: form.time_out.value,
            break_minutes: Number(form.break_minutes.value || 0),
            grace_minutes: Number(form.grace_minutes.value || 0),
            crosses_midnight: form.crosses_midnight.checked,
            description: form.description.value.trim() || null,
            is_active: form.is_active.checked,
        };

        const submit = form.querySelector('[type="submit"]');
        setButtonLoading(submit, true, 'Saving…');

        try {
            const { data } = editingId
                ? await http.put(`/shifts/${editingId}`, payload)
                : await http.post('/shifts', payload);

            toastSuccess(data.message || 'Shift saved');
            closeModal(modal);
            table.reload(true);
        } catch (error) {
            const response = error.response?.data;
            showErrors(form, response?.errors || {});
            toastError(response?.message || 'Unable to save shift');
        } finally {
            setButtonLoading(submit, false);
        }
    });

    table.reload(true);
}
