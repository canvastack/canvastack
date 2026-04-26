<?php

namespace Canvastack\Canvastack\Library\Exceptions\Table;

/**
 * Exception thrown when memory usage exceeds limits
 * 
 * This exception is thrown when:
 * - Memory usage exceeds configured threshold
 * - PHP memory limit is approaching
 * - Large dataset processing risks memory exhaustion
 * - Memory allocation fails
 * 
 * @package Canvastack\Canvastack\Library\Exceptions\Table
 * @author Canvastack Team
 * @since 1.0.0
 * 
 * @performance CRITICAL - Memory limit exceptions indicate that the system
 *              is processing too much data at once. Consider implementing
 *              chunking, streaming, or pagination.
 * 
 * @example Monitoring memory usage
 * ```php
 * public function processLargeDataset(array $data)
 * {
 *     $memoryLimit = $this->getMemoryLimit();
 *     $threshold = $memoryLimit * 0.8; // 80% threshold
 *     
 *     foreach ($data as $row) {
 *         $currentMemory = memory_get_usage(true);
 *         
 *         if ($currentMemory > $threshold) {
 *             throw new MemoryLimitException(
 *                 'Memory usage exceeded threshold',
 *                 0,
 *                 null,
 *                 [
 *                     'current_memory' => $currentMemory,
 *                     'threshold' => $threshold,
 *                     'memory_limit' => $memoryLimit,
 *                     'rows_processed' => count($data)
 *                 ]
 *             );
 *         }
 *         
 *         $this->processRow($row);
 *     }
 * }
 * ```
 * 
 * @example Handling memory limit
 * ```php
 * try {
 *     $export->generateExcel($data);
 * } catch (MemoryLimitException $e) {
 *     // Log memory issue
 *     Log::error('Memory limit exceeded', [
 *         'message' => $e->getMessage(),
 *         'current_memory' => $e->getCurrentMemory(),
 *         'memory_limit' => $e->getMemoryLimit()
 *     ]);
 *     
 *     // Switch to streaming export
 *     return $export->streamExcel($data);
 * }
 * ```
 */
class MemoryLimitException extends TablePerformanceException
{
    /**
     * Current memory usage in bytes
     *
     * @var int|null
     */
    protected ?int $currentMemory = null;

    /**
     * Memory limit in bytes
     *
     * @var int|null
     */
    protected ?int $memoryLimit = null;

    /**
     * Create a new MemoryLimitException instance
     *
     * @param string $message The exception message
     * @param int $code The exception code (default: 0)
     * @param \Exception|null $previous The previous exception for chaining
     * @param array $context Additional context data for debugging
     */
    public function __construct(
        string $message = "Memory limit exceeded",
        int $code = 0,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->metric = 'memory_usage';
    }

    /**
     * Set the current memory usage
     *
     * @param int $bytes Memory usage in bytes
     * @return self
     */
    public function setCurrentMemory(int $bytes): self
    {
        $this->currentMemory = $bytes;
        $this->actualValue = $bytes;
        return $this;
    }

    /**
     * Get the current memory usage
     *
     * @return int|null Memory usage in bytes
     */
    public function getCurrentMemory(): ?int
    {
        return $this->currentMemory;
    }

    /**
     * Set the memory limit
     *
     * @param int $bytes Memory limit in bytes
     * @return self
     */
    public function setMemoryLimit(int $bytes): self
    {
        $this->memoryLimit = $bytes;
        $this->threshold = $bytes;
        return $this;
    }

    /**
     * Get the memory limit
     *
     * @return int|null Memory limit in bytes
     */
    public function getMemoryLimit(): ?int
    {
        return $this->memoryLimit;
    }

    /**
     * Get memory usage as human-readable string
     *
     * @return string Memory usage (e.g., "128 MB")
     */
    public function getFormattedMemoryUsage(): string
    {
        if ($this->currentMemory === null) {
            return 'Unknown';
        }
        
        return $this->formatBytes($this->currentMemory);
    }

    /**
     * Get memory limit as human-readable string
     *
     * @return string Memory limit (e.g., "256 MB")
     */
    public function getFormattedMemoryLimit(): string
    {
        if ($this->memoryLimit === null) {
            return 'Unknown';
        }
        
        return $this->formatBytes($this->memoryLimit);
    }

    /**
     * Format bytes to human-readable string
     *
     * @param int $bytes Bytes to format
     * @return string Formatted string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
