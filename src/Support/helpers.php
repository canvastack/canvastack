<?php

declare(strict_types=1);

use Canvastack\Canvastack\Contracts\ThemeInterface;
use Canvastack\Canvastack\Support\Theme\ThemeManager;

if (!function_exists('theme')) {
    /**
     * Get the theme manager instance or a theme configuration value.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed|ThemeManager
     */
    function theme(?string $key = null, mixed $default = null): mixed
    {
        $manager = app('canvastack.theme');

        if ($key === null) {
            return $manager;
        }

        return $manager->config($key, $default);
    }
}

if (!function_exists('current_theme')) {
    /**
     * Get the current active theme.
     *
     * @return ThemeInterface
     */
    function current_theme(): ThemeInterface
    {
        return app('canvastack.theme')->current();
    }
}

if (!function_exists('theme_color')) {
    /**
     * Get a color value from the current theme.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function theme_color(string $key, mixed $default = null): mixed
    {
        $colors = app('canvastack.theme')->colors();

        return data_get($colors, $key, $default);
    }
}

if (!function_exists('theme_font')) {
    /**
     * Get a font value from the current theme.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function theme_font(string $key, mixed $default = null): mixed
    {
        $fonts = app('canvastack.theme')->fonts();

        return data_get($fonts, $key, $default);
    }
}

if (!function_exists('theme_css')) {
    /**
     * Get compiled CSS for the current theme.
     *
     * @param bool $minify
     * @return string
     */
    function theme_css(bool $minify = false): string
    {
        return app('canvastack.theme')->getCompiledCss($minify);
    }
}

if (!function_exists('theme_inject')) {
    /**
     * Inject complete theme (CSS + fonts + JS).
     *
     * @return string
     */
    function theme_inject(): string
    {
        return app('canvastack.theme')->injectComplete();
    }
}

if (!function_exists('canvas_config')) {
    /**
     * Get a CanvaStack configuration value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function canvas_config(string $key, mixed $default = null): mixed
    {
        return config("canvastack.{$key}", $default);
    }
}
