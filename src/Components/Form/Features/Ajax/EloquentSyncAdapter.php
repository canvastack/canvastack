<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form\Features\Ajax;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/**
 * EloquentSyncAdapter - Converts Eloquent queries to SQL for Ajax Sync.
 *
 * This adapter class converts Eloquent model relationships and query builders
 * to parameterized SQL queries that can be used with the existing Ajax Sync
 * infrastructure. It maintains compatibility with QueryEncryption and provides
 * performance optimizations through caching.
 */
class EloquentSyncAdapter
{
    /**
     * Convert Eloquent model relationship to SQL query.
     *
     * Detects the relationship type, generates appropriate SQL query,
     * and returns parameterized query with bindings.
     *
     * @param string $modelClass Eloquent model class name
     * @param string $relationship Relationship method name
     * @param array $config Configuration array with display, value, constraints
     * @return array ['sql' => string, 'bindings' => array, 'foreign_key' => string]
     * @throws InvalidArgumentException If model or relationship is invalid
     */
    public function modelToSql(string $modelClass, string $relationship, array $config): array
    {
        // Validate model class
        if (!class_exists($modelClass)) {
            throw new InvalidArgumentException("Model class {$modelClass} does not exist");
        }

        if (!is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException("{$modelClass} is not an Eloquent model");
        }

        // Get relationship instance
        $relationInstance = $this->getRelationshipInstance($modelClass, $relationship);

        // Detect foreign key
        $foreignKey = $this->detectForeignKey($relationInstance);

        // Build query based on relationship type
        $query = $this->buildQueryFromRelationship($relationInstance, $config);

        // Convert to SQL with bindings
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        return [
            'sql' => $sql,
            'bindings' => $bindings,
            'foreign_key' => $foreignKey,
        ];
    }

    /**
     * Convert query builder closure to SQL.
     *
     * Executes the closure with a placeholder value to generate the query,
     * then extracts SQL and bindings.
     *
     * @param Closure $closure Query builder closure
     * @param mixed $sourceValue Source field value (for testing)
     * @return array ['sql' => string, 'bindings' => array]
     * @throws InvalidArgumentException If closure doesn't return valid query
     */
    public function closureToSql(Closure $closure, $sourceValue = null): array
    {
        // Use placeholder value if none provided
        $testValue = $sourceValue ?? 1;

        // Execute closure
        $result = $closure($testValue);

        // Handle different return types
        if ($result instanceof EloquentBuilder) {
            $query = $result->getQuery();
        } elseif ($result instanceof QueryBuilder) {
            $query = $result;
        } elseif (is_object($result) && method_exists($result, 'toSql')) {
            // Collection or other object with toSql method
            // Try to get the query builder
            if (method_exists($result, 'getQuery')) {
                $query = $result->getQuery();
            } else {
                throw new InvalidArgumentException('Closure must return a query builder instance');
            }
        } else {
            throw new InvalidArgumentException('Closure must return a query builder instance');
        }

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        return [
            'sql' => $sql,
            'bindings' => $bindings,
        ];
    }

    /**
     * Detect foreign key from relationship.
     *
     * Extracts the foreign key column name from the relationship instance.
     * Caches the result for performance.
     *
     * @param Relation $relation Relationship instance
     * @return string Foreign key column name
     */
    public function detectForeignKey(Relation $relation): string
    {
        $cacheKey = $this->getRelationshipCacheKey($relation, 'foreign_key');

        return Cache::remember($cacheKey, 3600, function () use ($relation) {
            if ($relation instanceof BelongsTo) {
                return $relation->getForeignKeyName();
            } elseif ($relation instanceof HasMany) {
                return $relation->getForeignKeyName();
            } elseif ($relation instanceof BelongsToMany) {
                return $relation->getForeignPivotKeyName();
            }

            // Fallback: try to get foreign key from relation
            if (method_exists($relation, 'getForeignKeyName')) {
                return $relation->getForeignKeyName();
            }

            throw new InvalidArgumentException('Unable to detect foreign key from relationship');
        });
    }

    /**
     * Detect relationship type.
     *
     * Returns the relationship type as a string (belongsTo, hasMany, etc.).
     * Caches the result for performance.
     *
     * @param string $modelClass Model class name
     * @param string $relationship Relationship method name
     * @return string Relationship type
     */
    public function detectRelationshipType(string $modelClass, string $relationship): string
    {
        $cacheKey = "eloquent_sync.relationship_type.{$modelClass}.{$relationship}";

        return Cache::remember($cacheKey, 3600, function () use ($modelClass, $relationship) {
            $relationInstance = $this->getRelationshipInstance($modelClass, $relationship);

            if ($relationInstance instanceof BelongsTo) {
                return 'belongsTo';
            } elseif ($relationInstance instanceof HasMany) {
                return 'hasMany';
            } elseif ($relationInstance instanceof BelongsToMany) {
                return 'belongsToMany';
            }

            return 'unknown';
        });
    }

    /**
     * Generate parameterized query from Eloquent builder.
     *
     * Converts an Eloquent query builder to SQL with parameter bindings.
     * Ensures the query uses ? placeholders for security.
     *
     * @param EloquentBuilder $builder Eloquent query builder
     * @return array ['sql' => string, 'bindings' => array]
     */
    public function generateParameterizedQuery(EloquentBuilder $builder): array
    {
        $query = $builder->getQuery();
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        return [
            'sql' => $sql,
            'bindings' => $bindings,
        ];
    }

    /**
     * Get relationship instance from model.
     *
     * Creates a model instance and calls the relationship method.
     * Validates that the method exists and returns a Relation instance.
     *
     * @param string $modelClass Model class name
     * @param string $relationship Relationship method name
     * @return Relation Relationship instance
     * @throws InvalidArgumentException If relationship doesn't exist or is invalid
     */
    protected function getRelationshipInstance(string $modelClass, string $relationship): Relation
    {
        // Don't cache the Relation instance itself as it holds database connections
        // Only cache metadata (done in getRelationshipMetadata)

        // Create model instance
        $model = new $modelClass();

        // Check if relationship method exists
        if (!method_exists($model, $relationship)) {
            throw new InvalidArgumentException(
                "Relationship {$relationship} does not exist on model {$modelClass}"
            );
        }

        // Call relationship method
        $relationInstance = $model->$relationship();

        // Validate it's a Relation instance
        if (!$relationInstance instanceof Relation) {
            throw new InvalidArgumentException(
                "Method {$relationship} on {$modelClass} is not a valid relationship"
            );
        }

        return $relationInstance;
    }

    /**
     * Build query from relationship with configuration.
     *
     * Applies display column, value column, and additional constraints
     * to the relationship query.
     *
     * @param Relation $relation Relationship instance
     * @param array $config Configuration array
     * @return EloquentBuilder|QueryBuilder Query builder
     */
    protected function buildQueryFromRelationship(Relation $relation, array $config): EloquentBuilder|QueryBuilder
    {
        // Get the base query from relationship
        $query = $relation->getQuery();

        // Apply select columns (value and display)
        $valueColumn = $config['value'] ?? 'id';
        $displayColumn = $config['display'] ?? 'name';

        // Ensure we select the columns we need
        $query->select([$valueColumn, $displayColumn]);

        // Apply additional constraints
        if (isset($config['constraints']) && is_array($config['constraints'])) {
            foreach ($config['constraints'] as $constraint) {
                $this->applyConstraint($query, $constraint);
            }
        }

        // Apply order by
        if (isset($config['orderBy'])) {
            $orderBy = $config['orderBy'];
            $direction = $config['orderDirection'] ?? 'asc';
            $query->orderBy($orderBy, $direction);
        }

        return $query;
    }

    /**
     * Apply constraint to query.
     *
     * Applies a where clause or other constraint to the query builder.
     *
     * @param EloquentBuilder|QueryBuilder $query Query builder
     * @param array $constraint Constraint configuration
     * @return void
     */
    protected function applyConstraint(EloquentBuilder|QueryBuilder $query, array $constraint): void
    {
        $type = $constraint['type'] ?? 'where';

        switch ($type) {
            case 'where':
                $column = $constraint['column'];
                $operator = $constraint['operator'] ?? '=';
                $value = $constraint['value'];
                $query->where($column, $operator, $value);
                break;

            case 'whereIn':
                $column = $constraint['column'];
                $values = $constraint['values'];
                $query->whereIn($column, $values);
                break;

            case 'whereNull':
                $column = $constraint['column'];
                $query->whereNull($column);
                break;

            case 'whereNotNull':
                $column = $constraint['column'];
                $query->whereNotNull($column);
                break;
        }
    }

    /**
     * Get cache key for relationship metadata.
     *
     * @param Relation $relation Relationship instance
     * @param string $suffix Cache key suffix
     * @return string Cache key
     */
    protected function getRelationshipCacheKey(Relation $relation, string $suffix): string
    {
        $modelClass = get_class($relation->getParent());
        $relatedClass = get_class($relation->getRelated());

        return "eloquent_sync.{$suffix}.{$modelClass}.{$relatedClass}";
    }

    /**
     * Clear relationship metadata cache.
     *
     * Useful for testing or when model relationships change.
     *
     * @param string|null $modelClass Optional model class to clear specific cache
     * @return void
     */
    public function clearCache(?string $modelClass = null): void
    {
        if ($modelClass) {
            Cache::forget("eloquent_sync.relationship_type.{$modelClass}.*");
            Cache::forget("eloquent_sync.relationship_instance.{$modelClass}.*");
        } else {
            // Clear all eloquent sync caches
            Cache::flush(); // Note: This clears ALL cache, use with caution
        }
    }

    /**
     * Configure eager loading for relationship query.
     *
     * Analyzes the query and adds eager loading for related models
     * to prevent N+1 query problems.
     *
     * @param EloquentBuilder $query Query builder
     * @param array $relations Relations to eager load
     * @return EloquentBuilder Query builder with eager loading
     */
    public function configureEagerLoading(EloquentBuilder $query, array $relations): EloquentBuilder
    {
        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query;
    }
}
