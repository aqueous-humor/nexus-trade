<?php

use App\Console\Commands\ProcessMaturedInvestments;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process matured investments every minute
Schedule::command(ProcessMaturedInvestments::class)->everyMinute();
