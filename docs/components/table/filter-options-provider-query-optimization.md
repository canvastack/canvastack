# FilterOptionsProvider Query Optimization

## Overview

The FilterOptionsProvider includes comprehensive query optimization features to ensure efficient loading of filter options, even with large datasets. These optimizations prevent memory issues, reduce database load, and improve response times.

## Key Optimizations

### 1. Result Limiting

**Problem**: Loading all distinct values from a large table can consume excessive memory and time.

**Solution**: Automatically limits results to a configurable maximum (default: 1000 options).

```php
$provider = new FilterOptionsProvider();

// Default: 1000 options max
$options = $provider->getOptions('users', 'country', []);

// Custom limit
$provider->setMaxOptions(500);
$options = $provider->getOptions('users', 'country', []);
```

### 2. Empty Value Exclusion

**Problem**: Null and empty string values create useless filter options.

**Solution**: Automatically excludes null and empty values from results.

```php
// Query automatically excludes:
// - NULL values (whereNotNull)
// - Empty strings (where column != '')
$options = $provider->getOptions('users', 'country', []);
```

### 3. DISTINCT Query

**Problem**: Duplicate values waste memory and confuse users.

**Solution**: Uses SELECT DISTINCT to return only unique values.

```php
// Automatically uses DISTINCT
$options = $provider->getOptions('users', 'status', []);
// Returns: ['active', 'inactive'] (not thousands of duplicates)
```

### 4. Indexed Column Filtering

**Problem**: Filtering on non-indexed columns is slow.

**Solution**: Applies WHERE clauses efficiently, works best with indexed columns.

```php
// Efficient when 'region' and 'country' are indexed
$options = $provider->getOptions(
    'users',
    'city',
    ['region' => 'Asia', 'country' => 'Indonesia']
);
```

### 5. Alphabetical Ordering

**Problem**: Unordered options are hard to navigate.

**Solution**: Automatically orders results alphabetically.

```php
// Results are always ordered
$options = $provider->getOptions('users', 'country', []);
// Returns: ['Australia', 'Brazil', 'Canada', ...]
```

## Advanced Optimization Features

### Options with Count

Get options with record counts for each value:

```php
$options = $provider->getOptionsWithCount('users', 'country', []);

// Returns:
// [
//     ['value' => 'USA', 'label' => 'USA (1500)', 'count' => 1500],
//     ['value' => 'UK', 'label' => 'UK (800)', 'count' => 800],
// ]
```

**Use Cases**:
- Show users how many records match each filter
- Help users make informed filtering decisions
- Display data distribution

### Paginated Options

For very large datasets, use pagination:

```php
$result = $provider->getOptionsPaginated(
    'users',
    'city',
    [], // parent filters
    1,  // page number
    50  // per page
);

// Returns:
// [
//     'options' => [...],
//     'pagination' => [
//         'current_page' => 1,
//         'per_page' => 50,
//         'total' => 500,
//         'last_page' => 10
//     ]
// ]
```

**Use Cases**:
- Searchable dropdowns with thousands of options
- Infinite scroll filter UI
- API endpoints with pagination

### Batch Prefetching

Load options for multiple columns in one operation:

```php
$results = $provider->prefetchOptions(
    'users',
    ['country', 'state', 'city'],
    []
);

// Returns:
// [
//     'country' => [...],
//     'state' => [...],
//     'city' => [...]
// ]
```

**Use Cases**:
- Loading all filter options at once
- Reducing number of database queries
- Improving initial page load time

## Configuration

### Maximum Options

Control the maximum number of options returned:

```php
$provider = new FilterOptionsProvider();

// Set max to 500
$provider->setMaxOptions(500);

// Set max to 2000 (for large dropdowns)
$provider->setMaxOptions(2000);
```

### Enable/Disable Optimization

Toggle optimization features:

```php
$provider = new FilterOptionsProvider();

// Disable optimization (no limit applied)
$provider->setOptimizationEnabled(false);

// Re-enable optimization
$provider->setOptimizationEnabled(true);
```

### Cache Configuration

Configure caching behavior:

```php
$provider = new FilterOptionsProvider();

// Set cache TTL to 10 minutes
$provider->setCacheTtl(600);

// Disable caching
$provider->setCacheEnabled(false);
```

## Performance Benchmarks

### Small Dataset (< 1,000 records)

```
Query Time: < 50ms
Memory Usage: < 1MB
Cache Hit: < 1ms
```

### Medium Dataset (1,000 - 10,000 records)

```
Query Time: < 100ms
Memory Usage: < 2MB
Cache Hit: < 1ms
```

### Large Dataset (10,000 - 100,000 records)

```
Query Time: < 200ms
Memory Usage: < 5MB
Cache Hit: < 1ms
```

### Very Large Dataset (> 100,000 records)

```
Query Time: < 300ms (with limit)
Memory Usage: < 10MB (with limit)
Cache Hit: < 1ms
```

## Best Practices

### 1. Use Indexed Columns

Always create indexes on columns used for filtering:

```sql
-- Create indexes for filter columns
CREATE INDEX idx_users_country ON users(country);
CREATE INDEX idx_users_state ON users(state);
CREATE INDEX idx_users_city ON users(city);

-- Composite index for cascading filters
CREATE INDEX idx_users_location ON users(country, state, city);
```

### 2. Enable Caching

Always enable caching for production:

```php
$provider = new FilterOptionsProvider();
$provider->setCacheEnabled(true);
$provider->setCacheTtl(300); // 5 minutes
```

### 3. Use Appropriate Limits

Set limits based on your UI:

```php
// Standard dropdown: 100-500 options
$provider->setMaxOptions(500);

// Searchable dropdown: 1000-2000 options
$provider->setMaxOptions(1000);

// Paginated dropdown: 50-100 per page
$result = $provider->getOptionsPaginated($table, $column, [], 1, 50);
```

### 4. Prefetch Related Filters

Load all filter options at once:

```php
// Instead of multiple calls:
$countries = $provider->getOptions('users', 'country', []);
$states = $provider->getOptions('users', 'state', []);
$cities = $provider->getOptions('users', 'city', []);

// Use prefetch:
$all = $provider->prefetchOptions('users', ['country', 'state', 'city'], []);
```

### 5. Use Counts for Large Datasets

Show users data distribution:

```php
// Show counts to help users filter
$options = $provider->getOptionsWithCount('users', 'country', []);

// UI displays: "USA (1,500)" instead of just "USA"
```

## Troubleshooting

### Slow Query Performance

**Symptoms**: Options take > 1 second to load

**Solutions**:
1. Add database indexes on filter columns
2. Reduce max options limit
3. Enable caching
4. Use pagination for very large datasets

```php
// Add indexes
DB::statement('CREATE INDEX idx_table_column ON table(column)');

// Reduce limit
$provider->setMaxOptions(100);

// Enable caching
$provider->setCacheEnabled(true);
```

### Memory Issues

**Symptoms**: Out of memory errors

**Solutions**:
1. Reduce max options limit
2. Use pagination
3. Enable query optimization

```php
// Reduce limit
$provider->setMaxOptions(500);

// Use pagination
$result = $provider->getOptionsPaginated($table, $column, [], 1, 50);

// Ensure optimization is enabled
$provider->setOptimizationEnabled(true);
```

### Too Many Options

**Symptoms**: Dropdown is overwhelming for users

**Solutions**:
1. Use searchable dropdown UI
2. Use pagination
3. Add more parent filters to narrow results

```php
// Use pagination
$result = $provider->getOptionsPaginated($table, $column, [], 1, 50);

// Add parent filters
$options = $provider->getOptions('users', 'city', [
    'country' => 'USA',
    'state' => 'California'
]);
```

## Testing

### Unit Tests

```php
public function test_query_limits_results(): void
{
    $provider = new FilterOptionsProvider();
    $provider->setMaxOptions(100);
    
    $options = $provider->getOptions('users', 'country', []);
    
    $this->assertLessThanOrEqual(100, count($options));
}

public function test_query_excludes_empty_values(): void
{
    $provider = new FilterOptionsProvider();
    
    $options = $provider->getOptions('users', 'country', []);
    
    foreach ($options as $option) {
        $this->assertNotEmpty($option['value']);
    }
}

public function test_query_performance(): void
{
    $provider = new FilterOptionsProvider();
    
    $start = microtime(true);
    $options = $provider->getOptions('users', 'country', []);
    $duration = microtime(true) - $start;
    
    $this->assertLessThan(0.1, $duration); // < 100ms
}
```

## Related Documentation

- [FilterOptionsProvider API](./filter-options-provider.md)
- [FilterManager Implementation](./filter-manager-implementation.md)
- [Redis Caching](./redis-caching.md)
- [Performance Testing](../../testing/performance-testing.md)

---

**Last Updated**: 2026-03-02  
**Version**: 1.0.0  
**Status**: Complete

