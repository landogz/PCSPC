import http from '../../utils/http';
import { setButtonLoading } from '../../utils/button-loading';
import { toastSuccess, toastError, confirmAction } from '../../utils/toast';
import { createServerTable, escapeHtml, statusBadge, rowActionsCell } from '../../utils/server-table';
import { openModal, closeModal } from '../../utils/modal';

const TYPE_LABELS = {
    regular: 'Regular',
    special_non_working: 'Special non-working',
    special_working: 'Special working',
    company: 'Company',
};

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

function fillYearFilter(select) {
    if (!select || select.options.length > 1) {
        return;
    }
    const current = new Date().getFullYear();
    for (let year = current + 1; year >= current - 3; year -= 1) {
        const option = document.createElement('option');
        option.value = String(year);
        option.textContent = String(year);
        select.appendChild(option);
    }
    select.value = String(current);
}

function flagBadges(row) {
    const parts = [];
    if (row.is_recurring) {
        parts.push('<span class="inline-flex items-center h-6 px-2 rounded-md bg-subtle text-text-secondary text-[11px] font-semibold">Recurring</span>');
    }
    if (row.is_double_pay) {
        parts.push('<span class="inline-flex items-center h-6 px-2 rounded-md bg-warning-soft text-heading text-[11px] font-semibold">Double pay</span>');
    }
    parts.push(`<span class="text-xs text-muted">${escapeHtml(String(row.paid_hours ?? 8))}h</span>`);
    return `<div class="flex flex-wrap items-center gap-1.5">${parts.join('')}</div>`;
}

export function initHolidaysModule() {
    const root = document.querySelector('[data-module="holidays"]');
    if (!root) {
        return;
    }

    const panel = root.querySelector('[data-spa-module="holidays-table"]');
    const modal = document.getElementById('holiday-modal');
    const form = document.getElementById('holiday-form');
    const title = modal?.querySelector('[data-modal-title]');
    const searchInput = panel?.querySelector('[data-filter="search"]');
    const statusSelect = panel?.querySelector('[data-filter="status"]');
    const typeSelect = panel?.querySelector('[data-filter="type"]');
    const yearSelect = panel?.querySelector('[data-filter="year"]');
    let editingId = null;

    if (!panel || !modal || !form) {
        return;
    }

    fillYearFilter(yearSelect);

    const table = createServerTable({
        root: panel,
        endpoint: '/holidays',
        columns: 6,
        perPage: 10,
        extraParams: () => ({
            search: searchInput?.value?.trim() || '',
            status: statusSelect?.value || '',
            type: typeSelect?.value || '',
            year: yearSelect?.value || '',
        }),
        mapRow: (row) => {
            const actions = [
                { key: 'edit', label: 'Edit' },
                { key: 'delete', label: 'Delete', danger: true },
            ];
            return `
                <tr class="hover:bg-subtle/60" data-row-id="${escapeHtml(row.id)}" data-actions='${JSON.stringify(actions)}'>
                    <td class="px-4 py-3 text-heading whitespace-nowrap">${escapeHtml(row.holiday_date || '—')}</td>
                    <td class="px-4 py-3 font-semibold text-heading">${escapeHtml(row.name)}</td>
                    <td class="px-4 py-3 text-text-secondary">${escapeHtml(TYPE_LABELS[row.type] || row.type)}</td>
                    <td class="px-4 py-3">${flagBadges(row)}</td>
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
                    title: 'Delete holiday?',
                    text: `${row.name} on ${row.holiday_date} will be removed.`,
                    confirmButtonText: 'Delete',
                });
                if (!confirmed.isConfirmed) {
                    return;
                }
                try {
                    const { data } = await http.delete(`/holidays/${row.id}`);
                    toastSuccess(data.message || 'Holiday deleted');
                    table.reload();
                } catch (error) {
                    toastError(error.response?.data?.message || 'Unable to delete holiday');
                }
            }
        },
    });

    function resetForm() {
        form.reset();
        form.id.value = '';
        editingId = null;
        clearErrors(form);
        form.paid_hours.value = '8';
        form.is_active.checked = true;
        form.is_recurring.checked = false;
        form.is_double_pay.checked = false;
    }

    function openCreate() {
        resetForm();
        if (title) {
            title.textContent = 'Add holiday';
        }
        openModal(modal);
    }

    function openEdit(row) {
        resetForm();
        editingId = row.id;
        if (title) {
            title.textContent = 'Edit holiday';
        }
        form.id.value = row.id;
        form.name.value = row.name || '';
        form.holiday_date.value = row.holiday_date || '';
        form.type.value = row.type || 'regular';
        form.paid_hours.value = row.paid_hours ?? 8;
        form.description.value = row.description || '';
        form.is_recurring.checked = Boolean(row.is_recurring);
        form.is_double_pay.checked = Boolean(row.is_double_pay);
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
    typeSelect?.addEventListener('change', () => table.reload(true));
    yearSelect?.addEventListener('change', () => table.reload(true));

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(form);

        const payload = {
            name: form.name.value.trim(),
            holiday_date: form.holiday_date.value,
            type: form.type.value,
            paid_hours: Number(form.paid_hours.value || 8),
            description: form.description.value.trim() || null,
            is_recurring: form.is_recurring.checked,
            is_double_pay: form.is_double_pay.checked,
            is_active: form.is_active.checked,
        };

        const submit = form.querySelector('[type="submit"]');
        setButtonLoading(submit, true, 'Saving…');

        try {
            const { data } = editingId
                ? await http.put(`/holidays/${editingId}`, payload)
                : await http.post('/holidays', payload);

            toastSuccess(data.message || 'Holiday saved');
            closeModal(modal);
            table.reload(true);
        } catch (error) {
            const response = error.response?.data;
            showErrors(form, response?.errors || {});
            toastError(response?.message || 'Unable to save holiday');
        } finally {
            setButtonLoading(submit, false);
        }
    });

    table.reload(true);
}
