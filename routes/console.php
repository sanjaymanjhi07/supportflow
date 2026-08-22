<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sweep every tenant's tickets for SLA breaches every 5 minutes. Keep this
// frequent and cheap: the query is indexed on the due-date columns.
Schedule::command('sla:check-breaches')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();
