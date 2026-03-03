# Performance Optimization

**Version**: 1.0.0  
**Last Updated**: 2026-02-26  
**Status**: Complete

---

## Table of Contents

1. [Overview](#overview)
2. [Performance Targets](#performance-targets)
3. [Query Optimization](#query-optimization)
4. [Caching Strategies](#caching-strategies)
5. [Eager Loading](#eager-loading)
6. [Database Indexing](#database-indexing)
7. [Asset Optimization](#asset-optimization)
8. [Memory Management](#memory-management)
9. [Benchmarking](#benchmarking)
10. [Monitoring](#monitoring)
11. [Best Practices](#best-practices)
12. [Troubleshooting](#troubleshooting)

---

## Overview

CanvaStack achieves 50-80% performance improvements over the legacy implementation through comprehensive optimization strategies.

### Key Improvements

- **Query Optimization**: Eliminated N+1 queries, added eager loading
- **Caching**: Multi-layer caching (application, query, view)
- **Memory Management**: Reduced memory usage by 50%
- **Asset Optimization**: Vite bundling, code splitting
- **Database Indexing**: Optimized indexes for common queries

### Performance Metrics

| Metric | Legacy | CanvaStack | Improvement |
|--------|--------|------------|-------------|
| DataTable (1K rows) | ~2000ms | ~400ms | 80% faster |
| DataTable (10K rows) | ~15000ms | ~2000ms | 87% faster |
| Form render (50 fields) | ~200ms | ~50ms | 75% faster |
| Memory usage | ~256MB | ~128MB | 50% reduction |
| Cache hit ratio | 0% | >80% | New feature |

---

## Performance Targets

### Response Time Targets

| Operation | Target | Acceptable | Poor |
|-----------|--------|------------|------|
| DataTable (1K rows) | < 500ms | < 1000ms | > 1000ms |
| DataTable (10K rows) | < 2000ms | < 5000ms | > 5000ms |
| Form render | < 50ms | < 100ms | > 100ms |
| Page load | < 1000ms | < 2000ms | > 2000ms |
| API response | < 200ms | < 500ms | > 500ms |

### Resource Targets

| Resource | Target | Acceptable | Poor |
|----------|--------|------------|------|
| Memory usage | < 128MB | < 256MB | > 256MB |
| CPU usage | < 50% | < 75% | > 75% |
| Database connections | < 10 | < 20 | > 20 |
| Cache hit ratio | > 80% | > 60% | < 60% |

---

## Query Optimization

### Problem: N+1 Queries

Legacy code suffered from N+1 query problems:

```php
// ❌ BAD - N+1 queries (Legacy)
$users = User::all(); // 1 query

foreach ($users as $user) {
    echo $user->posts->count(); // N queries
}
// Total: 1 + N queries
```

### Solution: Eager Loading

```php
// ✅ GOOD - 2 queries only
$users = User::with('posts')->get(); // 2 queries

foreach ($users as $user) {
    echo $user->posts->count(); // No additional queries
}
// Total: 2 queries
```

### Table Component Optimization

```php
use Canvastack\Components\Table\TableBuilder;

$table = new TableBuilder();

// ✅ Eager load relationships
$table->eager(['posts', 'comments', 'profile']);

// ✅ Select only needed columns
$table->select(['id', 'name', 'email', 'created_at']);

// ✅ Enable query caching
$table->cache(300);

// ✅ Use chunking for large datasets
$table->chunk(100);

$table->runModel(User::class);
```

### Query Profiling

```php
// Enable query logging
DB::enableQueryLog();

// Run your code
$table->runModel(User::class);

// Get executed queries
$queries = DB::getQueryLog();

foreach ($queries as $query) {
    echo $query['query'] . "\n";
    echo "Time: " . $query['time'] . "ms\n";
}
```

### Optimize Complex Queries

```php
// ❌ BAD - Multiple queries
$activeUsers = User::where('status', 'active')->get();
$inactiveUsers = User::where('status', 'inactive')->get();

// ✅ GOOD - Single query with grouping
$users = User::select('status', DB::raw('count(*) as count'))
    ->groupBy('status')
    ->get();
```

---

## Caching Strategies

### Multi-Layer Caching

```
Application Cache (1 hour)
    ↓
Query Cache (5 minutes)
    ↓
View Cache (1 minute)
```

### Application Cache

```php
// Cache component configuration
$table = new TableBuilder();
$table->column('name', 'Name');
$table->column('email', 'Email');
// Configuration cached automatically
```

### Query Cache

```php
// Enable query caching
$table->cache(300); // 5 minutes

// Cache with tags
$table->cache(300, ['users', 'admin']);

// Conditional caching
$table->cacheWhen(function () {
    return !request()->has('nocache');
}, 300);
```

### View Cache

```php
// Cache rendered HTML
$table->cacheView(60); // 1 minute

// Cache with tags
$table->cacheView(60, ['users_view']);
```

### Manual Caching

```php
use Illuminate\Support\Facades\Cache;

// Cache expensive operations
$stats = Cache::remember('dashboard_stats', 3600, function () {
    return [
        'total_users' => User::count(),
        'active_users' => User::where('status', 'active')->count(),
        'total_posts' => Post::count(),
    ];
});
```

### Cache Invalidation

```php
// Invalidate on model changes
User::saved(function () {
    Cache::tags(['users'])->flush();
});

// Manual invalidation
Cache::forget('dashboard_stats');
Cache::tags(['users'])->flush();
```

---

## Eager Loading

### Basic Eager Loading

```php
// ✅ Eager load single relationship
$users = User::with('posts')->get();

// ✅ Eager load multiple relationships
$users = User::with(['posts', 'comments', 'profile'])->get();

// ✅ Nested eager loading
$users = User::with('posts.comments.author')->get();
```

### Conditional Eager Loading

```php
// Load relationship only when needed
$users = User::when(request('include_posts'), function ($query) {
    $query->with('posts');
})->get();
```

### Constrained Eager Loading

```php
// Load only specific related records
$users = User::with(['posts' => function ($query) {
    $query->where('status', 'published')
        ->orderBy('created_at', 'desc')
        ->limit(5);
}])->get();
```

### Table Component Eager Loading

```php
$table = new TableBuilder();

// ✅ Eager load relationships
$table->eager(['posts', 'comments']);

// ✅ Constrained eager loading
$table->eager([
    'posts' => function ($query) {
        $query->where('status', 'published');
    }
]);

$table->runModel(User::class);
```

### Lazy Eager Loading

```php
// Load relationship after initial query
$users = User::all();

if ($needPosts) {
    $users->load('posts');
}
```

---

## Database Indexing

### Identify Missing Indexes

```sql
-- Find slow queries
SELECT * FROM mysql.slow_log
WHERE query_time > 1
ORDER BY query_time DESC
LIMIT 10;

-- Analyze query execution
EXPLAIN SELECT * FROM users WHERE email = 'test@example.com';
```

### Add Indexes

```php
// Migration
Schema::table('users', function (Blueprint $table) {
    // Single column index
    $table->index('email');
    $table->index('status');
    
    // Composite index
    $table->index(['status', 'created_at']);
    
    // Unique index
    $table->unique('email');
    
    // Full-text index
    $table->fullText(['name', 'bio']);
});
```

### Index Best Practices

```php
// ✅ GOOD - Index frequently queried columns
$table->index('status');
$table->index('created_at');

// ✅ GOOD - Index foreign keys
$table->foreign('user_id')->references('id')->on('users');

// ✅ GOOD - Composite index for multiple columns
$table->index(['status', 'created_at']);

// ❌ BAD - Don't over-index
// Too many indexes slow down INSERT/UPDATE operations
```

### Monitor Index Usage

```sql
-- Check index usage
SELECT 
    table_name,
    index_name,
    cardinality
FROM information_schema.statistics
WHERE table_schema = 'your_database'
ORDER BY cardinality DESC;
```

---

## Asset Optimization

### Vite Configuration

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        // Code splitting
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor': ['alpinejs', 'axios'],
                    'charts': ['apexcharts'],
                },
            },
        },
        // Minification
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
            },
        },
    },
});
```

### CSS Optimization

```css
/* Use Tailwind's purge feature */
/* tailwind.config.js */
module.exports = {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    // Only include used classes
};
```

### Image Optimization

```php
// Optimize images on upload
use Intervention\Image\Facades\Image;

public function upload(Request $request)
{
    $image = $request->file('image');
    
    // Resize and optimize
    $optimized = Image::make($image)
        ->resize(800, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })
        ->encode('jpg', 80);
    
    Storage::put('images/optimized.jpg', $optimized);
}
```

### Lazy Loading

```blade
{{-- Lazy load images --}}
<img src="placeholder.jpg" data-src="actual-image.jpg" loading="lazy">

{{-- Lazy load components --}}
<div x-data="{ loaded: false }" x-intersect="loaded = true">
    <template x-if="loaded">
        <div>Heavy component content</div>
    </template>
</div>
```

---

## Memory Management

### Chunking Large Datasets

```php
// ❌ BAD - Load all records into memory
$users = User::all(); // Memory intensive

foreach ($users as $user) {
    // Process user
}

// ✅ GOOD - Process in chunks
User::chunk(100, function ($users) {
    foreach ($users as $user) {
        // Process user
    }
});
```

### Table Component Chunking

```php
$table = new TableBuilder();

// ✅ Enable chunking
$table->chunk(100);

$table->runModel(User::class);
```

### Cursor Iteration

```php
// ✅ GOOD - Use cursor for very large datasets
foreach (User::cursor() as $user) {
    // Process user
    // Only one record in memory at a time
}
```

### Memory Limit

```php
// Increase memory limit for heavy operations
ini_set('memory_limit', '512M');

// Or in php.ini
// memory_limit = 512M
```

### Garbage Collection

```php
// Force garbage collection
gc_collect_cycles();

// Disable garbage collection during heavy operations
gc_disable();

// Heavy operation here

gc_enable();
gc_collect_cycles();
```

---

## Benchmarking

### Built-in Benchmarking

```bash
# Benchmark current implementation
php artisan benchmark:current

# Benchmark new implementation
php artisan benchmark:new

# Compare results
php artisan benchmark:compare
```

### Manual Benchmarking

```php
use Illuminate\Support\Benchmark;

// Benchmark single operation
$result = Benchmark::measure(function () {
    User::with('posts')->get();
});

echo "Execution time: {$result}ms\n";

// Compare multiple implementations
$results = Benchmark::compare([
    'Without eager loading' => fn() => User::all(),
    'With eager loading' => fn() => User::with('posts')->get(),
], iterations: 100);

foreach ($results as $name => $time) {
    echo "{$name}: {$time}ms\n";
}
```

### Laravel Debugbar

```bash
# Install Laravel Debugbar
composer require barryvdh/laravel-debugbar --dev
```

```php
// View performance metrics in browser
// - Query count and time
// - Memory usage
// - View rendering time
// - Route information
```

### Query Profiling

```php
// Enable query logging
DB::listen(function ($query) {
    Log::info('Query executed', [
        'sql' => $query->sql,
        'bindings' => $query->bindings,
        'time' => $query->time,
    ]);
});
```

---

## Monitoring

### Application Performance Monitoring

```php
// Monitor response time
use Illuminate\Support\Facades\Log;

class PerformanceMiddleware
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = (microtime(true) - $start) * 1000;
        
        if ($duration > 1000) {
            Log::warning('Slow request', [
                'url' => $request->fullUrl(),
                'duration' => $duration,
                'memory' => memory_get_peak_usage(true) / 1024 / 1024,
            ]);
        }
        
        return $response;
    }
}
```

### Database Monitoring

```php
// Monitor slow queries
DB::listen(function ($query) {
    if ($query->time > 100) {
        Log::warning('Slow query', [
            'sql' => $query->sql,
            'time' => $query->time,
        ]);
    }
});
```

### Cache Monitoring

```php
// Monitor cache hit ratio
$hits = Cache::get('cache_hits', 0);
$misses = Cache::get('cache_misses', 0);
$total = $hits + $misses;
$ratio = $total > 0 ? ($hits / $total) * 100 : 0;

Log::info('Cache statistics', [
    'hits' => $hits,
    'misses' => $misses,
    'ratio' => $ratio,
]);
```

---

## Best Practices

### 1. Use Eager Loading

```php
// ✅ GOOD
$users = User::with('posts')->get();

// ❌ BAD
$users = User::all();
foreach ($users as $user) {
    $user->posts; // N+1 query
}
```

### 2. Enable Caching

```php
// ✅ GOOD
$table->cache(300);

// ❌ BAD
// No caching
```

### 3. Select Only Needed Columns

```php
// ✅ GOOD
$users = User::select(['id', 'name', 'email'])->get();

// ❌ BAD
$users = User::all(); // Selects all columns
```

### 4. Use Chunking for Large Datasets

```php
// ✅ GOOD
User::chunk(100, function ($users) {
    // Process users
});

// ❌ BAD
$users = User::all(); // Load all into memory
```

### 5. Add Database Indexes

```php
// ✅ GOOD
$table->index('email');
$table->index(['status', 'created_at']);

// ❌ BAD
// No indexes on frequently queried columns
```

### 6. Optimize Assets

```bash
# ✅ GOOD
npm run build

# ❌ BAD
# Using unminified assets in production
```

### 7. Monitor Performance

```php
// ✅ GOOD
Log::info('Performance metrics', [
    'duration' => $duration,
    'memory' => $memory,
    'queries' => $queryCount,
]);

// ❌ BAD
// No performance monitoring
```

---

## Troubleshooting

### Slow Queries

**Problem**: Queries taking too long

**Solutions**:

1. Enable query logging:
```php
DB::enableQueryLog();
// Run code
dd(DB::getQueryLog());
```

2. Add indexes:
```php
Schema::table('users', function (Blueprint $table) {
    $table->index('email');
});
```

3. Use eager loading:
```php
$users = User::with('posts')->get();
```

### High Memory Usage

**Problem**: Application using too much memory

**Solutions**:

1. Use chunking:
```php
User::chunk(100, function ($users) {
    // Process
});
```

2. Use cursor:
```php
foreach (User::cursor() as $user) {
    // Process
}
```

3. Increase memory limit:
```php
ini_set('memory_limit', '512M');
```

### Cache Not Working

**Problem**: Cache not improving performance

**Solutions**:

1. Verify cache is enabled:
```php
// config/canvastack.php
'cache' => [
    'enabled' => [
        'query' => true,
    ],
],
```

2. Check cache driver:
```bash
php artisan config:cache
php artisan cache:clear
```

3. Monitor cache hit ratio:
```php
$hits = Cache::get('cache_hits', 0);
$misses = Cache::get('cache_misses', 0);
```

### Slow Page Load

**Problem**: Pages loading slowly

**Solutions**:

1. Enable caching:
```php
$table->cache(300);
```

2. Optimize assets:
```bash
npm run build
```

3. Use CDN for static assets

4. Enable compression:
```apache
# .htaccess
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css text/javascript
</IfModule>
```

---

## See Also

- [Caching System](caching.md)
- [Eager Loading Guide](eager-loading.md)
- [Table Performance Tuning](../components/table/performance.md)
- [Best Practices](../guides/best-practices.md)

---

**Next**: [Dark Mode](dark-mode.md)
