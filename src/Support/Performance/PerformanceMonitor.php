<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Performance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Performance Monitor.
 *
 * Monitors and tracks performance metrics for themes, translations,
 * and other system components.
 */
class PerformanceMonitor
{
    /**
     * Cache key prefix.
     */
    protected string $cachePrefix = 'canvastack.performance';

    /**
     * Metrics storage.
     */
    protected array $metrics = [];

    /**
     * Timers storage.
     */
    protected array $timers = [];

    /**
     * Memory snapshots.
     */
    protected array $memorySnapshots = [];

    /**
     * Enable logging.
     */
    protected bool $loggingEnabled = false;

    /**
     * Start a timer.
     */
    public function startTimer(string $name): void
    {
        $this->timers[$name] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage(true),
        ];
    }

    /**
     * Stop a timer and record the metric.
     */
    public function stopTimer(string $name): array
    {
        if (!isset($this->timers[$name])) {
            return [
                'error' => 'Timer not found',
                'name' => $name,
            ];
        }

        $timer = $this->timers[$name];
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $metric = [
            'name' => $name,
            'duration_ms' => round(($endTime - $timer['start']) * 1000, 2),
            'memory_used_bytes' => $endMemory - $timer['memory_start'],
            'memory_used_kb' => round(($endMemory - $timer['memory_start']) / 1024, 2),
            'memory_used_mb' => round(($endMemory - $timer['memory_start']) / 1024 / 1024, 2),
            'timestamp' => time(),
        ];

        $this->recordMetric($name, $metric);

        unset($this->timers[$name]);

        if ($this->loggingEnabled) {
            Log::info("Performance: {$name}", $metric);
        }

        return $metric;
    }

    /**
     * Record a metric.
     */
    public function recordMetric(string $name, array $data): void
    {
        if (!isset($this->metrics[$name])) {
            $this->metrics[$name] = [];
        }

        $this->metrics[$name][] = $data;

        // Keep only last 100 metrics per name
        if (count($this->metrics[$name]) > 100) {
            array_shift($this->metrics[$name]);
        }
    }

    /**
     * Get metrics for a specific name.
     */
    public function getMetrics(string $name): array
    {
        return $this->metrics[$name] ?? [];
    }

    /**
     * Get all metrics.
     */
    public function getAllMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Get metric statistics.
     */
    public function getMetricStats(string $name): array
    {
        $metrics = $this->getMetrics($name);

        if (empty($metrics)) {
            return [
                'count' => 0,
                'avg_duration_ms' => 0,
                'min_duration_ms' => 0,
                'max_duration_ms' => 0,
                'avg_memory_kb' => 0,
                'min_memory_kb' => 0,
                'max_memory_kb' => 0,
            ];
        }

        $durations = array_column($metrics, 'duration_ms');
        $memories = array_column($metrics, 'memory_used_kb');

        return [
            'count' => count($metrics),
            'avg_duration_ms' => round(array_sum($durations) / count($durations), 2),
            'min_duration_ms' => round(min($durations), 2),
            'max_duration_ms' => round(max($durations), 2),
            'avg_memory_kb' => round(array_sum($memories) / count($memories), 2),
            'min_memory_kb' => round(min($memories), 2),
            'max_memory_kb' => round(max($memories), 2),
        ];
    }

    /**
     * Take a memory snapshot.
     */
    public function snapshotMemory(string $label): void
    {
        $this->memorySnapshots[$label] = [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Get memory snapshot.
     */
    public function getMemorySnapshot(string $label): ?array
    {
        return $this->memorySnapshots[$label] ?? null;
    }

    /**
     * Compare two memory snapshots.
     */
    public function compareMemorySnapshots(string $label1, string $label2): array
    {
        $snapshot1 = $this->getMemorySnapshot($label1);
        $snapshot2 = $this->getMemorySnapshot($label2);

        if (!$snapshot1 || !$snapshot2) {
            return [
                'error' => 'One or both snapshots not found',
            ];
        }

        $memoryDiff = $snapshot2['memory_usage'] - $snapshot1['memory_usage'];
        $peakDiff = $snapshot2['memory_peak'] - $snapshot1['memory_peak'];
        $timeDiff = $snapshot2['timestamp'] - $snapshot1['timestamp'];

        return [
            'label1' => $label1,
            'label2' => $label2,
            'memory_diff_bytes' => $memoryDiff,
            'memory_diff_kb' => round($memoryDiff / 1024, 2),
            'memory_diff_mb' => round($memoryDiff / 1024 / 1024, 2),
            'peak_diff_bytes' => $peakDiff,
            'peak_diff_kb' => round($peakDiff / 1024, 2),
            'peak_diff_mb' => round($peakDiff / 1024 / 1024, 2),
            'time_diff_ms' => round($timeDiff * 1000, 2),
        ];
    }

    /**
     * Get current system performance.
     */
    public function getCurrentPerformance(): array
    {
        return [
            'memory_usage_bytes' => memory_get_usage(true),
            'memory_usage_kb' => round(memory_get_usage(true) / 1024, 2),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_bytes' => memory_get_peak_usage(true),
            'memory_peak_kb' => round(memory_get_peak_usage(true) / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'memory_limit' => ini_get('memory_limit'),
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Monitor a callable and return its result with metrics.
     */
    public function monitor(string $name, callable $callback): array
    {
        $this->startTimer($name);
        $this->snapshotMemory("{$name}_start");

        try {
            $result = $callback();
            $success = true;
            $error = null;
        } catch (\Exception $e) {
            $result = null;
            $success = false;
            $error = $e->getMessage();
        }

        $this->snapshotMemory("{$name}_end");
        $metrics = $this->stopTimer($name);
        $memoryComparison = $this->compareMemorySnapshots("{$name}_start", "{$name}_end");

        return [
            'success' => $success,
            'result' => $result,
            'error' => $error,
            'metrics' => $metrics,
            'memory' => $memoryComparison,
        ];
    }

    /**
     * Get performance report.
     */
    public function getReport(): array
    {
        $report = [
            'current_performance' => $this->getCurrentPerformance(),
            'metrics_summary' => [],
            'total_metrics' => 0,
        ];

        foreach ($this->metrics as $name => $metrics) {
            $report['metrics_summary'][$name] = $this->getMetricStats($name);
            $report['total_metrics'] += count($metrics);
        }

        return $report;
    }

    /**
     * Get performance recommendations.
     */
    public function getRecommendations(): array
    {
        $recommendations = [];
        $currentPerf = $this->getCurrentPerformance();

        // Check memory usage
        $memoryLimitBytes = $this->parseMemoryLimit(ini_get('memory_limit'));
        $memoryUsagePercent = ($currentPerf['memory_usage_bytes'] / $memoryLimitBytes) * 100;

        if ($memoryUsagePercent > 80) {
            $recommendations[] = [
                'type' => 'critical',
                'category' => 'memory',
                'message' => 'Memory usage is very high (' . round($memoryUsagePercent, 2) . '%)',
                'suggestion' => 'Consider increasing memory_limit or optimizing memory usage',
            ];
        } elseif ($memoryUsagePercent > 60) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'memory',
                'message' => 'Memory usage is high (' . round($memoryUsagePercent, 2) . '%)',
                'suggestion' => 'Monitor memory usage and consider optimization',
            ];
        }

        // Check slow operations
        foreach ($this->metrics as $name => $metrics) {
            $stats = $this->getMetricStats($name);

            if ($stats['avg_duration_ms'] > 1000) {
                $recommendations[] = [
                    'type' => 'warning',
                    'category' => 'performance',
                    'message' => "{$name} is slow (avg: {$stats['avg_duration_ms']}ms)",
                    'suggestion' => 'Consider caching or optimization',
                ];
            }

            if ($stats['max_duration_ms'] > 5000) {
                $recommendations[] = [
                    'type' => 'critical',
                    'category' => 'performance',
                    'message' => "{$name} has very slow peaks (max: {$stats['max_duration_ms']}ms)",
                    'suggestion' => 'Investigate and optimize slow operations',
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Parse memory limit string to bytes.
     */
    protected function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($last) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Enable logging.
     */
    public function enableLogging(): self
    {
        $this->loggingEnabled = true;

        return $this;
    }

    /**
     * Disable logging.
     */
    public function disableLogging(): self
    {
        $this->loggingEnabled = false;

        return $this;
    }

    /**
     * Clear all metrics.
     */
    public function clearMetrics(): void
    {
        $this->metrics = [];
        $this->timers = [];
        $this->memorySnapshots = [];
    }

    /**
     * Export metrics to array.
     */
    public function exportMetrics(): array
    {
        return [
            'metrics' => $this->metrics,
            'current_performance' => $this->getCurrentPerformance(),
            'exported_at' => time(),
        ];
    }

    /**
     * Save metrics to cache.
     */
    public function saveToCache(int $ttl = 3600): void
    {
        Cache::put("{$this->cachePrefix}.metrics", $this->exportMetrics(), $ttl);
    }

    /**
     * Load metrics from cache.
     */
    public function loadFromCache(): bool
    {
        $cached = Cache::get("{$this->cachePrefix}.metrics");

        if ($cached && isset($cached['metrics'])) {
            $this->metrics = $cached['metrics'];

            return true;
        }

        return false;
    }
}
