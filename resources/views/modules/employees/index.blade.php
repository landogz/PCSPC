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
                <th class="px-4 py-3 font-semibold text-right">Actions</th>
            </tr>
        </x-slot:head>

        <x-slot:modals>
            <x-ui.modal
                id="employee-modal"
                title="Add employee"
                subtitle="Fill in the employee's details below"
                max-width="max-w-3xl"
            >
                <x-slot:header>
                    <div class="min-w-0 pr-2 pt-1">
                        <div class="flex items-center gap-2.5">
                            <span class="hidden sm:inline-flex h-9 w-9 items-center justify-center rounded-xl bg-primary-soft text-primary flex-shrink-0">
                                <i class="ph ph-identification-badge text-lg"></i>
                            </span>
                            <div class="min-w-0">
                                <h3 id="employee-modal-title" class="text-lg font-semibold text-heading" data-modal-title>Add employee</h3>
                                <p class="text-sm text-muted mt-0.5" data-modal-subtitle>Fill in the employee's details below</p>
                            </div>
                        </div>
                    </div>
                </x-slot:header>
                <form id="employee-form" class="flex min-h-0 flex-1 flex-col" novalidate>
                    <input type="hidden" name="id" value="">

                    <div class="flex-shrink-0 border-b border-border bg-subtle/60 px-3 pt-3 sm:px-5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between sm:gap-3">
                            <nav class="flex min-w-0 flex-1 flex-wrap gap-x-0.5 gap-y-0" data-employee-tabs role="tablist" aria-label="Employee form sections">
                                <button type="button" role="tab" data-tab="employment" aria-selected="true" class="employee-tab is-active">
                                    <i class="ph ph-briefcase text-base"></i><span>Employment</span>
                                    <span class="employee-tab-error hidden" data-tab-error="employment" aria-label="Has errors"></span>
                                </button>
                                <button type="button" role="tab" data-tab="personal" aria-selected="false" class="employee-tab">
                                    <i class="ph ph-user text-base"></i><span>Personal</span>
                                    <span class="employee-tab-error hidden" data-tab-error="personal" aria-label="Has errors"></span>
                                </button>
                                <button type="button" role="tab" data-tab="contact" aria-selected="false" class="employee-tab">
                                    <i class="ph ph-envelope-simple text-base"></i><span>Contact</span>
                                    <span class="employee-tab-error hidden" data-tab-error="contact" aria-label="Has errors"></span>
                                </button>
                                <button type="button" role="tab" data-tab="documents" aria-selected="false" class="employee-tab">
                                    <i class="ph ph-identification-card text-base"></i><span>Documents</span>
                                    <span class="employee-tab-error hidden" data-tab-error="documents" aria-label="Has errors"></span>
                                </button>
                            </nav>
                            <div class="flex flex-shrink-0 items-center gap-3 pb-2 sm:pb-2.5" data-form-progress-wrap>
                                <div class="employee-progress-track" data-progress-segments aria-hidden="true">
                                    <span class="employee-progress-seg is-current" data-progress-seg="employment"></span>
                                    <span class="employee-progress-seg" data-progress-seg="personal"></span>
                                    <span class="employee-progress-seg" data-progress-seg="contact"></span>
                                    <span class="employee-progress-seg" data-progress-seg="documents"></span>
                                </div>
                                <p class="text-xs font-medium text-text-secondary" data-form-progress>0 of 4 sections started</p>
                            </div>
                        </div>
                    </div>

                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain p-4 sm:p-5">
                        <div data-temp-password-banner class="hidden rounded-xl border border-primary/30 bg-primary-soft px-3 py-2.5 text-sm">
                            <p class="font-semibold text-heading">Temporary login password</p>
                            <p class="text-xs text-muted mt-0.5">Share this once with the employee. It will not be shown again.</p>
                            <div class="mt-2 flex flex-col sm:flex-row gap-2 sm:items-center">
                                <code data-temp-password class="flex-1 rounded-lg border border-border bg-surface px-3 py-2 font-mono text-sm text-heading"></code>
                                <button type="button" data-copy-temp-password class="h-10 px-3 rounded-xl border border-border text-sm font-medium">Copy</button>
                            </div>
                        </div>

                        {{-- Employment --}}
                        <div data-tab-panel="employment" role="tabpanel" class="space-y-5">
                            <section class="rounded-2xl border border-border bg-surface p-4 sm:p-5 space-y-4" data-photo-section>
                                <div class="flex items-center gap-2 border-b border-border pb-3">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary-soft text-primary">
                                        <i class="ph ph-camera text-base"></i>
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-semibold tracking-wide text-heading">Profile photo</h4>
                                        <p class="text-xs text-muted">Shown on Employees, Security, and the user menu</p>
                                    </div>
                                </div>

                                <div
                                    data-photo-dropzone
                                    class="employee-photo-dropzone flex flex-col items-center justify-center gap-4 rounded-2xl px-4 py-5 sm:flex-row sm:justify-start sm:gap-5"
                                >
                                    <button
                                        type="button"
                                        data-photo-trigger
                                        class="group relative h-24 w-24 flex-shrink-0 rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                                        aria-label="Upload profile photo"
                                    >
                                        <span data-photo-preview class="flex h-full w-full items-center justify-center overflow-hidden rounded-full border-2 border-dashed border-primary/50 bg-primary text-3xl font-semibold text-white transition group-hover:border-primary">
                                            <img data-photo-preview-img src="" alt="" class="hidden h-full w-full object-cover">
                                            <span data-photo-preview-initial>E</span>
                                        </span>
                                        <span class="absolute inset-0 flex items-center justify-center rounded-full bg-black/45 text-white opacity-0 transition group-hover:opacity-100 group-focus-visible:opacity-100">
                                            <i class="ph ph-camera text-2xl"></i>
                                        </span>
                                    </button>
                                    <div class="flex flex-col items-center gap-2 min-w-0 sm:items-start">
                                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="sr-only" data-photo-input>
                                        <button type="button" data-photo-trigger class="inline-flex h-10 items-center gap-1.5 rounded-xl border border-border-strong bg-surface px-3.5 text-sm font-semibold text-heading shadow-sm hover:bg-subtle transition-colors">
                                            <i class="ph ph-upload-simple text-base text-primary"></i>
                                            Choose photo
                                        </button>
                                        <p class="text-xs text-muted">Click avatar or button · JPG, PNG, WebP · max 2 MB</p>
                                        <label class="inline-flex items-center gap-2 text-sm text-text-secondary cursor-pointer">
                                            <input type="checkbox" name="remove_photo" value="1" class="rounded border-border accent-primary">
                                            Remove current photo
                                        </label>
                                        <p class="hidden text-xs text-danger" data-error="photo"></p>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-2xl border border-border bg-surface p-4 sm:p-5 space-y-4">
                                <div class="flex items-center gap-2 border-b border-border pb-3">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary-soft text-primary">
                                        <i class="ph ph-briefcase text-base"></i>
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-semibold tracking-wide text-heading">Employment</h4>
                                        <p class="text-xs text-muted">Identity and job details</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="ui-label ui-label-required" for="emp-first-name">First name</label>
                                        <input id="emp-first-name" name="first_name" required class="ui-input" placeholder="e.g. Juan" autocomplete="given-name">
                                        <p class="hidden text-xs text-danger mt-1" data-error="first_name"></p>
                                    </div>
                                    <div>
                                        <label class="ui-label" for="emp-middle-name">Middle name</label>
                                        <input id="emp-middle-name" name="middle_name" class="ui-input" autocomplete="additional-name">
                                    </div>
                                    <div>
                                        <label class="ui-label ui-label-required" for="emp-last-name">Last name</label>
                                        <input id="emp-last-name" name="last_name" required class="ui-input" placeholder="e.g. Dela Cruz" autocomplete="family-name">
                                        <p class="hidden text-xs text-danger mt-1" data-error="last_name"></p>
                                    </div>
                                    <div>
                                        <label class="ui-label" for="emp-suffix">Suffix</label>
                                        <input id="emp-suffix" name="suffix" class="ui-input" placeholder="Jr., III">
                                    </div>
                                    <div>
                                        <label class="ui-label ui-label-required" for="emp-number">Employee number</label>
                                        <input id="emp-number" name="employee_number" required class="ui-input" placeholder="e.g. EMP-0245" autocomplete="off" spellcheck="false">
                                        <p class="hidden text-xs text-danger mt-1" data-error="employee_number"></p>
                                    </div>
                                    <div>
                                        <label class="ui-label" for="emp-department">Department</label>
                                        <select id="emp-department" name="department_id" class="ui-select">
                                            <option value="">— Select —</option>
                                        </select>
                                        <p class="hidden text-xs text-danger mt-1" data-error="department_id"></p>
                                    </div>
                                    <div>
                                        <label class="ui-label" for="emp-position">Position</label>
                                        <input id="emp-position" name="position_title" class="ui-input" placeholder="e.g. HR Specialist">
                                    </div>
                                    <div>
                                        <label class="ui-label ui-label-required" for="emp-status">Employment status</label>
                                        <select id="emp-status" name="employment_status" class="ui-select"></select>
                                        <p class="hidden text-xs text-danger mt-1" data-error="employment_status"></p>
                                    </div>
                                    <div>
                                        <label class="ui-label" for="emp-hired">Date hired</label>
                                        <input id="emp-hired" name="date_hired" type="date" class="ui-input">
                                    </div>
                                    <div>
                                        <label class="ui-label" for="emp-regularized">Date regularized</label>
                                        <input id="emp-regularized" name="date_regularized" type="date" class="ui-input">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="ui-label" for="emp-separated">Date separated</label>
                                        <input id="emp-separated" name="date_separated" type="date" class="ui-input sm:max-w-[calc(50%-0.5rem)]">
                                        <p class="text-xs text-muted mt-1.5">Optional — leave blank for active employees.</p>
                                    </div>
                                </div>
                            </section>
                        </div>

                        {{-- Personal --}}
                        <div data-tab-panel="personal" role="tabpanel" class="hidden space-y-5">
                            <section class="rounded-2xl border border-border bg-surface p-4 sm:p-5 space-y-4">
                                <div class="flex items-center gap-2 border-b border-border pb-3">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary-soft text-primary">
                                        <i class="ph ph-user text-base"></i>
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-semibold tracking-wide text-heading">Personal</h4>
                                        <p class="text-xs text-muted">Demographics and civil status</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="ui-label" for="emp-birth">Birth date</label>
                                        <input id="emp-birth" name="birth_date" type="date" class="ui-input">
                                    </div>
                                    <div>
                                        <label class="ui-label" for="emp-gender">Gender</label>
                                        <select id="emp-gender" name="gender" class="ui-select">
                                            <option value="">— Select —</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="ui-label" for="emp-civil">Civil status</label>
                                        <select id="emp-civil" name="civil_status" class="ui-select">
                                            <option value="">— Select —</option>
                                            <option value="single">Single</option>
                                            <option value="married">Married</option>
                                            <option value="widowed">Widowed</option>
                                            <option value="separated">Separated</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="ui-label" for="emp-nationality">Nationality</label>
                                        <input id="emp-nationality" name="nationality" class="ui-input" value="Filipino">
                                    </div>
                                </div>
                            </section>
                        </div>

                        {{-- Contact --}}
                        <div data-tab-panel="contact" role="tabpanel" class="hidden space-y-5">
                            <section class="rounded-2xl border border-border bg-surface p-4 sm:p-5 space-y-4">
                                <div class="flex items-center gap-2 border-b border-border pb-3">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary-soft text-primary">
                                        <i class="ph ph-envelope-simple text-base"></i>
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-semibold tracking-wide text-heading">Contact</h4>
                                        <p class="text-xs text-muted">Login email and address</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="ui-label ui-label-required" for="emp-email">Email (login)</label>
                                        <input id="emp-email" name="email" type="email" required class="ui-input" placeholder="e.g. juan.delacruz@pcspc.local" autocomplete="email">
                                        <p class="hidden text-xs text-danger mt-1" data-error="email"></p>
                                    </div>
                                    <div>
                                        <label class="ui-label" for="emp-mobile">Mobile</label>
                                        <input id="emp-mobile" name="mobile" class="ui-input" placeholder="e.g. 09171234567" autocomplete="tel">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="ui-label" for="emp-address">Address</label>
                                        <input id="emp-address" name="address_line" class="ui-input" placeholder="Street, barangay, building">
                                    </div>
                                    <div>
                                        <label class="ui-label" for="emp-city">City</label>
                                        <input id="emp-city" name="city" class="ui-input">
                                    </div>
                                    <div>
                                        <label class="ui-label" for="emp-province">Province</label>
                                        <input id="emp-province" name="province" class="ui-input">
                                    </div>
                                    <div>
                                        <label class="ui-label" for="emp-zip">ZIP code</label>
                                        <input id="emp-zip" name="zip_code" class="ui-input" placeholder="e.g. 1000">
                                    </div>
                                </div>
                            </section>
                        </div>

                        {{-- Documents / statutory --}}
                        <div data-tab-panel="documents" role="tabpanel" class="hidden space-y-5">
                            <section class="rounded-2xl border border-border bg-surface p-4 sm:p-5 space-y-4">
                                <div class="flex items-center gap-2 border-b border-border pb-3">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary-soft text-primary">
                                        <i class="ph ph-identification-card text-base"></i>
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-semibold tracking-wide text-heading">Statutory IDs</h4>
                                        <p class="text-xs text-muted">Encrypted and masked in listings</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="ui-label" for="emp-tin">TIN</label>
                                        <input id="emp-tin" name="tin" class="ui-input" placeholder="000-000-000-000">
                                    </div>
                                    <div>
                                        <label class="ui-label" for="emp-sss">SSS number</label>
                                        <input id="emp-sss" name="sss_number" class="ui-input" placeholder="00-0000000-0">
                                    </div>
                                    <div>
                                        <label class="ui-label" for="emp-philhealth">PhilHealth number</label>
                                        <input id="emp-philhealth" name="philhealth_number" class="ui-input">
                                    </div>
                                    <div>
                                        <label class="ui-label" for="emp-pagibig">Pag-IBIG number</label>
                                        <input id="emp-pagibig" name="pagibig_number" class="ui-input">
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>

                    <div class="flex-shrink-0 border-t border-border bg-surface px-4 pt-3 sm:px-5">
                        <div class="rounded-xl border border-border border-l-4 border-l-slate-400 bg-subtle/80 p-3.5 space-y-1.5">
                            <h5 class="text-sm font-semibold text-heading flex items-center gap-1.5">
                                <i class="ph ph-info text-base text-slate-500"></i>
                                Account provisioning
                            </h5>
                            <p class="text-sm text-text-secondary">
                                Saving creates or links a User login and assigns the <span class="font-semibold text-heading">Employee</span> role.
                            </p>
                            <p class="text-sm font-medium text-heading" data-linked-user>No linked login yet.</p>
                        </div>
                    </div>

                    <div class="flex flex-shrink-0 flex-col-reverse gap-2 border-t border-border bg-surface p-4 shadow-[0_-4px_12px_rgba(15,23,42,0.04)] sm:flex-row sm:items-center sm:justify-end sm:gap-3 sm:p-5">
                        <button type="button" data-modal-dismiss class="ui-btn-secondary min-h-[44px] px-5">Cancel</button>
                        @if ($canManage)
                            <button type="submit" class="ui-btn-primary min-h-[44px] min-w-[9.5rem]">Save employee</button>
                        @endif
                    </div>
                </form>
            </x-ui.modal>
        </x-slot:modals>
    </x-ui.data-panel>
</section>
@endsection
