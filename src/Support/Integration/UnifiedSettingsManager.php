<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Integration;

use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Support\Theme\ThemeManager;

/**
 * Unified Settings Manager.
 *
 * Manages both theme and locale settings in a unified interface.
 */
class UnifiedSettingsManager
{
    /**
     * Theme manager instance.
     */
    protected ThemeManager $themeManager;

    /**
     * Locale manager instance.
     */
    protected LocaleManager $localeManager;

    /**
     * Theme locale integration instance.
     */
    protected ThemeLocaleIntegration $integration;

    /**
     * Constructor.
     */
    public function __construct(
        ThemeManager $themeManager,
        LocaleManager $localeManager,
        ThemeLocaleIntegration $integration
    ) {
        $this->themeManager = $themeManager;
        $this->localeManager = $localeManager;
        $this->integration = $integration;
    }

    /**
     * Get all settings (theme + locale).
     */
    public function getAllSettings(): array
    {
        return [
            'theme' => $this->getThemeSettings(),
            'locale' => $this->getLocaleSettings(),
            'integration' => $this->getIntegrationSettings(),
        ];
    }

    /**
     * Get theme settings.
     */
    public function getThemeSettings(): array
    {
        $currentTheme = $this->themeManager->current();

        return [
            'current' => [
                'name' => $currentTheme->getName(),
                'display_name' => $currentTheme->getDisplayName(),
                'version' => $currentTheme->getVersion(),
                'author' => $currentTheme->getAuthor(),
                'description' => $currentTheme->getDescription(),
            ],
            'available' => $this->getAvailableThemes(),
            'supports_dark_mode' => $currentTheme->supportsDarkMode(),
            'colors' => $currentTheme->getColors(),
            'fonts' => $currentTheme->getFonts(),
        ];
    }

    /**
     * Get locale settings.
     */
    public function getLocaleSettings(): array
    {
        $currentLocale = $this->localeManager->getLocale();

        return [
            'current' => [
                'code' => $currentLocale,
                'name' => $this->localeManager->getLocaleName($currentLocale),
                'native' => $this->localeManager->getLocaleNativeName($currentLocale),
                'flag' => $this->localeManager->getLocaleFlag($currentLocale),
                'direction' => $this->localeManager->getDirection($currentLocale),
                'is_rtl' => $this->localeManager->isRtl($currentLocale),
            ],
            'available' => $this->getAvailableLocales(),
            'default' => $this->localeManager->getDefaultLocale(),
            'fallback' => $this->localeManager->getFallbackLocale(),
        ];
    }

    /**
     * Get integration settings.
     */
    public function getIntegrationSettings(): array
    {
        return [
            'html_attributes' => $this->integration->getHtmlAttributes(),
            'body_classes' => $this->integration->getBodyClasses(),
            'cache_stats' => $this->integration->getCacheStats(),
        ];
    }

    /**
     * Get available themes with metadata.
     */
    protected function getAvailableThemes(): array
    {
        $themes = [];

        foreach ($this->themeManager->all() as $theme) {
            $themes[] = [
                'name' => $theme->getName(),
                'display_name' => $theme->getDisplayName(),
                'version' => $theme->getVersion(),
                'author' => $theme->getAuthor(),
                'description' => $theme->getDescription(),
                'supports_dark_mode' => $theme->supportsDarkMode(),
                'preview_colors' => [
                    'primary' => $theme->get('colors.primary'),
                    'secondary' => $theme->get('colors.secondary'),
                    'accent' => $theme->get('colors.accent'),
                ],
            ];
        }

        return $themes;
    }

    /**
     * Get available locales with metadata.
     */
    protected function getAvailableLocales(): array
    {
        $locales = [];

        foreach ($this->localeManager->getAvailableLocales() as $code => $info) {
            $locales[] = [
                'code' => $code,
                'name' => $info['name'],
                'native' => $info['native'],
                'flag' => $info['flag'],
                'is_rtl' => $this->localeManager->isRtl($code),
                'direction' => $this->localeManager->getDirection($code),
            ];
        }

        return $locales;
    }

    /**
     * Apply settings (theme + locale).
     */
    public function applySettings(array $settings): array
    {
        $results = [
            'theme' => ['success' => false, 'message' => ''],
            'locale' => ['success' => false, 'message' => ''],
        ];

        // Apply theme if provided
        if (isset($settings['theme'])) {
            try {
                $this->themeManager->setCurrentTheme($settings['theme']);
                $results['theme']['success'] = true;
                $results['theme']['message'] = "Theme changed to '{$settings['theme']}'";
            } catch (\Exception $e) {
                $results['theme']['success'] = false;
                $results['theme']['message'] = $e->getMessage();
            }
        }

        // Apply locale if provided
        if (isset($settings['locale'])) {
            try {
                $success = $this->localeManager->setLocale($settings['locale']);
                $results['locale']['success'] = $success;
                $results['locale']['message'] = $success
                    ? "Locale changed to '{$settings['locale']}'"
                    : "Failed to change locale to '{$settings['locale']}'";
            } catch (\Exception $e) {
                $results['locale']['success'] = false;
                $results['locale']['message'] = $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Reset settings to defaults.
     */
    public function resetToDefaults(): array
    {
        $defaultTheme = config('canvastack-ui.theme.active', 'default');
        $defaultLocale = $this->localeManager->getDefaultLocale();

        return $this->applySettings([
            'theme' => $defaultTheme,
            'locale' => $defaultLocale,
        ]);
    }

    /**
     * Export settings.
     */
    public function exportSettings(): array
    {
        return [
            'theme' => $this->themeManager->current()->getName(),
            'locale' => $this->localeManager->getLocale(),
            'exported_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Import settings.
     */
    public function importSettings(array $settings): array
    {
        $results = [];

        if (isset($settings['theme'])) {
            $results['theme'] = $this->themeManager->has($settings['theme']);
        }

        if (isset($settings['locale'])) {
            $results['locale'] = $this->localeManager->isAvailable($settings['locale']);
        }

        if (!empty($results) && !in_array(false, $results, true)) {
            return $this->applySettings($settings);
        }

        return [
            'theme' => ['success' => false, 'message' => 'Invalid theme in import'],
            'locale' => ['success' => false, 'message' => 'Invalid locale in import'],
        ];
    }

    /**
     * Validate settings.
     */
    public function validateSettings(array $settings): array
    {
        $errors = [];

        if (isset($settings['theme']) && !$this->themeManager->has($settings['theme'])) {
            $errors['theme'] = "Theme '{$settings['theme']}' does not exist";
        }

        if (isset($settings['locale']) && !$this->localeManager->isAvailable($settings['locale'])) {
            $errors['locale'] = "Locale '{$settings['locale']}' is not available";
        }

        return $errors;
    }

    /**
     * Get settings for UI rendering.
     */
    public function getSettingsForUI(): array
    {
        $currentTheme = $this->themeManager->current()->getName();
        $currentLocale = $this->localeManager->getLocale();

        return [
            'current' => [
                'theme' => $currentTheme,
                'locale' => $currentLocale,
            ],
            'themes' => array_map(function ($theme) use ($currentTheme) {
                return [
                    'value' => $theme['name'],
                    'label' => $theme['display_name'],
                    'description' => $theme['description'],
                    'selected' => $theme['name'] === $currentTheme,
                    'preview' => $theme['preview_colors'],
                ];
            }, $this->getAvailableThemes()),
            'locales' => array_map(function ($locale) use ($currentLocale) {
                return [
                    'value' => $locale['code'],
                    'label' => $locale['native'],
                    'flag' => $locale['flag'],
                    'selected' => $locale['code'] === $currentLocale,
                    'rtl' => $locale['is_rtl'],
                ];
            }, $this->getAvailableLocales()),
            'integration' => [
                'html_attributes' => $this->integration->getHtmlAttributes(),
                'body_classes' => $this->integration->getBodyClasses(),
            ],
        ];
    }

    /**
     * Clear all caches.
     */
    public function clearAllCaches(): void
    {
        $this->themeManager->clearCache();
        $this->localeManager->clearCache();
        $this->integration->clearCache();
    }
}
