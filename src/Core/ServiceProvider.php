<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Core;

/**
 * Base Service Provider.
 *
 * Provides a foundation for registering and bootstrapping services.
 */
abstract class ServiceProvider
{
    /**
     * The container instance.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * Create a new service provider instance.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register services in the container.
     *
     * @return void
     */
    abstract public function register(): void;

    /**
     * Bootstrap services after all providers are registered.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Determine if the provider is deferred.
     *
     * @return bool
     */
    public function isDeferred(): bool
    {
        return false;
    }
}
