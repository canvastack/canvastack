<?php

namespace Canvastack\Canvastack\Support\Cache;

use Illuminate\Support\Facades\Config;

/**
 * ConfigCache.
 *
 * Provides caching for configuration values to improve performance
 * by reducing repeated config file reads.
 */
class ConfigCache
{
    /**
     * Cache manager instance.
     *
     * @var CacheManager
     */
    protected CacheManager $cache;

    /**
     * In-memory cache for current request.
     *
     * @var array
     */
    protected static array $memoryCache = [];

    /**
     * Constructor.
     *
     * @param CacheManager $cache
     */
    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get configuration value with caching.
     *
     * @param string $key Configuration key (e.g., 'canvastack.cache.enabled')
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Check memory cache first (fastest)
        if (isset(static::$memoryCache[$key])) {
            return static::$memoryCache[$key];
        }

        // Check persistent cache
        $cacheKey = "config:{$key}";
        
        // Check if cache driver supports tagging
        if ($this->cache->supportsTags()) {
            $value = $this->cache->tags(['config'])->get($cacheKey);

            if ($value === null) {
                // Cache miss - get from config and cache it
                $value = Config::get($key, $default);
                $this->cache->tags(['config'])->put($cacheKey, $value, 3600);
            }
        } else {
            // Fallback to non-tagged cache
            $value = $this->cache->get($cacheKey);

            if ($value === null) {
                // Cache miss - get from config and cache it
                $value = Config::get($key, $default);
                $this->cache->put($cacheKey, $value, 3600);
            }
        }

        // Store in memory cache for this request
        static::$memoryCache[$key] = $value;

        return $value;
    }

    /**
     * Get all configuration for a namespace.
     *
     * @param string $namespace Configuration namespace (e.g., 'canvastack')
     * @return array
     */
    public function getAll(string $namespace): array
    {
        $cacheKey = "config:all:{$namespace}";

        if ($this->cache->supportsTags()) {
            return $this->cache->tags(['config'])->remember(
                $cacheKey,
                3600,
                fn () => Config::get($namespace, [])
            );
        }

        // Fallback to non-tagged cache
        return $this->cache->remember(
            $cacheKey,
            3600,
            fn () => Config::get($namespace, [])
        );
    }

    /**
     * Invalidate configuration cache.
     *
     * @param string|null $key Specific key to invalidate, or null for all
     * @return bool
     */
    public function invalidate(?string $key = null): bool
    {
        // Clear memory cache
        if ($key) {
            unset(static::$memoryCache[$key]);
            $cacheKey = "config:{$key}";

            if ($this->cache->supportsTags()) {
                return $this->cache->tags(['config'])->forget($cacheKey);
            }

            // Fallback to non-tagged cache
            return $this->cache->forget($cacheKey);
        }

        // Clear all config cache
        static::$memoryCache = [];

        return $this->cache->flush(['config']);
    }

    /**
     * Warm up configuration cache.
     *
     * Pre-cache commonly used configuration values
     *
     * @return void
     */
    public function warmUp(): void
    {
        $commonConfigs = [
            'canvastack.cache.enabled',
            'canvastack.cache.driver',
            'canvastack.performance.chunk_size',
            'canvastack.performance.eager_load',
            'canvastack-ui.theme.colors',
            'canvastack-ui.dark_mode.enabled',
            'canvastack-rbac.authorization.super_admin_bypass',
            'canvastack-rbac.cache.enabled',
        ];

        foreach ($commonConfigs as $key) {
            $this->get($key);
        }
    }

    /**
     * Get cache statistics.
     *
     * @return array
     */
    public function stats(): array
    {
        return [
            'memory_cache_size' => count(static::$memoryCache),
            'memory_cache_keys' => array_keys(static::$memoryCache),
        ];
    }
}
