# POST Method Implementation Guide for Canvastack DataTables

## Table of Contents
1. [Overview](#overview)
2. [Quick Start](#quick-start)
3. [Configuration](#configuration)
4. [Advanced Features](#advanced-features)
5. [Security](#security)
6. [Performance Optimization](#performance-optimization)
7. [Troubleshooting](#troubleshooting)
8. [API Reference](#api-reference)
9. [Migration Guide](#migration-guide)
10. [Best Practices](#best-practices)

## Overview

The POST method implementation for Canvastack DataTables provides enhanced security, better performance, and advanced filtering capabilities compared to the traditional GET method. This guide covers everything you need to know to implement and optimize POST method DataTables in your application.

### Key Benefits

- **Enhanced Security**: CSRF protection, parameter sanitization, audit trails
- **Better Performance**: Query optimization, memory management, caching
- **Advanced Filters**: Date ranges, multi-select dropdowns, custom filters
- **Large Dataset Support**: Efficient handling of millions of records
- **Production Ready**: Comprehensive monitoring and error handling

### System Requirements

- PHP 8.0 or higher
- Laravel 9.0 or higher
- MySQL 5.7 or higher (or compatible database)
- Redis (optional, for caching)
- Minimum 512MB memory limit

## Quick Start

### Basic Implementation

1. **Enable POST Method in Your Controller**

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Canvastack\Canvastack\Library\Components\Table\View;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $view = new View();
        
        // Configure DataTable with POST method
        $view->data['components']->table->method = 'post';
        $view->data['components']->table->model = [
            'users' => [
                'type' => 'model',
                'source' => new \App\Models\User()
            ]
        ];
        
        // Handle DataTables request
        $datatables_response = $view->handleDatatables($request);
        if ($datatables_response) {
            return response()->json($datatables_response);
        }
        
        return view('users.index', $view->data);
    }
}
```

2. **Update Your Blade Template**

```blade
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Users</h1>
    
    <div class="card">
        <div class="card-body">
            {!! $components->table->render() !!}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // DataTable will automatically use POST method
    // when configured in the controller
});
</script>
@endpush
```

3. **Add CSRF Token to Your Layout**

```blade
<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
</script>
```

### That's it! Your DataTable is now using the secure POST method.

## Configuration

### Basic Configuration

```php
// In your controller
$view->data['components']->table->configure([
    'method' => 'post',
    'security' => [
        'enable_csrf' => true,
        'enable_rate_limiting' => true,
        'enable_audit_trail' => true,
    ],
    'performance' => [
        'enable_caching' => true,
        'enable_query_optimization' => true,
        'memory_limit_mb' => 512,
    ],
    'filters' => [
        'enable_advanced_filters' => true,
        'enable_date_ranges' => true,
        'enable_selectbox_filters' => true,
    ]
]);
```

### Environment Configuration

Add these to your `.env` file:

```env
# DataTables POST Method Configuration
DATATABLE_POST_ENABLED=true
DATATABLE_CACHE_ENABLED=true
DATATABLE_CACHE_TTL=3600
DATATABLE_MEMORY_LIMIT=512
DATATABLE_SECURITY_ENABLED=true
DATATABLE_AUDIT_TRAIL_ENABLED=true
DATATABLE_RATE_LIMIT_ENABLED=true
```

### Database Configuration

For optimal performance, ensure your database has proper indexes:

```sql
-- Example indexes for better performance
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_created_at ON users(created_at);
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_orders_date_status ON orders(order_date, status);
```

## Advanced Features

### Date Range Filters

```php
use Canvastack\Canvastack\Library\Components\Table\Filters\DateRangeFilter;

// In your controller
$date_filter = new DateRangeFilter([
    'format' => 'Y-m-d',
    'display_format' => 'd/m/Y',
    'enable_presets' => true,
    'timezone' => 'Asia/Jakarta',
]);

$view->data['components']->table->addFilter('created_at', $date_filter);
```

**Frontend Usage:**

```javascript
// Date range filter will automatically generate HTML
// Users can select from presets or custom ranges
$('.daterange-filter').on('change', function() {
    table.ajax.reload();
});
```

### Selectbox Filters

```php
use Canvastack\Canvastack\Library\Components\Table\Filters\SelectboxFilter;

// Static options
$status_filter = new SelectboxFilter([
    'multiple' => false,
    'searchable' => true,
]);

$status_filter->setDataSource([
    'active' => 'Active Users',
    'inactive' => 'Inactive Users',
    'pending' => 'Pending Approval',
]);

// Dynamic options from database
$department_filter = new SelectboxFilter([
    'multiple' => true,
    'searchable' => true,
    'lazy_load' => true,
]);

$department_filter->setDataSource('departments', [
    'value_column' => 'id',
    'label_column' => 'name',
]);

$view->data['components']->table->addFilter('status', $status_filter);
$view->data['components']->table->addFilter('department_id', $department_filter);
```

### Custom Filters

```php
// Create custom filter class
class SalaryRangeFilter extends \Canvastack\Canvastack\Library\Components\Table\Filters\BaseFilter
{
    public function process(array $data, string $column): array
    {
        $min_salary = $data[$column . '_min'] ?? null;
        $max_salary = $data[$column . '_max'] ?? null;
        
        $conditions = [];
        
        if ($min_salary) {
            $conditions[] = [
                'field_name' => $column,
                'operator' => '>=',
                'value' => $min_salary,
            ];
        }
        
        if ($max_salary) {
            $conditions[] = [
                'field_name' => $column,
                'operator' => '<=',
                'value' => $max_salary,
            ];
        }
        
        return [
            'valid' => true,
            'conditions' => $conditions,
        ];
    }
    
    public function generateHtml(string $column, array $options = []): string
    {
        return '
            <div class="salary-range-filter">
                <input type="number" name="' . $column . '_min" placeholder="Min Salary" class="form-control">
                <input type="number" name="' . $column . '_max" placeholder="Max Salary" class="form-control">
            </div>
        ';
    }
}

// Use custom filter
$salary_filter = new SalaryRangeFilter();
$view->data['components']->table->addFilter('salary', $salary_filter);
```

### Complex Relationships

```php
// Handle complex relationships with POST method
$view->data['components']->table->model = [
    'users' => [
        'type' => 'model',
        'source' => User::with(['department', 'orders'])
    ]
];

// Define columns with relationships
$view->data['components']->table->columns = [
    'id' => ['label' => 'ID'],
    'name' => ['label' => 'Name'],
    'email' => ['label' => 'Email'],
    'department.name' => ['label' => 'Department'],
    'orders_count' => ['label' => 'Total Orders'],
    'orders_sum_amount' => ['label' => 'Total Amount'],
];
```

## Security

### CSRF Protection

CSRF protection is enabled by default. Ensure your forms include the CSRF token:

```blade
<!-- Automatic CSRF token inclusion -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Or manual inclusion in forms -->
<form method="POST">
    @csrf
    <!-- form fields -->
</form>
```

### Rate Limiting

Configure rate limiting to prevent abuse:

```php
// In your controller or middleware
$security_config = [
    'rate_limiting' => [
        'enabled' => true,
        'max_requests' => 100,
        'time_window' => 60, // seconds
        'block_duration' => 300, // seconds
    ]
];
```

### Parameter Sanitization

All parameters are automatically sanitized, but you can add custom validation:

```php
// Custom validation rules
$view->data['components']->table->validation_rules = [
    'search.value' => 'string|max:255',
    'length' => 'integer|min:1|max:1000',
    'start' => 'integer|min:0',
    'custom_filter' => 'string|in:active,inactive,pending',
];
```

### Audit Trail

Enable audit trail to track all DataTable requests:

```php
// Enable audit trail
$view->data['components']->table->configure([
    'security' => [
        'enable_audit_trail' => true,
        'audit_storage' => 'database', // or 'file', 'cache'
        'audit_retention_days' => 30,
    ]
]);
```

View audit logs:

```php
use Canvastack\Canvastack\Library\Components\Table\Security\AuditTrail;

$audit = new AuditTrail();
$logs = $audit->getLogs([
    'user_id' => auth()->id(),
    'date_from' => '2024-01-01',
    'date_to' => '2024-01-31',
]);
```

## Performance Optimization

### Query Optimization

```php
use Canvastack\Canvastack\Library\Components\Table\Performance\QueryOptimizer;

// Enable automatic query optimization
$optimizer = new QueryOptimizer([
    'enable_cache' => true,
    'enable_explain' => true,
    'auto_optimize' => true,
    'slow_query_threshold' => 1000, // ms
]);

$view->data['components']->table->setQueryOptimizer($optimizer);
```

### Memory Management

```php
use Canvastack\Canvastack\Library\Components\Table\Performance\MemoryManager;

// Configure memory management
$memory_manager = new MemoryManager([
    'memory_limit_mb' => 512,
    'chunk_size' => 1000,
    'enable_gc_optimization' => true,
    'enable_leak_detection' => true,
]);

$view->data['components']->table->setMemoryManager($memory_manager);
```

### Caching Strategies

```php
// Configure caching
$view->data['components']->table->configure([
    'caching' => [
        'enabled' => true,
        'driver' => 'redis', // or 'file', 'database'
        'ttl' => 3600, // seconds
        'cache_queries' => true,
        'cache_results' => true,
        'cache_filters' => true,
    ]
]);
```

### Large Dataset Handling

```php
// For very large datasets
$view->data['components']->table->configure([
    'performance' => [
        'chunk_size' => 5000,
        'enable_streaming' => true,
        'max_memory_mb' => 1024,
        'enable_pagination_optimization' => true,
    ]
]);
```

## Troubleshooting

### Common Issues

#### 1. CSRF Token Mismatch

**Problem**: Getting 419 CSRF token mismatch errors.

**Solution**:
```javascript
// Ensure CSRF token is included in AJAX requests
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
```

#### 2. Memory Limit Exceeded

**Problem**: Getting memory limit exceeded errors with large datasets.

**Solution**:
```php
// Increase memory limit and enable chunking
ini_set('memory_limit', '1024M');

$view->data['components']->table->configure([
    'performance' => [
        'chunk_size' => 1000,
        'enable_streaming' => true,
    ]
]);
```

#### 3. Slow Query Performance

**Problem**: DataTable requests are taking too long.

**Solution**:
```php
// Enable query optimization and add database indexes
$view->data['components']->table->configure([
    'performance' => [
        'enable_query_optimization' => true,
        'enable_caching' => true,
    ]
]);

// Add database indexes
// CREATE INDEX idx_table_column ON table_name(column_name);
```

#### 4. Filter Not Working

**Problem**: Custom filters are not being applied.

**Solution**:
```php
// Ensure filter is properly registered
$filter = new CustomFilter();
$view->data['components']->table->addFilter('column_name', $filter);

// Check filter validation
$result = $filter->process($request->all(), 'column_name');
if (!$result['valid']) {
    // Handle validation error
    Log::error('Filter validation failed: ' . $result['error']);
}
```

### Debug Mode

Enable debug mode for detailed logging:

```php
// In your .env file
DATATABLE_DEBUG=true
DATATABLE_LOG_QUERIES=true
DATATABLE_LOG_PERFORMANCE=true

// In your controller
$view->data['components']->table->configure([
    'debug' => [
        'enabled' => true,
        'log_queries' => true,
        'log_performance' => true,
        'show_execution_time' => true,
    ]
]);
```

### Performance Monitoring

```php
// Get performance statistics
$stats = $view->data['components']->table->getPerformanceStats();

// Example output:
[
    'total_queries' => 150,
    'avg_execution_time_ms' => 245.5,
    'slow_queries_count' => 3,
    'cache_hit_rate' => 85.2,
    'memory_usage_mb' => 45.8,
]
```

## API Reference

### Core Classes

#### `Post` Class

```php
use Canvastack\Canvastack\Library\Components\Table\Post;

$post = new Post();

// Main processing method
$result = $post->process(Request $request);

// Configuration methods
$post->configure(array $config);
$post->setModel($model);
$post->addFilter(string $column, FilterInterface $filter);
$post->setQueryOptimizer(QueryOptimizer $optimizer);
$post->setMemoryManager(MemoryManager $manager);
```

#### `DateRangeFilter` Class

```php
use Canvastack\Canvastack\Library\Components\Table\Filters\DateRangeFilter;

$filter = new DateRangeFilter([
    'format' => 'Y-m-d',
    'display_format' => 'd/m/Y',
    'timezone' => 'UTC',
    'enable_presets' => true,
    'enable_cache' => true,
]);

// Process filter data
$result = $filter->process(array $data, string $column);

// Generate HTML
$html = $filter->generateHtml(string $column, array $options);

// Get JavaScript configuration
$js_config = $filter->getJsConfig();
```

#### `SelectboxFilter` Class

```php
use Canvastack\Canvastack\Library\Components\Table\Filters\SelectboxFilter;

$filter = new SelectboxFilter([
    'multiple' => true,
    'searchable' => true,
    'lazy_load' => true,
]);

// Set data source
$filter->setDataSource('table_name', [
    'value_column' => 'id',
    'label_column' => 'name',
]);

// Or static data
$filter->setDataSource([
    'value1' => 'Label 1',
    'value2' => 'Label 2',
]);

// Process and generate
$result = $filter->process(array $data, string $column);
$html = $filter->generateHtml(string $column, array $options);
```

#### `QueryOptimizer` Class

```php
use Canvastack\Canvastack\Library\Components\Table\Performance\QueryOptimizer;

$optimizer = new QueryOptimizer([
    'enable_cache' => true,
    'enable_explain' => true,
    'auto_optimize' => false,
    'slow_query_threshold' => 1000,
]);

// Optimize query
$result = $optimizer->optimizeQuery(Builder $query, array $options);

// Get performance statistics
$stats = $optimizer->getPerformanceStats();

// Clear cache
$optimizer->clearCache();
```

#### `MemoryManager` Class

```php
use Canvastack\Canvastack\Library\Components\Table\Performance\MemoryManager;

$manager = new MemoryManager([
    'memory_limit_mb' => 512,
    'chunk_size' => 1000,
    'enable_gc_optimization' => true,
]);

// Create checkpoints
$checkpoint = $manager->checkpoint('operation_start');

// Process large datasets
$result = $manager->processLargeDataset($data, $processor, $options);

// Check memory usage
$status = $manager->checkMemoryUsage();

// Get statistics
$stats = $manager->getMemoryStats();
```

### Configuration Options

#### Security Configuration

```php
'security' => [
    'enable_csrf' => true,
    'enable_rate_limiting' => true,
    'enable_audit_trail' => true,
    'enable_parameter_sanitization' => true,
    'csrf_token_name' => '_token',
    'rate_limit_max_requests' => 100,
    'rate_limit_time_window' => 60,
    'audit_storage' => 'database', // 'database', 'file', 'cache'
    'audit_retention_days' => 30,
]
```

#### Performance Configuration

```php
'performance' => [
    'enable_caching' => true,
    'enable_query_optimization' => true,
    'enable_memory_management' => true,
    'cache_driver' => 'redis', // 'redis', 'file', 'database'
    'cache_ttl' => 3600,
    'memory_limit_mb' => 512,
    'chunk_size' => 1000,
    'slow_query_threshold' => 1000,
]
```

#### Filter Configuration

```php
'filters' => [
    'enable_advanced_filters' => true,
    'enable_date_ranges' => true,
    'enable_selectbox_filters' => true,
    'enable_custom_filters' => true,
    'filter_cache_ttl' => 1800,
    'max_filter_options' => 1000,
]
```

## Migration Guide

### From GET to POST Method

#### Step 1: Update Controller

**Before (GET method):**
```php
public function index(Request $request)
{
    $view = new View();
    // Default GET method
    return view('users.index', $view->data);
}
```

**After (POST method):**
```php
public function index(Request $request)
{
    $view = new View();
    
    // Enable POST method
    $view->data['components']->table->method = 'post';
    
    // Handle DataTables request
    $datatables_response = $view->handleDatatables($request);
    if ($datatables_response) {
        return response()->json($datatables_response);
    }
    
    return view('users.index', $view->data);
}
```

#### Step 2: Update Frontend

**Add CSRF token:**
```blade
<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
</script>
```

#### Step 3: Update Routes (if needed)

```php
// Ensure your route accepts POST requests
Route::match(['GET', 'POST'], '/users', [UserController::class, 'index'])->name('users.index');
```

#### Step 4: Test Migration

1. Test basic functionality
2. Test filters
3. Test sorting and pagination
4. Test search functionality
5. Verify security features

### Backward Compatibility

The POST method implementation maintains backward compatibility with existing GET method implementations. You can gradually migrate your DataTables by:

1. Enabling POST method on new tables
2. Testing thoroughly in staging environment
3. Migrating existing tables one by one
4. Monitoring performance and security

## Best Practices

### Security Best Practices

1. **Always Enable CSRF Protection**
```php
$view->data['components']->table->configure([
    'security' => ['enable_csrf' => true]
]);
```

2. **Use Rate Limiting**
```php
$view->data['components']->table->configure([
    'security' => [
        'enable_rate_limiting' => true,
        'rate_limit_max_requests' => 100,
    ]
]);
```

3. **Sanitize User Input**
```php
// Custom validation rules
$view->data['components']->table->validation_rules = [
    'search.value' => 'string|max:255|regex:/^[a-zA-Z0-9\s\-_]+$/',
    'custom_filter' => 'string|in:active,inactive',
];
```

4. **Enable Audit Trail**
```php
$view->data['components']->table->configure([
    'security' => ['enable_audit_trail' => true]
]);
```

### Performance Best Practices

1. **Use Database Indexes**
```sql
-- Index frequently filtered columns
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_created_at ON users(created_at);

-- Composite indexes for complex filters
CREATE INDEX idx_orders_user_date ON orders(user_id, order_date);
```

2. **Enable Caching**
```php
$view->data['components']->table->configure([
    'performance' => [
        'enable_caching' => true,
        'cache_ttl' => 3600,
    ]
]);
```

3. **Optimize Memory Usage**
```php
$view->data['components']->table->configure([
    'performance' => [
        'chunk_size' => 1000,
        'memory_limit_mb' => 512,
    ]
]);
```

4. **Use Query Optimization**
```php
$view->data['components']->table->configure([
    'performance' => ['enable_query_optimization' => true]
]);
```

### Code Organization Best Practices

1. **Create Dedicated Filter Classes**
```php
// app/DataTable/Filters/StatusFilter.php
class StatusFilter extends SelectboxFilter
{
    public function __construct()
    {
        parent::__construct([
            'multiple' => false,
            'searchable' => false,
        ]);
        
        $this->setDataSource([
            'active' => 'Active',
            'inactive' => 'Inactive',
            'pending' => 'Pending',
        ]);
    }
}
```

2. **Use Configuration Files**
```php
// config/datatables.php
return [
    'post_method' => [
        'enabled' => env('DATATABLE_POST_ENABLED', true),
        'security' => [
            'csrf' => true,
            'rate_limiting' => true,
            'audit_trail' => env('DATATABLE_AUDIT_ENABLED', false),
        ],
        'performance' => [
            'caching' => env('DATATABLE_CACHE_ENABLED', true),
            'memory_limit' => env('DATATABLE_MEMORY_LIMIT', 512),
        ],
    ],
];
```

3. **Create Base Controller**
```php
// app/Http/Controllers/BaseDataTableController.php
abstract class BaseDataTableController extends Controller
{
    protected function configureDataTable(View $view): void
    {
        $view->data['components']->table->method = 'post';
        $view->data['components']->table->configure(config('datatables.post_method'));
    }
    
    protected function handleDataTableRequest(Request $request, View $view)
    {
        $datatables_response = $view->handleDatatables($request);
        if ($datatables_response) {
            return response()->json($datatables_response);
        }
        return null;
    }
}
```

### Testing Best Practices

1. **Write Comprehensive Tests**
```php
// tests/Feature/DataTablePostMethodTest.php
class DataTablePostMethodTest extends TestCase
{
    public function test_basic_post_request()
    {
        $response = $this->post('/users', [
            'renderDataTables' => 'true',
            'difta' => ['name' => 'users', 'source' => 'dynamics'],
            'draw' => 1,
        ]);
        
        $response->assertStatus(200)
                ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
    }
    
    public function test_csrf_protection()
    {
        $response = $this->post('/users', [
            'renderDataTables' => 'true',
            // Missing CSRF token
        ]);
        
        $response->assertStatus(419); // CSRF token mismatch
    }
}
```

2. **Performance Testing**
```php
public function test_large_dataset_performance()
{
    $start_time = microtime(true);
    
    $response = $this->post('/users', [
        'renderDataTables' => 'true',
        'length' => 1000,
        // ... other parameters
    ]);
    
    $execution_time = (microtime(true) - $start_time) * 1000;
    
    $this->assertLessThan(5000, $execution_time); // Should complete within 5 seconds
}
```

### Monitoring Best Practices

1. **Log Performance Metrics**
```php
// In your controller
Log::info('DataTable request processed', [
    'execution_time_ms' => $execution_time,
    'memory_usage_mb' => memory_get_peak_usage(true) / 1024 / 1024,
    'query_count' => DB::getQueryLog(),
]);
```

2. **Monitor Error Rates**
```php
// Custom middleware for monitoring
class DataTableMonitoringMiddleware
{
    public function handle($request, Closure $next)
    {
        $start_time = microtime(true);
        
        try {
            $response = $next($request);
            
            // Log successful request
            $this->logSuccess($request, $response, $start_time);
            
            return $response;
        } catch (\Exception $e) {
            // Log error
            $this->logError($request, $e, $start_time);
            throw $e;
        }
    }
}
```

3. **Set Up Alerts**
```php
// In your monitoring service
if ($error_rate > 5) { // 5% error rate
    Mail::to('admin@example.com')->send(new DataTableErrorAlert($metrics));
}

if ($avg_response_time > 2000) { // 2 seconds
    Slack::send('DataTable performance degraded: ' . $avg_response_time . 'ms');
}
```

---

## Conclusion

The POST method implementation for Canvastack DataTables provides a robust, secure, and high-performance solution for handling large datasets with advanced filtering capabilities. By following this guide and implementing the best practices, you can ensure optimal performance and security for your DataTable implementations.

For additional support or questions, please refer to the troubleshooting section or contact the development team.

---

**Document Version**: 1.0  
**Last Updated**: December 2024  
**Compatibility**: Canvastack DataTables v2.0+