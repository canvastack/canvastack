<?php

namespace Canvastack\Canvastack\Library\Exceptions\Table;

/**
 * Exception thrown when a database query exceeds the timeout threshold
 * 
 * This exception is thrown when:
 * - Query execution time exceeds configured timeout
 * - Database connection timeout occurs
 * - Long-running query is detected
 * - Query is forcibly terminated
 * 
 * @package Canvastack\Canvastack\Library\Exceptions\Table
 * @author Canvastack Team
 * @since 1.0.0
 * 
 * @performance CRITICAL - Query timeouts indicate serious performance issues
 *              that need immediate attention. Consider query optimization,
 *              indexing, or data partitioning.
 * 
 * @example Detecting query timeout
 * ```php
 * public function executeWithTimeout($query, float $timeout = 5.0)
 * {
 *     $startTime = microtime(true);
 *     
 *     try {
 *         $result = DB::select($query);
 *     } finally {
 *         $executionTime = microtime(true) - $startTime;
 *         
 *         if ($executionTime > $timeout) {
 *             throw new QueryTimeoutException(
 *                 "Query exceeded timeout of {$timeout} seconds",
 *                 0,
 *                 null,
 *                 [
 *                     'query' => $query,
 *                     'execution_time' => $executionTime,
 *                     'timeout' => $timeout
 *                 ]
 *             );
 *         }
 *     }
 *     
 *     return $result;
 * }
 * ```
 * 
 * @example Handling query timeout
 * ```php
 * try {
 *     $data = $datatables->process($method, $data, $filters);
 * } catch (QueryTimeoutException $e) {
 *     // Log the slow query
 *     Log::error('Query timeout', [
 *         'message' => $e->getMessage(),
 *         'query' => $e->getQuery(),
 *         'execution_time' => $e->getExecutionTime()
 *     ]);
 *     
 *     // Return partial results or cached data
 *     return $this->getCachedResults();
 * }
 * ```
 */
class QueryTimeoutException extends TablePerformanceException
{
    /**
     * The query that timed out
     *
     * @var string|null
     */
    protected ?string $query = null;

    /**
     * The execution time in seconds
     *
     * @var float|null
     */
    protected ?float $executionTime = null;

    /**
     * Create a new QueryTimeoutException instance
     *
     * @param string $message The exception message
     * @param int $code The exception code (default: 0)
     * @param \Exception|null $previous The previous exception for chaining
     * @param array $context Additional context data for debugging
     */
    public function __construct(
        string $message = "Query execution timeout",
        int $code = 0,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->metric = 'query_time';
    }

    /**
     * Set the query that timed out
     *
     * @param string $query The SQL query
     * @return self
     */
    public function setQuery(string $query): self
    {
        $this->query = $query;
        return $this;
    }

    /**
     * Get the query that timed out
     *
     * @return string|null The SQL query
     */
    public function getQuery(): ?string
    {
        return $this->query;
    }

    /**
     * Set the execution time
     *
     * @param float $time The execution time in seconds
     * @return self
     */
    public function setExecutionTime(float $time): self
    {
        $this->executionTime = $time;
        $this->actualValue = $time;
        return $this;
    }

    /**
     * Get the execution time
     *
     * @return float|null The execution time in seconds
     */
    public function getExecutionTime(): ?float
    {
        return $this->executionTime;
    }
}
