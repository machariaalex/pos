import './bootstrap';

// Registers the app-shell service worker (see public/sw.js) so the app is
// installable and static assets survive brief connection drops. Livewire
// requests are never intercepted — this doesn't enable offline sales.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js');
    });
}

import {
    Chart,
    BarController,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    DoughnutController,
    ArcElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
} from 'chart.js';

Chart.register(
    BarController,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    DoughnutController,
    ArcElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
);

// Exposed globally so chart-init scripts embedded in Blade views (inside a
// wire:ignore wrapper, re-run on livewire:navigated) can construct charts
// without every view needing its own import/bundle entry.
window.Chart = Chart;

const chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: {
            beginAtZero: true,
            ticks: { callback: (v) => 'KES ' + v.toLocaleString() },
            grid: { color: '#e7e1d5' },
        },
        x: { grid: { display: false } },
    },
};

// Livewire replaces the chart's host element (via a changing wire:key) on
// toggle rather than morphing it, so Chart.js's canvas/listeners need
// explicit teardown when that happens — watched for here rather than via
// Alpine's $cleanup magic, which isn't available in Livewire's bundled
// Alpine build.
function destroyOnRemoval(el, chart) {
    const parent = el.parentElement;
    if (!parent) return;

    const observer = new MutationObserver(() => {
        if (!parent.contains(el)) {
            chart.destroy();
            observer.disconnect();
        }
    });
    observer.observe(parent, { childList: true });
}

// Registered once here (not inline per-view) since Livewire's DOM morphing
// on re-render can't reliably re-execute inline <script> tags — the
// wire:ignore host div's wire:key changing forces Alpine to rebuild the
// component against this already-registered factory instead.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('barChart', (data, color = '#2d7a48') => ({
        init() {
            const ctx = this.$refs.canvas.getContext('2d');
            // A subtle top-to-bottom fade on each bar — options.scales
            // aren't known yet at chart-construction time, so this uses a
            // fixed height rather than reading the eventual chart area;
            // close enough for a decorative gradient at this chart size.
            const gradient = ctx.createLinearGradient(0, 0, 0, 256);
            gradient.addColorStop(0, color);
            gradient.addColorStop(1, `${color}55`);

            const chart = new window.Chart(this.$refs.canvas, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.values,
                        backgroundColor: gradient,
                        hoverBackgroundColor: color,
                        borderRadius: 4,
                        maxBarThickness: 36,
                    }],
                },
                options: chartDefaults,
            });
            destroyOnRemoval(this.$el, chart);
        },
    }));

    window.Alpine.data('doughnutChart', (data, colors) => ({
        init() {
            const chart = new window.Chart(this.$refs.canvas, {
                type: 'doughnut',
                data: {
                    labels: data.labels,
                    datasets: [{ data: data.values, backgroundColor: colors, borderWidth: 0 }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 12 } } },
                },
            });
            destroyOnRemoval(this.$el, chart);
        },
    }));
});
