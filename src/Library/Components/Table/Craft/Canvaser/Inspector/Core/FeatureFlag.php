<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Inspector\Core;

/**
 * Feature Flag Management for Inspector
 *
 * Handles environment detection and feature flag management for the Inspector module.
 * Provides multiple layers of safety to ensure Inspector only runs in appropriate environments.
 *
 * @version 1.0.0
 */
class FeatureFlag
{
    /**
     * Check if the refactor pipeline is enabled.
     *
     * This controls whether the new refactored modules are active.
     * Default is OFF for safety during refactor process.
     */
    public static function pipelineEnabled(): bool
    {
        // Default OFF via config; fallback to env if config helper/container not available
        if (function_exists('app')) {
            try {
                $app = app();
                if ($app && method_exists($app, 'bound') && $app->bound('config')) {
                    return (bool) config('canvastack.datatables.pipeline_enabled', false);
                }
            } catch (\Throwable $e) {
                // Config not available, fall back to environment
            }
        }

        $env = getenv('CANVASTACK_DT_ENABLED');

        return filter_var($env, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
    }

    /**
     * Get the current datatable mode.
     *
     * Modes:
     * - 'legacy': Use original implementation only
     * - 'hybrid': Use both implementations and compare results
     * - 'refactored': Use refactored implementation only
     */
    public static function mode(): string
    {
        if (function_exists('app')) {
            try {
                $app = app();
                if ($app && method_exists($app, 'bound') && $app->bound('config')) {
                    return (string) config('canvastack.datatables.mode', 'legacy');
                }
            } catch (\Throwable $e) {
                // Config not available, fall back to environment
            }
        }

        return getenv('CANVASTACK_DT_MODE') ?: 'legacy';
    }

    /**
     * Check if Inspector should be enabled.
     *
     * Inspector is enabled when:
     * 1. Application is in local environment, OR
     * 2. Datatable mode is 'hybrid' (for comparison testing)
     * 3. Explicitly enabled via configuration
     */
    public static function inspectorEnabled(): bool
    {
        // Check explicit configuration first
        if (function_exists('app')) {
            try {
                $app = app();
                if ($app && method_exists($app, 'bound') && $app->bound('config')) {
                    $explicitSetting = config('canvastack.datatables.inspector.enabled');
                    if ($explicitSetting !== null) {
                        return (bool) $explicitSetting;
                    }
                }
            } catch (\Throwable $e) {
                // Continue with other checks
            }
        }

        // Check environment-based enabling
        $isLocal = self::isLocalEnvironment();
        $isHybrid = self::mode() === 'hybrid';

        return $isLocal || $isHybrid;
    }

    /**
     * Check if current environment is local/development.
     */
    public static function isLocalEnvironment(): bool
    {
        if (function_exists('app')) {
            try {
                return app()->environment('local', 'development', 'testing');
            } catch (\Throwable $e) {
                // Fall back to environment variable check
            }
        }

        $env = getenv('APP_ENV') ?: 'production';

        return in_array($env, ['local', 'development', 'testing'], true);
    }

    /**
     * Check if debug mode is enabled.
     */
    public static function isDebugMode(): bool
    {
        if (function_exists('app')) {
            try {
                return (bool) config('app.debug', false);
            } catch (\Throwable $e) {
                // Fall back to environment variable
            }
        }

        $debug = getenv('APP_DEBUG');

        return filter_var($debug, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
    }

    /**
     * Get comprehensive environment information.
     */
    public static function getEnvironmentInfo(): array
    {
        return [
            'app_env' => getenv('APP_ENV') ?: 'unknown',
            'app_debug' => self::isDebugMode(),
            'is_local' => self::isLocalEnvironment(),
            'pipeline_enabled' => self::pipelineEnabled(),
            'datatable_mode' => self::mode(),
            'inspector_enabled' => self::inspectorEnabled(),
            'php_version' => PHP_VERSION,
            'laravel_version' => function_exists('app') ? app()->version() : 'unknown',
        ];
    }

    /**
     * Check if current request should be inspected.
     *
     * Additional filters can be applied here to limit inspection
     * to specific routes, users, or conditions.
     */
    public static function shouldInspectRequest(): bool
    {
        if (! self::inspectorEnabled()) {
            return false;
        }

        // Add additional filters here if needed
        // For example: specific routes, user roles, etc.

        return true;
    }
}
