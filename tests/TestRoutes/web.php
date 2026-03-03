<?php

use Illuminate\Support\Facades\Route;
use Canvastack\Canvastack\Tests\TestControllers\FilterActionsTestController;

/*
|--------------------------------------------------------------------------
| Test Routes
|--------------------------------------------------------------------------
|
| These routes are used for testing filter actions functionality.
|
*/

Route::get('/test/filter-modal', [FilterActionsTestController::class, 'filterModal']);
Route::get('/test/filter-modal-autosubmit', [FilterActionsTestController::class, 'filterModalAutoSubmit']);
Route::post('/datatable/filter-options', [FilterActionsTestController::class, 'getFilterOptions']);
Route::post('/datatable/save-filters', [FilterActionsTestController::class, 'saveFilters']);