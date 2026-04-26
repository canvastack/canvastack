# Configuration Guide

**Version:** 2.0.0  
**Last Updated:** April 4, 2026

---

## Overview

Complete configuration reference for CanvaStack Table Components. All configurations are located in:
- `config/canvastack.cache.php` - Cache configuration (66 options)
- `config/canvastack.datatables.php` - DataTables configuration (159 options)

## Configuration Files

### Publishing Configs

```bash
# Publish all configs
php artisan vendor:publish --provider="Canvastack\Canvastack\CanvastackServiceProvider"

# Publish specific config
php artisan vendor:publish --tag=config
```

---

## Cache Configuration

**File:** `config/canvastack.cache.php`

### Global Settings

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Cache Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch for all caching functionality.
    | Set to false to disable all caching.
    |
    */
    'enabled' => env('CANVASTACK_CACHE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cache Store
    |--------------------------------------------------------------------------
    |
    | Cache store to use (redis, memcached, file, array).
    | Should match a store defined in config/cache.php
    |
    */
    'store' => env('CANVASTACK_CACHE_STORE', env('CACHE_DRIVER', 'file')),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    |
    | Default time-to-live for cache entries in seconds.
    | Individual cache types can override this value.
    |
    */
    'ttl' => env('CANVASTACK_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix for all cache keys to avoid collisions.
    |
    */
    'prefix' => env('CANVASTACK_CACHE_PREFIX', 'canvastack_'),

    /*
    |--------------------------------------------------------------------------
    | Cache Tags
    |--------------------------------------------------------------------------
    |
    | Tags for cache entries (requires Redis or Memcached).
    | Allows bulk invalidation by tag.
    |
    */
    'tags' => ['canvastack', 'tables'],
];
```

### Schema Cache

```php
'table_schema' => [
    'enabled' => true,
    'ttl' => 3600, // 1 hour
    'key_prefix' => 'schema_',
    'cache_columns' => true,
    'cache_indexes' => true,
    'cache_foreign_keys' => true,
],
```

### Validation Cache

```php
'validation' => [
    'enabled' => true,
    'ttl' => 3600,
    'key_prefix' => 'validation_',
    'cache_column_listing' => true,
    'cache_table_existence' => true,
],
```

### Config Cache

```php
'config' => [
    'enabled' => true,
    'ttl' => 3600,
    'key_prefix' => 'config_',
    'cache_column_labels' => true,
    'cache_display_config' => true,
],
```

### Relationships Cache

```php
'relationships' => [
    'cache_definitions' => true,
    'ttl' => 1800, // 30 minutes
    'key_prefix' => 'relationships_',
    'cache_nested' => true,
],
```

### Query Results Cache

```php
'query_results' => [
    'enabled' => false, // Disabled by default
    'ttl' => 300, // 5 minutes
    'key_prefix' => 'query_',
    'cache_count' => true,
    'cache_results' => true,
],
```

### Formula Results Cache

```php
'formula_results' => [
    'enabled' => true,
    'ttl' => 600, // 10 minutes
    'key_prefix' => 'formula_',
    'cache_calculations' => true,
],
```

### Cache Invalidation

```php
'invalidation' => [
    /*
    | Enable cache invalidation
    */
    'enabled' => true,

    /*
    | Invalidation strategy:
    | - immediate: Clear cache immediately
    | - lazy: Mark as stale, clear on next access
    | - scheduled: Queue for scheduled clearing
    */
    'strategy' => 'immediate',

    /*
    | Events that trigger invalidation
    */
    'invalidation_events' => [
        'model.updated',
        'model.deleted',
        'schema.changed',
    ],

    /*
    | Cascade invalidation to related caches
    */
    'cascade_invalidation' => true,
],
```

### Cache Monitoring

```php
'monitoring' => [
    /*
    | Enable cache monitoring
    */
    'enabled' => true,

    /*
    | Log cache hits and misses
    */
    'log_hits_misses' => true,

    /*
    | Log channel for monitoring
    */
    'log_channel' => 'daily',

    /*
    | Track cache statistics
    */
    'track_statistics' => true,

    /*
    | Statistics TTL (24 hours)
    */
    'statistics_ttl' => 86400,
],
```

### Cache Warming

```php
'warming' => [
    /*
    | Enable cache warming
    */
    'enabled' => true,

    /*
    | Warm cache on application boot
    | Only runs in production environment
    */
    'on_boot' => true,

    /*
    | Enable scheduled warming
    */
    'scheduled' => true,

    /*
    | Cron expression for scheduled warming
    | Default: Every 6 hours
    */
    'schedule' => '0 */6 * * *',

    /*
    | Tables to warm
    */
    'tables' => [
        'users',
        'posts',
        'comments',
    ],
],
```

### Development Settings

```php
'development' => [
    /*
    | Disable cache in development environment
    */
    'disable_in_dev' => false,

    /*
    | Log all cache operations
    */
    'log_operations' => true,
],
```

---

## DataTables Configuration

**File:** `config/canvastack.datatables.php`

### Allowed Tables

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Allowed Tables
    |--------------------------------------------------------------------------
    |
    | Whitelist of tables that can be accessed via DataTables.
    | Set to null to allow all tables (validates against database).
    |
    */
    'allowed_tables' => null, // or ['users', 'posts', 'comments']
];
```

### Security Settings

```php
'security' => [
    /*
    | XSS Protection
    | Automatically escape all user input
    */
    'xss_protection' => true,

    /*
    | SQL Injection Prevention
    | Validate operators and sort directions
    */
    'sql_injection_prevention' => true,

    /*
    | Input Validation
    | Validate all user inputs
    */
    'input_validation' => true,

    /*
    | Log Security Events
    | Log all security-related events
    */
    'log_security_events' => true,

    /*
    | Security Log Channel
    */
    'security_log_channel' => 'daily',

    /*
    | Allowed SQL Operators
    */
    'allowed_operators' => [
        '=', '!=', '<>', '>', '<', '>=', '<=',
        'LIKE', 'NOT LIKE', 'IN', 'NOT IN',
        'BETWEEN', 'IS NULL', 'IS NOT NULL',
    ],

    /*
    | Allowed Sort Directions
    */
    'allowed_sort_directions' => ['asc', 'desc', 'ASC', 'DESC'],

    /*
    | Use SafeHtml Marker
    | Prevent double-encoding
    */
    'use_safehtml_marker' => true,

    /*
    | Validate Column Names
    | Check column exists in table schema
    */
    'validate_column_names' => true,

    /*
    | Maximum Search Length
    */
    'max_search_length' => 255,

    /*
    | Destructive Actions
    | Require confirmation for delete/truncate
    */
    'destructive_actions' => ['delete', 'truncate'],
],
```

### Performance Settings

```php
'performance' => [
    /*
    | Query Optimization
    | Enable query optimization features
    */
    'query_optimization' => true,

    /*
    | Select Required Columns Only
    | Only select columns needed for display
    */
    'select_required_only' => true,

    /*
    | Eager Loading
    | Enable eager loading for relationships
    */
    'eager_loading' => true,

    /*
    | Log Slow Queries
    */
    'log_slow_queries' => true,

    /*
    | Slow Query Threshold (milliseconds)
    */
    'slow_query_threshold' => 1000,

    /*
    | Slow Query Log Channel
    */
    'slow_query_log_channel' => 'daily',

    /*
    | Monitor Memory Usage
    */
    'monitor_memory' => true,

    /*
    | Maximum Memory Rows
    | Switch to chunking above this threshold
    */
    'max_memory_rows' => 1000,
],
```

### Accessibility Settings

```php
'accessibility' => [
    /*
    | ARIA Enabled
    | Enable ARIA attributes
    */
    'aria_enabled' => true,

    /*
    | Add ARIA Labels
    */
    'add_aria_labels' => true,

    /*
    | Add ARIA Sort
    */
    'add_aria_sort' => true,

    /*
    | Add Table Captions
    */
    'add_captions' => true,

    /*
    | Add ARIA Busy
    */
    'add_aria_busy' => true,

    /*
    | Keyboard Navigation
    */
    'keyboard_navigation' => true,

    /*
    | Screen Reader Support
    */
    'screen_reader_support' => true,

    /*
    | Focus Indicators
    */
    'focus_indicators' => true,

    /*
    | Announce Loading
    */
    'announce_loading' => true,

    /*
    | Announce Filters
    */
    'announce_filters' => true,

    /*
    | Announce Sorting
    */
    'announce_sorting' => true,
],
```

### Search Settings

```php
'search' => [
    /*
    | Global Search
    */
    'global_search' => true,

    /*
    | Case Insensitive Search
    */
    'case_insensitive' => true,

    /*
    | Regex Search
    */
    'regex_search' => false,

    /*
    | Debounce Delay (milliseconds)
    */
    'debounce_delay' => 300,

    /*
    | Minimum Search Length
    */
    'min_search_length' => 2,

    /*
    | Highlight Results
    */
    'highlight_results' => true,

    /*
    | Wildcard Search
    | Support * and ? wildcards
    */
    'wildcard_search' => true,

    /*
    | Partial Matching
    | Add % at start and end
    */
    'partial_matching' => true,

    /*
    | Persist Search State
    | Save search in session
    */
    'persist_search_state' => true,

    /*
    | Search History
    */
    'search_history' => true,

    /*
    | Maximum Search History
    */
    'max_search_history' => 10,
],
```

### Export Settings

```php
'export' => [
    /*
    | Export Enabled
    */
    'enabled' => true,

    /*
    | Allowed Formats
    */
    'formats' => ['csv', 'excel', 'pdf'],

    /*
    | Maximum Export Rows
    */
    'max_rows' => 10000,

    /*
    | Include Headers
    */
    'include_headers' => true,

    /*
    | Filename Pattern
    | Available: {table}, {date}, {time}, {user}
    */
    'filename_pattern' => '{table}_export_{date}',

    /*
    | CSV Settings
    */
    'csv' => [
        'delimiter' => ',',
        'enclosure' => '"',
        'escape' => '\\',
        'chunk_size' => 1000,
        'compression' => false,
        'include_bom' => true,
    ],
],
```

### Column Settings

```php
'columns' => [
    /*
    | Date Format
    */
    'date_format' => 'Y-m-d',

    /*
    | DateTime Format
    */
    'datetime_format' => 'Y-m-d H:i:s',

    /*
    | Time Format
    */
    'time_format' => 'H:i:s',

    /*
    | Decimal Places
    */
    'decimal_places' => 2,

    /*
    | Thousand Separator
    */
    'thousand_separator' => ',',

    /*
    | Decimal Separator
    */
    'decimal_separator' => '.',
],
```

### Relationship Settings

```php
'relationships' => [
    /*
    | Nested Eager Loading
    */
    'nested_eager_loading' => true,

    /*
    | Lazy Loading Threshold
    | Skip eager loading above this row count
    */
    'lazy_loading_threshold' => 100,

    /*
    | Relationship Cache TTL
    */
    'relationship_cache_ttl' => 1800,
],
```

### Development Settings

```php
'development' => [
    /*
    | Log Queries
    */
    'log_queries' => true,

    /*
    | Log Cache Operations
    */
    'log_cache_operations' => true,

    /*
    | Log Performance Metrics
    */
    'log_performance_metrics' => true,
],
```

### Error Handling

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
    | Detailed Errors
    | Show detailed errors in development
    */
    'detailed_errors' => env('APP_DEBUG', false),
],
```

### Action Buttons

```php
'actions' => [
    /*
    | Actions Enabled
    */
    'enabled' => true,

    /*
    | Check Privileges
    */
    'check_privileges' => true,

    /*
    | Action Icons
    */
    'icons' => [
        'view' => 'fa-eye',
        'edit' => 'fa-edit',
        'delete' => 'fa-trash',
    ],

    /*
    | Action Labels
    */
    'labels' => [
        'view' => 'View',
        'edit' => 'Edit',
        'delete' => 'Delete',
    ],

    /*
    | Action Classes
    */
    'classes' => [
        'view' => 'btn-info',
        'edit' => 'btn-primary',
        'delete' => 'btn-danger',
    ],
],
```

### DataTables Defaults

```php
'defaults' => [
    'page_length' => 10,
    'ordering' => true,
    'searching' => true,
    'paging' => true,
    'info' => true,
    'auto_width' => true,
    'responsive' => true,
    'scroll_x' => false,
    'scroll_y' => false,
    'scroll_collapse' => false,
    'state_save' => false,
    'state_duration' => 7200,
    'dom' => 'lfrtip',
],
```

---

## Environment Variables

You can override configurations using environment variables:

```env
# Cache
CANVASTACK_CACHE_ENABLED=true
CANVASTACK_CACHE_STORE=redis
CANVASTACK_CACHE_TTL=3600
CANVASTACK_CACHE_PREFIX=canvastack_

# Security
CANVASTACK_SECURITY_XSS=true
CANVASTACK_SECURITY_SQL_INJECTION=true
CANVASTACK_SECURITY_LOG_EVENTS=true

# Performance
CANVASTACK_PERFORMANCE_EAGER_LOADING=true
CANVASTACK_PERFORMANCE_LOG_SLOW_QUERIES=true
CANVASTACK_PERFORMANCE_SLOW_QUERY_THRESHOLD=1000

# Development
CANVASTACK_DEV_LOG_QUERIES=true
CANVASTACK_DEV_LOG_CACHE=true
```

---

## Configuration Best Practices

### 1. Production Settings

```php
// Enable all security features
'security' => [
    'xss_protection' => true,
    'sql_injection_prevention' => true,
    'input_validation' => true,
    'log_security_events' => true,
],

// Use Redis for caching
'cache' => [
    'store' => 'redis',
    'enabled' => true,
],

// Enable monitoring
'monitoring' => [
    'enabled' => true,
    'track_statistics' => true,
],
```

### 2. Development Settings

```php
// Enable detailed logging
'development' => [
    'log_queries' => true,
    'log_cache_operations' => true,
    'log_performance_metrics' => true,
],

// Disable cache for fresh data
'cache' => [
    'development' => [
        'disable_in_dev' => true,
    ],
],
```

### 3. Performance Optimization

```php
// Enable all performance features
'performance' => [
    'query_optimization' => true,
    'select_required_only' => true,
    'eager_loading' => true,
    'monitor_memory' => true,
],

// Enable cache warming
'warming' => [
    'enabled' => true,
    'scheduled' => true,
    'tables' => ['users', 'posts'],
],
```

---

## Related Documentation

- [Security Features](./features/SECURITY.md)
- [Cache Management](./features/CACHE_MANAGEMENT.md)
- [Performance Guide](./PERFORMANCE.md)
- [Best Practices](./guides/BEST_PRACTICES.md)

---

**Last Updated:** April 4, 2026  
**Maintained By:** CanvaStack Team
