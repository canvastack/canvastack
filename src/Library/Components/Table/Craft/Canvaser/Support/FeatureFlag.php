<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support;

use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

class FeatureFlag
{
    public static function pipelineEnabled(): bool
    {
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('FeatureFlag: Checking pipeline enabled status', [
                'has_app_function' => function_exists('app')
            ]);
        }

        // Default OFF via config; fallback to env if config helper/container not available
        if (function_exists('app')) {
            try {
                $app = app();
                if ($app && method_exists($app, 'bound') && $app->bound('config')) {
                    return (bool) config('canvastack.datatables.pipeline_enabled', false);
                }
            } catch (\Throwable $e) {
            }
        }
        $env = getenv('CANVASTACK_DT_ENABLED');

        return filter_var($env, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
    }

    public static function mode(): string
    {
        if (function_exists('app')) {
            try {
                $app = app();
                if ($app && method_exists($app, 'bound') && $app->bound('config')) {
                    return (string) config('canvastack.datatables.mode', 'legacy');
                }
            } catch (\Throwable $e) {
            }
        }

        return getenv('CANVASTACK_DT_MODE') ?: 'legacy';
    }
}
