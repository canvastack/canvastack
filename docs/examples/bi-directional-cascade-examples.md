# Bi-Directional Filter Cascade - Usage Examples

## 📦 Overview

This document provides comprehensive, tested examples for using the bi-directional filter cascade feature in CanvaStack TableBuilder. All examples are production-ready and follow best practices.

---

## 🎯 Basic Examples

### Example 1: Enable Globally

Enable bi-directional cascade for all filters in a table.

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;
use App\Models\User;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Enable bi-directional cascade globally
    $table->setBidirectionalCascade(true);
    
    // All filters will cascade in both directions
    $table->filterGroups('name', 'selectbox', true);
    $table->filterGroups('email', 'selectbox', true);
    $table->filterGroups('created_at', 'datebox', true);
    
    $table->setFields([
        'name:Name',
        'email:Email',
        'created_at:Created Date'
    ]);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

**How it works:**
- When user selects `created_at` (last filter), both `name` and `email` update (upstream cascade)
- When user selects `name` (first filter), both `email` and `created_at` update (downstream cascade)
- When user selects `email` (middle filter), both `name` and `created_at` update (bi-directional)

---

### Example 2: Enable Per Filter

Enable bi-directional cascade for specific filters only.

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Enable bi-directional for specific filters
    $table->filterGroups('name', 'selectbox', true, true);      // bidirectional = true
    $table->filterGroups('email', 'selectbox', true, true);     // bidirectional = true
    $table->filterGroups('created_at', 'datebox', true, true);  // bidirectional = true
    
    $table->setFields([
        'name:Name',
        'email:Email',
        'created_at:Created Date'
    ]);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

**When to use:**
- When you want fine-grained control over which filters cascade bi-directionally
- When mixing bi-directional and uni-directional filters

---

### Example 3: Mixed Cascade Directions

Combine bi-directional and uni-directional filters.

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Bi-directional filters
    $table->filterGroups('name', 'selectbox', true, true);
    $table->filterGroups('email', 'selectbox', true, true);
    
    // Uni-directional filter (existing behavior)
    $table->filterGroups('status', 'selectbox', true, false);
    
    // No cascade
    $table->filterGroups('role', 'selectbox', false, false);
    
    $table->setFields([
        'name:Name',
        'email:Email',
        'status:Status',
        'role:Role'
    ]);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

**Behavior:**
- `name` ↔ `email`: Bi-directional cascade
- `status`: Only cascades downstream (to filters after it)
- `role`: No cascade at all

---

## 🌍 Geographic Filters (Province → City → District)

### Example 4: Simple Geographic Cascade

```php
use App\Models\Location;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Location());
    
    // Enable bi-directional cascade
    $table->setBidirectionalCascade(true);
    
    // Geographic filters
    $table->filterGroups('province', 'selectbox', true);
    $table->filterGroups('city', 'selectbox', true);
    $table->filterGroups('district', 'selectbox', true);
    
    $table->setFields([
        'province:Province',
        'city:City',
        'district:District',
        'address:Address'
    ]);
    
    $table->format();
    
    return view('locations.index', ['table' => $table]);
}
```

**User Experience:**
- Select district first → Province and city options update
- Select city first → Province and district options update
- Select province first → City and district options update

---

### Example 5: Complex Geographic Relationships

Define explicit relationships between geographic filters.

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Location());
    
    // Define complex relationships
    $table->setFilterRelationships([
        'province' => ['city', 'district'],
        'city' => ['province', 'district'],
        'district' => ['province', 'city']
    ]);
    
    $table->filterGroups('province', 'selectbox');
    $table->filterGroups('city', 'selectbox');
    $table->filterGroups('district', 'selectbox');
    
    $table->setFields([
        'province:Province',
        'city:City',
        'district:District'
    ]);
    
    $table->format();
    
    return view('locations.index', ['table' => $table]);
}
```

**Benefits:**
- Explicit control over which filters affect each other
- More maintainable for complex relationships
- Better performance (only updates specified filters)

---

## 📅 Date Range Filters

### Example 6: Date Range with Other Filters

```php
use App\Models\Order;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Order());
    
    $table->setBidirectionalCascade(true);
    
    // Customer filter
    $table->filterGroups('customer_name', 'selectbox', true);
    
    // Product filter
    $table->filterGroups('product_name', 'selectbox', true);
    
    // Date range filter
    $table->filterGroups('order_date', 'datebox', true);
    
    $table->setFields([
        'order_number:Order #',
        'customer_name:Customer',
        'product_name:Product',
        'order_date:Date',
        'total:Total'
    ]);
    
    $table->format();
    
    return view('orders.index', ['table' => $table]);
}
```

**User Experience:**
- Select date first → Customer and product lists update to show only those with orders on that date
- Select customer first → Product and date options update to show only that customer's data
- Select product first → Customer and date options update to show only orders with that product

---

### Example 7: Date Range with Status Filter

```php
use App\Models\Transaction;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Transaction());
    
    $table->setBidirectionalCascade(true);
    
    $table->filterGroups('status', 'selectbox', true);
    $table->filterGroups('payment_method', 'selectbox', true);
    $table->filterGroups('transaction_date', 'daterangebox', true);
    
    $table->setFields([
        'transaction_id:ID',
        'status:Status',
        'payment_method:Payment',
        'transaction_date:Date',
        'amount:Amount'
    ]);
    
    $table->format();
    
    return view('transactions.index', ['table' => $table]);
}
```

---

## 🏢 Multi-Level Hierarchical Data

### Example 8: Department → Team → Employee

```php
use App\Models\Employee;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Employee());
    
    $table->setBidirectionalCascade(true);
    
    // Hierarchical filters
    $table->filterGroups('department', 'selectbox', true);
    $table->filterGroups('team', 'selectbox', true);
    $table->filterGroups('position', 'selectbox', true);
    
    $table->setFields([
        'name:Name',
        'department:Department',
        'team:Team',
        'position:Position',
        'hire_date:Hired'
    ]);
    
    $table->format();
    
    return view('employees.index', ['table' => $table]);
}
```

---

### Example 9: Category → Subcategory → Product

```php
use App\Models\Product;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Product());
    
    $table->setBidirectionalCascade(true);
    
    $table->filterGroups('category', 'selectbox', true);
    $table->filterGroups('subcategory', 'selectbox', true);
    $table->filterGroups('brand', 'selectbox', true);
    $table->filterGroups('status', 'selectbox', true);
    
    $table->setFields([
        'name:Product Name',
        'category:Category',
        'subcategory:Subcategory',
        'brand:Brand',
        'price:Price',
        'status:Status'
    ]);
    
    $table->format();
    
    return view('products.index', ['table' => $table]);
}
```

---

## 🎨 Advanced Patterns

### Example 10: With Custom Filter Options

Provide custom filter options instead of auto-generating from database.

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    $table->setBidirectionalCascade(true);
    
    // Custom status options
    $statusOptions = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'pending' => 'Pending'
    ];
    
    $table->filterGroups('status', 'selectbox', true);
    $table->filterGroups('role', 'selectbox', true);
    $table->filterGroups('created_at', 'datebox', true);
    
    $table->setFields([
        'name:Name',
        'status:Status',
        'role:Role',
        'created_at:Created'
    ]);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

---

### Example 11: With Eager Loading (Performance Optimization)

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Order());
    
    $table->setBidirectionalCascade(true);
    
    // Eager load relationships to prevent N+1 queries
    $table->eager(['customer', 'product', 'status']);
    
    $table->filterGroups('customer.name', 'selectbox', true);
    $table->filterGroups('product.name', 'selectbox', true);
    $table->filterGroups('status.name', 'selectbox', true);
    
    $table->setFields([
        'order_number:Order #',
        'customer.name:Customer',
        'product.name:Product',
        'status.name:Status',
        'total:Total'
    ]);
    
    $table->format();
    
    return view('orders.index', ['table' => $table]);
}
```

---

### Example 12: With Caching (Performance Optimization)

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Report());
    
    $table->setBidirectionalCascade(true);
    
    // Cache filter options for 5 minutes
    $table->cache(300);
    
    $table->filterGroups('region', 'selectbox', true);
    $table->filterGroups('category', 'selectbox', true);
    $table->filterGroups('period', 'datebox', true);
    
    $table->setFields([
        'region:Region',
        'category:Category',
        'period:Period',
        'revenue:Revenue'
    ]);
    
    $table->format();
    
    return view('reports.index', ['table' => $table]);
}
```

---

## 🔧 Configuration Examples

### Example 13: Using Environment Configuration

Set global defaults in `.env`:

```env
# Enable bi-directional cascade globally
CANVASTACK_BIDIRECTIONAL_CASCADE=true

# Debounce delay (ms)
CANVASTACK_FILTER_DEBOUNCE=300

# Frontend cache TTL (seconds)
CANVASTACK_FILTER_CACHE_TTL=300

# Show cascade indicators
CANVASTACK_SHOW_CASCADE_INDICATORS=true
```

Then in your controller:

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Bi-directional cascade is already enabled via config
    // No need to call setBidirectionalCascade(true)
    
    $table->filterGroups('name', 'selectbox', true);
    $table->filterGroups('email', 'selectbox', true);
    $table->filterGroups('created_at', 'datebox', true);
    
    $table->setFields([
        'name:Name',
        'email:Email',
        'created_at:Created'
    ]);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

---

### Example 14: Override Global Configuration

Override global config for specific table:

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Override global config (disable bi-directional for this table)
    $table->setBidirectionalCascade(false);
    
    // Use traditional uni-directional cascade
    $table->filterGroups('name', 'selectbox', true);
    $table->filterGroups('email', 'selectbox', true);
    
    $table->setFields([
        'name:Name',
        'email:Email'
    ]);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

---

## 🎭 Real-World Use Cases

### Example 15: E-Commerce Product Filtering

```php
use App\Models\Product;

public function index(TableBuilder $table)
{
    $table->setContext('public'); // Public-facing product catalog
    $table->setModel(new Product());
    
    $table->setBidirectionalCascade(true);
    
    // Product filters
    $table->filterGroups('category', 'selectbox', true);
    $table->filterGroups('brand', 'selectbox', true);
    $table->filterGroups('price_range', 'selectbox', true);
    $table->filterGroups('color', 'selectbox', true);
    $table->filterGroups('size', 'selectbox', true);
    
    $table->setFields([
        'image:Image',
        'name:Product',
        'category:Category',
        'brand:Brand',
        'price:Price',
        'stock:Stock'
    ]);
    
    $table->format();
    
    return view('shop.products', ['table' => $table]);
}
```

**User Experience:**
- Customer selects "Red" color → Only brands, categories, and sizes available in red are shown
- Customer selects "Nike" brand → Only categories, colors, and sizes available from Nike are shown
- Customer selects price range → All other filters update to show only products in that range

---

### Example 16: Real Estate Property Search

```php
use App\Models\Property;

public function search(TableBuilder $table)
{
    $table->setContext('public');
    $table->setModel(new Property());
    
    $table->setBidirectionalCascade(true);
    
    // Property search filters
    $table->filterGroups('city', 'selectbox', true);
    $table->filterGroups('district', 'selectbox', true);
    $table->filterGroups('property_type', 'selectbox', true);
    $table->filterGroups('bedrooms', 'selectbox', true);
    $table->filterGroups('price_range', 'selectbox', true);
    
    $table->setFields([
        'title:Property',
        'city:City',
        'district:District',
        'property_type:Type',
        'bedrooms:Beds',
        'price:Price'
    ]);
    
    $table->format();
    
    return view('properties.search', ['table' => $table]);
}
```

---

### Example 17: Job Board Filtering

```php
use App\Models\Job;

public function index(TableBuilder $table)
{
    $table->setContext('public');
    $table->setModel(new Job());
    
    $table->setBidirectionalCascade(true);
    
    // Job filters
    $table->filterGroups('industry', 'selectbox', true);
    $table->filterGroups('job_type', 'selectbox', true);
    $table->filterGroups('experience_level', 'selectbox', true);
    $table->filterGroups('location', 'selectbox', true);
    $table->filterGroups('salary_range', 'selectbox', true);
    
    $table->setFields([
        'title:Job Title',
        'company:Company',
        'industry:Industry',
        'location:Location',
        'salary_range:Salary',
        'posted_date:Posted'
    ]);
    
    $table->format();
    
    return view('jobs.index', ['table' => $table]);
}
```

---

### Example 18: Event Management System

```php
use App\Models\Event;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Event());
    
    $table->setBidirectionalCascade(true);
    
    // Event filters
    $table->filterGroups('venue', 'selectbox', true);
    $table->filterGroups('category', 'selectbox', true);
    $table->filterGroups('organizer', 'selectbox', true);
    $table->filterGroups('event_date', 'daterangebox', true);
    $table->filterGroups('status', 'selectbox', true);
    
    $table->setFields([
        'title:Event',
        'venue:Venue',
        'category:Category',
        'organizer:Organizer',
        'event_date:Date',
        'attendees:Attendees',
        'status:Status'
    ]);
    
    $table->format();
    
    return view('events.index', ['table' => $table]);
}
```

---

## 🚀 Performance Best Practices

### Example 19: Optimized for Large Datasets

```php
use App\Models\Transaction;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Transaction());
    
    $table->setBidirectionalCascade(true);
    
    // Performance optimizations
    $table->cache(600); // Cache for 10 minutes
    $table->eager(['customer', 'product']); // Prevent N+1 queries
    $table->chunk(100); // Process in chunks
    
    $table->filterGroups('customer.name', 'selectbox', true);
    $table->filterGroups('product.name', 'selectbox', true);
    $table->filterGroups('status', 'selectbox', true);
    $table->filterGroups('transaction_date', 'datebox', true);
    
    $table->setFields([
        'transaction_id:ID',
        'customer.name:Customer',
        'product.name:Product',
        'status:Status',
        'amount:Amount',
        'transaction_date:Date'
    ]);
    
    $table->format();
    
    return view('transactions.index', ['table' => $table]);
}
```

---

### Example 20: With Database Indexes

Ensure proper database indexes for filter columns:

```php
// Migration file
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFilterIndexesToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Single column indexes
            $table->index('name');
            $table->index('email');
            $table->index('created_at');
            
            // Composite indexes for common filter combinations
            $table->index(['name', 'email']);
            $table->index(['name', 'created_at']);
            $table->index(['email', 'created_at']);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['email']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['name', 'email']);
            $table->dropIndex(['name', 'created_at']);
            $table->dropIndex(['email', 'created_at']);
        });
    }
}
```

Then use in controller:

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    $table->setBidirectionalCascade(true);
    
    // These filters will use the indexes we created
    $table->filterGroups('name', 'selectbox', true);
    $table->filterGroups('email', 'selectbox', true);
    $table->filterGroups('created_at', 'datebox', true);
    
    $table->setFields([
        'name:Name',
        'email:Email',
        'created_at:Created'
    ]);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

---

## 🧪 Testing Examples

### Example 21: Feature Test

```php
use Tests\TestCase;
use App\Models\User;

class BiDirectionalCascadeTest extends TestCase
{
    public function test_bidirectional_cascade_updates_all_filters()
    {
        // Create test data
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'created_at' => '2026-03-01'
        ]);
        
        // Visit page
        $response = $this->get(route('users.index'));
        
        // Verify page loads
        $response->assertStatus(200);
        
        // Verify filter modal exists
        $response->assertSee('filter-modal');
        
        // Verify bi-directional config is passed
        $response->assertSee('bidirectional_cascade');
    }
    
    public function test_filter_options_api_returns_correct_data()
    {
        // Create test data
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        
        // Call filter options API
        $response = $this->postJson('/datatable/filter-options', [
            'table' => 'users',
            'column' => 'email',
            'parentFilters' => ['name' => 'John Doe'],
            'type' => 'selectbox'
        ]);
        
        // Verify response
        $response->assertStatus(200);
        $response->assertJsonStructure(['options']);
        $response->assertJsonFragment(['value' => 'john@example.com']);
    }
}
```

---

## 💡 Tips & Best Practices

### Tip 1: Always Add Database Indexes

```php
// ✅ GOOD - Add indexes for filter columns
Schema::table('products', function (Blueprint $table) {
    $table->index('category');
    $table->index('brand');
    $table->index('price');
});

// ❌ BAD - No indexes, slow queries
// (no indexes)
```

### Tip 2: Use Caching for Static Data

```php
// ✅ GOOD - Cache filter options
$table->cache(600); // 10 minutes

// ❌ BAD - No caching, repeated queries
// (no caching)
```

### Tip 3: Eager Load Relationships

```php
// ✅ GOOD - Eager load to prevent N+1
$table->eager(['customer', 'product']);

// ❌ BAD - N+1 query problem
// (no eager loading)
```

### Tip 4: Use Descriptive Filter Labels

```php
// ✅ GOOD - Clear labels
$table->filterGroups('customer_name', 'selectbox', true);

// ❌ BAD - Unclear labels
$table->filterGroups('cust', 'selectbox', true);
```

### Tip 5: Test with Production-Like Data

```php
// ✅ GOOD - Test with realistic data volume
User::factory()->count(10000)->create();

// ❌ BAD - Test with only a few records
User::factory()->count(5)->create();
```

---

## 🔍 Troubleshooting

### Issue 1: Filters Not Updating

**Problem**: Filters don't update when selecting another filter.

**Solution**: Ensure `relate` parameter is set to `true`:

```php
// ✅ CORRECT
$table->filterGroups('name', 'selectbox', true); // relate = true

// ❌ WRONG
$table->filterGroups('name', 'selectbox', false); // relate = false
```

### Issue 2: Slow Performance

**Problem**: Filter cascade is slow.

**Solution**: Add database indexes and enable caching:

```php
// Add indexes (migration)
$table->index('name');
$table->index('email');

// Enable caching (controller)
$table->cache(300);
```

### Issue 3: Empty Filter Options

**Problem**: Filter shows "No options available".

**Solution**: Check parent filter constraints:

```php
// Verify data exists with current filter combination
User::where('name', 'John Doe')
    ->where('created_at', '2026-03-01')
    ->get(); // Should return results
```

---

## 📚 Related Documentation

- [Filter Configuration Guide](../configuration/filter-configuration.md)
- [TableBuilder API Reference](../api/table.md)
- [Performance Optimization Guide](../guides/performance.md)
- [Testing Guide](../guides/testing.md)

---

**Last Updated**: 2026-03-03  
**Version**: 1.0.0  
**Status**: Published
