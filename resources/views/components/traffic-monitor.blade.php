<div class="stats-1col" id="traffic-monitor">
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
</div>
