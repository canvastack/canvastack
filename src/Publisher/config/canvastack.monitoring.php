<?php

/**
 * Canvastack Table Components Monitoring Configuration
 * 
 * This configuration file provides comprehensive monitoring and logging
 * settings for the Canvastack Table Components system including security
 * event logging, performance monitoring, slow query logging, error rate
 * monitoring, and alert configuration.
 * 
 * @package Canvastack\Canvastack
 * @category Configuration
 * @version 1.0.0
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Monitoring Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch to enable/disable all monitoring features.
    | Set to false to disable all monitoring (not recommended for production).
    |
    */

    'enabled' => env('TABLE_MONITORING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Security Event Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for logging security-related events including XSS attempts,
    | SQL injection attempts, invalid input attempts, and suspicious patterns.
    |
    | Supports: Requirement 2 (SQL Injection Prevention)
    |
    */

    'security' => [

        /*
         * Enable security event logging
         */
        'enabled' => env('TABLE_SECURITY_LOGGING_ENABLED', true),

        /*
         * Log channel for security events (from config/logging.php)
         */
        'log_channel' => env('TABLE_SECURITY_LOG_CHANNEL', 'table_security'),

        /*
         * Log level for security events
         * Options: emergency, alert, critical, error, warning, notice, info, debug
         */
        'log_level' => env('TABLE_SECURITY_LOG_LEVEL', 'warning'),

        /*
         * Events to log
         */
        'events' => [
            'xss_attempt' => env('TABLE_LOG_XSS_ATTEMPTS', true),
            'sql_injection_attempt' => env('TABLE_LOG_SQL_INJECTION', true),
            'invalid_table_name' => env('TABLE_LOG_INVALID_TABLE', true),
            'invalid_column_name' => env('TABLE_LOG_INVALID_COLUMN', true),
            'invalid_operator' => env('TABLE_LOG_INVALID_OPERATOR', true),
            'suspicious_query' => env('TABLE_LOG_SUSPICIOUS_QUERY', true),
            'privilege_violation' => env('TABLE_LOG_PRIVILEGE_VIOLATION', true),
            'invalid_pagination' => env('TABLE_LOG_INVALID_PAGINATION', true),
            'invalid_sort' => env('TABLE_LOG_INVALID_SORT', true),
            'malformed_request' => env('TABLE_LOG_MALFORMED_REQUEST', true),
        ],

        /*
         * Include request context in security logs
         */
        'include_context' => [
            'ip_address' => env('TABLE_LOG_IP_ADDRESS', true),
            'user_agent' => env('TABLE_LOG_USER_AGENT', true),
            'user_id' => env('TABLE_LOG_USER_ID', true),
            'request_url' => env('TABLE_LOG_REQUEST_URL', true),
            'request_method' => env('TABLE_LOG_REQUEST_METHOD', true),
            'request_parameters' => env('TABLE_LOG_REQUEST_PARAMS', true),
            'session_id' => env('TABLE_LOG_SESSION_ID', false),
            'timestamp' => true,
        ],

        /*
         * Suspicious pattern detection
         */
        'patterns' => [
            /*
             * SQL injection patterns to detect
             */
            'sql_injection' => [
                'union\s+select',
                'drop\s+table',
                'delete\s+from',
                'insert\s+into',
                'update\s+.*\s+set',
                '--',
                ';--',
                '\/\*.*\*\/',
                'xp_cmdshell',
                'exec\s*\(',
                'execute\s*\(',
            ],

            /*
             * XSS patterns to detect
             */
            'xss' => [
                '<script',
                'javascript:',
                'onerror=',
                'onload=',
                'onclick=',
                '<iframe',
                '<object',
                '<embed',
                'eval\(',
            ],
        ],

        /*
         * Rate limiting for security logs (prevent log flooding)
         */
        'rate_limit' => [
            'enabled' => env('TABLE_SECURITY_RATE_LIMIT', true),
            'max_events_per_minute' => env('TABLE_SECURITY_MAX_EVENTS_PER_MIN', 100),
            'max_events_per_hour' => env('TABLE_SECURITY_MAX_EVENTS_PER_HOUR', 1000),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for monitoring query execution times, memory usage,
    | and other performance metrics.
    |
    | Supports: Requirement 4 (Query Optimization)
    |
    */

    'performance' => [

        /*
         * Enable performance monitoring
         */
        'enabled' => env('TABLE_PERFORMANCE_MONITORING_ENABLED', true),

        /*
         * Log channel for performance events
         */
        'log_channel' => env('TABLE_PERFORMANCE_LOG_CHANNEL', 'table_performance'),

        /*
         * Log level for performance events
         */
        'log_level' => env('TABLE_PERFORMANCE_LOG_LEVEL', 'info'),

        /*
         * Metrics to track
         */
        'metrics' => [
            'query_execution_time' => env('TABLE_TRACK_QUERY_TIME', true),
            'memory_usage' => env('TABLE_TRACK_MEMORY', true),
            'row_count' => env('TABLE_TRACK_ROW_COUNT', true),
            'cache_hit_rate' => env('TABLE_TRACK_CACHE_HITS', true),
            'database_connections' => env('TABLE_TRACK_DB_CONNECTIONS', true),
            'eager_loading_usage' => env('TABLE_TRACK_EAGER_LOADING', true),
        ],

        /*
         * Query execution time thresholds (in seconds)
         */
        'thresholds' => [
            'fast' => env('TABLE_THRESHOLD_FAST', 0.1),      // < 100ms
            'normal' => env('TABLE_THRESHOLD_NORMAL', 0.5),  // 100ms - 500ms
            'slow' => env('TABLE_THRESHOLD_SLOW', 1.0),      // 500ms - 1s
            'very_slow' => env('TABLE_THRESHOLD_VERY_SLOW', 3.0), // > 1s
        ],

        /*
         * Memory usage thresholds (in MB)
         */
        'memory_thresholds' => [
            'normal' => env('TABLE_MEMORY_NORMAL', 32),      // < 32MB
            'high' => env('TABLE_MEMORY_HIGH', 64),          // 32MB - 64MB
            'critical' => env('TABLE_MEMORY_CRITICAL', 128), // > 64MB
        ],

        /*
         * Log performance metrics for all queries
         */
        'log_all_queries' => env('TABLE_LOG_ALL_QUERIES', false),

        /*
         * Log only slow queries
         */
        'log_slow_queries_only' => env('TABLE_LOG_SLOW_ONLY', true),

        /*
         * Include query details in performance logs
         */
        'include_query_details' => [
            'sql' => env('TABLE_LOG_SQL', true),
            'bindings' => env('TABLE_LOG_BINDINGS', false), // Security: may contain sensitive data
            'table_name' => env('TABLE_LOG_TABLE_NAME', true),
            'columns' => env('TABLE_LOG_COLUMNS', true),
            'filters' => env('TABLE_LOG_FILTERS', true),
            'sorting' => env('TABLE_LOG_SORTING', true),
            'pagination' => env('TABLE_LOG_PAGINATION', true),
        ],

        /*
         * Performance sampling rate (1.0 = 100%, 0.1 = 10%)
         * Reduce to lower overhead in high-traffic environments
         */
        'sampling_rate' => env('TABLE_PERFORMANCE_SAMPLING_RATE', 1.0),

    ],

    /*
    |--------------------------------------------------------------------------
    | Slow Query Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for identifying and logging slow database queries
    | to help identify optimization opportunities.
    |
    | Supports: Requirement 4 (Query Optimization)
    |
    */

    'slow_queries' => [

        /*
         * Enable slow query logging
         */
        'enabled' => env('TABLE_SLOW_QUERY_LOGGING_ENABLED', true),

        /*
         * Log channel for slow queries
         */
        'log_channel' => env('TABLE_SLOW_QUERY_LOG_CHANNEL', 'table_performance'),

        /*
         * Slow query threshold in seconds
         * Queries taking longer than this will be logged
         */
        'threshold' => env('TABLE_SLOW_QUERY_THRESHOLD', 1.0),

        /*
         * Log level for slow queries
         */
        'log_level' => env('TABLE_SLOW_QUERY_LOG_LEVEL', 'warning'),

        /*
         * Include in slow query logs
         */
        'include' => [
            'execution_time' => true,
            'sql_query' => env('TABLE_SLOW_QUERY_LOG_SQL', true),
            'bindings' => env('TABLE_SLOW_QUERY_LOG_BINDINGS', false),
            'table_name' => true,
            'row_count' => true,
            'memory_usage' => true,
            'stack_trace' => env('TABLE_SLOW_QUERY_STACK_TRACE', false),
            'optimization_hints' => env('TABLE_SLOW_QUERY_HINTS', true),
        ],

        /*
         * Automatic optimization suggestions
         */
        'optimization_hints' => [
            'enabled' => env('TABLE_OPTIMIZATION_HINTS_ENABLED', true),
            'suggest_indexes' => env('TABLE_SUGGEST_INDEXES', true),
            'suggest_eager_loading' => env('TABLE_SUGGEST_EAGER_LOADING', true),
            'suggest_caching' => env('TABLE_SUGGEST_CACHING', true),
        ],

        /*
         * Track slow query patterns
         */
        'track_patterns' => env('TABLE_TRACK_SLOW_PATTERNS', true),

        /*
         * Alert on repeated slow queries
         */
        'alert_on_repeated' => [
            'enabled' => env('TABLE_ALERT_REPEATED_SLOW', true),
            'threshold' => env('TABLE_REPEATED_SLOW_THRESHOLD', 10), // Alert after 10 occurrences
            'time_window' => env('TABLE_REPEATED_SLOW_WINDOW', 3600), // Within 1 hour
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Error Rate Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for monitoring error rates and detecting issues early.
    |
    | Supports: Requirement 23 (Error Handling)
    |
    */

    'error_monitoring' => [

        /*
         * Enable error rate monitoring
         */
        'enabled' => env('TABLE_ERROR_MONITORING_ENABLED', true),

        /*
         * Log channel for errors
         */
        'log_channel' => env('TABLE_ERROR_LOG_CHANNEL', 'table_errors'),

        /*
         * Log level for errors
         */
        'log_level' => env('TABLE_ERROR_LOG_LEVEL', 'error'),

        /*
         * Error types to monitor
         */
        'error_types' => [
            'validation_errors' => env('TABLE_MONITOR_VALIDATION_ERRORS', true),
            'database_errors' => env('TABLE_MONITOR_DATABASE_ERRORS', true),
            'query_errors' => env('TABLE_MONITOR_QUERY_ERRORS', true),
            'security_errors' => env('TABLE_MONITOR_SECURITY_ERRORS', true),
            'performance_errors' => env('TABLE_MONITOR_PERFORMANCE_ERRORS', true),
            'export_errors' => env('TABLE_MONITOR_EXPORT_ERRORS', true),
            'formula_errors' => env('TABLE_MONITOR_FORMULA_ERRORS', true),
            'relationship_errors' => env('TABLE_MONITOR_RELATIONSHIP_ERRORS', true),
        ],

        /*
         * Include in error logs
         */
        'include' => [
            'error_message' => true,
            'error_code' => true,
            'exception_class' => true,
            'stack_trace' => env('TABLE_ERROR_STACK_TRACE', true),
            'request_context' => env('TABLE_ERROR_REQUEST_CONTEXT', true),
            'user_context' => env('TABLE_ERROR_USER_CONTEXT', true),
            'table_context' => env('TABLE_ERROR_TABLE_CONTEXT', true),
        ],

        /*
         * Error rate thresholds (errors per minute)
         */
        'rate_thresholds' => [
            'normal' => env('TABLE_ERROR_RATE_NORMAL', 1),      // < 1 error/min
            'elevated' => env('TABLE_ERROR_RATE_ELEVATED', 5),  // 1-5 errors/min
            'high' => env('TABLE_ERROR_RATE_HIGH', 10),         // 5-10 errors/min
            'critical' => env('TABLE_ERROR_RATE_CRITICAL', 20), // > 10 errors/min
        ],

        /*
         * Track error patterns
         */
        'track_patterns' => env('TABLE_TRACK_ERROR_PATTERNS', true),

        /*
         * Group similar errors
         */
        'group_similar_errors' => env('TABLE_GROUP_SIMILAR_ERRORS', true),

        /*
         * Time window for error rate calculation (in seconds)
         */
        'rate_window' => env('TABLE_ERROR_RATE_WINDOW', 60),

    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Configuration - Security Incidents
    |--------------------------------------------------------------------------
    |
    | Configuration for alerts on security-related incidents.
    |
    | Supports: Requirement 2 (SQL Injection Prevention)
    |
    */

    'alerts' => [

        'security' => [

            /*
             * Enable security alerts
             */
            'enabled' => env('TABLE_SECURITY_ALERTS_ENABLED', true),

            /*
             * Alert channels (email, slack, log, database)
             */
            'channels' => explode(',', env('TABLE_SECURITY_ALERT_CHANNELS', 'log')),

            /*
             * Alert on specific security events
             */
            'events' => [
                'sql_injection_attempt' => [
                    'enabled' => env('TABLE_ALERT_SQL_INJECTION', true),
                    'severity' => 'critical',
                    'threshold' => 1, // Alert immediately
                ],
                'xss_attempt' => [
                    'enabled' => env('TABLE_ALERT_XSS', true),
                    'severity' => 'high',
                    'threshold' => 1,
                ],
                'privilege_violation' => [
                    'enabled' => env('TABLE_ALERT_PRIVILEGE_VIOLATION', true),
                    'severity' => 'high',
                    'threshold' => 3, // Alert after 3 attempts
                ],
                'suspicious_pattern' => [
                    'enabled' => env('TABLE_ALERT_SUSPICIOUS_PATTERN', true),
                    'severity' => 'medium',
                    'threshold' => 5,
                ],
                'repeated_invalid_input' => [
                    'enabled' => env('TABLE_ALERT_REPEATED_INVALID', true),
                    'severity' => 'medium',
                    'threshold' => 10, // Alert after 10 invalid attempts
                    'time_window' => 300, // Within 5 minutes
                ],
            ],

            /*
             * Email alert configuration
             */
            'email' => [
                'enabled' => env('TABLE_SECURITY_EMAIL_ALERTS', false),
                'to' => explode(',', env('TABLE_SECURITY_ALERT_EMAIL', '')),
                'from' => env('MAIL_FROM_ADDRESS', 'security@example.com'),
                'subject_prefix' => env('TABLE_SECURITY_EMAIL_PREFIX', '[SECURITY ALERT]'),
            ],

            /*
             * Slack alert configuration
             */
            'slack' => [
                'enabled' => env('TABLE_SECURITY_SLACK_ALERTS', false),
                'webhook_url' => env('TABLE_SECURITY_SLACK_WEBHOOK', ''),
                'channel' => env('TABLE_SECURITY_SLACK_CHANNEL', '#security-alerts'),
                'username' => env('TABLE_SECURITY_SLACK_USERNAME', 'Table Security Bot'),
                'icon_emoji' => env('TABLE_SECURITY_SLACK_EMOJI', ':warning:'),
            ],

            /*
             * Alert rate limiting (prevent alert flooding)
             */
            'rate_limit' => [
                'enabled' => env('TABLE_SECURITY_ALERT_RATE_LIMIT', true),
                'max_alerts_per_hour' => env('TABLE_SECURITY_MAX_ALERTS_PER_HOUR', 50),
                'cooldown_period' => env('TABLE_SECURITY_ALERT_COOLDOWN', 300), // 5 minutes
            ],

        ],

        /*
         * Performance issue alerts
         */
        'performance' => [

            /*
             * Enable performance alerts
             */
            'enabled' => env('TABLE_PERFORMANCE_ALERTS_ENABLED', true),

            /*
             * Alert channels
             */
            'channels' => explode(',', env('TABLE_PERFORMANCE_ALERT_CHANNELS', 'log')),

            /*
             * Alert on specific performance issues
             */
            'events' => [
                'slow_query' => [
                    'enabled' => env('TABLE_ALERT_SLOW_QUERY', true),
                    'severity' => 'medium',
                    'threshold' => 3.0, // Alert on queries > 3 seconds
                ],
                'very_slow_query' => [
                    'enabled' => env('TABLE_ALERT_VERY_SLOW_QUERY', true),
                    'severity' => 'high',
                    'threshold' => 10.0, // Alert on queries > 10 seconds
                ],
                'high_memory_usage' => [
                    'enabled' => env('TABLE_ALERT_HIGH_MEMORY', true),
                    'severity' => 'high',
                    'threshold' => 128, // Alert when memory > 128MB
                ],
                'n_plus_one_detected' => [
                    'enabled' => env('TABLE_ALERT_N_PLUS_ONE', true),
                    'severity' => 'medium',
                    'threshold' => 1,
                ],
                'cache_miss_rate' => [
                    'enabled' => env('TABLE_ALERT_CACHE_MISS', true),
                    'severity' => 'low',
                    'threshold' => 0.8, // Alert when cache miss rate > 80%
                ],
            ],

            /*
             * Email alert configuration
             */
            'email' => [
                'enabled' => env('TABLE_PERFORMANCE_EMAIL_ALERTS', false),
                'to' => explode(',', env('TABLE_PERFORMANCE_ALERT_EMAIL', '')),
                'from' => env('MAIL_FROM_ADDRESS', 'performance@example.com'),
                'subject_prefix' => env('TABLE_PERFORMANCE_EMAIL_PREFIX', '[PERFORMANCE ALERT]'),
            ],

            /*
             * Slack alert configuration
             */
            'slack' => [
                'enabled' => env('TABLE_PERFORMANCE_SLACK_ALERTS', false),
                'webhook_url' => env('TABLE_PERFORMANCE_SLACK_WEBHOOK', ''),
                'channel' => env('TABLE_PERFORMANCE_SLACK_CHANNEL', '#performance-alerts'),
                'username' => env('TABLE_PERFORMANCE_SLACK_USERNAME', 'Table Performance Bot'),
                'icon_emoji' => env('TABLE_PERFORMANCE_SLACK_EMOJI', ':chart_with_downwards_trend:'),
            ],

            /*
             * Alert rate limiting
             */
            'rate_limit' => [
                'enabled' => env('TABLE_PERFORMANCE_ALERT_RATE_LIMIT', true),
                'max_alerts_per_hour' => env('TABLE_PERFORMANCE_MAX_ALERTS_PER_HOUR', 20),
                'cooldown_period' => env('TABLE_PERFORMANCE_ALERT_COOLDOWN', 600), // 10 minutes
            ],

        ],

        /*
         * Error rate alerts
         */
        'errors' => [

            /*
             * Enable error rate alerts
             */
            'enabled' => env('TABLE_ERROR_ALERTS_ENABLED', true),

            /*
             * Alert channels
             */
            'channels' => explode(',', env('TABLE_ERROR_ALERT_CHANNELS', 'log')),

            /*
             * Alert on error rate thresholds
             */
            'thresholds' => [
                'elevated' => [
                    'enabled' => env('TABLE_ALERT_ELEVATED_ERRORS', true),
                    'severity' => 'medium',
                    'rate' => 5, // 5 errors per minute
                ],
                'high' => [
                    'enabled' => env('TABLE_ALERT_HIGH_ERRORS', true),
                    'severity' => 'high',
                    'rate' => 10, // 10 errors per minute
                ],
                'critical' => [
                    'enabled' => env('TABLE_ALERT_CRITICAL_ERRORS', true),
                    'severity' => 'critical',
                    'rate' => 20, // 20 errors per minute
                ],
            ],

            /*
             * Email alert configuration
             */
            'email' => [
                'enabled' => env('TABLE_ERROR_EMAIL_ALERTS', false),
                'to' => explode(',', env('TABLE_ERROR_ALERT_EMAIL', '')),
                'from' => env('MAIL_FROM_ADDRESS', 'errors@example.com'),
                'subject_prefix' => env('TABLE_ERROR_EMAIL_PREFIX', '[ERROR ALERT]'),
            ],

            /*
             * Slack alert configuration
             */
            'slack' => [
                'enabled' => env('TABLE_ERROR_SLACK_ALERTS', false),
                'webhook_url' => env('TABLE_ERROR_SLACK_WEBHOOK', ''),
                'channel' => env('TABLE_ERROR_SLACK_CHANNEL', '#error-alerts'),
                'username' => env('TABLE_ERROR_SLACK_USERNAME', 'Table Error Bot'),
                'icon_emoji' => env('TABLE_ERROR_SLACK_EMOJI', ':x:'),
            ],

            /*
             * Alert rate limiting
             */
            'rate_limit' => [
                'enabled' => env('TABLE_ERROR_ALERT_RATE_LIMIT', true),
                'max_alerts_per_hour' => env('TABLE_ERROR_MAX_ALERTS_PER_HOUR', 30),
                'cooldown_period' => env('TABLE_ERROR_ALERT_COOLDOWN', 300), // 5 minutes
            ],

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics Storage
    |--------------------------------------------------------------------------
    |
    | Configuration for storing monitoring metrics for analysis and reporting.
    |
    */

    'metrics_storage' => [

        /*
         * Enable metrics storage
         */
        'enabled' => env('TABLE_METRICS_STORAGE_ENABLED', true),

        /*
         * Storage driver (database, redis, file)
         */
        'driver' => env('TABLE_METRICS_DRIVER', 'database'),

        /*
         * Database configuration
         */
        'database' => [
            'connection' => env('TABLE_METRICS_DB_CONNECTION', 'mysql'),
            'table' => env('TABLE_METRICS_TABLE', 'table_metrics'),
        ],

        /*
         * Redis configuration
         */
        'redis' => [
            'connection' => env('TABLE_METRICS_REDIS_CONNECTION', 'default'),
            'key_prefix' => env('TABLE_METRICS_REDIS_PREFIX', 'table_metrics:'),
        ],

        /*
         * File configuration
         */
        'file' => [
            'path' => storage_path('metrics/table'),
        ],

        /*
         * Metrics retention period (in days)
         */
        'retention_days' => env('TABLE_METRICS_RETENTION_DAYS', 30),

        /*
         * Aggregate metrics for reporting
         */
        'aggregation' => [
            'enabled' => env('TABLE_METRICS_AGGREGATION', true),
            'intervals' => ['hourly', 'daily', 'weekly'],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for monitoring dashboard (if implemented).
    |
    */

    'dashboard' => [

        /*
         * Enable monitoring dashboard
         */
        'enabled' => env('TABLE_DASHBOARD_ENABLED', false),

        /*
         * Dashboard route
         */
        'route' => env('TABLE_DASHBOARD_ROUTE', 'admin/table-monitoring'),

        /*
         * Dashboard middleware
         */
        'middleware' => ['web', 'auth'],

        /*
         * Refresh interval in seconds
         */
        'refresh_interval' => env('TABLE_DASHBOARD_REFRESH', 30),

    ],

];
