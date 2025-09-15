# Custom Middleware

CanvaStack Table supports custom middleware for processing requests, responses, and data transformations. This allows you to implement custom business logic, security checks, and data processing pipelines.

## Table of Contents

- [Middleware Basics](#middleware-basics)
- [Request Middleware](#request-middleware)
- [Response Middleware](#response-middleware)
- [Data Processing Middleware](#data-processing-middleware)
- [Security Middleware](#security-middleware)
- [Caching Middleware](#caching-middleware)
- [Logging Middleware](#logging-middleware)
- [Custom Middleware Examples](#custom-middleware-examples)

## Middleware Basics

### Understanding Middleware Pipeline

CanvaStack Table processes requests through a middleware pipeline:

```
Request → Security → Authentication → Validation → Processing → Caching → Response
```

### Registering Middleware

Register custom middleware in your controller:

```php
public function index()
{
    $this->setPage();

    // Register middleware
    $this->table->addMiddleware([
        'security' => SecurityMiddleware::class,
        'audit' => AuditMiddleware::class,
        'transform' => DataTransformMiddleware::class
    ]);

    $this->table->lists('users', ['name', 'email', 'created_at']);

    return $this->render();
}
```

### Middleware Order

Control middleware execution order:

```php
$this->table->setMiddlewareOrder([
    'security',     // First - security checks
    'auth',         // Second - authentication
    'validation',   // Third - input validation
    'transform',    // Fourth - data transformation
    'cache',        // Fifth - caching
    'audit'         // Last - logging/auditing
]);
```

## Request Middleware

### Basic Request Middleware

Create middleware to process incoming requests:

```php
<?php

namespace App\Middleware\Table;

use Closure;

class RequestValidationMiddleware
{
    public function handle($request, Closure $next)
    {
        // Validate request parameters
        $this->validateRequest($request);
        
        // Sanitize input
        $request = $this->sanitizeRequest($request);
        
        // Add custom headers
        $request->headers->set('X-Table-Request', 'processed');
        
        return $next($request);
    }
    
    private function validateRequest($request)
    {
        $rules = [
            'draw' => 'integer|min:1',
            'start' => 'integer|min:0',
            'length' => 'integer|min:1|max:1000',
            'search.value' => 'string|max:255',
            'order.*.column' => 'integer|min:0',
            'order.*.dir' => 'in:asc,desc'
        ];
        
        $validator = validator($request->all(), $rules);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
    
    private function sanitizeRequest($request)
    {
        // Sanitize search input
        if ($request->has('search.value')) {
            $searchValue = strip_tags($request->input('search.value'));
            $searchValue = htmlspecialchars($searchValue, ENT_QUOTES, 'UTF-8');
            $request->merge(['search' => ['value' => $searchValue]]);
        }
        
        return $request;
    }
}
```

### Rate Limiting Middleware

Implement rate limiting for table requests:

```php
<?php

namespace App\Middleware\Table;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class TableRateLimitMiddleware
{
    public function handle($request, Closure $next)
    {
        $key = $this->resolveRequestSignature($request);
        
        if (RateLimiter::tooManyAttempts($key, 60)) { // 60 requests per minute
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'error' => 'Too many requests',
                'retry_after' => $seconds
            ], 429);
        }
        
        RateLimiter::hit($key);
        
        $response = $next($request);
        
        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', 60);
        $response->headers->set('X-RateLimit-Remaining', RateLimiter::remaining($key, 60));
        
        return $response;
    }
    
    private function resolveRequestSignature($request)
    {
        return sha1(
            $request->method() .
            '|' . $request->getHost() .
            '|' . $request->path() .
            '|' . $request->ip() .
            '|' . auth()->id()
        );
    }
}
```

### Request Transformation Middleware

Transform request data before processing:

```php
<?php

namespace App\Middleware\Table;

use Closure;

class RequestTransformMiddleware
{
    public function handle($request, Closure $next)
    {
        // Transform search parameters
        $this->transformSearchParameters($request);
        
        // Transform filter parameters
        $this->transformFilterParameters($request);
        
        // Transform sort parameters
        $this->transformSortParameters($request);
        
        return $next($request);
    }
    
    private function transformSearchParameters($request)
    {
        if ($request->has('search.value')) {
            $searchValue = $request->input('search.value');
            
            // Convert search shortcuts
            $shortcuts = [
                'active:' => 'status:active',
                'inactive:' => 'status:inactive',
                'admin:' => 'role:admin',
                'today:' => 'created_at:' . now()->toDateString()
            ];
            
            foreach ($shortcuts as $shortcut => $replacement) {
                if (str_starts_with($searchValue, $shortcut)) {
                    $searchValue = str_replace($shortcut, $replacement, $searchValue);
                    break;
                }
            }
            
            $request->merge(['search' => ['value' => $searchValue]]);
        }
    }
    
    private function transformFilterParameters($request)
    {
        if ($request->has('filters')) {
            $filters = $request->input('filters');
            
            // Transform date filters
            foreach ($filters as $key => $value) {
                if (str_ends_with($key, '_date') && is_string($value)) {
                    try {
                        $filters[$key] = Carbon::parse($value)->format('Y-m-d');
                    } catch (\Exception $e) {
                        // Invalid date, remove filter
                        unset($filters[$key]);
                    }
                }
            }
            
            $request->merge(['filters' => $filters]);
        }
    }
    
    private function transformSortParameters($request)
    {
        if ($request->has('order')) {
            $order = $request->input('order');
            
            // Map column indexes to names
            $columnMap = [
                0 => 'name',
                1 => 'email',
                2 => 'department.name',
                3 => 'created_at'
            ];
            
            foreach ($order as &$orderItem) {
                if (isset($columnMap[$orderItem['column']])) {
                    $orderItem['column_name'] = $columnMap[$orderItem['column']];
                }
            }
            
            $request->merge(['order' => $order]);
        }
    }
}
```

## Response Middleware

### Response Formatting Middleware

Format responses consistently:

```php
<?php

namespace App\Middleware\Table;

use Closure;

class ResponseFormattingMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        // Format JSON response
        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            
            // Add metadata
            $data['meta'] = [
                'timestamp' => now()->toISOString(),
                'version' => '2.0',
                'request_id' => $request->header('X-Request-ID', uniqid())
            ];
            
            // Format data
            $data = $this->formatResponseData($data);
            
            $response->setData($data);
        }
        
        // Add security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        return $response;
    }
    
    private function formatResponseData($data)
    {
        // Format dates consistently
        if (isset($data['data'])) {
            foreach ($data['data'] as &$row) {
                foreach ($row as $key => &$value) {
                    if (str_ends_with($key, '_at') && $value) {
                        $value = Carbon::parse($value)->format('Y-m-d H:i:s');
                    }
                }
            }
        }
        
        // Add performance metrics
        $data['performance'] = [
            'memory_usage' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - LARAVEL_START
        ];
        
        return $data;
    }
}
```

### Response Compression Middleware

Compress responses for better performance:

```php
<?php

namespace App\Middleware\Table;

use Closure;

class ResponseCompressionMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        // Check if compression is supported
        $acceptEncoding = $request->header('Accept-Encoding', '');
        
        if (str_contains($acceptEncoding, 'gzip') && $this->shouldCompress($response)) {
            $content = $response->getContent();
            $compressedContent = gzencode($content, 6);
            
            if ($compressedContent !== false && strlen($compressedContent) < strlen($content)) {
                $response->setContent($compressedContent);
                $response->headers->set('Content-Encoding', 'gzip');
                $response->headers->set('Content-Length', strlen($compressedContent));
            }
        }
        
        return $response;
    }
    
    private function shouldCompress($response)
    {
        $contentType = $response->headers->get('Content-Type', '');
        $contentLength = strlen($response->getContent());
        
        // Only compress JSON responses larger than 1KB
        return str_contains($contentType, 'application/json') && $contentLength > 1024;
    }
}
```

## Data Processing Middleware

### Data Transformation Middleware

Transform data before sending to client:

```php
<?php

namespace App\Middleware\Table;

use Closure;

class DataTransformationMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            
            if (isset($data['data'])) {
                $data['data'] = $this->transformData($data['data'], $request);
                $response->setData($data);
            }
        }
        
        return $response;
    }
    
    private function transformData($data, $request)
    {
        $user = auth()->user();
        
        return array_map(function($row) use ($user) {
            // Add computed fields
            $row['full_name'] = ($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '');
            $row['initials'] = $this->getInitials($row['full_name']);
            
            // Format currency fields
            foreach (['salary', 'bonus', 'commission'] as $field) {
                if (isset($row[$field])) {
                    $row[$field . '_formatted'] = '$' . number_format($row[$field], 2);
                }
            }
            
            // Add user-specific data
            if ($user) {
                $row['can_edit'] = $user->can('update', $row);
                $row['can_delete'] = $user->can('delete', $row);
            }
            
            // Remove sensitive data based on permissions
            if (!$user || !$user->hasRole('admin')) {
                unset($row['salary'], $row['ssn'], $row['bank_account']);
            }
            
            return $row;
        }, $data);
    }
    
    private function getInitials($name)
    {
        $words = explode(' ', trim($name));
        $initials = '';
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
        }
        
        return $initials;
    }
}
```

### Data Filtering Middleware

Apply additional data filtering:

```php
<?php

namespace App\Middleware\Table;

use Closure;

class DataFilteringMiddleware
{
    public function handle($request, Closure $next)
    {
        // Apply pre-processing filters
        $this->applyPreFilters($request);
        
        $response = $next($request);
        
        // Apply post-processing filters
        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            
            if (isset($data['data'])) {
                $data['data'] = $this->applyPostFilters($data['data'], $request);
                $response->setData($data);
            }
        }
        
        return $response;
    }
    
    private function applyPreFilters($request)
    {
        $user = auth()->user();
        
        // Add department filter for non-admin users
        if (!$user->hasRole('admin')) {
            $filters = $request->input('filters', []);
            $filters['department_id'] = $user->department_id;
            $request->merge(['filters' => $filters]);
        }
        
        // Add date range filter for performance
        if (!$request->has('filters.created_at')) {
            $filters = $request->input('filters', []);
            $filters['created_at'] = [
                'start' => now()->subYear()->toDateString(),
                'end' => now()->toDateString()
            ];
            $request->merge(['filters' => $filters]);
        }
    }
    
    private function applyPostFilters($data, $request)
    {
        $user = auth()->user();
        
        // Filter based on user permissions
        return array_filter($data, function($row) use ($user) {
            // Hide inactive records for regular users
            if (!$user->hasRole('admin') && isset($row['active']) && !$row['active']) {
                return false;
            }
            
            // Hide confidential records
            if (isset($row['confidential']) && $row['confidential'] && !$user->hasPermission('view-confidential')) {
                return false;
            }
            
            return true;
        });
    }
}
```

## Security Middleware

### Authentication Middleware

Verify user authentication:

```php
<?php

namespace App\Middleware\Table;

use Closure;

class TableAuthenticationMiddleware
{
    public function handle($request, Closure $next)
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        // Check if user has table access permission
        if (!auth()->user()->can('view-table', $request->route('table'))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        
        // Check session validity
        if (!$this->isValidSession($request)) {
            return response()->json(['error' => 'Invalid session'], 401);
        }
        
        return $next($request);
    }
    
    private function isValidSession($request)
    {
        $sessionId = $request->session()->getId();
        $userId = auth()->id();
        
        // Check if session is still valid in database
        return DB::table('sessions')
                 ->where('id', $sessionId)
                 ->where('user_id', $userId)
                 ->where('last_activity', '>', now()->subMinutes(120)->timestamp)
                 ->exists();
    }
}
```

### Authorization Middleware

Check user permissions:

```php
<?php

namespace App\Middleware\Table;

use Closure;

class TableAuthorizationMiddleware
{
    public function handle($request, Closure $next)
    {
        $user = auth()->user();
        $tableName = $request->route('table');
        
        // Check table-specific permissions
        if (!$this->hasTablePermission($user, $tableName, $request)) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }
        
        // Check column-level permissions
        $this->filterColumnsByPermissions($request, $user);
        
        // Check row-level permissions
        $this->addRowLevelFilters($request, $user);
        
        return $next($request);
    }
    
    private function hasTablePermission($user, $tableName, $request)
    {
        $action = $this->getActionFromRequest($request);
        $permission = "table.{$tableName}.{$action}";
        
        return $user->can($permission);
    }
    
    private function getActionFromRequest($request)
    {
        if ($request->isMethod('GET')) {
            return 'view';
        } elseif ($request->isMethod('POST')) {
            return $request->has('export') ? 'export' : 'view';
        }
        
        return 'view';
    }
    
    private function filterColumnsByPermissions($request, $user)
    {
        $restrictedColumns = [
            'salary' => 'view-salary',
            'ssn' => 'view-ssn',
            'bank_account' => 'view-financial'
        ];
        
        $columns = $request->input('columns', []);
        
        foreach ($columns as $index => $column) {
            $columnName = $column['data'] ?? '';
            
            if (isset($restrictedColumns[$columnName]) && !$user->can($restrictedColumns[$columnName])) {
                unset($columns[$index]);
            }
        }
        
        $request->merge(['columns' => array_values($columns)]);
    }
    
    private function addRowLevelFilters($request, $user)
    {
        // Add department filter for non-admin users
        if (!$user->hasRole('admin')) {
            $filters = $request->input('filters', []);
            $filters['department_id'] = $user->department_id;
            $request->merge(['filters' => $filters]);
        }
        
        // Add ownership filter for certain tables
        $ownershipTables = ['orders', 'invoices', 'reports'];
        $tableName = $request->route('table');
        
        if (in_array($tableName, $ownershipTables) && !$user->hasRole(['admin', 'manager'])) {
            $filters = $request->input('filters', []);
            $filters['user_id'] = $user->id;
            $request->merge(['filters' => $filters]);
        }
    }
}
```

## Caching Middleware

### Response Caching Middleware

Cache responses for better performance:

```php
<?php

namespace App\Middleware\Table;

use Closure;
use Illuminate\Support\Facades\Cache;

class TableCachingMiddleware
{
    public function handle($request, Closure $next)
    {
        $cacheKey = $this->getCacheKey($request);
        
        // Check if response is cached
        if (Cache::has($cacheKey)) {
            $cachedResponse = Cache::get($cacheKey);
            
            return response()->json($cachedResponse)
                             ->header('X-Cache', 'HIT')
                             ->header('X-Cache-Key', $cacheKey);
        }
        
        $response = $next($request);
        
        // Cache the response if it's successful
        if ($response->isSuccessful() && $this->shouldCache($request, $response)) {
            $ttl = $this->getCacheTTL($request);
            Cache::put($cacheKey, $response->getData(true), $ttl);
            
            $response->header('X-Cache', 'MISS')
                    ->header('X-Cache-TTL', $ttl)
                    ->header('X-Cache-Key', $cacheKey);
        }
        
        return $response;
    }
    
    private function getCacheKey($request)
    {
        $keyParts = [
            'table_response',
            $request->path(),
            auth()->id(),
            md5(serialize($request->all()))
        ];
        
        return implode(':', $keyParts);
    }
    
    private function shouldCache($request, $response)
    {
        // Don't cache if user has admin role (they might see different data)
        if (auth()->user()->hasRole('admin')) {
            return false;
        }
        
        // Don't cache if response contains errors
        $data = $response->getData(true);
        if (isset($data['error'])) {
            return false;
        }
        
        // Don't cache if request has real-time filters
        $realTimeFilters = ['last_login', 'online_status', 'current_activity'];
        $filters = $request->input('filters', []);
        
        foreach ($realTimeFilters as $filter) {
            if (isset($filters[$filter])) {
                return false;
            }
        }
        
        return true;
    }
    
    private function getCacheTTL($request)
    {
        // Different TTL based on data type
        $tableName = $request->route('table');
        
        $ttlMap = [
            'users' => 300,      // 5 minutes
            'products' => 600,   // 10 minutes
            'orders' => 60,      // 1 minute
            'reports' => 1800    // 30 minutes
        ];
        
        return $ttlMap[$tableName] ?? 300;
    }
}
```

## Logging Middleware

### Audit Logging Middleware

Log all table operations for auditing:

```php
<?php

namespace App\Middleware\Table;

use Closure;
use Illuminate\Support\Facades\Log;

class TableAuditMiddleware
{
    public function handle($request, Closure $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Log request
        $this->logRequest($request);
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        // Log response
        $this->logResponse($request, $response, [
            'execution_time' => $endTime - $startTime,
            'memory_usage' => $endMemory - $startMemory,
            'peak_memory' => memory_get_peak_usage()
        ]);
        
        return $response;
    }
    
    private function logRequest($request)
    {
        $logData = [
            'type' => 'table_request',
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'parameters' => $this->sanitizeParameters($request->all()),
            'timestamp' => now()->toISOString()
        ];
        
        Log::channel('audit')->info('Table request', $logData);
    }
    
    private function logResponse($request, $response, $metrics)
    {
        $data = $response instanceof JsonResponse ? $response->getData(true) : [];
        
        $logData = [
            'type' => 'table_response',
            'user_id' => auth()->id(),
            'status_code' => $response->getStatusCode(),
            'record_count' => isset($data['recordsTotal']) ? $data['recordsTotal'] : 0,
            'filtered_count' => isset($data['recordsFiltered']) ? $data['recordsFiltered'] : 0,
            'execution_time' => round($metrics['execution_time'] * 1000, 2), // milliseconds
            'memory_usage' => $this->formatBytes($metrics['memory_usage']),
            'peak_memory' => $this->formatBytes($metrics['peak_memory']),
            'timestamp' => now()->toISOString()
        ];
        
        if ($response->isSuccessful()) {
            Log::channel('audit')->info('Table response', $logData);
        } else {
            $logData['error'] = isset($data['error']) ? $data['error'] : 'Unknown error';
            Log::channel('audit')->error('Table response error', $logData);
        }
    }
    
    private function sanitizeParameters($parameters)
    {
        // Remove sensitive parameters
        $sensitive = ['password', 'token', 'api_key', 'secret'];
        
        foreach ($sensitive as $key) {
            if (isset($parameters[$key])) {
                $parameters[$key] = '[REDACTED]';
            }
        }
        
        return $parameters;
    }
    
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
```

## Custom Middleware Examples

### Multi-Tenant Middleware

Handle multi-tenant data isolation:

```php
<?php

namespace App\Middleware\Table;

use Closure;

class MultiTenantMiddleware
{
    public function handle($request, Closure $next)
    {
        $tenant = $this->resolveTenant($request);
        
        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }
        
        // Set tenant context
        app()->instance('tenant', $tenant);
        
        // Add tenant filter to all queries
        $this->addTenantFilter($request, $tenant);
        
        $response = $next($request);
        
        // Ensure response data belongs to tenant
        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            $data = $this->filterTenantData($data, $tenant);
            $response->setData($data);
        }
        
        return $response;
    }
    
    private function resolveTenant($request)
    {
        // Get tenant from subdomain
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];
        
        return Tenant::where('subdomain', $subdomain)->first();
    }
    
    private function addTenantFilter($request, $tenant)
    {
        $filters = $request->input('filters', []);
        $filters['tenant_id'] = $tenant->id;
        $request->merge(['filters' => $filters]);
    }
    
    private function filterTenantData($data, $tenant)
    {
        if (isset($data['data'])) {
            $data['data'] = array_filter($data['data'], function($row) use ($tenant) {
                return isset($row['tenant_id']) && $row['tenant_id'] == $tenant->id;
            });
        }
        
        return $data;
    }
}
```

### Localization Middleware

Handle multi-language support:

```php
<?php

namespace App\Middleware\Table;

use Closure;

class LocalizationMiddleware
{
    public function handle($request, Closure $next)
    {
        // Set locale from request
        $locale = $request->header('Accept-Language', app()->getLocale());
        app()->setLocale($locale);
        
        $response = $next($request);
        
        // Translate response data
        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            $data = $this->translateData($data, $locale);
            $response->setData($data);
        }
        
        return $response;
    }
    
    private function translateData($data, $locale)
    {
        if (isset($data['data'])) {
            foreach ($data['data'] as &$row) {
                // Translate status values
                if (isset($row['status'])) {
                    $row['status_translated'] = __('status.' . $row['status'], [], $locale);
                }
                
                // Translate department names
                if (isset($row['department'])) {
                    $row['department_translated'] = __('departments.' . $row['department'], [], $locale);
                }
                
                // Format dates according to locale
                foreach ($row as $key => &$value) {
                    if (str_ends_with($key, '_at') && $value) {
                        $row[$key . '_formatted'] = Carbon::parse($value)->locale($locale)->isoFormat('LLL');
                    }
                }
            }
        }
        
        return $data;
    }
}
```

---

## Related Documentation

- [Basic Usage](../basic-usage.md) - Basic table setup
- [Security Features](security.md) - Security middleware examples
- [Performance Optimization](performance.md) - Performance middleware
- [API Reference](../api/objects.md) - Middleware-related methods