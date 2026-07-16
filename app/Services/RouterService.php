<?php

namespace App\Services;

use Mivo\LaravelMikrotikRos6\Facades\MikrotikRos6;

class RouterService
{
    public function getData(): array
    {
        try {
            $client = MikrotikRos6::connection([
                'host' => config('mikrotik-ros6.connections.default.host'),
                'username' => config('mikrotik-ros6.connections.default.username'),
                'password' => config('mikrotik-ros6.connections.default.password'),
                'port' => config('mikrotik-ros6.connections.default.port'),
                'ssl' => config('mikrotik-ros6.connections.default.ssl'),
                'timeout' => config('mikrotik-ros6.connections.default.timeout'),
                'attempts' => config('mikrotik-ros6.connections.default.attempts'),
                'delay' => config('mikrotik-ros6.connections.default.delay'),
                'debug' => config('mikrotik-ros6.connections.default.debug'),
            ]);
            $connected = $client->isConnected();

            // \Log::info('RouterService connection check', [
            //     'connected' => $connected,
            //     'host' => config('mikrotik-ros6.connections.default.host'),
            //     'username' => config('mikrotik-ros6.connections.default.username'),
            //     'port' => config('mikrotik-ros6.connections.default.port'),
            //     'ssl' => config('mikrotik-ros6.connections.default.ssl'),
            // ]);

            if (! $connected) {
                return [
                    'status' => [
                        'status' => 'disconnected',
                        'message' => 'Router is not connected',
                    ],
                    'traffic' => [
                        'status' => 'disconnected',
                        'message' => 'Router is not connected',
                    ],
                    'topConnections' => [
                        'status' => 'disconnected',
                        'message' => 'Router is not connected',
                    ],
                    'systemUtilization' => [
                        'status' => 'disconnected',
                        'message' => 'Router is not connected',
                    ],
                    'networkInfo' => [
                        'status' => 'disconnected',
                        'message' => 'Router is not connected',
                    ],
                ];
            }

        $identity = $client->comm('/system/identity/print');
        $identityName = $identity[0]['name'] ?? 'Unknown';

        $traffic = $client->comm('/interface/monitor-traffic', [
            'interface' => 'ether1',
            'once' => '',
        ]);
        $trafficData = [];
        if (! empty($traffic)) {
            $td = $traffic[0];
            $trafficData = [
                'status' => 'connected',
                'rx' => isset($td['rx-bits-per-second']) ? (int) $td['rx-bits-per-second'] : 0,
                'tx' => isset($td['tx-bits-per-second']) ? (int) $td['tx-bits-per-second'] : 0,
            ];
        } else {
            $trafficData = [
                'status' => 'error',
                'message' => 'No traffic data available for ether1',
            ];
        }

        $connections = $client->comm('/ip/firewall/connection/print');
        $topConnectionsData = ['status' => 'connected', 'sources' => [], 'destinations' => []];
        if (! empty($connections)) {
            $sourceCounts = [];
            $destinationCounts = [];

            foreach ($connections as $conn) {
                $src = $conn['src-address'] ?? null;
                $dst = $conn['dst-address'] ?? null;

                if ($src) {
                    $sourceCounts[$src] = ($sourceCounts[$src] ?? 0) + 1;
                }
                if ($dst) {
                    $destinationCounts[$dst] = ($destinationCounts[$dst] ?? 0) + 1;
                }
            }

            arsort($sourceCounts);
            arsort($destinationCounts);

            $topSources = array_slice($sourceCounts, 0, 3, true);
            $topDestinations = array_slice($destinationCounts, 0, 3, true);

            $topConnectionsData = [
                'status' => 'connected',
                'sources' => array_keys($topSources),
                'source_counts' => $topSources,
                'destinations' => array_keys($topDestinations),
                'destination_counts' => $topDestinations,
            ];
        }

        $resources = $client->comm('/system/resource/print');
        $systemData = ['status' => 'error', 'message' => 'No system resource data available'];
        if (! empty($resources)) {
            $data = $resources[0];

            $cpuLoad = (int) ($data['cpu-load'] ?? 0);
            $totalMemory = (int) ($data['total-memory'] ?? 1);
            $freeMemory = (int) ($data['free-memory'] ?? 0);
            $memoryUsed = $totalMemory - $freeMemory;
            $memoryPercent = $totalMemory > 0 ? round(($memoryUsed / $totalMemory) * 100) : 0;

            $totalStorage = (int) ($data['total-hdd-space'] ?? 1);
            $freeStorage = (int) ($data['free-hdd-space'] ?? 0);
            $storageUsed = $totalStorage - $freeStorage;
            $storagePercent = $totalStorage > 0 ? round(($storageUsed / $totalStorage) * 100) : 0;

            $uptime = $data['uptime'] ?? '0s';
            $version = $data['version'] ?? 'Unknown';

            $latestVersion = $this->getLatestRouterOSVersion();
            $isLatest = $this->isLatestVersion($version, $latestVersion);

            $systemData = [
                'status' => 'connected',
                'cpu' => $cpuLoad,
                'memory' => $memoryPercent,
                'storage' => $storagePercent,
                'uptime' => $this->formatUptime($uptime),
                'version' => $version,
                'is_latest' => $isLatest,
                'latest_version' => $latestVersion,
            ];
        }

        $networks = $client->comm('/ip/dhcp-server/network/print');
        $leases = $client->comm('/ip/dhcp-server/lease/print');
        $networkData = ['status' => 'connected', 'networks' => []];
        if (! empty($networks)) {
            $networksList = [];

            foreach ($networks as $network) {
                $networkAddress = $network['network'] ?? $network['address'] ?? '--';
                $gateway = $network['gateway'] ?? '--';
                $dns = $network['dns-server'] ?? '--';

                $leaseCount = 0;
                if (! empty($leases)) {
                    $parts = explode('.', $networkAddress);
                    $networkPrefix = count($parts) >= 3 ? $parts[0].'.'.$parts[1].'.'.$parts[2].'.' : '';
                    foreach ($leases as $lease) {
                        $leaseIp = $lease['address'] ?? '';
                        if ($networkPrefix && str_starts_with($leaseIp, $networkPrefix)) {
                            $leaseCount++;
                        }
                    }
                }

                $networksList[] = [
                    'address' => $networkAddress,
                    'gateway' => $gateway,
                    'dns' => $dns,
                    'leases' => $leaseCount,
                ];
            }

            $networkData = [
                'status' => 'connected',
                'networks' => $networksList,
            ];
        }

        $client->disconnect();

        return [
            'status' => [
                'status' => 'connected',
                'identity' => $identityName,
                'message' => 'Router is online',
            ],
            'traffic' => $trafficData,
            'topConnections' => $topConnectionsData,
            'systemUtilization' => $systemData,
            'networkInfo' => $networkData,
        ];
    } catch (\Throwable $e) {
        \Log::error('RouterService error', ['message' => $e->getMessage()]);

        return [
            'status' => [
                'status' => 'error',
                'message' => $e->getMessage(),
            ],
            'traffic' => [
                'status' => 'error',
                'message' => $e->getMessage(),
            ],
            'topConnections' => [
                'status' => 'error',
                'message' => $e->getMessage(),
            ],
            'systemUtilization' => [
                'status' => 'error',
                'message' => $e->getMessage(),
            ],
            'networkInfo' => [
                'status' => 'error',
                'message' => $e->getMessage(),
            ],
        ];
    }
}

    private function formatUptime(string $uptime): string
    {
        $pattern = '/(?:(\d+)w)?(?:(\d+)d)?(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?/';
        preg_match($pattern, $uptime, $matches);

        $weeks = (int) ($matches[1] ?? 0);
        $days = (int) ($matches[2] ?? 0);
        $hours = (int) ($matches[3] ?? 0);
        $minutes = (int) ($matches[4] ?? 0);
        $seconds = (int) ($matches[5] ?? 0);

        $parts = [];
        if ($weeks > 0) {
            $parts[] = "{$weeks}W";
        }
        if ($days > 0) {
            $parts[] = "{$days}D";
        }
        if ($hours > 0) {
            $parts[] = "{$hours}H";
        }
        if ($minutes > 0) {
            $parts[] = "{$minutes}M";
        }

        return implode(' ', $parts) ?: '0M';
    }

    private function getLatestRouterOSVersion(): string
    {
        $cached = cache()->get('latest_routeros_version');
        if ($cached) {
            return $cached;
        }

        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'user_agent' => 'MikroPulse/1.0',
                ],
            ]);

            $html = @file_get_contents('https://mikrotik.com/download', false, $context);
            if ($html && preg_match('/routeros\s*v?(\d+\.\d+(?:\.\d+)?)/i', $html, $matches)) {
                $version = $matches[1];
                cache()->put('latest_routeros_version', $version, 3600);

                return $version;
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return '7.18';
    }

    private function isLatestVersion(string $current, string $latest): bool
    {
        if ($latest === 'Unknown' || $current === 'Unknown') {
            return false;
        }

        $currentParts = explode('.', $current);
        $latestParts = explode('.', $latest);

        $max = max(count($currentParts), count($latestParts));

        for ($i = 0; $i < $max; $i++) {
            $currentPart = (int) ($currentParts[$i] ?? 0);
            $latestPart = (int) ($latestParts[$i] ?? 0);

            if ($currentPart < $latestPart) {
                return false;
            }
            if ($currentPart > $latestPart) {
                return true;
            }
        }

        return true;
    }
}