<?php

namespace Canvastack\Canvastack;

use Canvastack\Canvastack\Controllers\Core\Controller as CoreController;
use Canvastack\Canvastack\Library\Components\Utility\DynamicRouteRegistrar;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class CanvastackServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Define no-op auth route macro for Laravel 10+ when laravel/ui is absent
        \Illuminate\Support\Facades\Route::macro('auth', function () {
        });

        // Proper route loading handled by Laravel (works with/without cache)
        if (file_exists(__DIR__.'/routes/web.php')) {
            $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        }
        
        // Auto-register restore routes for soft delete models
        $this->app->booted(function () {
            DynamicRouteRegistrar::registerRestoreRoutes();
        });

        // View namespace to app's resources/views
        $this->loadViewsFrom(base_path('resources/views'), 'CanvaStack');

        // Load Table Security Config files with merge capability
        $this->mergeConfigFrom(__DIR__.'/Library/Components/Table/config/canvastack-security.php', 'canvastack-security');
        $this->mergeConfigFrom(__DIR__.'/Library/Components/Table/config/security_whitelist.php', 'canvastack-table-security');

        // Facade alias: CanvaStack::...
        AliasLoader::getInstance()->alias('CanvaStack', \Canvastack\Canvastack\Facade\CanvaStack::class);

        // Publishable resources
        $publishPath = __DIR__.'/Publisher/';
        if ($this->app->runningInConsole()) {
            $this->publishes([
                "{$publishPath}database/migrations" => database_path('migrations'),
                "{$publishPath}database/seeders" => database_path('seeders'),
                "{$publishPath}config" => base_path('config'),
                "{$publishPath}routes" => base_path('routes'),
                "{$publishPath}app" => app_path(),
                "{$publishPath}resources/views" => resource_path('views'),
            ], 'CanvaStack');

            $this->publishes([
                "{$publishPath}public" => public_path(),
            ], 'CanvaStack Public Folder');

            // Publish Table Security Configuration Files
            $this->publishes([
                __DIR__.'/Library/Components/Table/config/canvastack-security.php' => config_path('canvastack-security.php'),
                __DIR__.'/Library/Components/Table/config/security_whitelist.php' => config_path('canvastack-table-security.php'),
            ], 'CanvaStack Security Config');

            // Publish Delete Handler Assets
            $this->publishes([
                __DIR__.'/Library/Components/Table/Craft/assets/js/delete-handler.js' => public_path('assets/js/delete-handler.js'),
                __DIR__.'/Library/Components/Table/Craft/assets/css/delete-modal.css' => public_path('assets/css/delete-modal.css'),
            ], 'CanvaStack Delete Assets');
        }

        // Register custom Artisan commands for testing and tooling
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Canvastack\Canvastack\Console\CanvastackTestCommand::class,
                \Canvastack\Canvastack\Console\CanvastackSnapshotValidateCommand::class,
                \Canvastack\Canvastack\Console\CanvastackSnapshotMakeCommand::class,
                \Canvastack\Canvastack\Console\CanvastackPipelineDryRunCommand::class,
                \Canvastack\Canvastack\Console\CanvastackDbCheckCommand::class,
                \Canvastack\Canvastack\Console\CanvastackInspectorSummaryCommand::class,
                \Canvastack\Canvastack\Console\CanvastackPipelineBenchCommand::class,
                \Canvastack\Canvastack\Console\CanvastackRelationBenchCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->app->singleton('CanvaStack', function ($app) {
            return new CoreController();
        });
    }
}
