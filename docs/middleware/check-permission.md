# CheckPermission Middleware

## 📦 Location

- **File Location**: `packages/canvastack/canvastack/src/Auth/Middleware/CheckPermission.php`
- **Test Location**: `packages/canvastack/canvastack/tests/Unit/Auth/Middleware/CheckPermissionTest.php`
- **Related Files**: 
  - `Gate.php` - Authorization gate
  - `AuthenticateAdmin.php` - Admin authentication
  - `AuthenticatePublic.php` - Public authentication

## 🎯 Features

- Single or multiple permission checks
- AND/OR logic for multiple permissions
- Context-aware permission checking (admin, public, api)
- Custom guard support
- Descriptive error messages
- Integration with RBAC Gate system

## 📖 Basic Usage

### Single Permission

```php
// In routes/web.php
Route::get('/users', [UserController::class, 'index'])
    ->middleware(['auth', 'permission:users.view']);
```

### Multiple Permissions (AND Logic)

```php
// User must have ALL permissions
Route::post('/users', [UserController::class, 'store'])
    ->middleware(['auth', 'permission:users.view,users.create']);
```

### Multiple Permissions (OR Logic)

```php
// User must have ANY permission
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'permission:admin.dashboard,moderator.dashboard,or']);
```

## 🔧 Parameters

| Parameter | Type | Default | Options | Description |
|-----------|------|---------|---------|-------------|
| permissions | string | - | - | Comma-separated permission names (e.g., 'users.view' or 'users.view,users.edit') |
| logic | string | 'and' | 'and', 'or' | Logic operator for multiple permissions |
| context | string\|null | null | 'admin', 'public', 'api' | Context for permission check |
| guard | string\|null | null | - | Guard name (defaults to default guard) |

## 📝 Examples

### Example 1: Basic Permission Check

```php
// routes/web.php
Route::middleware(['auth', 'permission:users.view'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
});
```

### Example 2: Multiple Permissions with AND Logic

```php
// User must have both permissions
Route::put('/users/{user}', [UserController::class, 'update'])
    ->middleware(['auth', 'permission:users.view,users.edit,and']);
```

### Example 3: Multiple Permissions with OR Logic

```php
// User must have at least one permission
Route::get('/reports', [ReportController::class, 'index'])
    ->middleware(['auth', 'permission:reports.view,reports.export,or']);
```

### Example 4: Context-Aware Permission Check

```php
// Check permission in admin context
Route::middleware(['auth', 'permission:users.manage,and,admin'])->group(function () {
    Route::resource('admin/users', AdminUserController::class);
});

// Check permission in public context
Route::middleware(['auth', 'permission:posts.create,and,public'])->group(function () {
    Route::post('/posts', [PostController::class, 'store']);
});
```

### Example 5: Custom Guard

```php
// Use API guard
Route::middleware(['auth:api', 'permission:api.access,and,api,api'])->group(function () {
    Route::get('/api/users', [ApiUserController::class, 'index']);
});
```

### Example 6: Complex Route Group

```php
// Admin routes with multiple permission checks
Route::middleware(['auth', 'permission:admin.access,and,admin'])->prefix('admin')->group(function () {
    
    // Users management
    Route::middleware(['permission:users.view'])->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::get('/users/{user}', [AdminUserController::class, 'show']);
    });
    
    // User creation/editing
    Route::middleware(['permission:users.view,users.edit,and'])->group(function () {
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit']);
        Route::put('/users/{user}', [AdminUserController::class, 'update']);
    });
    
    // User deletion (requires both view and delete)
    Route::middleware(['permission:users.view,users.delete,and'])->group(function () {
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);
    });
});
```

### Example 7: Flexible Access Control

```php
// Allow access if user has either admin or moderator role permissions
Route::get('/moderation', [ModerationController::class, 'index'])
    ->middleware(['auth', 'permission:admin.moderate,moderator.moderate,or']);
```

## 🎮 Programmatic Usage

While the middleware is typically used in routes, you can also use it programmatically:

```php
use Canvastack\Canvastack\Auth\Middleware\CheckPermission;
use Illuminate\Http\Request;

class MyController extends Controller
{
    protected CheckPermission $permissionMiddleware;
    
    public function __construct(CheckPermission $permissionMiddleware)
    {
        $this->permissionMiddleware = $permissionMiddleware;
    }
    
    public function index(Request $request)
    {
        // Check permission manually
        $this->permissionMiddleware->handle(
            $request,
            function ($req) {
                // Continue processing
            },
            'users.view'
        );
        
        // Your logic here
    }
}
```

## 🔍 Implementation Details

### Permission Parsing

The middleware parses comma-separated permissions and trims whitespace:

```php
// These are equivalent:
'users.view,users.edit'
'users.view, users.edit'
'users.view , users.edit'
```

### Logic Operators

- **AND Logic** (`and`): User must have ALL specified permissions
- **OR Logic** (`or`): User must have ANY of the specified permissions

### Context-Aware Checks

When a context is provided, the middleware uses `Gate::allowsInContext()` instead of `Gate::hasPermission()`:

```php
// Without context
Gate::hasPermission($user, 'users.view')

// With context
Gate::allowsInContext($user, 'users.view', 'admin')
```

### Error Messages

The middleware provides descriptive error messages:

- Single permission: `"You do not have permission to access this resource. Required permission: users.view"`
- Multiple permissions (AND): `"You do not have permission to access this resource. Required permissions (all): users.view, users.edit"`
- Multiple permissions (OR): `"You do not have permission to access this resource. Required permissions (any): users.view, users.edit"`

## 🎯 Accessibility

The middleware ensures:
- Clear error messages for unauthorized access
- Proper HTTP status codes (403 Forbidden)
- Integration with Laravel's exception handling

## 🎨 Integration with Other Middleware

### Recommended Middleware Stack

```php
// routes/web.php
Route::middleware([
    'web',                                    // Web middleware group
    'auth',                                   // Authentication
    'permission:users.view,and,admin'         // Permission check
])->group(function () {
    // Your routes
});
```

### With Custom Authentication

```php
// routes/api.php
Route::middleware([
    'api',                                    // API middleware group
    'auth:sanctum',                           // Sanctum authentication
    'permission:api.access,and,api,sanctum'   // Permission check with custom guard
])->group(function () {
    // Your API routes
});
```

## 🧪 Testing

### Unit Tests

```php
use Canvastack\Canvastack\Auth\Middleware\CheckPermission;
use Canvastack\Canvastack\Auth\RBAC\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

public function test_allows_access_when_user_has_permission()
{
    $user = $this->createUser();
    $this->grantPermission($user, 'users.view');
    
    $middleware = app(CheckPermission::class);
    $request = Request::create('/test', 'GET');
    
    $this->actingAs($user);
    
    $result = $middleware->handle($request, function ($req) {
        return 'success';
    }, 'users.view');
    
    $this->assertEquals('success', $result);
}

public function test_denies_access_when_user_lacks_permission()
{
    $user = $this->createUser();
    
    $middleware = app(CheckPermission::class);
    $request = Request::create('/test', 'GET');
    
    $this->actingAs($user);
    
    $this->expectException(AuthorizationException::class);
    
    $middleware->handle($request, function ($req) {
        return 'success';
    }, 'users.delete');
}
```

### Feature Tests

```php
public function test_user_can_access_route_with_permission()
{
    $user = $this->createUser();
    $this->grantPermission($user, 'users.view');
    
    $response = $this->actingAs($user)->get('/users');
    
    $response->assertStatus(200);
}

public function test_user_cannot_access_route_without_permission()
{
    $user = $this->createUser();
    
    $response = $this->actingAs($user)->get('/users');
    
    $response->assertStatus(403);
}
```

## 💡 Tips & Best Practices

1. **Always use with authentication middleware** - CheckPermission requires an authenticated user
2. **Use descriptive permission names** - Follow the pattern `resource.action` (e.g., `users.view`, `posts.create`)
3. **Prefer AND logic for sensitive operations** - Require multiple permissions for critical actions
4. **Use OR logic for flexible access** - Allow access if user has any of several permissions
5. **Leverage context-aware checks** - Use different permissions for admin vs public contexts
6. **Group related routes** - Use middleware groups to avoid repetition
7. **Test permission checks** - Always write tests for permission-protected routes

## 🎭 Common Patterns

### Pattern 1: Resource CRUD Permissions

```php
Route::middleware(['auth'])->prefix('users')->group(function () {
    // List/View - requires view permission
    Route::get('/', [UserController::class, 'index'])
        ->middleware('permission:users.view');
    
    Route::get('/{user}', [UserController::class, 'show'])
        ->middleware('permission:users.view');
    
    // Create - requires view and create permissions
    Route::get('/create', [UserController::class, 'create'])
        ->middleware('permission:users.view,users.create,and');
    
    Route::post('/', [UserController::class, 'store'])
        ->middleware('permission:users.view,users.create,and');
    
    // Edit - requires view and edit permissions
    Route::get('/{user}/edit', [UserController::class, 'edit'])
        ->middleware('permission:users.view,users.edit,and');
    
    Route::put('/{user}', [UserController::class, 'update'])
        ->middleware('permission:users.view,users.edit,and');
    
    // Delete - requires view and delete permissions
    Route::delete('/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:users.view,users.delete,and');
});
```

### Pattern 2: Role-Based Route Groups

```php
// Admin routes
Route::middleware(['auth', 'permission:admin.access,and,admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    Route::resource('users', AdminUserController::class);
});

// Moderator routes
Route::middleware(['auth', 'permission:moderator.access,and,admin'])->prefix('moderator')->group(function () {
    Route::get('/dashboard', [ModeratorDashboardController::class, 'index']);
    Route::resource('posts', ModeratorPostController::class);
});

// Public user routes
Route::middleware(['auth', 'permission:user.access,and,public'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
});
```

### Pattern 3: Flexible Access Control

```php
// Allow access if user has either permission
Route::get('/content', [ContentController::class, 'index'])
    ->middleware(['auth', 'permission:content.view,content.moderate,or']);

// Require all permissions for sensitive action
Route::post('/content/publish', [ContentController::class, 'publish'])
    ->middleware(['auth', 'permission:content.edit,content.publish,and']);
```

## 🔗 Related Components / Documentation

- [Gate](../rbac/gate.md) - Authorization gate
- [RoleManager](../rbac/role-manager.md) - Role management
- [PermissionManager](../rbac/permission-manager.md) - Permission management
- [AuthenticateAdmin](./authenticate-admin.md) - Admin authentication
- [AuthenticatePublic](./authenticate-public.md) - Public authentication

## 📚 Resources

- [Laravel Authorization](https://laravel.com/docs/authorization)
- [Laravel Middleware](https://laravel.com/docs/middleware)
- [RBAC Best Practices](../guides/rbac-best-practices.md)

---

**Last Updated**: 2026-03-01  
**Version**: 1.0.0  
**Status**: Published  
**Test Coverage**: 100%
