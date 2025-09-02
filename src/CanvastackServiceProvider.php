<?php

namespace Canvastack\Canvastack;

use Canvastack\Canvastack\Controllers\Core\Controller as CoreController;
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

        // View namespace to app's resources/views
        $this->loadViewsFrom(base_path('resources/views'), 'CanvaStack');

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
