<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'MikroPulse') }} - Dashboard</title>

        <script>
            window.ReverbConfig = {
                key: '{{ env('REVERB_APP_KEY') }}',
                wsHost: '{{ env('REVERB_SERVER_HOST') }}',
                wsPort: {{ env('REVERB_SERVER_PORT', 8088) }},
                wssPort: {{ env('REVERB_SERVER_PORT', 8088) }},
                forceTLS: {{ env('REVERB_SERVER_SCHEME') === 'https' ? 'true' : 'false' }},
            };
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-50 text-gray-800 font-sans antialiased">
        <div class="min-h-screen flex flex-col">
            <!-- Header -->
            <header class="header">
                <div class="header-container">
                    <div class="header-inner">
                        <div class="header-brand">
                            <div class="header-logo">
                                <i class="fa-solid fa-bolt text-white"></i>
                            </div>
                            <span class="header-title">{{ config('app.name', 'MikroPulse') }}</span>
                        </div>
                        <nav class="header-nav">
                            @if (Route::has('login'))
                                @auth
                                    <a href="{{ url('/dashboard') }}" class="header-link">
                                        Dashboard
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="header-link">
                                        Log in
                                    </a>
                                    @if (Route::has('register'))
                                        <a href="{{ route('register') }}" class="header-btn-primary">
                                            Get Started
                                        </a>
                                    @endif
                                @endauth
                            @endif
                        </nav>
                        <div class="router-status" id="router-status">
                            <span class="router-status-dot router-status-dot-checking" id="router-status-dot"></span>
                            <span class="router-status-label" id="router-status-label">Checking...</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1">
                <!-- Traffic Monitor Section -->
                <section class="traffic-monitor" id="traffic-monitor">
                    <div class="traffic-monitor-container">
                        <div class="traffic-monitor-header">
                            <div>
                                <h1 class="traffic-monitor-title">Realtime Traffic Monitor</h1>
                                <p class="traffic-monitor-interface">Interface: <span class="font-medium">ether1</span></p>
                            </div>
                            <div class="traffic-monitor-badge traffic-monitor-badge-checking" id="traffic-status-badge">
                                Checking...
                            </div>
                        </div>
                        <div class="traffic-monitor-grid">
                            <div class="traffic-monitor-card">
                                <div class="traffic-monitor-card-header">
                                    <span class="traffic-monitor-card-title">Download (RX)</span>
                                </div>
                                <div class="flex items-baseline gap-2 mb-4">
                                    <span class="traffic-monitor-card-value" id="rx-value">--</span>
                                    <span class="traffic-monitor-card-unit">Mbps</span>
                                </div>
                                <div class="traffic-monitor-canvas-wrap">
                                    <canvas class="traffic-monitor-canvas" id="rx-chart"></canvas>
                                    <div class="traffic-monitor-skeleton" id="rx-skeleton">
                                        <div class="skeleton-bar"></div>
                                        <div class="skeleton-bar"></div>
                                        <div class="skeleton-bar"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="traffic-monitor-card">
                                <div class="traffic-monitor-card-header">
                                    <span class="traffic-monitor-card-title">Upload (TX)</span>
                                </div>
                                <div class="flex items-baseline gap-2 mb-4">
                                    <span class="traffic-monitor-card-value" id="tx-value">--</span>
                                    <span class="traffic-monitor-card-unit">Mbps</span>
                                </div>
                                <div class="traffic-monitor-canvas-wrap">
                                    <canvas class="traffic-monitor-canvas" id="tx-chart"></canvas>
                                    <div class="traffic-monitor-skeleton" id="tx-skeleton">
                                        <div class="skeleton-bar"></div>
                                        <div class="skeleton-bar"></div>
                                        <div class="skeleton-bar"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="traffic-monitor-legend">
                            <div class="traffic-monitor-legend-item">
                                <span class="traffic-monitor-legend-dot traffic-monitor-legend-dot-rx"></span>
                                <span class="traffic-monitor-legend-label">Download (RX)</span>
                            </div>
                            <div class="traffic-monitor-legend-item">
                                <span class="traffic-monitor-legend-dot traffic-monitor-legend-dot-tx"></span>
                                <span class="traffic-monitor-legend-label">Upload (TX)</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Stats Section -->
                <section class="py-4">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="stats-3col">
                            <div class="top-list" id="top-connections">
                                <div class="top-list-header">
                                    <i class="fa-solid fa-arrow-right-arrow-left text-indigo-600"></i>
                                    <span class="top-list-title">Top Connections</span>
                                </div>
                                <div class="top-list-section">
                                    <div class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Top Sources</div>
                                    <div class="top-list-items" id="sources-list">
                                        <div class="top-list-item">
                                            <span class="top-list-ip text-gray-400">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="top-list-section mt-3">
                                    <div class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Top Destinations</div>
                                    <div class="top-list-items" id="destinations-list">
                                        <div class="top-list-item">
                                            <span class="top-list-ip text-gray-400">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="system-utilization" id="system-utilization">
                                <div class="system-utilization-header">
                                    <span class="system-utilization-title">System Utilization</span>
                                    <span class="system-utilization-uptime" id="system-uptime">Loading...</span>
                                </div>
                                <div class="system-utilization-gauges">
                                    <div class="gauge-item">
                                        <canvas class="gauge-canvas" id="cpu-gauge"></canvas>
                                        <span class="gauge-label">CPU</span>
                                    </div>
                                    <div class="gauge-item">
                                        <canvas class="gauge-canvas" id="ram-gauge"></canvas>
                                        <span class="gauge-label">RAM</span>
                                    </div>
                                    <div class="gauge-item">
                                        <canvas class="gauge-canvas" id="storage-gauge"></canvas>
                                        <span class="gauge-label">Storage</span>
                                    </div>
                                </div>
                                <div class="system-utilization-footer">
                                    <span class="system-utilization-version" id="system-board-name">Loading...</span>
                                    <span class="system-utilization-version" id="system-version">Loading...</span>
                                </div>
                            </div>
                            <div class="network-info" id="network-info">
                                <div class="network-info-header">
                                    <i class="fa-solid fa-network-wired text-gray-400"></i>
                                    <span class="network-info-title">Network IP</span>
                                </div>
                                <div class="network-info-list" id="network-list">
                                    <div class="network-info-item">
                                        <div class="network-info-row">
                                            <span class="network-info-label">LAN</span>
                                            <span class="network-info-value text-gray-400">Loading...</span>
                                        </div>
                                        <div class="network-info-row">
                                            <span class="network-info-label">GW</span>
                                            <span class="network-info-value text-gray-400">--</span>
                                            <span class="network-info-label ml-2">DNS</span>
                                            <span class="network-info-value text-gray-400">--</span>
                                            <span class="network-info-badge">LS --</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="stats-3col mt-4">
                            <div class="connected-clients lg:col-span-2" id="connected-clients">
                                <div class="connected-clients-header">
                                    <i class="fa-solid fa-users text-gray-400"></i>
                                    <span class="connected-clients-title">Connected Clients</span>
                                </div>
                                <div class="connected-clients-list" id="clients-list">
                                    <div class="client-item">
                                        <div class="client-row">
                                            <span class="client-ip text-gray-400">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="queue-monitor" id="queue-monitor">
                                <div class="queue-monitor-header">
                                    <i class="fa-solid fa-list-ol text-gray-400"></i>
                                    <span class="queue-monitor-title">Simple Queue</span>
                                </div>
                                <div class="queue-monitor-list" id="queue-list">
                                    <div class="queue-item">
                                        <div class="queue-row">
                                            <span class="queue-name text-gray-400">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Features Section -->
                <section class="features">
                    <div class="features-container">
                        <div class="features-header">
                            <h2 class="features-title">Features</h2>
                            <p class="features-subtitle">Everything you need to manage your MikroTik infrastructure</p>
                        </div>
                        <div class="features-grid">
                            <div class="feature-card">
                                <div class="feature-icon feature-icon-indigo">
                                    <i class="fa-solid fa-chart-line"></i>
                                </div>
                                <h3 class="feature-title">Real-time Monitoring</h3>
                                <p class="feature-text">Track device performance, bandwidth usage, and network health in real-time.</p>
                            </div>
                            <div class="feature-card">
                                <div class="feature-icon feature-icon-green">
                                    <i class="fa-solid fa-rotate"></i>
                                </div>
                                <h3 class="feature-title">Automated Configuration</h3>
                                <p class="feature-text">Deploy and manage configurations across multiple devices automatically.</p>
                            </div>
                            <div class="feature-card">
                                <div class="feature-icon feature-icon-amber">
                                    <i class="fa-solid fa-lock"></i>
                                </div>
                                <h3 class="feature-title">Secure Access</h3>
                                <p class="feature-text">Role-based access control with full audit logging and security compliance.</p>
                            </div>
                        </div>
                    </div>
                </section>
            </main>

            <!-- Footer -->
            <footer class="footer">
                <div class="footer-container">
                    <div class="footer-inner">
                        <div class="footer-brand">
                            <div class="footer-logo">
                                <i class="fa-solid fa-bolt text-white"></i>
                            </div>
                            <span class="footer-title">MikroPulse</span>
                        </div>
                        <p class="footer-text">
                            {{ config('app.name', 'MikroPulse') }} v{{ app()->version() }}
                        </p>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
