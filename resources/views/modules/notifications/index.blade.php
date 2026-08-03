@extends('layouts.app')

@section('title', ($module['label'] ?? 'Notifications') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'Notifications')

@section('content')
<section class="space-y-4 md:space-y-5" data-module="notifications">
    <div class="bg-surface border border-border rounded-2xl p-5 md:p-6">
        <p class="text-xs font-bold tracking-wide text-faint uppercase">{{ $module['section'] ?? 'System' }}</p>
        <h2 class="text-2xl font-bold text-heading mt-1">{{ $module['label'] ?? 'Notifications' }}</h2>
        <p class="text-sm text-text-secondary mt-2 max-w-3xl">
            {{ $module['summary'] ?? 'In-app alerts for account, approvals, and system events. Email is sent when applicable.' }}
        </p>
    </div>

    <x-ui.data-panel id="user-notifications" title="Your inbox">
        <x-slot:subtitle>
            <span data-notif-unread-label>Loading unread…</span>
        </x-slot:subtitle>
        <x-slot:actions>
            <button
                type="button"
                data-action="mark-all-read"
                class="inline-flex items-center justify-center gap-1.5 h-10 min-h-[44px] sm:min-h-10 px-3.5 rounded-xl border border-border bg-surface text-sm font-medium text-text hover:border-border-strong transition-colors"
            >
                <i class="ph ph-checks text-base"></i>
                Mark all read
            </button>
        </x-slot:actions>
        <x-slot:filters>
            <div class="relative w-full sm:col-span-2 lg:w-52 lg:flex-none">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                <input
                    type="search"
                    data-filter="search"
                    placeholder="Search title, body…"
                    class="w-full h-10 min-h-[44px] sm:min-h-10 pl-9 pr-3 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                >
            </div>
            <select data-filter="type" class="w-full lg:w-44 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm">
                <option value="">All types</option>
            </select>
            <select data-filter="unread" class="w-full lg:w-36 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm">
                <option value="">All</option>
                <option value="1">Unread only</option>
            </select>
        </x-slot:filters>

        <x-slot:head>
            <tr>
                <th class="px-4 py-3 font-semibold">When</th>
                <th class="px-4 py-3 font-semibold">Type</th>
                <th class="px-4 py-3 font-semibold">Message</th>
                <th class="px-4 py-3 font-semibold">Status</th>
                <th class="px-4 py-3 font-semibold text-right">Actions</th>
            </tr>
        </x-slot:head>

        <x-slot:modals>
            <x-ui.modal id="notification-detail-modal" title="Notification">
                <div class="flex min-h-0 flex-1 flex-col">
                    <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-4 sm:p-5 space-y-3 text-sm" data-notif-detail></div>
                    <div class="flex-shrink-0 border-t border-border p-4 sm:p-5 flex flex-wrap justify-end gap-2">
                        <button type="button" data-modal-dismiss class="inline-flex items-center h-10 min-h-[44px] sm:min-h-10 px-4 rounded-xl border border-border text-sm font-medium text-text hover:border-border-strong transition-colors">
                            Close
                        </button>
                        <a
                            href="#"
                            data-notif-open-link
                            class="hidden inline-flex items-center gap-1.5 h-10 min-h-[44px] sm:min-h-10 px-4 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-strong transition-colors"
                        >
                            Open
                            <i class="ph ph-arrow-right text-sm"></i>
                        </a>
                    </div>
                </div>
            </x-ui.modal>
        </x-slot:modals>
    </x-ui.data-panel>
</section>
@endsection
