# Configuration Guide

Complete guide to configuring CanvaStack Enhanced for your application.

## Table of Contents

1. [Configuration Files](#configuration-files)
2. [Application Settings](#application-settings)
3. [Cache Configuration](#cache-configuration)
4. [UI Configuration](#ui-configuration)
5. [Performance Settings](#performance-settings)
6. [Environment Variables](#environment-variables)

---

## Configuration Files

CanvaStack uses three main configuration files:

### Main Configuration

**File**: `config/canvastack.php`

Contains application settings, cache configuration, database connections, and performance settings.

### UI Configuration

**File**: `config/canvastack-ui.php`

Contains theme settings, color palette, typography, and layout configuration.

### RBAC Configuration

**File**: `config/canvastack-rbac.php`

Contains role and permission definitions, policy mappings, and authorization settings.

---

## Application Settings

### Basic Settings

```php
// config/canvastack.php

'app' => [
    // Application name
    'name' => env('CANVASTACK_APP_NAME', env('APP_NAME', 'CanvaStack')),
    
    // Application version
    'version' => '1.0.0',
    
    // Timezone
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    
    // Locale
    'locale' => env('APP_LOCALE', 'en'),
    
    // Debug mode
    'debug' => env('APP_DEBUG', false),
],
```

### Environment Variables

```env
CANVASTACK_APP_NAME="My Application"
APP_TIMEZONE="Asia/Jakarta"
APP_LOCALE="id"
```

---

## Cache Configuration

### Cache Settings

```php
// config/canvastack.php

'cache' => [
    // Enable/disable caching
    'enabled' => env('CANVASTACK_ENABLE_CACHING', true),
    
    // Cache driver (file, redis, memcached, database)
    'driver' => env('CANVASTACK_CACHE_DRIVER', 'redis'),
    
    // Default TTL in seconds
    'ttl' => env('CANVASTACK_CACHE_TTL', 3600),
    
    // Cache key prefix
    'prefix' => env('CANVASTACK_CACHE_PREFIX', 'canvastack_'),
    
    // Cache tags support
    'tags' => env('CANVASTACK_CACHE_TAGS', true),
],
```

### Cache Drivers

#### Redis (Recommended)

```env
CANVASTACK_CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CACHE_DB=1
```

**Benefits:**
- Fast performance
- Supports cache tags
- Persistent storage
- Distributed caching

#### File Cache

```env
CANVASTACK_CACHE_DRIVER=file
```

**Benefits:**
- No additional setup
- Works everywhere
- Good for development

**Limitations:**
- Slower than Redis
- No cache tags support
- Not suitable for distributed systems

#### Database Cache

```env
CANVASTACK_CACHE_DRIVER=database
```

**Setup:**
```bash
php artisan cache:table
php artisan migrate
```

**Benefits:**
- Persistent storage
- No additional services
- Supports cache tags

**Limitations:**
- Slower than Redis
- Database overhead

### Cache Strategies

#### Form Validation Cache

```php
'validation_cache' => [
    'enabled' => true,
    'ttl' => 3600, // 1 hour
    'driver' => 'redis',
],
```

#### Table Query Cache

```php
'table_cache' => [
    'enabled' => true,
    'ttl' => 300, // 5 minutes
    'driver' => 'redis',
],
```

#### Ajax Sync Cache

```php
'ajax_cache' => [
    'enabled' => true,
    'ttl' => 300, // 5 minutes
    'driver' => 'redis',
],
```

---

## UI Configuration

### Theme Settings

```php
// config/canvastack-ui.php

'theme' => [
    // Default theme (light, dark, auto)
    'default' => env('CANVASTACK_THEME', 'light'),
    
    // Enable dark mode
    'dark_mode' => env('CANVASTACK_DARK_MODE', true),
    
    // Dark mode strategy (class, media)
    'dark_mode_strategy' => 'class',
    
    // Font family
    'font' => env('CANVASTACK_FONT', 'Inter'),
    
    // Font source (google, local, cdn)
    'font_source' => 'google',
],
```

### Color Palette

```php
'colors' => [
    // Primary color
    'primary' => env('CANVASTACK_COLOR_PRIMARY', '#6366f1'),
    
    // Secondary color
    'secondary' => env('CANVASTACK_COLOR_SECONDARY', '#8b5cf6'),
    
    // Accent color
    'accent' => env('CANVASTACK_COLOR_ACCENT', '#a855f7'),
    
    // Success color
    'success' => '#10b981',
    
    // Warning color
    'warning' => '#f59e0b',
    
    // Error color
    'error' => '#ef4444',
    
    // Info color
    'info' => '#3b82f6',
],
```

### Typography

```php
'typography' => [
    // Font sizes
    'sizes' => [
        'xs' => '0.75rem',
        'sm' => '0.875rem',
        'base' => '1rem',
        'lg' => '1.125rem',
        'xl' => '1.25rem',
        '2xl' => '1.5rem',
        '3xl' => '1.875rem',
        '4xl' => '2.25rem',
    ],
    
    // Font weights
    'weights' => [
        'light' => 300,
        'normal' => 400,
        'medium' => 500,
        'semibold' => 600,
        'bold' => 700,
    ],
],
```

### Layout

```php
'layout' => [
    // Container max width
    'container_max_width' => '1280px',
    
    // Sidebar width
    'sidebar_width' => '256px',
    
    // Header height
    'header_height' => '64px',
    
    // Spacing scale
    'spacing' => [
        'xs' => '0.25rem',
        'sm' => '0.5rem',
        'md' => '1rem',
        'lg' => '1.5rem',
        'xl' => '2rem',
    ],
],
```

### Components

```php
'components' => [
    // Button variants
    'button' => [
        'variants' => ['primary', 'secondary', 'outline', 'ghost'],
        'sizes' => ['sm', 'md', 'lg'],
        'rounded' => 'lg',
    ],
    
    // Card settings
    'card' => [
        'shadow' => 'md',
        'rounded' => 'lg',
        'padding' => 'md',
    ],
    
    // Form settings
    'form' => [
        'label_position' => 'top', // top, left, inline
        'input_rounded' => 'lg',
        'input_size' => 'md',
    ],
    
    // Table settings
    'table' => [
        'striped' => true,
        'hover' => true,
        'bordered' => false,
        'compact' => false,
    ],
],
```

---

## Performance Settings

### Query Optimization

```php
// config/canvastack.php

'performance' => [
    // Enable query logging
    'enable_query_log' => env('CANVASTACK_ENABLE_QUERY_LOG', false),
    
    // Chunk size for large datasets
    'chunk_size' => env('CANVASTACK_CHUNK_SIZE', 100),
    
    // Maximum results per page
    'max_results' => env('CANVASTACK_MAX_RESULTS', 1000),
    
    // Enable eager loading
    'eager_loading' => true,
    
    // Query timeout (seconds)
    'query_timeout' => 30,
],
```

### Asset Optimization

```php
'assets' => [
    // Minify assets in production
    'minify' => env('APP_ENV') === 'production',
    
    // Combine CSS files
    'combine_css' => true,
    
    // Combine JS files
    'combine_js' => true,
    
    // CDN URL
    'cdn_url' => env('CANVASTACK_CDN_URL', null),
    
    // Asset version
    'version' => env('CANVASTACK_ASSET_VERSION', '1.0.0'),
],
```

### Memory Management

```php
'memory' => [
    // Memory limit for operations
    'limit' => env('CANVASTACK_MEMORY_LIMIT', '256M'),
    
    // Enable memory profiling
    'profiling' => env('CANVASTACK_MEMORY_PROFILING', false),
    
    // Garbage collection
    'gc_enabled' => true,
    'gc_probability' => 1,
    'gc_divisor' => 100,
],
```

---

## Environment Variables

### Complete .env Configuration

```env
# CanvaStack Application
CANVASTACK_APP_NAME="My Application"
CANVASTACK_THEME=light
CANVASTACK_DARK_MODE=true

# Cache Configuration
CANVASTACK_ENABLE_CACHING=true
CANVASTACK_CACHE_DRIVER=redis
CANVASTACK_CACHE_TTL=3600
CANVASTACK_CACHE_PREFIX=canvastack_

# Performance
CANVASTACK_ENABLE_QUERY_LOG=false
CANVASTACK_CHUNK_SIZE=100
CANVASTACK_MAX_RESULTS=1000
CANVASTACK_MEMORY_LIMIT=256M

# UI Colors
CANVASTACK_COLOR_PRIMARY=#6366f1
CANVASTACK_COLOR_SECONDARY=#8b5cf6
CANVASTACK_COLOR_ACCENT=#a855f7

# Font
CANVASTACK_FONT=Inter

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CACHE_DB=1

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

---

## Advanced Configuration

### Custom Cache Store

```php
// config/canvastack.php

'cache' => [
    'stores' => [
        'canvastack' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],
    ],
],
```

### Custom Validation Rules

```php
'validation' => [
    // Custom validation rules
    'rules' => [
        'phone' => 'regex:/^[0-9]{10,15}$/',
        'username' => 'regex:/^[a-zA-Z0-9_]{3,20}$/',
    ],
    
    // Custom error messages
    'messages' => [
        'phone.regex' => 'Phone number must be 10-15 digits.',
        'username.regex' => 'Username must be 3-20 alphanumeric characters.',
    ],
],
```

### Custom Date Formats

```php
'formats' => [
    'date' => 'Y-m-d',
    'datetime' => 'Y-m-d H:i:s',
    'time' => 'H:i:s',
    'display_date' => 'M d, Y',
    'display_datetime' => 'M d, Y H:i',
],
```

---

## Configuration Best Practices

### 1. Use Environment Variables

```php
// Good: Use environment variables
'cache_ttl' => env('CANVASTACK_CACHE_TTL', 3600),

// Bad: Hardcode values
'cache_ttl' => 3600,
```

### 2. Provide Sensible Defaults

```php
'chunk_size' => env('CANVASTACK_CHUNK_SIZE', 100),
```

### 3. Cache Configuration in Production

```bash
php artisan config:cache
```

### 4. Separate Concerns

Use different configuration files for different aspects:
- `canvastack.php` - Core functionality
- `canvastack-ui.php` - UI/UX settings
- `canvastack-rbac.php` - Authorization

### 5. Document Custom Settings

```php
/*
|--------------------------------------------------------------------------
| Custom Setting
|--------------------------------------------------------------------------
|
| This setting controls XYZ behavior. Set to true to enable.
| Default: false
|
*/
'custom_setting' => env('CANVASTACK_CUSTOM_SETTING', false),
```

---

## Configuration Examples

### Development Environment

```env
APP_ENV=local
APP_DEBUG=true
CANVASTACK_ENABLE_CACHING=false
CANVASTACK_ENABLE_QUERY_LOG=true
CANVASTACK_CACHE_DRIVER=file
```

### Production Environment

```env
APP_ENV=production
APP_DEBUG=false
CANVASTACK_ENABLE_CACHING=true
CANVASTACK_ENABLE_QUERY_LOG=false
CANVASTACK_CACHE_DRIVER=redis
CANVASTACK_CACHE_TTL=7200
```

### Testing Environment

```env
APP_ENV=testing
APP_DEBUG=true
CANVASTACK_ENABLE_CACHING=false
CANVASTACK_CACHE_DRIVER=array
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

---

## Troubleshooting Configuration

### Issue: Configuration Not Loading

**Solution:**
```bash
php artisan config:clear
php artisan config:cache
```

### Issue: Environment Variables Not Working

**Solution:**
1. Check `.env` file exists
2. Verify variable names match
3. Clear config cache
4. Restart web server

### Issue: Cache Not Working

**Solution:**
1. Check cache driver is installed
2. Verify Redis/Memcached is running
3. Check permissions on cache directory
4. Test cache connection:
```bash
php artisan tinker
Cache::put('test', 'value', 60);
Cache::get('test');
```

---

## Next Steps

- [Quick Start Guide](quick-start.md) - Build your first application
- [Upgrade Guide](upgrade-guide.md) - Upgrade from previous versions
- [Performance Guide](../features/performance.md) - Optimize performance
- [Security Guide](../features/security.md) - Secure your application

---

**Version**: 1.0.0  
**Last Updated**: 2026-02-26  
**Status**: Production Ready
