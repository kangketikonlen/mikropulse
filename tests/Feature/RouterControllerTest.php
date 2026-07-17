<?php

use App\Services\RouterService;

beforeEach(function () {
    RouterService::disconnect();
});

test('dashboard page loads successfully', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('MIKROPULSE');
});

test('router status endpoint returns connected data', function () {
    $mockData = [
        'status' => 'connected',
        'identity' => 'TestRouter',
        'message' => 'Router is online',
    ];

    $this->mock(RouterService::class, function ($mock) use ($mockData) {
        $mock->shouldReceive('getStatus')->andReturn($mockData);
    });

    $response = $this->get('/router/status');

    $response->assertStatus(200)
        ->assertJson($mockData);
});

test('router status endpoint returns disconnected data', function () {
    $mockData = [
        'status' => 'disconnected',
        'message' => 'Router is not connected',
    ];

    $this->mock(RouterService::class, function ($mock) use ($mockData) {
        $mock->shouldReceive('getStatus')->andReturn($mockData);
    });

    $response = $this->get('/router/status');

    $response->assertStatus(200)
        ->assertJson($mockData);
});

test('router status endpoint returns error data', function () {
    $mockData = [
        'status' => 'error',
        'message' => 'Connection timeout',
    ];

    $this->mock(RouterService::class, function ($mock) use ($mockData) {
        $mock->shouldReceive('getStatus')->andReturn($mockData);
    });

    $response = $this->get('/router/status');

    $response->assertStatus(200)
        ->assertJson($mockData);
});

test('traffic endpoint returns connected data', function () {
    $mockData = [
        'status' => 'connected',
        'rx' => 1500000,
        'tx' => 500000,
    ];

    $this->mock(RouterService::class, function ($mock) use ($mockData) {
        $mock->shouldReceive('getTraffic')->andReturn($mockData);
    });

    $response = $this->get('/router/traffic');

    $response->assertStatus(200)
        ->assertJson($mockData);
});

test('traffic endpoint returns disconnected data', function () {
    $mockData = [
        'status' => 'disconnected',
        'message' => 'Router is not connected',
    ];

    $this->mock(RouterService::class, function ($mock) use ($mockData) {
        $mock->shouldReceive('getTraffic')->andReturn($mockData);
    });

    $response = $this->get('/router/traffic');

    $response->assertStatus(200)
        ->assertJson($mockData);
});

test('traffic endpoint returns error when no traffic data', function () {
    $mockData = [
        'status' => 'error',
        'message' => 'No traffic data available for ether1',
    ];

    $this->mock(RouterService::class, function ($mock) use ($mockData) {
        $mock->shouldReceive('getTraffic')->andReturn($mockData);
    });

    $response = $this->get('/router/traffic');

    $response->assertStatus(200)
        ->assertJson($mockData);
});

test('top-connections endpoint returns data', function () {
    $mockData = [
        'status' => 'connected',
        'sources' => ['192.168.1.100', '192.168.1.101', '192.168.1.102'],
        'source_counts' => [
            '192.168.1.100' => 15,
            '192.168.1.101' => 10,
            '192.168.1.102' => 5,
        ],
        'destinations' => ['8.8.8.8', '1.1.1.1', 'google.com'],
        'destination_counts' => [
            '8.8.8.8' => 20,
            '1.1.1.1' => 12,
            'google.com' => 8,
        ],
    ];

    $this->mock(RouterService::class, function ($mock) use ($mockData) {
        $mock->shouldReceive('getTopConnections')->andReturn($mockData);
    });

    $response = $this->get('/router/top-connections');

    $response->assertStatus(200)
        ->assertJson($mockData);
});

test('top-connections endpoint returns empty data', function () {
    $mockData = [
        'status' => 'connected',
        'sources' => [],
        'destinations' => [],
    ];

    $this->mock(RouterService::class, function ($mock) use ($mockData) {
        $mock->shouldReceive('getTopConnections')->andReturn($mockData);
    });

    $response = $this->get('/router/top-connections');

    $response->assertStatus(200)
        ->assertJson($mockData);
});

test('system utilization endpoint returns data', function () {
    $mockData = [
        'status' => 'connected',
        'cpu' => 45,
        'memory' => 62,
        'storage' => 78,
        'uptime' => '2W 3D 5H 30M',
        'board_name' => 'RB750Gr3',
        'version' => '7.15.2',
    ];

    $this->mock(RouterService::class, function ($mock) use ($mockData) {
        $mock->shouldReceive('getSystemUtilization')->andReturn($mockData);
    });

    $response = $this->get('/router/system');

    $response->assertStatus(200)
        ->assertJson($mockData);
});

test('system utilization endpoint returns error data', function () {
    $mockData = [
        'status' => 'error',
        'message' => 'No system resource data available',
    ];

    $this->mock(RouterService::class, function ($mock) use ($mockData) {
        $mock->shouldReceive('getSystemUtilization')->andReturn($mockData);
    });

    $response = $this->get('/router/system');

    $response->assertStatus(200)
        ->assertJson($mockData);
});

test('network info endpoint returns data', function () {
    $mockData = [
        'status' => 'connected',
        'networks' => [
            [
                'address' => '192.168.88.0/24',
                'gateway' => '192.168.88.1',
                'dns' => '192.168.88.1',
                'leases' => 12,
            ],
            [
                'address' => '10.0.0.0/24',
                'gateway' => '10.0.0.1',
                'dns' => '10.0.0.1',
                'leases' => 5,
            ],
        ],
    ];

    $this->mock(RouterService::class, function ($mock) use ($mockData) {
        $mock->shouldReceive('getNetworkInfo')->andReturn($mockData);
    });

    $response = $this->get('/router/network');

    $response->assertStatus(200)
        ->assertJson($mockData);
});

test('network info endpoint returns empty networks', function () {
    $mockData = [
        'status' => 'connected',
        'networks' => [],
    ];

    $this->mock(RouterService::class, function ($mock) use ($mockData) {
        $mock->shouldReceive('getNetworkInfo')->andReturn($mockData);
    });

    $response = $this->get('/router/network');

    $response->assertStatus(200)
        ->assertJson($mockData);
});

test('connected clients endpoint returns data', function () {
    $mockData = [
        'status' => 'connected',
        'clients' => [
            [
                'ip' => '192.168.88.10',
                'hostname' => 'client-pc',
                'connections' => 25,
                'upload_bytes' => 1024000,
                'download_bytes' => 5120000,
            ],
            [
                'ip' => '192.168.88.20',
                'hostname' => 'phone',
                'connections' => 10,
                'upload_bytes' => 512000,
                'download_bytes' => 2048000,
            ],
        ],
    ];

    $this->mock(RouterService::class, function ($mock) use ($mockData) {
        $mock->shouldReceive('getConnectedClients')->andReturn($mockData);
    });

    $response = $this->get('/router/clients');

    $response->assertStatus(200)
        ->assertJson($mockData);
});

test('connected clients endpoint returns empty clients', function () {
    $mockData = [
        'status' => 'connected',
        'clients' => [],
    ];

    $this->mock(RouterService::class, function ($mock) use ($mockData) {
        $mock->shouldReceive('getConnectedClients')->andReturn($mockData);
    });

    $response = $this->get('/router/clients');

    $response->assertStatus(200)
        ->assertJson($mockData);
});

test('queue monitoring endpoint returns data', function () {
    $mockData = [
        'status' => 'connected',
        'queues' => [
            [
                'name' => 'default',
                'parent' => '',
                'upload' => 1000000,
                'download' => 5000000,
                'children' => [],
            ],
            [
                'name' => 'download-limit',
                'parent' => 'default',
                'upload' => 0,
                'download' => 2000000,
                'children' => [],
            ],
        ],
    ];

    $this->mock(RouterService::class, function ($mock) use ($mockData) {
        $mock->shouldReceive('getQueueMonitoring')->andReturn($mockData);
    });

    $response = $this->get('/router/queue');

    $response->assertStatus(200)
        ->assertJson($mockData);
});

test('queue monitoring endpoint returns empty queues', function () {
    $mockData = [
        'status' => 'connected',
        'queues' => [],
    ];

    $this->mock(RouterService::class, function ($mock) use ($mockData) {
        $mock->shouldReceive('getQueueMonitoring')->andReturn($mockData);
    });

    $response = $this->get('/router/queue');

    $response->assertStatus(200)
        ->assertJson($mockData);
});

test('broadcast endpoint returns success message', function () {
    $mockData = [
        'status' => ['status' => 'connected', 'identity' => 'TestRouter', 'message' => 'Router is online'],
        'traffic' => ['status' => 'connected', 'rx' => 1000000, 'tx' => 500000],
        'topConnections' => ['status' => 'connected', 'sources' => [], 'destinations' => []],
        'systemUtilization' => ['status' => 'connected', 'cpu' => 10, 'memory' => 20, 'storage' => 30, 'uptime' => '1M', 'board_name' => 'Test', 'version' => '7.0'],
        'networkInfo' => ['status' => 'connected', 'networks' => []],
        'connectedClients' => ['status' => 'connected', 'clients' => []],
        'queueMonitoring' => ['status' => 'connected', 'queues' => []],
    ];

    $this->mock(RouterService::class, function ($mock) use ($mockData) {
        $mock->shouldReceive('getData')->andReturn($mockData);
    });

    $response = $this->get('/router/broadcast');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Broadcast sent']);
});

test('broadcast endpoint handles errors gracefully', function () {
    $this->mock(RouterService::class, function ($mock) {
        $mock->shouldReceive('getData')->andThrow(new \Exception('Connection failed'));
    });

    $response = $this->get('/router/broadcast');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'error',
            'message' => 'Connection failed',
        ]);
});

test('getData endpoint returns complete data structure', function () {
    $mockData = [
        'status' => ['status' => 'connected', 'identity' => 'MikroTik', 'message' => 'Router is online'],
        'traffic' => ['status' => 'connected', 'rx' => 2500000, 'tx' => 1000000],
        'topConnections' => ['status' => 'connected', 'sources' => ['192.168.1.1', '192.168.1.2'], 'source_counts' => ['192.168.1.1' => 5, '192.168.1.2' => 3], 'destinations' => ['8.8.8.8'], 'destination_counts' => ['8.8.8.8' => 8]],
        'systemUtilization' => ['status' => 'connected', 'cpu' => 25, 'memory' => 45, 'storage' => 60, 'uptime' => '1W 2D 3H', 'board_name' => 'hEX S', 'version' => '7.16'],
        'networkInfo' => ['status' => 'connected', 'networks' => [['address' => '192.168.88.0/24', 'gateway' => '192.168.88.1', 'dns' => '192.168.88.1', 'leases' => 8]]],
        'connectedClients' => ['status' => 'connected', 'clients' => [['ip' => '192.168.88.50', 'hostname' => 'laptop', 'connections' => 12, 'upload_bytes' => 500000, 'download_bytes' => 2000000]]],
        'queueMonitoring' => ['status' => 'connected', 'queues' => [['name' => 'guest', 'parent' => '', 'upload' => 500000, 'download' => 2000000, 'children' => []]]],
    ];

    $this->mock(RouterService::class, function ($mock) use ($mockData) {
        $mock->shouldReceive('getData')->andReturn($mockData);
    });

    $response = $this->get('/router/broadcast');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Broadcast sent']);
});

test('getData endpoint returns disconnected response when router is offline', function () {
    $mockData = [
        'status' => ['status' => 'disconnected', 'message' => 'Router is not connected'],
        'traffic' => ['status' => 'disconnected', 'message' => 'Router is not connected'],
        'topConnections' => ['status' => 'disconnected', 'message' => 'Router is not connected'],
        'systemUtilization' => ['status' => 'disconnected', 'message' => 'Router is not connected'],
        'networkInfo' => ['status' => 'disconnected', 'message' => 'Router is not connected'],
        'connectedClients' => ['status' => 'disconnected', 'message' => 'Router is not connected'],
        'queueMonitoring' => ['status' => 'disconnected', 'message' => 'Router is not connected'],
    ];

    $this->mock(RouterService::class, function ($mock) use ($mockData) {
        $mock->shouldReceive('getData')->andReturn($mockData);
    });

    $response = $this->get('/router/broadcast');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Broadcast sent']);
});
