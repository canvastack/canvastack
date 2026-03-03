<?php

if (!function_exists('canvastack')) {
    /**
     * Get the CanvaStack instance.
     */
    function canvastack(): \Canvastack\Canvastack\Core\Application
    {
        return app('canvastack');
    }
}

if (!function_exists('canvastack_config')) {
    /**
     * Get CanvaStack configuration value.
     */
    function canvastack_config(string $key, mixed $default = null): mixed
    {
        return config("canvastack.{$key}", $default);
    }
}

if (!function_exists('canvastack_asset')) {
    /**
     * Get CanvaStack asset URL.
     */
    function canvastack_asset(string $path): string
    {
        return asset("vendor/canvastack/{$path}");
    }
}

if (!function_exists('canvastack_view')) {
    /**
     * Get CanvaStack view path.
     */
    function canvastack_view(string $view): string
    {
        return "canvastack::{$view}";
    }
}
