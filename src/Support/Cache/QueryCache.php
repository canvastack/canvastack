<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Cache;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * QueryCache - Caches database query results.
 *
 * Features:
 * - Automatic cache key generation
 * - Tag-based cache invalidation
 * - TTL configuration
 * - Query fingerprinting
 */
class QueryCache
{
    protected CacheManager $cacheManager;

    protected int $defaultTtl = 300; // 5 minutes

    protected array $tags = ['queries'];

    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Remember query results in cache.
     *
     * @param Builder $query
     * @param int|null $ttl Time to live in seconds
     * @param array<string> $tags Additional cache tags
     * @return mixed
     */
    public function remember(Builder $query, ?int $ttl = null, array $tags = [])
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $key = $this->generateKey($query);
        $allTags = array_merge($this->tags, $tags);

        return $this->cacheManager->tags($allTags)->remember($key, $ttl, function () use ($query) {
            return $query->get();
        });
    }

    /**
     * Remember a single query result in cache.
     *
     * @param Builder $query
     * @param int|null $ttl
     * @param array<string> $tags
     * @return mixed
     */
    public function rememberOne(Builder $query, ?int $ttl = null, array $tags = [])
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $key = $this->generateKey($query);
        $allTags = array_merge($this->tags, $tags);

        return $this->cacheManager->tags($allTags)->remember($key, $ttl, function () use ($query) {
            return $query->first();
        });
    }

    /**
     * Remember paginated query results in cache.
     *
     * @param Builder $query
     * @param int $perPage
     * @param int|null $ttl
     * @param array<string> $tags
     * @return mixed
     */
    public function rememberPaginated(Builder $query, int $perPage = 15, ?int $ttl = null, array $tags = [])
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $key = $this->generateKey($query) . ':page:' . request()->get('page', 1);
        $allTags = array_merge($this->tags, $tags);

        return $this->cacheManager->tags($allTags)->remember($key, $ttl, function () use ($query, $perPage) {
            return $query->paginate($perPage);
        });
    }

    /**
     * Remember count query result in cache.
     *
     * @param Builder $query
     * @param int|null $ttl
     * @param array<string> $tags
     * @return int
     */
    public function rememberCount(Builder $query, ?int $ttl = null, array $tags = []): int
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $key = $this->generateKey($query) . ':count';
        $allTags = array_merge($this->tags, $tags);

        return $this->cacheManager->tags($allTags)->remember($key, $ttl, function () use ($query) {
            return $query->count();
        });
    }

    /**
     * Generate cache key from query.
     *
     * @param Builder $query
     * @return string
     */
    protected function generateKey(Builder $query): string
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        $model = get_class($query->getModel());

        // Create fingerprint
        $fingerprint = md5($model . $sql . serialize($bindings));

        return "query:{$fingerprint}";
    }

    /**
     * Invalidate query cache by tags.
     *
     * @param array<string> $tags
     * @return bool
     */
    public function invalidate(array $tags = []): bool
    {
        $allTags = empty($tags) ? $this->tags : array_merge($this->tags, $tags);

        return $this->cacheManager->tags($allTags)->flush();
    }

    /**
     * Invalidate all query caches.
     *
     * @return bool
     */
    public function invalidateAll(): bool
    {
        return $this->cacheManager->tags($this->tags)->flush();
    }

    /**
     * Set default TTL.
     *
     * @param int $ttl
     * @return self
     */
    public function setDefaultTtl(int $ttl): self
    {
        $this->defaultTtl = $ttl;

        return $this;
    }

    /**
     * Set default tags.
     *
     * @param array<string> $tags
     * @return self
     */
    public function setTags(array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Add a tag.
     *
     * @param string $tag
     * @return self
     */
    public function addTag(string $tag): self
    {
        if (!in_array($tag, $this->tags)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    /**
     * Get cache statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        return [
            'default_ttl' => $this->defaultTtl,
            'tags' => $this->tags,
            'driver' => $this->cacheManager->getDriver(),
        ];
    }
}
