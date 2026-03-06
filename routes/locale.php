<?php

declare(strict_types=1);

use Canvastack\Canvastack\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Locale Routes
|--------------------------------------------------------------------------
|
| Routes for locale management and switching.
|
*/

Route::prefix('locale')->name('canvastack.locale.')->group(function () {
    // Switch locale (POST)
    Route::post('/switch', [LocaleController::class, 'switch'])
        ->name('switch');

    // Get current locale (GET)
    Route::get('/current', [LocaleController::class, 'current'])
        ->name('current');

    // Get available locales (GET)
    Route::get('/available', [LocaleController::class, 'available'])
        ->name('available');
});
