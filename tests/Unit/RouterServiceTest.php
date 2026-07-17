<?php

use App\Services\RouterService;

beforeEach(function () {
    RouterService::disconnect();
});

test('formatUptime formats weeks and days correctly', function () {
    $service = new RouterService();
    $method = new ReflectionMethod($service, 'formatUptime');
    $result = $method->invoke($service, '2w3d5h30m');
    expect($result)->toBe('2W 3D 5H 30M');
});

test('formatUptime formats hours and minutes correctly', function () {
    $service = new RouterService();
    $method = new ReflectionMethod($service, 'formatUptime');
    $result = $method->invoke($service, '5h30m');
    expect($result)->toBe('5H 30M');
});

test('formatUptime returns 0M for empty string', function () {
    $service = new RouterService();
    $method = new ReflectionMethod($service, 'formatUptime');
    $result = $method->invoke($service, '');
    expect($result)->toBe('0M');
});

test('formatUptime handles seconds only', function () {
    $service = new RouterService();
    $method = new ReflectionMethod($service, 'formatUptime');
    $result = $method->invoke($service, '45s');
    expect($result)->toBe('0M');
});

test('formatUptime handles complex uptime string', function () {
    $service = new RouterService();
    $method = new ReflectionMethod($service, 'formatUptime');
    $result = $method->invoke($service, '1w2d3h15m');
    expect($result)->toBe('1W 2D 3H 15M');
});

test('parseRate returns 0 for empty string', function () {
    $service = new RouterService();
    $method = new ReflectionMethod($service, 'parseRate');
    $result = $method->invoke($service, '');
    expect($result)->toBe(0);
});

test('parseRate returns 0 for zero', function () {
    $service = new RouterService();
    $method = new ReflectionMethod($service, 'parseRate');
    $result = $method->invoke($service, '0');
    expect($result)->toBe(0);
});

test('parseRate parses kilobits correctly', function () {
    $service = new RouterService();
    $method = new ReflectionMethod($service, 'parseRate');
    $result = $method->invoke($service, '500k');
    expect($result)->toBe(500000);
});

test('parseRate parses megabits correctly', function () {
    $service = new RouterService();
    $method = new ReflectionMethod($service, 'parseRate');
    $result = $method->invoke($service, '10M');
    expect($result)->toBe(10000000);
});

test('parseRate parses gigabits correctly', function () {
    $service = new RouterService();
    $method = new ReflectionMethod($service, 'parseRate');
    $result = $method->invoke($service, '1G');
    expect($result)->toBe(1000000000);
});

test('parseRate parses plain number correctly', function () {
    $service = new RouterService();
    $method = new ReflectionMethod($service, 'parseRate');
    $result = $method->invoke($service, '1000');
    expect($result)->toBe(1000);
});

test('buildQueueTree builds flat tree from flat list', function () {
    $service = new RouterService();
    $method = new ReflectionMethod($service, 'buildQueueTree');
    $queues = [
        ['name' => 'default', 'parent' => '', 'upload' => 1000, 'download' => 5000],
        ['name' => 'child1', 'parent' => 'default', 'upload' => 500, 'download' => 2000],
        ['name' => 'child2', 'parent' => 'default', 'upload' => 300, 'download' => 1000],
    ];

    $result = $method->invoke($service, $queues);

    expect($result)->toHaveCount(1);
    expect($result[0]['name'])->toBe('default');
    expect($result[0]['children'])->toHaveCount(2);
    expect($result[0]['children'][0]['name'])->toBe('child1');
    expect($result[0]['children'][1]['name'])->toBe('child2');
});

test('buildQueueTree handles multiple root queues', function () {
    $service = new RouterService();
    $method = new ReflectionMethod($service, 'buildQueueTree');
    $queues = [
        ['name' => 'queue1', 'parent' => '', 'upload' => 1000, 'download' => 5000],
        ['name' => 'queue2', 'parent' => '', 'upload' => 500, 'download' => 2000],
        ['name' => 'queue3', 'parent' => 'queue1', 'upload' => 300, 'download' => 1000],
    ];

    $result = $method->invoke($service, $queues);

    expect($result)->toHaveCount(2);
    expect($result[0]['name'])->toBe('queue1');
    expect($result[0]['children'][0]['name'])->toBe('queue3');
    expect($result[1]['name'])->toBe('queue2');
});

test('buildQueueTree handles empty list', function () {
    $service = new RouterService();
    $method = new ReflectionMethod($service, 'buildQueueTree');
    $result = $method->invoke($service, []);
    expect($result)->toBeEmpty();
});

test('buildQueueTree handles queue with no children', function () {
    $service = new RouterService();
    $method = new ReflectionMethod($service, 'buildQueueTree');
    $queues = [
        ['name' => 'standalone', 'parent' => '', 'upload' => 1000, 'download' => 5000],
    ];

    $result = $method->invoke($service, $queues);

    expect($result)->toHaveCount(1);
    expect($result[0]['name'])->toBe('standalone');
});

test('buildQueueTree handles deeply nested queues', function () {
    $service = new RouterService();
    $method = new ReflectionMethod($service, 'buildQueueTree');
    $queues = [
        ['name' => 'root', 'parent' => '', 'upload' => 1000, 'download' => 5000],
        ['name' => 'level1', 'parent' => 'root', 'upload' => 500, 'download' => 2000],
        ['name' => 'level2', 'parent' => 'level1', 'upload' => 200, 'download' => 1000],
    ];

    $result = $method->invoke($service, $queues);

    expect($result)->toHaveCount(1);
    expect($result[0]['name'])->toBe('root');
    expect($result[0]['children'])->toHaveCount(1);
    expect($result[0]['children'][0]['name'])->toBe('level1');
    expect($result[0]['children'][0]['children'])->toHaveCount(1);
    expect($result[0]['children'][0]['children'][0]['name'])->toBe('level2');
});

test('buildQueueTree adds orphan queues as root nodes', function () {
    $service = new RouterService();
    $method = new ReflectionMethod($service, 'buildQueueTree');
    $queues = [
        ['name' => 'orphan', 'parent' => 'nonexistent', 'upload' => 1000, 'download' => 5000],
        ['name' => 'valid', 'parent' => '', 'upload' => 500, 'download' => 2000],
    ];

    $result = $method->invoke($service, $queues);

    expect($result)->toHaveCount(2);
    expect($result[0]['name'])->toBe('orphan');
    expect($result[1]['name'])->toBe('valid');
});

test('disconnectedResponse returns correct structure', function () {
    $service = new RouterService();
    $method = new ReflectionMethod($service, 'disconnectedResponse');
    $result = $method->invoke($service);

    expect($result['status']['status'])->toBe('disconnected');
    expect($result['status']['message'])->toBe('Router is not connected');
    expect($result['traffic']['status'])->toBe('disconnected');
    expect($result['topConnections']['status'])->toBe('disconnected');
    expect($result['systemUtilization']['status'])->toBe('disconnected');
    expect($result['networkInfo']['status'])->toBe('disconnected');
    expect($result['connectedClients']['status'])->toBe('disconnected');
    expect($result['queueMonitoring']['status'])->toBe('disconnected');
});

test('errorResponse returns correct structure with custom message', function () {
    $service = new RouterService();
    $method = new ReflectionMethod($service, 'errorResponse');
    $result = $method->invoke($service, 'Custom error');

    expect($result['status']['status'])->toBe('error');
    expect($result['status']['message'])->toBe('Custom error');
    expect($result['traffic']['message'])->toBe('Custom error');
    expect($result['queueMonitoring']['message'])->toBe('Custom error');
});
