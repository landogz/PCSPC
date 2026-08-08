@extends('layouts.app')

@section('title', ($module['label'] ?? 'Leave') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'Leave')

@php
    $user = auth()->user();
    $canFile = $user?->hasPermission('leave.file') ?? false;
    $canManage = $user?->hasPermission('leave.manage') ?? false;
    $canApprove = ($user?->hasPermission('leave.approve') || $canManage) ?? false;
    $canViewBalances = $canApprove;
    $defaultTab = $canApprove ? 'approvals' : ($canFile ? 'requests' : ($canViewBalances ? 'balances' : 'types'));
@endphp

@section('content')
<section
    class="space-y-4 md:space-y-5"
    data-module="leave"
    data-can-file="{{ $canFile ? '1' : '0' }}"
    data-can-approve="{{ $canApprove ? '1' : '0' }}"
    data-can-manage="{{ $canManage ? '1' : '0' }}"
    data-can-view-balances="{{ $canViewBalances ? '1' : '0' }}"
>
    <div class="bg-surface border border-border rounded-2xl p-5 md:p-6">
        <p class="text-xs font-bold tracking-wide text-faint uppercase">{{ $module['section'] ?? 'People operations' }}</p>
        <h2 class="text-2xl font-bold text-heading mt-1">{{ $module['label'] ?? 'Leave Management' }}</h2>
        <p class="text-sm text-text-secondary mt-2 max-w-3xl">
            File leave with a mandatory reason, approve via Approver → HR (or HR-only for special types), and manage VL/SL credits.
        </p>

        <div class="mt-5 flex flex-wrap gap-2 border-b border-border" role="tablist" aria-label="Leave sections">
            @if ($canFile)
                <button type="button" data-leave-tab="requests" role="tab"
                    class="leave-tab h-10 min-h-[44px] sm:min-h-10 px-4 text-sm font-semibold border-b-2 -mb-px transition-colors {{ $defaultTab === 'requests' ? 'border-primary text-primary' : 'border-transparent text-muted hover:text-heading' }}"
                    aria-selected="{{ $defaultTab === 'requests' ? 'true' : 'false' }}">My requests</button>
            @endif
            @if ($canApprove)
                <button type="button" data-leave-tab="approvals" role="tab"
                    class="leave-tab h-10 min-h-[44px] sm:min-h-10 px-4 text-sm font-semibold border-b-2 -mb-px transition-colors {{ $defaultTab === 'approvals' ? 'border-primary text-primary' : 'border-transparent text-muted hover:text-heading' }}"
                    aria-selected="{{ $defaultTab === 'approvals' ? 'true' : 'false' }}">Approvals</button>
            @endif
            @if ($canViewBalances)
                <button type="button" data-leave-tab="balances" role="tab"
                    class="leave-tab h-10 min-h-[44px] sm:min-h-10 px-4 text-sm font-semibold border-b-2 -mb-px transition-colors {{ $defaultTab === 'balances' ? 'border-primary text-primary' : 'border-transparent text-muted hover:text-heading' }}"
                    aria-selected="{{ $defaultTab === 'balances' ? 'true' : 'false' }}">Balances</button>
            @endif
            <button type="button" data-leave-tab="types" role="tab"
                class="leave-tab h-10 min-h-[44px] sm:min-h-10 px-4 text-sm font-semibold border-b-2 -mb-px transition-colors {{ $defaultTab === 'types' ? 'border-primary text-primary' : 'border-transparent text-muted hover:text-heading' }}"
                aria-selected="{{ $defaultTab === 'types' ? 'true' : 'false' }}">Leave types</button>
        </div>
    </div>

    @if ($canFile)
        <div data-leave-panel="requests" class="{{ $defaultTab === 'requests' ? '' : 'hidden' }}">
            <x-ui.data-panel id="leave-my-requests" title="My leave requests" create-label="File leave">
                <x-slot:subtitle>Submit VL/SL (and other active types) with a reason. Pending requests can be cancelled.</x-slot:subtitle>
                <x-slot:filters>
                    <div class="relative w-full sm:col-span-2 lg:w-52 lg:flex-none">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                        <input type="search" data-filter="search" placeholder="Search reason…"
                            class="w-full h-10 min-h-[44px] sm:min-h-10 pl-9 pr-3 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                    </div>
                    <select data-filter="status" class="w-full lg:w-36 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm">
                        <option value="">All status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <select data-filter="leave_type" class="w-full lg:w-40 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm">
                        <option value="">All types</option>
                    </select>
                </x-slot:filters>
                <x-slot:head>
                    <tr>
                        <th class="px-4 py-3 font-semibold">Type</th>
                        <th class="px-4 py-3 font-semibold">Dates</th>
                        <th class="px-4 py-3 font-semibold text-right">Days</th>
                        <th class="px-4 py-3 font-semibold">Step</th>
                        <th class="px-4 py-3 font-semibold">Reason</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </x-slot:head>
            </x-ui.data-panel>
        </div>
    @endif

    @if ($canApprove)
        <div data-leave-panel="approvals" class="{{ $defaultTab === 'approvals' ? '' : 'hidden' }}">
            <x-ui.data-panel id="leave-approvals" title="Leave approvals">
                <x-slot:subtitle>Approve or reject at your step. Standard types need Approver then HR; requires-HR types go to HR only.</x-slot:subtitle>
                <x-slot:filters>
                    <div class="relative w-full sm:col-span-2 lg:w-52 lg:flex-none">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                        <input type="search" data-filter="search" placeholder="Search employee…"
                            class="w-full h-10 min-h-[44px] sm:min-h-10 pl-9 pr-3 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                    </div>
                    <select data-filter="status" class="w-full lg:w-36 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm">
                        <option value="pending">Pending</option>
                        <option value="">All status</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <select data-filter="leave_type" class="w-full lg:w-40 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm">
                        <option value="">All types</option>
                    </select>
                </x-slot:filters>
                <x-slot:head>
                    <tr>
                        <th class="px-4 py-3 font-semibold">Employee</th>
                        <th class="px-4 py-3 font-semibold">Type</th>
                        <th class="px-4 py-3 font-semibold">Dates</th>
                        <th class="px-4 py-3 font-semibold text-right">Days</th>
                        <th class="px-4 py-3 font-semibold">Step</th>
                        <th class="px-4 py-3 font-semibold">Reason</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </x-slot:head>
            </x-ui.data-panel>
        </div>
    @endif

    @if ($canViewBalances)
        <div data-leave-panel="balances" class="{{ $defaultTab === 'balances' ? '' : 'hidden' }}">
            <x-ui.data-panel id="leave-balances" title="Leave balances" :create-label="$canManage ? 'Adjust balance' : null">
                <x-slot:subtitle>Per-employee credits by leave year. Ending = beginning + earned + adjusted − used.</x-slot:subtitle>
                <x-slot:filters>
                    <div class="relative w-full sm:col-span-2 lg:w-52 lg:flex-none">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                        <input type="search" data-filter="search" placeholder="Search employee…"
                            class="w-full h-10 min-h-[44px] sm:min-h-10 pl-9 pr-3 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                    </div>
                    <select data-filter="leave_year" class="w-full lg:w-28 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm">
                        <option value="">Year</option>
                    </select>
                    <select data-filter="leave_type" class="w-full lg:w-40 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm">
                        <option value="">All types</option>
                    </select>
                </x-slot:filters>
                @if ($canManage)
                    <x-slot:actions>
                        <button type="button" data-action="run-accrual"
                            class="inline-flex w-full sm:w-auto items-center justify-center gap-1.5 h-10 min-h-[44px] sm:min-h-10 px-4 rounded-xl border border-border bg-surface text-sm font-medium text-heading hover:bg-subtle transition-colors whitespace-nowrap">
                            <i class="ph ph-calendar-plus text-base" aria-hidden="true"></i>
                            Run monthly accrual
                        </button>
                    </x-slot:actions>
                @endif
                <x-slot:head>
                    <tr>
                        <th class="px-4 py-3 font-semibold">Employee</th>
                        <th class="px-4 py-3 font-semibold">Type</th>
                        <th class="px-4 py-3 font-semibold">Year</th>
                        <th class="px-4 py-3 font-semibold text-right">Beg</th>
                        <th class="px-4 py-3 font-semibold text-right">Earned</th>
                        <th class="px-4 py-3 font-semibold text-right">Used</th>
                        <th class="px-4 py-3 font-semibold text-right">Adj</th>
                        <th class="px-4 py-3 font-semibold text-right">Ending</th>
                        @if ($canManage)
                            <th class="px-4 py-3 font-semibold text-right">Actions</th>
                        @endif
                    </tr>
                </x-slot:head>
            </x-ui.data-panel>
        </div>
    @endif

    <div data-leave-panel="types" class="{{ $defaultTab === 'types' ? '' : 'hidden' }}">
        <div class="bg-surface border border-border rounded-2xl overflow-hidden" data-leave-types-panel>
            <div class="border-b border-border p-4 md:p-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-heading">Leave types</h3>
                    <p class="text-sm text-muted mt-1">Active types can be filed. Accruing types receive monthly credits.</p>
                </div>
                @if ($canManage)
                    <button
                        type="button"
                        data-action="create-type"
                        class="inline-flex w-full sm:w-auto items-center justify-center gap-1.5 h-10 min-h-[44px] sm:min-h-10 px-4 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-strong transition-colors whitespace-nowrap"
                    >
                        <i class="ph ph-plus text-base" aria-hidden="true"></i>
                        Add leave type
                    </button>
                @endif
            </div>
            <div class="overflow-x-auto -webkit-overflow-scrolling-touch">
                <table class="w-full text-sm text-left min-w-[720px]">
                    <thead class="bg-subtle text-xs uppercase tracking-wide text-muted">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Code</th>
                            <th class="px-4 py-3 font-semibold">Name</th>
                            <th class="px-4 py-3 font-semibold">Flags</th>
                            <th class="px-4 py-3 font-semibold">Accruing</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            @if ($canManage)
                                <th class="px-4 py-3 font-semibold text-right">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody data-leave-types-body class="divide-y divide-border">
                        <tr><td colspan="{{ $canManage ? 6 : 5 }}" class="px-4 py-10 text-center text-muted">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($canFile)
        <x-ui.modal id="leave-file-modal" title="File leave" max-width="max-w-lg">
            <form id="leave-file-form" class="flex min-h-0 flex-1 flex-col" novalidate>
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain p-4 sm:p-5">
                    <div>
                        <label class="ui-label ui-label-required" for="leave-file-type">Leave type</label>
                        <select id="leave-file-type" name="leave_type_id" required class="ui-select">
                            <option value="">Select type…</option>
                        </select>
                        <p class="hidden text-xs text-danger mt-1" data-error="leave_type_id"></p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="ui-label ui-label-required" for="leave-file-start">Start date</label>
                            <input id="leave-file-start" name="start_date" type="date" required class="ui-input">
                            <p class="hidden text-xs text-danger mt-1" data-error="start_date"></p>
                        </div>
                        <div>
                            <label class="ui-label ui-label-required" for="leave-file-end">End date</label>
                            <input id="leave-file-end" name="end_date" type="date" required class="ui-input">
                            <p class="hidden text-xs text-danger mt-1" data-error="end_date"></p>
                        </div>
                    </div>
                    <p class="text-xs text-muted" data-leave-days-hint>Days are counted inclusively (calendar days).</p>
                    <div>
                        <label class="ui-label ui-label-required" for="leave-file-reason">Reason</label>
                        <textarea id="leave-file-reason" name="reason" rows="3" required maxlength="1000" class="ui-input" placeholder="Why are you taking leave?"></textarea>
                        <p class="hidden text-xs text-danger mt-1" data-error="reason"></p>
                    </div>
                </div>
                <div class="flex-shrink-0 border-t border-border p-4 sm:p-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" data-modal-dismiss class="ui-btn-secondary w-full sm:w-auto">
                        <i class="ph ph-x text-base" aria-hidden="true"></i>
                        Cancel
                    </button>
                    <button type="submit" class="ui-btn-primary w-full sm:w-auto">
                        <i class="ph ph-paper-plane-tilt text-base" aria-hidden="true"></i>
                        Submit request
                    </button>
                </div>
            </form>
        </x-ui.modal>
    @endif

    @if ($canApprove)
        <x-ui.modal id="leave-decide-modal" title="Decide leave request" max-width="max-w-md">
            <form id="leave-decide-form" class="flex min-h-0 flex-1 flex-col" novalidate>
                <input type="hidden" name="request_id" value="">
                <input type="hidden" name="decision" value="">
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain p-4 sm:p-5">
                    <p class="text-sm text-text-secondary" data-decide-summary></p>
                    <div>
                        <label class="ui-label" for="leave-decide-notes">Notes (optional)</label>
                        <textarea id="leave-decide-notes" name="approver_notes" rows="3" maxlength="1000" class="ui-input" placeholder="Visible to the employee"></textarea>
                        <p class="hidden text-xs text-danger mt-1" data-error="approver_notes"></p>
                    </div>
                </div>
                <div class="flex-shrink-0 border-t border-border p-4 sm:p-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" data-modal-dismiss class="ui-btn-secondary w-full sm:w-auto">
                        <i class="ph ph-x text-base" aria-hidden="true"></i>
                        Cancel
                    </button>
                    <button type="submit" class="ui-btn-primary w-full sm:w-auto" data-decide-submit>
                        <i class="ph ph-check text-base" aria-hidden="true"></i>
                        Confirm
                    </button>
                </div>
            </form>
        </x-ui.modal>
    @endif

    @if ($canManage)
        <x-ui.modal id="leave-type-modal" title="Add leave type" max-width="max-w-lg">
            <form id="leave-type-form" class="flex min-h-0 flex-1 flex-col" novalidate>
                <input type="hidden" name="id" value="">
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain p-4 sm:p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="ui-label ui-label-required" for="leave-type-code">Code</label>
                            <input id="leave-type-code" name="code" required maxlength="20" class="ui-input uppercase" placeholder="e.g. VL">
                            <p class="hidden text-xs text-danger mt-1" data-error="code"></p>
                        </div>
                        <div>
                            <label class="ui-label" for="leave-type-sort">Sort order</label>
                            <input id="leave-type-sort" name="sort_order" type="number" min="0" max="9999" class="ui-input" value="0">
                            <p class="hidden text-xs text-danger mt-1" data-error="sort_order"></p>
                        </div>
                    </div>
                    <div>
                        <label class="ui-label ui-label-required" for="leave-type-name">Name</label>
                        <input id="leave-type-name" name="name" required maxlength="100" class="ui-input" placeholder="e.g. Vacation Leave">
                        <p class="hidden text-xs text-danger mt-1" data-error="name"></p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="inline-flex items-center gap-2 min-h-[44px] text-sm text-heading">
                            <input type="checkbox" name="is_accruing" value="1" class="rounded border-border text-primary focus:ring-primary/30">
                            Accruing (monthly credits)
                        </label>
                        <label class="inline-flex items-center gap-2 min-h-[44px] text-sm text-heading">
                            <input type="checkbox" name="requires_reason" value="1" checked class="rounded border-border text-primary focus:ring-primary/30">
                            Requires reason
                        </label>
                        <label class="inline-flex items-center gap-2 min-h-[44px] text-sm text-heading">
                            <input type="checkbox" name="requires_hr" value="1" class="rounded border-border text-primary focus:ring-primary/30">
                            Requires HR approval
                        </label>
                        <label class="inline-flex items-center gap-2 min-h-[44px] text-sm text-heading">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded border-border text-primary focus:ring-primary/30">
                            Active (can be filed)
                        </label>
                    </div>
                </div>
                <div class="flex-shrink-0 border-t border-border p-4 sm:p-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" data-modal-dismiss class="ui-btn-secondary w-full sm:w-auto">
                        <i class="ph ph-x text-base" aria-hidden="true"></i>
                        Cancel
                    </button>
                    <button type="submit" class="ui-btn-primary w-full sm:w-auto">
                        <i class="ph ph-floppy-disk text-base" aria-hidden="true"></i>
                        Save leave type
                    </button>
                </div>
            </form>
        </x-ui.modal>

        <x-ui.modal id="leave-adjust-modal" title="Adjust leave balance" max-width="max-w-lg">
            <form id="leave-adjust-form" class="flex min-h-0 flex-1 flex-col" novalidate>
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain p-4 sm:p-5">
                    <x-ui.employee-search name="employee_id" id="leave-adjust-employee" label="Employee" :required="true" />
                    <div>
                        <label class="ui-label ui-label-required" for="leave-adjust-type">Leave type</label>
                        <select id="leave-adjust-type" name="leave_type_id" required class="ui-select">
                            <option value="">Select type…</option>
                        </select>
                        <p class="hidden text-xs text-danger mt-1" data-error="leave_type_id"></p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="ui-label ui-label-required" for="leave-adjust-year">Leave year</label>
                            <input id="leave-adjust-year" name="leave_year" type="number" min="2000" max="2100" required class="ui-input">
                            <p class="hidden text-xs text-danger mt-1" data-error="leave_year"></p>
                        </div>
                        <div>
                            <label class="ui-label ui-label-required" for="leave-adjust-amount">Amount (days)</label>
                            <input id="leave-adjust-amount" name="amount" type="number" step="0.01" required class="ui-input" placeholder="e.g. 1.25 or -0.5">
                            <p class="hidden text-xs text-danger mt-1" data-error="amount"></p>
                        </div>
                    </div>
                    <div>
                        <label class="ui-label" for="leave-adjust-date">Effective date</label>
                        <input id="leave-adjust-date" name="effective_date" type="date" class="ui-input">
                        <p class="hidden text-xs text-danger mt-1" data-error="effective_date"></p>
                    </div>
                    <div>
                        <label class="ui-label ui-label-required" for="leave-adjust-reason">Reason</label>
                        <textarea id="leave-adjust-reason" name="reason" rows="3" required maxlength="500" class="ui-input" placeholder="Why is this balance being adjusted?"></textarea>
                        <p class="hidden text-xs text-danger mt-1" data-error="reason"></p>
                    </div>
                </div>
                <div class="flex-shrink-0 border-t border-border p-4 sm:p-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" data-modal-dismiss class="ui-btn-secondary w-full sm:w-auto">
                        <i class="ph ph-x text-base" aria-hidden="true"></i>
                        Cancel
                    </button>
                    <button type="submit" class="ui-btn-primary w-full sm:w-auto">
                        <i class="ph ph-sliders-horizontal text-base" aria-hidden="true"></i>
                        Save adjustment
                    </button>
                </div>
            </form>
        </x-ui.modal>

        <x-ui.modal id="leave-accrual-modal" title="Run monthly accrual" max-width="max-w-md">
            <form id="leave-accrual-form" class="flex min-h-0 flex-1 flex-col" novalidate>
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain p-4 sm:p-5">
                    <p class="text-sm text-text-secondary">
                        Credits Vacation Leave for regularized active employees using tenure tiers
                        (1.25 / 1.50 / 1.66 days per month). Safe to re-run — already accrued months are skipped.
                    </p>
                    <div>
                        <label class="ui-label ui-label-required" for="leave-accrual-month">Year-month</label>
                        <input id="leave-accrual-month" name="year_month" type="month" required class="ui-input">
                        <p class="hidden text-xs text-danger mt-1" data-error="year_month"></p>
                    </div>
                </div>
                <div class="flex-shrink-0 border-t border-border p-4 sm:p-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" data-modal-dismiss class="ui-btn-secondary w-full sm:w-auto">
                        <i class="ph ph-x text-base" aria-hidden="true"></i>
                        Cancel
                    </button>
                    <button type="submit" class="ui-btn-primary w-full sm:w-auto">
                        <i class="ph ph-calendar-plus text-base" aria-hidden="true"></i>
                        Run accrual
                    </button>
                </div>
            </form>
        </x-ui.modal>
    @endif
</section>
@endsection
