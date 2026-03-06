<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Exceptions;

/**
 * Exception thrown when server-side processing encounters an error.
 *
 * This exception is thrown when:
 * - Server-side data processing fails
 * - Database query errors occur
 * - Data transformation fails
 * - Invalid request parameters are provided
 * - Security validation fails
 *
 * @package Canvastack\Canvastack\Components\Table\Exceptions
 */
class ServerSideException extends TableException
{
    /**
     * Create exception for query execution failure.
     *
     * @param string $reason The reason for query failure
     * @param \Throwable|null $previous The previous exception
     * @return static
     */
    public static function queryFailed(string $reason, ?\Throwable $previous = null): static
    {
        return new static(
            "Server-side query execution failed: {$reason}",
            0,
            $previous
        );
    }

    /**
     * Create exception for invalid request parameters.
     *
     * @param string $parameter The invalid parameter name
     * @param string $reason The reason why it's invalid
     * @return static
     */
    public static function invalidParameter(string $parameter, string $reason): static
    {
        return new static(
            "Invalid server-side request parameter '{$parameter}': {$reason}"
        );
    }

    /**
     * Create exception for data transformation failure.
     *
     * @param string $reason The reason for transformation failure
     * @param \Throwable|null $previous The previous exception
     * @return static
     */
    public static function transformationFailed(string $reason, ?\Throwable $previous = null): static
    {
        return new static(
            "Server-side data transformation failed: {$reason}",
            0,
            $previous
        );
    }

    /**
     * Create exception for security validation failure.
     *
     * @param string $reason The security issue detected
     * @return static
     */
    public static function securityViolation(string $reason): static
    {
        return new static(
            "Server-side security validation failed: {$reason}"
        );
    }

    /**
     * Create exception for invalid sort column.
     *
     * @param string $columnName The invalid column name
     * @param array<string> $allowedColumns List of allowed columns
     * @return static
     */
    public static function invalidSortColumn(string $columnName, array $allowedColumns = []): static
    {
        $message = "Invalid sort column '{$columnName}'.";
        
        if (!empty($allowedColumns)) {
            $message .= ' Allowed columns: ' . implode(', ', $allowedColumns);
        }
        
        return new static($message);
    }

    /**
     * Create exception for invalid filter.
     *
     * @param string $filterName The invalid filter name
     * @param string $reason The reason why it's invalid
     * @return static
     */
    public static function invalidFilter(string $filterName, string $reason): static
    {
        return new static(
            "Invalid filter '{$filterName}': {$reason}"
        );
    }

    /**
     * Create exception for pagination error.
     *
     * @param string $reason The reason for pagination failure
     * @return static
     */
    public static function paginationFailed(string $reason): static
    {
        return new static(
            "Server-side pagination failed: {$reason}"
        );
    }

    /**
     * Create exception for missing model.
     *
     * @return static
     */
    public static function missingModel(): static
    {
        return new static(
            "Server-side processing requires a model to be set. Use setModel() or setQuery() before enabling server-side processing."
        );
    }
}
