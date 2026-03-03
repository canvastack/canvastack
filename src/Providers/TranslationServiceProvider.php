<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Providers;

use Canvastack\Canvastack\Support\Localization\BladeDirectives;
use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Support\Localization\MissingTranslationDetector;
use Canvastack\Canvastack\Support\Localization\TranslationCache;
use Canvastack\Canvastack\Support\Localization\TranslationFallback;
use Canvastack\Canvastack\Support\Localization\TranslationLoader;
use Canvastack\Canvastack\Support\Localization\TranslationManager;
use Canvastack\Canvastack\Support\Localization\TranslationRegistry;
use Canvastack\Canvastack\Support\Localization\TranslationVersion;
use Illuminate\Support\ServiceProvider;

/**
 * Translation Service Provider.
 *
 * Registers all translation-related services, bindings, and configurations.
 */
class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Load translation helper functions
        require_once __DIR__ . '/../Support/Localization/helpers.php';

        // Register LocaleManager
        $this->app->singleton('canvastack.locale', function ($app) {
            return new LocaleManager();
        });

        // Register TranslationLoader
        $this->app->singleton('canvastack.translation.loader', function ($app) {
            return new TranslationLoader();
        });

        // Register TranslationRegistry
        $this->app->singleton('canvastack.translation.registry', function ($app) {
            return new TranslationRegistry(
                $app->make('canvastack.translation.loader')
            );
        });

        // Register MissingTranslationDetector
        $this->app->singleton('canvastack.translation.detector', function ($app) {
            return new MissingTranslationDetector(
                $app->make('canvastack.translation.loader'),
                $app->make('canvastack.locale')
            );
        });

        // Register TranslationFallback
        $this->app->singleton('canvastack.translation.fallback', function ($app) {
            return new TranslationFallback(
                $app->make('canvastack.translation.loader'),
                $app->make('canvastack.locale'),
                $app->make('canvastack.translation.detector')
            );
        });

        // Register TranslationCache
        $this->app->singleton('canvastack.translation.cache', function ($app) {
            return new TranslationCache(
                $app->make('canvastack.translation.loader')
            );
        });

        // Register TranslationVersion
        $this->app->singleton('canvastack.translation.version', function ($app) {
            return new TranslationVersion(
                $app->make('canvastack.translation.loader')
            );
        });

        // Register TranslationManager
        $this->app->singleton('canvastack.translation', function ($app) {
            return new TranslationManager(
                $app->make('translator'),
                $app->make('canvastack.translation.cache'),
                $app->make('canvastack.translation.fallback'),
                $app->make('canvastack.locale')
            );
        });

        // Register aliases
        $this->app->alias('canvastack.translation', TranslationManager::class);
        $this->app->alias('canvastack.locale', LocaleManager::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Register Blade directives
        $this->callAfterResolving('blade.compiler', function () {
            BladeDirectives::register();
        });

        // Register translation event listeners
        $this->registerEventListeners();

        // Publish translation files
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../resources/lang' => lang_path('vendor/canvastack'),
            ], 'canvastack-translations');
        }
    }

    /**
     * Register translation event listeners.
     *
     * @return void
     */
    protected function registerEventListeners(): void
    {
        // Listen for locale changes
        $this->app['events']->listen(
            \Canvastack\Canvastack\Events\Translation\LocaleChanged::class,
            function ($event) {
                // Clear translation cache when locale changes
                $this->app->make('canvastack.translation.cache')->clear();

                // Fire translation loaded event
                $this->app['events']->dispatch(
                    new \Canvastack\Canvastack\Events\Translation\TranslationLoaded($event->locale)
                );
            }
        );

        // Listen for translation missing events
        $this->app['events']->listen(
            \Canvastack\Canvastack\Events\Translation\TranslationMissing::class,
            function ($event) {
                // Log missing translation
                $detector = $this->app->make('canvastack.translation.detector');
                $detector->logMissing($event->key, $event->locale);
            }
        );

        // Listen for translation cache cleared events
        $this->app['events']->listen(
            \Canvastack\Canvastack\Events\Translation\TranslationCacheCleared::class,
            function ($event) {
                // Log cache clear
                if ($event->locale) {
                    \Illuminate\Support\Facades\Log::info("Translation cache cleared for locale: {$event->locale}");
                } else {
                    \Illuminate\Support\Facades\Log::info('Translation cache cleared for all locales');
                }
            }
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            'canvastack.locale',
            'canvastack.translation',
            'canvastack.translation.loader',
            'canvastack.translation.registry',
            'canvastack.translation.detector',
            'canvastack.translation.fallback',
            'canvastack.translation.cache',
            'canvastack.translation.version',
        ];
    }
}
