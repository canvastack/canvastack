<?php

namespace Canvastack\Canvastack\Library\Exceptions\Table;

/**
 * Exception thrown when a performance issue is detected in Table Components
 * 
 * This is the base class for all performance-related exceptions. It covers:
 * - Query timeout issues
 * - Memory limit exceeded
 * - Slow query warnings
 * - Resource exhaustion
 * - Performance threshold violations
 * 
 * @package Canvastack\Canvastack\Library\Exceptions\Table
 * @author Canvastack Team
 * @since 1.0.0
 * 
 * @performance This exception indicates a performance issue that may impact
 *              user experience. Monitor these exceptions to identify bottlenecks.
 * 
 * @example Monitoring query performance
 * ```php
 * public function executeQuery($query)
 * {
 *     $startTime = microtime(true);
 *     
 *     try {
 *         $result = DB::select($query);
 *     } finally {
 *         $executionTime = microtime(true) - $startTime;
 *         
 *         if ($executionTime > 5.0) {
 *             $exception = new TablePerformanceException(
 *                 'Query execution time exceeded threshold',
 *                 0,
 *                 null,
 *                 [
 *                     'execution_time' => $executionTime,
 *                     'threshold' => 5.0,
 *                     'query' => $query
 *                 ]
 *             );
 *             
 *             Log::warning($exception->getMessage(), $exception->getContext());
 *         }
 *     }
 *     
 *     return $result;
 * }
 * ```
 * 
 * @example Handling performance exceptions
 * ```php
 * try {
 *     $table->lists('users', $fields, $actions);
 * } catch (TablePerformanceException $e) {
 *     // Log performance issue
 *     Log::warning('Performance issue detected', [
 *         'message' => $e->getMessage(),
 *         'metric' => $e->getMetric(),
 *         'threshold' => $e->getThreshold(),
 *         'actual' => $e->getActualValue()
 *     ]);
 *     
 *     // Continue with degraded performance or return cached data
 *     return $this->getCachedData();
 * }
 * ```
 */
class TablePerformanceException extends TableComponentException
{
    /**
     * The performance metric that was violated
     * 
     * Possible values:
     * - 'query_time': Query execution time
     * - 'memory_usage': Memory consumption
     * - 'row_count': Number of rows processed
     * - 'response_time': Total response time
     *
     * @var string|null
     */
    protected ?string $metric = null;

    /**
     * The threshold value that was exceeded
     *
     * @var float|int|null
     */
    protected float|int|null $threshold = null;

    /**
     * The actual value that exceeded the threshold
     *
     * @var float|int|null
     */
    protected float|int|null $actualValue = null;

    /**
     * Set the performance metric
     *
     * @param string $metric The metric name
     * @return self
     */
    public function setMetric(string $metric): self
    {
        $this->metric = $metric;
        return $this;
    }

    /**
     * Get the performance metric
     *
     * @return string|null The metric name
     */
    public function getMetric(): ?string
    {
        return $this->metric;
    }

    /**
     * Set the threshold value
     *
     * @param float|int $threshold The threshold value
     * @return self
     */
    public function setThreshold(float|int $threshold): self
    {
        $this->threshold = $threshold;
        return $this;
    }

    /**
     * Get the threshold value
     *
     * @return float|int|null The threshold value
     */
    public function getThreshold(): float|int|null
    {
        return $this->threshold;
    }

    /**
     * Set the actual value
     *
     * @param float|int $value The actual value
     * @return self
     */
    public function setActualValue(float|int $value): self
    {
        $this->actualValue = $value;
        return $this;
    }

    /**
     * Get the actual value
     *
     * @return float|int|null The actual value
     */
    public function getActualValue(): float|int|null
    {
        return $this->actualValue;
    }

    /**
     * Get a string representation with performance details
     *
     * @return string
     */
    public function __toString(): string
    {
        $string = "[PERFORMANCE ISSUE] " . $this->getMessage();
        
        if ($this->metric) {
            $string .= "\nMetric: " . $this->metric;
        }
        
        if ($this->threshold !== null) {
            $string .= "\nThreshold: " . $this->threshold;
        }
        
        if ($this->actualValue !== null) {
            $string .= "\nActual Value: " . $this->actualValue;
        }
        
        $string .= "\nFile: " . $this->getFile() . ":" . $this->getLine();
        
        if (!empty($this->context)) {
            $string .= "\nContext: " . json_encode($this->context, JSON_PRETTY_PRINT);
        }
        
        return $string;
    }
}
