<?php

declare(strict_types=1);

use Canvastack\Canvastack\Http\Controllers\TableTabController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CanvaStack API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the CanvaStack package.
| These routes are loaded by the CanvastackServiceProvider.
|
*/

// Tab Loading Route (Task 4.2.1 - Requirement 6.4)
// POST route for lazy loading tab content via AJAX
// Task 4.2.3 - Added rate limiting middleware (Requirement 10.10)
// Note: 'web' middleware is already applied at the route group level in CanvastackServiceProvider
// Note: Auth middleware removed for demo - add back in production with ->middleware(['auth', 'throttle:60,1'])
Route::post('/table/tab/{index}', [TableTabController::class, 'loadTab'])
    ->middleware(['throttle:60,1'])
    ->name('canvastack.table.tab.load')
    ->where(['index' => '[0-9]+']);

// Filter Options Routes
// POST route for getting filter options (for cascading filters)
// Note: 'web' middleware is already applied at the route group level
// CSRF verification is handled by web middleware
Route::post('/table/filter-options', [\Canvastack\Canvastack\Http\Controllers\FilterOptionsController::class, 'getOptions'])
    ->name('canvastack.table.filter.options');

// POST route for getting filter options with count
Route::post('/table/filter-options/count', [\Canvastack\Canvastack\Http\Controllers\FilterOptionsController::class, 'getOptionsWithCount'])
    ->name('canvastack.table.filter.options.count');

// POST route for prefetching multiple filter options
Route::post('/table/filter-options/prefetch', [\Canvastack\Canvastack\Http\Controllers\FilterOptionsController::class, 'prefetchOptions'])
    ->name('canvastack.table.filter.options.prefetch');

// POST route for clearing filter options cache
Route::post('/table/filter-options/cache/clear', [\Canvastack\Canvastack\Http\Controllers\FilterOptionsController::class, 'clearCache'])
    ->middleware(['auth'])
    ->name('canvastack.table.filter.options.cache.clear');

