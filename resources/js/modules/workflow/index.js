/**
 * Workflow approvals inbox (P4c).
 */
import http from '../../utils/http';
import { setButtonLoading } from '../../utils/button-loading';
import { toastSuccess, toastError } from '../../utils/toast';
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

export function initWorkflowModule() {
    const root = document.querySelector('[data-module="workflow"]');
    if (!root || root.dataset.canApprove !== '1') {
        return;
    }

    const inboxPanel = root.querySelector('[data-spa-module="workflow-inbox"]');
    const defsList = root.querySelector('[data-workflow-definitions-list]');
    const decideModal = document.getElementById('workflow-decide-modal');
    const decideForm = document.getElementById('workflow-decide-form');
    bindDismiss(decideModal);

    async function loadDefinitions() {
        if (!defsList) {
            return;
        }
        try {
            const { data } = await http.get('/workflow/definitions');
            const items = data?.data?.items || [];
            if (!items.length) {
                defsList.innerHTML = '<li class="text-muted">No active workflow definitions.</li>';
                return;
            }
            defsList.innerHTML = items
                .map((def) => {
                    const steps = (def.steps || [])
                        .map((s) => escapeHtml(s.label))
                        .join(' → ');
                    return `<li><span class="font-semibold text-heading">${escapeHtml(def.name)}</span>
                        <span class="text-muted">(${escapeHtml(def.code)})</span>
                        <span class="block sm:inline sm:ml-1">${steps || '—'}</span></li>`;
                })
                .join('');
        } catch (error) {
            defsList.innerHTML = '<li class="text-danger">Failed to load definitions.</li>';
            toastError(error?.response?.data?.message || 'Unable to load workflow definitions.');
        }
    }

    function openDecide(row, decision) {
        if (!decideModal || !decideForm) {
            return;
        }
        clearErrors(decideForm);
        decideForm.reset();
        decideForm.querySelector('[name="id"]').value = row.id;
        decideForm.querySelector('[name="decision"]').value = decision;
        const summary = decideForm.querySelector('[data-workflow-decide-summary]');
        if (summary) {
            summary.textContent = `${row.subject?.label || 'Request'} · ${row.definition?.name || ''} · ${row.current_step_label || ''}`;
        }
        const title = decideModal.querySelector('[data-modal-title]');
        if (title) {
            title.textContent = decision === 'approve' ? 'Approve step' : 'Reject request';
        }
        const submitBtn = decideForm.querySelector('[data-workflow-decide-submit]');
        if (submitBtn) {
            submitBtn.innerHTML = decision === 'approve'
                ? '<i class="ph ph-check-circle text-base" aria-hidden="true"></i><span>Approve</span>'
                : '<i class="ph ph-x-circle text-base" aria-hidden="true"></i><span>Reject</span>';
        }
        openModal(decideModal);
    }

    loadDefinitions();

    if (!inboxPanel) {
        return;
    }

    const table = createServerTable({
        root: inboxPanel,
        endpoint: '/workflow/inbox',
        columns: 6,
        perPage: 10,
        extraParams: () => ({
            search: inboxPanel.querySelector('[data-filter="search"]')?.value?.trim() || '',
        }),
        mapRow: (row) => {
            const actions = [
                { key: 'approve', label: 'Approve' },
                { key: 'reject', label: 'Reject', danger: true },
            ];
            return `
                <tr class="hover:bg-subtle/60" data-row-id="${escapeHtml(row.id)}" data-actions='${JSON.stringify(actions)}'>
                    <td class="px-4 py-3">
                        <div class="font-semibold text-heading">${escapeHtml(row.subject?.label || '—')}</div>
                        <div class="text-xs text-muted">${escapeHtml(row.subject?.employee || '')}</div>
                    </td>
                    <td class="px-4 py-3 text-sm">${escapeHtml(row.definition?.name || '—')}</td>
                    <td class="px-4 py-3 text-sm">${escapeHtml(row.current_step_label || '—')}</td>
                    <td class="px-4 py-3 text-sm">${escapeHtml(row.starter?.name || '—')}</td>
                    <td class="px-4 py-3">${statusBadge(row.status)}</td>
                    ${rowActionsCell(actions)}
                </tr>`;
        },
        onRowAction: (action, row) => {
            if (action === 'approve' || action === 'reject') {
                openDecide(row, action);
            }
        },
    });

    let searchTimer = null;
    inboxPanel.querySelector('[data-filter="search"]')?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => table.reload(true), 300);
    });

    table.reload(true);

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
            const { data } = await http.post(`/workflow/instances/${id}/${decision}`, { notes });
            toastSuccess(data?.message || 'Decision saved.');
            closeModal(decideModal);
            table.reload(true);
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
