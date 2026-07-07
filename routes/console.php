<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('app:about', function () {
    $this->comment('AGR Laravel migration scaffold');
})->purpose('Display information about the application');
