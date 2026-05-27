<?php

use Illuminate\Support\Facades\Schedule;

/*
| Voltra — penjadwalan otomatis (Laravel Scheduler).
| Depresiasi Garis Lurus dijalankan tiap tanggal 1 pukul 00:30
| untuk membukukan penyusutan bulan sebelumnya (alur 5 PRD).
| Aktifkan dengan menjalankan: php artisan schedule:work
*/

Schedule::command('voltra:depreciate')
    ->monthlyOn(1, '00:30')
    ->withoutOverlapping()
    ->description('Depresiasi Garis Lurus akhir bulan');
