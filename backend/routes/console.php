<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('payments:expire-attempts')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('shipments:sync-tracking')->everyFiveMinutes()->withoutOverlapping();
