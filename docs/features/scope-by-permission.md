# scopeByPermission Query Scope

## Overview

The `scopeByPermission()` query scope provides row-level permission filtering for Eloquent models. It automatically filters query results based on the user's permissions and configured permission rules.

## Features

- Automatic row-level filtering based on permission rules
- Template variable support (e.g., `{{auth.id}}`, `{{auth.department}}`)
- Chainable with other Eloquent scopes
- Optimized query performance
- Works with pagination, sorting, and other query builders

## Installation

### Step 1: Add Trait to Model

Add the `HasPermissionScopes` trait to your Eloquent model:

```php
use Canvastack\Canvastack\Auth\RBAC\Traits\HasPermissionScopes;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasPermissionScopes;
    
    // ... rest of your model
}
```

### Step 2: Configure Permission Rules

Create row-level permission rules using the PermissionRuleManager:

```php
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;

$ruleManager = app(PermissionRuleManager::class);

// Add row-level rule: users can only view their own posts
$ruleManager->addRowRule(
    $permissionId,
    Post::class,
    ['user_id' => '{{auth.id}}'],
    'AND'
);
```

## Usage

### Basic Usage

```php
// Get all posts the user can view
$posts = Post::byPermission(auth()->id(), 'posts.view')->get();
```

### Chaining with Other Scopes

```php
// Filter by permission and status
$posts = Post::byPermission(auth()->id(), 'posts.view')
    ->where('status', 'published')
    ->latest()
    ->get();
```

### With Pagination

```php
// Paginate results with permission filtering
$posts = Post::byPermission(auth()->id(), 'posts.view')
    ->paginate(15);
```

### With Sorting

```php
// Sort results with permission filtering
$posts = Post::byPermission(auth()->id(), 'posts.view')
    ->orderBy('created_at', 'desc')
    ->get();
```

### With Relationships

```php
// Eager load relationships with permission filtering
$posts = Post::byPermission(auth()->id(), 'posts.view')
    ->with(['author', 'comments'])
    ->get();
```

## Permission Rule Examples

### Single Condition

```php
// Users can only view their own posts
$ruleManager->addRowRule(
    $permissionId,
    Post::class,
    ['user_id' => '{{auth.id}}'],
    'AND'
);
```

### Multiple Conditions (AND)

```php
// Users can only view posts in their department
$ruleManager->addRowRule(
    $permissionId,
    Post::class,
    [
        'user_id' => '{{auth.id}}',
        'department_id' => '{{auth.department}}'
    ],
    'AND'
);
```

### Multiple Conditions (OR)

```php
// Users can view posts they created OR posts in their department
$ruleManager->addRowRule(
    $permissionId,
    Post::class,
    [
        'user_id' => '{{auth.id}}',
        'department_id' => '{{auth.department}}'
    ],
    'OR'
);
```

## Template Variables

The following template variables are available by default:

- `{{auth.id}}` - Current user's ID
- `{{auth.role}}` - Current user's role
- `{{auth.department}}` - Current user's department ID
- `{{auth.email}}` - Current user's email

### Custom Template Variables

You can register custom template variables:

```php
use Canvastack\Canvastack\Auth\RBAC\TemplateVariableResolver;

$resolver = app(TemplateVariableResolver::class);

$resolver->register('auth.team', function() {
    return auth()->user()->team_id;
});
```

## Performance Considerations

### Caching

Permission rule evaluations are automatically cached for 3600 seconds (1 hour). The cache is invalidated when:

- Permission rules are created/updated/deleted
- User overrides are created/updated/deleted
- Cache is manually cleared

### Query Optimization

The scope applies filters directly to the SQL query, ensuring optimal performance:

```sql
-- Without scope
SELECT * FROM posts;

-- With scope (user_id = 1)
SELECT * FROM posts WHERE user_id = 1;
```

### Eager Loading

Always use eager loading when working with relationships:

```php
// Good - eager loading
$posts = Post::byPermission(auth()->id(), 'posts.view')
    ->with(['author', 'comments'])
    ->get();

// Bad - N+1 queries
$posts = Post::byPermission(auth()->id(), 'posts.view')->get();
foreach ($posts as $post) {
    echo $post->author->name; // N+1 query
}
```

## Behavior

### No Rules Defined

If no permission rules are defined for a permission, the scope returns all records:

```php
// No rules defined for 'posts.view'
$posts = Post::byPermission(auth()->id(), 'posts.view')->get();
// Returns all posts
```

### Permission Not Found

If the permission doesn't exist, the scope returns an empty result:

```php
// Permission 'nonexistent.permission' doesn't exist
$posts = Post::byPermission(auth()->id(), 'nonexistent.permission')->get();
// Returns empty collection
```

### Fine-Grained Permissions Disabled

If fine-grained permissions are disabled in configuration, the scope returns all records:

```php
// config/canvastack-rbac.php
'fine_grained' => [
    'enabled' => false, // Disabled
],

// Scope returns all records
$posts = Post::byPermission(auth()->id(), 'posts.view')->get();
```

## Testing

### Unit Tests

```php
public function test_scope_by_permission_filters_by_user_id()
{
    // Create test data
    Post::create(['title' => 'Post 1', 'user_id' => 1]);
    Post::create(['title' => 'Post 2', 'user_id' => 2]);
    
    // Create permission rule
    $ruleManager->addRowRule(
        $permissionId,
        Post::class,
        ['user_id' => '{{auth.id}}'],
        'AND'
    );
    
    // Test scope
    $posts = Post::byPermission(1, 'posts.view')->get();
    
    $this->assertCount(1, $posts);
    $this->assertEquals('Post 1', $posts[0]->title);
}
```

## API Reference

### scopeByPermission()

```php
public function scopeByPermission(
    Builder $query,
    int $userId,
    string $permission
): Builder
```

**Parameters:**
- `$query` (Builder) - The query builder instance
- `$userId` (int) - The user ID to check permissions for
- `$permission` (string) - The permission name (e.g., 'posts.view')

**Returns:**
- `Builder` - The modified query builder with permission filters applied

**Example:**
```php
$posts = Post::byPermission(auth()->id(), 'posts.view')->get();
```

## Related Documentation

- [Permission Rule Manager](../api/permission-rule-manager.md)
- [Row-Level Permissions](./row-level-permissions.md)
- [RBAC System](./rbac.md)
- [Template Variables](./template-variables.md)

---

**Last Updated**: 2026-02-28  
**Version**: 1.0.0  
**Status**: Published
