<?php

namespace App\Jobs;

use App\Services\BusinessInsights;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateBusinessInsights implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $timeout = 120;

    public function handle(BusinessInsights $insights): void
    {
        $insights->generate();
    }
}
