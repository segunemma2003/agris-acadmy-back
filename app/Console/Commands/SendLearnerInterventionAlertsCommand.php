<?php

namespace App\Console\Commands;

use App\Services\LearnerInterventionAlertService;
use Illuminate\Console\Command;

class SendLearnerInterventionAlertsCommand extends Command
{
    protected $signature = 'learners:send-intervention-alerts';

    protected $description = 'Nightly: email admin@agrisiti.com about learners inactive 7+ days or failing the same quiz twice';

    public function handle(LearnerInterventionAlertService $service): int
    {
        $result = $service->runNightly();

        $this->info("Detected {$result['detected']} alert(s); emailed {$result['emailed']} to " . LearnerInterventionAlertService::ADMIN_EMAIL);

        return self::SUCCESS;
    }
}
