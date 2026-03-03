<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Localization;

use Illuminate\Support\Facades\Cache;

/**
 * Translation Performance Optimizer.
 *
 * Optimizes translation loading and caching performance through
 * lazy loading, preloading, and intelligent caching strategies.
 */
class TranslationPerformanceOptimizer
{
    /**
     * Locale manager instance.
     */
    protected LocaleManager $localeManager;

    /**
     * Translation loader instance.
     */
    protected TranslationLoader $translationLoader;

    /**
     * Cache TTL in seconds.
     */
    protected int $cacheTtl = 3600;

    /**
     * Cache key prefix.
     */
    protected string $cachePrefix = 'canvastack.translation.perf';

    /**
     * Lazy loading enabled.
     */
    protected bool $lazyLoadingEnabled = true;

    /**
     * Preloaded translations.
     */
    protected array $preloadedTranslations = [];

    /**
     * Constructor.
     */
    public function __construct(LocaleManager $localeManager, TranslationLoader $translationLoader)
    {
        $this->localeManager = $localeManager;
        $this->translationLoader = $translationLoader;
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
     * Preload translations for a locale.
     */
    public function preloadLocale(string $locale): void
    {
        if (isset($this->preloadedTranslations[$locale])) {
            return;
        }

        $translations = $this->translationLoader->loadAll($locale);

        $this->preloadedTranslations[$locale] = [
            'translations' => $translations,
            'loaded_at' => microtime(true),
            'size_bytes' => strlen(json_encode($translations)),
        ];
    }

    /**
     * Get preloaded translations.
     */
    public function getPreloadedTranslations(string $locale): ?array
    {
        return $this->preloadedTranslations[$locale] ?? null;
    }

    /**
     * Clear preloaded translations.
     */
    public function clearPreloaded(): void
    {
        $this->preloadedTranslations = [];
    }

    /**
     * Preload commonly used locales.
     */
    public function preloadCommonLocales(array $locales = []): array
    {
        if (empty($locales)) {
            // Default to current locale and default locale
            $locales = [
                $this->localeManager->getLocale(),
                $this->localeManager->getDefaultLocale(),
            ];
            $locales = array_unique($locales);
        }

        $results = [
            'total' => count($locales),
            'loaded' => 0,
            'failed' => 0,
            'time_ms' => 0,
        ];

        $startTime = microtime(true);

        foreach ($locales as $locale) {
            try {
                $this->preloadLocale($locale);
                $results['loaded']++;
            } catch (\Exception $e) {
                $results['failed']++;
            }
        }

        $results['time_ms'] = round((microtime(true) - $startTime) * 1000, 2);

        return $results;
    }

    /**
     * Lazy load translations for a locale and namespace.
     */
    public function lazyLoadTranslations(string $locale, string $namespace = '*'): array
    {
        if (!$this->lazyLoadingEnabled) {
            return $this->translationLoader->load($locale, $namespace);
        }

        $cacheKey = "{$this->cachePrefix}.{$locale}.{$namespace}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($locale, $namespace) {
            return $this->translationLoader->load($locale, $namespace);
        });
    }

    /**
     * Lazy load all translations for a locale.
     */
    public function lazyLoadAll(string $locale): array
    {
        if (!$this->lazyLoadingEnabled) {
            return $this->translationLoader->loadAll($locale);
        }

        $cacheKey = "{$this->cachePrefix}.all.{$locale}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($locale) {
            return $this->translationLoader->loadAll($locale);
        });
    }

    /**
     * Optimize locale switching by preloading next locale.
     */
    public function optimizeSwitch(string $newLocale): void
    {
        // Preload new locale
        $this->preloadLocale($newLocale);

        // Preload translations into cache
        $this->lazyLoadAll($newLocale);
    }

    /**
     * Measure translation loading performance.
     */
    public function measureLoadingPerformance(string $locale): array
    {
        // Measure without cache
        Cache::forget("{$this->cachePrefix}.all.{$locale}");

        $startTime = microtime(true);
        $translations = $this->translationLoader->loadAll($locale);
        $uncachedTime = (microtime(true) - $startTime) * 1000;

        // Measure with cache
        $startTime = microtime(true);
        $cachedTranslations = $this->lazyLoadAll($locale);
        $cachedTime = (microtime(true) - $startTime) * 1000;

        return [
            'locale' => $locale,
            'uncached_time_ms' => round($uncachedTime, 2),
            'cached_time_ms' => round($cachedTime, 2),
            'improvement_percent' => round((($uncachedTime - $cachedTime) / $uncachedTime) * 100, 2),
            'translation_count' => count($translations, COUNT_RECURSIVE),
            'size_bytes' => strlen(json_encode($translations)),
            'size_kb' => round(strlen(json_encode($translations)) / 1024, 2),
        ];
    }

    /**
     * Benchmark all locales.
     */
    public function benchmarkAllLocales(): array
    {
        $locales = array_keys($this->localeManager->getAvailableLocales());

        $results = [
            'total_locales' => count($locales),
            'measurements' => [],
            'summary' => [
                'avg_uncached_ms' => 0,
                'avg_cached_ms' => 0,
                'avg_improvement_percent' => 0,
                'total_translations' => 0,
                'total_size_kb' => 0,
            ],
        ];

        $totalUncached = 0;
        $totalCached = 0;
        $totalTranslations = 0;
        $totalSize = 0;

        foreach ($locales as $locale) {
            $measurement = $this->measureLoadingPerformance($locale);
            $results['measurements'][] = $measurement;

            $totalUncached += $measurement['uncached_time_ms'];
            $totalCached += $measurement['cached_time_ms'];
            $totalTranslations += $measurement['translation_count'];
            $totalSize += $measurement['size_bytes'];
        }

        $count = count($locales);
        $results['summary']['avg_uncached_ms'] = round($totalUncached / $count, 2);
        $results['summary']['avg_cached_ms'] = round($totalCached / $count, 2);
        $results['summary']['avg_improvement_percent'] = round((($totalUncached - $totalCached) / $totalUncached) * 100, 2);
        $results['summary']['total_translations'] = $totalTranslations;
        $results['summary']['total_size_kb'] = round($totalSize / 1024, 2);

        return $results;
    }

    /**
     * Warm up cache for all locales.
     */
    public function warmupCache(): array
    {
        $locales = array_keys($this->localeManager->getAvailableLocales());

        $results = [
            'total' => count($locales),
            'cached' => 0,
            'failed' => 0,
            'time_ms' => 0,
        ];

        $startTime = microtime(true);

        foreach ($locales as $locale) {
            try {
                $this->lazyLoadAll($locale);
                $results['cached']++;
            } catch (\Exception $e) {
                $results['failed']++;
            }
        }

        $results['time_ms'] = round((microtime(true) - $startTime) * 1000, 2);

        return $results;
    }

    /**
     * Warm up cache for specific namespaces.
     */
    public function warmupNamespaces(array $namespaces = []): array
    {
        if (empty($namespaces)) {
            $namespaces = ['ui', 'validation', 'auth', 'components'];
        }

        $locales = array_keys($this->localeManager->getAvailableLocales());

        $results = [
            'total' => count($locales) * count($namespaces),
            'cached' => 0,
            'failed' => 0,
            'time_ms' => 0,
        ];

        $startTime = microtime(true);

        foreach ($locales as $locale) {
            foreach ($namespaces as $namespace) {
                try {
                    $this->lazyLoadTranslations($locale, $namespace);
                    $results['cached']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                }
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
        $locales = array_keys($this->localeManager->getAvailableLocales());

        foreach ($locales as $locale) {
            Cache::forget("{$this->cachePrefix}.all.{$locale}");

            // Clear namespace caches
            $namespaces = ['ui', 'validation', 'auth', 'components', 'errors'];
            foreach ($namespaces as $namespace) {
                Cache::forget("{$this->cachePrefix}.{$locale}.{$namespace}");
            }
        }

        $this->clearPreloaded();
    }

    /**
     * Get cache statistics.
     */
    public function getCacheStats(): array
    {
        $locales = array_keys($this->localeManager->getAvailableLocales());
        $cachedLocales = 0;
        $cachedNamespaces = 0;

        $namespaces = ['ui', 'validation', 'auth', 'components', 'errors'];

        foreach ($locales as $locale) {
            if (Cache::has("{$this->cachePrefix}.all.{$locale}")) {
                $cachedLocales++;
            }

            foreach ($namespaces as $namespace) {
                if (Cache::has("{$this->cachePrefix}.{$locale}.{$namespace}")) {
                    $cachedNamespaces++;
                }
            }
        }

        $totalLocales = count($locales);
        $totalNamespaces = count($locales) * count($namespaces);

        return [
            'total_locales' => $totalLocales,
            'cached_locales' => $cachedLocales,
            'locale_cache_ratio' => $totalLocales > 0 ? round(($cachedLocales / $totalLocales) * 100, 2) : 0,
            'total_namespaces' => $totalNamespaces,
            'cached_namespaces' => $cachedNamespaces,
            'namespace_cache_ratio' => $totalNamespaces > 0 ? round(($cachedNamespaces / $totalNamespaces) * 100, 2) : 0,
            'preloaded_locales' => count($this->preloadedTranslations),
        ];
    }

    /**
     * Get performance recommendations.
     */
    public function getRecommendations(): array
    {
        $stats = $this->getCacheStats();
        $recommendations = [];

        if ($stats['locale_cache_ratio'] < 50) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'cache',
                'message' => 'Locale cache ratio is low (' . $stats['locale_cache_ratio'] . '%)',
                'suggestion' => 'Run warmupCache() to preload all locales',
            ];
        }

        if ($stats['namespace_cache_ratio'] < 50) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'cache',
                'message' => 'Namespace cache ratio is low (' . $stats['namespace_cache_ratio'] . '%)',
                'suggestion' => 'Run warmupNamespaces() to preload common namespaces',
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

        if ($stats['preloaded_locales'] === 0) {
            $recommendations[] = [
                'type' => 'info',
                'category' => 'optimization',
                'message' => 'No locales preloaded',
                'suggestion' => 'Use preloadCommonLocales() to preload frequently used locales',
            ];
        }

        return $recommendations;
    }

    /**
     * Get memory usage estimate.
     */
    public function getMemoryUsageEstimate(): array
    {
        $locales = array_keys($this->localeManager->getAvailableLocales());
        $totalSize = 0;

        foreach ($locales as $locale) {
            try {
                $translations = $this->translationLoader->loadAll($locale);
                $totalSize += strlen(json_encode($translations));
            } catch (\Exception $e) {
                // Skip failed locales
            }
        }

        return [
            'total_locales' => count($locales),
            'total_size_bytes' => $totalSize,
            'total_size_kb' => round($totalSize / 1024, 2),
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'avg_size_per_locale_kb' => count($locales) > 0 ? round(($totalSize / 1024) / count($locales), 2) : 0,
        ];
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
