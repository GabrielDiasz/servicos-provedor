import Chart from 'chart.js/auto';

const isDark = () => document.documentElement.classList.contains('dark');

const palette = [
    '#064b31',
    '#0c5f3a',
    '#ff7a00',
    '#f97316',
    '#0ea5e9',
    '#2563eb',
    '#10b981',
    '#f43f5e',
    '#8b5cf6',
    '#14b8a6',
];

const soften = (hex, alpha = '22') => `${hex}${alpha}`;

const buildColors = (count, accent = 'green') => {
    const accentPalette = accent === 'orange'
        ? ['#ff7a00', '#f97316', '#fb923c', '#fdba74', '#fed7aa', '#ffedd5']
        : ['#064b31', '#0c5f3a', '#10b981', '#34d399', '#6ee7b7', '#a7f3d0'];

    return Array.from({ length: count }, (_, index) => accentPalette[index % accentPalette.length]);
};

document.querySelectorAll('[data-dashboard-chart]').forEach((canvas) => {
    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const values = JSON.parse(canvas.dataset.values || '[]');
    const chartType = canvas.dataset.dashboardChart;
    const accent = canvas.dataset.accent || 'green';

    if (!labels.length || !values.length) {
        return;
    }

    const textColor = isDark() ? '#cbd5e1' : '#475569';
    const gridColor = isDark() ? 'rgba(148, 163, 184, 0.16)' : 'rgba(148, 163, 184, 0.18)';
    const borderColor = accent === 'orange' ? '#ff7a00' : '#064b31';
    const colors = buildColors(labels.length, accent);

    new Chart(canvas, {
        type: chartType === 'doughnut' ? 'doughnut' : 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: canvas.dataset.chartTitle || 'Dados',
                    data: values,
                    backgroundColor:
                        chartType === 'doughnut'
                            ? colors.map((color, index) => soften(color, index % 2 === 0 ? 'E6' : 'CC'))
                            : colors[0],
                    borderColor:
                        chartType === 'doughnut'
                            ? '#ffffff'
                            : borderColor,
                    borderWidth: chartType === 'doughnut' ? 2 : 1,
                    borderRadius: chartType === 'bar' ? 10 : 0,
                    hoverOffset: chartType === 'doughnut' ? 4 : undefined,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: chartType === 'doughnut',
                    position: 'bottom',
                    labels: {
                        color: textColor,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 10,
                        padding: 16,
                    },
                },
                tooltip: {
                    backgroundColor: isDark() ? '#0f172a' : '#ffffff',
                    titleColor: isDark() ? '#f8fafc' : '#0f172a',
                    bodyColor: isDark() ? '#e2e8f0' : '#1f2937',
                    borderColor: gridColor,
                    borderWidth: 1,
                    padding: 12,
                },
            },
            scales:
                chartType === 'doughnut'
                    ? {}
                    : {
                          x: {
                              ticks: { color: textColor },
                              grid: { color: gridColor },
                          },
                          y: {
                              beginAtZero: true,
                              ticks: { color: textColor, precision: 0 },
                              grid: { color: gridColor },
                          },
                      },
        },
    });
});
