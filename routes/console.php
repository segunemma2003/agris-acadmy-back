<?php

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
