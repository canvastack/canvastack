<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Validation;

use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * SchemaInspector - Database schema validation and inspection.
 *
 * SECURITY: Validates table and column existence against the actual database
 * schema to prevent SQL injection and invalid queries.
 *
 * FEATURES:
 * - Table existence validation
 * - Column existence validation
 * - Schema introspection (column types, table structure)
 * - Helpful error messages with suggestions using Levenshtein distance
 * - Multi-connection support
 *
 * WHY THIS MATTERS:
 * Table and column names cannot be parameterized in SQL queries, making them
 * potential SQL injection vectors. This class ensures only valid schema
 * elements are used in queries.
 */
class SchemaInspector
{
    /**
     * Cache for table existence checks to avoid repeated queries.
     *
     * @var array<string, bool>
     */
    protected array $tableExistsCache = [];

    /**
     * Cache for table columns to avoid repeated queries.
     *
     * @var array<string, array>
     */
    protected array $tableColumnsCache = [];

    /**
     * Cache for available tables to avoid repeated queries.
     *
     * @var array<string, array>
     */
    protected array $availableTablesCache = [];

    /**
     * Validate that a table exists in the database.
     *
     * @param string $tableName The table name to validate
     * @param string|null $connection The database connection name (null = default)
     *
     * @throws InvalidArgumentException If table does not exist
     */
    public function validateTable(string $tableName, ?string $connection = null): void
    {
        $cacheKey = ($connection ?? 'default') . '.' . $tableName;

        // Check cache first
        if (isset($this->tableExistsCache[$cacheKey])) {
            if (!$this->tableExistsCache[$cacheKey]) {
                throw new InvalidArgumentException("Table '{$tableName}' does not exist (cached result).");
            }

            return;
        }

        // Query database
        $exists = Schema::connection($connection)->hasTable($tableName);
        $this->tableExistsCache[$cacheKey] = $exists;

        if (!$exists) {
            $connectionName = $connection ?? 'default';
            $availableTables = $this->getAvailableTables($connection);
            $suggestion = $this->findSimilarTable($tableName, $availableTables);

            $message = "Table '{$tableName}' does not exist in database connection '{$connectionName}'.";

            if ($suggestion) {
                $message .= " Did you mean '{$suggestion}'?";
            }

            if (count($availableTables) > 0) {
                $tableList = implode(', ', array_slice($availableTables, 0, 5));
                $message .= " Available tables: {$tableList}" . (count($availableTables) > 5 ? '...' : '');
            }

            throw new InvalidArgumentException($message);
        }
    }

    /**
     * Get complete table schema with column types.
     *
     * @param string $tableName The table name
     * @param string|null $connection The database connection name
     *
     * @return array Column names as keys, types as values
     */
    public function getTableSchema(string $tableName, ?string $connection = null): array
    {
        $cacheKey = ($connection ?? 'default') . '.' . $tableName . '.schema';

        // Check cache first
        if (isset($this->tableColumnsCache[$cacheKey])) {
            return $this->tableColumnsCache[$cacheKey];
        }

        // Validate table exists (this will cache the existence check)
        $this->validateTable($tableName, $connection);

        // Get columns (this will cache the column list)
        $columns = $this->getTableColumns($tableName, $connection);
        $schema = [];

        foreach ($columns as $column) {
            // @phpstan-ignore-next-line
            $schema[$column] = $this->getColumnType($tableName, $column, $connection);
        }

        // Cache the schema
        $this->tableColumnsCache[$cacheKey] = $schema;

        return $schema;
    }

    /**
     * Validate that a column exists in a table.
     *
     * @param string $column The column name to validate
     * @param string $tableName The table name
     * @param string|null $connection The database connection name (null = default)
     *
     * @throws InvalidArgumentException If column does not exist
     */
    public function validateColumn(string $column, string $tableName, ?string $connection = null): void
    {
        $columns = $this->getTableColumns($tableName, $connection);

        if (!in_array($column, $columns)) {
            $suggestion = $this->findSimilarColumn($column, $columns);

            $message = "Column '{$column}' does not exist in table '{$tableName}'.";

            if ($suggestion) {
                $message .= " Did you mean '{$suggestion}'?";
            }

            if (count($columns) > 0) {
                $columnList = implode(', ', array_slice($columns, 0, 10));
                $message .= " Available columns: {$columnList}" . (count($columns) > 10 ? '...' : '');
            }

            throw new InvalidArgumentException($message);
        }
    }

    /**
     * Get list of all columns in a table.
     *
     * @param string $tableName The table name
     * @param string|null $connection The database connection name
     *
     * @return array List of column names
     */
    public function getTableColumns(string $tableName, ?string $connection = null): array
    {
        $cacheKey = ($connection ?? 'default') . '.' . $tableName;

        // Check cache first
        if (isset($this->tableColumnsCache[$cacheKey])) {
            return $this->tableColumnsCache[$cacheKey];
        }

        // Validate table exists first
        $this->validateTable($tableName, $connection);

        // Query database and cache result
        $columns = Schema::connection($connection)->getColumnListing($tableName);
        $this->tableColumnsCache[$cacheKey] = $columns;

        return $columns;
    }

    /**
     * Get the data type of a specific column.
     *
     * Retrieves the database column type for a specific column in a table.
     * Useful for type-aware operations and validation.
     *
     * @param string $tableName The table name
     * @param string $column The column name
     * @param string|null $connection The database connection name (null = default)
     * @return string The column type (e.g., 'string', 'integer', 'datetime', 'boolean')
     */
    protected function getColumnType(
        string $tableName,
        string $column,
        ?string $connection
    ): string {
        return Schema::connection($connection)->getColumnType($tableName, $column);
    }

    /**
     * Get list of available tables in the database.
     *
     * @param string|null $connection The database connection name
     *
     * @return array List of table names
     */
    protected function getAvailableTables(?string $connection = null): array
    {
        $cacheKey = $connection ?? 'default';

        // Check cache first
        if (isset($this->availableTablesCache[$cacheKey])) {
            return $this->availableTablesCache[$cacheKey];
        }

        try {
            $tables = Schema::connection($connection)->getTables();

            // getTables() returns array of arrays with table info
            // Extract just the table names
            if (!empty($tables) && is_array($tables[0] ?? null)) {
                $tableNames = array_map(function ($table) {
                    // @phpstan-ignore-next-line
                    return $table['name'] ?? $table['table_name'] ?? $table;
                }, $tables);
            } else {
                $tableNames = $tables;
            }

            // Cache the result
            $this->availableTablesCache[$cacheKey] = $tableNames;

            return $tableNames;
        } catch (\Exception $e) {
            // If getTables() is not available, return empty array
            return [];
        }
    }

    /**
     * Clear all schema caches.
     *
     * Clears all cached schema information to force re-querying
     * the database on the next validation.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->tableExistsCache = [];
        $this->tableColumnsCache = [];
        $this->availableTablesCache = [];
    }

    /**
     * Find a similar table name using Levenshtein distance algorithm.
     *
     * Uses the Levenshtein distance algorithm to find the closest matching
     * table name, helping users identify typos in table names.
     *
     * ALGORITHM: Levenshtein distance measures the minimum number of single-character
     * edits (insertions, deletions, substitutions) needed to change one string into another.
     *
     * @param string $tableName The table name to match
     * @param array $availableTables List of available table names
     * @return string|null The most similar table name (distance <= 3), or null if none found
     *
     * @example
     * findSimilarTable('usr', ['users', 'posts']) // Returns: 'users'
     * findSimilarTable('usres', ['users', 'posts']) // Returns: 'users'
     */
    protected function findSimilarTable(string $tableName, array $availableTables): ?string
    {
        if (empty($availableTables)) {
            return null;
        }

        $minDistance = PHP_INT_MAX;
        $suggestion = null;

        foreach ($availableTables as $table) {
            $distance = levenshtein(strtolower($tableName), strtolower($table));

            // Only suggest if distance is less than 3 (close match)
            if ($distance < $minDistance && $distance <= 3) {
                $minDistance = $distance;
                $suggestion = $table;
            }
        }

        return $suggestion;
    }

    /**
     * Find a similar column name using Levenshtein distance algorithm.
     *
     * Uses the Levenshtein distance algorithm to find the closest matching
     * column name, helping users identify typos in column names.
     *
     * ALGORITHM: Calculates edit distance between strings. Only suggests
     * columns with distance of 1-2 (very close matches) to avoid false positives.
     *
     * @param string $column The column name to match
     * @param array $availableColumns List of available column names
     * @return string|null The most similar column name (distance 1-2), or null if none found
     *
     * @example
     * findSimilarColumn('emai', ['email', 'name']) // Returns: 'email'
     * findSimilarColumn('usre_id', ['user_id', 'post_id']) // Returns: 'user_id'
     */
    protected function findSimilarColumn(string $column, array $availableColumns): ?string
    {
        if (empty($availableColumns)) {
            return null;
        }

        $minDistance = PHP_INT_MAX;
        $suggestion = null;

        foreach ($availableColumns as $availableColumn) {
            $distance = levenshtein(strtolower($column), strtolower($availableColumn));

            // Only suggest if distance is 1 or 2 (very close match)
            // Distance 1: one character difference (insertion, deletion, or substitution)
            // Distance 2: two character difference
            if ($distance > 0 && $distance < $minDistance && $distance <= 2) {
                $minDistance = $distance;
                $suggestion = $availableColumn;
            }
        }

        return $suggestion;
    }
}
