const ConnectedClients = (() => {
    function init() {
        const listContainer = document.getElementById('clients-list');
        if (!listContainer) return;

        function renderList(clients) {
            if (!clients || clients.length === 0) {
                listContainer.innerHTML = '<div class="client-item text-gray-400">No connected clients</div>';
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
                    </div>
                `;
            }).join('');
        }

        if (window.Echo) {
            Echo.channel('router-updates')
                .listen('RouterDataUpdated', (e) => {
                    const data = e.data?.connectedClients ?? e.connectedClients;
                    if (data && data.status === 'connected') {
                        renderList(data.clients || []);
                    } else {
                        renderList([]);
                    }
                });
        }
    }

    return { init };
})();

export default ConnectedClients;
