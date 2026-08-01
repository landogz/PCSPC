import http from '../../utils/http';
import { toastError } from '../../utils/toast';
import {
    chartTheme,
    createBarChart,
    createDoughnutChart,
    createLineChart,
    destroyChart,
} from '../../utils/charts';

const charts = {
    attendanceTrend: null,
    attendanceToday: null,
    leaveByMonth: null,
    departmentHeadcount: null,
    headcountTrend: null,
};

function formatCount(value) {
    if (value == null || Number.isNaN(Number(value))) {
        return '—';
    }

    return Number(value).toLocaleString();
}

function setText(root, selector, value) {
    const el = root.querySelector(selector);
    if (el) {
        el.textContent = value;
    }
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function greetingForHour(hour) {
    if (hour < 12) {
        return 'Good morning';
    }
    if (hour < 18) {
        return 'Good afternoon';
    }
    return 'Good evening';
}

function setChartEmpty(root, key, show) {
    const empty = root.querySelector(`[data-empty-for="${key}"]`);
    const canvas = root.querySelector(`[data-chart="${key}"]`);
    if (empty) {
        empty.classList.toggle('hidden', !show);
    }
    if (canvas) {
        canvas.classList.toggle('opacity-0', show);
        canvas.classList.toggle('pointer-events-none', show);
    }
}

function renderPending(root, pending) {
    const list = root.querySelector('[data-pending-list]');
    if (!list) {
        return;
    }

    const items = pending?.items || [];
    if (items.length === 0) {
        list.innerHTML = '<li class="text-sm text-muted">Nothing pending.</li>';
        return;
    }

    list.innerHTML = items.map((item) => {
        const live = item.available;
        const count = live ? formatCount(item.count) : '—';
        const badge = live
            ? (item.count > 0
                ? '<span class="text-[10px] font-semibold uppercase tracking-wide text-danger">Action</span>'
                : '<span class="text-[10px] font-semibold uppercase tracking-wide text-success">Clear</span>')
            : `<span class="text-[10px] font-semibold uppercase tracking-wide text-muted">${escapeHtml(item.phase || 'Soon')}</span>`;

        return `
            <li>
                <a href="${escapeHtml(item.href || '#')}" class="flex items-start justify-between gap-3 rounded-xl border border-border p-3 hover:border-primary/40 hover:bg-primary-soft/40 transition-colors min-h-[44px]">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-medium text-heading truncate">${escapeHtml(item.label)}</p>
                            ${badge}
                        </div>
                        <p class="text-xs text-muted mt-0.5">${escapeHtml(item.description || '')}</p>
                    </div>
                    <span class="text-base font-bold text-heading flex-shrink-0">${count}</span>
                </a>
            </li>
        `;
    }).join('');
}

function renderOnLeave(root, onLeave) {
    const list = root.querySelector('[data-on-leave-list]');
    const note = root.querySelector('[data-on-leave-note]');
    if (note) {
        note.textContent = onLeave?.note || '';
    }
    if (!list) {
        return;
    }

    const items = onLeave?.items || [];
    if (items.length === 0) {
        list.innerHTML = '<li class="rounded-xl border border-dashed border-border bg-subtle/50 px-3 py-4 text-sm text-muted text-center">No one marked on leave.</li>';
        return;
    }

    list.innerHTML = items.map((item) => `
        <li class="flex items-center justify-between gap-3 rounded-xl border border-border px-3 py-2.5">
            <div class="min-w-0">
                <p class="text-sm font-medium text-heading truncate">${escapeHtml(item.name)}</p>
                <p class="text-xs text-muted truncate">${escapeHtml(item.department || 'No department')}${item.position_title ? ` · ${escapeHtml(item.position_title)}` : ''}</p>
            </div>
            <span class="text-[11px] font-semibold text-muted flex-shrink-0">${escapeHtml(item.employee_number)}</span>
        </li>
    `).join('');
}

function relativeTime(iso) {
    if (!iso) {
        return '';
    }
    const then = new Date(iso).getTime();
    if (Number.isNaN(then)) {
        return '';
    }
    const seconds = Math.round((Date.now() - then) / 1000);
    if (seconds < 60) {
        return 'just now';
    }
    if (seconds < 3600) {
        return `${Math.floor(seconds / 60)}m ago`;
    }
    if (seconds < 86400) {
        return `${Math.floor(seconds / 3600)}h ago`;
    }
    return `${Math.floor(seconds / 86400)}d ago`;
}

function renderActivity(root, activity) {
    const list = root.querySelector('[data-activity-list]');
    if (!list) {
        return;
    }

    const items = activity?.items || [];
    if (items.length === 0) {
        list.innerHTML = '<li class="rounded-xl border border-dashed border-border bg-subtle/50 px-3 py-4 text-sm text-muted text-center">No recent HR activity yet.</li>';
        return;
    }

    list.innerHTML = items.map((item) => `
        <li class="flex items-start gap-3">
            <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary-soft text-primary flex-shrink-0">
                <i class="ph ph-pulse text-base"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-sm text-heading">${escapeHtml(item.message)}</p>
                <p class="text-xs text-muted mt-0.5">
                    ${escapeHtml(item.actor || 'System')}
                    <span class="text-faint">·</span>
                    ${escapeHtml(relativeTime(item.created_at))}
                </p>
            </div>
        </li>
    `).join('');
}

function renderCharts(root, chartsData) {
    const theme = chartTheme();
    const attendanceTrend = chartsData?.attendance_trend;
    const attendanceToday = chartsData?.attendance_today;
    const leaveByMonth = chartsData?.leave_by_month;
    const department = chartsData?.department_headcount;
    const headcount = chartsData?.headcount_trend;

    const attendanceTrendCanvas = root.querySelector('[data-chart="attendance-trend"]');
    const attendanceTodayCanvas = root.querySelector('[data-chart="attendance-today"]');
    const leaveCanvas = root.querySelector('[data-chart="leave-by-month"]');
    const departmentCanvas = root.querySelector('[data-chart="department-headcount"]');
    const headcountCanvas = root.querySelector('[data-chart="headcount-trend"]');

    destroyChart(charts.attendanceTrend);
    destroyChart(charts.attendanceToday);
    destroyChart(charts.leaveByMonth);
    destroyChart(charts.departmentHeadcount);
    destroyChart(charts.headcountTrend);

    setChartEmpty(root, 'attendance-trend', !attendanceTrend?.available);
    setChartEmpty(root, 'attendance-today', !attendanceToday?.available);
    setChartEmpty(root, 'leave-by-month', !leaveByMonth?.available);

    if (departmentCanvas) {
        const hasDeptData = (department?.values || []).some((value) => Number(value) > 0);
        setChartEmpty(root, 'department-headcount', !hasDeptData);
        if (hasDeptData) {
            charts.departmentHeadcount = createBarChart(departmentCanvas, {
                labels: department.labels || [],
                values: department.values || [],
                horizontal: true,
                label: 'Employees',
            });
        }
    }

    if (headcountCanvas) {
        const hasMovement = (headcount?.hires || []).some((value) => Number(value) > 0)
            || (headcount?.separations || []).some((value) => Number(value) > 0);
        setChartEmpty(root, 'headcount-trend', !hasMovement);
        if (hasMovement) {
            charts.headcountTrend = createLineChart(headcountCanvas, {
                labels: headcount.labels || [],
                datasets: [
                    {
                        label: 'Hires',
                        data: headcount.hires || [],
                        borderColor: theme.success,
                        backgroundColor: `${theme.success}33`,
                        fill: true,
                    },
                    {
                        label: 'Separations',
                        data: headcount.separations || [],
                        borderColor: theme.danger,
                        backgroundColor: `${theme.danger}22`,
                        fill: true,
                    },
                    {
                        label: 'Net',
                        data: headcount.net || [],
                        borderColor: theme.primary,
                        backgroundColor: 'transparent',
                        fill: false,
                    },
                ],
            });
        }
    }

    if (attendanceToday?.available && attendanceTodayCanvas) {
        charts.attendanceToday = createDoughnutChart(attendanceTodayCanvas, {
            labels: attendanceToday.labels || [],
            values: attendanceToday.values || [],
        });
    }

    if (leaveByMonth?.available && leaveCanvas) {
        charts.leaveByMonth = createBarChart(leaveCanvas, {
            labels: leaveByMonth.labels || [],
            values: leaveByMonth.values || [],
            label: 'Leave requests',
        });
    }

    if (attendanceTrend?.available && attendanceTrendCanvas) {
        charts.attendanceTrend = createLineChart(attendanceTrendCanvas, {
            labels: attendanceTrend.labels || [],
            datasets: [
                {
                    label: 'On time',
                    data: attendanceTrend.on_time || [],
                    borderColor: theme.success,
                    backgroundColor: `${theme.success}33`,
                    fill: true,
                },
                {
                    label: 'Late',
                    data: attendanceTrend.late || [],
                    borderColor: theme.danger,
                    backgroundColor: `${theme.danger}22`,
                    fill: true,
                },
            ],
        });
    }
}

function fillKpis(root, stats) {
    setText(root, '[data-stat="employees"]', formatCount(stats.employees?.value));
    setText(root, '[data-stat="on-leave"]', formatCount(stats.on_leave?.value));
    setText(root, '[data-stat="departments"]', formatCount(stats.departments?.value));
    setText(root, '[data-stat="summary-on-leave"]', formatCount(stats.summary?.on_leave ?? stats.on_leave?.value));
    setText(root, '[data-stat="summary-pending"]', formatCount(stats.pending?.total_actionable));
    setText(root, '[data-stat="pending-total"]', formatCount(stats.pending?.total_actionable));
    setText(root, '[data-stat="net-headcount"]', formatCount(stats.headcount_movement?.net_this_month));

    const checkInsEl = root.querySelector('[data-stat="summary-check-ins"]');
    if (checkInsEl) {
        checkInsEl.textContent = stats.summary?.check_ins?.available
            ? (stats.summary.check_ins.value ?? '—')
            : '—';
    }

    const onLeaveBar = root.querySelector('[data-stat-bar="on-leave"]');
    if (onLeaveBar) {
        const share = stats.on_leave?.share_percent;
        onLeaveBar.style.width = share == null ? '0%' : `${Math.min(100, Math.max(0, share))}%`;
    }

    const pendingBar = root.querySelector('[data-stat-bar="pending"]');
    if (pendingBar) {
        const actionable = Number(stats.pending?.total_actionable || 0);
        const employees = Math.max(1, Number(stats.employees?.value || 1));
        pendingBar.style.width = `${Math.min(100, (actionable / employees) * 100)}%`;
    }
}

export function initDashboardModule() {
    const root = document.querySelector('[data-module="dashboard"]');
    if (!root) {
        return;
    }

    const greeting = root.querySelector('[data-dash-greeting]');
    if (greeting) {
        greeting.textContent = greetingForHour(new Date().getHours());
    }

    (async () => {
        try {
            const { data } = await http.get('/dashboard/stats');
            const stats = data?.data || {};

            fillKpis(root, stats);
            renderPending(root, stats.pending);
            renderOnLeave(root, stats.on_leave_now);
            renderActivity(root, stats.activity);
            renderCharts(root, stats.charts);
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to load dashboard stats');
            setText(root, '[data-pending-list]', '');
            const pending = root.querySelector('[data-pending-list]');
            if (pending) {
                pending.innerHTML = '<li class="text-sm text-danger">Unable to load pending actions.</li>';
            }
        }
    })();
}
