<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reminder processing - every minute (checks send_time match)
Schedule::command('reminder:process')
    ->everyMinute()
    ->withoutOverlapping();

// Log cleanup - weekly on Sunday at 02:00 WIB (19:00 Saturday UTC = 02:00 Sunday WIB)
Schedule::command('log:cleanup')
    ->weekly()
    ->saturdays()
    ->at('19:00')
    ->timezone('UTC');

// Device health check - every minute
Schedule::command('device:health-check')
    ->everyMinute()
    ->withoutOverlapping();

// Subscription expiry check - daily at 08:00 WIB (01:00 UTC = 08:00 WIB)
Schedule::command('subscription:check-expiry')
    ->dailyAt('01:00')
    ->timezone('UTC')
    ->withoutOverlapping();

// Trial expiry check - daily at 08:00 WIB (01:00 UTC = 08:00 WIB)
Schedule::command('trial:check-expiry')
    ->dailyAt('01:00')
    ->timezone('UTC')
    ->withoutOverlapping();

// Alert check - every 5 minutes
Schedule::command('alert:check')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Scheduled broadcast dispatch - every minute
Schedule::command('broadcast:dispatch-scheduled')
    ->everyMinute()
    ->withoutOverlapping();
