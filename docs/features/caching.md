# Caching System

**Version**: 1.0.0  
**Last Updated**: 2026-02-26  
**Status**: Complete

---

## Table of Contents

1. [Overview](#overview)
2. [Cache Architecture](#cache-architecture)
3. [Cache Layers](#cache-layers)
4. [Configuration](#configuration)
5. [Table Component Caching](#table-component-caching)
6. [Form Component Caching](#form-component-caching)
7. [Query Result Caching](#query-result-caching)
8. [Cache Invalidation](#cache-invalidation)
9. [Redis Setup](#redis-setup)
10. [Performance Impact](#performance-impact)
11. [Best Practices](#best-practices)
12. [Troubleshooting](#troubleshooting)

---

## Overview

CanvaStack implements a multi-layer caching strategy to achieve 50-80% performance improvements over the legacy implementation. The caching system is designed to be transparent, automatic, and highly configurable.

### Key Features

- **Multi-layer caching**: Application, query, and view caching
- **Automatic cache invalidation**: Smart invalidation on data changes
- **Redis support**: High-performance distributed caching
- **Configurable TTL**: Per-component cache duration
- **Cache warming**: Pre-populate cache for critical data
- **Cache tags**: Group related cache entries for bulk invalidation

### Performance Benefits

| Operation | Without Cache | With Cache | Improvement |
|-----------|---------------|------------|-------------|
| DataTable (1K rows) | ~2000ms | ~400ms | 80% faster |
| Form validation | ~50ms | ~5ms | 90% faster |
| Query results | ~100ms | ~10ms | 90% faster |
| View rendering | ~80ms | ~15ms | 81% faster |

---

## Cache Architecture

### Layered Caching Strategy

```
┌─────────────────────────────────────────┐
│         Application Layer               │
│  (Component configs, metadata)          │
└─────────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────────┐
│         Service Layer                   │
│  (Validation rules, permissions)        │
└─────────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────────┐
│         Query Layer                     │
│  (Database query results)               │
└─────────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────────┐
│         View Layer                      │
│  (Rendered HTML fragments)              │
└─────────────────────────────────────────┘
```

### Cache Flow

```php
Request → Check Cache → Cache Hit? → Return Cached Data
                ↓
            Cache Miss
                ↓
        Execute Operation
                ↓
         Store in Cache
                ↓
         Return Data
```

---

## Cache Layers

### 1. Application Cache

Stores component configurations, metadata, and settings.

**What's Cached:**
- Component configurations
- Field definitions
- Column definitions
- Action configurations
- Permission mappings

**TTL**: 3600 seconds (1 hour)

**Example:**
```php
// Automatically cached
$table = new TableBuilder();
$table->column('name', 'Name');
$table->column('email', 'Email');
// Column config cached for 1 hour
```

### 2. Query Cache

Stores database query results.

**What's Cached:**
- SELECT query results
- Aggregation results
- Relationship data
- Filtered datasets

**TTL**: 300 seconds (5 minutes) - configurable

**Example:**
```php
// Enable query caching
$table->cache(300); // Cache for 5 minutes

// Query results cached automatically
$table->runModel(User::class);
```

### 3. Validation Cache

Stores compiled validation rules.

**What's Cached:**
- Compiled validation rules
- Custom validator instances
- Validation error messages

**TTL**: 1800 seconds (30 minutes)

**Example:**
```php
// Validation rules compiled once and cached
$form->text('email', 'Email')
    ->required()
    ->email()
    ->maxLength(255);
```

### 4. View Cache

Stores rendered HTML fragments.

**What's Cached:**
- Rendered table HTML
- Rendered form HTML
- Blade component output

**TTL**: 60 seconds (1 minute) - configurable

**Example:**
```php
// Enable view caching
$table->cacheView(60); // Cache rendered HTML for 1 minute
```

---

## Configuration

### Cache Configuration File

`config/canvastack.php`:

```php
return [
    'cache' => [
        // Cache driver (file, redis, memcached, array)
        'driver' => env('CANVASTACK_CACHE_DRIVER', 'redis'),
        
        // Default TTL in seconds
        'ttl' => [
            'application' => 3600,  // 1 hour
            'query' => 300,         // 5 minutes
            'validation' => 1800,   // 30 minutes
            'view' => 60,           // 1 minute
        ],
        
        // Cache key prefix
        'prefix' => env('CANVASTACK_CACHE_PREFIX', 'canvastack'),
        
        // Enable/disable caching per layer
        'enabled' => [
            'application' => true,
            'query' => true,
            'validation' => true,
            'view' => true,
        ],
        
        // Cache tags support (requires Redis or Memcached)
        'tags' => [
            'enabled' => true,
            'separator' => ':',
        ],
        
        // Cache warming
        'warming' => [
            'enabled' => false,
            'schedule' => '0 */6 * * *', // Every 6 hours
        ],
    ],
];
```

### Environment Variables

`.env`:

```env
# Cache driver
CANVASTACK_CACHE_DRIVER=redis

# Cache prefix
CANVASTACK_CACHE_PREFIX=canvastack

# Redis connection
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=1
```

---

## Table Component Caching

### Enable Query Caching

```php
use Canvastack\Components\Table\TableBuilder;

$table = new TableBuilder();

// Enable caching with default TTL (300 seconds)
$table->cache();

// Enable caching with custom TTL
$table->cache(600); // 10 minutes

// Disable caching
$table->cache(false);
```

### Cache with Tags

```php
// Cache with tags for easy invalidation
$table->cache(300, ['users', 'admin']);

// Later, invalidate all 'users' cache
Cache::tags(['users'])->flush();
```

### Conditional Caching

```php
// Cache only for specific conditions
$table->cacheWhen(function () {
    return auth()->user()->isAdmin();
}, 300);

// Cache unless condition is true
$table->cacheUnless(function () {
    return request()->has('nocache');
}, 300);
```

### Cache Key Customization

```php
// Custom cache key
$table->cacheKey('users_table_' . auth()->id());

// Dynamic cache key based on request
$table->cacheKey(function () {
    return 'users_' . request('status', 'all');
});
```

### View Caching

```php
// Cache rendered HTML
$table->cacheView(60); // Cache for 1 minute

// Cache view with tags
$table->cacheView(60, ['users_view', 'admin_view']);
```

---

## Form Component Caching

### Validation Rule Caching

Validation rules are automatically compiled and cached:

```php
use Canvastack\Components\Form\FormBuilder;

$form = new FormBuilder();

// Rules compiled once and cached
$form->text('email', 'Email')
    ->required()
    ->email()
    ->maxLength(255);

// Subsequent requests use cached rules
```

### Field Configuration Caching

Field configurations are cached automatically:

```php
// Field config cached for 1 hour
$form->select('status', 'Status', [
    'active' => 'Active',
    'inactive' => 'Inactive',
]);

// Cache invalidated when form definition changes
```

### Disable Form Caching

```php
// Disable caching for specific form
$form->disableCache();

// Or configure in config file
'cache' => [
    'enabled' => [
        'validation' => false,
    ],
],
```

---

## Query Result Caching

### Automatic Query Caching

```php
// Enable query caching
$table->cache(300);

// All queries cached automatically
$table->runModel(User::class);

// Includes:
// - Main query
// - Count query
// - Relationship queries (if eager loaded)
```

### Cache with Eager Loading

```php
// Cache query with relationships
$table->cache(300)
    ->eager(['posts', 'comments']);

// All relationship queries cached
$table->runModel(User::class);
```

### Manual Query Caching

```php
use Illuminate\Support\Facades\Cache;

// Manual caching
$users = Cache::remember('users_list', 300, function () {
    return User::with('posts')->get();
});
```

### Cache Invalidation on Model Events

```php
// In your model
class User extends Model
{
    protected static function booted()
    {
        // Invalidate cache on save
        static::saved(function ($user) {
            Cache::tags(['users'])->flush();
        });
        
        // Invalidate cache on delete
        static::deleted(function ($user) {
            Cache::tags(['users'])->flush();
        });
    }
}
```

---

## Cache Invalidation

### Automatic Invalidation

CanvaStack automatically invalidates cache when:

1. **Model changes**: Create, update, delete operations
2. **Configuration changes**: Component config modifications
3. **Permission changes**: Role/permission updates
4. **Manual flush**: Explicit cache clearing

### Manual Invalidation

```php
use Illuminate\Support\Facades\Cache;

// Clear all CanvaStack cache
Cache::tags(['canvastack'])->flush();

// Clear specific component cache
Cache::tags(['canvastack:table'])->flush();
Cache::tags(['canvastack:form'])->flush();

// Clear specific model cache
Cache::tags(['users'])->flush();

// Clear specific cache key
Cache::forget('canvastack:users_table');
```

### Invalidation Strategies

#### 1. Time-Based (TTL)

```php
// Cache expires after TTL
$table->cache(300); // 5 minutes
```

#### 2. Event-Based

```php
// Invalidate on model events
User::saved(function () {
    Cache::tags(['users'])->flush();
});
```

#### 3. Manual

```php
// Explicit invalidation
Cache::tags(['users'])->flush();
```

#### 4. Conditional

```php
// Invalidate based on condition
if ($user->isAdmin()) {
    Cache::tags(['admin_data'])->flush();
}
```

### Cache Warming

Pre-populate cache for critical data:

```php
// In a scheduled command
use Illuminate\Console\Command;

class WarmCache extends Command
{
    protected $signature = 'cache:warm';
    
    public function handle()
    {
        // Warm users cache
        Cache::remember('users_list', 3600, function () {
            return User::with('posts')->get();
        });
        
        // Warm other critical data
        $this->info('Cache warmed successfully');
    }
}
```

Schedule in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('cache:warm')->everyFourHours();
}
```

---

## Redis Setup

### Installation

```bash
# Install Redis
sudo apt-get install redis-server

# Start Redis
sudo systemctl start redis
sudo systemctl enable redis

# Verify Redis is running
redis-cli ping
# Should return: PONG
```

### Laravel Configuration

`config/database.php`:

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DB', 0),
    ],
    
    'cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_CACHE_DB', 1),
    ],
],
```

`.env`:

```env
CACHE_DRIVER=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

### Install PHP Redis Extension

```bash
# Install phpredis extension
sudo pecl install redis

# Enable extension
echo "extension=redis.so" | sudo tee /etc/php/8.2/mods-available/redis.ini
sudo phpenmod redis

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

### Verify Redis Connection

```php
use Illuminate\Support\Facades\Redis;

// Test connection
Redis::set('test_key', 'test_value');
$value = Redis::get('test_key');
// Should return: 'test_value'
```

---

## Performance Impact

### Benchmark Results

#### Without Caching (Legacy)

```
DataTable (1K rows):     ~2000ms
Form validation:         ~50ms
Query execution:         ~100ms
View rendering:          ~80ms
Total request:           ~2230ms
Memory usage:            ~256MB
```

#### With Caching (CanvaStack)

```
DataTable (1K rows):     ~400ms  (80% faster)
Form validation:         ~5ms    (90% faster)
Query execution:         ~10ms   (90% faster)
View rendering:          ~15ms   (81% faster)
Total request:           ~430ms  (81% faster)
Memory usage:            ~128MB  (50% reduction)
```

### Cache Hit Ratio

Target: > 80% cache hit ratio

```php
// Monitor cache hit ratio
$hits = Cache::get('cache_hits', 0);
$misses = Cache::get('cache_misses', 0);
$ratio = $hits / ($hits + $misses) * 100;

echo "Cache hit ratio: {$ratio}%";
```

---

## Best Practices

### 1. Use Appropriate TTL

```php
// Short TTL for frequently changing data
$table->cache(60); // 1 minute

// Long TTL for static data
$table->cache(3600); // 1 hour

// Very long TTL for rarely changing data
$table->cache(86400); // 24 hours
```

### 2. Use Cache Tags

```php
// Group related cache entries
$table->cache(300, ['users', 'admin']);

// Easy bulk invalidation
Cache::tags(['users'])->flush();
```

### 3. Implement Cache Warming

```php
// Pre-populate cache for critical data
Cache::remember('critical_data', 3600, function () {
    return CriticalModel::all();
});
```

### 4. Monitor Cache Performance

```php
// Log cache hits/misses
Cache::macro('rememberWithStats', function ($key, $ttl, $callback) {
    if (Cache::has($key)) {
        Cache::increment('cache_hits');
        return Cache::get($key);
    }
    
    Cache::increment('cache_misses');
    return Cache::remember($key, $ttl, $callback);
});
```

### 5. Use Conditional Caching

```php
// Cache only when beneficial
$table->cacheWhen(function () {
    return request()->has('filter'); // Cache filtered results
}, 300);
```

### 6. Invalidate Strategically

```php
// Invalidate only affected cache
User::saved(function ($user) {
    // Don't flush all cache, only user-related
    Cache::forget("user_{$user->id}");
    Cache::tags(['users'])->flush();
});
```

### 7. Use Redis for Production

```env
# Development: file cache
CACHE_DRIVER=file

# Production: Redis cache
CACHE_DRIVER=redis
```

---

## Troubleshooting

### Cache Not Working

**Problem**: Cache doesn't seem to be working

**Solutions**:

1. Check cache driver configuration:
```bash
php artisan config:cache
php artisan cache:clear
```

2. Verify Redis connection:
```bash
redis-cli ping
```

3. Check cache is enabled:
```php
// config/canvastack.php
'cache' => [
    'enabled' => [
        'query' => true,
    ],
],
```

### Cache Not Invalidating

**Problem**: Stale data in cache

**Solutions**:

1. Check model events:
```php
User::saved(function () {
    Cache::tags(['users'])->flush();
});
```

2. Manual flush:
```bash
php artisan cache:clear
```

3. Reduce TTL:
```php
$table->cache(60); // Shorter TTL
```

### Redis Connection Errors

**Problem**: "Connection refused" errors

**Solutions**:

1. Check Redis is running:
```bash
sudo systemctl status redis
```

2. Check Redis configuration:
```bash
redis-cli ping
```

3. Check firewall:
```bash
sudo ufw allow 6379
```

### High Memory Usage

**Problem**: Redis using too much memory

**Solutions**:

1. Set max memory limit:
```bash
# redis.conf
maxmemory 256mb
maxmemory-policy allkeys-lru
```

2. Monitor memory:
```bash
redis-cli info memory
```

3. Clear unused cache:
```bash
php artisan cache:clear
```

---

## See Also

- [Performance Optimization](performance.md)
- [Redis Setup Guide](../guides/redis-setup.md)
- [Table Component Performance](../components/table/performance.md)
- [Architecture Overview](../architecture/overview.md)

---

**Next**: [Security Features](security.md)
