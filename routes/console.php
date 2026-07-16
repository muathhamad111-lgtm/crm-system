<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// CRM scheduled jobs (mirror the original pg_cron cadences).
Schedule::command('crm:sla-scan')->everyFifteenMinutes();
Schedule::command('crm:auto-close')->everyFifteenMinutes();
Schedule::command('crm:auto-escalate')->hourly();
