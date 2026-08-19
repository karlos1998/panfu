<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Laravel\Telescope\TelescopeServiceProvider;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (class_exists(TelescopeServiceProvider::class)) {
    Schedule::command('telescope:prune', [
        '--hours' => (int) config('telescope.prune_hours', 48),
    ])->daily()->withoutOverlapping();
}
