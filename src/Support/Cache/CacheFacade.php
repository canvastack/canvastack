<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Cache;

use Illuminate\Support\Facades\Cache as LaravelCache;

/**
 * Cache Facade with Tagging Support Fallback.
 * 
 * This facade wraps Laravel's Cache facade and provides automatic fallback
 * for cache drivers that don't support tagging (array, file).
 */
class CacheFacade
{
    /**
     * Check if current cache driver supports tagging.
     */
    public static function supportsTags(): bool
    {
        try {
            $store = LaravelCache::getStore();
            return method_exists($store, 'tags');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get cache with tags support fallback.
     */
    public static function tags(array $tags)
    {
        if (static::supportsTags()) {
            return LaravelCache::tags($tags);
        }

        // Fallback: Return cache instance without tags
        return LaravelCache::store();
    }

    /**
     * Forward all other calls to Laravel Cache.
     */
    public static function __callStatic($method, $parameters)
    {
        return LaravelCache::$method(...$parameters);
    }
}
