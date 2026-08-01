<?php

namespace App\Console\Commands;

use App\Jobs\RefreshCohortHeatMapCache;
use App\Services\CohortHeatMapService;
use Illuminate\Console\Command;

class RefreshCohortHeatMapCommand extends Command
{
    protected $signature = 'cohort:refresh-heatmap {--sync : Run synchronously instead of queueing}';

    protected $description = 'Rebuild the daily cohort heat map cache used by facilitator/admin panels';

    public function handle(CohortHeatMapService $service): int
    {
        if ($this->option('sync')) {
            $meta = $service->refreshAll();
            $this->info('Heat map cache rebuilt: ' . json_encode($meta));

            return self::SUCCESS;
        }

        RefreshCohortHeatMapCache::dispatch();
        $this->info('RefreshCohortHeatMapCache job dispatched.');

        return self::SUCCESS;
    }
}
