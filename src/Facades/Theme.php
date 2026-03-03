<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Facades;

use Canvastack\Canvastack\Contracts\ThemeInterface;
use Canvastack\Canvastack\Support\Theme\ThemeManager;
use Illuminate\Support\Facades\Facade;

/**
 * Theme Facade.
 *
 * @method static ThemeManager initialize()
 * @method static ThemeManager loadThemes()
 * @method static ThemeInterface current()
 * @method static ThemeManager setCurrentTheme(string $name)
 * @method static ThemeInterface get(string $name)
 * @method static bool has(string $name)
 * @method static array all()
 * @method static array names()
 * @method static ThemeManager register(ThemeInterface $theme)
 * @method static ThemeManager loadFromFile(string $path)
 * @method static ThemeManager loadFromArray(array $config)
 * @method static array getCssVariables()
 * @method static string generateCss(?string $themeName = null)
 * @method static string getCompiledCss(bool $minify = false)
 * @method static array getTailwindConfig()
 * @method static string getJavaScriptConfig()
 * @method static mixed config(string $key, mixed $default = null)
 * @method static array colors()
 * @method static array fonts()
 * @method static array layout()
 * @method static bool supportsDarkMode()
 * @method static ThemeManager clearCache()
 * @method static ThemeManager reload()
 * @method static array getAllMetadata()
 * @method static string export(string $format = 'json')
 * @method static string injectCss()
 * @method static string injectFonts()
 * @method static string injectComplete()
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
