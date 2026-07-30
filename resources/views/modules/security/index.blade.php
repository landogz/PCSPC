@extends('layouts.app')

@section('title', ($module['label'] ?? 'Users & Security') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'Users & Security')

@section('content')
<section class="space-y-4 md:space-y-5" data-module="security">
    <div class="bg-surface border border-border rounded-2xl p-5 md:p-6">
        <p class="text-xs font-bold tracking-wide text-faint uppercase">{{ $module['section'] ?? 'System' }}</p>
        <h2 class="text-2xl font-bold text-heading mt-1">{{ $module['label'] ?? 'Users & Security' }}</h2>
        <p class="text-sm text-text-secondary mt-2 max-w-3xl">{{ $module['summary'] ?? '' }}</p>
    </div>

    <x-ui.data-panel id="security-users" title="User accounts" create-label="Add user">
        <x-slot:subtitle>Manage access, roles, MFA flags, and unlock locked accounts.</x-slot:subtitle>
        <x-slot:filters>
            <div class="relative min-w-[180px] flex-1 sm:flex-none">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-muted"></i>
                <input
                    type="search"
                    data-filter="search"
                    placeholder="Search users…"
                    class="w-full sm:w-56 h-10 pl-9 pr-3 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                >
            </div>
            <select
                data-filter="status"
                class="h-10 px-3 rounded-xl border border-border bg-surface text-sm min-w-[120px]"
            >
                <option value="">All status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="locked">Locked</option>
            </select>
            <select
                data-filter="role"
                class="h-10 px-3 rounded-xl border border-border bg-surface text-sm min-w-[140px]"
            >
                <option value="">All roles</option>
            </select>
        </x-slot:filters>

        <x-slot:head>
            <tr>
                <th class="px-4 py-3 font-semibold">User</th>
                <th class="px-4 py-3 font-semibold">Employee #</th>
                <th class="px-4 py-3 font-semibold">Roles</th>
                <th class="px-4 py-3 font-semibold">Status</th>
                <th class="px-4 py-3 font-semibold">MFA</th>
                <th class="px-4 py-3 font-semibold">Last login</th>
            </tr>
        </x-slot:head>

        <x-slot:modals>
            <div id="security-user-modal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" data-modal-dismiss></div>
                <div class="relative w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl border border-border bg-surface shadow-xl">
                    <div class="p-5 border-b border-border flex items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-heading" data-modal-title>Add user</h3>
                        <button type="button" class="h-9 w-9 rounded-lg hover:bg-subtle" data-modal-dismiss aria-label="Close">
                            <i class="ph ph-x text-lg"></i>
                        </button>
                    </div>
                    <form id="security-user-form" class="p-5 space-y-4" novalidate>
                        <input type="hidden" name="id" value="">
                        <div>
                            <label class="block text-sm font-medium text-heading mb-1.5">Full name</label>
                            <input name="name" required class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                            <p class="hidden text-xs text-danger mt-1" data-error="name"></p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-heading mb-1.5">Email</label>
                                <input name="email" type="email" required class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                <p class="hidden text-xs text-danger mt-1" data-error="email"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-heading mb-1.5">Employee number</label>
                                <input name="employee_number" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                <p class="hidden text-xs text-danger mt-1" data-error="employee_number"></p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-heading mb-1.5">Password</label>
                            <input name="password" type="password" autocomplete="new-password" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                            <p class="text-xs text-muted mt-1" data-password-hint>Required for new users.</p>
                            <p class="hidden text-xs text-danger mt-1" data-error="password"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-heading mb-1.5">Roles</label>
                            <div class="space-y-2 max-h-40 overflow-y-auto border border-border rounded-xl p-3" data-role-options></div>
                            <p class="hidden text-xs text-danger mt-1" data-error="role_ids"></p>
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
                        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
                            <button type="button" data-modal-dismiss class="h-11 px-4 rounded-xl border border-border text-sm font-medium">Cancel</button>
                            <button type="submit" class="h-11 px-4 rounded-xl bg-primary text-white text-sm font-medium">Save user</button>
                        </div>
                    </form>
                </div>
            </div>
        </x-slot:modals>
    </x-ui.data-panel>
</section>
@endsection
