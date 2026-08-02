@extends('layouts.app')

@section('title', ($module['label'] ?? 'Schedules') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'Schedules')

@section('content')
<section class="space-y-4 md:space-y-5" data-module="schedules">
    <div class="bg-surface border border-border rounded-2xl p-5 md:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-bold tracking-wide text-faint uppercase">{{ $module['section'] ?? 'System' }}</p>
                <h2 class="text-2xl font-bold text-heading mt-1">{{ $module['label'] ?? 'Schedules' }}</h2>
                <p class="text-sm text-text-secondary mt-2 max-w-3xl">
                    {{ $module['summary'] ?? 'Assign shift templates to employees or departments.' }}
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach (($module['req_ids'] ?? ['ADM-009']) as $req)
                        <span class="inline-flex items-center h-7 px-2.5 rounded-lg bg-subtle border border-border text-[11px] font-semibold text-heading">{{ $req }}</span>
                    @endforeach
                    <span class="inline-flex items-center h-7 px-2.5 rounded-lg bg-primary-soft text-primary text-[11px] font-semibold">Phase P3</span>
                </div>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <button type="button" data-action="print-schedules" class="inline-flex items-center justify-center gap-1.5 h-10 min-h-[44px] px-4 rounded-xl border border-border text-sm font-medium hover:bg-subtle whitespace-nowrap">
                    <i class="ph ph-printer"></i> Print schedules
                </button>
                <a href="{{ route('modules.show', ['module' => 'shifts']) }}" class="inline-flex items-center justify-center gap-1.5 h-10 min-h-[44px] px-4 rounded-xl border border-border text-sm font-medium hover:bg-subtle whitespace-nowrap">
                    <i class="ph ph-clock"></i> Shift templates
                </a>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap gap-2" data-effective-tabs role="tablist" aria-label="Schedule period">
        <button type="button" data-effective-tab="" class="inline-flex items-center gap-2 h-10 min-h-[44px] px-3.5 rounded-xl border border-border bg-primary-soft text-primary text-sm font-medium" aria-pressed="true">All</button>
        <button type="button" data-effective-tab="current" class="inline-flex items-center gap-2 h-10 min-h-[44px] px-3.5 rounded-xl border border-border bg-surface text-sm font-medium text-heading" aria-pressed="false">Current</button>
        <button type="button" data-effective-tab="upcoming" class="inline-flex items-center gap-2 h-10 min-h-[44px] px-3.5 rounded-xl border border-border bg-surface text-sm font-medium text-heading" aria-pressed="false">Upcoming</button>
        <button type="button" data-effective-tab="ended" class="inline-flex items-center gap-2 h-10 min-h-[44px] px-3.5 rounded-xl border border-border bg-surface text-sm font-medium text-heading" aria-pressed="false">Ended</button>
    </div>

    <x-ui.data-panel id="schedules-table" title="Shift assignments" create-label="Assign schedule" :view-toggle="true">
        <x-slot:subtitle>Employee-specific assignments override department schedules when both apply.</x-slot:subtitle>
        <x-slot:filters>
            <div class="relative w-full sm:col-span-2 lg:w-52 lg:flex-none">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-muted pointer-events-none"></i>
                <input
                    type="search"
                    data-filter="search"
                    placeholder="Search schedules…"
                    class="w-full h-10 min-h-[44px] sm:min-h-10 pl-9 pr-3 rounded-xl border border-border bg-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                >
            </div>
            <select data-filter="shift_id" class="w-full lg:w-44 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm">
                <option value="">All shifts</option>
            </select>
            <select data-filter="assignee_type" class="w-full lg:w-40 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm">
                <option value="">All assignees</option>
                <option value="employee">Employee</option>
                <option value="department">Department</option>
            </select>
            <select data-filter="status" class="w-full lg:w-36 h-10 min-h-[44px] sm:min-h-10 px-3 rounded-xl border border-border bg-surface text-sm">
                <option value="">All status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <input type="hidden" data-filter="effective" value="">
        </x-slot:filters>

        <x-slot:head>
            <tr>
                <th class="px-4 py-3 font-semibold">Assignee</th>
                <th class="px-4 py-3 font-semibold">Shift</th>
                <th class="px-4 py-3 font-semibold">Effective</th>
                <th class="px-4 py-3 font-semibold">Days</th>
                <th class="px-4 py-3 font-semibold">Status</th>
                <th class="px-4 py-3 font-semibold text-right">Actions</th>
            </tr>
        </x-slot:head>

        <x-slot:modals>
            <x-ui.modal id="schedule-print-modal" title="Print schedules" subtitle="Landscape A4 report — per employee or per department" max-width="max-w-lg">
                <form id="schedule-print-form" class="flex min-h-0 flex-1 flex-col" novalidate>
                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain p-4 sm:p-5">
                        <div>
                            <label class="ui-label ui-label-required">Layout</label>
                            <div class="grid grid-cols-2 gap-2 mt-1">
                                <label class="inline-flex items-center justify-center gap-2 h-10 min-h-[44px] rounded-xl border border-border px-3 text-sm cursor-pointer has-[:checked]:bg-primary-soft has-[:checked]:border-primary/40 has-[:checked]:text-primary">
                                    <input type="radio" name="scope" value="employee" class="sr-only" checked data-print-scope>
                                    <i class="ph ph-user"></i> Per employee
                                </label>
                                <label class="inline-flex items-center justify-center gap-2 h-10 min-h-[44px] rounded-xl border border-border px-3 text-sm cursor-pointer has-[:checked]:bg-primary-soft has-[:checked]:border-primary/40 has-[:checked]:text-primary">
                                    <input type="radio" name="scope" value="department" class="sr-only" data-print-scope>
                                    <i class="ph ph-buildings"></i> Per department
                                </label>
                            </div>
                        </div>

                        <div data-print-employee>
                            <x-ui.employee-search
                                name="employee_id"
                                id="schedule-print-employee"
                                label="Employee"
                                :required="false"
                                hint="Leave blank to print all employees with schedules."
                            />
                        </div>

                        <div class="hidden" data-print-department>
                            <label class="ui-label" for="schedule-print-department">Department</label>
                            <select id="schedule-print-department" name="department_id" class="ui-select" data-print-department-select>
                                <option value="">All departments with schedules</option>
                            </select>
                            <p class="text-xs text-muted mt-1">Leave as “All” to print every department that has assignments.</p>
                        </div>

                        <div>
                            <label class="ui-label" for="schedule-print-effective">Period</label>
                            <select id="schedule-print-effective" name="effective" class="ui-select">
                                <option value="current" selected>Current</option>
                                <option value="upcoming">Upcoming</option>
                                <option value="ended">Ended</option>
                                <option value="all">All periods</option>
                            </select>
                        </div>

                        <label class="inline-flex items-start gap-2 text-sm text-heading">
                            <input type="checkbox" name="include_related" value="1" checked class="mt-0.5 rounded border-border">
                            <span>
                                Include related schedules
                                <span class="block text-xs text-muted font-normal mt-0.5">
                                    Employee print shows department defaults; department print lists employee overrides.
                                </span>
                            </span>
                        </label>
                    </div>
                    <div class="flex flex-shrink-0 flex-col-reverse gap-2 border-t border-border bg-surface p-4 sm:flex-row sm:justify-end sm:gap-3 sm:p-5">
                        <button type="button" data-modal-dismiss class="ui-btn-secondary min-h-[44px] px-5">Cancel</button>
                        <button type="submit" class="ui-btn-primary min-h-[44px] min-w-[9rem]">
                            <i class="ph ph-printer"></i> Print landscape
                        </button>
                    </div>
                </form>
            </x-ui.modal>

            <x-ui.modal id="schedule-modal" title="Assign schedule" subtitle="Link a shift template to an employee or department" max-width="max-w-lg">
                <form id="schedule-form" class="flex min-h-0 flex-1 flex-col" novalidate>
                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain p-4 sm:p-5">
                        <input type="hidden" name="id" value="">

                        <div>
                            <label class="ui-label ui-label-required" for="schedule-shift">Shift template</label>
                            <select id="schedule-shift" name="shift_id" required class="ui-select" data-shift-select>
                                <option value="">— Select —</option>
                            </select>
                            <p class="hidden text-xs text-danger mt-1" data-error="shift_id"></p>
                        </div>

                        <div>
                            <label class="ui-label ui-label-required">Assign to</label>
                            <div class="grid grid-cols-2 gap-2 mt-1">
                                <label class="inline-flex items-center justify-center gap-2 h-10 min-h-[44px] rounded-xl border border-border px-3 text-sm cursor-pointer has-[:checked]:bg-primary-soft has-[:checked]:border-primary/40 has-[:checked]:text-primary">
                                    <input type="radio" name="assignee_type" value="employee" class="sr-only" checked data-assignee-type>
                                    <i class="ph ph-user"></i> Employee
                                </label>
                                <label class="inline-flex items-center justify-center gap-2 h-10 min-h-[44px] rounded-xl border border-border px-3 text-sm cursor-pointer has-[:checked]:bg-primary-soft has-[:checked]:border-primary/40 has-[:checked]:text-primary">
                                    <input type="radio" name="assignee_type" value="department" class="sr-only" data-assignee-type>
                                    <i class="ph ph-buildings"></i> Department
                                </label>
                            </div>
                            <p class="hidden text-xs text-danger mt-1" data-error="assignee_type"></p>
                        </div>

                        <div data-assignee-employee>
                            <x-ui.employee-search
                                name="employee_id"
                                id="schedule-employee"
                                label="Employee"
                                :required="true"
                                hint="Search and select the employee to assign."
                            />
                        </div>

                        <div class="hidden" data-assignee-department>
                            <label class="ui-label ui-label-required" for="schedule-department">Department</label>
                            <select id="schedule-department" name="department_id" class="ui-select" data-department-select>
                                <option value="">— Select —</option>
                            </select>
                            <p class="hidden text-xs text-danger mt-1" data-error="department_id"></p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="ui-label ui-label-required" for="schedule-from">Effective from</label>
                                <input id="schedule-from" name="effective_from" type="date" required class="ui-input">
                                <p class="hidden text-xs text-danger mt-1" data-error="effective_from"></p>
                            </div>
                            <div>
                                <label class="ui-label" for="schedule-to">Effective to</label>
                                <input id="schedule-to" name="effective_to" type="date" class="ui-input">
                                <p class="text-xs text-muted mt-1">Leave blank for open-ended.</p>
                                <p class="hidden text-xs text-danger mt-1" data-error="effective_to"></p>
                            </div>
                        </div>

                        <div>
                            <p class="ui-label">Days of week</p>
                            <div class="mt-2 flex flex-wrap gap-2" data-days-of-week>
                                @foreach ([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'] as $day => $label)
                                    <label class="inline-flex items-center gap-1.5 h-9 min-h-[40px] px-2.5 rounded-lg border border-border text-xs font-medium cursor-pointer has-[:checked]:bg-primary-soft has-[:checked]:border-primary/40 has-[:checked]:text-primary">
                                        <input type="checkbox" name="days_of_week[]" value="{{ $day }}" class="rounded border-border" @checked(in_array($day, [1,2,3,4,5], true))>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            <p class="text-xs text-muted mt-1">Uncheck all for every day.</p>
                            <p class="hidden text-xs text-danger mt-1" data-error="days_of_week"></p>
                        </div>

                        <div>
                            <label class="ui-label" for="schedule-notes">Notes</label>
                            <textarea id="schedule-notes" name="notes" rows="2" maxlength="500" class="ui-input" placeholder="Optional"></textarea>
                            <p class="hidden text-xs text-danger mt-1" data-error="notes"></p>
                        </div>

                        <label class="inline-flex items-center gap-2 text-sm text-heading">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded border-border">
                            Active assignment
                        </label>
                    </div>
                    <div class="flex flex-shrink-0 flex-col-reverse gap-2 border-t border-border bg-surface p-4 sm:flex-row sm:justify-end sm:gap-3 sm:p-5">
                        <button type="button" data-modal-dismiss class="ui-btn-secondary min-h-[44px] px-5">Cancel</button>
                        <button type="submit" class="ui-btn-primary min-h-[44px] min-w-[9rem]">Save schedule</button>
                    </div>
                </form>
            </x-ui.modal>
        </x-slot:modals>
    </x-ui.data-panel>
</section>
@endsection
