<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configure security features for Core Controller Components including
    | XSS protection, CSRF verification, SQL injection prevention, and
    | file upload security.
    |
    */

    'security' => [
        // Enable XSS protection (escape all user-controllable output)
        'xss_protection' => env('CANVASTACK_XSS_PROTECTION', true),

        // Enable CSRF token verification for POST requests
        'csrf_protection' => env('CANVASTACK_CSRF_PROTECTION', true),

        // Enable SQL injection prevention (validate table/column names)
        'sql_injection_prevention' => env('CANVASTACK_SQL_INJECTION_PREVENTION', true),

        // Escape output by default
        'escape_output' => env('CANVASTACK_ESCAPE_OUTPUT', true),

        // Allowed file extensions for uploads
        'allowed_file_extensions' => [
            'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp',  // Images
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',  // Documents
            'txt', 'csv', 'zip', 'rar',  // Other
        ],

        // Maximum file size in bytes (10MB default)
        'max_file_size' => env('CANVASTACK_MAX_FILE_SIZE', 10485760),

        // Sanitize filenames before storage
        'sanitize_filenames' => env('CANVASTACK_SANITIZE_FILENAMES', true),

        // Validate MIME types
        'validate_mime_types' => env('CANVASTACK_VALIDATE_MIME_TYPES', true),

        // Scan uploaded files for malware (requires ClamAV or similar)
        'scan_uploads' => env('CANVASTACK_SCAN_UPLOADS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configure performance optimization features including caching,
    | eager loading, query optimization, and memory management.
    |
    */

    'performance' => [
        // Enable caching for expensive operations
        'enable_caching' => env('CANVASTACK_ENABLE_CACHING', true),

        // Default cache TTL in seconds (1 hour)
        'cache_ttl' => env('CANVASTACK_CACHE_TTL', 3600),

        // Enable eager loading to prevent N+1 queries
        'eager_loading' => env('CANVASTACK_EAGER_LOADING', true),

        // Enable query optimization
        'query_optimization' => env('CANVASTACK_QUERY_OPTIMIZATION', true),

        // Memory limit for controller operations
        'memory_limit' => env('CANVASTACK_MEMORY_LIMIT', '256M'),

        // Enable performance monitoring
        'performance_monitoring' => env('CANVASTACK_PERFORMANCE_MONITORING', false),

        // Log slow queries (threshold in milliseconds)
        'slow_query_threshold' => env('CANVASTACK_SLOW_QUERY_THRESHOLD', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for specific controller components including
    | privileges, route info, preferences, and file validations.
    |
    */

    'caching' => [
        // Cache privilege checks
        'privilege_cache_enabled' => env('CANVASTACK_PRIVILEGE_CACHE_ENABLED', true),
        'privilege_cache_ttl' => env('CANVASTACK_PRIVILEGE_CACHE_TTL', 3600),

        // Cache route information
        'route_info_cache_enabled' => env('CANVASTACK_ROUTE_INFO_CACHE_ENABLED', true),
        'route_info_cache_ttl' => env('CANVASTACK_ROUTE_INFO_CACHE_TTL', 3600),

        // Cache user preferences
        'preference_cache_enabled' => env('CANVASTACK_PREFERENCE_CACHE_ENABLED', true),
        'preference_cache_ttl' => env('CANVASTACK_PREFERENCE_CACHE_TTL', 7200),

        // Cache file validation results
        'file_validation_cache_enabled' => env('CANVASTACK_FILE_VALIDATION_CACHE_ENABLED', true),
        'file_validation_cache_ttl' => env('CANVASTACK_FILE_VALIDATION_CACHE_TTL', 1800),

        // Cache query results
        'query_cache_enabled' => env('CANVASTACK_QUERY_CACHE_ENABLED', true),
        'query_cache_ttl' => env('CANVASTACK_QUERY_CACHE_TTL', 600),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Configure file upload handling including chunking, thumbnails,
    | storage paths, and processing options.
    |
    */

    'file_upload' => [
        // Enable chunked uploads for large files
        'enable_chunking' => env('CANVASTACK_ENABLE_CHUNKING', true),

        // Chunk size in bytes (1MB default)
        'chunk_size' => env('CANVASTACK_CHUNK_SIZE', 1048576),

        // Enable automatic thumbnail generation for images
        'enable_thumbnails' => env('CANVASTACK_ENABLE_THUMBNAILS', true),

        // Thumbnail dimensions
        'thumbnail_width' => env('CANVASTACK_THUMBNAIL_WIDTH', 150),
        'thumbnail_height' => env('CANVASTACK_THUMBNAIL_HEIGHT', 150),

        // Storage path relative to storage/app
        'storage_path' => env('CANVASTACK_STORAGE_PATH', 'uploads'),

        // Generate unique filenames
        'unique_filenames' => env('CANVASTACK_UNIQUE_FILENAMES', true),

        // Preserve original filename in metadata
        'preserve_original_name' => env('CANVASTACK_PRESERVE_ORIGINAL_NAME', true),

        // Enable upload progress tracking
        'track_progress' => env('CANVASTACK_TRACK_PROGRESS', false),

        // Maximum concurrent uploads per user
        'max_concurrent_uploads' => env('CANVASTACK_MAX_CONCURRENT_UPLOADS', 3),

        // Upload timeout in seconds
        'upload_timeout' => env('CANVASTACK_UPLOAD_TIMEOUT', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Configuration
    |--------------------------------------------------------------------------
    |
    | Configure validation rules and strictness for input validation,
    | database operations, and security checks.
    |
    */

    'validation' => [
        // Enable strict validation mode
        'strict_mode' => env('CANVASTACK_STRICT_MODE', true),

        // Validate table names against whitelist
        'validate_table_names' => env('CANVASTACK_VALIDATE_TABLE_NAMES', true),

        // Validate column names against schema
        'validate_column_names' => env('CANVASTACK_VALIDATE_COLUMN_NAMES', true),

        // Maximum query length to prevent DoS
        'max_query_length' => env('CANVASTACK_MAX_QUERY_LENGTH', 10000),

        // Maximum filter depth for nested filters
        'max_filter_depth' => env('CANVASTACK_MAX_FILTER_DEPTH', 5),

        // Maximum array size for input validation
        'max_array_size' => env('CANVASTACK_MAX_ARRAY_SIZE', 1000),

        // Validate session data integrity
        'validate_session_integrity' => env('CANVASTACK_VALIDATE_SESSION_INTEGRITY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging for security events, performance issues,
    | validation failures, and other controller operations.
    |
    */

    'logging' => [
        // Log security-related events
        'log_security_events' => env('CANVASTACK_LOG_SECURITY_EVENTS', true),

        // Log performance issues
        'log_performance_issues' => env('CANVASTACK_LOG_PERFORMANCE_ISSUES', true),

        // Log validation failures
        'log_validation_failures' => env('CANVASTACK_LOG_VALIDATION_FAILURES', true),

        // Log file upload operations
        'log_file_uploads' => env('CANVASTACK_LOG_FILE_UPLOADS', true),

        // Log privilege violations
        'log_privilege_violations' => env('CANVASTACK_LOG_PRIVILEGE_VIOLATIONS', true),

        // Log CSRF token failures
        'log_csrf_failures' => env('CANVASTACK_LOG_CSRF_FAILURES', true),

        // Log SQL injection attempts
        'log_sql_injection_attempts' => env('CANVASTACK_LOG_SQL_INJECTION_ATTEMPTS', true),

        // Log channel for controller events
        'log_channel' => env('CANVASTACK_LOG_CHANNEL', 'stack'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    |
    | Configure session management including timeout, regeneration,
    | and data integrity checks.
    |
    */

    'session' => [
        // Session timeout in minutes
        'timeout' => env('CANVASTACK_SESSION_TIMEOUT', 120),

        // Regenerate session ID after authentication
        'regenerate_on_auth' => env('CANVASTACK_REGENERATE_ON_AUTH', true),

        // Encrypt sensitive session data
        'encrypt_sensitive_data' => env('CANVASTACK_ENCRYPT_SENSITIVE_DATA', true),

        // Session data versioning
        'enable_versioning' => env('CANVASTACK_SESSION_VERSIONING', false),

        // Session integrity check
        'integrity_check' => env('CANVASTACK_SESSION_INTEGRITY_CHECK', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | DataTables Configuration
    |--------------------------------------------------------------------------
    |
    | Configure DataTables server-side processing including pagination,
    | filtering, and request validation.
    |
    */

    'datatables' => [
        // Default page length
        'default_page_length' => env('CANVASTACK_DT_PAGE_LENGTH', 10),

        // Maximum page length
        'max_page_length' => env('CANVASTACK_DT_MAX_PAGE_LENGTH', 100),

        // Enable server-side processing
        'server_side' => env('CANVASTACK_DT_SERVER_SIDE', true),

        // Validate DataTables request structure
        'validate_request' => env('CANVASTACK_DT_VALIDATE_REQUEST', true),

        // Cache DataTables results
        'cache_results' => env('CANVASTACK_DT_CACHE_RESULTS', false),

        // Cache TTL for DataTables results
        'cache_ttl' => env('CANVASTACK_DT_CACHE_TTL', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Script Management Configuration
    |--------------------------------------------------------------------------
    |
    | Configure JavaScript minification and concatenation for inline scripts
    | and external script files. These settings control how scripts are
    | processed before being sent to the browser.
    |
    */

    'script_management' => [
        // Enable script minification (removes whitespace, comments, etc.)
        'enable_minification' => env('CANVASTACK_CONTROLLER_ENABLE_MINIFICATION', false),

        // Minify inline scripts (scripts added via $this->js(..., ..., true))
        'minify_inline_scripts' => env('CANVASTACK_CONTROLLER_MINIFY_INLINE_SCRIPTS', false),

        // Minify external script files
        'minify_external_scripts' => env('CANVASTACK_CONTROLLER_MINIFY_EXTERNAL_SCRIPTS', false),

        // Enable script concatenation (combine multiple inline scripts into one)
        'enable_concatenation' => env('CANVASTACK_CONTROLLER_ENABLE_CONCATENATION', false),

        // Preserve important comments (/*! ... */) during minification
        'preserve_important_comments' => env('CANVASTACK_CONTROLLER_PRESERVE_IMPORTANT_COMMENTS', true),

        // Cache minified scripts
        'cache_minified' => env('CANVASTACK_CONTROLLER_MINIFICATION_CACHE_ENABLED', false),

        // Cache TTL for minified scripts (in seconds)
        'cache_ttl' => env('CANVASTACK_CONTROLLER_MINIFICATION_CACHE_TTL', 86400),

        // Cache manifests for script loading
        'cache_manifests' => env('CANVASTACK_CONTROLLER_CACHE_MANIFESTS', false),

        // Manifest cache TTL (in seconds)
        'manifest_cache_ttl' => env('CANVASTACK_CONTROLLER_MANIFEST_CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Script Management Configuration (Legacy)
    |--------------------------------------------------------------------------
    |
    | Legacy configuration for JavaScript and CSS asset management.
    | Use 'script_management' section above for new implementations.
    |
    */

    'scripts' => [
        // Enable script deduplication
        'deduplicate' => env('CANVASTACK_SCRIPTS_DEDUPLICATE', true),

        // Enable script minification
        'minify' => env('CANVASTACK_SCRIPTS_MINIFY', false),

        // Enable script concatenation
        'concatenate' => env('CANVASTACK_SCRIPTS_CONCATENATE', false),

        // Support async loading
        'async_loading' => env('CANVASTACK_SCRIPTS_ASYNC', false),

        // Support defer loading
        'defer_loading' => env('CANVASTACK_SCRIPTS_DEFER', false),

        // Cache script manifests
        'cache_manifests' => env('CANVASTACK_SCRIPTS_CACHE_MANIFESTS', true),

        // Manifest cache TTL
        'manifest_cache_ttl' => env('CANVASTACK_SCRIPTS_MANIFEST_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling Configuration
    |--------------------------------------------------------------------------
    |
    | Configure error handling behavior including exception reporting,
    | user-friendly messages, and graceful degradation.
    |
    */

    'error_handling' => [
        // Show detailed errors in development
        'show_detailed_errors' => env('CANVASTACK_SHOW_DETAILED_ERRORS', env('APP_DEBUG', false)),

        // Enable graceful degradation
        'graceful_degradation' => env('CANVASTACK_GRACEFUL_DEGRADATION', true),

        // Retry failed operations
        'enable_retry' => env('CANVASTACK_ENABLE_RETRY', true),

        // Maximum retry attempts
        'max_retry_attempts' => env('CANVASTACK_MAX_RETRY_ATTEMPTS', 3),

        // Fallback to database on cache failure
        'cache_fallback' => env('CANVASTACK_CACHE_FALLBACK', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configure monitoring and alerting for security incidents and
    | performance issues. Includes thresholds for various alert types.
    |
    */

    'monitoring' => [
        // Enable alert system
        'alerts_enabled' => env('CANVASTACK_ALERTS_ENABLED', true),

        // Alert cooldown period in seconds (prevent spam)
        'alert_cooldown' => env('CANVASTACK_ALERT_COOLDOWN', 300),

        // Security alert thresholds (events per time window)
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

        // Alert notification channels (email, slack, etc.)
        'alert_email' => env('CANVASTACK_ALERT_EMAIL', null),
        'alert_slack_webhook' => env('CANVASTACK_ALERT_SLACK_WEBHOOK', null),
    ],

];
