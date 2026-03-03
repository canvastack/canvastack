<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Localization;

use Illuminate\Support\Facades\App;
use Illuminate\Translation\Translator;

/**
 * Translation Manager.
 *
 * Provides a unified interface for translation operations with caching,
 * fallback, and context-aware translation support.
 */
class TranslationManager
{
    /**
     * The translator instance.
     *
     * @var Translator
     */
    protected Translator $translator;

    /**
     * The translation cache instance.
     *
     * @var TranslationCache
     */
    protected TranslationCache $cache;

    /**
     * The translation fallback instance.
     *
     * @var TranslationFallback
     */
    protected TranslationFallback $fallback;

    /**
     * The locale manager instance.
     *
     * @var LocaleManager
     */
    protected LocaleManager $localeManager;

    /**
     * Create a new translation manager instance.
     *
     * @param  Translator  $translator
     * @param  TranslationCache  $cache
     * @param  TranslationFallback  $fallback
     * @param  LocaleManager  $localeManager
     */
    public function __construct(
        Translator $translator,
        TranslationCache $cache,
        TranslationFallback $fallback,
        LocaleManager $localeManager
    ) {
        $this->translator = $translator;
        $this->cache = $cache;
        $this->fallback = $fallback;
        $this->localeManager = $localeManager;
    }

    /**
     * Get a translation.
     *
     * @param  string  $key
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string
     */
    public function get(string $key, array $replace = [], ?string $locale = null): string
    {
        return $this->translator->get($key, $replace, $locale);
    }

    /**
     * Get a translation with pluralization.
     *
     * @param  string  $key
     * @param  int|array<string, mixed>  $count
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string
     */
    public function choice(string $key, $count, array $replace = [], ?string $locale = null): string
    {
        $number = is_array($count) ? ($count['count'] ?? 0) : $count;
        $replace = array_merge(['count' => $number], $replace);

        return $this->translator->choice($key, $number, $replace, $locale);
    }

    /**
     * Get a translation with fallback to default value.
     *
     * @param  string  $key
     * @param  string  $default
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string
     */
    public function fallback(string $key, string $default, array $replace = [], ?string $locale = null): string
    {
        $translation = $this->get($key, $replace, $locale);

        return $translation === $key ? $default : $translation;
    }

    /**
     * Get a translation only if it exists.
     *
     * @param  string  $key
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string|null
     */
    public function ifExists(string $key, array $replace = [], ?string $locale = null): ?string
    {
        $translation = $this->get($key, $replace, $locale);

        return $translation === $key ? null : $translation;
    }

    /**
     * Get a translation with context.
     *
     * @param  string  $key
     * @param  string  $context
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string
     */
    public function withContext(string $key, string $context, array $replace = [], ?string $locale = null): string
    {
        $contextKey = "{$context}.{$key}";
        $translation = $this->get($contextKey, $replace, $locale);

        if ($translation === $contextKey) {
            return $this->get($key, $replace, $locale);
        }

        return $translation;
    }

    /**
     * Get a component translation.
     *
     * @param  string  $component
     * @param  string  $key
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string
     */
    public function component(string $component, string $key, array $replace = [], ?string $locale = null): string
    {
        return $this->get("canvastack::components.{$component}.{$key}", $replace, $locale);
    }

    /**
     * Get a UI translation.
     *
     * @param  string  $key
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string
     */
    public function ui(string $key, array $replace = [], ?string $locale = null): string
    {
        return $this->get("canvastack::ui.{$key}", $replace, $locale);
    }

    /**
     * Get a validation translation.
     *
     * @param  string  $key
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string
     */
    public function validation(string $key, array $replace = [], ?string $locale = null): string
    {
        return $this->get("canvastack::validation.{$key}", $replace, $locale);
    }

    /**
     * Get an error translation.
     *
     * @param  string  $key
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string
     */
    public function error(string $key, array $replace = [], ?string $locale = null): string
    {
        return $this->get("canvastack::errors.{$key}", $replace, $locale);
    }

    /**
     * Get a cached translation.
     *
     * @param  string  $key
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string
     */
    public function cached(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->getLocale();

        return $this->cache->get($locale, $key, $replace);
    }

    /**
     * Check if a translation exists.
     *
     * @param  string  $key
     * @param  string|null  $locale
     * @return bool
     */
    public function has(string $key, ?string $locale = null): bool
    {
        return $this->translator->has($key, $locale);
    }

    /**
     * Get all translations for a locale.
     *
     * @param  string|null  $locale
     * @return array<string, mixed>
     */
    public function all(?string $locale = null): array
    {
        $locale = $locale ?? $this->getLocale();

        return $this->translator->getLoader()->load($locale, '*', '*');
    }

    /**
     * Add a namespace hint.
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace(string $namespace, string $hint): void
    {
        $this->translator->addNamespace($namespace, $hint);
    }

    /**
     * Add a JSON path.
     *
     * @param  string  $path
     * @return void
     */
    public function addJsonPath(string $path): void
    {
        $this->translator->addJsonPath($path);
    }

    /**
     * Get the translator loader.
     *
     * @return \Illuminate\Contracts\Translation\Loader
     */
    public function getLoader()
    {
        return $this->translator->getLoader();
    }

    /**
     * Get the current locale.
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    /**
     * Set the current locale.
     *
     * @param  string  $locale
     * @return void
     */
    public function setLocale(string $locale): void
    {
        $this->translator->setLocale($locale);
        App::setLocale($locale);
    }

    /**
     * Get the fallback locale.
     *
     * @return string
     */
    public function getFallback(): string
    {
        return $this->translator->getFallback();
    }

    /**
     * Set the fallback locale.
     *
     * @param  string  $fallback
     * @return void
     */
    public function setFallback(string $fallback): void
    {
        $this->translator->setFallback($fallback);
    }

    /**
     * Get the locale manager.
     *
     * @return LocaleManager
     */
    public function getLocaleManager(): LocaleManager
    {
        return $this->localeManager;
    }

    /**
     * Get the translation cache.
     *
     * @return TranslationCache
     */
    public function getCache(): TranslationCache
    {
        return $this->cache;
    }

    /**
     * Get the translation fallback.
     *
     * @return TranslationFallback
     */
    public function getFallbackManager(): TranslationFallback
    {
        return $this->fallback;
    }
}
