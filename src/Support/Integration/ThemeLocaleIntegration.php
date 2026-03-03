<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Integration;

use Canvastack\Canvastack\Contracts\ThemeInterface;
use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Support\Localization\RtlSupport;
use Canvastack\Canvastack\Support\Theme\ThemeManager;
use Illuminate\Support\Facades\Cache;

/**
 * Theme Locale Integration.
 *
 * Ensures themes work seamlessly with all locales, including RTL support.
 */
class ThemeLocaleIntegration
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
     * RTL support instance.
     */
    protected RtlSupport $rtlSupport;

    /**
     * Cache TTL in seconds.
     */
    protected int $cacheTtl = 3600;

    /**
     * Constructor.
     */
    public function __construct(
        ThemeManager $themeManager,
        LocaleManager $localeManager,
        RtlSupport $rtlSupport
    ) {
        $this->themeManager = $themeManager;
        $this->localeManager = $localeManager;
        $this->rtlSupport = $rtlSupport;
    }

    /**
     * Get theme CSS with locale-specific adjustments.
     */
    public function getLocalizedThemeCss(?string $themeName = null, ?string $locale = null): string
    {
        $themeName = $themeName ?? $this->themeManager->current()->getName();
        $locale = $locale ?? $this->localeManager->getLocale();

        $cacheKey = "theme_locale_css.{$themeName}.{$locale}";

        return Cache::tags(['theme', 'locale', 'css'])->remember(
            $cacheKey,
            $this->cacheTtl,
            function () use ($themeName, $locale) {
                $theme = $this->themeManager->get($themeName);
                $baseCss = $this->themeManager->generateCss($themeName);

                // Add RTL-specific CSS if needed
                if ($this->rtlSupport->isRtl($locale)) {
                    $baseCss .= "\n\n" . $this->generateRtlCss($theme);
                }

                // Add locale-specific font adjustments
                $baseCss .= "\n\n" . $this->generateLocaleFontCss($theme, $locale);

                return $baseCss;
            }
        );
    }

    /**
     * Generate RTL-specific CSS for a theme.
     */
    protected function generateRtlCss(ThemeInterface $theme): string
    {
        $css = "/* RTL Adjustments */\n";
        $css .= "[dir=\"rtl\"] {\n";
        $css .= "  direction: rtl;\n";
        $css .= "  text-align: right;\n";
        $css .= "}\n\n";

        // Flip margins and paddings
        $css .= "[dir=\"rtl\"] .ml-auto { margin-left: 0 !important; margin-right: auto !important; }\n";
        $css .= "[dir=\"rtl\"] .mr-auto { margin-right: 0 !important; margin-left: auto !important; }\n";
        $css .= "[dir=\"rtl\"] .float-left { float: right !important; }\n";
        $css .= "[dir=\"rtl\"] .float-right { float: left !important; }\n";
        $css .= "[dir=\"rtl\"] .text-left { text-align: right !important; }\n";
        $css .= "[dir=\"rtl\"] .text-right { text-align: left !important; }\n\n";

        // Flip icons that need flipping
        $css .= "[dir=\"rtl\"] .flip-rtl { transform: scaleX(-1); }\n";

        return $css;
    }

    /**
     * Generate locale-specific font CSS.
     */
    protected function generateLocaleFontCss(ThemeInterface $theme, string $locale): string
    {
        $fonts = $theme->getFonts();
        $localeFonts = $this->getLocaleFonts($locale);

        if (empty($localeFonts)) {
            return '';
        }

        $css = "/* Locale-specific fonts for {$locale} */\n";
        $css .= "[lang=\"{$locale}\"] {\n";

        if (isset($localeFonts['sans'])) {
            $css .= "  font-family: {$localeFonts['sans']}, {$fonts['sans']};\n";
        }

        if (isset($localeFonts['serif'])) {
            $css .= "  --font-serif: {$localeFonts['serif']};\n";
        }

        $css .= "}\n";

        return $css;
    }

    /**
     * Get locale-specific font preferences.
     */
    protected function getLocaleFonts(string $locale): array
    {
        $localeFonts = [
            'ar' => [
                'sans' => 'Noto Sans Arabic, Tajawal, Cairo',
                'serif' => 'Noto Naskh Arabic, Amiri',
            ],
            'he' => [
                'sans' => 'Noto Sans Hebrew, Rubik',
                'serif' => 'Noto Serif Hebrew',
            ],
            'fa' => [
                'sans' => 'Noto Sans Arabic, Vazir, Samim',
                'serif' => 'Noto Naskh Arabic',
            ],
            'ja' => [
                'sans' => 'Noto Sans JP, Hiragino Sans',
                'serif' => 'Noto Serif JP',
            ],
            'zh' => [
                'sans' => 'Noto Sans SC, PingFang SC',
                'serif' => 'Noto Serif SC',
            ],
            'ko' => [
                'sans' => 'Noto Sans KR, Malgun Gothic',
                'serif' => 'Noto Serif KR',
            ],
        ];

        return $localeFonts[$locale] ?? [];
    }

    /**
     * Get theme configuration with locale adjustments.
     */
    public function getLocalizedThemeConfig(?string $themeName = null, ?string $locale = null): array
    {
        $themeName = $themeName ?? $this->themeManager->current()->getName();
        $locale = $locale ?? $this->localeManager->getLocale();

        $cacheKey = "theme_locale_config.{$themeName}.{$locale}";

        return Cache::tags(['theme', 'locale', 'config'])->remember(
            $cacheKey,
            $this->cacheTtl,
            function () use ($themeName, $locale) {
                $theme = $this->themeManager->get($themeName);
                $config = $theme->toArray();

                // Add locale information
                $config['locale'] = [
                    'code' => $locale,
                    'name' => $this->localeManager->getLocaleName($locale),
                    'native' => $this->localeManager->getLocaleNativeName($locale),
                    'direction' => $this->rtlSupport->getDirection($locale),
                    'is_rtl' => $this->rtlSupport->isRtl($locale),
                ];

                // Adjust layout for RTL
                if ($this->rtlSupport->isRtl($locale)) {
                    $config['layout']['direction'] = 'rtl';
                    $config['layout']['text_align'] = 'right';
                }

                // Add locale-specific fonts
                $localeFonts = $this->getLocaleFonts($locale);
                if (!empty($localeFonts)) {
                    $config['fonts']['locale'] = $localeFonts;
                }

                return $config;
            }
        );
    }

    /**
     * Validate theme compatibility with locale.
     */
    public function validateThemeLocaleCompatibility(string $themeName, string $locale): array
    {
        $issues = [];
        $theme = $this->themeManager->get($themeName);

        // Check if theme supports RTL if locale is RTL
        if ($this->rtlSupport->isRtl($locale)) {
            $rtlSupport = $theme->get('rtl_support', false);
            if (!$rtlSupport) {
                $issues[] = [
                    'type' => 'warning',
                    'message' => "Theme '{$themeName}' does not explicitly declare RTL support for locale '{$locale}'",
                    'suggestion' => 'Add rtl_support: true to theme configuration',
                ];
            }
        }

        // Check if theme has locale-specific fonts
        $fonts = $theme->getFonts();
        $localeFonts = $this->getLocaleFonts($locale);
        if (!empty($localeFonts) && !isset($fonts['locale'])) {
            $issues[] = [
                'type' => 'info',
                'message' => "Theme '{$themeName}' could benefit from locale-specific fonts for '{$locale}'",
                'suggestion' => 'Consider adding locale-specific font families',
            ];
        }

        return $issues;
    }

    /**
     * Test theme rendering in all available locales.
     */
    public function testThemeInAllLocales(string $themeName): array
    {
        $results = [];
        $locales = $this->localeManager->getAvailableLocales();

        foreach (array_keys($locales) as $locale) {
            $results[$locale] = [
                'locale' => $locale,
                'name' => $this->localeManager->getLocaleName($locale),
                'is_rtl' => $this->rtlSupport->isRtl($locale),
                'css_generated' => false,
                'config_generated' => false,
                'issues' => [],
            ];

            try {
                // Test CSS generation
                $css = $this->getLocalizedThemeCss($themeName, $locale);
                $results[$locale]['css_generated'] = !empty($css);
                $results[$locale]['css_size'] = strlen($css);

                // Test config generation
                $config = $this->getLocalizedThemeConfig($themeName, $locale);
                $results[$locale]['config_generated'] = !empty($config);

                // Validate compatibility
                $issues = $this->validateThemeLocaleCompatibility($themeName, $locale);
                $results[$locale]['issues'] = $issues;

                $results[$locale]['status'] = 'success';
            } catch (\Exception $e) {
                $results[$locale]['status'] = 'error';
                $results[$locale]['error'] = $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Get HTML attributes for theme and locale.
     */
    public function getHtmlAttributes(?string $locale = null): array
    {
        $locale = $locale ?? $this->localeManager->getLocale();

        return [
            'lang' => $locale,
            'dir' => $this->rtlSupport->getDirection($locale),
            'class' => implode(' ', [
                $this->rtlSupport->getRtlClass($locale),
                'theme-' . $this->themeManager->current()->getName(),
            ]),
        ];
    }

    /**
     * Get body classes for theme and locale.
     */
    public function getBodyClasses(?string $locale = null): string
    {
        $locale = $locale ?? $this->localeManager->getLocale();
        $classes = [];

        // Add locale class
        $classes[] = 'locale-' . $locale;

        // Add direction class
        $classes[] = $this->rtlSupport->getRtlClass($locale);

        // Add theme class
        $classes[] = 'theme-' . $this->themeManager->current()->getName();

        // Add variant class if dark mode
        if ($this->themeManager->current()->get('dark_mode.enabled', false)) {
            $classes[] = 'theme-supports-dark';
        }

        return implode(' ', $classes);
    }

    /**
     * Clear integration cache.
     */
    public function clearCache(): void
    {
        Cache::tags(['theme', 'locale'])->flush();
    }

    /**
     * Get cache statistics.
     */
    public function getCacheStats(): array
    {
        $themes = $this->themeManager->names();
        $locales = array_keys($this->localeManager->getAvailableLocales());

        $totalCombinations = count($themes) * count($locales);
        $cachedCss = 0;
        $cachedConfig = 0;

        foreach ($themes as $theme) {
            foreach ($locales as $locale) {
                if (Cache::tags(['theme', 'locale', 'css'])->has("theme_locale_css.{$theme}.{$locale}")) {
                    $cachedCss++;
                }
                if (Cache::tags(['theme', 'locale', 'config'])->has("theme_locale_config.{$theme}.{$locale}")) {
                    $cachedConfig++;
                }
            }
        }

        return [
            'total_combinations' => $totalCombinations,
            'cached_css' => $cachedCss,
            'cached_config' => $cachedConfig,
            'css_cache_ratio' => $totalCombinations > 0 ? round(($cachedCss / $totalCombinations) * 100, 2) : 0,
            'config_cache_ratio' => $totalCombinations > 0 ? round(($cachedConfig / $totalCombinations) * 100, 2) : 0,
        ];
    }
}
