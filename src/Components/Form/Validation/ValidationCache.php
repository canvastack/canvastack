<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Validation;

use Illuminate\Support\Facades\Cache;

/**
 * ValidationCache - Caches compiled validation rules.
 *
 * Improves performance by caching validation rule compilation.
 */
class ValidationCache
{
    protected string $cachePrefix = 'form.validation.';

    protected int $defaultTtl = 3600; // 1 hour

    /**
     * Get cached validation rules.
     *
     * @param string $formIdentity
     * @return array|null
     */
    public function get(string $formIdentity): ?array
    {
        $cacheKey = $this->getCacheKey($formIdentity);

        return Cache::tags(['forms', 'validations'])->get($cacheKey);
    }

    /**
     * Store validation rules in cache.
     *
     * @param string $formIdentity
     * @param array $rules
     * @param int|null $ttl Time to live in seconds
     * @return bool
     */
    public function put(string $formIdentity, array $rules, ?int $ttl = null): bool
    {
        $cacheKey = $this->getCacheKey($formIdentity);
        $ttl = $ttl ?? $this->defaultTtl;

        return Cache::tags(['forms', 'validations'])->put($cacheKey, $rules, $ttl);
    }

    /**
     * Check if validation rules exist in cache.
     *
     * @param string $formIdentity
     * @return bool
     */
    public function has(string $formIdentity): bool
    {
        $cacheKey = $this->getCacheKey($formIdentity);

        return Cache::tags(['forms', 'validations'])->has($cacheKey);
    }

    /**
     * Remove validation rules from cache.
     *
     * @param string $formIdentity
     * @return bool
     */
    public function forget(string $formIdentity): bool
    {
        $cacheKey = $this->getCacheKey($formIdentity);

        return Cache::tags(['forms', 'validations'])->forget($cacheKey);
    }

    /**
     * Clear all validation caches.
     *
     * @return bool
     */
    public function flush(): bool
    {
        return Cache::tags(['validations'])->flush();
    }

    /**
     * Get or set validation rules with caching.
     *
     * @param string $formIdentity
     * @param callable $callback
     * @param int|null $ttl
     * @return array
     */
    public function remember(string $formIdentity, callable $callback, ?int $ttl = null): array
    {
        $cacheKey = $this->getCacheKey($formIdentity);
        $ttl = $ttl ?? $this->defaultTtl;

        return Cache::tags(['forms', 'validations'])->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Generate cache key for form identity.
     *
     * @param string $formIdentity
     * @return string
     */
    protected function getCacheKey(string $formIdentity): string
    {
        return $this->cachePrefix . md5($formIdentity);
    }

    /**
     * Set default TTL.
     *
     * @param int $ttl
     * @return void
     */
    public function setDefaultTtl(int $ttl): void
    {
        $this->defaultTtl = $ttl;
    }

    /**
     * Get default TTL.
     *
     * @return int
     */
    public function getDefaultTtl(): int
    {
        return $this->defaultTtl;
    }
}
