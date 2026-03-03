<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Validation;

use InvalidArgumentException;

/**
 * ColumnValidator - Validates column names against table schema.
 *
 * SECURITY: Prevents SQL injection by validating all column names against
 * the actual database schema before they are used in queries.
 *
 * FEATURES:
 * - Schema-based validation (columns must exist in database)
 * - Helpful error messages with suggestions
 * - Support for multiple database connections
 * - Batch validation for multiple columns
 *
 * WHY THIS MATTERS:
 * Even with parameter binding, column names cannot be parameterized in SQL.
 * This validator ensures only legitimate column names are used in queries.
 */
class ColumnValidator
{
    protected SchemaInspector $schemaInspector;

    /**
     * Constructor.
     *
     * Initializes the validator with a schema inspector for database
     * schema validation.
     *
     * @param SchemaInspector $schemaInspector Schema inspector instance for database queries
     */
    public function __construct(SchemaInspector $schemaInspector)
    {
        $this->schemaInspector = $schemaInspector;
    }

    /**
     * Validate a single column name against table schema.
     *
     * Checks if the specified column exists in the table schema.
     * Throws an exception with helpful suggestions if the column is not found.
     *
     * SECURITY: This is a critical security check that prevents SQL injection
     * through column names, which cannot be parameterized in SQL queries.
     *
     * @param string $column The column name to validate (e.g., 'email', 'user_id')
     * @param string $tableName The table name (e.g., 'users', 'posts')
     * @param string|null $connection The database connection name (null = default)
     * @return void
     *
     * @throws InvalidArgumentException If column does not exist in the table schema
     *
     * @example
     * $validator->validate('email', 'users'); // OK
     * $validator->validate('invalid_col', 'users'); // Throws exception
     */
    public function validate(string $column, string $tableName, ?string $connection = null): void
    {
        $this->schemaInspector->validateColumn($column, $tableName, $connection);
    }

    /**
     * Validate multiple column names against table schema.
     *
     * @param array $columns Array of column names to validate
     * @param string $tableName The table name
     * @param string|null $connection The database connection name
     *
     * @throws InvalidArgumentException If any column does not exist
     */
    public function validateMultiple(array $columns, string $tableName, ?string $connection = null): void
    {
        foreach ($columns as $column) {
            $this->validate($column, $tableName, $connection);
        }
    }

    /**
     * Check if a column is valid without throwing exception.
     *
     * @param string $column The column name to check
     * @param string $tableName The table name
     * @param string|null $connection The database connection name
     *
     * @return bool True if column exists, false otherwise
     */
    public function isValid(string $column, string $tableName, ?string $connection = null): bool
    {
        try {
            $this->validate($column, $tableName, $connection);

            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }
}
