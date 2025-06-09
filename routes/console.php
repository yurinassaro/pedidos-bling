<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Console\Scheduling\Schedule;
use App\Services\BlingAuthService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// app(Schedule::class)->daily()->call(function () {
//     $blingAuthService = app(BlingAuthService::class);

//     if (!$blingAuthService->hasValidToken()) {
//         $blingAuthService->refreshToken();
//     }
// })->name('bling:refresh-token');


// app(Schedule::class)->everyMinute()->call(function () {
//     $blingAuthService = app(BlingAuthService::class);

//     if (!$blingAuthService->hasValidToken()) {
//         $blingAuthService->refreshToken();
//     }
// })->name('bling:refresh-token');

app(Schedule::class)->daily()->command('bling:refresh-token')->name('bling:refresh-token');

