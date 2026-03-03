<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Query;

use Canvastack\Canvastack\Components\Table\Validation\ColumnValidator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * FilterBuilder - Secure filter application for table queries.
 *
 * SECURITY: Uses Laravel's query builder parameter binding to prevent SQL injection.
 * All filter values are automatically escaped and bound as parameters.
 *
 * FEATURES:
 * - Secure parameter binding (prevents SQL injection)
 * - Column validation against schema
 * - Support for multiple filter types (exact, IN, BETWEEN, LIKE, NULL)
 * - Operator validation (whitelist-based)
 * - Search across multiple columns
 * - Date range filtering
 * - Sorting and pagination
 *
 * SUPPORTED OPERATORS:
 * - Comparison: =, !=, >, >=, <, <=
 * - Pattern matching: LIKE, NOT LIKE
 * - Set operations: IN, NOT IN
 * - Range: BETWEEN
 * - NULL checks: IS NULL, IS NOT NULL
 */
class FilterBuilder
{
    protected array $allowedOperators = [
        '=',
        '!=',
        '>',
        '>=',
        '<',
        '<=',
        'LIKE',
        'NOT LIKE',
        'IN',
        'NOT IN',
        'BETWEEN',
        'IS NULL',
        'IS NOT NULL',
    ];

    protected array $config;

    protected ColumnValidator $columnValidator;

    /**
     * Constructor.
     *
     * Initializes the filter builder with a column validator and configuration options.
     *
     * @param ColumnValidator $columnValidator Validator for column name validation
     * @param array $config Configuration options:
     *                      - enable_validation: bool (default: true) - Validate columns against schema
     *                      - case_sensitive: bool (default: false) - Case-sensitive filtering
     */
    public function __construct(ColumnValidator $columnValidator, array $config = [])
    {
        $this->columnValidator = $columnValidator;
        $this->config = array_merge([
            'enable_validation' => true,
            'case_sensitive' => false,
        ], $config);
    }

    /**
     * Build WHERE conditions with secure parameter binding.
     *
     * Constructs SQL WHERE clauses with parameter binding to prevent SQL injection.
     * Supports multiple condition formats and operators.
     *
     * SECURITY: All values are bound as parameters, never concatenated into SQL.
     *
     * SUPPORTED FORMATS:
     * - Associative: ['column' => 'value'] (uses = operator)
     * - Array with operator: [['column', 'operator', 'value']]
     * - Array without operator: [['column', 'value']] (uses = operator)
     *
     * @param array $conditions Array of conditions in supported formats
     * @param string $tableName The table name for column validation
     * @param string|null $connection The database connection name (null = default)
     * @return array Array with 'query' (SQL string) and 'bindings' (parameter values) keys
     *
     * @throws \InvalidArgumentException If operator is invalid or column doesn't exist
     *
     * @example
     * buildWhere(['status' => 'active'], 'users')
     * // Returns: ['query' => 'status = ?', 'bindings' => ['active']]
     *
     * buildWhere([['age', '>', 18]], 'users')
     * // Returns: ['query' => 'age > ?', 'bindings' => [18]]
     */
    public function buildWhere(array $conditions, string $tableName, ?string $connection = null): array
    {
        $whereClauses = [];
        $bindings = [];

        foreach ($conditions as $key => $value) {
            $conditionData = $this->parseCondition($key, $value);

            if ($conditionData === null) {
                continue;
            }

            // Validate column
            if ($this->config['enable_validation']) {
                $this->columnValidator->validate($conditionData['column'], $tableName, $connection);
            }

            // Validate and normalize operator
            $operator = $this->validateOperator($conditionData['operator']);

            // Build WHERE clause with parameter binding
            $clauseResult = $this->buildWhereClause(
                $conditionData['column'],
                $operator,
                $conditionData['value']
            );

            $whereClauses[] = $clauseResult['clause'];
            $bindings = array_merge($bindings, $clauseResult['bindings']);
        }

        $query = empty($whereClauses) ? '' : implode(' AND ', $whereClauses);

        return [
            'query' => $query,
            'bindings' => $bindings,
        ];
    }

    /**
     * Parse condition into column, operator, and value.
     *
     * @param mixed $key The condition key
     * @param mixed $value The condition value
     * @return array|null Array with column, operator, value or null if invalid
     */
    private function parseCondition(mixed $key, mixed $value): ?array
    {
        // Handle array format: [column, operator, value] or [column, value]
        if (is_numeric($key) && is_array($value)) {
            return $this->parseArrayCondition($value);
        }

        // Handle associative format: [column => value]
        return [
            'column' => $key,
            'operator' => '=',
            'value' => $value,
        ];
    }

    /**
     * Parse array-based condition format.
     *
     * @param array $value The condition array
     * @return array|null Parsed condition or null if invalid
     */
    private function parseArrayCondition(array $value): ?array
    {
        if (count($value) === 3) {
            return [
                'column' => $value[0],
                'operator' => $value[1],
                'value' => $value[2],
            ];
        }

        if (count($value) === 2) {
            return [
                'column' => $value[0],
                'operator' => '=',
                'value' => $value[1],
            ];
        }

        return null;
    }

    /**
     * Validate and normalize operator.
     *
     * @param string $operator The operator to validate
     * @return string Normalized operator
     *
     * @throws \InvalidArgumentException If operator is invalid
     */
    private function validateOperator(string $operator): string
    {
        $operator = strtoupper($operator);

        if (!in_array($operator, $this->allowedOperators)) {
            throw new \InvalidArgumentException("Invalid operator: {$operator}");
        }

        return $operator;
    }

    /**
     * Build WHERE clause for specific operator type.
     *
     * @param string $column The column name
     * @param string $operator The operator
     * @param mixed $value The value
     * @return array Array with 'clause' and 'bindings'
     *
     * @throws \InvalidArgumentException If value format is invalid for operator
     */
    private function buildWhereClause(string $column, string $operator, mixed $value): array
    {
        if ($operator === 'IN' || $operator === 'NOT IN') {
            return $this->buildInClause($column, $operator, $value);
        }

        if ($operator === 'BETWEEN') {
            return $this->buildBetweenClause($column, $value);
        }

        if ($operator === 'IS NULL' || $operator === 'IS NOT NULL') {
            return $this->buildNullClause($column, $operator);
        }

        return $this->buildStandardClause($column, $operator, $value);
    }

    /**
     * Build IN or NOT IN clause.
     *
     * @param string $column The column name
     * @param string $operator IN or NOT IN
     * @param mixed $value The array of values
     * @return array Array with 'clause' and 'bindings'
     *
     * @throws \InvalidArgumentException If value is not an array
     */
    private function buildInClause(string $column, string $operator, mixed $value): array
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException("{$operator} requires array value");
        }

        $placeholders = implode(',', array_fill(0, count($value), '?'));

        return [
            'clause' => "{$column} {$operator} ({$placeholders})",
            'bindings' => $value,
        ];
    }

    /**
     * Build BETWEEN clause.
     *
     * @param string $column The column name
     * @param mixed $value Array with 2 values
     * @return array Array with 'clause' and 'bindings'
     *
     * @throws \InvalidArgumentException If value is not array with 2 elements
     */
    private function buildBetweenClause(string $column, mixed $value): array
    {
        if (!is_array($value) || count($value) !== 2) {
            throw new \InvalidArgumentException('BETWEEN requires array with 2 values');
        }

        return [
            'clause' => "{$column} BETWEEN ? AND ?",
            'bindings' => [$value[0], $value[1]],
        ];
    }

    /**
     * Build IS NULL or IS NOT NULL clause.
     *
     * @param string $column The column name
     * @param string $operator IS NULL or IS NOT NULL
     * @return array Array with 'clause' and empty 'bindings'
     */
    private function buildNullClause(string $column, string $operator): array
    {
        return [
            'clause' => "{$column} {$operator}",
            'bindings' => [],
        ];
    }

    /**
     * Build standard comparison clause (=, !=, >, <, etc.).
     *
     * @param string $column The column name
     * @param string $operator The comparison operator
     * @param mixed $value The value to compare
     * @return array Array with 'clause' and 'bindings'
     */
    private function buildStandardClause(string $column, string $operator, mixed $value): array
    {
        return [
            'clause' => "{$column} {$operator} ?",
            'bindings' => [$value],
        ];
    }

    /**
     * Build filter groups with cascading relationships.
     *
     * Processes filter configurations and builds filter groups that can have
     * cascading relationships (where one filter affects another).
     *
     * FILTER TYPES:
     * - inputbox: Text input filter
     * - datebox: Single date picker
     * - daterangebox: Date range picker
     * - selectbox: Dropdown select
     * - checkbox: Multiple selection checkboxes
     * - radiobox: Single selection radio buttons
     *
     * RELATIONSHIPS:
     * - false: No relationships (independent filter)
     * - true: Relates to all other columns
     * - string: Relates to specific column
     * - array: Relates to multiple specific columns
     *
     * @param array $filters Array of filter configurations with 'column', 'type', and optional 'relate'
     * @param string $tableName The table name for column validation
     * @return array Processed filter groups with validated columns and relationships
     *
     * @throws \InvalidArgumentException If filter type is invalid or column doesn't exist
     *
     * @example
     * $filterGroups = $filterBuilder->buildFilterGroups([
     *     ['column' => 'country', 'type' => 'selectbox', 'relate' => 'city'],
     *     ['column' => 'city', 'type' => 'selectbox', 'relate' => false]
     * ], 'users');
     */
    public function buildFilterGroups(array $filters, string $tableName): array
    {
        $filterGroups = [];

        foreach ($filters as $filter) {
            if (!isset($filter['column']) || !isset($filter['type'])) {
                continue;
            }

            $column = $filter['column'];
            $type = $filter['type'];
            $relate = $filter['relate'] ?? false;

            // Validate column
            if ($this->config['enable_validation']) {
                $this->columnValidator->validate($column, $tableName, null);
            }

            // Validate filter type
            $allowedTypes = ['inputbox', 'datebox', 'daterangebox', 'selectbox', 'checkbox', 'radiobox'];
            if (!in_array($type, $allowedTypes)) {
                throw new \InvalidArgumentException("Invalid filter type: {$type}");
            }

            // Build filter group
            $filterGroup = [
                'column' => $column,
                'type' => $type,
                'relate' => $relate,
            ];

            // Handle relate parameter
            if ($relate === true) {
                // Relate to all columns
                $filterGroup['related_columns'] = 'all';
            } elseif (is_string($relate)) {
                // Relate to specific column
                if ($this->config['enable_validation']) {
                    $this->columnValidator->validate($relate, $tableName, null);
                }
                $filterGroup['related_columns'] = [$relate];
            } elseif (is_array($relate)) {
                // Relate to multiple columns
                if ($this->config['enable_validation']) {
                    $this->columnValidator->validateMultiple($relate, $tableName, null);
                }
                $filterGroup['related_columns'] = $relate;
            } else {
                $filterGroup['related_columns'] = [];
            }

            $filterGroups[] = $filterGroup;
        }

        return $filterGroups;
    }

    /**
     * Apply filters to query with security validation.
     *
     * Processes an array of filters and applies them to the Eloquent query builder
     * using secure parameter binding. Automatically handles different value types.
     *
     * SECURITY: All values are bound as parameters to prevent SQL injection.
     * VALIDATION: Validates all column names against schema before use.
     *
     * FILTER TYPES:
     * - Exact match: ['status' => 'active'] -> WHERE status = 'active'
     * - IN clause: ['id' => [1, 2, 3]] -> WHERE id IN (1, 2, 3)
     * - Range: ['age' => '18|65'] -> WHERE age BETWEEN 18 AND 65
     * - LIKE: ['name' => '%John%'] -> WHERE name LIKE '%John%'
     * - NULL: ['deleted_at' => 'NULL'] -> WHERE deleted_at IS NULL
     *
     * @param Builder $query The Eloquent query builder instance
     * @param array $filters Associative array of column => value filters
     * @return Builder Query builder with filters applied
     *
     * @throws \InvalidArgumentException If column doesn't exist in schema
     *
     * @example
     * $query = $filterBuilder->apply($query, [
     *     'status' => 'active',
     *     'role' => ['admin', 'editor'],
     *     'age' => '18|65'
     * ]);
     */
    public function apply(Builder $query, array $filters): Builder
    {
        foreach ($filters as $column => $value) {
            // Skip empty filters
            if ($value === null || $value === '') {
                continue;
            }

            // Validate column exists in table schema
            if ($this->config['enable_validation']) {
                $this->validateColumn($query, $column);
            }

            // Apply filter based on value type
            $query = $this->applyFilter($query, $column, $value);
        }

        return $query;
    }

    /**
     * Apply single filter to query.
     *
     * @phpstan-return Builder
     */
    protected function applyFilter(Builder $query, string $column, mixed $value): Builder
    {
        // Handle array values (IN clause)
        if (is_array($value)) {
            // @phpstan-ignore-next-line
            return $query->whereIn($column, $value);
        }

        // Handle range filters (BETWEEN)
        if (is_string($value) && strpos($value, '|') !== false) {
            $parts = explode('|', $value);
            if (count($parts) === 2) {
                // @phpstan-ignore-next-line
                return $query->whereBetween($column, [$parts[0], $parts[1]]);
            }
        }

        // Handle LIKE filters (contains)
        if (is_string($value) && (strpos($value, '%') !== false || strpos($value, '_') !== false)) {
            // @phpstan-ignore-next-line
            return $query->where($column, 'LIKE', $value);
        }

        // Handle NULL checks
        if ($value === 'NULL' || $value === 'null') {
            // @phpstan-ignore-next-line
            return $query->whereNull($column);
        }

        if ($value === 'NOT NULL' || $value === 'not null') {
            // @phpstan-ignore-next-line
            return $query->whereNotNull($column);
        }

        // Default: exact match with parameter binding
        // @phpstan-ignore-next-line
        return $query->where($column, '=', $value);
    }

    /**
     * Apply advanced filter with custom operator.
     *
     * Applies a filter with a specific operator, providing more control than
     * the automatic filter type detection in apply().
     *
     * SECURITY: Uses parameter binding for all values.
     * VALIDATION: Validates operator against whitelist and column against schema.
     *
     * @param Builder $query The Eloquent query builder instance
     * @param string $column The column name to filter on
     * @param string $operator The comparison operator (=, !=, >, <, >=, <=, LIKE, IN, BETWEEN, etc.)
     * @param mixed $value The filter value (type depends on operator)
     * @return Builder Query builder with filter applied
     *
     * @throws \InvalidArgumentException If operator is invalid, column doesn't exist, or value type is wrong
     *
     * @example
     * $query = $filterBuilder->applyAdvanced($query, 'age', '>', 18);
     * $query = $filterBuilder->applyAdvanced($query, 'status', 'IN', ['active', 'pending']);
     * $query = $filterBuilder->applyAdvanced($query, 'created_at', 'BETWEEN', ['2024-01-01', '2024-12-31']);
     */
    public function applyAdvanced(Builder $query, string $column, string $operator, mixed $value): Builder
    {
        // Validate operator
        $operator = strtoupper($operator);
        if (!in_array($operator, $this->allowedOperators)) {
            throw new \InvalidArgumentException("Invalid operator: {$operator}");
        }

        // Validate column
        if ($this->config['enable_validation']) {
            $this->validateColumn($query, $column);
        }

        // Apply filter based on operator
        switch ($operator) {
            case 'IN':
                if (!is_array($value)) {
                    throw new \InvalidArgumentException('IN operator requires array value');
                }

                // @phpstan-ignore-next-line
                return $query->whereIn($column, $value);

            case 'NOT IN':
                if (!is_array($value)) {
                    throw new \InvalidArgumentException('NOT IN operator requires array value');
                }

                // @phpstan-ignore-next-line
                return $query->whereNotIn($column, $value);

            case 'BETWEEN':
                if (!is_array($value) || count($value) !== 2) {
                    throw new \InvalidArgumentException('BETWEEN operator requires array with 2 values');
                }

                // @phpstan-ignore-next-line
                return $query->whereBetween($column, $value);

            case 'IS NULL':
                // @phpstan-ignore-next-line
                return $query->whereNull($column);

            case 'IS NOT NULL':
                // @phpstan-ignore-next-line
                return $query->whereNotNull($column);

            case 'LIKE':
            case 'NOT LIKE':
                // Ensure value is string for LIKE operations
                if (!is_string($value)) {
                    $value = (string) $value;
                }

                // @phpstan-ignore-next-line
                return $query->where($column, $operator, $value);

            default:
                // Standard comparison operators
                // @phpstan-ignore-next-line
                return $query->where($column, $operator, $value);
        }
    }

    /**
     * Apply search filter across multiple columns.
     *
     * Creates an OR condition that searches for the term across all specified columns.
     * Useful for implementing global search functionality.
     *
     * SECURITY: Uses parameter binding for the search term.
     * VALIDATION: Validates all column names against schema.
     *
     * @param Builder $query The Eloquent query builder instance
     * @param array $columns Array of column names to search in
     * @param string $searchTerm The search term (will be wrapped with % for LIKE)
     * @return Builder Query builder with search conditions applied
     *
     * @throws \InvalidArgumentException If any column doesn't exist in schema
     *
     * @example
     * $query = $filterBuilder->applySearch($query, ['name', 'email', 'phone'], 'john');
     * // WHERE (name LIKE '%john%' OR email LIKE '%john%' OR phone LIKE '%john%')
     */
    public function applySearch(Builder $query, array $columns, string $searchTerm): Builder
    {
        return $query->where(function ($q) use ($columns, $searchTerm) {
            foreach ($columns as $column) {
                // Validate column
                if ($this->config['enable_validation']) {
                    $this->validateColumn($q, $column);
                }

                // Add OR condition for each column
                $q->orWhere($column, 'LIKE', "%{$searchTerm}%");
            }
        });
    }

    /**
     * Apply date range filter.
     *
     * Filters records where the column value falls between the start and end dates (inclusive).
     *
     * SECURITY: Uses parameter binding for date values.
     * VALIDATION: Validates column exists in schema.
     *
     * @param Builder $query The Eloquent query builder instance
     * @param string $column The date column name
     * @param string $startDate The start date (format: Y-m-d or any valid date string)
     * @param string $endDate The end date (format: Y-m-d or any valid date string)
     * @return Builder Query builder with date range filter applied
     *
     * @throws \InvalidArgumentException If column doesn't exist in schema
     *
     * @example
     * $query = $filterBuilder->applyDateRange($query, 'created_at', '2024-01-01', '2024-12-31');
     */
    public function applyDateRange(Builder $query, string $column, string $startDate, string $endDate): Builder
    {
        // Validate column
        if ($this->config['enable_validation']) {
            $this->validateColumn($query, $column);
        }

        // @phpstan-ignore-next-line
        return $query->whereBetween($column, [$startDate, $endDate]);
    }

    /**
     * Apply sorting to query.
     *
     * Adds ORDER BY clause to the query with column and direction validation.
     *
     * SECURITY: Validates column against schema to prevent SQL injection.
     * VALIDATION: Validates sort direction is 'asc' or 'desc'.
     *
     * @param Builder $query The Eloquent query builder instance
     * @param string $column The column name to sort by
     * @param string $direction The sort direction ('asc' or 'desc', case-insensitive)
     * @return Builder Query builder with sorting applied
     *
     * @throws \InvalidArgumentException If column doesn't exist or direction is invalid
     *
     * @example
     * $query = $filterBuilder->applySort($query, 'created_at', 'desc');
     * $query = $filterBuilder->applySort($query, 'name', 'asc');
     */
    public function applySort(Builder $query, string $column, string $direction = 'asc'): Builder
    {
        // Validate column
        if ($this->config['enable_validation']) {
            $this->validateColumn($query, $column);
        }

        // Validate direction
        $direction = strtolower($direction);
        if (!in_array($direction, ['asc', 'desc'])) {
            throw new \InvalidArgumentException("Invalid sort direction: {$direction}");
        }

        // @phpstan-ignore-next-line
        return $query->orderBy($column, $direction);
    }

    /**
     * Apply pagination to query.
     *
     * Adds LIMIT and OFFSET clauses to the query for pagination.
     *
     * @param Builder $query The Eloquent query builder instance
     * @param int $page The page number (1-indexed)
     * @param int $perPage The number of records per page
     * @return Builder Query builder with pagination applied
     *
     * @example
     * $query = $filterBuilder->applyPagination($query, 1, 10); // First page, 10 records
     * $query = $filterBuilder->applyPagination($query, 3, 25); // Third page, 25 records
     */
    public function applyPagination(Builder $query, int $page, int $perPage): Builder
    {
        $offset = ($page - 1) * $perPage;

        // @phpstan-ignore-next-line
        return $query->skip($offset)->take($perPage);
    }

    /**
     * Validate column exists in table schema.
     *
     * @throws \InvalidArgumentException
     */
    protected function validateColumn(Builder $query, string $column): void
    {
        // Get table name from query
        $table = $query->getModel()->getTable();

        // Remove table prefix if present (e.g., "users.id" -> "id")
        $columnName = $column;
        if (strpos($column, '.') !== false) {
            $parts = explode('.', $column);
            $columnName = end($parts);
        }

        // Get columns from schema
        $columns = Schema::getColumnListing($table);

        if (!in_array($columnName, $columns)) {
            throw new \InvalidArgumentException(
                "Invalid column: {$column}. Column not found in table {$table}."
            );
        }
    }

    /**
     * Build filter array from HTTP request parameters.
     *
     * Extracts filter parameters from request data, excluding common
     * non-filter parameters like pagination and sorting.
     *
     * EXCLUDED PARAMETERS:
     * - page, per_page: Pagination
     * - sort, order: Sorting
     * - _token, _method: Laravel CSRF and method spoofing
     *
     * @param array $request The HTTP request parameters (typically from $request->all())
     * @return array Filtered array containing only filter parameters
     *
     * @example
     * $filters = $filterBuilder->buildFromRequest($request->all());
     * // Input: ['status' => 'active', 'page' => 2, '_token' => '...']
     * // Output: ['status' => 'active']
     */
    public function buildFromRequest(array $request): array
    {
        $filters = [];

        // Extract filter parameters
        foreach ($request as $key => $value) {
            // Skip non-filter parameters
            if (in_array($key, ['page', 'per_page', 'sort', 'order', '_token', '_method'])) {
                continue;
            }

            // Skip empty values
            if ($value === null || $value === '') {
                continue;
            }

            $filters[$key] = $value;
        }

        return $filters;
    }

    /**
     * Get list of allowed operators.
     *
     * Returns the whitelist of operators that can be used in filters.
     * This is a security measure to prevent SQL injection through operators.
     *
     * @return array List of allowed operator strings
     */
    public function getAllowedOperators(): array
    {
        return $this->allowedOperators;
    }

    /**
     * Add custom operator to the whitelist.
     *
     * Allows adding custom operators for specialized filtering needs.
     * The operator is automatically converted to uppercase.
     *
     * WARNING: Only add operators that are safe and supported by your database.
     *
     * @param string $operator The operator to add (e.g., 'REGEXP', 'SOUNDS LIKE')
     * @return void
     *
     * @example
     * $filterBuilder->addOperator('REGEXP'); // MySQL regex operator
     * $filterBuilder->addOperator('ILIKE'); // PostgreSQL case-insensitive LIKE
     */
    public function addOperator(string $operator): void
    {
        $operator = strtoupper($operator);
        if (!in_array($operator, $this->allowedOperators)) {
            $this->allowedOperators[] = $operator;
        }
    }
}
