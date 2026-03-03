# FilterGroups Bi-Directional Cascade Examples

## Overview

This document provides comprehensive examples for using the `filterGroups()` method with bi-directional cascade support.

## Basic Usage

### Example 1: Simple Filter (No Cascade)

```php
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Simple filter without cascade
    $table->filterGroups('status', 'selectbox');
    
    $table->setFields(['name:Name', 'email:Email', 'status:Status']);
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

**Behavior**: Status filter works independently, no cascade to other filters.

---

### Example 2: Forward Cascade Only (Existing Behavior)

```php
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Forward cascade: selecting name updates email and date
    $table->filterGroups('name', 'selectbox', true);
    $table->filterGroups('email', 'selectbox', true);
    $table->filterGroups('created_at', 'datebox', true);
    
    $table->setFields(['name:Name', 'email:Email', 'created_at:Created']);
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

**Behavior**: 
- Selecting `name` updates `email` and `created_at`
- Selecting `email` updates `created_at`
- Selecting `created_at` doesn't update anything (last filter)

---

## Bi-Directional Cascade

### Example 3: Enable Bi-Directional Per Filter

```php
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Enable bi-directional cascade per filter (4th parameter = true)
    $table->filterGroups('name', 'selectbox', true, true);
    $table->filterGroups('email', 'selectbox', true, true);
    $table->filterGroups('created_at', 'datebox', true, true);
    
    $table->setFields(['name:Name', 'email:Email', 'created_at:Created']);
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

**Behavior**:
- Selecting `name` updates `email` and `created_at` (downstream)
- Selecting `email` updates `name` (upstream) AND `created_at` (downstream)
- Selecting `created_at` updates `name` and `email` (upstream)

---

### Example 4: Enable Bi-Directional Globally

```php
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Enable bi-directional cascade globally
    $table->setBidirectionalCascade(true);
    
    // All filters now have bi-directional cascade
    $table->filterGroups('name', 'selectbox', true);
    $table->filterGroups('email', 'selectbox', true);
    $table->filterGroups('created_at', 'datebox', true);
    
    $table->setFields(['name:Name', 'email:Email', 'created_at:Created']);
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

**Behavior**: Same as Example 3, but configured globally instead of per filter.

---

### Example 5: Mixed Cascade Modes

```php
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Mix forward and bi-directional cascade
    $table->filterGroups('name', 'selectbox', true, true);      // Bi-directional
    $table->filterGroups('email', 'selectbox', true, false);    // Forward only
    $table->filterGroups('status', 'selectbox', true, true);    // Bi-directional
    $table->filterGroups('created_at', 'datebox', true, false); // Forward only
    
    $table->setFields(['name:Name', 'email:Email', 'status:Status', 'created_at:Created']);
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

**Behavior**:
- `name` (bi-directional): Updates all filters
- `email` (forward only): Updates `status` and `created_at` only
- `status` (bi-directional): Updates all filters
- `created_at` (forward only): No cascade (last filter)

---

## Advanced Cascade Patterns

### Example 6: Specific Column Cascade

```php
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new Address());
    
    // Cascade to specific columns
    $table->filterGroups('province', 'selectbox', 'city', true);
    $table->filterGroups('city', 'selectbox', 'district', true);
    $table->filterGroups('district', 'selectbox');
    
    $table->setFields(['province:Province', 'city:City', 'district:District']);
    $table->format();
    
    return view('addresses.index', ['table' => $table]);
}
```

**Behavior**:
- Selecting `province` updates `city` (and `city` updates `province` due to bi-directional)
- Selecting `city` updates `district` (and `district` updates `city` due to bi-directional)
- Selecting `district` updates `city` (which then updates `province`)

---

### Example 7: Multiple Column Cascade

```php
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new Product());
    
    // Cascade to multiple specific columns
    $table->filterGroups('category', 'selectbox', ['brand', 'price_range'], true);
    $table->filterGroups('brand', 'selectbox', ['category', 'price_range'], true);
    $table->filterGroups('price_range', 'selectbox', ['category', 'brand'], true);
    
    $table->setFields(['category:Category', 'brand:Brand', 'price_range:Price Range']);
    $table->format();
    
    return view('products.index', ['table' => $table]);
}
```

**Behavior**: All three filters update each other in a fully connected graph.

---

### Example 8: Complex Relationships with setFilterRelationships()

```php
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new Address());
    
    // Define complex relationships
    $table->setFilterRelationships([
        'province' => ['city', 'district'],
        'city' => ['province', 'district'],
        'district' => ['province', 'city']
    ]);
    
    // Add filters (relationships already defined)
    $table->filterGroups('province', 'selectbox');
    $table->filterGroups('city', 'selectbox');
    $table->filterGroups('district', 'selectbox');
    
    $table->setFields(['province:Province', 'city:City', 'district:District']);
    $table->format();
    
    return view('addresses.index', ['table' => $table]);
}
```

**Behavior**: Relationships defined centrally, cleaner code for complex scenarios.

---

## Date Filter Examples

### Example 9: Date Filter with Bi-Directional Cascade

```php
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new Order());
    
    // Date filter with bi-directional cascade
    $table->filterGroups('customer_name', 'selectbox', true, true);
    $table->filterGroups('status', 'selectbox', true, true);
    $table->filterGroups('order_date', 'datebox', true, true);
    
    $table->setFields(['customer_name:Customer', 'status:Status', 'order_date:Order Date']);
    $table->format();
    
    return view('orders.index', ['table' => $table]);
}
```

**Behavior**:
- Selecting `order_date` updates `customer_name` and `status` with customers/statuses that have orders on that date
- Selecting `customer_name` updates `order_date` to show only dates when that customer placed orders
- Flatpickr automatically updates with available dates

---

### Example 10: Date Range Filter

```php
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new Transaction());
    
    // Date range filter with bi-directional cascade
    $table->filterGroups('account', 'selectbox', true, true);
    $table->filterGroups('type', 'selectbox', true, true);
    $table->filterGroups('date_range', 'daterangebox', true, true);
    
    $table->setFields(['account:Account', 'type:Type', 'date_range:Date Range']);
    $table->format();
    
    return view('transactions.index', ['table' => $table]);
}
```

**Behavior**: Date range filter updates and is updated by other filters.

---

## Performance Optimization Examples

### Example 11: With Database Indexes

```php
// Migration file
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        // Add indexes for filter columns
        $table->index('name');
        $table->index('email');
        $table->index('created_at');
        
        // Composite indexes for common combinations
        $table->index(['name', 'email']);
        $table->index(['name', 'created_at']);
    });
}

// Controller
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Filters will use indexes for better performance
    $table->filterGroups('name', 'selectbox', true, true);
    $table->filterGroups('email', 'selectbox', true, true);
    $table->filterGroups('created_at', 'datebox', true, true);
    
    $table->setFields(['name:Name', 'email:Email', 'created_at:Created']);
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

---

### Example 12: With Caching

```php
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new Product());
    
    // Enable caching for better performance
    $table->cache(300); // Cache for 5 minutes
    
    // Bi-directional cascade with caching
    $table->filterGroups('category', 'selectbox', true, true);
    $table->filterGroups('brand', 'selectbox', true, true);
    $table->filterGroups('status', 'selectbox', true, true);
    
    $table->setFields(['category:Category', 'brand:Brand', 'status:Status']);
    $table->format();
    
    return view('products.index', ['table' => $table]);
}
```

---

## Real-World Use Cases

### Example 13: E-Commerce Product Filtering

```php
public function index(TableBuilder $table): View
{
    $table->setContext('public'); // Public-facing
    $table->setModel(new Product());
    
    // Enable bi-directional cascade globally
    $table->setBidirectionalCascade(true);
    
    // Product filters
    $table->filterGroups('category', 'selectbox', true);
    $table->filterGroups('brand', 'selectbox', true);
    $table->filterGroups('price_range', 'selectbox', true);
    $table->filterGroups('color', 'selectbox', true);
    $table->filterGroups('size', 'selectbox', true);
    
    $table->setFields([
        'name:Product',
        'category:Category',
        'brand:Brand',
        'price:Price',
        'color:Color',
        'size:Size'
    ]);
    
    $table->format();
    
    return view('shop.products', ['table' => $table]);
}
```

**User Experience**: Users can start filtering by any attribute (color, size, brand, etc.) and all other filters update to show only valid combinations.

---

### Example 14: HR Employee Directory

```php
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new Employee());
    
    // Enable bi-directional cascade
    $table->setBidirectionalCascade(true);
    
    // Employee filters
    $table->filterGroups('department', 'selectbox', true);
    $table->filterGroups('position', 'selectbox', true);
    $table->filterGroups('location', 'selectbox', true);
    $table->filterGroups('employment_type', 'selectbox', true);
    $table->filterGroups('hire_date', 'datebox', true);
    
    $table->setFields([
        'name:Name',
        'department:Department',
        'position:Position',
        'location:Location',
        'employment_type:Type',
        'hire_date:Hire Date'
    ]);
    
    $table->format();
    
    return view('hr.employees', ['table' => $table]);
}
```

---

### Example 15: Reporting Dashboard

```php
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new SalesReport());
    
    // Complex filter relationships for reporting
    $table->setFilterRelationships([
        'region' => ['country', 'city', 'salesperson'],
        'country' => ['region', 'city', 'salesperson'],
        'city' => ['region', 'country', 'salesperson'],
        'salesperson' => ['region', 'country', 'city'],
        'product_category' => ['product'],
        'product' => ['product_category'],
    ]);
    
    // Add filters
    $table->filterGroups('region', 'selectbox');
    $table->filterGroups('country', 'selectbox');
    $table->filterGroups('city', 'selectbox');
    $table->filterGroups('salesperson', 'selectbox');
    $table->filterGroups('product_category', 'selectbox');
    $table->filterGroups('product', 'selectbox');
    $table->filterGroups('date_range', 'daterangebox', true, true);
    
    $table->setFields([
        'region:Region',
        'country:Country',
        'city:City',
        'salesperson:Salesperson',
        'product:Product',
        'sales:Sales',
        'date:Date'
    ]);
    
    $table->format();
    
    return view('reports.sales', ['table' => $table]);
}
```

---

## Testing Examples

### Example 16: Unit Test

```php
public function test_filter_groups_accepts_bidirectional_parameter()
{
    $table = new TableBuilder();
    $table->setModel(new User());
    
    // Test with bidirectional = true
    $table->filterGroups('name', 'selectbox', true, true);
    
    $filters = $table->getFilterGroups();
    
    $this->assertCount(1, $filters);
    $this->assertEquals('name', $filters[0]['column']);
    $this->assertEquals('selectbox', $filters[0]['type']);
    $this->assertTrue($filters[0]['relate']);
    $this->assertTrue($filters[0]['bidirectional']);
}

public function test_filter_groups_backward_compatible()
{
    $table = new TableBuilder();
    $table->setModel(new User());
    
    // Test without bidirectional parameter (backward compatible)
    $table->filterGroups('name', 'selectbox', true);
    
    $filters = $table->getFilterGroups();
    
    $this->assertCount(1, $filters);
    $this->assertFalse($filters[0]['bidirectional']); // Default is false
}
```

---

## Configuration Examples

### Example 17: Environment Configuration

```env
# .env file

# Enable bi-directional cascade globally
CANVASTACK_BIDIRECTIONAL_CASCADE=true

# Debounce delay for filter changes (ms)
CANVASTACK_FILTER_DEBOUNCE=300

# Frontend cache TTL (seconds)
CANVASTACK_FILTER_CACHE_TTL=300

# Max cascade depth (prevent infinite loops)
CANVASTACK_MAX_CASCADE_DEPTH=10

# Show cascade indicators
CANVASTACK_SHOW_CASCADE_INDICATORS=true
```

```php
// Controller - uses environment configuration
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Bi-directional cascade enabled via environment
    // No need to call setBidirectionalCascade(true)
    $table->filterGroups('name', 'selectbox', true);
    $table->filterGroups('email', 'selectbox', true);
    $table->filterGroups('created_at', 'datebox', true);
    
    $table->setFields(['name:Name', 'email:Email', 'created_at:Created']);
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

---

## Migration Examples

### Example 18: Migrating from Forward-Only to Bi-Directional

```php
// Before (forward-only cascade)
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    $table->filterGroups('name', 'selectbox', true);
    $table->filterGroups('email', 'selectbox', true);
    $table->filterGroups('created_at', 'datebox', true);
    
    $table->setFields(['name:Name', 'email:Email', 'created_at:Created']);
    $table->format();
    
    return view('users.index', ['table' => $table]);
}

// After (bi-directional cascade) - Option 1: Per filter
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Just add 4th parameter = true
    $table->filterGroups('name', 'selectbox', true, true);
    $table->filterGroups('email', 'selectbox', true, true);
    $table->filterGroups('created_at', 'datebox', true, true);
    
    $table->setFields(['name:Name', 'email:Email', 'created_at:Created']);
    $table->format();
    
    return view('users.index', ['table' => $table]);
}

// After (bi-directional cascade) - Option 2: Global
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Add one line at the top
    $table->setBidirectionalCascade(true);
    
    $table->filterGroups('name', 'selectbox', true);
    $table->filterGroups('email', 'selectbox', true);
    $table->filterGroups('created_at', 'datebox', true);
    
    $table->setFields(['name:Name', 'email:Email', 'created_at:Created']);
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

---

## Best Practices

### ✅ DO

1. **Add database indexes** for filter columns
2. **Enable caching** for better performance
3. **Use bi-directional cascade** when users need flexible filtering
4. **Test with production data** to verify performance
5. **Use setFilterRelationships()** for complex scenarios

### ❌ DON'T

1. **Don't enable bi-directional** if filters have strict hierarchy
2. **Don't forget indexes** on filter columns
3. **Don't create circular dependencies** without testing
4. **Don't skip performance testing** with large datasets
5. **Don't mix too many cascade modes** (keep it simple)

---

## Troubleshooting

### Issue: Filters not updating

**Solution**: Check that `relate` parameter is set correctly and bi-directional flag is enabled.

### Issue: Slow performance

**Solution**: Add database indexes, enable caching, check query optimization.

### Issue: Circular cascade loops

**Solution**: System has built-in protection, but verify filter relationships are logical.

### Issue: Date filter not updating

**Solution**: Ensure Flatpickr is properly initialized and date column is indexed.

---

## Resources

- [Bi-Directional Cascade Requirements](../../.kiro/specs/bi-directional-filter-cascade/requirements.md)
- [Bi-Directional Cascade Design](../../.kiro/specs/bi-directional-filter-cascade/design.md)
- [TableBuilder API Documentation](../api/table.md)
- [Filter Modal Component](../../resources/views/components/table/filter-modal.blade.php)

---

**Last Updated**: 2026-03-03  
**Version**: 1.0.0  
**Status**: Complete
