# POST Method (Server-Side Processing)

The POST method in CanvaStack Table implements server-side processing where data is processed on the server and sent to the client via AJAX requests. This approach is essential for large datasets and provides better performance and security.

## Table of Contents

- [Overview](#overview)
- [Basic Implementation](#basic-implementation)
- [AJAX Request Handling](#ajax-request-handling)
- [Advanced Configuration](#advanced-configuration)
- [Performance Optimization](#performance-optimization)
- [Security Considerations](#security-considerations)
- [Error Handling](#error-handling)
- [Best Practices](#best-practices)

## Overview

### How POST Method Works

In POST method (server-side processing):

1. **Initial Load**: Only table structure is rendered initially
2. **AJAX Requests**: Data is fetched via AJAX POST requests
3. **Server Processing**: Filtering, sorting, and pagination happen on the server
4. **Partial Updates**: Only necessary data is sent to the client
5. **Real-time Data**: Fresh data is fetched with each request

### When to Use POST Method

Use POST method when:
- Dataset is large (> 1,000 records)
- Data changes frequently
- Server-side security is required
- Memory usage needs to be minimized
- Real-time filtering and sorting are needed

## Basic Implementation

### Simple POST Method Setup

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

        // Enable server-side processing
        $this->table->method('POST');

        // Enable features that work well with server-side processing
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
}
```

### Automatic Method Selection

```php
public function index()
{
    $this->setPage();

    // Automatically choose method based on dataset size
    $recordCount = User::count();
    
    if ($recordCount > 1000) {
        $this->table->method('POST');
        Log::info("Using server-side processing for {$recordCount} records");
    } else {
        $this->table->method('GET');
        Log::info("Using client-side processing for {$recordCount} records");
    }

    $this->table->searchable()
                ->sortable()
                ->clickable();

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email Address',
        'created_at:Registration Date'
    ], true);

    return $this->render();
}
```

## AJAX Request Handling

### Understanding AJAX Requests

Server-side processing sends AJAX requests with these parameters:

```javascript
// Example AJAX request data
{
    "draw": 1,                    // Request counter
    "start": 0,                   // Starting record number
    "length": 25,                 // Number of records to return
    "search": {
        "value": "john",          // Global search value
        "regex": false            // Whether search is regex
    },
    "order": [
        {
            "column": 0,          // Column index to sort
            "dir": "asc"          // Sort direction
        }
    ],
    "columns": [
        {
            "data": "name",       // Column data source
            "name": "name",       // Column name
            "searchable": true,   // Whether column is searchable
            "orderable": true,    // Whether column is sortable
            "search": {
                "value": "",      // Column-specific search
                "regex": false
            }
        }
    ]
}
```

### Custom AJAX Handler

```php
public function ajaxData(Request $request)
{
    // Validate AJAX request
    $this->validateAjaxRequest($request);

    // Get base query
    $query = $this->getBaseQuery();

    // Apply global search
    if ($request->has('search.value') && !empty($request->input('search.value'))) {
        $query = $this->applyGlobalSearch($query, $request->input('search.value'));
    }

    // Apply column-specific searches
    $query = $this->applyColumnSearches($query, $request->input('columns', []));

    // Apply filters
    if ($request->has('filters')) {
        $query = $this->applyFilters($query, $request->input('filters'));
    }

    // Get total count before filtering
    $totalRecords = $this->getTotalRecords();

    // Get filtered count
    $filteredRecords = $query->count();

    // Apply sorting
    $query = $this->applySorting($query, $request->input('order', []));

    // Apply pagination
    $start = $request->input('start', 0);
    $length = $request->input('length', 25);
    $query = $query->skip($start)->take($length);

    // Get data
    $data = $query->get();

    // Transform data
    $transformedData = $this->transformData($data);

    // Return DataTables response
    return response()->json([
        'draw' => intval($request->input('draw')),
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $transformedData
    ]);
}

private function validateAjaxRequest(Request $request)
{
    $rules = [
        'draw' => 'required|integer|min:1',
        'start' => 'required|integer|min:0',
        'length' => 'required|integer|min:1|max:1000',
        'search.value' => 'nullable|string|max:255',
        'order.*.column' => 'integer|min:0',
        'order.*.dir' => 'in:asc,desc'
    ];

    $request->validate($rules);
}

private function getBaseQuery()
{
    return User::with(['department:id,name'])
               ->select([
                   'users.id',
                   'users.name',
                   'users.email',
                   'users.department_id',
                   'users.created_at'
               ]);
}

private function applyGlobalSearch($query, $searchValue)
{
    return $query->where(function($q) use ($searchValue) {
        $q->where('name', 'LIKE', "%{$searchValue}%")
          ->orWhere('email', 'LIKE', "%{$searchValue}%")
          ->orWhereHas('department', function($dept) use ($searchValue) {
              $dept->where('name', 'LIKE', "%{$searchValue}%");
          });
    });
}

private function applySorting($query, $orderData)
{
    $columnMap = [
        0 => 'name',
        1 => 'email',
        2 => 'department.name',
        3 => 'created_at'
    ];

    foreach ($orderData as $order) {
        $columnIndex = $order['column'];
        $direction = $order['dir'];

        if (isset($columnMap[$columnIndex])) {
            $column = $columnMap[$columnIndex];

            if (str_contains($column, '.')) {
                // Handle relationship sorting
                [$relation, $field] = explode('.', $column);
                $query = $query->join("{$relation}s", "users.{$relation}_id", '=', "{$relation}s.id")
                              ->orderBy("{$relation}s.{$field}", $direction);
            } else {
                $query = $query->orderBy($column, $direction);
            }
        }
    }

    return $query;
}

private function transformData($data)
{
    return $data->map(function($item) {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'email' => $item->email,
            'department_name' => $item->department->name ?? 'N/A',
            'created_at' => $item->created_at->format('Y-m-d H:i:s'),
            'actions' => $this->generateActionButtons($item)
        ];
    });
}

private function generateActionButtons($item)
{
    $buttons = [];

    if (auth()->user()->can('view', $item)) {
        $buttons[] = '<a href="/users/' . $item->id . '" class="btn btn-info btn-sm">View</a>';
    }

    if (auth()->user()->can('update', $item)) {
        $buttons[] = '<a href="/users/' . $item->id . '/edit" class="btn btn-primary btn-sm">Edit</a>';
    }

    if (auth()->user()->can('delete', $item)) {
        $buttons[] = '<button class="btn btn-danger btn-sm delete-btn" data-id="' . $item->id . '">Delete</button>';
    }

    return implode(' ', $buttons);
}
```

## Advanced Configuration

### DataTables Server-Side Configuration

```php
public function index()
{
    $this->setPage();

    $this->table->method('POST');

    // Configure DataTables for server-side processing
    $this->table->setDataTablesConfig([
        'processing' => true,
        'serverSide' => true,
        'ajax' => [
            'url' => route('users.ajax'),
            'type' => 'POST',
            'headers' => [
                'X-CSRF-TOKEN' => csrf_token()
            ],
            'data' => function($d) {
                // Add custom parameters
                $d['custom_filter'] = 'value';
                return $d;
            },
            'error' => function($xhr, $error, $thrown) {
                console.error('AJAX Error:', error);
                alert('Failed to load data. Please try again.');
            }
        ],
        'pageLength' => 25,
        'lengthMenu' => [[10, 25, 50, 100], [10, 25, 50, 100]],
        'order' => [[3, 'desc']], // Order by created_at desc
        'searching' => true,
        'ordering' => true,
        'paging' => true,
        'info' => true,
        'autoWidth' => false,
        'responsive' => true,
        'searchDelay' => 500, // Delay search to reduce server requests
        'language' => [
            'processing' => 'Loading data...',
            'search' => 'Search users:',
            'lengthMenu' => 'Show _MENU_ users per page',
            'info' => 'Showing _START_ to _END_ of _TOTAL_ users',
            'infoEmpty' => 'No users found',
            'infoFiltered' => '(filtered from _MAX_ total users)',
            'paginate' => [
                'first' => 'First',
                'last' => 'Last',
                'next' => 'Next',
                'previous' => 'Previous'
            ]
        ]
    ]);

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email',
        'department.name:Department',
        'created_at:Registration Date'
    ], true);

    return $this->render();
}
```

### Advanced Filtering

```php
public function ajaxData(Request $request)
{
    $query = $this->getBaseQuery();

    // Apply advanced filters
    $filters = $request->input('filters', []);

    // Date range filter
    if (!empty($filters['date_range'])) {
        $dateRange = explode(' to ', $filters['date_range']);
        if (count($dateRange) === 2) {
            $query->whereBetween('created_at', [
                Carbon::parse($dateRange[0])->startOfDay(),
                Carbon::parse($dateRange[1])->endOfDay()
            ]);
        }
    }

    // Status filter
    if (!empty($filters['status'])) {
        $query->where('status', $filters['status']);
    }

    // Department filter with dependency
    if (!empty($filters['department_id'])) {
        $query->where('department_id', $filters['department_id']);
    }

    // Role filter (many-to-many)
    if (!empty($filters['role_id'])) {
        $query->whereHas('roles', function($q) use ($filters) {
            $q->where('role_id', $filters['role_id']);
        });
    }

    // Custom search filters
    if (!empty($filters['custom_search'])) {
        $searchTerm = $filters['custom_search'];
        $query->where(function($q) use ($searchTerm) {
            $q->where('name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('email', 'LIKE', "%{$searchTerm}%")
              ->orWhere('phone', 'LIKE', "%{$searchTerm}%");
        });
    }

    // Continue with standard processing...
    return $this->processAjaxRequest($query, $request);
}
```

### Real-time Updates

```php
public function index()
{
    $this->setPage();

    $this->table->method('POST');

    // Configure real-time updates
    $this->table->setRealTimeConfig([
        'enabled' => true,
        'interval' => 30000, // 30 seconds
        'events' => ['user.created', 'user.updated', 'user.deleted'],
        'websocket' => [
            'enabled' => true,
            'channel' => 'users-table',
            'event' => 'table-update'
        ],
        'polling' => [
            'enabled' => false, // Disable if using WebSocket
            'interval' => 60000 // 1 minute fallback
        ]
    ]);

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email',
        'created_at:Registration Date'
    ], true);

    return $this->render();
}

// WebSocket event handler
public function broadcastTableUpdate($event, $data)
{
    broadcast(new TableUpdateEvent('users-table', [
        'event' => $event,
        'data' => $data,
        'timestamp' => now()->toISOString()
    ]));
}
```

## Performance Optimization

### Query Optimization

```php
private function getOptimizedQuery()
{
    return User::query()
        // Select only needed columns
        ->select([
            'users.id',
            'users.name',
            'users.email',
            'users.status',
            'users.department_id',
            'users.created_at'
        ])
        // Eager load relationships
        ->with(['department:id,name'])
        // Add indexes for commonly filtered/sorted columns
        ->addSelect(DB::raw('departments.name as department_name'))
        ->leftJoin('departments', 'users.department_id', '=', 'departments.id');
}

private function applyCaching($query, $request)
{
    // Cache expensive queries
    $cacheKey = $this->generateCacheKey($request);
    $cacheTTL = 300; // 5 minutes

    return Cache::remember($cacheKey, $cacheTTL, function() use ($query) {
        return $query->get();
    });
}

private function generateCacheKey($request)
{
    $keyParts = [
        'users_table',
        auth()->id(),
        md5(serialize($request->only(['search', 'order', 'filters'])))
    ];

    return implode(':', $keyParts);
}
```

### Pagination Optimization

```php
private function getOptimizedPagination($query, $start, $length)
{
    // Use cursor pagination for better performance on large datasets
    if ($start > 10000) {
        return $this->getCursorPagination($query, $start, $length);
    }

    // Use offset pagination for smaller datasets
    return $query->skip($start)->take($length);
}

private function getCursorPagination($query, $start, $length)
{
    // Implement cursor-based pagination
    $lastId = $this->getLastIdFromStart($start);
    
    return $query->where('id', '>', $lastId)
                 ->orderBy('id')
                 ->take($length);
}

private function getSmartCount($query)
{
    // Use approximate count for large datasets
    $exactCountThreshold = 10000;
    
    $count = $query->count();
    
    if ($count > $exactCountThreshold) {
        // Return approximate count for better performance
        return $this->getApproximateCount($query);
    }
    
    return $count;
}
```

### Response Optimization

```php
private function optimizeResponse($data, $request)
{
    // Minimize response size
    $optimizedData = $data->map(function($item) {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'email' => $item->email,
            'department' => $item->department_name,
            'created_at' => $item->created_at->format('Y-m-d'),
            // Only include actions if user has permissions
            'actions' => $this->shouldIncludeActions() ? $this->generateActions($item) : null
        ];
    });

    // Compress response if supported
    if ($request->header('Accept-Encoding') && str_contains($request->header('Accept-Encoding'), 'gzip')) {
        return response()->json($optimizedData)->header('Content-Encoding', 'gzip');
    }

    return response()->json($optimizedData);
}
```

## Security Considerations

### Input Validation

```php
private function validateAndSanitizeInput($request)
{
    // Validate all input parameters
    $validated = $request->validate([
        'draw' => 'required|integer|min:1|max:999999',
        'start' => 'required|integer|min:0|max:1000000',
        'length' => 'required|integer|min:1|max:1000',
        'search.value' => 'nullable|string|max:255',
        'order.*.column' => 'integer|min:0|max:50',
        'order.*.dir' => 'in:asc,desc',
        'columns.*.data' => 'string|max:100',
        'columns.*.search.value' => 'nullable|string|max:255'
    ]);

    // Sanitize search input
    if (!empty($validated['search']['value'])) {
        $validated['search']['value'] = $this->sanitizeSearchInput($validated['search']['value']);
    }

    // Validate column names against whitelist
    $this->validateColumnNames($validated['columns'] ?? []);

    return $validated;
}

private function sanitizeSearchInput($input)
{
    // Remove potentially dangerous characters
    $input = strip_tags($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    
    // Remove SQL injection patterns
    $sqlPatterns = [
        '/(\s*(union|select|insert|update|delete|drop|create|alter|exec|execute)\s+)/i',
        '/(\s*(or|and)\s+\d+\s*=\s*\d+)/i',
        '/(\s*;\s*)/i'
    ];
    
    foreach ($sqlPatterns as $pattern) {
        $input = preg_replace($pattern, '', $input);
    }
    
    return trim($input);
}

private function validateColumnNames($columns)
{
    $allowedColumns = ['name', 'email', 'department.name', 'created_at', 'status'];
    
    foreach ($columns as $column) {
        if (!in_array($column['data'], $allowedColumns)) {
            throw new ValidationException("Invalid column: {$column['data']}");
        }
    }
}
```

### Access Control

```php
public function ajaxData(Request $request)
{
    // Check user permissions
    if (!auth()->user()->can('view-users-table')) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    // Apply row-level security
    $query = $this->getSecureQuery();

    // Continue with processing...
}

private function getSecureQuery()
{
    $query = User::query();
    $user = auth()->user();

    // Apply department-level access control
    if (!$user->hasRole('admin')) {
        $query->where('department_id', $user->department_id);
    }

    // Apply additional security filters
    if (!$user->hasPermission('view-inactive-users')) {
        $query->where('active', true);
    }

    return $query;
}
```

## Error Handling

### Comprehensive Error Handling

```php
public function ajaxData(Request $request)
{
    try {
        // Validate request
        $validated = $this->validateAndSanitizeInput($request);

        // Process request
        $result = $this->processAjaxRequest($validated);

        return response()->json($result);

    } catch (ValidationException $e) {
        Log::warning('Table AJAX validation error', [
            'user_id' => auth()->id(),
            'errors' => $e->errors(),
            'request' => $request->all()
        ]);

        return response()->json([
            'error' => 'Invalid request parameters',
            'details' => $e->errors()
        ], 422);

    } catch (QueryException $e) {
        Log::error('Table AJAX database error', [
            'user_id' => auth()->id(),
            'error' => $e->getMessage(),
            'sql' => $e->getSql()
        ]);

        return response()->json([
            'error' => 'Database error occurred',
            'message' => app()->environment('local') ? $e->getMessage() : 'Please try again later'
        ], 500);

    } catch (\Exception $e) {
        Log::error('Table AJAX general error', [
            'user_id' => auth()->id(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'error' => 'An unexpected error occurred',
            'message' => 'Please refresh the page and try again'
        ], 500);
    }
}
```

### Client-Side Error Handling

```javascript
// Configure DataTables error handling
$('#users-table').DataTable({
    // ... other configuration
    ajax: {
        url: '/users/ajax',
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        error: function(xhr, error, thrown) {
            console.error('DataTables AJAX Error:', {
                status: xhr.status,
                error: error,
                thrown: thrown,
                response: xhr.responseText
            });

            // Show user-friendly error message
            let message = 'Failed to load data. ';
            
            if (xhr.status === 422) {
                message += 'Invalid request parameters.';
            } else if (xhr.status === 403) {
                message += 'You do not have permission to view this data.';
            } else if (xhr.status === 500) {
                message += 'Server error occurred. Please try again later.';
            } else {
                message += 'Please check your connection and try again.';
            }

            // Display error message
            $('#error-message').text(message).show();
            
            // Hide loading indicator
            $('.dataTables_processing').hide();
        }
    }
});
```

## Best Practices

### Request Optimization

```php
public function ajaxData(Request $request)
{
    // Implement request deduplication
    $requestHash = $this->getRequestHash($request);
    
    if ($this->isDuplicateRequest($requestHash)) {
        return $this->getCachedResponse($requestHash);
    }

    // Process request
    $response = $this->processRequest($request);
    
    // Cache response
    $this->cacheResponse($requestHash, $response);
    
    return $response;
}

private function getRequestHash($request)
{
    return md5(serialize([
        'user_id' => auth()->id(),
        'params' => $request->only(['draw', 'start', 'length', 'search', 'order', 'filters'])
    ]));
}
```

### Monitoring and Logging

```php
public function ajaxData(Request $request)
{
    $startTime = microtime(true);
    $startMemory = memory_get_usage();

    try {
        $result = $this->processAjaxRequest($request);

        // Log successful request
        $this->logRequest($request, $startTime, $startMemory, 'success');

        return response()->json($result);

    } catch (\Exception $e) {
        // Log failed request
        $this->logRequest($request, $startTime, $startMemory, 'error', $e);
        
        throw $e;
    }
}

private function logRequest($request, $startTime, $startMemory, $status, $exception = null)
{
    $executionTime = microtime(true) - $startTime;
    $memoryUsage = memory_get_usage() - $startMemory;

    $logData = [
        'user_id' => auth()->id(),
        'status' => $status,
        'execution_time' => round($executionTime * 1000, 2), // milliseconds
        'memory_usage' => $this->formatBytes($memoryUsage),
        'request_size' => strlen(json_encode($request->all())),
        'parameters' => [
            'start' => $request->input('start'),
            'length' => $request->input('length'),
            'search' => !empty($request->input('search.value')),
            'filters' => count($request->input('filters', [])),
            'order' => count($request->input('order', []))
        ]
    ];

    if ($exception) {
        $logData['error'] = $exception->getMessage();
    }

    Log::channel('table_performance')->info('Table AJAX request', $logData);
}
```

---

## Related Documentation

- [GET Method](get.md) - Client-side processing alternative
- [AJAX Handling](ajax.md) - Advanced AJAX customization
- [Performance Optimization](../advanced/performance.md) - Server-side performance tuning
- [Security Features](../advanced/security.md) - Security best practices