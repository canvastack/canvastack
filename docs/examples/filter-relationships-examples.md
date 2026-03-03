# Filter Relationships Examples

This document provides comprehensive examples for using the `setFilterRelationships()` method to define complex filter cascade relationships.

## Overview

The `setFilterRelationships()` method allows you to define which filters should cascade to which other filters when changed. This enables complex multi-directional cascade relationships beyond simple top-to-bottom or bi-directional cascading.

## Basic Usage

### Example 1: Simple Parent-Child Relationships

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Product());
    
    // Define relationships: category affects subcategory and product
    $table->setFilterRelationships([
        'category' => ['subcategory', 'product'],
        'subcategory' => ['product']
    ]);
    
    // Add filters
    $table->filterGroups('category', 'selectbox')
        ->filterGroups('subcategory', 'selectbox')
        ->filterGroups('product', 'selectbox');
    
    $table->format();
    
    return view('products.index', ['table' => $table]);
}
```

**Behavior**:
- When user selects a category, both subcategory and product filters update
- When user selects a subcategory, only product filter updates

---

### Example 2: Bi-Directional Relationships (Province/City/District)

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Location());
    
    // Define bi-directional relationships
    $table->setFilterRelationships([
        'province' => ['city', 'district'],  // province affects city and district
        'city' => ['province', 'district'],  // city affects province and district
        'district' => ['province', 'city']   // district affects province and city
    ]);
    
    // Add filters
    $table->filterGroups('province', 'selectbox')
        ->filterGroups('city', 'selectbox')
        ->filterGroups('district', 'selectbox');
    
    $table->format();
    
    return view('locations.index', ['table' => $table]);
}
```

**Behavior**:
- User can select any filter first (province, city, or district)
- All other filters update based on the selection
- Maintains data integrity across all filters

---

### Example 3: Complex Multi-Level Relationships

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Employee());
    
    // Define complex relationships
    $table->setFilterRelationships([
        'department' => ['team', 'role', 'manager'],
        'team' => ['role', 'manager'],
        'role' => ['manager'],
        'manager' => ['department', 'team']
    ]);
    
    // Add filters
    $table->filterGroups('department', 'selectbox')
        ->filterGroups('team', 'selectbox')
        ->filterGroups('role', 'selectbox')
        ->filterGroups('manager', 'selectbox');
    
    $table->format();
    
    return view('employees.index', ['table' => $table]);
}
```

**Behavior**:
- Department selection cascades to team, role, and manager
- Team selection cascades to role and manager
- Role selection cascades to manager
- Manager selection cascades back to department and team

---

## Advanced Usage

### Example 4: Combining with Bi-Directional Cascade

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Order());
    
    // Enable bi-directional cascade globally
    $table->setBidirectionalCascade(true);
    
    // Define specific relationships
    $table->setFilterRelationships([
        'customer' => ['product', 'status'],
        'product' => ['category', 'status'],
        'status' => ['customer', 'product']
    ]);
    
    // Add filters
    $table->filterGroups('customer', 'selectbox', true)
        ->filterGroups('product', 'selectbox', true)
        ->filterGroups('category', 'selectbox', true)
        ->filterGroups('status', 'selectbox', true);
    
    $table->format();
    
    return view('orders.index', ['table' => $table]);
}
```

**Behavior**:
- Bi-directional cascade is enabled globally
- Custom relationships define specific cascade paths
- Provides maximum flexibility for complex filtering scenarios

---

### Example 5: Date-Based Relationships

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Transaction());
    
    // Define relationships including date filters
    $table->setFilterRelationships([
        'account' => ['transaction_type', 'date_range'],
        'transaction_type' => ['date_range'],
        'date_range' => ['account', 'transaction_type']
    ]);
    
    // Add filters
    $table->filterGroups('account', 'selectbox')
        ->filterGroups('transaction_type', 'selectbox')
        ->filterGroups('date_range', 'daterangebox');
    
    $table->format();
    
    return view('transactions.index', ['table' => $table]);
}
```

**Behavior**:
- Account selection updates transaction types and available date ranges
- Transaction type selection updates available date ranges
- Date range selection updates available accounts and transaction types

---

## Method Chaining

### Example 6: Fluent Interface

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin')
        ->setModel(new Product())
        ->setBidirectionalCascade(true)
        ->setFilterRelationships([
            'brand' => ['category', 'price_range'],
            'category' => ['brand', 'price_range'],
            'price_range' => ['brand', 'category']
        ])
        ->filterGroups('brand', 'selectbox', true)
        ->filterGroups('category', 'selectbox', true)
        ->filterGroups('price_range', 'selectbox', true)
        ->format();
    
    return view('products.index', ['table' => $table]);
}
```

---

## Validation Examples

### Example 7: Handling Invalid Relationships

```php
public function index(TableBuilder $table)
{
    try {
        // This will throw InvalidArgumentException
        $table->setFilterRelationships([
            123 => ['city'], // Invalid: numeric key
        ]);
    } catch (\InvalidArgumentException $e) {
        // Handle error
        return back()->withErrors(['filter' => $e->getMessage()]);
    }
}
```

### Example 8: Validating Related Columns

```php
public function index(TableBuilder $table)
{
    try {
        // This will throw InvalidArgumentException
        $table->setFilterRelationships([
            'province' => 'city', // Invalid: string instead of array
        ]);
    } catch (\InvalidArgumentException $e) {
        // Handle error
        return back()->withErrors(['filter' => $e->getMessage()]);
    }
}
```

---

## Best Practices

### 1. Keep Relationships Simple

```php
// ✅ GOOD: Clear, simple relationships
$table->setFilterRelationships([
    'category' => ['subcategory'],
    'subcategory' => ['product']
]);

// ❌ BAD: Overly complex relationships
$table->setFilterRelationships([
    'category' => ['subcategory', 'product', 'brand', 'supplier', 'warehouse'],
    // Too many relationships make it hard to understand
]);
```

### 2. Avoid Circular Dependencies

```php
// ✅ GOOD: Bi-directional but clear
$table->setFilterRelationships([
    'province' => ['city'],
    'city' => ['province']
]);

// ⚠️ CAUTION: Complex circular relationships
$table->setFilterRelationships([
    'A' => ['B', 'C'],
    'B' => ['C', 'A'],
    'C' => ['A', 'B']
]);
// This works but can be confusing
```

### 3. Document Your Relationships

```php
// ✅ GOOD: Well-documented relationships
$table->setFilterRelationships([
    // When user selects a department, update team and role filters
    'department' => ['team', 'role'],
    
    // When user selects a team, update role filter
    'team' => ['role']
]);
```

### 4. Test with Real Data

```php
// ✅ GOOD: Test with production-like data
// 1. Create test data with various relationships
// 2. Test all filter combinations
// 3. Verify cascade behavior
// 4. Check performance with large datasets
```

---

## Performance Considerations

### Example 9: Optimizing Relationships

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Product());
    
    // Add database indexes for filter columns
    // Migration:
    // Schema::table('products', function (Blueprint $table) {
    //     $table->index('category_id');
    //     $table->index('brand_id');
    //     $table->index(['category_id', 'brand_id']);
    // });
    
    // Define relationships
    $table->setFilterRelationships([
        'category' => ['brand', 'price_range'],
        'brand' => ['category', 'price_range']
    ]);
    
    // Enable caching for better performance
    $table->cache(300); // 5 minutes
    
    // Add filters
    $table->filterGroups('category', 'selectbox', true)
        ->filterGroups('brand', 'selectbox', true)
        ->filterGroups('price_range', 'selectbox', true);
    
    $table->format();
    
    return view('products.index', ['table' => $table]);
}
```

---

## Troubleshooting

### Issue 1: Relationships Not Working

**Problem**: Filters don't cascade as expected

**Solution**:
```php
// 1. Verify relationships are defined correctly
$config = $table->getConfig();
dd($config['filter_relationships']);

// 2. Check that filters are added with correct column names
$table->filterGroups('province', 'selectbox') // Column name must match
    ->filterGroups('city', 'selectbox');

// 3. Verify bi-directional cascade is enabled if needed
$table->setBidirectionalCascade(true);
```

### Issue 2: Slow Performance

**Problem**: Cascade operations are slow

**Solution**:
```php
// 1. Add database indexes
Schema::table('locations', function (Blueprint $table) {
    $table->index('province_id');
    $table->index('city_id');
    $table->index(['province_id', 'city_id']);
});

// 2. Enable caching
$table->cache(300);

// 3. Limit number of relationships
$table->setFilterRelationships([
    'province' => ['city'], // Limit to essential relationships
]);
```

### Issue 3: Invalid Filter Values

**Problem**: Filter values become invalid after cascade

**Solution**:
```php
// The system automatically clears invalid values
// No action needed - this is expected behavior

// To debug:
// 1. Check browser console for warnings
// 2. Verify database data integrity
// 3. Check filter relationships are correct
```

---

## API Reference

### Method Signature

```php
public function setFilterRelationships(array $relationships): self
```

### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `$relationships` | array | Yes | Associative array mapping filter columns to arrays of related filter columns |

### Returns

Returns `self` for method chaining.

### Throws

- `\InvalidArgumentException` - If relationships array is invalid
- `\InvalidArgumentException` - If column names are not strings
- `\InvalidArgumentException` - If related columns are not arrays
- `\InvalidArgumentException` - If related column names are not strings

### Example

```php
$table->setFilterRelationships([
    'province' => ['city', 'district'],
    'city' => ['province', 'district'],
    'district' => ['province', 'city']
]);
```

---

## Related Documentation

- [Bi-Directional Filter Cascade](../features/bi-directional-cascade.md)
- [Filter Configuration](../components/table-builder.md#filters)
- [Performance Optimization](../guides/performance.md)
- [Testing Filters](../guides/testing.md#filter-tests)

---

**Last Updated**: 2026-03-03  
**Version**: 1.0.0  
**Status**: Published
