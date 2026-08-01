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

    <x-ui.data-panel id="employees-table" title="Employee 201" :create-label="$canManage ? 'Add employee' : null" :view-toggle="true">
        <x-slot:subtitle>Master employee records linked to User logins with the Employee role.</x-slot:subtitle>
        <x-slot:filters>
            <div class="relative w-full sm:col-span-2 lg:w-52 lg:flex-none">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                <input
                    type="search"
                    data-filter="search"
                    placeholder="Search employees…"
                    class="w-full h-10 min-h-[44px] sm:min-h-10 pl-9 pr-3 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                >
            </div>
            <select data-filter="department" class="w-full lg:w-40 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm">
                <option value="">All departments</option>
            </select>
            <select data-filter="status" class="w-full lg:w-36 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm">
                <option value="">All status</option>
            </select>
        </x-slot:filters>
        <x-slot:actions>
            <button
                type="button"
                data-action="export"
                class="inline-flex w-full sm:w-auto items-center justify-center gap-1.5 h-10 min-h-[44px] sm:min-h-10 px-3.5 rounded-xl border border-border bg-surface text-sm font-medium text-heading hover:bg-subtle transition-colors whitespace-nowrap"
            >
                <i class="ph ph-microsoft-excel-logo text-base text-success"></i>
                Export Excel
            </button>
        </x-slot:actions>

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

                    <x-employees.form-tabs />

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

                        {{-- Dependents --}}
                        <div data-tab-panel="dependents" role="tabpanel" class="hidden space-y-5" data-dependents-root>
                            <section class="rounded-2xl border border-border bg-surface p-4 sm:p-5 space-y-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between border-b border-border pb-3">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary-soft text-primary flex-shrink-0">
                                            <i class="ph ph-users-three text-base"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <h4 class="text-sm font-semibold tracking-wide text-heading">Dependents</h4>
                                            <p class="text-xs text-muted">Family members for benefits and emergency contact</p>
                                        </div>
                                    </div>
                                    @if ($canManage)
                                        <button
                                            type="button"
                                            data-dependent-add
                                            class="hidden inline-flex w-full sm:w-auto items-center justify-center gap-1.5 h-10 min-h-[44px] sm:min-h-10 px-3.5 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-strong transition-colors whitespace-nowrap"
                                        >
                                            <i class="ph ph-plus text-base"></i>
                                            Add dependent
                                        </button>
                                    @endif
                                </div>

                                <div data-dependents-locked class="rounded-xl border border-dashed border-border bg-subtle/50 px-4 py-6 text-center">
                                    <p class="text-sm font-medium text-heading">Save the employee first</p>
                                    <p class="text-xs text-muted mt-1">Dependents can be added after the 201 record is created.</p>
                                </div>

                                <div data-dependents-empty class="hidden rounded-xl border border-dashed border-border bg-subtle/50 px-4 py-6 text-center">
                                    <p class="text-sm font-medium text-heading">No dependents yet</p>
                                    <p class="text-xs text-muted mt-1">Add a spouse, child, or other dependent for this employee.</p>
                                </div>

                                <div data-dependents-list class="space-y-3"></div>
                            </section>
                        </div>

                        {{-- Education --}}
                        <div data-tab-panel="education" role="tabpanel" class="hidden space-y-5">
                            <section class="rounded-2xl border border-border bg-surface p-4 sm:p-5 space-y-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between border-b border-border pb-3">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary-soft text-primary flex-shrink-0">
                                            <i class="ph ph-graduation-cap text-base"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <h4 class="text-sm font-semibold tracking-wide text-heading">Education</h4>
                                            <p class="text-xs text-muted">Schools, degrees, and highest attainment</p>
                                        </div>
                                    </div>
                                    @if ($canManage)
                                        <button type="button" data-education-add class="hidden inline-flex w-full sm:w-auto items-center justify-center gap-1.5 h-10 min-h-[44px] sm:min-h-10 px-3.5 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-strong transition-colors whitespace-nowrap">
                                            <i class="ph ph-plus text-base"></i>
                                            Add education
                                        </button>
                                    @endif
                                </div>
                                <div data-educations-locked class="rounded-xl border border-dashed border-border bg-subtle/50 px-4 py-6 text-center">
                                    <p class="text-sm font-medium text-heading">Save the employee first</p>
                                    <p class="text-xs text-muted mt-1">Education records can be added after the 201 is created.</p>
                                </div>
                                <div data-educations-empty class="hidden rounded-xl border border-dashed border-border bg-subtle/50 px-4 py-6 text-center">
                                    <p class="text-sm font-medium text-heading">No education records yet</p>
                                    <p class="text-xs text-muted mt-1">Add schools and degrees for this employee.</p>
                                </div>
                                <div data-educations-list class="space-y-3"></div>
                            </section>
                        </div>

                        {{-- Employment history --}}
                        <div data-tab-panel="history" role="tabpanel" class="hidden space-y-5">
                            <section class="rounded-2xl border border-border bg-surface p-4 sm:p-5 space-y-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between border-b border-border pb-3">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary-soft text-primary flex-shrink-0">
                                            <i class="ph ph-clock-counter-clockwise text-base"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <h4 class="text-sm font-semibold tracking-wide text-heading">Employment history</h4>
                                            <p class="text-xs text-muted">Previous employers and positions</p>
                                        </div>
                                    </div>
                                    @if ($canManage)
                                        <button type="button" data-history-add class="hidden inline-flex w-full sm:w-auto items-center justify-center gap-1.5 h-10 min-h-[44px] sm:min-h-10 px-3.5 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-strong transition-colors whitespace-nowrap">
                                            <i class="ph ph-plus text-base"></i>
                                            Add history
                                        </button>
                                    @endif
                                </div>
                                <div data-history-locked class="rounded-xl border border-dashed border-border bg-subtle/50 px-4 py-6 text-center">
                                    <p class="text-sm font-medium text-heading">Save the employee first</p>
                                    <p class="text-xs text-muted mt-1">Employment history can be added after the 201 is created.</p>
                                </div>
                                <div data-history-empty class="hidden rounded-xl border border-dashed border-border bg-subtle/50 px-4 py-6 text-center">
                                    <p class="text-sm font-medium text-heading">No employment history yet</p>
                                    <p class="text-xs text-muted mt-1">Add prior employers and roles for this employee.</p>
                                </div>
                                <div data-history-list class="space-y-3"></div>
                            </section>
                        </div>

                        {{-- Training (EMP-005 stub — full CRUD in P6) --}}
                        <div data-tab-panel="training" role="tabpanel" class="hidden space-y-5">
                            <x-employees.stub-records-panel
                                title="Training records"
                                subtitle="Courses, certifications, and seminars (EMP-005)"
                                icon="ph-chalkboard-teacher"
                                badge="EMP-005 · P6"
                                phase-note="Training confirmation and certificates will be managed here in Phase 6. The Training module page tracks delivery scope."
                                :module-href="route('modules.show', ['module' => 'training'])"
                                module-label="Training module"
                            />
                        </div>

                        {{-- Medical (EMP-006 stub — full CRUD in P6) --}}
                        <div data-tab-panel="medical" role="tabpanel" class="hidden space-y-5">
                            <x-employees.stub-records-panel
                                title="Medical records"
                                subtitle="APE, vaccines, and medical notes (EMP-006)"
                                icon="ph-heartbeat"
                                badge="EMP-006 · P6"
                                phase-note="Secure medical records and reimbursements land in Phase 6. Sensitive fields will use encryption and RBAC."
                                :module-href="route('modules.show', ['module' => 'medical'])"
                                module-label="Medical module"
                            />
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

            <x-ui.modal
                id="dependent-modal"
                title="Add dependent"
                subtitle="Linked to this employee 201 record"
                max-width="max-w-lg"
                class="z-[80]"
            >
                <form id="dependent-form" class="flex min-h-0 flex-1 flex-col" novalidate>
                    <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-4 sm:p-5 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="ui-label ui-label-required" for="dep-first-name">First name</label>
                                <input id="dep-first-name" name="first_name" required class="ui-input" autocomplete="off">
                                <p class="hidden text-xs text-danger mt-1" data-error="first_name"></p>
                            </div>
                            <div>
                                <label class="ui-label" for="dep-middle-name">Middle name</label>
                                <input id="dep-middle-name" name="middle_name" class="ui-input" autocomplete="off">
                            </div>
                            <div>
                                <label class="ui-label ui-label-required" for="dep-last-name">Last name</label>
                                <input id="dep-last-name" name="last_name" required class="ui-input" autocomplete="off">
                                <p class="hidden text-xs text-danger mt-1" data-error="last_name"></p>
                            </div>
                            <div>
                                <label class="ui-label" for="dep-suffix">Suffix</label>
                                <input id="dep-suffix" name="suffix" class="ui-input" placeholder="Jr., III">
                            </div>
                            <div>
                                <label class="ui-label ui-label-required" for="dep-relationship">Relationship</label>
                                <select id="dep-relationship" name="relationship" required class="ui-select">
                                    <option value="spouse">Spouse</option>
                                    <option value="child">Child</option>
                                    <option value="parent">Parent</option>
                                    <option value="sibling">Sibling</option>
                                    <option value="other">Other</option>
                                </select>
                                <p class="hidden text-xs text-danger mt-1" data-error="relationship"></p>
                            </div>
                            <div>
                                <label class="ui-label" for="dep-birth-date">Birth date</label>
                                <input id="dep-birth-date" name="birth_date" type="date" class="ui-input">
                                <p class="hidden text-xs text-danger mt-1" data-error="birth_date"></p>
                            </div>
                            <div>
                                <label class="ui-label" for="dep-gender">Gender</label>
                                <select id="dep-gender" name="gender" class="ui-select">
                                    <option value="">— Select —</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="ui-label" for="dep-mobile">Mobile</label>
                                <input id="dep-mobile" name="mobile" class="ui-input" placeholder="e.g. 09171234567" autocomplete="tel">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="ui-label" for="dep-notes">Notes</label>
                                <input id="dep-notes" name="notes" class="ui-input" maxlength="500" placeholder="Optional">
                            </div>
                        </div>
                        <div class="flex flex-col gap-2.5 sm:flex-row sm:gap-5">
                            <label class="inline-flex items-center gap-2 text-sm text-text-secondary cursor-pointer">
                                <input type="checkbox" name="is_beneficiary" value="1" class="rounded border-border accent-primary">
                                Beneficiary
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-text-secondary cursor-pointer">
                                <input type="checkbox" name="is_emergency_contact" value="1" class="rounded border-border accent-primary">
                                Emergency contact
                            </label>
                        </div>
                    </div>
                    <div class="flex flex-shrink-0 flex-col-reverse gap-2 border-t border-border bg-surface p-4 sm:flex-row sm:justify-end sm:gap-3 sm:p-5">
                        <button type="button" data-modal-dismiss class="ui-btn-secondary min-h-[44px] px-5">Cancel</button>
                        <button type="submit" class="ui-btn-primary min-h-[44px] min-w-[8.5rem]">Save dependent</button>
                    </div>
                </form>
            </x-ui.modal>

            <x-ui.modal
                id="education-modal"
                title="Add education"
                subtitle="Linked to this employee 201 record"
                max-width="max-w-lg"
                class="z-[80]"
            >
                <form id="education-form" class="flex min-h-0 flex-1 flex-col" novalidate>
                    <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-4 sm:p-5 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="ui-label ui-label-required" for="edu-institution">School / institution</label>
                                <input id="edu-institution" name="institution" required class="ui-input" autocomplete="off">
                                <p class="hidden text-xs text-danger mt-1" data-error="institution"></p>
                            </div>
                            <div>
                                <label class="ui-label ui-label-required" for="edu-level">Level</label>
                                <select id="edu-level" name="level" required class="ui-select">
                                    <option value="bachelor">Bachelor's</option>
                                    <option value="master">Master's</option>
                                    <option value="doctorate">Doctorate</option>
                                    <option value="associate">Associate</option>
                                    <option value="vocational">Vocational</option>
                                    <option value="high_school">High school</option>
                                    <option value="elementary">Elementary</option>
                                    <option value="other">Other</option>
                                </select>
                                <p class="hidden text-xs text-danger mt-1" data-error="level"></p>
                            </div>
                            <div>
                                <label class="ui-label" for="edu-course">Degree / course</label>
                                <input id="edu-course" name="degree_or_course" class="ui-input" placeholder="e.g. BS Computer Science">
                            </div>
                            <div>
                                <label class="ui-label" for="edu-year-started">Year started</label>
                                <input id="edu-year-started" name="year_started" type="number" min="1950" max="2100" class="ui-input" placeholder="e.g. 2015">
                                <p class="hidden text-xs text-danger mt-1" data-error="year_started"></p>
                            </div>
                            <div>
                                <label class="ui-label" for="edu-year-ended">Year ended</label>
                                <input id="edu-year-ended" name="year_ended" type="number" min="1950" max="2100" class="ui-input" placeholder="e.g. 2019">
                                <p class="hidden text-xs text-danger mt-1" data-error="year_ended"></p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="ui-label" for="edu-honors">Honors</label>
                                <input id="edu-honors" name="honors" class="ui-input" placeholder="e.g. Cum Laude">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="ui-label" for="edu-notes">Notes</label>
                                <input id="edu-notes" name="notes" class="ui-input" maxlength="500" placeholder="Optional">
                            </div>
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm text-text-secondary cursor-pointer">
                            <input type="checkbox" name="is_highest" value="1" class="rounded border-border accent-primary">
                            Highest educational attainment
                        </label>
                    </div>
                    <div class="flex flex-shrink-0 flex-col-reverse gap-2 border-t border-border bg-surface p-4 sm:flex-row sm:justify-end sm:gap-3 sm:p-5">
                        <button type="button" data-modal-dismiss class="ui-btn-secondary min-h-[44px] px-5">Cancel</button>
                        <button type="submit" class="ui-btn-primary min-h-[44px] min-w-[8.5rem]">Save education</button>
                    </div>
                </form>
            </x-ui.modal>

            <x-ui.modal
                id="employment-history-modal"
                title="Add employment history"
                subtitle="Linked to this employee 201 record"
                max-width="max-w-lg"
                class="z-[80]"
            >
                <form id="employment-history-form" class="flex min-h-0 flex-1 flex-col" novalidate>
                    <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-4 sm:p-5 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="ui-label ui-label-required" for="hist-employer">Employer</label>
                                <input id="hist-employer" name="employer_name" required class="ui-input" autocomplete="off">
                                <p class="hidden text-xs text-danger mt-1" data-error="employer_name"></p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="ui-label ui-label-required" for="hist-position">Position</label>
                                <input id="hist-position" name="position_title" required class="ui-input" autocomplete="off">
                                <p class="hidden text-xs text-danger mt-1" data-error="position_title"></p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="ui-label" for="hist-location">Location</label>
                                <input id="hist-location" name="location" class="ui-input" placeholder="City / site">
                            </div>
                            <div>
                                <label class="ui-label ui-label-required" for="hist-from">Start date</label>
                                <input id="hist-from" name="date_from" type="date" required class="ui-input">
                                <p class="hidden text-xs text-danger mt-1" data-error="date_from"></p>
                            </div>
                            <div>
                                <label class="ui-label" for="hist-to">End date</label>
                                <input id="hist-to" name="date_to" type="date" class="ui-input">
                                <p class="hidden text-xs text-danger mt-1" data-error="date_to"></p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="ui-label" for="hist-notes">Notes</label>
                                <input id="hist-notes" name="notes" class="ui-input" maxlength="500" placeholder="Optional">
                            </div>
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm text-text-secondary cursor-pointer">
                            <input type="checkbox" name="is_current" value="1" class="rounded border-border accent-primary">
                            This is the current job
                        </label>
                    </div>
                    <div class="flex flex-shrink-0 flex-col-reverse gap-2 border-t border-border bg-surface p-4 sm:flex-row sm:justify-end sm:gap-3 sm:p-5">
                        <button type="button" data-modal-dismiss class="ui-btn-secondary min-h-[44px] px-5">Cancel</button>
                        <button type="submit" class="ui-btn-primary min-h-[44px] min-w-[8.5rem]">Save history</button>
                    </div>
                </form>
            </x-ui.modal>
        </x-slot:modals>
    </x-ui.data-panel>
</section>
@endsection
