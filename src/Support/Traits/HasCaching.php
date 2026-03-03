<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Traits;

use Illuminate\Support\Facades\Cache;

/**
 * Has Caching Trait.
 *
 * Provides caching functionality with constants.
 * Uses PHP 8.2 constants in traits feature.
 */
trait HasCaching
{
    /**
     * Default cache TTL in seconds.
     */
    public const CACHE_TTL = 3600;

    /**
     * Cache prefix for all keys.
     */
    public const CACHE_PREFIX = 'canvastack:';

    /**
     * Cache tags.
     */
    public const CACHE_TAGS = ['canvastack'];

    /**
     * Get cache key with prefix.
     *
     * @param string $key
     * @return string
     */
    protected function getCacheKey(string $key): string
    {
        return self::CACHE_PREFIX . $key;
    }

    /**
     * Get value from cache.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getFromCache(string $key, mixed $default = null): mixed
    {
        return Cache::tags(self::CACHE_TAGS)->get($this->getCacheKey($key), $default);
    }

    /**
     * Put value in cache.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    protected function putInCache(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? self::CACHE_TTL;

        return Cache::tags(self::CACHE_TAGS)->put($this->getCacheKey($key), $value, $ttl);
    }

    /**
     * Remember value in cache.
     *
     * @param string $key
     * @param callable $callback
     * @param int|null $ttl
     * @return mixed
     */
    protected function rememberInCache(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $ttl = $ttl ?? self::CACHE_TTL;

        return Cache::tags(self::CACHE_TAGS)->remember($this->getCacheKey($key), $ttl, $callback);
    }

    /**
     * Forget value from cache.
     *
     * @param string $key
     * @return bool
     */
    protected function forgetFromCache(string $key): bool
    {
        return Cache::tags(self::CACHE_TAGS)->forget($this->getCacheKey($key));
    }

    /**
     * Flush all cache with tags.
     *
     * @return bool
     */
    protected function flushCache(): bool
    {
        return Cache::tags(self::CACHE_TAGS)->flush();
    }

    /**
     * Check if key exists in cache.
     *
     * @param string $key
     * @return bool
     */
    protected function hasInCache(string $key): bool
    {
        return Cache::tags(self::CACHE_TAGS)->has($this->getCacheKey($key));
    }
}
