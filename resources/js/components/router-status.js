import { isDark } from '../utils.js';

import { subscribeToRouterUpdates } from '../utils.js';

const RouterStatus = (() => {
    function init() {
        const statusIcon = document.getElementById('router-status-icon');
        const statusLabel = document.getElementById('router-status-label');

        if (!statusIcon || !statusLabel) return;

        function updateStatus(data) {
            statusIcon.classList.remove('text-green-500', 'text-red-500', 'text-yellow-500');

            const status = data?.status ?? data?.data?.status;
            const identity = data?.identity ?? data?.data?.identity;

            if (status === 'connected') {
                statusIcon.classList.add('text-green-500');
                statusLabel.textContent = "You're in " + (identity || 'Router Online');
            } else if (status === 'disconnected') {
                statusIcon.classList.add('text-red-500');
                statusLabel.textContent = 'Router Offline';
            } else {
                statusIcon.classList.add('text-red-500');
                statusLabel.textContent = 'Connection Error';
            }
        }

        subscribeToRouterUpdates((e) => updateStatus(e.data?.status));
    }

    return { init };
})();

export default RouterStatus;
