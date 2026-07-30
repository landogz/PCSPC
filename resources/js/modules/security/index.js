import http from '../../utils/http';
import { setButtonLoading } from '../../utils/button-loading';
import { toastSuccess, toastError, confirmAction } from '../../utils/toast';
import { createServerTable, escapeHtml, statusBadge, lockedBadge } from '../../utils/server-table';
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

function formatDate(value) {
    if (!value) {
        return '—';
    }
    try {
        return new Date(value).toLocaleString();
    } catch {
        return value;
    }
}

function initSecurityTabs(root) {
    const tabs = root.querySelectorAll('[data-security-tab]');
    const panels = root.querySelectorAll('[data-security-panel]');

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const key = tab.dataset.securityTab;
            tabs.forEach((btn) => {
                const active = btn.dataset.securityTab === key;
                btn.classList.toggle('border-primary', active);
                btn.classList.toggle('text-primary', active);
                btn.classList.toggle('border-transparent', !active);
                btn.classList.toggle('text-muted', !active);
            });
            panels.forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.securityPanel !== key);
            });
        });
    });
}

function initUsersPanel(root) {
    const panel = root.querySelector('[data-spa-module="security-users"]');
    const modal = document.getElementById('security-user-modal');
    const form = document.getElementById('security-user-form');
    if (!panel || !modal || !form) {
        return;
    }

    const roleSelect = panel.querySelector('[data-filter="role"]');
    const searchInput = panel.querySelector('[data-filter="search"]');
    const statusSelect = panel.querySelector('[data-filter="status"]');
    const roleInput = form.elements.namedItem('role_id');
    const employeePicker = form.querySelector('[data-employee-picker]');
    const employeeSearch = form.querySelector('[data-employee-search]');
    const employeeResults = form.querySelector('[data-employee-results]');
    const duplicateBanner = form.querySelector('[data-duplicate-banner]');
    const title = modal.querySelector('[data-modal-title]');
    const passwordHint = form.querySelector('[data-password-hint]');
    let editingId = null;
    let availableRoles = [];
    let selectedEmployee = null;
    let employeeSearchTimer = null;

    function primaryRoleId(roles = []) {
        const order = ['super-admin', 'hr-admin', 'employee'];
        for (const slug of order) {
            const match = roles.find((role) => role.slug === slug);
            if (match) {
                return match.id;
            }
        }

        return roles[0]?.id || '';
    }

    function setProfileFieldsLocked(locked) {
        form.name.disabled = locked;
        form.email.disabled = locked;
        form.employee_number.disabled = true;
    }

    function clearEmployeeSelection() {
        selectedEmployee = null;
        form.employee_id.value = '';
        if (employeeSearch) {
            employeeSearch.value = '';
        }
        employeeResults?.classList.add('hidden');
        employeeResults && (employeeResults.innerHTML = '');
        duplicateBanner?.classList.add('hidden');
    }

    function applyEmployeeSelection(employee) {
        selectedEmployee = employee;
        form.employee_id.value = employee.id;
        form.name.value = employee.full_name || '';
        form.email.value = employee.email || '';
        form.employee_number.value = employee.employee_number || '';
        if (employeeSearch) {
            employeeSearch.value = employee.label || `${employee.employee_number} — ${employee.full_name}`;
        }
        employeeResults?.classList.add('hidden');
        setProfileFieldsLocked(true);
        duplicateBanner?.classList.toggle('hidden', !employee.has_account);
        clearErrors(form);
        if (employee.has_account) {
            showErrors(form, { employee_id: ['This employee already has a user account.'] });
        }
    }

    function renderEmployeeResults(items = []) {
        if (!employeeResults) {
            return;
        }
        if (!items.length) {
            employeeResults.innerHTML = '<div class="px-3 py-2.5 text-sm text-muted">No employees found.</div>';
            employeeResults.classList.remove('hidden');
            return;
        }

        employeeResults.innerHTML = items.map((item) => {
            const disabled = item.has_account;
            const payload = encodeURIComponent(JSON.stringify(item));
            return `
                <button
                    type="button"
                    data-employee-option
                    data-employee-json="${payload}"
                    class="flex w-full items-start gap-2 px-3 py-2.5 text-left text-sm hover:bg-subtle ${disabled ? 'opacity-60 cursor-not-allowed' : ''}"
                >
                    <div class="min-w-0 flex-1">
                        <div class="font-medium text-heading truncate">${escapeHtml(item.full_name || '—')}</div>
                        <div class="text-xs text-muted truncate">${escapeHtml(item.employee_number || '—')} · ${escapeHtml(item.email || 'No email')}</div>
                    </div>
                    ${disabled ? '<span class="flex-shrink-0 text-[10px] font-semibold uppercase text-danger">Has login</span>' : ''}
                </button>
            `;
        }).join('');
        employeeResults.classList.remove('hidden');
    }

    async function searchEmployees(query) {
        try {
            const { data } = await http.get('/security/employees/search', {
                params: { search: query || '' },
            });
            renderEmployeeResults(data?.data?.items || []);
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to search employees');
        }
    }

    const table = createServerTable({
        root: panel,
        endpoint: '/security/users',
        columns: 7,
        perPage: 10,
        extraParams: () => ({
            search: searchInput?.value?.trim() || '',
            status: statusSelect?.value || '',
            role: roleSelect?.value || '',
        }),
        mapRow: (row) => {
            const actions = [{ key: 'view', label: 'View / Edit' }];
            if (row.is_locked) {
                actions.push({ key: 'unlock', label: 'Unlock account' });
            }
            if (row.is_active && !row.is_protected) {
                actions.push({ key: 'deactivate', label: 'Deactivate', danger: true });
            }
            if (row.can_delete !== false && !row.is_protected) {
                actions.push({ key: 'delete', label: 'Delete', danger: true });
            }

            const rolesLabel = (row.roles || []).map((r) => r.name).join(', ') || '—';
            return `
                <tr class="hover:bg-subtle/60 ${row.is_locked ? 'bg-warning-soft/40' : ''}" data-row-id="${escapeHtml(row.id)}" data-actions='${JSON.stringify(actions)}'>
                    <td class="px-4 py-3">
                        <div class="font-medium text-heading flex items-center gap-1.5">
                            ${row.is_locked ? '<i class="ph ph-lock-key text-base text-heading" title="Account locked"></i>' : ''}
                            ${escapeHtml(row.name)}
                            ${row.is_protected ? '<span class="inline-flex items-center h-6 px-2 rounded-md bg-subtle text-[10px] font-semibold text-muted uppercase">Protected</span>' : ''}
                        </div>
                        <div class="text-xs text-muted">${escapeHtml(row.email)}</div>
                    </td>
                    <td class="px-4 py-3 text-text-secondary">${escapeHtml(row.employee_number || '—')}</td>
                    <td class="px-4 py-3 text-text-secondary">${escapeHtml(rolesLabel)}</td>
                    <td class="px-4 py-3">${statusBadge(row.is_active, row.is_locked)}</td>
                    <td class="px-4 py-3">${lockedBadge(row.is_locked, row.locked_until)}</td>
                    <td class="px-4 py-3">${row.mfa_enabled ? 'On' : 'Off'}</td>
                    <td class="px-4 py-3 text-text-secondary whitespace-nowrap">${escapeHtml(formatDate(row.last_login_at))}</td>
                </tr>
            `;
        },
        onRowAction: async (action, row) => {
            if (action === 'view') {
                openEdit(row);
                return;
            }
            if (action === 'unlock') {
                const confirmed = await confirmAction({
                    title: 'Unlock user?',
                    text: `Clear lockout for ${row.name}?`,
                    confirmButtonText: 'Unlock',
                });
                if (!confirmed.isConfirmed) {
                    return;
                }
                try {
                    const { data } = await http.post(`/security/users/${row.id}/unlock`);
                    toastSuccess(data.message || 'User unlocked');
                    table.reload();
                } catch (error) {
                    toastError(error.response?.data?.message || 'Unable to unlock user');
                }
                return;
            }
            if (action === 'deactivate') {
                const confirmed = await confirmAction({
                    title: 'Deactivate user?',
                    text: `${row.name} will no longer be able to sign in.`,
                    confirmButtonText: 'Deactivate',
                });
                if (!confirmed.isConfirmed) {
                    return;
                }
                try {
                    const { data } = await http.post(`/security/users/${row.id}/deactivate`);
                    toastSuccess(data.message || 'User deactivated');
                    table.reload();
                } catch (error) {
                    toastError(error.response?.data?.message || 'Unable to deactivate user');
                }
                return;
            }
            if (action === 'delete') {
                const confirmed = await confirmAction({
                    title: 'Delete user?',
                    text: `${row.name} (${row.email}) will be permanently removed.`,
                    confirmButtonText: 'Delete',
                });
                if (!confirmed.isConfirmed) {
                    return;
                }
                try {
                    const { data } = await http.delete(`/security/users/${row.id}`);
                    toastSuccess(data.message || 'User deleted');
                    table.reload();
                } catch (error) {
                    toastError(error.response?.data?.message || 'Unable to delete user');
                }
            }
        },
    });

    async function loadRoles() {
        try {
            const { data } = await http.get('/security/roles');
            availableRoles = data?.data?.roles || [];
            if (roleSelect) {
                const current = roleSelect.value;
                roleSelect.innerHTML = '<option value="">All roles</option>' + availableRoles
                    .map((role) => `<option value="${escapeHtml(role.slug)}">${escapeHtml(role.name)}</option>`)
                    .join('');
                roleSelect.value = current;
            }
            if (roleInput) {
                const current = roleInput.value;
                roleInput.innerHTML = '<option value="">— Select role —</option>' + availableRoles
                    .map((role) => `<option value="${escapeHtml(role.id)}" data-role-slug="${escapeHtml(role.slug || '')}">${escapeHtml(role.name)}</option>`)
                    .join('');
                roleInput.value = current;
            }
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to load roles');
        }
    }

    function resetForm() {
        form.reset();
        form.id.value = '';
        editingId = null;
        clearErrors(form);
        form.is_active.checked = true;
        form.is_active.disabled = false;
        form.mfa_enabled.checked = false;
        form.employee_number.value = '';
        form.employee_number.disabled = true;
        setProfileFieldsLocked(false);
        clearEmployeeSelection();
        if (roleInput) {
            roleInput.value = '';
            roleInput.disabled = false;
        }
        form.querySelector('[data-lock-banner]')?.classList.add('hidden');
        const until = form.querySelector('[data-lock-until]');
        if (until) {
            until.textContent = '';
        }
        employeePicker?.classList.add('hidden');
        const empField = form.querySelector('[data-employee-number-field]');
        empField?.classList.remove('hidden');
        const empHint = form.querySelector('[data-employee-number-hint]');
        if (empHint) {
            empHint.textContent = 'Synced from the selected employee.';
        }
    }

    function openCreate() {
        resetForm();
        title.textContent = 'Add user';
        passwordHint.textContent = 'Required for new users. Meets ADM-005 complexity; user must change on first login.';
        form.password.required = true;
        employeePicker?.classList.remove('hidden');
        form.querySelector('[data-employee-number-field]')?.classList.remove('hidden');
        setProfileFieldsLocked(true);
        form.name.value = '';
        form.email.value = '';
        form.employee_number.value = '';
        openModal(modal);
        employeeSearch?.focus();
    }

    function openEdit(row) {
        resetForm();
        editingId = row.id;
        title.textContent = row.is_protected ? 'Edit protected user' : 'Edit user';
        passwordHint.textContent = 'Leave blank to keep current. If set, user must change on next login.';
        form.password.required = false;
        form.id.value = row.id;
        form.name.value = row.name || '';
        form.email.value = row.email || '';
        form.employee_number.value = row.employee_number || '';
        form.employee_number.disabled = true;
        setProfileFieldsLocked(false);
        employeePicker?.classList.add('hidden');
        form.querySelector('[data-employee-number-field]')?.classList.remove('hidden');
        form.is_active.checked = !!row.is_active;
        form.is_active.disabled = !!row.is_protected;
        form.mfa_enabled.checked = !!row.mfa_enabled;

        if (roleInput) {
            roleInput.value = primaryRoleId(row.roles || []);
            if (row.is_protected) {
                const superAdmin = availableRoles.find((role) => role.slug === 'super-admin');
                if (superAdmin) {
                    roleInput.value = superAdmin.id;
                }
                roleInput.disabled = true;
            }
        }

        const empHint = form.querySelector('[data-employee-number-hint]');
        if (empHint) {
            empHint.textContent = row.employee_number
                ? 'Synced from Employees 201 — edit it there.'
                : 'No employee # linked to this login.';
        }

        if (row.is_locked) {
            form.querySelector('[data-lock-banner]')?.classList.remove('hidden');
            const until = form.querySelector('[data-lock-until]');
            if (until) {
                until.textContent = row.locked_until
                    ? `Locked until ${formatDate(row.locked_until)}`
                    : 'This account is currently locked out.';
            }
        }

        openModal(modal);
    }

    panel.querySelector('[data-action="create"]')?.addEventListener('click', openCreate);
    modal.querySelectorAll('[data-modal-dismiss]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });

    employeeSearch?.addEventListener('input', () => {
        selectedEmployee = null;
        form.employee_id.value = '';
        form.name.value = '';
        form.email.value = '';
        form.employee_number.value = '';
        duplicateBanner?.classList.add('hidden');
        clearTimeout(employeeSearchTimer);
        employeeSearchTimer = setTimeout(() => {
            searchEmployees(employeeSearch.value.trim());
        }, 250);
    });

    employeeSearch?.addEventListener('focus', () => {
        if (!editingId) {
            searchEmployees(employeeSearch.value.trim());
        }
    });

    employeeResults?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-employee-option]');
        if (!button) {
            return;
        }
        let employee;
        try {
            employee = JSON.parse(decodeURIComponent(button.getAttribute('data-employee-json') || '{}'));
        } catch {
            return;
        }
        applyEmployeeSelection(employee);
        if (employee.has_account) {
            toastError('This employee already has a user account.');
        }
    });

    document.addEventListener('click', (event) => {
        if (!employeePicker?.contains(event.target)) {
            employeeResults?.classList.add('hidden');
        }
    });

    let searchTimer = null;
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => table.reload(true), 300);
    });
    statusSelect?.addEventListener('change', () => table.reload(true));
    roleSelect?.addEventListener('change', () => table.reload(true));

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(form);

        const selectedRole = roleInput?.disabled
            ? (availableRoles.find((role) => role.slug === 'super-admin')?.id || roleInput.value)
            : (roleInput?.value || '');

        if (!selectedRole) {
            showErrors(form, { role_id: ['Please select a role.'] });
            return;
        }

        let payload;
        if (editingId) {
            payload = {
                name: form.name.value.trim(),
                email: form.email.value.trim(),
                is_active: form.is_active.disabled ? true : form.is_active.checked,
                mfa_enabled: form.mfa_enabled.checked,
                role_ids: [selectedRole],
            };
            if (form.password.value) {
                payload.password = form.password.value;
            }
        } else {
            if (!form.employee_id.value) {
                showErrors(form, { employee_id: ['Please select an employee.'] });
                return;
            }
            if (selectedEmployee?.has_account) {
                showErrors(form, { employee_id: ['This employee already has a user account.'] });
                toastError('This employee already has a user account.');
                return;
            }
            if (!form.password.value) {
                showErrors(form, { password: ['Password is required.'] });
                return;
            }
            payload = {
                employee_id: form.employee_id.value,
                password: form.password.value,
                is_active: form.is_active.checked,
                mfa_enabled: form.mfa_enabled.checked,
                role_ids: [selectedRole],
            };
        }

        const submit = form.querySelector('[type="submit"]');
        setButtonLoading(submit, true, 'Saving…');

        try {
            const { data } = editingId
                ? await http.put(`/security/users/${editingId}`, payload)
                : await http.post('/security/users', payload);

            toastSuccess(data.message || 'User saved');
            closeModal(modal);
            table.reload(true);
        } catch (error) {
            const response = error.response?.data;
            showErrors(form, response?.errors || {});
            toastError(response?.message || 'Unable to save user');
        } finally {
            setButtonLoading(submit, false);
        }
    });

    loadRoles().then(() => table.reload(true));
}

function initRolesPanel(root) {
    const panel = root.querySelector('[data-spa-module="security-roles"]');
    const modal = document.getElementById('security-role-modal');
    const form = document.getElementById('security-role-form');
    if (!panel || !modal || !form) {
        return;
    }

    const searchInput = panel.querySelector('[data-filter="search"]');
    const title = modal.querySelector('[data-modal-title]');
    const permGroups = form.querySelector('[data-permission-groups]');
    const slugInput = form.querySelector('[data-slug-input]');
    const slugHint = form.querySelector('[data-slug-hint]');
    let editingId = null;
    let permissionGroups = [];

    const table = createServerTable({
        root: panel,
        endpoint: '/security/rbac/roles',
        columns: 5,
        perPage: 10,
        extraParams: () => ({
            search: searchInput?.value?.trim() || '',
        }),
        mapRow: (row) => {
            const actions = [{ key: 'edit', label: 'Edit permissions' }];
            if (!row.is_system) {
                actions.push({ key: 'delete', label: 'Delete', danger: true });
            }

            return `
                <tr class="hover:bg-subtle/60" data-row-id="${escapeHtml(row.id)}" data-actions='${JSON.stringify(actions)}'>
                    <td class="px-4 py-3">
                        <div class="font-medium text-heading flex items-center gap-1.5">
                            ${escapeHtml(row.name)}
                            ${row.is_system ? '<span class="inline-flex items-center h-6 px-2 rounded-md bg-subtle text-[10px] font-semibold text-muted uppercase">System</span>' : ''}
                        </div>
                        <div class="text-xs text-muted mt-0.5">${escapeHtml(row.description || '—')}</div>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-text-secondary">${escapeHtml(row.slug)}</td>
                    <td class="px-4 py-3 text-text-secondary">${escapeHtml(String(row.permissions_count ?? 0))}</td>
                    <td class="px-4 py-3 text-text-secondary">${escapeHtml(String(row.users_count ?? 0))}</td>
                    <td class="px-4 py-3">${row.requires_mfa ? 'Required' : 'Optional'}</td>
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
                    title: 'Delete role?',
                    text: `${row.name} will be permanently removed.`,
                    confirmButtonText: 'Delete',
                });
                if (!confirmed.isConfirmed) {
                    return;
                }
                try {
                    const { data } = await http.delete(`/security/rbac/roles/${row.id}`);
                    toastSuccess(data.message || 'Role deleted');
                    table.reload();
                } catch (error) {
                    toastError(error.response?.data?.message || 'Unable to delete role');
                }
            }
        },
    });

    function renderPermissionGroups(selectedIds = []) {
        const selected = new Set(selectedIds);
        permGroups.innerHTML = permissionGroups
            .map((group) => {
                const items = (group.permissions || [])
                    .map(
                        (permission) => `
                    <label class="flex items-start gap-2 text-sm py-1">
                        <input
                            type="checkbox"
                            name="permission_ids[]"
                            value="${escapeHtml(permission.id)}"
                            class="mt-0.5 rounded border-border"
                            ${selected.has(permission.id) ? 'checked' : ''}
                        >
                        <span>
                            <span class="font-medium text-heading">${escapeHtml(permission.name)}</span>
                            <span class="block text-[11px] text-muted font-mono">${escapeHtml(permission.slug)}</span>
                        </span>
                    </label>`,
                    )
                    .join('');

                return `
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-faint mb-1.5">${escapeHtml(group.module)}</p>
                        <div class="space-y-0.5">${items}</div>
                    </div>
                `;
            })
            .join('');
    }

    async function loadPermissions() {
        try {
            const { data } = await http.get('/security/rbac/permissions');
            permissionGroups = data?.data?.groups || [];
            renderPermissionGroups();
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to load permissions');
        }
    }

    function resetForm() {
        form.reset();
        form.id.value = '';
        editingId = null;
        clearErrors(form);
        form.requires_mfa.checked = false;
        slugInput.disabled = false;
        slugHint.textContent = 'Leave blank to auto-generate from the name.';
        renderPermissionGroups();
    }

    function openCreate() {
        resetForm();
        title.textContent = 'Add role';
        openModal(modal);
    }

    function openEdit(row) {
        resetForm();
        editingId = row.id;
        title.textContent = 'Edit role';
        form.id.value = row.id;
        form.name.value = row.name || '';
        form.slug.value = row.slug || '';
        form.description.value = row.description || '';
        form.requires_mfa.checked = !!row.requires_mfa;
        if (row.is_system) {
            slugInput.disabled = true;
            slugHint.textContent = 'System role slug cannot be changed.';
        }
        renderPermissionGroups(row.permission_ids || []);
        openModal(modal);
    }

    panel.querySelector('[data-action="create"]')?.addEventListener('click', openCreate);
    modal.querySelectorAll('[data-modal-dismiss]').forEach((el) => {
        el.addEventListener('click', () => closeModal(modal));
    });

    form.querySelector('[data-perm-select="all"]')?.addEventListener('click', () => {
        form.querySelectorAll('input[name="permission_ids[]"]').forEach((el) => {
            el.checked = true;
        });
    });
    form.querySelector('[data-perm-select="none"]')?.addEventListener('click', () => {
        form.querySelectorAll('input[name="permission_ids[]"]').forEach((el) => {
            el.checked = false;
        });
    });

    let searchTimer = null;
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => table.reload(true), 300);
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(form);

        const payload = {
            name: form.name.value.trim(),
            description: form.description.value.trim() || null,
            requires_mfa: form.requires_mfa.checked,
            permission_ids: [...form.querySelectorAll('input[name="permission_ids[]"]:checked')].map((el) => el.value),
        };

        if (!slugInput.disabled && form.slug.value.trim()) {
            payload.slug = form.slug.value.trim();
        }

        const submit = form.querySelector('[type="submit"]');
        setButtonLoading(submit, true, 'Saving…');

        try {
            const { data } = editingId
                ? await http.put(`/security/rbac/roles/${editingId}`, payload)
                : await http.post('/security/rbac/roles', payload);

            toastSuccess(data.message || 'Role saved');
            closeModal(modal);
            table.reload(true);
        } catch (error) {
            const response = error.response?.data;
            showErrors(form, response?.errors || {});
            toastError(response?.message || 'Unable to save role');
        } finally {
            setButtonLoading(submit, false);
        }
    });

    loadPermissions().then(() => table.reload(true));
}

export function initSecurityModule() {
    const root = document.querySelector('[data-module="security"]');
    if (!root) {
        return;
    }

    initSecurityTabs(root);

    if (root.dataset.canUsers === '1') {
        initUsersPanel(root);
    }

    if (root.dataset.canRoles === '1') {
        initRolesPanel(root);
    }
}
