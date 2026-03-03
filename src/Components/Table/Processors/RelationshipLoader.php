<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Processors;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * RelationshipLoader - Handles eager loading of Eloquent relationships.
 *
 * PROBLEM SOLVED: N+1 Query Problem
 * Without eager loading, displaying related data causes N+1 queries:
 * - 1 query to fetch main records
 * - N queries to fetch related data (one per record)
 * With eager loading: Only 2 queries total (main + relations)
 *
 * FEATURES:
 * - Eager loading with validation
 * - Field value replacement with related data
 * - Redis caching for relational data
 * - Cache invalidation support
 * - Helpful error messages with suggestions
 *
 * PERFORMANCE IMPACT:
 * - Without eager loading: 1 + N queries (e.g., 1 + 100 = 101 queries for 100 records)
 * - With eager loading: 2 queries (1 for main, 1 for all relations)
 * - Performance improvement: Up to 98% reduction in database queries
 */
class RelationshipLoader
{
    /**
     * Default cache TTL in seconds.
     */
    private const DEFAULT_CACHE_TTL = 300;

    /**
     * Load relationships using eager loading.
     *
     * This method uses Laravel's with() method to eager load relationships,
     * preventing N+1 query problems.
     *
     * @param Model $model The model instance to load relationships on
     * @param array $relations Array of relationship names to load
     * @return Model The model with loaded relationships
     * @throws \BadMethodCallException If a relationship doesn't exist on the model
     */
    public function loadRelations(Model $model, array $relations): Model
    {
        if (empty($relations)) {
            return $model;
        }

        // Validate that all relationships exist on the model
        foreach ($relations as $relation) {
            if (!method_exists($model, $relation)) {
                $modelClass = get_class($model);
                $availableMethods = $this->getAvailableRelationships($model);

                $message = "Relationship method '{$relation}' does not exist on model {$modelClass}.";

                $suggestion = $this->findSimilarMethod($relation, $availableMethods);
                if ($suggestion) {
                    $message .= " Did you mean '{$suggestion}'?";
                }

                // Always show available relationships section
                if (!empty($availableMethods)) {
                    $message .= ' Available relationships: ' . implode(', ', array_slice($availableMethods, 0, 5));
                    if (count($availableMethods) > 5) {
                        $message .= '...';
                    }
                } else {
                    $message .= ' Available relationships: none found';
                }

                $message .= " Example: \$table->relations(\$model, 'user', 'name')";

                // Log the error for debugging
                Log::warning('Relationship method not found', [
                    'model' => $modelClass,
                    'relation' => $relation,
                    'available' => $availableMethods,
                ]);

                throw new \BadMethodCallException($message);
            }
        }

        // Load relationships using eager loading
        // Note: This assumes $model is a query builder or collection
        // For a single model instance, we'd use $model->load($relations)
        // @phpstan-ignore-next-line
        if ($model instanceof \Illuminate\Database\Eloquent\Builder) {
            return $model->with($relations);
        }

        // If it's a model instance, load the relationships
        $model->load($relations);

        return $model;
    }

    /**
     * Replace foreign key values with related data.
     *
     * This method replaces foreign key IDs with actual related data values,
     * using eager loaded relationships to avoid N+1 queries.
     *
     * @param Collection $data The collection of data rows
     * @param array $replacements Array of replacement configurations
     *                           Each item: ['field' => 'user_id', 'relation' => 'user', 'display' => 'name']
     * @return Collection The collection with replaced values
     */
    public function replaceFieldValues(Collection $data, array $replacements): Collection
    {
        if (empty($replacements)) {
            return $data;
        }

        /** @var Collection $result */
        $result = $data->map(function ($item) use ($replacements) {
            foreach ($replacements as $replacement) {
                $field = $replacement['field'] ?? null;
                $relation = $replacement['relation'] ?? null;
                $display = $replacement['display'] ?? null;

                if (!$field || !$relation || !$display) {
                    continue;
                }

                // Check if the relationship is loaded
                if ($item->relationLoaded($relation)) {
                    $relatedModel = $item->$relation;

                    // Replace the field value with the related data
                    if ($relatedModel) {
                        $item->$field = $relatedModel->$display ?? $item->$field;
                    }
                }
            }

            return $item;
        });

        return $result;
    }

    /**
     * Cache relational data in Redis.
     *
     * @param string $key The cache key
     * @param mixed $data The data to cache
     * @param int $ttl Time to live in seconds (default: 300)
     * @return void
     */
    public function cacheRelationalData(string $key, mixed $data, int $ttl = self::DEFAULT_CACHE_TTL): void
    {
        try {
            // Only cache if Redis is available
            if (config('cache.default') === 'redis') {
                Cache::put($key, $data, $ttl);
            }
        } catch (\Throwable $e) {
            // Silently fail if caching is not available
            // This ensures the application continues to work even if Redis is down
        }
    }

    /**
     * Get cached relational data.
     *
     * @param string $key The cache key
     * @return mixed|null The cached data or null if not found
     */
    public function getCachedRelationalData(string $key): mixed
    {
        try {
            // Only retrieve from cache if Redis is available
            if (config('cache.default') === 'redis') {
                return Cache::get($key);
            }
        } catch (\Throwable $e) {
            // Silently fail if caching is not available
        }

        return null;
    }

    /**
     * Invalidate cached relational data.
     *
     * @param string $key The cache key or pattern
     * @return void
     */
    public function invalidateCache(string $key): void
    {
        try {
            if (config('cache.default') === 'redis') {
                Cache::forget($key);
            }
        } catch (\Throwable $e) {
            // Silently fail if caching is not available
        }
    }

    /**
     * Generate a cache key for relational data.
     *
     * @param string $modelClass The model class name
     * @param array $relations Array of relationship names
     * @param array $conditions Additional conditions for uniqueness
     * @return string The generated cache key
     */
    public function generateCacheKey(string $modelClass, array $relations, array $conditions = []): string
    {
        $parts = [
            'table_relations',
            class_basename($modelClass),
            implode('_', $relations),
        ];

        if (!empty($conditions)) {
            $parts[] = md5(serialize($conditions));
        }

        return implode(':', $parts);
    }

    /**
     * Get available relationship methods on a model.
     *
     * @param Model $model The model to inspect
     * @return array List of public method names that might be relationships
     */
    private function getAvailableRelationships(Model $model): array
    {
        $reflection = new \ReflectionClass($model);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        $relationships = [];
        foreach ($methods as $method) {
            // Skip magic methods, getters, setters, and Laravel internal methods
            $name = $method->getName();
            if (strpos($name, '__') === 0 ||
                strpos($name, 'get') === 0 ||
                strpos($name, 'set') === 0 ||
                in_array($name, ['save', 'delete', 'update', 'create', 'find', 'all', 'fresh', 'refresh', 'replicate', 'toArray', 'toJson'])) {
                continue;
            }

            // Only include methods defined in the model class or its direct parents (not from Eloquent base)
            $declaringClass = $method->getDeclaringClass()->getName();
            if ($declaringClass !== 'Illuminate\Database\Eloquent\Model' &&
                strpos($declaringClass, 'Illuminate\\') !== 0) {
                $relationships[] = $name;
            }
        }

        return $relationships;
    }

    /**
     * Find a similar method name using Levenshtein distance.
     *
     * @param string $method The method name to match
     * @param array $availableMethods List of available methods
     * @return string|null The most similar method name, or null if none found
     */
    private function findSimilarMethod(string $method, array $availableMethods): ?string
    {
        if (empty($availableMethods)) {
            return null;
        }

        $minDistance = PHP_INT_MAX;
        $suggestion = null;

        foreach ($availableMethods as $availableMethod) {
            $distance = levenshtein(strtolower($method), strtolower($availableMethod));

            // Only suggest if distance is less than 3 (close match)
            if ($distance < $minDistance && $distance <= 3) {
                $minDistance = $distance;
                $suggestion = $availableMethod;
            }
        }

        return $suggestion;
    }
}
