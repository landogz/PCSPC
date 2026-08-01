@extends('layouts.app')

@section('title', ($module['label'] ?? 'Holidays') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'Holidays')

@section('content')
<section class="space-y-4 md:space-y-5" data-module="holidays">
    <div class="bg-surface border border-border rounded-2xl p-5 md:p-6">
        <p class="text-xs font-bold tracking-wide text-faint uppercase">{{ $module['section'] ?? 'System' }}</p>
        <h2 class="text-2xl font-bold text-heading mt-1">{{ $module['label'] ?? 'Holidays' }}</h2>
        <p class="text-sm text-text-secondary mt-2 max-w-3xl">{{ $module['summary'] ?? '' }}</p>
    </div>

    <x-ui.data-panel id="holidays-table" title="Holiday calendar" create-label="Add holiday">
        <x-slot:subtitle>Company and national holidays used by attendance and payroll rules.</x-slot:subtitle>
        <x-slot:filters>
            <div class="relative w-full sm:col-span-2 lg:w-52 lg:flex-none">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                <input
                    type="search"
                    data-filter="search"
                    placeholder="Search holidays…"
                    class="w-full h-10 min-h-[44px] sm:min-h-10 pl-9 pr-3 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                >
            </div>
            <select data-filter="year" class="w-full lg:w-28 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm">
                <option value="">All years</option>
            </select>
            <select data-filter="type" class="w-full lg:w-44 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm">
                <option value="">All types</option>
                <option value="regular">Regular</option>
                <option value="special_non_working">Special non-working</option>
                <option value="special_working">Special working</option>
                <option value="company">Company</option>
            </select>
            <select data-filter="status" class="w-full lg:w-36 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm">
                <option value="">All status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </x-slot:filters>

        <x-slot:head>
            <tr>
                <th class="px-4 py-3 font-semibold">Date</th>
                <th class="px-4 py-3 font-semibold">Name</th>
                <th class="px-4 py-3 font-semibold">Type</th>
                <th class="px-4 py-3 font-semibold">Flags</th>
                <th class="px-4 py-3 font-semibold">Status</th>
                <th class="px-4 py-3 font-semibold text-right">Actions</th>
            </tr>
        </x-slot:head>

        <x-slot:modals>
            <x-ui.modal id="holiday-modal" title="Add holiday" max-width="max-w-lg">
                <form id="holiday-form" class="flex min-h-0 flex-1 flex-col" novalidate>
                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain p-4 sm:p-5">
                        <input type="hidden" name="id" value="">
                        <div>
                            <label class="ui-label ui-label-required" for="holiday-name">Name</label>
                            <input id="holiday-name" name="name" required maxlength="150" class="ui-input" placeholder="e.g. Independence Day">
                            <p class="hidden text-xs text-danger mt-1" data-error="name"></p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="ui-label ui-label-required" for="holiday-date">Date</label>
                                <input id="holiday-date" name="holiday_date" type="date" required class="ui-input">
                                <p class="hidden text-xs text-danger mt-1" data-error="holiday_date"></p>
                            </div>
                            <div>
                                <label class="ui-label ui-label-required" for="holiday-type">Type</label>
                                <select id="holiday-type" name="type" required class="ui-select">
                                    <option value="regular">Regular holiday</option>
                                    <option value="special_non_working">Special non-working</option>
                                    <option value="special_working">Special working</option>
                                    <option value="company">Company holiday</option>
                                </select>
                                <p class="hidden text-xs text-danger mt-1" data-error="type"></p>
                            </div>
                            <div>
                                <label class="ui-label" for="holiday-paid-hours">Paid hours</label>
                                <input id="holiday-paid-hours" name="paid_hours" type="number" min="0" max="24" value="8" class="ui-input">
                                <p class="hidden text-xs text-danger mt-1" data-error="paid_hours"></p>
                            </div>
                        </div>
                        <div>
                            <label class="ui-label" for="holiday-description">Description</label>
                            <textarea id="holiday-description" name="description" rows="2" maxlength="500" class="ui-input"></textarea>
                        </div>
                        <div class="flex flex-col gap-2.5 sm:flex-row sm:flex-wrap sm:gap-5">
                            <label class="inline-flex items-center gap-2 text-sm text-text-secondary cursor-pointer">
                                <input type="checkbox" name="is_recurring" value="1" class="rounded border-border accent-primary">
                                Recurring yearly
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-text-secondary cursor-pointer">
                                <input type="checkbox" name="is_double_pay" value="1" class="rounded border-border accent-primary">
                                Double pay eligible
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-text-secondary cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" class="rounded border-border accent-primary" checked>
                                Active
                            </label>
                        </div>
                    </div>
                    <div class="flex flex-shrink-0 flex-col-reverse gap-2 border-t border-border bg-surface p-4 sm:flex-row sm:justify-end sm:gap-3 sm:p-5">
                        <button type="button" data-modal-dismiss class="ui-btn-secondary min-h-[44px] px-5">Cancel</button>
                        <button type="submit" class="ui-btn-primary min-h-[44px] min-w-[8.5rem]">Save holiday</button>
                    </div>
                </form>
            </x-ui.modal>
        </x-slot:modals>
    </x-ui.data-panel>
</section>
@endsection
