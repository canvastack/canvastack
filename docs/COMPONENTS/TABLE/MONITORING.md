# Monitoring Setup Guide

**Version:** 2.0.0  
**Last Updated:** April 4, 2026  
**Status:** Production Ready

---

## Overview

This guide covers the complete monitoring setup for CanvaStack Table Components, including security event logging, performance monitoring, slow query tracking, error rate monitoring, and alerting configuration.

The monitoring system provides comprehensive visibility into:
- Security events and potential threats
- Performance metrics and bottlenecks
- Query execution times and slow queries
- Cache hit rates and effectiveness
- Memory usage and resource consumption
- Error rates and exception patterns

---

## Table of Contents

1. [Security Event Logging](#security-event-logging)
2. [Performance Monitoring](#performance-monitoring)
3. [Slow Query Logging](#slow-query-logging)
4. [Error Rate Monitoring](#error-rate-monitoring)
5. [Cache Monitoring](#cache-monitoring)
6. [Memory Monitoring](#memory-monitoring)
7. [Alert Configuration](#alert-configuration)
8. [Log Channels](#log-channels)
9. [Monitoring Dashboard](#monitoring-dashboard)
10. [Best Practices](#best-practices)

---

## Security Event Logging

### Configuration

Security event logging tracks potential security threats and suspicious activities.

**File:** `config/canvastack.datatables.php`

```php
'security' => [
    /*
    | Log Security Events
    | Log all security-related events
    */
    'log_security_events' => true,

    /*
    | Security Log Channel
    | Laravel log channel for security events
    */
    'security_log_channel' => 'daily',

    /*
    | Log Levels
    | - info: Normal security checks
    | - warning: Suspicious activity
    | - error: Security violations
    | - critical: Severe security incidents
    */
    'security_log_level' => 'warning',

    /*
    | Log Context
    | Include additional context in logs
    */
    'log_context' => [
        'user_id' => true,
        'ip_address' => true,
        'user_agent' => true,
        'request_url' => true,
        'request_method' => true,
    ],
],
```

### Security Events Tracked

The system automatically logs the following security events:

1. **XSS Attempts**
   - Unescaped user input detected
   - Dangerous HTML patterns in input
   - Script injection attempts

2. **SQL Injection Attempts**
   - Invalid operators detected
   - Suspicious SQL patterns
   - Table name validation failures
   - Column name validation failures

3. **Input Validation Failures**
   - Invalid pagination parameters
   - Invalid sort parameters
   - Malformed search terms
   - Excessive input length

4. **Access Violations**
   - Unauthorized table access attempts
   - Privilege check failures
   - Destructive action attempts without confirmation

### Using Security Logging

```php
use function Canvastack\Canvastack\Library\Helpers\canvastack_table_log_security_event;

// Log a security event
canvastack_table_log_security_event(
    'xss_attempt',
    'Potential XSS detected in column label',
    [
        'table' => 'users',
        'column' => 'name',
        'value' => $suspiciousValue,
        'user_id' => auth()->id(),
    ]
);
```

### Security Log Format

```
[2026-04-04 10:15:23] security.WARNING: SQL Injection Attempt Detected
{
    "event_type": "sql_injection_attempt",
    "message": "Invalid operator detected",
    "context": {
        "table": "users",
        "operator": "'; DROP TABLE users--",
        "user_id": 123,
        "ip_address": "192.168.1.100",
        "user_agent": "Mozilla/5.0...",
        "request_url": "/admin/users/datatable",
        "timestamp": "2026-04-04T10:15:23+00:00"
    }
}
```

### Viewing Security Logs

```bash
# View today's security logs
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep security

# Search for specific security events
grep "sql_injection_attempt" storage/logs/*.log

# Count security events by type
grep "security\." storage/logs/*.log | cut -d':' -f3 | sort | uniq -c
```

---

## Performance Monitoring

### Configuration

**File:** `config/canvastack.datatables.php`

```php
'performance' => [
    /*
    | Monitor Memory Usage
    */
    'monitor_memory' => true,

    /*
    | Log Performance Metrics
    */
    'log_performance_metrics' => true,

    /*
    | Performance Log Channel
    */
    'performance_log_channel' => 'daily',

    /*
    | Track Query Execution Time
    */
    'track_query_time' => true,

    /*
    | Track Cache Operations
    */
    'track_cache_operations' => true,

    /*
    | Performance Thresholds
    */
    'thresholds' => [
        'query_time_warning' => 500,      // milliseconds
        'query_time_critical' => 1000,    // milliseconds
        'memory_warning' => 50,           // MB
        'memory_critical' => 100,         // MB
    ],
],
```

### Metrics Tracked

1. **Query Performance**
   - Query execution time
   - Number of queries executed
   - Query complexity (joins, conditions)
   - Result set size

2. **Memory Usage**
   - Peak memory usage
   - Memory per row processed
   - Memory growth rate
   - Memory limit warnings

3. **Cache Performance**
   - Cache hit rate
   - Cache miss rate
   - Cache operation time
   - Cache size

4. **Processing Time**
   - Total request processing time
   - Data transformation time
   - HTML generation time
   - JavaScript generation time

### Performance Log Format

```
[2026-04-04 10:20:15] performance.INFO: DataTables Request Processed
{
    "table": "users",
    "metrics": {
        "total_time": 245,
        "query_time": 120,
        "processing_time": 85,
        "rendering_time": 40,
        "queries_executed": 3,
        "rows_processed": 100,
        "memory_peak": 12.5,
        "cache_hits": 5,
        "cache_misses": 2
    },
    "timestamp": "2026-04-04T10:20:15+00:00"
}
```

### Viewing Performance Metrics

```bash
# View performance logs
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep performance

# Extract query times
grep "query_time" storage/logs/*.log | jq '.metrics.query_time'

# Calculate average query time
grep "query_time" storage/logs/*.log | jq '.metrics.query_time' | awk '{sum+=$1; count++} END {print sum/count}'
```

---

## Slow Query Logging

### Configuration

**File:** `config/canvastack.datatables.php`

```php
'performance' => [
    /*
    | Log Slow Queries
    */
    'log_slow_queries' => true,

    /*
    | Slow Query Threshold (milliseconds)
    | Queries exceeding this threshold will be logged
    */
    'slow_query_threshold' => 1000,

    /*
    | Slow Query Log Channel
    */
    'slow_query_log_channel' => 'daily',

    /*
    | Log Query SQL
    | Include full SQL in slow query logs
    */
    'log_query_sql' => true,

    /*
    | Log Query Bindings
    | Include parameter bindings
    */
    'log_query_bindings' => true,

    /*
    | Log Query Explain
    | Include EXPLAIN output for slow queries
    */
    'log_query_explain' => true,
],
```

### Slow Query Log Format

```
[2026-04-04 10:25:30] slow_query.WARNING: Slow Query Detected
{
    "execution_time": 1250,
    "threshold": 1000,
    "table": "users",
    "sql": "SELECT * FROM users WHERE email LIKE ? ORDER BY created_at DESC LIMIT 100",
    "bindings": ["%@example.com%"],
    "explain": [
        {
            "id": 1,
            "select_type": "SIMPLE",
            "table": "users",
            "type": "ALL",
            "possible_keys": null,
            "key": null,
            "rows": 50000,
            "Extra": "Using where; Using filesort"
        }
    ],
    "context": {
        "request_url": "/admin/users/datatable",
        "user_id": 123
    },
    "timestamp": "2026-04-04T10:25:30+00:00"
}
```

### Analyzing Slow Queries

```bash
# View slow queries
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep slow_query

# Find slowest queries
grep "slow_query" storage/logs/*.log | jq '.execution_time' | sort -rn | head -10

# Group slow queries by table
grep "slow_query" storage/logs/*.log | jq -r '.table' | sort | uniq -c | sort -rn
```

### Optimization Recommendations

When slow queries are detected:

1. **Check Indexes**
   - Verify indexes exist on filtered columns
   - Add composite indexes for multi-column filters
   - Check index usage with EXPLAIN

2. **Optimize Queries**
   - Use select-only-required columns
   - Enable eager loading for relationships
   - Reduce result set size with better filters

3. **Enable Caching**
   - Cache frequently accessed data
   - Use query result caching
   - Implement schema caching

---

## Error Rate Monitoring

### Configuration

**File:** `config/canvastack.datatables.php`

```php
'error_handling' => [
    /*
    | Log Errors
    */
    'log_errors' => true,

    /*
    | Error Log Channel
    */
    'error_log_channel' => 'daily',

    /*
    | Log Stack Trace
    */
    'log_stack_trace' => true,

    /*
    | Log Request Context
    */
    'log_request_context' => true,

    /*
    | Error Rate Threshold
    | Alert when error rate exceeds this percentage
    */
    'error_rate_threshold' => 5, // 5% error rate

    /*
    | Error Rate Window
    | Time window for calculating error rate (minutes)
    */
    'error_rate_window' => 60,
],
```

### Error Types Tracked

1. **Validation Errors**
   - Invalid input parameters
   - Schema validation failures
   - Type mismatch errors

2. **Database Errors**
   - Connection failures
   - Query execution errors
   - Transaction failures

3. **Security Errors**
   - XSS attempt blocked
   - SQL injection prevented
   - Access denied

4. **Performance Errors**
   - Memory limit exceeded
   - Query timeout
   - Cache operation failures

### Error Log Format

```
[2026-04-04 10:30:45] error.ERROR: Table Component Error
{
    "error_type": "InvalidColumnException",
    "message": "Column 'invalid_column' does not exist in table 'users'",
    "table": "users",
    "column": "invalid_column",
    "stack_trace": "...",
    "context": {
        "request_url": "/admin/users/datatable",
        "user_id": 123,
        "request_data": {...}
    },
    "timestamp": "2026-04-04T10:30:45+00:00"
}
```

### Monitoring Error Rates

```bash
# Count errors in last hour
grep "error\." storage/logs/laravel-$(date +%Y-%m-%d).log | \
  awk -v cutoff="$(date -d '1 hour ago' '+%Y-%m-%d %H:%M:%S')" \
  '$0 > cutoff' | wc -l

# Group errors by type
grep "error_type" storage/logs/*.log | jq -r '.error_type' | sort | uniq -c | sort -rn

# Calculate error rate
total=$(grep "DataTables Request" storage/logs/*.log | wc -l)
errors=$(grep "error\." storage/logs/*.log | wc -l)
echo "scale=2; ($errors / $total) * 100" | bc
```

---

## Cache Monitoring

### Configuration

**File:** `config/canvastack.cache.php`

```php
'monitoring' => [
    /*
    | Enable Cache Monitoring
    */
    'enabled' => true,

    /*
    | Log Cache Hits and Misses
    */
    'log_hits_misses' => true,

    /*
    | Log Channel for Monitoring
    */
    'log_channel' => 'daily',

    /*
    | Track Cache Statistics
    */
    'track_statistics' => true,

    /*
    | Statistics TTL (24 hours)
    */
    'statistics_ttl' => 86400,

    /*
    | Cache Hit Rate Threshold
    | Alert when hit rate falls below this percentage
    */
    'hit_rate_threshold' => 70,
],
```

### Cache Metrics Tracked

1. **Hit/Miss Rates**
   - Cache hit count
   - Cache miss count
   - Hit rate percentage
   - Miss rate percentage

2. **Operation Performance**
   - Cache read time
   - Cache write time
   - Cache invalidation time

3. **Cache Size**
   - Total cache entries
   - Cache memory usage
   - Cache growth rate

### Cache Log Format

```
[2026-04-04 10:35:20] cache.INFO: Cache Operation
{
    "operation": "get",
    "key": "schema_users",
    "hit": true,
    "time": 2.5,
    "size": 1024,
    "timestamp": "2026-04-04T10:35:20+00:00"
}
```

### Viewing Cache Statistics

```bash
# View cache operations
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep cache

# Calculate hit rate
hits=$(grep '"hit":true' storage/logs/*.log | wc -l)
misses=$(grep '"hit":false' storage/logs/*.log | wc -l)
total=$((hits + misses))
echo "scale=2; ($hits / $total) * 100" | bc

# Find most accessed cache keys
grep "cache\." storage/logs/*.log | jq -r '.key' | sort | uniq -c | sort -rn | head -10
```

---

## Memory Monitoring

### Configuration

**File:** `config/canvastack.datatables.php`

```php
'performance' => [
    /*
    | Monitor Memory Usage
    */
    'monitor_memory' => true,

    /*
    | Memory Warning Threshold (MB)
    */
    'memory_warning_threshold' => 50,

    /*
    | Memory Critical Threshold (MB)
    */
    'memory_critical_threshold' => 100,

    /*
    | Log Memory Warnings
    */
    'log_memory_warnings' => true,

    /*
    | Maximum Memory Rows
    | Switch to chunking above this threshold
    */
    'max_memory_rows' => 1000,
],
```

### Memory Metrics Tracked

1. **Memory Usage**
   - Current memory usage
   - Peak memory usage
   - Memory limit
   - Available memory

2. **Memory Growth**
   - Memory per row processed
   - Memory growth rate
   - Memory leak detection

### Memory Log Format

```
[2026-04-04 10:40:10] memory.WARNING: High Memory Usage Detected
{
    "current_memory": 75.5,
    "peak_memory": 82.3,
    "memory_limit": 128,
    "threshold": 50,
    "rows_processed": 5000,
    "memory_per_row": 0.015,
    "table": "users",
    "timestamp": "2026-04-04T10:40:10+00:00"
}
```

### Monitoring Memory Usage

```bash
# View memory warnings
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep memory

# Find tables with high memory usage
grep "memory\." storage/logs/*.log | jq -r '.table' | sort | uniq -c | sort -rn

# Calculate average memory per row
grep "memory_per_row" storage/logs/*.log | jq '.memory_per_row' | \
  awk '{sum+=$1; count++} END {print sum/count}'
```

---

## Alert Configuration

### Setting Up Alerts

Alerts can be configured to notify administrators when thresholds are exceeded.

**File:** `config/canvastack.datatables.php`

```php
'alerts' => [
    /*
    | Enable Alerts
    */
    'enabled' => true,

    /*
    | Alert Channels
    | Available: mail, slack, database, custom
    */
    'channels' => ['mail', 'slack'],

    /*
    | Alert Recipients
    */
    'recipients' => [
        'mail' => ['admin@example.com', 'devops@example.com'],
        'slack' => ['#alerts', '#devops'],
    ],

    /*
    | Alert Thresholds
    */
    'thresholds' => [
        'security_events' => [
            'enabled' => true,
            'threshold' => 10,      // events per hour
            'severity' => 'high',
        ],
        'slow_queries' => [
            'enabled' => true,
            'threshold' => 5,       // queries per hour
            'severity' => 'medium',
        ],
        'error_rate' => [
            'enabled' => true,
            'threshold' => 5,       // percentage
            'severity' => 'high',
        ],
        'cache_hit_rate' => [
            'enabled' => true,
            'threshold' => 70,      // percentage
            'severity' => 'low',
        ],
        'memory_usage' => [
            'enabled' => true,
            'threshold' => 80,      // percentage of limit
            'severity' => 'high',
        ],
    ],

    /*
    | Alert Cooldown
    | Minimum time between alerts (minutes)
    */
    'cooldown' => 60,

    /*
    | Alert Aggregation
    | Group similar alerts within time window
    */
    'aggregation' => [
        'enabled' => true,
        'window' => 15,         // minutes
    ],
],
```

### Creating Custom Alert Handlers

```php
namespace App\Alerts;

use Canvastack\Canvastack\Library\Components\Table\Alerts\AlertHandler;

class CustomAlertHandler extends AlertHandler
{
    public function handle(array $alert): void
    {
        // Custom alert logic
        $this->sendToMonitoringService($alert);
        $this->logToDatabase($alert);
        $this->notifyTeam($alert);
    }

    private function sendToMonitoringService(array $alert): void
    {
        // Send to external monitoring service
        // e.g., Datadog, New Relic, Sentry
    }
}
```

### Alert Email Template

```
Subject: [ALERT] Table Component - {severity} - {type}

Alert Type: {type}
Severity: {severity}
Timestamp: {timestamp}

Details:
{details}

Metrics:
{metrics}

Recommended Actions:
{recommendations}

View Logs: {log_url}
```

---

## Log Channels

### Configuring Log Channels

**File:** `config/logging.php`

```php
'channels' => [
    // Security events
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'warning',
        'days' => 90,
    ],

    // Performance metrics
    'performance' => [
        'driver' => 'daily',
        'path' => storage_path('logs/performance.log'),
        'level' => 'info',
        'days' => 30,
    ],

    // Slow queries
    'slow_query' => [
        'driver' => 'daily',
        'path' => storage_path('logs/slow_query.log'),
        'level' => 'warning',
        'days' => 60,
    ],

    // Cache operations
    'cache' => [
        'driver' => 'daily',
        'path' => storage_path('logs/cache.log'),
        'level' => 'info',
        'days' => 14,
    ],

    // Memory warnings
    'memory' => [
        'driver' => 'daily',
        'path' => storage_path('logs/memory.log'),
        'level' => 'warning',
        'days' => 30,
    ],
],
```

### Log Rotation

```bash
# Configure logrotate for table component logs
sudo nano /etc/logrotate.d/canvastack-tables

# Add configuration
/path/to/storage/logs/security.log {
    daily
    rotate 90
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
}

/path/to/storage/logs/performance.log {
    daily
    rotate 30
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
}
```

---

## Monitoring Dashboard

### Laravel Telescope Integration

```php
// config/telescope.php
'watchers' => [
    Watchers\QueryWatcher::class => [
        'enabled' => true,
        'slow' => 1000, // milliseconds
    ],
    
    Watchers\CacheWatcher::class => [
        'enabled' => true,
    ],
    
    Watchers\ExceptionWatcher::class => [
        'enabled' => true,
    ],
],
```

### Custom Monitoring Dashboard

Create a custom dashboard to visualize metrics:

```php
// routes/web.php
Route::get('/admin/monitoring/tables', [MonitoringController::class, 'index'])
    ->middleware(['auth', 'admin']);

// app/Http/Controllers/MonitoringController.php
public function index()
{
    $metrics = [
        'security_events' => $this->getSecurityEventCount(),
        'slow_queries' => $this->getSlowQueryCount(),
        'error_rate' => $this->getErrorRate(),
        'cache_hit_rate' => $this->getCacheHitRate(),
        'memory_usage' => $this->getMemoryUsage(),
    ];

    return view('admin.monitoring.tables', compact('metrics'));
}
```

### Metrics API Endpoint

```php
// routes/api.php
Route::get('/api/monitoring/tables/metrics', function () {
    return response()->json([
        'security' => [
            'events_last_hour' => Cache::get('security_events_count', 0),
            'events_last_day' => Cache::get('security_events_day', 0),
        ],
        'performance' => [
            'avg_query_time' => Cache::get('avg_query_time', 0),
            'slow_queries' => Cache::get('slow_queries_count', 0),
        ],
        'cache' => [
            'hit_rate' => Cache::get('cache_hit_rate', 0),
            'total_operations' => Cache::get('cache_operations', 0),
        ],
        'errors' => [
            'error_rate' => Cache::get('error_rate', 0),
            'total_errors' => Cache::get('total_errors', 0),
        ],
    ]);
})->middleware('auth:api');
```

---

## Best Practices

### 1. Production Monitoring

```php
// Enable all monitoring in production
'monitoring' => [
    'security' => true,
    'performance' => true,
    'slow_queries' => true,
    'cache' => true,
    'memory' => true,
    'errors' => true,
],

// Set appropriate thresholds
'thresholds' => [
    'slow_query' => 1000,
    'memory_warning' => 50,
    'error_rate' => 5,
    'cache_hit_rate' => 70,
],

// Enable alerts
'alerts' => [
    'enabled' => true,
    'channels' => ['mail', 'slack'],
],
```

### 2. Development Monitoring

```php
// Detailed logging in development
'development' => [
    'log_queries' => true,
    'log_cache_operations' => true,
    'log_performance_metrics' => true,
    'detailed_errors' => true,
],

// Lower thresholds for early detection
'thresholds' => [
    'slow_query' => 500,
    'memory_warning' => 25,
],
```

### 3. Log Management

- Use separate log channels for different event types
- Configure appropriate log retention periods
- Implement log rotation to manage disk space
- Archive old logs to long-term storage
- Use structured logging (JSON) for easier parsing

### 4. Alert Management

- Set realistic thresholds based on baseline metrics
- Implement alert cooldown to prevent spam
- Aggregate similar alerts within time windows
- Route alerts to appropriate teams
- Document alert response procedures

### 5. Performance Optimization

- Monitor cache hit rates and optimize caching strategy
- Track slow queries and add indexes as needed
- Monitor memory usage and implement chunking
- Review performance metrics regularly
- Set up automated performance testing

### 6. Security Monitoring

- Review security logs daily
- Investigate all security warnings
- Track patterns in security events
- Implement automated threat detection
- Maintain audit trail for compliance

### 7. Integration with External Tools

Consider integrating with:
- **Datadog** - Comprehensive monitoring and alerting
- **New Relic** - Application performance monitoring
- **Sentry** - Error tracking and reporting
- **Grafana** - Metrics visualization
- **ELK Stack** - Log aggregation and analysis

---

## Troubleshooting

### Logs Not Appearing

1. Check log channel configuration
2. Verify file permissions on log directory
3. Ensure monitoring is enabled in config
4. Check Laravel log level settings

### High Log Volume

1. Adjust log levels (info → warning → error)
2. Disable verbose logging in production
3. Implement log sampling for high-frequency events
4. Use log aggregation services

### Missing Metrics

1. Verify monitoring is enabled
2. Check cache configuration
3. Ensure statistics tracking is enabled
4. Review log channel configuration

### Alert Fatigue

1. Adjust alert thresholds
2. Implement alert cooldown
3. Enable alert aggregation
4. Review and tune alert rules regularly

---

## Related Documentation

- [Configuration Guide](./CONFIGURATION.md)
- [Security Features](./features/SECURITY.md)
- [Performance Guide](./PERFORMANCE.md)
- [Cache Management](./features/CACHE_MANAGEMENT.md)
- [Troubleshooting Guide](./guides/TROUBLESHOOTING.md)

---

**Last Updated:** April 4, 2026  
**Maintained By:** CanvaStack Team

