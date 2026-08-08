/**
 * Overtime module — OT / OT Meal filings + multi-step approvals (P4c).
 */
import http from '../../utils/http';
import { setButtonLoading } from '../../utils/button-loading';
import { toastSuccess, toastError, confirmAction } from '../../utils/toast';
import { createServerTable, escapeHtml, rowActionsCell } from '../../utils/server-table';
import { openModal, closeModal } from '../../utils/modal';

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

function setActiveTab(root, key) {
    root.querySelectorAll('[data-ot-tab]').forEach((btn) => {
        const active = btn.getAttribute('data-ot-tab') === key;
        btn.classList.toggle('border-primary', active);
        btn.classList.toggle('text-primary', active);
        btn.classList.toggle('border-transparent', !active);
        btn.classList.toggle('text-muted', !active);
        btn.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    root.querySelectorAll('[data-ot-panel]').forEach((panel) => {
        panel.classList.toggle('hidden', panel.getAttribute('data-ot-panel') !== key);
    });
}

function statusBadge(status) {
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

export function initOvertimeModule() {
    const root = document.querySelector('[data-module="overtime"]');
    if (!root) {
        return;
    }

    const canFile = root.dataset.canFile === '1';
    const canApprove = root.dataset.canApprove === '1';

    root.querySelectorAll('[data-ot-tab]').forEach((btn) => {
        btn.addEventListener('click', () => setActiveTab(root, btn.getAttribute('data-ot-tab')));
    });

    const myPanel = root.querySelector('[data-spa-module="ot-my-requests"]');
    const approvalsPanel = root.querySelector('[data-spa-module="ot-approvals"]');
    const fileModal = document.getElementById('ot-file-modal');
    const fileForm = document.getElementById('ot-file-form');
    const decideModal = document.getElementById('ot-decide-modal');
    const decideForm = document.getElementById('ot-decide-form');
    const mealNotesWrap = fileForm?.querySelector('[data-ot-meal-notes]');
    const kindSelect = fileForm?.querySelector('[name="kind"]');

    bindDismiss(fileModal);
    bindDismiss(decideModal);

    function toggleMealNotes() {
        const isMeal = kindSelect?.value === 'ot_meal';
        mealNotesWrap?.classList.toggle('hidden', !isMeal);
    }
    kindSelect?.addEventListener('change', toggleMealNotes);
    toggleMealNotes();

    let myTable = null;
    let approvalsTable = null;

    function openDecide(row, decision) {
        if (!decideModal || !decideForm) {
            return;
        }
        clearErrors(decideForm);
        decideForm.reset();
        decideForm.querySelector('[name="id"]').value = row.id;
        decideForm.querySelector('[name="decision"]').value = decision;
        const summary = decideForm.querySelector('[data-ot-decide-summary]');
        if (summary) {
            summary.textContent = `${row.employee?.full_name || 'Employee'} · ${row.kind_label} · ${row.work_date} · ${row.hours}h · ${row.workflow?.current_step_label || '—'}`;
        }
        const title = decideModal.querySelector('[data-modal-title]');
        if (title) {
            title.textContent = decision === 'approve' ? 'Approve overtime' : 'Reject overtime';
        }
        const submitBtn = decideForm.querySelector('[data-ot-decide-submit]');
        if (submitBtn) {
            submitBtn.innerHTML = decision === 'approve'
                ? '<i class="ph ph-check-circle text-base" aria-hidden="true"></i><span>Approve</span>'
                : '<i class="ph ph-x-circle text-base" aria-hidden="true"></i><span>Reject</span>';
        }
        openModal(decideModal);
    }

    async function handleRowAction(action, row) {
        if (action === 'cancel') {
            const confirmed = await confirmAction({
                title: 'Cancel overtime request?',
                text: `${row.kind_label} on ${row.work_date} will be cancelled.`,
                confirmButtonText: 'Cancel request',
            });
            if (!confirmed?.isConfirmed) {
                return;
            }
            try {
                const { data } = await http.post(`/overtime/requests/${row.id}/cancel`);
                toastSuccess(data?.message || 'Cancelled.');
                myTable?.reload();
                approvalsTable?.reload();
            } catch (error) {
                toastError(error?.response?.data?.message || 'Unable to cancel.');
            }
            return;
        }
        if (action === 'approve' || action === 'reject') {
            openDecide(row, action);
        }
    }

    if (canFile && myPanel) {
        myTable = createServerTable({
            root: myPanel,
            endpoint: '/overtime/requests/mine',
            columns: 7,
            perPage: 10,
            extraParams: () => ({
                search: myPanel.querySelector('[data-filter="search"]')?.value?.trim() || '',
                status: myPanel.querySelector('[data-filter="status"]')?.value || '',
                kind: myPanel.querySelector('[data-filter="kind"]')?.value || '',
            }),
            mapRow: (row) => {
                const actions = row.status === 'pending'
                    ? [{ key: 'cancel', label: 'Cancel', danger: true }]
                    : [];
                return `
                    <tr class="hover:bg-subtle/60" data-row-id="${escapeHtml(row.id)}" data-actions='${JSON.stringify(actions)}'>
                        <td class="px-4 py-3 font-semibold text-heading">${escapeHtml(row.kind_label)}</td>
                        <td class="px-4 py-3">${escapeHtml(row.work_date || '—')}</td>
                        <td class="px-4 py-3 text-right tabular-nums">${escapeHtml(row.hours)}</td>
                        <td class="px-4 py-3 text-sm text-text-secondary">${escapeHtml(row.workflow?.current_step_label || '—')}</td>
                        <td class="px-4 py-3 text-sm max-w-[12rem] truncate" title="${escapeHtml(row.reason || '')}">${escapeHtml(row.reason || '—')}</td>
                        <td class="px-4 py-3">${statusBadge(row.status)}</td>
                        ${rowActionsCell(actions)}
                    </tr>`;
            },
            onRowAction: handleRowAction,
        });
        bindSearchAndFilters(myTable, {
            searchInput: myPanel.querySelector('[data-filter="search"]'),
            selects: [
                myPanel.querySelector('[data-filter="status"]'),
                myPanel.querySelector('[data-filter="kind"]'),
            ],
        });
        myPanel.querySelector('[data-action="create"]')?.addEventListener('click', () => {
            clearErrors(fileForm);
            fileForm?.reset();
            toggleMealNotes();
            openModal(fileModal);
        });
        myTable.reload(true);
    }

    if (canApprove && approvalsPanel) {
        approvalsTable = createServerTable({
            root: approvalsPanel,
            endpoint: '/overtime/requests',
            columns: 8,
            perPage: 10,
            extraParams: () => ({
                search: approvalsPanel.querySelector('[data-filter="search"]')?.value?.trim() || '',
                status: approvalsPanel.querySelector('[data-filter="status"]')?.value || '',
                kind: approvalsPanel.querySelector('[data-filter="kind"]')?.value || '',
            }),
            mapRow: (row) => {
                const actions = row.status === 'pending'
                    ? [
                        { key: 'approve', label: 'Approve' },
                        { key: 'reject', label: 'Reject', danger: true },
                    ]
                    : [];
                return `
                    <tr class="hover:bg-subtle/60" data-row-id="${escapeHtml(row.id)}" data-actions='${JSON.stringify(actions)}'>
                        <td class="px-4 py-3 font-semibold text-heading">${escapeHtml(row.employee?.full_name || '—')}</td>
                        <td class="px-4 py-3">${escapeHtml(row.kind_label)}</td>
                        <td class="px-4 py-3">${escapeHtml(row.work_date || '—')}</td>
                        <td class="px-4 py-3 text-right tabular-nums">${escapeHtml(row.hours)}</td>
                        <td class="px-4 py-3 text-sm text-text-secondary">${escapeHtml(row.workflow?.current_step_label || '—')}</td>
                        <td class="px-4 py-3 text-sm max-w-[10rem] truncate" title="${escapeHtml(row.reason || '')}">${escapeHtml(row.reason || '—')}</td>
                        <td class="px-4 py-3">${statusBadge(row.status)}</td>
                        ${rowActionsCell(actions)}
                    </tr>`;
            },
            onRowAction: handleRowAction,
        });
        bindSearchAndFilters(approvalsTable, {
            searchInput: approvalsPanel.querySelector('[data-filter="search"]'),
            selects: [
                approvalsPanel.querySelector('[data-filter="status"]'),
                approvalsPanel.querySelector('[data-filter="kind"]'),
            ],
        });
        approvalsTable.reload(true);
    }

    fileForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(fileForm);
        const submitBtn = fileForm.querySelector('[type="submit"]');
        const kind = fileForm.querySelector('[name="kind"]')?.value || 'ot';
        const payload = {
            kind,
            work_date: fileForm.querySelector('[name="work_date"]')?.value || '',
            hours: fileForm.querySelector('[name="hours"]')?.value || '',
            reason: fileForm.querySelector('[name="reason"]')?.value?.trim() || '',
            meal_notes: kind === 'ot_meal'
                ? (fileForm.querySelector('[name="meal_notes"]')?.value?.trim() || '')
                : null,
        };
        setButtonLoading(submitBtn, true, 'Submitting…');
        try {
            const { data } = await http.post('/overtime/requests', payload);
            toastSuccess(data?.message || 'Overtime submitted.');
            closeModal(fileModal);
            myTable?.reload(true);
            approvalsTable?.reload();
        } catch (error) {
            const res = error?.response?.data;
            if (res?.errors) {
                showErrors(fileForm, res.errors);
            }
            toastError(res?.message || 'Unable to submit overtime.');
        } finally {
            setButtonLoading(submitBtn, false);
        }
    });

    decideForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(decideForm);
        const submitBtn = decideForm.querySelector('[type="submit"]');
        const id = decideForm.querySelector('[name="id"]')?.value;
        const decision = decideForm.querySelector('[name="decision"]')?.value;
        const notes = decideForm.querySelector('[name="notes"]')?.value?.trim() || null;
        if (!id || !decision) {
            return;
        }
        setButtonLoading(submitBtn, true, decision === 'approve' ? 'Approving…' : 'Rejecting…');
        try {
            const { data } = await http.post(`/overtime/requests/${id}/${decision}`, { notes });
            toastSuccess(data?.message || 'Decision saved.');
            closeModal(decideModal);
            myTable?.reload();
            approvalsTable?.reload(true);
        } catch (error) {
            const res = error?.response?.data;
            if (res?.errors) {
                showErrors(decideForm, res.errors);
            }
            toastError(res?.message || 'Unable to save decision.');
        } finally {
            setButtonLoading(submitBtn, false);
        }
    });
}
