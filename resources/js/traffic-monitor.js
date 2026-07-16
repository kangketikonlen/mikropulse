import Chart from 'chart.js/auto';

const TrafficMonitor = (() => {
    const MAX_POINTS = 50;

    const rxData = new Array(MAX_POINTS).fill(0);
    const txData = new Array(MAX_POINTS).fill(0);
    let rxChart = null;
    let txChart = null;
    let scheduled = false;

    function formatMbps(bitsPerSecond) {
        return (bitsPerSecond / 1000000).toFixed(2);
    }

    function initCharts() {
        const rxCanvas = document.getElementById('rx-chart');
        const txCanvas = document.getElementById('tx-chart');

        if (!rxCanvas || !txCanvas) return;

        const wrapper = rxCanvas.parentElement;
        const rect = wrapper.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;

        rxCanvas.width = rect.width * dpr;
        rxCanvas.height = rect.height * dpr;
        txCanvas.width = rect.width * dpr;
        txCanvas.height = rect.height * dpr;

        rxChart = new Chart(rxCanvas, {
            type: 'line',
            data: {
                labels: new Array(MAX_POINTS).fill(''),
                datasets: [{
                    data: [...rxData],
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0,
                    pointRadius: 0,
                }]
            },
            options: {
                animation: {
                    duration: 0,
                    easing: 'linear',
                },
                responsive: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false },
                },
                scales: {
                    x: { display: false },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: { display: false },
                    },
                },
            },
        });

        txChart = new Chart(txCanvas, {
            type: 'line',
            data: {
                labels: new Array(MAX_POINTS).fill(''),
                datasets: [{
                    data: [...txData],
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22, 163, 74, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0,
                    pointRadius: 0,
                }]
            },
            options: {
                animation: {
                    duration: 0,
                    easing: 'linear',
                },
                responsive: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false },
                },
                scales: {
                    x: { display: false },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: { display: false },
                    },
                },
            },
        });
    }

    function scheduleRender() {
        if (scheduled) return;
        scheduled = true;
        requestAnimationFrame(() => {
            scheduled = false;
            if (rxChart) rxChart.update('none');
            if (txChart) txChart.update('none');
        });
    }

    function updateBadge(status) {
        const badge = document.getElementById('traffic-status-badge');
        if (!badge) return;

        badge.classList.remove('traffic-monitor-badge-checking', 'traffic-monitor-badge-connected', 'traffic-monitor-badge-disconnected');

        if (status === 'connected') {
            badge.classList.add('traffic-monitor-badge-connected');
            badge.textContent = 'Router Online';
        } else {
            badge.classList.add('traffic-monitor-badge-disconnected');
            badge.textContent = status === 'disconnected' ? 'Router Offline' : 'Connection Error';
        }
    }

    function handleTraffic(data) {
        const traffic = data?.traffic ?? data?.data?.traffic ?? data;
        updateBadge(traffic?.status);

        if (traffic?.status === 'connected') {
            rxData.push(traffic.rx || 0);
            rxData.shift();
            txData.push(traffic.tx || 0);
            txData.shift();

            const rxValueEl = document.getElementById('rx-value');
            const txValueEl = document.getElementById('tx-value');
            if (rxValueEl) rxValueEl.textContent = formatMbps(traffic.rx || 0);
            if (txValueEl) txValueEl.textContent = formatMbps(traffic.tx || 0);

            if (rxChart) {
                rxChart.data.datasets[0].data = [...rxData];
            }
            if (txChart) {
                txChart.data.datasets[0].data = [...txData];
            }

            scheduleRender();
        } else {
            const rxValueEl = document.getElementById('rx-value');
            const txValueEl = document.getElementById('tx-value');
            if (rxValueEl) rxValueEl.textContent = '--';
            if (txValueEl) txValueEl.textContent = '--';
        }
    }

    function init() {
        const rxValueEl = document.getElementById('rx-value');
        const txValueEl = document.getElementById('tx-value');
        const rxCanvas = document.getElementById('rx-chart');
        const txCanvas = document.getElementById('tx-chart');
        const badge = document.getElementById('traffic-status-badge');

        if (!rxValueEl || !txValueEl || !rxCanvas || !txCanvas) return;

        initCharts();

        if (window.Echo) {
            Echo.channel('router-updates')
                .listen('RouterDataUpdated', (e) => {
                    console.log('[TrafficMonitor] Event received', e.data?.traffic);
                    handleTraffic(e.data?.traffic);
                });
        }
    }

    return { init };
})();

export default TrafficMonitor;
