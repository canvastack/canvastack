# Security Best Practices: Multi-Table & Tab System

This guide covers essential security practices for implementing the TanStack Table Multi-Table & Tab System in CanvaStack applications.

## 📦 Overview

Security is critical when implementing multi-table systems with AJAX-based lazy loading. This guide covers connection security, CSRF protection, input validation, rate limiting, and other security measures to protect your application.

## 🔒 Connection Security

### Principle: Never Expose Database Connection Details

Database connection information must never be exposed to the client-side code or HTML output.

#### ✅ Secure Implementation

```php
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Connection is handled internally
    // Never exposed to browser
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

#### ❌ Insecure Implementation

```php
// DON'T DO THIS
public function index(): View
{
    $connection = config('database.connections.mysql');
    
    return view('users.index', [
        'connection' => $connection, // NEVER expose connection details
    ]);
}
```

### Connection Override Warnings

Enable connection override warnings to catch potential configuration errors:

```env
# .env
CANVASTACK_CONNECTION_WARNING=true
CANVASTACK_CONNECTION_WARNING_METHOD=log
```


**Configuration**:

```php
// config/canvastack.php
'table' => [
    'connection_warning' => [
        'enabled' => env('CANVASTACK_CONNECTION_WARNING', true),
        'method' => env('CANVASTACK_CONNECTION_WARNING_METHOD', 'log'),
        // Options: 'log', 'toast', 'both'
    ],
],
```

**Why This Matters**:
- Detects accidental connection mismatches
- Prevents data leakage between databases
- Helps catch configuration errors early
- Logs suspicious connection overrides

### Secure Connection Detection

The system automatically detects connections from Eloquent models:

```php
// Automatic detection (SECURE)
$table->setModel(new User()); // Connection auto-detected

// Manual override (triggers warning if different)
$table->setModel(new User());
$table->connection('secondary_db'); // Warning logged if mismatch
```

**Priority Order**:
1. Manual override (via `connection()` method)
2. Model connection (via `getConnectionName()`)
3. Config default (via `config('database.default')`)

---

## 🛡️ CSRF Protection

### Principle: All AJAX Requests Must Include CSRF Token

Cross-Site Request Forgery (CSRF) protection is mandatory for all AJAX requests.

#### ✅ Secure Implementation

The system automatically includes CSRF tokens in all AJAX requests:

```javascript
// Alpine.js component (built-in)
loadTab(index) {
    this.loading = true;
    
    fetch(this.getTabUrl(index), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ tab_index: index })
    })
    .then(response => response.json())
    .then(data => {
        this.tabContent[index] = data.html;
        this.tabsLoaded.push(index);
        this.loading = false;
    });
}
```


#### ❌ Insecure Implementation

```javascript
// DON'T DO THIS - Missing CSRF token
fetch('/api/table/tab/1', {
    method: 'POST',
    // Missing CSRF token - vulnerable to CSRF attacks
})
```

### CSRF Token in Blade Templates

Always include CSRF token meta tag in your layout:

```blade
<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @themeInject
</head>
<body>
    @yield('content')
</body>
</html>
```

### Server-Side CSRF Validation

The controller automatically validates CSRF tokens:

```php
use Illuminate\Http\Request;

public function loadTab(Request $request, int $index)
{
    // Laravel automatically validates CSRF token
    // via VerifyCsrfToken middleware
    
    // Additional validation
    $request->validate([
        'tab_index' => 'required|integer|min:0',
    ]);
    
    // Process request...
}
```

**Middleware Configuration**:

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\VerifyCsrfToken::class,
        // Other middleware...
    ],
];
```

---

## ✅ Input Validation

### Principle: Validate All User Inputs

Never trust user input. Always validate and sanitize.

#### Tab Index Validation

```php
public function loadTab(Request $request, int $index)
{
    // Validate tab index
    $validated = $request->validate([
        'tab_index' => 'required|integer|min:0|max:50',
    ]);
    
    $tabIndex = $validated['tab_index'];
    
    // Verify tab exists
    $tabConfig = session("table_tabs.{$tabIndex}");
    
    if (!$tabConfig) {
        abort(404, __('errors.tab_not_found'));
    }
    
    // Process request...
}
```


#### Column Name Validation

```php
// TableBuilder automatically validates column names
protected function validateColumnName(string $column): void
{
    // Only allow alphanumeric, underscore, dot
    if (!preg_match('/^[a-zA-Z0-9_.]+$/', $column)) {
        throw new InvalidArgumentException(
            "Invalid column name: {$column}. Only alphanumeric, underscore, and dot allowed."
        );
    }
}
```

#### Filter Value Validation

```php
// Validate filter values before using in queries
protected function validateFilterValue($value, string $type): mixed
{
    return match($type) {
        'integer' => filter_var($value, FILTER_VALIDATE_INT),
        'email' => filter_var($value, FILTER_VALIDATE_EMAIL),
        'url' => filter_var($value, FILTER_VALIDATE_URL),
        'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        default => htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
    };
}
```

### SQL Injection Prevention

Always use parameterized queries:

```php
// ✅ SECURE - Parameterized query
$query->where('name', 'LIKE', '%' . $search . '%');

// ❌ INSECURE - Raw SQL
$query->whereRaw("name LIKE '%{$search}%'"); // SQL injection vulnerability
```

### XSS Prevention

Always escape output:

```php
// ✅ SECURE - Escaped output
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// In Blade (automatic escaping)
{{ $userInput }}

// ❌ INSECURE - Unescaped output
{!! $userInput !!} // Only use for trusted content
```

---

## 🚦 Rate Limiting

### Principle: Prevent Abuse with Rate Limiting

Rate limiting prevents abuse of AJAX endpoints and protects server resources.

### Route Configuration

```php
// routes/api.php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::post('/canvastack/table/tab/{index}', [TableTabController::class, 'loadTab'])
        ->name('canvastack.table.tab.load');
});
```

**Rate Limit**: 60 requests per minute per user


### Custom Rate Limiting

For more granular control:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

// In AppServiceProvider::boot()
RateLimiter::for('table-tabs', function (Request $request) {
    return $request->user()
        ? Limit::perMinute(60)->by($request->user()->id)
        : Limit::perMinute(10)->by($request->ip());
});
```

**Apply to Route**:

```php
Route::middleware(['auth', 'throttle:table-tabs'])->group(function () {
    Route::post('/canvastack/table/tab/{index}', [TableTabController::class, 'loadTab']);
});
```

### Rate Limit Response

When rate limit is exceeded:

```json
{
    "message": "Too Many Attempts.",
    "retry_after": 60
}
```

**Client-Side Handling**:

```javascript
loadTab(index) {
    fetch(url, options)
        .then(response => {
            if (response.status === 429) {
                const retryAfter = response.headers.get('Retry-After');
                this.error = `Too many requests. Please wait ${retryAfter} seconds.`;
                return;
            }
            return response.json();
        });
}
```

---

## 🔐 Authorization & Permissions

### Principle: Validate User Permissions

Always check user permissions before returning sensitive data.

### Controller-Level Authorization

```php
public function loadTab(Request $request, int $index)
{
    // Validate CSRF token (automatic via middleware)
    
    // Validate tab index
    $validated = $request->validate([
        'tab_index' => 'required|integer|min:0',
    ]);
    
    // Get tab configuration
    $tabConfig = session("table_tabs.{$index}");
    
    if (!$tabConfig) {
        abort(404, __('errors.tab_not_found'));
    }
    
    // CRITICAL: Check user permissions
    $modelClass = $tabConfig['model'] ?? null;
    
    if ($modelClass && !$this->canViewModel($modelClass)) {
        abort(403, __('errors.unauthorized'));
    }
    
    // Render tab content...
}

protected function canViewModel(string $modelClass): bool
{
    // Check if user has permission to view this model
    return auth()->user()->can('view', $modelClass);
}
```


### Row-Level Authorization

For row-level permissions:

```php
public function loadTab(Request $request, int $index)
{
    // ... validation ...
    
    // Get query builder
    $query = $this->buildQuery($tabConfig);
    
    // Apply row-level authorization
    $query = $this->applyRowLevelSecurity($query, $tabConfig['model']);
    
    // Execute query
    $data = $query->get();
    
    // Render...
}

protected function applyRowLevelSecurity($query, string $modelClass)
{
    $user = auth()->user();
    
    // Example: Users can only see their own data
    if (!$user->hasRole('admin')) {
        $query->where('user_id', $user->id);
    }
    
    // Example: Filter by organization
    if ($user->organization_id) {
        $query->where('organization_id', $user->organization_id);
    }
    
    return $query;
}
```

### Policy-Based Authorization

Use Laravel policies for consistent authorization:

```php
// app/Policies/UserPolicy.php
public function viewAny(User $user): bool
{
    return $user->hasPermission('users.view');
}

public function view(User $user, User $model): bool
{
    return $user->hasPermission('users.view') 
        && ($user->id === $model->id || $user->hasRole('admin'));
}
```

**In Controller**:

```php
public function loadTab(Request $request, int $index)
{
    $modelClass = $tabConfig['model'] ?? null;
    
    // Check policy
    $this->authorize('viewAny', $modelClass);
    
    // Process request...
}
```

---

## 🔍 Input Validation

### Principle: Validate Everything

All user inputs must be validated before processing.

### Request Validation

```php
public function loadTab(Request $request, int $index)
{
    // Validate all inputs
    $validated = $request->validate([
        'tab_index' => 'required|integer|min:0|max:50',
        'filters' => 'sometimes|array',
        'filters.*.column' => 'required|string|max:100',
        'filters.*.operator' => 'required|in:=,!=,>,<,>=,<=,LIKE',
        'filters.*.value' => 'required|string|max:255',
        'sort_by' => 'sometimes|string|max:100',
        'sort_direction' => 'sometimes|in:asc,desc',
        'page' => 'sometimes|integer|min:1',
        'per_page' => 'sometimes|integer|min:1|max:100',
    ]);
    
    // Use validated data only
    $tabIndex = $validated['tab_index'];
    $filters = $validated['filters'] ?? [];
    
    // Process...
}
```


### Column Name Whitelist

Only allow validated column names:

```php
protected function validateColumn(string $column, array $allowedColumns): void
{
    // Check against whitelist
    if (!in_array($column, $allowedColumns, true)) {
        throw new InvalidArgumentException(
            "Invalid column: {$column}. Allowed columns: " . implode(', ', $allowedColumns)
        );
    }
    
    // Additional validation
    if (!preg_match('/^[a-zA-Z0-9_.]+$/', $column)) {
        throw new InvalidArgumentException(
            "Invalid column format: {$column}"
        );
    }
}
```

### Filter Operator Whitelist

Only allow safe operators:

```php
protected const ALLOWED_OPERATORS = [
    '=', '!=', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE',
    'IN', 'NOT IN', 'BETWEEN', 'IS NULL', 'IS NOT NULL'
];

protected function validateOperator(string $operator): void
{
    if (!in_array($operator, self::ALLOWED_OPERATORS, true)) {
        throw new InvalidArgumentException(
            "Invalid operator: {$operator}"
        );
    }
}
```

### Sanitize User Input

```php
protected function sanitizeInput(string $input): string
{
    // Remove HTML tags
    $input = strip_tags($input);
    
    // Escape special characters
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    
    // Trim whitespace
    $input = trim($input);
    
    return $input;
}
```

---

## 🔑 Unique ID Security

### Principle: IDs Must Be Non-Predictable

Table unique IDs must not expose sensitive information or be predictable.

### Secure ID Generation

The HashGenerator uses SHA256 with cryptographically secure random bytes:

```php
public function generate(
    string $tableName,
    string $connectionName,
    array $fields
): string {
    $instanceNumber = $this->getNextInstanceNumber();
    
    $inputs = [
        $tableName,
        $connectionName,
        $instanceNumber,
        serialize($fields),
        microtime(true),
        bin2hex(random_bytes(16)), // Cryptographically secure
    ];
    
    $hash = hash('sha256', implode('|', $inputs));
    
    return 'canvastable_' . substr($hash, 0, 16);
}
```


**Security Features**:
- Uses `random_bytes()` for cryptographic security
- Includes microtime for temporal uniqueness
- Includes instance counter for sequential uniqueness
- SHA256 prevents reverse engineering
- No predictable patterns in output
- Does not expose table names or structure

#### ❌ Insecure ID Generation

```php
// DON'T DO THIS
public function generate(string $tableName): string
{
    static $counter = 0;
    $counter++;
    
    // Predictable pattern - security vulnerability
    return "table_{$tableName}_{$counter}";
}
```

**Why This Is Insecure**:
- Exposes table name to client
- Predictable sequence
- Easy to enumerate other tables
- No collision resistance

### Information Hiding

Never expose internal structure:

```php
// ✅ SECURE - Opaque ID
<div id="canvastable_a1b2c3d4e5f6g7h8">
    <!-- Table content -->
</div>

// ❌ INSECURE - Exposes structure
<div id="users_table_mysql_1">
    <!-- Reveals table name, connection, instance -->
</div>
```

---

## 🚨 Error Handling

### Principle: Never Expose Sensitive Information in Errors

Error messages must be user-friendly without revealing system details.

### Production Error Messages

```php
public function loadTab(Request $request, int $index)
{
    try {
        // Process request...
        
    } catch (\Exception $e) {
        // Log detailed error for debugging
        Log::error('Tab loading failed', [
            'tab_index' => $index,
            'user_id' => auth()->id(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        // Return generic error to user
        if (app()->environment('production')) {
            return response()->json([
                'success' => false,
                'message' => __('errors.generic_error'),
            ], 500);
        }
        
        // In development, show detailed error
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
}
```


### Client-Side Error Handling

```javascript
loadTab(index) {
    fetch(url, options)
        .then(response => {
            if (!response.ok) {
                // Handle HTTP errors
                if (response.status === 403) {
                    this.error = 'You do not have permission to view this content.';
                } else if (response.status === 404) {
                    this.error = 'Content not found.';
                } else if (response.status === 429) {
                    this.error = 'Too many requests. Please wait and try again.';
                } else {
                    this.error = 'An error occurred. Please try again.';
                }
                return;
            }
            return response.json();
        })
        .catch(error => {
            // Network errors
            this.error = 'Network error. Please check your connection.';
            console.error('Tab loading error:', error);
        });
}
```

---

## 🔒 Session Security

### Principle: Secure Session Management

Tab configurations stored in session must be secured.

### Session Configuration

```php
// config/session.php
return [
    'driver' => env('SESSION_DRIVER', 'redis'),
    'lifetime' => 120, // 2 hours
    'expire_on_close' => false,
    'encrypt' => true, // Encrypt session data
    'secure' => env('SESSION_SECURE_COOKIE', true), // HTTPS only
    'http_only' => true, // Prevent JavaScript access
    'same_site' => 'lax', // CSRF protection
];
```

### Storing Tab Configuration

```php
public function renderWithTabs(): string
{
    $tabs = $this->tabManager->getTabs();
    
    // Store tab config in encrypted session
    foreach ($tabs as $index => $tab) {
        session()->put("table_tabs.{$this->uniqueId}.{$index}", [
            'name' => $tab['name'],
            'model' => $tab['model'] ?? null,
            'fields' => $tab['fields'] ?? [],
            // Don't store sensitive data
        ]);
    }
    
    // Render...
}
```

### Retrieving Tab Configuration

```php
public function loadTab(Request $request, int $index)
{
    // Get from session
    $tabConfig = session("table_tabs.{$request->input('table_id')}.{$index}");
    
    if (!$tabConfig) {
        abort(404, __('errors.tab_not_found'));
    }
    
    // Verify ownership
    if (!$this->userOwnsSession($request)) {
        abort(403, __('errors.unauthorized'));
    }
    
    // Process...
}
```


---

## 🛡️ XSS Prevention

### Principle: Escape All Output

Prevent Cross-Site Scripting by escaping all user-generated content.

### Blade Template Escaping

```blade
{{-- ✅ SECURE - Automatic escaping --}}
<div>{{ $userInput }}</div>
<div>{{ $user->name }}</div>

{{-- ❌ INSECURE - No escaping --}}
<div>{!! $userInput !!}</div>

{{-- ✅ SECURE - Only for trusted content --}}
<div>{!! $trustedHtml !!}</div>
```

### PHP Escaping

```php
// ✅ SECURE
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// ✅ SECURE - Laravel helper
echo e($userInput);

// ❌ INSECURE
echo $userInput;
```

### JavaScript Escaping

```javascript
// ✅ SECURE - Use textContent
element.textContent = userInput;

// ❌ INSECURE - innerHTML with user input
element.innerHTML = userInput; // XSS vulnerability
```

### Content Security Policy (CSP)

Add CSP headers to prevent XSS:

```php
// app/Http/Middleware/SecurityHeaders.php
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    $response->headers->set('Content-Security-Policy', 
        "default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; " .
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
        "font-src 'self' https://fonts.gstatic.com; " .
        "img-src 'self' data: https:;"
    );
    
    return $response;
}
```

---

## 🔐 Authentication

### Principle: Require Authentication for Sensitive Endpoints

All tab loading endpoints must require authentication.

### Middleware Configuration

```php
// routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/canvastack/table/tab/{index}', [TableTabController::class, 'loadTab'])
        ->name('canvastack.table.tab.load');
});
```

### Controller Authentication Check

```php
public function loadTab(Request $request, int $index)
{
    // Verify user is authenticated
    if (!auth()->check()) {
        abort(401, __('errors.unauthenticated'));
    }
    
    // Verify user has active session
    if (!$request->session()->has('_token')) {
        abort(401, __('errors.invalid_session'));
    }
    
    // Process request...
}
```


### Session Timeout

Configure appropriate session timeout:

```php
// config/session.php
'lifetime' => env('SESSION_LIFETIME', 120), // 2 hours

// In controller
public function loadTab(Request $request, int $index)
{
    // Check session age
    $lastActivity = session('last_activity');
    
    if ($lastActivity && (time() - $lastActivity) > 7200) {
        session()->flush();
        abort(401, __('errors.session_expired'));
    }
    
    session(['last_activity' => time()]);
    
    // Process...
}
```

---

## 🚫 Information Disclosure Prevention

### Principle: Minimize Information Exposure

Never expose internal system details to users.

### What NOT to Expose

❌ **Database connection details**:
```php
// DON'T DO THIS
return response()->json([
    'connection' => config('database.connections.mysql'),
]);
```

❌ **Table structure**:
```php
// DON'T DO THIS
return response()->json([
    'columns' => Schema::getColumnListing('users'),
]);
```

❌ **Internal paths**:
```php
// DON'T DO THIS
return response()->json([
    'error' => 'File not found: /var/www/app/storage/file.txt',
]);
```

❌ **Stack traces in production**:
```php
// DON'T DO THIS in production
return response()->json([
    'error' => $exception->getMessage(),
    'trace' => $exception->getTraceAsString(),
]);
```

### What TO Expose

✅ **Generic error messages**:
```php
return response()->json([
    'success' => false,
    'message' => __('errors.generic_error'),
]);
```

✅ **User-friendly validation errors**:
```php
return response()->json([
    'success' => false,
    'errors' => [
        'email' => __('validation.email'),
    ],
]);
```

✅ **Safe metadata**:
```php
return response()->json([
    'success' => true,
    'data' => $data,
    'meta' => [
        'current_page' => $page,
        'total' => $total,
    ],
]);
```


---

## 🔒 HTTPS Enforcement

### Principle: Always Use HTTPS in Production

All production traffic must use HTTPS.

### Force HTTPS

```php
// app/Providers/AppServiceProvider.php
public function boot()
{
    if (app()->environment('production')) {
        URL::forceScheme('https');
    }
}
```

### Middleware

```php
// app/Http/Middleware/ForceHttps.php
public function handle($request, Closure $next)
{
    if (!$request->secure() && app()->environment('production')) {
        return redirect()->secure($request->getRequestUri());
    }
    
    return $next($request);
}
```

### Security Headers

```php
// app/Http/Middleware/SecurityHeaders.php
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
    $response->headers->set('X-XSS-Protection', '1; mode=block');
    $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
    
    return $response;
}
```

---

## 📊 Logging & Monitoring

### Principle: Log Security Events

Log all security-relevant events for auditing and monitoring.

### What to Log

```php
use Illuminate\Support\Facades\Log;

public function loadTab(Request $request, int $index)
{
    // Log access attempt
    Log::info('Tab loading requested', [
        'user_id' => auth()->id(),
        'tab_index' => $index,
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
    ]);
    
    // Validate permissions
    if (!$this->canViewModel($modelClass)) {
        // Log unauthorized access attempt
        Log::warning('Unauthorized tab access attempt', [
            'user_id' => auth()->id(),
            'tab_index' => $index,
            'model' => $modelClass,
            'ip' => $request->ip(),
        ]);
        
        abort(403, __('errors.unauthorized'));
    }
    
    // Process request...
}
```

### Security Event Logging

```php
// Log connection override warnings
Log::warning('Connection override detected', [
    'model' => $modelClass,
    'model_connection' => $modelConnection,
    'override_connection' => $overrideConnection,
    'user_id' => auth()->id(),
]);

// Log rate limit exceeded
Log::warning('Rate limit exceeded', [
    'user_id' => auth()->id(),
    'ip' => $request->ip(),
    'endpoint' => $request->path(),
]);

// Log failed CSRF validation
Log::warning('CSRF token mismatch', [
    'user_id' => auth()->id(),
    'ip' => $request->ip(),
    'endpoint' => $request->path(),
]);
```


---

## 🔍 Security Testing

### Principle: Test Security Measures

All security features must be tested.

### CSRF Protection Tests

```php
public function test_tab_loading_requires_csrf_token()
{
    $response = $this->postJson('/api/canvastack/table/tab/1', [
        'tab_index' => 1,
        // Missing CSRF token
    ]);
    
    $response->assertStatus(419); // CSRF token mismatch
}

public function test_tab_loading_with_valid_csrf_token()
{
    $response = $this->withHeaders([
        'X-CSRF-TOKEN' => csrf_token(),
    ])->postJson('/api/canvastack/table/tab/1', [
        'tab_index' => 1,
    ]);
    
    $response->assertStatus(200);
}
```

### Authorization Tests

```php
public function test_unauthorized_user_cannot_load_tab()
{
    $user = User::factory()->create(['role' => 'guest']);
    
    $response = $this->actingAs($user)
        ->postJson('/api/canvastack/table/tab/1', [
            'tab_index' => 1,
        ]);
    
    $response->assertStatus(403);
}

public function test_authorized_user_can_load_tab()
{
    $user = User::factory()->create(['role' => 'admin']);
    
    $response = $this->actingAs($user)
        ->postJson('/api/canvastack/table/tab/1', [
            'tab_index' => 1,
        ]);
    
    $response->assertStatus(200);
}
```

### Input Validation Tests

```php
public function test_invalid_tab_index_rejected()
{
    $response = $this->postJson('/api/canvastack/table/tab/1', [
        'tab_index' => -1, // Invalid
    ]);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['tab_index']);
}

public function test_sql_injection_prevented()
{
    $response = $this->postJson('/api/canvastack/table/tab/1', [
        'tab_index' => 1,
        'filters' => [
            ['column' => "name'; DROP TABLE users; --", 'operator' => '=', 'value' => 'test']
        ],
    ]);
    
    $response->assertStatus(422);
}
```

### Rate Limiting Tests

```php
public function test_rate_limiting_enforced()
{
    $user = User::factory()->create();
    
    // Make 61 requests (exceeds 60/minute limit)
    for ($i = 0; $i < 61; $i++) {
        $response = $this->actingAs($user)
            ->postJson('/api/canvastack/table/tab/1', [
                'tab_index' => 1,
            ]);
    }
    
    // Last request should be rate limited
    $response->assertStatus(429);
}
```


---

## 🎯 Security Checklist

Before deploying to production, verify:

### Configuration
- [ ] HTTPS enforced in production
- [ ] Session encryption enabled
- [ ] Secure cookies enabled (HTTPS only)
- [ ] HTTP-only cookies enabled
- [ ] SameSite cookie attribute set
- [ ] CSRF protection enabled
- [ ] Rate limiting configured
- [ ] Security headers configured

### Code
- [ ] All user inputs validated
- [ ] All outputs escaped
- [ ] Parameterized queries used
- [ ] No raw SQL with user input
- [ ] Column names whitelisted
- [ ] Operators whitelisted
- [ ] Authorization checks implemented
- [ ] Error messages sanitized

### Testing
- [ ] CSRF protection tested
- [ ] Authorization tested
- [ ] Input validation tested
- [ ] Rate limiting tested
- [ ] XSS prevention tested
- [ ] SQL injection prevention tested
- [ ] Information disclosure tested

### Monitoring
- [ ] Security events logged
- [ ] Failed auth attempts logged
- [ ] Rate limit violations logged
- [ ] Suspicious activity monitored
- [ ] Log rotation configured

---

## 💡 Security Best Practices Summary

### DO ✅

1. **Always validate user inputs** before processing
2. **Always escape outputs** to prevent XSS
3. **Always use parameterized queries** to prevent SQL injection
4. **Always require CSRF tokens** for state-changing requests
5. **Always check user permissions** before returning data
6. **Always use HTTPS** in production
7. **Always log security events** for auditing
8. **Always rate limit** public endpoints
9. **Always encrypt sensitive data** in session/database
10. **Always use secure random** for ID generation

### DON'T ❌

1. **Don't expose database connection details** to client
2. **Don't expose table structure** in HTML/JavaScript
3. **Don't use raw SQL** with user input
4. **Don't trust user input** without validation
5. **Don't expose stack traces** in production
6. **Don't use predictable IDs** for sensitive resources
7. **Don't skip authorization checks** for convenience
8. **Don't disable CSRF protection** for AJAX endpoints
9. **Don't log sensitive data** (passwords, tokens, etc.)
10. **Don't use weak random** for security-critical operations

---

## 🚨 Common Vulnerabilities

### 1. SQL Injection

**Vulnerability**:
```php
// ❌ VULNERABLE
$query->whereRaw("name = '{$userInput}'");
```

**Fix**:
```php
// ✅ SECURE
$query->where('name', '=', $userInput);
```

### 2. XSS (Cross-Site Scripting)

**Vulnerability**:
```blade
{{-- ❌ VULNERABLE --}}
<div>{!! $userInput !!}</div>
```

**Fix**:
```blade
{{-- ✅ SECURE --}}
<div>{{ $userInput }}</div>
```

### 3. CSRF (Cross-Site Request Forgery)

**Vulnerability**:
```javascript
// ❌ VULNERABLE - Missing CSRF token
fetch('/api/table/tab/1', { method: 'POST' });
```

**Fix**:
```javascript
// ✅ SECURE - Includes CSRF token
fetch('/api/table/tab/1', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    }
});
```

### 4. Information Disclosure

**Vulnerability**:
```php
// ❌ VULNERABLE
catch (\Exception $e) {
    return response()->json(['error' => $e->getMessage()]);
}
```

**Fix**:
```php
// ✅ SECURE
catch (\Exception $e) {
    Log::error('Tab loading failed', ['error' => $e->getMessage()]);
    
    return response()->json([
        'error' => app()->environment('production') 
            ? __('errors.generic_error')
            : $e->getMessage()
    ]);
}
```

### 5. Insecure Direct Object Reference (IDOR)

**Vulnerability**:
```php
// ❌ VULNERABLE - No authorization check
public function loadTab(Request $request, int $index)
{
    $tabConfig = session("table_tabs.{$index}");
    return response()->json(['html' => $this->render($tabConfig)]);
}
```

**Fix**:
```php
// ✅ SECURE - Authorization check
public function loadTab(Request $request, int $index)
{
    $tabConfig = session("table_tabs.{$index}");
    
    // Check if user can access this data
    $this->authorize('viewAny', $tabConfig['model']);
    
    return response()->json(['html' => $this->render($tabConfig)]);
}
```


---

## 🔐 Advanced Security Measures

### API Token Authentication

For API access, use Laravel Sanctum:

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/canvastack/table/tab/{index}', [TableTabController::class, 'loadTab']);
});
```

**Token Generation**:

```php
$token = $user->createToken('table-access')->plainTextToken;
```

**Token Usage**:

```javascript
fetch('/api/canvastack/table/tab/1', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
    }
});
```

### IP Whitelisting

For sensitive operations:

```php
// app/Http/Middleware/IpWhitelist.php
public function handle($request, Closure $next)
{
    $allowedIps = config('security.allowed_ips', []);
    
    if (!in_array($request->ip(), $allowedIps)) {
        Log::warning('Blocked IP access attempt', [
            'ip' => $request->ip(),
            'path' => $request->path(),
        ]);
        
        abort(403, __('errors.ip_not_allowed'));
    }
    
    return $next($request);
}
```

### Request Signing

For critical operations:

```php
public function loadTab(Request $request, int $index)
{
    // Verify request signature
    $signature = $request->header('X-Signature');
    $expectedSignature = hash_hmac('sha256', 
        $request->getContent(), 
        config('app.key')
    );
    
    if (!hash_equals($expectedSignature, $signature)) {
        abort(401, __('errors.invalid_signature'));
    }
    
    // Process request...
}
```

### Two-Factor Authentication

For admin access:

```php
public function loadTab(Request $request, int $index)
{
    $user = auth()->user();
    
    // Require 2FA for admin users
    if ($user->hasRole('admin') && !$user->hasTwoFactorEnabled()) {
        return response()->json([
            'success' => false,
            'message' => __('errors.2fa_required'),
            'redirect' => route('2fa.setup'),
        ], 403);
    }
    
    // Process request...
}
```

---

## 🛠️ Security Configuration

### Environment Variables

```env
# .env

# HTTPS
APP_URL=https://yourdomain.com
SESSION_SECURE_COOKIE=true

# CSRF
SANCTUM_STATEFUL_DOMAINS=yourdomain.com

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true

# Rate Limiting
THROTTLE_REQUESTS=60
THROTTLE_DECAY=1

# Connection Warnings
CANVASTACK_CONNECTION_WARNING=true
CANVASTACK_CONNECTION_WARNING_METHOD=log

# Debug (disable in production)
APP_DEBUG=false
LOG_LEVEL=error
```


### Configuration File

```php
// config/canvastack.php
return [
    'table' => [
        // Connection warnings
        'connection_warning' => [
            'enabled' => env('CANVASTACK_CONNECTION_WARNING', true),
            'method' => env('CANVASTACK_CONNECTION_WARNING_METHOD', 'log'),
        ],
        
        // Security
        'security' => [
            'validate_columns' => true,
            'validate_operators' => true,
            'max_tab_index' => 50,
            'max_filter_count' => 10,
            'max_filter_value_length' => 255,
        ],
        
        // Rate limiting
        'rate_limit' => [
            'enabled' => true,
            'requests_per_minute' => 60,
        ],
    ],
];
```

---

## 📚 Security Resources

### Laravel Security

- [Laravel Security Documentation](https://laravel.com/docs/security)
- [Laravel Authentication](https://laravel.com/docs/authentication)
- [Laravel Authorization](https://laravel.com/docs/authorization)
- [Laravel CSRF Protection](https://laravel.com/docs/csrf)

### OWASP Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [OWASP Cheat Sheet Series](https://cheatsheetseries.owasp.org/)
- [SQL Injection Prevention](https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html)
- [XSS Prevention](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)

### Security Tools

- **PHPStan** - Static analysis for security issues
- **Laravel Pint** - Code style enforcement
- **Snyk** - Dependency vulnerability scanning
- **SonarQube** - Code quality and security analysis

---

## 🔗 Related Documentation

- [API Reference](../api/table-multi-tab.md) - Complete API documentation
- [Configuration Reference](../configuration/table-config.md) - Configuration options
- [Performance Optimization](performance-optimization.md) - Performance best practices
- [Troubleshooting Guide](troubleshooting.md) - Common issues and solutions

---

**Last Updated**: 2026-03-09  
**Version**: 1.0.0  
**Status**: Published

