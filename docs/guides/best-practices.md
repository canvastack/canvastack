# Best Practices Guide

**Version**: 1.0.0  
**Last Updated**: 2026-02-26  
**Status**: Complete

---

## Table of Contents

1. [Overview](#overview)
2. [Code Organization](#code-organization)
3. [Performance Best Practices](#performance-best-practices)
4. [Security Best Practices](#security-best-practices)
5. [Database Best Practices](#database-best-practices)
6. [Caching Best Practices](#caching-best-practices)
7. [Testing Best Practices](#testing-best-practices)
8. [Error Handling](#error-handling)
9. [API Design](#api-design)
10. [Documentation](#documentation)
11. [Version Control](#version-control)
12. [Common Pitfalls](#common-pitfalls)

---

## Overview

This guide provides best practices for developing with CanvaStack, covering code quality, performance, security, and maintainability.

### Core Principles

1. **Simplicity**: Write simple, readable code
2. **Performance**: Optimize for speed and efficiency
3. **Security**: Follow security best practices
4. **Maintainability**: Write code that's easy to maintain
5. **Testing**: Test thoroughly and continuously
6. **Documentation**: Document clearly and completely

---

## Code Organization

### 1. Follow PSR-12 Standards

```php
// ✅ GOOD - PSR-12 compliant
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::paginate(15);
        
        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }
}

// ❌ BAD - Not PSR-12 compliant
<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class UserController extends Controller {
    public function index(Request $request) {
        $users=User::paginate(15);
        return response()->json(['success'=>true,'data'=>$users]);
    }
}
```

### 2. Use Type Declarations

```php
// ✅ GOOD - Type declarations
public function createUser(string $name, string $email, int $age): User
{
    return User::create([
        'name' => $name,
        'email' => $email,
        'age' => $age,
    ]);
}

// ❌ BAD - No type declarations
public function createUser($name, $email, $age)
{
    return User::create([
        'name' => $name,
        'email' => $email,
        'age' => $age,
    ]);
}
```

### 3. Use Dependency Injection

```php
// ✅ GOOD - Dependency injection
class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private CacheManager $cache
    ) {}
    
    public function getUsers(): Collection
    {
        return $this->cache->remember('users', 300, function () {
            return $this->userRepository->all();
        });
    }
}

// ❌ BAD - Direct instantiation
class UserService
{
    public function getUsers(): Collection
    {
        $repository = new UserRepository();
        $cache = new CacheManager();
        
        return $cache->remember('users', 300, function () use ($repository) {
            return $repository->all();
        });
    }
}
```

### 4. Single Responsibility Principle

```php
// ✅ GOOD - Single responsibility
class UserCreator
{
    public function create(array $data): User
    {
        return User::create($data);
    }
}

class UserNotifier
{
    public function sendWelcomeEmail(User $user): void
    {
        Mail::to($user)->send(new WelcomeEmail($user));
    }
}

// ❌ BAD - Multiple responsibilities
class UserManager
{
    public function createAndNotify(array $data): User
    {
        $user = User::create($data);
        Mail::to($user)->send(new WelcomeEmail($user));
        return $user;
    }
}
```

### 5. Use Meaningful Names

```php
// ✅ GOOD - Descriptive names
$activeUsers = User::where('status', 'active')->get();
$totalRevenue = Order::sum('amount');
$isUserAdmin = $user->hasRole('admin');

// ❌ BAD - Unclear names
$u = User::where('status', 'active')->get();
$t = Order::sum('amount');
$x = $user->hasRole('admin');
```

---

## Performance Best Practices

### 1. Always Use Eager Loading

```php
// ✅ GOOD - Eager loading
$users = User::with(['posts', 'comments'])->get();

foreach ($users as $user) {
    echo $user->posts->count(); // No additional query
}

// ❌ BAD - N+1 queries
$users = User::all();

foreach ($users as $user) {
    echo $user->posts->count(); // N additional queries
}
```

### 2. Select Only Needed Columns

```php
// ✅ GOOD - Select specific columns
$users = User::select(['id', 'name', 'email'])->get();

// ❌ BAD - Select all columns
$users = User::all();
```

### 3. Use Chunking for Large Datasets

```php
// ✅ GOOD - Process in chunks
User::chunk(100, function ($users) {
    foreach ($users as $user) {
        // Process user
    }
});

// ❌ BAD - Load all into memory
$users = User::all();
foreach ($users as $user) {
    // Process user
}
```

### 4. Enable Caching

```php
// ✅ GOOD - Use caching
$table = new TableBuilder();
$table->cache(300);
$table->eager(['posts', 'comments']);

// ❌ BAD - No caching
$table = new TableBuilder();
// No caching, no eager loading
```

### 5. Use Database Indexes

```php
// ✅ GOOD - Add indexes
Schema::table('users', function (Blueprint $table) {
    $table->index('email');
    $table->index('status');
    $table->index(['status', 'created_at']);
});

// ❌ BAD - No indexes on frequently queried columns
```

---

## Security Best Practices

### 1. Always Validate Input

```php
// ✅ GOOD - Validate all input
$validated = $request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
    'password' => 'required|min:8|confirmed',
]);

User::create($validated);

// ❌ BAD - No validation
User::create($request->all());
```

### 2. Use Parameterized Queries

```php
// ✅ GOOD - Parameterized query
$users = DB::select('SELECT * FROM users WHERE email = ?', [$email]);

// ❌ BAD - String concatenation (SQL injection risk)
$users = DB::select("SELECT * FROM users WHERE email = '{$email}'");
```

### 3. Escape Output

```blade
{{-- ✅ GOOD - Escaped output --}}
<div>{{ $user->name }}</div>
<div>{{ $user->bio }}</div>

{{-- ❌ BAD - Unescaped output (XSS risk) --}}
<div>{!! $user->name !!}</div>
<div>{!! $user->bio !!}</div>
```

### 4. Use Mass Assignment Protection

```php
// ✅ GOOD - Protected attributes
class User extends Model
{
    protected $fillable = ['name', 'email', 'password'];
    
    protected $guarded = ['id', 'is_admin'];
}

// ❌ BAD - No protection
class User extends Model
{
    protected $guarded = [];
}
```

### 5. Implement Rate Limiting

```php
// ✅ GOOD - Rate limiting
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// ❌ BAD - No rate limiting
Route::post('/login', [AuthController::class, 'login']);
```

---

## Database Best Practices

### 1. Use Transactions

```php
// ✅ GOOD - Use transactions
DB::transaction(function () {
    $user = User::create($userData);
    $profile = Profile::create($profileData);
    $user->profile()->associate($profile);
    $user->save();
});

// ❌ BAD - No transaction
$user = User::create($userData);
$profile = Profile::create($profileData);
$user->profile()->associate($profile);
$user->save();
```

### 2. Use Migrations

```php
// ✅ GOOD - Use migrations
php artisan make:migration create_users_table

// In migration:
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamps();
});

// ❌ BAD - Manual SQL
CREATE TABLE users (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255)
);
```

### 3. Use Eloquent Relationships

```php
// ✅ GOOD - Use relationships
class User extends Model
{
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

$user->posts; // Eloquent handles the query

// ❌ BAD - Manual queries
$posts = Post::where('user_id', $user->id)->get();
```

### 4. Use Query Scopes

```php
// ✅ GOOD - Use scopes
class User extends Model
{
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDays(7));
    }
}

$activeUsers = User::active()->get();
$recentUsers = User::recent()->get();

// ❌ BAD - Repeat queries
$activeUsers = User::where('status', 'active')->get();
$recentUsers = User::where('created_at', '>=', now()->subDays(7))->get();
```

---

## Caching Best Practices

### 1. Use Appropriate TTL

```php
// ✅ GOOD - Appropriate TTL
Cache::remember('users', 300, function () {
    return User::all(); // 5 minutes for frequently changing data
});

Cache::remember('settings', 3600, function () {
    return Setting::all(); // 1 hour for rarely changing data
});

// ❌ BAD - Same TTL for everything
Cache::remember('users', 86400, function () {
    return User::all(); // 24 hours too long for user data
});
```

### 2. Use Cache Tags

```php
// ✅ GOOD - Use tags
Cache::tags(['users', 'admin'])->put('users_list', $users, 300);

// Invalidate all user cache
Cache::tags(['users'])->flush();

// ❌ BAD - No tags
Cache::put('users_list', $users, 300);
Cache::forget('users_list'); // Must know exact key
```

### 3. Cache Expensive Operations

```php
// ✅ GOOD - Cache expensive operations
$stats = Cache::remember('dashboard_stats', 3600, function () {
    return [
        'total_users' => User::count(),
        'active_users' => User::where('status', 'active')->count(),
        'total_revenue' => Order::sum('amount'),
    ];
});

// ❌ BAD - No caching
$stats = [
    'total_users' => User::count(),
    'active_users' => User::where('status', 'active')->count(),
    'total_revenue' => Order::sum('amount'),
];
```

### 4. Invalidate Cache Strategically

```php
// ✅ GOOD - Invalidate only affected cache
User::saved(function ($user) {
    Cache::forget("user_{$user->id}");
    Cache::tags(['users'])->flush();
});

// ❌ BAD - Clear all cache
User::saved(function ($user) {
    Cache::flush(); // Clears everything!
});
```

---

## Testing Best Practices

### 1. Write Descriptive Test Names

```php
// ✅ GOOD - Descriptive names
public function test_user_can_create_post_with_valid_data(): void
{
    // Test code
}

public function test_user_cannot_create_post_without_title(): void
{
    // Test code
}

// ❌ BAD - Unclear names
public function test_create(): void
{
    // Test code
}

public function test_validation(): void
{
    // Test code
}
```

### 2. Use Arrange-Act-Assert Pattern

```php
// ✅ GOOD - Clear structure
public function test_user_can_update_profile(): void
{
    // Arrange
    $user = User::factory()->create();
    $newData = ['name' => 'New Name'];
    
    // Act
    $response = $this->actingAs($user)->put("/profile", $newData);
    
    // Assert
    $response->assertStatus(200);
    $this->assertEquals('New Name', $user->fresh()->name);
}

// ❌ BAD - Mixed structure
public function test_update(): void
{
    $user = User::factory()->create();
    $response = $this->actingAs($user)->put("/profile", ['name' => 'New Name']);
    $this->assertEquals('New Name', $user->fresh()->name);
    $response->assertStatus(200);
}
```

### 3. Test One Thing Per Test

```php
// ✅ GOOD - One assertion per test
public function test_user_can_login_with_valid_credentials(): void
{
    $user = User::factory()->create();
    
    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);
    
    $response->assertStatus(200);
}

public function test_user_is_redirected_after_login(): void
{
    $user = User::factory()->create();
    
    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);
    
    $response->assertRedirect('/dashboard');
}

// ❌ BAD - Multiple unrelated assertions
public function test_login(): void
{
    $user = User::factory()->create();
    
    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);
    
    $response->assertStatus(200);
    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
    $this->assertEquals(1, $user->login_count);
}
```

### 4. Use Factories

```php
// ✅ GOOD - Use factories
$user = User::factory()->create();
$posts = Post::factory()->count(5)->create(['user_id' => $user->id]);

// ❌ BAD - Manual creation
$user = User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => Hash::make('password'),
]);
```

---

## Error Handling

### 1. Use Try-Catch Blocks

```php
// ✅ GOOD - Handle exceptions
public function processPayment(Order $order): bool
{
    try {
        $payment = PaymentGateway::charge($order->amount);
        $order->update(['status' => 'paid']);
        return true;
    } catch (PaymentException $e) {
        Log::error('Payment failed', [
            'order_id' => $order->id,
            'error' => $e->getMessage(),
        ]);
        return false;
    }
}

// ❌ BAD - No error handling
public function processPayment(Order $order): bool
{
    $payment = PaymentGateway::charge($order->amount);
    $order->update(['status' => 'paid']);
    return true;
}
```

### 2. Log Errors Appropriately

```php
// ✅ GOOD - Contextual logging
try {
    $user = User::findOrFail($id);
} catch (ModelNotFoundException $e) {
    Log::warning('User not found', [
        'user_id' => $id,
        'ip' => request()->ip(),
        'url' => request()->fullUrl(),
    ]);
    abort(404);
}

// ❌ BAD - Generic logging
try {
    $user = User::findOrFail($id);
} catch (ModelNotFoundException $e) {
    Log::error('Error');
    abort(404);
}
```

### 3. Return Consistent Error Responses

```php
// ✅ GOOD - Consistent format
public function store(Request $request): JsonResponse
{
    try {
        $user = User::create($request->validated());
        
        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'User created successfully',
        ], 201);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create user',
            'error' => $e->getMessage(),
        ], 500);
    }
}

// ❌ BAD - Inconsistent format
public function store(Request $request)
{
    try {
        $user = User::create($request->all());
        return ['user' => $user];
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}
```

---

## API Design

### 1. Use RESTful Conventions

```php
// ✅ GOOD - RESTful routes
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);

// ❌ BAD - Non-RESTful routes
Route::get('/get-users', [UserController::class, 'getUsers']);
Route::post('/create-user', [UserController::class, 'createUser']);
Route::post('/update-user', [UserController::class, 'updateUser']);
```

### 2. Version Your API

```php
// ✅ GOOD - Versioned API
Route::prefix('api/v1')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
});

Route::prefix('api/v2')->group(function () {
    Route::get('/users', [UserV2Controller::class, 'index']);
});

// ❌ BAD - No versioning
Route::prefix('api')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
});
```

### 3. Use Resource Controllers

```php
// ✅ GOOD - Resource controller
Route::apiResource('users', UserController::class);

// ❌ BAD - Manual routes
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
// ... repeat for all methods
```

---

## Documentation

### 1. Write Clear Comments

```php
// ✅ GOOD - Clear documentation
/**
 * Create a new user account.
 *
 * @param array $data User data including name, email, and password
 * @return User The created user instance
 * @throws ValidationException If validation fails
 */
public function createUser(array $data): User
{
    return User::create($data);
}

// ❌ BAD - No documentation
public function createUser(array $data): User
{
    return User::create($data);
}
```

### 2. Document Complex Logic

```php
// ✅ GOOD - Explain complex logic
/**
 * Calculate user discount based on loyalty points and purchase history.
 * 
 * Discount tiers:
 * - Bronze (0-999 points): 5% discount
 * - Silver (1000-4999 points): 10% discount
 * - Gold (5000+ points): 15% discount
 * 
 * Additional 5% for users with 10+ purchases in last 30 days.
 */
public function calculateDiscount(User $user): float
{
    $baseDiscount = match (true) {
        $user->points >= 5000 => 0.15,
        $user->points >= 1000 => 0.10,
        default => 0.05,
    };
    
    $recentPurchases = $user->orders()
        ->where('created_at', '>=', now()->subDays(30))
        ->count();
    
    $bonusDiscount = $recentPurchases >= 10 ? 0.05 : 0;
    
    return $baseDiscount + $bonusDiscount;
}

// ❌ BAD - No explanation
public function calculateDiscount(User $user): float
{
    $d = $user->points >= 5000 ? 0.15 : ($user->points >= 1000 ? 0.10 : 0.05);
    $p = $user->orders()->where('created_at', '>=', now()->subDays(30))->count();
    return $d + ($p >= 10 ? 0.05 : 0);
}
```

---

## Version Control

### 1. Write Meaningful Commit Messages

```bash
# ✅ GOOD - Descriptive commits
git commit -m "Add user authentication with email verification"
git commit -m "Fix N+1 query in user dashboard"
git commit -m "Update table component to support caching"

# ❌ BAD - Vague commits
git commit -m "Update"
git commit -m "Fix bug"
git commit -m "Changes"
```

### 2. Use Feature Branches

```bash
# ✅ GOOD - Feature branches
git checkout -b feature/user-authentication
git checkout -b fix/table-performance
git checkout -b refactor/cache-layer

# ❌ BAD - Work directly on main
git checkout main
# Make changes directly
```

### 3. Keep Commits Atomic

```bash
# ✅ GOOD - One feature per commit
git add app/Http/Controllers/UserController.php
git commit -m "Add user registration endpoint"

git add tests/Feature/UserRegistrationTest.php
git commit -m "Add tests for user registration"

# ❌ BAD - Multiple unrelated changes
git add .
git commit -m "Various updates"
```

---

## Common Pitfalls

### 1. N+1 Query Problem

```php
// ❌ PROBLEM
$users = User::all();
foreach ($users as $user) {
    echo $user->posts->count(); // N+1 queries
}

// ✅ SOLUTION
$users = User::withCount('posts')->get();
foreach ($users as $user) {
    echo $user->posts_count; // No additional queries
}
```

### 2. Not Using Transactions

```php
// ❌ PROBLEM
$user = User::create($userData);
$profile = Profile::create($profileData);
// If this fails, user is created but profile is not

// ✅ SOLUTION
DB::transaction(function () use ($userData, $profileData) {
    $user = User::create($userData);
    $profile = Profile::create($profileData);
});
```

### 3. Forgetting to Cache

```php
// ❌ PROBLEM
public function dashboard()
{
    $stats = [
        'users' => User::count(),
        'posts' => Post::count(),
        'revenue' => Order::sum('amount'),
    ];
    // Queries run on every request
}

// ✅ SOLUTION
public function dashboard()
{
    $stats = Cache::remember('dashboard_stats', 3600, function () {
        return [
            'users' => User::count(),
            'posts' => Post::count(),
            'revenue' => Order::sum('amount'),
        ];
    });
}
```

### 4. Not Validating Input

```php
// ❌ PROBLEM
public function store(Request $request)
{
    User::create($request->all()); // No validation!
}

// ✅ SOLUTION
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
    ]);
    
    User::create($validated);
}
```

---

## See Also

- [Performance Optimization](../features/performance.md)
- [Security Features](../features/security.md)
- [Caching System](../features/caching.md)
- [Testing Guide](testing.md)
- [Deployment Guide](deployment.md)

---

**Summary**: Follow these best practices to write clean, performant, secure, and maintainable code with CanvaStack.
