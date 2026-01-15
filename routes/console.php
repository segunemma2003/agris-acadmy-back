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
