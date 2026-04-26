<?php

namespace Canvastack\Canvastack\Library\Exceptions\Table;

/**
 * Exception thrown when an invalid column is specified
 * 
 * This exception is thrown when:
 * - Column name doesn't exist in the table schema
 * - Column name contains invalid characters
 * - Column configuration is invalid
 * - Column type is not supported
 * - Column is not accessible
 * 
 * @package Canvastack\Canvastack\Library\Exceptions\Table
 * @author Canvastack Team
 * @since 1.0.0
 * 
 * @example Validating column existence
 * ```php
 * public function validateColumn(string $tableName, string $columnName): void
 * {
 *     $schema = $this->getTableSchema($tableName);
 *     
 *     if (!isset($schema['columns'][$columnName])) {
 *         throw new InvalidColumnException(
 *             "Column '{$columnName}' does not exist in table '{$tableName}'",
 *             0,
 *             null,
 *             [
 *                 'table' => $tableName,
 *                 'column' => $columnName,
 *                 'available_columns' => array_keys($schema['columns'])
 *             ]
 *         );
 *     }
 * }
 * ```
 * 
 * @example Handling invalid column
 * ```php
 * try {
 *     $table->orderBy($columnName, 'asc');
 * } catch (InvalidColumnException $e) {
 *     Log::warning('Invalid column specified', [
 *         'message' => $e->getMessage(),
 *         'column' => $e->getColumnName(),
 *         'table' => $e->getTableName()
 *     ]);
 *     
 *     return response()->json([
 *         'error' => 'Invalid column',
 *         'message' => 'The specified column does not exist'
 *     ], 400);
 * }
 * ```
 */
class InvalidColumnException extends TableValidationException
{
    /**
     * The invalid column name
     *
     * @var string|null
     */
    protected ?string $columnName = null;

    /**
     * The table name where the column was expected
     *
     * @var string|null
     */
    protected ?string $tableName = null;

    /**
     * Set the invalid column name
     *
     * @param string $columnName The column name
     * @return self
     */
    public function setColumnName(string $columnName): self
    {
        $this->columnName = $columnName;
        return $this;
    }

    /**
     * Get the invalid column name
     *
     * @return string|null The column name
     */
    public function getColumnName(): ?string
    {
        return $this->columnName;
    }

    /**
     * Set the table name
     *
     * @param string $tableName The table name
     * @return self
     */
    public function setTableName(string $tableName): self
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * Get the table name
     *
     * @return string|null The table name
     */
    public function getTableName(): ?string
    {
        return $this->tableName;
    }
}
