<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use Canvastack\Canvastack\View\Directives\ThemeDirective;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

/**
 * Theme Service Provider.
 *
 * Registers theme services and bindings
 */
class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register theme repository
        $this->app->singleton(ThemeRepository::class, function ($app) {
            return new ThemeRepository();
        });

        // Register theme loader
        $this->app->singleton(ThemeLoader::class, function ($app) {
            $themesPath = config('canvastack-ui.theme.path', resource_path('themes'));

            return new ThemeLoader(
                $app->make(Filesystem::class),
                $themesPath
            );
        });

        // Register theme manager
        $this->app->singleton('canvastack.theme', function ($app) {
            $manager = new ThemeManager(
                $app->make(ThemeRepository::class),
                $app->make(ThemeLoader::class)
            );

            // Set cache if enabled
            if (config('canvastack-ui.theme.cache_enabled', true)) {
                $manager->setCache($app->make('cache.store'));
                $manager->setCacheTtl(config('canvastack-ui.theme.cache_ttl', 3600));
            }

            return $manager;
        });

        // Alias
        $this->app->alias('canvastack.theme', ThemeManager::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Initialize theme manager
        $this->app->make('canvastack.theme')->initialize();

        // Register Blade directives
        ThemeDirective::register();

        // Publish theme configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../../config/canvastack-ui.php' => config_path('canvastack-ui.php'),
            ], 'canvastack-config');

            $this->publishes([
                __DIR__ . '/../../../resources/themes' => resource_path('themes'),
            ], 'canvastack-themes');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            'canvastack.theme',
            ThemeManager::class,
            ThemeRepository::class,
            ThemeLoader::class,
        ];
    }
}
