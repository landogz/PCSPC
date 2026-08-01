/**
 * Shared Chart.js helpers for SPA dashboards.
 */
import {
    Chart,
    ArcElement,
    BarElement,
    CategoryScale,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    BarController,
    DoughnutController,
    PointElement,
    Tooltip,
} from 'chart.js';

Chart.register(
    ArcElement,
    BarElement,
    CategoryScale,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    BarController,
    DoughnutController,
    PointElement,
    Tooltip,
);

function cssVar(name, fallback) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return value || fallback;
}

export function chartTheme() {
    return {
        primary: cssVar('--color-primary', '#d31219'),
        success: cssVar('--color-success', '#2f9e44'),
        warning: cssVar('--color-warning', '#ffcc00'),
        danger: cssVar('--color-danger', '#cd3d1c'),
        muted: cssVar('--color-n400', '#94a3b8'),
        border: cssVar('--color-n200', '#e2e5ea'),
        text: cssVar('--color-n700', '#334155'),
        soft: [
            cssVar('--color-primary', '#d31219'),
            '#e6347f',
            cssVar('--color-success', '#2f9e44'),
            '#f59e0b',
            '#6366f1',
            '#0ea5e9',
            '#14b8a6',
            '#8b5cf6',
        ],
    };
}

export function destroyChart(chart) {
    if (chart && typeof chart.destroy === 'function') {
        chart.destroy();
    }
}

export function createBarChart(canvas, { labels, values, horizontal = false, label = 'Count' }) {
    const theme = chartTheme();
    const colors = labels.map((_, index) => theme.soft[index % theme.soft.length]);

    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label,
                data: values,
                backgroundColor: colors,
                borderRadius: 8,
                maxBarThickness: horizontal ? 28 : 36,
            }],
        },
        options: {
            indexAxis: horizontal ? 'y' : 'x',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { mode: 'index', intersect: false },
            },
            scales: {
                x: {
                    grid: { color: `${theme.border}55`, drawBorder: false },
                    ticks: { color: theme.muted, font: { size: 11 } },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: `${theme.border}55`, drawBorder: false },
                    ticks: {
                        color: theme.muted,
                        font: { size: 11 },
                        precision: 0,
                    },
                },
            },
        },
    });
}

export function createLineChart(canvas, { labels, datasets }) {
    const theme = chartTheme();

    return new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: datasets.map((set) => ({
                tension: 0.35,
                fill: set.fill ?? false,
                borderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 5,
                ...set,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 10, boxHeight: 10, color: theme.text, font: { size: 11 } },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: theme.muted, font: { size: 10 }, maxRotation: 0 },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: `${theme.border}55`, drawBorder: false },
                    ticks: { color: theme.muted, font: { size: 11 }, precision: 0 },
                },
            },
        },
    });
}

export function createDoughnutChart(canvas, { labels, values }) {
    const theme = chartTheme();

    return new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: [
                    theme.success,
                    theme.warning,
                    theme.danger,
                    theme.primary,
                ],
                borderWidth: 0,
                hoverOffset: 4,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 10, boxHeight: 10, color: theme.text, font: { size: 11 } },
                },
            },
        },
    });
}

export { Chart };
