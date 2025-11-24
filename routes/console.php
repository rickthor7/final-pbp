<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

// Artisan command for inspiration
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled Tasks
Schedule::command('telescope:prune --hours=48')->daily();
Schedule::command('cache:prune-stale-tags')->hourly();

// Order Management Tasks
Schedule::command('orders:check-expired-payments')->everyFiveMinutes();
Schedule::command('orders:update-statuses')->everyTenMinutes();
Schedule::command('orders:send-reminders')->dailyAt('09:00');

// Notification Tasks
Schedule::command('notifications:send-daily-digest')->dailyAt('08:00');
Schedule::command('notifications:clean-old')->weekly();

// Report Generation Tasks
Schedule::command('reports:generate-daily')->dailyAt('23:00');
Schedule::command('reports:generate-weekly')->weeklyOn(1, '02:00');
Schedule::command('reports:generate-monthly')->monthlyOn(1, '03:00');

// Database Maintenance Tasks
Schedule::command('backup:clean')->daily();
Schedule::command('backup:run')->dailyAt('01:00');
Schedule::command('model:prune')->daily();

// Fabric Stock Management
Schedule::command('fabrics:check-low-stock')->dailyAt('06:00');
Schedule::command('fabrics:update-popularity')->hourly();

// Tailor Performance Tracking
Schedule::command('tailors:update-ratings')->dailyAt('04:00');
Schedule::command('tailors:check-assignments')->everyThirtyMinutes();
