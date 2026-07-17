import Chart from 'chart.js/auto';

const TrafficMonitor = (() => {
    const MAX_POINTS = 50;

    const rxData = new Array(MAX_POINTS).fill(0);
    const txData = new Array(MAX_POINTS).fill(0);
    let rxChart = null;
    let txChart = null;

    function formatMbps(bitsPerSecond) {
        return (bitsPerSecond / 1000000).toFixed(2);
    }

    function getSpeedClass(mbps) {
        if (mbps < 10) return 'speed-low';
        if (mbps < 17) return 'speed-medium';
        return 'speed-high';
    }

    function initCharts() {
        const rxCanvas = document.getElementById('rx-chart');
        const txCanvas = document.getElementById('tx-chart');

        if (!rxCanvas || !txCanvas) return;

        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            layout: {
                padding: 0,
            },
            interaction: {
                enabled: false,
            },
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false },
            },
            scales: {
                x: {
                    display: false,
                    grid: { display: false },
                    ticks: { display: false },
                },
                y: {
                    beginAtZero: true,
                    suggestedMax: 10,
                    grid: {
                        color: '#fff',
                        drawTicks: false,
                    },
                    ticks: {
                        display: false,
                        stepSize: 10,
                    },
                    border: { display: false },
                },
            },
            elements: {
                point: {
                    radius: 0,
                    hoverRadius: 0,
                },
                line: {
                    tension: 0,
                    borderWidth: 2,
                    fill: true,
                },
            },
        };

        rxChart = new Chart(rxCanvas, {
            type: 'line',
            data: {
                labels: new Array(MAX_POINTS).fill(''),
                datasets: [{
                    data: [...rxData],
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.2)',
                    fill: true,
                }]
            },
            options: commonOptions,
        });

        txChart = new Chart(txCanvas, {
            type: 'line',
            data: {
                labels: new Array(MAX_POINTS).fill(''),
                datasets: [{
                    data: [...txData],
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22, 163, 74, 0.2)',
                    fill: true,
                }]
            },
            options: commonOptions,
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

            if (rxValueEl) {
                const rxMbps = formatMbps(traffic.rx || 0);
                rxValueEl.textContent = rxMbps;
                rxValueEl.className = 'traffic-monitor-card-value ' + getSpeedClass(parseFloat(rxMbps));
            }

            if (txValueEl) {
                const txMbps = formatMbps(traffic.tx || 0);
                txValueEl.textContent = txMbps;
                txValueEl.className = 'traffic-monitor-card-value ' + getSpeedClass(parseFloat(txMbps));
            }

            if (rxChart) {
                rxChart.data.datasets[0].data = [...rxData];
                rxChart.update('none');
            }

            if (txChart) {
                txChart.data.datasets[0].data = [...txData];
                txChart.update('none');
            }
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
                    handleTraffic(e.data?.traffic);
                });
        }
    }

    return { init };
})();

export default TrafficMonitor;
