@extends('layouts.app')

@section('title', 'Dashboard — '.config('app.name'))
@section('page-title', 'Dashboard')

@php
    use App\Support\Navigation;
    $can = static fn (string $key): bool => Navigation::userCanAccess(
        auth()->user(),
        Navigation::find($key) ?? [],
    );
    $isHrDashboard = $can('employees');
@endphp

@section('content')
<div data-module="dashboard" class="space-y-3 md:space-y-4" data-dashboard-mode="{{ $isHrDashboard ? 'hr' : 'employee' }}">
{{-- Welcome Hero + Quick Actions (+ Today's Summary for HR) --}}
{{-- Employee: md:grid-cols-2 (Hrivo has this). HR: 12-col + spans that exist in Hrivo. --}}
<section class="grid grid-cols-1 gap-3 md:gap-4 {{ $isHrDashboard ? 'lg:grid-cols-12' : 'md:grid-cols-2' }}">
    <div class="{{ $isHrDashboard ? 'lg:col-span-6 2xl:col-span-5' : '' }} relative overflow-hidden rounded-2xl bg-primary/10 p-5 md:p-6 flex flex-col justify-between min-h-[180px]">
        <div class="pointer-events-none absolute -top-8 -right-8 w-40 h-40 rounded-full bg-primary/10"></div>
        <div class="pointer-events-none absolute top-12 -right-4 w-24 h-24 rounded-full bg-primary/8"></div>
        <div class="pointer-events-none absolute -bottom-6 right-16 w-20 h-20 rounded-full bg-primary/6"></div>

        <div>
            <p class="text-muted text-sm font-medium flex items-center gap-1.5">
                <span data-dash-greeting>Good day</span>
            </p>
            <h2 class="text-heading text-2xl font-bold mt-1">
                Welcome back, <span id="dash-first-name">there</span>!
            </h2>
            <p class="text-text-secondary text-sm mt-1.5">
                @if ($isHrDashboard)
                    Here's your HR command center for today.
                @else
                    Here's your self-service home for today.
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 mt-5">
            @if ($can('employees'))
                <a href="{{ route('modules.show', ['module' => 'employees']) }}" class="inline-flex items-center gap-1.5 h-10 min-h-[44px] sm:min-h-10 px-4 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-strong transition-colors">
                    <i class="ph ph-users text-base"></i>Manage Team
                </a>
            @elseif ($can('leave'))
                <a href="{{ route('modules.show', ['module' => 'leave']) }}" class="inline-flex items-center gap-1.5 h-10 min-h-[44px] sm:min-h-10 px-4 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-strong transition-colors">
                    <i class="ph ph-calendar-x text-base"></i>File Leave
                </a>
            @endif
            @if ($can('reports'))
                <a href="{{ route('modules.show', ['module' => 'reports']) }}" class="inline-flex items-center gap-1.5 h-10 min-h-[44px] sm:min-h-10 px-4 rounded-xl border border-border bg-surface text-text text-sm font-medium hover:border-border-strong transition-colors">
                    <i class="ph ph-chart-bar text-base"></i>View Reports
                </a>
            @elseif ($can('timekeeping'))
                <a href="{{ route('modules.show', ['module' => 'timekeeping']) }}" class="inline-flex items-center gap-1.5 h-10 min-h-[44px] sm:min-h-10 px-4 rounded-xl border border-border bg-surface text-text text-sm font-medium hover:border-border-strong transition-colors">
                    <i class="ph ph-clock-user text-base"></i>Timekeeping
                </a>
            @endif
        </div>
    </div>

    <div class="{{ $isHrDashboard ? 'lg:col-span-6 2xl:col-span-4' : 'min-w-0' }} bg-surface border border-border rounded-2xl p-5 md:p-6 flex flex-col min-h-0">
        <h2 class="text-base font-semibold text-heading mb-4 flex-shrink-0">Quick Actions</h2>
        <div class="{{ $isHrDashboard ? 'grid sm:grid-cols-2 gap-3' : 'grid grid-cols-1 gap-3' }}">
            @if ($can('employees'))
                <a href="{{ route('modules.show', ['module' => 'employees']) }}" class="flex items-center gap-3 p-3 min-h-[44px] rounded-xl border border-border hover:border-primary/40 hover:bg-primary-soft transition-colors group">
                    <span class="w-9 h-9 rounded-lg bg-primary-soft text-primary flex items-center justify-center flex-shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                        <i class="ph ph-user-plus text-lg"></i>
                    </span>
                    <span class="text-sm font-medium text-text group-hover:text-primary transition-colors truncate">Employees</span>
                </a>
            @endif
            @if ($can('leave'))
                <a href="{{ route('modules.show', ['module' => 'leave']) }}" class="flex items-center gap-3 p-3 min-h-[44px] rounded-xl border border-border hover:border-[#e6347f]/40 hover:bg-[#e6347f]/8 transition-colors group">
                    <span class="w-9 h-9 rounded-lg bg-[#e6347f]/12 text-[#e6347f] flex items-center justify-center flex-shrink-0 group-hover:bg-[#e6347f] group-hover:text-white transition-colors">
                        <i class="ph ph-calendar-x text-lg"></i>
                    </span>
                    <span class="text-sm font-medium text-text group-hover:text-[#e6347f] transition-colors truncate">Leave Request</span>
                </a>
            @endif
            @if ($can('workflow'))
                <a href="{{ route('modules.show', ['module' => 'workflow']) }}" class="flex items-center gap-3 p-3 min-h-[44px] rounded-xl border border-border hover:border-primary/40 hover:bg-primary-soft transition-colors group">
                    <span class="w-9 h-9 rounded-lg bg-primary-soft text-primary flex items-center justify-center flex-shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                        <i class="ph ph-git-branch text-lg"></i>
                    </span>
                    <span class="text-sm font-medium text-text group-hover:text-primary transition-colors truncate">Approvals</span>
                </a>
            @endif
            @if ($can('timekeeping'))
                <a href="{{ route('modules.show', ['module' => 'timekeeping']) }}" class="flex items-center gap-3 p-3 min-h-[44px] rounded-xl border border-border hover:border-success/40 hover:bg-success-soft transition-colors group">
                    <span class="w-9 h-9 rounded-lg bg-success-soft text-success flex items-center justify-center flex-shrink-0 group-hover:bg-success group-hover:text-white transition-colors">
                        <i class="ph ph-clock-user text-lg"></i>
                    </span>
                    <span class="text-sm font-medium text-text group-hover:text-success transition-colors truncate">Timekeeping</span>
                </a>
            @endif
            @if ($can('overtime'))
                <a href="{{ route('modules.show', ['module' => 'overtime']) }}" class="flex items-center gap-3 p-3 min-h-[44px] rounded-xl border border-border hover:border-primary/40 hover:bg-primary-soft transition-colors group">
                    <span class="w-9 h-9 rounded-lg bg-primary-soft text-primary flex items-center justify-center flex-shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                        <i class="ph ph-timer text-lg"></i>
                    </span>
                    <span class="text-sm font-medium text-text group-hover:text-primary transition-colors truncate">Overtime</span>
                </a>
            @endif
            @if ($isHrDashboard && $can('medical'))
                <a href="{{ route('modules.show', ['module' => 'medical']) }}" class="flex items-center gap-3 p-3 min-h-[44px] rounded-xl border border-border hover:border-[#f59e0b]/40 hover:bg-[#f59e0b]/10 transition-colors group">
                    <span class="w-9 h-9 rounded-lg bg-[#f59e0b]/15 text-[#f59e0b] flex items-center justify-center flex-shrink-0 group-hover:bg-[#f59e0b] group-hover:text-white transition-colors">
                        <i class="ph ph-heartbeat text-lg"></i>
                    </span>
                    <span class="text-sm font-medium text-text group-hover:text-[#f59e0b] transition-colors truncate">Medical</span>
                </a>
            @endif
        </div>
    </div>

    @if ($isHrDashboard)
        <div class="lg:col-span-12 2xl:col-span-3 bg-surface border border-border rounded-2xl p-5 md:p-6 flex flex-col">
            <div class="flex items-center justify-between gap-2 mb-4">
                <h2 class="text-base font-semibold text-heading">Today's Summary</h2>
                <span class="text-xs text-muted">{{ now()->format('M j') }}</span>
            </div>

            <div class="flex flex-col gap-4 flex-1">
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-success flex-shrink-0"></span>
                            <span class="text-sm text-text">Check-ins</span>
                        </div>
                        <span class="text-sm font-semibold text-heading" data-stat="summary-check-ins">—</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-subtle overflow-hidden">
                        <div class="h-full rounded-full bg-success" data-stat-bar="check-ins" style="width: 0%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-warning flex-shrink-0"></span>
                            <span class="text-sm text-text">On Leave</span>
                        </div>
                        <span class="text-sm font-semibold text-heading" data-stat="summary-on-leave">—</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-subtle overflow-hidden">
                        <div class="h-full rounded-full bg-warning" data-stat-bar="on-leave" style="width: 0%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-danger flex-shrink-0"></span>
                            <span class="text-sm text-text">Needs attention</span>
                        </div>
                        <span class="text-sm font-semibold text-heading" data-stat="summary-pending">—</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-subtle overflow-hidden">
                        <div class="h-full rounded-full bg-danger" data-stat-bar="pending" style="width: 0%"></div>
                    </div>
                </div>

                <div class="mt-auto pt-3 border-t border-border-subtle flex items-center justify-between">
                    <span class="text-sm text-primary font-medium">Session</span>
                    <span id="dash-roles" class="text-xs font-semibold text-primary">—</span>
                </div>
            </div>
        </div>
    @endif
</section>

@if ($isHrDashboard)
{{-- Compact KPI strip --}}
<section class="grid grid-cols-2 xl:grid-cols-4 gap-3 md:gap-4">
    <article class="bg-surface border border-border rounded-2xl p-4 flex items-center gap-3">
        <span class="w-10 h-10 rounded-xl bg-primary-soft text-primary flex items-center justify-center flex-shrink-0">
            <i class="ph ph-users-three text-xl"></i>
        </span>
        <div class="min-w-0">
            <p class="text-xl font-bold text-heading leading-none" data-stat="employees">—</p>
            <p class="text-xs text-muted mt-1">Employees</p>
        </div>
    </article>
    <article class="bg-surface border border-border rounded-2xl p-4 flex items-center gap-3">
        <span class="w-10 h-10 rounded-xl bg-success-soft text-success flex items-center justify-center flex-shrink-0">
            <i class="ph ph-airplane-takeoff text-xl"></i>
        </span>
        <div class="min-w-0">
            <p class="text-xl font-bold text-heading leading-none" data-stat="on-leave">—</p>
            <p class="text-xs text-muted mt-1">On leave</p>
        </div>
    </article>
    <article class="bg-surface border border-border rounded-2xl p-4 flex items-center gap-3">
        <span class="w-10 h-10 rounded-xl bg-[#f59e0b]/15 text-[#f59e0b] flex items-center justify-center flex-shrink-0">
            <i class="ph ph-buildings text-xl"></i>
        </span>
        <div class="min-w-0">
            <p class="text-xl font-bold text-heading leading-none" data-stat="departments">—</p>
            <p class="text-xs text-muted mt-1">Departments</p>
        </div>
    </article>
    <article class="bg-surface border border-border rounded-2xl p-4 flex items-center gap-3">
        <span class="w-10 h-10 rounded-xl bg-[#e6347f]/12 text-[#e6347f] flex items-center justify-center flex-shrink-0">
            <i class="ph ph-arrows-left-right text-xl"></i>
        </span>
        <div class="min-w-0">
            <p class="text-xl font-bold text-heading leading-none" data-stat="net-headcount">—</p>
            <p class="text-xs text-muted mt-1">Net hires (month)</p>
        </div>
    </article>
</section>

{{-- Charts --}}
<section class="grid grid-cols-1 lg:grid-cols-2 gap-3 md:gap-4">
    <x-dashboard.chart-card
        title="Attendance trend"
        subtitle="On-time vs late check-ins"
        badge="P5"
        :href="$can('timekeeping') ? route('modules.show', ['module' => 'timekeeping']) : null"
        href-label="Timekeeping"
    >
        <canvas data-chart="attendance-trend" class="w-full h-full" aria-label="Attendance trend chart"></canvas>
        <x-dashboard.chart-empty
            data-empty-for="attendance-trend"
            title="Attendance charts coming soon"
            phase="P5"
            message="Daily punch trends will appear when Timekeeping & biometric ingestion go live."
        />
    </x-dashboard.chart-card>

    <x-dashboard.chart-card
        title="Today's attendance"
        subtitle="Present / Late / Absent / On leave"
        badge="P5"
    >
        <canvas data-chart="attendance-today" class="w-full h-full" aria-label="Today attendance breakdown"></canvas>
        <x-dashboard.chart-empty
            data-empty-for="attendance-today"
            title="Breakdown pending timekeeping"
            phase="P5"
            message="A live donut of today's attendance will replace this placeholder."
        />
    </x-dashboard.chart-card>

    <x-dashboard.chart-card
        title="Leave by month"
        subtitle="Requests over the last 6 months"
        badge="P4"
        :href="$can('leave') ? route('modules.show', ['module' => 'leave']) : null"
        href-label="Leave"
    >
        <canvas data-chart="leave-by-month" class="w-full h-full" aria-label="Leave by month chart"></canvas>
        <x-dashboard.chart-empty
            data-empty-for="leave-by-month"
            title="Leave trends coming in P4"
            phase="P4"
            message="Seasonal leave patterns populate once Leave Management is connected."
        />
    </x-dashboard.chart-card>

    <x-dashboard.chart-card
        title="Headcount by department"
        subtitle="Active roster distribution"
        :href="$can('departments') ? route('modules.show', ['module' => 'departments']) : null"
        href-label="Departments"
    >
        <canvas data-chart="department-headcount" class="w-full h-full" aria-label="Department headcount chart"></canvas>
        <x-dashboard.chart-empty
            class="hidden"
            data-empty-for="department-headcount"
            title="No department data yet"
            message="Add departments and assign employees to see distribution."
        />
    </x-dashboard.chart-card>
</section>

<section class="grid grid-cols-1 xl:grid-cols-3 gap-3 md:gap-4">
    <x-dashboard.chart-card
        class="xl:col-span-2"
        title="Headcount movement"
        subtitle="Hires vs separations (12 months)"
        :href="$can('employees') ? route('modules.show', ['module' => 'employees']) : null"
        href-label="Employees"
    >
        <canvas data-chart="headcount-trend" class="w-full h-full" aria-label="Headcount movement chart"></canvas>
        <x-dashboard.chart-empty
            class="hidden"
            data-empty-for="headcount-trend"
            title="No hire / separation dates yet"
            message="Set date hired and date separated on employee 201 records to plot movement."
        />
    </x-dashboard.chart-card>

    <article class="bg-surface border border-border rounded-2xl p-4 md:p-5 flex flex-col min-h-[280px]">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div>
                <h3 class="text-sm font-semibold text-heading">Needs your attention</h3>
                <p class="text-xs text-muted mt-0.5">
                    <span data-stat="pending-total">0</span> actionable item(s)
                </p>
            </div>
            @if ($can('workflow'))
                <a href="{{ route('modules.show', ['module' => 'workflow']) }}" class="text-xs font-medium text-muted hover:text-primary transition-colors min-h-[44px] sm:min-h-0 inline-flex items-center">
                    Workflow
                    <i class="ph ph-arrow-right text-sm ml-1"></i>
                </a>
            @endif
        </div>
        <ul class="space-y-2 flex-1 overflow-y-auto" data-pending-list>
            <li class="text-sm text-muted">Loading…</li>
        </ul>
    </article>
</section>

<section class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-3 md:gap-4">
    <article class="bg-surface border border-border rounded-2xl p-4 md:p-5 flex flex-col min-h-[260px]">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div>
                <h3 class="text-sm font-semibold text-heading">On leave now</h3>
                <p class="text-xs text-muted mt-0.5">From employee status</p>
            </div>
            @if ($can('employees'))
                <a href="{{ route('modules.show', ['module' => 'employees']) }}?status=on_leave" class="text-xs font-medium text-muted hover:text-primary transition-colors min-h-[44px] sm:min-h-0 inline-flex items-center">
                    View all
                    <i class="ph ph-arrow-right text-sm ml-1"></i>
                </a>
            @endif
        </div>
        <ul class="space-y-2 flex-1 overflow-y-auto" data-on-leave-list>
            <li class="text-sm text-muted">Loading…</li>
        </ul>
        <p class="text-[11px] text-muted mt-3 border-t border-border pt-3" data-on-leave-note></p>
    </article>

    <article class="bg-surface border border-border rounded-2xl p-4 md:p-5 flex flex-col min-h-[260px]">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div>
                <h3 class="text-sm font-semibold text-heading">Recent activity</h3>
                <p class="text-xs text-muted mt-0.5">HR mutations & audits</p>
            </div>
            @if ($can('audit'))
                <a href="{{ route('modules.show', ['module' => 'audit']) }}" class="text-xs font-medium text-muted hover:text-primary transition-colors min-h-[44px] sm:min-h-0 inline-flex items-center">
                    Audit log
                    <i class="ph ph-arrow-right text-sm ml-1"></i>
                </a>
            @endif
        </div>
        <ul class="space-y-2.5 flex-1 overflow-y-auto" data-activity-list>
            <li class="text-sm text-muted">Loading…</li>
        </ul>
    </article>

    <article class="bg-surface border border-border rounded-2xl p-4 md:p-5 flex flex-col min-h-[260px] space-y-4">
        <div>
            <div class="flex flex-wrap items-center gap-2 mb-1">
                <h3 class="text-sm font-semibold text-heading">Payroll & talent snapshot</h3>
                <span class="inline-flex items-center h-6 px-2 rounded-lg bg-subtle border border-border text-[10px] font-semibold uppercase tracking-wide text-muted">P6–P7</span>
            </div>
            <p class="text-xs text-muted">Placeholders until Comp, Loans, Training, and Performance ship.</p>
        </div>

        <div class="grid grid-cols-1 gap-3 flex-1">
            <div class="rounded-xl border border-dashed border-border bg-subtle/50 p-3">
                <p class="text-xs font-semibold text-heading">Payroll / loans</p>
                <p class="text-xs text-muted mt-1" data-payroll-status>Upcoming payroll run and active loans appear in Phase 7.</p>
                <div class="flex flex-wrap gap-2 mt-2">
                    @if ($can('earnings'))
                        <a href="{{ route('modules.show', ['module' => 'earnings']) }}" class="text-xs font-medium text-primary hover:underline">Earnings</a>
                    @endif
                    @if ($can('deductions'))
                        <a href="{{ route('modules.show', ['module' => 'deductions']) }}" class="text-xs font-medium text-primary hover:underline">Deductions</a>
                    @endif
                    @if ($can('loans'))
                        <a href="{{ route('modules.show', ['module' => 'loans']) }}" class="text-xs font-medium text-primary hover:underline">Loans</a>
                    @endif
                </div>
            </div>
            <div class="rounded-xl border border-dashed border-border bg-subtle/50 p-3">
                <p class="text-xs font-semibold text-heading">Training / performance</p>
                <p class="text-xs text-muted mt-1" data-talent-status>Sessions and review cycle progress land in Phase 6.</p>
                <div class="flex flex-wrap gap-2 mt-2">
                    @if ($can('training'))
                        <a href="{{ route('modules.show', ['module' => 'training']) }}" class="text-xs font-medium text-primary hover:underline">Training</a>
                    @endif
                    @if ($can('performance'))
                        <a href="{{ route('modules.show', ['module' => 'performance']) }}" class="text-xs font-medium text-primary hover:underline">Performance</a>
                    @endif
                </div>
            </div>
        </div>
    </article>
</section>
@endif

{{-- Account strip --}}
<section class="bg-surface border border-border rounded-2xl p-5 md:p-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-base font-semibold text-heading">Your account</h2>
            <p class="text-sm text-muted mt-1">
                Signed in as <span id="dash-name" class="font-medium text-text">—</span>
                <span class="text-faint">·</span>
                <span id="dash-email" class="text-text-secondary">—</span>
            </p>
            @unless ($isHrDashboard)
                <p class="text-xs text-muted mt-2">
                    Roles: <span id="dash-roles" class="font-medium text-text">—</span>
                </p>
            @endunless
        </div>
        <button type="button" id="logout-others-btn-main" class="inline-flex items-center justify-center gap-1.5 h-10 min-h-[44px] px-4 rounded-xl border border-border bg-surface text-sm font-medium text-text-secondary hover:border-border-strong hover:bg-subtle transition-colors">
            <i class="ph ph-devices text-base"></i>
            Logout other devices
        </button>
    </div>
</section>
</div>
@endsection
