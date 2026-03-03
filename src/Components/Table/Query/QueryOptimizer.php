<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Query;

use Canvastack\Canvastack\Components\Table\Validation\ColumnValidator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * QueryOptimizer - Optimizes database queries for table component.
 *
 * Features:
 * - Automatic eager loading detection
 * - Query result caching
 * - Index usage optimization
 * - Query monitoring and logging
 */
class QueryOptimizer
{
    protected array $config;

    protected bool $enableQueryLog = false;

    protected array $queryLog = [];

    protected FilterBuilder $filterBuilder;

    protected ColumnValidator $columnValidator;

    public function __construct(
        FilterBuilder $filterBuilder,
        ColumnValidator $columnValidator,
        array $config = []
    ) {
        $this->filterBuilder = $filterBuilder;
        $this->columnValidator = $columnValidator;
        $this->config = array_merge([
            'enable_eager_loading' => true,
            'enable_query_cache' => true,
            'enable_query_log' => false,
            'max_eager_load_depth' => 3,
        ], $config);

        $this->enableQueryLog = $this->config['enable_query_log'];
    }

    /**
     * Build optimized query from model and configuration.
     *
     * @param Model $model The Eloquent model
     * @param array $config Query configuration
     *
     * @return Builder Optimized query builder
     */
    public function buildQuery(Model $model, array $config): Builder
    {
        $query = $model->newQuery();

        // Apply column selection
        if (isset($config['columns']) && !empty($config['columns'])) {
            $query->select($config['columns']);
        }

        // Apply eager loading
        if (isset($config['eager_load']) && !empty($config['eager_load'])) {
            $query = $this->applyEagerLoading($query, $config['eager_load']);
        }

        // Apply filters
        if (isset($config['filters']) && !empty($config['filters'])) {
            $query = $this->applyFilters($query, $config['filters']);
        }

        // Apply ordering
        if (isset($config['order_column']) && isset($config['order_direction'])) {
            $query = $this->applyOrdering(
                $query,
                $config['order_column'],
                $config['order_direction']
            );
        }

        return $query;
    }

    /**
     * Apply eager loading to prevent N+1 queries.
     *
     * @param Builder $query The query builder
     * @param array $relations Array of relationship names
     *
     * @return Builder Query with eager loading
     */
    public function applyEagerLoading(Builder $query, array $relations): Builder
    {
        if (empty($relations)) {
            return $query;
        }

        return $query->with($relations);
    }

    /**
     * Apply filters to query using FilterBuilder.
     *
     * @param Builder $query The query builder
     * @param array $filters Array of filters
     *
     * @return Builder Query with filters applied
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        return $this->filterBuilder->apply($query, $filters);
    }

    /**
     * Apply ordering to query with column validation.
     *
     * @param Builder $query The query builder
     * @param string $column Column to order by
     * @param string $direction Sort direction (asc/desc)
     *
     * @return Builder Query with ordering applied
     *
     * @phpstan-return Builder
     */
    public function applyOrdering(Builder $query, string $column, string $direction = 'asc'): Builder
    {
        // Validate column
        $tableName = $query->getModel()->getTable();
        $this->columnValidator->validate($column, $tableName, null);

        // Validate direction
        $direction = strtolower($direction);
        if (!in_array($direction, ['asc', 'desc'])) {
            throw new \InvalidArgumentException("Invalid sort direction: {$direction}");
        }

        // @phpstan-ignore-next-line
        return $query->orderBy($column, $direction);
    }

    /**
     * Process query results in chunks to manage memory.
     *
     * @param Builder $query The query builder
     * @param int $size Chunk size
     * @param callable $callback Callback function to process each chunk
     */
    public function chunk(Builder $query, int $size, callable $callback): void
    {
        $query->chunk($size, $callback);
    }

    /**
     * Optimize query with all available strategies.
     */
    public function optimize(Builder $query): Builder
    {
        // Start query logging if enabled
        if ($this->enableQueryLog) {
            DB::enableQueryLog();
        }

        // Apply optimizations
        $query = $this->optimizeSelect($query);
        $query = $this->optimizeJoins($query);
        $query = $this->optimizeOrderBy($query);

        // Log queries if enabled
        if ($this->enableQueryLog) {
            $this->queryLog = DB::getQueryLog();
            DB::disableQueryLog();
        }

        return $query;
    }

    /**
     * Optimize SELECT clause to only fetch needed columns.
     */
    protected function optimizeSelect(Builder $query): Builder
    {
        // Get the model's table name
        $table = $query->getModel()->getTable();

        // If no specific columns selected, select all from main table
        $columns = $query->getQuery()->columns;
        if (empty($columns)) {
            $query->select("{$table}.*");
        }

        return $query;
    }

    /**
     * Optimize JOIN operations.
     */
    protected function optimizeJoins(Builder $query): Builder
    {
        // Check for duplicate joins and remove them
        $joins = $query->getQuery()->joins ?? [];

        if (!empty($joins)) {
            $uniqueJoins = [];
            $seenTables = [];

            foreach ($joins as $join) {
                $table = $join->table;
                if (!in_array($table, $seenTables)) {
                    $uniqueJoins[] = $join;
                    $seenTables[] = $table;
                }
            }

            $query->getQuery()->joins = $uniqueJoins;
        }

        return $query;
    }

    /**
     * Optimize ORDER BY clause.
     */
    protected function optimizeOrderBy(Builder $query): Builder
    {
        // Ensure ORDER BY uses indexed columns when possible
        $orders = $query->getQuery()->orders ?? [];

        if (empty($orders)) {
            // Default to primary key ordering for consistent results
            $primaryKey = $query->getModel()->getKeyName();
            /* @var Builder $query */
            $query->orderBy($primaryKey, 'desc');
        }

        return $query;
    }

    /**
     * Detect N+1 query problems.
     *
     * @return array List of potential N+1 issues
     */
    public function detectN1Problems(Builder $query): array
    {
        $issues = [];

        // Check if eager loading is used
        $eagerLoad = $query->getEagerLoads();

        if (empty($eagerLoad)) {
            $issues[] = [
                'type' => 'missing_eager_load',
                'message' => 'No eager loading detected. This may cause N+1 queries.',
                'severity' => 'warning',
            ];
        }

        return $issues;
    }

    /**
     * Analyze query performance.
     *
     * @return array Performance metrics
     */
    public function analyzePerformance(Builder $query): array
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        return [
            'sql' => $sql,
            'bindings' => $bindings,
            'eager_loads' => array_keys($query->getEagerLoads()),
            'has_joins' => !empty($query->getQuery()->joins),
            'has_where' => !empty($query->getQuery()->wheres),
            'has_order' => !empty($query->getQuery()->orders),
        ];
    }

    /**
     * Get query execution log.
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * Count total queries executed.
     */
    public function getQueryCount(): int
    {
        return count($this->queryLog);
    }

    /**
     * Suggest indexes for better performance.
     *
     * @return array Index suggestions
     */
    public function suggestIndexes(Builder $query): array
    {
        $suggestions = [];
        // @phpstan-ignore-next-line - Property may be null in some query states
        $wheres = $query->getQuery()->wheres ?? [];

        foreach ($wheres as $where) {
            // @phpstan-ignore-next-line
            if (isset($where['column'])) {
                $suggestions[] = [
                    'table' => $query->getModel()->getTable(),
                    'column' => $where['column'],
                    'reason' => 'Used in WHERE clause',
                ];
            }
        }

        $orders = $query->getQuery()->orders ?? [];
        foreach ($orders as $order) {
            // @phpstan-ignore-next-line
            if (isset($order['column'])) {
                $suggestions[] = [
                    'table' => $query->getModel()->getTable(),
                    'column' => $order['column'],
                    'reason' => 'Used in ORDER BY clause',
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Enable query logging.
     */
    public function enableQueryLog(): void
    {
        $this->enableQueryLog = true;
    }

    /**
     * Disable query logging.
     */
    public function disableQueryLog(): void
    {
        $this->enableQueryLog = false;
    }

    /**
     * Clear query log.
     */
    public function clearQueryLog(): void
    {
        $this->queryLog = [];
    }
}
