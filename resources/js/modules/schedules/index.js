import http from '../../utils/http';
import { setButtonLoading } from '../../utils/button-loading';
import { toastSuccess, toastError, confirmAction } from '../../utils/toast';
import { createServerTable, escapeHtml, statusBadge, rowActionsCell, cardActions } from '../../utils/server-table';
import { openModal, closeModal } from '../../utils/modal';
import { initEmployeeSearch } from '../../utils/employee-search';
import { openSchedulePrintWindow } from './print';

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

function periodBadge(period) {
    if (period === 'upcoming') {
        return '<span class="inline-flex items-center h-6 px-2 rounded-md bg-warning-soft text-heading text-[11px] font-semibold">Upcoming</span>';
    }
    if (period === 'ended') {
        return '<span class="inline-flex items-center h-6 px-2 rounded-md bg-subtle text-muted text-[11px] font-semibold">Ended</span>';
    }
    return '<span class="inline-flex items-center h-6 px-2 rounded-md bg-success-soft text-success text-[11px] font-semibold">Current</span>';
}

function assigneeMarkup(row) {
    if (row.assignee_type === 'department' && row.department) {
        return `
            <div class="min-w-0">
                <p class="font-semibold text-heading truncate">${escapeHtml(row.department.name)}</p>
                <p class="text-xs text-muted">Department · ${escapeHtml(row.department.code || '')}</p>
            </div>
        `;
    }
    if (row.employee) {
        return `
            <div class="min-w-0">
                <p class="font-semibold text-heading truncate">${escapeHtml(row.employee.name || '—')}</p>
                <p class="text-xs text-muted">Employee · ${escapeHtml(row.employee.employee_number || '')}</p>
            </div>
        `;
    }
    return '<span class="text-muted">—</span>';
}

export function initSchedulesModule() {
    const root = document.querySelector('[data-module="schedules"]');
    if (!root) {
        return;
    }

    const panel = root.querySelector('[data-spa-module="schedules-table"]');
    const modal = document.getElementById('schedule-modal');
    const printModal = document.getElementById('schedule-print-modal');
    const form = document.getElementById('schedule-form');
    const printForm = document.getElementById('schedule-print-form');
    const title = modal?.querySelector('[data-modal-title]');
    const searchInput = panel?.querySelector('[data-filter="search"]');
    const shiftFilter = panel?.querySelector('[data-filter="shift_id"]');
    const assigneeFilter = panel?.querySelector('[data-filter="assignee_type"]');
    const statusFilter = panel?.querySelector('[data-filter="status"]');
    const effectiveFilter = panel?.querySelector('[data-filter="effective"]');
    const shiftSelect = form?.querySelector('[data-shift-select]');
    const departmentSelect = form?.querySelector('[data-department-select]');
    const printDepartmentSelect = printForm?.querySelector('[data-print-department-select]');
    const employeeWrap = form?.querySelector('[data-assignee-employee]');
    const departmentWrap = form?.querySelector('[data-assignee-department]');
    const printEmployeeWrap = printForm?.querySelector('[data-print-employee]');
    const printDepartmentWrap = printForm?.querySelector('[data-print-department]');
    let editingId = null;
    let meta = { shifts: [], departments: [] };

    if (!panel || !modal || !form) {
        return;
    }

    const employeeSearch = initEmployeeSearch(form.querySelector('[data-employee-search-root]'));
    const printEmployeeSearch = printForm
        ? initEmployeeSearch(printForm.querySelector('[data-employee-search-root]'))
        : null;

    function fillSelect(select, items, { blankLabel = 'All', preferBlankLabel = false } = {}) {
        if (!select) {
            return;
        }
        const current = select.value;
        const isFilter = select.hasAttribute('data-filter') || preferBlankLabel;
        select.innerHTML = isFilter
            ? `<option value="">${escapeHtml(blankLabel)}</option>`
            : '<option value="">— Select —</option>';
        items.forEach((item) => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.label || `${item.code} — ${item.name}`;
            select.appendChild(option);
        });
        if ([...select.options].some((opt) => opt.value === current)) {
            select.value = current;
        }
    }

    function syncAssigneeUi() {
        const type = form.querySelector('input[name="assignee_type"]:checked')?.value || 'employee';
        employeeWrap?.classList.toggle('hidden', type !== 'employee');
        departmentWrap?.classList.toggle('hidden', type !== 'department');
        if (departmentSelect) {
            departmentSelect.required = type === 'department';
        }
    }

    function syncPrintScopeUi() {
        if (!printForm) {
            return;
        }
        const scope = printForm.querySelector('input[name="scope"]:checked')?.value || 'employee';
        printEmployeeWrap?.classList.toggle('hidden', scope !== 'employee');
        printDepartmentWrap?.classList.toggle('hidden', scope !== 'department');
    }

    function openPrintModal() {
        if (!printModal || !printForm) {
            return;
        }
        printForm.reset();
        printForm.querySelector('input[name="scope"][value="employee"]').checked = true;
        printForm.effective.value = 'current';
        printForm.include_related.checked = true;
        printEmployeeSearch?.clear();
        if (printDepartmentSelect) {
            printDepartmentSelect.value = '';
        }
        syncPrintScopeUi();
        openModal(printModal);
    }

    function setEffectiveTab(value) {
        if (effectiveFilter) {
            effectiveFilter.value = value || '';
        }
        root.querySelectorAll('[data-effective-tab]').forEach((btn) => {
            const active = (btn.dataset.effectiveTab || '') === (value || '');
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
            btn.classList.toggle('bg-primary-soft', active);
            btn.classList.toggle('text-primary', active);
            btn.classList.toggle('bg-surface', !active);
            btn.classList.toggle('text-heading', !active);
        });
    }

    function selectedDays() {
        return [...form.querySelectorAll('input[name="days_of_week[]"]:checked')].map((el) => Number(el.value));
    }

    function setSelectedDays(days) {
        const set = new Set((days || []).map(Number));
        form.querySelectorAll('input[name="days_of_week[]"]').forEach((el) => {
            el.checked = set.size ? set.has(Number(el.value)) : [1, 2, 3, 4, 5].includes(Number(el.value));
        });
        if (Array.isArray(days) && days.length === 0) {
            form.querySelectorAll('input[name="days_of_week[]"]').forEach((el) => {
                el.checked = false;
            });
        }
    }

    const table = createServerTable({
        root: panel,
        endpoint: '/schedules',
        columns: 6,
        perPage: 10,
        extraParams: () => ({
            search: searchInput?.value?.trim() || '',
            shift_id: shiftFilter?.value || '',
            assignee_type: assigneeFilter?.value || '',
            status: statusFilter?.value || '',
            effective: effectiveFilter?.value || '',
        }),
        mapRow: (row) => {
            const actions = [
                { key: 'edit', label: 'Edit' },
                { key: 'delete', label: 'Delete', danger: true },
            ];
            const effective = `${escapeHtml(row.effective_from || '—')}${row.effective_to ? ` → ${escapeHtml(row.effective_to)}` : ' → open'}`;

            return `
                <tr class="hover:bg-subtle/60" data-row-id="${escapeHtml(row.id)}" data-actions='${JSON.stringify(actions)}'>
                    <td class="px-4 py-3">${assigneeMarkup(row)}</td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-heading">${escapeHtml(row.shift?.name || '—')}</p>
                        <p class="text-xs text-muted">${escapeHtml(row.shift?.code || '')} · ${escapeHtml(row.shift?.time_in || '')}–${escapeHtml(row.shift?.time_out || '')}</p>
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-sm text-heading">${effective}</p>
                        <div class="mt-1">${periodBadge(row.period)}</div>
                    </td>
                    <td class="px-4 py-3 text-text-secondary">${escapeHtml(row.days_label || 'Every day')}</td>
                    <td class="px-4 py-3">${statusBadge(row.is_active)}</td>
                    ${rowActionsCell(actions)}
                </tr>
            `;
        },
        mapCard: (row) => {
            const actions = [
                { key: 'edit', label: 'Edit' },
                { key: 'delete', label: 'Delete', danger: true },
            ];
            return `
                <article class="rounded-2xl border border-border bg-surface p-4 flex flex-col gap-3 hover:border-primary/30 transition-colors" data-row-id="${escapeHtml(row.id)}" data-actions='${JSON.stringify(actions)}'>
                    <div class="flex items-start justify-between gap-2">
                        ${assigneeMarkup(row)}
                        ${periodBadge(row.period)}
                    </div>
                    <div class="text-sm space-y-1">
                        <p class="font-medium text-heading">${escapeHtml(row.shift?.label || row.shift?.name || '—')}</p>
                        <p class="text-xs text-muted">${escapeHtml(row.effective_from || '—')}${row.effective_to ? ` → ${escapeHtml(row.effective_to)}` : ' → open'} · ${escapeHtml(row.days_label || 'Every day')}</p>
                        <div>${statusBadge(row.is_active)}</div>
                    </div>
                    ${cardActions(actions)}
                </article>
            `;
        },
        onRowAction: async (action, row) => {
            if (action === 'edit') {
                openEdit(row);
                return;
            }
            if (action === 'delete') {
                const confirmed = await confirmAction({
                    title: 'Delete schedule?',
                    text: 'This assignment will be removed. Timekeeping will stop using it.',
                    confirmButtonText: 'Delete',
                });
                if (!confirmed.isConfirmed) {
                    return;
                }
                try {
                    const { data } = await http.delete(`/schedules/${row.id}`);
                    toastSuccess(data.message || 'Schedule deleted');
                    table.reload();
                } catch (error) {
                    toastError(error.response?.data?.message || 'Unable to delete schedule');
                }
            }
        },
    });

    function resetForm() {
        form.reset();
        form.id.value = '';
        editingId = null;
        clearErrors(form);
        form.querySelector('input[name="assignee_type"][value="employee"]').checked = true;
        form.is_active.checked = true;
        form.effective_from.value = new Date().toISOString().slice(0, 10);
        setSelectedDays([1, 2, 3, 4, 5]);
        employeeSearch?.clear();
        syncAssigneeUi();
    }

    function openCreate() {
        resetForm();
        if (title) {
            title.textContent = 'Assign schedule';
        }
        openModal(modal);
    }

    function openEdit(row) {
        resetForm();
        editingId = row.id;
        if (title) {
            title.textContent = 'Edit schedule';
        }
        form.id.value = row.id;
        if (shiftSelect && row.shift?.id) {
            shiftSelect.value = row.shift.id;
        }
        const type = row.assignee_type || 'employee';
        const typeRadio = form.querySelector(`input[name="assignee_type"][value="${type}"]`);
        if (typeRadio) {
            typeRadio.checked = true;
        }
        syncAssigneeUi();
        if (type === 'employee' && row.employee?.id) {
            employeeSearch?.setSelection({
                id: row.employee.id,
                employee_number: row.employee.employee_number,
                full_name: row.employee.name,
                email: row.employee.email,
                label: `${row.employee.employee_number || ''} — ${row.employee.name || ''}`.trim(),
            });
        }
        if (type === 'department' && row.department?.id && departmentSelect) {
            departmentSelect.value = row.department.id;
        }
        form.effective_from.value = row.effective_from || '';
        form.effective_to.value = row.effective_to || '';
        form.notes.value = row.notes || '';
        form.is_active.checked = !!row.is_active;
        setSelectedDays(row.days_of_week || []);
        openModal(modal);
    }

    panel.querySelector('[data-action="create"]')?.addEventListener('click', openCreate);
    root.querySelector('[data-action="print-schedules"]')?.addEventListener('click', openPrintModal);
    modal.querySelectorAll('[data-modal-dismiss]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });
    printModal?.querySelectorAll('[data-modal-dismiss]').forEach((el) => {
        el.addEventListener('click', () => closeModal(printModal));
    });

    form.querySelectorAll('[data-assignee-type]').forEach((el) => {
        el.addEventListener('change', syncAssigneeUi);
    });
    printForm?.querySelectorAll('[data-print-scope]').forEach((el) => {
        el.addEventListener('change', syncPrintScopeUi);
    });

    printForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const scope = printForm.querySelector('input[name="scope"]:checked')?.value || 'employee';
        const params = {
            scope,
            effective: printForm.effective.value || 'current',
            include_related: printForm.include_related.checked ? 1 : 0,
        };
        if (scope === 'employee') {
            const employeeId = printEmployeeSearch?.getValue() || '';
            if (employeeId) {
                params.employee_id = employeeId;
            }
        } else {
            const departmentId = printForm.department_id.value || '';
            if (departmentId) {
                params.department_id = departmentId;
            }
        }

        const submit = printForm.querySelector('[type="submit"]');
        setButtonLoading(submit, true, 'Preparing…');
        try {
            const { data } = await http.get('/schedules/print', { params });
            const report = data?.data;
            if (!report) {
                toastError('Unable to prepare print report');
                return;
            }
            openSchedulePrintWindow(report);
            closeModal(printModal);
            if (!(report.groups || []).length) {
                toastError('No schedules matched this print selection');
            } else {
                toastSuccess('Print preview opened');
            }
        } catch (error) {
            toastError(error.response?.data?.message || error.message || 'Unable to print schedules');
        } finally {
            setButtonLoading(submit, false);
        }
    });

    root.querySelectorAll('[data-effective-tab]').forEach((btn) => {
        btn.addEventListener('click', () => {
            setEffectiveTab(btn.dataset.effectiveTab || '');
            table.reload(true);
        });
    });

    let searchTimer = null;
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => table.reload(true), 300);
    });
    [shiftFilter, assigneeFilter, statusFilter].forEach((el) => {
        el?.addEventListener('change', () => table.reload(true));
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(form);

        const assigneeType = form.querySelector('input[name="assignee_type"]:checked')?.value || 'employee';
        const payload = {
            shift_id: form.shift_id.value,
            assignee_type: assigneeType,
            employee_id: assigneeType === 'employee' ? (employeeSearch?.getValue() || null) : null,
            department_id: assigneeType === 'department' ? (form.department_id.value || null) : null,
            effective_from: form.effective_from.value,
            effective_to: form.effective_to.value || null,
            days_of_week: selectedDays(),
            notes: form.notes.value.trim() || null,
            is_active: form.is_active.checked,
        };

        if (assigneeType === 'employee' && !payload.employee_id) {
            showErrors(form, { employee_id: ['Please select an employee.'] });
            toastError('Please select an employee.');
            return;
        }

        const submit = form.querySelector('[type="submit"]');
        setButtonLoading(submit, true, 'Saving…');
        try {
            const { data } = editingId
                ? await http.put(`/schedules/${editingId}`, payload)
                : await http.post('/schedules', payload);

            toastSuccess(data.message || 'Schedule saved');
            closeModal(modal);
            table.reload(true);
        } catch (error) {
            const response = error.response?.data;
            showErrors(form, response?.errors || {});
            toastError(response?.message || 'Unable to save schedule');
        } finally {
            setButtonLoading(submit, false);
        }
    });

    (async () => {
        try {
            const { data } = await http.get('/schedules/meta');
            meta = data?.data || meta;
            fillSelect(shiftFilter, meta.shifts || [], { blankLabel: 'All shifts' });
            fillSelect(shiftSelect, meta.shifts || []);
            fillSelect(departmentSelect, meta.departments || []);
            fillSelect(printDepartmentSelect, meta.departments || [], {
                blankLabel: 'All departments with schedules',
                preferBlankLabel: true,
            });
        } catch {
            toastError('Unable to load schedule options');
        }
        setEffectiveTab('');
        syncAssigneeUi();
        table.reload(true);
    })();
}
