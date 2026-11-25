<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('inventory:check-alerts')
    ->dailyAt('08:00')
    ->timezone('America/La_Paz')
    ->onFailure(function ($output) {
        Log::error('El comando inventory:check-alerts falló: '.$output);
    });

Schedule::command('pedidos:expirar')
    ->hourly()
    ->timezone('America/La_Paz')
    ->onFailure(function ($output) {
        Log::error('El comando pedidos:expirar falló: '.$output);
    });
