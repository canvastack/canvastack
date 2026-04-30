# Core Controller Components API Documentation

بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

Alhamdulillah, this comprehensive API documentation covers all public methods, security warnings, usage examples, and guidelines for the CanvaStack Core Controller Components.

## Table of Contents

1. [Overview](#overview)
2. [Controller System](#controller-system)
3. [Action Trait](#action-trait)
4. [View Trait](#view-trait)
5. [Session Trait](#session-trait)
6. [FileUpload Trait](#fileupload-trait)
7. [Privileges Trait](#privileges-trait)
8. [RouteInfo Trait](#routeinfo-trait)
9. [Scripts Trait](#scripts-trait)
10. [Handler Trait](#handler-trait)
11. [Helper Functions](#helper-functions)
12. [Security Guidelines](#security-guidelines)
13. [Performance Guidelines](#performance-guidelines)

## Overview

### Purpose

The Core Controller Components provide a comprehensive foundation for building secure, performant, and maintainable web applications with the CanvaStack framework. These components handle everything from CRUD operations to view rendering, session management, file uploads, and access control.

### Architecture

The controller system uses a trait-based architecture that provides modular functionality:

- **Controller**: Base controller class that coordinates all components
- **Action**: CRUD operations and DataTables integration
- **View**: Template rendering and data compilation
- **Session**: User session management
- **FileUpload**: Secure file upload handling
- **Privileges**: Module-based access control
- **RouteInfo**: Dynamic route information and action buttons
- **Scripts**: JavaScript/CSS asset management
- **Handler**: Filter management and application

### Key Features

- **Security**: XSS protection, SQL injection prevention, CSRF protection, input validation
- **Performance**: Query optimization, caching, memory management, lazy loading
- **Flexibility**: Modular design, configurable behavior, extensible architecture
- **Developer Experience**: Comprehensive PHPDoc, clear error messages, debugging tools


## Controller System

### Class: `Canvastack\Canvastack\Controllers\Core\Controller`

The base controller class that extends Laravel's BaseController and provides comprehensive functionality for building web applications.

#### Constructor

```php
public function __construct(bool|string $model = false, bool|string $route_page = false)
```

Initializes the controller with an optional Eloquent model and route page configuration.

**Parameters:**
- `$model` (bool|string): Fully qualified model class name or false to skip model initialization
  - Example: `'App\Models\User'`, `'App\Models\Product'`, or `false`
- `$route_page` (bool|string): Route page type or false for default
  - Values: `'adminpage'`, `'frontpage'`, `'login'`, or `false`

**Security:**
- Validates model class name before instantiation
- Escapes all session data to prevent XSS
- Escapes route parameters to prevent XSS in URLs
- Validates route page type against allowed values

**Performance:**
- Sets memory limit from configuration (default: 256M)
- Registers shutdown handler for out-of-memory detection
- Defers full model initialization for memory efficiency

**Example:**
```php
// Controller with model and admin page
class UserController extends Controller {
    public function __construct() {
        parent::__construct('App\Models\User', 'adminpage');
    }
}

// Controller without model (static pages)
class AboutController extends Controller {
    public function __construct() {
        parent::__construct(false, 'frontpage');
    }
}
```


#### callAction()

```php
public function callAction($method, $parameters)
```

Executes an action method with security validation and special routing for DataTables POST requests.

**Parameters:**
- `$method` (string): Controller method name to execute (e.g., 'index', 'store', 'update')
- `$parameters` (array): Parameters to pass to the method

**Returns:**
- `Response|mixed`: Response from the executed method

**Security:**
- **CRITICAL**: Validates CSRF tokens for POST/PUT/PATCH/DELETE requests
- Logs all CSRF failures with full context for security auditing
- Returns 419 status code on CSRF failure
- Prevents CSRF attacks on all state-changing operations

**Special Routing:**
- Intercepts `store()` calls to detect DataTables POST requests
- Routes DataTables POST to `index()` method for proper handling
- Maintains RESTful routing while supporting DataTables AJAX

**Example:**
```php
// Called automatically by Laravel routing
// POST /users with CSRF token
public function store(Request $request) {
    // CSRF already validated by callAction()
    $user = User::create($request->validated());
    return redirect()->route('users.index');
}

// DataTables AJAX request
// POST /users?renderDataTables=true
// Automatically routed to index() method
```

**CSRF Token Sources:**
- Form submissions: `_token` field in request body
- AJAX requests: `X-CSRF-TOKEN` or `X-XSRF-TOKEN` headers
- Query parameters: `_token` in URL (not recommended)


## Action Trait

### Trait: `Canvastack\Canvastack\Controllers\Core\Craft\Action`

Provides comprehensive CRUD operations and DataTables integration with security features and performance optimizations.

### Public Methods

#### index()

```php
public function index()
```

Displays the main index page with an interactive DataTables component for listing records.

**Returns:**
- `View|JsonResponse`: View for GET requests, JSON for POST/AJAX requests

**Security:**
- CSRF protection on all POST requests (handled by `Controller::callAction()`)
- XSS protection: Model table name is escaped to prevent XSS attacks
- All output data is sanitized before rendering

**Performance:**
- Server-side processing handles large datasets efficiently
- DataTables AJAX requests optimized with database-level pagination and filtering

**Example:**
```php
// Route: GET /users
// Renders: resources/views/users/index.blade.php with DataTables

// Route: POST /users (with DataTables parameters)
// Returns: JSON response with paginated, filtered, sorted data
```

#### create()

```php
public function create()
```

Shows the form for creating a new resource.

**Returns:**
- `View`: The create form view

**Security:**
- CSRF protection: Form automatically includes CSRF token field
- XSS protection: All form HTML is generated by form helpers which escape user input
- Input validation: Form fields validated against rules set via `setValidations()`

**Example:**
```php
// Route: GET /users/create
// Renders: resources/views/users/create.blade.php
```


#### show()

```php
public function show(mixed $id)
```

Displays the specified resource in read-only mode.

**Parameters:**
- `$id` (mixed): The resource ID to display (typically an integer primary key)

**Returns:**
- `View`: The show view with read-only form fields

**Security:**
- Route parameter validation: ID is escaped and validated as integer
- XSS protection: All field values are escaped before rendering
- Type validation: ID parameter validated to ensure it's a positive integer

**Example:**
```php
// Route: GET /users/123
// Renders: resources/views/users/show.blade.php with user ID 123 in read-only mode
```

#### edit()

```php
public function edit($id)
```

Shows the form for editing the specified resource.

**Parameters:**
- `$id` (mixed): The resource ID to edit (typically an integer primary key)

**Returns:**
- `View|null`: The edit form view, or null if record not found

**Security:**
- Route parameter validation: ID is escaped and validated as integer
- XSS protection: All field values are escaped before rendering in the form
- Existence check: Verifies record exists before rendering edit form

**Example:**
```php
// Route: GET /users/123/edit
// Renders: resources/views/users/edit.blade.php with user ID 123 data
```

#### store()

```php
public function store(Request $request)
```

Stores a newly created resource in storage.

**Parameters:**
- `$request` (Request): The HTTP request containing form data

**Returns:**
- `RedirectResponse`: Redirect to index or specified route

**Security:**
- CSRF protection: Validated by `Controller::callAction()` before execution
- Input validation: All inputs validated against rules set via `setValidations()`
- XSS protection: Validation error messages are escaped before display
- SQL injection prevention: Uses query builder with parameter binding

**Example:**
```php
// Route: POST /users
// Validates and stores new user, then redirects to users.index
```


#### update()

```php
public function update(Request $request, $id)
```

Updates the specified resource in storage.

**Parameters:**
- `$request` (Request): The HTTP request containing form data
- `$id` (mixed): The resource ID to update

**Returns:**
- `RedirectResponse`: Redirect to index or specified route

**Security:**
- CSRF protection: Validated by `Controller::callAction()`
- Input validation: All inputs validated against rules
- Route parameter validation: ID is escaped and validated
- SQL injection prevention: Uses query builder with parameter binding

**Example:**
```php
// Route: PUT /users/123
// Validates and updates user 123, then redirects
```

#### destroy()

```php
public function destroy($id)
```

Removes the specified resource from storage.

**Parameters:**
- `$id` (mixed): The resource ID to delete

**Returns:**
- `RedirectResponse`: Redirect to index

**Security:**
- CSRF protection: Validated by `Controller::callAction()`
- Route parameter validation: ID is escaped and validated
- Soft delete support: Respects model's soft delete configuration

**Example:**
```php
// Route: DELETE /users/123
// Deletes user 123, then redirects to users.index
```

## View Trait

### Trait: `Canvastack\Canvastack\Controllers\Core\Craft\View`

Provides comprehensive view rendering functionality with XSS protection and performance optimizations.

### Public Methods

#### render()

```php
public function render(mixed $data = false): mixed
```

Renders view with comprehensive data compilation and XSS protection.

**Parameters:**
- `$data` (mixed): Additional data to pass to view (array, object, or single value)

**Returns:**
- `View|JsonResponse`: View for normal requests, JSON for AJAX

**Security:**
- **CRITICAL**: Primary XSS prevention mechanism for all view rendering
- Escapes application name, breadcrumb labels, action button labels, page titles
- Menu items generated by trusted system functions (not escaped)
- Content page data escaped via `escapeArray/escapeObject`

**Performance:**
- Lazy loading: Components only loaded if they have elements to render
- View path caching: Compiled view paths cached
- Preference caching: Preference data cached to reduce database queries
- Script deduplication: Scripts only added if components are rendered

**Example:**
```php
// Basic view rendering
public function index() {
    $this->setPage('User Management');
    return $this->render();
}

// With additional data
public function dashboard() {
    $this->setPage('Dashboard');
    return $this->render([
        'total_users' => User::count(),
        'recent_posts' => Post::latest()->take(5)->get()
    ]);
}
```


#### setPage()

```php
public function setPage(string $title = '', string|null $view = null): void
```

Sets the page title and optional custom view path.

**Parameters:**
- `$title` (string): Page title to display
- `$view` (string|null): Optional custom view path

**Security:**
- Page title is escaped to prevent XSS attacks

**Example:**
```php
$this->setPage('User Management');
$this->setPage('Dashboard', 'custom.dashboard');
```

## Session Trait

### Trait: `Canvastack\Canvastack\Controllers\Core\Craft\Session`

Provides user session management with security validation and integrity checking.

### Public Methods

#### getSession()

```php
public function getSession(string $key = null): mixed
```

Retrieves session data with optional key filtering.

**Parameters:**
- `$key` (string|null): Optional session key to retrieve specific value

**Returns:**
- `mixed`: Session value or entire session array

**Security:**
- Session data validated for integrity before return
- Detects session tampering and throws exception

**Example:**
```php
$userId = $this->getSession('id');
$username = $this->getSession('username');
$allSession = $this->getSession();
```

#### setSession()

```php
public function setSession(string $key, mixed $value): void
```

Sets a session value with validation.

**Parameters:**
- `$key` (string): Session key
- `$value` (mixed): Value to store

**Security:**
- Validates data type before storing
- Encrypts sensitive data if configured

**Example:**
```php
$this->setSession('user_preference', 'dark_mode');
$this->setSession('last_activity', now());
```


## FileUpload Trait

### Trait: `Canvastack\Canvastack\Controllers\Core\Craft\Includes\FileUpload`

Provides secure file upload handling with comprehensive validation and optimization.

### Public Methods

#### uploadFiles()

```php
public function uploadFiles(Request $request, array $config): array
```

Handles file upload with security validation and processing.

**Parameters:**
- `$request` (Request): HTTP request containing uploaded files
- `$config` (array): Upload configuration (field name, type, validation, thumbnail settings)

**Returns:**
- `array`: Array of uploaded file paths and metadata

**Security:**
- **CRITICAL**: Validates file extensions against whitelist
- Validates MIME types to match extensions
- Validates file sizes against configured limits
- Sanitizes file names to remove dangerous characters
- Generates unique file names to prevent overwrites
- Scans for malicious content if configured

**Performance:**
- Supports chunked uploads for large files (>10MB)
- Optimizes image processing and thumbnail generation
- Efficient memory management during upload

**Example:**
```php
$config = [
    'field_name' => 'avatar',
    'file_type' => 'image',
    'validation' => 'image|mimes:jpeg,png,jpg|max:2048',
    'thumbnail' => ['width' => 150, 'height' => 150]
];

$uploadedFiles = $this->uploadFiles($request, $config);
```

## Privileges Trait

### Trait: `Canvastack\Canvastack\Controllers\Core\Craft\Includes\Privileges`

Provides module-based access control and authorization.

### Public Methods

#### checkPrivilege()

```php
public function checkPrivilege(string $module, string $action = 'view'): bool
```

Checks if current user has privilege for specified module and action.

**Parameters:**
- `$module` (string): Module name to check
- `$action` (string): Action to check (view, create, edit, delete)

**Returns:**
- `bool`: True if user has privilege, false otherwise

**Security:**
- Implements role-based access control (RBAC)
- Logs all privilege violations with context
- Supports privilege inheritance from parent groups

**Performance:**
- Caches privilege checks to reduce database queries
- Batch privilege checking for multiple modules

**Example:**
```php
if ($this->checkPrivilege('users', 'edit')) {
    // User can edit users
}

if ($this->checkPrivilege('reports', 'view')) {
    // User can view reports
}
```


## RouteInfo Trait

### Trait: `Canvastack\Canvastack\Controllers\Core\Craft\Includes\RouteInfo`

Provides dynamic route information and action button generation.

### Public Methods

#### routeInfo()

```php
public function routeInfo(): object
```

Generates route information including current path, module name, and action buttons.

**Returns:**
- `object`: Route information object with properties:
  - `current_path`: Current route path
  - `module_name`: Module name
  - `page_info`: Page info (index, create, edit, show)
  - `action_page`: Array of action buttons
  - `controller_name`: Controller name
  - `controller_path`: Controller path

**Security:**
- Action button labels are escaped to prevent XSS
- URLs are validated before generation
- Privilege checking integrated for button visibility

**Performance:**
- Route info cached to avoid repeated processing
- Efficient URL generation

**Example:**
```php
$routeInfo = $this->routeInfo();
echo $routeInfo->module_name; // "users"
echo $routeInfo->page_info; // "index"
```

## Scripts Trait

### Trait: `Canvastack\Canvastack\Controllers\Core\Craft\Scripts`

Provides JavaScript and CSS asset management with optimization.

### Public Methods

#### addScript()

```php
public function addScript(string $path, string $position = 'bottom', bool $asCode = false): void
```

Adds a JavaScript file or inline code to the page.

**Parameters:**
- `$path` (string): Script path or inline code
- `$position` (string): Position ('top', 'bottom', 'last')
- `$asCode` (bool): Whether to treat as inline code

**Performance:**
- Automatic script deduplication
- Supports async/defer loading
- Script concatenation for production
- Respects load order for dependencies

**Example:**
```php
$this->addScript('/js/custom.js', 'bottom');
$this->addScript('console.log("Hello");', 'bottom', true);
```

#### addStyle()

```php
public function addStyle(string $path, bool $asCode = false): void
```

Adds a CSS file or inline styles to the page.

**Parameters:**
- `$path` (string): CSS path or inline styles
- `$asCode` (bool): Whether to treat as inline code

**Performance:**
- Automatic CSS deduplication
- Style concatenation for production

**Example:**
```php
$this->addStyle('/css/custom.css');
$this->addStyle('.custom { color: red; }', true);
```


## Handler Trait

### Trait: `Canvastack\Canvastack\Controllers\Core\Craft\Handler`

Provides filter management and application for data queries.

### Public Methods

#### filterPage()

```php
public function filterPage(array $filters): void
```

Applies filters to model queries.

**Parameters:**
- `$filters` (array): Array of filters to apply (field => value pairs)

**Security:**
- Validates filter values to prevent SQL injection
- Validates array depth to prevent DoS attacks
- Detects null bytes in filter values

**Performance:**
- Caches filter results for repeated queries
- Efficient filter chaining

**Example:**
```php
$this->filterPage(['status' => 'active', 'role' => 'admin']);
```

## Helper Functions

### Global Helper Functions in `App.php`

#### canvastack_insert()

```php
function canvastack_insert(object $model, array $data, bool $getField = false): mixed
```

Inserts data into database with validation and security.

**Parameters:**
- `$model` (object): Eloquent model instance
- `$data` (array): Data to insert
- `$getField` (bool): Whether to return inserted ID

**Returns:**
- `mixed`: Inserted ID if `$getField` is true, void otherwise

**Security:**
- Validates data before insertion
- Uses query builder with parameter binding
- Prevents SQL injection

**Example:**
```php
$userId = canvastack_insert($userModel, [
    'name' => 'John Doe',
    'email' => 'john@example.com'
], true);
```

#### canvastack_update()

```php
function canvastack_update(object $model, array $data): void
```

Updates data in database with validation.

**Parameters:**
- `$model` (object): Eloquent model instance
- `$data` (array): Data to update

**Security:**
- Validates data before update
- Uses query builder with parameter binding
- Prevents SQL injection

**Example:**
```php
canvastack_update($userModel, [
    'name' => 'Jane Doe',
    'email' => 'jane@example.com'
]);
```


#### canvastack_query()

```php
function canvastack_query(string $sql, string $type = 'TABLE', string|null $connection = null): mixed
```

Executes a database query with security validation.

**Parameters:**
- `$sql` (string): SQL query to execute
- `$type` (string): Query type ('TABLE', 'SINGLE', 'VALUE')
- `$connection` (string|null): Database connection name

**Returns:**
- `mixed`: Query results based on type

**Security:**
- **CRITICAL**: Validates SQL query length to prevent DoS
- Logs suspicious queries
- Use with caution - prefer query builder methods

**Example:**
```php
$users = canvastack_query("SELECT * FROM users WHERE status = ?", 'TABLE');
```

#### canvastack_controller_validate_csrf()

```php
function canvastack_controller_validate_csrf(Request $request): bool
```

Validates CSRF token for state-changing requests.

**Parameters:**
- `$request` (Request): HTTP request to validate

**Returns:**
- `bool`: True if valid, throws exception if invalid

**Security:**
- **CRITICAL**: Prevents CSRF attacks
- Checks multiple token sources (form, headers, query)
- Logs all failures with context

**Throws:**
- `CSRFException`: If token is invalid or missing

**Example:**
```php
try {
    canvastack_controller_validate_csrf($request);
} catch (CSRFException $e) {
    return response()->json(['error' => $e->getUserMessage()], 419);
}
```

#### canvastack_controller_sanitize_filename()

```php
function canvastack_controller_sanitize_filename(string $filename): string
```

Sanitizes file name to remove dangerous characters.

**Parameters:**
- `$filename` (string): Original file name

**Returns:**
- `string`: Sanitized file name safe for storage

**Security:**
- Removes path traversal characters (../, ..\)
- Removes null bytes
- Removes special characters that could cause issues
- Preserves file extension

**Example:**
```php
$safeName = canvastack_controller_sanitize_filename('../../../etc/passwd.txt');
// Returns: 'passwd.txt'
```


## Security Guidelines

### XSS (Cross-Site Scripting) Protection

#### Overview

All user-controllable data is automatically escaped before rendering to prevent XSS attacks. The framework implements a comprehensive defense-in-depth strategy.

#### Automatic Protection

The following data is automatically escaped:
- Session data (username, email, full name, etc.)
- Route parameters and query strings
- Form field values and labels
- Table cell values and headers
- Breadcrumb labels
- Action button labels
- Page titles
- Error messages

#### Manual Escaping

When adding custom data to views, use the escaping helpers:

```php
// Escape single value
$safe = htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// In Blade templates (automatic escaping)
{{ $userInput }} // Escaped automatically

// Raw output (use with caution)
{!! $trustedHtml !!} // NOT escaped - only for trusted content
```

#### SafeHtml Marking

For trusted internal HTML that should not be escaped:

```php
use Canvastack\Canvastack\Library\Constants\SafeHtml;

$trustedHtml = '<strong>System Message</strong>';
$marked = SafeHtml::mark($trustedHtml);
// This HTML will not be escaped during rendering
```

#### Configuration

Control XSS protection via `config/canvastack.controller.php`:

```php
'security' => [
    'xss_protection' => true, // Enable/disable XSS protection
    'escape_output' => true,  // Escape all output
]
```

### SQL Injection Prevention

#### Overview

The framework prevents SQL injection through multiple layers of defense.

#### Query Builder (Recommended)

Always use Laravel's query builder with parameter binding:

```php
// GOOD: Uses parameter binding
$users = DB::table('users')
    ->where('email', $email)
    ->where('status', 'active')
    ->get();

// BAD: String concatenation (vulnerable)
$users = DB::select("SELECT * FROM users WHERE email = '$email'");
```

#### Table Name Validation

Dynamic table names are validated against a whitelist:

```php
// Automatically validated in Action trait
$this->model($modelClass);
// Table name checked against database schema
```

#### Column Name Validation

Column names are validated against the model's schema:

```php
// Automatically validated in filterPage()
$this->filterPage(['email' => $value]);
// Column 'email' checked against table schema
```

#### Operator Validation

SQL operators are validated against a whitelist:

```php
// Allowed operators: =, !=, <, >, <=, >=, LIKE, NOT LIKE, IN, NOT IN
// Automatically validated in query building
```

#### Configuration

```php
'security' => [
    'sql_injection_prevention' => true,
],
'validation' => [
    'validate_table_names' => true,
    'validate_column_names' => true,
]
```


### CSRF (Cross-Site Request Forgery) Protection

#### Overview

All state-changing requests (POST, PUT, PATCH, DELETE) are automatically protected by CSRF token validation.

#### Automatic Protection

CSRF validation happens in `Controller::callAction()` before any controller method executes:

```php
// Automatically validated for all POST/PUT/PATCH/DELETE requests
public function store(Request $request) {
    // CSRF already validated - safe to process
}
```

#### Token Sources

CSRF tokens are accepted from multiple sources:

1. **Form Submissions**: `_token` field in request body
```html
<form method="POST" action="/users">
    @csrf
    <!-- Form fields -->
</form>
```

2. **AJAX Requests**: `X-CSRF-TOKEN` header
```javascript
$.ajax({
    url: '/users',
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    data: { name: 'John Doe' }
});
```

3. **Query Parameters**: `_token` in URL (not recommended)
```
POST /users?_token=abc123
```

#### Error Handling

CSRF failures return 419 status code with user-friendly message:

```javascript
$.ajax({
    url: '/users',
    method: 'POST',
    error: function(xhr) {
        if (xhr.status === 419) {
            alert('Your session has expired. Please refresh the page.');
            location.reload();
        }
    }
});
```

#### Configuration

```php
'security' => [
    'csrf_protection' => true, // Enable/disable CSRF protection
]
```

### Input Validation

#### Overview

All user inputs are validated before processing to prevent malicious data.

#### Validation Rules

Set validation rules in controller constructor:

```php
public function __construct() {
    parent::__construct('App\Models\User');
    
    $this->setValidations([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:8|confirmed',
        'role' => 'required|in:admin,user,guest'
    ]);
}
```

#### Automatic Validation

Validation happens automatically in `store()` and `update()` methods:

```php
public function store(Request $request) {
    // Validation already performed
    // Only valid data reaches here
}
```

#### Custom Validation

For custom validation logic:

```php
public function store(Request $request) {
    $validator = Validator::make($request->all(), [
        'email' => ['required', 'email', function ($attribute, $value, $fail) {
            if (!str_ends_with($value, '@company.com')) {
                $fail('Email must be from company domain.');
            }
        }],
    ]);
    
    if ($validator->fails()) {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput();
    }
}
```

#### Array Validation

Arrays are validated for depth and size to prevent DoS attacks:

```php
// Automatically validated in filterPage()
$this->filterPage($filters);
// Checks array depth (max 5 levels)
// Checks array size (max 100 elements)
```

#### Configuration

```php
'validation' => [
    'strict_mode' => true,
    'max_array_depth' => 5,
    'max_input_variables' => 100,
]
```


### File Upload Security

#### Overview

File uploads are validated comprehensively to prevent malicious file uploads.

#### Validation Layers

1. **File Extension Validation**
```php
$config = [
    'validation' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
];
// Only allows specified extensions
```

2. **MIME Type Validation**
```php
// MIME type must match file extension
// Prevents extension spoofing attacks
```

3. **File Size Validation**
```php
'file_upload' => [
    'max_file_size' => 10485760, // 10MB in bytes
]
```

4. **File Name Sanitization**
```php
// Automatically sanitizes file names
// Removes: ../, ..\, null bytes, special characters
$safeName = canvastack_controller_sanitize_filename($originalName);
```

5. **Unique Name Generation**
```php
// Generates unique file names to prevent overwrites
// Format: timestamp_random_originalname.ext
```

#### Allowed File Types

Configure allowed file types in `config/canvastack.controller.php`:

```php
'security' => [
    'allowed_file_extensions' => [
        'jpg', 'jpeg', 'png', 'gif', 'svg', // Images
        'pdf', 'doc', 'docx', 'xls', 'xlsx', // Documents
        'zip', 'rar', // Archives
    ],
]
```

#### Storage Location

Files are stored outside the web root by default:

```php
'file_upload' => [
    'storage_path' => 'uploads', // Relative to storage/app
]
```

#### Example

```php
$config = [
    'field_name' => 'document',
    'file_type' => 'document',
    'validation' => 'file|mimes:pdf,doc,docx|max:5120',
];

$uploadedFiles = $this->uploadFiles($request, $config);
```

### Session Security

#### Overview

Session management includes integrity checking and security validation.

#### Session Validation

Sessions are validated for integrity on every access:

```php
// Automatically validated in getSession()
$userId = $this->getSession('id');
// Throws SessionException if tampered
```

#### Session Regeneration

Session IDs are regenerated after authentication:

```php
// Automatically done after successful login
// Prevents session fixation attacks
```

#### Session Timeout

Configure session timeout:

```php
'session' => [
    'lifetime' => 120, // Minutes
    'expire_on_close' => false,
]
```

#### Sensitive Data Encryption

Sensitive session data can be encrypted:

```php
'security' => [
    'encrypt_session_data' => true,
]
```

#### Session Integrity Check

Detects session tampering:

```php
try {
    $data = $this->getSession();
} catch (SessionException $e) {
    // Session tampered - force logout
    return redirect()->route('login');
}
```


## Performance Guidelines

### Query Optimization

#### Eager Loading

Prevent N+1 query problems by eager loading relationships:

```php
// BAD: N+1 queries (1 + N queries)
$users = User::all();
foreach ($users as $user) {
    echo $user->profile->bio; // Separate query for each user
}

// GOOD: Eager loading (2 queries total)
$users = User::with('profile')->get();
foreach ($users as $user) {
    echo $user->profile->bio; // No additional queries
}
```

#### Column Selection

Select only required columns instead of SELECT *:

```php
// BAD: Loads all columns
$users = User::all();

// GOOD: Loads only needed columns
$users = User::select('id', 'name', 'email')->get();
```

#### Efficient Pagination

Use database-level pagination:

```php
// GOOD: Database-level pagination
$users = User::paginate(20);

// BAD: Loading all records then slicing
$users = User::all()->slice(0, 20);
```

#### Query Monitoring

Monitor slow queries:

```php
'performance' => [
    'enable_query_monitoring' => true,
    'slow_query_threshold' => 1000, // milliseconds
]
```

Slow queries are automatically logged:

```
[WARNING] Slow query detected
Query: SELECT * FROM users WHERE ...
Execution time: 1250ms
Threshold: 1000ms
```

### Caching Strategy

#### Privilege Caching

Privilege checks are automatically cached:

```php
'caching' => [
    'privilege_cache_enabled' => true,
    'privilege_cache_ttl' => 3600, // 1 hour
]
```

#### Route Info Caching

Route information is cached to avoid repeated processing:

```php
'caching' => [
    'route_info_cache_enabled' => true,
    'route_info_cache_ttl' => 3600,
]
```

#### Preference Caching

Application preferences are cached:

```php
'caching' => [
    'preference_cache_enabled' => true,
    'preference_cache_ttl' => 7200, // 2 hours
]
```

#### Custom Caching

Implement custom caching for expensive operations:

```php
use Illuminate\Support\Facades\Cache;

public function getStatistics() {
    return Cache::remember('statistics', 3600, function () {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'total_posts' => Post::count(),
        ];
    });
}
```

#### Cache Invalidation

Invalidate cache when data changes:

```php
public function store(Request $request) {
    $user = User::create($request->validated());
    
    // Invalidate statistics cache
    Cache::forget('statistics');
    
    return redirect()->route('users.index');
}
```


### Memory Management

#### Memory Limits

Configure memory limits in `config/canvastack.controller.php`:

```php
'performance' => [
    'memory_limit' => '256M', // PHP memory limit
]
```

#### Chunked Processing

For large datasets, use chunked processing:

```php
// Process 1000 records at a time
User::chunk(1000, function ($users) {
    foreach ($users as $user) {
        // Process user
    }
});
```

#### Variable Cleanup

Clean up large variables after use:

```php
$largeData = $this->processLargeDataset();
// Use $largeData
unset($largeData); // Free memory
```

#### File Upload Chunking

Large file uploads use chunking automatically:

```php
'file_upload' => [
    'enable_chunking' => true,
    'chunk_size' => 1048576, // 1MB chunks
]
```

#### Memory Monitoring

Monitor memory usage:

```php
'performance' => [
    'enable_memory_monitoring' => true,
    'memory_warning_threshold' => 0.8, // 80% of limit
]
```

### Asset Optimization

#### Script Deduplication

Scripts are automatically deduplicated:

```php
// Adding same script multiple times
$this->addScript('/js/jquery.js');
$this->addScript('/js/jquery.js'); // Ignored (duplicate)
// Only loaded once in final output
```

#### Script Concatenation

Concatenate scripts for production:

```php
'performance' => [
    'enable_script_concatenation' => true,
    'concatenation_enabled_environments' => ['production'],
]
```

#### Script Minification

Minify scripts for production:

```php
'performance' => [
    'enable_script_minification' => true,
]
```

#### Async/Defer Loading

Load scripts asynchronously:

```php
$this->addScript('/js/analytics.js', 'bottom', false, 'async');
$this->addScript('/js/tracking.js', 'bottom', false, 'defer');
```

#### CSS Optimization

CSS files are also deduplicated and can be concatenated:

```php
$this->addStyle('/css/custom.css');
$this->addStyle('/css/custom.css'); // Ignored (duplicate)
```

### Lazy Loading

#### Component Lazy Loading

Components are only loaded when needed:

```php
// Form component only loaded if form elements exist
if (!empty($this->data['components']->form->elements)) {
    $formElements = $this->form->render(...);
}
```

#### View Lazy Loading

Views are compiled on-demand:

```php
'performance' => [
    'lazy_loading' => true,
]
```

### Performance Monitoring

#### Enable Monitoring

```php
'performance' => [
    'enable_query_monitoring' => true,
    'enable_memory_monitoring' => true,
]
```

#### Performance Metrics

Metrics are logged for analysis:

```
[DEBUG] Query performance
Query: SELECT * FROM users
Execution time: 45ms
Memory used: 2.5MB
```

#### Slow Query Logging

```
[WARNING] Slow query detected
Query: SELECT * FROM posts WHERE ...
Execution time: 1250ms
Threshold: 1000ms
User ID: 123
Route: /posts
```


## Complete Examples

### Basic CRUD Controller

```php
<?php
namespace App\Http\Controllers;

use Canvastack\Canvastack\Controllers\Core\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller {
    
    public function __construct() {
        // Initialize with User model and admin page type
        parent::__construct('App\Models\User', 'adminpage');
        
        // Set validation rules
        $this->setValidations([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:admin,user,guest'
        ]);
        
        // Set hidden fields (not visible in forms)
        $this->hideFields = ['created_by', 'updated_by'];
        
        // Set excluded fields (not in forms at all)
        $this->excludeFields = ['password_hash', 'remember_token'];
    }
    
    // index(), create(), store(), show(), edit(), update(), destroy()
    // are automatically handled by Action trait
}
```

### Advanced Controller with Filters

```php
<?php
namespace App\Http\Controllers;

use Canvastack\Canvastack\Controllers\Core\Controller;
use App\Models\Post;

class PostController extends Controller {
    
    public function __construct() {
        parent::__construct('App\Models\Post', 'adminpage');
        
        // Apply default filters
        $this->filterPage([
            'status' => 'published',
            'user_id' => session('id')
        ]);
        
        $this->setValidations([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'tags' => 'array',
            'featured_image' => 'image|mimes:jpeg,png,jpg|max:2048'
        ]);
    }
    
    public function index() {
        // Add custom data to view
        $this->setPage('Post Management');
        
        // Add custom scripts
        $this->addScript('/js/post-manager.js');
        
        // Add custom styles
        $this->addStyle('/css/post-manager.css');
        
        return $this->render([
            'categories' => Category::all(),
            'total_posts' => Post::count()
        ]);
    }
    
    public function store(Request $request) {
        // Handle file upload
        if ($request->hasFile('featured_image')) {
            $config = [
                'field_name' => 'featured_image',
                'file_type' => 'image',
                'validation' => 'image|mimes:jpeg,png,jpg|max:2048',
                'thumbnail' => ['width' => 300, 'height' => 200]
            ];
            
            $uploadedFiles = $this->uploadFiles($request, $config);
            $request->merge(['image_path' => $uploadedFiles[0]['path']]);
        }
        
        // Store is handled automatically by Action trait
        return $this->insert_data($request);
    }
}
```

### Custom Dashboard Controller

```php
<?php
namespace App\Http\Controllers;

use Canvastack\Canvastack\Controllers\Core\Controller;
use App\Models\User;
use App\Models\Post;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller {
    
    public function __construct() {
        parent::__construct(false, 'adminpage');
    }
    
    public function index() {
        $this->setPage('Dashboard', 'admin.dashboard');
        
        // Get statistics with caching
        $stats = Cache::remember('dashboard_stats', 3600, function () {
            return [
                'total_users' => User::count(),
                'active_users' => User::where('status', 'active')->count(),
                'total_posts' => Post::count(),
                'pending_orders' => Order::where('status', 'pending')->count(),
                'revenue_today' => Order::whereDate('created_at', today())
                    ->sum('total'),
            ];
        });
        
        // Get recent activity
        $recentUsers = User::latest()->take(5)->get();
        $recentPosts = Post::latest()->take(5)->get();
        
        // Add chart data
        $this->chart->addChart('revenue', [
            'type' => 'line',
            'data' => $this->getRevenueData(),
            'options' => ['responsive' => true]
        ]);
        
        return $this->render([
            'stats' => $stats,
            'recent_users' => $recentUsers,
            'recent_posts' => $recentPosts
        ]);
    }
    
    private function getRevenueData() {
        return Order::selectRaw('DATE(created_at) as date, SUM(total) as revenue')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
}
```


### API Controller with JSON Responses

```php
<?php
namespace App\Http\Controllers\Api;

use Canvastack\Canvastack\Controllers\Core\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductApiController extends Controller {
    
    public function __construct() {
        parent::__construct('App\Models\Product');
    }
    
    public function index(Request $request) {
        // Apply filters from request
        $filters = [];
        if ($request->has('category')) {
            $filters['category_id'] = $request->category;
        }
        if ($request->has('status')) {
            $filters['status'] = $request->status;
        }
        
        $this->filterPage($filters);
        
        // Get paginated products with eager loading
        $products = Product::with(['category', 'images'])
            ->paginate($request->get('per_page', 20));
        
        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }
    
    public function store(Request $request) {
        // Validation happens automatically
        $productId = $this->insert_data($request, false);
        
        $product = Product::with(['category', 'images'])->find($productId);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }
    
    public function update(Request $request, $id) {
        $this->update_data($request, $id);
        
        $product = Product::with(['category', 'images'])->find($id);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Product updated successfully',
            'data' => $product
        ]);
    }
    
    public function destroy($id) {
        Product::findOrFail($id)->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully'
        ]);
    }
}
```

## Configuration Reference

### Complete Configuration File

Location: `config/canvastack.controller.php`

```php
<?php

return [
    // Security Configuration
    'security' => [
        'xss_protection' => true,
        'csrf_protection' => true,
        'sql_injection_prevention' => true,
        'escape_output' => true,
        'allowed_file_extensions' => [
            'jpg', 'jpeg', 'png', 'gif', 'svg',
            'pdf', 'doc', 'docx', 'xls', 'xlsx',
            'zip', 'rar'
        ],
        'max_file_size' => 10485760, // 10MB
        'sanitize_filenames' => true,
        'encrypt_session_data' => false,
    ],
    
    // Performance Configuration
    'performance' => [
        'enable_caching' => true,
        'cache_ttl' => 3600,
        'eager_loading' => true,
        'query_optimization' => true,
        'memory_limit' => '256M',
        'enable_query_monitoring' => true,
        'slow_query_threshold' => 1000, // milliseconds
        'enable_memory_monitoring' => true,
        'memory_warning_threshold' => 0.8,
        'lazy_loading' => true,
        'enable_script_concatenation' => true,
        'enable_script_minification' => true,
        'concatenation_enabled_environments' => ['production'],
    ],
    
    // Caching Configuration
    'caching' => [
        'privilege_cache_enabled' => true,
        'privilege_cache_ttl' => 3600,
        'route_info_cache_enabled' => true,
        'route_info_cache_ttl' => 3600,
        'preference_cache_enabled' => true,
        'preference_cache_ttl' => 7200,
        'view_cache_enabled' => true,
    ],
    
    // File Upload Configuration
    'file_upload' => [
        'enable_chunking' => true,
        'chunk_size' => 1048576, // 1MB
        'enable_thumbnails' => true,
        'thumbnail_width' => 150,
        'thumbnail_height' => 150,
        'storage_path' => 'uploads',
    ],
    
    // Validation Configuration
    'validation' => [
        'strict_mode' => true,
        'validate_table_names' => true,
        'validate_column_names' => true,
        'max_query_length' => 10000,
        'max_array_depth' => 5,
        'max_input_variables' => 100,
    ],
    
    // Logging Configuration
    'logging' => [
        'log_security_events' => true,
        'log_performance_issues' => true,
        'log_validation_failures' => true,
        'log_file_uploads' => true,
        'log_datatables_requests' => false,
    ],
    
    // DataTables Configuration
    'datatables' => [
        'max_records_per_page' => 1000,
        'default_page_length' => 10,
    ],
];
```

## Conclusion

The CanvaStack Core Controller Components provide a comprehensive, secure, and performant foundation for building web applications. By following the guidelines and examples in this documentation, you can build robust applications that are protected against common security vulnerabilities while maintaining excellent performance.

### Key Takeaways

1. **Security First**: All user input is validated and escaped automatically
2. **Performance Optimized**: Caching, lazy loading, and query optimization built-in
3. **Developer Friendly**: Comprehensive PHPDoc, clear error messages, extensive examples
4. **Highly Configurable**: All features can be customized via configuration files
5. **Battle Tested**: Used in production applications with proven reliability

### Support and Resources

- **Documentation**: `vendor/canvastack/canvastack/docs/CORE/`
- **Migration Guide**: `MIGRATION_GUIDE.md`
- **Monitoring Guide**: `MONITORING_AND_LOGGING.md`
- **Configuration**: `config/canvastack.controller.php`

### Version Information

- **Version**: 2.0.0
- **Last Updated**: 2024
- **Compatibility**: Laravel 8.x, 9.x, 10.x, 11.x

---

بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

Alhamdulillah, this API documentation has been completed to provide comprehensive guidance for developers using the CanvaStack Core Controller Components.

