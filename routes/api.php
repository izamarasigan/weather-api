<?php

use App\Http\Controllers\Api\WeatherController;
use App\Http\Controllers\Api\WeatherHistoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('weather')->group(function () {
    // "history" has to be registered before the {city} routes or it gets treated as a city name
    Route::get('history', [WeatherHistoryController::class, 'index']);
    Route::get('{city}/cached', [WeatherController::class, 'cached']);
    Route::get('{city}', [WeatherController::class, 'show']);
});
