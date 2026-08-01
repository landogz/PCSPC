@extends('layouts.app')

@section('title', ($module['label'] ?? 'Users & Security') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'Users & Security')

@php
    $canUsers = auth()->user()?->hasPermission('users.manage') ?? false;
    $canRoles = auth()->user()?->hasPermission('roles.manage') ?? false;
    $canPasswordPolicy = $canUsers;
    $defaultTab = $canUsers ? 'users' : ($canRoles ? 'roles' : ($canPasswordPolicy ? 'password-policy' : 'users'));
@endphp

@section('content')
<section
    class="space-y-4 md:space-y-5"
    data-module="security"
    data-can-users="{{ $canUsers ? '1' : '0' }}"
    data-can-roles="{{ $canRoles ? '1' : '0' }}"
    data-can-password-policy="{{ $canPasswordPolicy ? '1' : '0' }}"
>
    <div class="bg-surface border border-border rounded-2xl p-5 md:p-6">
        <p class="text-xs font-bold tracking-wide text-faint uppercase">{{ $module['section'] ?? 'System' }}</p>
        <h2 class="text-2xl font-bold text-heading mt-1">{{ $module['label'] ?? 'Users & Security' }}</h2>
        <p class="text-sm text-text-secondary mt-2 max-w-3xl">{{ $module['summary'] ?? '' }}</p>

        <div class="mt-5 flex flex-wrap gap-2 border-b border-border" role="tablist">
            @if ($canUsers)
                <button
                    type="button"
                    data-security-tab="users"
                    class="security-tab h-10 px-4 text-sm font-semibold border-b-2 -mb-px transition-colors {{ $defaultTab === 'users' ? 'border-primary text-primary' : 'border-transparent text-muted hover:text-heading' }}"
                >
                    Users
                </button>
            @endif
            @if ($canRoles)
                <button
                    type="button"
                    data-security-tab="roles"
                    class="security-tab h-10 px-4 text-sm font-semibold border-b-2 -mb-px transition-colors {{ $defaultTab === 'roles' ? 'border-primary text-primary' : 'border-transparent text-muted hover:text-heading' }}"
                >
                    Roles &amp; permissions
                </button>
            @endif
            @if ($canPasswordPolicy)
                <button
                    type="button"
                    data-security-tab="password-policy"
                    class="security-tab h-10 px-4 text-sm font-semibold border-b-2 -mb-px transition-colors {{ $defaultTab === 'password-policy' ? 'border-primary text-primary' : 'border-transparent text-muted hover:text-heading' }}"
                >
                    Password policy
                </button>
            @endif
        </div>
    </div>

    @if ($canUsers)
        <div data-security-panel="users" class="{{ $defaultTab === 'users' ? '' : 'hidden' }}">
            <x-ui.data-panel id="security-users" title="User accounts" create-label="Add user">
                <x-slot:subtitle>Manage access, roles, MFA flags, and unlock locked accounts.</x-slot:subtitle>
                <x-slot:filters>
                    <div class="relative w-full sm:col-span-2 lg:w-52 lg:flex-none">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                        <input
                            type="search"
                            data-filter="search"
                            placeholder="Search users…"
                            class="w-full h-10 min-h-[44px] sm:min-h-10 pl-9 pr-3 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                        >
                    </div>
                    <select data-filter="status" class="w-full lg:w-36 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm">
                        <option value="">All status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="locked">Locked</option>
                    </select>
                    <select data-filter="role" class="w-full lg:w-40 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm">
                        <option value="">All roles</option>
                    </select>
                </x-slot:filters>

                <x-slot:head>
                    <tr>
                        <th class="px-4 py-3 font-semibold">User</th>
                        <th class="px-4 py-3 font-semibold">Employee #</th>
                        <th class="px-4 py-3 font-semibold">Roles</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Locked</th>
                        <th class="px-4 py-3 font-semibold">MFA</th>
                        <th class="px-4 py-3 font-semibold">Last login</th>
                        <th class="px-4 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </x-slot:head>

                <x-slot:modals>
                    <x-ui.modal id="security-user-modal" title="Add user">
                        <form id="security-user-form" class="flex min-h-0 flex-1 flex-col" novalidate>
                            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain p-4 sm:p-5">
                                <input type="hidden" name="id" value="">
                                <div data-lock-banner class="hidden rounded-xl border border-warning/40 bg-warning-soft px-3 py-2.5 text-sm text-heading">
                                    <div class="flex items-start gap-2">
                                        <i class="ph ph-lock-key text-lg flex-shrink-0 mt-0.5"></i>
                                        <div>
                                            <p class="font-semibold">Account is locked</p>
                                            <p class="text-xs text-muted mt-0.5" data-lock-until></p>
                                        </div>
                                    </div>
                                </div>
                                <div data-employee-picker class="hidden">
                                    <x-ui.employee-search
                                        name="employee_id"
                                        id="user-employee"
                                        label="Employee"
                                        :required="true"
                                        hint="Select an employee 201 record. Employees with an existing login cannot be selected."
                                    >
                                        <div data-duplicate-banner class="hidden rounded-xl border border-danger/30 bg-danger-soft px-3 py-2 text-sm text-heading">
                                            This employee already has a user account.
                                        </div>
                                    </x-ui.employee-search>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">Full name</label>
                                    <input name="name" required class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                    <p class="hidden text-xs text-danger mt-1" data-error="name"></p>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-heading mb-1.5">Email</label>
                                        <input name="email" type="email" required class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                        <p class="text-xs text-muted mt-1" data-email-hint>Login email for this account.</p>
                                        <p class="hidden text-xs text-danger mt-1" data-error="email"></p>
                                    </div>
                                    <div data-employee-number-field>
                                        <label class="block text-sm font-medium text-heading mb-1.5">Employee number</label>
                                        <input
                                            name="employee_number"
                                            disabled
                                            class="w-full h-11 px-3 rounded-xl border border-border bg-subtle text-sm text-muted cursor-not-allowed"
                                            placeholder="—"
                                        >
                                        <p class="text-xs text-muted mt-1" data-employee-number-hint>Synced from the selected employee.</p>
                                        <p class="hidden text-xs text-danger mt-1" data-error="employee_number"></p>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5" for="user-password">Password</label>
                                    <x-ui.password-input
                                        name="password"
                                        id="user-password"
                                        autocomplete="new-password"
                                    />
                                    <p class="text-xs text-muted mt-1" data-password-hint>Required for new users.</p>
                                    <p class="hidden text-xs text-danger mt-1" data-error="password"></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">Role</label>
                                    <select name="role_id" required class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                        <option value="">— Select role —</option>
                                    </select>
                                    <p class="hidden text-xs text-danger mt-1" data-error="role_ids"></p>
                                    <p class="hidden text-xs text-danger mt-1" data-error="role_id"></p>
                                </div>
                                <div class="flex flex-wrap gap-4">
                                    <label class="inline-flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="is_active" value="1" class="rounded border-border" checked>
                                        Active
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="mfa_enabled" value="1" class="rounded border-border">
                                        MFA enabled
                                    </label>
                                </div>
                            </div>
                            <div class="flex flex-shrink-0 flex-col-reverse gap-2 border-t border-border bg-surface p-4 sm:flex-row sm:justify-end sm:p-5">
                                <button type="button" data-modal-dismiss class="h-11 px-4 rounded-xl border border-border text-sm font-medium">Cancel</button>
                                <button type="submit" class="h-11 px-4 rounded-xl bg-primary text-white text-sm font-medium">Save user</button>
                            </div>
                        </form>
                    </x-ui.modal>
                </x-slot:modals>
            </x-ui.data-panel>
        </div>
    @endif

    @if ($canRoles)
        <div data-security-panel="roles" class="{{ $defaultTab === 'roles' ? '' : 'hidden' }}">
            <x-ui.data-panel id="security-roles" title="Roles & permissions" create-label="Add role">
                <x-slot:subtitle>Create roles and assign module permissions (RBAC).</x-slot:subtitle>
                <x-slot:filters>
                    <div class="relative w-full sm:col-span-2 lg:w-52 lg:flex-none">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                        <input
                            type="search"
                            data-filter="search"
                            placeholder="Search roles…"
                            class="w-full h-10 min-h-[44px] sm:min-h-10 pl-9 pr-3 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                        >
                    </div>
                </x-slot:filters>

                <x-slot:head>
                    <tr>
                        <th class="px-4 py-3 font-semibold">Role</th>
                        <th class="px-4 py-3 font-semibold">Slug</th>
                        <th class="px-4 py-3 font-semibold">Permissions</th>
                        <th class="px-4 py-3 font-semibold">Users</th>
                        <th class="px-4 py-3 font-semibold">MFA</th>
                        <th class="px-4 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </x-slot:head>

                <x-slot:modals>
                    <x-ui.modal id="security-role-modal" title="Add role" max-width="max-w-2xl">
                        <form id="security-role-form" class="flex min-h-0 flex-1 flex-col" novalidate>
                            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain p-4 sm:p-5">
                                <input type="hidden" name="id" value="">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-heading mb-1.5">Role name</label>
                                        <input name="name" required class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                        <p class="hidden text-xs text-danger mt-1" data-error="name"></p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-heading mb-1.5">Slug</label>
                                        <input name="slug" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm" placeholder="auto-from-name" data-slug-input>
                                        <p class="text-xs text-muted mt-1" data-slug-hint>Leave blank to auto-generate from the name.</p>
                                        <p class="hidden text-xs text-danger mt-1" data-error="slug"></p>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">Description</label>
                                    <textarea name="description" rows="2" class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-sm"></textarea>
                                    <p class="hidden text-xs text-danger mt-1" data-error="description"></p>
                                </div>
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="requires_mfa" value="1" class="rounded border-border">
                                    Require MFA for this role
                                </label>
                                <div>
                                    <div class="mb-2 flex items-center justify-between gap-3">
                                        <label class="block text-sm font-medium text-heading">Permissions</label>
                                        <div class="flex gap-2">
                                            <button type="button" data-perm-select="all" class="text-xs font-medium text-primary hover:underline">Select all</button>
                                            <button type="button" data-perm-select="none" class="text-xs font-medium text-muted hover:underline">Clear</button>
                                        </div>
                                    </div>
                                    <div class="max-h-56 space-y-4 overflow-y-auto rounded-xl border border-border p-3 sm:max-h-64" data-permission-groups></div>
                                    <p class="hidden text-xs text-danger mt-1" data-error="permission_ids"></p>
                                </div>
                            </div>
                            <div class="flex flex-shrink-0 flex-col-reverse gap-2 border-t border-border bg-surface p-4 sm:flex-row sm:justify-end sm:p-5">
                                <button type="button" data-modal-dismiss class="h-11 min-h-[44px] px-4 rounded-xl border border-border text-sm font-medium">Cancel</button>
                                <button type="submit" class="h-11 min-h-[44px] px-4 rounded-xl bg-primary text-white text-sm font-medium">Save role</button>
                            </div>
                        </form>
                    </x-ui.modal>
                </x-slot:modals>
            </x-ui.data-panel>
        </div>
    @endif

    @if ($canPasswordPolicy)
        <div data-security-panel="password-policy" class="{{ $defaultTab === 'password-policy' ? '' : 'hidden' }}">
            <div class="bg-surface border border-border rounded-2xl p-5 md:p-6" data-spa-module="password-policy">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold tracking-wide text-faint uppercase">ADM-005</p>
                        <h3 class="text-lg font-semibold text-heading mt-1">Password policy</h3>
                        <p class="text-sm text-muted mt-1 max-w-2xl">
                            Complexity, expiration, reuse history, and temporary-password force change.
                        </p>
                    </div>
                    <button
                        type="button"
                        data-save-policy
                        class="inline-flex items-center justify-center h-10 min-h-[44px] px-4 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-strong transition-colors"
                    >
                        Save policy
                    </button>
                </div>

                <form id="password-policy-form" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5" novalidate>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5" for="min_length">Minimum length</label>
                        <input id="min_length" name="min_length" type="number" min="6" max="64" class="w-full h-11 px-3 rounded-xl border border-border bg-subtle text-sm">
                        <p class="hidden text-xs text-danger mt-1" data-error="min_length"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5" for="expire_days">Expire after (days)</label>
                        <input id="expire_days" name="expire_days" type="number" min="0" max="730" class="w-full h-11 px-3 rounded-xl border border-border bg-subtle text-sm">
                        <p class="text-xs text-muted mt-1">Use 0 to disable expiration.</p>
                        <p class="hidden text-xs text-danger mt-1" data-error="expire_days"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1.5" for="history_count">Password history count</label>
                        <input id="history_count" name="history_count" type="number" min="0" max="24" class="w-full h-11 px-3 rounded-xl border border-border bg-subtle text-sm">
                        <p class="text-xs text-muted mt-1">Block reuse of the last N passwords.</p>
                        <p class="hidden text-xs text-danger mt-1" data-error="history_count"></p>
                    </div>
                    <div class="space-y-3 md:pt-7">
                        <label class="inline-flex items-center gap-2 text-sm text-text-secondary cursor-pointer">
                            <input type="checkbox" name="require_mixed_case" class="accent-primary" value="1">
                            Require upper and lower case
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-text-secondary cursor-pointer">
                            <input type="checkbox" name="require_numbers" class="accent-primary" value="1">
                            Require a number
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-text-secondary cursor-pointer">
                            <input type="checkbox" name="require_symbols" class="accent-primary" value="1">
                            Require a symbol
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-text-secondary cursor-pointer">
                            <input type="checkbox" name="uncompromised" class="accent-primary" value="1">
                            Reject known breached passwords
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-text-secondary cursor-pointer">
                            <input type="checkbox" name="force_change_temporary" class="accent-primary" value="1">
                            Force change on temporary / admin-set passwords
                        </label>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs text-muted" data-policy-hint></p>
                    </div>
                </form>
            </div>
        </div>
    @endif
</section>
@endsection
