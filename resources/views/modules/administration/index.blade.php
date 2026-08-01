@extends('layouts.app')

@section('title', ($module['label'] ?? 'Administration') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'Administration')

@section('content')
<section class="space-y-4 md:space-y-5" data-module="administration">
    <div class="bg-surface border border-border rounded-2xl p-5 md:p-6">
        <p class="text-xs font-bold tracking-wide text-faint uppercase">{{ $module['section'] ?? 'System' }}</p>
        <h2 class="text-2xl font-bold text-heading mt-1">{{ $module['label'] ?? 'Administration' }}</h2>
        <p class="text-sm text-text-secondary mt-2 max-w-3xl">{{ $module['summary'] ?? '' }}</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 md:gap-4">
        <a href="{{ route('modules.show', ['module' => 'departments']) }}" class="bg-surface border border-border rounded-2xl p-5 hover:border-primary/40 hover:bg-primary-soft/40 transition-colors group">
            <span class="w-10 h-10 rounded-xl bg-primary-soft text-primary inline-flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                <i class="ph ph-buildings text-xl"></i>
            </span>
            <h3 class="text-base font-semibold text-heading mt-4">Departments</h3>
            <p class="text-sm text-muted mt-1">Manage org structure under People → Departments.</p>
        </a>

        <a href="{{ route('modules.show', ['module' => 'security']) }}" class="bg-surface border border-border rounded-2xl p-5 hover:border-primary/40 hover:bg-primary-soft/40 transition-colors group">
            <span class="w-10 h-10 rounded-xl bg-primary-soft text-primary inline-flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                <i class="ph ph-shield-check text-xl"></i>
            </span>
            <h3 class="text-base font-semibold text-heading mt-4">Users & Security</h3>
            <p class="text-sm text-muted mt-1">Accounts, roles, MFA, and password policy (ADM-005).</p>
        </a>

        <a href="{{ route('modules.show', ['module' => 'audit']) }}" class="bg-surface border border-border rounded-2xl p-5 hover:border-primary/40 hover:bg-primary-soft/40 transition-colors group">
            <span class="w-10 h-10 rounded-xl bg-primary-soft text-primary inline-flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                <i class="ph ph-detective text-xl"></i>
            </span>
            <h3 class="text-base font-semibold text-heading mt-4">Audit Log</h3>
            <p class="text-sm text-muted mt-1">Review auth and security activity events.</p>
        </a>

        <a href="{{ route('modules.show', ['module' => 'lookups']) }}" class="bg-surface border border-border rounded-2xl p-5 hover:border-primary/40 hover:bg-primary-soft/40 transition-colors group">
            <span class="w-10 h-10 rounded-xl bg-primary-soft text-primary inline-flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                <i class="ph ph-list-bullets text-xl"></i>
            </span>
            <h3 class="text-base font-semibold text-heading mt-4">Lookups</h3>
            <p class="text-sm text-muted mt-1">ADM-006 master data tables powering dropdowns across modules.</p>
        </a>

        <a href="{{ route('modules.show', ['module' => 'holidays']) }}" class="bg-surface border border-border rounded-2xl p-5 hover:border-primary/40 hover:bg-primary-soft/40 transition-colors group">
            <span class="w-10 h-10 rounded-xl bg-primary-soft text-primary inline-flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                <i class="ph ph-calendar-blank text-xl"></i>
            </span>
            <h3 class="text-base font-semibold text-heading mt-4">Holidays</h3>
            <p class="text-sm text-muted mt-1">ADM-008 holiday calendar, types, and double-pay flags.</p>
        </a>

        <a href="{{ route('modules.show', ['module' => 'shifts']) }}" class="bg-surface border border-border rounded-2xl p-5 hover:border-primary/40 hover:bg-primary-soft/40 transition-colors group">
            <span class="w-10 h-10 rounded-xl bg-primary-soft text-primary inline-flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                <i class="ph ph-clock text-xl"></i>
            </span>
            <h3 class="text-base font-semibold text-heading mt-4">Shifts</h3>
            <p class="text-sm text-muted mt-1">ADM-009 shift templates. Assign them under Schedules.</p>
        </a>

        <a href="{{ route('modules.show', ['module' => 'schedules']) }}" class="bg-surface border border-border rounded-2xl p-5 hover:border-primary/40 hover:bg-primary-soft/40 transition-colors group">
            <span class="w-10 h-10 rounded-xl bg-primary-soft text-primary inline-flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                <i class="ph ph-calendar-check text-xl"></i>
            </span>
            <h3 class="text-base font-semibold text-heading mt-4">Schedules</h3>
            <p class="text-sm text-muted mt-1">ADM-009 assign shifts to employees or departments.</p>
        </a>
    </div>

    <div class="bg-surface border border-border rounded-2xl p-5 md:p-6" data-spa-module="system-parameters">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <p class="text-xs font-bold tracking-wide text-faint uppercase">ADM-010</p>
                <h3 class="text-lg font-semibold text-heading mt-1">System parameters</h3>
                <p class="text-sm text-muted mt-1 max-w-2xl">
                    Company identity and logo, timezone, leave-year start, and attendance defaults used across modules.
                </p>
            </div>
            <button
                type="button"
                data-save-parameters
                class="inline-flex items-center justify-center h-10 min-h-[44px] px-4 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-strong transition-colors"
            >
                Save parameters
            </button>
        </div>

        <form id="system-parameters-form" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5" novalidate>
            <div class="md:col-span-2 rounded-2xl border border-border bg-subtle/60 p-4 sm:p-5" data-logo-section>
                <div class="flex flex-col sm:flex-row sm:items-start gap-4">
                    <div
                        class="flex h-20 w-40 flex-shrink-0 items-center justify-center overflow-hidden rounded-xl border border-border bg-white px-3 py-2"
                        data-logo-preview-wrap
                    >
                        <img
                            data-logo-preview
                            data-brand-logo
                            src="{{ asset('images/brand/pcspc-logo.png') }}"
                            alt="Company logo preview"
                            class="max-h-full max-w-full object-contain"
                        >
                    </div>
                    <div class="min-w-0 flex-1 space-y-3">
                        <div>
                            <p class="text-sm font-semibold text-heading">Company logo</p>
                            <p class="text-xs text-muted mt-0.5">
                                Shown on login, sidebar, and email templates. JPG, PNG, or WebP · max 2 MB.
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <input
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                class="sr-only"
                                data-logo-input
                            >
                            <button
                                type="button"
                                data-logo-upload
                                class="inline-flex h-10 min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 text-sm font-medium text-heading hover:bg-subtle transition-colors"
                            >
                                <i class="ph ph-upload-simple text-base"></i>
                                Upload logo
                            </button>
                            <button
                                type="button"
                                data-logo-remove
                                class="hidden h-10 min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-danger/30 bg-danger-soft px-3.5 text-sm font-medium text-heading hover:bg-danger/10 transition-colors"
                            >
                                <i class="ph ph-arrow-counter-clockwise text-base"></i>
                                Reset to default
                            </button>
                        </div>
                        <p class="hidden text-xs text-danger" data-error="logo"></p>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1.5" for="company_name">Company name</label>
                <input id="company_name" name="company_name" type="text" maxlength="200" class="w-full h-11 px-3 rounded-xl border border-border bg-subtle text-sm" required>
                <p class="hidden text-xs text-danger mt-1" data-error="company_name"></p>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1.5" for="company_short_name">Short name</label>
                <input id="company_short_name" name="company_short_name" type="text" maxlength="40" class="w-full h-11 px-3 rounded-xl border border-border bg-subtle text-sm" required>
                <p class="hidden text-xs text-danger mt-1" data-error="company_short_name"></p>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1.5" for="timezone">Timezone</label>
                <select id="timezone" name="timezone" class="w-full h-11 px-3 rounded-xl border border-border bg-subtle text-sm" required></select>
                <p class="hidden text-xs text-danger mt-1" data-error="timezone"></p>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1.5" for="date_format">Date format</label>
                <select id="date_format" name="date_format" class="w-full h-11 px-3 rounded-xl border border-border bg-subtle text-sm" required></select>
                <p class="hidden text-xs text-danger mt-1" data-error="date_format"></p>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1.5" for="currency_code">Currency code</label>
                <input id="currency_code" name="currency_code" type="text" maxlength="3" class="w-full h-11 px-3 rounded-xl border border-border bg-subtle text-sm uppercase" required>
                <p class="hidden text-xs text-danger mt-1" data-error="currency_code"></p>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1.5" for="support_email">Support email</label>
                <input id="support_email" name="support_email" type="email" maxlength="150" class="w-full h-11 px-3 rounded-xl border border-border bg-subtle text-sm" required>
                <p class="hidden text-xs text-danger mt-1" data-error="support_email"></p>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1.5" for="leave_year_start_month">Leave year starts (month)</label>
                <select id="leave_year_start_month" name="leave_year_start_month" class="w-full h-11 px-3 rounded-xl border border-border bg-subtle text-sm" required>
                    @foreach (range(1, 12) as $month)
                        <option value="{{ $month }}">{{ \Carbon\Carbon::create()->month($month)->format('F') }}</option>
                    @endforeach
                </select>
                <p class="hidden text-xs text-danger mt-1" data-error="leave_year_start_month"></p>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1.5" for="week_start">Week starts on</label>
                <select id="week_start" name="week_start" class="w-full h-11 px-3 rounded-xl border border-border bg-subtle text-sm" required>
                    <option value="monday">Monday</option>
                    <option value="sunday">Sunday</option>
                </select>
                <p class="hidden text-xs text-danger mt-1" data-error="week_start"></p>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1.5" for="rest_day_holiday_paid_hours">Rest-day holiday paid hours</label>
                <input id="rest_day_holiday_paid_hours" name="rest_day_holiday_paid_hours" type="number" min="0" max="24" class="w-full h-11 px-3 rounded-xl border border-border bg-subtle text-sm" required>
                <p class="text-xs text-muted mt-1">TOR default: 8 hours when a holiday falls on a rest day.</p>
                <p class="hidden text-xs text-danger mt-1" data-error="rest_day_holiday_paid_hours"></p>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1.5" for="default_grace_minutes">Default grace (minutes)</label>
                <input id="default_grace_minutes" name="default_grace_minutes" type="number" min="0" max="120" class="w-full h-11 px-3 rounded-xl border border-border bg-subtle text-sm" required>
                <p class="text-xs text-muted mt-1">Used when a shift has no grace override.</p>
                <p class="hidden text-xs text-danger mt-1" data-error="default_grace_minutes"></p>
            </div>
        </form>
    </div>
</section>
@endsection
