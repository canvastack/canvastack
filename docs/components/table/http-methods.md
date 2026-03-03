# HTTP Method Configuration Guide

**Version**: 2.0.0  
**Package**: canvastack/canvastack  
**Last Updated**: 2026-02-26

---

## Table of Contents

1. [Introduction](#introduction)
2. [HTTP Method Overview](#http-method-overview)
3. [Security Considerations](#security-considerations)
4. [API Reference](#api-reference)
5. [Configuration Examples](#configuration-examples)
6. [Server-Side Processing Setup](#server-side-processing-setup)
7. [CSRF Token Handling](#csrf-token-handling)
8. [When to Use GET vs POST](#when-to-use-get-vs-post)
9. [Troubleshooting AJAX Issues](#troubleshooting-ajax-issues)
10. [Best Practices](#best-practices)

---

## Introduction

The CanvaStack Table Component supports configurable HTTP methods for AJAX requests when using server-side processing with DataTables. This guide explains how to configure HTTP methods, when to use GET vs POST, and how to troubleshoot common AJAX issues.

### Key Features

- **Default POST Method**: Secure by default with CSRF protection
- **GET Method Support**: For cacheable, read-only operations
- **Automatic CSRF Handling**: CSRF tokens automatically included for POST requests
- **Custom AJAX URLs**: Configure custom endpoints for data loading
- **Security First**: Built-in protection against common vulnerabilities

---

## HTTP Method Overview

### Default Behavior

By default, the Table Component uses **POST** for all AJAX requests:

```php
$table = app(TableBuilder::class);
$table->model(User::class)
    ->setServerSide(true)
    ->render();
// Uses POST method by default
```

### Why POST is Default

1. **Security**: POST requests include CSRF token protection
2. **No URL Length Limits**: Complex filters don't hit URL length restrictions
3. **Data Privacy**: Request parameters not visible in browser history/logs
4. **Best Practice**: POST is recommended for operations that retrieve sensitive data

### When POST is Generated

The Table Component automatically generates POST AJAX requests when:
- Server-side processing is enabled (`setServerSide(true)`)
- No explicit HTTP method is configured
- DataTables initialization includes CSRF token headers

---

## Security Considerations

### CSRF Protection (POST Requests)

When using POST method, CSRF protection is **automatically enabled**:

```javascript
// Generated JavaScript includes CSRF token
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

$('#table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: '/admin/users/datatable',
        type: 'POST'  // CSRF token automatically included
    }
});
```

**Requirements:**
- Laravel CSRF middleware must be enabled (default)
- CSRF token meta tag must be present in HTML head:
  ```html
  <meta name="csrf-token" content="{{ csrf_token() }}">
  ```

### GET Request Security

GET requests do **not** include CSRF tokens:

```javascript
// Generated JavaScript for GET method
$('#table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: '/admin/users/datatable',
        type: 'GET'  // No CSRF token needed
    }
});
```

**Security Notes:**
- GET requests should only be used for read-only operations
- Sensitive data should not be passed in URL parameters
- Consider caching implications for sensitive data

---

## API Reference

### setHttpMethod()

Set the HTTP method for AJAX requests.

**Signature:**
```php
public function setHttpMethod(string $method): self
```

**Parameters:**
- `$method` (string): HTTP method ('GET' or 'POST', case-insensitive)

**Returns:** `self` for method chaining

**Throws:**
- `InvalidArgumentException`: If method is not 'GET' or 'POST'

**Example:**
```php
// Use POST (default)
$table->setHttpMethod('POST');

// Use GET
$table->setHttpMethod('GET');

// Case-insensitive
$table->setHttpMethod('post');  // Valid
$table->setHttpMethod('get');   // Valid
```

---

### getHttpMethod()

Get the current HTTP method configuration.

**Signature:**
```php
public function getHttpMethod(): string
```

**Returns:** string - Current HTTP method ('GET' or 'POST')

**Example:**
```php
$method = $table->getHttpMethod();
echo "Current method: " . $method;  // Output: POST
```

---

### setAjaxUrl()

Set a custom AJAX URL for data loading.

**Signature:**
```php
public function setAjaxUrl(string $url): self
```

**Parameters:**
- `$url` (string): AJAX endpoint URL (must start with '/' or 'http')

**Returns:** `self` for method chaining

**Throws:**
- `InvalidArgumentException`: If URL format is invalid

**Example:**
```php
// Relative URL
$table->setAjaxUrl('/admin/users/datatable');

// Absolute URL
$table->setAjaxUrl('https://api.example.com/users');

// With route helper
$table->setAjaxUrl(route('users.datatable'));
```

---

### getAjaxUrl()

Get the configured AJAX URL.

**Signature:**
```php
public function getAjaxUrl(): ?string
```

**Returns:** string|null - Configured AJAX URL or null if not set

**Example:**
```php
$url = $table->getAjaxUrl();
if ($url) {
    echo "AJAX URL: " . $url;
} else {
    echo "Using auto-generated URL";
}
```

---

## Configuration Examples

### Example 1: Basic Server-Side Table with Default POST

```php
use Canvastack\Components\Table\TableBuilder;
use App\Models\User;

$table = app(TableBuilder::class);

$html = $table->model(User::class)
    ->setFields(['id', 'name', 'email', 'created_at'])
    ->setServerSide(true)  // Enable server-side processing
    // POST method used by default
    ->render();

echo $html;
```

**Generated AJAX Configuration:**
```javascript
{
    processing: true,
    serverSide: true,
    ajax: {
        url: '/admin/users/datatable',
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': '...'  // Automatically included
        }
    }
}
```

---

### Example 2: Server-Side Table with Explicit GET

```php
$table = app(TableBuilder::class);

$html = $table->model(User::class)
    ->setFields(['id', 'name', 'email', 'status'])
    ->setServerSide(true)
    ->setHttpMethod('GET')  // Explicitly use GET
    ->render();

echo $html;
```

**Generated AJAX Configuration:**
```javascript
{
    processing: true,
    serverSide: true,
    ajax: {
        url: '/admin/users/datatable',
        type: 'GET'
        // No CSRF token for GET requests
    }
}
```

**Use Case:** Public data tables, cached results, read-only operations

---

### Example 3: Server-Side Table with Custom AJAX URL

```php
$table = app(TableBuilder::class);

$html = $table->model(User::class)
    ->setFields(['id', 'name', 'email', 'role'])
    ->setServerSide(true)
    ->setHttpMethod('POST')
    ->setAjaxUrl(route('admin.users.datatable'))  // Custom URL
    ->render();

echo $html;
```

**Generated AJAX Configuration:**
```javascript
{
    processing: true,
    serverSide: true,
    ajax: {
        url: 'https://example.com/admin/users/datatable',
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': '...'
        }
    }
}
```

**Use Case:** Custom endpoints, API integration, microservices

---

### Example 4: Server-Side Table with Filter Groups

```php
$table = app(TableBuilder::class);

$html = $table->model(Order::class)
    ->setFields(['id', 'customer', 'product', 'status', 'total'])
    ->setServerSide(true)
    ->setHttpMethod('POST')  // POST recommended for complex filters
    ->filterGroups('status', 'selectbox', false)
    ->filterGroups('created_at', 'daterangebox', false)
    ->render();

echo $html;
```

**Why POST for Filters:**
- Complex filter data can exceed URL length limits with GET
- Filter values remain private (not in browser history)
- Better security for sensitive filter criteria

---

### Example 5: Client-Side Table (No AJAX)

```php
$table = app(TableBuilder::class);

$html = $table->model(User::class)
    ->setFields(['id', 'name', 'email'])
    ->setServerSide(false)  // Client-side processing
    // HTTP method not used (no AJAX)
    ->render();

echo $html;
```

**Behavior:**
- All data loaded at once in HTML
- No AJAX requests made
- HTTP method configuration ignored
- Suitable for small datasets (< 1000 rows)

---

## Server-Side Processing Setup

### Step 1: Enable Server-Side Processing

```php
$table->setServerSide(true);
```

### Step 2: Configure HTTP Method (Optional)

```php
// Use POST (default, recommended)
$table->setHttpMethod('POST');

// Or use GET for cacheable data
$table->setHttpMethod('GET');
```

### Step 3: Set Custom AJAX URL (Optional)

```php
$table->setAjaxUrl(route('users.datatable'));
```

### Step 4: Create Route

```php
// routes/web.php
Route::get('/admin/users/datatable', [UserController::class, 'datatable'])
    ->name('users.datatable');

// Or for POST
Route::post('/admin/users/datatable', [UserController::class, 'datatable'])
    ->name('users.datatable');
```

### Step 5: Create Controller Method

```php
// app/Http/Controllers/UserController.php
use Illuminate\Http\Request;
use Canvastack\Components\Table\TableBuilder;
use App\Models\User;

public function datatable(Request $request)
{
    $table = app(TableBuilder::class);
    
    $table->model(User::class)
        ->setFields(['id', 'name', 'email', 'created_at'])
        ->setServerSide(true);
    
    // Return JSON response for DataTables
    return $table->ajax($request);
}
```

### Step 6: Ensure CSRF Token in Layout

```html
<!-- resources/views/layouts/app.blade.php -->
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
```

---

## CSRF Token Handling

### Automatic CSRF Token Inclusion

For POST requests, the Table Component automatically generates JavaScript that includes the CSRF token:

```javascript
// Automatically generated for POST method
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
```

### Manual CSRF Token Configuration

If you need to customize CSRF handling:

```javascript
// Custom CSRF token handling
$('#table').DataTable({
    ajax: {
        url: '/admin/users/datatable',
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        // Or use data parameter
        data: function(d) {
            d._token = '{{ csrf_token() }}';
            return d;
        }
    }
});
```

### CSRF Token Validation in Controller

Laravel automatically validates CSRF tokens for POST requests:

```php
// No additional code needed - Laravel handles it
public function datatable(Request $request)
{
    // CSRF token already validated by middleware
    // If invalid, Laravel returns 419 error
    
    return $table->ajax($request);
}
```

### Troubleshooting CSRF Issues

**Error: 419 Page Expired**

**Cause:** CSRF token missing or invalid

**Solutions:**
1. Ensure meta tag is present:
   ```html
   <meta name="csrf-token" content="{{ csrf_token() }}">
   ```

2. Check CSRF middleware is enabled:
   ```php
   // app/Http/Kernel.php
   protected $middlewareGroups = [
       'web' => [
           \App\Http\Middleware\VerifyCsrfToken::class,
       ],
   ];
   ```

3. Exclude route from CSRF if needed (not recommended):
   ```php
   // app/Http/Middleware/VerifyCsrfToken.php
   protected $except = [
       'admin/users/datatable',  // Not recommended
   ];
   ```

---

## When to Use GET vs POST

### Use POST When:

✅ **Recommended for most cases**

- Handling sensitive data (user information, financial data)
- Complex filter criteria that might exceed URL length limits
- Operations that modify server state (even indirectly)
- You want CSRF protection
- Data should not be cached by browsers/proxies
- Request parameters should not appear in logs

**Example:**
```php
// User management table with sensitive data
$table->model(User::class)
    ->setServerSide(true)
    ->setHttpMethod('POST')  // Secure, CSRF protected
    ->render();
```

### Use GET When:

✅ **Suitable for specific scenarios**

- Public data that's safe to cache
- Read-only operations with no side effects
- Simple filter criteria (no complex objects)
- You want browser/CDN caching
- Debugging (easier to see parameters in URL)
- Bookmarkable URLs with filters

**Example:**
```php
// Public product catalog
$table->model(Product::where('published', true))
    ->setServerSide(true)
    ->setHttpMethod('GET')  // Cacheable, public data
    ->render();
```

### Comparison Table

| Feature | POST | GET |
|---------|------|-----|
| CSRF Protection | ✅ Yes | ❌ No |
| URL Length Limit | ✅ No limit | ⚠️ ~2000 chars |
| Browser Caching | ❌ Not cached | ✅ Cached |
| Visible in Logs | ✅ Hidden | ❌ Visible |
| Bookmarkable | ❌ No | ✅ Yes |
| Security | ✅ Higher | ⚠️ Lower |
| Default Method | ✅ Yes | ❌ No |

---

## Troubleshooting AJAX Issues

### Issue 1: Table Not Loading Data

**Symptoms:**
- Empty table with "Loading..." message
- No data appears after page load
- Console shows no errors

**Diagnosis:**
```javascript
// Open browser console (F12) and check Network tab
// Look for AJAX request to datatable endpoint
```

**Solutions:**

1. **Check server-side processing is enabled:**
   ```php
   $table->setServerSide(true);  // Must be true
   ```

2. **Verify AJAX URL is correct:**
   ```php
   // Check generated URL
   $url = $table->getAjaxUrl();
   dd($url);  // Should match your route
   ```

3. **Check route exists:**
   ```bash
   php artisan route:list | grep datatable
   ```

4. **Verify controller method returns JSON:**
   ```php
   public function datatable(Request $request)
   {
       return $table->ajax($request);  // Must return JSON
   }
   ```

---

### Issue 2: 419 CSRF Token Mismatch

**Symptoms:**
- 419 error in browser console
- "Page Expired" message
- POST requests failing

**Solutions:**

1. **Add CSRF meta tag:**
   ```html
   <head>
       <meta name="csrf-token" content="{{ csrf_token() }}">
   </head>
   ```

2. **Verify CSRF middleware is active:**
   ```php
   // Check app/Http/Kernel.php
   'web' => [
       \App\Http\Middleware\VerifyCsrfToken::class,
   ],
   ```

3. **Use GET method if CSRF is problematic:**
   ```php
   $table->setHttpMethod('GET');  // Bypass CSRF
   ```

---

### Issue 3: 404 Not Found Error

**Symptoms:**
- 404 error in browser console
- AJAX request to wrong URL
- Route not found

**Solutions:**

1. **Check route is defined:**
   ```php
   // routes/web.php
   Route::post('/admin/users/datatable', [UserController::class, 'datatable']);
   ```

2. **Verify HTTP method matches:**
   ```php
   // If using GET in table
   $table->setHttpMethod('GET');
   
   // Route must also be GET
   Route::get('/admin/users/datatable', ...);
   ```

3. **Use named routes:**
   ```php
   // Define named route
   Route::post('/admin/users/datatable', ...)
       ->name('users.datatable');
   
   // Use in table
   $table->setAjaxUrl(route('users.datatable'));
   ```

---

### Issue 4: 500 Internal Server Error

**Symptoms:**
- 500 error in browser console
- Server error in AJAX response
- Table shows error message

**Diagnosis:**
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log
```

**Common Causes & Solutions:**

1. **Model not set:**
   ```php
   // ❌ Wrong
   $table->setFields(['id', 'name']);
   return $table->ajax($request);
   
   // ✅ Correct
   $table->model(User::class);
   $table->setFields(['id', 'name']);
   return $table->ajax($request);
   ```

2. **Invalid column names:**
   ```php
   // ❌ Wrong - column doesn't exist
   $table->setFields(['id', 'nonexistent_column']);
   
   // ✅ Correct - use valid columns
   $table->setFields(['id', 'name', 'email']);
   ```

3. **Database connection error:**
   ```php
   // Check database configuration
   DB::connection()->getPdo();  // Test connection
   ```

---

### Issue 5: Slow AJAX Requests

**Symptoms:**
- Table takes > 2 seconds to load
- "Processing..." message shows for long time
- Poor user experience

**Diagnosis:**
```php
// Enable query logging
DB::enableQueryLog();
$table->ajax($request);
$queries = DB::getQueryLog();
dd($queries);  // Check query count and time
```

**Solutions:**

1. **Enable caching:**
   ```php
   $table->config(['cache_seconds' => 300]);  // 5 minutes
   ```

2. **Use eager loading:**
   ```php
   $table->relations(User::class, 'role', 'name');  // Prevent N+1
   ```

3. **Optimize query:**
   ```php
   // Select only needed columns
   $table->model(User::select(['id', 'name', 'email']));
   ```

4. **Add database indexes:**
   ```sql
   CREATE INDEX idx_users_email ON users(email);
   CREATE INDEX idx_users_created_at ON users(created_at);
   ```

---

### Issue 6: CORS Errors (Cross-Origin)

**Symptoms:**
- CORS error in browser console
- "Access-Control-Allow-Origin" error
- AJAX request blocked

**Solutions:**

1. **Use same-origin URLs:**
   ```php
   // ❌ Wrong - cross-origin
   $table->setAjaxUrl('https://api.example.com/users');
   
   // ✅ Correct - same origin
   $table->setAjaxUrl('/admin/users/datatable');
   ```

2. **Configure CORS middleware (if needed):**
   ```php
   // app/Http/Middleware/Cors.php
   public function handle($request, Closure $next)
   {
       return $next($request)
           ->header('Access-Control-Allow-Origin', '*')
           ->header('Access-Control-Allow-Methods', 'GET, POST')
           ->header('Access-Control-Allow-Headers', 'Content-Type, X-CSRF-TOKEN');
   }
   ```

---

## Best Practices

### 1. Always Use POST for Sensitive Data

```php
// ✅ Good - secure by default
$table->model(User::class)
    ->setServerSide(true)
    ->setHttpMethod('POST')  // CSRF protected
    ->render();
```

### 2. Use Named Routes for AJAX URLs

```php
// ✅ Good - maintainable
Route::post('/admin/users/datatable', ...)
    ->name('users.datatable');

$table->setAjaxUrl(route('users.datatable'));
```

### 3. Enable Caching for Better Performance

```php
// ✅ Good - faster subsequent loads
$table->config(['cache_seconds' => 300])
    ->setServerSide(true)
    ->render();
```

### 4. Use Eager Loading for Relationships

```php
// ✅ Good - prevents N+1 queries
$table->relations(Order::class, 'customer', 'name')
    ->relations(Order::class, 'product', 'name')
    ->render();
```

### 5. Validate and Sanitize Request Data

```php
// ✅ Good - secure controller
public function datatable(Request $request)
{
    $validated = $request->validate([
        'draw' => 'required|integer',
        'start' => 'required|integer|min:0',
        'length' => 'required|integer|min:1|max:100',
    ]);
    
    return $table->ajax($request);
}
```

### 6. Handle Errors Gracefully

```php
// ✅ Good - error handling
public function datatable(Request $request)
{
    try {
        return $table->ajax($request);
    } catch (\Exception $e) {
        Log::error('DataTable error: ' . $e->getMessage());
        
        return response()->json([
            'error' => 'Failed to load data'
        ], 500);
    }
}
```

### 7. Use GET Only for Public, Cacheable Data

```php
// ✅ Good - public product catalog
$table->model(Product::where('published', true))
    ->setServerSide(true)
    ->setHttpMethod('GET')  // Cacheable
    ->config(['cache_seconds' => 600])  // 10 minutes
    ->render();
```

### 8. Monitor Performance

```php
// ✅ Good - performance monitoring
$start = microtime(true);
$response = $table->ajax($request);
$duration = microtime(true) - $start;

if ($duration > 1.0) {
    Log::warning('Slow DataTable request', [
        'duration' => $duration,
        'url' => $request->url()
    ]);
}

return $response;
```

---

## Summary

### Key Takeaways

1. **POST is default** - Secure by default with CSRF protection
2. **GET for public data** - Use when caching is beneficial
3. **CSRF automatic** - Handled automatically for POST requests
4. **Custom URLs supported** - Use `setAjaxUrl()` for custom endpoints
5. **Server-side required** - HTTP method only applies to server-side processing

### Quick Reference

```php
// Default (POST with CSRF)
$table->setServerSide(true)->render();

// Explicit POST
$table->setServerSide(true)
    ->setHttpMethod('POST')
    ->render();

// Use GET
$table->setServerSide(true)
    ->setHttpMethod('GET')
    ->render();

// Custom URL
$table->setServerSide(true)
    ->setAjaxUrl(route('users.datatable'))
    ->render();
```

---

**For more information:**
- [API Documentation](./API-DOCUMENTATION.md)
- [Code Examples](./CODE-EXAMPLES.md)
- [Troubleshooting Guide](./TROUBLESHOOTING.md)
- [Performance Tuning](./PERFORMANCE-TUNING.md)
