@extends('layouts.app')

@section('title', ($module['label'] ?? 'Shifts') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'Shifts')

@section('content')
<section class="space-y-4 md:space-y-5" data-module="shifts">
    <div class="bg-surface border border-border rounded-2xl p-5 md:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-bold tracking-wide text-faint uppercase">{{ $module['section'] ?? 'System' }}</p>
                <h2 class="text-2xl font-bold text-heading mt-1">{{ $module['label'] ?? 'Shifts' }}</h2>
                <p class="text-sm text-text-secondary mt-2 max-w-3xl">{{ $module['summary'] ?? '' }}</p>
            </div>
            <a href="{{ route('modules.show', ['module' => 'schedules']) }}" class="inline-flex items-center justify-center gap-1.5 h-10 min-h-[44px] px-4 rounded-xl border border-border text-sm font-medium hover:bg-subtle whitespace-nowrap">
                <i class="ph ph-calendar-check"></i> Assign schedules
            </a>
        </div>
    </div>

    <x-ui.data-panel id="shifts-table" title="Shift templates" create-label="Add shift">
        <x-slot:subtitle>Work-shift templates for scheduling and timekeeping.</x-slot:subtitle>
        <x-slot:filters>
            <div class="relative w-full sm:col-span-2 lg:w-52 lg:flex-none">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                <input
                    type="search"
                    data-filter="search"
                    placeholder="Search shifts…"
                    class="w-full h-10 min-h-[44px] sm:min-h-10 pl-9 pr-3 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                >
            </div>
            <select data-filter="status" class="w-full lg:w-36 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm sm:col-span-2 lg:col-span-1">
                <option value="">All status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </x-slot:filters>

        <x-slot:head>
            <tr>
                <th class="px-4 py-3 font-semibold">Code</th>
                <th class="px-4 py-3 font-semibold">Name</th>
                <th class="px-4 py-3 font-semibold">Schedule</th>
                <th class="px-4 py-3 font-semibold">Work hours</th>
                <th class="px-4 py-3 font-semibold">Status</th>
                <th class="px-4 py-3 font-semibold text-right">Actions</th>
            </tr>
        </x-slot:head>

        <x-slot:modals>
            <x-ui.modal id="shift-modal" title="Add shift" max-width="max-w-lg">
                <form id="shift-form" class="flex min-h-0 flex-1 flex-col" novalidate>
                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain p-4 sm:p-5">
                        <input type="hidden" name="id" value="">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="ui-label ui-label-required" for="shift-code">Code</label>
                                <input id="shift-code" name="code" required maxlength="30" class="ui-input uppercase" placeholder="e.g. DAY">
                                <p class="hidden text-xs text-danger mt-1" data-error="code"></p>
                            </div>
                            <div>
                                <label class="ui-label ui-label-required" for="shift-name">Name</label>
                                <input id="shift-name" name="name" required maxlength="150" class="ui-input" placeholder="e.g. Day shift">
                                <p class="hidden text-xs text-danger mt-1" data-error="name"></p>
                            </div>
                            <div>
                                <label class="ui-label ui-label-required" for="shift-time-in">Time in</label>
                                <input id="shift-time-in" name="time_in" type="time" required class="ui-input">
                                <p class="hidden text-xs text-danger mt-1" data-error="time_in"></p>
                            </div>
                            <div>
                                <label class="ui-label ui-label-required" for="shift-time-out">Time out</label>
                                <input id="shift-time-out" name="time_out" type="time" required class="ui-input">
                                <p class="hidden text-xs text-danger mt-1" data-error="time_out"></p>
                            </div>
                            <div>
                                <label class="ui-label" for="shift-break">Break (minutes)</label>
                                <input id="shift-break" name="break_minutes" type="number" min="0" max="480" value="60" class="ui-input">
                                <p class="hidden text-xs text-danger mt-1" data-error="break_minutes"></p>
                            </div>
                            <div>
                                <label class="ui-label" for="shift-grace">Grace (minutes)</label>
                                <input id="shift-grace" name="grace_minutes" type="number" min="0" max="120" value="0" class="ui-input">
                                <p class="hidden text-xs text-danger mt-1" data-error="grace_minutes"></p>
                            </div>
                        </div>
                        <div>
                            <label class="ui-label" for="shift-description">Description</label>
                            <textarea id="shift-description" name="description" rows="2" maxlength="500" class="ui-input"></textarea>
                        </div>
                        <div class="flex flex-col gap-2.5 sm:flex-row sm:gap-5">
                            <label class="inline-flex items-center gap-2 text-sm text-text-secondary cursor-pointer">
                                <input type="checkbox" name="crosses_midnight" value="1" class="rounded border-border accent-primary">
                                Crosses midnight
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-text-secondary cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" class="rounded border-border accent-primary" checked>
                                Active
                            </label>
                        </div>
                    </div>
                    <div class="flex flex-shrink-0 flex-col-reverse gap-2 border-t border-border bg-surface p-4 sm:flex-row sm:justify-end sm:gap-3 sm:p-5">
                        <button type="button" data-modal-dismiss class="ui-btn-secondary min-h-[44px] px-5">Cancel</button>
                        <button type="submit" class="ui-btn-primary min-h-[44px] min-w-[8.5rem]">Save shift</button>
                    </div>
                </form>
            </x-ui.modal>
        </x-slot:modals>
    </x-ui.data-panel>
</section>
@endsection
