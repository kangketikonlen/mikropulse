<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', strtoupper(config('app.name', 'MikroPulse')))</title>

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
    <body class="bg-gray-50 text-gray-800 font-sans antialiased dark:bg-dark-bg dark:text-gray-100">
        <div class="min-h-screen flex flex-col">
            @include('layouts.header')

            <main class="flex-1">
                @yield('content')
            </main>

            @include('layouts.footer')
        </div>

        <script>
            (function() {
                const html = document.documentElement;
                const stored = localStorage.getItem('theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (stored === 'dark' || (!stored && prefersDark)) {
                    html.classList.add('dark');
                }
            })();
        </script>
    </body>
</html>
