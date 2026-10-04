<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

// Shared-hosting friendly: everything runs from one cron line (docs/architecture/21.3 step 6)
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')->everyMinute()->withoutOverlapping();
Schedule::command('smukn:reminders')->dailyAt('08:10')->timezone('Africa/Lagos');
Schedule::command('smukn:flag-review-due')->dailyAt('02:30');
Schedule::command('smukn:sources-check')->dailyAt('03:40')->withoutOverlapping(); // un-verify facts whose official page changed
Schedule::command('smukn:expire-payments')->hourly();
Schedule::command('queue:prune-failed --hours=720')->weekly();
// heartbeat: proves the hPanel cron job runs even when the queue is empty (admin dashboard, live verification)
Schedule::call(fn () => Cache::forever('scheduler.heartbeat', now()->timestamp))->everyMinute()->name('scheduler-heartbeat');
