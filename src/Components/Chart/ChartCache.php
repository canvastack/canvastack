<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Chart;

use Illuminate\Support\Facades\Cache;

/**
 * ChartCache - Caching layer for chart data.
 *
 * Provides efficient caching for chart data and rendered output.
 * Uses Redis as primary cache store with file fallback.
 *
 * Features:
 * - Data caching with TTL
 * - Tag-based cache invalidation
 * - Cache key generation
 * - Cache statistics
 */
class ChartCache
{
    /**
     * Cache tag for all chart caches.
     */
    protected const CACHE_TAG = 'charts';

    /**
     * Default cache TTL in minutes.
     */
    protected const DEFAULT_TTL = 60;

    /**
     * Cache a chart's data.
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int|null $ttl Time to live in minutes (null = default)
     * @return bool Success status
     */
    public function put(string $key, $data, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? self::DEFAULT_TTL;
        $cacheKey = $this->generateKey($key);

        try {
            if ($this->supportsTagging()) {
                Cache::tags([self::CACHE_TAG])->put($cacheKey, $data, now()->addMinutes($ttl));
            } else {
                Cache::put($cacheKey, $data, now()->addMinutes($ttl));
            }

            return true;
        } catch (\Exception $e) {
            // Log error but don't fail
            \Log::warning('Chart cache put failed', [
                'key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get cached chart data.
     *
     * @param string $key Cache key
     * @return mixed|null Cached data or null if not found
     */
    public function get(string $key)
    {
        $cacheKey = $this->generateKey($key);

        try {
            if ($this->supportsTagging()) {
                return Cache::tags([self::CACHE_TAG])->get($cacheKey);
            }

            return Cache::get($cacheKey);
        } catch (\Exception $e) {
            \Log::warning('Chart cache get failed', [
                'key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Check if cache key exists.
     *
     * @param string $key Cache key
     * @return bool
     */
    public function has(string $key): bool
    {
        $cacheKey = $this->generateKey($key);

        try {
            if ($this->supportsTagging()) {
                return Cache::tags([self::CACHE_TAG])->has($cacheKey);
            }

            return Cache::has($cacheKey);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remember (get or put) cached data.
     *
     * @param string $key Cache key
     * @param int|null $ttl Time to live in minutes
     * @param callable $callback Callback to generate data if not cached
     * @return mixed Cached or generated data
     */
    public function remember(string $key, ?int $ttl, callable $callback)
    {
        $ttl = $ttl ?? self::DEFAULT_TTL;
        $cacheKey = $this->generateKey($key);

        try {
            if ($this->supportsTagging()) {
                return Cache::tags([self::CACHE_TAG])->remember(
                    $cacheKey,
                    now()->addMinutes($ttl),
                    $callback
                );
            }

            return Cache::remember($cacheKey, now()->addMinutes($ttl), $callback);
        } catch (\Exception $e) {
            \Log::warning('Chart cache remember failed', [
                'key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);

            // Execute callback directly if cache fails
            return $callback();
        }
    }

    /**
     * Forget (delete) cached data.
     *
     * @param string $key Cache key
     * @return bool Success status
     */
    public function forget(string $key): bool
    {
        $cacheKey = $this->generateKey($key);

        try {
            if ($this->supportsTagging()) {
                return Cache::tags([self::CACHE_TAG])->forget($cacheKey);
            }

            return Cache::forget($cacheKey);
        } catch (\Exception $e) {
            \Log::warning('Chart cache forget failed', [
                'key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Flush all chart caches.
     *
     * @return bool Success status
     */
    public function flush(): bool
    {
        try {
            if ($this->supportsTagging()) {
                Cache::tags([self::CACHE_TAG])->flush();

                return true;
            }

            // If tagging not supported, we can't flush selectively
            // Return false to indicate limitation
            return false;
        } catch (\Exception $e) {
            \Log::warning('Chart cache flush failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Generate cache key with prefix.
     *
     * @param string $key Base key
     * @return string Prefixed cache key
     */
    protected function generateKey(string $key): string
    {
        return 'chart.' . $key;
    }

    /**
     * Generate cache key from chart configuration.
     *
     * Creates a unique cache key based on chart type, data, and options.
     *
     * @param string $type Chart type
     * @param array $data Chart data
     * @param array $options Chart options
     * @return string Generated cache key
     */
    public function generateKeyFromConfig(string $type, array $data, array $options = []): string
    {
        $hash = md5(serialize([
            'type' => $type,
            'data' => $data,
            'options' => $options,
        ]));

        return $type . '.' . $hash;
    }

    /**
     * Check if cache driver supports tagging.
     *
     * @return bool
     */
    protected function supportsTagging(): bool
    {
        $driver = config('cache.default');

        // Redis and Memcached support tagging
        return in_array($driver, ['redis', 'memcached']);
    }

    /**
     * Get cache statistics.
     *
     * @return array Cache statistics
     */
    public function getStats(): array
    {
        // This is a basic implementation
        // More detailed stats would require cache driver support
        return [
            'driver' => config('cache.default'),
            'supports_tagging' => $this->supportsTagging(),
            'tag' => self::CACHE_TAG,
            'default_ttl' => self::DEFAULT_TTL,
        ];
    }

    /**
     * Warm up cache with data.
     *
     * Pre-populate cache with chart data.
     *
     * @param array $charts Array of ['key' => $key, 'data' => $data, 'ttl' => $ttl]
     * @return int Number of successfully cached items
     */
    public function warmUp(array $charts): int
    {
        $count = 0;

        foreach ($charts as $chart) {
            $key = $chart['key'] ?? null;
            $data = $chart['data'] ?? null;
            $ttl = $chart['ttl'] ?? null;

            if ($key && $data !== null) {
                if ($this->put($key, $data, $ttl)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Get cache TTL for a key.
     *
     * Note: Not all cache drivers support getting TTL.
     *
     * @param string $key Cache key
     * @return int|null TTL in seconds, or null if not available
     */
    public function getTtl(string $key): ?int
    {
        // This would require driver-specific implementation
        // For now, return null as not all drivers support this
        return null;
    }

    /**
     * Extend cache TTL.
     *
     * @param string $key Cache key
     * @param int $minutes Additional minutes to add
     * @return bool Success status
     */
    public function extend(string $key, int $minutes): bool
    {
        $data = $this->get($key);

        if ($data === null) {
            return false;
        }

        // Get current TTL (if available) and add minutes
        // For simplicity, we'll just re-cache with new TTL
        return $this->put($key, $data, $minutes);
    }
}
