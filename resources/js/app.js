import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

import RouterStatus from './components/router-status.js';
import TrafficMonitor from './components/traffic-monitor.js';
import TopConnections from './components/top-connections.js';
import SystemUtilization from './components/system-utilization.js';
import NetworkInfo from './components/network-info.js';
import ConnectedClients from './components/connected-clients.js';
import QueueMonitor from './components/queue-monitor.js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: window.ReverbConfig?.key || 'local',
    wsHost: window.ReverbConfig?.wsHost || '127.0.0.1',
    wsPort: window.ReverbConfig?.wsPort || 8088,
    wssPort: window.ReverbConfig?.wssPort || 8088,
    forceTLS: window.ReverbConfig?.forceTLS ?? false,
    enabledTransports: ['ws', 'wss'],
});

RouterStatus.init();
TrafficMonitor.init();
TopConnections.init();
SystemUtilization.init();
NetworkInfo.init();
ConnectedClients.init();
QueueMonitor.init();

const themeToggle = document.getElementById('theme-toggle');
if (themeToggle) {
    themeToggle.addEventListener('click', () => {
        const html = document.documentElement;
        const isDark = html.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    });
}
