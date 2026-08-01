<?php

namespace App\Jobs;

use App\Services\CohortHeatMapService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Rebuilds the facilitator/admin cohort heat map cache once per day.
 * Pages read the cache only — progress is not queried live on each page view.
 */
class RefreshCohortHeatMapCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function handle(CohortHeatMapService $service): void
    {
        $meta = $service->refreshAll();

        Log::info('Cohort heat map cache refreshed', $meta);
    }
}
