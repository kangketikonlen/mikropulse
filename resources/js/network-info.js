const NetworkInfo = (() => {
    function init() {
        const listContainer = document.getElementById('network-list');

        if (!listContainer) return;

        function renderList(networks) {
            if (!networks || networks.length === 0) {
                listContainer.innerHTML = '<div class="network-info-item text-gray-400">No data</div>';
                return;
            }

            listContainer.innerHTML = networks.map((item, index) => {
                const rowClass = index % 2 === 0 ? 'network-info-item-odd' : 'network-info-item-even';
                return `
                    <div class="network-info-item ${rowClass}">
                        <div class="network-info-row">
                            <span class="network-info-label">LAN</span>
                            <span class="network-info-value">${item.address}</span>
                        </div>
                        <div class="network-info-row">
                            <span class="network-info-label">GW</span>
                            <span class="network-info-value">${item.gateway}</span>
                            <span class="network-info-label ml-2">DNS</span>
                            <span class="network-info-value">${item.dns}</span>
                            <span class="network-info-badge ml-auto">LS ${item.leases}</span>
                        </div>
                    </div>
                `;
            }).join('');
        }

        if (window.Echo) {
            Echo.channel('router-updates')
                .listen('RouterDataUpdated', (e) => {
                    const networks = e.data?.networkInfo?.networks ?? e.networkInfo?.networks ?? [];
                    renderList(networks);
                });
        }
    }

    return { init };
})();

export default NetworkInfo;
