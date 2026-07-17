<?php

namespace App\Http\Controllers;

use App\Events\RouterDataUpdated;
use Illuminate\Http\JsonResponse;
use App\Services\RouterService;

class RouterController extends Controller
{
    public function status(RouterService $service): JsonResponse
    {
        $data = $service->getStatus();

        return response()->json($data, 200);
    }

    public function traffic(RouterService $service): JsonResponse
    {
        $data = $service->getTraffic();

        return response()->json($data, 200);
    }

    public function topConnections(RouterService $service): JsonResponse
    {
        $data = $service->getTopConnections();

        return response()->json($data, 200);
    }

    public function systemUtilization(RouterService $service): JsonResponse
    {
        $data = $service->getSystemUtilization();

        return response()->json($data, 200);
    }

    public function networkInfo(RouterService $service): JsonResponse
    {
        $data = $service->getNetworkInfo();

        return response()->json($data, 200);
    }

    public function connectedClients(RouterService $service): JsonResponse
    {
        $data = $service->getConnectedClients();

        return response()->json($data, 200);
    }

    public function queueMonitoring(RouterService $service): JsonResponse
    {
        $data = $service->getQueueMonitoring();

        return response()->json($data, 200);
    }

    public function broadcastUpdate(RouterService $service): JsonResponse
    {
        try {
            $data = $service->getData();

            $event = new RouterDataUpdated($data);
            broadcast($event);

            return response()->json(['message' => 'Broadcast sent']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 200);
        }
    }
}
