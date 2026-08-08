<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The daily backup is NOT triggered via Laravel's scheduler — Schedule::command()
// only defines when a job should run; something still has to call
// `php artisan schedule:run` on a timer for it to actually fire, and nothing in
// this deployment does. Since Render's persistent disk is only mountable by the
// one web service that holds the SQLite file, a separate Cron Job service
// couldn't reach it anyway — so `backup:database` is instead invoked directly
// by a system cron daemon running inside that same container.
// See docker/crontab and the "Starting cron" step in docker/start.sh.
