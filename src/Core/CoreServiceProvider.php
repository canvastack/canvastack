<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Core;

use Canvastack\Canvastack\Contracts\RepositoryInterface;
use Canvastack\Canvastack\Repositories\BaseRepository;

/**
 * Core Service Provider.
 *
 * Registers core services and bindings for the CanvaStack package.
 */
class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register core services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerContainer();
        $this->registerRepositories();
        $this->registerHelpers();
    }

    /**
     * Bootstrap core services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register container bindings.
     *
     * @return void
     */
    protected function registerContainer(): void
    {
        $this->container->singleton('canvastack.container', function ($app) {
            return $app;
        });
    }

    /**
     * Register repository bindings.
     *
     * @return void
     */
    protected function registerRepositories(): void
    {
        $this->container->bind(RepositoryInterface::class, BaseRepository::class);
    }

    /**
     * Register helper functions.
     *
     * @return void
     */
    protected function registerHelpers(): void
    {
        if (file_exists($helpers = __DIR__ . '/../Support/Helpers/helpers.php')) {
            require_once $helpers;
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
            'canvastack.container',
            RepositoryInterface::class,
        ];
    }
}
