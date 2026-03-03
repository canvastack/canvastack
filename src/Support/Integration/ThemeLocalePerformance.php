<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Integration;

use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Support\Theme\ThemeManager;
use Illuminate\Support\Facades\Cache;

/**
 * Theme Locale Performance Optimizer.
 *
 * Optimizes performance for theme and locale switching operations.
 */
class ThemeLocalePerformance
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
     * Cache TTL in seconds.
     */
    protected int $cacheTtl = 3600;

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
     * Warm up cache for all theme + locale combinations.
     */
    public function warmupCache(): array
    {
        $themes = $this->themeManager->names();
        $locales = array_keys($this->localeManager->getAvailableLocales());

        $results = [
            'total' => count($themes) * count($locales),
            'cached' => 0,
            'failed' => 0,
            'time' => 0,
        ];

        $startTime = microtime(true);

        foreach ($themes as $theme) {
            foreach ($locales as $locale) {
                try {
                    // Generate and cache CSS
                    $this->integration->getLocalizedThemeCss($theme, $locale);

                    // Generate and cache config
                    $this->integration->getLocalizedThemeConfig($theme, $locale);

                    $results['cached']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                }
            }
        }

        $results['time'] = round((microtime(true) - $startTime) * 1000, 2);

        return $results;
    }

    /**
     * Preload commonly used theme + locale combinations.
     */
    public function preloadCommon(array $combinations = []): array
    {
        if (empty($combinations)) {
            // Default common combinations
            $combinations = [
                ['theme' => 'default', 'locale' => 'en'],
                ['theme' => 'default', 'locale' => 'id'],
                ['theme' => 'default', 'locale' => 'ar'],
            ];
        }

        $results = [
            'total' => count($combinations),
            'loaded' => 0,
            'failed' => 0,
            'time' => 0,
        ];

        $startTime = microtime(true);

        foreach ($combinations as $combo) {
            try {
                $this->integration->getLocalizedThemeCss($combo['theme'], $combo['locale']);
                $this->integration->getLocalizedThemeConfig($combo['theme'], $combo['locale']);
                $results['loaded']++;
            } catch (\Exception $e) {
                $results['failed']++;
            }
        }

        $results['time'] = round((microtime(true) - $startTime) * 1000, 2);

        return $results;
    }

    /**
     * Optimize theme switching by preloading next theme.
     */
    public function optimizeThemeSwitch(string $newTheme): void
    {
        $currentLocale = $this->localeManager->getLocale();

        // Preload new theme with current locale
        $this->integration->getLocalizedThemeCss($newTheme, $currentLocale);
        $this->integration->getLocalizedThemeConfig($newTheme, $currentLocale);
    }

    /**
     * Optimize locale switching by preloading next locale.
     */
    public function optimizeLocaleSwitch(string $newLocale): void
    {
        $currentTheme = $this->themeManager->current()->getName();

        // Preload current theme with new locale
        $this->integration->getLocalizedThemeCss($currentTheme, $newLocale);
        $this->integration->getLocalizedThemeConfig($currentTheme, $newLocale);
    }

    /**
     * Measure theme switching performance.
     */
    public function measureThemeSwitch(string $fromTheme, string $toTheme): array
    {
        $locale = $this->localeManager->getLocale();

        // Measure without cache
        $this->integration->clearCache();
        $startTime = microtime(true);
        $this->themeManager->setCurrentTheme($toTheme);
        $this->integration->getLocalizedThemeCss($toTheme, $locale);
        $uncachedTime = (microtime(true) - $startTime) * 1000;

        // Measure with cache
        $startTime = microtime(true);
        $this->integration->getLocalizedThemeCss($toTheme, $locale);
        $cachedTime = (microtime(true) - $startTime) * 1000;

        return [
            'from_theme' => $fromTheme,
            'to_theme' => $toTheme,
            'locale' => $locale,
            'uncached_time_ms' => round($uncachedTime, 2),
            'cached_time_ms' => round($cachedTime, 2),
            'improvement' => round((($uncachedTime - $cachedTime) / $uncachedTime) * 100, 2),
        ];
    }

    /**
     * Measure locale switching performance.
     */
    public function measureLocaleSwitch(string $fromLocale, string $toLocale): array
    {
        $theme = $this->themeManager->current()->getName();

        // Measure without cache
        $this->integration->clearCache();
        $startTime = microtime(true);
        $this->localeManager->setLocale($toLocale);
        $this->integration->getLocalizedThemeCss($theme, $toLocale);
        $uncachedTime = (microtime(true) - $startTime) * 1000;

        // Measure with cache
        $startTime = microtime(true);
        $this->integration->getLocalizedThemeCss($theme, $toLocale);
        $cachedTime = (microtime(true) - $startTime) * 1000;

        return [
            'theme' => $theme,
            'from_locale' => $fromLocale,
            'to_locale' => $toLocale,
            'uncached_time_ms' => round($uncachedTime, 2),
            'cached_time_ms' => round($cachedTime, 2),
            'improvement' => round((($uncachedTime - $cachedTime) / $uncachedTime) * 100, 2),
        ];
    }

    /**
     * Benchmark all theme + locale combinations.
     */
    public function benchmark(): array
    {
        $themes = $this->themeManager->names();
        $locales = array_keys($this->localeManager->getAvailableLocales());

        $results = [
            'themes' => count($themes),
            'locales' => count($locales),
            'combinations' => count($themes) * count($locales),
            'measurements' => [],
            'summary' => [
                'avg_uncached_ms' => 0,
                'avg_cached_ms' => 0,
                'avg_improvement' => 0,
                'min_time_ms' => PHP_FLOAT_MAX,
                'max_time_ms' => 0,
            ],
        ];

        $totalUncached = 0;
        $totalCached = 0;
        $count = 0;

        foreach ($themes as $theme) {
            foreach ($locales as $locale) {
                // Measure uncached
                $this->integration->clearCache();
                $startTime = microtime(true);
                $this->integration->getLocalizedThemeCss($theme, $locale);
                $uncachedTime = (microtime(true) - $startTime) * 1000;

                // Measure cached
                $startTime = microtime(true);
                $this->integration->getLocalizedThemeCss($theme, $locale);
                $cachedTime = (microtime(true) - $startTime) * 1000;

                $measurement = [
                    'theme' => $theme,
                    'locale' => $locale,
                    'uncached_ms' => round($uncachedTime, 2),
                    'cached_ms' => round($cachedTime, 2),
                    'improvement' => round((($uncachedTime - $cachedTime) / $uncachedTime) * 100, 2),
                ];

                $results['measurements'][] = $measurement;

                $totalUncached += $uncachedTime;
                $totalCached += $cachedTime;
                $count++;

                $results['summary']['min_time_ms'] = min($results['summary']['min_time_ms'], $cachedTime);
                $results['summary']['max_time_ms'] = max($results['summary']['max_time_ms'], $cachedTime);
            }
        }

        $results['summary']['avg_uncached_ms'] = round($totalUncached / $count, 2);
        $results['summary']['avg_cached_ms'] = round($totalCached / $count, 2);
        $results['summary']['avg_improvement'] = round((($totalUncached - $totalCached) / $totalUncached) * 100, 2);

        return $results;
    }

    /**
     * Get performance recommendations.
     */
    public function getRecommendations(): array
    {
        $stats = $this->integration->getCacheStats();
        $recommendations = [];

        // Check cache ratio
        if ($stats['css_cache_ratio'] < 50) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'cache',
                'message' => 'CSS cache ratio is low (' . $stats['css_cache_ratio'] . '%)',
                'suggestion' => 'Run warmupCache() to preload all theme + locale combinations',
            ];
        }

        if ($stats['config_cache_ratio'] < 50) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'cache',
                'message' => 'Config cache ratio is low (' . $stats['config_cache_ratio'] . '%)',
                'suggestion' => 'Run warmupCache() to preload all theme + locale combinations',
            ];
        }

        // Check number of combinations
        if ($stats['total_combinations'] > 20) {
            $recommendations[] = [
                'type' => 'info',
                'category' => 'optimization',
                'message' => 'Large number of theme + locale combinations (' . $stats['total_combinations'] . ')',
                'suggestion' => 'Consider using preloadCommon() instead of warmupCache() to only cache frequently used combinations',
            ];
        }

        // Check if cache is empty
        if ($stats['cached_css'] === 0 && $stats['cached_config'] === 0) {
            $recommendations[] = [
                'type' => 'error',
                'category' => 'cache',
                'message' => 'No cached data found',
                'suggestion' => 'Run warmupCache() or preloadCommon() to improve performance',
            ];
        }

        return $recommendations;
    }

    /**
     * Clear old cache entries.
     */
    public function clearOldCache(): void
    {
        $this->integration->clearCache();
    }

    /**
     * Get cache size estimate.
     */
    public function getCacheSizeEstimate(): array
    {
        $themes = $this->themeManager->names();
        $locales = array_keys($this->localeManager->getAvailableLocales());

        $totalSize = 0;
        $count = 0;

        foreach ($themes as $theme) {
            foreach ($locales as $locale) {
                try {
                    $css = $this->integration->getLocalizedThemeCss($theme, $locale);
                    $config = json_encode($this->integration->getLocalizedThemeConfig($theme, $locale));

                    $totalSize += strlen($css) + strlen($config);
                    $count++;
                } catch (\Exception $e) {
                    // Skip failed combinations
                }
            }
        }

        return [
            'total_combinations' => $count,
            'total_size_bytes' => $totalSize,
            'total_size_kb' => round($totalSize / 1024, 2),
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'avg_size_per_combination_bytes' => $count > 0 ? round($totalSize / $count, 2) : 0,
        ];
    }
}
