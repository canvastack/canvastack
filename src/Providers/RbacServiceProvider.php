<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Providers;

use Canvastack\Canvastack\Auth\RBAC\BladeDirectives;
use Illuminate\Support\ServiceProvider;

/**
 * RBAC Service Provider.
 *
 * Registers RBAC services, Blade directives, and middleware.
 */
class RbacServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register RBAC services here if needed
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Validate fine-grained permissions configuration
        $this->validateFineGrainedConfig();

        // Register model observers for automatic cache invalidation
        $this->registerObservers();

        // Register RBAC Blade directives
        $this->callAfterResolving('blade.compiler', function () {
            BladeDirectives::register();
        });
    }

    /**
     * Register model observers for automatic cache invalidation.
     *
     * @return void
     */
    protected function registerObservers(): void
    {
        // Only register observers if fine-grained permissions are enabled
        $config = config('canvastack-rbac.fine_grained', []);
        
        if (!empty($config) && ($config['enabled'] ?? false)) {
            \Canvastack\Canvastack\Models\PermissionRule::observe(
                \Canvastack\Canvastack\Auth\RBAC\Observers\PermissionRuleObserver::class
            );

            \Canvastack\Canvastack\Models\UserPermissionOverride::observe(
                \Canvastack\Canvastack\Auth\RBAC\Observers\UserPermissionOverrideObserver::class
            );
        }
    }

    /**
     * Validate fine-grained permissions configuration.
     *
     * @return void
     * @throws \InvalidArgumentException If configuration is invalid
     */
    protected function validateFineGrainedConfig(): void
    {
        $config = config('canvastack-rbac.fine_grained', []);

        // Only validate if fine-grained permissions are enabled
        if (!empty($config) && ($config['enabled'] ?? false)) {
            $validator = new \Canvastack\Canvastack\Auth\RBAC\ConfigValidator();
            $validator->validate($config);
        }
    }
}
