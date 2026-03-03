# FilterManager Integration with TableBuilder

## Overview

The FilterManager is now fully integrated into TableBuilder, providing a robust and flexible filtering system with session persistence, cascading filters, and multiple filter types.

**Status**: Implemented  
**Version**: 1.0.0  
**Last Updated**: 2026-03-02

---

## Features

### Core Features

1. **Multiple Filter Types**
   - Selectbox (dropdown)
   - Inputbox (text search with LIKE)
   - Datebox (date exact match)
   - Daterangebox (date range)
   - Checkbox (multiple values)
   - Radiobox (single value)

2. **Cascading Filters**
   - Parent filter changes update child filter options
   - Supports one-to-one, one-to-many relationships
   - Automatic option loading via AJAX

3. **Session Persistence**
   - Filters automatically saved to session
   - Restored on page reload
   - Per-table session keys

4. **Query Integration**
   - Filters automatically applied to database queries
   - Supports both Eloquent and Query Builder
   - Optimized query generation

---

## Basic Usage

### Adding Filters

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function index(TableBuilder $table)
{
    $table->setName('users');
    $table->setModel(new User());
    
    // Add simple filter
    $table->filterGroups('status', 'selectbox');
    
    // Add text search filter
    $table->filterGroups('name', 'inputbox');
    
    // Add date filter
    $table->filterGroups('created_at', 'datebox');
    
    $table->setFields(['name:Name', 'email:Email', 'status:Status']);
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

### Setting Active Filters

```php
// From request
$table->setActiveFilters($request->input('filters'));

// Manually
$table->setActiveFilters([
    'status' => 'active',
    'category' => 'premium',
]);

// Without saving to session
$table->setActiveFilters(['status' => 'active'], false);
```

### Getting Active Filters

```php
$activeFilters = $table->getActiveFilters();
// ['status' => 'active', 'category' => 'premium']

// Check if any filters are active
$filterManager = $table->getFilterManager();
if ($filterManager->hasActiveFilters()) {
    // Filters are active
}
```

### Clearing Filters

```php
// Clear filters and session
$table->clearActiveFilters();

// Clear filters but keep session
$table->clearActiveFilters(false);
```

---

## Cascading Filters

### Basic Cascading

```php
// Province cascades to all following filters
$table->filterGroups('province', 'selectbox', true);

// City cascades to district
$table->filterGroups('city', 'selectbox', 'district');

// District (no cascade)
$table->filterGroups('district', 'selectbox');
```

### Specific Column Cascade

```php
// Province cascades to city only
$table->filterGroups('province', 'selectbox', 'city');

// City cascades to district only
$table->filterGroups('city', 'selectbox', 'district');

// District (no cascade)
$table->filterGroups('district', 'selectbox');
```

### Multiple Column Cascade

```php
// Province cascades to both city and district
$table->filterGroups('province', 'selectbox', ['city', 'district']);

// City cascades to district
$table->filterGroups('city', 'selectbox', 'district');

// District (no cascade)
$table->filterGroups('district', 'selectbox');
```

---

## Session Persistence

### Enabling Session Persistence

```php
public function index(TableBuilder $table)
{
    $table->setName('users');
    $table->setModel(new User());
    
    // Enable session persistence
    $table->sessionFilters();
    
    // Add filters
    $table->filterGroups('status', 'selectbox');
    $table->filterGroups('category', 'selectbox');
    
    $table->setFields(['name:Name', 'status:Status']);
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

### Manual Session Management

```php
// Load filters from session
$table->loadFiltersFromSession();

// Save filters to session
$table->setActiveFilters(['status' => 'active'], true);

// Clear session
$table->clearActiveFilters(true);
```

---

## Filter Types

### Selectbox (Dropdown)

```php
$table->filterGroups('status', 'selectbox');
```

**Query Applied**:
```sql
WHERE status = 'active'
```

### Inputbox (Text Search)

```php
$table->filterGroups('name', 'inputbox');
```

**Query Applied**:
```sql
WHERE name LIKE '%John%'
```

### Datebox (Date Exact Match)

```php
$table->filterGroups('created_at', 'datebox');
```

**Query Applied**:
```sql
WHERE DATE(created_at) = '2024-01-01'
```

### Daterangebox (Date Range)

```php
$table->filterGroups('created_at', 'daterangebox');
```

**Query Applied**:
```sql
WHERE DATE(created_at) >= '2024-01-01' 
  AND DATE(created_at) <= '2024-01-31'
```

**Filter Value Format**:
```php
[
    'start' => '2024-01-01',
    'end' => '2024-01-31',
]
```

### Checkbox (Multiple Values)

```php
$table->filterGroups('status', 'checkbox');
```

**Query Applied**:
```sql
WHERE status IN ('active', 'pending')
```

**Filter Value Format**:
```php
['active', 'pending']
```

### Radiobox (Single Value)

```php
$table->filterGroups('status', 'radiobox');
```

**Query Applied**:
```sql
WHERE status = 'active'
```

---

## Advanced Usage

### Accessing FilterManager

```php
$filterManager = $table->getFilterManager();

// Get all filters
$filters = $filterManager->getFilters();

// Get specific filter
$statusFilter = $filterManager->getFilter('status');

// Check if filter exists
if ($filterManager->hasFilter('status')) {
    // Filter exists
}

// Get active filter count
$count = $filterManager->getActiveFilterCount();
```

### Custom Filter Logic

```php
// Get FilterManager
$filterManager = $table->getFilterManager();

// Add filter programmatically
$filterManager->addFilter('custom_field', 'selectbox', false);

// Set filter value
$filterManager->setActiveFilters(['custom_field' => 'value']);

// Get filter configuration
$filters = $filterManager->toArray();
```

### Combining with Other Features

```php
public function index(TableBuilder $table, Request $request)
{
    $table->setName('users');
    $table->setModel(new User());
    
    // Enable session persistence
    $table->sessionFilters();
    
    // Add filters
    $table->filterGroups('status', 'selectbox');
    $table->filterGroups('category', 'selectbox');
    $table->filterGroups('name', 'inputbox');
    
    // Apply filters from request
    if ($request->has('filters')) {
        $table->setActiveFilters($request->input('filters'));
    }
    
    // Add eager loading
    $table->eager(['profile', 'roles']);
    
    // Add caching
    $table->cache(300);
    
    // Configure columns
    $table->setFields([
        'name:Name',
        'email:Email',
        'status:Status',
        'category:Category',
    ]);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

---

## API Reference

### TableBuilder Methods

#### `filterGroups(string $column, string $type, $relate = false): self`

Add a filter to the table.

**Parameters**:
- `$column` - Column name to filter
- `$type` - Filter type (selectbox, inputbox, datebox, daterangebox, checkbox, radiobox)
- `$relate` - Related filters for cascading (true = all following, string = specific column, array = multiple columns)

**Returns**: `self` for method chaining

**Throws**: `\InvalidArgumentException` if column doesn't exist or type is invalid

#### `getFilterManager(): FilterManager`

Get the FilterManager instance.

**Returns**: `FilterManager` instance

#### `setActiveFilters(array $filters, bool $saveToSession = true): self`

Set active filter values.

**Parameters**:
- `$filters` - Associative array of column => value pairs
- `$saveToSession` - Whether to save filters to session (default: true)

**Returns**: `self` for method chaining

#### `getActiveFilters(): array`

Get active filter values.

**Returns**: Associative array of column => value pairs

#### `clearActiveFilters(bool $clearSession = true): self`

Clear all active filters.

**Parameters**:
- `$clearSession` - Whether to clear filters from session (default: true)

**Returns**: `self` for method chaining

#### `loadFiltersFromSession(): self`

Load active filters from session.

**Returns**: `self` for method chaining

---

## Testing

### Unit Tests

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;

class FilterIntegrationTest extends TestCase
{
    public function test_filters_applied_to_query()
    {
        $table = $this->app->make(TableBuilder::class);
        $table->setName('users');
        $table->setModel(new User());
        
        // Add filter
        $table->filterGroups('status', 'selectbox');
        $table->setActiveFilters(['status' => 'active'], false);
        
        // Get data
        $data = $table->getData();
        
        // Assert only active users returned
        foreach ($data['data'] as $row) {
            $this->assertEquals('active', $row['status']);
        }
    }
}
```

### Feature Tests

```php
public function test_filter_form_submission()
{
    $response = $this->post(route('users.index'), [
        'filters' => [
            'status' => 'active',
            'category' => 'premium',
        ],
    ]);
    
    $response->assertStatus(200);
    $response->assertViewHas('table');
}
```

---

## Performance Considerations

### Query Optimization

Filters are applied using Query Builder, which:
- Prevents SQL injection
- Uses parameterized queries
- Optimizes query execution
- Supports query caching

### Session Storage

Filter values are stored in session:
- Minimal storage overhead
- Fast retrieval
- Per-table isolation
- Automatic cleanup

### Caching

Combine filters with caching for best performance:

```php
$table->filterGroups('status', 'selectbox');
$table->cache(300); // Cache for 5 minutes

// Filtered results are cached
$table->setActiveFilters(['status' => 'active']);
```

---

## Migration from Legacy System

### Old Code

```php
// Legacy filter system
$this->table->filterGroups('status', 'selectbox');

// Filters stored in $this->filterGroups array
// No FilterManager
// No session persistence
```

### New Code

```php
// New filter system with FilterManager
$this->table->filterGroups('status', 'selectbox');

// Filters managed by FilterManager
// Automatic session persistence
// Advanced filter operations available

// Access FilterManager
$filterManager = $this->table->getFilterManager();
```

### Backward Compatibility

The new system is 100% backward compatible:
- `filterGroups()` method signature unchanged
- Filters still stored in `$this->filterGroups` array
- Existing code works without modifications
- New features available via FilterManager

---

## Troubleshooting

### Filters Not Applied

**Problem**: Filters set but not applied to query

**Solution**: Ensure filters are set before calling `format()` or `getData()`

```php
// Correct order
$table->filterGroups('status', 'selectbox');
$table->setActiveFilters(['status' => 'active']);
$table->format(); // Filters applied here
```

### Session Not Persisting

**Problem**: Filters not restored from session

**Solution**: Call `sessionFilters()` before adding filters

```php
// Correct order
$table->sessionFilters(); // Enable session first
$table->filterGroups('status', 'selectbox');
$table->loadFiltersFromSession(); // Load saved filters
```

### Invalid Column Error

**Problem**: `InvalidArgumentException` when adding filter

**Solution**: Ensure column exists in table schema

```php
// Check column exists
$table->setName('users');
$table->filterGroups('status', 'selectbox'); // 'status' must exist in 'users' table
```

---

## Related Documentation

- [FilterManager API](./filter-manager-implementation.md)
- [Filter Class](./filter-implementation.md)
- [FilterOptionsProvider](./filter-options-provider.md)
- [Session Management](../session-restoration.md)
- [TableBuilder API](../table-builder-api.md)

---

**Last Updated**: 2026-03-02  
**Version**: 1.0.0  
**Status**: Implemented
