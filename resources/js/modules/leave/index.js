/**
 * Leave module — filing, multi-level approvals, balances, types (P4a–P4c).
 */
import http from '../../utils/http';
import { setButtonLoading } from '../../utils/button-loading';
import { toastSuccess, toastError, confirmAction } from '../../utils/toast';
import { createServerTable, escapeHtml, statusBadge, rowActionsCell } from '../../utils/server-table';
import { openModal, closeModal } from '../../utils/modal';
import { initEmployeeSearch } from '../../utils/employee-search';

function clearErrors(form) {
    form?.querySelectorAll('[data-error]').forEach((el) => {
        el.textContent = '';
        el.classList.add('hidden');
    });
}

function showErrors(form, errors = {}) {
    Object.entries(errors).forEach(([field, messages]) => {
        const el = form?.querySelector(`[data-error="${field}"]`);
        if (!el) {
            return;
        }
        el.textContent = Array.isArray(messages) ? messages[0] : String(messages);
        el.classList.remove('hidden');
    });
}

function fillYearSelect(select, selected) {
    if (!select) {
        return;
    }
    const current = selected || new Date().getFullYear();
    select.innerHTML = '';
    for (let year = current + 1; year >= current - 3; year -= 1) {
        const option = document.createElement('option');
        option.value = String(year);
        option.textContent = String(year);
        if (year === current) {
            option.selected = true;
        }
        select.appendChild(option);
    }
}

function setActiveTab(root, key) {
    root.querySelectorAll('[data-leave-tab]').forEach((btn) => {
        const active = btn.getAttribute('data-leave-tab') === key;
        btn.classList.toggle('border-primary', active);
        btn.classList.toggle('text-primary', active);
        btn.classList.toggle('border-transparent', !active);
        btn.classList.toggle('text-muted', !active);
        btn.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    root.querySelectorAll('[data-leave-panel]').forEach((panel) => {
        panel.classList.toggle('hidden', panel.getAttribute('data-leave-panel') !== key);
    });
}

function requestStatusBadge(status) {
    const map = {
        pending: 'bg-warning-soft text-heading',
        approved: 'bg-success-soft text-success',
        rejected: 'bg-danger-soft text-danger',
        cancelled: 'bg-subtle text-muted',
    };
    const cls = map[status] || 'bg-subtle text-muted';
    const label = status ? status.charAt(0).toUpperCase() + status.slice(1) : '—';
    return `<span class="inline-flex items-center h-7 px-2.5 rounded-lg ${cls} text-xs font-semibold">${escapeHtml(label)}</span>`;
}

function populateTypeSelects(selects, items, { activeOnly = false, emptyLabel = 'All types' } = {}) {
    selects.forEach((select) => {
        if (!select) {
            return;
        }
        const current = select.value;
        select.innerHTML = '';
        const blank = document.createElement('option');
        blank.value = '';
        blank.textContent = emptyLabel;
        select.appendChild(blank);
        items
            .filter((type) => !activeOnly || type.is_active)
            .forEach((type) => {
                const option = document.createElement('option');
                option.value = type.id;
                option.textContent = `${type.code} — ${type.name}`;
                select.appendChild(option);
            });
        if (current) {
            select.value = current;
        }
    });
}

function typeFlagBadges(type) {
    const parts = [];
    if (type.requires_reason) {
        parts.push('<span class="inline-flex items-center h-6 px-2 rounded-md bg-subtle text-text-secondary text-[11px] font-semibold">Reason</span>');
    }
    if (type.requires_hr) {
        parts.push('<span class="inline-flex items-center h-6 px-2 rounded-md bg-warning-soft text-heading text-[11px] font-semibold">HR</span>');
    }
    if (!parts.length) {
        return '<span class="text-xs text-muted">—</span>';
    }
    return `<div class="flex flex-wrap items-center gap-1.5">${parts.join('')}</div>`;
}

async function loadLeaveTypes({
    filterSelects = [],
    fileSelect = null,
    adjustSelect = null,
    typesBody = null,
    canManage = false,
    onRowAction = null,
} = {}) {
    try {
        const { data } = await http.get('/leave/types', { params: { all: 1 } });
        const items = data?.data?.items || [];
        populateTypeSelects(filterSelects, items, { emptyLabel: 'All types' });
        populateTypeSelects([fileSelect], items, { activeOnly: true, emptyLabel: 'Select type…' });
        populateTypeSelects([adjustSelect], items, { emptyLabel: 'Select type…' });

        if (typesBody) {
            const cols = canManage ? 6 : 5;
            if (!items.length) {
                typesBody.innerHTML = `<tr><td colspan="${cols}" class="px-4 py-10 text-center text-muted">No leave types seeded.</td></tr>`;
            } else {
                typesBody.innerHTML = items
                    .map((type) => {
                        const actions = canManage
                            ? [
                                { key: 'edit', label: 'Edit' },
                                { key: 'delete', label: 'Delete', danger: true },
                            ]
                            : [];
                        return `
                    <tr class="hover:bg-subtle/60" data-row-id="${escapeHtml(type.id)}" data-actions='${JSON.stringify(actions)}'>
                        <td class="px-4 py-3 font-semibold text-heading">${escapeHtml(type.code)}</td>
                        <td class="px-4 py-3 text-heading">${escapeHtml(type.name)}</td>
                        <td class="px-4 py-3">${typeFlagBadges(type)}</td>
                        <td class="px-4 py-3">${type.is_accruing
                            ? '<span class="inline-flex items-center h-7 px-2.5 rounded-lg bg-success-soft text-success text-xs font-semibold">Yes</span>'
                            : '<span class="inline-flex items-center h-7 px-2.5 rounded-lg bg-subtle text-muted text-xs font-semibold">No</span>'}</td>
                        <td class="px-4 py-3">${statusBadge(Boolean(type.is_active))}</td>
                        ${canManage ? rowActionsCell(actions) : ''}
                    </tr>`;
                    })
                    .join('');

                if (canManage && typeof onRowAction === 'function') {
                    typesBody.querySelectorAll('[data-row-action]').forEach((btn) => {
                        btn.addEventListener('click', (event) => {
                            event.preventDefault();
                            const rowId = btn.closest('[data-row-id]')?.getAttribute('data-row-id');
                            const row = items.find((item) => item.id === rowId);
                            if (row) {
                                onRowAction(btn.dataset.rowAction, row);
                            }
                        });
                    });
                }
            }
        }

        return items;
    } catch (error) {
        toastError(error?.response?.data?.message || 'Unable to load leave types.');
        if (typesBody) {
            const cols = canManage ? 6 : 5;
            typesBody.innerHTML = `<tr><td colspan="${cols}" class="px-4 py-10 text-center text-danger">Failed to load leave types.</td></tr>`;
        }
        return [];
    }
}
function bindDismiss(modal) {
    modal?.querySelectorAll('[data-modal-dismiss]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });
}

function bindSearchAndFilters(table, { searchInput, selects = [] } = {}) {
    let searchTimer = null;
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => table.reload(true), 300);
    });
    selects.forEach((el) => el?.addEventListener('change', () => table.reload(true)));
}

export function initLeaveModule() {
    const root = document.querySelector('[data-module="leave"]');
    if (!root) {
        return;
    }

    const canFile = root.dataset.canFile === '1';
    const canApprove = root.dataset.canApprove === '1';
    const canManage = root.dataset.canManage === '1';
    const canViewBalances = root.dataset.canViewBalances === '1';
    const typesBody = root.querySelector('[data-leave-types-body]');

    root.querySelectorAll('[data-leave-tab]').forEach((btn) => {
        btn.addEventListener('click', () => setActiveTab(root, btn.getAttribute('data-leave-tab')));
    });

    const myPanel = root.querySelector('[data-spa-module="leave-my-requests"]');
    const approvalsPanel = root.querySelector('[data-spa-module="leave-approvals"]');
    const balancesPanel = root.querySelector('[data-spa-module="leave-balances"]');

    const fileModal = document.getElementById('leave-file-modal');
    const fileForm = document.getElementById('leave-file-form');
    const decideModal = document.getElementById('leave-decide-modal');
    const decideForm = document.getElementById('leave-decide-form');
    const typeModal = document.getElementById('leave-type-modal');
    const typeForm = document.getElementById('leave-type-form');
    const typeTitle = typeModal?.querySelector('[data-modal-title]');
    const adjustModal = document.getElementById('leave-adjust-modal');
    const adjustForm = document.getElementById('leave-adjust-form');
    const accrualModal = document.getElementById('leave-accrual-modal');
    const accrualForm = document.getElementById('leave-accrual-form');
    let editingTypeId = null;

    bindDismiss(fileModal);
    bindDismiss(decideModal);
    bindDismiss(typeModal);
    bindDismiss(adjustModal);
    bindDismiss(accrualModal);
    const yearSelect = balancesPanel?.querySelector('[data-filter="leave_year"]');
    fillYearSelect(yearSelect, new Date().getFullYear());
    const yearInput = adjustForm?.querySelector('[name="leave_year"]');
    if (yearInput) {
        yearInput.value = String(new Date().getFullYear());
    }

    const employeeSearch = initEmployeeSearch(adjustForm?.querySelector('[data-employee-search-root]'));

    function refreshLeaveTypes() {
        return loadLeaveTypes({
            filterSelects: [
                myPanel?.querySelector('[data-filter="leave_type"]'),
                approvalsPanel?.querySelector('[data-filter="leave_type"]'),
                balancesPanel?.querySelector('[data-filter="leave_type"]'),
            ],
            fileSelect: fileForm?.querySelector('[name="leave_type_id"]'),
            adjustSelect: adjustForm?.querySelector('[name="leave_type_id"]'),
            typesBody,
            canManage,
            onRowAction: handleTypeAction,
        });
    }

    function openTypeCreate() {
        if (!typeModal || !typeForm) {
            return;
        }
        editingTypeId = null;
        clearErrors(typeForm);
        typeForm.reset();
        typeForm.querySelector('[name="id"]').value = '';
        typeForm.querySelector('[name="requires_reason"]').checked = true;
        typeForm.querySelector('[name="is_active"]').checked = true;
        typeForm.querySelector('[name="is_accruing"]').checked = false;
        typeForm.querySelector('[name="requires_hr"]').checked = false;
        typeForm.querySelector('[name="sort_order"]').value = '0';
        if (typeTitle) {
            typeTitle.textContent = 'Add leave type';
        }
        openModal(typeModal);
    }

    function openTypeEdit(row) {
        if (!typeModal || !typeForm) {
            return;
        }
        editingTypeId = row.id;
        clearErrors(typeForm);
        typeForm.reset();
        typeForm.querySelector('[name="id"]').value = row.id;
        typeForm.querySelector('[name="code"]').value = row.code || '';
        typeForm.querySelector('[name="name"]').value = row.name || '';
        typeForm.querySelector('[name="sort_order"]').value = String(row.sort_order ?? 0);
        typeForm.querySelector('[name="is_accruing"]').checked = Boolean(row.is_accruing);
        typeForm.querySelector('[name="requires_reason"]').checked = Boolean(row.requires_reason);
        typeForm.querySelector('[name="requires_hr"]').checked = Boolean(row.requires_hr);
        typeForm.querySelector('[name="is_active"]').checked = Boolean(row.is_active);
        if (typeTitle) {
            typeTitle.textContent = 'Edit leave type';
        }
        openModal(typeModal);
    }

    async function handleTypeAction(action, row) {
        if (action === 'edit') {
            openTypeEdit(row);
            return;
        }
        if (action !== 'delete') {
            return;
        }
        const confirmed = await confirmAction({
            title: 'Delete leave type?',
            text: `${row.code} — ${row.name} will be removed. Types in use must be deactivated instead.`,
            confirmButtonText: 'Delete',
        });
        if (!confirmed?.isConfirmed) {
            return;
        }
        try {
            const { data } = await http.delete(`/leave/types/${row.id}`);
            toastSuccess(data?.message || 'Leave type deleted.');
            await refreshLeaveTypes();
        } catch (error) {
            toastError(error?.response?.data?.message || 'Unable to delete leave type.');
        }
    }

    refreshLeaveTypes();

    root.querySelector('[data-action="create-type"]')?.addEventListener('click', openTypeCreate);

    typeForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(typeForm);
        const submitBtn = typeForm.querySelector('[type="submit"]');
        const payload = {
            code: typeForm.querySelector('[name="code"]')?.value?.trim() || '',
            name: typeForm.querySelector('[name="name"]')?.value?.trim() || '',
            sort_order: Number(typeForm.querySelector('[name="sort_order"]')?.value || 0),
            is_accruing: Boolean(typeForm.querySelector('[name="is_accruing"]')?.checked),
            requires_reason: Boolean(typeForm.querySelector('[name="requires_reason"]')?.checked),
            requires_hr: Boolean(typeForm.querySelector('[name="requires_hr"]')?.checked),
            is_active: Boolean(typeForm.querySelector('[name="is_active"]')?.checked),
        };
        setButtonLoading(submitBtn, true, 'Saving…');
        try {
            const { data } = editingTypeId
                ? await http.put(`/leave/types/${editingTypeId}`, payload)
                : await http.post('/leave/types', payload);
            toastSuccess(data?.message || 'Leave type saved.');
            closeModal(typeModal);
            await refreshLeaveTypes();
        } catch (error) {
            const res = error?.response?.data;
            if (res?.errors) {
                showErrors(typeForm, res.errors);
            }
            toastError(res?.message || 'Unable to save leave type.');
        } finally {
            setButtonLoading(submitBtn, false);
        }
    });

    let myTable = null;
    let approvalsTable = null;
    let balancesTable = null;

    if (canFile && myPanel) {
        myTable = createServerTable({
            root: myPanel,
            endpoint: '/leave/requests/mine',
            columns: 7,
            perPage: 10,
            extraParams: () => ({
                search: myPanel.querySelector('[data-filter="search"]')?.value?.trim() || '',
                status: myPanel.querySelector('[data-filter="status"]')?.value || '',
                leave_type: myPanel.querySelector('[data-filter="leave_type"]')?.value || '',
            }),
            mapRow: (row) => {
                const type = row.leave_type || {};
                const actions = row.status === 'pending' ? [{ key: 'cancel', label: 'Cancel', danger: true }] : [];
                return `
                    <tr class="hover:bg-subtle/60" data-row-id="${escapeHtml(row.id)}" data-actions='${JSON.stringify(actions)}'>
                        <td class="px-4 py-3 font-semibold text-heading">${escapeHtml(type.code || '—')}</td>
                        <td class="px-4 py-3 text-text-secondary whitespace-nowrap">${escapeHtml(row.start_date)} → ${escapeHtml(row.end_date)}</td>
                        <td class="px-4 py-3 text-right tabular-nums">${escapeHtml(row.days)}</td>
                        <td class="px-4 py-3 text-sm text-text-secondary">${escapeHtml(row.workflow?.current_step_label || '—')}</td>
                        <td class="px-4 py-3 text-text-secondary max-w-[14rem] truncate" title="${escapeHtml(row.reason || '')}">${escapeHtml(row.reason || '—')}</td>
                        <td class="px-4 py-3">${requestStatusBadge(row.status)}</td>
                        ${rowActionsCell(actions)}
                    </tr>`;
            },
            onRowAction: async (action, row) => {
                if (action !== 'cancel') {
                    return;
                }
                const confirmed = await confirmAction({
                    title: 'Cancel leave request?',
                    text: `${row.leave_type?.code || 'Leave'} ${row.start_date} → ${row.end_date} will be cancelled.`,
                    confirmButtonText: 'Cancel request',
                });
                if (!confirmed?.isConfirmed) {
                    return;
                }
                try {
                    const { data } = await http.post(`/leave/requests/${row.id}/cancel`);
                    toastSuccess(data?.message || 'Leave request cancelled.');
                    myTable?.reload();
                    approvalsTable?.reload();
                } catch (error) {
                    toastError(error?.response?.data?.message || 'Unable to cancel leave request.');
                }
            },
        });
        bindSearchAndFilters(myTable, {
            searchInput: myPanel.querySelector('[data-filter="search"]'),
            selects: [
                myPanel.querySelector('[data-filter="status"]'),
                myPanel.querySelector('[data-filter="leave_type"]'),
            ],
        });
        myPanel.querySelector('[data-action="create"]')?.addEventListener('click', () => {
            if (!fileModal || !fileForm) {
                return;
            }
            clearErrors(fileForm);
            fileForm.reset();
            openModal(fileModal);
        });
        myTable.reload(true);
    }

    if (canApprove && approvalsPanel) {
        approvalsTable = createServerTable({
            root: approvalsPanel,
            endpoint: '/leave/requests',
            columns: 8,
            perPage: 10,
            extraParams: () => ({
                search: approvalsPanel.querySelector('[data-filter="search"]')?.value?.trim() || '',
                status: approvalsPanel.querySelector('[data-filter="status"]')?.value || '',
                leave_type: approvalsPanel.querySelector('[data-filter="leave_type"]')?.value || '',
            }),
            mapRow: (row) => {
                const emp = row.employee || {};
                const type = row.leave_type || {};
                const actions = [];
                if (row.status === 'pending') {
                    const stepPerm = row.workflow?.current_step_permission || '';
                    const canActOnStep = stepPerm === 'leave.manage'
                        ? canManage
                        : (stepPerm === 'leave.approve' ? canApprove : canApprove);
                    if (canActOnStep) {
                        actions.push({ key: 'approve', label: 'Approve' });
                        actions.push({ key: 'reject', label: 'Reject', danger: true });
                    }
                }
                return `
                    <tr class="hover:bg-subtle/60" data-row-id="${escapeHtml(row.id)}" data-actions='${JSON.stringify(actions)}'>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-heading">${escapeHtml(emp.full_name || '—')}</div>
                            <div class="text-xs text-muted">${escapeHtml(emp.employee_number || '')}</div>
                        </td>
                        <td class="px-4 py-3 text-heading">${escapeHtml(type.code || '—')}${type.requires_hr ? ' <span class="text-[11px] text-muted">(HR)</span>' : ''}</td>
                        <td class="px-4 py-3 text-text-secondary whitespace-nowrap">${escapeHtml(row.start_date)} → ${escapeHtml(row.end_date)}</td>
                        <td class="px-4 py-3 text-right tabular-nums">${escapeHtml(row.days)}</td>
                        <td class="px-4 py-3 text-sm text-text-secondary">${escapeHtml(row.workflow?.current_step_label || '—')}</td>
                        <td class="px-4 py-3 text-text-secondary max-w-[12rem] truncate" title="${escapeHtml(row.reason || '')}">${escapeHtml(row.reason || '—')}</td>
                        <td class="px-4 py-3">${requestStatusBadge(row.status)}</td>
                        ${rowActionsCell(actions)}
                    </tr>`;
            },
            onRowAction: (action, row) => {
                if (!decideModal || !decideForm || (action !== 'approve' && action !== 'reject')) {
                    return;
                }
                clearErrors(decideForm);
                decideForm.reset();
                decideForm.querySelector('[name="request_id"]').value = row.id;
                decideForm.querySelector('[name="decision"]').value = action;
                const summary = decideForm.querySelector('[data-decide-summary]');
                if (summary) {
                    summary.textContent = `${action === 'approve' ? 'Approve' : 'Reject'} ${row.employee?.full_name || 'employee'} · ${row.leave_type?.code || 'leave'} · ${row.start_date} → ${row.end_date} (${row.days} day(s))`;
                }
                const submitBtn = decideForm.querySelector('[data-decide-submit]');
                if (submitBtn) {
                    submitBtn.innerHTML = action === 'approve'
                        ? '<i class="ph ph-check-circle text-base" aria-hidden="true"></i><span>Approve</span>'
                        : '<i class="ph ph-x-circle text-base" aria-hidden="true"></i><span>Reject</span>';
                }
                openModal(decideModal);
            },
        });
        bindSearchAndFilters(approvalsTable, {
            searchInput: approvalsPanel.querySelector('[data-filter="search"]'),
            selects: [
                approvalsPanel.querySelector('[data-filter="status"]'),
                approvalsPanel.querySelector('[data-filter="leave_type"]'),
            ],
        });
        approvalsTable.reload(true);
    }

    if (canViewBalances && balancesPanel) {
        balancesTable = createServerTable({
            root: balancesPanel,
            endpoint: '/leave/balances',
            columns: canManage ? 9 : 8,
            perPage: 10,
            extraParams: () => ({
                search: balancesPanel.querySelector('[data-filter="search"]')?.value?.trim() || '',
                leave_year: yearSelect?.value || '',
                leave_type: balancesPanel.querySelector('[data-filter="leave_type"]')?.value || '',
            }),
            mapRow: (row) => {
                const emp = row.employee || {};
                const type = row.leave_type || {};
                const actions = canManage ? [{ key: 'adjust', label: 'Adjust' }] : [];
                return `
                    <tr class="hover:bg-subtle/60" data-row-id="${escapeHtml(row.id)}" data-actions='${JSON.stringify(actions)}'>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-heading">${escapeHtml(emp.full_name || '—')}</div>
                            <div class="text-xs text-muted">${escapeHtml(emp.employee_number || '')}${emp.department ? ` · ${escapeHtml(emp.department)}` : ''}</div>
                        </td>
                        <td class="px-4 py-3 text-heading">${escapeHtml(type.code || '—')}</td>
                        <td class="px-4 py-3 text-text-secondary">${escapeHtml(String(row.leave_year ?? ''))}</td>
                        <td class="px-4 py-3 text-right tabular-nums">${escapeHtml(row.beginning)}</td>
                        <td class="px-4 py-3 text-right tabular-nums">${escapeHtml(row.earned)}</td>
                        <td class="px-4 py-3 text-right tabular-nums">${escapeHtml(row.used)}</td>
                        <td class="px-4 py-3 text-right tabular-nums">${escapeHtml(row.adjusted)}</td>
                        <td class="px-4 py-3 text-right tabular-nums font-semibold text-heading">${escapeHtml(row.ending)}</td>
                        ${canManage ? rowActionsCell(actions) : ''}
                    </tr>`;
            },
            onRowAction: (action, row) => {
                if (action === 'adjust') {
                    openAdjust(row);
                }
            },
        });
        bindSearchAndFilters(balancesTable, {
            searchInput: balancesPanel.querySelector('[data-filter="search"]'),
            selects: [yearSelect, balancesPanel.querySelector('[data-filter="leave_type"]')],
        });
        balancesTable.reload(true);
    }

    function openAdjust(row = null) {
        if (!adjustModal || !adjustForm) {
            return;
        }
        clearErrors(adjustForm);
        adjustForm.reset();
        employeeSearch?.clear?.();
        if (yearInput) {
            yearInput.value = String(row?.leave_year || yearSelect?.value || new Date().getFullYear());
        }
        const typeSelect = adjustForm.querySelector('[name="leave_type_id"]');
        if (typeSelect && row?.leave_type?.id) {
            typeSelect.value = row.leave_type.id;
        }
        if (row?.employee?.id && employeeSearch?.setSelection) {
            employeeSearch.setSelection({
                id: row.employee.id,
                full_name: row.employee.full_name,
                employee_number: row.employee.employee_number,
            });
        }
        openModal(adjustModal);
    }

    balancesPanel?.querySelector('[data-action="create"]')?.addEventListener('click', () => openAdjust());
    balancesPanel?.querySelector('[data-action="run-accrual"]')?.addEventListener('click', () => {
        if (!accrualModal || !accrualForm) {
            return;
        }
        clearErrors(accrualForm);
        const monthInput = accrualForm.querySelector('[name="year_month"]');
        if (monthInput && !monthInput.value) {
            const now = new Date();
            monthInput.value = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
        }
        openModal(accrualModal);
    });

    fileForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(fileForm);
        const submitBtn = fileForm.querySelector('[type="submit"]');
        const payload = {
            leave_type_id: fileForm.querySelector('[name="leave_type_id"]')?.value || '',
            start_date: fileForm.querySelector('[name="start_date"]')?.value || '',
            end_date: fileForm.querySelector('[name="end_date"]')?.value || '',
            reason: fileForm.querySelector('[name="reason"]')?.value?.trim() || '',
        };
        setButtonLoading(submitBtn, true, 'Submitting…');
        try {
            const { data } = await http.post('/leave/requests', payload);
            toastSuccess(data?.message || 'Leave request submitted.');
            closeModal(fileModal);
            myTable?.reload(true);
            approvalsTable?.reload(true);
        } catch (error) {
            const res = error?.response?.data;
            if (res?.errors) {
                showErrors(fileForm, res.errors);
            }
            toastError(res?.message || 'Unable to submit leave request.');
        } finally {
            setButtonLoading(submitBtn, false);
        }
    });

    decideForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(decideForm);
        const id = decideForm.querySelector('[name="request_id"]')?.value || '';
        const decision = decideForm.querySelector('[name="decision"]')?.value || '';
        const submitBtn = decideForm.querySelector('[type="submit"]');
        const payload = {
            approver_notes: decideForm.querySelector('[name="approver_notes"]')?.value?.trim() || undefined,
        };
        setButtonLoading(submitBtn, true, decision === 'approve' ? 'Approving…' : 'Rejecting…');
        try {
            const { data } = await http.post(`/leave/requests/${id}/${decision}`, payload);
            toastSuccess(data?.message || `Leave request ${decision}d.`);
            closeModal(decideModal);
            approvalsTable?.reload();
            myTable?.reload();
            balancesTable?.reload();
        } catch (error) {
            const res = error?.response?.data;
            if (res?.errors) {
                showErrors(decideForm, res.errors);
            }
            toastError(res?.message || `Unable to ${decision} leave request.`);
        } finally {
            setButtonLoading(submitBtn, false);
        }
    });

    adjustForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(adjustForm);
        const submitBtn = adjustForm.querySelector('[type="submit"]');
        const payload = {
            employee_id: employeeSearch?.getValue() || adjustForm.querySelector('[data-employee-id]')?.value || '',
            leave_type_id: adjustForm.querySelector('[name="leave_type_id"]')?.value || '',
            leave_year: Number(adjustForm.querySelector('[name="leave_year"]')?.value || 0) || undefined,
            amount: adjustForm.querySelector('[name="amount"]')?.value || '',
            reason: adjustForm.querySelector('[name="reason"]')?.value?.trim() || '',
            effective_date: adjustForm.querySelector('[name="effective_date"]')?.value || undefined,
        };
        setButtonLoading(submitBtn, true, 'Saving…');
        try {
            const { data } = await http.post('/leave/balances/adjust', payload);
            toastSuccess(data?.message || 'Leave balance adjusted.');
            closeModal(adjustModal);
            balancesTable?.reload();
        } catch (error) {
            const res = error?.response?.data;
            if (res?.errors) {
                showErrors(adjustForm, res.errors);
            }
            toastError(res?.message || 'Unable to adjust leave balance.');
        } finally {
            setButtonLoading(submitBtn, false);
        }
    });

    accrualForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(accrualForm);
        const yearMonth = accrualForm.querySelector('[name="year_month"]')?.value || '';
        const confirmed = await confirmAction({
            title: 'Run monthly accrual?',
            text: `Credit VL for ${yearMonth}. Already-processed employees for this month will be skipped.`,
            confirmButtonText: 'Run accrual',
        });
        if (!confirmed?.isConfirmed) {
            return;
        }
        const submitBtn = accrualForm.querySelector('[type="submit"]');
        setButtonLoading(submitBtn, true, 'Running…');
        try {
            const { data } = await http.post('/leave/accruals/run', { year_month: yearMonth });
            const result = data?.data || {};
            toastSuccess(
                data?.message
                    || `Accrual done: ${result.accrued ?? 0} credited, ${result.skipped ?? 0} skipped.`,
            );
            closeModal(accrualModal);
            balancesTable?.reload();
        } catch (error) {
            const res = error?.response?.data;
            if (res?.errors) {
                showErrors(accrualForm, res.errors);
            }
            toastError(res?.message || 'Unable to run leave accrual.');
        } finally {
            setButtonLoading(submitBtn, false);
        }
    });
}
