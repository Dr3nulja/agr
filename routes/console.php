<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('app:about', function () {
    $this->comment('AGR Laravel migration scaffold');
})->purpose('Display information about the application');

// Утечки воды за вчерашний день — каждое утро (нужен cron: * * * * * php artisan schedule:run)
Schedule::command('leaks:check')->dailyAt('06:00')->timezone('Europe/Tallinn')->withoutOverlapping();
