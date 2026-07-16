const RouterStatus = (() => {
    function init() {
        const statusDot = document.getElementById('router-status-dot');
        const statusLabel = document.getElementById('router-status-label');

        if (!statusDot || !statusLabel) return;

        function updateStatus(data) {
            statusDot.classList.remove('router-status-dot-checking');

            const status = data?.status ?? data?.data?.status;
            const identity = data?.identity ?? data?.data?.identity;

            if (status === 'connected') {
                statusDot.classList.add('router-status-dot-connected');
                statusLabel.textContent = identity || 'Router Online';
            } else if (status === 'disconnected') {
                statusDot.classList.add('router-status-dot-disconnected');
                statusLabel.textContent = 'Router Offline';
            } else {
                statusDot.classList.add('router-status-dot-disconnected');
                statusLabel.textContent = 'Connection Error';
            }
        }

        if (window.Echo) {
            Echo.channel('router-updates')
                .listen('RouterDataUpdated', (e) => {
                    console.log('[RouterStatus] Event received', e.data?.status);
                    updateStatus(e.data?.status);
                });
        }
    }

    return { init };
})();

export default RouterStatus;
