<?php

namespace App\Http\Controllers;

use App\Events\RouterDataUpdated;
use Illuminate\Http\JsonResponse;
use App\Services\RouterService;
use Mivo\LaravelMikrotikRos6\Facades\MikrotikRos6;

class RouterController extends Controller
{
    public function status(): JsonResponse
    {
        try {
            $client = MikrotikRos6::connection();
            $connected = $client->isConnected();

            if (! $connected) {
                return response()->json([
                    'status' => 'disconnected',
                    'message' => 'Router is not connected',
                ], 200);
            }

            $identity = $client->comm('/system/identity/print');
            $identityName = $identity[0]['name'] ?? 'Unknown';

            $client->disconnect();

            return response()->json([
                'status' => 'connected',
                'identity' => $identityName,
                'message' => 'Router is online',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 200);
        }
    }

    public function traffic(): JsonResponse
    {
        try {
            $client = MikrotikRos6::connection();
            $connected = $client->isConnected();

            if (! $connected) {
                return response()->json([
                    'status' => 'disconnected',
                    'message' => 'Router is not connected',
                ], 200);
            }

            $traffic = $client->comm('/interface/monitor-traffic', [
                'interface' => 'ether1',
                'once' => '',
            ]);

            $client->disconnect();

            if (empty($traffic)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No traffic data available for ether1',
                ], 200);
            }

            $data = $traffic[0];

            return response()->json([
                'status' => 'connected',
                'rx' => isset($data['rx-bits-per-second']) ? (int) $data['rx-bits-per-second'] : 0,
                'tx' => isset($data['tx-bits-per-second']) ? (int) $data['tx-bits-per-second'] : 0,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 200);
        }
    }

    public function topConnections(): JsonResponse
    {
        try {
            $client = MikrotikRos6::connection();
            $connected = $client->isConnected();

            if (! $connected) {
                return response()->json([
                    'status' => 'disconnected',
                    'message' => 'Router is not connected',
                ], 200);
            }

            $connections = $client->comm('/ip/firewall/connection/print');

            $client->disconnect();

            if (empty($connections)) {
                return response()->json([
                    'status' => 'connected',
                    'sources' => [],
                    'destinations' => [],
                ], 200);
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

            return response()->json([
                'status' => 'connected',
                'sources' => array_keys($topSources),
                'source_counts' => $topSources,
                'destinations' => array_keys($topDestinations),
                'destination_counts' => $topDestinations,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 200);
        }
    }

    public function systemUtilization(): JsonResponse
    {
        try {
            $client = MikrotikRos6::connection();
            $connected = $client->isConnected();

            if (! $connected) {
                return response()->json([
                    'status' => 'disconnected',
                    'message' => 'Router is not connected',
                ], 200);
            }

            $resources = $client->comm('/system/resource/print');
            $client->disconnect();

            if (empty($resources)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No system resource data available',
                ], 200);
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

            return response()->json([
                'status' => 'connected',
                'cpu' => $cpuLoad,
                'memory' => $memoryPercent,
                'storage' => $storagePercent,
                'uptime' => $this->formatUptime($uptime),
                'board_name' => $boardName,
                'version' => $version,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 200);
        }
    }

    public function networkInfo(): JsonResponse
    {
        try {
            $client = MikrotikRos6::connection();
            $connected = $client->isConnected();

            if (! $connected) {
                return response()->json([
                    'status' => 'disconnected',
                    'message' => 'Router is not connected',
                ], 200);
            }

            $networks = $client->comm('/ip/dhcp-server/network/print');
            $leases = $client->comm('/ip/dhcp-server/lease/print');
            $client->disconnect();

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

            return response()->json([
                'status' => 'connected',
                'networks' => $networksList,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 200);
        }
    }

    public function broadcastUpdate(RouterService $service)
    {
        try {
            $data = $service->getData();

            $event = new RouterDataUpdated($data);

            \Log::info('Broadcast preparing', [
                'event' => get_class($event),
                'channel' => $event->broadcastOn(),
                'data_keys' => array_keys($event->data),
            ]);

            broadcast($event);

            \Log::info('Broadcast dispatched', [
                'event' => get_class($event),
                'channel' => $event->broadcastOn(),
                'name' => method_exists($event, 'broadcastAs') ? $event->broadcastAs() : get_class($event),
            ]);

            return response()->json(['message' => 'Broadcast sent']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 200);
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
}
