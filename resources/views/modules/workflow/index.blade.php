@extends('layouts.app')

@section('title', ($module['label'] ?? 'Workflow') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'Workflow Approvals')

@php
    $user = auth()->user();
    $canApprove = (
        $user?->hasPermission('ot.approve')
        || $user?->hasPermission('ot.manage')
        || $user?->hasPermission('leave.approve')
        || $user?->hasPermission('leave.manage')
    ) ?? false;
@endphp

@section('content')
<section class="space-y-4 md:space-y-5" data-module="workflow" data-can-approve="{{ $canApprove ? '1' : '0' }}">
    <div class="bg-surface border border-border rounded-2xl p-5 md:p-6">
        <p class="text-xs font-bold tracking-wide text-faint uppercase">{{ $module['section'] ?? 'People operations' }}</p>
        <h2 class="text-2xl font-bold text-heading mt-1">{{ $module['label'] ?? 'Workflow Approvals' }}</h2>
        <p class="text-sm text-text-secondary mt-2 max-w-3xl">
            Inbox of items waiting on your step. Leave and OT share this engine; Travel arrives later.
        </p>
    </div>

    @if ($canApprove)
        <div class="bg-surface border border-border rounded-2xl p-4 sm:p-5" data-workflow-definitions>
            <h3 class="text-sm font-semibold text-heading">Active definitions</h3>
            <ul data-workflow-definitions-list class="mt-3 space-y-2 text-sm text-text-secondary">
                <li class="text-muted">Loading…</li>
            </ul>
        </div>

        <x-ui.data-panel id="workflow-inbox" title="My approval inbox">
            <x-slot:subtitle>Pending instances where your permission matches the current step.</x-slot:subtitle>
            <x-slot:filters>
                <div class="relative w-full sm:col-span-2 lg:w-52 lg:flex-none">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                    <input type="search" data-filter="search" placeholder="Search…"
                        class="w-full h-10 min-h-[44px] sm:min-h-10 pl-9 pr-3 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                </div>
            </x-slot:filters>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3 font-semibold">Subject</th>
                    <th class="px-4 py-3 font-semibold">Workflow</th>
                    <th class="px-4 py-3 font-semibold">Step</th>
                    <th class="px-4 py-3 font-semibold">Started by</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold text-right">Actions</th>
                </tr>
            </x-slot:head>
        </x-ui.data-panel>
    @else
        <div class="bg-surface border border-border rounded-2xl p-6 text-sm text-muted">
            You do not have overtime approval permissions for the workflow inbox.
        </div>
    @endif
</section>

@if ($canApprove)
    <x-ui.modal id="workflow-decide-modal" title="Decide workflow">
        <form id="workflow-decide-form" class="flex min-h-0 flex-1 flex-col" novalidate>
            <input type="hidden" name="id" value="">
            <input type="hidden" name="decision" value="">
            <div class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-5 space-y-4">
                <p data-workflow-decide-summary class="text-sm text-text-secondary"></p>
                <div>
                    <label for="workflow-decide-notes" class="block text-sm font-medium text-heading mb-1.5">Notes (optional)</label>
                    <textarea id="workflow-decide-notes" name="notes" rows="3" maxlength="1000"
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
                <button type="submit" data-workflow-decide-submit
                    class="inline-flex items-center justify-center gap-1.5 h-10 min-h-[44px] sm:min-h-10 px-4 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/90">
                    <i class="ph ph-check text-base" aria-hidden="true"></i>
                    Confirm
                </button>
            </div>
        </form>
    </x-ui.modal>
@endif
@endsection
