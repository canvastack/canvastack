<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Support;

use Canvastack\Canvastack\Support\Integration\UserPreferences;
use Canvastack\Canvastack\Support\Localization\LocaleManager;

/**
 * LocaleIntegration - Manages locale for table components.
 *
 * Provides locale management functionality for table components including:
 * - Locale detection and switching
 * - Locale persistence via UserPreferences
 * - Translation support
 * - RTL support
 *
 * @package Canvastack\Canvastack\Components\Table\Support
 * @version 1.0.0
 */
class LocaleIntegration
{
    /**
     * Locale manager instance.
     */
    protected LocaleManager $localeManager;

    /**
     * User preferences instance.
     */
    protected UserPreferences $userPreferences;

    /**
     * Constructor.
     */
    public function __construct(
        LocaleManager $localeManager,
        UserPreferences $userPreferences
    ) {
        $this->localeManager = $localeManager;
        $this->userPreferences = $userPreferences;
    }

    /**
     * Get current locale.
     */
    public function getLocale(): string
    {
        return $this->localeManager->getLocale();
    }

    /**
     * Set locale and persist preference.
     */
    public function setLocale(string $locale): bool
    {
        // Validate locale is available
        if (!$this->localeManager->isAvailable($locale)) {
            return false;
        }

        // Set locale in LocaleManager
        $result = $this->localeManager->setLocale($locale);

        if ($result) {
            // Persist to user preferences
            $this->userPreferences->setLocale($locale);
        }

        return $result;
    }

    /**
     * Get available locales.
     */
    public function getAvailableLocales(): array
    {
        return $this->localeManager->getAvailableLocales();
    }

    /**
     * Check if locale is available.
     */
    public function isAvailable(string $locale): bool
    {
        return $this->localeManager->isAvailable($locale);
    }

    /**
     * Get locale information.
     */
    public function getLocaleInfo(?string $locale = null): ?array
    {
        return $this->localeManager->getLocaleInfo($locale);
    }

    /**
     * Get locale name.
     */
    public function getLocaleName(?string $locale = null): ?string
    {
        return $this->localeManager->getLocaleName($locale);
    }

    /**
     * Get locale native name.
     */
    public function getLocaleNativeName(?string $locale = null): ?string
    {
        return $this->localeManager->getLocaleNativeName($locale);
    }

    /**
     * Get locale flag emoji.
     */
    public function getLocaleFlag(?string $locale = null): ?string
    {
        return $this->localeManager->getLocaleFlag($locale);
    }

    /**
     * Check if current locale is RTL.
     */
    public function isRtl(?string $locale = null): bool
    {
        return $this->localeManager->isRtl($locale);
    }

    /**
     * Get text direction for current locale.
     */
    public function getDirection(?string $locale = null): string
    {
        return $this->localeManager->getDirection($locale);
    }

    /**
     * Get user's preferred locale from preferences.
     */
    public function getUserPreferredLocale(): ?string
    {
        return $this->userPreferences->getLocale();
    }

    /**
     * Initialize locale from user preferences.
     *
     * This should be called during application bootstrap to load
     * the user's preferred locale.
     */
    public function initializeFromPreferences(): void
    {
        $preferredLocale = $this->getUserPreferredLocale();

        if ($preferredLocale && $this->isAvailable($preferredLocale)) {
            $this->localeManager->setLocale($preferredLocale);
        }
    }

    /**
     * Get locale data for JavaScript/Alpine.js.
     *
     * Returns locale information that can be passed to frontend components.
     */
    public function getLocaleDataForJs(): array
    {
        $currentLocale = $this->getLocale();

        return [
            'current' => $currentLocale,
            'direction' => $this->getDirection(),
            'isRtl' => $this->isRtl(),
            'name' => $this->getLocaleName(),
            'nativeName' => $this->getLocaleNativeName(),
            'flag' => $this->getLocaleFlag(),
            'available' => $this->getAvailableLocales(),
        ];
    }

    /**
     * Get HTML attributes for locale support.
     *
     * Returns lang and dir attributes for HTML elements.
     */
    public function getHtmlAttributes(): array
    {
        return [
            'lang' => $this->getLocale(),
            'dir' => $this->getDirection(),
        ];
    }

    /**
     * Clear locale cache.
     */
    public function clearCache(): void
    {
        $this->localeManager->clearCache();
    }
}
