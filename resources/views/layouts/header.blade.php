<header class="header">
    <div class="header-container">
        <div class="header-inner">
            <div class="header-brand">
                <div class="header-logo">
                    <i class="fa-solid fa-bolt text-white"></i>
                </div>
                <span class="header-title">{{ strtoupper(config('app.name', 'MikroPulse')) }}</span>
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
