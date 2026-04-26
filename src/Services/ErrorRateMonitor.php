<?php

namespace Canvastack\Canvastack\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Error Rate Monitor Service
 * 
 * Monitors and tracks error rates, exception frequencies, and failure patterns.
 * Provides alerts when error rates exceed configured thresholds.
 * 
 * @package Canvastack\Canvastack\Services
 */
class ErrorRateMonitor
{
    /**
     * Log channel for error rate events
     */
    private string $logChannel;

    /**
     * Whether error rate monitoring is enabled
     */
    private bool $enabled;

    /**
     * Error rate threshold (errors per minute)
     */
    private int $errorRateThreshold;

    /**
     * Time window for error rate calculation (in seconds)
     */
    private int $timeWindow;

    /**
     * Cache key prefix for error tracking
     */
    private string $cachePrefix = 'error_rate_monitor:';

    /**
     * Collected errors during request
     */
    private array $errors = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->logChannel = config('canvastack.controller.logging.log_channel', 'stack');
        $this->enabled = config('canvastack.controller.logging.log_performance_issues', true);
        $this->errorRateThreshold = config('canvastack.controller.monitoring.error_rate_threshold', 10);
        $this->timeWindow = config('canvastack.controller.monitoring.error_rate_window', 60);
    }

    /**
     * Track an exception
     * 
     * @param Throwable $exception Exception to track
     * @param array $context Additional context
     * @return void
     */
    public function trackException(Throwable $exception, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $errorData = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'timestamp' => now()->toIso8601String(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            ...$context,
        ];

        $this->errors[] = $errorData;

        // Increment error counter in cache
        $this->incrementErrorCount($errorData['type']);

        // Check if error rate threshold exceeded
        $this->checkErrorRate($errorData['type']);

        // Log the exception
        Log::channel($this->logChannel)->error('Exception tracked', $errorData);
    }

    /**
     * Track a validation failure
     * 
     * @param string $validationType Type of validation
     * @param array $errors Validation errors
     * @param array $context Additional context
     * @return void
     */
    public function trackValidationFailure(string $validationType, array $errors, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $errorData = [
            'type' => 'ValidationFailure',
            'validation_type' => $validationType,
            'errors' => $errors,
            'timestamp' => now()->toIso8601String(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'user_id' => auth()->id(),
            ...$context,
        ];

        $this->errors[] = $errorData;

        // Increment validation failure counter
        $this->incrementErrorCount('ValidationFailure');
    }

    /**
     * Track a database error
     * 
     * @param string $query SQL query
     * @param string $error Error message
     * @param array $context Additional context
     * @return void
     */
    public function trackDatabaseError(string $query, string $error, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $errorData = [
            'type' => 'DatabaseError',
            'query' => $this->sanitizeQuery($query),
            'error' => $error,
            'timestamp' => now()->toIso8601String(),
            'url' => request()->fullUrl(),
            'user_id' => auth()->id(),
            ...$context,
        ];

        $this->errors[] = $errorData;

        // Increment database error counter
        $this->incrementErrorCount('DatabaseError');

        // Check if error rate threshold exceeded
        $this->checkErrorRate('DatabaseError');

        Log::channel($this->logChannel)->error('Database error tracked', $errorData);
    }

    /**
     * Track a cache error
     * 
     * @param string $operation Cache operation (get, set, delete, etc.)
     * @param string $key Cache key
     * @param string $error Error message
     * @param array $context Additional context
     * @return void
     */
    public function trackCacheError(string $operation, string $key, string $error, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $errorData = [
            'type' => 'CacheError',
            'operation' => $operation,
            'key' => $key,
            'error' => $error,
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ];

        $this->errors[] = $errorData;

        // Increment cache error counter
        $this->incrementErrorCount('CacheError');
    }

    /**
     * Get error rate for a specific error type
     * 
     * @param string $errorType Error type
     * @return float Errors per minute
     */
    public function getErrorRate(string $errorType): float
    {
        $count = $this->getErrorCount($errorType);
        return ($count / $this->timeWindow) * 60; // Convert to errors per minute
    }

    /**
     * Get total error count for a specific error type
     * 
     * @param string $errorType Error type
     * @return int Error count
     */
    public function getErrorCount(string $errorType): int
    {
        $cacheKey = $this->cachePrefix . $errorType;
        return Cache::get($cacheKey, 0);
    }

    /**
     * Get all errors collected during request
     * 
     * @return array Errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get error statistics
     * 
     * @return array Error statistics
     */
    public function getStatistics(): array
    {
        $errorsByType = [];
        
        foreach ($this->errors as $error) {
            $type = $error['type'];
            if (!isset($errorsByType[$type])) {
                $errorsByType[$type] = 0;
            }
            $errorsByType[$type]++;
        }

        return [
            'total_errors' => count($this->errors),
            'errors_by_type' => $errorsByType,
            'unique_error_types' => count($errorsByType),
        ];
    }

    /**
     * Reset error collection
     * 
     * @return void
     */
    public function reset(): void
    {
        $this->errors = [];
    }

    /**
     * Clear error rate counters from cache
     * 
     * @param string|null $errorType Specific error type or null for all
     * @return void
     */
    public function clearCounters(?string $errorType = null): void
    {
        if ($errorType) {
            Cache::forget($this->cachePrefix . $errorType);
        } else {
            // Clear all error rate counters
            $keys = Cache::get($this->cachePrefix . 'tracked_types', []);
            foreach ($keys as $type) {
                Cache::forget($this->cachePrefix . $type);
            }
            Cache::forget($this->cachePrefix . 'tracked_types');
        }
    }

    /**
     * Increment error count in cache
     * 
     * @param string $errorType Error type
     * @return void
     */
    private function incrementErrorCount(string $errorType): void
    {
        $cacheKey = $this->cachePrefix . $errorType;
        
        // Increment counter with TTL
        $count = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $count + 1, $this->timeWindow);

        // Track error types
        $trackedTypes = Cache::get($this->cachePrefix . 'tracked_types', []);
        if (!in_array($errorType, $trackedTypes)) {
            $trackedTypes[] = $errorType;
            Cache::put($this->cachePrefix . 'tracked_types', $trackedTypes, 3600);
        }
    }

    /**
     * Check if error rate exceeds threshold and alert
     * 
     * @param string $errorType Error type
     * @return void
     */
    private function checkErrorRate(string $errorType): void
    {
        $errorRate = $this->getErrorRate($errorType);

        if ($errorRate >= $this->errorRateThreshold) {
            $this->alertHighErrorRate($errorType, $errorRate);
        }
    }

    /**
     * Alert about high error rate
     * 
     * @param string $errorType Error type
     * @param float $errorRate Current error rate
     * @return void
     */
    private function alertHighErrorRate(string $errorType, float $errorRate): void
    {
        // Check if we already alerted recently (prevent spam)
        $alertKey = $this->cachePrefix . 'alert:' . $errorType;
        if (Cache::has($alertKey)) {
            return;
        }

        // Set alert flag for 5 minutes
        Cache::put($alertKey, true, 300);

        Log::channel($this->logChannel)->critical('High error rate detected', [
            'error_type' => $errorType,
            'error_rate' => round($errorRate, 2),
            'threshold' => $this->errorRateThreshold,
            'time_window_seconds' => $this->timeWindow,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Sanitize query for logging
     * 
     * @param string $query SQL query
     * @param int $maxLength Maximum length
     * @return string Sanitized query
     */
    private function sanitizeQuery(string $query, int $maxLength = 500): string
    {
        $query = preg_replace('/\s+/', ' ', trim($query));
        
        if (strlen($query) > $maxLength) {
            return substr($query, 0, $maxLength) . '... [truncated]';
        }
        
        return $query;
    }
}
