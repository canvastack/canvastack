<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Filter;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * FilterOptionsProvider - Provides filter options from database
 * 
 * Handles loading filter options with parent filter support for cascading.
 * Includes query optimization and caching.
 * 
 * @package Canvastack\Canvastack\Components\Table\Filter
 */
class FilterOptionsProvider
{
    /**
     * Cache TTL in seconds (5 minutes)
     * 
     * @var int
     */
    protected int $cacheTtl = 300;

    /**
     * Whether to use caching
     * 
     * @var bool
     */
    protected bool $cacheEnabled = true;

    /**
     * Maximum number of options to return (prevents memory issues)
     * 
     * @var int
     */
    protected int $maxOptions = 1000;

    /**
     * Whether to use query optimization
     * 
     * @var bool
     */
    protected bool $optimizationEnabled = true;

    /**
     * Cache prefix for filter options
     * 
     * @var string
     */
    protected string $cachePrefix = 'filter_options';

    /**
     * Constructor - Initialize with configuration values
     */
    public function __construct()
    {
        $this->cacheEnabled = config('canvastack.cache.filter_options.enabled', true);
        $this->cacheTtl = config('canvastack.cache.filter_options.ttl', 300);
        $this->cachePrefix = config('canvastack.cache.filter_options.prefix', 'filter_options');
        $this->optimizationEnabled = config('canvastack.performance.filter_optimization', true);
        $this->maxOptions = config('canvastack.performance.max_filter_options', 1000);
    }

    /**
     * Get filter options from database
     * 
     * Optimizations:
     * - Uses SELECT DISTINCT with indexed columns
     * - Limits result set to prevent memory issues
     * - Uses query builder for SQL injection prevention
     * - Caches results to reduce database load
     * 
     * @param string $table Table name
     * @param string $column Column name
     * @param array $parentFilters Parent filter values for cascading
     * @param string|null $connection Database connection name (null = default)
     * @return array
     */
    public function getOptions(string $table, string $column, array $parentFilters = [], ?string $connection = null): array
    {
        // Log the connection parameter for debugging
        \Log::info('FilterOptionsProvider::getOptions called', [
            'table' => $table,
            'column' => $column,
            'connection' => $connection,
            'parentFilters' => $parentFilters,
        ]);
        
        // Generate cache key (include connection in key)
        $cacheKey = $this->generateCacheKey($table, $column, $parentFilters, $connection);

        // Try to get from cache
        if ($this->cacheEnabled) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                \Log::info('FilterOptionsProvider: Returning cached options', [
                    'table' => $table,
                    'column' => $column,
                    'count' => count($cached),
                ]);
                return $cached;
            }
        }

        try {
            // Build optimized query with connection support
            $query = DB::connection($connection)->table($table)
                ->select($column)
                ->distinct()
                ->whereNotNull($column)
                ->where($column, '!=', ''); // Exclude empty strings

            // Apply parent filters with indexed columns
            foreach ($parentFilters as $col => $value) {
                if ($value !== null && $value !== '') {
                    $query->where($col, $value);
                }
            }

            // Optimization: Limit result set to prevent memory issues
            // Most filter dropdowns don't need more than configured max options
            if ($this->optimizationEnabled) {
                $query->limit($this->maxOptions);
            }

            // Get options with optimized query
            $options = $query
                ->orderBy($column)
                ->pluck($column)
                ->map(function ($value) {
                    return [
                        'value' => $value,
                        'label' => $value,
                    ];
                })
                ->values()
                ->toArray();

            // Cache the result
            if ($this->cacheEnabled) {
                Cache::put($cacheKey, $options, $this->cacheTtl);
            }

            return $options;
        } catch (\Exception $e) {
            // Log error for debugging
            \Log::error('FilterOptionsProvider error', [
                'table' => $table,
                'column' => $column,
                'connection' => $connection,
                'error' => $e->getMessage()
            ]);
            
            // Return empty array on error
            return [];
        }
    }

    /**
     * Set cache TTL
     * 
     * @param int $seconds Cache TTL in seconds
     * @return void
     */
    public function setCacheTtl(int $seconds): void
    {
        $this->cacheTtl = $seconds;
    }

    /**
     * Enable or disable caching
     * 
     * @param bool $enabled Whether to enable caching
     * @return void
     */
    public function setCacheEnabled(bool $enabled): void
    {
        $this->cacheEnabled = $enabled;
    }

    /**
     * Set maximum number of options
     * 
     * @param int $max Maximum number of options
     * @return void
     */
    public function setMaxOptions(int $max): void
    {
        $this->maxOptions = $max;
    }

    /**
     * Enable or disable query optimization
     * 
     * @param bool $enabled Whether to enable optimization
     * @return void
     */
    public function setOptimizationEnabled(bool $enabled): void
    {
        $this->optimizationEnabled = $enabled;
    }

    /**
     * Clear cache for specific table and column
     * 
     * @param string $table Table name
     * @param string $column Column name
     * @return void
     */
    public function clearCache(string $table, string $column): void
    {
        try {
            // Use cache tags if available (Redis/Memcached)
            $tags = config('canvastack.cache.filter_options.tags', []);
            
            // Check if cache driver supports tagging
            $store = Cache::getStore();
            $supportsTagging = $store instanceof \Illuminate\Cache\TaggableStore;
            
            if (!empty($tags) && $supportsTagging) {
                // Clear tagged cache entries
                Cache::tags($tags)->flush();
            } else {
                // Fallback: Clear specific cache key patterns
                // This is a simplified approach - in production you might want to track keys
                $baseKey = "{$this->cachePrefix}:{$table}:{$column}";
                
                // Try to clear common cache key variations
                $patterns = [
                    $this->generateCacheKey($table, $column, []),
                    $this->generateCacheKey($table, $column . '_count', []),
                ];
                
                foreach ($patterns as $key) {
                    Cache::forget($key);
                }
            }
        } catch (\BadMethodCallException $e) {
            // Cache driver doesn't support tagging, use fallback
            $baseKey = "{$this->cachePrefix}:{$table}:{$column}";
            
            $patterns = [
                $this->generateCacheKey($table, $column, []),
                $this->generateCacheKey($table, $column . '_count', []),
            ];
            
            foreach ($patterns as $key) {
                Cache::forget($key);
            }
        }
    }

    /**
     * Clear all filter options cache
     * 
     * @return void
     */
    public function clearAllCache(): void
    {
        try {
            // Use cache tags if available (Redis/Memcached)
            $tags = config('canvastack.cache.filter_options.tags', []);
            
            // Check if cache driver supports tagging
            $store = Cache::getStore();
            $supportsTagging = $store instanceof \Illuminate\Cache\TaggableStore;
            
            if (!empty($tags) && $supportsTagging) {
                // Clear all tagged cache entries
                Cache::tags($tags)->flush();
            } else {
                // Fallback: Clear entire cache (not recommended for production)
                // In production, you should track filter cache keys or use tags
                Cache::flush();
            }
        } catch (\BadMethodCallException $e) {
            // Cache driver doesn't support tagging, use fallback
            Cache::flush();
        }
    }

    /**
     * Generate cache key
     * 
     * @param string $table Table name
     * @param string $column Column name
     * @param array $parentFilters Parent filter values
     * @param string|null $connection Database connection name
     * @return string
     */
    public function generateCacheKey(string $table, string $column, array $parentFilters, ?string $connection = null): string
    {
        $filterHash = md5(json_encode($parentFilters));
        $connStr = $connection ? ":{$connection}" : '';
        return "{$this->cachePrefix}:{$table}:{$column}{$connStr}:{$filterHash}";
    }

    /**
     * Get options with custom query
     * 
     * Allows for more complex option loading with custom queries.
     * Includes query optimization best practices.
     * 
     * @param callable $queryCallback Query builder callback
     * @param string $valueColumn Column for option value
     * @param string|null $labelColumn Column for option label (defaults to value column)
     * @return array
     */
    public function getOptionsWithQuery(
        callable $queryCallback,
        string $valueColumn,
        ?string $labelColumn = null
    ): array {
        $labelColumn = $labelColumn ?? $valueColumn;

        $query = DB::query();
        $queryCallback($query);

        // Optimization: Add distinct and limit
        return $query
            ->distinct()
            ->limit(1000) // Prevent memory issues
            ->orderBy($labelColumn)
            ->get()
            ->map(function ($row) use ($valueColumn, $labelColumn) {
                return [
                    'value' => $row->$valueColumn,
                    'label' => $row->$labelColumn,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get options with count (for large datasets)
     * 
     * Returns options with count of records for each option.
     * Useful for showing how many records match each filter value.
     * 
     * @param string $table Table name
     * @param string $column Column name
     * @param array $parentFilters Parent filter values
     * @return array
     */
    public function getOptionsWithCount(string $table, string $column, array $parentFilters = []): array
    {
        // Generate cache key
        $cacheKey = $this->generateCacheKey($table, $column . '_count', $parentFilters);

        // Try to get from cache
        if ($this->cacheEnabled) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        // Build optimized query with count
        $query = DB::table($table)
            ->select($column, DB::raw('COUNT(*) as count'))
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->groupBy($column);

        // Apply parent filters
        foreach ($parentFilters as $col => $value) {
            if ($value !== null && $value !== '') {
                $query->where($col, $value);
            }
        }

        // Get options with count
        $options = $query
            ->orderBy($column)
            ->limit(1000)
            ->get()
            ->map(function ($row) use ($column) {
                return [
                    'value' => $row->$column,
                    'label' => $row->$column . ' (' . $row->count . ')',
                    'count' => $row->count,
                ];
            })
            ->values()
            ->toArray();

        // Cache the result
        if ($this->cacheEnabled) {
            Cache::put($cacheKey, $options, $this->cacheTtl);
        }

        return $options;
    }

    /**
     * Get options with pagination (for very large datasets)
     * 
     * Returns paginated options to prevent memory issues.
     * 
     * @param string $table Table name
     * @param string $column Column name
     * @param array $parentFilters Parent filter values
     * @param int $page Page number (1-indexed)
     * @param int $perPage Items per page
     * @return array
     */
    public function getOptionsPaginated(
        string $table,
        string $column,
        array $parentFilters = [],
        int $page = 1,
        int $perPage = 50
    ): array {
        // Build subquery for distinct values
        $subQuery = DB::table($table)
            ->select($column)
            ->distinct()
            ->whereNotNull($column)
            ->where($column, '!=', '');

        // Apply parent filters
        foreach ($parentFilters as $col => $value) {
            if ($value !== null && $value !== '') {
                $subQuery->where($col, $value);
            }
        }

        // Get distinct values first
        $distinctValues = $subQuery->pluck($column)->toArray();
        $total = count($distinctValues);

        // Sort values
        sort($distinctValues);

        // Paginate the distinct values
        $paginatedValues = array_slice(
            $distinctValues,
            ($page - 1) * $perPage,
            $perPage
        );

        // Convert to options format
        $options = array_map(function ($value) {
            return [
                'value' => $value,
                'label' => $value,
            ];
        }, $paginatedValues);

        return [
            'options' => $options,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ];
    }

    /**
     * Prefetch options for multiple columns (batch optimization)
     * 
     * Fetches options for multiple columns in a single operation.
     * Useful for loading all filter options at once.
     * 
     * @param string $table Table name
     * @param array $columns Column names
     * @param array $parentFilters Parent filter values
     * @return array
     */
    public function prefetchOptions(string $table, array $columns, array $parentFilters = []): array
    {
        $results = [];

        foreach ($columns as $column) {
            $results[$column] = $this->getOptions($table, $column, $parentFilters);
        }

        return $results;
    }

    /**
     * Get options from array
     * 
     * Converts a simple array to options format.
     * 
     * @param array $values Array of values
     * @return array
     */
    public function getOptionsFromArray(array $values): array
    {
        return array_map(function ($value) {
            if (is_array($value) && isset($value['value']) && isset($value['label'])) {
                return $value;
            }

            return [
                'value' => $value,
                'label' => $value,
            ];
        }, $values);
    }

    /**
     * Get options from key-value array
     * 
     * Converts a key-value array to options format.
     * 
     * @param array $keyValues Key-value array
     * @return array
     */
    public function getOptionsFromKeyValue(array $keyValues): array
    {
        $options = [];

        foreach ($keyValues as $key => $value) {
            $options[] = [
                'value' => $key,
                'label' => $value,
            ];
        }

        return $options;
    }

    /**
     * Get cached value with tag support
     * 
     * @param string $key Cache key
     * @return mixed
     */
    protected function getCached(string $key)
    {
        if (!$this->cacheEnabled) {
            return null;
        }

        try {
            $tags = config('canvastack.cache.filter_options.tags', []);
            
            // Check if cache driver supports tagging
            $store = Cache::getStore();
            $supportsTagging = $store instanceof \Illuminate\Cache\TaggableStore;
            
            if (!empty($tags) && $supportsTagging) {
                return Cache::tags($tags)->get($key);
            }
            
            return Cache::get($key);
        } catch (\BadMethodCallException $e) {
            // Cache driver doesn't support tagging, use regular cache
            return Cache::get($key);
        }
    }

    /**
     * Put value in cache with tag support
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $ttl Time to live (null = use default)
     * @return void
     */
    protected function putCached(string $key, $value, ?int $ttl = null): void
    {
        if (!$this->cacheEnabled) {
            return;
        }

        try {
            $ttl = $ttl ?? $this->cacheTtl;
            $tags = config('canvastack.cache.filter_options.tags', []);
            
            // Check if cache driver supports tagging
            $store = Cache::getStore();
            $supportsTagging = $store instanceof \Illuminate\Cache\TaggableStore;
            
            if (!empty($tags) && $supportsTagging) {
                Cache::tags($tags)->put($key, $value, $ttl);
            } else {
                Cache::put($key, $value, $ttl);
            }
        } catch (\BadMethodCallException $e) {
            // Cache driver doesn't support tagging, use regular cache
            $ttl = $ttl ?? $this->cacheTtl;
            Cache::put($key, $value, $ttl);
        }
    }
}
