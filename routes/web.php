<?php

use Canvastack\Canvastack\Http\Controllers\Admin\ThemeController;
use Canvastack\Canvastack\Http\Controllers\AjaxSyncController;
use Canvastack\Canvastack\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CanvaStack Web Routes
|--------------------------------------------------------------------------
|
| Here are the web routes for the CanvaStack package.
| These routes are loaded by the CanvastackServiceProvider.
|
*/

// Ajax Sync endpoint for cascading dropdowns
Route::post('/canvastack/ajax/sync', [AjaxSyncController::class, 'handle'])
    ->middleware(['web'])
    ->name('canvastack.ajax.sync');

// Locale Switching Route
Route::post('/locale/switch', [LocaleController::class, 'switch'])
    ->middleware(['web'])
    ->name('locale.switch');

// DataTables AJAX endpoint for server-side processing
Route::match(['get', 'post'], '/datatable/data', [\Canvastack\Canvastack\Http\Controllers\DataTableController::class, 'getData'])
    ->middleware(['web'])
    ->name('datatable.data');

// TanStack Table AJAX endpoint for server-side processing
Route::match(['get', 'post'], '/canvastack/table/data', [\Canvastack\Canvastack\Http\Controllers\DataTableController::class, 'getTanStackData'])
    ->middleware(['web'])
    ->name('canvastack.table.data');

// DataTables Filter Endpoints
Route::post('/datatable/filter-options', [\Canvastack\Canvastack\Http\Controllers\DataTableController::class, 'getFilterOptions'])
    ->middleware(['web'])
    ->name('datatable.filter-options');

Route::post('/datatable/save-filters', [\Canvastack\Canvastack\Http\Controllers\DataTableController::class, 'saveFilters'])
    ->middleware(['web'])
    ->name('datatable.save-filters');

Route::post('/datatable/get-filters', [\Canvastack\Canvastack\Http\Controllers\DataTableController::class, 'getFilters'])
    ->middleware(['web'])
    ->name('datatable.get-filters');

Route::post('/datatable/save-display-limit', [\Canvastack\Canvastack\Http\Controllers\DataTableController::class, 'saveDisplayLimit'])
    ->middleware(['web'])
    ->name('datatable.save-display-limit');

// DataTables Cache Management Endpoints
Route::post('/datatable/clear-filter-cache', [\Canvastack\Canvastack\Http\Controllers\DataTableController::class, 'clearFilterCache'])
    ->middleware(['web'])
    ->name('datatable.clear-filter-cache');

Route::post('/datatable/warm-filter-cache', [\Canvastack\Canvastack\Http\Controllers\DataTableController::class, 'warmFilterCache'])
    ->middleware(['web'])
    ->name('datatable.warm-filter-cache');

// Admin Dashboard Route (for testing/navigation)
Route::get('/admin/dashboard', function () {
    return view('canvastack::admin.dashboard');
})->middleware(['web'])->name('admin.dashboard');

// Admin Profile Route (for navbar)
Route::get('/admin/profile', function () {
    return view('canvastack::admin.profile');
})->middleware(['web'])->name('admin.profile');

// Admin Settings Route (for navbar)
Route::get('/admin/settings', function () {
    return view('canvastack::admin.settings');
})->middleware(['web'])->name('admin.settings');

// Logout Route (for navbar)
Route::post('/logout', function () {
    auth()->logout();

    return redirect('/');
})->middleware(['web'])->name('logout');

// Admin Theme Management Routes
Route::prefix('admin/themes')->name('admin.themes.')->middleware(['web'])->group(function () {
    Route::get('/', [ThemeController::class, 'index'])->name('index');
    Route::get('/{theme}', [ThemeController::class, 'show'])->name('show');
    Route::post('/{theme}/activate', [ThemeController::class, 'activate'])->name('activate');
    Route::post('/clear-cache', [ThemeController::class, 'clearCache'])->name('clear-cache');
    Route::post('/reload', [ThemeController::class, 'reload'])->name('reload');
    Route::get('/{theme}/export/{format}', [ThemeController::class, 'export'])->name('export');
    Route::get('/{theme}/preview', [ThemeController::class, 'preview'])->name('preview');
    Route::get('/stats/all', [ThemeController::class, 'stats'])->name('stats');
});

// Admin Locale Management Routes
Route::prefix('admin/locales')->name('admin.locales.')->middleware(['web'])->group(function () {
    Route::get('/', [\Canvastack\Canvastack\Http\Controllers\Admin\LocaleController::class, 'index'])->name('index');
});

// Test Routes for Fixed Columns (Phase 4)
Route::prefix('test/fixed-columns')->name('test.fixed-columns.')->middleware(['web'])->group(function () {
    Route::get('/left', [\Canvastack\Canvastack\Http\Controllers\TestFixedColumnsController::class, 'testLeftFixed'])->name('left');
    Route::get('/right', [\Canvastack\Canvastack\Http\Controllers\TestFixedColumnsController::class, 'testRightFixed'])->name('right');
    Route::get('/both', [\Canvastack\Canvastack\Http\Controllers\TestFixedColumnsController::class, 'testBothFixed'])->name('both');
    Route::get('/none', [\Canvastack\Canvastack\Http\Controllers\TestFixedColumnsController::class, 'testNoFixed'])->name('none');
});
