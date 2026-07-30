@extends('layouts.app')

@section('title', ($module['label'] ?? 'Employees') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'Employees')

@php
    $canManage = auth()->user()?->hasPermission('employees.manage') ?? false;
@endphp

@section('content')
<section
    class="space-y-4 md:space-y-5"
    data-module="employees"
    data-can-manage="{{ $canManage ? '1' : '0' }}"
>
    <div class="bg-surface border border-border rounded-2xl p-5 md:p-6">
        <p class="text-xs font-bold tracking-wide text-faint uppercase">{{ $module['section'] ?? 'People' }}</p>
        <h2 class="text-2xl font-bold text-heading mt-1">{{ $module['label'] ?? 'Employees' }}</h2>
        <p class="text-sm text-text-secondary mt-2 max-w-3xl">{{ $module['summary'] ?? '' }}</p>
    </div>

    <x-ui.data-panel id="employees-table" title="Employee 201" :create-label="$canManage ? 'Add employee' : null">
        <x-slot:subtitle>Master employee records linked to User logins with the Employee role.</x-slot:subtitle>
        <x-slot:filters>
            <div class="relative min-w-[180px] flex-1 sm:flex-none">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-muted"></i>
                <input
                    type="search"
                    data-filter="search"
                    placeholder="Search employees…"
                    class="w-full sm:w-56 h-10 pl-9 pr-3 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                >
            </div>
            <select data-filter="department" class="h-10 px-3 rounded-xl border border-border bg-surface text-sm min-w-[140px]">
                <option value="">All departments</option>
            </select>
            <select data-filter="status" class="h-10 px-3 rounded-xl border border-border bg-surface text-sm min-w-[130px]">
                <option value="">All status</option>
            </select>
        </x-slot:filters>

        <x-slot:head>
            <tr>
                <th class="px-4 py-3 font-semibold">Employee #</th>
                <th class="px-4 py-3 font-semibold">Name</th>
                <th class="px-4 py-3 font-semibold">Department</th>
                <th class="px-4 py-3 font-semibold">Position</th>
                <th class="px-4 py-3 font-semibold">Status</th>
                <th class="px-4 py-3 font-semibold">Login</th>
            </tr>
        </x-slot:head>

        <x-slot:modals>
            <x-ui.modal id="employee-modal" title="Add employee" max-width="max-w-3xl">
                <form id="employee-form" class="flex min-h-0 flex-1 flex-col" novalidate>
                    <div class="min-h-0 flex-1 space-y-5 overflow-y-auto overscroll-contain p-4 sm:p-5">
                        <input type="hidden" name="id" value="">

                        <div data-temp-password-banner class="hidden rounded-xl border border-primary/30 bg-primary-soft px-3 py-2.5 text-sm">
                            <p class="font-semibold text-heading">Temporary login password</p>
                            <p class="text-xs text-muted mt-0.5">Share this once with the employee. It will not be shown again.</p>
                            <div class="mt-2 flex flex-col sm:flex-row gap-2 sm:items-center">
                                <code data-temp-password class="flex-1 rounded-lg border border-border bg-surface px-3 py-2 font-mono text-sm text-heading"></code>
                                <button type="button" data-copy-temp-password class="h-10 px-3 rounded-xl border border-border text-sm font-medium">Copy</button>
                            </div>
                        </div>

                        <section class="space-y-3">
                            <h4 class="text-sm font-semibold text-heading uppercase tracking-wide text-faint">Employment</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">Employee number</label>
                                    <input name="employee_number" required class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm uppercase">
                                    <p class="hidden text-xs text-danger mt-1" data-error="employee_number"></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">Department</label>
                                    <select name="department_id" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                        <option value="">— Select —</option>
                                    </select>
                                    <p class="hidden text-xs text-danger mt-1" data-error="department_id"></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">First name</label>
                                    <input name="first_name" required class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                    <p class="hidden text-xs text-danger mt-1" data-error="first_name"></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">Middle name</label>
                                    <input name="middle_name" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">Last name</label>
                                    <input name="last_name" required class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                    <p class="hidden text-xs text-danger mt-1" data-error="last_name"></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">Suffix</label>
                                    <input name="suffix" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm" placeholder="Jr., III">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">Email (login)</label>
                                    <input name="email" type="email" required class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                    <p class="hidden text-xs text-danger mt-1" data-error="email"></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">Mobile</label>
                                    <input name="mobile" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">Position</label>
                                    <input name="position_title" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">Employment status</label>
                                    <select name="employment_status" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm"></select>
                                    <p class="hidden text-xs text-danger mt-1" data-error="employment_status"></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">Date hired</label>
                                    <input name="date_hired" type="date" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">Date regularized</label>
                                    <input name="date_regularized" type="date" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">Date separated</label>
                                    <input name="date_separated" type="date" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                </div>
                            </div>
                        </section>

                        <section class="space-y-3">
                            <h4 class="text-sm font-semibold text-heading uppercase tracking-wide text-faint">Personal</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">Birth date</label>
                                    <input name="birth_date" type="date" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">Gender</label>
                                    <select name="gender" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                        <option value="">—</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">Civil status</label>
                                    <select name="civil_status" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                        <option value="">—</option>
                                        <option value="single">Single</option>
                                        <option value="married">Married</option>
                                        <option value="widowed">Widowed</option>
                                        <option value="separated">Separated</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">Nationality</label>
                                    <input name="nationality" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm" value="Filipino">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-heading mb-1.5">Address</label>
                                    <input name="address_line" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">City</label>
                                    <input name="city" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">Province</label>
                                    <input name="province" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">ZIP code</label>
                                    <input name="zip_code" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                </div>
                            </div>
                        </section>

                        <section class="space-y-3">
                            <h4 class="text-sm font-semibold text-heading uppercase tracking-wide text-faint">Statutory</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">TIN</label>
                                    <input name="tin" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">SSS number</label>
                                    <input name="sss_number" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">PhilHealth number</label>
                                    <input name="philhealth_number" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-heading mb-1.5">Pag-IBIG number</label>
                                    <input name="pagibig_number" class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-sm">
                                </div>
                            </div>
                        </section>

                        <section class="rounded-xl border border-border bg-subtle/50 p-4 space-y-2">
                            <h4 class="text-sm font-semibold text-heading">Account</h4>
                            <p class="text-sm text-muted">
                                Saving will create or link a User login and assign the <span class="font-semibold text-heading">Employee</span> role.
                            </p>
                            <p class="text-sm text-text-secondary" data-linked-user>No linked login yet.</p>
                        </section>
                    </div>

                    <div class="flex flex-shrink-0 flex-col-reverse gap-2 border-t border-border bg-surface p-4 sm:flex-row sm:justify-end sm:p-5">
                        <button type="button" data-modal-dismiss class="h-11 min-h-[44px] px-4 rounded-xl border border-border text-sm font-medium">Cancel</button>
                        @if ($canManage)
                            <button type="submit" class="h-11 min-h-[44px] px-4 rounded-xl bg-primary text-white text-sm font-medium">Save employee</button>
                        @endif
                    </div>
                </form>
            </x-ui.modal>
        </x-slot:modals>
    </x-ui.data-panel>
</section>
@endsection
