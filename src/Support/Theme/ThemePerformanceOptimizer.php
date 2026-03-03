<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use Illuminate\Support\Facades\Cache;

/**
 * Theme Performance Optimizer.
 *
 * Optimizes theme loading and compilation performance through
 * lazy loading, caching, and preloading strategies.
 */
class ThemePerformanceOptimizer
{
    /**
     * Theme manager instance.
     */
    protected ThemeManager $themeManager;

    /**
     * Cache TTL in seconds.
     */
    protected int $cacheTtl = 3600;

    /**
     * Cache key prefix.
     */
    protected string $cachePrefix = 'canvastack.theme.perf';

    /**
     * Lazy loading enabled.
     */
    protected bool $lazyLoadingEnabled = true;

    /**
     * Preloaded themes.
     */
    protected array $preloadedThemes = [];

    /**
     * Constructor.
     */
    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    /**
     * Enable lazy loading.
     */
    public function enableLazyLoading(): self
    {
        $this->lazyLoadingEnabled = true;

        return $this;
    }

    /**
     * Disable lazy loading.
     */
    public function disableLazyLoading(): self
    {
        $this->lazyLoadingEnabled = false;

        return $this;
    }

    /**
     * Check if lazy loading is enabled.
     */
    public function isLazyLoadingEnabled(): bool
    {
        return $this->lazyLoadingEnabled;
    }

    /**
     * Preload a theme into memory.
     */
    public function preloadTheme(string $themeName): void
    {
        if (isset($this->preloadedThemes[$themeName])) {
            return;
        }

        $theme = $this->themeManager->get($themeName);

        // Preload compiled CSS
        $this->preloadedThemes[$themeName] = [
            'theme' => $theme,
            'css' => $this->themeManager->generateCss($themeName),
            'config' => $theme->toArray(),
            'loaded_at' => microtime(true),
        ];
    }

    /**
     * Get preloaded theme data.
     */
    public function getPreloadedTheme(string $themeName): ?array
    {
        return $this->preloadedThemes[$themeName] ?? null;
    }

    /**
     * Clear preloaded themes.
     */
    public function clearPreloaded(): void
    {
        $this->preloadedThemes = [];
    }

    /**
     * Preload commonly used themes.
     */
    public function preloadCommonThemes(array $themeNames = []): array
    {
        if (empty($themeNames)) {
            // Default to current theme and default theme
            $themeNames = [
                $this->themeManager->current()->getName(),
                'default',
            ];
            $themeNames = array_unique($themeNames);
        }

        $results = [
            'total' => count($themeNames),
            'loaded' => 0,
            'failed' => 0,
            'time_ms' => 0,
        ];

        $startTime = microtime(true);

        foreach ($themeNames as $themeName) {
            try {
                $this->preloadTheme($themeName);
                $results['loaded']++;
            } catch (\Exception $e) {
                $results['failed']++;
            }
        }

        $results['time_ms'] = round((microtime(true) - $startTime) * 1000, 2);

        return $results;
    }

    /**
     * Lazy load theme CSS.
     */
    public function lazyLoadCss(string $themeName): string
    {
        if (!$this->lazyLoadingEnabled) {
            return $this->themeManager->generateCss($themeName);
        }

        $cacheKey = "{$this->cachePrefix}.css.{$themeName}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($themeName) {
            return $this->themeManager->generateCss($themeName);
        });
    }

    /**
     * Lazy load theme config.
     */
    public function lazyLoadConfig(string $themeName): array
    {
        if (!$this->lazyLoadingEnabled) {
            return $this->themeManager->get($themeName)->toArray();
        }

        $cacheKey = "{$this->cachePrefix}.config.{$themeName}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($themeName) {
            return $this->themeManager->get($themeName)->toArray();
        });
    }

    /**
     * Optimize theme switching by preloading next theme.
     */
    public function optimizeSwitch(string $newThemeName): void
    {
        // Preload new theme
        $this->preloadTheme($newThemeName);

        // Preload CSS and config into cache
        $this->lazyLoadCss($newThemeName);
        $this->lazyLoadConfig($newThemeName);
    }

    /**
     * Measure theme loading performance.
     */
    public function measureLoadingPerformance(string $themeName): array
    {
        // Measure without cache
        Cache::forget("{$this->cachePrefix}.css.{$themeName}");
        Cache::forget("{$this->cachePrefix}.config.{$themeName}");

        $startTime = microtime(true);
        $css = $this->themeManager->generateCss($themeName);
        $config = $this->themeManager->get($themeName)->toArray();
        $uncachedTime = (microtime(true) - $startTime) * 1000;

        // Measure with cache
        $startTime = microtime(true);
        $cachedCss = $this->lazyLoadCss($themeName);
        $cachedConfig = $this->lazyLoadConfig($themeName);
        $cachedTime = (microtime(true) - $startTime) * 1000;

        return [
            'theme' => $themeName,
            'uncached_time_ms' => round($uncachedTime, 2),
            'cached_time_ms' => round($cachedTime, 2),
            'improvement_percent' => round((($uncachedTime - $cachedTime) / $uncachedTime) * 100, 2),
            'css_size_bytes' => strlen($css),
            'config_size_bytes' => strlen(json_encode($config)),
        ];
    }

    /**
     * Benchmark all themes.
     */
    public function benchmarkAllThemes(): array
    {
        $themes = $this->themeManager->names();

        $results = [
            'total_themes' => count($themes),
            'measurements' => [],
            'summary' => [
                'avg_uncached_ms' => 0,
                'avg_cached_ms' => 0,
                'avg_improvement_percent' => 0,
                'total_css_size_kb' => 0,
                'total_config_size_kb' => 0,
            ],
        ];

        $totalUncached = 0;
        $totalCached = 0;
        $totalCssSize = 0;
        $totalConfigSize = 0;

        foreach ($themes as $themeName) {
            $measurement = $this->measureLoadingPerformance($themeName);
            $results['measurements'][] = $measurement;

            $totalUncached += $measurement['uncached_time_ms'];
            $totalCached += $measurement['cached_time_ms'];
            $totalCssSize += $measurement['css_size_bytes'];
            $totalConfigSize += $measurement['config_size_bytes'];
        }

        $count = count($themes);
        $results['summary']['avg_uncached_ms'] = round($totalUncached / $count, 2);
        $results['summary']['avg_cached_ms'] = round($totalCached / $count, 2);
        $results['summary']['avg_improvement_percent'] = round((($totalUncached - $totalCached) / $totalUncached) * 100, 2);
        $results['summary']['total_css_size_kb'] = round($totalCssSize / 1024, 2);
        $results['summary']['total_config_size_kb'] = round($totalConfigSize / 1024, 2);

        return $results;
    }

    /**
     * Warm up cache for all themes.
     */
    public function warmupCache(): array
    {
        $themes = $this->themeManager->names();

        $results = [
            'total' => count($themes),
            'cached' => 0,
            'failed' => 0,
            'time_ms' => 0,
        ];

        $startTime = microtime(true);

        foreach ($themes as $themeName) {
            try {
                $this->lazyLoadCss($themeName);
                $this->lazyLoadConfig($themeName);
                $results['cached']++;
            } catch (\Exception $e) {
                $results['failed']++;
            }
        }

        $results['time_ms'] = round((microtime(true) - $startTime) * 1000, 2);

        return $results;
    }

    /**
     * Clear performance cache.
     */
    public function clearCache(): void
    {
        $themes = $this->themeManager->names();

        foreach ($themes as $themeName) {
            Cache::forget("{$this->cachePrefix}.css.{$themeName}");
            Cache::forget("{$this->cachePrefix}.config.{$themeName}");
        }

        $this->clearPreloaded();
    }

    /**
     * Get cache statistics.
     */
    public function getCacheStats(): array
    {
        $themes = $this->themeManager->names();
        $cachedCss = 0;
        $cachedConfig = 0;

        foreach ($themes as $themeName) {
            if (Cache::has("{$this->cachePrefix}.css.{$themeName}")) {
                $cachedCss++;
            }
            if (Cache::has("{$this->cachePrefix}.config.{$themeName}")) {
                $cachedConfig++;
            }
        }

        $total = count($themes);

        return [
            'total_themes' => $total,
            'cached_css' => $cachedCss,
            'cached_config' => $cachedConfig,
            'css_cache_ratio' => $total > 0 ? round(($cachedCss / $total) * 100, 2) : 0,
            'config_cache_ratio' => $total > 0 ? round(($cachedConfig / $total) * 100, 2) : 0,
            'preloaded_themes' => count($this->preloadedThemes),
        ];
    }

    /**
     * Get performance recommendations.
     */
    public function getRecommendations(): array
    {
        $stats = $this->getCacheStats();
        $recommendations = [];

        if ($stats['css_cache_ratio'] < 50) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'cache',
                'message' => 'CSS cache ratio is low (' . $stats['css_cache_ratio'] . '%)',
                'suggestion' => 'Run warmupCache() to preload all themes',
            ];
        }

        if ($stats['config_cache_ratio'] < 50) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'cache',
                'message' => 'Config cache ratio is low (' . $stats['config_cache_ratio'] . '%)',
                'suggestion' => 'Run warmupCache() to preload all themes',
            ];
        }

        if (!$this->lazyLoadingEnabled) {
            $recommendations[] = [
                'type' => 'info',
                'category' => 'optimization',
                'message' => 'Lazy loading is disabled',
                'suggestion' => 'Enable lazy loading with enableLazyLoading() for better performance',
            ];
        }

        if ($stats['preloaded_themes'] === 0) {
            $recommendations[] = [
                'type' => 'info',
                'category' => 'optimization',
                'message' => 'No themes preloaded',
                'suggestion' => 'Use preloadCommonThemes() to preload frequently used themes',
            ];
        }

        return $recommendations;
    }

    /**
     * Set cache TTL.
     */
    public function setCacheTtl(int $seconds): self
    {
        $this->cacheTtl = $seconds;

        return $this;
    }

    /**
     * Get cache TTL.
     */
    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }
}
