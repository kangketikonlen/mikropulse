import Chart from 'chart.js/auto';

class ChartCache {
    constructor(key, ttl = 60000) {
        this.key = key;
        this.ttl = ttl;
    }

    get() {
        try {
            const raw = localStorage.getItem(this.key);
            if (!raw) return null;
            const { data, timestamp } = JSON.parse(raw);
            if (Date.now() - timestamp > this.ttl) return null;
            return data;
        } catch {
            return null;
        }
    }

    set(data) {
        try {
            localStorage.setItem(this.key, JSON.stringify({ data, timestamp: Date.now() }));
        } catch {
        }
    }
}

class SmoothChartLoader {
    constructor() {
        this.rxCanvas = document.getElementById('rx-chart');
        this.txCanvas = document.getElementById('tx-chart');
        this.rxValueEl = document.getElementById('rx-value');
        this.txValueEl = document.getElementById('tx-value');
        this.badge = document.getElementById('traffic-status-badge');

        this.cache = new ChartCache('mikropulse-chart-data', 30000);
        this.rxChart = null;
        this.txChart = null;
        this.isLoading = false;
        this.retryCount = 0;
        this.maxRetries = 5;
        this.retryDelay = 1000;
        this.updateQueued = false;
        this.lastData = null;

        this.MAX_POINTS = 50;
        this.rxData = new Array(this.MAX_POINTS).fill(0);
        this.txData = new Array(this.MAX_POINTS).fill(0);
        this.rxDisplay = new Array(this.MAX_POINTS).fill(0);
        this.txDisplay = new Array(this.MAX_POINTS).fill(0);
        this.SMOOTH_FACTOR = 0.1;
        this.rafId = null;
    }

    init() {
        if (!this.rxCanvas || !this.txCanvas) return;

        this.showLoading();
        this.initCharts();
        this.startAnimationLoop();

        const cached = this.cache.get();
        if (cached) {
            this.applyData(cached);
            this.hideLoading();
        }

        if (window.Echo) {
            window.Echo.channel('router-updates')
                .listen('RouterDataUpdated', (e) => {
                    this.retryCount = 0;
                    this.handleData(e.data);
                })
                .error(() => {
                    this.retry();
                });
        }

        setTimeout(() => {
            if (this.isLoading) {
                this.hideLoading();
            }
        }, 5000);
    }

    initCharts() {
        const wrapper = this.rxCanvas.parentElement;
        const rect = wrapper.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;

        this.rxCanvas.width = rect.width * dpr;
        this.rxCanvas.height = rect.height * dpr;
        this.txCanvas.width = rect.width * dpr;
        this.txCanvas.height = rect.height * dpr;

        this.rxChart = new Chart(this.rxCanvas, {
            type: 'line',
            data: {
                labels: new Array(this.MAX_POINTS).fill(''),
                datasets: [{
                    data: [...this.rxDisplay],
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
                        suggestedMax: 10,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: { display: false },
                    },
                },
            },
        });

        this.txChart = new Chart(this.txCanvas, {
            type: 'line',
            data: {
                labels: new Array(this.MAX_POINTS).fill(''),
                datasets: [{
                    data: [...this.txDisplay],
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
                        suggestedMax: 10,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: { display: false },
                    },
                },
            },
        });
    }

    startAnimationLoop() {
        const animate = () => {
            this.renderFrame();
            if (this.isTransitioning()) {
                this.rafId = requestAnimationFrame(animate);
            } else {
                this.rafId = null;
            }
        };
        this.rafId = requestAnimationFrame(animate);
    }

    isTransitioning() {
        for (let i = 0; i < this.MAX_POINTS; i++) {
            if (Math.abs(this.rxData[i] - this.rxDisplay[i]) > 0.01) return true;
            if (Math.abs(this.txData[i] - this.txDisplay[i]) > 0.01) return true;
        }
        return false;
    }

    renderFrame() {
        let needsUpdate = false;

        for (let i = 0; i < this.MAX_POINTS; i++) {
            const oldRx = this.rxDisplay[i];
            const newRx = this.rxData[i];
            const oldTx = this.txDisplay[i];
            const newTx = this.txData[i];

            if (Math.abs(newRx - oldRx) > 0.01) {
                this.rxDisplay[i] = oldRx + (newRx - oldRx) * this.SMOOTH_FACTOR;
                needsUpdate = true;
            } else if (oldRx !== newRx) {
                this.rxDisplay[i] = newRx;
                needsUpdate = true;
            }

            if (Math.abs(newTx - oldTx) > 0.01) {
                this.txDisplay[i] = oldTx + (newTx - oldTx) * this.SMOOTH_FACTOR;
                needsUpdate = true;
            } else if (oldTx !== newTx) {
                this.txDisplay[i] = newTx;
                needsUpdate = true;
            }
        }

        if (needsUpdate) {
            if (this.rxChart) {
                this.rxChart.data.datasets[0].data = [...this.rxDisplay];
                this.rxChart.update('none');
            }
            if (this.txChart) {
                this.txChart.data.datasets[0].data = [...this.txDisplay];
                this.txChart.update('none');
            }
        }
    }

    handleData(data) {
        const traffic = data?.traffic ?? data?.data?.traffic ?? data;
        this.updateBadge(traffic?.status);
        this.isLoading = false;
        this.hideLoading();

        if (traffic?.status === 'connected') {
            this.rxData.push(traffic.rx || 0);
            this.rxData.shift();
            this.txData.push(traffic.tx || 0);
            this.txData.shift();

            this.transitionValue(this.rxValueEl, this.formatMbps(traffic.rx || 0));
            this.transitionValue(this.txValueEl, this.formatMbps(traffic.tx || 0));

            if (!this.rafId) {
                this.startAnimationLoop();
            }

            const payload = {
                rx: this.rxData,
                tx: this.txData,
                status: traffic.status,
            };
            this.lastData = payload;
            this.cache.set(payload);
        } else {
            if (this.rxValueEl) this.rxValueEl.textContent = '--';
            if (this.txValueEl) this.txValueEl.textContent = '--';
        }
    }

    transitionValue(el, newValue) {
        if (!el) return;

        const speed = parseFloat(newValue);
        el.classList.remove('speed-low', 'speed-medium', 'speed-high');
        if (!isNaN(speed)) {
            if (speed < 10) {
                el.classList.add('speed-low');
            } else if (speed < 17) {
                el.classList.add('speed-medium');
            } else {
                el.classList.add('speed-high');
            }
        }

        el.textContent = newValue;
    }

    applyData(data) {
        if (!data) return;
        if (data.rx && data.rx.length === this.MAX_POINTS) {
            this.rxData = [...data.rx];
            this.rxDisplay = [...data.rx];
        }
        if (data.tx && data.tx.length === this.MAX_POINTS) {
            this.txData = [...data.tx];
            this.txDisplay = [...data.tx];
        }
        if (data.status) {
            this.updateBadge(data.status);
        }
    }

    hideSkeletons() {
        const rxSkeleton = document.getElementById('rx-skeleton');
        const txSkeleton = document.getElementById('tx-skeleton');
        if (rxSkeleton) rxSkeleton.style.opacity = '0';
        if (txSkeleton) txSkeleton.style.opacity = '0';
    }

    showLoading() {
        this.isLoading = true;
        const container = document.getElementById('traffic-monitor');
        if (container) {
            container.classList.add('traffic-monitor-loading');
        }
    }

    hideLoading() {
        this.isLoading = false;
        this.hideSkeletons();
        const container = document.getElementById('traffic-monitor');
        if (container) {
            container.classList.remove('traffic-monitor-loading');
        }
    }

    updateBadge(status) {
        if (!this.badge) return;

        this.badge.classList.remove('traffic-monitor-badge-checking', 'traffic-monitor-badge-connected', 'traffic-monitor-badge-disconnected');

        if (status === 'connected') {
            this.badge.classList.add('traffic-monitor-badge-connected');
            this.badge.textContent = 'Router Online';
        } else {
            this.badge.classList.add('traffic-monitor-badge-disconnected');
            this.badge.textContent = status === 'disconnected' ? 'Router Offline' : 'Connection Error';
        }
    }

    formatMbps(bitsPerSecond) {
        return (bitsPerSecond / 1000000).toFixed(2);
    }

    retry() {
        if (this.retryCount >= this.maxRetries) return;

        this.retryCount++;
        const delay = this.retryDelay * Math.pow(2, this.retryCount - 1);

        setTimeout(() => {
            if (window.Echo && !window.Echo.connector.pusher.connection.state === 'connected') {
                window.Echo.channel('router-updates');
            }
        }, delay);
    }
}

const TrafficMonitor = (() => {
    let loader = null;

    function init() {
        if (loader) return;
        loader = new SmoothChartLoader();
        loader.init();
    }

    return { init };
})();

export default TrafficMonitor;
