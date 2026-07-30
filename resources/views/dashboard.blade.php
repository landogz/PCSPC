@extends('layouts.app')

@section('title', 'Dashboard — '.config('app.name'))
@section('page-title', 'Dashboard')

@php
    use App\Support\Navigation;
    $can = static fn (string $key): bool => Navigation::userCanAccess(
        auth()->user(),
        Navigation::find($key) ?? [],
    );
@endphp

@section('content')
{{-- Welcome Hero + Quick Actions + Today's Summary --}}
<section class="grid grid-cols-1 lg:grid-cols-12 gap-3 md:gap-4">
    <div class="lg:col-span-6 2xl:col-span-5 relative overflow-hidden rounded-2xl bg-primary/10 p-5 md:p-6 flex flex-col justify-between min-h-[180px]">
        <div class="pointer-events-none absolute -top-8 -right-8 w-40 h-40 rounded-full bg-primary/10"></div>
        <div class="pointer-events-none absolute top-12 -right-4 w-24 h-24 rounded-full bg-primary/8"></div>
        <div class="pointer-events-none absolute -bottom-6 right-16 w-20 h-20 rounded-full bg-primary/6"></div>

        <div>
            <p class="text-muted text-sm font-medium flex items-center gap-1.5">
                Good morning <span>👋</span>
            </p>
            <h2 class="text-heading text-2xl font-bold mt-1">
                Welcome back, <span id="dash-first-name">there</span>!
            </h2>
            <p class="text-text-secondary text-sm mt-1.5">
                Here's what's happening at PCSPC today.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 mt-5">
            @if ($can('employees'))
                <a href="{{ route('modules.show', ['module' => 'employees']) }}" class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-strong transition-colors">
                    <i class="ph ph-users text-base"></i>Manage Team
                </a>
            @elseif ($can('leave'))
                <a href="{{ route('modules.show', ['module' => 'leave']) }}" class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-strong transition-colors">
                    <i class="ph ph-calendar-x text-base"></i>File Leave
                </a>
            @endif
            @if ($can('reports'))
                <a href="{{ route('modules.show', ['module' => 'reports']) }}" class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl border border-border bg-surface text-text text-sm font-medium hover:border-border-strong transition-colors">
                    <i class="ph ph-chart-bar text-base"></i>View Reports
                </a>
            @elseif ($can('timekeeping'))
                <a href="{{ route('modules.show', ['module' => 'timekeeping']) }}" class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl border border-border bg-surface text-text text-sm font-medium hover:border-border-strong transition-colors">
                    <i class="ph ph-clock-user text-base"></i>Timekeeping
                </a>
            @endif
        </div>
    </div>

    <div class="lg:col-span-6 2xl:col-span-4 bg-surface border border-border rounded-2xl p-5 md:p-6 flex flex-col">
        <h2 class="text-base font-semibold text-heading mb-4">Quick Actions</h2>
        <div class="grid sm:grid-cols-2 gap-3 flex-1">
            @if ($can('employees'))
                <a href="{{ route('modules.show', ['module' => 'employees']) }}" class="flex items-center gap-3 p-3 rounded-xl border border-border hover:border-primary/40 hover:bg-primary-soft transition-colors group">
                    <span class="w-9 h-9 rounded-lg bg-primary-soft text-primary flex items-center justify-center flex-shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                        <i class="ph ph-user-plus text-lg"></i>
                    </span>
                    <span class="text-sm font-medium text-text group-hover:text-primary transition-colors">Employees</span>
                </a>
            @endif
            @if ($can('leave'))
                <a href="{{ route('modules.show', ['module' => 'leave']) }}" class="flex items-center gap-3 p-3 rounded-xl border border-border hover:border-[#e6347f]/40 hover:bg-[#e6347f]/8 transition-colors group">
                    <span class="w-9 h-9 rounded-lg bg-[#e6347f]/12 text-[#e6347f] flex items-center justify-center flex-shrink-0 group-hover:bg-[#e6347f] group-hover:text-white transition-colors">
                        <i class="ph ph-calendar-x text-lg"></i>
                    </span>
                    <span class="text-sm font-medium text-text group-hover:text-[#e6347f] transition-colors">Leave Request</span>
                </a>
            @endif
            @if ($can('timekeeping'))
                <a href="{{ route('modules.show', ['module' => 'timekeeping']) }}" class="flex items-center gap-3 p-3 rounded-xl border border-border hover:border-success/40 hover:bg-success-soft transition-colors group">
                    <span class="w-9 h-9 rounded-lg bg-success-soft text-success flex items-center justify-center flex-shrink-0 group-hover:bg-success group-hover:text-white transition-colors">
                        <i class="ph ph-clock-user text-lg"></i>
                    </span>
                    <span class="text-sm font-medium text-text group-hover:text-success transition-colors">Timekeeping</span>
                </a>
            @endif
            @if ($can('medical'))
                <a href="{{ route('modules.show', ['module' => 'medical']) }}" class="flex items-center gap-3 p-3 rounded-xl border border-border hover:border-[#f59e0b]/40 hover:bg-[#f59e0b]/10 transition-colors group">
                    <span class="w-9 h-9 rounded-lg bg-[#f59e0b]/15 text-[#f59e0b] flex items-center justify-center flex-shrink-0 group-hover:bg-[#f59e0b] group-hover:text-white transition-colors">
                        <i class="ph ph-heartbeat text-lg"></i>
                    </span>
                    <span class="text-sm font-medium text-text group-hover:text-[#f59e0b] transition-colors">Medical</span>
                </a>
            @endif
        </div>
    </div>

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
                    <span class="text-sm font-semibold text-heading">312 / 348</span>
                </div>
                <div class="h-1.5 rounded-full bg-subtle overflow-hidden">
                    <div class="h-full rounded-full bg-success" style="width: 89.7%"></div>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-warning flex-shrink-0"></span>
                        <span class="text-sm text-text">On Leave</span>
                    </div>
                    <span class="text-sm font-semibold text-heading">24</span>
                </div>
                <div class="h-1.5 rounded-full bg-subtle overflow-hidden">
                    <div class="h-full rounded-full bg-warning" style="width: 35%"></div>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-danger flex-shrink-0"></span>
                        <span class="text-sm text-text">Late Arrivals</span>
                    </div>
                    <span class="text-sm font-semibold text-heading">12</span>
                </div>
                <div class="h-1.5 rounded-full bg-subtle overflow-hidden">
                    <div class="h-full rounded-full bg-danger" style="width: 18%"></div>
                </div>
            </div>

            <div class="mt-auto pt-3 border-t border-border-subtle flex items-center justify-between">
                <span class="text-sm text-primary font-medium">Session</span>
                <span id="dash-roles" class="text-xs font-semibold text-primary">—</span>
            </div>
        </div>
    </div>
</section>

{{-- Stat cards --}}
<section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 md:gap-4">
    <article class="bg-surface border border-border rounded-2xl p-4 md:p-5 flex flex-col gap-4">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-11 h-11 rounded-xl bg-primary-soft text-primary flex items-center justify-center flex-shrink-0">
                    <i class="ph ph-fingerprint text-2xl"></i>
                </span>
                <div class="min-w-0">
                    <p class="text-2xl font-bold text-heading leading-none">324</p>
                    <p class="text-sm text-muted mt-1 text-nowrap">Attendance</p>
                </div>
            </div>
            <span class="inline-flex items-center gap-0.5 text-xs font-semibold text-success flex-shrink-0">
                <i class="ph ph-trend-up text-sm"></i>12.32%
            </span>
        </div>
        @if ($can('timekeeping'))
            <a href="{{ route('modules.show', ['module' => 'timekeeping']) }}" class="flex items-center justify-between text-sm text-muted hover:text-primary transition-colors pt-3 border-t border-border-subtle">
                <span>View Details</span>
                <i class="ph ph-arrow-square-out"></i>
            </a>
        @endif
    </article>

    <article class="bg-surface border border-border rounded-2xl p-4 md:p-5 flex flex-col gap-4">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-11 h-11 rounded-xl bg-[#e6347f]/12 text-[#e6347f] flex items-center justify-center flex-shrink-0">
                    <i class="ph ph-users-three text-2xl"></i>
                </span>
                <div class="min-w-0">
                    <p class="text-2xl font-bold text-heading leading-none">1,248</p>
                    <p class="text-sm text-muted mt-1 text-nowrap">Employees</p>
                </div>
            </div>
            <span class="inline-flex items-center gap-0.5 text-xs font-semibold text-success flex-shrink-0">
                <i class="ph ph-trend-up text-sm"></i>3.4%
            </span>
        </div>
        @if ($can('employees'))
            <a href="{{ route('modules.show', ['module' => 'employees']) }}" class="flex items-center justify-between text-sm text-muted hover:text-primary transition-colors pt-3 border-t border-border-subtle">
                <span>View Details</span>
                <i class="ph ph-arrow-square-out"></i>
            </a>
        @endif
    </article>

    <article class="bg-surface border border-border rounded-2xl p-4 md:p-5 flex flex-col gap-4">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-11 h-11 rounded-xl bg-success-soft text-success flex items-center justify-center flex-shrink-0">
                    <i class="ph ph-airplane-takeoff text-2xl"></i>
                </span>
                <div class="min-w-0">
                    <p class="text-2xl font-bold text-heading leading-none">24</p>
                    <p class="text-sm text-muted mt-1 text-nowrap">On Leave</p>
                </div>
            </div>
            <span class="inline-flex items-center gap-0.5 text-xs font-semibold text-danger flex-shrink-0">
                <i class="ph ph-trend-down text-sm"></i>1.2%
            </span>
        </div>
        @if ($can('leave'))
            <a href="{{ route('modules.show', ['module' => 'leave']) }}" class="flex items-center justify-between text-sm text-muted hover:text-primary transition-colors pt-3 border-t border-border-subtle">
                <span>View Details</span>
                <i class="ph ph-arrow-square-out"></i>
            </a>
        @endif
    </article>

    <article class="bg-surface border border-border rounded-2xl p-4 md:p-5 flex flex-col gap-4">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-11 h-11 rounded-xl bg-[#f59e0b]/15 text-[#f59e0b] flex items-center justify-center flex-shrink-0">
                    <i class="ph ph-buildings text-2xl"></i>
                </span>
                <div class="min-w-0">
                    <p class="text-2xl font-bold text-heading leading-none">18</p>
                    <p class="text-sm text-muted mt-1 text-nowrap">Departments</p>
                </div>
            </div>
            <span class="inline-flex items-center gap-0.5 text-xs font-semibold text-success flex-shrink-0">
                <i class="ph ph-trend-up text-sm"></i>0.8%
            </span>
        </div>
        @if ($can('departments'))
            <a href="{{ route('modules.show', ['module' => 'departments']) }}" class="flex items-center justify-between text-sm text-muted hover:text-primary transition-colors pt-3 border-t border-border-subtle">
                <span>View Details</span>
                <i class="ph ph-arrow-square-out"></i>
            </a>
        @endif
    </article>
</section>

{{-- Account strip --}}
<section class="bg-surface border border-border rounded-2xl p-5 md:p-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-base font-semibold text-heading">Your account</h2>
            <p class="text-sm text-muted mt-1">
                Signed in as <span id="dash-name" class="font-medium text-text">—</span>
                (<span class="text-faint">·</span>
                <span id="dash-email" class="text-text-secondary">—</span>
            </p>
        </div>
        <button type="button" id="logout-others-btn-main" class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl border border-border bg-surface text-sm font-medium text-text-secondary hover:border-border-strong hover:bg-subtle transition-colors">
            <i class="ph ph-devices text-base"></i>
            Logout other devices
        </button>
    </div>
</section>
@endsection
