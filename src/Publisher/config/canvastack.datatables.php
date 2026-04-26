<?php

/**
 * Canvastack DataTables Configuration
 * 
 * This configuration file provides comprehensive settings for the Canvastack
 * Table Components system including DataTables options, security settings,
 * performance tuning, and accessibility features.
 * 
 * @package Canvastack\Canvastack
 * @category Configuration
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Table Name Whitelist
    |--------------------------------------------------------------------------
    |
    | List of allowed table names for security. Only tables in this whitelist
    | can be used with the Table Components system. This prevents SQL injection
    | attacks through table name manipulation.
    |
    | Set to null to allow all tables (not recommended for production).
    | Set to an array of table names to restrict access.
    |
    | Security: Requirement 2.2 - Table Name Validation
    |
    */

    'allowed_tables' => env('DATATABLES_ALLOWED_TABLES', null),

    /*
    |--------------------------------------------------------------------------
    | Default DataTables Options
    |--------------------------------------------------------------------------
    |
    | Default configuration options for DataTables initialization.
    | These values can be overridden per table instance.
    |
    */

    'defaults' => [
        
        /*
         * Default HTTP method for DataTables AJAX requests
         * Supported: 'GET', 'POST'
         */
        'method' => env('DATATABLES_METHOD', 'POST'),

        /*
         * Default page length (number of rows per page)
         * Range: 1-100 rows
         */
        'page_length' => env('DATATABLES_PAGE_LENGTH', 10),

        /*
         * Maximum allowed page length
         * Prevents memory issues with large datasets
         */
        'max_page_length' => env('DATATABLES_MAX_PAGE_LENGTH', 100),

        /*
         * Enable server-side processing by default
         */
        'server_side' => env('DATATABLES_SERVER_SIDE', true),

        /*
         * Enable processing indicator
         */
        'processing' => env('DATATABLES_PROCESSING', true),

        /*
         * Enable searching
         */
        'searching' => env('DATATABLES_SEARCHING', true),

        /*
         * Enable ordering (sorting)
         */
        'ordering' => env('DATATABLES_ORDERING', true),

        /*
         * Enable pagination
         */
        'paging' => env('DATATABLES_PAGING', true),

        /*
         * Enable info display (showing X to Y of Z entries)
         */
        'info' => env('DATATABLES_INFO', true),

        /*
         * Enable length change dropdown
         */
        'length_change' => env('DATATABLES_LENGTH_CHANGE', true),

        /*
         * Available page length options
         */
        'length_menu' => [10, 25, 50, 100],

        /*
         * Default ordering column and direction
         * Format: [[column_index, 'asc'|'desc']]
         */
        'order' => [[0, 'asc']],

        /*
         * Enable responsive design
         */
        'responsive' => env('DATATABLES_RESPONSIVE', true),

        /*
         * Enable auto width calculation
         */
        'auto_width' => env('DATATABLES_AUTO_WIDTH', false),

        /*
         * Enable state saving (remember pagination, sorting, etc.)
         */
        'state_save' => env('DATATABLES_STATE_SAVE', false),

        /*
         * State save duration in seconds (default: 2 hours)
         */
        'state_duration' => env('DATATABLES_STATE_DURATION', 7200),

    ],

    /*
    |--------------------------------------------------------------------------
    | Security Options
    |--------------------------------------------------------------------------
    |
    | Security-related configuration options to protect against XSS,
    | SQL injection, and other vulnerabilities.
    |
    */

    'security' => [

        /*
         * Enable XSS protection (escape HTML in output)
         * Requirement 1: XSS Protection
         */
        'xss_protection' => env('DATATABLES_XSS_PROTECTION', true),

        /*
         * Enable SQL injection prevention
         * Requirement 2: SQL Injection Prevention
         */
        'sql_injection_prevention' => env('DATATABLES_SQL_INJECTION_PREVENTION', true),

        /*
         * Enable input validation
         * Requirement 3: Input Validation
         */
        'input_validation' => env('DATATABLES_INPUT_VALIDATION', true),

        /*
         * Log security events (XSS attempts, SQL injection attempts, etc.)
         */
        'log_security_events' => env('DATATABLES_LOG_SECURITY', true),

        /*
         * Security log channel (from config/logging.php)
         */
        'security_log_channel' => env('DATATABLES_SECURITY_LOG_CHANNEL', 'daily'),

        /*
         * Allowed SQL operators for filtering
         * Whitelist to prevent SQL injection
         */
        'allowed_operators' => [
            '=', '!=', '<>', '>', '<', '>=', '<=',
            'LIKE', 'NOT LIKE', 'ILIKE', 'NOT ILIKE',
            'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN',
            'IS NULL', 'IS NOT NULL',
        ],

        /*
         * Allowed sort directions
         */
        'allowed_sort_directions' => ['asc', 'desc', 'ASC', 'DESC'],

        /*
         * Enable SafeHtml marker system
         * Prevents double-encoding of already-safe HTML
         */
        'use_safehtml_marker' => env('DATATABLES_USE_SAFEHTML', true),

        /*
         * Validate column names against schema
         * Prevents SQL injection through column name manipulation
         */
        'validate_column_names' => env('DATATABLES_VALIDATE_COLUMNS', true),

        /*
         * Maximum search term length (prevents DoS attacks)
         */
        'max_search_length' => env('DATATABLES_MAX_SEARCH_LENGTH', 255),

    ],


    /*
    |--------------------------------------------------------------------------
    | Performance Options
    |--------------------------------------------------------------------------
    |
    | Performance tuning options for query optimization, caching,
    | and memory management.
    |
    */

    'performance' => [

        /*
         * Enable query optimization
         * Requirement 4: Query Optimization
         */
        'query_optimization' => env('DATATABLES_QUERY_OPTIMIZATION', true),

        /*
         * Enable eager loading for relationships
         * Prevents N+1 query problems
         */
        'eager_loading' => env('DATATABLES_EAGER_LOADING', true),

        /*
         * Select only required columns (not SELECT *)
         */
        'select_required_only' => env('DATATABLES_SELECT_REQUIRED_ONLY', true),

        /*
         * Enable database-level sorting
         */
        'database_sorting' => env('DATATABLES_DATABASE_SORTING', true),

        /*
         * Enable database-level filtering
         */
        'database_filtering' => env('DATATABLES_DATABASE_FILTERING', true),

        /*
         * Query timeout in seconds
         * Prevents long-running queries from blocking
         */
        'query_timeout' => env('DATATABLES_QUERY_TIMEOUT', 30),

        /*
         * Log slow queries (queries exceeding threshold)
         */
        'log_slow_queries' => env('DATATABLES_LOG_SLOW_QUERIES', true),

        /*
         * Slow query threshold in seconds
         */
        'slow_query_threshold' => env('DATATABLES_SLOW_QUERY_THRESHOLD', 1.0),

        /*
         * Slow query log channel
         */
        'slow_query_log_channel' => env('DATATABLES_SLOW_QUERY_LOG_CHANNEL', 'daily'),

        /*
         * Enable query result caching
         * Cache results for identical queries
         */
        'query_caching' => env('DATATABLES_QUERY_CACHING', false),

        /*
         * Query cache TTL in seconds
         */
        'query_cache_ttl' => env('DATATABLES_QUERY_CACHE_TTL', 300),

        /*
         * Maximum rows to process in memory
         * Larger datasets will use chunking
         */
        'max_memory_rows' => env('DATATABLES_MAX_MEMORY_ROWS', 1000),

        /*
         * Chunk size for large dataset processing
         */
        'chunk_size' => env('DATATABLES_CHUNK_SIZE', 1000),

        /*
         * Memory limit warning threshold (in MB)
         */
        'memory_warning_threshold' => env('DATATABLES_MEMORY_WARNING_MB', 128),

        /*
         * Enable memory usage monitoring
         */
        'monitor_memory' => env('DATATABLES_MONITOR_MEMORY', true),

    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Options
    |--------------------------------------------------------------------------
    |
    | Configuration for caching table schemas, validation results,
    | and other expensive operations.
    |
    | Requirement 5: Caching Strategy
    |
    */

    'cache' => [

        /*
         * Enable caching system
         */
        'enabled' => env('DATATABLES_CACHE_ENABLED', true),

        /*
         * Cache store to use (from config/cache.php)
         */
        'store' => env('DATATABLES_CACHE_STORE', 'file'),

        /*
         * Cache key prefix
         */
        'prefix' => env('DATATABLES_CACHE_PREFIX', 'canvastack_table_'),

        /*
         * Schema cache configuration
         */
        'schema' => [
            'enabled' => env('DATATABLES_CACHE_SCHEMA', true),
            'ttl' => env('DATATABLES_CACHE_SCHEMA_TTL', 3600), // 1 hour
            'key_prefix' => 'schema_',
        ],

        /*
         * Validation cache configuration
         */
        'validation' => [
            'enabled' => env('DATATABLES_CACHE_VALIDATION', true),
            'ttl' => env('DATATABLES_CACHE_VALIDATION_TTL', 3600), // 1 hour
            'key_prefix' => 'validation_',
        ],

        /*
         * Configuration cache
         */
        'config' => [
            'enabled' => env('DATATABLES_CACHE_CONFIG', true),
            'ttl' => env('DATATABLES_CACHE_CONFIG_TTL', 3600), // 1 hour
            'key_prefix' => 'config_',
        ],

        /*
         * Relationship cache
         */
        'relationships' => [
            'enabled' => env('DATATABLES_CACHE_RELATIONSHIPS', true),
            'ttl' => env('DATATABLES_CACHE_RELATIONSHIPS_TTL', 3600), // 1 hour
            'key_prefix' => 'relationships_',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Accessibility Options
    |--------------------------------------------------------------------------
    |
    | Configuration for WCAG 2.1 Level A compliance including ARIA attributes,
    | keyboard navigation, and screen reader support.
    |
    | Requirements 11, 12, 13: Accessibility
    |
    */

    'accessibility' => [

        /*
         * Enable ARIA attributes
         */
        'aria_enabled' => env('DATATABLES_ARIA_ENABLED', true),

        /*
         * Enable keyboard navigation
         */
        'keyboard_navigation' => env('DATATABLES_KEYBOARD_NAVIGATION', true),

        /*
         * Enable screen reader support
         */
        'screen_reader_support' => env('DATATABLES_SCREEN_READER_SUPPORT', true),

        /*
         * Add table captions for screen readers
         */
        'add_captions' => env('DATATABLES_ADD_CAPTIONS', true),

        /*
         * Add aria-label to interactive elements
         */
        'add_aria_labels' => env('DATATABLES_ADD_ARIA_LABELS', true),

        /*
         * Add aria-sort to sortable columns
         */
        'add_aria_sort' => env('DATATABLES_ADD_ARIA_SORT', true),

        /*
         * Add aria-busy during loading
         */
        'add_aria_busy' => env('DATATABLES_ADD_ARIA_BUSY', true),

        /*
         * Enable focus indicators
         */
        'focus_indicators' => env('DATATABLES_FOCUS_INDICATORS', true),

        /*
         * Announce loading status to screen readers
         */
        'announce_loading' => env('DATATABLES_ANNOUNCE_LOADING', true),

        /*
         * Announce filter changes to screen readers
         */
        'announce_filters' => env('DATATABLES_ANNOUNCE_FILTERS', true),

        /*
         * Announce sort changes to screen readers
         */
        'announce_sorting' => env('DATATABLES_ANNOUNCE_SORTING', true),

    ],

    /*
    |--------------------------------------------------------------------------
    | Action Button Configuration
    |--------------------------------------------------------------------------
    |
    | Default configuration for action buttons (view, edit, delete, etc.)
    |
    | Requirement 20: Action Buttons
    |
    */

    'actions' => [

        /*
         * Default action buttons to display
         */
        'default_actions' => ['view', 'edit', 'delete'],

        /*
         * Action button icons (using Font Awesome classes)
         */
        'icons' => [
            'view' => 'fa fa-eye',
            'edit' => 'fa fa-edit',
            'delete' => 'fa fa-trash',
            'insert' => 'fa fa-plus',
            'restore' => 'fa fa-undo',
            'download' => 'fa fa-download',
            'upload' => 'fa fa-upload',
        ],

        /*
         * Action button labels
         */
        'labels' => [
            'view' => 'View',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'insert' => 'Insert',
            'restore' => 'Restore',
            'download' => 'Download',
            'upload' => 'Upload',
        ],

        /*
         * Action button CSS classes
         */
        'classes' => [
            'view' => 'btn btn-sm btn-info',
            'edit' => 'btn btn-sm btn-primary',
            'delete' => 'btn btn-sm btn-danger',
            'insert' => 'btn btn-sm btn-success',
            'restore' => 'btn btn-sm btn-warning',
        ],

        /*
         * Enable confirmation dialogs for destructive actions
         */
        'confirm_destructive' => env('DATATABLES_CONFIRM_DESTRUCTIVE', true),

        /*
         * Destructive actions that require confirmation
         */
        'destructive_actions' => ['delete', 'restore'],

        /*
         * Default confirmation message
         */
        'confirm_message' => 'Are you sure you want to perform this action?',

        /*
         * Enable privilege checking for actions
         */
        'check_privileges' => env('DATATABLES_CHECK_PRIVILEGES', true),

    ],


    /*
    |--------------------------------------------------------------------------
    | Search & Filter Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for advanced search and filtering functionality.
    |
    | Requirements 16, 17: Search & Filter
    |
    */

    'search' => [

        /*
         * Enable advanced filtering
         */
        'advanced_filtering' => env('DATATABLES_ADVANCED_FILTERING', true),

        /*
         * Enable global search
         */
        'global_search' => env('DATATABLES_GLOBAL_SEARCH', true),

        /*
         * Enable column-specific search
         */
        'column_search' => env('DATATABLES_COLUMN_SEARCH', true),

        /*
         * Case insensitive search
         */
        'case_insensitive' => env('DATATABLES_CASE_INSENSITIVE', true),

        /*
         * Enable partial matching
         */
        'partial_matching' => env('DATATABLES_PARTIAL_MATCHING', true),

        /*
         * Enable wildcard search
         */
        'wildcard_search' => env('DATATABLES_WILDCARD_SEARCH', false),

        /*
         * Enable regex search
         */
        'regex_search' => env('DATATABLES_REGEX_SEARCH', false),

        /*
         * Search debounce delay in milliseconds
         */
        'debounce_delay' => env('DATATABLES_SEARCH_DEBOUNCE', 300),

        /*
         * Minimum search term length
         */
        'min_search_length' => env('DATATABLES_MIN_SEARCH_LENGTH', 1),

        /*
         * Highlight search terms in results
         */
        'highlight_results' => env('DATATABLES_HIGHLIGHT_RESULTS', false),

        /*
         * Persist search state across page loads
         */
        'persist_search' => env('DATATABLES_PERSIST_SEARCH', false),

        /*
         * Available filter operators
         */
        'filter_operators' => [
            'equals' => '=',
            'not_equals' => '!=',
            'greater_than' => '>',
            'less_than' => '<',
            'greater_equal' => '>=',
            'less_equal' => '<=',
            'like' => 'LIKE',
            'not_like' => 'NOT LIKE',
            'in' => 'IN',
            'not_in' => 'NOT IN',
            'between' => 'BETWEEN',
            'is_null' => 'IS NULL',
            'is_not_null' => 'IS NOT NULL',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Export Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for data export functionality (CSV, Excel, PDF).
    |
    | Requirement 18: Export Functionality
    |
    */

    'export' => [

        /*
         * Enable export functionality
         */
        'enabled' => env('DATATABLES_EXPORT_ENABLED', true),

        /*
         * Available export formats
         */
        'formats' => ['csv', 'excel', 'pdf'],

        /*
         * Maximum rows to export
         * Prevents memory issues with large datasets
         */
        'max_rows' => env('DATATABLES_EXPORT_MAX_ROWS', 10000),

        /*
         * Use streaming for large exports
         */
        'use_streaming' => env('DATATABLES_EXPORT_STREAMING', true),

        /*
         * Streaming threshold (rows)
         */
        'streaming_threshold' => env('DATATABLES_EXPORT_STREAMING_THRESHOLD', 1000),

        /*
         * Include column headers in export
         */
        'include_headers' => env('DATATABLES_EXPORT_HEADERS', true),

        /*
         * Include only filtered data
         */
        'filtered_only' => env('DATATABLES_EXPORT_FILTERED_ONLY', true),

        /*
         * Show export progress indicator
         */
        'show_progress' => env('DATATABLES_EXPORT_PROGRESS', true),

        /*
         * Export file name pattern
         * Available placeholders: {table}, {date}, {time}
         */
        'filename_pattern' => env('DATATABLES_EXPORT_FILENAME', '{table}_{date}'),

        /*
         * CSV configuration
         */
        'csv' => [
            'delimiter' => env('DATATABLES_CSV_DELIMITER', ','),
            'enclosure' => env('DATATABLES_CSV_ENCLOSURE', '"'),
            'escape' => env('DATATABLES_CSV_ESCAPE', '\\'),
            'encoding' => env('DATATABLES_CSV_ENCODING', 'UTF-8'),
        ],

        /*
         * Excel configuration
         */
        'excel' => [
            'format' => env('DATATABLES_EXCEL_FORMAT', 'xlsx'), // xlsx or xls
            'auto_size' => env('DATATABLES_EXCEL_AUTO_SIZE', true),
            'freeze_header' => env('DATATABLES_EXCEL_FREEZE_HEADER', true),
        ],

        /*
         * PDF configuration
         */
        'pdf' => [
            'orientation' => env('DATATABLES_PDF_ORIENTATION', 'landscape'), // portrait or landscape
            'paper_size' => env('DATATABLES_PDF_PAPER_SIZE', 'A4'),
            'font_size' => env('DATATABLES_PDF_FONT_SIZE', 10),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Formula Column Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for calculated/formula columns.
    |
    | Requirement 19: Formula Columns
    |
    */

    'formula' => [

        /*
         * Enable formula columns
         */
        'enabled' => env('DATATABLES_FORMULA_ENABLED', true),

        /*
         * Cache formula results
         */
        'cache_results' => env('DATATABLES_FORMULA_CACHE', true),

        /*
         * Formula cache TTL in seconds
         */
        'cache_ttl' => env('DATATABLES_FORMULA_CACHE_TTL', 300),

        /*
         * Allowed mathematical operators
         */
        'allowed_operators' => ['+', '-', '*', '/', '%', '**'],

        /*
         * Allowed functions
         */
        'allowed_functions' => [
            'abs', 'ceil', 'floor', 'round', 'sqrt', 'pow',
            'min', 'max', 'sum', 'avg', 'count',
            'upper', 'lower', 'trim', 'length',
            'concat', 'substr', 'replace',
            'date', 'now', 'year', 'month', 'day',
        ],

        /*
         * Maximum formula complexity (nested operations)
         */
        'max_complexity' => env('DATATABLES_FORMULA_MAX_COMPLEXITY', 10),

        /*
         * Enable lazy evaluation (calculate only when needed)
         */
        'lazy_evaluation' => env('DATATABLES_FORMULA_LAZY_EVAL', true),

    ],

    /*
    |--------------------------------------------------------------------------
    | Relationship Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for handling table relationships and joins.
    |
    | Requirement 21: Relationships
    |
    */

    'relationships' => [

        /*
         * Enable relationship support
         */
        'enabled' => env('DATATABLES_RELATIONSHIPS_ENABLED', true),

        /*
         * Enable eager loading by default
         */
        'eager_load_default' => env('DATATABLES_EAGER_LOAD_DEFAULT', true),

        /*
         * Maximum join depth
         */
        'max_join_depth' => env('DATATABLES_MAX_JOIN_DEPTH', 3),

        /*
         * Supported relationship types
         */
        'supported_types' => [
            'hasOne',
            'hasMany',
            'belongsTo',
            'belongsToMany',
            'hasManyThrough',
            'morphTo',
            'morphOne',
            'morphMany',
        ],

        /*
         * Handle missing relationships gracefully
         */
        'graceful_missing' => env('DATATABLES_GRACEFUL_MISSING_RELATIONS', true),

        /*
         * Default value for missing relationships
         */
        'missing_value' => env('DATATABLES_MISSING_RELATION_VALUE', '-'),

    ],

    /*
    |--------------------------------------------------------------------------
    | Column Configuration
    |--------------------------------------------------------------------------
    |
    | Default configuration for table columns.
    |
    | Requirement 22: Column Configuration
    |
    */

    'columns' => [

        /*
         * Default column width (null = auto)
         */
        'default_width' => null,

        /*
         * Default column alignment
         */
        'default_align' => 'left',

        /*
         * Enable column visibility toggle
         */
        'visibility_toggle' => env('DATATABLES_COLUMN_VISIBILITY_TOGGLE', true),

        /*
         * Enable column reordering
         */
        'reordering' => env('DATATABLES_COLUMN_REORDERING', false),

        /*
         * Enable fixed columns (frozen)
         */
        'fixed_columns' => env('DATATABLES_FIXED_COLUMNS', false),

        /*
         * Number of fixed columns from left
         */
        'fixed_left' => env('DATATABLES_FIXED_LEFT', 0),

        /*
         * Number of fixed columns from right
         */
        'fixed_right' => env('DATATABLES_FIXED_RIGHT', 0),

        /*
         * Enable column grouping/merging
         */
        'grouping' => env('DATATABLES_COLUMN_GROUPING', false),

        /*
         * Supported column types
         */
        'supported_types' => [
            'string', 'integer', 'decimal', 'float',
            'date', 'datetime', 'time', 'timestamp',
            'boolean', 'json', 'array',
            'text', 'longtext', 'enum',
        ],

        /*
         * Default date format
         */
        'date_format' => env('DATATABLES_DATE_FORMAT', 'Y-m-d'),

        /*
         * Default datetime format
         */
        'datetime_format' => env('DATATABLES_DATETIME_FORMAT', 'Y-m-d H:i:s'),

        /*
         * Default time format
         */
        'time_format' => env('DATATABLES_TIME_FORMAT', 'H:i:s'),

        /*
         * Default decimal places
         */
        'decimal_places' => env('DATATABLES_DECIMAL_PLACES', 2),

        /*
         * Thousand separator
         */
        'thousand_separator' => env('DATATABLES_THOUSAND_SEPARATOR', ','),

        /*
         * Decimal separator
         */
        'decimal_separator' => env('DATATABLES_DECIMAL_SEPARATOR', '.'),

    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for error handling and logging.
    |
    | Requirement 23: Error Handling
    |
    */

    'error_handling' => [

        /*
         * Enable detailed error messages (disable in production)
         */
        'detailed_errors' => env('DATATABLES_DETAILED_ERRORS', env('APP_DEBUG', false)),

        /*
         * Log all errors
         */
        'log_errors' => env('DATATABLES_LOG_ERRORS', true),

        /*
         * Error log channel
         */
        'error_log_channel' => env('DATATABLES_ERROR_LOG_CHANNEL', 'daily'),

        /*
         * Include stack trace in logs
         */
        'log_stack_trace' => env('DATATABLES_LOG_STACK_TRACE', true),

        /*
         * Include request context in logs
         */
        'log_request_context' => env('DATATABLES_LOG_REQUEST_CONTEXT', true),

        /*
         * User-friendly error message
         */
        'user_error_message' => env('DATATABLES_USER_ERROR_MESSAGE', 'An error occurred while processing your request.'),

        /*
         * Enable error monitoring/alerting
         */
        'enable_monitoring' => env('DATATABLES_ERROR_MONITORING', false),

        /*
         * Error rate threshold for alerts (errors per minute)
         */
        'alert_threshold' => env('DATATABLES_ERROR_ALERT_THRESHOLD', 10),

    ],

    /*
    |--------------------------------------------------------------------------
    | Testing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for testing support.
    |
    | Requirement 25: Testing Support
    |
    */

    'testing' => [

        /*
         * Enable test mode
         */
        'test_mode' => env('DATATABLES_TEST_MODE', false),

        /*
         * Seed for random value generation in tests
         */
        'random_seed' => env('DATATABLES_TEST_SEED', null),

        /*
         * Enable test fixtures
         */
        'use_fixtures' => env('DATATABLES_USE_FIXTURES', false),

        /*
         * Mock external dependencies in tests
         */
        'mock_dependencies' => env('DATATABLES_MOCK_DEPENDENCIES', false),

    ],

    /*
    |--------------------------------------------------------------------------
    | Backward Compatibility
    |--------------------------------------------------------------------------
    |
    | Settings to ensure backward compatibility with existing code.
    |
    | Requirement 24: Backward Compatibility
    |
    */

    'compatibility' => [

        /*
         * Enable legacy mode (maintains old behavior)
         */
        'legacy_mode' => env('DATATABLES_LEGACY_MODE', false),

        /*
         * Warn about deprecated features
         */
        'warn_deprecated' => env('DATATABLES_WARN_DEPRECATED', true),

        /*
         * Log deprecated feature usage
         */
        'log_deprecated' => env('DATATABLES_LOG_DEPRECATED', true),

        /*
         * Deprecated features log channel
         */
        'deprecated_log_channel' => env('DATATABLES_DEPRECATED_LOG_CHANNEL', 'daily'),

    ],

];
