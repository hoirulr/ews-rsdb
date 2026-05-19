<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('ews:monitor-failed')
    ->everyFiveMinutes()
    ->emailOutputOnFailure(env('ADMIN_EMAIL', 'admin@rsud-depatibahrin.id'));

Schedule::command('queue:flush')
    ->daily()
    ->at('02:00');
