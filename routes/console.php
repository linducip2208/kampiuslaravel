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
    // M-022 scheduler akademik: rollover periode tiap awal semester
    // (ganjil ~Agustus, genap ~Februari). Idempoten (firstOrCreate + 1 aktif);
    // nonaktifkan manual bila kalender akademik menyimpang.
    $schedule->command('akademik:rollover')->yearlyOn(2, 1, '00:30')->withoutOverlapping();
    $schedule->command('akademik:rollover')->yearlyOn(8, 1, '00:30')->withoutOverlapping();
    // M-036: penyusutan aset tiap awal bulan (idempoten per bulan).
    $schedule->command('aset:susutkan')->monthlyOn(1, '02:00')->withoutOverlapping();
    // RBAC batch 4: terapkan/cabut delegasi peran tiap pagi (idempoten).
    $schedule->command('delegasi:sinkron')->dailyAt('00:30')->withoutOverlapping();
    // M-051: segel attempt CBT kedaluwarsa tiap jam.
    $schedule->command('kuis:seal-kedaluwarsa')->hourly()->withoutOverlapping();
});
