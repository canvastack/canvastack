<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Providers;

use Canvastack\Canvastack\Support\Theme\ThemeCache;
use Canvastack\Canvastack\Support\Theme\ThemeLoader;
use Canvastack\Canvastack\Support\Theme\ThemeManager;
use Canvastack\Canvastack\Support\Theme\ThemeRepository;
use Canvastack\Canvastack\View\BladeDirectives;
use Illuminate\Support\ServiceProvider;

/**
 * Theme Service Provider.
 *
 * Registers theme-related services, bindings, and configurations.
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
        // Register ThemeRepository as singleton
        $this->app->singleton(ThemeRepository::class, function ($app) {
            return new ThemeRepository();
        });

        // Register ThemeLoader as singleton
        $this->app->singleton(ThemeLoader::class, function ($app) {
            $basePath = config('canvastack-ui.theme.path', resource_path('themes'));

            return new ThemeLoader($basePath);
        });

        // Register ThemeCache as singleton
        $this->app->singleton(ThemeCache::class, function ($app) {
            // Get cache repository from Laravel
            $cacheDriver = config('canvastack-ui.theme.cache.driver', 'array');
            $cache = $app->make('cache')->driver($cacheDriver);
            $ttl = config('canvastack-ui.theme.cache.ttl', 3600);

            return new ThemeCache($cache, $ttl);
        });

        // Register ThemeManager as singleton
        $this->app->singleton('canvastack.theme', function ($app) {
            $manager = new ThemeManager(
                $app->make(ThemeRepository::class),
                $app->make(ThemeLoader::class),
                $app->make(ThemeCache::class)
            );

            // Initialize the theme manager
            $manager->initialize();

            return $manager;
        });

        // Alias for easier access
        $this->app->alias('canvastack.theme', ThemeManager::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Register Blade directives
        BladeDirectives::register();

        // Publish theme assets
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../resources/themes' => resource_path('themes'),
            ], 'canvastack-themes');
        }

        // Enable hot-reload in development
        if (config('app.debug') && config('canvastack-ui.theme.hot_reload', false)) {
            $this->enableHotReload();
        }
    }

    /**
     * Enable hot-reload for theme development.
     *
     * @return void
     */
    protected function enableHotReload(): void
    {
        $manager = $this->app->make('canvastack.theme');

        // Check for changes and reload if needed
        $manager->reloadIfChanged();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            'canvastack.theme',
            ThemeManager::class,
            ThemeRepository::class,
            ThemeLoader::class,
            ThemeCache::class,
        ];
    }
}
