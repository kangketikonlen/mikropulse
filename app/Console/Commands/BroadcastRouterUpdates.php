<?php

namespace App\Console\Commands;

use App\Services\RouterService;
use App\Events\RouterDataUpdated;
use Illuminate\Console\Command;

class BroadcastRouterUpdates extends Command
{
    protected $signature = 'app:broadcast-router-updates';
    protected $description = 'Broadcast router data updates via Reverb';

    public function handle(RouterService $service)
    {
        $this->info('Starting continuous broadcast...');

        pcntl_async_signals(true);
        pcntl_signal(SIGINT, fn () => RouterService::disconnect());
        pcntl_signal(SIGTERM, fn () => RouterService::disconnect());

        while (true) {
            try {
                $data = $service->getData();

                broadcast(new RouterDataUpdated($data));

                $this->line('['.now()->format('H:i:s').'] Broadcast sent');
            } catch (\Throwable $e) {
                $this->error('['.now()->format('H:i:s').'] '.$e->getMessage());
            }

            usleep(2000000); // Sleep for 2 seconds before the next broadcast
        }
    }
}
