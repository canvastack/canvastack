<?php

namespace Canvastack\Canvastack\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Performance Monitor Service
 * 
 * Monitors and logs performance metrics including query execution times,
 * memory usage, cache hit rates, and page load times.
 * 
 * @package Canvastack\Canvastack\Services
 */
class PerformanceMonitor
{
    /**
     * Log channel for performance events
     */
    private string $logChannel;

    /**
     * Whether performance monitoring is enabled
     */
    private bool $enabled;

    /**
     * Slow query threshold in milliseconds
     */
    private int $slowQueryThreshold;

    /**
     * Performance metrics storage
     */
    private array $metrics = [];

    /**
     * Timers for tracking operation durations
     */
    private array $timers = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->logChannel = config('canvastack.controller.logging.log_channel', 'stack');
        $this->enabled = config('canvastack.controller.performance.performance_monitoring', false);
        $this->slowQueryThreshold = config('canvastack.controller.performance.slow_query_threshold', 1000);
    }

    /**
     * Start a performance timer
     * 
     * @param string $name Timer name
     * @return void
     */
    public function startTimer(string $name): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->timers[$name] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage(true),
        ];
    }

    /**
     * Stop a performance timer and log if threshold exceeded
     * 
     * @param string $name Timer name
     * @param array $context Additional context
     * @return float|null Duration in milliseconds
     */
    public function stopTimer(string $name, array $context = []): ?float
    {
        if (!$this->enabled || !isset($this->timers[$name])) {
            return null;
        }

        $duration = (microtime(true) - $this->timers[$name]['start']) * 1000; // Convert to milliseconds
        $memoryUsed = memory_get_usage(true) - $this->timers[$name]['memory_start'];

        $this->metrics[$name] = [
            'duration_ms' => $duration,
            'memory_used_bytes' => $memoryUsed,
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ];

        unset($this->timers[$name]);

        return $duration;
    }

    /**
     * Log query execution time
     * 
     * @param string $query SQL query
     * @param float $duration Duration in milliseconds
     * @param array $context Additional context
     * @return void
     */
    public function logQueryExecution(string $query, float $duration, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        // Log slow queries
        if ($duration >= $this->slowQueryThreshold) {
            $this->logSlowQuery($query, $duration, $context);
        }

        // Store metric
        $this->metrics['queries'][] = [
            'query' => $this->sanitizeQuery($query),
            'duration_ms' => $duration,
            'is_slow' => $duration >= $this->slowQueryThreshold,
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ];
    }

    /**
     * Log memory usage
     * 
     * @param string $operation Operation name
     * @param int $memoryUsed Memory used in bytes
     * @param array $context Additional context
     * @return void
     */
    public function logMemoryUsage(string $operation, int $memoryUsed, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $memoryLimit = $this->parseMemoryLimit(config('canvastack.controller.performance.memory_limit', '256M'));
        $memoryUsageMB = $memoryUsed / 1024 / 1024;
        $memoryLimitMB = $memoryLimit / 1024 / 1024;
        $percentageUsed = ($memoryUsed / $memoryLimit) * 100;

        // Log if memory usage exceeds 80% of limit
        if ($percentageUsed >= 80) {
            Log::channel($this->logChannel)->warning('High memory usage detected', [
                'operation' => $operation,
                'memory_used_mb' => round($memoryUsageMB, 2),
                'memory_limit_mb' => round($memoryLimitMB, 2),
                'percentage_used' => round($percentageUsed, 2),
                'timestamp' => now()->toIso8601String(),
                ...$context,
            ]);
        }

        $this->metrics['memory'][] = [
            'operation' => $operation,
            'memory_used_bytes' => $memoryUsed,
            'memory_used_mb' => round($memoryUsageMB, 2),
            'percentage_used' => round($percentageUsed, 2),
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ];
    }

    /**
     * Log cache hit/miss
     * 
     * @param string $key Cache key
     * @param bool $hit Whether cache was hit
     * @param array $context Additional context
     * @return void
     */
    public function logCacheAccess(string $key, bool $hit, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!isset($this->metrics['cache'])) {
            $this->metrics['cache'] = [
                'hits' => 0,
                'misses' => 0,
                'hit_rate' => 0,
            ];
        }

        if ($hit) {
            $this->metrics['cache']['hits']++;
        } else {
            $this->metrics['cache']['misses']++;
        }

        $total = $this->metrics['cache']['hits'] + $this->metrics['cache']['misses'];
        $this->metrics['cache']['hit_rate'] = $total > 0 ? ($this->metrics['cache']['hits'] / $total) * 100 : 0;
    }

    /**
     * Log page load time
     * 
     * @param string $route Route name
     * @param float $duration Duration in milliseconds
     * @param array $context Additional context
     * @return void
     */
    public function logPageLoad(string $route, float $duration, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->metrics['page_loads'][] = [
            'route' => $route,
            'duration_ms' => $duration,
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ];

        // Log slow page loads (>2 seconds)
        if ($duration >= 2000) {
            Log::channel($this->logChannel)->warning('Slow page load detected', [
                'route' => $route,
                'duration_ms' => $duration,
                'timestamp' => now()->toIso8601String(),
                ...$context,
            ]);
        }
    }

    /**
     * Get all collected metrics
     * 
     * @return array Performance metrics
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Get cache hit rate
     * 
     * @return float Cache hit rate percentage
     */
    public function getCacheHitRate(): float
    {
        return $this->metrics['cache']['hit_rate'] ?? 0;
    }

    /**
     * Get average query execution time
     * 
     * @return float Average duration in milliseconds
     */
    public function getAverageQueryTime(): float
    {
        if (!isset($this->metrics['queries']) || empty($this->metrics['queries'])) {
            return 0;
        }

        $total = array_sum(array_column($this->metrics['queries'], 'duration_ms'));
        return $total / count($this->metrics['queries']);
    }

    /**
     * Get slow query count
     * 
     * @return int Number of slow queries
     */
    public function getSlowQueryCount(): int
    {
        if (!isset($this->metrics['queries'])) {
            return 0;
        }

        return count(array_filter($this->metrics['queries'], fn($q) => $q['is_slow']));
    }

    /**
     * Reset all metrics
     * 
     * @return void
     */
    public function resetMetrics(): void
    {
        $this->metrics = [];
        $this->timers = [];
    }

    /**
     * Log slow query
     * 
     * @param string $query SQL query
     * @param float $duration Duration in milliseconds
     * @param array $context Additional context
     * @return void
     */
    private function logSlowQuery(string $query, float $duration, array $context = []): void
    {
        if (!config('canvastack.controller.logging.log_performance_issues', true)) {
            return;
        }

        Log::channel($this->logChannel)->warning('Slow query detected', [
            'query' => $this->sanitizeQuery($query),
            'duration_ms' => $duration,
            'threshold_ms' => $this->slowQueryThreshold,
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Sanitize query for logging (truncate long queries)
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

    /**
     * Parse memory limit string to bytes
     * 
     * @param string $limit Memory limit (e.g., "256M", "1G")
     * @return int Memory limit in bytes
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($last) {
            case 'g':
                $value *= 1024;
                // fall through
            case 'm':
                $value *= 1024;
                // fall through
            case 'k':
                $value *= 1024;
        }

        return $value;
    }
}
