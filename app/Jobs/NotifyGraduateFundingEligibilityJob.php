<?php

namespace App\Jobs;

use App\Models\Certificate;
use App\Services\GraduateFundingNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Learn → Fund: notify learner of graduate loan eligibility after certificate issuance.
 * Queued so delivery stays within the 1-hour SLA without blocking certificate generation.
 */
class NotifyGraduateFundingEligibilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [60, 300];

    public $timeout = 60;

    public function __construct(public int $certificateId) {}

    public function handle(GraduateFundingNotificationService $notifier): void
    {
        $certificate = Certificate::with(['user', 'course'])->find($this->certificateId);

        if (! $certificate) {
            Log::warning('Graduate funding notify skipped: certificate missing', [
                'certificate_id' => $this->certificateId,
            ]);

            return;
        }

        $result = $notifier->notifyIfEligible($certificate);

        if (! ($result['sent'] ?? false)) {
            Log::info('Graduate funding notify not sent', [
                'certificate_id' => $this->certificateId,
                'reason' => $result['reason'] ?? 'unknown',
            ]);
        }
    }
}
