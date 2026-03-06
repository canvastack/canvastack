# Filter Persistence

## Overview

The Filter Persistence feature allows table filters to be saved to the session and automatically restored when users return to the page. This provides a better user experience by maintaining filter state across page loads and navigation.

## Features

- Save active filters to session automatically
- Load filters from session on page load
- Clear individual filters
- Clear all filters at once
- Active filter count badge
- Visual display of active filters

## Basic Usage

### Automatic Persistence

By default, filters are automatically saved to session when applied:

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Add filters
    $table->filterGroups('status', 'selectbox');
    $table->filterGroups('category', 'selectbox');
    
    // Filters are automatically loaded from session
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

### Manual Filter Management

You can manually control filter persistence:

```php
// Set filters and save to session
$table->setActiveFilters([
    'status' => 'active',
    'category' => 'news'
], true); // true = save to session (default)

// Set filters without saving to session
$table->setActiveFilters([
    'status' => 'active'
], false);

// Load filters from session
$table->loadFiltersFromSession();

// Get active filters
$activeFilters = $table->getActiveFilters();
// ['status' => 'active', 'category' => 'news']

// Clear all filters and session
$table->clearActiveFilters();

// Clear all filters but keep session
$table->clearActiveFilters(false);

// Clear individual filter
$table->clearFilter('status');

// Clear individual filter but keep in session
$table->clearFilter('status', false);
```

## Filter Manager API

The FilterManager provides low-level control over filter persistence:

```php
$filterManager = $table->getFilterManager();

// Set session key
$filterManager->setSessionKey('my_table_filters');

// Save to session
$filterManager->saveToSession();

// Load from session
$filterManager->loadFromSession();

// Clear session
$filterManager->clearSession();

// Clear individual filter from session
$filterManager->clearFilterFromSession('status');

// Check if filters are active
if ($filterManager->hasActiveFilters()) {
    $count = $filterManager->getActiveFilterCount();
    echo "Active filters: {$count}";
}
```

## UI Components

### Filter Modal

The filter modal automatically displays active filters with clear buttons:

```blade
{{-- Filter button with active count badge --}}
<button>
    <i data-lucide="filter"></i>
    <span>Filters</span>
    <span class="badge">3</span> {{-- Active filter count --}}
</button>

{{-- Active filters display --}}
<div class="active-filters">
    <span class="filter-tag">
        Status: Active
        <button @click="clearFilter('status')">×</button>
    </span>
    <span class="filter-tag">
        Category: News
        <button @click="clearFilter('category')">×</button>
    </span>
</div>

{{-- Clear all button --}}
<button @click="clearAllFilters">Clear All</button>
```

### JavaScript Integration

The filter modal uses Alpine.js for reactive state management:

```javascript
function filterModal() {
    return {
        filters: {},
        activeFilterCount: 0,
        
        // Clear individual filter
        clearFilter(key) {
            this.filters[key] = null;
            this.calculateActiveFilters();
        },
        
        // Clear all filters
        clearAllFilters() {
            for (const key in this.filters) {
                this.filters[key] = null;
            }
            this.calculateActiveFilters();
            this.applyFilters();
        },
        
        // Apply filters and save to session
        applyFilters() {
            sessionStorage.setItem('table_filters', JSON.stringify(this.filters));
            // Reload table with new filters
        }
    };
}
```

## Session Storage

Filters are stored in the session using a unique key per table:

```php
// Session key format
$sessionKey = "table_filters_{$tableId}";

// Session data structure
[
    'status' => 'active',
    'category' => 'news',
    'created_at_start' => '2024-01-01',
    'created_at_end' => '2024-12-31'
]
```

## Controller Examples

### Basic Filter Persistence

```php
public function index(Request $request, TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Add filters
    $table->filterGroups('status', 'selectbox');
    $table->filterGroups('role', 'selectbox');
    
    // Apply filters from request (if any)
    if ($request->has('filters')) {
        $table->setActiveFilters($request->input('filters'));
    }
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

### Clear Filters Endpoint

```php
public function clearFilters(Request $request, TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Clear all filters
    $table->clearActiveFilters();
    
    return redirect()->route('users.index')
        ->with('success', __('ui.messages.filters_cleared'));
}
```

### Clear Individual Filter Endpoint

```php
public function clearFilter(Request $request, string $column, TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Clear specific filter
    $table->clearFilter($column);
    
    return redirect()->route('users.index')
        ->with('success', __('ui.messages.filter_cleared', ['filter' => $column]));
}
```

## Advanced Usage

### Custom Session Key

```php
// Use custom session key for multiple tables on same page
$table->getFilterManager()->setSessionKey('users_table_filters');
$table2->getFilterManager()->setSessionKey('posts_table_filters');
```

### Conditional Persistence

```php
// Only persist filters for authenticated users
if (auth()->check()) {
    $table->setActiveFilters($filters, true);
} else {
    $table->setActiveFilters($filters, false);
}
```

### Filter Expiration

```php
// Clear filters after certain time
$lastFilterTime = session('last_filter_time');
if ($lastFilterTime && now()->diffInHours($lastFilterTime) > 24) {
    $table->clearActiveFilters();
}
session(['last_filter_time' => now()]);
```

## Testing

### Unit Tests

```php
public function test_filters_persist_across_page_loads()
{
    // First page load
    $table = app(TableBuilder::class);
    $table->setActiveFilters(['status' => 'active']);
    $table->getFilterManager()->saveToSession();
    
    // Second page load
    $table2 = app(TableBuilder::class);
    $table2->getFilterManager()->setSessionKey('test_table_filters');
    $table2->getFilterManager()->loadFromSession();
    
    $this->assertEquals(['status' => 'active'], $table2->getActiveFilters());
}
```

### Feature Tests

```php
public function test_filter_persistence_in_browser()
{
    $this->actingAs($user)
        ->post(route('users.filter'), ['filters' => ['status' => 'active']])
        ->assertSessionHas('table_filters_users', ['status' => 'active']);
    
    $this->get(route('users.index'))
        ->assertSee('Status: Active');
}
```

## Performance Considerations

### Session Size

- Filters are stored as simple key-value pairs
- Minimal session storage impact
- Automatically cleaned up when cleared

### Cache Integration

Filters work seamlessly with table caching:

```php
// Cache table data with filters
$table->cache(300); // 5 minutes
$table->setActiveFilters(['status' => 'active']);

// Cache key includes filter values
// cache_key: table_users_status_active_page_1
```

## Security

### Input Validation

All filter values are validated before being applied to queries:

```php
// Column names are validated against table schema
$table->filterGroups('status', 'selectbox'); // ✅ Valid column

// Invalid columns are rejected
$table->filterGroups('invalid_column', 'selectbox'); // ❌ Throws exception
```

### SQL Injection Prevention

Filters use parameterized queries:

```php
// Safe - uses Query Builder
$query->where('status', '=', $filterValue);

// Never uses raw SQL
// $query->whereRaw("status = '{$filterValue}'"); // ❌ Vulnerable
```

## Troubleshooting

### Filters Not Persisting

**Problem**: Filters are not saved to session.

**Solution**: Ensure session key is set:

```php
$table->getFilterManager()->setSessionKey('my_table_filters');
```

### Filters Not Loading

**Problem**: Filters are not loaded from session.

**Solution**: Call loadFromSession() before format():

```php
$table->loadFiltersFromSession();
$table->format();
```

### Session Conflicts

**Problem**: Multiple tables share the same session key.

**Solution**: Use unique session keys:

```php
$table1->getFilterManager()->setSessionKey('users_table_filters');
$table2->getFilterManager()->setSessionKey('posts_table_filters');
```

## Related Documentation

- [Filter Modal](../components/filter-modal.md)
- [Bi-directional Cascading Filters](./bi-directional-cascade.md)
- [Table Caching](./table-caching.md)
- [Session Management](./session-management.md)

## Requirements Validated

This feature validates the following requirements:

- **Requirement 10.3**: Persist active filters in session
- **Requirement 10.5**: Allow clearing individual filters
- **Requirement 10.6**: Allow clearing all filters at once
- **Requirement 33.3**: Persist filters in session
- **Requirement 33.4**: Load filters from session on page load

---

**Last Updated**: 2026-03-05  
**Version**: 1.0.0  
**Status**: Complete

