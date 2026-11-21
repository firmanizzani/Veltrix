<?php

namespace App\Jobs;

use App\Models\Inventory;
use App\Services\NotificationCenter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshOperationalNotifications implements ShouldQueue
{
    use Queueable;
    public int $tries = 3;
    public int $timeout = 60;

    public function handle(NotificationCenter $center): void
    {
        Inventory::with('ingredient', 'branch')
            ->whereColumn('quantity', '<=', 'minimum')
            ->chunkById(100, function ($stocks) use ($center) {
                foreach ($stocks as $stock) {
                    $center->lowStock($stock);
                }
            });
    }
}
