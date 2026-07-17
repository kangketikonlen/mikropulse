<header class="header">
    <div class="header-container">
        <div class="header-inner">
            <div class="router-status" id="router-status">
                <i class="fa-solid fa-location-dot router-status-icon" id="router-status-icon"></i>
                <span class="router-status-label" id="router-status-label">Checking...</span>
            </div>

            <div class="header-brand">
                <div class="header-logo">
                    <i class="fa-solid fa-bolt text-white"></i>
                </div>
                <span class="header-title">{{ strtoupper(config('app.name', 'MikroPulse')) }}</span>
            </div>

            <button id="theme-toggle" class="theme-toggle" type="button">
                <span class="theme-toggle-track">
                    <i class="fa-solid fa-moon theme-toggle-icon-dark"></i>
                    <i class="fa-solid fa-sun theme-toggle-icon-light"></i>
                    <span class="theme-toggle-thumb"></span>
                </span>
            </button>
        </div>
    </div>
</header>
