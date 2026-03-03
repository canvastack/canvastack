<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Contracts;

/**
 * Cache Store Interface
 * 
 * Defines the contract for cache store implementations.
 */
interface CacheStoreInterface
{
    /**
     * Retrieve an item from the cache.
     */
    public function get(string $key, array $tags = []): mixed;

    /**
     * Store an item in the cache.
     */
    public function put(string $key, mixed $value, int $ttl, array $tags = []): bool;

    /**
     * Store an item in the cache indefinitely.
     */
    public function forever(string $key, mixed $value, array $tags = []): bool;

    /**
     * Determine if an item exists in the cache.
     */
    public function has(string $key, array $tags = []): bool;

    /**
     * Remove an item from the cache.
     */
    public function forget(string $key, array $tags = []): bool;

    /**
     * Flush items with the given tags.
     */
    public function flush(array $tags = []): bool;

    /**
     * Increment the value of an item in the cache.
     */
    public function increment(string $key, int $value = 1): int|bool;

    /**
     * Decrement the value of an item in the cache.
     */
    public function decrement(string $key, int $value = 1): int|bool;

    /**
     * Get cache statistics.
     */
    public function getStats(): array;

    /**
     * Clear all cache.
     */
    public function clear(): bool;
}
