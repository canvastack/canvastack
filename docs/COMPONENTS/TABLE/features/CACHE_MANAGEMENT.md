# Cache Management

**Version:** 2.0.0  
**Phases:** 0, 3, 4  
**Status:** ✅ Completed (19/19 options)

---

## Overview

Comprehensive caching system with multi-layer architecture, intelligent invalidation, monitoring, and warming capabilities for optimal performance.

## Architecture

### Multi-Layer Caching

```
┌─────────────────────────────────────────────────────┐
│  L1: In-Memory Cache (Per-Request)                 │
│  - Fastest access                                   │
│  - No serialization overhead                        │
│  - Automatic cleanup after request                  │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│  L2: Persistent Cache (Redis/Memcached/File)       │
│  - Shared across requests                           │
│  - Configurable TTL                                 │
│  - Survives application restart                     │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│  L3: Database (Source of Truth)                     │
│  - Authoritative data                               │
│  - Queried only on cache miss                       │
└─────────────────────────────────────────────────────┘
```

---

## Cache Types

### 1. Schema Cache

**Purpose:** Store table schema information  
**TTL:** 3600 seconds (1 hour)  
**Key Pattern:** `{prefix}schema_{connection}_{table}`

```php
// Get cached schema
$schema = canvastack_table_get_cached_schema('users', 'mysql');

// Cache schema manually
canvastack_table_cache_schema('users', $schema, 'mysql', 3600);

// Invalidate schema cache
canvastack_table_invalidate_cache('users', 'schema', 'mysql');
```

**What's Cached:**
- Column names and types
- Primary keys
- Foreign keys
- Indexes

---

### 2. Validation Cache

**Purpose:** Store validation data (column listings)  
**TTL:** 3600 seconds (1 hour)  
**Key Pattern:** `{prefix}validation_{connection}_{table}_columns`

```php
// Automatically cached during validation
$validated = $datatables->validateColumnName('email', 'users');

// Cache contains: ['id', 'name', 'email', 'created_at', ...]
```

**What's Cached:**
- Column listings for validation
- Image validation results
- Table existence checks

---

### 3. Config Cache

**Purpose:** Store table configuration  
**TTL:** 3600 seconds (1 hour)  
**Key Pattern:** `{prefix}config_{connection}_{table}`

```php
// Column label caching
$label = $builder->getColumnLabel('user_name');
// Cached as: "User Name"
```

**What's Cached:**
- Column labels
- Display configurations
- Format settings

---

### 4. Relationships Cache

**Purpose:** Store relationship definitions  
**TTL:** 1800 seconds (30 minutes)  
**Key Pattern:** `{prefix}relationships_{table}_{hash}`

```php
// Automatically cached during eager loading
$model->with(['profile', 'posts.comments']);

// Cache contains: ['profile', 'posts.comments']
```

**What's Cached:**
- Relationship names
- Nested relationship paths
- Eager loading configurations

---

### 5. Query Results Cache

**Purpose:** Store query results  
**TTL:** Configurable  
**Key Pattern:** `{prefix}query_{table}`

```php
// Enable query caching
'cache' => [
    'query_results' => [
        'enabled' => true,
        'ttl' => 300, // 5 minutes
    ],
],
```

---

### 6. Formula Results Cache

**Purpose:** Store calculated formula results  
**TTL:** Configurable  
**Key Pattern:** `{prefix}formula_{table}`

```php
// Automatically cached in Formula.php
$result = $formula->calculate($expression, $data);
```

---

## Cache Invalidation

### Strategies

#### 1. Immediate Invalidation (Default)

Clears cache immediately when triggered.

```php
// config/canvastack.cache.php
return [
    'invalidation' => [
        'enabled' => true,
        'strategy' => 'immediate', // Default
    ],
];

// Usage
canvastack_table_invalidate_cache('users', 'all', 'mysql');
// Cache cleared immediately
```

**Use Cases:**
- Data modifications
- Schema changes
- Critical updates

---

#### 2. Lazy Invalidation

Marks cache as stale, clears on next access.

```php
// config/canvastack.cache.php
return [
    'invalidation' => [
        'strategy' => 'lazy',
    ],
];

// Usage
canvastack_table_invalidate_cache('users', 'all', 'mysql');
// Cache marked as stale, cleared on next read
```

**Use Cases:**
- Non-critical updates
- Batch operations
- Reduced write load

---

#### 3. Scheduled Invalidation

Queues invalidation for scheduled execution.

```php
// config/canvastack.cache.php
return [
    'invalidation' => [
        'strategy' => 'scheduled',
    ],
];

// Usage
canvastack_table_invalidate_cache('users', 'all', 'mysql');
// Logged for scheduled job to process
```

**Use Cases:**
- Off-peak processing
- Bulk invalidations
- Coordinated cache clearing

---

### Cascade Invalidation

Automatically invalidates related caches.

```php
// config/canvastack.cache.php
return [
    'invalidation' => [
        'cascade_invalidation' => true,
    ],
];

// Invalidate all related caches
canvastack_table_invalidate_cache('users', 'all', 'mysql');

// Clears:
// - schema cache
// - config cache
// - validation cache
// - relationships cache
// - query cache
// - formula cache
```

---

### Manual Invalidation

```php
// Invalidate specific type
canvastack_table_invalidate_cache('users', 'schema', 'mysql');

// Invalidate all types
canvastack_table_invalidate_cache('users', 'all', 'mysql');

// Invalidate multiple tables
foreach (['users', 'posts', 'comments'] as $table) {
    canvastack_table_invalidate_cache($table, 'all', 'mysql');
}
```

---

## Cache Monitoring

### Enable Monitoring

```php
// config/canvastack.cache.php
return [
    'monitoring' => [
        'enabled' => true,
        'log_hits_misses' => true,
        'log_channel' => 'daily',
        'track_statistics' => true,
        'statistics_ttl' => 86400, // 24 hours
    ],
];
```

### Automatic Monitoring

Monitoring is automatically integrated into all cache operations:

```php
// Cache hit
$cached = Cache::get($key);
if ($cached !== null) {
    canvastack_table_cache_monitor('get', $key, true); // HIT
}

// Cache miss
canvastack_table_cache_monitor('get', $key, false); // MISS
```

### View Statistics

```php
// Get today's statistics
$stats = Cache::get('cache_stats_' . date('Y-m-d'));

// Output:
[
    'hits' => 1250,
    'misses' => 150,
    'operations' => [
        'get' => 1400,
        'put' => 150,
        'forget' => 50,
    ],
]

// Calculate hit rate
$hitRate = ($stats['hits'] / ($stats['hits'] + $stats['misses'])) * 100;
// 89.3% hit rate
```

### Log Format

```
[2026-04-04 10:30:00] daily.INFO: Cache get: HIT
{
    "key": "canvastack_schema_mysql_users",
    "timestamp": "2026-04-04 10:30:00"
}

[2026-04-04 10:30:05] daily.INFO: Cache get: MISS
{
    "key": "canvastack_schema_mysql_posts",
    "timestamp": "2026-04-04 10:30:05"
}
```

---

## Cache Warming

### Manual Warming

```bash
# Warm all configured tables
php artisan canvastack:warm-cache

# Warm specific tables
php artisan canvastack:warm-cache --tables=users,posts,comments

# Force warming (ignore config)
php artisan canvastack:warm-cache --force
```

### Boot Warming

Automatically warms cache when application boots.

```php
// config/canvastack.cache.php
return [
    'warming' => [
        'enabled' => true,
        'on_boot' => true,
        'tables' => ['users', 'posts', 'comments'],
    ],
];
```

**Features:**
- Only runs in production
- Asynchronous (doesn't block boot)
- Runs after response sent
- Error handling with logging

### Scheduled Warming

Automatically warms cache on schedule.

```php
// config/canvastack.cache.php
return [
    'warming' => [
        'enabled' => true,
        'scheduled' => true,
        'schedule' => '0 */6 * * *', // Every 6 hours
        'tables' => ['users', 'posts', 'comments'],
    ],
];
```

**Cron Expression Examples:**
- `'0 */6 * * *'` - Every 6 hours
- `'0 0 * * *'` - Daily at midnight
- `'0 */1 * * *'` - Every hour
- `'*/30 * * * *'` - Every 30 minutes

**Note:** Requires Laravel scheduler in crontab:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## Configuration Reference

### Complete Configuration

```php
// config/canvastack.cache.php
return [
    // Global settings
    'enabled' => true,
    'store' => 'redis', // redis, memcached, file
    'ttl' => 3600,
    'prefix' => 'canvastack_',
    'tags' => ['canvastack', 'tables'],
    
    // Schema cache
    'table_schema' => [
        'enabled' => true,
        'ttl' => 3600,
        'key_prefix' => 'schema_',
    ],
    
    // Validation cache
    'validation' => [
        'enabled' => true,
        'ttl' => 3600,
        'key_prefix' => 'validation_',
    ],
    
    // Config cache
    'config' => [
        'enabled' => true,
        'ttl' => 3600,
        'key_prefix' => 'config_',
    ],
    
    // Relationships cache
    'relationships' => [
        'cache_definitions' => true,
        'ttl' => 1800,
        'key_prefix' => 'relationships_',
    ],
    
    // Query results cache
    'query_results' => [
        'enabled' => false,
        'ttl' => 300,
        'key_prefix' => 'query_',
    ],
    
    // Formula results cache
    'formula_results' => [
        'enabled' => true,
        'ttl' => 600,
        'key_prefix' => 'formula_',
    ],
    
    // Invalidation
    'invalidation' => [
        'enabled' => true,
        'strategy' => 'immediate', // immediate, lazy, scheduled
        'cascade_invalidation' => true,
    ],
    
    // Monitoring
    'monitoring' => [
        'enabled' => true,
        'log_hits_misses' => true,
        'log_channel' => 'daily',
        'track_statistics' => true,
        'statistics_ttl' => 86400,
    ],
    
    // Warming
    'warming' => [
        'enabled' => true,
        'on_boot' => true,
        'scheduled' => true,
        'schedule' => '0 */6 * * *',
        'tables' => ['users', 'posts', 'comments'],
    ],
    
    // Development
    'development' => [
        'disable_in_dev' => false,
        'log_operations' => true,
    ],
];
```

---

## Best Practices

### 1. Choose Right Cache Store

```php
// Production: Use Redis or Memcached
'store' => 'redis',

// Development: Use file or array
'store' => env('CACHE_DRIVER', 'file'),
```

### 2. Set Appropriate TTLs

```php
// Frequently changing data: Short TTL
'query_results' => ['ttl' => 300], // 5 minutes

// Rarely changing data: Long TTL
'table_schema' => ['ttl' => 3600], // 1 hour
```

### 3. Enable Monitoring in Production

```php
'monitoring' => [
    'enabled' => true,
    'track_statistics' => true,
],
```

### 4. Use Cache Warming

```php
// Warm cache before peak hours
'warming' => [
    'scheduled' => true,
    'schedule' => '0 7 * * *', // 7 AM daily
],
```

### 5. Invalidate on Data Changes

```php
// After model update
User::updated(function ($user) {
    canvastack_table_invalidate_cache('users', 'all');
});
```

---

## Performance Tips

### 1. Monitor Hit Rate

Target: >80% hit rate

```php
$stats = Cache::get('cache_stats_' . date('Y-m-d'));
$hitRate = ($stats['hits'] / ($stats['hits'] + $stats['misses'])) * 100;

if ($hitRate < 80) {
    // Investigate: TTL too short? Cache not warming?
}
```

### 2. Use Cascade Invalidation

```php
// Invalidate all related caches at once
'cascade_invalidation' => true,
```

### 3. Warm Frequently Accessed Tables

```php
'warming' => [
    'tables' => ['users', 'posts'], // Most accessed
],
```

### 4. Disable Cache in Development

```php
'development' => [
    'disable_in_dev' => true, // Fresh data always
],
```

---

## Troubleshooting

### Cache Not Working

```php
// Check if enabled
config('canvastack.cache.enabled'); // Should be true

// Check cache driver
config('cache.default'); // Should be redis/memcached

// Test cache manually
Cache::put('test', 'value', 60);
$value = Cache::get('test'); // Should return 'value'
```

### Low Hit Rate

1. Check TTL settings (too short?)
2. Enable cache warming
3. Check invalidation frequency
4. Monitor cache size limits

### Cache Not Invalidating

```php
// Check if invalidation enabled
config('canvastack.cache.invalidation.enabled'); // Should be true

// Test manual invalidation
canvastack_table_invalidate_cache('users', 'all');
```

---

## Related Documentation

- [Performance Configuration](../PERFORMANCE.md)
- [Helper Functions](../api/HELPERS.md)
- [Console Commands](../guides/CONSOLE_COMMANDS.md)

---

**Last Updated:** April 4, 2026  
**Maintained By:** CanvaStack Team
