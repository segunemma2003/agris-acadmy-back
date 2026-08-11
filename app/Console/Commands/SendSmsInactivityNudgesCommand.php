<?php

namespace App\Console\Commands;

use App\Services\SmsInactivityNudgeService;
use Illuminate\Console\Command;

class SendSmsInactivityNudgesCommand extends Command
{
    protected $signature = 'sms:send-inactivity-nudges {--force : Send even outside the configured send-time window}';

    protected $description = 'Send inactivity SMS nudges using admin notification settings';

    public function handle(SmsInactivityNudgeService $service): int
    {
        $result = $service->run(forceWindow: (bool) $this->option('force'));

        if ($result['skipped_disabled']) {
            $this->info('SMS nudges are disabled in admin settings.');

            return self::SUCCESS;
        }

        if ($result['skipped_window']) {
            $this->info('Outside configured send-time window — nothing sent. Use --force to override.');

            return self::SUCCESS;
        }

        $this->info("Eligible: {$result['eligible']}; sent: {$result['sent']}");

        return self::SUCCESS;
    }
}
