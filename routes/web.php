<?php

use Canvastack\Canvastack\Http\Controllers\Admin\ThemeController;
use Canvastack\Canvastack\Http\Controllers\AjaxSyncController;
use Canvastack\Canvastack\Http\Controllers\LocaleController;
use Canvastack\Canvastack\Http\Controllers\PublicController;
use Canvastack\Canvastack\Http\Controllers\DemoController;
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

// Homepage Route (Default CanvaStack Welcome Page)
Route::get('/', [PublicController::class, 'home'])
    ->middleware(['web'])
    ->name('home');

// About Page Route
Route::get('/about', [PublicController::class, 'about'])
    ->middleware(['web'])
    ->name('about');

// Placeholder routes for navbar (redirect to home)
Route::get('/login', function () {
    return redirect()->route('home');
})->middleware(['web'])->name('login');

Route::get('/register', function () {
    return redirect()->route('home');
})->middleware(['web'])->name('register');

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
    $meta = app(\Canvastack\Canvastack\Library\Components\MetaTags::class);
    $meta->title('Dashboard');
    $meta->description('Admin Dashboard');
    
    $chart = app(\Canvastack\Canvastack\Components\Chart\ChartBuilder::class);
    $chart->setContext('admin');
    $chart->line([
        ['name' => 'Users', 'data' => [10, 20, 30, 40, 50, 60]]
    ], ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']);
    
    return view('canvastack::admin.dashboard', compact('meta', 'chart'));
})->middleware(['web'])->name('admin.dashboard');

// Admin Profile Route (for navbar)
Route::get('/admin/profile', function () {
    $meta = app(\Canvastack\Canvastack\Library\Components\MetaTags::class);
    $meta->title('Profile');
    $meta->description('User Profile');
    
    return view('canvastack::admin.profile', compact('meta'));
})->middleware(['web'])->name('admin.profile');

// Admin Settings Route (for navbar)
Route::get('/admin/settings', function () {
    $meta = app(\Canvastack\Canvastack\Library\Components\MetaTags::class);
    $meta->title('Settings');
    $meta->description('Application Settings');
    
    return view('canvastack::admin.settings', compact('meta'));
})->middleware(['web'])->name('admin.settings');

// Logout Route (for navbar)
Route::post('/logout', function () {
    auth()->logout();

    return redirect('/');
})->middleware(['web'])->name('logout');

// Test Routes (for page/testing)
Route::prefix('page')->name('page.')->middleware(['web'])->group(function () {
    Route::get('/dashboard', [DemoController::class, 'dashboard'])->name('dashboard');
    Route::get('/form-create', [DemoController::class, 'formCreate'])->name('form-create');
    Route::get('/form-edit', [DemoController::class, 'formEdit'])->name('form-edit');
    Route::get('/chart', [DemoController::class, 'chart'])->name('chart');
    Route::get('/table', [DemoController::class, 'table'])->name('table');
    Route::get('/multi-table', [DemoController::class, 'multiTable'])->name('multi-table');
    Route::get('/tanstacktable', [DemoController::class, 'tanstackTable'])->name('tanstacktable');
    Route::get('/tanstack-tabs', [DemoController::class, 'tanstackMultiTableTabs'])->name('tanstack-tabs');
    Route::get('/theme', [DemoController::class, 'theme'])->name('theme');
    Route::get('/i18n', [DemoController::class, 'i18n'])->name('i18n');
});

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

// Tab Loading Route - MOVED TO api.php
// The tab loading route has been moved to routes/api.php
// to use POST method with CSRF protection and proper authentication

// Test Routes for Fixed Columns (Phase 4)
Route::prefix('test/fixed-columns')->name('test.fixed-columns.')->middleware(['web'])->group(function () {
    Route::get('/left', [\Canvastack\Canvastack\Http\Controllers\TestFixedColumnsController::class, 'testLeftFixed'])->name('left');
    Route::get('/right', [\Canvastack\Canvastack\Http\Controllers\TestFixedColumnsController::class, 'testRightFixed'])->name('right');
    Route::get('/both', [\Canvastack\Canvastack\Http\Controllers\TestFixedColumnsController::class, 'testBothFixed'])->name('both');
    Route::get('/none', [\Canvastack\Canvastack\Http\Controllers\TestFixedColumnsController::class, 'testNoFixed'])->name('none');
});
