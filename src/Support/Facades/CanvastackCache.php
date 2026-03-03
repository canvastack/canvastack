<?php

namespace Canvastack\Canvastack\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * CanvastackCache Facade.
 *
 * Provides easy access to the CacheManager
 *
 * @method static mixed remember(string $key, callable $callback, ?string $component = null, ?int $ttl = null)
 * @method static bool put(string $key, mixed $value, ?string $component = null, ?int $ttl = null)
 * @method static mixed get(string $key, mixed $default = null, ?string $component = null)
 * @method static bool has(string $key, ?string $component = null)
 * @method static bool forget(string $key, ?string $component = null)
 * @method static bool flush(string $component)
 * @method static bool flushAll()
 * @method static string key(string $key, ?string $component = null)
 * @method static array stats()
 *
 * @see \Canvastack\Canvastack\Support\Cache\CacheManager
 */
class CanvastackCache extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'canvastack.cache';
    }
}
