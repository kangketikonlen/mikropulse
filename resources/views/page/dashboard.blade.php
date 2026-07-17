@extends('layout')

@section('title', strtoupper(config('app.name', 'MikroPulse')) . ' - DASHBOARD')

@section('content')
    <!-- Stats Section -->
    <section class="py-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @include('components.traffic-monitor')

            <div class="stats-3col mt-4">
                @include('components.top-connections')
                @include('components.system-utilization')
                @include('components.network-info')
            </div>

            <div class="stats-3col mt-4">
                @include('components.connected-clients')
                @include('components.queue-monitor')
            </div>
        </div>
    </section>
@endsection
