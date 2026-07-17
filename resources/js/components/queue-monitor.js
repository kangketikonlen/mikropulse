import { renderEmpty, subscribeToRouterUpdates } from '../utils.js';

const QueueMonitor = (() => {
    function formatRate(bitsPerSecond) {
        if (bitsPerSecond <= 0) return '0 Mbps';

        const mbps = bitsPerSecond / 1000000;
        if (mbps >= 1) {
            return mbps.toFixed(1) + ' Mbps';
        }

        const kbps = bitsPerSecond / 1000;
        return kbps.toFixed(0) + ' Kbps';
    }

    function renderQueueItem(queue, depth = 0) {
        const rowClass = depth % 2 === 0 ? 'queue-item-odd' : 'queue-item-even';
        const indent = depth > 0 ? 'padding-left: ' + (depth * 16) + 'px;' : '';
        const children = queue.children || [];
        const childHtml = children.map(child => renderQueueItem(child, depth + 1)).join('');

        return `
            <div class="queue-item ${rowClass}" style="${indent}">
                <div class="queue-row">
                    <span class="queue-name">${queue.name}</span>
                </div>
                <div class="queue-row">
                    <span class="queue-stat">DL: ${formatRate(queue.download)}</span>
                    <span class="queue-stat">UL: ${formatRate(queue.upload)}</span>
                </div>
            </div>
            ${childHtml}
        `;
    }

    function init() {
        const listContainer = document.getElementById('queue-list');
        if (!listContainer) return;

        function renderList(queues) {
            if (!queues || queues.length === 0) {
                renderEmpty(listContainer, 'No queues configured');
                return;
            }

            listContainer.innerHTML = queues.map((queue, index) => renderQueueItem(queue, 0)).join('');
        }

        subscribeToRouterUpdates((e) => {
            const data = e.data?.queueMonitoring ?? e.queueMonitoring;
            if (data && data.status === 'connected') {
                renderList(data.queues || []);
            } else {
                renderList([]);
            }
        });
    }

    return { init };
})();

export default QueueMonitor;
