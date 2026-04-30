<?php

/**
 * Canvastack Cache Configuration
 * 
 * This configuration file provides caching settings for Canvastack components
 * including table schema caching, validation result caching, and configuration caching.
 * 
 * @package Canvastack\Canvastack
 * @category Configuration
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Cache Driver
    |--------------------------------------------------------------------------
    |
    | Default cache driver for Canvastack components.
    | Uses Laravel's cache configuration from config/cache.php
    |
    | Supported: "file", "redis", "memcached", "database", "array"
    |
    */

    'driver' => env('CANVASTACK_CACHE_DRIVER', env('CACHE_DRIVER', 'file')),

    /*
    |--------------------------------------------------------------------------
    | Cache Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix for all Canvastack cache keys to avoid collisions with other
    | application cache entries.
    |
    */

    'prefix' => env('CANVASTACK_CACHE_PREFIX', 'canvastack_'),

    /*
    |--------------------------------------------------------------------------
    | Table Schema Cache
    |--------------------------------------------------------------------------
    |
    | Configuration for caching database table schemas. Schema caching
    | significantly improves performance by avoiding repeated database
    | metadata queries.
    |
    | Requirement 5.1: Schema Caching
    | Property 16: Caching - Schema Caching
    |
    */

    'table_schema' => [

        /*
         * Enable table schema caching
         */
        'enabled' => env('CANVASTACK_CACHE_SCHEMA_ENABLED', true),

        /*
         * Cache store to use (null = use default driver)
         */
        'store' => env('CANVASTACK_CACHE_SCHEMA_STORE', null),

        /*
         * Cache TTL in seconds (default: 1 hour)
         * Set to 0 for no expiration
         */
        'ttl' => env('CANVASTACK_CACHE_SCHEMA_TTL', 3600),

        /*
         * Cache key prefix for schema entries
         */
        'key_prefix' => 'table_schema_',

        /*
         * Cache the following schema information:
         * - columns: Column names and types
         * - indexes: Table indexes
         * - foreign_keys: Foreign key relationships
         * - primary_key: Primary key column(s)
         */
        'cache_items' => [
            'columns' => true,
            'indexes' => true,
            'foreign_keys' => true,
            'primary_key' => true,
        ],

        /*
         * Automatically invalidate cache when schema changes detected
         */
        'auto_invalidate' => env('CANVASTACK_CACHE_SCHEMA_AUTO_INVALIDATE', false),

        /*
         * Warm up cache on application boot
         * Preloads schemas for specified tables
         */
        'warmup' => [
            'enabled' => env('CANVASTACK_CACHE_SCHEMA_WARMUP', false),
            'tables' => env('CANVASTACK_CACHE_SCHEMA_WARMUP_TABLES', []),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Result Cache
    |--------------------------------------------------------------------------
    |
    | Configuration for caching validation results. Expensive validation
    | operations (like image validation) are cached to improve performance.
    |
    | Requirement 5.3: Validation Result Caching
    | Property 17: Caching - Validation Result Caching
    |
    */

    'validation' => [

        /*
         * Enable validation result caching
         */
        'enabled' => env('CANVASTACK_CACHE_VALIDATION_ENABLED', true),

        /*
         * Cache store to use (null = use default driver)
         */
        'store' => env('CANVASTACK_CACHE_VALIDATION_STORE', null),

        /*
         * Cache TTL in seconds (default: 1 hour)
         */
        'ttl' => env('CANVASTACK_CACHE_VALIDATION_TTL', 3600),

        /*
         * Cache key prefix for validation entries
         */
        'key_prefix' => 'validation_',

        /*
         * Cache validation results for these types:
         * - table_name: Table name validation
         * - column_name: Column name validation
         * - image: Image validation
         * - url: URL validation
         */
        'cache_types' => [
            'table_name' => true,
            'column_name' => true,
            'image' => true,
            'url' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration Cache
    |--------------------------------------------------------------------------
    |
    | Configuration for caching table configurations and settings.
    |
    | Requirement 5.4: Configuration Caching
    |
    */

    'config' => [

        /*
         * Enable configuration caching
         */
        'enabled' => env('CANVASTACK_CACHE_CONFIG_ENABLED', true),

        /*
         * Cache store to use (null = use default driver)
         */
        'store' => env('CANVASTACK_CACHE_CONFIG_STORE', null),

        /*
         * Cache TTL in seconds (default: 1 hour)
         */
        'ttl' => env('CANVASTACK_CACHE_CONFIG_TTL', 3600),

        /*
         * Cache key prefix for configuration entries
         */
        'key_prefix' => 'config_',

        /*
         * Cache table configurations
         */
        'cache_table_configs' => env('CANVASTACK_CACHE_TABLE_CONFIGS', true),

        /*
         * Cache DataTables options
         */
        'cache_datatables_options' => env('CANVASTACK_CACHE_DATATABLES_OPTIONS', true),

    ],

    /*
    |--------------------------------------------------------------------------
    | Relationship Cache
    |--------------------------------------------------------------------------
    |
    | Configuration for caching relationship definitions and parsed data.
    |
    | Requirement 5.5: Relationship Definition Caching
    |
    */

    'relationships' => [

        /*
         * Enable relationship caching
         */
        'enabled' => env('CANVASTACK_CACHE_RELATIONSHIPS_ENABLED', true),

        /*
         * Cache store to use (null = use default driver)
         */
        'store' => env('CANVASTACK_CACHE_RELATIONSHIPS_STORE', null),

        /*
         * Cache TTL in seconds (default: 1 hour)
         */
        'ttl' => env('CANVASTACK_CACHE_RELATIONSHIPS_TTL', 3600),

        /*
         * Cache key prefix for relationship entries
         */
        'key_prefix' => 'relationships_',

        /*
         * Cache relationship definitions
         */
        'cache_definitions' => env('CANVASTACK_CACHE_RELATIONSHIP_DEFS', true),

        /*
         * Cache parsed relationship data
         */
        'cache_parsed_data' => env('CANVASTACK_CACHE_RELATIONSHIP_PARSED', true),

    ],

    /*
    |--------------------------------------------------------------------------
    | Query Result Cache
    |--------------------------------------------------------------------------
    |
    | Configuration for caching database query results.
    | Use with caution - only cache queries that don't change frequently.
    |
    */

    'query_results' => [

        /*
         * Enable query result caching
         */
        'enabled' => env('CANVASTACK_CACHE_QUERY_RESULTS_ENABLED', false),

        /*
         * Cache store to use (null = use default driver)
         */
        'store' => env('CANVASTACK_CACHE_QUERY_RESULTS_STORE', null),

        /*
         * Cache TTL in seconds (default: 5 minutes)
         * Keep this short to avoid stale data
         */
        'ttl' => env('CANVASTACK_CACHE_QUERY_RESULTS_TTL', 300),

        /*
         * Cache key prefix for query result entries
         */
        'key_prefix' => 'query_results_',

        /*
         * Maximum result size to cache (in bytes)
         * Prevents caching very large result sets
         */
        'max_size' => env('CANVASTACK_CACHE_QUERY_MAX_SIZE', 1048576), // 1MB

    ],

    /*
    |--------------------------------------------------------------------------
    | Formula Result Cache
    |--------------------------------------------------------------------------
    |
    | Configuration for caching formula calculation results.
    |
    */

    'formula_results' => [

        /*
         * Enable formula result caching
         */
        'enabled' => env('CANVASTACK_CACHE_FORMULA_ENABLED', true),

        /*
         * Cache store to use (null = use default driver)
         */
        'store' => env('CANVASTACK_CACHE_FORMULA_STORE', null),

        /*
         * Cache TTL in seconds (default: 5 minutes)
         */
        'ttl' => env('CANVASTACK_CACHE_FORMULA_TTL', 300),

        /*
         * Cache key prefix for formula result entries
         */
        'key_prefix' => 'formula_results_',

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Invalidation
    |--------------------------------------------------------------------------
    |
    | Configuration for cache invalidation mechanisms.
    |
    | Requirement 5.6: Cache Invalidation Mechanisms
    |
    */

    'invalidation' => [

        /*
         * Enable automatic cache invalidation
         */
        'enabled' => env('CANVASTACK_CACHE_INVALIDATION_ENABLED', true),

        /*
         * Invalidation strategies:
         * - manual: Explicit invalidation calls only
         * - time_based: TTL-based expiration
         * - event_based: Invalidate on specific events
         */
        'strategy' => env('CANVASTACK_CACHE_INVALIDATION_STRATEGY', 'time_based'),

        /*
         * Events that trigger cache invalidation
         */
        'invalidation_events' => [
            'table.created',
            'table.updated',
            'table.deleted',
            'schema.modified',
            'config.updated',
        ],

        /*
         * Clear all related caches when invalidating
         * (e.g., clear validation cache when schema cache is invalidated)
         */
        'cascade_invalidation' => env('CANVASTACK_CACHE_CASCADE_INVALIDATION', true),

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for monitoring cache performance and hit rates.
    |
    */

    'monitoring' => [

        /*
         * Enable cache monitoring
         */
        'enabled' => env('CANVASTACK_CACHE_MONITORING_ENABLED', false),

        /*
         * Log cache hits and misses
         */
        'log_hits_misses' => env('CANVASTACK_CACHE_LOG_HITS_MISSES', false),

        /*
         * Log channel for cache monitoring
         */
        'log_channel' => env('CANVASTACK_CACHE_LOG_CHANNEL', 'daily'),

        /*
         * Track cache statistics
         */
        'track_statistics' => env('CANVASTACK_CACHE_TRACK_STATS', false),

        /*
         * Statistics TTL in seconds (default: 24 hours)
         */
        'statistics_ttl' => env('CANVASTACK_CACHE_STATS_TTL', 86400),

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Warming
    |--------------------------------------------------------------------------
    |
    | Configuration for cache warming (preloading cache entries).
    |
    */

    'warming' => [

        /*
         * Enable cache warming
         */
        'enabled' => env('CANVASTACK_CACHE_WARMING_ENABLED', false),

        /*
         * Warm cache on application boot
         */
        'on_boot' => env('CANVASTACK_CACHE_WARM_ON_BOOT', false),

        /*
         * Warm cache via scheduled command
         */
        'scheduled' => env('CANVASTACK_CACHE_WARM_SCHEDULED', false),

        /*
         * Schedule frequency (cron expression)
         */
        'schedule' => env('CANVASTACK_CACHE_WARM_SCHEDULE', '0 */6 * * *'), // Every 6 hours

        /*
         * Tables to warm up
         */
        'tables' => env('CANVASTACK_CACHE_WARM_TABLES', []),

    ],

    /*
    |--------------------------------------------------------------------------
    | Development Settings
    |--------------------------------------------------------------------------
    |
    | Settings for development and debugging.
    |
    */

    'development' => [

        /*
         * Disable caching in development
         */
        'disable_in_dev' => env('CANVASTACK_CACHE_DISABLE_IN_DEV', false),

        /*
         * Log all cache operations in development
         */
        'log_operations' => env('CANVASTACK_CACHE_LOG_OPERATIONS', false),

        /*
         * Show cache debug information
         */
        'debug' => env('CANVASTACK_CACHE_DEBUG', false),

    ],

];
