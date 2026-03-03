<?php

namespace Canvastack\Canvastack\Tests\Concerns;

use Illuminate\Support\Facades\Route;

/**
 * Trait for defining test routes.
 *
 * This trait provides helper methods for registering routes needed in tests.
 */
trait InteractsWithRoutes
{
    /**
     * Register common admin routes for testing.
     *
     * @return void
     */
    protected function registerAdminRoutes(): void
    {
        Route::middleware(['web'])->group(function () {
            // Dashboard
            Route::get('/admin/dashboard', function () {
                return view('canvastack::admin.dashboard');
            })->name('admin.dashboard');

            // Profile
            Route::get('/admin/profile', function () {
                return view('canvastack::admin.profile');
            })->name('admin.profile');

            // Settings
            Route::get('/admin/settings', function () {
                return view('canvastack::admin.settings');
            })->name('admin.settings');

            // Users
            Route::get('/admin/users', function () {
                return view('canvastack::admin.users.index');
            })->name('admin.users.index');

            Route::get('/admin/users/create', function () {
                return view('canvastack::admin.users.create');
            })->name('admin.users.create');

            Route::get('/admin/users/{id}', function ($id) {
                return view('canvastack::admin.users.show', ['id' => $id]);
            })->name('admin.users.show');

            Route::get('/admin/users/{id}/edit', function ($id) {
                return view('canvastack::admin.users.edit', ['id' => $id]);
            })->name('admin.users.edit');

            Route::delete('/admin/users/{id}', function ($id) {
                return response()->json(['success' => true]);
            })->name('admin.users.destroy');
        });
    }

    /**
     * Register authentication routes for testing.
     *
     * @return void
     */
    protected function registerAuthRoutes(): void
    {
        Route::middleware(['web'])->group(function () {
            // Login
            Route::get('/login', function () {
                return view('canvastack::auth.login');
            })->name('login');

            Route::post('/login', function () {
                return redirect()->route('admin.dashboard');
            })->name('login.post');

            // Logout
            Route::post('/logout', function () {
                auth()->logout();

                return redirect('/');
            })->name('logout');

            // Register
            Route::get('/register', function () {
                return view('canvastack::auth.register');
            })->name('register');

            Route::post('/register', function () {
                return redirect()->route('admin.dashboard');
            })->name('register.post');

            // Password Reset
            Route::get('/forgot-password', function () {
                return view('canvastack::auth.forgot-password');
            })->name('password.request');

            Route::post('/forgot-password', function () {
                return back()->with('status', 'Password reset link sent!');
            })->name('password.email');
        });
    }

    /**
     * Register theme routes for testing.
     *
     * @return void
     */
    protected function registerThemeRoutes(): void
    {
        Route::middleware(['web'])->group(function () {
            Route::get('/admin/themes', function () {
                return view('canvastack::admin.themes.index');
            })->name('admin.themes.index');

            Route::get('/admin/themes/{theme}', function ($theme) {
                return view('canvastack::admin.themes.show', ['theme' => $theme]);
            })->name('admin.themes.show');

            Route::post('/admin/themes/{theme}/activate', function ($theme) {
                return redirect()->route('admin.themes.index')
                    ->with('success', "Theme '{$theme}' activated successfully");
            })->name('admin.themes.activate');

            Route::post('/admin/themes/cache/clear', function () {
                return redirect()->route('admin.themes.index')
                    ->with('success', 'Theme cache cleared successfully');
            })->name('admin.themes.cache.clear');

            Route::post('/admin/themes/reload', function () {
                return redirect()->route('admin.themes.index')
                    ->with('success', 'Themes reloaded successfully');
            })->name('admin.themes.reload');

            Route::get('/admin/themes/{theme}/export', function ($theme) {
                return response()->json(['theme' => $theme]);
            })->name('admin.themes.export');

            Route::get('/admin/themes/{theme}/preview', function ($theme) {
                return response()->json(['theme' => $theme]);
            })->name('admin.themes.preview');

            Route::get('/admin/themes/stats', function () {
                return response()->json(['total' => 3, 'active' => 1]);
            })->name('admin.themes.stats');
        });
    }

    /**
     * Register all common test routes.
     *
     * @return void
     */
    protected function registerAllTestRoutes(): void
    {
        $this->registerAdminRoutes();
        $this->registerAuthRoutes();
        $this->registerThemeRoutes();
    }

    /**
     * Assert that a route exists.
     *
     * @param string $name Route name
     * @return void
     */
    protected function assertRouteExists(string $name): void
    {
        $this->assertTrue(
            Route::has($name),
            "Expected route '{$name}' to exist"
        );
    }

    /**
     * Assert that a route does not exist.
     *
     * @param string $name Route name
     * @return void
     */
    protected function assertRouteDoesNotExist(string $name): void
    {
        $this->assertFalse(
            Route::has($name),
            "Expected route '{$name}' to not exist"
        );
    }
}
