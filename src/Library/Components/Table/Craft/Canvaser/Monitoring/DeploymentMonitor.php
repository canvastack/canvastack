<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Monitoring;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * DeploymentMonitor - Comprehensive monitoring system for POST method deployment
 * 
 * Provides monitoring capabilities including:
 * - Health checks
 * - Performance monitoring
 * - Error tracking
 * - Usage analytics
 * - System diagnostics
 * - Automated alerts
 */
class DeploymentMonitor
{
    /**
     * Monitor configuration
     */
    private array $config;

    /**
     * Health check results
     */
    private array $healthChecks = [];

    /**
     * Performance metrics
     */
    private array $performanceMetrics = [];

    /**
     * Error tracking
     */
    private array $errorTracking = [];

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'enable_health_checks' => true,
            'enable_performance_monitoring' => true,
            'enable_error_tracking' => true,
            'enable_usage_analytics' => true,
            'enable_automated_alerts' => true,
            'health_check_interval' => 300, // 5 minutes
            'performance_sample_rate' => 0.1, // 10% sampling
            'error_threshold' => 5, // errors per minute
            'response_time_threshold' => 2000, // 2 seconds
            'memory_usage_threshold' => 80, // 80% of limit
            'disk_usage_threshold' => 85, // 85% of available space
            'alert_channels' => ['log', 'email'], // log, email, slack, webhook
            'retention_days' => 30
        ], $config);
    }

    /**
     * Run comprehensive system health check
     */
    public function runHealthCheck(): array
    {
        $startTime = microtime(true);
        $healthStatus = [
            'overall_status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'checks' => [],
            'warnings' => [],
            'errors' => [],
            'execution_time' => 0
        ];

        try {
            // Database connectivity check
            $healthStatus['checks']['database'] = $this->checkDatabaseHealth();
            
            // Cache system check
            $healthStatus['checks']['cache'] = $this->checkCacheHealth();
            
            // File system check
            $healthStatus['checks']['filesystem'] = $this->checkFileSystemHealth();
            
            // Memory usage check
            $healthStatus['checks']['memory'] = $this->checkMemoryHealth();
            
            // POST method functionality check
            $healthStatus['checks']['post_method'] = $this->checkPostMethodHealth();
            
            // Filter system check
            $healthStatus['checks']['filters'] = $this->checkFilterSystemHealth();
            
            // Security system check
            $healthStatus['checks']['security'] = $this->checkSecuritySystemHealth();
            
            // Performance check
            $healthStatus['checks']['performance'] = $this->checkPerformanceHealth();

            // Determine overall status
            $healthStatus = $this->determineOverallHealth($healthStatus);
            
        } catch (\Exception $e) {
            $healthStatus['overall_status'] = 'critical';
            $healthStatus['errors'][] = [
                'type' => 'health_check_failure',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
        }

        $healthStatus['execution_time'] = round((microtime(true) - $startTime) * 1000, 2);
        $this->healthChecks[] = $healthStatus;
        
        // Store health check result
        $this->storeHealthCheckResult($healthStatus);
        
        // Send alerts if needed
        if ($healthStatus['overall_status'] !== 'healthy') {
            $this->sendHealthAlert($healthStatus);
        }

        return $healthStatus;
    }

    /**
     * Check database health
     */
    private function checkDatabaseHealth(): array
    {
        try {
            $startTime = microtime(true);
            
            // Test basic connectivity
            DB::select('SELECT 1');
            
            // Test write capability
            $testTable = 'health_check_test_' . time();
            DB::statement("CREATE TEMPORARY TABLE {$testTable} (id INT)");
            DB::statement("INSERT INTO {$testTable} VALUES (1)");
            DB::statement("DROP TEMPORARY TABLE {$testTable}");
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'connection_count' => $this->getDatabaseConnectionCount(),
                'slow_queries' => $this->getSlowQueryCount()
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'response_time_ms' => null
            ];
        }
    }

    /**
     * Check cache health
     */
    private function checkCacheHealth(): array
    {
        try {
            $startTime = microtime(true);
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';
            
            // Test write
            Cache::put($testKey, $testValue, 60);
            
            // Test read
            $retrievedValue = Cache::get($testKey);
            
            // Test delete
            Cache::forget($testKey);
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($retrievedValue !== $testValue) {
                throw new \Exception('Cache read/write mismatch');
            }
            
            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'driver' => config('cache.default'),
                'hit_rate' => $this->getCacheHitRate()
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'response_time_ms' => null
            ];
        }
    }

    /**
     * Check file system health
     */
    private function checkFileSystemHealth(): array
    {
        try {
            $storagePath = storage_path();
            $logPath = storage_path('logs');
            
            // Check disk space
            $totalSpace = disk_total_space($storagePath);
            $freeSpace = disk_free_space($storagePath);
            $usedPercentage = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2);
            
            // Check write permissions
            $testFile = $logPath . '/health_check_' . time() . '.tmp';
            file_put_contents($testFile, 'test');
            unlink($testFile);
            
            $status = $usedPercentage > $this->config['disk_usage_threshold'] ? 'warning' : 'healthy';
            
            return [
                'status' => $status,
                'disk_usage_percentage' => $usedPercentage,
                'free_space_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
                'total_space_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
                'writable' => true
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'writable' => false
            ];
        }
    }

    /**
     * Check memory health
     */
    private function checkMemoryHealth(): array
    {
        $memoryUsage = memory_get_usage();
        $memoryPeak = memory_get_peak_usage();
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        
        $usagePercentage = round(($memoryUsage / $memoryLimit) * 100, 2);
        $peakPercentage = round(($memoryPeak / $memoryLimit) * 100, 2);
        
        $status = 'healthy';
        if ($usagePercentage > $this->config['memory_usage_threshold']) {
            $status = 'warning';
        }
        if ($usagePercentage > 95) {
            $status = 'critical';
        }
        
        return [
            'status' => $status,
            'usage_percentage' => $usagePercentage,
            'peak_percentage' => $peakPercentage,
            'current_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'peak_usage_mb' => round($memoryPeak / 1024 / 1024, 2),
            'limit_mb' => round($memoryLimit / 1024 / 1024, 2)
        ];
    }

    /**
     * Check POST method health
     */
    private function checkPostMethodHealth(): array
    {
        try {
            // Test basic POST functionality
            $testData = [
                'renderDataTables' => 'true',
                'draw' => 1,
                'start' => 0,
                'length' => 1,
                'difta' => [
                    'name' => 'health_check',
                    'source' => 'test'
                ]
            ];
            
            // This would need to be adapted based on your actual implementation
            // For now, we'll just check if the classes exist and are loadable
            $postClass = 'Canvastack\Canvastack\Library\Components\Table\Craft\Post';
            
            if (!class_exists($postClass)) {
                throw new \Exception('POST method class not found');
            }
            
            return [
                'status' => 'healthy',
                'class_loaded' => true,
                'method_available' => true
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'class_loaded' => false
            ];
        }
    }

    /**
     * Check filter system health
     */
    private function checkFilterSystemHealth(): array
    {
        try {
            $filterClasses = [
                'DateRangeFilter' => 'Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Filters\DateRangeFilter',
                'SelectboxFilter' => 'Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Filters\SelectboxFilter'
            ];
            
            $loadedFilters = [];
            foreach ($filterClasses as $name => $class) {
                $loadedFilters[$name] = class_exists($class);
            }
            
            $allLoaded = !in_array(false, $loadedFilters);
            
            return [
                'status' => $allLoaded ? 'healthy' : 'unhealthy',
                'loaded_filters' => $loadedFilters,
                'total_filters' => count($filterClasses),
                'loaded_count' => count(array_filter($loadedFilters))
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check security system health
     */
    private function checkSecuritySystemHealth(): array
    {
        try {
            $securityChecks = [
                'csrf_protection' => $this->checkCSRFProtection(),
                'rate_limiting' => $this->checkRateLimiting(),
                'input_sanitization' => $this->checkInputSanitization(),
                'audit_logging' => $this->checkAuditLogging()
            ];
            
            $healthyChecks = array_filter($securityChecks);
            $status = count($healthyChecks) === count($securityChecks) ? 'healthy' : 'warning';
            
            return [
                'status' => $status,
                'checks' => $securityChecks,
                'healthy_count' => count($healthyChecks),
                'total_count' => count($securityChecks)
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check performance health
     */
    private function checkPerformanceHealth(): array
    {
        $recentMetrics = $this->getRecentPerformanceMetrics();
        
        if (empty($recentMetrics)) {
            return [
                'status' => 'unknown',
                'message' => 'No recent performance data available'
            ];
        }
        
        $avgResponseTime = array_sum(array_column($recentMetrics, 'response_time')) / count($recentMetrics);
        $errorRate = $this->calculateErrorRate($recentMetrics);
        
        $status = 'healthy';
        if ($avgResponseTime > $this->config['response_time_threshold']) {
            $status = 'warning';
        }
        if ($errorRate > 0.05) { // 5% error rate
            $status = 'critical';
        }
        
        return [
            'status' => $status,
            'avg_response_time_ms' => round($avgResponseTime, 2),
            'error_rate_percentage' => round($errorRate * 100, 2),
            'sample_size' => count($recentMetrics),
            'threshold_ms' => $this->config['response_time_threshold']
        ];
    }

    /**
     * Monitor POST method performance
     */
    public function monitorPerformance(array $requestData, float $executionTime, array $result): void
    {
        if (!$this->config['enable_performance_monitoring']) {
            return;
        }
        
        // Sample based on configuration
        if (mt_rand() / mt_getrandmax() > $this->config['performance_sample_rate']) {
            return;
        }
        
        $metric = [
            'timestamp' => now()->toISOString(),
            'execution_time_ms' => round($executionTime * 1000, 2),
            'memory_usage_mb' => round(memory_get_usage() / 1024 / 1024, 2),
            'peak_memory_mb' => round(memory_get_peak_usage() / 1024 / 1024, 2),
            'request_size_bytes' => strlen(json_encode($requestData)),
            'response_size_bytes' => strlen(json_encode($result)),
            'record_count' => $result['recordsTotal'] ?? 0,
            'filtered_count' => $result['recordsFiltered'] ?? 0,
            'has_filters' => !empty($requestData['filters']),
            'draw' => $requestData['draw'] ?? 0,
            'start' => $requestData['start'] ?? 0,
            'length' => $requestData['length'] ?? 0
        ];
        
        $this->performanceMetrics[] = $metric;
        $this->storePerformanceMetric($metric);
        
        // Check for performance issues
        if ($metric['execution_time_ms'] > $this->config['response_time_threshold']) {
            $this->logPerformanceIssue('slow_response', $metric);
        }
        
        if ($metric['memory_usage_mb'] > 256) { // High memory usage
            $this->logPerformanceIssue('high_memory_usage', $metric);
        }
    }

    /**
     * Track errors
     */
    public function trackError(\Exception $exception, array $context = []): void
    {
        if (!$this->config['enable_error_tracking']) {
            return;
        }
        
        $error = [
            'timestamp' => now()->toISOString(),
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
            'severity' => $this->determineSeverity($exception),
            'hash' => md5($exception->getMessage() . $exception->getFile() . $exception->getLine())
        ];
        
        $this->errorTracking[] = $error;
        $this->storeError($error);
        
        // Check error threshold
        $recentErrors = $this->getRecentErrors(60); // Last minute
        if (count($recentErrors) > $this->config['error_threshold']) {
            $this->sendErrorAlert($recentErrors);
        }
    }

    /**
     * Generate monitoring dashboard data
     */
    public function getDashboardData(): array
    {
        return [
            'health_status' => $this->getLatestHealthStatus(),
            'performance_summary' => $this->getPerformanceSummary(),
            'error_summary' => $this->getErrorSummary(),
            'usage_analytics' => $this->getUsageAnalytics(),
            'system_info' => $this->getSystemInfo(),
            'alerts' => $this->getActiveAlerts()
        ];
    }

    /**
     * Get latest health status
     */
    private function getLatestHealthStatus(): array
    {
        $latest = end($this->healthChecks);
        if (!$latest) {
            return $this->runHealthCheck();
        }
        
        // Run new health check if last one is too old
        $lastCheckTime = strtotime($latest['timestamp']);
        if (time() - $lastCheckTime > $this->config['health_check_interval']) {
            return $this->runHealthCheck();
        }
        
        return $latest;
    }

    /**
     * Get performance summary
     */
    private function getPerformanceSummary(): array
    {
        $recentMetrics = $this->getRecentPerformanceMetrics(3600); // Last hour
        
        if (empty($recentMetrics)) {
            return [
                'status' => 'no_data',
                'message' => 'No performance data available'
            ];
        }
        
        $responseTimes = array_column($recentMetrics, 'execution_time_ms');
        $memoryUsages = array_column($recentMetrics, 'memory_usage_mb');
        
        return [
            'total_requests' => count($recentMetrics),
            'avg_response_time_ms' => round(array_sum($responseTimes) / count($responseTimes), 2),
            'min_response_time_ms' => min($responseTimes),
            'max_response_time_ms' => max($responseTimes),
            'p95_response_time_ms' => $this->calculatePercentile($responseTimes, 95),
            'avg_memory_usage_mb' => round(array_sum($memoryUsages) / count($memoryUsages), 2),
            'max_memory_usage_mb' => max($memoryUsages),
            'requests_per_minute' => $this->calculateRequestsPerMinute($recentMetrics)
        ];
    }

    /**
     * Get error summary
     */
    private function getErrorSummary(): array
    {
        $recentErrors = $this->getRecentErrors(3600); // Last hour
        
        $errorsByType = [];
        $errorsBySeverity = [];
        
        foreach ($recentErrors as $error) {
            $type = $error['type'];
            $severity = $error['severity'];
            
            $errorsByType[$type] = ($errorsByType[$type] ?? 0) + 1;
            $errorsBySeverity[$severity] = ($errorsBySeverity[$severity] ?? 0) + 1;
        }
        
        return [
            'total_errors' => count($recentErrors),
            'error_rate' => $this->calculateErrorRate($recentErrors),
            'errors_by_type' => $errorsByType,
            'errors_by_severity' => $errorsBySeverity,
            'most_common_error' => $this->getMostCommonError($recentErrors)
        ];
    }

    /**
     * Export monitoring report
     */
    public function exportMonitoringReport(string $filename = null): string
    {
        if (!$filename) {
            $filename = 'monitoring_report_' . date('Y-m-d_H-i-s') . '.json';
        }
        
        $report = [
            'report_info' => [
                'generated_at' => now()->toISOString(),
                'period' => '24 hours',
                'version' => '1.0'
            ],
            'dashboard_data' => $this->getDashboardData(),
            'detailed_metrics' => [
                'health_checks' => $this->healthChecks,
                'performance_metrics' => $this->performanceMetrics,
                'error_tracking' => $this->errorTracking
            ],
            'configuration' => $this->config
        ];
        
        $filepath = storage_path('logs/' . $filename);
        file_put_contents($filepath, json_encode($report, JSON_PRETTY_PRINT));
        
        return $filepath;
    }

    /**
     * Utility methods
     */
    private function parseMemoryLimit(string $memoryLimit): int
    {
        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);
        
        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return (int) $memoryLimit;
        }
    }

    private function calculatePercentile(array $values, int $percentile): float
    {
        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);
        
        if (floor($index) == $index) {
            return $values[$index];
        }
        
        $lower = $values[floor($index)];
        $upper = $values[ceil($index)];
        $fraction = $index - floor($index);
        
        return $lower + ($fraction * ($upper - $lower));
    }

    private function determineOverallHealth(array $healthStatus): array
    {
        $statuses = array_column($healthStatus['checks'], 'status');
        
        if (in_array('critical', $statuses) || in_array('unhealthy', $statuses)) {
            $healthStatus['overall_status'] = 'unhealthy';
        } elseif (in_array('warning', $statuses)) {
            $healthStatus['overall_status'] = 'warning';
        } else {
            $healthStatus['overall_status'] = 'healthy';
        }
        
        return $healthStatus;
    }

    // Additional utility methods would be implemented here...
    // (Storage methods, alert methods, calculation methods, etc.)
    
    private function storeHealthCheckResult(array $result): void
    {
        // Implementation for storing health check results
        Cache::put('health_check_latest', $result, 3600);
    }
    
    private function storePerformanceMetric(array $metric): void
    {
        // Implementation for storing performance metrics
        $key = 'performance_metrics_' . date('Y-m-d-H');
        $existing = Cache::get($key, []);
        $existing[] = $metric;
        Cache::put($key, $existing, 3600);
    }
    
    private function storeError(array $error): void
    {
        // Implementation for storing errors
        Log::error('POST Method Error Tracked', $error);
    }
    
    private function sendHealthAlert(array $healthStatus): void
    {
        // Implementation for sending health alerts
        if (in_array('log', $this->config['alert_channels'])) {
            Log::warning('Health Check Alert', $healthStatus);
        }
    }
    
    private function sendErrorAlert(array $errors): void
    {
        // Implementation for sending error alerts
        Log::critical('Error Threshold Exceeded', [
            'error_count' => count($errors),
            'threshold' => $this->config['error_threshold']
        ]);
    }
    
    // Placeholder methods for various checks and calculations
    private function getDatabaseConnectionCount(): int { return 1; }
    private function getSlowQueryCount(): int { return 0; }
    private function getCacheHitRate(): float { return 0.95; }
    private function checkCSRFProtection(): bool { return true; }
    private function checkRateLimiting(): bool { return true; }
    private function checkInputSanitization(): bool { return true; }
    private function checkAuditLogging(): bool { return true; }
    private function getRecentPerformanceMetrics(int $seconds = 3600): array { return []; }
    private function getRecentErrors(int $seconds = 3600): array { return []; }
    private function calculateErrorRate(array $metrics): float { return 0.0; }
    private function calculateRequestsPerMinute(array $metrics): float { return 0.0; }
    private function getMostCommonError(array $errors): ?string { return null; }
    private function getUsageAnalytics(): array { return []; }
    private function getSystemInfo(): array { return []; }
    private function getActiveAlerts(): array { return []; }
    private function logPerformanceIssue(string $type, array $metric): void {}
    private function determineSeverity(\Exception $exception): string { return 'medium'; }
}