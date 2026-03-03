# Performance Tuning Guide

This guide provides best practices and strategies for optimizing the performance of the CanvaStack Table Component.

## Table of Contents

1. [Caching Strategies](#caching-strategies)
2. [Eager Loading Best Practices](#eager-loading-best-practices)
3. [Chunk Processing Configuration](#chunk-processing-configuration)
4. [Query Optimization Techniques](#query-optimization-techniques)
5. [Server-Side vs Client-Side Processing](#server-side-vs-client-side-processing)
6. [Memory Management](#memory-management)
7. [Performance Monitoring](#performance-monitoring)

---

## Caching Strategies

### Overview

The Table Component supports multi-layer caching to dramatically improve performance for repeated queries.

### Enabling Cache

```php
// Enable caching for 5 minutes (300 seconds)
$table->cache(300);

// Enable caching for 1 hour
$table->cache(3600);

// Disable caching
$table->cache(0);
```

### Cache Key Generation

Cache keys are automatically generated based on:
- Table name
- Selected columns
- Where conditions
- Filter groups
- Sort order
- Relationships

This ensures that different queries get different cache entries.

### Cache Invalidation

Cache is automatically invalidated when:
- Data in the table is modified
- Cache TTL expires
- Manual cache clear is triggered

```php
// Clear cache for specific table
Cache::tags(['table:users'])->flush();

// Clear all table caches
Cache::tags(['canvastack:tables'])->flush();
```

### Best Practices

1. **Use longer cache times for static data**
   ```php
   // Static reference data - cache for 1 day
   $table->setName('countries')->cache(86400);
   ```

2. **Use shorter cache times for dynamic data**
   ```php
   // User activity data - cache for 1 minute
   $table->setName('user_activities')->cache(60);
   ```

3. **Disable cache for real-time data**
   ```php
   // Real-time dashboard - no cache
   $table->setName('live_metrics')->cache(0);
   ```

4. **Use cache tags for efficient invalidation**
   ```php
   // When updating users, invalidate user table cache
   User::updated(function ($user) {
       Cache::tags(['table:users'])->flush();
   });
   ```

### Performance Impact

- **First request**: Normal query time (e.g., 200ms)
- **Cached requests**: < 50ms (75% faster)
- **Cache hit ratio**: Target > 80% for repeated queries

---

## Eager Loading Best Practices

### Overview

Eager loading prevents N+1 query problems by loading related data upfront.

### The N+1 Problem

**Bad - N+1 Queries:**
```php
// This generates 1 + N queries (1 for users, N for roles)
$table->model(User::class)
    ->setFields(['id', 'name', 'role.name'])
    ->render();
// Queries: 1 (users) + 100 (roles) = 101 queries for 100 users
```

**Good - Eager Loading:**
```php
// This generates only 2 queries (1 for users, 1 for all roles)
$table->model(User::class)
    ->setFields(['id', 'name', 'role.name'])
    ->relations(new User(), 'role', 'name')
    ->render();
// Queries: 1 (users) + 1 (roles) = 2 queries for 100 users
```

### Using relations() Method

```php
// Load single relationship
$table->relations(new User(), 'role', 'name');

// Load multiple relationships
$table->relations(new User(), 'role', 'name')
    ->relations(new User(), 'department', 'name')
    ->relations(new User(), 'manager', 'full_name');
```

### Using fieldReplacementValue() Method

```php
// Replace foreign key with related data
$table->fieldReplacementValue(
    new User(),
    'role',           // Relationship method
    'name',           // Field to display
    'Role',           // Column label
    'role_id'         // Foreign key column
);
```

### Nested Relationships

```php
// Load nested relationships
$table->relations(new Order(), 'customer.country', 'name');

// This automatically eager loads: orders -> customers -> countries
```

### Best Practices

1. **Always use eager loading for relationships**
   ```php
   // ✅ Good
   $table->relations(new User(), 'role', 'name');
   
   // ❌ Bad - causes N+1
   $table->setFields(['id', 'name', 'role.name']);
   ```

2. **Load only required relationship fields**
   ```php
   // ✅ Good - load only name
   $table->relations(new User(), 'role', 'name');
   
   // ❌ Bad - loads all role fields
   $table->relations(new User(), 'role', '*');
   ```

3. **Use fieldReplacementValue for foreign keys**
   ```php
   // Replace role_id with role name
   $table->fieldReplacementValue(new User(), 'role', 'name', 'Role', 'role_id');
   ```

4. **Combine with caching for maximum performance**
   ```php
   $table->relations(new User(), 'role', 'name')
       ->cache(300); // Cache eager loaded data
   ```

### Performance Impact

- **Without eager loading**: 1 + N queries (e.g., 101 queries for 100 rows)
- **With eager loading**: 1 + R queries (e.g., 3 queries for 100 rows with 2 relationships)
- **Performance gain**: 97% reduction in queries (101 → 3)

---

## Chunk Processing Configuration

### Overview

Chunk processing loads and processes data in batches to prevent memory exhaustion with large datasets.

### Enabling Chunk Processing

```php
// Process in chunks of 100 rows (default)
$table->chunk(100);

// Process in chunks of 500 rows
$table->chunk(500);

// Process in chunks of 1000 rows
$table->chunk(1000);
```

### When to Use Chunk Processing

1. **Large datasets (> 1,000 rows)**
   ```php
   $table->setName('transactions')
       ->chunk(500); // Process 10,000 rows in 20 chunks
   ```

2. **Memory-constrained environments**
   ```php
   $table->setName('logs')
       ->chunk(100); // Smaller chunks for limited memory
   ```

3. **Export operations**
   ```php
   $table->setName('orders')
       ->chunk(1000)
       ->export('csv');
   ```

### Chunk Size Guidelines

| Dataset Size | Recommended Chunk Size | Memory Usage |
|--------------|------------------------|--------------|
| < 1,000 rows | No chunking needed | < 16MB |
| 1K - 10K rows | 500 rows | < 64MB |
| 10K - 100K rows | 1,000 rows | < 128MB |
| > 100K rows | 2,000 rows | < 256MB |

### Best Practices

1. **Balance chunk size with memory**
   ```php
   // Small chunks = more queries, less memory
   $table->chunk(100); // 10 queries for 1K rows, ~10MB memory
   
   // Large chunks = fewer queries, more memory
   $table->chunk(1000); // 1 query for 1K rows, ~100MB memory
   ```

2. **Combine with server-side processing**
   ```php
   $table->setServerSide(true)
       ->chunk(500); // Load only current page in chunks
   ```

3. **Use with eager loading**
   ```php
   $table->relations(new User(), 'role', 'name')
       ->chunk(500); // Chunk both main and related data
   ```

4. **Monitor memory usage**
   ```php
   $startMemory = memory_get_usage();
   $table->chunk(500)->render();
   $endMemory = memory_get_usage();
   $used = ($endMemory - $startMemory) / 1024 / 1024;
   echo "Memory used: {$used}MB";
   ```

### Performance Impact

- **Without chunking**: Loads all data into memory (e.g., 256MB for 10K rows)
- **With chunking**: Loads data in batches (e.g., 64MB for 10K rows in 500-row chunks)
- **Memory reduction**: 75% less memory usage

---

## Query Optimization Techniques

### Select Only Required Columns

**Bad - Select All:**
```php
// Loads all columns (slow, high memory)
$table->model(User::class)
    ->render();
```

**Good - Select Specific:**
```php
// Loads only required columns (fast, low memory)
$table->model(User::class)
    ->setFields(['id', 'name', 'email', 'created_at'])
    ->render();
```

### Use Indexes for Sorting and Filtering

```php
// Ensure database indexes exist for:
// - Columns used in orderby()
// - Columns used in where()
// - Columns used in filterGroups()

// Example: Add index to users.created_at
Schema::table('users', function (Blueprint $table) {
    $table->index('created_at');
});

// Then use in table
$table->orderby('created_at', 'desc'); // Uses index, fast
```

### Optimize Where Conditions

```php
// ✅ Good - Use indexed columns
$table->where('status', '=', 'active')
    ->where('created_at', '>', '2024-01-01');

// ❌ Bad - Avoid functions in where
$table->where('YEAR(created_at)', '=', '2024'); // Can't use index

// ✅ Better - Use date range
$table->where('created_at', '>=', '2024-01-01')
    ->where('created_at', '<', '2025-01-01');
```

### Use Query Caching

```php
// Cache query results
$table->cache(300); // 5 minutes

// Cache with relationships
$table->relations(new User(), 'role', 'name')
    ->cache(300);
```

### Limit Result Set

```php
// Use server-side processing for large datasets
$table->setServerSide(true)
    ->displayRowsLimitOnLoad(25); // Load only 25 rows per page

// Or use client-side with limit
$table->setServerSide(false)
    ->displayRowsLimitOnLoad(100); // Load max 100 rows
```

### Performance Checklist

- [ ] Select only required columns with setFields()
- [ ] Add database indexes for sort/filter columns
- [ ] Use eager loading for relationships
- [ ] Enable caching for repeated queries
- [ ] Use server-side processing for large datasets
- [ ] Use chunk processing for memory efficiency
- [ ] Optimize where conditions to use indexes
- [ ] Monitor query count (target < 5 queries)

---

## Server-Side vs Client-Side Processing

### Server-Side Processing

**When to use:**
- Large datasets (> 1,000 rows)
- Real-time data that changes frequently
- Limited client-side resources

**Advantages:**
- Loads only current page (fast initial load)
- Low memory usage on client
- Handles millions of rows

**Disadvantages:**
- Requires AJAX endpoint
- Additional server requests for sorting/filtering
- Slightly slower user interactions

**Example:**
```php
$table->setServerSide(true)
    ->displayRowsLimitOnLoad(25)
    ->setAjaxUrl('/api/users/datatable')
    ->render();
```

### Client-Side Processing

**When to use:**
- Small datasets (< 1,000 rows)
- Static data that rarely changes
- Fast user interactions required

**Advantages:**
- Instant sorting/filtering (no server requests)
- Works offline
- Simpler implementation

**Disadvantages:**
- Loads all data at once (slow initial load)
- High memory usage on client
- Limited to ~10,000 rows

**Example:**
```php
$table->setServerSide(false)
    ->displayRowsLimitOnLoad(100)
    ->cache(3600) // Cache for 1 hour
    ->render();
```

### Decision Matrix

| Criteria | Server-Side | Client-Side |
|----------|-------------|-------------|
| Dataset size | > 1,000 rows | < 1,000 rows |
| Data volatility | High (real-time) | Low (static) |
| Initial load time | Fast (< 500ms) | Slow (1-2s) |
| Interaction speed | Medium (AJAX) | Fast (instant) |
| Memory usage | Low | High |
| Complexity | Medium | Low |

---

## Memory Management

### Monitor Memory Usage

```php
// Check memory before and after
$before = memory_get_usage(true);
$table->render();
$after = memory_get_usage(true);
$used = ($after - $before) / 1024 / 1024;

if ($used > 128) {
    // Memory usage too high, optimize
    $table->chunk(500); // Enable chunking
}
```

### Memory Optimization Techniques

1. **Use chunk processing**
   ```php
   $table->chunk(500); // Process in batches
   ```

2. **Select only required columns**
   ```php
   $table->setFields(['id', 'name', 'email']); // Not all columns
   ```

3. **Use server-side processing**
   ```php
   $table->setServerSide(true); // Load only current page
   ```

4. **Unset large variables**
   ```php
   $data = $table->getData();
   // Process data
   unset($data); // Free memory
   ```

5. **Use generators for large datasets**
   ```php
   // Internal implementation uses generators
   $table->chunk(500); // Automatically uses generators
   ```

### Memory Limits

| Environment | Memory Limit | Max Rows (no chunking) | Max Rows (with chunking) |
|-------------|--------------|------------------------|--------------------------|
| Shared hosting | 64MB | ~500 | ~5,000 |
| VPS | 128MB | ~1,000 | ~10,000 |
| Dedicated | 256MB+ | ~2,000 | ~100,000+ |

---

## Performance Monitoring

### Enable Query Logging

```php
// Enable query log
DB::enableQueryLog();

// Render table
$table->render();

// Get queries
$queries = DB::getQueryLog();
echo "Query count: " . count($queries) . "\n";

foreach ($queries as $query) {
    echo "SQL: {$query['query']}\n";
    echo "Time: {$query['time']}ms\n";
}
```

### Measure Execution Time

```php
$start = microtime(true);
$html = $table->render();
$end = microtime(true);
$time = ($end - $start) * 1000;

echo "Render time: {$time}ms\n";

// Target: < 500ms for 1K rows
if ($time > 500) {
    // Optimize: enable caching, chunking, or server-side
}
```

### Performance Metrics

Track these metrics for optimization:

1. **Query Count**: Target < 5 queries
2. **Execution Time**: Target < 500ms for 1K rows
3. **Memory Usage**: Target < 128MB for 10K rows
4. **Cache Hit Ratio**: Target > 80%

### Laravel Telescope

Use Laravel Telescope for detailed performance monitoring:

```bash
composer require laravel/telescope
php artisan telescope:install
php artisan migrate
```

Then monitor:
- Query count and execution time
- Memory usage
- Cache hits/misses
- Slow queries

### Performance Testing

```php
// Run performance test
php artisan test --filter=PerformanceTest

// Benchmark legacy vs enhanced
php artisan benchmark:compare
```

---

## Quick Reference

### Performance Optimization Checklist

- [ ] Enable caching for repeated queries
- [ ] Use eager loading for relationships
- [ ] Select only required columns
- [ ] Add database indexes for sort/filter columns
- [ ] Use server-side processing for large datasets
- [ ] Enable chunk processing for memory efficiency
- [ ] Monitor query count (target < 5)
- [ ] Monitor execution time (target < 500ms)
- [ ] Monitor memory usage (target < 128MB)
- [ ] Achieve cache hit ratio > 80%

### Common Performance Issues

| Issue | Symptom | Solution |
|-------|---------|----------|
| N+1 queries | 100+ queries | Use eager loading |
| Slow initial load | > 2s load time | Enable server-side processing |
| High memory usage | > 256MB | Enable chunk processing |
| Slow sorting/filtering | > 1s response | Add database indexes |
| Repeated slow queries | Same query slow | Enable caching |

### Performance Targets

| Metric | Target | Excellent |
|--------|--------|-----------|
| Query count | < 5 | < 3 |
| Execution time (1K rows) | < 500ms | < 200ms |
| Execution time (10K rows) | < 2s | < 1s |
| Memory usage (10K rows) | < 128MB | < 64MB |
| Cache hit ratio | > 80% | > 95% |

---

## Additional Resources

- [Laravel Query Optimization](https://laravel.com/docs/queries#optimizing-queries)
- [Laravel Eager Loading](https://laravel.com/docs/eloquent-relationships#eager-loading)
- [Laravel Caching](https://laravel.com/docs/cache)
- [DataTables Performance](https://datatables.net/manual/server-side)
- [MySQL Query Optimization](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)
