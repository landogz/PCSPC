import http from '../../utils/http';
import { setButtonLoading } from '../../utils/button-loading';
import { toastSuccess, toastError, confirmAction } from '../../utils/toast';
import { createServerTable, escapeHtml, rowActionsCell, cardActions } from '../../utils/server-table';
import { openModal, closeModal } from '../../utils/modal';
import { avatarInitial, avatarMarkup } from '../../utils/avatar';
import { downloadBlob, filenameFromContentDisposition } from '../../utils/download';
import { fillSidebarUser } from '../layout';
import { initEmployeeDependents } from './dependents';
import { initEmployeeEducations } from './education';
import { initEmployeeEmploymentHistory } from './employment-history';
import { initEmployeeFormTabsNav } from './form-tabs-nav';

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
    form.querySelectorAll('.ui-input.is-invalid, .ui-select.is-invalid').forEach((el) => {
        el.classList.remove('is-invalid');
        el.removeAttribute('aria-invalid');
    });
    form.querySelectorAll('[data-tab-error]').forEach((el) => {
        el.classList.add('hidden');
    });
}

function showErrors(form, errors = {}) {
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

function updateTabErrorBadges(form, tabKeys, tabFields) {
    tabKeys.forEach((key) => {
        const badge = form.querySelector(`[data-tab-error="${key}"]`);
        if (!badge) {
            return;
        }
        let hasError = tabFields[key].some((field) => {
            const input = form.elements.namedItem(field);
            return Boolean(input && 'classList' in input && input.classList.contains('is-invalid'));
        });

        if (!hasError && key === 'employment') {
            const photoErr = form.querySelector('[data-error="photo"]');
            hasError = Boolean(
                photoErr
                && !photoErr.classList.contains('hidden')
                && photoErr.textContent.trim() !== '',
            );
        }

        badge.classList.toggle('hidden', !hasError);
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
    let meta = { departments: [], statuses: [], dependent_relationships: [], education_levels: [] };
    let dependentsCount = 0;
    let educationCount = 0;
    let historyCount = 0;

    function rowActionsFor(row) {
        const actions = [{ key: 'view', label: canManage ? 'Edit' : 'View' }];
        if (canManage) {
            if (row.employment_status === 'active' || row.employment_status === 'on_leave') {
                actions.push({ key: 'deactivate', label: 'Deactivate', danger: true });
            }
            if (!row.user) {
                actions.push({ key: 'delete', label: 'Delete', danger: true });
            }
        }
        return actions;
    }

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
            const actions = rowActionsFor(row);
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
        mapCard: (row) => {
            const actions = rowActionsFor(row);
            const login = row.user
                ? `<p class="text-xs text-muted truncate">${escapeHtml(row.user.email)} · ${row.user.is_active ? 'Active login' : 'Inactive login'}</p>`
                : '<p class="text-xs text-muted">Login not linked</p>';

            return `
                <article
                    class="rounded-2xl border border-border bg-surface p-4 flex flex-col gap-3 hover:border-primary/30 transition-colors"
                    data-row-id="${escapeHtml(row.id)}"
                    data-actions='${JSON.stringify(actions)}'
                >
                    <div class="flex items-start gap-3">
                        ${avatarMarkup({
                            url: row.photo_url,
                            name: row.full_name,
                            sizeClass: 'w-12 h-12',
                            textClass: 'text-sm',
                        })}
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-heading truncate">${escapeHtml(row.full_name)}</p>
                            <p class="text-xs text-muted mt-0.5">${escapeHtml(row.employee_number)}</p>
                            <p class="text-xs text-muted truncate">${escapeHtml(row.email || '')}</p>
                        </div>
                    </div>
                    <div class="space-y-1.5 text-sm">
                        <p class="text-text-secondary truncate">
                            <span class="text-muted">Dept:</span> ${escapeHtml(row.department?.name || '—')}
                        </p>
                        <p class="text-text-secondary truncate">
                            <span class="text-muted">Position:</span> ${escapeHtml(row.position_title || '—')}
                        </p>
                        <div class="flex flex-wrap items-center gap-2">
                            ${employmentBadge(row.employment_status)}
                        </div>
                        ${login}
                    </div>
                    ${cardActions(actions)}
                </article>
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
        const statusOptions = meta.status_options?.length
            ? meta.status_options
            : (meta.statuses || []).map((status) => ({ code: status, label: status.replaceAll('_', ' ') }));
        statusOptions.forEach((option) => {
            const el = document.createElement('option');
            el.value = option.code;
            el.textContent = option.label;
            statusSelect.appendChild(el);
        });
        statusSelect.value = currentStatus;

        const fillOptionSelect = (select, options, { includeBlank = false, blankLabel = '— Select —' } = {}) => {
            if (!select || !Array.isArray(options)) {
                return;
            }
            const current = select.value;
            select.innerHTML = includeBlank ? `<option value="">${blankLabel}</option>` : '';
            options.forEach((option) => {
                const el = document.createElement('option');
                el.value = option.code;
                el.textContent = option.label;
                select.appendChild(el);
            });
            if ([...select.options].some((opt) => opt.value === current)) {
                select.value = current;
            }
        };

        fillOptionSelect(form.gender, meta.genders || [], { includeBlank: true });
        fillOptionSelect(form.civil_status, meta.civil_statuses || [], { includeBlank: true });

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
            statusOptions.forEach((option) => {
                const el = document.createElement('option');
                el.value = option.code;
                el.textContent = option.label;
                statusFilter.appendChild(el);
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
            preview.classList.remove('bg-primary', 'text-white', 'border-dashed', 'border-primary/40');
            preview.classList.add('border-solid', 'border-border');
            return;
        }

        img.removeAttribute('src');
        img.classList.add('hidden');
        initial.classList.remove('hidden');
        preview.classList.add('bg-primary', 'text-white', 'border-dashed', 'border-primary/40');
        preview.classList.remove('border-solid', 'border-border');
    }

    const subtitle = modal.querySelector('[data-modal-subtitle]');
    const progressEl = form.querySelector('[data-form-progress]');
    const tabsNav = initEmployeeFormTabsNav(form);
    const TAB_KEYS = ['employment', 'personal', 'contact', 'documents', 'dependents', 'education', 'history', 'training', 'medical'];
    const TAB_FIELDS = {
        employment: ['first_name', 'middle_name', 'last_name', 'suffix', 'employee_number', 'department_id', 'position_title', 'employment_status', 'date_hired', 'date_regularized', 'date_separated'],
        personal: ['birth_date', 'gender', 'civil_status', 'nationality'],
        contact: ['email', 'mobile', 'address_line', 'city', 'province', 'zip_code'],
        documents: ['tin', 'sss_number', 'philhealth_number', 'pagibig_number'],
        dependents: [],
        education: [],
        history: [],
        training: [],
        medical: [],
    };
    let activeTabKey = 'employment';

    function setActiveTab(tabKey) {
        activeTabKey = tabKey;
        TAB_KEYS.forEach((key) => {
            const tab = form.querySelector(`[data-tab="${key}"]`);
            const panel = form.querySelector(`[data-tab-panel="${key}"]`);
            const active = key === tabKey;
            tab?.classList.toggle('is-active', active);
            tab?.setAttribute('aria-selected', active ? 'true' : 'false');
            panel?.classList.toggle('hidden', !active);
            if (active && tab) {
                const nav = form.querySelector('[data-employee-tabs]');
                if (nav) {
                    const navRect = nav.getBoundingClientRect();
                    const tabRect = tab.getBoundingClientRect();
                    const overflowed = tabRect.left < navRect.left + 8 || tabRect.right > navRect.right - 8;
                    if (overflowed) {
                        tab.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                        requestAnimationFrame(() => tabsNav.refresh());
                    }
                }
            }
        });
        updateProgress();
        tabsNav.refresh();
        if (!editingId) {
            return;
        }
        if (tabKey === 'dependents') {
            dependents.reload();
        } else if (tabKey === 'education') {
            educations.reload();
        } else if (tabKey === 'history') {
            histories.reload();
        }
    }

    function sectionStarted(fields, tabKey) {
        if (tabKey === 'dependents') {
            return dependentsCount > 0;
        }
        if (tabKey === 'education') {
            return educationCount > 0;
        }
        if (tabKey === 'history') {
            return historyCount > 0;
        }
        return fields.some((key) => {
            const field = form.elements.namedItem(key);
            if (!field) {
                return false;
            }
            if (key === 'nationality') {
                return String(field.value || '').trim() !== '' && String(field.value).trim() !== 'Filipino';
            }
            if (key === 'employment_status') {
                return String(field.value || '').trim() !== '' && String(field.value).trim() !== 'active';
            }
            return String(field.value || '').trim() !== '';
        });
    }

    function updateProgress() {
        const startedKeys = TAB_KEYS.filter((key) => sectionStarted(TAB_FIELDS[key], key));
        const started = startedKeys.length;
        const requiredReady = ['first_name', 'last_name', 'employee_number', 'email', 'employment_status']
            .every((key) => String(form.elements.namedItem(key)?.value || '').trim() !== '');

        if (progressEl) {
            progressEl.textContent = requiredReady
                ? `${started} of ${TAB_KEYS.length} sections started · required fields complete`
                : `${started} of ${TAB_KEYS.length} sections started`;
        }

        TAB_KEYS.forEach((key) => {
            const seg = form.querySelector(`[data-progress-seg="${key}"]`);
            if (!seg) {
                return;
            }
            const done = startedKeys.includes(key);
            const current = key === activeTabKey;
            seg.classList.toggle('is-done', done);
            seg.classList.toggle('is-current', current);
        });
    }

    const dependents = initEmployeeDependents({
        root: form,
        getEmployeeId: () => editingId,
        canManage,
        onCountChange: (count) => {
            dependentsCount = count;
            updateProgress();
        },
    });

    const educations = initEmployeeEducations({
        root: form,
        getEmployeeId: () => editingId,
        canManage,
        onCountChange: (count) => {
            educationCount = count;
            updateProgress();
        },
    });

    const histories = initEmployeeEmploymentHistory({
        root: form,
        getEmployeeId: () => editingId,
        canManage,
        onCountChange: (count) => {
            historyCount = count;
            updateProgress();
        },
    });

    function refreshTabErrorBadges() {
        updateTabErrorBadges(form, TAB_KEYS, TAB_FIELDS);
    }

    function focusFirstErrorTab(errors = {}) {
        const fields = Object.keys(errors);
        if (fields.includes('photo')) {
            setActiveTab('employment');
            return;
        }
        for (const key of TAB_KEYS) {
            if (fields.some((field) => TAB_FIELDS[key].includes(field))) {
                setActiveTab(key);
                return;
            }
        }
    }

    function scrollFirstInvalidIntoView() {
        const invalid = form.querySelector('.ui-input.is-invalid, .ui-select.is-invalid');
        invalid?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        invalid?.focus?.({ preventScroll: true });
    }

    function setModalCopy(mode) {
        if (mode === 'create') {
            title.textContent = 'Add employee';
            if (subtitle) {
                subtitle.textContent = "Fill in the employee's details below";
            }
            return;
        }
        title.textContent = canManage ? 'Edit employee' : 'View employee';
        if (subtitle) {
            subtitle.textContent = canManage
                ? 'Update employment, contact, and statutory details'
                : 'View employee 201 record details';
        }
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
        setActiveTab('employment');
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
        form.querySelectorAll('[data-photo-trigger]').forEach((btn) => {
            btn.disabled = !canManage;
        });
        dependents.reset();
        educations.reset();
        histories.reset();
        updateProgress();
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
        setActiveTab('employment');
        updateProgress();
    }

    function openCreate() {
        if (!canManage) {
            return;
        }
        resetForm();
        setModalCopy('create');
        openModal(modal);
        requestAnimationFrame(() => tabsNav.refresh());
    }

    async function openEdit(id) {
        resetForm();
        editingId = id;
        setModalCopy('edit');

        try {
            const { data } = await http.get(`/employees/${id}`);
            fillForm(data.data.employee);
            openModal(modal);
            requestAnimationFrame(() => tabsNav.refresh());
            await Promise.all([
                dependents.reload(),
                educations.reload(),
                histories.reload(),
            ]);
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

    form.querySelectorAll('[data-tab]').forEach((tab) => {
        tab.addEventListener('click', () => setActiveTab(tab.dataset.tab));
    });

    form.querySelectorAll('[data-photo-trigger]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!canManage || form.photo?.disabled) {
                return;
            }
            form.photo?.click();
        });
    });

    form.employee_number?.addEventListener('input', () => {
        const field = form.employee_number;
        if (!field) {
            return;
        }
        const start = field.selectionStart;
        const end = field.selectionEnd;
        const next = String(field.value || '').toUpperCase();
        if (field.value !== next) {
            field.value = next;
            if (typeof start === 'number' && typeof end === 'number') {
                field.setSelectionRange(start, end);
            }
        }
    });

    form.addEventListener('input', (event) => {
        const target = event.target;
        if (target?.classList?.contains('is-invalid')) {
            target.classList.remove('is-invalid');
            target.removeAttribute('aria-invalid');
            const err = form.querySelector(`[data-error="${target.name}"]`);
            if (err) {
                err.textContent = '';
                err.classList.add('hidden');
            }
            refreshTabErrorBadges();
        }
        updateProgress();
    });
    form.addEventListener('change', (event) => {
        const target = event.target;
        if (target?.classList?.contains('is-invalid')) {
            target.classList.remove('is-invalid');
            target.removeAttribute('aria-invalid');
            const err = form.querySelector(`[data-error="${target.name}"]`);
            if (err) {
                err.textContent = '';
                err.classList.add('hidden');
            }
            refreshTabErrorBadges();
        }
        updateProgress();
    });

    form.photo?.addEventListener('change', () => {
        const file = form.photo.files?.[0];
        if (!file) {
            setPhotoPreview(currentPhotoUrl, `${form.first_name.value} ${form.last_name.value}`.trim());
            updateProgress();
            return;
        }
        if (form.remove_photo) {
            form.remove_photo.checked = false;
        }
        const objectUrl = URL.createObjectURL(file);
        setPhotoPreview(objectUrl, `${form.first_name.value} ${form.last_name.value}`.trim());
        updateProgress();
    });

    form.remove_photo?.addEventListener('change', () => {
        if (form.remove_photo.checked) {
            if (form.photo) {
                form.photo.value = '';
            }
            setPhotoPreview(null, `${form.first_name.value} ${form.last_name.value}`.trim());
            updateProgress();
            return;
        }
        setPhotoPreview(currentPhotoUrl, `${form.first_name.value} ${form.last_name.value}`.trim());
        updateProgress();
    });

    panel.querySelector('[data-action="create"]')?.addEventListener('click', openCreate);

    panel.querySelector('[data-action="export"]')?.addEventListener('click', async (event) => {
        const button = event.currentTarget;
        setButtonLoading(button, true, 'Exporting…');
        try {
            const response = await http.get('/employees/export', {
                params: {
                    search: searchInput?.value?.trim() || '',
                    department: departmentFilter?.value || '',
                    status: statusFilter?.value || '',
                },
                responseType: 'blob',
            });

            const contentType = response.headers['content-type'] || '';
            if (contentType.includes('application/json')) {
                const text = await response.data.text();
                const payload = JSON.parse(text);
                throw new Error(payload.message || 'Unable to export employees');
            }

            const filename = filenameFromContentDisposition(
                response.headers['content-disposition'],
                `employees-${new Date().toISOString().slice(0, 10)}.xlsx`,
            );
            downloadBlob(
                response.data,
                filename,
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            );
            toastSuccess('Employee Excel export downloaded');
        } catch (error) {
            let message = error.message || 'Unable to export employees';
            const data = error.response?.data;
            if (data instanceof Blob) {
                try {
                    const payload = JSON.parse(await data.text());
                    message = payload.message || message;
                } catch {
                    // keep fallback message
                }
            } else if (error.response?.data?.message) {
                message = error.response.data.message;
            }
            toastError(message);
        } finally {
            setButtonLoading(button, false);
        }
    });

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
                await dependents.reload();
                await educations.reload();
                await histories.reload();
            } else {
                closeModal(modal);
            }
        } catch (error) {
            const response = error.response?.data;
            const errors = response?.errors || {};
            showErrors(form, errors);
            refreshTabErrorBadges();
            focusFirstErrorTab(errors);
            scrollFirstInvalidIntoView();
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
            dependents.setRelationships(meta.dependent_relationship_options || meta.dependent_relationships || []);
            dependents.setGenders?.(meta.genders || []);
            educations.setMeta({
                levels: meta.education_levels || [],
                level_options: meta.education_level_options || [],
            });
            const statusFromUrl = new URLSearchParams(window.location.search).get('status');
            if (statusFromUrl && statusFilter && meta.statuses?.includes(statusFromUrl)) {
                statusFilter.value = statusFromUrl;
            }
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to load employee meta');
        }
        table.reload(true);
    })();
}
