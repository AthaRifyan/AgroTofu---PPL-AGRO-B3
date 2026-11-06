<?php

use App\Services\TahapProduksiService;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => app(TahapProduksiService::class)->sinkronkan())
    ->name('sinkronkan-tahap-produksi')
    ->everyMinute()
    ->withoutOverlapping();