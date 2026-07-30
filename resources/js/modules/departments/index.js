import http from '../../utils/http';
import { setButtonLoading } from '../../utils/button-loading';
import { toastSuccess, toastError, confirmAction } from '../../utils/toast';
import { createServerTable, escapeHtml, statusBadge } from '../../utils/server-table';
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

export function initDepartmentsModule() {
    const root = document.querySelector('[data-module="departments"]');
    if (!root) {
        return;
    }

    const panel = root.querySelector('[data-spa-module="departments-table"]');
    const modal = document.getElementById('department-modal');
    const form = document.getElementById('department-form');
    const title = modal.querySelector('[data-modal-title]');
    const searchInput = panel.querySelector('[data-filter="search"]');
    const statusSelect = panel.querySelector('[data-filter="status"]');
    let editingId = null;

    const table = createServerTable({
        root: panel,
        endpoint: '/departments',
        columns: 4,
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
                    <td class="px-4 py-3 text-text-secondary">${escapeHtml(row.description || '—')}</td>
                    <td class="px-4 py-3">${statusBadge(row.is_active)}</td>
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
                    title: 'Delete department?',
                    text: `${row.name} (${row.code}) will be removed.`,
                    confirmButtonText: 'Delete',
                });
                if (!confirmed.isConfirmed) {
                    return;
                }
                try {
                    const { data } = await http.delete(`/departments/${row.id}`);
                    toastSuccess(data.message || 'Department deleted');
                    table.reload();
                } catch (error) {
                    toastError(error.response?.data?.message || 'Unable to delete department');
                }
            }
        },
    });

    function resetForm() {
        form.reset();
        form.id.value = '';
        editingId = null;
        clearErrors(form);
        form.is_active.checked = true;
    }

    function openCreate() {
        resetForm();
        title.textContent = 'Add department';
        openModal(modal);
    }

    function openEdit(row) {
        resetForm();
        editingId = row.id;
        title.textContent = 'Edit department';
        form.id.value = row.id;
        form.code.value = row.code || '';
        form.name.value = row.name || '';
        form.description.value = row.description || '';
        form.is_active.checked = !!row.is_active;
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
            description: form.description.value.trim() || null,
            is_active: form.is_active.checked,
        };

        const submit = form.querySelector('[type="submit"]');
        setButtonLoading(submit, true, 'Saving…');

        try {
            const { data } = editingId
                ? await http.put(`/departments/${editingId}`, payload)
                : await http.post('/departments', payload);

            toastSuccess(data.message || 'Department saved');
            closeModal(modal);
            table.reload(true);
        } catch (error) {
            const response = error.response?.data;
            showErrors(form, response?.errors || {});
            toastError(response?.message || 'Unable to save department');
        } finally {
            setButtonLoading(submit, false);
        }
    });

    table.reload(true);
}
