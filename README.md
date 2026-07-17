<p align="center">
    <img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
    <img src="https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS">
    <img src="https://img.shields.io/badge/Laravel_Reverb-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel Reverb">
    <img src="https://img.shields.io/badge/Chart.js-FF6384?style=for-the-badge&logo=chartdotjs&logoColor=white" alt="Chart.js">
</p>

<h1 align="center">MikroPulse</h1>

<p align="center">
    <b>Realtime MikroTik Router Monitoring Dashboard</b>
</p>

<p align="center">
    A modern, real-time monitoring dashboard for MikroTik routers built with Laravel, Tailwind CSS, and Laravel Reverb for live WebSocket updates.
</p>

## Features

- **Realtime Traffic Monitor** - Live RX/TX bandwidth visualization with Chart.js
- **System Utilization** - CPU, RAM, and Storage gauges with canvas rendering
- **Network Information** - LAN, Gateway, DNS, and DHCP lease tracking
- **Connected Clients** - Active client monitoring with bandwidth usage (DL/UL)
- **Queue Monitoring** - Simple Queue tree status and traffic rates
- **Top Connections** - Top source and destination IP tracking
- **Dark Mode** - Full dark/light theme support with smooth transitions
- **Reactive Updates** - Real-time data via Laravel Reverb WebSockets

## Tech Stack

- **Backend**: Laravel 13
- **Frontend**: Tailwind CSS 4, Vanilla JavaScript (ES Modules)
- **Real-time**: Laravel Reverb (WebSockets)
- **Charts**: Chart.js
- **Icons**: Font Awesome

## Prerequisites

- PHP 8.2+
- Composer
- Node.js & npm/pnpm
- MikroTik RouterOS with RouterOS API access
- Redis (for queue/reverb)

## Installation

```bash
# Clone repository
git clone https://github.com/yourusername/mikropulse.git
cd mikropulse

# Install PHP dependencies
composer install

# Install JS dependencies
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Configure Reverb in .env
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8088

# Run migrations
php artisan migrate

# Build assets
npm run build

# Start Reverb server
php artisan reverb:start

# Serve application
php artisan serve
```

## Router Configuration

The dashboard connects to MikroTik routers via the RouterOS API. Configure your router credentials in the `.env` file:

```env
ROUTER_HOST=192.168.88.1
ROUTER_USER=admin
ROUTER_PASSWORD=your-password
ROUTER_PORT=8728
```

## Project Structure

```
resources/
├── css/
│   ├── app.css                    # Main CSS entry
│   └── components/                # Component styles
│       ├── utilities.css          # Shared utility classes
│       ├── header.css
│       ├── traffic-monitor.css
│       ├── system-utilization.css
│       ├── network-info.css
│       ├── stats.css
│       ├── connected-clients.css
│       ├── queue-monitor.css
│       └── footer.css
├── js/
│   ├── app.js                     # Main JS entry
│   ├── utils.js                   # Shared utilities
│   └── components/                # Component scripts
│       ├── router-status.js
│       ├── traffic-monitor.js
│       ├── system-utilization.js
│       ├── network-info.js
│       ├── top-connections.js
│       ├── connected-clients.js
│       └── queue-monitor.js
└── views/
    ├── layout.blade.php
    ├── layouts/
    │   ├── header.blade.php
    │   └── footer.blade.php
    ├── components/
    │   ├── traffic-monitor.blade.php
    │   ├── system-utilization.blade.php
    │   ├── network-info.blade.php
    │   ├── top-connections.blade.php
    │   ├── connected-clients.blade.php
    │   └── queue-monitor.blade.php
    └── page/
        └── dashboard.blade.php
```

## License

The MikroPulse is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
