import './bootstrap';
import Chart from 'chart.js/auto';
import confetti from 'canvas-confetti';

/* ------------------------------------------------------------------ */
/* Theme                                                               */
/* ------------------------------------------------------------------ */
window.toggleTheme = function () {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    if (lastCharts) {
        renderCharts(lastCharts, true);
    }
};

/* ------------------------------------------------------------------ */
/* Toasts                                                              */
/* ------------------------------------------------------------------ */
function toast(message, type = 'success') {
    const host = document.getElementById('toast-host');
    if (!host) return;

    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.innerHTML = '<span class="toast-dot"></span><span class="text-sm font-medium leading-5"></span>';
    el.querySelector('span:last-child').textContent = message;
    host.appendChild(el);

    requestAnimationFrame(() => el.classList.add('toast-in'));
    setTimeout(() => {
        el.classList.remove('toast-in');
        setTimeout(() => el.remove(), 350);
    }, 3600);
}

window.toast = toast;

window.addEventListener('toast', (event) => {
    const detail = event.detail ?? {};
    const data = Array.isArray(detail) ? detail[0] : detail;
    toast(data?.message ?? 'Saved', data?.type ?? 'success');
});

/* ------------------------------------------------------------------ */
/* Count-up numbers                                                    */
/* ------------------------------------------------------------------ */
function animateCounters() {
    document.querySelectorAll('[data-countup]').forEach((el) => {
        if (el.dataset.counted === '1') return;
        el.dataset.counted = '1';

        const target = Number(el.dataset.countup || 0);
        const decimals = Number(el.dataset.decimals || 0);
        const prefix = el.dataset.prefix || '';
        const suffix = el.dataset.suffix || '';
        const start = performance.now();
        const duration = 900;

        const format = (value) =>
            prefix +
            value.toLocaleString('en-PH', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            }) +
            suffix;

        const tick = (now) => {
            const progress = Math.min(1, (now - start) / duration);
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = format(target * eased);
            if (progress < 1) requestAnimationFrame(tick);
        };

        requestAnimationFrame(tick);
    });
}

/* ------------------------------------------------------------------ */
/* Charts                                                              */
/* ------------------------------------------------------------------ */
let lastCharts = null;
let chartTimer = null;

function readCharts() {
    const node = document.getElementById('chart-payload');
    if (!node) return;

    try {
        const payload = JSON.parse(node.dataset.charts || '[]');
        if (Array.isArray(payload) && payload.length) {
            lastCharts = payload;
            renderCharts(payload);
        }
    } catch (error) {
        console.warn('Chart payload could not be parsed', error);
    }
}

function renderCharts(payload, force = false) {
    if (!payload) return;

    const dark = document.documentElement.classList.contains('dark');
    Chart.defaults.font.family = "'Inter', ui-sans-serif, system-ui, sans-serif";
    Chart.defaults.font.size = 11;
    Chart.defaults.color = dark ? '#94a3b8' : '#64748b';
    Chart.defaults.borderColor = dark ? 'rgba(148,163,184,0.1)' : 'rgba(100,116,139,0.12)';

    payload.forEach((cfg) => {
        const canvas = document.getElementById(cfg.id);
        if (!canvas) return;

        const existing = Chart.getChart(canvas);
        if (existing) existing.destroy();

        const currency = cfg.currency || '₱';
        const money = (value) =>
            currency +
            Number(value).toLocaleString('en-PH', {
                maximumFractionDigits: 0,
            });

        const baseOptions = {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: force ? 350 : 950,
                easing: 'easeOutQuart',
            },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: cfg.legend ?? true,
                    position: 'top',
                    align: 'end',
                    labels: {
                        boxWidth: 8,
                        boxHeight: 8,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 16,
                    },
                },
                tooltip: {
                    backgroundColor: dark ? 'rgba(15,23,42,0.95)' : 'rgba(15,23,42,0.92)',
                    titleFont: { weight: '600' },
                    padding: 12,
                    cornerRadius: 10,
                    displayColors: false,
                    callbacks: {
                        label: (context) => {
                            const raw =
                                cfg.type === 'doughnut' || cfg.type === 'pie'
                                    ? context.parsed
                                    : context.parsed.y ?? context.parsed;
                            const prefix = context.dataset.label ? `${context.dataset.label}: ` : '';
                            return ` ${prefix}${money(raw)}`;
                        },
                    },
                },
            },
        };

        const scales =
            cfg.type === 'doughnut' || cfg.type === 'pie'
                ? {}
                : {
                      x: {
                          grid: { display: false },
                          border: { display: false },
                      },
                      y: {
                          beginAtZero: true,
                          border: { display: false },
                          grid: {
                              color: dark ? 'rgba(148,163,184,0.08)' : 'rgba(100,116,139,0.1)',
                          },
                          ticks: {
                              maxTicksLimit: 5,
                              callback: (value) => money(value),
                          },
                      },
                  };

        new Chart(canvas, {
            type: cfg.type,
            data: cfg.data,
            options: {
                ...baseOptions,
                ...(cfg.options || {}),
                plugins: { ...baseOptions.plugins, ...(cfg.options?.plugins || {}) },
                scales,
            },
        });
    });
}

/* ------------------------------------------------------------------ */
/* Confetti                                                            */
/* ------------------------------------------------------------------ */
window.addEventListener('celebrate', () => {
    const colors = ['#34d399', '#22d3ee', '#a78bfa', '#fbbf24', '#f472b6'];
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduced) return;

    confetti({
        particleCount: 110,
        spread: 78,
        origin: { y: 0.65 },
        colors,
        disableForReducedMotion: true,
    });
    setTimeout(
        () => confetti({ particleCount: 55, angle: 60, spread: 60, origin: { x: 0, y: 0.7 }, colors }),
        150,
    );
    setTimeout(
        () => confetti({ particleCount: 55, angle: 120, spread: 60, origin: { x: 1, y: 0.7 }, colors }),
        150,
    );
});

/* ------------------------------------------------------------------ */
/* Livewire hooks                                                      */
/* ------------------------------------------------------------------ */
document.addEventListener('livewire:init', () => {
    Livewire.hook('morphed', () => {
        clearTimeout(chartTimer);
        chartTimer = setTimeout(() => {
            readCharts();
            animateCounters();
        }, 60);
    });
});

document.addEventListener('livewire:navigated', () => {
    readCharts();
    animateCounters();
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        readCharts();
        animateCounters();
    });
} else {
    readCharts();
    animateCounters();
}
