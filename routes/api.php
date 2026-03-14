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
// Task 4.2.2 - Added authentication middleware (Requirement 10.6)
// Task 4.2.3 - Added rate limiting middleware (Requirement 10.10)
// Note: 'web' middleware is already applied at the route group level in CanvastackServiceProvider
Route::post('/table/tab/{index}', [TableTabController::class, 'loadTab'])
    ->middleware(['auth', 'throttle:60,1'])
    ->name('canvastack.table.tab.load')
    ->where(['index' => '[0-9]+']);

