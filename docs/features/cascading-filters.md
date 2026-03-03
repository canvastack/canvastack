# Cascading Filters

## Overview

Cascading filters allow you to create parent-child relationships between filters, where changing a parent filter automatically updates the options available in child filters. This is commonly used for hierarchical data like Country → State → City or Period → Region → Cluster.

**Status**: Implemented  
**Version**: 1.0.0  
**Last Updated**: 2026-03-02

---

## Features

- ✅ **Parent-Child Relationships** - Define which filters depend on others
- ✅ **Automatic Option Loading** - Child filter options load via AJAX when parent changes
- ✅ **Multiple Cascade Levels** - Support unlimited levels of cascading (A → B → C → D)
- ✅ **Auto-Submit** - Optional automatic filter application on change
- ✅ **Loading States** - Visual feedback during option loading
- ✅ **Error Handling** - Graceful error handling with user notifications
- ✅ **Value Clearing** - Child filter values automatically cleared when parent changes
- ✅ **Session Persistence** - Filter state saved and restored across page reloads

---

## Basic Usage

### Simple Cascading (Two Levels)

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Report());
    
    // Parent filter
    $table->filterGroups('country', 'selectbox', true);
    
    // Child filter (will update when country changes)
    $table->filterGroups('state', 'selectbox');
    
    $table->format();
    
    return view('reports.index', ['table' => $table]);
}
```

### Multiple Cascade Levels

```php
// 4-level cascade: Period → COR → Region → Cluster
$table->filterGroups('period_string', 'selectbox', true);  // Cascades to all below
$table->filterGroups('cor', 'selectbox', true);            // Cascades to all below
$table->filterGroups('region', 'selectbox', true);         // Cascades to cluster
$table->filterGroups('cluster', 'selectbox');              // No cascade
```

---

## Cascade Relationship Types

### 1. Boolean `true` - Cascade to All Subsequent Filters

When `relate` is `true`, the filter cascades to ALL filters defined after it.

```php
$table->filterGroups('period', 'selectbox', true);
$table->filterGroups('region', 'selectbox');
$table->filterGroups('cluster', 'selectbox');
$table->filterGroups('outlet', 'selectbox');

// Changing 'period' will update: region, cluster, outlet
```

**Use Case**: Hierarchical data where each level depends on all previous levels.

---

### 2. String - Cascade to Specific Filter

When `relate` is a string, the filter cascades only to that specific filter.

```php
$table->filterGroups('country', 'selectbox', 'state');
$table->filterGroups('state', 'selectbox', 'city');
$table->filterGroups('city', 'selectbox');

// Changing 'country' will update only 'state'
// Changing 'state' will update only 'city'
```

**Use Case**: Direct parent-child relationships without affecting other filters.

---

### 3. Array - Cascade to Multiple Specific Filters

When `relate` is an array, the filter cascades to those specific filters.

```php
$table->filterGroups('period', 'selectbox', ['region', 'cluster']);
$table->filterGroups('cor', 'selectbox');
$table->filterGroups('region', 'selectbox');
$table->filterGroups('cluster', 'selectbox');

// Changing 'period' will update: region, cluster (but not cor)
```

**Use Case**: Complex relationships where a filter affects multiple non-sequential filters.

---

### 4. Boolean `false` - No Cascade

When `relate` is `false` (default), the filter does not cascade to any other filters.

```php
$table->filterGroups('status', 'selectbox', false);
$table->filterGroups('type', 'selectbox', false);

// These filters are independent
```

**Use Case**: Independent filters that don't affect each other.

---

## Auto-Submit

Filters can automatically apply when changed, without requiring the user to click "Apply Filter".

```php
// Auto-submit enabled (third parameter is true)
$table->filterGroups('period', 'selectbox', true);

// Manual submit (default)
$table->filterGroups('cluster', 'selectbox');
```

**Behavior**:
- **Auto-submit**: Modal closes immediately after selection, table reloads
- **Manual submit**: User must click "Apply Filter" button

**Best Practice**: Use auto-submit for top-level filters, manual submit for leaf filters.

---

## Backend Implementation

### Filter Options Endpoint

The cascading filter system requires a backend endpoint to load filter options dynamically.

**Route**:
```php
// routes/web.php
Route::post('/datatable/filter-options', [DataTableController::class, 'getFilterOptions'])
    ->name('datatable.filter-options');
```

**Controller**:
```php
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

public function getFilterOptions(Request $request): JsonResponse
{
    $table = $request->input('table');
    $column = $request->input('column');
    $parentFilters = $request->input('parentFilters', []);
    
    // Validate table and column exist
    $this->validateTableAndColumn($table, $column);
    
    // Build query
    $query = DB::table($table)
        ->select($column)
        ->distinct();
    
    // Apply parent filters
    foreach ($parentFilters as $col => $value) {
        if (!empty($value)) {
            $this->validateTableAndColumn($table, $col);
            $query->where($col, $value);
        }
    }
    
    // Get options
    $options = $query->pluck($column)->map(function($value) {
        return [
            'value' => $value,
            'label' => $value
        ];
    })->values();
    
    return response()->json([
        'options' => $options
    ]);
}

protected function validateTableAndColumn(string $table, string $column): void
{
    if (!Schema::hasTable($table)) {
        throw new \InvalidArgumentException("Table {$table} does not exist");
    }
    
    if (!Schema::hasColumn($table, $column)) {
        throw new \InvalidArgumentException("Column {$column} does not exist in table {$table}");
    }
}
```

---

## Frontend Implementation

The cascading logic is implemented in Alpine.js within the filter modal component.

### Key Methods

#### 1. `handleFilterChange(filter)`

Called when a filter value changes. Handles cascading and auto-submit.

```javascript
async handleFilterChange(filter) {
    if (filter.relate) {
        await this.updateRelatedFilters(filter);
    }
    
    if (filter.autoSubmit) {
        await this.applyFilters();
    }
}
```

#### 2. `updateRelatedFilters(parentFilter)`

Loads options for child filters via AJAX.

```javascript
async updateRelatedFilters(parentFilter) {
    const relatedColumns = this.getRelatedColumns(parentFilter);
    
    for (const column of relatedColumns) {
        const filter = this.filters.find(f => f.column === column);
        if (filter) {
            filter.loading = true;
            
            try {
                const response = await fetch('/datatable/filter-options', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        table: '{{ $tableName }}',
                        column: column,
                        parentFilters: this.filterValues
                    })
                });
                
                const data = await response.json();
                filter.options = data.options;
                
                // Clear child filter value
                this.filterValues[column] = '';
            } catch (error) {
                console.error('Error loading filter options:', error);
            } finally {
                filter.loading = false;
            }
        }
    }
}
```

#### 3. `getRelatedColumns(filter)`

Determines which filters depend on the changed filter.

```javascript
getRelatedColumns(filter) {
    if (filter.relate === true) {
        // Cascade to all filters after this one
        const currentIndex = this.filters.findIndex(f => f.column === filter.column);
        return this.filters.slice(currentIndex + 1).map(f => f.column);
    } else if (typeof filter.relate === 'string') {
        return [filter.relate];
    } else if (Array.isArray(filter.relate)) {
        return filter.relate;
    }
    return [];
}
```

---

## Loading States

The system provides visual feedback during option loading:

### 1. Spinner Overlay on Select

```blade
<div x-show="filter.loading" class="absolute right-3 top-1/2 -translate-y-1/2">
    <span class="loading loading-spinner loading-sm"></span>
</div>
```

### 2. Loading Message

```blade
<div x-show="filter.loading" class="mt-2 flex items-center gap-2">
    <span class="loading loading-spinner loading-xs"></span>
    <span>{{ __('ui.filter.loading_options') }}</span>
</div>
```

### 3. Disabled State

```blade
<select :disabled="filter.loading">
    <!-- Options -->
</select>
```

---

## Error Handling

### Frontend Error Handling

```javascript
try {
    const response = await fetch('/datatable/filter-options', {
        // ... request config
    });
    
    if (!response.ok) {
        throw new Error('Failed to load filter options');
    }
    
    const data = await response.json();
    filter.options = data.options;
} catch (error) {
    console.error('Error loading filter options:', error);
    
    // Show error notification
    if (window.showNotification) {
        window.showNotification('error', 'Failed to load filter options');
    }
} finally {
    filter.loading = false;
}
```

### Backend Error Handling

```php
public function getFilterOptions(Request $request): JsonResponse
{
    try {
        $table = $request->input('table');
        $column = $request->input('column');
        
        // Validate inputs
        $this->validateTableAndColumn($table, $column);
        
        // Load options
        $options = $this->loadFilterOptions($table, $column, $parentFilters);
        
        return response()->json(['options' => $options]);
    } catch (\InvalidArgumentException $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 400);
    } catch (\Exception $e) {
        \Log::error('Filter options error: ' . $e->getMessage());
        
        return response()->json([
            'error' => 'Failed to load filter options'
        ], 500);
    }
}
```

---

## Session Persistence

Filters are automatically saved to session and restored on page reload.

### Save Filters

```javascript
async applyFilters() {
    // Save to session
    await fetch('/datatable/save-filters', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            table: '{{ $tableName }}',
            filters: this.filterValues
        })
    });
    
    // Reload table
    if (window.dataTable) {
        window.dataTable.ajax.reload();
    }
}
```

### Load Filters

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Report());
    
    // Enable session persistence
    $table->sessionFilters();
    
    // Define filters
    $table->filterGroups('period', 'selectbox', true);
    $table->filterGroups('region', 'selectbox');
    
    $table->format();
    
    return view('reports.index', ['table' => $table]);
}
```

---

## Real-World Examples

### Example 1: Keren Pro Report (4 Levels)

```php
public function kerenPro(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new KerenProReport());
    $table->sessionFilters();
    
    // 4-level cascade
    $table->filterGroups('period_string', 'selectbox', true);
    $table->filterGroups('cor', 'selectbox', true);
    $table->filterGroups('region', 'selectbox', true);
    $table->filterGroups('cluster', 'selectbox');
    
    $table->setFields([
        'period_string:Period',
        'cor:COR',
        'region:Region',
        'cluster:Cluster',
        'total_sales:Total Sales'
    ]);
    
    $table->format();
    
    return view('reports.keren-pro', ['table' => $table]);
}
```

### Example 2: Location Hierarchy (3 Levels)

```php
public function locations(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Location());
    $table->sessionFilters();
    
    // 3-level cascade
    $table->filterGroups('country', 'selectbox', 'state');
    $table->filterGroups('state', 'selectbox', 'city');
    $table->filterGroups('city', 'selectbox');
    
    $table->setFields([
        'country:Country',
        'state:State',
        'city:City',
        'population:Population'
    ]);
    
    $table->format();
    
    return view('locations.index', ['table' => $table]);
}
```

### Example 3: Mixed Cascade Types

```php
public function reports(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Report());
    $table->sessionFilters();
    
    // Period cascades to region and cluster (but not status)
    $table->filterGroups('period', 'selectbox', ['region', 'cluster']);
    
    // Status is independent
    $table->filterGroups('status', 'selectbox', false);
    
    // Region cascades to cluster
    $table->filterGroups('region', 'selectbox', 'cluster');
    
    // Cluster has no cascade
    $table->filterGroups('cluster', 'selectbox');
    
    $table->format();
    
    return view('reports.index', ['table' => $table]);
}
```

---

## Performance Optimization

### 1. Cache Filter Options

```php
public function getFilterOptions(Request $request): JsonResponse
{
    $table = $request->input('table');
    $column = $request->input('column');
    $parentFilters = $request->input('parentFilters', []);
    
    // Generate cache key
    $cacheKey = 'filter_options_' . md5($table . $column . serialize($parentFilters));
    
    // Cache for 5 minutes
    $options = Cache::remember($cacheKey, 300, function() use ($table, $column, $parentFilters) {
        return $this->loadFilterOptions($table, $column, $parentFilters);
    });
    
    return response()->json(['options' => $options]);
}
```

### 2. Debounce AJAX Requests

```javascript
// Add debounce to prevent rapid-fire requests
let debounceTimer;

async handleFilterChange(filter) {
    clearTimeout(debounceTimer);
    
    debounceTimer = setTimeout(async () => {
        if (filter.relate) {
            await this.updateRelatedFilters(filter);
        }
        
        if (filter.autoSubmit) {
            await this.applyFilters();
        }
    }, 300); // 300ms debounce
}
```

### 3. Preload Initial Options

```javascript
async loadInitialOptions() {
    // Load options for all selectbox filters in parallel
    const promises = this.filters
        .filter(f => f.type === 'selectbox' && !f.options.length)
        .map(filter => this.loadOptionsForFilter(filter));
    
    await Promise.all(promises);
}
```

---

## Testing

### Unit Tests

```php
public function test_parent_filter_change_triggers_child_update()
{
    $filterManager = new FilterManager();
    $filterManager->addFilter('period', 'selectbox', true);
    $filterManager->addFilter('region', 'selectbox');
    
    $filterManager->setActiveFilters(['period' => '2025-04']);
    
    $this->assertEquals('2025-04', $filterManager->getActiveFilters()['period']);
}
```

### Browser Tests

```php
public function test_cascading_filters_work_in_browser()
{
    $this->browse(function (Browser $browser) {
        $browser->loginAs(User::factory()->create())
                ->visit('/reports')
                ->click('@filter-button')
                ->select('@filter-period', '2025-04')
                ->pause(1000)
                ->assertSelectHasOptions('@filter-region', ['WEST', 'EAST']);
    });
}
```

---

## Troubleshooting

### Issue 1: Child Filter Not Updating

**Symptom**: Child filter options don't update when parent changes.

**Solution**:
1. Check that `relate` parameter is set correctly
2. Verify backend endpoint is working: `/datatable/filter-options`
3. Check browser console for JavaScript errors
4. Verify CSRF token is present in page

### Issue 2: Loading State Stuck

**Symptom**: Loading spinner never disappears.

**Solution**:
1. Check network tab for failed AJAX requests
2. Verify backend endpoint returns valid JSON
3. Check for JavaScript errors in console
4. Ensure `filter.loading = false` is in `finally` block

### Issue 3: Filter Values Not Persisting

**Symptom**: Filter values lost on page reload.

**Solution**:
1. Verify `sessionFilters()` is called in controller
2. Check that `/datatable/save-filters` endpoint is working
3. Verify session is configured correctly in Laravel
4. Check browser cookies are enabled

---

## Best Practices

1. **Use Boolean `true` for Hierarchical Data** - When each level depends on all previous levels
2. **Use String for Direct Relationships** - When you have simple parent-child pairs
3. **Use Array for Complex Relationships** - When a filter affects multiple non-sequential filters
4. **Enable Auto-Submit for Top-Level Filters** - Improves UX for primary filters
5. **Cache Filter Options** - Reduces database load for frequently accessed options
6. **Validate Inputs** - Always validate table and column names on backend
7. **Handle Errors Gracefully** - Show user-friendly error messages
8. **Test Thoroughly** - Test all cascade levels and edge cases

---

## API Reference

### TableBuilder Methods

```php
// Add filter with cascading
$table->filterGroups(string $column, string $type, bool|string|array $relate = false): self

// Enable session persistence
$table->sessionFilters(): self
```

### Filter Methods

```php
// Get related filters
$filter->getRelatedFilters(): array

// Check if filter has cascading
$filter->hasCascading(): bool

// Check if filter cascades to all
$filter->cascadesToAll(): bool

// Set/get loading state
$filter->setLoading(bool $loading): void
$filter->isLoading(): bool

// Set/get error
$filter->setError(?string $error): void
$filter->getError(): ?string
$filter->hasError(): bool
```

---

## Related Documentation

- [Filter Modal Component](../components/filter-modal.md)
- [TableBuilder API](../api/table.md)
- [Session Management](../features/session-management.md)
- [AJAX Endpoints](../api/endpoints.md)

---

**Document Version**: 1.0.0  
**Last Updated**: 2026-03-02  
**Status**: Complete  
**Maintainer**: CanvaStack Team

