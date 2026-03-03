<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Cache;

use Canvastack\Canvastack\Support\Cache\Stores\RedisStore;
use Canvastack\Canvastack\Support\Cache\Stores\FileStore;
use Canvastack\Canvastack\Contracts\CacheStoreInterface;

/**
 * Cache Manager
 * 
 * Manages caching with multiple store support (Redis, File).
 * Provides tag-based caching and automatic fallback.
 */
class CacheManager
{
    /**
     * Cache store instance.
     */
    protected CacheStoreInterface $store;

    /**
     * Current cache tags.
     */
    protected array $tags = [];

    /**
     * Cache configuration.
     */
    protected array $config;

    /**
     * Create a new cache manager instance.
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'driver' => 'redis',
            'prefix' => 'canvastack',
            'ttl' => 3600,
            'redis' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'database' => 0,
            ],
            'file' => [
                'path' => storage_path('framework/cache/data'),
            ],
        ], $config);

        $this->store = $this->createStore();
    }

    /**
     * Create cache store based on configuration.
     */
    protected function createStore(): CacheStoreInterface
    {
        $driver = $this->config['driver'];

        try {
            if ($driver === 'redis') {
                return new RedisStore($this->config['redis'], $this->config['prefix']);
            }

            return new FileStore($this->config['file']['path'], $this->config['prefix']);
        } catch (\Exception $e) {
            // Fallback to file store if Redis fails
            return new FileStore($this->config['file']['path'], $this->config['prefix']);
        }
    }

    /**
     * Set cache tags for the next operation.
     */
    public function tags(array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * Get an item from the cache.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->store->get($this->prefixKey($key), $this->tags);
        
        $this->resetTags();
        
        return $value !== null ? $value : $default;
    }

    /**
     * Store an item in the cache.
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->config['ttl'];
        
        $result = $this->store->put($this->prefixKey($key), $value, $ttl, $this->tags);
        
        $this->resetTags();
        
        return $result;
    }

    /**
     * Store an item in the cache indefinitely.
     */
    public function forever(string $key, mixed $value): bool
    {
        $result = $this->store->forever($this->prefixKey($key), $value, $this->tags);
        
        $this->resetTags();
        
        return $result;
    }

    /**
     * Retrieve an item from the cache or store the default value.
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        
        $this->put($key, $value, $ttl);

        return $value;
    }

    /**
     * Retrieve an item from the cache or store it forever.
     */
    public function rememberForever(string $key, callable $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        
        $this->forever($key, $value);

        return $value;
    }

    /**
     * Determine if an item exists in the cache.
     */
    public function has(string $key): bool
    {
        $result = $this->store->has($this->prefixKey($key), $this->tags);
        
        $this->resetTags();
        
        return $result;
    }

    /**
     * Remove an item from the cache.
     */
    public function forget(string $key): bool
    {
        $result = $this->store->forget($this->prefixKey($key), $this->tags);
        
        $this->resetTags();
        
        return $result;
    }

    /**
     * Flush all items with the given tags.
     */
    public function flush(?array $tags = null): bool
    {
        $tags = $tags ?? $this->tags;
        
        $result = $this->store->flush($tags);
        
        $this->resetTags();
        
        return $result;
    }

    /**
     * Increment the value of an item in the cache.
     */
    public function increment(string $key, int $value = 1): int|bool
    {
        $result = $this->store->increment($this->prefixKey($key), $value);
        
        $this->resetTags();
        
        return $result;
    }

    /**
     * Decrement the value of an item in the cache.
     */
    public function decrement(string $key, int $value = 1): int|bool
    {
        $result = $this->store->decrement($this->prefixKey($key), $value);
        
        $this->resetTags();
        
        return $result;
    }

    /**
     * Get the cache store instance.
     */
    public function getStore(): CacheStoreInterface
    {
        return $this->store;
    }

    /**
     * Get cache statistics.
     */
    public function getStats(): array
    {
        return $this->store->getStats();
    }

    /**
     * Clear all cache.
     */
    public function clear(): bool
    {
        return $this->store->clear();
    }

    /**
     * Prefix the cache key.
     */
    protected function prefixKey(string $key): string
    {
        return $key;
    }

    /**
     * Reset tags after operation.
     */
    protected function resetTags(): void
    {
        $this->tags = [];
    }
}
