const TopConnections = (() => {
    function init() {
        const sourcesList = document.getElementById('sources-list');
        const destinationsList = document.getElementById('destinations-list');

        if (!sourcesList || !destinationsList) return;

        function renderList(container, items, counts, emptyText) {
            if (!container) return;

            if (!items || items.length === 0) {
                container.innerHTML = `<div class="top-list-item"><span class="top-list-ip text-gray-400">${emptyText}</span></div>`;
                return;
            }

            container.innerHTML = items.map((ip, index) => `
                <div class="top-list-item">
                    <span class="top-list-ip">${index + 1}. ${ip}</span>
                    <span class="top-list-count">${counts[ip] ?? 0}</span>
                </div>
            `).join('');
        }

        if (window.Echo) {
            Echo.channel('router-updates')
                .listen('RouterDataUpdated', (e) => {
                    const topData = e.data?.topConnections ?? e.topConnections;

                    if (topData && topData.status === 'connected') {
                        renderList(sourcesList, topData.sources || [], topData.source_counts || {}, 'No data');
                        renderList(destinationsList, topData.destinations || [], topData.destination_counts || {}, 'No data');
                    } else {
                        renderList(sourcesList, [], {}, 'Disconnected');
                        renderList(destinationsList, [], {}, 'Disconnected');
                    }
                });
        }
    }

    return { init };
})();

export default TopConnections;
