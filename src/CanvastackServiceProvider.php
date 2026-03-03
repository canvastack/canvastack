<?php

declare(strict_types=1);

namespace Canvastack\Canvastack;

use Illuminate\Support\ServiceProvider;

/**
 * CanvaStack Service Provider.
 *
 * Main service provider for the CanvaStack package.
 * Registers all package services, bindings, and configurations.
 */
class CanvastackServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Load helper functions
        require_once __DIR__ . '/Support/helpers.php';

        // Merge package configurations
        $this->mergeConfigFrom(
            __DIR__ . '/../config/canvastack.php',
            'canvastack'
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../config/canvastack-ui.php',
            'canvastack-ui'
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../config/canvastack-rbac.php',
            'canvastack-rbac'
        );

        // Register core services
        $this->registerCoreServices();

        // Register cache services
        $this->registerCacheServices();

        // Register theme service provider
        $this->app->register(\Canvastack\Canvastack\Providers\ThemeServiceProvider::class);

        // Register translation service provider
        $this->app->register(\Canvastack\Canvastack\Providers\TranslationServiceProvider::class);

        // Register RBAC service provider
        $this->app->register(\Canvastack\Canvastack\Providers\RbacServiceProvider::class);

        // Register compatibility service provider
        $this->app->register(\Canvastack\Canvastack\Providers\CompatibilityServiceProvider::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish configurations
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/canvastack.php' => config_path('canvastack.php'),
            ], 'canvastack-config');

            $this->publishes([
                __DIR__ . '/../config/canvastack-ui.php' => config_path('canvastack-ui.php'),
            ], 'canvastack-ui-config');

            $this->publishes([
                __DIR__ . '/../config/canvastack-rbac.php' => config_path('canvastack-rbac.php'),
            ], 'canvastack-rbac-config');

            // Publish views
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/canvastack'),
            ], 'canvastack-views');

            // Publish translations
            $this->publishes([
                __DIR__ . '/../resources/lang' => lang_path('vendor/canvastack'),
            ], 'canvastack-lang');

            // Publish assets
            $this->publishes([
                __DIR__ . '/../resources/css' => public_path('vendor/canvastack/css'),
                __DIR__ . '/../resources/js' => public_path('vendor/canvastack/js'),
            ], 'canvastack-assets');

            // Register commands
            $this->commands([
                \Canvastack\Canvastack\Console\Commands\CacheClearCommand::class,
                \Canvastack\Canvastack\Console\Commands\CacheWarmCommand::class,
                \Canvastack\Canvastack\Console\Commands\TranslationRegistryCommand::class,
                \Canvastack\Canvastack\Console\Commands\TranslationMissingCommand::class,
                \Canvastack\Canvastack\Console\Commands\TranslationCacheCommand::class,
                \Canvastack\Canvastack\Console\Commands\TranslationVersionCommand::class,
                \Canvastack\Canvastack\Console\Commands\TranslateCommand::class,
                \Canvastack\Canvastack\Console\Commands\TranslationExportCommand::class,
                \Canvastack\Canvastack\Console\Commands\TranslationImportCommand::class,
                \Canvastack\Canvastack\Console\Commands\TranslationCoverageCommand::class,
                \Canvastack\Canvastack\Console\Commands\MigrateConfigCommand::class,
                \Canvastack\Canvastack\Console\Commands\MigrateViewsCommand::class,
            ]);
        }

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'canvastack');

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'canvastack');

        // Register middleware
        $this->registerMiddleware();

        // Register Blade component namespace
        $this->callAfterResolving('blade.compiler', function ($blade) {
            $blade->componentNamespace('Canvastack\\Canvastack\\View\\Components', 'canvastack');

            // Register anonymous components
            $blade->anonymousComponentPath(__DIR__ . '/../resources/views/components', '');

            // Register translation Blade directives
            \Canvastack\Canvastack\Support\Localization\BladeDirectives::register();

            // Register image optimization Blade directives
            $this->registerImageDirectives($blade);
        });

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }

    /**
     * Register core services.
     *
     * @return void
     */
    protected function registerCoreServices(): void
    {
        // Register container
        $this->app->singleton('canvastack.container', function ($app) {
            return $app;
        });

        // Register repositories
        $this->app->bind(
            \Canvastack\Canvastack\Contracts\RepositoryInterface::class,
            \Canvastack\Canvastack\Repositories\BaseRepository::class
        );

        // Register QueryEncryption for Ajax sync
        $this->app->singleton(
            \Canvastack\Canvastack\Components\Form\Features\Ajax\QueryEncryption::class,
            function ($app) {
                return new \Canvastack\Canvastack\Components\Form\Features\Ajax\QueryEncryption(
                    $app->make('encrypter')
                );
            }
        );

        // Register ImageOptimizer
        $this->app->singleton('canvastack.image', function ($app) {
            return new \Canvastack\Canvastack\Support\Assets\ImageOptimizer();
        });

        // Register LocaleManager
        $this->app->singleton('canvastack.locale', function ($app) {
            return new \Canvastack\Canvastack\Support\Localization\LocaleManager();
        });

        // Register TranslationLoader
        $this->app->singleton('canvastack.translation.loader', function ($app) {
            return new \Canvastack\Canvastack\Support\Localization\TranslationLoader();
        });

        // Also bind the class name for type-hinting
        $this->app->singleton(\Canvastack\Canvastack\Support\Localization\TranslationLoader::class, function ($app) {
            return $app->make('canvastack.translation.loader');
        });

        // Register TranslationRegistry
        $this->app->singleton('canvastack.translation.registry', function ($app) {
            return new \Canvastack\Canvastack\Support\Localization\TranslationRegistry(
                $app->make('canvastack.translation.loader')
            );
        });

        // Register MissingTranslationDetector
        $this->app->singleton('canvastack.translation.detector', function ($app) {
            return new \Canvastack\Canvastack\Support\Localization\MissingTranslationDetector(
                $app->make('canvastack.translation.loader'),
                $app->make('canvastack.locale')
            );
        });

        // Register TranslationFallback
        $this->app->singleton('canvastack.translation.fallback', function ($app) {
            return new \Canvastack\Canvastack\Support\Localization\TranslationFallback(
                $app->make('canvastack.translation.loader'),
                $app->make('canvastack.locale'),
                $app->make('canvastack.translation.detector')
            );
        });

        // Register TranslationCache
        $this->app->singleton('canvastack.translation.cache', function ($app) {
            return new \Canvastack\Canvastack\Support\Localization\TranslationCache(
                $app->make('canvastack.translation.loader')
            );
        });

        // Register TranslationVersion
        $this->app->singleton('canvastack.translation.version', function ($app) {
            return new \Canvastack\Canvastack\Support\Localization\TranslationVersion(
                $app->make('canvastack.translation.loader')
            );
        });

        // Register TranslationManager
        $this->app->singleton('canvastack.translation', function ($app) {
            return new \Canvastack\Canvastack\Support\Localization\TranslationManager(
                $app->make('translator'),
                $app->make('canvastack.translation.cache'),
                $app->make('canvastack.translation.fallback'),
                $app->make('canvastack.locale')
            );
        });
    }

    /**
     * Register cache services.
     *
     * @return void
     */
    protected function registerCacheServices(): void
    {
        // Register CacheManager as singleton
        $this->app->singleton('canvastack.cache', function ($app) {
            return new \Canvastack\Canvastack\Support\Cache\CacheManager();
        });

        // Register ConfigCache as singleton
        $this->app->singleton('canvastack.config.cache', function ($app) {
            return new \Canvastack\Canvastack\Support\Cache\ConfigCache(
                $app->make('canvastack.cache')
            );
        });

        // Alias for easier access
        $this->app->alias('canvastack.cache', \Canvastack\Canvastack\Support\Cache\CacheManager::class);
        $this->app->alias('canvastack.config.cache', \Canvastack\Canvastack\Support\Cache\ConfigCache::class);
    }

    /**
     * Register middleware.
     *
     * @return void
     */
    protected function registerMiddleware(): void
    {
        // Register SetLocale middleware alias
        $this->app['router']->aliasMiddleware('canvastack.locale', \Canvastack\Canvastack\Http\Middleware\SetLocale::class);

        // Add SetLocale middleware to web middleware group
        $this->app['router']->pushMiddlewareToGroup('web', \Canvastack\Canvastack\Http\Middleware\SetLocale::class);
    }

    /**
     * Register image optimization Blade directives.
     *
     * @param \Illuminate\View\Compilers\BladeCompiler $blade
     * @return void
     */
    protected function registerImageDirectives($blade): void
    {
        // @optimizedImage directive
        $blade->directive('optimizedImage', function ($expression) {
            return "<?php echo app('canvastack.image')->generatePicture({$expression}); ?>";
        });

        // @lazyImage directive
        $blade->directive('lazyImage', function ($expression) {
            return "<?php echo app('canvastack.image')->addLazyLoading({$expression}); ?>";
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            'canvastack.container',
            'canvastack.cache',
            'canvastack.config.cache',
            'canvastack.image',
        ];
    }
}
