# Troubleshooting Guide

Comprehensive troubleshooting guide for the CanvaStack multi-table and tab system.

## 📦 Location

- **Documentation**: `packages/canvastack/canvastack/docs/guides/troubleshooting.md`
- **Related Components**:
  - `packages/canvastack/canvastack/src/Components/Table/TableBuilder.php`
  - `packages/canvastack/canvastack/src/Components/Table/TabManager.php`
  - `packages/canvastack/canvastack/src/Components/Table/ConnectionDetector.php`

## 🎯 Overview

This guide helps you diagnose and fix common issues with the multi-table and tab system. It covers:

- Common issues and solutions
- Error message explanations
- Debug mode usage
- Performance troubleshooting
- Best practices for debugging

---

## 🚨 Common Issues

### Issue 1: Tab Not Loading

**Symptoms**:
- Tab content shows loading spinner indefinitely
- Console shows AJAX errors
- Tab remains empty after clicking

**Possible Causes**:

1. **Invalid Route Configuration**

```php
// ❌ WRONG - Route doesn't exist
$table->addTab('users', 'Users', route('admin.users.data'));

// ✅ CORRECT - Verify route exists
Route::post('/admin/users/data', [UserController::class, 'getData'])
    ->name('admin.users.data');
```

2. **Missing CSRF Token**

```php
// ❌ WRONG - No CSRF protection
Route::post('/admin/users/data', [UserController::class, 'getData']);

// ✅ CORRECT - Add CSRF middleware
Route::post('/admin/users/data', [UserController::class, 'getData'])
    ->middleware(['web', 'auth']);
```

3. **Controller Not Returning JSON**

```php
// ❌ WRONG - Returns view instead of JSON
public function getData(Request $request)
{
    return view('users.table');
}

// ✅ CORRECT - Return JSON response
public function getData(Request $request, TableBuilder $table)
{
    $table->setModel(new User());
    $table->setFields(['name:Name', 'email:Email']);
    $table->format();
    
    return response()->json([
        'html' => $table->render(),
        'count' => $table->getTotal()
    ]);
}
```

**Solutions**:

1. **Check Browser Console**:
```javascript
// Open browser console (F12) and look for errors
// Common errors:
// - 404 Not Found: Route doesn't exist
// - 419 CSRF Token Mismatch: Missing CSRF token
// - 500 Internal Server Error: Controller error
```

2. **Verify Route**:
```bash
# List all routes
php artisan route:list | grep users.data

# Test route manually
curl -X POST http://localhost/admin/users/data \
  -H "X-CSRF-TOKEN: your-token"
```

3. **Enable Debug Mode**:
```php
// In controller
$table->debug(true);
$table->format();

// Check logs
tail -f storage/logs/laravel.log
```

---

### Issue 2: Connection Override Warnings

**Symptoms**:
- Warning in logs: "Connection override detected"
- Tables using wrong database connection
- Data from incorrect database

**Possible Causes**:

1. **Manual Connection Override**

```php
// ❌ WRONG - Manual override conflicts with auto-detection
$table->setModel(new User());
$table->setConnection('mysql_secondary'); // Overrides auto-detection
```

2. **Model Connection Mismatch**

```php
// ❌ WRONG - Model connection doesn't match table connection
class User extends Model
{
    protected $connection = 'mysql_primary';
}

// In controller
$table->setModel(new User());
$table->setConnection('mysql_secondary'); // Conflict!
```

**Solutions**:

1. **Remove Manual Override**:
```php
// ✅ CORRECT - Let auto-detection work
$table->setModel(new User());
// Connection auto-detected from model
```

2. **Use Consistent Connections**:
```php
// ✅ CORRECT - Model and table use same connection
class User extends Model
{
    protected $connection = 'mysql_primary';
}

// In controller
$table->setModel(new User());
// Connection auto-detected as 'mysql_primary'
```

3. **Check Connection Detection**:
```php
// Enable debug mode to see detected connection
$table->debug(true);
$table->setModel(new User());
$table->format();

// Check logs for connection detection
// [DEBUG] Connection detected: mysql_primary (from model)
```

---

### Issue 3: Unique ID Collisions

**Symptoms**:
- Multiple tables on same page interfere with each other
- Tab clicks affect wrong table
- JavaScript errors about duplicate IDs

**Possible Causes**:

1. **Missing Unique IDs**

```php
// ❌ WRONG - No unique IDs
$table1->setModel(new User());
$table2->setModel(new Post());
// Both tables get default ID 'table-1'
```

2. **Duplicate IDs**

```php
// ❌ WRONG - Same ID used twice
$table1->setUniqueId('users-table');
$table2->setUniqueId('users-table'); // Collision!
```

**Solutions**:

1. **Set Unique IDs**:
```php
// ✅ CORRECT - Each table has unique ID
$table1->setUniqueId('users-table');
$table2->setUniqueId('posts-table');
$table3->setUniqueId('comments-table');
```

2. **Use Descriptive IDs**:
```php
// ✅ CORRECT - Descriptive and unique
$table->setUniqueId('admin-users-active');
$table->setUniqueId('admin-users-inactive');
$table->setUniqueId('admin-users-deleted');
```

3. **Verify IDs in HTML**:
```html
<!-- Check rendered HTML for duplicate IDs -->
<div id="users-table">...</div>
<div id="posts-table">...</div>
<!-- No duplicates! -->
```

---

### Issue 4: TanStack Initialization Failures

**Symptoms**:
- Table doesn't render
- Console error: "TanStack is not defined"
- Sorting/filtering doesn't work

**Possible Causes**:

1. **Missing TanStack Assets**

```blade
{{-- ❌ WRONG - TanStack not included --}}
@extends('layouts.app')

@section('content')
    {!! $table->render() !!}
@endsection
```

2. **Asset Loading Order**

```blade
{{-- ❌ WRONG - Table rendered before TanStack loaded --}}
@section('content')
    {!! $table->render() !!}
@endsection

@push('scripts')
    <script src="/js/tanstack.js"></script>
@endpush
```

**Solutions**:

1. **Include TanStack Assets**:
```blade
{{-- ✅ CORRECT - Include TanStack in head --}}
@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="/css/tanstack.css">
@endpush

@section('content')
    {!! $table->render() !!}
@endsection

@push('scripts')
    <script src="/js/tanstack.js"></script>
@endpush
```

2. **Use Asset Helper**:
```blade
{{-- ✅ CORRECT - Use helper to include assets --}}
@extends('layouts.app')

@push('head')
    {!! $table->getAssets('css') !!}
@endpush

@section('content')
    {!! $table->render() !!}
@endsection

@push('scripts')
    {!! $table->getAssets('js') !!}
@endpush
```

3. **Verify Asset Loading**:
```javascript
// Check browser console
console.log(typeof TanStack); // Should be 'object'
console.log(TanStack.version); // Should show version number
```

---

### Issue 5: AJAX Request Failures

**Symptoms**:
- Tab loading fails with error
- Console shows 500 Internal Server Error
- No data displayed in tab

**Possible Causes**:

1. **Controller Exception**

```php
// ❌ WRONG - Unhandled exception
public function getData(Request $request, TableBuilder $table)
{
    $table->setModel(new User());
    $table->setFields(['invalid_column:Name']); // Column doesn't exist
    $table->format();
    
    return response()->json(['html' => $table->render()]);
}
```

2. **Missing Validation**

```php
// ❌ WRONG - No input validation
public function getData(Request $request, TableBuilder $table)
{
    $status = $request->input('status'); // Could be malicious
    
    $table->setModel(new User());
    $table->where('status', $status); // SQL injection risk
    $table->format();
    
    return response()->json(['html' => $table->render()]);
}
```

**Solutions**:

1. **Add Error Handling**:
```php
// ✅ CORRECT - Handle exceptions
public function getData(Request $request, TableBuilder $table)
{
    try {
        $table->setModel(new User());
        $table->setFields(['name:Name', 'email:Email']);
        $table->format();
        
        return response()->json([
            'html' => $table->render(),
            'count' => $table->getTotal()
        ]);
    } catch (\Exception $e) {
        \Log::error('Table data error: ' . $e->getMessage());
        
        return response()->json([
            'error' => 'Failed to load data',
            'message' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}
```

2. **Validate Input**:
```php
// ✅ CORRECT - Validate all inputs
public function getData(Request $request, TableBuilder $table)
{
    $validated = $request->validate([
        'status' => 'nullable|in:active,inactive',
        'search' => 'nullable|string|max:255',
        'page' => 'nullable|integer|min:1',
    ]);
    
    $table->setModel(new User());
    
    if (isset($validated['status'])) {
        $table->where('status', $validated['status']);
    }
    
    $table->format();
    
    return response()->json(['html' => $table->render()]);
}
```

3. **Check Logs**:
```bash
# View error logs
tail -f storage/logs/laravel.log

# Filter for errors
grep "ERROR" storage/logs/laravel.log
```

---

### Issue 6: State Management Issues

**Symptoms**:
- Tab state not persisting
- Filters reset when switching tabs
- Sorting lost after page reload

**Possible Causes**:

1. **State Persistence Disabled**

```php
// ❌ WRONG - State not persisted
$table->addTab('active', 'Active Users', route('admin.users.active'));
// State lost when switching tabs
```

2. **Session Storage Issues**

```php
// ❌ WRONG - Session not configured
// .env
SESSION_DRIVER=file
SESSION_LIFETIME=120
// Session expires too quickly
```

**Solutions**:

1. **Enable State Persistence**:
```php
// ✅ CORRECT - Enable state persistence
$table->setUniqueId('users-table');
$table->persistState(true); // Enable state persistence
$table->addTab('active', 'Active Users', route('admin.users.active'));
```

2. **Configure Session**:
```env
# .env
SESSION_DRIVER=redis
SESSION_LIFETIME=43200
SESSION_ENCRYPT=true
```

3. **Use LocalStorage**:
```javascript
// Alternative: Use browser localStorage
const tableState = {
    activeTab: 'active',
    filters: { status: 'active' },
    sorting: { column: 'name', direction: 'asc' }
};

localStorage.setItem('users-table-state', JSON.stringify(tableState));
```

---

## 📋 Error Messages

### Error: "Invalid tab configuration"

**Full Message**:
```
InvalidArgumentException: Invalid tab configuration. Tab ID 'users' is missing required 'url' parameter.
```

**Cause**: Tab added without URL

**Solution**:
```php
// ❌ WRONG
$table->addTab('users', 'Users'); // Missing URL

// ✅ CORRECT
$table->addTab('users', 'Users', route('admin.users.data'));
```

---

### Error: "Connection detection failed"

**Full Message**:
```
RuntimeException: Connection detection failed. Model 'App\Models\User' does not have a valid database connection.
```

**Cause**: Model connection not configured

**Solution**:
```php
// Check model connection
class User extends Model
{
    protected $connection = 'mysql'; // Add this
}

// Or set in controller
$table->setConnection('mysql');
```

---

### Error: "Missing CSRF token"

**Full Message**:
```
TokenMismatchException: CSRF token mismatch.
```

**Cause**: CSRF token not included in AJAX request

**Solution**:
```javascript
// ✅ CORRECT - Include CSRF token
$.ajax({
    url: '/admin/users/data',
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    success: function(response) {
        // Handle response
    }
});
```

---

### Error: "Permission denied"

**Full Message**:
```
AuthorizationException: This action is unauthorized.
```

**Cause**: User doesn't have permission to access resource

**Solution**:
```php
// Check permissions in controller
public function getData(Request $request, TableBuilder $table)
{
    $this->authorize('viewAny', User::class);
    
    $table->setModel(new User());
    $table->format();
    
    return response()->json(['html' => $table->render()]);
}

// Or use middleware
Route::post('/admin/users/data', [UserController::class, 'getData'])
    ->middleware(['auth', 'can:viewAny,App\Models\User']);
```

---

### Error: "Rate limit exceeded"

**Full Message**:
```
TooManyRequestsException: Too many requests. Please try again later.
```

**Cause**: Too many AJAX requests in short time

**Solution**:
```php
// Add rate limiting to route
Route::post('/admin/users/data', [UserController::class, 'getData'])
    ->middleware(['throttle:60,1']); // 60 requests per minute

// Or use custom rate limiter
RateLimiter::for('table-data', function (Request $request) {
    return Limit::perMinute(100)->by($request->user()->id);
});
```

---

## 🔍 Debug Mode

### Enabling Debug Mode

**In Controller**:
```php
public function index(TableBuilder $table)
{
    // Enable debug mode
    $table->debug(true);
    
    $table->setModel(new User());
    $table->setFields(['name:Name', 'email:Email']);
    $table->format();
    
    return view('users.index', compact('table'));
}
```

**In Configuration**:
```php
// config/canvastack.php
'table' => [
    'debug' => env('TABLE_DEBUG', false),
],

// .env
TABLE_DEBUG=true
```

**In View**:
```blade
{{-- Enable debug mode for specific table --}}
@php
    $table->debug(true);
@endphp

{!! $table->render() !!}
```

---

### Reading Debug Logs

**Log Location**:
```bash
# Laravel logs
storage/logs/laravel.log

# Table-specific logs
storage/logs/table-debug.log
```

**Log Format**:
```
[2024-02-26 10:30:45] DEBUG: TableBuilder initialized
[2024-02-26 10:30:45] DEBUG: Model set: App\Models\User
[2024-02-26 10:30:45] DEBUG: Connection detected: mysql (from model)
[2024-02-26 10:30:45] DEBUG: Fields configured: name, email, created_at
[2024-02-26 10:30:45] DEBUG: Query executed: SELECT * FROM users WHERE status = 'active'
[2024-02-26 10:30:45] DEBUG: Query time: 45ms
[2024-02-26 10:30:45] DEBUG: Rows returned: 150
[2024-02-26 10:30:45] DEBUG: Memory usage: 12.5MB
[2024-02-26 10:30:45] DEBUG: Render time: 120ms
```

**Reading Logs**:
```bash
# View latest logs
tail -f storage/logs/laravel.log

# Filter debug logs
grep "DEBUG" storage/logs/laravel.log

# Filter table logs
grep "TableBuilder" storage/logs/laravel.log

# View specific time range
grep "2024-02-26 10:30" storage/logs/laravel.log
```

---

### Performance Metrics

**Enable Performance Tracking**:
```php
$table->debug(true);
$table->trackPerformance(true);
$table->format();

// Get metrics
$metrics = $table->getPerformanceMetrics();

/*
Array (
    'query_time' => 45,        // ms
    'render_time' => 120,      // ms
    'memory_usage' => 12.5,    // MB
    'rows_returned' => 150,
    'cache_hits' => 5,
    'cache_misses' => 2,
)
*/
```

**Display Metrics**:
```blade
@if(config('app.debug'))
    <div class="debug-panel">
        <h4>Performance Metrics</h4>
        <ul>
            <li>Query Time: {{ $table->getPerformanceMetrics()['query_time'] }}ms</li>
            <li>Render Time: {{ $table->getPerformanceMetrics()['render_time'] }}ms</li>
            <li>Memory Usage: {{ $table->getPerformanceMetrics()['memory_usage'] }}MB</li>
            <li>Rows: {{ $table->getPerformanceMetrics()['rows_returned'] }}</li>
        </ul>
    </div>
@endif
```

---

### Query Logging

**Enable Query Logging**:
```php
// In controller
DB::enableQueryLog();

$table->setModel(new User());
$table->format();

$queries = DB::getQueryLog();

// Log queries
foreach ($queries as $query) {
    \Log::debug('Query: ' . $query['query']);
    \Log::debug('Bindings: ' . json_encode($query['bindings']));
    \Log::debug('Time: ' . $query['time'] . 'ms');
}
```

**Analyze Queries**:
```php
// Check for N+1 queries
$queryCount = count(DB::getQueryLog());

if ($queryCount > 10) {
    \Log::warning("Possible N+1 query detected: {$queryCount} queries executed");
}

// Check for slow queries
foreach (DB::getQueryLog() as $query) {
    if ($query['time'] > 100) {
        \Log::warning("Slow query detected: {$query['query']} ({$query['time']}ms)");
    }
}
```

---

## ⚡ Performance Troubleshooting

### Issue: Slow Tab Loading

**Symptoms**:
- Tab takes > 2 seconds to load
- Browser shows "Loading..." for long time
- Users complain about slow performance

**Diagnosis**:

1. **Check Query Time**:
```php
$table->debug(true);
$table->trackPerformance(true);
$table->format();

$metrics = $table->getPerformanceMetrics();

if ($metrics['query_time'] > 1000) {
    \Log::warning("Slow query: {$metrics['query_time']}ms");
}
```

2. **Check for N+1 Queries**:
```php
DB::enableQueryLog();

$table->setModel(new User());
$table->format();

$queryCount = count(DB::getQueryLog());

if ($queryCount > 10) {
    \Log::warning("N+1 query detected: {$queryCount} queries");
}
```

3. **Check Memory Usage**:
```php
$memoryBefore = memory_get_usage(true);

$table->format();

$memoryAfter = memory_get_usage(true);
$memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024;

if ($memoryUsed > 50) {
    \Log::warning("High memory usage: {$memoryUsed}MB");
}
```

**Solutions**:

1. **Enable Caching**:
```php
// ✅ Cache table data
$table->cache(300); // 5 minutes
$table->format();
```

2. **Use Eager Loading**:
```php
// ✅ Fix N+1 queries
$table->setModel(new User());
$table->eager(['posts', 'comments', 'profile']);
$table->format();
```

3. **Optimize Query**:
```php
// ✅ Select only needed columns
$table->setFields(['id', 'name', 'email']);

// ✅ Add indexes to database
Schema::table('users', function (Blueprint $table) {
    $table->index('status');
    $table->index('created_at');
});
```

4. **Use Pagination**:
```php
// ✅ Limit rows per page
$table->perPage(25); // Instead of 100
$table->format();
```

---

### Issue: High Memory Usage

**Symptoms**:
- PHP memory limit exceeded
- Server runs out of memory
- Slow performance with large datasets

**Diagnosis**:
```php
// Check memory usage
$memoryBefore = memory_get_usage(true);

$table->setModel(new User());
$table->format();

$memoryAfter = memory_get_usage(true);
$memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024;

\Log::info("Memory used: {$memoryUsed}MB");
```

**Solutions**:

1. **Use Chunking**:
```php
// ✅ Process data in chunks
$table->chunk(100); // Process 100 rows at a time
$table->format();
```

2. **Limit Data**:
```php
// ✅ Limit rows returned
$table->setModel(new User());
$table->limit(1000); // Max 1000 rows
$table->format();
```

3. **Optimize Relationships**:
```php
// ✅ Select only needed relationship columns
$table->eager([
    'posts:id,user_id,title',
    'profile:id,user_id,avatar'
]);
```

4. **Increase Memory Limit**:
```php
// php.ini
memory_limit = 256M

// Or in code (temporary)
ini_set('memory_limit', '256M');
```

---

### Issue: Cache Misses

**Symptoms**:
- Low cache hit ratio
- Queries executed on every request
- Slow performance despite caching enabled

**Diagnosis**:
```php
$table->debug(true);
$table->cache(300);
$table->format();

$metrics = $table->getPerformanceMetrics();
$hitRatio = $metrics['cache_hits'] / ($metrics['cache_hits'] + $metrics['cache_misses']);

if ($hitRatio < 0.5) {
    \Log::warning("Low cache hit ratio: " . ($hitRatio * 100) . "%");
}
```

**Solutions**:

1. **Use Consistent Cache Keys**:
```php
// ✅ Use unique ID for consistent caching
$table->setUniqueId('users-active-table');
$table->cache(300);
```

2. **Increase Cache Duration**:
```php
// ✅ Cache for longer if data doesn't change often
$table->cache(3600); // 1 hour instead of 5 minutes
```

3. **Use Redis**:
```env
# .env
CACHE_DRIVER=redis
REDIS_CLIENT=phpredis
```

4. **Warm Cache**:
```php
// ✅ Pre-warm cache in background job
class WarmTableCache extends Job
{
    public function handle(TableBuilder $table)
    {
        $table->setUniqueId('users-active-table');
        $table->setModel(new User());
        $table->where('status', 'active');
        $table->cache(3600);
        $table->format();
    }
}
```

---

## 💡 Best Practices for Debugging

### 1. Use Structured Logging

```php
// ✅ GOOD - Structured logging
\Log::info('Table rendered', [
    'table_id' => $table->getUniqueId(),
    'model' => get_class($table->getModel()),
    'connection' => $table->getConnection(),
    'query_time' => $metrics['query_time'],
    'rows' => $metrics['rows_returned'],
]);

// ❌ BAD - Unstructured logging
\Log::info('Table rendered with ' . $metrics['rows_returned'] . ' rows');
```

### 2. Add Context to Errors

```php
// ✅ GOOD - Include context
try {
    $table->format();
} catch (\Exception $e) {
    \Log::error('Table format failed', [
        'table_id' => $table->getUniqueId(),
        'model' => get_class($table->getModel()),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    throw $e;
}

// ❌ BAD - No context
try {
    $table->format();
} catch (\Exception $e) {
    \Log::error($e->getMessage());
    throw $e;
}
```

### 3. Use Debug Helpers

```php
// ✅ Create debug helper
if (!function_exists('debug_table')) {
    function debug_table(TableBuilder $table): void
    {
        if (!config('app.debug')) {
            return;
        }
        
        $metrics = $table->getPerformanceMetrics();
        
        dump([
            'Table ID' => $table->getUniqueId(),
            'Model' => get_class($table->getModel()),
            'Connection' => $table->getConnection(),
            'Query Time' => $metrics['query_time'] . 'ms',
            'Render Time' => $metrics['render_time'] . 'ms',
            'Memory' => $metrics['memory_usage'] . 'MB',
            'Rows' => $metrics['rows_returned'],
        ]);
    }
}

// Usage
debug_table($table);
```

### 4. Monitor Performance

```php
// ✅ Add performance monitoring
class TablePerformanceMonitor
{
    public function monitor(TableBuilder $table): void
    {
        $metrics = $table->getPerformanceMetrics();
        
        // Alert if slow
        if ($metrics['query_time'] > 1000) {
            $this->alertSlowQuery($table, $metrics);
        }
        
        // Alert if high memory
        if ($metrics['memory_usage'] > 100) {
            $this->alertHighMemory($table, $metrics);
        }
        
        // Log metrics
        $this->logMetrics($table, $metrics);
    }
    
    private function alertSlowQuery(TableBuilder $table, array $metrics): void
    {
        \Log::warning('Slow table query detected', [
            'table_id' => $table->getUniqueId(),
            'query_time' => $metrics['query_time'],
            'threshold' => 1000,
        ]);
    }
    
    private function alertHighMemory(TableBuilder $table, array $metrics): void
    {
        \Log::warning('High memory usage detected', [
            'table_id' => $table->getUniqueId(),
            'memory_usage' => $metrics['memory_usage'],
            'threshold' => 100,
        ]);
    }
    
    private function logMetrics(TableBuilder $table, array $metrics): void
    {
        // Send to monitoring service (e.g., New Relic, DataDog)
        // ...
    }
}
```

---

## 🔗 Related Documentation

- [Multi-Table Usage Guide](multi-table-usage.md) - Using multiple tables
- [Tab System Usage Guide](tab-system-usage.md) - Tab system features
- [Performance Optimization Guide](performance-optimization.md) - Performance tips
- [Connection Detection Guide](connection-detection.md) - Connection detection
- [Configuration Reference](../configuration/table-config.md) - Configuration options
- [API Reference](../api/table-multi-tab.md) - Complete API documentation

---

## 📚 Additional Resources

### Laravel Documentation
- [Logging](https://laravel.com/docs/logging)
- [Debugging](https://laravel.com/docs/debugging)
- [Database Query Builder](https://laravel.com/docs/queries)
- [Eloquent Performance](https://laravel.com/docs/eloquent#performance)

### Tools
- [Laravel Debugbar](https://github.com/barryvdh/laravel-debugbar) - Debug toolbar
- [Laravel Telescope](https://laravel.com/docs/telescope) - Application monitoring
- [Clockwork](https://underground.works/clockwork/) - PHP debugging tool

### Community
- [CanvaStack GitHub Issues](https://github.com/canvastack/canvastack/issues)
- [CanvaStack Discussions](https://github.com/canvastack/canvastack/discussions)
- [Stack Overflow](https://stackoverflow.com/questions/tagged/canvastack)

---

**Last Updated**: 2024-02-26  
**Version**: 1.0.0  
**Status**: Published
