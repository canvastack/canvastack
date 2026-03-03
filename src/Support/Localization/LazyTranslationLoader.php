<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Localization;

use Illuminate\Support\Facades\Cache;

/**
 * Lazy Translation Loader.
 *
 * Implements lazy loading strategy for translations to improve
 * initial load performance by deferring translation loading until needed.
 */
class LazyTranslationLoader
{
    /**
     * Translation loader instance.
     */
    protected TranslationLoader $translationLoader;

    /**
     * Locale manager instance.
     */
    protected LocaleManager $localeManager;

    /**
     * Cache TTL in seconds.
     */
    protected int $cacheTtl = 3600;

    /**
     * Cache key prefix.
     */
    protected string $cachePrefix = 'canvastack.translation.lazy';

    /**
     * Loaded translations registry.
     */
    protected array $loadedTranslations = [];

    /**
     * Loading queue.
     */
    protected array $loadingQueue = [];

    /**
     * Constructor.
     */
    public function __construct(TranslationLoader $translationLoader, LocaleManager $localeManager)
    {
        $this->translationLoader = $translationLoader;
        $this->localeManager = $localeManager;
    }

    /**
     * Lazy load translations for a locale and namespace.
     */
    public function load(string $locale, string $namespace = '*'): array
    {
        $key = "{$locale}.{$namespace}";

        // Check if already loaded
        if (isset($this->loadedTranslations[$key])) {
            return $this->loadedTranslations[$key];
        }

        // Check cache first
        $cacheKey = "{$this->cachePrefix}.{$key}";
        $cachedTranslations = Cache::get($cacheKey);

        if ($cachedTranslations !== null) {
            $this->loadedTranslations[$key] = $cachedTranslations;

            return $cachedTranslations;
        }

        // Load translations
        $translations = $this->translationLoader->load($locale, $namespace);

        // Cache the translations
        Cache::put($cacheKey, $translations, $this->cacheTtl);

        // Store in loaded registry
        $this->loadedTranslations[$key] = $translations;

        return $translations;
    }

    /**
     * Lazy load all translations for a locale.
     */
    public function loadAll(string $locale): array
    {
        return $this->load($locale, '*');
    }

    /**
     * Lazy load a specific translation key.
     */
    public function loadKey(string $locale, string $key, $default = null)
    {
        // Parse namespace from key
        $parts = explode('.', $key, 2);
        $namespace = count($parts) > 1 ? $parts[0] : '*';

        // Load namespace translations
        $translations = $this->load($locale, $namespace);

        // Get the specific key
        return data_get($translations, $key, $default);
    }

    /**
     * Queue translations for lazy loading.
     */
    public function queue(string $locale, string $namespace = '*'): void
    {
        $key = "{$locale}.{$namespace}";

        if (!in_array($key, $this->loadingQueue)) {
            $this->loadingQueue[] = $key;
        }
    }

    /**
     * Process the loading queue.
     */
    public function processQueue(): array
    {
        $results = [
            'total' => count($this->loadingQueue),
            'loaded' => 0,
            'failed' => 0,
            'time_ms' => 0,
        ];

        $startTime = microtime(true);

        foreach ($this->loadingQueue as $key) {
            try {
                [$locale, $namespace] = explode('.', $key, 2);
                $this->load($locale, $namespace);
                $results['loaded']++;
            } catch (\Exception $e) {
                $results['failed']++;
            }
        }

        $results['time_ms'] = round((microtime(true) - $startTime) * 1000, 2);

        // Clear the queue
        $this->loadingQueue = [];

        return $results;
    }

    /**
     * Lazy load common namespaces for current locale.
     */
    public function loadCommonNamespaces(array $namespaces = []): array
    {
        if (empty($namespaces)) {
            $namespaces = ['ui', 'validation', 'auth', 'components'];
        }

        $locale = $this->localeManager->getLocale();

        $results = [
            'locale' => $locale,
            'total' => count($namespaces),
            'loaded' => 0,
            'failed' => 0,
            'time_ms' => 0,
        ];

        $startTime = microtime(true);

        foreach ($namespaces as $namespace) {
            try {
                $this->load($locale, $namespace);
                $results['loaded']++;
            } catch (\Exception $e) {
                $results['failed']++;
            }
        }

        $results['time_ms'] = round((microtime(true) - $startTime) * 1000, 2);

        return $results;
    }

    /**
     * Preload translations for locale switching.
     */
    public function preloadForSwitch(string $newLocale, array $namespaces = []): array
    {
        if (empty($namespaces)) {
            $namespaces = ['ui', 'validation', 'auth', 'components'];
        }

        $results = [
            'locale' => $newLocale,
            'total' => count($namespaces),
            'loaded' => 0,
            'failed' => 0,
            'time_ms' => 0,
        ];

        $startTime = microtime(true);

        foreach ($namespaces as $namespace) {
            try {
                $this->load($newLocale, $namespace);
                $results['loaded']++;
            } catch (\Exception $e) {
                $results['failed']++;
            }
        }

        $results['time_ms'] = round((microtime(true) - $startTime) * 1000, 2);

        return $results;
    }

    /**
     * Check if translations are loaded.
     */
    public function isLoaded(string $locale, string $namespace = '*'): bool
    {
        $key = "{$locale}.{$namespace}";

        return isset($this->loadedTranslations[$key]);
    }

    /**
     * Get loaded translations count.
     */
    public function getLoadedCount(): int
    {
        return count($this->loadedTranslations);
    }

    /**
     * Get queued translations count.
     */
    public function getQueuedCount(): int
    {
        return count($this->loadingQueue);
    }

    /**
     * Unload translations from memory.
     */
    public function unload(string $locale, string $namespace = '*'): void
    {
        $key = "{$locale}.{$namespace}";
        unset($this->loadedTranslations[$key]);
    }

    /**
     * Unload all translations from memory.
     */
    public function unloadAll(): void
    {
        $this->loadedTranslations = [];
    }

    /**
     * Clear lazy loading cache.
     */
    public function clearCache(): void
    {
        $locales = array_keys($this->localeManager->getAvailableLocales());
        $namespaces = ['*', 'ui', 'validation', 'auth', 'components', 'errors'];

        foreach ($locales as $locale) {
            foreach ($namespaces as $namespace) {
                $key = "{$locale}.{$namespace}";
                Cache::forget("{$this->cachePrefix}.{$key}");
            }
        }

        $this->unloadAll();
        $this->loadingQueue = [];
    }

    /**
     * Get cache statistics.
     */
    public function getCacheStats(): array
    {
        $locales = array_keys($this->localeManager->getAvailableLocales());
        $namespaces = ['*', 'ui', 'validation', 'auth', 'components', 'errors'];

        $cachedCount = 0;
        $totalCount = count($locales) * count($namespaces);

        foreach ($locales as $locale) {
            foreach ($namespaces as $namespace) {
                $key = "{$locale}.{$namespace}";
                if (Cache::has("{$this->cachePrefix}.{$key}")) {
                    $cachedCount++;
                }
            }
        }

        return [
            'total_locales' => count($locales),
            'total_namespaces' => count($namespaces),
            'total_combinations' => $totalCount,
            'loaded_translations' => count($this->loadedTranslations),
            'queued_translations' => count($this->loadingQueue),
            'cached_translations' => $cachedCount,
            'cache_ratio' => $totalCount > 0 ? round(($cachedCount / $totalCount) * 100, 2) : 0,
        ];
    }

    /**
     * Measure lazy loading performance.
     */
    public function measurePerformance(string $locale, string $namespace = '*'): array
    {
        $key = "{$locale}.{$namespace}";

        // Clear cache for accurate measurement
        Cache::forget("{$this->cachePrefix}.{$key}");

        // Measure first load (uncached)
        $startTime = microtime(true);
        $translations = $this->load($locale, $namespace);
        $firstLoadTime = (microtime(true) - $startTime) * 1000;

        // Measure second load (cached)
        $startTime = microtime(true);
        $translations = $this->load($locale, $namespace);
        $secondLoadTime = (microtime(true) - $startTime) * 1000;

        return [
            'locale' => $locale,
            'namespace' => $namespace,
            'first_load_ms' => round($firstLoadTime, 2),
            'second_load_ms' => round($secondLoadTime, 2),
            'translation_count' => count($translations, COUNT_RECURSIVE),
            'size_bytes' => strlen(json_encode($translations)),
            'size_kb' => round(strlen(json_encode($translations)) / 1024, 2),
            'cache_improvement_percent' => round((($firstLoadTime - $secondLoadTime) / $firstLoadTime) * 100, 2),
        ];
    }

    /**
     * Benchmark all locale + namespace combinations.
     */
    public function benchmark(): array
    {
        $locales = array_keys($this->localeManager->getAvailableLocales());
        $namespaces = ['ui', 'validation', 'auth', 'components'];

        $results = [
            'total_locales' => count($locales),
            'total_namespaces' => count($namespaces),
            'total_combinations' => count($locales) * count($namespaces),
            'measurements' => [],
            'summary' => [
                'avg_first_load_ms' => 0,
                'avg_second_load_ms' => 0,
                'avg_improvement_percent' => 0,
                'total_size_kb' => 0,
            ],
        ];

        $totalFirstLoad = 0;
        $totalSecondLoad = 0;
        $totalSize = 0;
        $count = 0;

        foreach ($locales as $locale) {
            foreach ($namespaces as $namespace) {
                $measurement = $this->measurePerformance($locale, $namespace);
                $results['measurements'][] = $measurement;

                $totalFirstLoad += $measurement['first_load_ms'];
                $totalSecondLoad += $measurement['second_load_ms'];
                $totalSize += $measurement['size_bytes'];
                $count++;
            }
        }

        $results['summary']['avg_first_load_ms'] = round($totalFirstLoad / $count, 2);
        $results['summary']['avg_second_load_ms'] = round($totalSecondLoad / $count, 2);
        $results['summary']['avg_improvement_percent'] = round((($totalFirstLoad - $totalSecondLoad) / $totalFirstLoad) * 100, 2);
        $results['summary']['total_size_kb'] = round($totalSize / 1024, 2);

        return $results;
    }

    /**
     * Get memory usage estimate.
     */
    public function getMemoryUsageEstimate(): array
    {
        $totalSize = 0;

        foreach ($this->loadedTranslations as $key => $translations) {
            $totalSize += strlen(json_encode($translations));
        }

        return [
            'loaded_count' => count($this->loadedTranslations),
            'total_size_bytes' => $totalSize,
            'total_size_kb' => round($totalSize / 1024, 2),
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'avg_size_per_translation_kb' => count($this->loadedTranslations) > 0 ? round(($totalSize / 1024) / count($this->loadedTranslations), 2) : 0,
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
