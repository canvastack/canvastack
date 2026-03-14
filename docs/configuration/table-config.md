# Table Configuration Reference

Complete configuration reference for TanStack Table Multi-Table & Tab System.

## 📦 Overview

This document provides comprehensive reference for all configuration options available for the TableBuilder component, including multi-table support, tab system, connection management, and performance optimization.

All configuration options are defined in:
- **Main Config**: `config/canvastack.php`
- **Table Config**: `config/canvastack-table.php`

Configuration values can be overridden using environment variables in your `.env` file.

---

## 🎯 Quick Reference

### Most Common Settings

```env
# Connection Warning System
CANVASTACK_CONNECTION_WARNING=true
CANVASTACK_CONNECTION_WARNING_METHOD=log

# Tab System
CANVASTACK_TAB_LAZY_LOAD=true
CANVASTACK_TAB_CACHE_ENABLED=true
CANVASTACK_TAB_CACHE_TTL=300

# Performance
CANVASTACK_CACHE_ENABLED=true
CANVASTACK_TABLE_ENGINE=tanstack
```

---

## 📋 Configuration Sections

1. [Connection Warning System](#connection-warning-system)
2. [Tab System Configuration](#tab-system-configuration)
3. [Lazy Loading Configuration](#lazy-loading-configuration)
4. [Cache Configuration](#cache-configuration)
5. [Performance Configuration](#performance-configuration)
6. [Security Configuration](#security-configuration)
7. [Theme Integration](#theme-integration)
8. [Debug Configuration](#debug-configuration)

---

## Connection Warning System

### Overview

The connection warning system alerts developers when manually overriding a database connection that differs from the model's default connection. This helps catch potential configuration errors.


### Configuration Options

#### `connection_warning.enabled`

**Type**: `boolean`  
**Default**: `true`  
**Environment Variable**: `CANVASTACK_CONNECTION_WARNING`

Enable or disable connection override warnings globally.

**Config File** (`config/canvastack.php`):
```php
'table' => [
    'connection_warning' => [
        'enabled' => env('CANVASTACK_CONNECTION_WARNING', true),
    ],
],
```

**Environment Variable** (`.env`):
```env
# Enable warnings (default)
CANVASTACK_CONNECTION_WARNING=true

# Disable warnings
CANVASTACK_CONNECTION_WARNING=false
```

**When to Enable**:
- ✅ Development environment
- ✅ Staging environment
- ✅ When debugging connection issues
- ✅ When working with multiple databases

**When to Disable**:
- ✅ Production environment (if warnings are noisy)
- ✅ When intentionally overriding connections
- ✅ When using legacy code with known overrides

---

#### `connection_warning.method`

**Type**: `string`  
**Default**: `'log'`  
**Environment Variable**: `CANVASTACK_CONNECTION_WARNING_METHOD`  
**Options**: `'log'`, `'toast'`, `'both'`

Specify how connection override warnings are displayed.

**Config File** (`config/canvastack.php`):
```php
'table' => [
    'connection_warning' => [
        'method' => env('CANVASTACK_CONNECTION_WARNING_METHOD', 'log'),
    ],
],
```

**Environment Variable** (`.env`):
```env
# Log to Laravel log file only (default)
CANVASTACK_CONNECTION_WARNING_METHOD=log

# Show toast notification in browser only
CANVASTACK_CONNECTION_WARNING_METHOD=toast

# Both log and toast
CANVASTACK_CONNECTION_WARNING_METHOD=both
```

**Warning Methods**:

1. **`log`** - Write to Laravel log file
   ```
   [2026-03-09 10:30:45] local.WARNING: Connection override detected:
   Model: App\Models\User
   Model Connection: mysql
   Override Connection: pgsql
   This may cause unexpected behavior if the model has connection-specific logic.
   ```

2. **`toast`** - JavaScript notification in browser
   ```javascript
   // Displays toast notification with warning message
   showToast('warning', 'Connection Override: User model expects mysql but using pgsql');
   ```

3. **`both`** - Log + Toast (recommended for development)

**Recommendation**:
- **Development**: `both` - See warnings immediately in browser and have log record
- **Staging**: `log` - Keep warnings in logs without disrupting UI
- **Production**: `log` or disabled - Avoid browser notifications in production

---

### Example Usage

**Scenario**: Model uses MySQL, but you override to PostgreSQL

**Model**:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $connection = 'mysql'; // Model's default connection
}
```

**Controller**:
```php
public function index(TableBuilder $table): View
{
    $table->setModel(new User());
    $table->connection('pgsql'); // Manual override - triggers warning
    $table->setFields(['name:Name', 'email:Email']);
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

**Warning Output** (method = 'log'):
```
[2026-03-09 10:30:45] local.WARNING: Connection override detected:
Model: App\Models\User
Model Connection: mysql
Override Connection: pgsql
This may cause unexpected behavior if the model has connection-specific logic.
```

**Warning Output** (method = 'toast'):
```html
<script>
window.addEventListener('DOMContentLoaded', function() {
    showToast('warning', 'Connection Override: User model expects mysql but using pgsql');
});
</script>
```

---

### Troubleshooting

#### Warning Not Showing

**Problem**: Connection override warning not appearing

**Solutions**:
1. Check if warnings are enabled:
   ```env
   CANVASTACK_CONNECTION_WARNING=true
   ```

2. Check warning method:
   ```env
   CANVASTACK_CONNECTION_WARNING_METHOD=log
   ```

3. Check Laravel log file:
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. Clear config cache:
   ```bash
   php artisan config:clear
   ```

#### Too Many Warnings

**Problem**: Getting too many connection override warnings

**Solutions**:
1. **Option 1**: Disable warnings in production
   ```env
   CANVASTACK_CONNECTION_WARNING=false
   ```

2. **Option 2**: Change method to log only
   ```env
   CANVASTACK_CONNECTION_WARNING_METHOD=log
   ```

3. **Option 3**: Fix the root cause - remove unnecessary overrides
   ```php
   // Instead of overriding connection
   $table->setModel(new User());
   // $table->connection('mysql'); // Remove this
   
   // Let model's connection be used automatically
   ```

---

## Tab System Configuration

### Overview

The tab system allows organizing multiple tables into a tabbed interface with lazy loading for optimal performance.


### Configuration Options

#### `tabs.lazy_load_enabled`

**Type**: `boolean`  
**Default**: `true`  
**Environment Variable**: `CANVASTACK_TAB_LAZY_LOAD`

Enable lazy loading for tabs. When enabled, only the first tab loads immediately; other tabs load when clicked.

**Config File** (`config/canvastack.php`):
```php
'table' => [
    'tabs' => [
        'lazy_load_enabled' => env('CANVASTACK_TAB_LAZY_LOAD', true),
    ],
],
```

**Environment Variable** (`.env`):
```env
# Enable lazy loading (default - recommended)
CANVASTACK_TAB_LAZY_LOAD=true

# Disable lazy loading (load all tabs immediately)
CANVASTACK_TAB_LAZY_LOAD=false
```

**Behavior**:

**When Enabled** (default):
- ✅ First tab loads immediately on page load
- ✅ Other tabs load when user clicks them
- ✅ Loaded tabs are cached (no re-fetch on switch)
- ✅ Faster initial page load
- ✅ Lower server load
- ✅ Better performance for many tabs

**When Disabled**:
- ✅ All tabs load immediately on page load
- ❌ Slower initial page load
- ❌ Higher server load
- ✅ All data available immediately
- ✅ Better for SEO (crawlers see all content)
- ✅ Better for print-friendly pages

**When to Enable**:
- ✅ Many tabs (4+)
- ✅ Large datasets per tab
- ✅ Performance is critical
- ✅ Mobile users
- ✅ Slow database queries

**When to Disable**:
- ✅ Few tabs (2-3)
- ✅ Small datasets per tab
- ✅ Need all data immediately
- ✅ SEO requirements (crawlers)
- ✅ Print-friendly pages

---

#### `tabs.cache_enabled`

**Type**: `boolean`  
**Default**: `true`  
**Environment Variable**: `CANVASTACK_TAB_CACHE_ENABLED`

Enable caching of tab content after loading. When enabled, loaded tabs are cached in Alpine.js state to avoid redundant AJAX requests.

**Config File** (`config/canvastack.php`):
```php
'table' => [
    'tabs' => [
        'cache_enabled' => env('CANVASTACK_TAB_CACHE_ENABLED', true),
    ],
],
```

**Environment Variable** (`.env`):
```env
# Enable tab content caching (default - recommended)
CANVASTACK_TAB_CACHE_ENABLED=true

# Disable tab content caching (reload on every switch)
CANVASTACK_TAB_CACHE_ENABLED=false
```

**Behavior**:

**When Enabled** (default):
- ✅ Tab content cached after first load
- ✅ Switching back to loaded tab is instant
- ✅ No redundant AJAX requests
- ✅ Better performance
- ✅ Lower server load
- ❌ Stale data if source changes

**When Disabled**:
- ✅ Tab content reloaded on every switch
- ✅ Always fresh data
- ❌ Slower tab switching
- ❌ More AJAX requests
- ❌ Higher server load

**When to Enable**:
- ✅ Data doesn't change frequently
- ✅ Performance is critical
- ✅ Users switch tabs frequently
- ✅ Server load is a concern

**When to Disable**:
- ✅ Real-time data (live feeds)
- ✅ Data changes frequently
- ✅ Always need fresh data
- ✅ Users rarely switch tabs

---

#### `tabs.cache_ttl`

**Type**: `integer` (seconds)  
**Default**: `300` (5 minutes)  
**Environment Variable**: `CANVASTACK_TAB_CACHE_TTL`

Time-to-live for tab content cache in seconds. After this duration, cached content is considered stale and will be reloaded.

**Config File** (`config/canvastack.php`):
```php
'table' => [
    'tabs' => [
        'cache_ttl' => env('CANVASTACK_TAB_CACHE_TTL', 300),
    ],
],
```

**Environment Variable** (`.env`):
```env
# 5 minutes (default)
CANVASTACK_TAB_CACHE_TTL=300

# 1 minute (for frequently changing data)
CANVASTACK_TAB_CACHE_TTL=60

# 10 minutes (for static data)
CANVASTACK_TAB_CACHE_TTL=600

# 1 hour (for very static data)
CANVASTACK_TAB_CACHE_TTL=3600
```

**Recommended Values**:

| Data Type | TTL | Reason |
|-----------|-----|--------|
| Real-time data | 30-60s | Data changes frequently |
| Dynamic data | 300s (5min) | Default, good balance |
| Semi-static data | 600s (10min) | Data changes occasionally |
| Static data | 3600s (1hr) | Data rarely changes |
| Reports/Analytics | 3600s+ | Historical data |

**Example**:
```env
# For real-time dashboard
CANVASTACK_TAB_CACHE_TTL=60

# For user management (default)
CANVASTACK_TAB_CACHE_TTL=300

# For reports (static)
CANVASTACK_TAB_CACHE_TTL=3600
```

---

### Example Configurations

#### Configuration 1: High Performance (Default)

**Best for**: Most applications with moderate data changes

```env
CANVASTACK_TAB_LAZY_LOAD=true
CANVASTACK_TAB_CACHE_ENABLED=true
CANVASTACK_TAB_CACHE_TTL=300
```

**Behavior**:
- First tab loads immediately
- Other tabs load on-demand
- Loaded tabs cached for 5 minutes
- Good balance of performance and freshness

---

#### Configuration 2: Real-Time Data

**Best for**: Live dashboards, real-time monitoring

```env
CANVASTACK_TAB_LAZY_LOAD=true
CANVASTACK_TAB_CACHE_ENABLED=false
CANVASTACK_TAB_CACHE_TTL=60
```

**Behavior**:
- First tab loads immediately
- Other tabs load on-demand
- No caching (always fresh data)
- Slower tab switching but always current

---

#### Configuration 3: Static Reports

**Best for**: Historical reports, analytics, archives

```env
CANVASTACK_TAB_LAZY_LOAD=true
CANVASTACK_TAB_CACHE_ENABLED=true
CANVASTACK_TAB_CACHE_TTL=3600
```

**Behavior**:
- First tab loads immediately
- Other tabs load on-demand
- Loaded tabs cached for 1 hour
- Maximum performance for static data

---

#### Configuration 4: SEO-Friendly

**Best for**: Public pages, print-friendly pages

```env
CANVASTACK_TAB_LAZY_LOAD=false
CANVASTACK_TAB_CACHE_ENABLED=true
CANVASTACK_TAB_CACHE_TTL=300
```

**Behavior**:
- All tabs load immediately
- All content visible to crawlers
- Cached for performance
- Slower initial load but SEO-friendly

---

### Troubleshooting

#### Tabs Not Lazy Loading

**Problem**: All tabs load immediately despite lazy loading enabled

**Solutions**:
1. Check configuration:
   ```env
   CANVASTACK_TAB_LAZY_LOAD=true
   ```

2. Clear config cache:
   ```bash
   php artisan config:clear
   ```

3. Check browser console for JavaScript errors

4. Verify Alpine.js is loaded:
   ```html
   <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
   ```

#### Tab Content Not Caching

**Problem**: Tab content reloads every time you switch

**Solutions**:
1. Check cache configuration:
   ```env
   CANVASTACK_TAB_CACHE_ENABLED=true
   ```

2. Check browser console for cache errors

3. Verify Alpine.js state is working:
   ```javascript
   // In browser console
   Alpine.store('tabSystem')
   ```

#### Stale Data in Tabs

**Problem**: Tab shows old data

**Solutions**:
1. **Option 1**: Reduce cache TTL
   ```env
   CANVASTACK_TAB_CACHE_TTL=60
   ```

2. **Option 2**: Disable caching
   ```env
   CANVASTACK_TAB_CACHE_ENABLED=false
   ```

3. **Option 3**: Add manual refresh button
   ```php
   $table->addTabContent('
       <button onclick="refreshTab()">Refresh</button>
   ');
   ```

---

## Lazy Loading Configuration

### Overview

Lazy loading defers loading of tab content until it's needed, improving initial page load performance.


### Configuration Options

#### Global Lazy Loading

Lazy loading for tabs is controlled by `tabs.lazy_load_enabled` (see [Tab System Configuration](#tab-system-configuration)).

#### Per-Table Lazy Loading

You can also control lazy loading per-table instance:

```php
// Disable lazy loading for this specific table
$table->setLazyLoading(false);

// Enable lazy loading for this specific table
$table->setLazyLoading(true);
```

**Priority**: Per-instance setting overrides global configuration.

---

### Performance Impact

#### With Lazy Loading Enabled

**Initial Page Load**:
- ✅ Loads only first tab
- ✅ Faster initial render (< 200ms for 1K rows)
- ✅ Lower memory usage
- ✅ Lower server load

**Tab Switching**:
- First switch: AJAX request (< 500ms for 1K rows)
- Subsequent switches: Instant (cached)

**Total Time** (3 tabs):
- Initial: ~200ms
- Tab 2 first click: ~500ms
- Tab 3 first click: ~500ms
- **Total**: ~1200ms (spread over user interactions)

---

#### With Lazy Loading Disabled

**Initial Page Load**:
- ❌ Loads all tabs
- ❌ Slower initial render (~600ms for 3 tabs with 1K rows each)
- ❌ Higher memory usage
- ❌ Higher server load

**Tab Switching**:
- All switches: Instant (already loaded)

**Total Time** (3 tabs):
- Initial: ~600ms
- Tab switches: 0ms
- **Total**: ~600ms (all upfront)

---

### Recommendation

**Enable lazy loading when**:
- ✅ You have 4+ tabs
- ✅ Each tab has large datasets (1K+ rows)
- ✅ Users typically view 1-2 tabs per session
- ✅ Initial page load speed is critical
- ✅ Server resources are limited

**Disable lazy loading when**:
- ✅ You have 2-3 tabs
- ✅ Each tab has small datasets (< 100 rows)
- ✅ Users typically view all tabs
- ✅ SEO is important (crawlers need all content)
- ✅ Print-friendly pages needed

---

## Cache Configuration

### Overview

Caching improves performance by storing query results, rendered HTML, and filter options.

### Configuration Options

#### `cache.enabled`

**Type**: `boolean`  
**Default**: `true`  
**Environment Variable**: `CANVASTACK_CACHE_ENABLED`

Enable or disable caching globally for all CanvaStack components.

**Config File** (`config/canvastack.php`):
```php
'cache' => [
    'enabled' => env('CANVASTACK_CACHE_ENABLED', true),
],
```

**Environment Variable** (`.env`):
```env
# Enable caching (default - recommended)
CANVASTACK_CACHE_ENABLED=true

# Disable caching (for debugging)
CANVASTACK_CACHE_ENABLED=false
```

---

#### `cache.driver`

**Type**: `string`  
**Default**: `'redis'`  
**Environment Variable**: `CANVASTACK_CACHE_DRIVER`  
**Options**: `'redis'`, `'memcached'`, `'file'`, `'database'`, `'array'`

Specify the cache driver to use.

**Config File** (`config/canvastack.php`):
```php
'cache' => [
    'driver' => env('CANVASTACK_CACHE_DRIVER', 'redis'),
],
```

**Environment Variable** (`.env`):
```env
# Redis (recommended for production)
CANVASTACK_CACHE_DRIVER=redis

# Memcached (alternative for production)
CANVASTACK_CACHE_DRIVER=memcached

# File (for development)
CANVASTACK_CACHE_DRIVER=file

# Array (for testing - no persistence)
CANVASTACK_CACHE_DRIVER=array
```

**Recommendation**:
- **Production**: `redis` or `memcached` (fast, distributed)
- **Development**: `file` (simple, no setup)
- **Testing**: `array` (no persistence, fast)

---

#### `cache.ttl`

**Type**: `array` (seconds per component)  
**Default**: See below

Time-to-live for different component caches.

**Config File** (`config/canvastack.php`):
```php
'cache' => [
    'ttl' => [
        'forms' => 3600,        // 1 hour
        'tables' => 300,        // 5 minutes
        'permissions' => 3600,  // 1 hour
        'views' => 3600,        // 1 hour
        'queries' => 300,       // 5 minutes
    ],
],
```

**Customization**:
```php
// In your config/canvastack.php
'cache' => [
    'ttl' => [
        'forms' => env('CANVASTACK_CACHE_TTL_FORMS', 3600),
        'tables' => env('CANVASTACK_CACHE_TTL_TABLES', 300),
        'permissions' => env('CANVASTACK_CACHE_TTL_PERMISSIONS', 3600),
        'views' => env('CANVASTACK_CACHE_TTL_VIEWS', 3600),
        'queries' => env('CANVASTACK_CACHE_TTL_QUERIES', 300),
    ],
],
```

**Environment Variables** (`.env`):
```env
CANVASTACK_CACHE_TTL_FORMS=3600
CANVASTACK_CACHE_TTL_TABLES=300
CANVASTACK_CACHE_TTL_PERMISSIONS=3600
CANVASTACK_CACHE_TTL_VIEWS=3600
CANVASTACK_CACHE_TTL_QUERIES=300
```

---

#### Per-Table Cache

You can also set cache TTL per table instance:

```php
// Cache for 5 minutes (default)
$table->cache(300);

// Cache for 10 minutes
$table->cache(600);

// Cache for 1 hour
$table->cache(3600);

// Disable cache for this table
$table->cache(0);
```

---

### Cache Tags

CanvaStack uses cache tags for organized cache management.

**Config File** (`config/canvastack.php`):
```php
'cache' => [
    'tags' => [
        'forms' => 'canvastack:forms',
        'tables' => 'canvastack:tables',
        'permissions' => 'canvastack:permissions',
        'views' => 'canvastack:views',
    ],
],
```

**Clear Cache by Tag**:
```php
// Clear all table caches
Cache::tags(['canvastack:tables'])->flush();

// Clear all form caches
Cache::tags(['canvastack:forms'])->flush();

// Clear all CanvaStack caches
Cache::tags(['canvastack'])->flush();
```

---

### Cache Management

#### Clear All Caches

```bash
# Clear all application caches
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear view cache
php artisan view:clear

# Clear route cache
php artisan route:clear
```

#### Clear Specific Caches

```php
// In your code
use Illuminate\Support\Facades\Cache;

// Clear table cache
Cache::tags(['canvastack:tables'])->flush();

// Clear specific table cache
Cache::forget('canvastack_table_users_admin');
```

---

### Troubleshooting

#### Cache Not Working

**Problem**: Changes not reflected, cache seems disabled

**Solutions**:
1. Check if caching is enabled:
   ```env
   CANVASTACK_CACHE_ENABLED=true
   ```

2. Check cache driver is configured:
   ```env
   CACHE_DRIVER=redis
   CANVASTACK_CACHE_DRIVER=redis
   ```

3. Verify Redis/Memcached is running:
   ```bash
   # For Redis
   redis-cli ping
   # Should return: PONG
   
   # For Memcached
   telnet localhost 11211
   ```

4. Clear cache:
   ```bash
   php artisan cache:clear
   ```

#### Stale Data

**Problem**: Seeing old data despite changes

**Solutions**:
1. **Option 1**: Reduce cache TTL
   ```env
   CANVASTACK_CACHE_TTL_TABLES=60
   ```

2. **Option 2**: Clear cache manually
   ```bash
   php artisan cache:clear
   ```

3. **Option 3**: Disable cache for specific table
   ```php
   $table->cache(0); // Disable cache
   ```

4. **Option 4**: Implement cache invalidation
   ```php
   // In your model
   protected static function booted()
   {
       static::saved(function () {
           Cache::tags(['canvastack:tables'])->flush();
       });
   }
   ```

---

## Performance Configuration

### Overview

Performance settings optimize query execution, memory usage, and rendering speed.


### Configuration Options

#### `performance.chunk_size`

**Type**: `integer`  
**Default**: `100`

Number of rows to process in each chunk for large datasets.

**Config File** (`config/canvastack.php`):
```php
'performance' => [
    'chunk_size' => 100,
],
```

**Recommendation**:
- Small datasets (< 1K rows): `100`
- Medium datasets (1K-10K rows): `500`
- Large datasets (10K+ rows): `1000`

---

#### `performance.eager_load`

**Type**: `boolean`  
**Default**: `true`

Enable eager loading by default to prevent N+1 query problems.

**Config File** (`config/canvastack.php`):
```php
'performance' => [
    'eager_load' => true,
],
```

**Recommendation**: Always keep enabled (true)

---

#### `performance.query_cache`

**Type**: `boolean`  
**Default**: `true`

Enable query result caching.

**Config File** (`config/canvastack.php`):
```php
'performance' => [
    'query_cache' => true,
],
```

---

#### `performance.optimize_queries`

**Type**: `boolean`  
**Default**: `true`

Enable automatic query optimization.

**Config File** (`config/canvastack.php`):
```php
'performance' => [
    'optimize_queries' => true,
],
```

---

### Performance Targets

**Config File** (`config/canvastack-table.php`):
```php
'performance' => [
    'targets' => [
        'render_time_ms' => 500,        // Target: < 500ms for 1K rows
        'memory_mb' => 128,             // Target: < 128MB peak memory
        'speed_multiplier_min' => 2.0,  // TanStack should be 2-5x faster
        'speed_multiplier_max' => 5.0,
    ],
],
```

These targets are used for performance validation and monitoring.

---

### Performance Monitoring

#### Enable Monitoring

**Environment Variable** (`.env`):
```env
# Enable performance monitoring (development only)
TABLE_PERFORMANCE_MONITORING=true

# Log performance metrics
TABLE_LOG_METRICS=true
```

**Config File** (`config/canvastack-table.php`):
```php
'performance' => [
    'monitoring' => env('TABLE_PERFORMANCE_MONITORING', env('APP_ENV') === 'local'),
    'log_metrics' => env('TABLE_LOG_METRICS', false),
    'log_channel' => 'performance',
],
```

---

#### Debug Panel

**Environment Variable** (`.env`):
```env
# Enable debug panel (development only)
TABLE_DEBUG_PANEL=true
```

**Config File** (`config/canvastack-table.php`):
```php
'performance' => [
    'debug_panel' => [
        'enabled' => env('TABLE_DEBUG_PANEL', env('APP_ENV') === 'local'),
        'position' => 'bottom-right', // bottom-right, bottom-left, top-right, top-left
        'auto_open' => false,
    ],
],
```

**Debug Panel Features**:
- Render time
- Memory usage
- Query count
- Cache hit ratio
- N+1 query detection

---

## Security Configuration

### Overview

Security settings protect against SQL injection, XSS, and other vulnerabilities.

### Configuration Options

**Config File** (`config/canvastack-table.php`):
```php
'security' => [
    'sql_injection_prevention' => true,  // Use parameterized queries
    'xss_protection' => true,            // Escape output
    'csrf_protection' => true,           // Require CSRF tokens
    'validate_column_names' => true,     // Validate sort/filter columns
    'validate_input' => true,            // Validate all user input
    'allowed_sort_columns' => [],        // Empty = all columns allowed
    'allowed_filter_columns' => [],      // Empty = all columns allowed
],
```

**Recommendation**: Keep all security features enabled (true)

---

### Column Whitelisting

For extra security, whitelist allowed columns for sorting and filtering:

```php
'security' => [
    'allowed_sort_columns' => ['name', 'email', 'created_at'],
    'allowed_filter_columns' => ['status', 'role', 'created_at'],
],
```

**Per-Table Whitelisting**:
```php
$table->setAllowedSortColumns(['name', 'email', 'created_at']);
$table->setAllowedFilterColumns(['status', 'role']);
```

---

## Theme Integration

### Overview

Theme integration ensures tables use theme colors, fonts, and support dark mode.

### Configuration Options

**Config File** (`config/canvastack-table.php`):
```php
'theme' => [
    'enabled' => true,
    'use_theme_colors' => true,  // Use theme colors instead of hardcoded
    'use_theme_fonts' => true,   // Use theme fonts instead of hardcoded
    'dark_mode' => true,         // Enable dark mode support
    'transitions' => true,       // Enable smooth transitions
    'transition_duration' => 200, // Milliseconds
],
```

**Recommendation**: Keep all theme features enabled for consistent UI

---

### Dark Mode Configuration

**Config File** (`config/canvastack-table.php`):
```php
'dark_mode' => [
    'enabled' => true,
    'sync_with_system' => true,          // Sync with system dark mode preference
    'show_toggle_button' => true,        // Show dark mode toggle button
    'persist_preference' => true,        // Persist user preference in localStorage
    'storage_key' => 'canvastack_dark_mode',
    'show_system_indicator' => false,    // Show indicator when using system preference
    'auto_init' => true,                 // Automatically initialize on page load
    'transition_duration' => 200,        // Milliseconds for smooth transitions
],
```

---

## Debug Configuration

### Overview

Debug settings help troubleshoot issues during development.

### Configuration Options

#### `debug`

**Type**: `boolean`  
**Default**: `false` (inherits from `APP_DEBUG`)  
**Environment Variable**: `APP_DEBUG`

Enable debug mode for detailed error messages and logging.

**Environment Variable** (`.env`):
```env
# Enable debug mode (development only)
APP_DEBUG=true

# Disable debug mode (production)
APP_DEBUG=false
```

**Config File** (`config/canvastack-table.php`):
```php
'debug' => env('APP_DEBUG', false),
```

**Warning**: Never enable debug mode in production!

---

### Debug Features

When debug mode is enabled:
- ✅ Detailed error messages
- ✅ SQL query logging
- ✅ Performance metrics
- ✅ Cache hit/miss logging
- ✅ Connection detection logging
- ✅ Warning system active

---

## Complete Configuration Example

### Development Environment

**`.env`**:
```env
# App
APP_ENV=local
APP_DEBUG=true

# Cache
CANVASTACK_CACHE_ENABLED=true
CANVASTACK_CACHE_DRIVER=file

# Connection Warnings
CANVASTACK_CONNECTION_WARNING=true
CANVASTACK_CONNECTION_WARNING_METHOD=both

# Tab System
CANVASTACK_TAB_LAZY_LOAD=true
CANVASTACK_TAB_CACHE_ENABLED=true
CANVASTACK_TAB_CACHE_TTL=60

# Performance
TABLE_PERFORMANCE_MONITORING=true
TABLE_LOG_METRICS=true
TABLE_DEBUG_PANEL=true

# Table Engine
CANVASTACK_TABLE_ENGINE=tanstack
```

---

### Production Environment

**`.env`**:
```env
# App
APP_ENV=production
APP_DEBUG=false

# Cache
CANVASTACK_CACHE_ENABLED=true
CANVASTACK_CACHE_DRIVER=redis

# Connection Warnings
CANVASTACK_CONNECTION_WARNING=false
CANVASTACK_CONNECTION_WARNING_METHOD=log

# Tab System
CANVASTACK_TAB_LAZY_LOAD=true
CANVASTACK_TAB_CACHE_ENABLED=true
CANVASTACK_TAB_CACHE_TTL=300

# Performance
TABLE_PERFORMANCE_MONITORING=false
TABLE_LOG_METRICS=false
TABLE_DEBUG_PANEL=false

# Table Engine
CANVASTACK_TABLE_ENGINE=tanstack
```

---

### Staging Environment

**`.env`**:
```env
# App
APP_ENV=staging
APP_DEBUG=false

# Cache
CANVASTACK_CACHE_ENABLED=true
CANVASTACK_CACHE_DRIVER=redis

# Connection Warnings
CANVASTACK_CONNECTION_WARNING=true
CANVASTACK_CONNECTION_WARNING_METHOD=log

# Tab System
CANVASTACK_TAB_LAZY_LOAD=true
CANVASTACK_TAB_CACHE_ENABLED=true
CANVASTACK_TAB_CACHE_TTL=300

# Performance
TABLE_PERFORMANCE_MONITORING=true
TABLE_LOG_METRICS=true
TABLE_DEBUG_PANEL=false

# Table Engine
CANVASTACK_TABLE_ENGINE=tanstack
```

---

## Environment Variable Reference

### Quick Reference Table

| Variable | Type | Default | Description |
|----------|------|---------|-------------|
| `CANVASTACK_CONNECTION_WARNING` | boolean | `true` | Enable connection override warnings |
| `CANVASTACK_CONNECTION_WARNING_METHOD` | string | `log` | Warning method: log, toast, both |
| `CANVASTACK_TAB_LAZY_LOAD` | boolean | `true` | Enable lazy loading for tabs |
| `CANVASTACK_TAB_CACHE_ENABLED` | boolean | `true` | Enable tab content caching |
| `CANVASTACK_TAB_CACHE_TTL` | integer | `300` | Tab cache TTL in seconds |
| `CANVASTACK_CACHE_ENABLED` | boolean | `true` | Enable global caching |
| `CANVASTACK_CACHE_DRIVER` | string | `redis` | Cache driver: redis, memcached, file |
| `CANVASTACK_TABLE_ENGINE` | string | `tanstack` | Table engine: datatables, tanstack |
| `TABLE_PERFORMANCE_MONITORING` | boolean | `false` | Enable performance monitoring |
| `TABLE_LOG_METRICS` | boolean | `false` | Log performance metrics |
| `TABLE_DEBUG_PANEL` | boolean | `false` | Show debug panel |
| `APP_DEBUG` | boolean | `false` | Enable debug mode |

---

## Troubleshooting Guide

### Configuration Not Applied

**Problem**: Configuration changes not taking effect

**Solutions**:
1. Clear config cache:
   ```bash
   php artisan config:clear
   ```

2. Restart web server:
   ```bash
   # For Laravel Sail
   sail restart
   
   # For PHP built-in server
   # Stop and restart: php artisan serve
   ```

3. Check `.env` file syntax:
   ```env
   # ✅ CORRECT
   CANVASTACK_TAB_LAZY_LOAD=true
   
   # ❌ WRONG (no spaces around =)
   CANVASTACK_TAB_LAZY_LOAD = true
   ```

4. Verify environment variable is loaded:
   ```php
   dd(env('CANVASTACK_TAB_LAZY_LOAD'));
   ```

---

### Performance Issues

**Problem**: Tables loading slowly

**Solutions**:
1. Enable caching:
   ```env
   CANVASTACK_CACHE_ENABLED=true
   CANVASTACK_CACHE_DRIVER=redis
   ```

2. Enable lazy loading:
   ```env
   CANVASTACK_TAB_LAZY_LOAD=true
   ```

3. Reduce cache TTL for testing:
   ```env
   CANVASTACK_TAB_CACHE_TTL=60
   ```

4. Enable performance monitoring:
   ```env
   TABLE_PERFORMANCE_MONITORING=true
   TABLE_DEBUG_PANEL=true
   ```

5. Check for N+1 queries:
   ```php
   $table->eager(['relation1', 'relation2']);
   ```

---

### Cache Issues

**Problem**: Stale data or cache not working

**Solutions**:
1. Clear all caches:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

2. Check cache driver is running:
   ```bash
   # Redis
   redis-cli ping
   
   # Memcached
   telnet localhost 11211
   ```

3. Verify cache configuration:
   ```env
   CACHE_DRIVER=redis
   CANVASTACK_CACHE_DRIVER=redis
   CANVASTACK_CACHE_ENABLED=true
   ```

4. Check cache permissions (for file driver):
   ```bash
   chmod -R 775 storage/framework/cache
   ```

---

## Best Practices

### 1. Environment-Specific Configuration

Use different settings per environment:

**Development**:
- Enable debug mode
- Enable all warnings
- Use file cache
- Enable performance monitoring

**Staging**:
- Disable debug mode
- Enable log warnings only
- Use Redis cache
- Enable performance monitoring

**Production**:
- Disable debug mode
- Disable or log warnings only
- Use Redis cache
- Disable performance monitoring

---

### 2. Cache Strategy

**Static Data** (rarely changes):
```env
CANVASTACK_TAB_CACHE_TTL=3600  # 1 hour
```

**Dynamic Data** (changes frequently):
```env
CANVASTACK_TAB_CACHE_TTL=60    # 1 minute
```

**Real-Time Data** (always fresh):
```env
CANVASTACK_TAB_CACHE_ENABLED=false
```

---

### 3. Performance Optimization

**For Many Tabs** (4+):
```env
CANVASTACK_TAB_LAZY_LOAD=true
CANVASTACK_TAB_CACHE_ENABLED=true
```

**For Large Datasets** (10K+ rows):
```php
'performance' => [
    'chunk_size' => 1000,
    'eager_load' => true,
    'query_cache' => true,
],
```

---

### 4. Security

**Always Enable**:
```php
'security' => [
    'sql_injection_prevention' => true,
    'xss_protection' => true,
    'csrf_protection' => true,
    'validate_column_names' => true,
    'validate_input' => true,
],
```

**For Extra Security**:
```php
'security' => [
    'allowed_sort_columns' => ['name', 'email', 'created_at'],
    'allowed_filter_columns' => ['status', 'role'],
],
```

---

## Related Documentation

- [Tab System Usage Guide](../guides/tab-system-usage.md) - How to use tabs
- [Multi-Table Usage Guide](../guides/multi-table-usage.md) - Multiple tables without tabs
- [Connection Detection Guide](../guides/connection-detection.md) - Database connection management
- [Performance Optimization Guide](../guides/performance-optimization.md) - Performance tips
- [TanStack Table API Reference](../api/table-multi-tab.md) - Complete API documentation

---

## Resources

- [Laravel Configuration Documentation](https://laravel.com/docs/configuration)
- [Laravel Caching Documentation](https://laravel.com/docs/cache)
- [Redis Documentation](https://redis.io/documentation)
- [Memcached Documentation](https://memcached.org/)

---

**Last Updated**: 2026-03-09  
**Version**: 1.0.0  
**Status**: Published
