@extends('layouts.app')

@section('title', ($module['label'] ?? 'Audit Log') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'Audit Log')

@section('content')
<section class="space-y-4 md:space-y-5" data-module="audit">
    <div class="bg-surface border border-border rounded-2xl p-5 md:p-6">
        <p class="text-xs font-bold tracking-wide text-faint uppercase">{{ $module['section'] ?? 'System' }}</p>
        <h2 class="text-2xl font-bold text-heading mt-1">{{ $module['label'] ?? 'Audit Log' }}</h2>
        <p class="text-sm text-text-secondary mt-2 max-w-3xl">{{ $module['summary'] ?? '' }}</p>
    </div>

    <x-ui.data-panel id="audit-logs" title="Auth & security events">
        <x-slot:subtitle>Filter login, MFA, lockout, and related activity.</x-slot:subtitle>
        <x-slot:filters>
            <div class="relative min-w-[180px] flex-1 sm:flex-none">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-muted"></i>
                <input
                    type="search"
                    data-filter="search"
                    placeholder="Search email, IP…"
                    class="w-full sm:w-56 h-10 pl-9 pr-3 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                >
            </div>
            <select data-filter="event" class="h-10 px-3 rounded-xl border border-border bg-surface text-sm min-w-[140px]">
                <option value="">All events</option>
            </select>
            <input type="date" data-filter="from" class="h-10 px-3 rounded-xl border border-border bg-surface text-sm">
            <input type="date" data-filter="to" class="h-10 px-3 rounded-xl border border-border bg-surface text-sm">
        </x-slot:filters>

        <x-slot:head>
            <tr>
                <th class="px-4 py-3 font-semibold">When</th>
                <th class="px-4 py-3 font-semibold">Event</th>
                <th class="px-4 py-3 font-semibold">User</th>
                <th class="px-4 py-3 font-semibold">IP</th>
                <th class="px-4 py-3 font-semibold">Details</th>
            </tr>
        </x-slot:head>

        <x-slot:modals>
            <div id="audit-log-modal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" data-modal-dismiss></div>
                <div class="relative w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl border border-border bg-surface shadow-xl">
                    <div class="p-5 border-b border-border flex items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-heading">Event details</h3>
                        <button type="button" class="h-9 w-9 rounded-lg hover:bg-subtle" data-modal-dismiss aria-label="Close">
                            <i class="ph ph-x text-lg"></i>
                        </button>
                    </div>
                    <div class="p-5 space-y-3 text-sm" data-audit-detail></div>
                </div>
            </div>
        </x-slot:modals>
    </x-ui.data-panel>
</section>
@endsection
