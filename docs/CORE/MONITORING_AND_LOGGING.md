# Monitoring and Logging Guide

بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

Alhamdulillah, this document provides comprehensive guidance on the monitoring and logging system for CanvaStack Core Controller Components.

## Table of Contents

1. [Overview](#overview)
2. [Security Event Logging](#security-event-logging)
3. [Performance Monitoring](#performance-monitoring)
4. [Slow Query Logging](#slow-query-logging)
5. [Error Rate Monitoring](#error-rate-monitoring)
6. [Alert System](#alert-system)
7. [Configuration](#configuration)
8. [Usage Examples](#usage-examples)
9. [Best Practices](#best-practices)
10. [Troubleshooting](#troubleshooting)

## Overview

The monitoring and logging system provides comprehensive visibility into security events, performance metrics, and system health. It consists of five main components:

1. **SecurityEventLogger** - Logs security-related events (XSS, SQL injection, CSRF, privilege violations)
2. **PerformanceMonitor** - Tracks performance metrics (query times, memory usage, cache hit rates)
3. **SlowQueryLogger** - Monitors and logs slow database queries
4. **ErrorRateMonitor** - Tracks error rates and exception frequencies
5. **AlertManager** - Sends alerts when thresholds are exceeded

### Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Application Layer                         │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                  Monitoring Services                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │  Security    │  │ Performance  │  │ Slow Query   │     │
│  │  Event       │  │  Monitor     │  │  Logger      │     │
│  │  Logger      │  │              │  │              │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
│  ┌──────────────┐  ┌──────────────┐                        │
│  │  Error Rate  │  │   Alert      │                        │
│  │  Monitor     │  │   Manager    │                        │
│  └──────────────┘  └──────────────┘                        │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Storage Layer                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │   Log Files  │  │    Cache     │  │  Database    │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└─────────────────────────────────────────────────────────────┘
```

## Security Event Logging

### Overview

The `SecurityEventLogger` service logs all security-related events including:

- XSS (Cross-Site Scripting) attempts
- SQL injection attempts
- CSRF token failures
- Privilege violations
- File upload security violations
- Session security events
- Validation failures

### Usage

```php
use Canvastack\Canvastack\Services\SecurityEventLogger;

$logger = new SecurityEventLogger();

// Log XSS attempt
$logger->logXSSAttempt(
    '<script>alert("xss")</script>',
    'user_input_field',
    ['field' => 'comment', 'user_id' => 123]
);

// Log SQL injection attempt
$logger->logSQLInjectionAttempt(
    "SELECT * FROM users WHERE id = '1' OR '1'='1'",
    'filter_query',
    ['table' => 'users', 'filter' => 'id']
);

// Log CSRF failure
$logger->logCSRFFailure(
    'Token mismatch',
    ['expected' => 'abc123', 'received' => 'xyz789']
);

// Log privilege violation
$logger->logPrivilegeViolation(
    123,
    'admin_panel',
    'delete',
    ['resource' => 'users', 'resource_id' => 456]
);

// Log file upload security event
$logger->logFileUploadSecurityEvent(
    'invalid_type',
    'malicious.php',
    ['mime_type' => 'application/x-php', 'size' => 1024]
);

// Log session security event
$logger->logSessionSecurityEvent(
    'tampered',
    ['session_id' => 'abc123', 'expected_hash' => 'xyz']
);

// Log validation failure
$logger->logValidationFailure(
    'file_upload',
    ['file_type' => 'Invalid file type', 'file_size' => 'File too large'],
    ['field' => 'avatar']
);
```

### Log Format

Security events are logged with the following structure:

```json
{
    "level": "warning",
    "message": "Security Event: xss_attempt",
    "context": {
        "input": "<script>alert(\"xss\")</script>",
        "location": "user_input_field",
        "ip_address": "192.168.1.100",
        "user_agent": "Mozilla/5.0...",
        "user_id": 123,
        "url": "https://example.com/comments",
        "method": "POST",
        "timestamp": "2024-01-15T10:30:00+00:00"
    }
}
```

## Performance Monitoring

### Overview

The `PerformanceMonitor` service tracks:

- Query execution times
- Memory usage
- Cache hit/miss rates
- Page load times
- Operation durations

### Usage

```php
use Canvastack\Canvastack\Services\PerformanceMonitor;

$monitor = new PerformanceMonitor();

// Start a timer
$monitor->startTimer('data_processing');

// ... perform operations ...

// Stop timer and get duration
$duration = $monitor->stopTimer('data_processing', [
    'operation' => 'user_data_export',
    'records' => 1000
]);

// Log query execution
$monitor->logQueryExecution(
    'SELECT * FROM users WHERE active = 1',
    125.5, // duration in milliseconds
    ['connection' => 'mysql', 'rows' => 100]
);

// Log memory usage
$monitor->logMemoryUsage(
    'image_processing',
    memory_get_usage(true),
    ['image_count' => 10, 'total_size_mb' => 50]
);

// Log cache access
$monitor->logCacheAccess('user_preferences_123', true); // cache hit
$monitor->logCacheAccess('user_settings_456', false); // cache miss

// Log page load
$monitor->logPageLoad(
    'admin.users.index',
    1250.5, // duration in milliseconds
    ['query_count' => 5, 'memory_mb' => 25]
);

// Get metrics
$metrics = $monitor->getMetrics();
$cacheHitRate = $monitor->getCacheHitRate();
$avgQueryTime = $monitor->getAverageQueryTime();
$slowQueryCount = $monitor->getSlowQueryCount();
```

### Performance Metrics

The monitor collects the following metrics:

```php
[
    'queries' => [
        [
            'query' => 'SELECT * FROM users...',
            'duration_ms' => 125.5,
            'is_slow' => false,
            'timestamp' => '2024-01-15T10:30:00+00:00'
        ]
    ],
    'memory' => [
        [
            'operation' => 'image_processing',
            'memory_used_mb' => 50.25,
            'percentage_used' => 19.6,
            'timestamp' => '2024-01-15T10:30:00+00:00'
        ]
    ],
    'cache' => [
        'hits' => 85,
        'misses' => 15,
        'hit_rate' => 85.0
    ],
    'page_loads' => [
        [
            'route' => 'admin.users.index',
            'duration_ms' => 1250.5,
            'timestamp' => '2024-01-15T10:30:00+00:00'
        ]
    ]
]
```

## Slow Query Logging

### Overview

The `SlowQueryLogger` service automatically monitors all database queries and logs those that exceed the configured threshold (default: 1000ms).

### Setup

Register the slow query logger in your service provider:

```php
use Canvastack\Canvastack\Services\SlowQueryLogger;

public function boot()
{
    $slowQueryLogger = new SlowQueryLogger();
    $slowQueryLogger->register();
}
```

### Usage

Once registered, the logger automatically tracks all queries. You can also manually check queries:

```php
use Canvastack\Canvastack\Services\SlowQueryLogger;

$logger = new SlowQueryLogger();

// Manually check a query
$logger->checkQuery(
    'SELECT * FROM large_table WHERE condition = ?',
    1500.5, // duration in milliseconds
    ['value1'],
    'mysql'
);

// Get slow queries
$slowQueries = $logger->getSlowQueries();
$count = $logger->getSlowQueryCount();
$totalTime = $logger->getTotalSlowQueryTime();
$slowest = $logger->getSlowestQuery();

// Get statistics
$stats = $logger->getStatistics();
// Returns: ['count' => 5, 'total_time_ms' => 7500, 'average_time_ms' => 1500, ...]
```

### Slow Query Log Format

```json
{
    "level": "warning",
    "message": "Slow query detected",
    "context": {
        "sql": "SELECT * FROM users WHERE...",
        "bindings": ["value1", "value2"],
        "time_ms": 1500.5,
        "threshold_ms": 1000,
        "connection": "mysql",
        "timestamp": "2024-01-15T10:30:00+00:00",
        "url": "https://example.com/users",
        "method": "GET",
        "user_id": 123,
        "memory_usage_mb": 25.5,
        "stack_trace": [...]
    }
}
```

## Error Rate Monitoring

### Overview

The `ErrorRateMonitor` service tracks error rates and alerts when thresholds are exceeded. It monitors:

- Exceptions
- Validation failures
- Database errors
- Cache errors

### Usage

```php
use Canvastack\Canvastack\Services\ErrorRateMonitor;

$monitor = new ErrorRateMonitor();

// Track an exception
try {
    // ... code that might throw exception ...
} catch (\Exception $e) {
    $monitor->trackException($e, [
        'operation' => 'user_registration',
        'step' => 'email_validation'
    ]);
}

// Track validation failure
$monitor->trackValidationFailure(
    'user_input',
    ['email' => 'Invalid email format', 'age' => 'Must be 18 or older'],
    ['form' => 'registration']
);

// Track database error
$monitor->trackDatabaseError(
    'INSERT INTO users...',
    'Duplicate entry for key PRIMARY',
    ['table' => 'users', 'operation' => 'insert']
);

// Track cache error
$monitor->trackCacheError(
    'set',
    'user_preferences_123',
    'Connection timeout',
    ['driver' => 'redis']
);

// Get error rate (errors per minute)
$errorRate = $monitor->getErrorRate('ValidationFailure');

// Get error count
$count = $monitor->getErrorCount('DatabaseError');

// Get statistics
$stats = $monitor->getStatistics();
// Returns: ['total_errors' => 10, 'errors_by_type' => [...], ...]
```

### Error Rate Calculation

Error rates are calculated as errors per minute within a configurable time window (default: 60 seconds):

```
Error Rate = (Error Count / Time Window in Seconds) × 60
```

## Alert System

### Overview

The `AlertManager` service sends alerts when security or performance thresholds are exceeded. It includes:

- Configurable thresholds
- Alert cooldown to prevent spam
- Multiple severity levels
- Extensible notification channels

### Usage

```php
use Canvastack\Canvastack\Services\AlertManager;

$alertManager = new AlertManager();

// Security alerts
$alertManager->alertXSSAttempts(10, ['source' => 'contact_form']);
$alertManager->alertSQLInjectionAttempts(5, ['table' => 'users']);
$alertManager->alertCSRFFailures(15, ['endpoint' => '/api/users']);
$alertManager->alertPrivilegeViolations(8, ['module' => 'admin_panel']);
$alertManager->alertFileUploadViolations(6, ['file_type' => 'php']);

// Performance alerts
$alertManager->alertSlowQueries(12, 1500.5, ['connection' => 'mysql']);
$alertManager->alertHighMemoryUsage(85.5, 220, ['operation' => 'export']);
$alertManager->alertSlowPageLoads(7, 2500.0, ['route' => 'admin.reports']);
$alertManager->alertHighCacheMissRate(65.0, ['cache_driver' => 'redis']);

// Clear cooldown (for testing or manual reset)
$alertManager->clearCooldown('security:xss_attempts');
$alertManager->clearCooldown(); // clear all
```

### Alert Thresholds

Default thresholds (configurable in `config/canvastack.controller.php`):

**Security Alerts:**
- XSS attempts: 5 per time window
- SQL injection attempts: 3 per time window
- CSRF failures: 10 per time window
- Privilege violations: 5 per time window
- File upload violations: 5 per time window

**Performance Alerts:**
- Slow queries: 10 per request
- High memory usage: 80% of limit
- Slow page loads: 5 per time window
- Cache miss rate: 50%

### Alert Severity Levels

- **Critical**: Immediate action required (SQL injection, high error rates)
- **High**: Urgent attention needed (XSS attempts, privilege violations, high memory)
- **Medium**: Should be addressed soon (CSRF failures, slow queries)
- **Low**: Informational (cache miss rate)

## Configuration

### Configuration File

All monitoring and logging settings are configured in `config/canvastack.controller.php`:

```php
'logging' => [
    // Enable/disable logging
    'log_security_events' => env('CANVASTACK_LOG_SECURITY_EVENTS', true),
    'log_performance_issues' => env('CANVASTACK_LOG_PERFORMANCE_ISSUES', true),
    'log_validation_failures' => env('CANVASTACK_LOG_VALIDATION_FAILURES', true),
    'log_file_uploads' => env('CANVASTACK_LOG_FILE_UPLOADS', true),
    'log_privilege_violations' => env('CANVASTACK_LOG_PRIVILEGE_VIOLATIONS', true),
    'log_csrf_failures' => env('CANVASTACK_LOG_CSRF_FAILURES', true),
    'log_sql_injection_attempts' => env('CANVASTACK_LOG_SQL_INJECTION_ATTEMPTS', true),
    
    // Log channel
    'log_channel' => env('CANVASTACK_LOG_CHANNEL', 'stack'),
],

'performance' => [
    // Performance monitoring
    'performance_monitoring' => env('CANVASTACK_PERFORMANCE_MONITORING', false),
    'slow_query_threshold' => env('CANVASTACK_SLOW_QUERY_THRESHOLD', 1000),
    'memory_limit' => env('CANVASTACK_MEMORY_LIMIT', '256M'),
],

'monitoring' => [
    // Alert configuration
    'alerts_enabled' => env('CANVASTACK_ALERTS_ENABLED', true),
    'alert_cooldown' => env('CANVASTACK_ALERT_COOLDOWN', 300),
    
    // Security alert thresholds
    'xss_alert_threshold' => env('CANVASTACK_XSS_ALERT_THRESHOLD', 5),
    'sql_injection_alert_threshold' => env('CANVASTACK_SQL_INJECTION_ALERT_THRESHOLD', 3),
    'csrf_alert_threshold' => env('CANVASTACK_CSRF_ALERT_THRESHOLD', 10),
    'privilege_alert_threshold' => env('CANVASTACK_PRIVILEGE_ALERT_THRESHOLD', 5),
    'file_upload_alert_threshold' => env('CANVASTACK_FILE_UPLOAD_ALERT_THRESHOLD', 5),
    
    // Performance alert thresholds
    'slow_query_alert_threshold' => env('CANVASTACK_SLOW_QUERY_ALERT_THRESHOLD', 10),
    'memory_alert_threshold' => env('CANVASTACK_MEMORY_ALERT_THRESHOLD', 80),
    'slow_page_alert_threshold' => env('CANVASTACK_SLOW_PAGE_ALERT_THRESHOLD', 5),
    'cache_miss_alert_threshold' => env('CANVASTACK_CACHE_MISS_ALERT_THRESHOLD', 50),
    
    // Error rate monitoring
    'error_rate_threshold' => env('CANVASTACK_ERROR_RATE_THRESHOLD', 10),
    'error_rate_window' => env('CANVASTACK_ERROR_RATE_WINDOW', 60),
],
```

### Environment Variables

Add these to your `.env` file:

```env
# Logging
CANVASTACK_LOG_SECURITY_EVENTS=true
CANVASTACK_LOG_PERFORMANCE_ISSUES=true
CANVASTACK_LOG_CHANNEL=stack

# Performance
CANVASTACK_PERFORMANCE_MONITORING=true
CANVASTACK_SLOW_QUERY_THRESHOLD=1000
CANVASTACK_MEMORY_LIMIT=256M

# Alerts
CANVASTACK_ALERTS_ENABLED=true
CANVASTACK_ALERT_COOLDOWN=300

# Security thresholds
CANVASTACK_XSS_ALERT_THRESHOLD=5
CANVASTACK_SQL_INJECTION_ALERT_THRESHOLD=3
CANVASTACK_CSRF_ALERT_THRESHOLD=10

# Performance thresholds
CANVASTACK_SLOW_QUERY_ALERT_THRESHOLD=10
CANVASTACK_MEMORY_ALERT_THRESHOLD=80
```

## Usage Examples

### Example 1: Complete Request Monitoring

```php
use Canvastack\Canvastack\Services\PerformanceMonitor;
use Canvastack\Canvastack\Services\SecurityEventLogger;
use Canvastack\Canvastack\Services\ErrorRateMonitor;

class UserController extends Controller
{
    private PerformanceMonitor $performanceMonitor;
    private SecurityEventLogger $securityLogger;
    private ErrorRateMonitor $errorMonitor;
    
    public function __construct()
    {
        $this->performanceMonitor = new PerformanceMonitor();
        $this->securityLogger = new SecurityEventLogger();
        $this->errorMonitor = new ErrorRateMonitor();
    }
    
    public function index(Request $request)
    {
        // Start performance timer
        $this->performanceMonitor->startTimer('user_index');
        
        try {
            // Validate input
            $validated = $request->validate([
                'search' => 'nullable|string|max:100',
            ]);
            
            // Query users
            $users = User::where('active', true)
                ->when($validated['search'] ?? null, function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->paginate(20);
            
            // Stop timer
            $duration = $this->performanceMonitor->stopTimer('user_index');
            
            // Log page load
            $this->performanceMonitor->logPageLoad('users.index', $duration);
            
            return view('users.index', compact('users'));
            
        } catch (ValidationException $e) {
            // Track validation failure
            $this->errorMonitor->trackValidationFailure(
                'user_search',
                $e->errors(),
                ['route' => 'users.index']
            );
            throw $e;
            
        } catch (\Exception $e) {
            // Track exception
            $this->errorMonitor->trackException($e, [
                'route' => 'users.index',
                'action' => 'index'
            ]);
            throw $e;
        }
    }
}
```

### Example 2: Security Event Logging

```php
use Canvastack\Canvastack\Services\SecurityEventLogger;

class AuthController extends Controller
{
    private SecurityEventLogger $securityLogger;
    
    public function login(Request $request)
    {
        $this->securityLogger = new SecurityEventLogger();
        
        // Validate CSRF token
        if (!$request->hasValidSignature()) {
            $this->securityLogger->logCSRFFailure(
                'Invalid signature',
                ['route' => 'login', 'ip' => $request->ip()]
            );
            abort(403);
        }
        
        // Check for XSS in input
        $username = $request->input('username');
        if (preg_match('/<script|javascript:/i', $username)) {
            $this->securityLogger->logXSSAttempt(
                $username,
                'login_username',
                ['ip' => $request->ip()]
            );
            abort(400, 'Invalid input detected');
        }
        
        // Attempt login
        if (Auth::attempt($request->only('username', 'password'))) {
            return redirect()->intended('dashboard');
        }
        
        return back()->withErrors(['username' => 'Invalid credentials']);
    }
}
```

### Example 3: Performance Monitoring with Alerts

```php
use Canvastack\Canvastack\Services\PerformanceMonitor;
use Canvastack\Canvastack\Services\SlowQueryLogger;
use Canvastack\Canvastack\Services\AlertManager;

class ReportController extends Controller
{
    public function generate(Request $request)
    {
        $monitor = new PerformanceMonitor();
        $slowQueryLogger = new SlowQueryLogger();
        $alertManager = new AlertManager();
        
        // Register slow query logger
        $slowQueryLogger->register();
        
        // Start monitoring
        $monitor->startTimer('report_generation');
        
        // Generate report (potentially slow operation)
        $report = $this->generateLargeReport($request->input('year'));
        
        // Stop monitoring
        $duration = $monitor->stopTimer('report_generation');
        
        // Check for slow queries
        $slowQueryCount = $slowQueryLogger->getSlowQueryCount();
        if ($slowQueryCount > 0) {
            $stats = $slowQueryLogger->getStatistics();
            $alertManager->alertSlowQueries(
                $slowQueryCount,
                $stats['average_time_ms'],
                ['operation' => 'report_generation']
            );
        }
        
        // Check memory usage
        $memoryUsed = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $percentage = ($memoryUsed / $this->parseMemoryLimit($memoryLimit)) * 100;
        
        if ($percentage > 80) {
            $alertManager->alertHighMemoryUsage(
                $percentage,
                round($memoryUsed / 1024 / 1024),
                ['operation' => 'report_generation']
            );
        }
        
        return response()->json(['report' => $report]);
    }
}
```

## Best Practices

### 1. Enable Monitoring in Production

Always enable monitoring in production environments to detect issues early:

```env
CANVASTACK_LOG_SECURITY_EVENTS=true
CANVASTACK_LOG_PERFORMANCE_ISSUES=true
CANVASTACK_PERFORMANCE_MONITORING=true
CANVASTACK_ALERTS_ENABLED=true
```

### 2. Configure Appropriate Thresholds

Adjust thresholds based on your application's normal behavior:

```env
# For high-traffic applications
CANVASTACK_SLOW_QUERY_THRESHOLD=500
CANVASTACK_XSS_ALERT_THRESHOLD=20

# For low-traffic applications
CANVASTACK_SLOW_QUERY_THRESHOLD=2000
CANVASTACK_XSS_ALERT_THRESHOLD=3
```

### 3. Use Separate Log Channels

Configure separate log channels for different event types:

```php
// config/logging.php
'channels' => [
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'warning',
        'days' => 90,
    ],
    'performance' => [
        'driver' => 'daily',
        'path' => storage_path('logs/performance.log'),
        'level' => 'info',
        'days' => 30,
    ],
],
```

### 4. Monitor Key Metrics

Focus on these key metrics:

- **Security**: XSS attempts, SQL injection attempts, privilege violations
- **Performance**: Slow queries, memory usage, cache hit rate
- **Reliability**: Error rate, exception frequency, database errors

### 5. Set Up Alert Notifications

Extend the AlertManager to send notifications via email, Slack, or SMS:

```php
// In AlertManager::sendSecurityAlert()
Notification::route('slack', config('services.slack.webhook'))
    ->notify(new SecurityAlertNotification($alertType, $data));
```

### 6. Regular Log Review

Schedule regular reviews of logs to identify patterns:

```bash
# Daily security log review
php artisan canvastack:review-security-logs --yesterday

# Weekly performance report
php artisan canvastack:performance-report --week
```

### 7. Archive Old Logs

Implement log rotation and archival:

```php
// config/logging.php
'daily' => [
    'driver' => 'daily',
    'path' => storage_path('logs/laravel.log'),
    'level' => 'debug',
    'days' => 14, // Keep logs for 14 days
],
```

## Troubleshooting

### Issue: Logs Not Being Written

**Symptoms**: No log entries in log files

**Solutions**:
1. Check logging is enabled in configuration
2. Verify log channel exists in `config/logging.php`
3. Check file permissions on `storage/logs` directory
4. Verify disk space is available

```bash
# Check permissions
chmod -R 775 storage/logs

# Check disk space
df -h
```

### Issue: Too Many Alert Notifications

**Symptoms**: Receiving excessive alert notifications

**Solutions**:
1. Increase alert thresholds
2. Increase cooldown period
3. Review and fix underlying issues

```env
# Increase cooldown to 10 minutes
CANVASTACK_ALERT_COOLDOWN=600

# Increase thresholds
CANVASTACK_XSS_ALERT_THRESHOLD=20
```

### Issue: Performance Monitoring Overhead

**Symptoms**: Application slowdown with monitoring enabled

**Solutions**:
1. Disable monitoring in development
2. Use sampling (monitor only percentage of requests)
3. Optimize log writing (use queue for async logging)

```env
# Disable in development
CANVASTACK_PERFORMANCE_MONITORING=false

# Or use sampling
CANVASTACK_MONITORING_SAMPLE_RATE=0.1  # Monitor 10% of requests
```

### Issue: Slow Query Logger Missing Queries

**Symptoms**: Known slow queries not being logged

**Solutions**:
1. Verify slow query logger is registered
2. Check threshold configuration
3. Ensure database query logging is enabled

```php
// In AppServiceProvider::boot()
$slowQueryLogger = new SlowQueryLogger();
$slowQueryLogger->register();
```

### Issue: Cache Errors in ErrorRateMonitor

**Symptoms**: Cache connection errors when tracking errors

**Solutions**:
1. Verify cache driver is configured correctly
2. Check cache connection is available
3. Use fallback to file-based tracking

```env
# Use file cache as fallback
CACHE_DRIVER=file
```

## Conclusion

The monitoring and logging system provides comprehensive visibility into your application's security and performance. By following the best practices and properly configuring thresholds, you can proactively detect and address issues before they impact users.

For additional support or questions, please refer to the main documentation or contact the development team.

---

بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

Alhamdulillah, monitoring and logging documentation has been completed.
