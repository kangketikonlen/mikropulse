<div class="system-utilization" id="system-utilization">
    <div class="system-utilization-header">
        <div class="flex items-center gap-1.5">
            <i class="fa-solid fa-microchip text-gray-400 dark:text-gray-500"></i>
            <span class="system-utilization-title">System Utilization</span>
        </div>
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
