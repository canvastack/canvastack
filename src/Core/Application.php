<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Core;

/**
 * Application Core.
 *
 * Central application instance that manages the container and service providers.
 */
class Application extends Container
{
    /**
     * The base path of the application.
     *
     * @var string
     */
    protected string $basePath;

    /**
     * Registered service providers.
     *
     * @var array<ServiceProvider>
     */
    protected array $serviceProviders = [];

    /**
     * Booted service providers.
     *
     * @var array<string, bool>
     */
    protected array $bootedProviders = [];

    /**
     * Indicates if the application has been bootstrapped.
     *
     * @var bool
     */
    protected bool $hasBeenBootstrapped = false;

    /**
     * Create a new application instance.
     *
     * @param string|null $basePath
     */
    public function __construct(?string $basePath = null)
    {
        if ($basePath) {
            $this->setBasePath($basePath);
        }

        $this->registerBaseBindings();
    }

    /**
     * Register base bindings into the container.
     *
     * @return void
     */
    protected function registerBaseBindings(): void
    {
        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance(Container::class, $this);
        $this->instance(Application::class, $this);
    }

    /**
     * Set the globally available instance of the container.
     *
     * @param Container|null $container
     * @return static|null
     */
    public static function setInstance(?Container $container = null): ?static
    {
        return static::$instance = $container;
    }

    /**
     * The globally available instance.
     *
     * @var static|null
     */
    protected static ?Container $instance = null;

    /**
     * Get the globally available instance.
     *
     * @return static
     */
    public static function getInstance(): static
    {
        return static::$instance;
    }

    /**
     * Set the base path for the application.
     *
     * @param string $basePath
     * @return $this
     */
    public function setBasePath(string $basePath): static
    {
        $this->basePath = rtrim($basePath, '\/');

        return $this;
    }

    /**
     * Get the base path of the application.
     *
     * @param string $path
     * @return string
     */
    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Register a service provider.
     *
     * @param ServiceProvider|string $provider
     * @return ServiceProvider
     */
    public function register(ServiceProvider|string $provider): ServiceProvider
    {
        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        $provider->register();

        $this->serviceProviders[] = $provider;

        return $provider;
    }

    /**
     * Boot all registered service providers.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->hasBeenBootstrapped) {
            return;
        }

        foreach ($this->serviceProviders as $provider) {
            $this->bootProvider($provider);
        }

        $this->hasBeenBootstrapped = true;
    }

    /**
     * Boot a service provider.
     *
     * @param ServiceProvider $provider
     * @return void
     */
    protected function bootProvider(ServiceProvider $provider): void
    {
        $class = get_class($provider);

        if (isset($this->bootedProviders[$class])) {
            return;
        }

        $provider->boot();

        $this->bootedProviders[$class] = true;
    }

    /**
     * Get all registered service providers.
     *
     * @return array<ServiceProvider>
     */
    public function getProviders(): array
    {
        return $this->serviceProviders;
    }

    /**
     * Determine if the application has been bootstrapped.
     *
     * @return bool
     */
    public function hasBeenBootstrapped(): bool
    {
        return $this->hasBeenBootstrapped;
    }
}
