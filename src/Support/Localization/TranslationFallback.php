<?php

namespace Canvastack\Canvastack\Support\Localization;

use Illuminate\Support\Facades\Config;

/**
 * TranslationFallback.
 *
 * Provides intelligent fallback mechanism for missing translations.
 * Supports multiple fallback strategies and locale chains.
 */
class TranslationFallback
{
    /**
     * Translation loader.
     *
     * @var TranslationLoader
     */
    protected TranslationLoader $loader;

    /**
     * Locale manager.
     *
     * @var LocaleManager
     */
    protected LocaleManager $localeManager;

    /**
     * Missing translation detector.
     *
     * @var MissingTranslationDetector|null
     */
    protected ?MissingTranslationDetector $detector;

    /**
     * Fallback chain.
     *
     * @var array<string, array<string>>
     */
    protected array $fallbackChain = [];

    /**
     * Fallback strategy.
     *
     * @var string
     */
    protected string $strategy;

    /**
     * Constructor.
     */
    public function __construct(
        TranslationLoader $loader,
        LocaleManager $localeManager,
        ?MissingTranslationDetector $detector = null
    ) {
        $this->loader = $loader;
        $this->localeManager = $localeManager;
        $this->detector = $detector;
        $this->strategy = Config::get('canvastack.localization.fallback_strategy', 'chain');
        $this->loadFallbackChain();
    }

    /**
     * Load fallback chain from configuration.
     *
     * @return void
     */
    protected function loadFallbackChain(): void
    {
        $this->fallbackChain = Config::get('canvastack.localization.fallback_chain', [
            'id' => ['en'],
            'en' => [],
        ]);
    }

    /**
     * Get translation with fallback.
     *
     * @param  string  $key
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string|array<string, mixed>
     */
    public function get(string $key, array $replace = [], ?string $locale = null): string|array
    {
        $locale = $locale ?? $this->localeManager->getLocale();

        // Try primary locale
        if ($this->loader->has($locale, $key)) {
            return $this->loader->get($locale, $key, $replace);
        }

        // Detect missing translation
        if ($this->detector) {
            $this->detector->detect($key, $locale);
        }

        // Apply fallback strategy
        return match ($this->strategy) {
            'chain' => $this->fallbackChain($key, $locale, $replace),
            'default' => $this->fallbackDefault($key, $replace),
            'key' => $this->fallbackKey($key),
            'empty' => '',
            default => $this->fallbackChain($key, $locale, $replace),
        };
    }

    /**
     * Fallback using locale chain.
     *
     * @param  string  $key
     * @param  string  $locale
     * @param  array<string, mixed>  $replace
     * @return string|array<string, mixed>
     */
    protected function fallbackChain(string $key, string $locale, array $replace = []): string|array
    {
        $chain = $this->getFallbackChain($locale);

        foreach ($chain as $fallbackLocale) {
            if ($this->loader->has($fallbackLocale, $key)) {
                return $this->loader->get($fallbackLocale, $key, $replace);
            }
        }

        // Final fallback to default locale
        return $this->fallbackDefault($key, $replace);
    }

    /**
     * Fallback to default locale.
     *
     * @param  string  $key
     * @param  array<string, mixed>  $replace
     * @return string|array<string, mixed>
     */
    protected function fallbackDefault(string $key, array $replace = []): string|array
    {
        $defaultLocale = $this->localeManager->getDefaultLocale();

        if ($this->loader->has($defaultLocale, $key)) {
            return $this->loader->get($defaultLocale, $key, $replace);
        }

        return $this->fallbackKey($key);
    }

    /**
     * Fallback to key itself.
     *
     * @param  string  $key
     * @return string
     */
    protected function fallbackKey(string $key): string
    {
        return $key;
    }

    /**
     * Get fallback chain for a locale.
     *
     * @param  string  $locale
     * @return array<string>
     */
    public function getFallbackChain(string $locale): array
    {
        if (isset($this->fallbackChain[$locale])) {
            return $this->fallbackChain[$locale];
        }

        // Default fallback to application fallback locale
        $fallbackLocale = $this->localeManager->getFallbackLocale();

        return $locale !== $fallbackLocale ? [$fallbackLocale] : [];
    }

    /**
     * Set fallback chain for a locale.
     *
     * @param  string  $locale
     * @param  array<string>  $chain
     * @return void
     */
    public function setFallbackChain(string $locale, array $chain): void
    {
        $this->fallbackChain[$locale] = $chain;
    }

    /**
     * Add fallback locale to chain.
     *
     * @param  string  $locale
     * @param  string  $fallbackLocale
     * @return void
     */
    public function addFallback(string $locale, string $fallbackLocale): void
    {
        if (!isset($this->fallbackChain[$locale])) {
            $this->fallbackChain[$locale] = [];
        }

        if (!in_array($fallbackLocale, $this->fallbackChain[$locale])) {
            $this->fallbackChain[$locale][] = $fallbackLocale;
        }
    }

    /**
     * Remove fallback locale from chain.
     *
     * @param  string  $locale
     * @param  string  $fallbackLocale
     * @return void
     */
    public function removeFallback(string $locale, string $fallbackLocale): void
    {
        if (isset($this->fallbackChain[$locale])) {
            $this->fallbackChain[$locale] = array_filter(
                $this->fallbackChain[$locale],
                fn ($l) => $l !== $fallbackLocale
            );
        }
    }

    /**
     * Get fallback strategy.
     *
     * @return string
     */
    public function getStrategy(): string
    {
        return $this->strategy;
    }

    /**
     * Set fallback strategy.
     *
     * @param  string  $strategy
     * @return void
     */
    public function setStrategy(string $strategy): void
    {
        $this->strategy = $strategy;
    }

    /**
     * Check if translation exists (including fallbacks).
     *
     * @param  string  $key
     * @param  string|null  $locale
     * @return bool
     */
    public function has(string $key, ?string $locale = null): bool
    {
        $locale = $locale ?? $this->localeManager->getLocale();

        // Check primary locale
        if ($this->loader->has($locale, $key)) {
            return true;
        }

        // Check fallback chain
        $chain = $this->getFallbackChain($locale);
        foreach ($chain as $fallbackLocale) {
            if ($this->loader->has($fallbackLocale, $key)) {
                return true;
            }
        }

        // Check default locale
        $defaultLocale = $this->localeManager->getDefaultLocale();
        if ($locale !== $defaultLocale && $this->loader->has($defaultLocale, $key)) {
            return true;
        }

        return false;
    }

    /**
     * Get translation with specific fallback locale.
     *
     * @param  string  $key
     * @param  string  $fallbackLocale
     * @param  array<string, mixed>  $replace
     * @param  string|null  $locale
     * @return string|array<string, mixed>
     */
    public function getWithFallback(
        string $key,
        string $fallbackLocale,
        array $replace = [],
        ?string $locale = null
    ): string|array {
        $locale = $locale ?? $this->localeManager->getLocale();

        // Try primary locale
        if ($this->loader->has($locale, $key)) {
            return $this->loader->get($locale, $key, $replace);
        }

        // Try specific fallback
        if ($this->loader->has($fallbackLocale, $key)) {
            return $this->loader->get($fallbackLocale, $key, $replace);
        }

        return $this->fallbackKey($key);
    }

    /**
     * Get all fallback chains.
     *
     * @return array<string, array<string>>
     */
    public function getAllChains(): array
    {
        return $this->fallbackChain;
    }

    /**
     * Clear fallback chains.
     *
     * @return void
     */
    public function clearChains(): void
    {
        $this->fallbackChain = [];
    }

    /**
     * Get translation source (which locale provided the translation).
     *
     * @param  string  $key
     * @param  string|null  $locale
     * @return string|null
     */
    public function getSource(string $key, ?string $locale = null): ?string
    {
        $locale = $locale ?? $this->localeManager->getLocale();

        // Check primary locale
        if ($this->loader->has($locale, $key)) {
            return $locale;
        }

        // Check fallback chain
        $chain = $this->getFallbackChain($locale);
        foreach ($chain as $fallbackLocale) {
            if ($this->loader->has($fallbackLocale, $key)) {
                return $fallbackLocale;
            }
        }

        // Check default locale
        $defaultLocale = $this->localeManager->getDefaultLocale();
        if ($locale !== $defaultLocale && $this->loader->has($defaultLocale, $key)) {
            return $defaultLocale;
        }

        return null;
    }
}
