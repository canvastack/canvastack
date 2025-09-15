# GET Method (Client-Side Processing)

The GET method in CanvaStack Table implements client-side processing where all data is loaded at once and processed in the browser. This approach is suitable for smaller datasets and provides instant interactions.

## Table of Contents

- [Overview](#overview)
- [Basic Implementation](#basic-implementation)
- [Configuration Options](#configuration-options)
- [Performance Considerations](#performance-considerations)
- [Advantages and Limitations](#advantages-and-limitations)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)

## Overview

### How GET Method Works

In GET method (client-side processing):

1. **Initial Load**: All data is fetched from the server in a single request
2. **Client Processing**: DataTables handles sorting, filtering, and pagination in the browser
3. **Instant Response**: User interactions are processed immediately without server requests
4. **Memory Usage**: All data is stored in browser memory

### When to Use GET Method

Use GET method when:
- Dataset is small (< 1,000 records)
- Users need instant filtering and sorting
- Server resources are limited
- Network latency is high
- Data doesn't change frequently

## Basic Implementation

### Simple GET Method Setup

```php
<?php

namespace App\Http\Controllers;

use Canvastack\Canvastack\Controllers\Core\Controller;
use App\Models\User;

class UserController extends Controller
{
    public function __construct()
    {
        parent::__construct(User::class, 'users');
    }

    public function index()
    {
        $this->setPage();

        // GET method is default - no need to specify
        // All data will be loaded at once
        $this->table->lists('users', [
            'name:Full Name',
            'email:Email Address',
            'created_at:Registration Date'
        ], true);

        return $this->render();
    }
}
```

### Explicit GET Method Configuration

```php
public function index()
{
    $this->setPage();

    // Explicitly set GET method
    $this->table->method('GET');

    // Enable features that work well with client-side processing
    $this->table->searchable()
                ->sortable()
                ->clickable();

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email Address',
        'department.name:Department',
        'created_at:Registration Date'
    ], true);

    return $this->render();
}
```

## Configuration Options

### DataTables Configuration

Configure DataTables for optimal client-side performance:

```php
public function index()
{
    $this->setPage();

    $this->table->method('GET');

    // Configure DataTables options
    $this->table->setDataTablesConfig([
        'pageLength' => 25,
        'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        'order' => [[0, 'asc']],
        'searching' => true,
        'ordering' => true,
        'paging' => true,
        'info' => true,
        'autoWidth' => false,
        'responsive' => true,
        'stateSave' => true,
        'stateDuration' => 60 * 60 * 24, // 24 hours
        'language' => [
            'search' => 'Search users:',
            'lengthMenu' => 'Show _MENU_ users per page',
            'info' => 'Showing _START_ to _END_ of _TOTAL_ users',
            'infoEmpty' => 'No users found',
            'infoFiltered' => '(filtered from _MAX_ total users)',
            'zeroRecords' => 'No matching users found'
        ]
    ]);

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email',
        'created_at:Join Date'
    ], true);

    return $this->render();
}
```

### Search Configuration

Configure search behavior for client-side processing:

```php
public function index()
{
    $this->setPage();

    $this->table->method('GET');
    $this->table->searchable();

    // Configure search options
    $this->table->setSearchConfig([
        'case_sensitive' => false,
        'regex' => false,
        'smart' => true,
        'placeholder' => 'Search users...',
        'delay' => 300, // Delay in milliseconds
        'min_length' => 2, // Minimum characters to trigger search
        'highlight_results' => true
    ]);

    // Set which columns are searchable
    $this->table->setSearchableColumns([
        'name', 'email', 'department.name'
    ]);

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email',
        'department.name:Department',
        'created_at:Join Date'
    ], true);

    return $this->render();
}
```

### Sorting Configuration

Configure sorting for client-side processing:

```php
public function index()
{
    $this->setPage();

    $this->table->method('GET');
    $this->table->sortable();

    // Configure sorting options
    $this->table->setSortConfig([
        'multi_column' => true,
        'initial_sort' => [
            ['column' => 'name', 'direction' => 'asc']
        ],
        'disable_sort_columns' => ['actions'], // Disable sorting for specific columns
        'sort_icons' => [
            'unsorted' => 'fas fa-sort',
            'asc' => 'fas fa-sort-up',
            'desc' => 'fas fa-sort-down'
        ]
    ]);

    // Custom sort functions for specific columns
    $this->table->setCustomSort([
        'status' => function($a, $b) {
            $order = ['active' => 1, 'pending' => 2, 'inactive' => 3];
            return $order[$a] <=> $order[$b];
        }
    ]);

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email',
        'status:Status',
        'created_at:Join Date'
    ], true);

    return $this->render();
}
```

### Filtering Configuration

Set up advanced filtering for client-side processing:

```php
public function index()
{
    $this->setPage();

    $this->table->method('GET');

    // Enable column filters
    $this->table->filterGroups('status', 'selectbox', true)
                ->filterGroups('department.name', 'selectbox', true)
                ->filterGroups('created_at', 'daterange', true);

    // Configure filter behavior
    $this->table->setFilterConfig([
        'auto_apply' => true, // Apply filters automatically
        'clear_button' => true,
        'reset_button' => true,
        'position' => 'top',
        'modal' => [
            'enabled' => true,
            'title' => 'Filter Users',
            'size' => 'modal-lg'
        ]
    ]);

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email',
        'status:Status',
        'department.name:Department',
        'created_at:Join Date'
    ], true);

    return $this->render();
}
```

## Performance Considerations

### Data Loading Optimization

Optimize data loading for client-side processing:

```php
public function index()
{
    $this->setPage();

    $this->table->method('GET');

    // Limit the dataset size
    $maxRecords = 1000;
    $query = User::query();
    
    if ($query->count() > $maxRecords) {
        // Switch to server-side processing for large datasets
        $this->table->method('POST');
    } else {
        // Optimize query for client-side processing
        $query = $query->select([
            'id', 'name', 'email', 'status', 'department_id', 'created_at'
        ])->with(['department:id,name']);
        
        $this->table->query($query);
    }

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email',
        'status:Status',
        'department.name:Department',
        'created_at:Join Date'
    ], true);

    return $this->render();
}
```

### Memory Management

Manage browser memory usage:

```php
public function index()
{
    $this->setPage();

    $this->table->method('GET');

    // Configure memory-efficient options
    $this->table->setMemoryConfig([
        'defer_render' => true, // Render rows only when needed
        'destroy_on_reload' => true, // Clean up previous instances
        'scroll_y' => '400px', // Limit visible rows
        'scroll_collapse' => true,
        'paging' => true,
        'page_length' => 25 // Reasonable page size
    ]);

    // Minimize data sent to client
    $this->table->setDataOptimization([
        'remove_null_values' => true,
        'compress_json' => true,
        'minimize_html' => true
    ]);

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email',
        'created_at:Join Date'
    ], true);

    return $this->render();
}
```

### Caching Strategy

Implement caching for GET method:

```php
public function index()
{
    $this->setPage();

    $this->table->method('GET');

    // Cache the data for better performance
    $cacheKey = 'users_table_data_' . auth()->id();
    $cacheTTL = 300; // 5 minutes

    $data = Cache::remember($cacheKey, $cacheTTL, function() {
        return User::with(['department:id,name'])
                   ->select(['id', 'name', 'email', 'status', 'department_id', 'created_at'])
                   ->get();
    });

    $this->table->setData($data);

    // Configure client-side caching
    $this->table->setBrowserCache([
        'enabled' => true,
        'duration' => 300, // 5 minutes
        'storage' => 'localStorage', // localStorage or sessionStorage
        'key_prefix' => 'canvastack_table_'
    ]);

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email',
        'status:Status',
        'department.name:Department',
        'created_at:Join Date'
    ], true);

    return $this->render();
}
```

## Advantages and Limitations

### Advantages of GET Method

**Performance Benefits:**
- Instant filtering and sorting
- No server requests for interactions
- Reduced server load
- Better user experience for small datasets

**Implementation Benefits:**
- Simpler setup
- No AJAX handling required
- Works offline after initial load
- Better for static data

**User Experience:**
- Immediate response to interactions
- No loading indicators needed
- Smooth animations and transitions
- Better for data analysis tasks

### Limitations of GET Method

**Scalability Issues:**
- Not suitable for large datasets (>1000 records)
- High memory usage in browser
- Slow initial page load for large data
- Browser may become unresponsive

**Data Freshness:**
- Data is static after initial load
- No real-time updates
- Requires page refresh for new data
- Not suitable for frequently changing data

**Security Considerations:**
- All data is visible in browser
- Sensitive data exposed to client
- Harder to implement row-level security
- Data can be manipulated client-side

## Best Practices

### Data Size Management

```php
public function index()
{
    $this->setPage();

    // Check data size and choose appropriate method
    $recordCount = User::count();
    
    if ($recordCount > 500) {
        // Switch to server-side processing
        $this->table->method('POST');
        Log::info("Switched to POST method due to large dataset: {$recordCount} records");
    } else {
        $this->table->method('GET');
        
        // Optimize for client-side processing
        $this->table->setOptimizations([
            'defer_render' => true,
            'page_length' => 25,
            'scroll_y' => '400px'
        ]);
    }

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email',
        'created_at:Join Date'
    ], true);

    return $this->render();
}
```

### Progressive Enhancement

```php
public function index()
{
    $this->setPage();

    $this->table->method('GET');

    // Progressive enhancement based on browser capabilities
    $this->table->setProgressiveEnhancement([
        'fallback_pagination' => true, // Fallback for older browsers
        'feature_detection' => [
            'local_storage' => 'localStorage' in window,
            'web_workers' => 'Worker' in window,
            'intersection_observer' => 'IntersectionObserver' in window
        ],
        'graceful_degradation' => true
    ]);

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email',
        'created_at:Join Date'
    ], true);

    return $this->render();
}
```

### Error Handling

```php
public function index()
{
    $this->setPage();

    $this->table->method('GET');

    // Configure error handling
    $this->table->setErrorHandling([
        'show_errors' => app()->environment('local'),
        'error_messages' => [
            'load_failed' => 'Failed to load data. Please refresh the page.',
            'memory_exceeded' => 'Too much data to display. Please use filters.',
            'browser_unsupported' => 'Your browser doesn\'t support this feature.'
        ],
        'retry_attempts' => 3,
        'fallback_mode' => 'basic_table'
    ]);

    try {
        $this->table->lists('users', [
            'name:Full Name',
            'email:Email',
            'created_at:Join Date'
        ], true);
    } catch (\Exception $e) {
        Log::error('Table rendering failed: ' . $e->getMessage());
        
        // Fallback to simple table
        return $this->renderFallbackTable();
    }

    return $this->render();
}

private function renderFallbackTable()
{
    $users = User::select(['name', 'email', 'created_at'])
                 ->limit(100)
                 ->get();
    
    return view('users.simple_table', compact('users'));
}
```

## Troubleshooting

### Common Issues and Solutions

**Issue: Page loads slowly with large datasets**
```php
// Solution: Implement data size checking
$recordCount = User::count();
if ($recordCount > 1000) {
    $this->table->method('POST'); // Switch to server-side
}
```

**Issue: Browser becomes unresponsive**
```php
// Solution: Enable deferred rendering and pagination
$this->table->setDataTablesConfig([
    'deferRender' => true,
    'pageLength' => 25,
    'scrollY' => '400px',
    'scrollCollapse' => true
]);
```

**Issue: Search is too slow**
```php
// Solution: Optimize search configuration
$this->table->setSearchConfig([
    'delay' => 500, // Increase delay
    'min_length' => 3, // Require more characters
    'smart' => false // Disable smart search for better performance
]);
```

**Issue: Memory usage too high**
```php
// Solution: Minimize data and enable cleanup
$this->table->setMemoryConfig([
    'cleanup_on_destroy' => true,
    'minimize_dom' => true,
    'remove_unused_data' => true
]);
```

### Performance Monitoring

```php
public function index()
{
    $this->setPage();

    $this->table->method('GET');

    // Monitor performance
    $this->table->setPerformanceMonitoring([
        'enabled' => app()->environment('local'),
        'metrics' => [
            'initial_load_time' => true,
            'memory_usage' => true,
            'render_time' => true,
            'interaction_response_time' => true
        ],
        'thresholds' => [
            'load_time_warning' => 2000, // 2 seconds
            'memory_warning' => 50 * 1024 * 1024, // 50MB
            'render_time_warning' => 1000 // 1 second
        ]
    ]);

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email',
        'created_at:Join Date'
    ], true);

    return $this->render();
}
```

---

## Related Documentation

- [POST Method](post.md) - Server-side processing alternative
- [AJAX Handling](ajax.md) - Custom AJAX implementations
- [Performance Optimization](../advanced/performance.md) - Performance tuning
- [Basic Usage](../basic-usage.md) - Getting started with tables