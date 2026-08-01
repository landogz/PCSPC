import http from '../../utils/http';
import { setButtonLoading } from '../../utils/button-loading';
import { toastSuccess, toastError, confirmAction } from '../../utils/toast';
import { openModal, closeModal } from '../../utils/modal';
import { escapeHtml } from '../../utils/server-table';

const RELATIONSHIP_LABELS = {
    spouse: 'Spouse',
    child: 'Child',
    parent: 'Parent',
    sibling: 'Sibling',
    other: 'Other',
};

function clearDependentErrors(form) {
    form.querySelectorAll('[data-error]').forEach((el) => {
        el.textContent = '';
        el.classList.add('hidden');
    });
    form.querySelectorAll('.ui-input.is-invalid, .ui-select.is-invalid').forEach((el) => {
        el.classList.remove('is-invalid');
        el.removeAttribute('aria-invalid');
    });
}

function showDependentErrors(form, errors = {}) {
    Object.entries(errors).forEach(([field, messages]) => {
        const el = form.querySelector(`[data-error="${field}"]`);
        if (el) {
            el.textContent = Array.isArray(messages) ? messages[0] : String(messages);
            el.classList.remove('hidden');
        }
        const input = form.elements.namedItem(field);
        if (input && 'classList' in input) {
            input.classList.add('is-invalid');
            input.setAttribute('aria-invalid', 'true');
        }
    });
}

function relationshipLabel(value) {
    return RELATIONSHIP_LABELS[value] || String(value || '—').replaceAll('_', ' ');
}

/**
 * Nested dependents CRUD for the employee modal.
 */
export function initEmployeeDependents({
    root,
    getEmployeeId,
    canManage,
    onCountChange,
}) {
    const listEl = root.querySelector('[data-dependents-list]');
    const emptyEl = root.querySelector('[data-dependents-empty]');
    const lockedEl = root.querySelector('[data-dependents-locked]');
    const addBtn = root.querySelector('[data-dependent-add]');
    const modal = document.getElementById('dependent-modal');
    const form = document.getElementById('dependent-form');
    if (!listEl || !modal || !form) {
        return {
            reload: async () => {},
            reset: () => {},
            count: () => 0,
        };
    }

    const titleEl = modal.querySelector('[data-modal-title]');
    let editingId = null;
    let items = [];
    let relationships = [
        { code: 'spouse', label: 'Spouse' },
        { code: 'child', label: 'Child' },
        { code: 'parent', label: 'Parent' },
        { code: 'sibling', label: 'Sibling' },
        { code: 'other', label: 'Other' },
    ];
    let genders = [
        { code: 'male', label: 'Male' },
        { code: 'female', label: 'Female' },
        { code: 'other', label: 'Other' },
    ];

    function normalizeOptions(list) {
        if (!Array.isArray(list) || !list.length) {
            return null;
        }
        if (typeof list[0] === 'string') {
            return list.map((code) => ({ code, label: relationshipLabel(code) }));
        }
        return list.map((item) => ({
            code: item.code || item.value,
            label: item.label || relationshipLabel(item.code || item.value),
        }));
    }

    function setRelationships(list) {
        const normalized = normalizeOptions(list);
        if (normalized) {
            relationships = normalized;
        }
        const select = form.elements.namedItem('relationship');
        if (!select) {
            return;
        }
        const current = select.value;
        select.innerHTML = relationships.map((item) => (
            `<option value="${escapeHtml(item.code)}">${escapeHtml(item.label)}</option>`
        )).join('');
        if (relationships.some((item) => item.code === current)) {
            select.value = current;
        }
    }

    function setGenders(list) {
        const normalized = normalizeOptions(list);
        if (normalized) {
            genders = normalized;
        }
        const select = form.elements.namedItem('gender');
        if (!select) {
            return;
        }
        const current = select.value;
        select.innerHTML = `<option value="">— Select —</option>${genders.map((item) => (
            `<option value="${escapeHtml(item.code)}">${escapeHtml(item.label)}</option>`
        )).join('')}`;
        if ([...select.options].some((opt) => opt.value === current)) {
            select.value = current;
        }
    }

    function renderList() {
        onCountChange?.(items.length);

        if (!getEmployeeId()) {
            listEl.innerHTML = '';
            emptyEl?.classList.add('hidden');
            lockedEl?.classList.remove('hidden');
            addBtn?.classList.add('hidden');
            return;
        }

        lockedEl?.classList.add('hidden');
        addBtn?.classList.toggle('hidden', !canManage);

        if (!items.length) {
            listEl.innerHTML = '';
            emptyEl?.classList.remove('hidden');
            return;
        }

        emptyEl?.classList.add('hidden');
        listEl.innerHTML = items.map((row) => {
            const badges = [
                row.is_beneficiary ? '<span class="inline-flex items-center h-6 px-2 rounded-md bg-success-soft text-success text-[11px] font-semibold">Beneficiary</span>' : '',
                row.is_emergency_contact ? '<span class="inline-flex items-center h-6 px-2 rounded-md bg-warning-soft text-heading text-[11px] font-semibold">Emergency</span>' : '',
            ].filter(Boolean).join('');

            const actions = canManage
                ? `<div class="flex flex-wrap gap-2 justify-end">
                    <button type="button" class="inline-flex items-center gap-1 h-9 px-3 rounded-lg border border-border text-sm font-medium hover:bg-subtle" data-dependent-edit="${escapeHtml(row.id)}">
                        <i class="ph ph-pencil-simple"></i> Edit
                    </button>
                    <button type="button" class="inline-flex items-center gap-1 h-9 px-3 rounded-lg border border-danger/30 text-sm font-medium text-danger hover:bg-danger-soft" data-dependent-delete="${escapeHtml(row.id)}">
                        <i class="ph ph-trash"></i> Remove
                    </button>
                   </div>`
                : '';

            return `
                <article class="rounded-xl border border-border bg-surface p-3.5 sm:p-4" data-dependent-id="${escapeHtml(row.id)}">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 space-y-1">
                            <p class="font-semibold text-heading truncate">${escapeHtml(row.full_name)}</p>
                            <p class="text-sm text-text-secondary capitalize">${escapeHtml(relationshipLabel(row.relationship))}${row.birth_date ? ` · Born ${escapeHtml(row.birth_date)}` : ''}</p>
                            ${row.mobile ? `<p class="text-xs text-muted">${escapeHtml(row.mobile)}</p>` : ''}
                            ${badges ? `<div class="flex flex-wrap gap-1.5 pt-1">${badges}</div>` : ''}
                        </div>
                        ${actions}
                    </div>
                </article>
            `;
        }).join('');
    }

    function resetForm() {
        editingId = null;
        form.reset();
        clearDependentErrors(form);
        if (form.is_beneficiary) {
            form.is_beneficiary.checked = false;
        }
        if (form.is_emergency_contact) {
            form.is_emergency_contact.checked = false;
        }
        if (titleEl) {
            titleEl.textContent = 'Add dependent';
        }
    }

    function fillForm(row) {
        editingId = row.id;
        form.first_name.value = row.first_name || '';
        form.middle_name.value = row.middle_name || '';
        form.last_name.value = row.last_name || '';
        form.suffix.value = row.suffix || '';
        form.relationship.value = row.relationship || relationships[0];
        form.birth_date.value = row.birth_date || '';
        form.gender.value = row.gender || '';
        form.mobile.value = row.mobile || '';
        form.notes.value = row.notes || '';
        form.is_beneficiary.checked = Boolean(row.is_beneficiary);
        form.is_emergency_contact.checked = Boolean(row.is_emergency_contact);
        if (titleEl) {
            titleEl.textContent = 'Edit dependent';
        }
    }

    async function reload() {
        const employeeId = getEmployeeId();
        if (!employeeId) {
            items = [];
            renderList();
            return;
        }

        try {
            const { data } = await http.get(`/employees/${employeeId}/dependents`);
            items = data.data?.items || [];
            setRelationships(data.data?.relationships || relationships);
            renderList();
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to load dependents');
            items = [];
            renderList();
        }
    }

    function reset() {
        items = [];
        resetForm();
        closeModal(modal);
        renderList();
    }

    addBtn?.addEventListener('click', () => {
        if (!canManage || !getEmployeeId()) {
            return;
        }
        resetForm();
        setRelationships(relationships);
        openModal(modal);
    });

    listEl.addEventListener('click', async (event) => {
        const editBtn = event.target.closest('[data-dependent-edit]');
        const deleteBtn = event.target.closest('[data-dependent-delete]');

        if (editBtn) {
            const row = items.find((item) => item.id === editBtn.getAttribute('data-dependent-edit'));
            if (!row) {
                return;
            }
            clearDependentErrors(form);
            fillForm(row);
            openModal(modal);
            return;
        }

        if (deleteBtn) {
            const id = deleteBtn.getAttribute('data-dependent-delete');
            const row = items.find((item) => item.id === id);
            if (!row || !getEmployeeId()) {
                return;
            }
            const confirmed = await confirmAction({
                title: 'Remove dependent?',
                text: `Remove ${row.full_name} from this employee record?`,
                confirmButtonText: 'Remove',
            });
            if (!confirmed.isConfirmed) {
                return;
            }
            try {
                await http.delete(`/employees/${getEmployeeId()}/dependents/${id}`);
                toastSuccess('Dependent removed');
                await reload();
            } catch (error) {
                toastError(error.response?.data?.message || 'Unable to remove dependent');
            }
        }
    });

    modal.querySelectorAll('[data-modal-dismiss]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const employeeId = getEmployeeId();
        if (!canManage || !employeeId) {
            return;
        }

        clearDependentErrors(form);
        const payload = {
            first_name: form.first_name.value.trim(),
            middle_name: form.middle_name.value.trim() || null,
            last_name: form.last_name.value.trim(),
            suffix: form.suffix.value.trim() || null,
            relationship: form.relationship.value,
            birth_date: form.birth_date.value || null,
            gender: form.gender.value || null,
            mobile: form.mobile.value.trim() || null,
            notes: form.notes.value.trim() || null,
            is_beneficiary: Boolean(form.is_beneficiary.checked),
            is_emergency_contact: Boolean(form.is_emergency_contact.checked),
        };

        const submit = form.querySelector('[type="submit"]');
        setButtonLoading(submit, true, editingId ? 'Saving…' : 'Adding…');

        try {
            if (editingId) {
                const { data } = await http.put(`/employees/${employeeId}/dependents/${editingId}`, payload);
                toastSuccess(data.message || 'Dependent updated');
            } else {
                const { data } = await http.post(`/employees/${employeeId}/dependents`, payload);
                toastSuccess(data.message || 'Dependent added');
            }
            closeModal(modal);
            await reload();
        } catch (error) {
            const response = error.response?.data;
            showDependentErrors(form, response?.errors || {});
            toastError(response?.message || 'Unable to save dependent');
        } finally {
            setButtonLoading(submit, false);
        }
    });

    renderList();

    return {
        reload,
        reset,
        setRelationships,
        setGenders,
        count: () => items.length,
    };
}
