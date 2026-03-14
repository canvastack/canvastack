# Tab System Cache Configuration

## 📦 Location

- **Configuration File**: `config/canvastack.php`
- **Section**: `cache.tab_system`
- **Environment Variables**: `.env`

## 🎯 Overview

The Tab System Cache Configuration provides comprehensive caching strategies for the multi-table tab system, including both client-side (Alpine.js) and server-side (Laravel) caching to optimize performance and reduce server load.

## 📖 Configuration Options

### Basic Cache Settings

```php
'cache' => [
    'tab_system' => [
        // Enable/disable tab content caching
        'enabled' => env('CANVASTACK_TAB_CACHE_ENABLED', true),
        
        // Cache TTL for tab content (in seconds)
        'ttl' => env('CANVASTACK_TAB_CACHE_TTL', 600), // 10 minutes
    ],
],
```

### Client-Side Cache (Alpine.js)

```php
'client_cache' => [
    'enabled' => env('CANVASTACK_TAB_CLIENT_CACHE_ENABLED', true),
    'storage' => 'memory', // 'memory' or 'sessionStorage'
],
```

**Options:**
- `enabled` - Enable/disable client-side caching in Alpine.js state
- `storage` - Storage mechanism:
  - `memory` - Store in Alpine.js reactive state (default, faster)
  - `sessionStorage` - Store in browser sessionStorage (persists across page reloads)

### Server-Side Cache (Laravel)

```php
'server_cache' => [
    'enabled' => env('CANVASTACK_TAB_SERVER_CACHE_ENABLED', true),
    'driver' => env('CANVASTACK_TAB_CACHE_DRIVER', null), // null = use default
    'prefix' => 'tab_content_',
],
```

**Options:**
- `enabled` - Enable/disable server-side caching
- `driver` - Cache driver to use (null = use default from `cache.driver`)
  - `redis` - Recommended for production
  - `memcached` - Alternative high-performance option
  - `file` - File-based caching
  - `database` - Database caching
  - `array` - In-memory (testing only)
- `prefix` - Cache key prefix for tab content

### Cache Invalidation Strategy

```php
'invalidation' => [
    // Auto-invalidate on data changes
    'auto' => env('CANVASTACK_TAB_CACHE_AUTO_INVALIDATE', true),
    
    // Events that trigger cache invalidation
    'events' => [
        'eloquent.created: *',
        'eloquent.updated: *',
        'eloquent.deleted: *',
    ],
    
    // Manual invalidation methods
    'methods' => [
        'on_save' => true,      // Invalidate on model save
        'on_delete' => true,    // Invalidate on model delete
        'on_request' => false,  // Invalidate on every request (not recommended)
    ],
],
```

**Options:**
- `auto` - Automatically invalidate cache when data changes
- `events` - Laravel events that trigger cache invalidation
- `methods` - Specific invalidation triggers

### Cache Key Generation

```php
'key_generation' => [
    // Include these in cache key for uniqueness
    'include' => [
        'table_id' => true,     // Unique table ID
        'tab_index' => true,    // Tab index
        'user_id' => false,     // User ID (set true for per-user caching)
        'filters' => true,      // Active filters
        'sorting' => true,      // Sort configuration
        'pagination' => true,   // Pagination state
    ],
    
    // Cache key format
    'format' => '{prefix}{table_id}_{tab_index}_{hash}',
],
```

**Options:**
- `include` - Components to include in cache key generation
  - `table_id` - Ensures different tables have different cache
  - `tab_index` - Ensures different tabs have different cache
  - `user_id` - Enable for per-user caching (e.g., personalized data)
  - `filters` - Include active filters in cache key
  - `sorting` - Include sort configuration in cache key
  - `pagination` - Include pagination state in cache key
- `format` - Cache key format template

### Performance Monitoring

```php
'monitoring' => [
    'enabled' => env('CANVASTACK_TAB_CACHE_MONITORING', false),
    'log_hits' => false,
    'log_misses' => false,
    'track_ratio' => true, // Track cache hit ratio
],
```

**Options:**
- `enabled` - Enable performance monitoring
- `log_hits` - Log cache hits to Laravel log
- `log_misses` - Log cache misses to Laravel log
- `track_ratio` - Track and report cache hit ratio

## 🔧 Environment Variables

Add these to your `.env` file:

```env
# Tab System Cache Configuration
CANVASTACK_TAB_CACHE_ENABLED=true
CANVASTACK_TAB_CACHE_TTL=600
CANVASTACK_TAB_CLIENT_CACHE_ENABLED=true
CANVASTACK_TAB_SERVER_CACHE_ENABLED=true
CANVASTACK_TAB_CACHE_DRIVER=redis
CANVASTACK_TAB_CACHE_AUTO_INVALIDATE=true
CANVASTACK_TAB_CACHE_MONITORING=false
```

## 📝 Usage Examples

### Example 1: Enable All Caching (Recommended)

```env
CANVASTACK_TAB_CACHE_ENABLED=true
CANVASTACK_TAB_CLIENT_CACHE_ENABLED=true
CANVASTACK_TAB_SERVER_CACHE_ENABLED=true
CANVASTACK_TAB_CACHE_DRIVER=redis
CANVASTACK_TAB_CACHE_TTL=600
```

**Result:**
- Client-side caching in Alpine.js (instant tab switching)
- Server-side caching in Redis (fast AJAX responses)
- 10-minute cache TTL
- Automatic cache invalidation on data changes

### Example 2: Client-Side Only (Development)

```env
CANVASTACK_TAB_CACHE_ENABLED=true
CANVASTACK_TAB_CLIENT_CACHE_ENABLED=true
CANVASTACK_TAB_SERVER_CACHE_ENABLED=false
```

**Result:**
- Client-side caching only
- No server-side caching (always fresh data from database)
- Good for development when data changes frequently

### Example 3: Server-Side Only (Shared Environment)

```env
CANVASTACK_TAB_CACHE_ENABLED=true
CANVASTACK_TAB_CLIENT_CACHE_ENABLED=false
CANVASTACK_TAB_SERVER_CACHE_ENABLED=true
CANVASTACK_TAB_CACHE_DRIVER=redis
```

**Result:**
- Server-side caching only
- No client-side caching (always fetch from server)
- Good for shared environments where multiple users see same data

### Example 4: Disable All Caching (Testing)

```env
CANVASTACK_TAB_CACHE_ENABLED=false
```

**Result:**
- No caching at all
- Always fetch fresh data
- Good for testing and debugging

### Example 5: Per-User Caching

```php
// config/canvastack.php
'cache' => [
    'tab_system' => [
        'key_generation' => [
            'include' => [
                'table_id' => true,
                'tab_index' => true,
                'user_id' => true,  // Enable per-user caching
                'filters' => true,
                'sorting' => true,
                'pagination' => true,
            ],
        ],
    ],
],
```

**Result:**
- Each user gets their own cache
- Good for personalized data (e.g., user-specific filters)

## 🎮 Programmatic Control

### Clear Tab Cache

```php
use Illuminate\Support\Facades\Cache;

// Clear all tab cache
Cache::tags('canvastack:tabs')->flush();

// Clear specific table's tab cache
$tableId = 'canvastable_abc123';
Cache::tags('canvastack:tabs')->forget("tab_content_{$tableId}_0");

// Clear all tabs for a specific table
$tableId = 'canvastable_abc123';
for ($i = 0; $i < 10; $i++) {
    Cache::tags('canvastack:tabs')->forget("tab_content_{$tableId}_{$i}");
}
```

### Check Cache Hit Ratio

```php
use Canvastack\Canvastack\Components\Table\Cache\TabCacheManager;

$cacheManager = app(TabCacheManager::class);

// Get cache statistics
$stats = $cacheManager->getStatistics();

echo "Cache Hit Ratio: " . $stats['hit_ratio'] . "%\n";
echo "Total Hits: " . $stats['hits'] . "\n";
echo "Total Misses: " . $stats['misses'] . "\n";
```

### Manual Cache Invalidation

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

$table = app(TableBuilder::class);

// Clear cache for this table
$table->clearCache();

// Or use the cache manager
$cacheManager = app(TabCacheManager::class);
$cacheManager->invalidate($tableId, $tabIndex);
```

## 🔍 Cache Key Format

The cache key is generated using the following format:

```
{prefix}{table_id}_{tab_index}_{hash}
```

**Example:**
```
tab_content_canvastable_abc123_0_d41d8cd98f00b204e9800998ecf8427e
```

**Components:**
- `tab_content_` - Prefix (configurable)
- `canvastable_abc123` - Unique table ID
- `0` - Tab index
- `d41d8cd98f00b204e9800998ecf8427e` - MD5 hash of filters, sorting, pagination

## 💡 Performance Tips

### 1. Use Redis for Production

```env
CANVASTACK_TAB_CACHE_DRIVER=redis
```

Redis provides the best performance for caching.

### 2. Adjust TTL Based on Data Volatility

```env
# Static data (rarely changes)
CANVASTACK_TAB_CACHE_TTL=3600  # 1 hour

# Dynamic data (changes frequently)
CANVASTACK_TAB_CACHE_TTL=300   # 5 minutes

# Real-time data (changes constantly)
CANVASTACK_TAB_CACHE_TTL=60    # 1 minute
```

### 3. Enable Both Client and Server Caching

```env
CANVASTACK_TAB_CLIENT_CACHE_ENABLED=true
CANVASTACK_TAB_SERVER_CACHE_ENABLED=true
```

This provides the best user experience:
- First tab switch: Server cache (fast)
- Subsequent switches: Client cache (instant)

### 4. Monitor Cache Hit Ratio

```env
CANVASTACK_TAB_CACHE_MONITORING=true
```

Aim for > 80% cache hit ratio for optimal performance.

### 5. Use Auto-Invalidation

```env
CANVASTACK_TAB_CACHE_AUTO_INVALIDATE=true
```

Automatically invalidates cache when data changes, ensuring users always see fresh data.

## 🎭 Common Patterns

### Pattern 1: High-Traffic Application

```env
CANVASTACK_TAB_CACHE_ENABLED=true
CANVASTACK_TAB_CLIENT_CACHE_ENABLED=true
CANVASTACK_TAB_SERVER_CACHE_ENABLED=true
CANVASTACK_TAB_CACHE_DRIVER=redis
CANVASTACK_TAB_CACHE_TTL=600
CANVASTACK_TAB_CACHE_AUTO_INVALIDATE=true
```

### Pattern 2: Development Environment

```env
CANVASTACK_TAB_CACHE_ENABLED=true
CANVASTACK_TAB_CLIENT_CACHE_ENABLED=true
CANVASTACK_TAB_SERVER_CACHE_ENABLED=false
CANVASTACK_TAB_CACHE_TTL=60
```

### Pattern 3: Testing Environment

```env
CANVASTACK_TAB_CACHE_ENABLED=false
```

### Pattern 4: Personalized Data

```php
'key_generation' => [
    'include' => [
        'table_id' => true,
        'tab_index' => true,
        'user_id' => true,  // Per-user caching
        'filters' => true,
        'sorting' => true,
        'pagination' => true,
    ],
],
```

## 🚨 Troubleshooting

### Issue 1: Cache Not Working

**Symptoms:**
- Tab content always loads from server
- No performance improvement

**Solutions:**
1. Check if caching is enabled:
   ```env
   CANVASTACK_TAB_CACHE_ENABLED=true
   ```

2. Verify cache driver is configured:
   ```env
   CANVASTACK_TAB_CACHE_DRIVER=redis
   ```

3. Check Redis connection:
   ```bash
   redis-cli ping
   ```

4. Clear cache and try again:
   ```bash
   php artisan cache:clear
   ```

### Issue 2: Stale Data

**Symptoms:**
- Users see old data after updates
- Changes don't appear immediately

**Solutions:**
1. Enable auto-invalidation:
   ```env
   CANVASTACK_TAB_CACHE_AUTO_INVALIDATE=true
   ```

2. Reduce cache TTL:
   ```env
   CANVASTACK_TAB_CACHE_TTL=300  # 5 minutes
   ```

3. Manually clear cache after updates:
   ```php
   Cache::tags('canvastack:tabs')->flush();
   ```

### Issue 3: Low Cache Hit Ratio

**Symptoms:**
- Cache hit ratio < 50%
- Poor performance despite caching

**Solutions:**
1. Check cache key generation:
   - Too many components in cache key = too many unique keys
   - Remove unnecessary components from `key_generation.include`

2. Increase cache TTL:
   ```env
   CANVASTACK_TAB_CACHE_TTL=1800  # 30 minutes
   ```

3. Review invalidation strategy:
   - Too aggressive invalidation = low hit ratio
   - Adjust `invalidation.methods` settings

### Issue 4: Memory Issues

**Symptoms:**
- Redis memory usage high
- Out of memory errors

**Solutions:**
1. Reduce cache TTL:
   ```env
   CANVASTACK_TAB_CACHE_TTL=300  # 5 minutes
   ```

2. Increase Redis max memory:
   ```
   # redis.conf
   maxmemory 2gb
   maxmemory-policy allkeys-lru
   ```

3. Use cache tags for selective clearing:
   ```php
   Cache::tags(['canvastack:tabs', 'table:users'])->flush();
   ```

## 🔗 Related Documentation

- [TableBuilder API](../api/table.md) - TableBuilder component reference
- [Tab System Usage](../guides/tab-system-usage.md) - How to use the tab system
- [Performance Optimization](../guides/performance.md) - Performance best practices
- [Caching Strategy](../architecture/caching.md) - Overall caching architecture

## 📚 Resources

- [Laravel Cache Documentation](https://laravel.com/docs/cache)
- [Redis Documentation](https://redis.io/documentation)
- [Alpine.js Reactivity](https://alpinejs.dev/essentials/reactivity)

---

**Last Updated**: 2026-03-08  
**Version**: 1.0.0  
**Status**: Published
