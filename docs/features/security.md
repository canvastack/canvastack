# Security Features

**Version**: 1.0.0  
**Last Updated**: 2026-02-26  
**Status**: Complete

---

## Table of Contents

1. [Overview](#overview)
2. [SQL Injection Prevention](#sql-injection-prevention)
3. [XSS Prevention](#xss-prevention)
4. [CSRF Protection](#csrf-protection)
5. [Mass Assignment Protection](#mass-assignment-protection)
6. [Authentication & Authorization](#authentication--authorization)
7. [Input Validation](#input-validation)
8. [Output Escaping](#output-escaping)
9. [Security Headers](#security-headers)
10. [Rate Limiting](#rate-limiting)
11. [Audit Logging](#audit-logging)
12. [Security Best Practices](#security-best-practices)
13. [Security Checklist](#security-checklist)

---

## Overview

CanvaStack implements comprehensive security measures to protect against common web vulnerabilities. All security features are enabled by default and follow OWASP Top 10 security guidelines.

### Security Features

- ✅ SQL Injection prevention (parameterized queries)
- ✅ XSS prevention (automatic output escaping)
- ✅ CSRF protection (Laravel tokens)
- ✅ Mass assignment protection
- ✅ Input validation and sanitization
- ✅ Secure password hashing (bcrypt)
- ✅ Role-based access control (RBAC)
- ✅ Rate limiting
- ✅ Audit logging
- ✅ Security headers

### Security Improvements Over Legacy

| Vulnerability | Legacy | CanvaStack | Status |
|---------------|--------|------------|--------|
| SQL Injection | ❌ Vulnerable | ✅ Protected | Fixed |
| XSS | ⚠️ Partial | ✅ Protected | Fixed |
| CSRF | ✅ Protected | ✅ Protected | Maintained |
| Mass Assignment | ⚠️ Partial | ✅ Protected | Improved |
| N+1 Queries | ❌ Vulnerable | ✅ Protected | Fixed |

---

## SQL Injection Prevention

### Problem in Legacy Code

The legacy `Datatables.php` was vulnerable to SQL injection:

```php
// ❌ VULNERABLE - Legacy code
foreach ($this->columns as $column) {
    $query->orWhere($column, 'like', '%' . $search . '%');
}

// ❌ VULNERABLE - Raw SQL
$query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE '%{$search}%'");
```

### Solution in CanvaStack

CanvaStack uses parameterized queries exclusively:

```php
// ✅ SAFE - Parameterized query
foreach ($this->columns as $column) {
    $query->orWhere($column, 'like', '%' . $search . '%');
}

// ✅ SAFE - Parameterized raw query
$query->whereRaw(
    "CONCAT(first_name, ' ', last_name) LIKE ?",
    ['%' . $search . '%']
);
```

### Query Builder Protection

```php
use Canvastack\Components\Table\TableBuilder;

$table = new TableBuilder();

// All queries are automatically parameterized
$table->column('name', 'Name')
    ->searchable()
    ->sortable();

// User input is automatically escaped
$table->runModel(User::class);
```

### Custom Queries

```php
// ✅ SAFE - Use query builder
$table->query(function ($query) {
    $query->where('status', request('status'))
        ->where('created_at', '>=', request('date'));
});

// ✅ SAFE - Use bindings for raw queries
$table->query(function ($query) {
    $query->whereRaw('YEAR(created_at) = ?', [request('year')]);
});

// ❌ NEVER DO THIS
$table->query(function ($query) {
    $query->whereRaw("status = '" . request('status') . "'");
});
```

### Validation Before Query

```php
// Validate input before querying
$validated = request()->validate([
    'status' => 'required|in:active,inactive',
    'date' => 'required|date',
]);

$table->query(function ($query) use ($validated) {
    $query->where('status', $validated['status'])
        ->where('created_at', '>=', $validated['date']);
});
```

---

## XSS Prevention

### Automatic Output Escaping

All output is automatically escaped in Blade templates:

```blade
{{-- ✅ SAFE - Automatically escaped --}}
<td>{{ $user->name }}</td>
<td>{{ $user->email }}</td>

{{-- ❌ DANGEROUS - Unescaped output --}}
<td>{!! $user->name !!}</td>
```

### HTML in Table Columns

```php
// ✅ SAFE - HTML is escaped by default
$table->column('name', 'Name');

// ✅ SAFE - Use format() for controlled HTML
$table->column('status', 'Status')
    ->format(function ($value) {
        $escaped = e($value);
        return "<span class='badge'>{$escaped}</span>";
    });

// ✅ SAFE - Use Blade components
$table->column('status', 'Status')
    ->component('badge', ['color' => 'status']);
```

### Form Input Escaping

```php
use Canvastack\Components\Form\FormBuilder;

$form = new FormBuilder();

// ✅ SAFE - Input values are automatically escaped
$form->text('name', 'Name')
    ->value(old('name', $user->name));

// ✅ SAFE - Attributes are escaped
$form->text('email', 'Email')
    ->placeholder('Enter your email')
    ->class('form-control');
```

### JavaScript Context

```blade
{{-- ✅ SAFE - Use @json directive --}}
<script>
    const user = @json($user);
    const config = @json($config);
</script>

{{-- ❌ DANGEROUS - Direct interpolation --}}
<script>
    const user = {!! json_encode($user) !!};
</script>
```

### Content Security Policy

Add CSP headers to prevent XSS:

```php
// app/Http/Middleware/SecurityHeaders.php
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    $response->headers->set('Content-Security-Policy', 
        "default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
        "style-src 'self' 'unsafe-inline';"
    );
    
    return $response;
}
```

---

## CSRF Protection

### Automatic CSRF Protection

Laravel provides automatic CSRF protection for all POST, PUT, PATCH, DELETE requests:

```blade
{{-- ✅ CSRF token automatically included --}}
<form method="POST" action="/users">
    @csrf
    <input type="text" name="name">
    <button type="submit">Submit</button>
</form>
```

### AJAX Requests

```javascript
// Setup CSRF token for AJAX
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Or use Axios (automatically includes CSRF token)
axios.post('/users', {
    name: 'John Doe'
});
```

### Form Component

```php
// CSRF token automatically included
$form = new FormBuilder();
$form->text('name', 'Name');
$form->sync(); // Includes @csrf token
```

### API Endpoints

For API endpoints, use Laravel Sanctum:

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users', [UserController::class, 'store']);
});
```

---

## Mass Assignment Protection

### Model Protection

```php
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    // ✅ Whitelist fillable attributes
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
    
    // ✅ Or blacklist guarded attributes
    protected $guarded = [
        'id',
        'is_admin',
        'created_at',
        'updated_at',
    ];
}
```

### Controller Validation

```php
public function store(Request $request)
{
    // ✅ Validate and filter input
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:8',
    ]);
    
    // Only validated fields are used
    User::create($validated);
}
```

### Form Component

```php
// Form component only submits defined fields
$form->text('name', 'Name');
$form->text('email', 'Email');
$form->password('password', 'Password');

// Hidden fields like 'is_admin' cannot be injected
```

---

## Authentication & Authorization

### Authentication

```php
// Check if user is authenticated
if (auth()->check()) {
    // User is logged in
}

// Get authenticated user
$user = auth()->user();

// Require authentication
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

### Authorization (RBAC)

```php
// Check user permissions
if (auth()->user()->can('edit-users')) {
    // User has permission
}

// In Blade templates
@can('edit-users')
    <button>Edit User</button>
@endcan

// In controllers
public function edit(User $user)
{
    $this->authorize('update', $user);
    
    // User is authorized
}
```

### Table Component Authorization

```php
// Show actions based on permissions
$table->action('edit', 'Edit')
    ->can('update', 'user');

$table->action('delete', 'Delete')
    ->can('delete', 'user');
```

### Form Component Authorization

```php
// Disable fields based on permissions
$form->text('name', 'Name')
    ->disabledUnless(auth()->user()->can('edit-users'));
```

---

## Input Validation

### Form Validation

```php
use Canvastack\Components\Form\FormBuilder;

$form = new FormBuilder();

// ✅ Comprehensive validation
$form->text('email', 'Email')
    ->required()
    ->email()
    ->maxLength(255)
    ->unique('users', 'email');

$form->text('phone', 'Phone')
    ->required()
    ->regex('/^[0-9]{10,15}$/');

$form->number('age', 'Age')
    ->required()
    ->min(18)
    ->max(120);
```

### Custom Validation Rules

```php
// Create custom rule
use Illuminate\Contracts\Validation\Rule;

class StrongPassword implements Rule
{
    public function passes($attribute, $value)
    {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $value);
    }
    
    public function message()
    {
        return 'Password must contain uppercase, lowercase, and numbers.';
    }
}

// Use custom rule
$form->password('password', 'Password')
    ->rule(new StrongPassword());
```

### Sanitization

```php
// Sanitize input
$form->text('name', 'Name')
    ->sanitize(function ($value) {
        return strip_tags($value);
    });

// Or use Laravel's built-in sanitization
$form->text('slug', 'Slug')
    ->sanitize(function ($value) {
        return Str::slug($value);
    });
```

---

## Output Escaping

### Blade Templates

```blade
{{-- ✅ SAFE - Escaped output --}}
{{ $user->name }}
{{ $user->bio }}

{{-- ✅ SAFE - Escaped in attributes --}}
<input type="text" value="{{ $user->name }}">
<div data-user="{{ $user->id }}">

{{-- ❌ DANGEROUS - Only use for trusted content --}}
{!! $trustedHtml !!}
```

### JavaScript Context

```blade
{{-- ✅ SAFE - Use @json --}}
<script>
    const user = @json($user);
</script>

{{-- ✅ SAFE - Escape in strings --}}
<script>
    const name = "{{ addslashes($user->name) }}";
</script>
```

### URL Context

```blade
{{-- ✅ SAFE - Use url() helper --}}
<a href="{{ url('/users/' . $user->id) }}">View</a>

{{-- ✅ SAFE - Use route() helper --}}
<a href="{{ route('users.show', $user) }}">View</a>
```

---

## Security Headers

### Recommended Headers

```php
// app/Http/Middleware/SecurityHeaders.php
namespace App\Http\Middleware;

use Closure;

class SecurityHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        
        // Prevent MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // Enable XSS protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Content Security Policy
        $response->headers->set('Content-Security-Policy', 
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
            "style-src 'self' 'unsafe-inline';"
        );
        
        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // HTTPS only (in production)
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        
        return $response;
    }
}
```

### Register Middleware

```php
// app/Http/Kernel.php
protected $middleware = [
    // ...
    \App\Http\Middleware\SecurityHeaders::class,
];
```

---

## Rate Limiting

### API Rate Limiting

```php
// routes/api.php
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

// Apply to routes
Route::middleware('throttle:api')->group(function () {
    Route::post('/users', [UserController::class, 'store']);
});
```

### Login Rate Limiting

```php
// Limit login attempts
use Illuminate\Support\Facades\RateLimiter;

public function login(Request $request)
{
    $key = 'login.' . $request->ip();
    
    if (RateLimiter::tooManyAttempts($key, 5)) {
        $seconds = RateLimiter::availableIn($key);
        return back()->withErrors([
            'email' => "Too many login attempts. Please try again in {$seconds} seconds."
        ]);
    }
    
    // Attempt login
    if (Auth::attempt($request->only('email', 'password'))) {
        RateLimiter::clear($key);
        return redirect('/dashboard');
    }
    
    RateLimiter::hit($key, 60); // Lock for 60 seconds after 5 attempts
    
    return back()->withErrors(['email' => 'Invalid credentials']);
}
```

---

## Audit Logging

### Log User Actions

```php
// app/Models/AuditLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];
    
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];
}
```

### Automatic Logging

```php
// app/Observers/UserObserver.php
namespace App\Observers;

use App\Models\AuditLog;

class UserObserver
{
    public function updated(User $user)
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'updated',
            'model_type' => User::class,
            'model_id' => $user->id,
            'old_values' => $user->getOriginal(),
            'new_values' => $user->getAttributes(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
    
    public function deleted(User $user)
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'deleted',
            'model_type' => User::class,
            'model_id' => $user->id,
            'old_values' => $user->getAttributes(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

### Register Observer

```php
// app/Providers/AppServiceProvider.php
use App\Models\User;
use App\Observers\UserObserver;

public function boot()
{
    User::observe(UserObserver::class);
}
```

---

## Security Best Practices

### 1. Keep Dependencies Updated

```bash
# Update Composer dependencies
composer update

# Check for security vulnerabilities
composer audit

# Update npm packages
npm update
npm audit fix
```

### 2. Use Environment Variables

```env
# .env - Never commit to version control
APP_KEY=base64:...
DB_PASSWORD=secret
REDIS_PASSWORD=secret
```

### 3. Disable Debug Mode in Production

```env
# Production .env
APP_DEBUG=false
APP_ENV=production
```

### 4. Use HTTPS

```php
// Force HTTPS in production
if (app()->environment('production')) {
    URL::forceScheme('https');
}
```

### 5. Secure File Uploads

```php
public function upload(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:jpg,png,pdf|max:2048',
    ]);
    
    $path = $request->file('file')->store('uploads', 'private');
    
    return response()->json(['path' => $path]);
}
```

### 6. Sanitize User Input

```php
// Always validate and sanitize
$validated = $request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email',
]);

$user = User::create([
    'name' => strip_tags($validated['name']),
    'email' => $validated['email'],
]);
```

### 7. Use Prepared Statements

```php
// ✅ SAFE - Parameterized query
DB::select('SELECT * FROM users WHERE email = ?', [$email]);

// ❌ DANGEROUS - String concatenation
DB::select("SELECT * FROM users WHERE email = '{$email}'");
```

### 8. Implement Password Policies

```php
$form->password('password', 'Password')
    ->required()
    ->minLength(8)
    ->regex('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/')
    ->confirmed();
```

---

## Security Checklist

### Development

- [ ] All user input is validated
- [ ] All output is escaped
- [ ] SQL queries use parameterized statements
- [ ] CSRF protection is enabled
- [ ] Mass assignment protection is configured
- [ ] File uploads are validated and sanitized
- [ ] Passwords are hashed (bcrypt)
- [ ] Sensitive data is not logged

### Production

- [ ] Debug mode is disabled (`APP_DEBUG=false`)
- [ ] HTTPS is enforced
- [ ] Security headers are configured
- [ ] Rate limiting is enabled
- [ ] Audit logging is enabled
- [ ] Dependencies are up to date
- [ ] Environment variables are secured
- [ ] Database credentials are strong
- [ ] Backups are encrypted
- [ ] Error pages don't leak information

### Monitoring

- [ ] Failed login attempts are logged
- [ ] Suspicious activity is monitored
- [ ] Security logs are reviewed regularly
- [ ] Vulnerability scans are performed
- [ ] Penetration testing is conducted
- [ ] Security patches are applied promptly

---

## See Also

- [Input Validation](../components/form/validation.md)
- [RBAC System](../architecture/rbac.md)
- [Best Practices](../guides/best-practices.md)
- [Deployment Guide](../guides/deployment.md)

---

**Next**: [Performance Optimization](performance.md)
