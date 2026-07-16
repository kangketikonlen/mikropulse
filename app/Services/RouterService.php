<?php

namespace App\Services;

use Mivo\LaravelMikrotikRos6\Facades\MikrotikRos6;

class RouterService
{
    private static ?\Mivo\MikrotikRos6\Client $client = null;
    private static int $lastKeepalive = 0;
    private static int $keepaliveInterval = 30;

    private static ?array $cachedSystemData = null;
    private static int $cachedSystemDataTime = 0;
    private static int $systemDataTtl = 5;

    private static ?array $cachedConnectionsData = null;
    private static int $cachedConnectionsDataTime = 0;
    private static int $connectionsDataTtl = 5;

    private static ?array $cachedNetworkData = null;
    private static int $cachedNetworkDataTime = 0;
    private static int $networkDataTtl = 30;

    private function getClient(): \Mivo\MikrotikRos6\Client
    {
        if (self::$client === null) {
            self::$client = MikrotikRos6::connection([
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
        }

        if (! self::$client->isConnected()) {
            self::$client->connect();
        }

        $now = time();
        if ($now - self::$lastKeepalive >= self::$keepaliveInterval) {
            self::$lastKeepalive = $now;
            try {
                self::$client->comm('/system/identity/print');
            } catch (\Throwable $e) {
                try {
                    self::$client->connect();
                } catch (\Throwable $e2) {
                    self::$client = null;
                    throw $e2;
                }
            }
        }

        return self::$client;
    }

    public static function disconnect(): void
    {
        if (self::$client !== null) {
            try {
                self::$client->disconnect();
            } catch (\Throwable $e) {
            }
            self::$client = null;
        }
    }

    public function getData(): array
    {
        try {
            $client = $this->getClient();
            $connected = $client->isConnected();

            if (! $connected) {
                return $this->disconnectedResponse();
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

            $now = time();

            if (self::$cachedSystemData === null || ($now - self::$cachedSystemDataTime) >= self::$systemDataTtl) {
                self::$cachedSystemData = $this->fetchSystemData($client);
                self::$cachedSystemDataTime = $now;
            }

            if (self::$cachedConnectionsData === null || ($now - self::$cachedConnectionsDataTime) >= self::$connectionsDataTtl) {
                self::$cachedConnectionsData = $this->fetchConnectionsData($client);
                self::$cachedConnectionsDataTime = $now;
            }

            if (self::$cachedNetworkData === null || ($now - self::$cachedNetworkDataTime) >= self::$networkDataTtl) {
                self::$cachedNetworkData = $this->fetchNetworkData($client);
                self::$cachedNetworkDataTime = $now;
            }

            return [
                'status' => [
                    'status' => 'connected',
                    'identity' => $identityName,
                    'message' => 'Router is online',
                ],
                'traffic' => $trafficData,
                'topConnections' => self::$cachedConnectionsData,
                'systemUtilization' => self::$cachedSystemData,
                'networkInfo' => self::$cachedNetworkData,
            ];
        } catch (\Throwable $e) {
            \Log::error('RouterService error', ['message' => $e->getMessage()]);

            return $this->errorResponse($e->getMessage());
        }
    }

    private function fetchSystemData($client): array
    {
        $resources = $client->comm('/system/resource/print');
        if (empty($resources)) {
            return ['status' => 'error', 'message' => 'No system resource data available'];
        }

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
        $boardName = $data['board-name'] ?? 'Unknown';
        $version = $data['version'] ?? 'Unknown';

        return [
            'status' => 'connected',
            'cpu' => $cpuLoad,
            'memory' => $memoryPercent,
            'storage' => $storagePercent,
            'uptime' => $this->formatUptime($uptime),
            'board_name' => $boardName,
            'version' => $version,
        ];
    }

    private function fetchConnectionsData($client): array
    {
        $connections = $client->comm('/ip/firewall/connection/print');
        if (empty($connections)) {
            return ['status' => 'connected', 'sources' => [], 'destinations' => []];
        }

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

        return [
            'status' => 'connected',
            'sources' => array_keys($topSources),
            'source_counts' => $topSources,
            'destinations' => array_keys($topDestinations),
            'destination_counts' => $topDestinations,
        ];
    }

    private function fetchNetworkData($client): array
    {
        $networks = $client->comm('/ip/dhcp-server/network/print');
        $leases = $client->comm('/ip/dhcp-server/lease/print');

        if (empty($networks)) {
            return ['status' => 'connected', 'networks' => []];
        }

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

        return [
            'status' => 'connected',
            'networks' => $networksList,
        ];
    }

    private function disconnectedResponse(): array
    {
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

    private function errorResponse(string $message): array
    {
        return [
            'status' => [
                'status' => 'error',
                'message' => $message,
            ],
            'traffic' => [
                'status' => 'error',
                'message' => $message,
            ],
            'topConnections' => [
                'status' => 'error',
                'message' => $message,
            ],
            'systemUtilization' => [
                'status' => 'error',
                'message' => $message,
            ],
            'networkInfo' => [
                'status' => 'error',
                'message' => $message,
            ],
        ];
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
}
