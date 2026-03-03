<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * FilterOptionsProvider - Provides filter options for cascading filters.
 *
 * Handles:
 * - Loading distinct values from database columns
 * - Applying parent filter constraints
 * - Caching filter options for performance
 */
class FilterOptionsProvider
{
    /**
     * Get filter options for a column.
     *
     * @param string $table Table name
     * @param string $column Column name
     * @param array $parentFilters Parent filter values
     * @return array Array of options [{value, label}, ...]
     */
    public function getOptions(string $table, string $column, array $parentFilters = []): array
    {
        // Generate cache key
        $cacheKey = $this->getCacheKey($table, $column, $parentFilters);

        // Try to get from cache
        return Cache::remember($cacheKey, 300, function () use ($table, $column, $parentFilters) {
            return $this->fetchOptions($table, $column, $parentFilters);
        });
    }

    /**
     * Fetch options from database.
     *
     * @param string $table Table name
     * @param string $column Column name
     * @param array $parentFilters Parent filter values
     * @return array Array of options
     */
    protected function fetchOptions(string $table, string $column, array $parentFilters): array
    {
        try {
            // Start query
            $query = DB::table($table)
                ->select($column)
                ->distinct()
                ->whereNotNull($column)
                ->where($column, '!=', '');

            // Apply parent filters
            foreach ($parentFilters as $filterColumn => $filterValue) {
                if ($filterValue !== '' && $filterValue !== null) {
                    $query->where($filterColumn, $filterValue);
                }
            }

            // Get results
            $results = $query
                ->orderBy($column)
                ->limit(1000) // Limit to prevent memory issues
                ->get();

            // Transform to options format
            return $results->map(function ($row) use ($column) {
                $value = $row->{$column};
                return [
                    'value' => $value,
                    'label' => $value,
                ];
            })->toArray();
        } catch (\Exception $e) {
            // Log error and return empty array
            \Log::error('FilterOptionsProvider error', [
                'table' => $table,
                'column' => $column,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Generate cache key for filter options.
     *
     * @param string $table Table name
     * @param string $column Column name
     * @param array $parentFilters Parent filter values
     * @return string Cache key
     */
    protected function getCacheKey(string $table, string $column, array $parentFilters): string
    {
        $filterHash = md5(json_encode($parentFilters));
        return "filter_options_{$table}_{$column}_{$filterHash}";
    }

    /**
     * Clear cache for a table.
     *
     * @param string $table Table name
     * @return void
     */
    public function clearCache(string $table): void
    {
        // Clear all cache keys for this table
        // Note: This is a simple implementation. For production, use cache tags.
        Cache::flush();
    }
}

