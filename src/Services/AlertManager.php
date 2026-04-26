<?php

namespace Canvastack\Canvastack\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/**
 * Alert Manager Service
 * 
 * Manages alerts for security incidents and performance issues.
 * Provides configurable thresholds and alert channels.
 * 
 * @package Canvastack\Canvastack\Services
 */
class AlertManager
{
    /**
     * Log channel for alerts
     */
    private string $logChannel;

    /**
     * Whether alerting is enabled
     */
    private bool $enabled;

    /**
     * Cache key prefix for alert tracking
     */
    private string $cachePrefix = 'alert_manager:';

    /**
     * Alert cooldown period in seconds (prevent spam)
     */
    private int $cooldownPeriod;

    /**
     * Security alert thresholds
     */
    private array $securityThresholds;

    /**
     * Performance alert thresholds
     */
    private array $performanceThresholds;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->logChannel = config('canvastack.controller.logging.log_channel', 'stack');
        $this->enabled = config('canvastack.controller.monitoring.alerts_enabled', true);
        $this->cooldownPeriod = config('canvastack.controller.monitoring.alert_cooldown', 300);
        
        $this->securityThresholds = [
            'xss_attempts' => config('canvastack.controller.monitoring.xss_alert_threshold', 5),
            'sql_injection_attempts' => config('canvastack.controller.monitoring.sql_injection_alert_threshold', 3),
            'csrf_failures' => config('canvastack.controller.monitoring.csrf_alert_threshold', 10),
            'privilege_violations' => config('canvastack.controller.monitoring.privilege_alert_threshold', 5),
            'file_upload_violations' => config('canvastack.controller.monitoring.file_upload_alert_threshold', 5),
        ];

        $this->performanceThresholds = [
            'slow_queries' => config('canvastack.controller.monitoring.slow_query_alert_threshold', 10),
            'high_memory_usage' => config('canvastack.controller.monitoring.memory_alert_threshold', 80),
            'slow_page_loads' => config('canvastack.controller.monitoring.slow_page_alert_threshold', 5),
            'cache_miss_rate' => config('canvastack.controller.monitoring.cache_miss_alert_threshold', 50),
        ];
    }

    /**
     * Alert about XSS attempts
     * 
     * @param int $count Number of attempts
     * @param array $context Additional context
     * @return void
     */
    public function alertXSSAttempts(int $count, array $context = []): void
    {
        if (!$this->enabled || $count < $this->securityThresholds['xss_attempts']) {
            return;
        }

        $this->sendSecurityAlert('xss_attempts', [
            'severity' => 'high',
            'message' => "Multiple XSS attempts detected: {$count} attempts",
            'count' => $count,
            'threshold' => $this->securityThresholds['xss_attempts'],
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Alert about SQL injection attempts
     * 
     * @param int $count Number of attempts
     * @param array $context Additional context
     * @return void
     */
    public function alertSQLInjectionAttempts(int $count, array $context = []): void
    {
        if (!$this->enabled || $count < $this->securityThresholds['sql_injection_attempts']) {
            return;
        }

        $this->sendSecurityAlert('sql_injection_attempts', [
            'severity' => 'critical',
            'message' => "Multiple SQL injection attempts detected: {$count} attempts",
            'count' => $count,
            'threshold' => $this->securityThresholds['sql_injection_attempts'],
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Alert about CSRF failures
     * 
     * @param int $count Number of failures
     * @param array $context Additional context
     * @return void
     */
    public function alertCSRFFailures(int $count, array $context = []): void
    {
        if (!$this->enabled || $count < $this->securityThresholds['csrf_failures']) {
            return;
        }

        $this->sendSecurityAlert('csrf_failures', [
            'severity' => 'medium',
            'message' => "Multiple CSRF token failures detected: {$count} failures",
            'count' => $count,
            'threshold' => $this->securityThresholds['csrf_failures'],
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Alert about privilege violations
     * 
     * @param int $count Number of violations
     * @param array $context Additional context
     * @return void
     */
    public function alertPrivilegeViolations(int $count, array $context = []): void
    {
        if (!$this->enabled || $count < $this->securityThresholds['privilege_violations']) {
            return;
        }

        $this->sendSecurityAlert('privilege_violations', [
            'severity' => 'high',
            'message' => "Multiple privilege violations detected: {$count} violations",
            'count' => $count,
            'threshold' => $this->securityThresholds['privilege_violations'],
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Alert about file upload security violations
     * 
     * @param int $count Number of violations
     * @param array $context Additional context
     * @return void
     */
    public function alertFileUploadViolations(int $count, array $context = []): void
    {
        if (!$this->enabled || $count < $this->securityThresholds['file_upload_violations']) {
            return;
        }

        $this->sendSecurityAlert('file_upload_violations', [
            'severity' => 'high',
            'message' => "Multiple file upload security violations detected: {$count} violations",
            'count' => $count,
            'threshold' => $this->securityThresholds['file_upload_violations'],
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Alert about slow queries
     * 
     * @param int $count Number of slow queries
     * @param float $averageTime Average query time in milliseconds
     * @param array $context Additional context
     * @return void
     */
    public function alertSlowQueries(int $count, float $averageTime, array $context = []): void
    {
        if (!$this->enabled || $count < $this->performanceThresholds['slow_queries']) {
            return;
        }

        $this->sendPerformanceAlert('slow_queries', [
            'severity' => 'medium',
            'message' => "Multiple slow queries detected: {$count} queries",
            'count' => $count,
            'average_time_ms' => round($averageTime, 2),
            'threshold' => $this->performanceThresholds['slow_queries'],
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Alert about high memory usage
     * 
     * @param float $percentage Memory usage percentage
     * @param int $usedMB Memory used in MB
     * @param array $context Additional context
     * @return void
     */
    public function alertHighMemoryUsage(float $percentage, int $usedMB, array $context = []): void
    {
        if (!$this->enabled || $percentage < $this->performanceThresholds['high_memory_usage']) {
            return;
        }

        $this->sendPerformanceAlert('high_memory_usage', [
            'severity' => 'high',
            'message' => "High memory usage detected: {$percentage}%",
            'percentage' => round($percentage, 2),
            'used_mb' => $usedMB,
            'threshold' => $this->performanceThresholds['high_memory_usage'],
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Alert about slow page loads
     * 
     * @param int $count Number of slow page loads
     * @param float $averageTime Average load time in milliseconds
     * @param array $context Additional context
     * @return void
     */
    public function alertSlowPageLoads(int $count, float $averageTime, array $context = []): void
    {
        if (!$this->enabled || $count < $this->performanceThresholds['slow_page_loads']) {
            return;
        }

        $this->sendPerformanceAlert('slow_page_loads', [
            'severity' => 'medium',
            'message' => "Multiple slow page loads detected: {$count} pages",
            'count' => $count,
            'average_time_ms' => round($averageTime, 2),
            'threshold' => $this->performanceThresholds['slow_page_loads'],
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Alert about high cache miss rate
     * 
     * @param float $missRate Cache miss rate percentage
     * @param array $context Additional context
     * @return void
     */
    public function alertHighCacheMissRate(float $missRate, array $context = []): void
    {
        if (!$this->enabled || $missRate < $this->performanceThresholds['cache_miss_rate']) {
            return;
        }

        $this->sendPerformanceAlert('cache_miss_rate', [
            'severity' => 'low',
            'message' => "High cache miss rate detected: {$missRate}%",
            'miss_rate' => round($missRate, 2),
            'threshold' => $this->performanceThresholds['cache_miss_rate'],
            'timestamp' => now()->toIso8601String(),
            ...$context,
        ]);
    }

    /**
     * Send security alert
     * 
     * @param string $alertType Alert type
     * @param array $data Alert data
     * @return void
     */
    private function sendSecurityAlert(string $alertType, array $data): void
    {
        // Check cooldown to prevent spam
        if (!$this->checkCooldown('security:' . $alertType)) {
            return;
        }

        Log::channel($this->logChannel)->critical("Security Alert: {$alertType}", $data);

        // Here you can add additional notification channels
        // For example: email, Slack, SMS, etc.
        // Notification::route('mail', config('canvastack.controller.monitoring.alert_email'))
        //     ->notify(new SecurityAlertNotification($alertType, $data));
    }

    /**
     * Send performance alert
     * 
     * @param string $alertType Alert type
     * @param array $data Alert data
     * @return void
     */
    private function sendPerformanceAlert(string $alertType, array $data): void
    {
        // Check cooldown to prevent spam
        if (!$this->checkCooldown('performance:' . $alertType)) {
            return;
        }

        Log::channel($this->logChannel)->warning("Performance Alert: {$alertType}", $data);

        // Here you can add additional notification channels
        // For example: email, Slack, SMS, etc.
    }

    /**
     * Check if alert is in cooldown period
     * 
     * @param string $alertKey Alert key
     * @return bool True if alert can be sent, false if in cooldown
     */
    private function checkCooldown(string $alertKey): bool
    {
        $cacheKey = $this->cachePrefix . 'cooldown:' . $alertKey;

        if (Cache::has($cacheKey)) {
            return false;
        }

        // Set cooldown
        Cache::put($cacheKey, true, $this->cooldownPeriod);
        return true;
    }

    /**
     * Clear alert cooldown
     * 
     * @param string|null $alertKey Specific alert key or null for all
     * @return void
     */
    public function clearCooldown(?string $alertKey = null): void
    {
        if ($alertKey) {
            Cache::forget($this->cachePrefix . 'cooldown:' . $alertKey);
        } else {
            // Clear all cooldowns (implementation depends on cache driver)
            // This is a simplified version
            foreach (array_keys($this->securityThresholds) as $type) {
                Cache::forget($this->cachePrefix . 'cooldown:security:' . $type);
            }
            foreach (array_keys($this->performanceThresholds) as $type) {
                Cache::forget($this->cachePrefix . 'cooldown:performance:' . $type);
            }
        }
    }
}
