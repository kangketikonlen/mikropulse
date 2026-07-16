import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

import RouterStatus from './router-status.js';
import TrafficMonitor from './traffic-monitor.js';
import TopConnections from './top-connections.js';
import SystemUtilization from './system-utilization.js';
import NetworkInfo from './network-info.js';
import ConnectedClients from './connected-clients.js';

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
