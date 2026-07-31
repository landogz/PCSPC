import http from '../../utils/http';
import { toastError } from '../../utils/toast';

function formatCount(value) {
    if (value == null || Number.isNaN(Number(value))) {
        return '—';
    }

    return Number(value).toLocaleString();
}

function renderDelta(el, delta) {
    if (!el) {
        return;
    }

    if (delta == null || Number.isNaN(Number(delta))) {
        el.classList.add('hidden');
        el.innerHTML = '';
        return;
    }

    const numeric = Number(delta);
    const up = numeric >= 0;
    el.classList.remove('hidden', 'text-success', 'text-danger');
    el.classList.add(up ? 'text-success' : 'text-danger');
    el.innerHTML = `
        <i class="ph ${up ? 'ph-trend-up' : 'ph-trend-down'} text-sm"></i>${Math.abs(numeric).toLocaleString()}%
    `;
}

function setText(selector, value) {
    const el = document.querySelector(selector);
    if (el) {
        el.textContent = value;
    }
}

export function initDashboardModule() {
    const root = document.querySelector('[data-module="dashboard"]');
    if (!root) {
        return;
    }

    (async () => {
        try {
            const { data } = await http.get('/dashboard/stats');
            const stats = data?.data || {};

            setText('[data-stat="employees"]', formatCount(stats.employees?.value));
            setText('[data-stat="on-leave"]', formatCount(stats.on_leave?.value));
            setText('[data-stat="departments"]', formatCount(stats.departments?.value));
            setText('[data-stat="summary-on-leave"]', formatCount(stats.summary?.on_leave ?? stats.on_leave?.value));

            renderDelta(root.querySelector('[data-stat-delta="employees"]'), stats.employees?.delta_percent);
            renderDelta(root.querySelector('[data-stat-delta="on-leave"]'), stats.on_leave?.delta_percent);
            renderDelta(root.querySelector('[data-stat-delta="departments"]'), stats.departments?.delta_percent);

            const attendanceEl = root.querySelector('[data-stat="attendance"]');
            if (attendanceEl) {
                attendanceEl.textContent = stats.attendance?.available
                    ? formatCount(stats.attendance.value)
                    : '—';
            }

            const checkInsEl = root.querySelector('[data-stat="summary-check-ins"]');
            if (checkInsEl) {
                checkInsEl.textContent = stats.summary?.check_ins?.available
                    ? (stats.summary.check_ins.value ?? '—')
                    : '—';
            }

            const lateEl = root.querySelector('[data-stat="summary-late"]');
            if (lateEl) {
                lateEl.textContent = stats.summary?.late_arrivals?.available
                    ? formatCount(stats.summary.late_arrivals.value)
                    : '—';
            }

            const onLeaveBar = root.querySelector('[data-stat-bar="on-leave"]');
            if (onLeaveBar) {
                const share = stats.on_leave?.share_percent;
                onLeaveBar.style.width = share == null ? '0%' : `${Math.min(100, Math.max(0, share))}%`;
            }
        } catch (error) {
            toastError(error.response?.data?.message || 'Unable to load dashboard stats');
        }
    })();
}
