<?php

namespace App\Services;

use Mivo\LaravelMikrotikRos6\Facades\MikrotikRos6;

class RouterService
{
    private static ?\Mivo\MikrotikRos6\Client $client = null;
    private static int $lastKeepalive = 0;
    private static int $keepaliveInterval = 30;

    private static ?array $cachedIdentity = null;
    private static int $cachedIdentityTime = 0;
    private static int $identityTtl = 60;

    private static ?array $cachedSystemData = null;
    private static int $cachedSystemDataTime = 0;
    private static int $systemDataTtl = 5;

    private static ?array $cachedConnectionsData = null;
    private static int $cachedConnectionsDataTime = 0;
    private static int $connectionsDataTtl = 10;

    private static ?array $cachedNetworkData = null;
    private static int $cachedNetworkDataTime = 0;
    private static int $networkDataTtl = 30;

    private static ?array $cachedClientsData = null;
    private static int $cachedClientsDataTime = 0;
    private static int $clientsDataTtl = 10;

    private static ?array $cachedQueueData = null;
    private static int $cachedQueueDataTime = 0;
    private static int $queueDataTtl = 10;

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

            $now = time();

            if (self::$cachedIdentity === null || ($now - self::$cachedIdentityTime) >= self::$identityTtl) {
                $identity = $client->comm('/system/identity/print', [
                    '.proplist' => 'name',
                ]);
                self::$cachedIdentity = ['name' => $identity[0]['name'] ?? 'Unknown'];
                self::$cachedIdentityTime = $now;
            }
            $identityName = self::$cachedIdentity['name'];

            $traffic = $client->comm('/interface/monitor-traffic', [
                'interface' => 'ether1',
                'once' => '',
                '.proplist' => 'rx-bits-per-second,tx-bits-per-second',
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

            if (self::$cachedSystemData === null || ($now - self::$cachedSystemDataTime) >= self::$systemDataTtl) {
                self::$cachedSystemData = $this->getSystemUtilization($client);
                self::$cachedSystemDataTime = $now;
            }

            if (self::$cachedConnectionsData === null || ($now - self::$cachedConnectionsDataTime) >= self::$connectionsDataTtl) {
                self::$cachedConnectionsData = $this->getTopConnections($client);
                self::$cachedConnectionsDataTime = $now;
            }

            if (self::$cachedNetworkData === null || ($now - self::$cachedNetworkDataTime) >= self::$networkDataTtl) {
                self::$cachedNetworkData = $this->getNetworkInfo($client);
                self::$cachedNetworkDataTime = $now;
            }

            if (self::$cachedClientsData === null || ($now - self::$cachedClientsDataTime) >= self::$clientsDataTtl) {
                self::$cachedClientsData = $this->getConnectedClients($client);
                self::$cachedClientsDataTime = $now;
            }

            if (self::$cachedQueueData === null || ($now - self::$cachedQueueDataTime) >= self::$queueDataTtl) {
                self::$cachedQueueData = $this->getQueueMonitoring($client);
                self::$cachedQueueDataTime = $now;
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
                'connectedClients' => self::$cachedClientsData,
                'queueMonitoring' => self::$cachedQueueData,
            ];
        } catch (\Throwable $e) {
            \Log::error('RouterService error', ['message' => $e->getMessage()]);

            return $this->errorResponse($e->getMessage());
        }
    }

    public function getStatus(): array
    {
        try {
            $client = $this->getClient();
            $connected = $client->isConnected();

            if (! $connected) {
                return [
                    'status' => 'disconnected',
                    'message' => 'Router is not connected',
                ];
            }

            $identity = $client->comm('/system/identity/print');
            $identityName = $identity[0]['name'] ?? 'Unknown';

            return [
                'status' => 'connected',
                'identity' => $identityName,
                'message' => 'Router is online',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getTraffic(): array
    {
        try {
            $client = $this->getClient();
            $connected = $client->isConnected();

            if (! $connected) {
                return [
                    'status' => 'disconnected',
                    'message' => 'Router is not connected',
                ];
            }

            $traffic = $client->comm('/interface/monitor-traffic', [
                'interface' => 'ether1',
                'once' => '',
            ]);

            if (empty($traffic)) {
                return [
                    'status' => 'error',
                    'message' => 'No traffic data available for ether1',
                ];
            }

            $data = $traffic[0];

            return [
                'status' => 'connected',
                'rx' => isset($data['rx-bits-per-second']) ? (int) $data['rx-bits-per-second'] : 0,
                'tx' => isset($data['tx-bits-per-second']) ? (int) $data['tx-bits-per-second'] : 0,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getTopConnections($client = null): array
    {
        try {
            if ($client === null) {
                $client = $this->getClient();
                $connected = $client->isConnected();

                if (! $connected) {
                    return [
                        'status' => 'disconnected',
                        'message' => 'Router is not connected',
                    ];
                }
            }

            $connections = $client->comm('/ip/firewall/connection/print', [
                '.proplist' => 'src-address,dst-address',
            ]);

            if (empty($connections)) {
                return [
                    'status' => 'connected',
                    'sources' => [],
                    'destinations' => [],
                ];
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
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getSystemUtilization($client = null): array
    {
        try {
            if ($client === null) {
                $client = $this->getClient();
                $connected = $client->isConnected();

                if (! $connected) {
                    return [
                        'status' => 'disconnected',
                        'message' => 'Router is not connected',
                    ];
                }
            }

            $resources = $client->comm('/system/resource/print', [
                '.proplist' => 'cpu-load,total-memory,free-memory,total-hdd-space,free-hdd-space,uptime,board-name,version',
            ]);

            if (empty($resources)) {
                return [
                    'status' => 'error',
                    'message' => 'No system resource data available',
                ];
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
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getNetworkInfo($client = null): array
    {
        try {
            if ($client === null) {
                $client = $this->getClient();
                $connected = $client->isConnected();

                if (! $connected) {
                    return [
                        'status' => 'disconnected',
                        'message' => 'Router is not connected',
                    ];
                }
            }

            $networks = $client->comm('/ip/dhcp-server/network/print');
            $leases = $client->comm('/ip/dhcp-server/lease/print');

            $networksList = [];

            if (! empty($networks)) {
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
            }

            return [
                'status' => 'connected',
                'networks' => $networksList,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getConnectedClients($client = null): array
    {
        try {
            if ($client === null) {
                $client = $this->getClient();
                $connected = $client->isConnected();

                if (! $connected) {
                    return [
                        'status' => 'disconnected',
                        'message' => 'Router is not connected',
                    ];
                }
            }

            $connections = $client->comm('/ip/firewall/connection/print', [
                '.proplist' => 'src-address,orig-bytes,repl-bytes',
            ]);
            $leases = $client->comm('/ip/dhcp-server/lease/print', [
                '.proplist' => 'address,host-name',
            ]);
            $dnsEntries = $client->comm('/ip/dns/static/print');

            $hostnameMap = [];
            if (! empty($leases)) {
                foreach ($leases as $lease) {
                    $ip = $lease['address'] ?? null;
                    $name = $lease['host-name'] ?? null;
                    if ($ip && $name) {
                        $hostnameMap[$ip] = $name;
                    }
                }
            }
            if (! empty($dnsEntries)) {
                foreach ($dnsEntries as $entry) {
                    $ip = $entry['address'] ?? null;
                    $name = $entry['name'] ?? null;
                    if ($ip && $name) {
                        $hostnameMap[$ip] = $name;
                    }
                }
            }

            $ipStats = [];

            if (! empty($connections)) {
                foreach ($connections as $conn) {
                    $src = $conn['src-address'] ?? null;
                    if (! $src) {
                        continue;
                    }

                    $ip = explode(':', $src)[0];

                    if (! isset($ipStats[$ip])) {
                        $ipStats[$ip] = [
                            'ip' => $ip,
                            'hostname' => $hostnameMap[$ip] ?? null,
                            'connections' => 0,
                            'upload_bytes' => 0,
                            'download_bytes' => 0,
                        ];
                    }

                    $ipStats[$ip]['connections']++;
                    $ipStats[$ip]['upload_bytes'] += (int) ($conn['orig-bytes'] ?? 0);
                    $ipStats[$ip]['download_bytes'] += (int) ($conn['repl-bytes'] ?? 0);
                }
            }

            uasort($ipStats, fn($a, $b) => ($b['download_bytes'] + $b['upload_bytes']) <=> ($a['download_bytes'] + $a['upload_bytes']));

            $topByBandwidth = array_slice($ipStats, 0, 9, true);

            uasort($topByBandwidth, fn($a, $b) => ip2long($a['ip']) <=> ip2long($b['ip']));

            $clients = [];
            foreach ($topByBandwidth as $stat) {
                $clients[] = [
                    'ip' => $stat['ip'],
                    'hostname' => $stat['hostname'] ?: 'Unknown',
                    'connections' => $stat['connections'],
                    'upload_bytes' => $stat['upload_bytes'],
                    'download_bytes' => $stat['download_bytes'],
                ];
            }

            return [
                'status' => 'connected',
                'clients' => $clients,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getQueueMonitoring($client = null): array
    {
        try {
            if ($client === null) {
                $client = $this->getClient();
                $connected = $client->isConnected();

                if (! $connected) {
                    return [
                        'status' => 'disconnected',
                        'message' => 'Router is not connected',
                    ];
                }
            }

            $queues = $client->comm('/queue/simple/print', [
                '.proplist' => 'name,parent,rate',
            ]);

            if (empty($queues)) {
                return [
                    'status' => 'connected',
                    'queues' => [],
                ];
            }

            $queueList = [];
            foreach ($queues as $queue) {
                $name = $queue['name'] ?? 'Unnamed';
                $parent = $queue['parent'] ?? '';
                $rate = $queue['rate'] ?? '';
                $upload = 0;
                $download = 0;

                if ($rate && str_contains($rate, '/')) {
                    $parts = explode('/', $rate, 2);
                    $upload = $this->parseRate($parts[0]);
                    $download = $this->parseRate($parts[1]);
                } elseif ($rate) {
                    $download = $this->parseRate($rate);
                }

                $queueList[] = [
                    'name' => $name,
                    'parent' => $parent,
                    'upload' => $upload,
                    'download' => $download,
                ];
            }

            $tree = $this->buildQueueTree($queueList);

            return [
                'status' => 'connected',
                'queues' => $tree,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
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
            'connectedClients' => [
                'status' => 'disconnected',
                'message' => 'Router is not connected',
            ],
            'queueMonitoring' => [
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
            'connectedClients' => [
                'status' => 'error',
                'message' => $message,
            ],
            'queueMonitoring' => [
                'status' => 'error',
                'message' => $message,
            ],
        ];
    }

    private function buildQueueTree(array $queues): array
    {
        $lookup = [];
        foreach ($queues as $queue) {
            $lookup[$queue['name']] = $queue;
        }

        $tree = [];
        foreach ($queues as $queue) {
            $parent = $queue['parent'];
            if ($parent && isset($lookup[$parent])) {
                $lookup[$parent]['children'][] = &$lookup[$queue['name']];
            } else {
                $tree[] = &$lookup[$queue['name']];
            }
        }

        return $tree;
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

    private function parseRate(string $rate): int
    {
        $rate = trim($rate);

        if ($rate === '' || $rate === '0') {
            return 0;
        }

        $multiplier = 1;
        $value = $rate;

        if (str_ends_with($rate, 'G')) {
            $multiplier = 1000000000;
            $value = rtrim($rate, 'G');
        } elseif (str_ends_with($rate, 'M')) {
            $multiplier = 1000000;
            $value = rtrim($rate, 'M');
        } elseif (str_ends_with($rate, 'k')) {
            $multiplier = 1000;
            $value = rtrim($rate, 'k');
        }

        return (int) ((float) $value * $multiplier);
    }
}
