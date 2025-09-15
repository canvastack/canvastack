# AJAX Handling

CanvaStack Table provides comprehensive AJAX handling capabilities for custom endpoints, real-time updates, and advanced data processing. This guide covers custom AJAX implementations beyond the standard POST method.

## Table of Contents

- [Custom AJAX Endpoints](#custom-ajax-endpoints)
- [AJAX Configuration](#ajax-configuration)
- [Real-time Updates](#real-time-updates)
- [Custom Data Processing](#custom-data-processing)
- [Error Handling](#error-handling)
- [Performance Optimization](#performance-optimization)
- [Security Considerations](#security-considerations)
- [Advanced Examples](#advanced-examples)

## Custom AJAX Endpoints

### Basic Custom Endpoint

Create custom AJAX endpoints for specialized data processing:

```php
<?php

namespace App\Http\Controllers;

use Canvastack\Canvastack\Controllers\Core\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CustomUserTableController extends Controller
{
    public function __construct()
    {
        parent::__construct(User::class, 'users');
    }

    public function index()
    {
        $this->setPage();

        // Configure custom AJAX endpoint
        $this->table->setCustomAjax([
            'url' => route('users.custom-data'),
            'method' => 'POST',
            'headers' => [
                'X-CSRF-TOKEN' => csrf_token(),
                'X-Custom-Header' => 'table-request'
            ],
            'data' => function($params) {
                // Add custom parameters
                $params['user_department'] = auth()->user()->department_id;
                $params['timestamp'] = now()->timestamp;
                return $params;
            }
        ]);

        $this->table->lists('users', [
            'name:Full Name',
            'email:Email',
            'custom_field:Custom Data'
        ], true);

        return $this->render();
    }

    public function customData(Request $request)
    {
        // Custom data processing logic
        $query = $this->buildCustomQuery($request);
        
        // Apply custom filters
        $query = $this->applyCustomFilters($query, $request);
        
        // Process and return data
        return $this->processCustomAjaxRequest($query, $request);
    }

    private function buildCustomQuery($request)
    {
        $query = User::query();
        
        // Add custom joins
        $query->leftJoin('user_profiles', 'users.id', '=', 'user_profiles.user_id')
              ->leftJoin('departments', 'users.department_id', '=', 'departments.id');
        
        // Select custom fields
        $query->select([
            'users.id',
            'users.name',
            'users.email',
            'departments.name as department_name',
            'user_profiles.bio',
            DB::raw('CONCAT(users.first_name, " ", users.last_name) as full_name'),
            DB::raw('DATEDIFF(NOW(), users.created_at) as days_since_registration')
        ]);
        
        return $query;
    }

    private function applyCustomFilters($query, $request)
    {
        // Department filter based on user permissions
        if (!auth()->user()->hasRole('admin')) {
            $query->where('users.department_id', auth()->user()->department_id);
        }
        
        // Custom date range filter
        if ($request->has('custom_date_range')) {
            $dateRange = explode(' to ', $request->input('custom_date_range'));
            if (count($dateRange) === 2) {
                $query->whereBetween('users.created_at', $dateRange);
            }
        }
        
        // Custom status filter
        if ($request->has('custom_status')) {
            $status = $request->input('custom_status');
            switch ($status) {
                case 'new':
                    $query->where('users.created_at', '>=', now()->subDays(7));
                    break;
                case 'active':
                    $query->where('users.last_login_at', '>=', now()->subDays(30));
                    break;
                case 'inactive':
                    $query->where('users.last_login_at', '<', now()->subDays(30))
                          ->orWhereNull('users.last_login_at');
                    break;
            }
        }
        
        return $query;
    }

    private function processCustomAjaxRequest($query, $request)
    {
        // Get total records
        $totalRecords = User::count();
        
        // Apply search
        if ($request->has('search.value') && !empty($request->input('search.value'))) {
            $searchValue = $request->input('search.value');
            $query->where(function($q) use ($searchValue) {
                $q->where('users.name', 'LIKE', "%{$searchValue}%")
                  ->orWhere('users.email', 'LIKE', "%{$searchValue}%")
                  ->orWhere('user_profiles.bio', 'LIKE', "%{$searchValue}%");
            });
        }
        
        // Get filtered count
        $filteredRecords = $query->count();
        
        // Apply sorting
        $this->applySorting($query, $request->input('order', []));
        
        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 25);
        $data = $query->skip($start)->take($length)->get();
        
        // Transform data
        $transformedData = $this->transformCustomData($data);
        
        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $transformedData,
            'custom_metadata' => [
                'processing_time' => microtime(true) - LARAVEL_START,
                'memory_usage' => memory_get_usage(true),
                'query_count' => DB::getQueryLog()
            ]
        ]);
    }

    private function transformCustomData($data)
    {
        return $data->map(function($item) {
            return [
                'id' => $item->id,
                'name' => $item->full_name,
                'email' => $item->email,
                'department' => $item->department_name,
                'bio' => Str::limit($item->bio, 100),
                'days_registered' => $item->days_since_registration,
                'custom_field' => $this->generateCustomField($item),
                'actions' => $this->generateCustomActions($item)
            ];
        });
    }
}
```

### Multiple AJAX Endpoints

Handle multiple AJAX endpoints for different data views:

```php
public function index()
{
    $this->setPage();

    // Configure multiple AJAX endpoints
    $this->table->setMultipleAjaxEndpoints([
        'default' => [
            'url' => route('users.ajax.default'),
            'description' => 'Standard user data'
        ],
        'detailed' => [
            'url' => route('users.ajax.detailed'),
            'description' => 'Detailed user information with relationships'
        ],
        'analytics' => [
            'url' => route('users.ajax.analytics'),
            'description' => 'User analytics and metrics'
        ],
        'export' => [
            'url' => route('users.ajax.export'),
            'description' => 'Export-optimized data format'
        ]
    ]);

    // Set default endpoint
    $this->table->setDefaultAjaxEndpoint('default');

    // Allow users to switch endpoints
    $this->table->setEndpointSwitcher([
        'enabled' => true,
        'position' => 'top-right',
        'style' => 'dropdown'
    ]);

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email',
        'created_at:Registration Date'
    ], true);

    return $this->render();
}

public function ajaxDefault(Request $request)
{
    // Standard processing
    return $this->processStandardRequest($request);
}

public function ajaxDetailed(Request $request)
{
    // Detailed processing with relationships
    $query = User::with(['department', 'roles', 'profile', 'orders']);
    return $this->processDetailedRequest($query, $request);
}

public function ajaxAnalytics(Request $request)
{
    // Analytics processing
    $query = User::withCount(['orders', 'posts', 'comments'])
               ->withSum('orders', 'total')
               ->withAvg('orders', 'total');
    
    return $this->processAnalyticsRequest($query, $request);
}
```

## AJAX Configuration

### Advanced AJAX Configuration

```php
public function index()
{
    $this->setPage();

    // Advanced AJAX configuration
    $this->table->setAdvancedAjaxConfig([
        'url' => route('users.ajax'),
        'method' => 'POST',
        'timeout' => 30000, // 30 seconds
        'cache' => false,
        'headers' => [
            'X-CSRF-TOKEN' => csrf_token(),
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json'
        ],
        'beforeSend' => 'function(xhr, settings) {
            // Show loading indicator
            $("#loading-overlay").show();
            
            // Add request timestamp
            settings.data.request_timestamp = Date.now();
            
            // Log request
            console.log("AJAX Request:", settings);
        }',
        'complete' => 'function(xhr, textStatus) {
            // Hide loading indicator
            $("#loading-overlay").hide();
            
            // Log response
            console.log("AJAX Complete:", textStatus);
        }',
        'success' => 'function(data, textStatus, xhr) {
            // Handle successful response
            if (data.warnings && data.warnings.length > 0) {
                showWarnings(data.warnings);
            }
            
            // Update metadata
            updateTableMetadata(data.custom_metadata);
        }',
        'error' => 'function(xhr, textStatus, errorThrown) {
            // Handle errors
            handleAjaxError(xhr, textStatus, errorThrown);
        }',
        'data' => 'function(params) {
            // Add custom parameters
            params.user_timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            params.screen_resolution = screen.width + "x" + screen.height;
            params.user_agent = navigator.userAgent;
            
            return params;
        }'
    ]);

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email',
        'created_at:Registration Date'
    ], true);

    return $this->render();
}
```

### Conditional AJAX Configuration

```php
public function index()
{
    $this->setPage();

    $user = auth()->user();

    // Configure AJAX based on user role
    if ($user->hasRole('admin')) {
        $this->table->setAjaxConfig([
            'url' => route('users.ajax.admin'),
            'data' => function($params) {
                $params['include_sensitive'] = true;
                $params['include_deleted'] = true;
                return $params;
            }
        ]);
    } elseif ($user->hasRole('manager')) {
        $this->table->setAjaxConfig([
            'url' => route('users.ajax.manager'),
            'data' => function($params) use ($user) {
                $params['department_id'] = $user->department_id;
                $params['include_subordinates'] = true;
                return $params;
            }
        ]);
    } else {
        $this->table->setAjaxConfig([
            'url' => route('users.ajax.basic'),
            'data' => function($params) use ($user) {
                $params['user_id'] = $user->id;
                $params['limited_view'] = true;
                return $params;
            }
        ]);
    }

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email',
        'created_at:Registration Date'
    ], true);

    return $this->render();
}
```

## Real-time Updates

### WebSocket Integration

```php
public function index()
{
    $this->setPage();

    // Configure WebSocket for real-time updates
    $this->table->setWebSocketConfig([
        'enabled' => true,
        'url' => env('WEBSOCKET_URL', 'ws://localhost:6001'),
        'channel' => 'users-table-' . auth()->id(),
        'events' => [
            'user.created' => 'addRow',
            'user.updated' => 'updateRow',
            'user.deleted' => 'removeRow',
            'user.restored' => 'addRow'
        ],
        'reconnect' => [
            'enabled' => true,
            'attempts' => 5,
            'delay' => 1000
        ],
        'heartbeat' => [
            'enabled' => true,
            'interval' => 30000
        ]
    ]);

    // Fallback to polling if WebSocket fails
    $this->table->setPollingFallback([
        'enabled' => true,
        'interval' => 60000, // 1 minute
        'endpoint' => route('users.ajax.updates')
    ]);

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email',
        'created_at:Registration Date'
    ], true);

    return $this->render();
}

// WebSocket event broadcasting
public function broadcastUserUpdate($user, $event)
{
    $channel = 'users-table-' . $user->department_id;
    
    broadcast(new UserTableUpdateEvent($channel, [
        'event' => $event,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at->toISOString()
        ],
        'timestamp' => now()->toISOString()
    ]));
}

// Polling endpoint for updates
public function ajaxUpdates(Request $request)
{
    $lastUpdate = $request->input('last_update');
    $lastUpdateTime = $lastUpdate ? Carbon::parse($lastUpdate) : now()->subMinutes(5);
    
    $updates = User::where('updated_at', '>', $lastUpdateTime)
                   ->with(['department:id,name'])
                   ->get();
    
    return response()->json([
        'updates' => $updates->map(function($user) {
            return [
                'id' => $user->id,
                'action' => 'update',
                'data' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'department' => $user->department->name ?? 'N/A',
                    'created_at' => $user->created_at->format('Y-m-d H:i:s')
                ]
            ];
        }),
        'last_update' => now()->toISOString()
    ]);
}
```

### Server-Sent Events (SSE)

```php
public function index()
{
    $this->setPage();

    // Configure Server-Sent Events
    $this->table->setSSEConfig([
        'enabled' => true,
        'endpoint' => route('users.sse'),
        'retry_interval' => 5000,
        'events' => [
            'table-update' => 'handleTableUpdate',
            'user-online' => 'handleUserOnline',
            'user-offline' => 'handleUserOffline'
        ]
    ]);

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email',
        'status:Online Status',
        'created_at:Registration Date'
    ], true);

    return $this->render();
}

public function sseStream(Request $request)
{
    $response = new StreamedResponse(function() {
        // Set SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        
        // Send initial connection event
        echo "event: connected\n";
        echo "data: " . json_encode(['message' => 'Connected to user table updates']) . "\n\n";
        ob_flush();
        flush();
        
        // Listen for updates
        while (true) {
            // Check for updates
            $updates = $this->checkForUpdates();
            
            if (!empty($updates)) {
                foreach ($updates as $update) {
                    echo "event: table-update\n";
                    echo "data: " . json_encode($update) . "\n\n";
                    ob_flush();
                    flush();
                }
            }
            
            // Sleep for 1 second
            sleep(1);
            
            // Check if client disconnected
            if (connection_aborted()) {
                break;
            }
        }
    });

    return $response;
}
```

## Custom Data Processing

### Advanced Data Transformation

```php
public function ajaxData(Request $request)
{
    $query = $this->getBaseQuery();
    
    // Apply filters
    $query = $this->applyFilters($query, $request);
    
    // Get data
    $data = $query->get();
    
    // Apply advanced transformations
    $transformedData = $this->applyAdvancedTransformations($data, $request);
    
    return response()->json([
        'draw' => intval($request->input('draw')),
        'recordsTotal' => $this->getTotalRecords(),
        'recordsFiltered' => $this->getFilteredRecords($query),
        'data' => $transformedData
    ]);
}

private function applyAdvancedTransformations($data, $request)
{
    $user = auth()->user();
    $timezone = $request->input('user_timezone', 'UTC');
    
    return $data->map(function($item) use ($user, $timezone) {
        // Base transformation
        $transformed = [
            'id' => $item->id,
            'name' => $item->name,
            'email' => $item->email
        ];
        
        // Conditional fields based on permissions
        if ($user->can('view-sensitive-data')) {
            $transformed['phone'] = $item->phone;
            $transformed['address'] = $item->address;
        }
        
        // Timezone-aware dates
        $transformed['created_at'] = $item->created_at
            ->setTimezone($timezone)
            ->format('Y-m-d H:i:s T');
        
        // Computed fields
        $transformed['account_age_days'] = $item->created_at->diffInDays(now());
        $transformed['is_new_user'] = $item->created_at->isAfter(now()->subDays(7));
        
        // Dynamic status
        $transformed['online_status'] = $this->getUserOnlineStatus($item);
        
        // Localized data
        $transformed['status_text'] = __('user.status.' . $item->status);
        
        // Custom formatting
        $transformed['formatted_name'] = $this->formatUserName($item, $user);
        
        // Actions based on permissions
        $transformed['actions'] = $this->generateContextualActions($item, $user);
        
        return $transformed;
    });
}

private function getUserOnlineStatus($user)
{
    $lastActivity = Cache::get("user_last_activity_{$user->id}");
    
    if (!$lastActivity) {
        return 'offline';
    }
    
    $lastActivityTime = Carbon::parse($lastActivity);
    
    if ($lastActivityTime->isAfter(now()->subMinutes(5))) {
        return 'online';
    } elseif ($lastActivityTime->isAfter(now()->subMinutes(30))) {
        return 'away';
    } else {
        return 'offline';
    }
}

private function formatUserName($user, $currentUser)
{
    $name = $user->name;
    
    // Add indicators
    if ($user->id === $currentUser->id) {
        $name .= ' (You)';
    }
    
    if ($user->hasRole('admin')) {
        $name = '👑 ' . $name;
    }
    
    if ($user->is_verified) {
        $name .= ' ✓';
    }
    
    return $name;
}
```

### Aggregated Data Processing

```php
public function ajaxAggregatedData(Request $request)
{
    // Get aggregated data
    $aggregatedData = $this->getAggregatedData($request);
    
    // Process for display
    $processedData = $this->processAggregatedData($aggregatedData, $request);
    
    return response()->json([
        'draw' => intval($request->input('draw')),
        'data' => $processedData,
        'aggregations' => $this->getAggregationSummary($aggregatedData)
    ]);
}

private function getAggregatedData($request)
{
    $query = User::query()
        ->select([
            'department_id',
            'departments.name as department_name',
            DB::raw('COUNT(*) as user_count'),
            DB::raw('COUNT(CASE WHEN active = 1 THEN 1 END) as active_count'),
            DB::raw('COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_users'),
            DB::raw('AVG(DATEDIFF(NOW(), created_at)) as avg_account_age'),
            DB::raw('MAX(last_login_at) as last_activity')
        ])
        ->join('departments', 'users.department_id', '=', 'departments.id')
        ->groupBy('department_id', 'departments.name');
    
    // Apply date filters
    if ($request->has('date_range')) {
        $dateRange = explode(' to ', $request->input('date_range'));
        if (count($dateRange) === 2) {
            $query->whereBetween('users.created_at', $dateRange);
        }
    }
    
    return $query->get();
}

private function processAggregatedData($data, $request)
{
    return $data->map(function($item) {
        return [
            'department_id' => $item->department_id,
            'department_name' => $item->department_name,
            'user_count' => $item->user_count,
            'active_count' => $item->active_count,
            'inactive_count' => $item->user_count - $item->active_count,
            'new_users' => $item->new_users,
            'avg_account_age' => round($item->avg_account_age),
            'last_activity' => $item->last_activity ? 
                Carbon::parse($item->last_activity)->diffForHumans() : 'Never',
            'activity_score' => $this->calculateActivityScore($item),
            'growth_rate' => $this->calculateGrowthRate($item)
        ];
    });
}
```

## Error Handling

### Comprehensive AJAX Error Handling

```php
public function ajaxData(Request $request)
{
    try {
        // Set error context
        $this->setErrorContext($request);
        
        // Validate request
        $this->validateAjaxRequest($request);
        
        // Process request with timeout
        $result = $this->processWithTimeout($request, 30);
        
        return response()->json($result);
        
    } catch (ValidationException $e) {
        return $this->handleValidationError($e, $request);
    } catch (TimeoutException $e) {
        return $this->handleTimeoutError($e, $request);
    } catch (QueryException $e) {
        return $this->handleDatabaseError($e, $request);
    } catch (\Exception $e) {
        return $this->handleGeneralError($e, $request);
    }
}

private function setErrorContext($request)
{
    Log::withContext([
        'user_id' => auth()->id(),
        'ip_address' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'request_id' => Str::uuid(),
        'timestamp' => now()->toISOString()
    ]);
}

private function handleValidationError(ValidationException $e, $request)
{
    Log::warning('AJAX validation error', [
        'errors' => $e->errors(),
        'request_data' => $request->all()
    ]);
    
    return response()->json([
        'error' => 'validation_failed',
        'message' => 'Invalid request parameters',
        'details' => $e->errors(),
        'retry_possible' => true
    ], 422);
}

private function handleTimeoutError(TimeoutException $e, $request)
{
    Log::error('AJAX timeout error', [
        'timeout_duration' => $e->getTimeout(),
        'request_data' => $request->only(['start', 'length', 'search'])
    ]);
    
    return response()->json([
        'error' => 'request_timeout',
        'message' => 'Request took too long to process',
        'suggestions' => [
            'Try reducing the number of records per page',
            'Simplify your search criteria',
            'Remove complex filters'
        ],
        'retry_possible' => true
    ], 408);
}

private function handleDatabaseError(QueryException $e, $request)
{
    Log::error('AJAX database error', [
        'sql_error' => $e->getMessage(),
        'sql_code' => $e->getCode(),
        'bindings' => $e->getBindings()
    ]);
    
    return response()->json([
        'error' => 'database_error',
        'message' => 'Database error occurred',
        'retry_possible' => true,
        'retry_delay' => 5000 // 5 seconds
    ], 500);
}

private function handleGeneralError(\Exception $e, $request)
{
    Log::error('AJAX general error', [
        'error_message' => $e->getMessage(),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine(),
        'stack_trace' => $e->getTraceAsString()
    ]);
    
    return response()->json([
        'error' => 'internal_error',
        'message' => 'An unexpected error occurred',
        'error_id' => Str::uuid(), // For support reference
        'retry_possible' => false
    ], 500);
}
```

### Client-Side Error Recovery

```javascript
// Advanced client-side error handling
function setupAdvancedErrorHandling() {
    let retryCount = 0;
    const maxRetries = 3;
    
    $('#users-table').DataTable({
        ajax: {
            url: '/users/ajax',
            type: 'POST',
            error: function(xhr, error, thrown) {
                const response = xhr.responseJSON;
                
                if (response && response.retry_possible && retryCount < maxRetries) {
                    retryCount++;
                    
                    // Show retry message
                    showRetryMessage(response.message, retryCount, maxRetries);
                    
                    // Retry after delay
                    const delay = response.retry_delay || 2000;
                    setTimeout(() => {
                        $('#users-table').DataTable().ajax.reload();
                    }, delay);
                    
                } else {
                    // Show permanent error
                    showPermanentError(response);
                    retryCount = 0;
                }
            },
            success: function(data) {
                // Reset retry count on success
                retryCount = 0;
                hideErrorMessages();
            }
        }
    });
}

function showRetryMessage(message, attempt, maxAttempts) {
    const retryMessage = `${message} (Attempt ${attempt}/${maxAttempts})`;
    $('#error-container').html(`
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            ${retryMessage}
            <div class="progress mt-2">
                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                     style="width: ${(attempt/maxAttempts)*100}%"></div>
            </div>
        </div>
    `).show();
}

function showPermanentError(response) {
    const errorHtml = `
        <div class="alert alert-danger">
            <h5><i class="fas fa-times-circle"></i> Error</h5>
            <p>${response.message}</p>
            ${response.error_id ? `<small>Error ID: ${response.error_id}</small>` : ''}
            ${response.suggestions ? `
                <hr>
                <strong>Suggestions:</strong>
                <ul>
                    ${response.suggestions.map(s => `<li>${s}</li>`).join('')}
                </ul>
            ` : ''}
            <hr>
            <button class="btn btn-primary btn-sm" onclick="location.reload()">
                <i class="fas fa-refresh"></i> Refresh Page
            </button>
        </div>
    `;
    
    $('#error-container').html(errorHtml).show();
}
```

## Performance Optimization

### Request Optimization

```php
public function ajaxData(Request $request)
{
    // Implement request caching
    $cacheKey = $this->generateCacheKey($request);
    
    return Cache::remember($cacheKey, 300, function() use ($request) {
        return $this->processAjaxRequest($request);
    });
}

private function generateCacheKey($request)
{
    $keyData = [
        'user_id' => auth()->id(),
        'user_roles' => auth()->user()->roles->pluck('name')->sort()->values(),
        'request_params' => $request->only([
            'start', 'length', 'search', 'order', 'filters'
        ])
    ];
    
    return 'ajax_table_' . md5(serialize($keyData));
}

// Cache invalidation
public function invalidateTableCache($userId = null)
{
    $pattern = $userId ? "ajax_table_*{$userId}*" : "ajax_table_*";
    
    $keys = Cache::getRedis()->keys($pattern);
    
    if (!empty($keys)) {
        Cache::getRedis()->del($keys);
    }
}
```

### Response Optimization

```php
private function optimizeResponse($data, $request)
{
    // Minimize response size
    $optimizedData = $data->map(function($item) use ($request) {
        $result = [
            'id' => $item->id,
            'name' => $item->name,
            'email' => $item->email
        ];
        
        // Only include fields that are actually displayed
        $visibleColumns = $request->input('visible_columns', []);
        
        if (in_array('department', $visibleColumns)) {
            $result['department'] = $item->department_name;
        }
        
        if (in_array('created_at', $visibleColumns)) {
            $result['created_at'] = $item->created_at->format('Y-m-d');
        }
        
        return $result;
    });
    
    // Compress response if supported
    if ($this->supportsCompression($request)) {
        return response()->json($optimizedData)
                         ->header('Content-Encoding', 'gzip');
    }
    
    return response()->json($optimizedData);
}

private function supportsCompression($request)
{
    $acceptEncoding = $request->header('Accept-Encoding', '');
    return str_contains($acceptEncoding, 'gzip');
}
```

## Security Considerations

### Request Security

```php
public function ajaxData(Request $request)
{
    // Verify CSRF token
    if (!$this->verifyCsrfToken($request)) {
        return response()->json(['error' => 'Invalid CSRF token'], 419);
    }
    
    // Rate limiting
    if ($this->isRateLimited($request)) {
        return response()->json(['error' => 'Too many requests'], 429);
    }
    
    // IP whitelist check
    if (!$this->isIpAllowed($request)) {
        return response()->json(['error' => 'Access denied'], 403);
    }
    
    // Continue with processing...
}

private function verifyCsrfToken($request)
{
    $token = $request->header('X-CSRF-TOKEN') ?: $request->input('_token');
    return hash_equals(session()->token(), $token);
}

private function isRateLimited($request)
{
    $key = 'ajax_rate_limit_' . auth()->id() . '_' . $request->ip();
    $attempts = Cache::get($key, 0);
    
    if ($attempts >= 100) { // 100 requests per minute
        return true;
    }
    
    Cache::put($key, $attempts + 1, 60);
    return false;
}

private function isIpAllowed($request)
{
    $allowedIps = config('canvastack.allowed_ips', []);
    
    if (empty($allowedIps)) {
        return true; // No IP restrictions
    }
    
    return in_array($request->ip(), $allowedIps);
}
```

## Advanced Examples

### Multi-Step Data Loading

```php
public function ajaxMultiStep(Request $request)
{
    $step = $request->input('step', 1);
    
    switch ($step) {
        case 1:
            return $this->loadBasicData($request);
        case 2:
            return $this->loadRelationshipData($request);
        case 3:
            return $this->loadAggregatedData($request);
        default:
            return response()->json(['error' => 'Invalid step'], 400);
    }
}

private function loadBasicData($request)
{
    $users = User::select(['id', 'name', 'email'])
                 ->limit(100)
                 ->get();
    
    return response()->json([
        'step' => 1,
        'data' => $users,
        'next_step' => 2,
        'progress' => 33
    ]);
}

private function loadRelationshipData($request)
{
    $userIds = $request->input('user_ids', []);
    
    $relationships = User::whereIn('id', $userIds)
                        ->with(['department:id,name', 'roles:id,name'])
                        ->get(['id', 'department_id']);
    
    return response()->json([
        'step' => 2,
        'data' => $relationships,
        'next_step' => 3,
        'progress' => 66
    ]);
}

private function loadAggregatedData($request)
{
    $userIds = $request->input('user_ids', []);
    
    $aggregated = User::whereIn('id', $userIds)
                     ->withCount(['orders', 'posts'])
                     ->get(['id', 'orders_count', 'posts_count']);
    
    return response()->json([
        'step' => 3,
        'data' => $aggregated,
        'next_step' => null,
        'progress' => 100,
        'complete' => true
    ]);
}
```

---

## Related Documentation

- [GET Method](get.md) - Client-side processing
- [POST Method](post.md) - Standard server-side processing
- [Performance Optimization](../advanced/performance.md) - AJAX performance tuning
- [Security Features](../advanced/security.md) - AJAX security best practices