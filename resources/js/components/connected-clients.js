import { renderEmpty, subscribeToRouterUpdates } from '../utils.js';

const ConnectedClients = (() => {
    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function init() {
        const listContainer = document.getElementById('clients-list');
        if (!listContainer) return;

        function renderList(clients) {
            if (!clients || clients.length === 0) {
                renderEmpty(listContainer, 'No connected clients');
                return;
            }

            listContainer.innerHTML = clients.map((client, index) => {
                const rowClass = index % 2 === 0 ? 'client-item-odd' : 'client-item-even';
                return `
                    <div class="client-item ${rowClass}">
                        <div class="client-row">
                            <span class="client-ip">${client.ip}</span>
                            <span class="client-hostname">${client.hostname}</span>
                        </div>
                        <div class="client-row">
                            <span class="client-stat">${client.connections} conn</span>
                        </div>
                        <div class="client-row">
                            <span class="client-stat">DL: ${formatBytes(client.download_bytes || 0)}</span>
                            <span class="client-stat">UL: ${formatBytes(client.upload_bytes || 0)}</span>
                        </div>
                    </div>
                `;
            }).join('');
        }

        subscribeToRouterUpdates((e) => {
            const data = e.data?.connectedClients ?? e.connectedClients;
            if (data && data.status === 'connected') {
                renderList(data.clients || []);
            } else {
                renderList([]);
            }
        });
    }

    return { init };
})();

export default ConnectedClients;
