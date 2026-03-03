# Redis Setup Guide for CanvaStack

## Overview

Redis is configured as the primary caching layer for CanvaStack to achieve the 50-80% performance improvement target. This document covers installation, configuration, and testing.

---

## 1. Installation

### Windows (Current Environment)

#### Option 1: Using WSL2 (Recommended)
```bash
# Install WSL2 if not already installed
wsl --install

# Inside WSL2, install Redis
sudo apt update
sudo apt install redis-server

# Start Redis
sudo service redis-server start

# Test connection
redis-cli ping
# Expected output: PONG
```

#### Option 2: Using Memurai (Windows Native)
Memurai is a Redis-compatible server for Windows.

1. Download from: https://www.memurai.com/get-memurai
2. Install the MSI package
3. Service starts automatically on port 6379

#### Option 3: Using Docker
```bash
# Pull Redis image
docker pull redis:7-alpine

# Run Redis container
docker run -d --name redis-canvastack -p 6379:6379 redis:7-alpine

# Test connection
docker exec -it redis-canvastack redis-cli ping
# Expected output: PONG
```

---

## 2. Configuration

### 2.1 Laravel Configuration

Update `config/cache.php` to use Redis as default:

```php
'default' => env('CACHE_DRIVER', 'redis'),

'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],

'prefix' => env('CACHE_PREFIX', 'canvastack_cache'),
```

### 2.2 Environment Variables

Update `.env` file:

```env
# Cache Configuration
CACHE_DRIVER=redis
CACHE_PREFIX=canvastack_cache

# Queue Configuration (optional, but recommended)
QUEUE_CONNECTION=redis

# Session Configuration (optional)
SESSION_DRIVER=redis

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_QUEUE_DB=2
REDIS_SESSION_DB=3
```

### 2.3 Redis Database Configuration

Update `config/database.php`:

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),

    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],

    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],

    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],

    'queue' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_QUEUE_DB', '2'),
    ],

    'session' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_SESSION_DB', '3'),
    ],
],
```

---

## 3. PHP Redis Extension

### Check if phpredis is installed:
```bash
php -m | grep redis
```

### Installation

#### Windows (XAMPP/PHP)
1. Download php_redis.dll from: https://pecl.php.net/package/redis
2. Copy to `php/ext/` directory
3. Add to `php.ini`:
   ```ini
   extension=php_redis.dll
   ```
4. Restart Apache/PHP-FPM

#### Linux/WSL2
```bash
sudo apt install php-redis
# or
sudo pecl install redis
```

#### Verify Installation
```bash
php -r "echo extension_loaded('redis') ? 'Redis installed' : 'Redis not installed';"
```

---

## 4. CanvaStack Cache Configuration

Create `config/canvastack.php` with cache settings:

```php
return [
    'cache' => [
        'enabled' => env('CANVASTACK_CACHE_ENABLED', true),
        'driver' => env('CACHE_DRIVER', 'redis'),
        
        'ttl' => [
            // Form definitions cache (1 hour)
            'forms' => env('CANVASTACK_CACHE_FORMS_TTL', 3600),
            
            // Table query results cache (5 minutes)
            'tables' => env('CANVASTACK_CACHE_TABLES_TTL', 300),
            
            // Permission cache (1 hour)
            'permissions' => env('CANVASTACK_CACHE_PERMISSIONS_TTL', 3600),
            
            // View cache (1 hour)
            'views' => env('CANVASTACK_CACHE_VIEWS_TTL', 3600),
            
            // Configuration cache (24 hours)
            'config' => env('CANVASTACK_CACHE_CONFIG_TTL', 86400),
        ],
        
        'tags' => [
            'forms' => 'canvastack:forms',
            'tables' => 'canvastack:tables',
            'permissions' => 'canvastack:permissions',
            'views' => 'canvastack:views',
            'config' => 'canvastack:config',
        ],
    ],
];
```

---

## 5. Testing Redis Connection

### 5.1 Command Line Test
```bash
# Test Redis connection
redis-cli ping

# Set a test key
redis-cli set test "Hello Redis"

# Get the test key
redis-cli get test

# Delete the test key
redis-cli del test
```

### 5.2 Laravel Artisan Test
```bash
# Test cache connection
php artisan tinker

# In tinker:
Cache::put('test', 'Hello from Laravel', 60);
Cache::get('test');
Cache::forget('test');
```

### 5.3 Create Test Script

Create `tests/Feature/RedisConnectionTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class RedisConnectionTest extends TestCase
{
    public function test_redis_connection(): void
    {
        $response = Redis::connection()->ping();
        $this->assertTrue($response);
    }

    public function test_cache_can_store_and_retrieve(): void
    {
        $key = 'test_key_' . time();
        $value = 'test_value_' . rand(1000, 9999);
        
        Cache::put($key, $value, 60);
        $retrieved = Cache::get($key);
        
        $this->assertEquals($value, $retrieved);
        
        Cache::forget($key);
    }

    public function test_cache_tags_work(): void
    {
        Cache::tags(['test'])->put('key1', 'value1', 60);
        Cache::tags(['test'])->put('key2', 'value2', 60);
        
        $this->assertEquals('value1', Cache::tags(['test'])->get('key1'));
        $this->assertEquals('value2', Cache::tags(['test'])->get('key2'));
        
        Cache::tags(['test'])->flush();
        
        $this->assertNull(Cache::tags(['test'])->get('key1'));
        $this->assertNull(Cache::tags(['test'])->get('key2'));
    }
}
```

Run the test:
```bash
php artisan test --filter=RedisConnectionTest
```

---

## 6. Cache Management Commands

### 6.1 Clear All Cache
```bash
php artisan cache:clear
```

### 6.2 Clear Specific Tags (CanvaStack)
```bash
# Clear form cache
php artisan cache:clear --tags=canvastack:forms

# Clear table cache
php artisan cache:clear --tags=canvastack:tables

# Clear permission cache
php artisan cache:clear --tags=canvastack:permissions
```

### 6.3 Monitor Cache Usage
```bash
# Connect to Redis CLI
redis-cli

# Monitor all commands
MONITOR

# Get cache statistics
INFO stats

# Get memory usage
INFO memory

# List all keys (use with caution in production)
KEYS *

# Count keys
DBSIZE
```

---

## 7. Performance Monitoring

### 7.1 Cache Hit Rate

Create `app/Console/Commands/CacheStats.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class CacheStats extends Command
{
    protected $signature = 'cache:stats';
    protected $description = 'Display cache statistics';

    public function handle()
    {
        $info = Redis::connection('cache')->info('stats');
        
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        $hitRate = $total > 0 ? ($hits / $total) * 100 : 0;
        
        $this->info("Cache Statistics:");
        $this->line("Hits: " . number_format($hits));
        $this->line("Misses: " . number_format($misses));
        $this->line("Hit Rate: " . number_format($hitRate, 2) . "%");
        
        if ($hitRate < 80) {
            $this->warn("Cache hit rate is below target (80%)");
        } else {
            $this->info("Cache hit rate is good!");
        }
    }
}
```

Usage:
```bash
php artisan cache:stats
```

---

## 8. Production Considerations

### 8.1 Security
```bash
# Set Redis password in production
redis-cli CONFIG SET requirepass "your-strong-password"

# Update .env
REDIS_PASSWORD=your-strong-password
```

### 8.2 Persistence
```bash
# Enable RDB snapshots (in redis.conf)
save 900 1
save 300 10
save 60 10000

# Enable AOF (Append Only File)
appendonly yes
appendfsync everysec
```

### 8.3 Memory Management
```bash
# Set max memory (in redis.conf)
maxmemory 256mb
maxmemory-policy allkeys-lru
```

### 8.4 Monitoring
- Use Redis Sentinel for high availability
- Monitor memory usage and eviction rate
- Set up alerts for connection failures
- Regular backups of Redis data

---

## 9. Troubleshooting

### Issue: Connection Refused
```bash
# Check if Redis is running
redis-cli ping

# Start Redis (WSL2)
sudo service redis-server start

# Start Redis (Docker)
docker start redis-canvastack
```

### Issue: phpredis Extension Not Found
```bash
# Check PHP extensions
php -m | grep redis

# Install phpredis
sudo pecl install redis
```

### Issue: Permission Denied
```bash
# Check Redis logs
tail -f /var/log/redis/redis-server.log

# Check file permissions
ls -la /var/run/redis/
```

### Issue: Out of Memory
```bash
# Check memory usage
redis-cli INFO memory

# Clear all keys (CAUTION!)
redis-cli FLUSHALL

# Or clear specific database
redis-cli -n 1 FLUSHDB
```

---

## 10. Fallback Strategy

If Redis is unavailable, CanvaStack will automatically fall back to file cache:

```php
// In CacheManager.php
public function getStore()
{
    try {
        if (config('canvastack.cache.driver') === 'redis') {
            Redis::connection('cache')->ping();
            return Cache::store('redis');
        }
    } catch (\Exception $e) {
        Log::warning('Redis unavailable, falling back to file cache', [
            'error' => $e->getMessage()
        ]);
    }
    
    return Cache::store('file');
}
```

---

## 11. Next Steps

After Redis setup:
1. ✅ Verify connection with tests
2. ✅ Monitor cache hit rate
3. ✅ Implement caching in Form component
4. ✅ Implement caching in Table component
5. ✅ Implement caching in RBAC system
6. ✅ Run performance benchmarks

---

## Summary

**Installation**: WSL2/Docker/Memurai  
**Configuration**: Redis as default cache driver  
**Testing**: Connection tests, cache tests  
**Monitoring**: Cache stats, hit rate tracking  
**Target**: 80%+ cache hit rate for 50-80% performance improvement

**Status**: ✅ Ready for Phase 1 implementation

---

**Last Updated**: 2026-02-24  
**Version**: 1.0.0
