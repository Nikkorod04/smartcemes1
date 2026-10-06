<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// DEADLINE SCHEDULER (5.14): daily at 07:00 — programs ending within 14 days
// and on-track objectives approaching target dates, with 7-day dedup.
Schedule::command('smartcemes:notify-deadlines')->dailyAt('07:00');

// NIGHTLY BACKUP (R7, absorbed from the retired "Phase 6"): 02:00, when nobody
// is encoding, keeping the last 14 dumps. Requires the scheduler to be running
// (`php artisan schedule:work` in dev, or the one cron entry in production).
Schedule::command('smartcemes:backup-database')->dailyAt('02:00');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
