<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tasks:daily-reminder')
    ->dailyAt('09:00')
    ->timezone('Asia/Kolkata');

Schedule::command('attendance:auto-checkout')
    ->dailyAt('23:59')
    ->timezone('Asia/Kolkata');
