import http from '../../utils/http';
import { setButtonLoading } from '../../utils/button-loading';
import { toastSuccess, toastError, confirmAction } from '../../utils/toast';
import { openModal, closeModal } from '../../utils/modal';

function clearFormErrors(form) {
    form.querySelectorAll('[data-error]').forEach((el) => {
        el.textContent = '';
        el.classList.add('hidden');
    });
    form.querySelectorAll('.ui-input.is-invalid, .ui-select.is-invalid').forEach((el) => {
        el.classList.remove('is-invalid');
        el.removeAttribute('aria-invalid');
    });
}

function showFormErrors(form, errors = {}) {
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

/**
 * Shared nested CRUD list + modal for employee child records.
 */
export function initNestedEmployeeRecords({
    root,
    listSelector,
    emptySelector,
    lockedSelector,
    addSelector,
    modalId,
    formId,
    collectionPath,
    itemKey,
    getEmployeeId,
    canManage,
    onCountChange,
    buildPayload,
    fillForm,
    renderItem,
    createTitle = 'Add record',
    editTitle = 'Edit record',
    deleteTitle = 'Remove record?',
    deleteText = (row) => `Remove this record?`,
    afterLoad = null,
    resetExtras = null,
}) {
    const listEl = root.querySelector(listSelector);
    const emptyEl = root.querySelector(emptySelector);
    const lockedEl = root.querySelector(lockedSelector);
    const addBtn = root.querySelector(addSelector);
    const modal = document.getElementById(modalId);
    const form = document.getElementById(formId);

    if (!listEl || !modal || !form) {
        return {
            reload: async () => {},
            reset: () => {},
            count: () => 0,
            setMeta: () => {},
        };
    }

    const titleEl = modal.querySelector('[data-modal-title]');
    let editingId = null;
    let items = [];
    let meta = {};

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
        listEl.innerHTML = items.map((row) => renderItem(row, canManage)).join('');
    }

    function resetForm() {
        editingId = null;
        form.reset();
        clearFormErrors(form);
        resetExtras?.(form);
        if (titleEl) {
            titleEl.textContent = createTitle;
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
            const { data } = await http.get(`/employees/${employeeId}/${collectionPath}`);
            items = data.data?.items || [];
            meta = data.data || meta;
            afterLoad?.(meta, form);
            renderList();
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to load records');
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
        afterLoad?.(meta, form);
        openModal(modal);
    });

    listEl.addEventListener('click', async (event) => {
        const editBtn = event.target.closest('[data-nested-edit]');
        const deleteBtn = event.target.closest('[data-nested-delete]');

        if (editBtn) {
            const row = items.find((item) => item.id === editBtn.getAttribute('data-nested-edit'));
            if (!row) {
                return;
            }
            clearFormErrors(form);
            editingId = row.id;
            fillForm(form, row);
            if (titleEl) {
                titleEl.textContent = editTitle;
            }
            openModal(modal);
            return;
        }

        if (deleteBtn) {
            const id = deleteBtn.getAttribute('data-nested-delete');
            const row = items.find((item) => item.id === id);
            if (!row || !getEmployeeId()) {
                return;
            }
            const confirmed = await confirmAction({
                title: deleteTitle,
                text: deleteText(row),
                confirmButtonText: 'Remove',
            });
            if (!confirmed.isConfirmed) {
                return;
            }
            try {
                await http.delete(`/employees/${getEmployeeId()}/${collectionPath}/${id}`);
                toastSuccess('Record removed');
                await reload();
            } catch (error) {
                toastError(error.response?.data?.message || 'Unable to remove record');
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

        clearFormErrors(form);
        const payload = buildPayload(form);
        const submit = form.querySelector('[type="submit"]');
        setButtonLoading(submit, true, editingId ? 'Saving…' : 'Adding…');

        try {
            if (editingId) {
                const { data } = await http.put(`/employees/${employeeId}/${collectionPath}/${editingId}`, payload);
                toastSuccess(data.message || 'Record updated');
            } else {
                const { data } = await http.post(`/employees/${employeeId}/${collectionPath}`, payload);
                toastSuccess(data.message || 'Record added');
            }
            closeModal(modal);
            await reload();
        } catch (error) {
            const response = error.response?.data;
            showFormErrors(form, response?.errors || {});
            toastError(response?.message || 'Unable to save record');
        } finally {
            setButtonLoading(submit, false);
        }
    });

    renderList();

    return {
        reload,
        reset,
        count: () => items.length,
        setMeta: (next) => {
            meta = { ...meta, ...next };
            afterLoad?.(meta, form);
        },
    };
}

export { clearFormErrors, showFormErrors };
