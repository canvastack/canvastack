<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Translation Facade.
 *
 * Provides a convenient static interface to the translation system.
 *
 * @method static string get(string $key, array $replace = [], string|null $locale = null)
 * @method static string choice(string $key, int|array $count, array $replace = [], string|null $locale = null)
 * @method static string fallback(string $key, string $default, array $replace = [], string|null $locale = null)
 * @method static string|null ifExists(string $key, array $replace = [], string|null $locale = null)
 * @method static string withContext(string $key, string $context, array $replace = [], string|null $locale = null)
 * @method static string component(string $component, string $key, array $replace = [], string|null $locale = null)
 * @method static string ui(string $key, array $replace = [], string|null $locale = null)
 * @method static string validation(string $key, array $replace = [], string|null $locale = null)
 * @method static string error(string $key, array $replace = [], string|null $locale = null)
 * @method static string cached(string $key, array $replace = [], string|null $locale = null)
 * @method static bool has(string $key, string|null $locale = null)
 * @method static array all(string|null $locale = null)
 * @method static void addNamespace(string $namespace, string $hint)
 * @method static void addJsonPath(string $path)
 * @method static array getLoader()
 * @method static string getLocale()
 * @method static void setLocale(string $locale)
 * @method static string getFallback()
 * @method static void setFallback(string $fallback)
 *
 * @see \Canvastack\Canvastack\Support\Localization\TranslationManager
 */
class Translation extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'canvastack.translation';
    }
}
