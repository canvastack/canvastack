# Filter Configuration

## Overview

This document describes all configuration options for the CanvaStack filter system, including bi-directional cascade functionality.

**Location**: `config/canvastack.php` → `table.filters` section

---

## Configuration Options

### Bi-Directional Cascade

#### `bidirectional_cascade`

**Type**: `boolean`  
**Default**: `false`  
**Environment Variable**: `CANVASTACK_BIDIRECTIONAL_CASCADE`

Enable bi-directional cascade globally for all filters. When enabled, selecting any filter will update ALL other related filters, not just filters after it.

```php
'bidirectional_cascade' => env('CANVASTACK_BIDIRECTIONAL_CASCADE', false),
```

**Usage**:
```bash
# .env
CANVASTACK_BIDIRECTIONAL_CASCADE=true
```

**Example**:
```php
// Enable globally via config
config(['canvastack.table.filters.bidirectional_cascade' => true]);

// OR enable per table
$table->setBidirectionalCascade(true);
```

---

### Debounce Delay

#### `debounce_delay`

**Type**: `integer` (milliseconds)  
**Default**: `300`  
**Environment Variable**: `CANVASTACK_FILTER_DEBOUNCE`

Delay in milliseconds before executing filter cascade after user input. Prevents excessive API calls when user rapidly changes filters.

```php
'debounce_delay' => env('CANVASTACK_FILTER_DEBOUNCE', 300),
```

**Usage**:
```bash
# .env
CANVASTACK_FILTER_DEBOUNCE=500  # 500ms delay
```

**Recommendations**:
- **Fast networks**: 200-300ms
- **Slow networks**: 500-1000ms
- **Mobile devices**: 500-800ms

---

### Frontend Cache TTL

#### `frontend_cache_ttl`

**Type**: `integer` (seconds)  
**Default**: `300` (5 minutes)  
**Environment Variable**: `CANVASTACK_FILTER_CACHE_TTL`

Time-to-live for cached filter options in the frontend (browser). Cached options are shown immediately while fresh data is fetched in the background.

```php
'frontend_cache_ttl' => env('CANVASTACK_FILTER_CACHE_TTL', 300),
```

**Usage**:
```bash
# .env
CANVASTACK_FILTER_CACHE_TTL=600  # 10 minutes
```

**Recommendations**:
- **Static data**: 3600 (1 hour)
- **Semi-static data**: 600 (10 minutes)
- **Dynamic data**: 300 (5 minutes)
- **Real-time data**: 60 (1 minute)

---

### Max Cascade Depth

#### `max_cascade_depth`

**Type**: `integer`  
**Default**: `10`  
**Environment Variable**: `CANVASTACK_MAX_CASCADE_DEPTH`

Maximum depth for cascade operations to prevent infinite loops. If cascade depth exceeds this limit, the operation is aborted.

```php
'max_cascade_depth' => env('CANVASTACK_MAX_CASCADE_DEPTH', 10),
```

**Usage**:
```bash
# .env
CANVASTACK_MAX_CASCADE_DEPTH=15
```

**Recommendations**:
- **Simple filters (2-3)**: 5-10
- **Complex filters (4-6)**: 10-15
- **Very complex (7+)**: 15-20

**Warning**: Higher values may impact performance.

---

### Show Cascade Indicators

#### `show_cascade_indicators`

**Type**: `boolean`  
**Default**: `true`  
**Environment Variable**: `CANVASTACK_SHOW_CASCADE_INDICATORS`

Show visual indicators (loading spinners, cascade direction) during cascade operations.

```php
'show_cascade_indicators' => env('CANVASTACK_SHOW_CASCADE_INDICATORS', true),
```

**Usage**:
```bash
# .env
CANVASTACK_SHOW_CASCADE_INDICATORS=false  # Hide indicators
```

**When to disable**:
- Custom UI implementation
- Performance testing
- Minimal UI design

---

## Performance Configuration

### Filter Optimization

#### `filter_optimization`

**Type**: `boolean`  
**Default**: `true`  
**Environment Variable**: `CANVASTACK_FILTER_OPTIMIZATION`

Enable query optimization for filter operations (eager loading, query caching, etc.).

```php
'filter_optimization' => env('CANVASTACK_FILTER_OPTIMIZATION', true),
```

---

### Max Filter Options

#### `max_filter_options`

**Type**: `integer`  
**Default**: `1000`  
**Environment Variable**: `CANVASTACK_MAX_FILTER_OPTIONS`

Maximum number of options to return for a single filter. Prevents memory issues with large datasets.

```php
'max_filter_options' => env('CANVASTACK_MAX_FILTER_OPTIONS', 1000),
```

**Recommendations**:
- **Small datasets**: 500-1000
- **Medium datasets**: 1000-2000
- **Large datasets**: Use searchable filters instead

---

### Filter Query Timeout

#### `filter_query_timeout`

**Type**: `integer` (seconds)  
**Default**: `30`  
**Environment Variable**: `CANVASTACK_FILTER_QUERY_TIMEOUT`

Maximum time allowed for filter option queries. Prevents long-running queries from blocking the application.

```php
'filter_query_timeout' => env('CANVASTACK_FILTER_QUERY_TIMEOUT', 30),
```

---

### Filter Memory Limit

#### `filter_memory_limit`

**Type**: `string`  
**Default**: `'128M'`  
**Environment Variable**: `CANVASTACK_FILTER_MEMORY_LIMIT`

Memory limit for filter operations. Prevents memory exhaustion with large datasets.

```php
'filter_memory_limit' => env('CANVASTACK_FILTER_MEMORY_LIMIT', '128M'),
```

---

## Cache Configuration

### Filter Cache Enabled

#### `cache.filter_options.enabled`

**Type**: `boolean`  
**Default**: `true`  
**Environment Variable**: `CANVASTACK_FILTER_CACHE_ENABLED`

Enable backend caching for filter options.

```php
'cache' => [
    'filter_options' => [
        'enabled' => env('CANVASTACK_FILTER_CACHE_ENABLED', true),
    ],
],
```

---

### Filter Cache Driver

#### `cache.filter_options.driver`

**Type**: `string|null`  
**Default**: `null` (uses default cache driver)  
**Environment Variable**: `CANVASTACK_FILTER_CACHE_DRIVER`

Cache driver for filter options. If null, uses the default cache driver.

```php
'driver' => env('CANVASTACK_FILTER_CACHE_DRIVER', null),
```

**Options**:
- `redis` (recommended for production)
- `memcached`
- `file`
- `array` (testing only)

---

### Auto Invalidate

#### `cache.filter_options.auto_invalidate.enabled`

**Type**: `boolean`  
**Default**: `true`  
**Environment Variable**: `CANVASTACK_FILTER_AUTO_INVALIDATE`

Automatically invalidate filter cache when model data changes.

```php
'auto_invalidate' => [
    'enabled' => env('CANVASTACK_FILTER_AUTO_INVALIDATE', true),
    'events' => ['created', 'updated', 'deleted'],
],
```

---

### Cache Warming

#### `cache.filter_options.warming.enabled`

**Type**: `boolean`  
**Default**: `false`  
**Environment Variable**: `CANVASTACK_FILTER_CACHE_WARMING`

Pre-warm filter cache on a schedule to improve performance.

```php
'warming' => [
    'enabled' => env('CANVASTACK_FILTER_CACHE_WARMING', false),
    'schedule' => '0 */6 * * *', // Every 6 hours
    'batch_size' => 10,
],
```

---

## Complete Configuration Example

```php
// config/canvastack.php
'table' => [
    'filters' => [
        // Bi-directional cascade
        'bidirectional_cascade' => env('CANVASTACK_BIDIRECTIONAL_CASCADE', false),
        'debounce_delay' => env('CANVASTACK_FILTER_DEBOUNCE', 300),
        'frontend_cache_ttl' => env('CANVASTACK_FILTER_CACHE_TTL', 300),
        'max_cascade_depth' => env('CANVASTACK_MAX_CASCADE_DEPTH', 10),
        'show_cascade_indicators' => env('CANVASTACK_SHOW_CASCADE_INDICATORS', true),
    ],
],

'performance' => [
    'filter_optimization' => env('CANVASTACK_FILTER_OPTIMIZATION', true),
    'max_filter_options' => env('CANVASTACK_MAX_FILTER_OPTIONS', 1000),
    'filter_query_timeout' => env('CANVASTACK_FILTER_QUERY_TIMEOUT', 30),
    'filter_memory_limit' => env('CANVASTACK_FILTER_MEMORY_LIMIT', '128M'),
],

'cache' => [
    'filter_options' => [
        'enabled' => env('CANVASTACK_FILTER_CACHE_ENABLED', true),
        'ttl' => env('CANVASTACK_FILTER_CACHE_TTL', 300),
        'driver' => env('CANVASTACK_FILTER_CACHE_DRIVER', null),
        'auto_invalidate' => [
            'enabled' => env('CANVASTACK_FILTER_AUTO_INVALIDATE', true),
        ],
        'warming' => [
            'enabled' => env('CANVASTACK_FILTER_CACHE_WARMING', false),
        ],
    ],
],
```

---

## Environment Variables Reference

```bash
# .env

# Bi-directional cascade
CANVASTACK_BIDIRECTIONAL_CASCADE=false
CANVASTACK_FILTER_DEBOUNCE=300
CANVASTACK_FILTER_CACHE_TTL=300
CANVASTACK_MAX_CASCADE_DEPTH=10
CANVASTACK_SHOW_CASCADE_INDICATORS=true

# Performance
CANVASTACK_FILTER_OPTIMIZATION=true
CANVASTACK_MAX_FILTER_OPTIONS=1000
CANVASTACK_FILTER_QUERY_TIMEOUT=30
CANVASTACK_FILTER_MEMORY_LIMIT=128M

# Cache
CANVASTACK_FILTER_CACHE_ENABLED=true
CANVASTACK_FILTER_CACHE_DRIVER=redis
CANVASTACK_FILTER_AUTO_INVALIDATE=true
CANVASTACK_FILTER_CACHE_WARMING=false
```

---

## Usage Examples

### Example 1: Enable Bi-Directional Cascade Globally

```bash
# .env
CANVASTACK_BIDIRECTIONAL_CASCADE=true
```

```php
// All tables will have bi-directional cascade enabled
$table->filterGroups('name', 'selectbox', true);
$table->filterGroups('email', 'selectbox', true);
$table->filterGroups('created_at', 'datebox', true);
```

### Example 2: Optimize for Slow Networks

```bash
# .env
CANVASTACK_FILTER_DEBOUNCE=800
CANVASTACK_FILTER_CACHE_TTL=600
```

### Example 3: High-Performance Setup

```bash
# .env
CANVASTACK_FILTER_CACHE_ENABLED=true
CANVASTACK_FILTER_CACHE_DRIVER=redis
CANVASTACK_FILTER_AUTO_INVALIDATE=true
CANVASTACK_FILTER_CACHE_WARMING=true
CANVASTACK_FILTER_OPTIMIZATION=true
```

### Example 4: Development/Testing Setup

```bash
# .env
CANVASTACK_FILTER_CACHE_ENABLED=false
CANVASTACK_SHOW_CASCADE_INDICATORS=true
CANVASTACK_FILTER_DEBOUNCE=100
```

---

## Performance Tuning

### For Small Datasets (< 10K rows)

```bash
CANVASTACK_FILTER_CACHE_TTL=600
CANVASTACK_MAX_FILTER_OPTIONS=1000
CANVASTACK_FILTER_DEBOUNCE=200
```

### For Medium Datasets (10K-100K rows)

```bash
CANVASTACK_FILTER_CACHE_TTL=300
CANVASTACK_MAX_FILTER_OPTIONS=500
CANVASTACK_FILTER_DEBOUNCE=300
CANVASTACK_FILTER_CACHE_ENABLED=true
```

### For Large Datasets (> 100K rows)

```bash
CANVASTACK_FILTER_CACHE_TTL=600
CANVASTACK_MAX_FILTER_OPTIONS=200
CANVASTACK_FILTER_DEBOUNCE=500
CANVASTACK_FILTER_CACHE_ENABLED=true
CANVASTACK_FILTER_CACHE_WARMING=true
```

---

## Troubleshooting

### Issue: Filters are slow

**Solution**:
1. Enable caching: `CANVASTACK_FILTER_CACHE_ENABLED=true`
2. Increase debounce: `CANVASTACK_FILTER_DEBOUNCE=500`
3. Reduce max options: `CANVASTACK_MAX_FILTER_OPTIONS=500`
4. Add database indexes

### Issue: Cascade not working

**Solution**:
1. Check `bidirectional_cascade` is enabled
2. Verify `relate` parameter is set
3. Check browser console for errors
4. Verify API endpoint is accessible

### Issue: Memory errors

**Solution**:
1. Reduce `max_filter_options`
2. Increase `filter_memory_limit`
3. Enable query optimization
4. Add database indexes

### Issue: Infinite cascade loop

**Solution**:
1. Check `max_cascade_depth` setting
2. Review filter relationships
3. Check for circular dependencies
4. Verify cascade logic

---

## Related Documentation

- [Bi-Directional Cascade Guide](../guides/bi-directional-cascade.md)
- [Filter API Reference](../api/table-filters.md)
- [Performance Optimization](../guides/performance-optimization.md)
- [Caching Strategy](../guides/caching-strategy.md)

---

**Last Updated**: 2026-03-03  
**Version**: 1.0.0  
**Status**: Published
