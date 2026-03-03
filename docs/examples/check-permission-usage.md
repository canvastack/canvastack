# CheckPermission Middleware - Usage Examples

This document provides practical examples of using the CheckPermission middleware in real-world scenarios.

## Basic Examples

### Example 1: Simple Permission Check

```php
// routes/web.php
Route::get('/users', [UserController::class, 'index'])
    ->middleware(['auth', 'permission:users.view']);
```

**What it does**: Only allows authenticated users with the `users.view` permission to access the route.

### Example 2: Multiple Permissions (AND)

```php
Route::post('/users', [UserController::class, 'store'])
    ->middleware(['auth', 'permission:users.view,users.create,and']);
```

**What it does**: Requires the user to have BOTH `users.view` AND `users.create` permissions.

### Example 3: Multiple Permissions (OR)

```php
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'permission:admin.dashboard,moderator.dashboard,or']);
```

**What it does**: Allows access if the user has EITHER `admin.dashboard` OR `moderator.dashboard` permission.

## Real-World Scenarios

### Scenario 1: User Management System

```php
// routes/web.php

// List users - only view permission needed
Route::get('/admin/users', [AdminUserController::class, 'index'])
    ->middleware(['auth', 'permission:users.view,and,admin']);

// View single user - only view permission needed
Route::get('/admin/users/{user}', [AdminUserController::class, 'show'])
    ->middleware(['auth', 'permission:users.view,and,admin']);

// Create user - needs both view and create
Route::get('/admin/users/create', [AdminUserController::class, 'create'])
    ->middleware(['auth', 'permission:users.view,users.create,and,admin']);

Route::post('/admin/users', [AdminUserController::class, 'store'])
    ->middleware(['auth', 'permission:users.view,users.create,and,admin']);

// Edit user - needs both view and edit
Route::get('/admin/users/{user}/edit', [AdminUserController::class, 'edit'])
    ->middleware(['auth', 'permission:users.view,users.edit,and,admin']);

Route::put('/admin/users/{user}', [AdminUserController::class, 'update'])
    ->middleware(['auth', 'permission:users.view,users.edit,and,admin']);

// Delete user - needs both view and delete
Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy'])
    ->middleware(['auth', 'permission:users.view,users.delete,and,admin']);
```

### Scenario 2: Content Management with Moderation

```php
// Public users can view published content
Route::get('/posts', [PostController::class, 'index'])
    ->middleware(['auth', 'permission:posts.view,and,public']);

// Authors can create posts
Route::post('/posts', [PostController::class, 'store'])
    ->middleware(['auth', 'permission:posts.create,and,public']);

// Authors can edit their own posts, moderators can edit any post
Route::put('/posts/{post}', [PostController::class, 'update'])
    ->middleware(['auth', 'permission:posts.edit,posts.moderate,or,public']);

// Only moderators can publish posts
Route::post('/posts/{post}/publish', [PostController::class, 'publish'])
    ->middleware(['auth', 'permission:posts.moderate,and,admin']);

// Only moderators can delete posts
Route::delete('/posts/{post}', [PostController::class, 'destroy'])
    ->middleware(['auth', 'permission:posts.moderate,and,admin']);
```

### Scenario 3: Multi-Context Application

```php
// Admin context - full management
Route::middleware(['auth', 'permission:admin.access,and,admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    
    Route::middleware(['permission:users.manage,and,admin'])->group(function () {
        Route::resource('users', AdminUserController::class);
    });
    
    Route::middleware(['permission:settings.manage,and,admin'])->group(function () {
        Route::resource('settings', AdminSettingsController::class);
    });
});

// Public context - limited access
Route::middleware(['auth', 'permission:user.access,and,public'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update'])
        ->middleware('permission:profile.edit,and,public');
    
    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store'])
        ->middleware('permission:posts.create,and,public');
});

// API context - token-based access
Route::middleware(['auth:sanctum', 'permission:api.access,and,api,sanctum'])->prefix('api')->group(function () {
    Route::get('/users', [ApiUserController::class, 'index'])
        ->middleware('permission:api.users.read,and,api,sanctum');
    
    Route::post('/users', [ApiUserController::class, 'store'])
        ->middleware('permission:api.users.write,and,api,sanctum');
});
```

### Scenario 4: Report System with Flexible Access

```php
// View reports - any user with view or export permission
Route::get('/reports', [ReportController::class, 'index'])
    ->middleware(['auth', 'permission:reports.view,reports.export,or']);

// Generate report - requires view permission
Route::post('/reports/generate', [ReportController::class, 'generate'])
    ->middleware(['auth', 'permission:reports.view']);

// Export report - requires export permission
Route::get('/reports/{report}/export', [ReportController::class, 'export'])
    ->middleware(['auth', 'permission:reports.export']);

// Delete report - requires both view and delete
Route::delete('/reports/{report}', [ReportController::class, 'destroy'])
    ->middleware(['auth', 'permission:reports.view,reports.delete,and']);

// Admin can access all reports
Route::get('/admin/reports', [AdminReportController::class, 'index'])
    ->middleware(['auth', 'permission:admin.reports,and,admin']);
```

### Scenario 5: E-commerce with Role-Based Access

```php
// Customer routes
Route::middleware(['auth', 'permission:customer.access,and,public'])->group(function () {
    Route::get('/shop', [ShopController::class, 'index']);
    Route::post('/cart', [CartController::class, 'add']);
    Route::post('/checkout', [CheckoutController::class, 'process']);
});

// Vendor routes
Route::middleware(['auth', 'permission:vendor.access,and,admin'])->prefix('vendor')->group(function () {
    Route::get('/dashboard', [VendorDashboardController::class, 'index']);
    
    // Manage products
    Route::middleware(['permission:products.manage,and,admin'])->group(function () {
        Route::resource('products', VendorProductController::class);
    });
    
    // View orders
    Route::get('/orders', [VendorOrderController::class, 'index'])
        ->middleware('permission:orders.view,and,admin');
});

// Admin routes
Route::middleware(['auth', 'permission:admin.access,and,admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    
    // Manage all vendors
    Route::middleware(['permission:vendors.manage,and,admin'])->group(function () {
        Route::resource('vendors', AdminVendorController::class);
    });
    
    // Manage all orders
    Route::middleware(['permission:orders.manage,and,admin'])->group(function () {
        Route::resource('orders', AdminOrderController::class);
    });
});
```

## Advanced Patterns

### Pattern 1: Conditional Middleware

```php
// In controller
public function __construct()
{
    // Apply permission middleware conditionally
    $this->middleware('permission:posts.edit,and,public')->only(['edit', 'update']);
    $this->middleware('permission:posts.delete,and,public')->only(['destroy']);
}
```

### Pattern 2: Dynamic Permissions

```php
// In routes
Route::get('/content/{type}', [ContentController::class, 'index'])
    ->middleware(['auth', function ($request, $next) {
        $type = $request->route('type');
        $permission = "content.{$type}.view";
        
        return app(CheckPermission::class)->handle(
            $request,
            $next,
            $permission
        );
    }]);
```

### Pattern 3: Nested Permission Groups

```php
Route::middleware(['auth', 'permission:admin.access,and,admin'])->prefix('admin')->group(function () {
    
    // Level 1: Admin access
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    
    // Level 2: User management
    Route::middleware(['permission:users.view,and,admin'])->prefix('users')->group(function () {
        Route::get('/', [AdminUserController::class, 'index']);
        
        // Level 3: User editing
        Route::middleware(['permission:users.edit,and,admin'])->group(function () {
            Route::get('/{user}/edit', [AdminUserController::class, 'edit']);
            Route::put('/{user}', [AdminUserController::class, 'update']);
        });
        
        // Level 3: User deletion
        Route::middleware(['permission:users.delete,and,admin'])->group(function () {
            Route::delete('/{user}', [AdminUserController::class, 'destroy']);
        });
    });
});
```

## Testing Examples

### Test 1: Basic Permission Check

```php
public function test_user_with_permission_can_access_route()
{
    $user = User::factory()->create();
    $this->grantPermission($user, 'users.view');
    
    $response = $this->actingAs($user)->get('/users');
    
    $response->assertStatus(200);
}

public function test_user_without_permission_cannot_access_route()
{
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)->get('/users');
    
    $response->assertStatus(403);
}
```

### Test 2: Multiple Permissions (AND)

```php
public function test_user_with_all_permissions_can_access_route()
{
    $user = User::factory()->create();
    $this->grantPermission($user, 'users.view');
    $this->grantPermission($user, 'users.edit');
    
    $response = $this->actingAs($user)->put('/users/1', ['name' => 'Updated']);
    
    $response->assertStatus(200);
}

public function test_user_with_partial_permissions_cannot_access_route()
{
    $user = User::factory()->create();
    $this->grantPermission($user, 'users.view');
    // Missing users.edit permission
    
    $response = $this->actingAs($user)->put('/users/1', ['name' => 'Updated']);
    
    $response->assertStatus(403);
}
```

### Test 3: Multiple Permissions (OR)

```php
public function test_user_with_any_permission_can_access_route()
{
    $user = User::factory()->create();
    $this->grantPermission($user, 'admin.dashboard');
    // Don't grant moderator.dashboard
    
    $response = $this->actingAs($user)->get('/dashboard');
    
    $response->assertStatus(200);
}
```

## Common Mistakes to Avoid

### Mistake 1: Forgetting Authentication Middleware

```php
// ❌ WRONG - No authentication
Route::get('/users', [UserController::class, 'index'])
    ->middleware('permission:users.view');

// ✅ CORRECT - Authentication first
Route::get('/users', [UserController::class, 'index'])
    ->middleware(['auth', 'permission:users.view']);
```

### Mistake 2: Wrong Logic Operator

```php
// ❌ WRONG - Using AND when OR is needed
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'permission:admin.dashboard,moderator.dashboard,and']);
// This requires BOTH permissions

// ✅ CORRECT - Using OR for flexible access
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'permission:admin.dashboard,moderator.dashboard,or']);
// This requires ANY permission
```

### Mistake 3: Missing Context

```php
// ❌ WRONG - No context specified
Route::get('/admin/users', [AdminUserController::class, 'index'])
    ->middleware(['auth', 'permission:users.view']);

// ✅ CORRECT - Context specified
Route::get('/admin/users', [AdminUserController::class, 'index'])
    ->middleware(['auth', 'permission:users.view,and,admin']);
```

## Quick Reference

### Middleware Syntax

```
permission:{permissions},{logic},{context},{guard}
```

- `{permissions}`: Comma-separated permission names (required)
- `{logic}`: 'and' or 'or' (optional, default: 'and')
- `{context}`: 'admin', 'public', 'api' (optional)
- `{guard}`: Guard name (optional)

### Examples

```php
// Single permission
'permission:users.view'

// Multiple permissions (AND)
'permission:users.view,users.edit,and'

// Multiple permissions (OR)
'permission:users.view,users.edit,or'

// With context
'permission:users.view,and,admin'

// With context and guard
'permission:api.access,and,api,sanctum'
```

---

**Last Updated**: 2026-03-01  
**Version**: 1.0.0  
**Status**: Published
