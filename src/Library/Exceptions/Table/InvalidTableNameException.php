<?php

namespace Canvastack\Canvastack\Library\Exceptions\Table;

/**
 * Exception thrown when an invalid table name is provided
 * 
 * This exception is thrown when:
 * - Table name contains invalid characters
 * - Table name is not in the whitelist
 * - Table name doesn't exist in the database
 * - Table name format is incorrect
 * - Table name is empty or null
 * 
 * @package Canvastack\Canvastack\Library\Exceptions\Table
 * @author Canvastack Team
 * @since 1.0.0
 * 
 * @security This exception may indicate a security issue if the table name
 *           contains suspicious patterns. Monitor for repeated attempts.
 * 
 * @example Validating table name format
 * ```php
 * public function setName(string $tableName): self
 * {
 *     // Check if empty
 *     if (empty($tableName)) {
 *         throw new InvalidTableNameException(
 *             'Table name cannot be empty',
 *             0,
 *             null,
 *             ['table_name' => $tableName]
 *         );
 *     }
 *     
 *     // Validate format (alphanumeric and underscore only)
 *     if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
 *         throw new InvalidTableNameException(
 *             'Table name contains invalid characters',
 *             0,
 *             null,
 *             [
 *                 'table_name' => $tableName,
 *                 'allowed_pattern' => 'alphanumeric and underscore only'
 *             ]
 *         );
 *     }
 *     
 *     $this->tableName = $tableName;
 *     return $this;
 * }
 * ```
 * 
 * @example Validating against whitelist
 * ```php
 * public function validateTableName(string $tableName): void
 * {
 *     $allowedTables = config('datatables.allowed_tables', []);
 *     
 *     if (!empty($allowedTables) && !in_array($tableName, $allowedTables)) {
 *         throw new InvalidTableNameException(
 *             'Table name is not in the allowed whitelist',
 *             0,
 *             null,
 *             [
 *                 'table_name' => $tableName,
 *                 'allowed_tables' => $allowedTables
 *             ]
 *         );
 *     }
 * }
 * ```
 * 
 * @example Checking table existence
 * ```php
 * public function checkTableExists(string $tableName): void
 * {
 *     $schema = DB::connection()->getDoctrineSchemaManager();
 *     
 *     if (!$schema->tablesExist([$tableName])) {
 *         throw new InvalidTableNameException(
 *             'Table does not exist in database',
 *             0,
 *             null,
 *             [
 *                 'table_name' => $tableName,
 *                 'database' => DB::connection()->getDatabaseName()
 *             ]
 *         );
 *     }
 * }
 * ```
 * 
 * @example Handling invalid table name
 * ```php
 * try {
 *     $table->setName($userInput);
 * } catch (InvalidTableNameException $e) {
 *     // Log the error
 *     Log::warning('Invalid table name provided', [
 *         'message' => $e->getMessage(),
 *         'context' => $e->getContext()
 *     ]);
 *     
 *     // Return user-friendly error
 *     return response()->json([
 *         'error' => 'Invalid table name',
 *         'message' => 'The specified table is not available'
 *     ], 400);
 * }
 * ```
 */
class InvalidTableNameException extends TableSecurityException
{
    /**
     * The invalid table name that triggered the exception
     *
     * @var string|null
     */
    protected ?string $tableName = null;

    /**
     * The reason why the table name is invalid
     * 
     * Possible values:
     * - 'empty': Table name is empty
     * - 'invalid_format': Contains invalid characters
     * - 'not_whitelisted': Not in allowed tables list
     * - 'not_exists': Table doesn't exist in database
     * - 'too_long': Table name exceeds maximum length
     *
     * @var string|null
     */
    protected ?string $reason = null;

    /**
     * Create a new InvalidTableNameException instance
     *
     * @param string $message The exception message
     * @param int $code The exception code (default: 0)
     * @param \Exception|null $previous The previous exception for chaining
     * @param array $context Additional context data for debugging
     */
    public function __construct(
        string $message = "Invalid table name",
        int $code = 0,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->severity = 'high';
    }

    /**
     * Set the invalid table name
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
     * Get the invalid table name
     *
     * @return string|null The table name
     */
    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    /**
     * Set the reason why the table name is invalid
     *
     * @param string $reason The reason
     * @return self
     */
    public function setReason(string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    /**
     * Get the reason why the table name is invalid
     *
     * @return string|null The reason
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * Get a string representation with table name details
     *
     * @return string
     */
    public function __toString(): string
    {
        $string = "[INVALID TABLE NAME] " . $this->getMessage();
        
        if ($this->tableName) {
            $string .= "\nTable Name: " . $this->tableName;
        }
        
        if ($this->reason) {
            $string .= "\nReason: " . $this->reason;
        }
        
        $string .= "\nFile: " . $this->getFile() . ":" . $this->getLine();
        
        if (!empty($this->context)) {
            $string .= "\nContext: " . json_encode($this->context, JSON_PRETTY_PRINT);
        }
        
        return $string;
    }
}
