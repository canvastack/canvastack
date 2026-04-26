<?php

namespace Canvastack\Canvastack\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Slow Query Logger Service
 * 
 * Monitors database queries and logs queries that exceed the configured threshold.
 * Provides detailed information about slow queries including execution time,
 * query parameters, and stack trace.
 * 
 * @package Canvastack\Canvastack\Services
 */
class SlowQueryLogger
{
    /**
     * Log channel for slow queries
     */
    private string $logChannel;

    /**
     * Whether slow query logging is enabled
     */
    private bool $enabled;

    /**
     * Slow query threshold in milliseconds
     */
    private int $threshold;

    /**
     * Collected slow queries
     */
    private array $slowQueries = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->logChannel = config('canvastack.controller.logging.log_channel', 'stack');
        $this->enabled = config('canvastack.controller.logging.log_performance_issues', true);
        $this->threshold = config('canvastack.controller.performance.slow_query_threshold', 1000);
    }

    /**
     * Register query listener to detect slow queries
     * 
     * @return void
     */
    public function register(): void
    {
        if (!$this->enabled) {
            return;
        }

        DB::listen(function ($query) {
            $this->checkQuery($query->sql, $query->time, $query->bindings, $query->connectionName);
        });
    }

    /**
     * Check if query is slow and log it
     * 
     * @param string $sql SQL query
     * @param float $time Execution time in milliseconds
     * @param array $bindings Query bindings
     * @param string $connection Connection name
     * @return void
     */
    public function checkQuery(string $sql, float $time, array $bindings = [], string $connection = 'default'): void
    {
        if (!$this->enabled || $time < $this->threshold) {
            return;
        }

        $this->logSlowQuery($sql, $time, $bindings, $connection);
    }

    /**
     * Log a slow query
     * 
     * @param string $sql SQL query
     * @param float $time Execution time in milliseconds
     * @param array $bindings Query bindings
     * @param string $connection Connection name
     * @return void
     */
    public function logSlowQuery(string $sql, float $time, array $bindings = [], string $connection = 'default'): void
    {
        $queryData = [
            'sql' => $this->sanitizeQuery($sql),
            'bindings' => $this->sanitizeBindings($bindings),
            'time_ms' => $time,
            'threshold_ms' => $this->threshold,
            'connection' => $connection,
            'timestamp' => now()->toIso8601String(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'user_id' => auth()->id(),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        ];

        // Add stack trace in debug mode
        if (config('app.debug')) {
            $queryData['stack_trace'] = $this->getStackTrace();
        }

        $this->slowQueries[] = $queryData;

        Log::channel($this->logChannel)->warning('Slow query detected', $queryData);
    }

    /**
     * Get all slow queries collected during request
     * 
     * @return array Slow queries
     */
    public function getSlowQueries(): array
    {
        return $this->slowQueries;
    }

    /**
     * Get slow query count
     * 
     * @return int Number of slow queries
     */
    public function getSlowQueryCount(): int
    {
        return count($this->slowQueries);
    }

    /**
     * Get total time spent on slow queries
     * 
     * @return float Total time in milliseconds
     */
    public function getTotalSlowQueryTime(): float
    {
        return array_sum(array_column($this->slowQueries, 'time_ms'));
    }

    /**
     * Get slowest query
     * 
     * @return array|null Slowest query data
     */
    public function getSlowestQuery(): ?array
    {
        if (empty($this->slowQueries)) {
            return null;
        }

        return collect($this->slowQueries)->sortByDesc('time_ms')->first();
    }

    /**
     * Reset slow query collection
     * 
     * @return void
     */
    public function reset(): void
    {
        $this->slowQueries = [];
    }

    /**
     * Get query statistics
     * 
     * @return array Query statistics
     */
    public function getStatistics(): array
    {
        if (empty($this->slowQueries)) {
            return [
                'count' => 0,
                'total_time_ms' => 0,
                'average_time_ms' => 0,
                'slowest_time_ms' => 0,
            ];
        }

        $times = array_column($this->slowQueries, 'time_ms');

        return [
            'count' => count($this->slowQueries),
            'total_time_ms' => array_sum($times),
            'average_time_ms' => array_sum($times) / count($times),
            'slowest_time_ms' => max($times),
            'fastest_time_ms' => min($times),
        ];
    }

    /**
     * Sanitize query for logging
     * 
     * @param string $sql SQL query
     * @param int $maxLength Maximum length
     * @return string Sanitized query
     */
    private function sanitizeQuery(string $sql, int $maxLength = 1000): string
    {
        // Normalize whitespace
        $sql = preg_replace('/\s+/', ' ', trim($sql));
        
        // Truncate if too long
        if (strlen($sql) > $maxLength) {
            return substr($sql, 0, $maxLength) . '... [truncated]';
        }
        
        return $sql;
    }

    /**
     * Sanitize query bindings for logging
     * 
     * @param array $bindings Query bindings
     * @return array Sanitized bindings
     */
    private function sanitizeBindings(array $bindings): array
    {
        return array_map(function ($binding) {
            if (is_string($binding) && strlen($binding) > 100) {
                return substr($binding, 0, 100) . '... [truncated]';
            }
            return $binding;
        }, $bindings);
    }

    /**
     * Get simplified stack trace
     * 
     * @param int $limit Maximum number of frames
     * @return array Stack trace
     */
    private function getStackTrace(int $limit = 10): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit + 5);
        
        // Skip internal frames
        $trace = array_slice($trace, 5);
        
        return array_map(function ($frame) {
            return [
                'file' => $frame['file'] ?? 'unknown',
                'line' => $frame['line'] ?? 0,
                'function' => ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? ''),
            ];
        }, array_slice($trace, 0, $limit));
    }
}
