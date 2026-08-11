<?php

use App\Jobs\RefreshCohortHeatMapCache;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule video transcription to run every hour
Schedule::command('videos:transcribe')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Schedule queue monitoring every 5 minutes
Schedule::command('queue:monitor')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Prune old failed jobs (older than 7 days) daily
Schedule::command('queue:prune-failed --hours=168')
    ->daily()
    ->at('02:00')
    ->withoutOverlapping();

// Cohort heat map: daily refresh (not realtime) for facilitator/admin panels.
Schedule::command('cohort:refresh-heatmap --sync')
    ->dailyAt('01:15')
    ->withoutOverlapping()
    ->name('cohort-heatmap-refresh');

// Learner intervention alerts → email admin@agrisiti.com (inactive threshold from settings / quiz failed twice).
Schedule::command('learners:send-intervention-alerts')
    ->dailyAt('01:45')
    ->withoutOverlapping()
    ->name('learner-intervention-alerts');

// SMS inactivity nudges — runs in morning/afternoon/evening windows; command
// only sends when the admin-configured send_time matches the current window.
Schedule::command('sms:send-inactivity-nudges')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->name('sms-inactivity-nudges-morning');

Schedule::command('sms:send-inactivity-nudges')
    ->dailyAt('13:00')
    ->withoutOverlapping()
    ->name('sms-inactivity-nudges-afternoon');

Schedule::command('sms:send-inactivity-nudges')
    ->dailyAt('18:00')
    ->withoutOverlapping()
    ->name('sms-inactivity-nudges-evening');
