<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use Canvastack\Canvastack\Contracts\ThemeInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Lazy Theme Loader.
 *
 * Implements lazy loading strategy for themes to improve
 * initial load performance by deferring theme loading until needed.
 */
class LazyThemeLoader
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
    protected string $cachePrefix = 'canvastack.theme.lazy';

    /**
     * Loaded themes registry.
     */
    protected array $loadedThemes = [];

    /**
     * Loading queue.
     */
    protected array $loadingQueue = [];

    /**
     * Constructor.
     */
    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    /**
     * Lazy load a theme.
     *
     * Returns a promise/proxy that loads the theme only when accessed.
     */
    public function load(string $themeName): ThemeInterface
    {
        // Check if already loaded
        if (isset($this->loadedThemes[$themeName])) {
            return $this->loadedThemes[$themeName];
        }

        // Check cache first
        $cacheKey = "{$this->cachePrefix}.{$themeName}";
        $cachedTheme = Cache::get($cacheKey);

        if ($cachedTheme !== null) {
            $this->loadedThemes[$themeName] = $cachedTheme;

            return $cachedTheme;
        }

        // Load theme
        $theme = $this->themeManager->get($themeName);

        // Cache the theme
        Cache::put($cacheKey, $theme, $this->cacheTtl);

        // Store in loaded registry
        $this->loadedThemes[$themeName] = $theme;

        return $theme;
    }

    /**
     * Queue a theme for lazy loading.
     */
    public function queue(string $themeName): void
    {
        if (!in_array($themeName, $this->loadingQueue)) {
            $this->loadingQueue[] = $themeName;
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

        foreach ($this->loadingQueue as $themeName) {
            try {
                $this->load($themeName);
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
     * Lazy load theme CSS.
     */
    public function loadCss(string $themeName): string
    {
        $cacheKey = "{$this->cachePrefix}.css.{$themeName}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($themeName) {
            $theme = $this->load($themeName);

            return $this->themeManager->generateCss($themeName);
        });
    }

    /**
     * Lazy load theme config.
     */
    public function loadConfig(string $themeName): array
    {
        $cacheKey = "{$this->cachePrefix}.config.{$themeName}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($themeName) {
            $theme = $this->load($themeName);

            return $theme->toArray();
        });
    }

    /**
     * Lazy load theme metadata only (lightweight).
     */
    public function loadMetadata(string $themeName): array
    {
        $cacheKey = "{$this->cachePrefix}.metadata.{$themeName}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($themeName) {
            $theme = $this->load($themeName);

            return [
                'name' => $theme->getName(),
                'display_name' => $theme->getDisplayName(),
                'version' => $theme->getVersion(),
                'author' => $theme->getAuthor(),
                'description' => $theme->getDescription(),
            ];
        });
    }

    /**
     * Lazy load all theme metadata (for theme selector).
     */
    public function loadAllMetadata(): array
    {
        $cacheKey = "{$this->cachePrefix}.all_metadata";

        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            $themes = $this->themeManager->names();
            $metadata = [];

            foreach ($themes as $themeName) {
                $metadata[$themeName] = $this->loadMetadata($themeName);
            }

            return $metadata;
        });
    }

    /**
     * Check if a theme is loaded.
     */
    public function isLoaded(string $themeName): bool
    {
        return isset($this->loadedThemes[$themeName]);
    }

    /**
     * Get loaded themes count.
     */
    public function getLoadedCount(): int
    {
        return count($this->loadedThemes);
    }

    /**
     * Get queued themes count.
     */
    public function getQueuedCount(): int
    {
        return count($this->loadingQueue);
    }

    /**
     * Unload a theme from memory.
     */
    public function unload(string $themeName): void
    {
        unset($this->loadedThemes[$themeName]);
    }

    /**
     * Unload all themes from memory.
     */
    public function unloadAll(): void
    {
        $this->loadedThemes = [];
    }

    /**
     * Clear lazy loading cache.
     */
    public function clearCache(): void
    {
        $themes = $this->themeManager->names();

        foreach ($themes as $themeName) {
            Cache::forget("{$this->cachePrefix}.{$themeName}");
            Cache::forget("{$this->cachePrefix}.css.{$themeName}");
            Cache::forget("{$this->cachePrefix}.config.{$themeName}");
            Cache::forget("{$this->cachePrefix}.metadata.{$themeName}");
        }

        Cache::forget("{$this->cachePrefix}.all_metadata");

        $this->unloadAll();
        $this->loadingQueue = [];
    }

    /**
     * Get cache statistics.
     */
    public function getCacheStats(): array
    {
        $themes = $this->themeManager->names();
        $cachedThemes = 0;
        $cachedCss = 0;
        $cachedConfig = 0;
        $cachedMetadata = 0;

        foreach ($themes as $themeName) {
            if (Cache::has("{$this->cachePrefix}.{$themeName}")) {
                $cachedThemes++;
            }
            if (Cache::has("{$this->cachePrefix}.css.{$themeName}")) {
                $cachedCss++;
            }
            if (Cache::has("{$this->cachePrefix}.config.{$themeName}")) {
                $cachedConfig++;
            }
            if (Cache::has("{$this->cachePrefix}.metadata.{$themeName}")) {
                $cachedMetadata++;
            }
        }

        $total = count($themes);

        return [
            'total_themes' => $total,
            'loaded_themes' => count($this->loadedThemes),
            'queued_themes' => count($this->loadingQueue),
            'cached_themes' => $cachedThemes,
            'cached_css' => $cachedCss,
            'cached_config' => $cachedConfig,
            'cached_metadata' => $cachedMetadata,
            'theme_cache_ratio' => $total > 0 ? round(($cachedThemes / $total) * 100, 2) : 0,
            'css_cache_ratio' => $total > 0 ? round(($cachedCss / $total) * 100, 2) : 0,
            'config_cache_ratio' => $total > 0 ? round(($cachedConfig / $total) * 100, 2) : 0,
            'metadata_cache_ratio' => $total > 0 ? round(($cachedMetadata / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Measure lazy loading performance.
     */
    public function measurePerformance(string $themeName): array
    {
        // Clear cache for accurate measurement
        Cache::forget("{$this->cachePrefix}.{$themeName}");
        Cache::forget("{$this->cachePrefix}.css.{$themeName}");
        Cache::forget("{$this->cachePrefix}.config.{$themeName}");
        Cache::forget("{$this->cachePrefix}.metadata.{$themeName}");

        // Measure first load (uncached)
        $startTime = microtime(true);
        $theme = $this->load($themeName);
        $firstLoadTime = (microtime(true) - $startTime) * 1000;

        // Measure second load (cached)
        $startTime = microtime(true);
        $theme = $this->load($themeName);
        $secondLoadTime = (microtime(true) - $startTime) * 1000;

        // Measure CSS load
        $startTime = microtime(true);
        $css = $this->loadCss($themeName);
        $cssLoadTime = (microtime(true) - $startTime) * 1000;

        // Measure config load
        $startTime = microtime(true);
        $config = $this->loadConfig($themeName);
        $configLoadTime = (microtime(true) - $startTime) * 1000;

        // Measure metadata load
        $startTime = microtime(true);
        $metadata = $this->loadMetadata($themeName);
        $metadataLoadTime = (microtime(true) - $startTime) * 1000;

        return [
            'theme' => $themeName,
            'first_load_ms' => round($firstLoadTime, 2),
            'second_load_ms' => round($secondLoadTime, 2),
            'css_load_ms' => round($cssLoadTime, 2),
            'config_load_ms' => round($configLoadTime, 2),
            'metadata_load_ms' => round($metadataLoadTime, 2),
            'cache_improvement_percent' => round((($firstLoadTime - $secondLoadTime) / $firstLoadTime) * 100, 2),
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
