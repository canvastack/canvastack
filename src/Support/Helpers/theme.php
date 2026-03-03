<?php

declare(strict_types=1);

use Canvastack\Canvastack\Support\Theme\ThemeManager;

if (!function_exists('theme')) {
    /**
     * Get the theme manager instance or a configuration value.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    function theme(?string $key = null, mixed $default = null): mixed
    {
        $manager = app(ThemeManager::class);

        if ($key === null) {
            return $manager;
        }

        return $manager->config($key, $default);
    }
}
