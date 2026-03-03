<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Theme Facade.
 *
 * @method static \Canvastack\Canvastack\Support\Theme\ThemeManager initialize()
 * @method static \Canvastack\Canvastack\Contracts\ThemeInterface current()
 * @method static \Canvastack\Canvastack\Support\Theme\ThemeManager setCurrentTheme(string $name)
 * @method static \Canvastack\Canvastack\Contracts\ThemeInterface get(string $name)
 * @method static bool has(string $name)
 * @method static array all()
 * @method static array names()
 * @method static \Canvastack\Canvastack\Support\Theme\ThemeManager register(\Canvastack\Canvastack\Contracts\ThemeInterface $theme)
 * @method static array getCssVariables()
 * @method static string generateCss(?string $themeName = null)
 * @method static mixed config(string $key, mixed $default = null)
 * @method static array colors()
 * @method static array fonts()
 * @method static array layout()
 * @method static bool supportsDarkMode()
 * @method static \Canvastack\Canvastack\Support\Theme\ThemeManager clearCache()
 * @method static \Canvastack\Canvastack\Support\Theme\ThemeManager reload()
 * @method static array getAllMetadata()
 * @method static string export(string $format = 'json')
 *
 * @see \Canvastack\Canvastack\Support\Theme\ThemeManager
 */
class Theme extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'canvastack.theme';
    }
}
