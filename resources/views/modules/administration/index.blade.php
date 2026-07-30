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
            <p class="text-sm text-muted mt-1">Accounts, roles, MFA flags, and unlock controls.</p>
        </a>

        <a href="{{ route('modules.show', ['module' => 'audit']) }}" class="bg-surface border border-border rounded-2xl p-5 hover:border-primary/40 hover:bg-primary-soft/40 transition-colors group">
            <span class="w-10 h-10 rounded-xl bg-primary-soft text-primary inline-flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                <i class="ph ph-detective text-xl"></i>
            </span>
            <h3 class="text-base font-semibold text-heading mt-4">Audit Log</h3>
            <p class="text-sm text-muted mt-1">Review auth and security activity events.</p>
        </a>

        <div class="bg-surface border border-border rounded-2xl p-5 opacity-80">
            <span class="w-10 h-10 rounded-xl bg-subtle text-muted inline-flex items-center justify-center">
                <i class="ph ph-calendar-blank text-xl"></i>
            </span>
            <h3 class="text-base font-semibold text-heading mt-4">Holidays</h3>
            <p class="text-sm text-muted mt-1">Coming next — holiday calendar and workday rules.</p>
        </div>

        <div class="bg-surface border border-border rounded-2xl p-5 opacity-80">
            <span class="w-10 h-10 rounded-xl bg-subtle text-muted inline-flex items-center justify-center">
                <i class="ph ph-clock text-xl"></i>
            </span>
            <h3 class="text-base font-semibold text-heading mt-4">Shifts</h3>
            <p class="text-sm text-muted mt-1">Coming next — shift templates and schedules.</p>
        </div>
    </div>

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
                class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-strong transition-colors"
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
</section>
@endsection
