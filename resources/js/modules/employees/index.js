import http from '../../utils/http';
import { setButtonLoading } from '../../utils/button-loading';
import { toastSuccess, toastError, confirmAction } from '../../utils/toast';
import { createServerTable, escapeHtml, rowActionsCell } from '../../utils/server-table';
import { openModal, closeModal } from '../../utils/modal';
import { avatarInitial, avatarMarkup } from '../../utils/avatar';
import { fillSidebarUser } from '../layout';

const FIELD_KEYS = [
    'employee_number',
    'first_name',
    'middle_name',
    'last_name',
    'suffix',
    'email',
    'mobile',
    'department_id',
    'position_title',
    'employment_status',
    'date_hired',
    'date_regularized',
    'date_separated',
    'birth_date',
    'gender',
    'civil_status',
    'nationality',
    'address_line',
    'city',
    'province',
    'zip_code',
    'tin',
    'sss_number',
    'philhealth_number',
    'pagibig_number',
];

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

function employmentBadge(status) {
    const map = {
        active: 'bg-success-soft text-success',
        inactive: 'bg-subtle text-muted',
        separated: 'bg-danger-soft text-danger',
        on_leave: 'bg-warning-soft text-heading',
    };
    const classes = map[status] || 'bg-subtle text-muted';
    const label = String(status || '—').replaceAll('_', ' ');

    return `<span class="inline-flex items-center h-7 px-2.5 rounded-lg ${classes} text-xs font-semibold capitalize">${escapeHtml(label)}</span>`;
}

function emptyToNull(value) {
    const trimmed = String(value ?? '').trim();

    return trimmed === '' ? null : trimmed;
}

export function initEmployeesModule() {
    const root = document.querySelector('[data-module="employees"]');
    if (!root) {
        return;
    }

    const canManage = root.dataset.canManage === '1';
    const panel = root.querySelector('[data-spa-module="employees-table"]');
    const modal = document.getElementById('employee-modal');
    const form = document.getElementById('employee-form');
    const title = modal.querySelector('[data-modal-title]');
    const searchInput = panel.querySelector('[data-filter="search"]');
    const departmentFilter = panel.querySelector('[data-filter="department"]');
    const statusFilter = panel.querySelector('[data-filter="status"]');
    const tempBanner = form.querySelector('[data-temp-password-banner]');
    const tempPasswordEl = form.querySelector('[data-temp-password]');
    const linkedUserEl = form.querySelector('[data-linked-user]');
    let editingId = null;
    let meta = { departments: [], statuses: [] };

    const table = createServerTable({
        root: panel,
        endpoint: '/employees',
        columns: 7,
        perPage: 10,
        extraParams: () => ({
            search: searchInput?.value?.trim() || '',
            department: departmentFilter?.value || '',
            status: statusFilter?.value || '',
        }),
        mapRow: (row) => {
            const actions = [{ key: 'view', label: canManage ? 'Edit' : 'View' }];
            if (canManage) {
                if (row.employment_status === 'active' || row.employment_status === 'on_leave') {
                    actions.push({ key: 'deactivate', label: 'Deactivate', danger: true });
                }
                if (!row.user) {
                    actions.push({ key: 'delete', label: 'Delete', danger: true });
                }
            }

            const login = row.user
                ? `<div class="text-sm text-heading">${escapeHtml(row.user.email)}</div>
                   <div class="text-[11px] text-muted">${row.user.is_active ? 'Active login' : 'Inactive login'}</div>`
                : '<span class="text-sm text-muted">Not linked</span>';

            return `
                <tr class="hover:bg-subtle/60" data-row-id="${escapeHtml(row.id)}" data-actions='${JSON.stringify(actions)}'>
                    <td class="px-4 py-3 font-semibold text-heading whitespace-nowrap">${escapeHtml(row.employee_number)}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3 min-w-0">
                            ${avatarMarkup({
                                url: row.photo_url,
                                name: row.full_name,
                                sizeClass: 'w-9 h-9',
                                textClass: 'text-xs',
                            })}
                            <div class="min-w-0">
                                <div class="font-medium text-heading truncate">${escapeHtml(row.full_name)}</div>
                                <div class="text-xs text-muted truncate">${escapeHtml(row.email || '')}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-text-secondary">${escapeHtml(row.department?.name || '—')}</td>
                    <td class="px-4 py-3 text-text-secondary">${escapeHtml(row.position_title || '—')}</td>
                    <td class="px-4 py-3">${employmentBadge(row.employment_status)}</td>
                    <td class="px-4 py-3">${login}</td>
                    ${rowActionsCell(actions)}
                </tr>
            `;
        },
        onRowAction: async (action, row) => {
            if (action === 'view') {
                await openEdit(row.id);
                return;
            }
            if (action === 'deactivate') {
                const confirmed = await confirmAction({
                    title: 'Deactivate employee?',
                    text: `${row.full_name} will be set inactive and their login disabled.`,
                    confirmButtonText: 'Deactivate',
                });
                if (!confirmed.isConfirmed) {
                    return;
                }
                try {
                    const { data } = await http.post(`/employees/${row.id}/deactivate`);
                    toastSuccess(data.message || 'Employee deactivated');
                    table.reload();
                } catch (error) {
                    toastError(error.response?.data?.message || 'Unable to deactivate employee');
                }
                return;
            }
            if (action === 'delete') {
                const confirmed = await confirmAction({
                    title: 'Delete employee?',
                    text: `${row.full_name} (${row.employee_number}) will be removed.`,
                    confirmButtonText: 'Delete',
                });
                if (!confirmed.isConfirmed) {
                    return;
                }
                try {
                    const { data } = await http.delete(`/employees/${row.id}`);
                    toastSuccess(data.message || 'Employee deleted');
                    table.reload();
                } catch (error) {
                    toastError(error.response?.data?.message || 'Unable to delete employee');
                }
            }
        },
    });

    function hideTempPassword() {
        tempBanner?.classList.add('hidden');
        if (tempPasswordEl) {
            tempPasswordEl.textContent = '';
        }
    }

    function showTempPassword(password) {
        if (!password || !tempBanner || !tempPasswordEl) {
            return;
        }
        tempPasswordEl.textContent = password;
        tempBanner.classList.remove('hidden');
    }

    function setLinkedUser(user) {
        if (!linkedUserEl) {
            return;
        }
        if (!user) {
            linkedUserEl.textContent = 'No linked login yet.';
            return;
        }
        const roles = Array.isArray(user.roles) ? user.roles.join(', ') : 'employee';
        linkedUserEl.textContent = `Linked: ${user.email} · Roles: ${roles}${user.is_active ? '' : ' · Inactive'}`;
    }

    function populateMetaSelects() {
        const deptSelect = form.department_id;
        const currentDept = deptSelect.value;
        deptSelect.innerHTML = '<option value="">— Select —</option>';
        meta.departments.forEach((department) => {
            const option = document.createElement('option');
            option.value = department.id;
            option.textContent = `${department.code} — ${department.name}`;
            deptSelect.appendChild(option);
        });
        deptSelect.value = currentDept;

        const statusSelect = form.employment_status;
        const currentStatus = statusSelect.value || 'active';
        statusSelect.innerHTML = '';
        meta.statuses.forEach((status) => {
            const option = document.createElement('option');
            option.value = status;
            option.textContent = status.replaceAll('_', ' ');
            statusSelect.appendChild(option);
        });
        statusSelect.value = currentStatus;

        if (departmentFilter) {
            const current = departmentFilter.value;
            departmentFilter.innerHTML = '<option value="">All departments</option>';
            meta.departments.forEach((department) => {
                const option = document.createElement('option');
                option.value = department.id;
                option.textContent = department.name;
                departmentFilter.appendChild(option);
            });
            departmentFilter.value = current;
        }

        if (statusFilter) {
            const current = statusFilter.value;
            statusFilter.innerHTML = '<option value="">All status</option>';
            meta.statuses.forEach((status) => {
                const option = document.createElement('option');
                option.value = status;
                option.textContent = status.replaceAll('_', ' ');
                statusFilter.appendChild(option);
            });
            statusFilter.value = current;
        }
    }

    let currentPhotoUrl = null;

    function setPhotoPreview(url, name = '') {
        const preview = form.querySelector('[data-photo-preview]');
        const img = form.querySelector('[data-photo-preview-img]');
        const initial = form.querySelector('[data-photo-preview-initial]');
        if (!preview || !img || !initial) {
            return;
        }

        initial.textContent = avatarInitial(name || form.first_name?.value, form.email?.value);

        if (url) {
            img.src = url;
            img.classList.remove('hidden');
            initial.classList.add('hidden');
            preview.classList.remove('bg-primary', 'text-white');
            return;
        }

        img.removeAttribute('src');
        img.classList.add('hidden');
        initial.classList.remove('hidden');
        preview.classList.add('bg-primary', 'text-white');
    }

    function resetForm() {
        form.reset();
        form.id.value = '';
        editingId = null;
        currentPhotoUrl = null;
        clearErrors(form);
        hideTempPassword();
        setLinkedUser(null);
        setPhotoPreview(null);
        form.nationality.value = 'Filipino';
        form.employment_status.value = 'active';
        if (form.remove_photo) {
            form.remove_photo.checked = false;
        }
        const submit = form.querySelector('[type="submit"]');
        if (submit) {
            submit.classList.toggle('hidden', !canManage);
            submit.disabled = !canManage;
        }
        FIELD_KEYS.forEach((key) => {
            const field = form.elements.namedItem(key);
            if (field && 'disabled' in field) {
                field.disabled = !canManage;
            }
        });
        if (form.photo) {
            form.photo.disabled = !canManage;
        }
        if (form.remove_photo) {
            form.remove_photo.disabled = !canManage;
        }
    }

    function fillForm(employee) {
        form.id.value = employee.id || '';
        FIELD_KEYS.forEach((key) => {
            const field = form.elements.namedItem(key);
            if (!field) {
                return;
            }
            const value = employee[key];
            field.value = value == null ? '' : value;
        });
        currentPhotoUrl = employee.photo_url || null;
        setPhotoPreview(currentPhotoUrl, employee.full_name);
        if (form.remove_photo) {
            form.remove_photo.checked = false;
        }
        setLinkedUser(employee.user || null);
    }

    function openCreate() {
        if (!canManage) {
            return;
        }
        resetForm();
        title.textContent = 'Add employee';
        openModal(modal);
    }

    async function openEdit(id) {
        resetForm();
        editingId = id;
        title.textContent = canManage ? 'Edit employee' : 'View employee';

        try {
            const { data } = await http.get(`/employees/${id}`);
            fillForm(data.data.employee);
            openModal(modal);
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to load employee');
        }
    }

    function buildFormData() {
        const formData = new FormData();
        FIELD_KEYS.forEach((key) => {
            const field = form.elements.namedItem(key);
            if (!field) {
                return;
            }
            if (key === 'employee_number' || key === 'first_name' || key === 'last_name' || key === 'email' || key === 'employment_status') {
                formData.append(key, String(field.value || '').trim());
                return;
            }
            const value = emptyToNull(field.value);
            formData.append(key, value == null ? '' : value);
        });

        const file = form.photo?.files?.[0];
        if (file) {
            formData.append('photo', file);
        }
        if (form.remove_photo?.checked) {
            formData.append('remove_photo', '1');
        }

        return formData;
    }

    async function refreshCurrentUserAvatar() {
        try {
            const { data } = await http.get('/auth/me');
            if (data?.data?.user) {
                fillSidebarUser(data.data.user);
            }
        } catch {
            // Ignore — layout will refresh on next navigation.
        }
    }

    form.photo?.addEventListener('change', () => {
        const file = form.photo.files?.[0];
        if (!file) {
            setPhotoPreview(currentPhotoUrl, `${form.first_name.value} ${form.last_name.value}`.trim());
            return;
        }
        if (form.remove_photo) {
            form.remove_photo.checked = false;
        }
        const objectUrl = URL.createObjectURL(file);
        setPhotoPreview(objectUrl, `${form.first_name.value} ${form.last_name.value}`.trim());
    });

    form.remove_photo?.addEventListener('change', () => {
        if (form.remove_photo.checked) {
            if (form.photo) {
                form.photo.value = '';
            }
            setPhotoPreview(null, `${form.first_name.value} ${form.last_name.value}`.trim());
            return;
        }
        setPhotoPreview(currentPhotoUrl, `${form.first_name.value} ${form.last_name.value}`.trim());
    });

    panel.querySelector('[data-action="create"]')?.addEventListener('click', openCreate);

    modal.querySelectorAll('[data-modal-dismiss]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });

    form.querySelector('[data-copy-temp-password]')?.addEventListener('click', async () => {
        const value = tempPasswordEl?.textContent || '';
        if (!value) {
            return;
        }
        try {
            await navigator.clipboard.writeText(value);
            toastSuccess('Temporary password copied');
        } catch {
            toastError('Unable to copy password');
        }
    });

    let searchTimer = null;
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => table.reload(true), 300);
    });
    departmentFilter?.addEventListener('change', () => table.reload(true));
    statusFilter?.addEventListener('change', () => table.reload(true));

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!canManage) {
            return;
        }
        clearErrors(form);
        hideTempPassword();

        const formData = buildFormData();
        const submit = form.querySelector('[type="submit"]');
        setButtonLoading(submit, true, 'Saving…');

        try {
            const { data } = editingId
                ? await http.post(`/employees/${editingId}`, (() => {
                    formData.append('_method', 'PUT');
                    return formData;
                })())
                : await http.post('/employees', formData);

            toastSuccess(data.message || 'Employee saved');
            table.reload(true);
            await refreshCurrentUserAvatar();

            if (data.data?.temporary_password) {
                fillForm(data.data.employee);
                editingId = data.data.employee.id;
                title.textContent = 'Edit employee';
                showTempPassword(data.data.temporary_password);
                form.querySelector('.overflow-y-auto')?.scrollTo({ top: 0 });
            } else {
                closeModal(modal);
            }
        } catch (error) {
            const response = error.response?.data;
            showErrors(form, response?.errors || {});
            toastError(response?.message || 'Unable to save employee');
        } finally {
            setButtonLoading(submit, false);
        }
    });

    (async () => {
        try {
            const { data } = await http.get('/employees/meta');
            meta = data.data || meta;
            populateMetaSelects();
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to load employee meta');
        }
        table.reload(true);
    })();
}
