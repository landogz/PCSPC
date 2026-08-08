@extends('layouts.app')

@section('title', ($module['label'] ?? 'Overtime') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'Overtime')

@php
    $user = auth()->user();
    $canFile = $user?->hasPermission('ot.file') ?? false;
    $canManage = $user?->hasPermission('ot.manage') ?? false;
    $canApprove = ($user?->hasPermission('ot.approve') || $canManage) ?? false;
    $defaultTab = $canApprove ? 'approvals' : 'requests';
@endphp

@section('content')
<section
    class="space-y-4 md:space-y-5"
    data-module="overtime"
    data-can-file="{{ $canFile ? '1' : '0' }}"
    data-can-approve="{{ $canApprove ? '1' : '0' }}"
    data-can-manage="{{ $canManage ? '1' : '0' }}"
>
    <div class="bg-surface border border-border rounded-2xl p-5 md:p-6">
        <p class="text-xs font-bold tracking-wide text-faint uppercase">{{ $module['section'] ?? 'People operations' }}</p>
        <h2 class="text-2xl font-bold text-heading mt-1">{{ $module['label'] ?? 'Overtime' }}</h2>
        <p class="text-sm text-text-secondary mt-2 max-w-3xl">
            File OT or OT Meal with a mandatory reason. Approvals run Approver → HR via the shared workflow engine.
            Department/rank Annex A matrices will replace the provisional steps later.
        </p>

        <div class="mt-5 flex flex-wrap gap-2 border-b border-border" role="tablist" aria-label="Overtime sections">
            @if ($canFile)
                <button type="button" data-ot-tab="requests" role="tab"
                    class="ot-tab h-10 min-h-[44px] sm:min-h-10 px-4 text-sm font-semibold border-b-2 -mb-px transition-colors {{ $defaultTab === 'requests' ? 'border-primary text-primary' : 'border-transparent text-muted hover:text-heading' }}"
                    aria-selected="{{ $defaultTab === 'requests' ? 'true' : 'false' }}">My requests</button>
            @endif
            @if ($canApprove)
                <button type="button" data-ot-tab="approvals" role="tab"
                    class="ot-tab h-10 min-h-[44px] sm:min-h-10 px-4 text-sm font-semibold border-b-2 -mb-px transition-colors {{ $defaultTab === 'approvals' ? 'border-primary text-primary' : 'border-transparent text-muted hover:text-heading' }}"
                    aria-selected="{{ $defaultTab === 'approvals' ? 'true' : 'false' }}">Approvals</button>
            @endif
        </div>
    </div>

    @if ($canFile)
        <div data-ot-panel="requests" class="{{ $defaultTab === 'requests' ? '' : 'hidden' }}">
            <x-ui.data-panel id="ot-my-requests" title="My overtime requests" create-label="File OT">
                <x-slot:subtitle>Submit OT or OT Meal. Pending filings can be cancelled before final approval.</x-slot:subtitle>
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
                    <select data-filter="kind" class="w-full lg:w-36 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm">
                        <option value="">All kinds</option>
                        <option value="ot">OT</option>
                        <option value="ot_meal">OT Meal</option>
                    </select>
                </x-slot:filters>
                <x-slot:head>
                    <tr>
                        <th class="px-4 py-3 font-semibold">Kind</th>
                        <th class="px-4 py-3 font-semibold">Date</th>
                        <th class="px-4 py-3 font-semibold text-right">Hours</th>
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
        <div data-ot-panel="approvals" class="{{ $defaultTab === 'approvals' ? '' : 'hidden' }}">
            <x-ui.data-panel id="ot-approvals" title="Overtime approvals">
                <x-slot:subtitle>Approve or reject at your step. Full approval requires Approver then HR.</x-slot:subtitle>
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
                    <select data-filter="kind" class="w-full lg:w-36 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm">
                        <option value="">All kinds</option>
                        <option value="ot">OT</option>
                        <option value="ot_meal">OT Meal</option>
                    </select>
                </x-slot:filters>
                <x-slot:head>
                    <tr>
                        <th class="px-4 py-3 font-semibold">Employee</th>
                        <th class="px-4 py-3 font-semibold">Kind</th>
                        <th class="px-4 py-3 font-semibold">Date</th>
                        <th class="px-4 py-3 font-semibold text-right">Hours</th>
                        <th class="px-4 py-3 font-semibold">Step</th>
                        <th class="px-4 py-3 font-semibold">Reason</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </x-slot:head>
            </x-ui.data-panel>
        </div>
    @endif
</section>

@if ($canFile)
    <x-ui.modal id="ot-file-modal" title="File overtime">
        <form id="ot-file-form" class="flex min-h-0 flex-1 flex-col" novalidate>
            <div class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-5 space-y-4">
                <div>
                    <label for="ot-kind" class="block text-sm font-medium text-heading mb-1.5">Kind</label>
                    <select id="ot-kind" name="kind" required
                        class="w-full h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                        <option value="ot">OT</option>
                        <option value="ot_meal">OT Meal</option>
                    </select>
                    <p data-error="kind" class="hidden mt-1 text-xs text-danger"></p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="ot-work-date" class="block text-sm font-medium text-heading mb-1.5">Work date</label>
                        <input type="date" id="ot-work-date" name="work_date" required
                            class="w-full h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                        <p data-error="work_date" class="hidden mt-1 text-xs text-danger"></p>
                    </div>
                    <div>
                        <label for="ot-hours" class="block text-sm font-medium text-heading mb-1.5">Hours</label>
                        <input type="number" id="ot-hours" name="hours" step="0.25" min="0.25" max="24" required
                            class="w-full h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                        <p data-error="hours" class="hidden mt-1 text-xs text-danger"></p>
                    </div>
                </div>
                <div>
                    <label for="ot-reason" class="block text-sm font-medium text-heading mb-1.5">Reason</label>
                    <textarea id="ot-reason" name="reason" rows="3" required maxlength="1000"
                        class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                        placeholder="Why is overtime needed?"></textarea>
                    <p data-error="reason" class="hidden mt-1 text-xs text-danger"></p>
                </div>
                <div data-ot-meal-notes class="hidden">
                    <label for="ot-meal-notes" class="block text-sm font-medium text-heading mb-1.5">Meal notes</label>
                    <textarea id="ot-meal-notes" name="meal_notes" rows="2" maxlength="500"
                        class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                        placeholder="Meal allowance details…"></textarea>
                    <p data-error="meal_notes" class="hidden mt-1 text-xs text-danger"></p>
                </div>
            </div>
            <div class="flex-shrink-0 border-t border-border p-4 sm:p-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                <button type="button" data-modal-dismiss
                    class="inline-flex items-center justify-center gap-1.5 h-10 min-h-[44px] sm:min-h-10 px-4 rounded-xl border border-border text-sm font-medium text-heading hover:bg-subtle">
                    <i class="ph ph-x text-base" aria-hidden="true"></i>
                    Cancel
                </button>
                <button type="submit"
                    class="inline-flex items-center justify-center gap-1.5 h-10 min-h-[44px] sm:min-h-10 px-4 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/90">
                    <i class="ph ph-paper-plane-tilt text-base" aria-hidden="true"></i>
                    Submit
                </button>
            </div>
        </form>
    </x-ui.modal>
@endif

@if ($canApprove)
    <x-ui.modal id="ot-decide-modal" title="Decide overtime">
        <form id="ot-decide-form" class="flex min-h-0 flex-1 flex-col" novalidate>
            <input type="hidden" name="id" value="">
            <input type="hidden" name="decision" value="">
            <div class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-5 space-y-4">
                <p data-ot-decide-summary class="text-sm text-text-secondary"></p>
                <div>
                    <label for="ot-decide-notes" class="block text-sm font-medium text-heading mb-1.5">Notes (optional)</label>
                    <textarea id="ot-decide-notes" name="notes" rows="3" maxlength="1000"
                        class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"></textarea>
                    <p data-error="notes" class="hidden mt-1 text-xs text-danger"></p>
                </div>
            </div>
            <div class="flex-shrink-0 border-t border-border p-4 sm:p-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                <button type="button" data-modal-dismiss
                    class="inline-flex items-center justify-center gap-1.5 h-10 min-h-[44px] sm:min-h-10 px-4 rounded-xl border border-border text-sm font-medium text-heading hover:bg-subtle">
                    <i class="ph ph-x text-base" aria-hidden="true"></i>
                    Cancel
                </button>
                <button type="submit" data-ot-decide-submit
                    class="inline-flex items-center justify-center gap-1.5 h-10 min-h-[44px] sm:min-h-10 px-4 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/90">
                    <i class="ph ph-check text-base" aria-hidden="true"></i>
                    Confirm
                </button>
            </div>
        </form>
    </x-ui.modal>
@endif
@endsection
