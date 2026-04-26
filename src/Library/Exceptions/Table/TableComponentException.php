<?php

namespace Canvastack\Canvastack\Library\Exceptions\Table;

use Exception;

/**
 * Base exception class for all Table Component exceptions
 * 
 * This is the root exception class for the Table Components system. All specific
 * table-related exceptions extend from this class, allowing for comprehensive
 * exception handling at different levels of granularity.
 * 
 * @package Canvastack\Canvastack\Library\Exceptions\Table
 * @author Canvastack Team
 * @since 1.0.0
 * 
 * @example Basic usage
 * ```php
 * try {
 *     $table->lists('users', $fields, $actions);
 * } catch (TableComponentException $e) {
 *     // Catch all table-related exceptions
 *     Log::error('Table error: ' . $e->getMessage(), $e->getContext());
 * }
 * ```
 * 
 * @example Catching specific exception types
 * ```php
 * try {
 *     $table->lists('users', $fields, $actions);
 * } catch (TableSecurityException $e) {
 *     // Handle security-related exceptions
 *     Log::critical('Security issue: ' . $e->getMessage());
 * } catch (TablePerformanceException $e) {
 *     // Handle performance-related exceptions
 *     Log::warning('Performance issue: ' . $e->getMessage());
 * } catch (TableComponentException $e) {
 *     // Handle all other table exceptions
 *     Log::error('Table error: ' . $e->getMessage());
 * }
 * ```
 */
class TableComponentException extends Exception
{
    /**
     * Additional context data for debugging
     *
     * @var array
     */
    protected array $context = [];

    /**
     * Create a new TableComponentException instance
     *
     * @param string $message The exception message
     * @param int $code The exception code (default: 0)
     * @param Exception|null $previous The previous exception for chaining
     * @param array $context Additional context data for debugging
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get the context data associated with this exception
     *
     * Context data provides additional debugging information such as:
     * - Table name
     * - Column names
     * - User input values
     * - Query parameters
     * - Stack trace information
     *
     * @return array The context data
     * 
     * @example
     * ```php
     * try {
     *     $table->setName($tableName);
     * } catch (TableComponentException $e) {
     *     $context = $e->getContext();
     *     Log::error('Table error', [
     *         'message' => $e->getMessage(),
     *         'context' => $context
     *     ]);
     * }
     * ```
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set additional context data
     *
     * @param array $context The context data to set
     * @return self
     * 
     * @example
     * ```php
     * $exception = new TableComponentException('Invalid table configuration');
     * $exception->setContext([
     *     'table' => 'users',
     *     'fields' => ['id', 'name', 'email']
     * ]);
     * throw $exception;
     * ```
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Add a single context item
     *
     * @param string $key The context key
     * @param mixed $value The context value
     * @return self
     * 
     * @example
     * ```php
     * $exception = new TableComponentException('Query failed');
     * $exception->addContext('query_time', 5.2)
     *           ->addContext('table', 'users');
     * throw $exception;
     * ```
     */
    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Get a string representation of the exception with context
     *
     * @return string
     */
    public function __toString(): string
    {
        $string = parent::__toString();
        
        if (!empty($this->context)) {
            $string .= "\nContext: " . json_encode($this->context, JSON_PRETTY_PRINT);
        }
        
        return $string;
    }
}
