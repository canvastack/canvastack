<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * TableCacheManager
 * 
 * Manages caching for table data, filter options, and relationship data.
 * Provides intelligent cache key generation and selective cache invalidation.
 * 
 * @package Canvastack\Canvastack\Components\Table\Support
 */
class TableCacheManager
{
    /**
     * Default cache time in seconds (5 minutes).
     */
    protected const DEFAULT_CACHE_TIME = 300;

    /**
     * Cache tag prefix for tables.
     */
    protected const CACHE_TAG_PREFIX = 'table';

    /**
     * Cache tag for filter options.
     */
    protected const FILTER_TAG = 'filters';

    /**
     * Cache tag for relationships.
     */
    protected const RELATIONSHIP_TAG = 'relationships';

    /**
     * Get cached query results.
     * 
     * @param string $cacheKey Cache key
     * @param array $cacheTags Cache tags for selective invalidation
     * @param int $cacheTime Cache time in seconds
     * @param callable $callback Callback to execute if cache miss
     * @return array Cached or fresh data
     */
    public function getCachedData(
        string $cacheKey,
        array $cacheTags,
        int $cacheTime,
        callable $callback
    ): array {
        // If cache time is 0 or negative, don't cache
        if ($cacheTime <= 0) {
            return $callback();
        }

        // Try to get from cache with tags
        return Cache::tags($cacheTags)->remember(
            $cacheKey,
            $cacheTime,
            $callback
        );
    }

    /**
     * Get cached filter options.
     * 
     * @param string $filterName Filter name
     * @param string|null $modelClass Model class name for tagging
     * @param int|null $cacheTime Cache time in seconds (null = default)
     * @param callable $callback Callback to execute if cache miss
     * @return array Filter options
     */
    public function getCachedFilterOptions(
        string $filterName,
        ?string $modelClass,
        ?int $cacheTime,
        callable $callback
    ): array {
        $cacheKey = $this->buildFilterCacheKey($filterName, $modelClass);
        $cacheTags = $this->buildFilterCacheTags($modelClass);
        $time = $cacheTime ?? self::DEFAULT_CACHE_TIME;

        return $this->getCachedData($cacheKey, $cacheTags, $time, $callback);
    }

    /**
     * Get cached relationship data.
     * 
     * @param string $relationName Relationship name
     * @param string|null $modelClass Model class name for tagging
     * @param int|null $cacheTime Cache time in seconds (null = default)
     * @param callable $callback Callback to execute if cache miss
     * @return Collection Relationship data
     */
    public function getCachedRelationship(
        string $relationName,
        ?string $modelClass,
        ?int $cacheTime,
        callable $callback
    ): Collection {
        $cacheKey = $this->buildRelationshipCacheKey($relationName, $modelClass);
        $cacheTags = $this->buildRelationshipCacheTags($modelClass);
        $time = $cacheTime ?? self::DEFAULT_CACHE_TIME;

        // Wrap callback to ensure array return for getCachedData
        $wrappedCallback = function () use ($callback) {
            $result = $callback();
            return $result instanceof Collection ? $result->toArray() : $result;
        };

        $data = $this->getCachedData($cacheKey, $cacheTags, $time, $wrappedCallback);

        // Ensure we return a Collection
        return $data instanceof Collection ? $data : collect($data);
    }

    /**
     * Build cache key for table data.
     * 
     * @param array $params Parameters to include in cache key
     * @return string Cache key
     */
    public function buildTableCacheKey(array $params): string
    {
        // Sort params for consistent key generation
        ksort($params);

        // Generate hash from serialized params
        $hash = md5(serialize($params));

        return self::CACHE_TAG_PREFIX . '.' . $hash;
    }

    /**
     * Build cache key for filter options.
     * 
     * @param string $filterName Filter name
     * @param string|null $modelClass Model class name
     * @return string Cache key
     */
    protected function buildFilterCacheKey(string $filterName, ?string $modelClass): string
    {
        $parts = [self::CACHE_TAG_PREFIX, self::FILTER_TAG, $filterName];

        if ($modelClass) {
            $parts[] = $this->getTableNameFromModel($modelClass);
        }

        return implode('.', $parts);
    }

    /**
     * Build cache key for relationship data.
     * 
     * @param string $relationName Relationship name
     * @param string|null $modelClass Model class name
     * @return string Cache key
     */
    protected function buildRelationshipCacheKey(string $relationName, ?string $modelClass): string
    {
        $parts = [self::CACHE_TAG_PREFIX, self::RELATIONSHIP_TAG, $relationName];

        if ($modelClass) {
            $parts[] = $this->getTableNameFromModel($modelClass);
        }

        return implode('.', $parts);
    }

    /**
     * Build cache tags for table data.
     * 
     * @param string|null $modelClass Model class name
     * @return array Cache tags
     */
    public function buildTableCacheTags(?string $modelClass): array
    {
        $tags = ['tables'];

        if ($modelClass) {
            $tableName = $this->getTableNameFromModel($modelClass);
            $tags[] = self::CACHE_TAG_PREFIX . '.' . $tableName;
        }

        return $tags;
    }

    /**
     * Build cache tags for filter options.
     * 
     * @param string|null $modelClass Model class name
     * @return array Cache tags
     */
    protected function buildFilterCacheTags(?string $modelClass): array
    {
        $tags = ['tables', self::FILTER_TAG];

        if ($modelClass) {
            $tableName = $this->getTableNameFromModel($modelClass);
            $tags[] = self::CACHE_TAG_PREFIX . '.' . $tableName;
            $tags[] = self::FILTER_TAG . '.' . $tableName;
        }

        return $tags;
    }

    /**
     * Build cache tags for relationship data.
     * 
     * @param string|null $modelClass Model class name
     * @return array Cache tags
     */
    protected function buildRelationshipCacheTags(?string $modelClass): array
    {
        $tags = ['tables', self::RELATIONSHIP_TAG];

        if ($modelClass) {
            $tableName = $this->getTableNameFromModel($modelClass);
            $tags[] = self::CACHE_TAG_PREFIX . '.' . $tableName;
            $tags[] = self::RELATIONSHIP_TAG . '.' . $tableName;
        }

        return $tags;
    }

    /**
     * Clear all table caches.
     * 
     * @return void
     */
    public function clearAllTableCaches(): void
    {
        Cache::tags(['tables'])->flush();
    }

    /**
     * Clear cache for specific model.
     * 
     * @param string|Model $model Model class name or instance
     * @return void
     */
    public function clearModelCache(string|Model $model): void
    {
        $modelClass = is_string($model) ? $model : get_class($model);
        $tableName = $this->getTableNameFromModel($modelClass);

        Cache::tags([self::CACHE_TAG_PREFIX . '.' . $tableName])->flush();
    }

    /**
     * Clear filter caches for specific model.
     * 
     * @param string|Model $model Model class name or instance
     * @return void
     */
    public function clearFilterCache(string|Model $model): void
    {
        $modelClass = is_string($model) ? $model : get_class($model);
        $tableName = $this->getTableNameFromModel($modelClass);

        Cache::tags([self::FILTER_TAG . '.' . $tableName])->flush();
    }

    /**
     * Clear relationship caches for specific model.
     * 
     * @param string|Model $model Model class name or instance
     * @return void
     */
    public function clearRelationshipCache(string|Model $model): void
    {
        $modelClass = is_string($model) ? $model : get_class($model);
        $tableName = $this->getTableNameFromModel($modelClass);

        Cache::tags([self::RELATIONSHIP_TAG . '.' . $tableName])->flush();
    }

    /**
     * Clear specific cache by key.
     * 
     * @param string $cacheKey Cache key
     * @return void
     */
    public function clearCacheByKey(string $cacheKey): void
    {
        Cache::forget($cacheKey);
    }

    /**
     * Clear cache by tags.
     * 
     * @param array $tags Cache tags
     * @return void
     */
    public function clearCacheByTags(array $tags): void
    {
        Cache::tags($tags)->flush();
    }

    /**
     * Get table name from model class.
     * 
     * @param string $modelClass Model class name
     * @return string Table name
     */
    protected function getTableNameFromModel(string $modelClass): string
    {
        try {
            // Try to instantiate model and get table name
            $model = new $modelClass();
            
            if ($model instanceof Model) {
                return $model->getTable();
            }
        } catch (\Throwable $e) {
            // If instantiation fails, fall back to class name
        }

        // Fallback: use class name as identifier
        return str_replace('\\', '_', strtolower($modelClass));
    }

    /**
     * Check if cache is enabled.
     * 
     * @param int|null $cacheTime Cache time
     * @return bool True if caching is enabled
     */
    public function isCacheEnabled(?int $cacheTime): bool
    {
        return $cacheTime !== null && $cacheTime > 0;
    }

    /**
     * Get default cache time.
     * 
     * @return int Default cache time in seconds
     */
    public function getDefaultCacheTime(): int
    {
        return self::DEFAULT_CACHE_TIME;
    }

    /**
     * Warm up cache with data.
     * 
     * @param string $cacheKey Cache key
     * @param array $cacheTags Cache tags
     * @param int $cacheTime Cache time in seconds
     * @param mixed $data Data to cache
     * @return void
     */
    public function warmCache(
        string $cacheKey,
        array $cacheTags,
        int $cacheTime,
        mixed $data
    ): void {
        if ($cacheTime > 0) {
            Cache::tags($cacheTags)->put($cacheKey, $data, $cacheTime);
        }
    }

    /**
     * Check if cache exists.
     * 
     * @param string $cacheKey Cache key
     * @param array $cacheTags Cache tags
     * @return bool True if cache exists
     */
    public function hasCachedData(string $cacheKey, array $cacheTags): bool
    {
        return Cache::tags($cacheTags)->has($cacheKey);
    }

    /**
     * Get cache statistics.
     * 
     * @return array Cache statistics
     */
    public function getCacheStats(): array
    {
        // Note: This is a basic implementation
        // Real statistics would require cache driver support
        return [
            'default_cache_time' => self::DEFAULT_CACHE_TIME,
            'cache_tag_prefix' => self::CACHE_TAG_PREFIX,
            'filter_tag' => self::FILTER_TAG,
            'relationship_tag' => self::RELATIONSHIP_TAG,
        ];
    }
}
