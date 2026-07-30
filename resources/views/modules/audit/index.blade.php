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
                <th class="px-4 py-3 font-semibold text-right">Actions</th>
            </tr>
        </x-slot:head>

        <x-slot:modals>
            <x-ui.modal id="audit-log-modal" title="Event details">
                <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-4 sm:p-5 space-y-3 text-sm" data-audit-detail></div>
            </x-ui.modal>
        </x-slot:modals>
    </x-ui.data-panel>
</section>
@endsection
