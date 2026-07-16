const SystemUtilization = (() => {
    function drawGauge(canvasId, value, maxValue, color) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();

        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        ctx.scale(dpr, dpr);

        const width = rect.width;
        const height = rect.height;
        const centerX = width / 2;
        const centerY = height - 4;
        const radius = Math.max(Math.min(width / 2, height) - 8, 10);
        const lineWidth = 6;

        ctx.clearRect(0, 0, width, height);

        const startAngle = Math.PI;
        const endAngle = 2 * Math.PI;
        const percent = Math.min(value / maxValue, 1);
        const valueAngle = startAngle + (endAngle - startAngle) * percent;

        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, startAngle, endAngle);
        ctx.strokeStyle = '#e5e7eb';
        ctx.lineWidth = lineWidth;
        ctx.lineCap = 'round';
        ctx.stroke();

        if (percent > 0) {
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius, startAngle, valueAngle);
            ctx.strokeStyle = color;
            ctx.lineWidth = lineWidth;
            ctx.lineCap = 'round';
            ctx.stroke();
        }

        ctx.fillStyle = '#111827';
        ctx.font = 'bold 10px ui-sans-serif, system-ui, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(Math.round(value) + '%', centerX, centerY - radius / 2);
    }

    function init() {
        const uptimeEl = document.getElementById('system-uptime');
        const boardNameEl = document.getElementById('system-board-name');
        const versionEl = document.getElementById('system-version');

        if (!uptimeEl || !boardNameEl || !versionEl) return;

        function handleSystemData(data) {
            const system = data?.systemUtilization ?? data?.data?.systemUtilization ?? data;
            if (system?.status === 'connected') {
                uptimeEl.textContent = system.uptime || '0M';
                boardNameEl.textContent = system.board_name || 'Unknown Router';
                versionEl.textContent = 'v' + (system.version || 'Unknown');

                drawGauge('cpu-gauge', system.cpu || 0, 100, '#4f46e5');
                drawGauge('ram-gauge', system.memory || 0, 100, '#16a34a');
                drawGauge('storage-gauge', system.storage || 0, 100, '#d97706');
            }
        }

        if (window.Echo) {
            Echo.channel('router-updates')
                .listen('RouterDataUpdated', (e) => {
                    handleSystemData(e);
                });
        }
    }

    return { init };
})();

export default SystemUtilization;
