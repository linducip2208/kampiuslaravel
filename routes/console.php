<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

app()->booted(function () {
    $schedule = app(Schedule::class);
    // Pengingat tagihan H-7 / H+7 via WA + inbox, idempoten per tagihan.
    $schedule->command('tagihan:ingatkan')->dailyAt('08:00')->withoutOverlapping();
    // Denda otomatis harian (idempoten: nilai ditimpa, bukan diakumulasi).
    $schedule->command('denda:terapkan')->dailyAt('01:00')->withoutOverlapping();
});
