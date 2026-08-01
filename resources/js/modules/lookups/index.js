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

export function initLookupsModule() {
    const root = document.querySelector('[data-module="lookups"]');
    if (!root) {
        return;
    }

    const panel = root.querySelector('[data-spa-module="lookups-table"]');
    const modal = document.getElementById('lookup-modal');
    const form = document.getElementById('lookup-form');
    const title = modal?.querySelector('[data-modal-title]');
    const typeList = root.querySelector('[data-type-list]');
    const typeSelect = form?.querySelector('[data-lookup-type-select]');
    const searchInput = panel?.querySelector('[data-filter="search"]');
    const statusSelect = panel?.querySelector('[data-filter="status"]');
    const typeFilter = panel?.querySelector('[data-filter="type"]');
    let editingId = null;
    let types = [];
    let isSystemEdit = false;

    if (!panel || !modal || !form) {
        return;
    }

    function renderTypes(activeType = '') {
        if (!typeList) {
            return;
        }
        if (!types.length) {
            typeList.innerHTML = '<p class="text-sm text-muted px-2 py-3">No lookup types configured.</p>';
            return;
        }

        typeList.innerHTML = [
            `<button type="button" data-type="" class="inline-flex items-center gap-2 whitespace-nowrap xl:w-full text-left h-10 min-h-[44px] px-3 rounded-xl text-sm font-medium transition-colors ${activeType === '' ? 'bg-primary-soft text-primary' : 'text-heading hover:bg-subtle'}">
                <i class="ph ph-list-bullets text-base" aria-hidden="true"></i>
                <span class="flex-1">All tables</span>
                <span class="text-xs opacity-80">${types.reduce((sum, item) => sum + (item.count || 0), 0)}</span>
            </button>`,
            ...types.map((item) => `
                <button type="button" data-type="${escapeHtml(item.type)}" class="inline-flex items-center gap-2 whitespace-nowrap xl:w-full text-left h-10 min-h-[44px] px-3 rounded-xl text-sm font-medium transition-colors ${activeType === item.type ? 'bg-primary-soft text-primary' : 'text-heading hover:bg-subtle'}" title="${escapeHtml(item.description || '')}">
                    <i class="ph ph-database text-base text-muted" aria-hidden="true"></i>
                    <span class="flex-1 truncate">${escapeHtml(item.label)}</span>
                    <span class="text-xs text-muted">${item.count ?? 0}</span>
                </button>
            `),
        ].join('');

        if (typeSelect) {
            const current = typeSelect.value || activeType || types[0]?.type || '';
            typeSelect.innerHTML = types.map((item) => (
                `<option value="${escapeHtml(item.type)}">${escapeHtml(item.label)}</option>`
            )).join('');
            typeSelect.value = current;
        }
    }

    function setActiveType(type) {
        if (typeFilter) {
            typeFilter.value = type || '';
        }
        renderTypes(type || '');
    }

    const table = createServerTable({
        root: panel,
        endpoint: '/lookups',
        columns: 6,
        perPage: 20,
        extraParams: () => ({
            search: searchInput?.value?.trim() || '',
            status: statusSelect?.value || '',
            type: typeFilter?.value || '',
        }),
        onLoaded: (payload) => {
            if (Array.isArray(payload.types)) {
                types = payload.types;
                renderTypes(typeFilter?.value || '');
            }
        },
        mapRow: (row) => {
            const actions = [{ key: 'edit', label: 'Edit' }];
            if (!row.is_system) {
                actions.push({ key: 'delete', label: 'Delete', danger: true });
            }

            const flags = [
                row.is_system
                    ? '<span class="inline-flex items-center h-6 px-2 rounded-md bg-subtle text-muted text-[11px] font-semibold">System</span>'
                    : '<span class="inline-flex items-center h-6 px-2 rounded-md bg-primary-soft text-primary text-[11px] font-semibold">Custom</span>',
            ].join('');

            return `
                <tr class="hover:bg-subtle/60" data-row-id="${escapeHtml(row.id)}" data-actions='${JSON.stringify(actions)}'>
                    <td class="px-4 py-3 font-mono text-sm text-heading">${escapeHtml(row.code)}</td>
                    <td class="px-4 py-3">
                        <p class="font-semibold text-heading">${escapeHtml(row.label)}</p>
                        <p class="text-xs text-muted mt-0.5">${escapeHtml(row.type_label || row.type)}</p>
                    </td>
                    <td class="px-4 py-3 text-text-secondary">${escapeHtml(String(row.sort_order ?? 0))}</td>
                    <td class="px-4 py-3">${flags}</td>
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
                    title: 'Delete lookup value?',
                    text: `${row.label} (${row.code}) will be removed from dropdowns.`,
                    confirmButtonText: 'Delete',
                });
                if (!confirmed.isConfirmed) {
                    return;
                }
                try {
                    const { data } = await http.delete(`/lookups/${row.id}`);
                    toastSuccess(data.message || 'Lookup value deleted');
                    table.reload();
                } catch (error) {
                    toastError(error.response?.data?.message || 'Unable to delete lookup value');
                }
            }
        },
    });

    function resetForm() {
        form.reset();
        form.id.value = '';
        editingId = null;
        isSystemEdit = false;
        clearErrors(form);
        form.is_active.checked = true;
        form.code.readOnly = false;
        form.type.disabled = false;
        if (typeFilter?.value) {
            form.type.value = typeFilter.value;
        }
    }

    function openCreate() {
        resetForm();
        if (title) {
            title.textContent = 'Add lookup value';
        }
        openModal(modal);
        form.code.focus();
    }

    function openEdit(row) {
        resetForm();
        editingId = row.id;
        isSystemEdit = !!row.is_system;
        if (title) {
            title.textContent = 'Edit lookup value';
        }
        form.id.value = row.id;
        form.type.value = row.type;
        form.code.value = row.code;
        form.label.value = row.label || '';
        form.sort_order.value = String(row.sort_order ?? 0);
        form.is_active.checked = !!row.is_active;
        form.description.value = row.description || '';
        form.code.readOnly = isSystemEdit;
        form.type.disabled = true;
        openModal(modal);
        form.label.focus();
    }

    panel.querySelector('[data-action="create"]')?.addEventListener('click', openCreate);
    modal.querySelectorAll('[data-modal-dismiss]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });

    typeList?.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-type]');
        if (!btn) {
            return;
        }
        setActiveType(btn.dataset.type || '');
        table.reload(true);
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
            type: form.type.value,
            code: form.code.value.trim().toLowerCase(),
            label: form.label.value.trim(),
            sort_order: Number(form.sort_order.value || 0),
            is_active: form.is_active.checked,
            description: form.description.value.trim() || null,
        };

        const submit = form.querySelector('[type="submit"]');
        setButtonLoading(submit, true, 'Saving…');
        try {
            const { data } = editingId
                ? await http.put(`/lookups/${editingId}`, payload)
                : await http.post('/lookups', payload);

            toastSuccess(data.message || 'Lookup value saved');
            closeModal(modal);
            table.reload(true);
        } catch (error) {
            const response = error.response?.data;
            showErrors(form, response?.errors || {});
            toastError(response?.message || 'Unable to save lookup value');
        } finally {
            setButtonLoading(submit, false);
        }
    });

    table.reload(true);
}
