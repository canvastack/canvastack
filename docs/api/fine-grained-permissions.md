# Fine-Grained Permissions System - API Reference

## 📋 Overview

This document provides complete API reference for the Fine-Grained Permissions System. All public methods are documented with parameters, return types, exceptions, and usage examples.

**Status**: Published  
**Version**: 1.0.0  
**Last Updated**: 2026-02-27

---

## 📦 Table of Contents

1. [PermissionRuleManager](#permissionrulemanager)
2. [PermissionRule Model](#permissionrule-model)
3. [UserPermissionOverride Model](#userpermissionoverride-model)
4. [Gate Integration](#gate-integration)
5. [Blade Directives](#blade-directives)
6. [FormBuilder Integration](#formbuilder-integration)
7. [TableBuilder Integration](#tablebuilder-integration)
8. [Configuration](#configuration)

---

## PermissionRuleManager

**Namespace**: `Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager`

**Location**: `packages/canvastack/canvastack/src/Auth/RBAC/PermissionRuleManager.php`

The main service class for managing and evaluating fine-grained permission rules.

### Constructor

```php
public function __construct(
    CacheManager $cache,
    RoleManager $roleManager,
    PermissionManager $permissionManager,
    TemplateVariableResolver $templateResolver
)
```

**Parameters**:
- `$cache` - Cache manager instance
- `$roleManager` - Role manager instance
- `$permissionManager` - Permission manager instance
- `$templateResolver` - Template variable resolver instance

**Example**:
```php
$ruleManager = app(PermissionRuleManager::class);
```

---

### Row-Level Methods

#### addRowRule()

Add a row-level permission rule.

```php
public function addRowRule(
    int $permissionId,
    string $modelClass,
    array $conditions,
    string $operator = 'AND'
): PermissionRule
```

**Parameters**:
- `$permissionId` (int) - Permission ID
- `$modelClass` (string) - Fully qualified model class name
- `$conditions` (array) - Associative array of field => value conditions
- `$operator` (string) - Logical operator ('AND' or 'OR'), default: 'AND'

**Returns**: `PermissionRule` - Created rule instance

**Throws**: 
- `InvalidArgumentException` - If model class doesn't exist
- `ValidationException` - If conditions are invalid

**Example**:
```php
// User can only access their own posts
$ruleManager->addRowRule(
    $permissionId,
    Post::class,
    ['user_id' => '{{auth.id}}'],
    'AND'
);

// User can access posts from their department
$ruleManager->addRowRule(
    $permissionId,
    Post::class,
    [
        'department_id' => '{{auth.department}}',
        'status' => 'published'
    ],
    'AND'
);
```

---

#### canAccessRow()

Check if user can access a specific row.

```php
public function canAccessRow(
    int $userId,
    string $permission,
    object $model
): bool
```

**Parameters**:
- `$userId` (int) - User ID
- `$permission` (string) - Permission name (e.g., 'posts.edit')
- `$model` (object) - Model instance to check

**Returns**: `bool` - True if access allowed, false otherwise

**Example**:
```php
$post = Post::find(1);

if ($ruleManager->canAccessRow(auth()->id(), 'posts.edit', $post)) {
    // User can edit this post
}
```

---

#### scopeByPermission()

Apply row-level filtering to a query.

```php
public function scopeByPermission(
    Builder $query,
    int $userId,
    string $permission
): Builder
```

**Parameters**:
- `$query` (Builder) - Eloquent query builder
- `$userId` (int) - User ID
- `$permission` (string) - Permission name

**Returns**: `Builder` - Modified query with row-level filters applied

**Example**:
```php
// Get only posts user can edit
$posts = Post::query()
    ->where('status', 'published')
    ->tap(function($query) use ($ruleManager) {
        $ruleManager->scopeByPermission($query, auth()->id(), 'posts.edit');
    })
    ->get();
```

---

### Column-Level Methods

#### addColumnRule()

Add a column-level permission rule.

```php
public function addColumnRule(
    int $permissionId,
    string $modelClass,
    array $allowedColumns,
    array $deniedColumns = []
): PermissionRule
```

**Parameters**:
- `$permissionId` (int) - Permission ID
- `$modelClass` (string) - Fully qualified model class name
- `$allowedColumns` (array) - Array of allowed column names (whitelist)
- `$deniedColumns` (array) - Array of denied column names (blacklist), default: []

**Returns**: `PermissionRule` - Created rule instance

**Throws**: 
- `InvalidArgumentException` - If model class doesn't exist
- `ValidationException` - If both allowed and denied columns are empty

**Example**:
```php
// Whitelist approach: only allow specific columns
$ruleManager->addColumnRule(
    $permissionId,
    Post::class,
    ['title', 'content', 'excerpt', 'tags']
);

// Blacklist approach: deny specific columns
$ruleManager->addColumnRule(
    $permissionId,
    Post::class,
    [], // empty allowed = allow all
    ['status', 'featured', 'published_at']
);
```

---

#### canAccessColumn()

Check if user can access a specific column.

```php
public function canAccessColumn(
    int $userId,
    string $permission,
    object $model,
    string $column
): bool
```

**Parameters**:
- `$userId` (int) - User ID
- `$permission` (string) - Permission name
- `$model` (object) - Model instance
- `$column` (string) - Column name to check

**Returns**: `bool` - True if access allowed, false otherwise

**Example**:
```php
$post = Post::find(1);

if ($ruleManager->canAccessColumn(auth()->id(), 'posts.edit', $post, 'status')) {
    // User can edit the status column
}
```

---

#### getAccessibleColumns()

Get all accessible columns for a user and permission.

```php
public function getAccessibleColumns(
    int $userId,
    string $permission,
    string $modelClass
): array
```

**Parameters**:
- `$userId` (int) - User ID
- `$permission` (string) - Permission name
- `$modelClass` (string) - Fully qualified model class name

**Returns**: `array` - Array of accessible column names

**Example**:
```php
$columns = $ruleManager->getAccessibleColumns(
    auth()->id(),
    'posts.edit',
    Post::class
);
// ['title', 'content', 'excerpt', 'tags']

// Use in form
foreach ($columns as $column) {
    $form->text($column, ucfirst($column));
}
```

---

### JSON Attribute Methods

#### addJsonAttributeRule()

Add a JSON attribute permission rule.

```php
public function addJsonAttributeRule(
    int $permissionId,
    string $modelClass,
    string $jsonColumn,
    array $allowedPaths,
    array $deniedPaths = []
): PermissionRule
```

**Parameters**:
- `$permissionId` (int) - Permission ID
- `$modelClass` (string) - Fully qualified model class name
- `$jsonColumn` (string) - JSON column name
- `$allowedPaths` (array) - Array of allowed JSON paths (supports wildcards)
- `$deniedPaths` (array) - Array of denied JSON paths (supports wildcards), default: []

**Returns**: `PermissionRule` - Created rule instance

**Throws**: 
- `InvalidArgumentException` - If model class doesn't exist or column is not JSON
- `ValidationException` - If both allowed and denied paths are empty

**Example**:
```php
// Allow specific paths with wildcards
$ruleManager->addJsonAttributeRule(
    $permissionId,
    Post::class,
    'metadata',
    ['seo.*', 'social.*', 'layout.*']
);

// Deny specific paths
$ruleManager->addJsonAttributeRule(
    $permissionId,
    Post::class,
    'metadata',
    [], // empty = allow all
    ['featured', 'promoted', 'sticky']
);
```

---

#### canAccessJsonAttribute()

Check if user can access a specific JSON attribute.

```php
public function canAccessJsonAttribute(
    int $userId,
    string $permission,
    object $model,
    string $jsonColumn,
    string $path
): bool
```

**Parameters**:
- `$userId` (int) - User ID
- `$permission` (string) - Permission name
- `$model` (object) - Model instance
- `$jsonColumn` (string) - JSON column name
- `$path` (string) - JSON path (dot notation)

**Returns**: `bool` - True if access allowed, false otherwise

**Example**:
```php
$post = Post::find(1);

if ($ruleManager->canAccessJsonAttribute(
    auth()->id(),
    'posts.edit',
    $post,
    'metadata',
    'seo.title'
)) {
    // User can edit metadata.seo.title
}
```

---

#### getAccessibleJsonPaths()

Get all accessible JSON paths for a user and permission.

```php
public function getAccessibleJsonPaths(
    int $userId,
    string $permission,
    string $modelClass,
    string $jsonColumn
): array
```

**Parameters**:
- `$userId` (int) - User ID
- `$permission` (string) - Permission name
- `$modelClass` (string) - Fully qualified model class name
- `$jsonColumn` (string) - JSON column name

**Returns**: `array` - Array of accessible JSON paths

**Example**:
```php
$paths = $ruleManager->getAccessibleJsonPaths(
    auth()->id(),
    'posts.edit',
    Post::class,
    'metadata'
);
// ['seo.*', 'social.*', 'layout.*']
```

---

### Conditional Methods

#### addConditionalRule()

Add a conditional permission rule.

```php
public function addConditionalRule(
    int $permissionId,
    string $modelClass,
    string $condition
): PermissionRule
```

**Parameters**:
- `$permissionId` (int) - Permission ID
- `$modelClass` (string) - Fully qualified model class name
- `$condition` (string) - Condition expression

**Returns**: `PermissionRule` - Created rule instance

**Throws**: 
- `InvalidArgumentException` - If model class doesn't exist
- `ValidationException` - If condition syntax is invalid
- `SecurityException` - If condition contains disallowed operators

**Example**:
```php
// Simple condition
$ruleManager->addConditionalRule(
    $permissionId,
    Post::class,
    "status === 'draft' AND user_id === {{auth.id}}"
);

// Complex condition with relationships
$ruleManager->addConditionalRule(
    $permissionId,
    Post::class,
    "comments_count === 0 AND created_at > '2024-01-01'"
);
```

---

#### evaluateCondition()

Evaluate a condition expression against a model.

```php
protected function evaluateCondition(
    string $condition,
    object $model
): bool
```

**Parameters**:
- `$condition` (string) - Condition expression
- `$model` (object) - Model instance

**Returns**: `bool` - True if condition passes, false otherwise

**Throws**: 
- `SecurityException` - If condition contains code injection attempts

**Note**: This is a protected method used internally. Use `canAccessRow()` instead.

---

### User Override Methods

#### addUserOverride()

Add a user-specific permission override.

```php
public function addUserOverride(
    int $userId,
    int $permissionId,
    string $modelType,
    ?int $modelId = null,
    ?string $fieldName = null,
    bool $allowed = true
): UserPermissionOverride
```

**Parameters**:
- `$userId` (int) - User ID
- `$permissionId` (int) - Permission ID
- `$modelType` (string) - Model class name
- `$modelId` (int|null) - Specific model ID (null = all instances), default: null
- `$fieldName` (string|null) - Specific field name (null = all fields), default: null
- `$allowed` (bool) - Allow or deny access, default: true

**Returns**: `UserPermissionOverride` - Created override instance

**Example**:
```php
// Allow user to edit specific post
$ruleManager->addUserOverride(
    $userId,
    $permissionId,
    Post::class,
    $postId,
    null,
    true
);

// Deny user from editing status field on all posts
$ruleManager->addUserOverride(
    $userId,
    $permissionId,
    Post::class,
    null,
    'status',
    false
);
```

---

#### removeUserOverride()

Remove a user permission override.

```php
public function removeUserOverride(
    int $userId,
    int $permissionId,
    string $modelType,
    ?int $modelId = null
): bool
```

**Parameters**:
- `$userId` (int) - User ID
- `$permissionId` (int) - Permission ID
- `$modelType` (string) - Model class name
- `$modelId` (int|null) - Specific model ID (null = all), default: null

**Returns**: `bool` - True if removed, false if not found

**Example**:
```php
// Remove override for specific post
$ruleManager->removeUserOverride(
    $userId,
    $permissionId,
    Post::class,
    $postId
);

// Remove all overrides for model type
$ruleManager->removeUserOverride(
    $userId,
    $permissionId,
    Post::class
);
```

---

#### getUserOverrides()

Get all overrides for a user and permission.

```php
public function getUserOverrides(
    int $userId,
    int $permissionId
): Collection
```

**Parameters**:
- `$userId` (int) - User ID
- `$permissionId` (int) - Permission ID

**Returns**: `Collection` - Collection of UserPermissionOverride instances

**Example**:
```php
$overrides = $ruleManager->getUserOverrides(auth()->id(), $permissionId);

foreach ($overrides as $override) {
    echo "Model: {$override->model_type}\n";
    echo "Allowed: " . ($override->allowed ? 'Yes' : 'No') . "\n";
}
```

---

### Cache Methods

#### cacheRuleEvaluation()

Cache a rule evaluation result.

```php
protected function cacheRuleEvaluation(
    string $key,
    bool $result,
    int $ttl
): void
```

**Parameters**:
- `$key` (string) - Cache key
- `$result` (bool) - Evaluation result
- `$ttl` (int) - Time to live in seconds

**Note**: This is a protected method used internally.

---

#### getCachedEvaluation()

Get cached rule evaluation result.

```php
protected function getCachedEvaluation(
    string $key
): ?bool
```

**Parameters**:
- `$key` (string) - Cache key

**Returns**: `bool|null` - Cached result or null if not found

**Note**: This is a protected method used internally.

---

#### clearRuleCache()

Clear rule evaluation cache.

```php
public function clearRuleCache(
    ?int $userId = null,
    ?string $permission = null
): bool
```

**Parameters**:
- `$userId` (int|null) - User ID (null = all users), default: null
- `$permission` (string|null) - Permission name (null = all permissions), default: null

**Returns**: `bool` - True if cache cleared successfully

**Example**:
```php
// Clear all rule cache
$ruleManager->clearRuleCache();

// Clear cache for specific user
$ruleManager->clearRuleCache($userId);

// Clear cache for specific permission
$ruleManager->clearRuleCache(null, 'posts.edit');

// Clear cache for specific user and permission
$ruleManager->clearRuleCache($userId, 'posts.edit');
```

---

#### warmUpCache()

Warm up cache for frequently used permissions.

```php
public function warmUpCache(
    int $userId,
    array $permissions
): void
```

**Parameters**:
- `$userId` (int) - User ID
- `$permissions` (array) - Array of permission names

**Example**:
```php
// Warm up cache on user login
$ruleManager->warmUpCache(auth()->id(), [
    'posts.view',
    'posts.edit',
    'posts.delete',
    'users.view'
]);
```

---

## PermissionRule Model

**Namespace**: `Canvastack\Canvastack\Models\PermissionRule`

**Location**: `packages/canvastack/canvastack/src/Models/PermissionRule.php`

Eloquent model for permission rules.

### Properties

```php
protected $table = 'permission_rules';

protected $fillable = [
    'permission_id',
    'rule_type',
    'rule_config',
    'priority',
];

protected $casts = [
    'rule_config' => 'array',
    'priority' => 'integer',
];
```

### Relationships

#### permission()

Get the permission this rule belongs to.

```php
public function permission(): BelongsTo
```

**Returns**: `BelongsTo` - Belongs to Permission relationship

**Example**:
```php
$rule = PermissionRule::find(1);
$permission = $rule->permission;
echo $permission->name;
```

---

### Methods

#### evaluate()

Evaluate the rule against a model.

```php
public function evaluate(
    object $model,
    ?string $field = null
): bool
```

**Parameters**:
- `$model` (object) - Model instance
- `$field` (string|null) - Field name (for column/JSON rules), default: null

**Returns**: `bool` - True if rule passes, false otherwise

**Example**:
```php
$rule = PermissionRule::find(1);
$post = Post::find(1);

if ($rule->evaluate($post)) {
    // Rule passes
}

// For column rule
if ($rule->evaluate($post, 'status')) {
    // Can access status column
}
```

---

### Scopes

#### scopeForPermission()

Filter rules by permission.

```php
public function scopeForPermission(Builder $query, int $permissionId): Builder
```

**Parameters**:
- `$query` (Builder) - Query builder
- `$permissionId` (int) - Permission ID

**Returns**: `Builder` - Modified query

**Example**:
```php
$rules = PermissionRule::forPermission($permissionId)->get();
```

---

#### scopeByType()

Filter rules by type.

```php
public function scopeByType(Builder $query, string $type): Builder
```

**Parameters**:
- `$query` (Builder) - Query builder
- `$type` (string) - Rule type ('row', 'column', 'json_attribute', 'conditional')

**Returns**: `Builder` - Modified query

**Example**:
```php
$rowRules = PermissionRule::byType('row')->get();
$columnRules = PermissionRule::byType('column')->get();
```

---

#### scopeByPriority()

Order rules by priority.

```php
public function scopeByPriority(Builder $query): Builder
```

**Parameters**:
- `$query` (Builder) - Query builder

**Returns**: `Builder` - Modified query ordered by priority DESC

**Example**:
```php
$rules = PermissionRule::byPriority()->get();
```

---

## UserPermissionOverride Model

**Namespace**: `Canvastack\Canvastack\Models\UserPermissionOverride`

**Location**: `packages/canvastack/canvastack/src/Models/UserPermissionOverride.php`

Eloquent model for user permission overrides.

### Properties

```php
protected $table = 'user_permission_overrides';

protected $fillable = [
    'user_id',
    'permission_id',
    'model_type',
    'model_id',
    'field_name',
    'rule_config',
    'allowed',
];

protected $casts = [
    'rule_config' => 'array',
    'allowed' => 'boolean',
];
```

### Relationships

#### user()

Get the user this override belongs to.

```php
public function user(): BelongsTo
```

**Returns**: `BelongsTo` - Belongs to User relationship

**Example**:
```php
$override = UserPermissionOverride::find(1);
$user = $override->user;
echo $user->name;
```

---

#### permission()

Get the permission this override belongs to.

```php
public function permission(): BelongsTo
```

**Returns**: `BelongsTo` - Belongs to Permission relationship

**Example**:
```php
$override = UserPermissionOverride::find(1);
$permission = $override->permission;
echo $permission->name;
```

---

### Scopes

#### scopeForUser()

Filter overrides by user.

```php
public function scopeForUser(Builder $query, int $userId): Builder
```

**Parameters**:
- `$query` (Builder) - Query builder
- `$userId` (int) - User ID

**Returns**: `Builder` - Modified query

**Example**:
```php
$overrides = UserPermissionOverride::forUser(auth()->id())->get();
```

---

#### scopeForPermission()

Filter overrides by permission.

```php
public function scopeForPermission(Builder $query, int $permissionId): Builder
```

**Parameters**:
- `$query` (Builder) - Query builder
- `$permissionId` (int) - Permission ID

**Returns**: `Builder` - Modified query

**Example**:
```php
$overrides = UserPermissionOverride::forPermission($permissionId)->get();
```

---

#### scopeForModel()

Filter overrides by model type and optionally model ID.

```php
public function scopeForModel(
    Builder $query,
    string $modelType,
    ?int $modelId = null
): Builder
```

**Parameters**:
- `$query` (Builder) - Query builder
- `$modelType` (string) - Model class name
- `$modelId` (int|null) - Model ID (null = all instances), default: null

**Returns**: `Builder` - Modified query

**Example**:
```php
// All overrides for Post model
$overrides = UserPermissionOverride::forModel(Post::class)->get();

// Overrides for specific post
$overrides = UserPermissionOverride::forModel(Post::class, $postId)->get();
```

---

## Gate Integration

**Namespace**: `Canvastack\Canvastack\Auth\RBAC\Gate`

**Location**: `packages/canvastack/canvastack/src/Auth/RBAC/Gate.php`

Enhanced Gate class with fine-grained permission support.

### Methods

#### canAccessRow()

Check if user can access a specific row.

```php
public function canAccessRow(
    int $userId,
    string $permission,
    object $model
): bool
```

**Parameters**:
- `$userId` (int) - User ID
- `$permission` (string) - Permission name
- `$model` (object) - Model instance

**Returns**: `bool` - True if access allowed, false otherwise

**Example**:
```php
use Canvastack\Canvastack\Facades\Gate;

$post = Post::find(1);

if (Gate::canAccessRow(auth()->id(), 'posts.edit', $post)) {
    // User can edit this post
}
```

---

#### canAccessColumn()

Check if user can access a specific column.

```php
public function canAccessColumn(
    int $userId,
    string $permission,
    object $model,
    string $column
): bool
```

**Parameters**:
- `$userId` (int) - User ID
- `$permission` (string) - Permission name
- `$model` (object) - Model instance
- `$column` (string) - Column name

**Returns**: `bool` - True if access allowed, false otherwise

**Example**:
```php
use Canvastack\Canvastack\Facades\Gate;

$post = Post::find(1);

if (Gate::canAccessColumn(auth()->id(), 'posts.edit', $post, 'status')) {
    // User can edit status column
}
```

---

#### canAccessJsonAttribute()

Check if user can access a specific JSON attribute.

```php
public function canAccessJsonAttribute(
    int $userId,
    string $permission,
    object $model,
    string $jsonColumn,
    string $path
): bool
```

**Parameters**:
- `$userId` (int) - User ID
- `$permission` (string) - Permission name
- `$model` (object) - Model instance
- `$jsonColumn` (string) - JSON column name
- `$path` (string) - JSON path (dot notation)

**Returns**: `bool` - True if access allowed, false otherwise

**Example**:
```php
use Canvastack\Canvastack\Facades\Gate;

$post = Post::find(1);

if (Gate::canAccessJsonAttribute(
    auth()->id(),
    'posts.edit',
    $post,
    'metadata',
    'seo.title'
)) {
    // User can edit metadata.seo.title
}
```

---

## Blade Directives

### @canAccessRow

Check row-level access in Blade templates.

**Syntax**:
```blade
@canAccessRow($permission, $model)
    {{-- Content shown if access allowed --}}
@endcanAccessRow
```

**Parameters**:
- `$permission` (string) - Permission name
- `$model` (object) - Model instance

**Example**:
```blade
@canAccessRow('posts.edit', $post)
    <a href="{{ route('posts.edit', $post) }}" class="btn btn-primary">
        {{ __('ui.buttons.edit') }}
    </a>
@endcanAccessRow

@canAccessRow('posts.delete', $post)
    <form method="POST" action="{{ route('posts.destroy', $post) }}">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-error">
            {{ __('ui.buttons.delete') }}
        </button>
    </form>
@endcanAccessRow
```

---

### @canAccessColumn

Check column-level access in Blade templates.

**Syntax**:
```blade
@canAccessColumn($permission, $model, $column)
    {{-- Content shown if access allowed --}}
@endcanAccessColumn
```

**Parameters**:
- `$permission` (string) - Permission name
- `$model` (object) - Model instance
- `$column` (string) - Column name

**Example**:
```blade
<div class="form-group">
    <label>{{ __('ui.labels.title') }}</label>
    <input type="text" name="title" value="{{ $post->title }}" class="form-control">
</div>

@canAccessColumn('posts.edit', $post, 'status')
    <div class="form-group">
        <label>{{ __('ui.labels.status') }}</label>
        <select name="status" class="form-control">
            <option value="draft">{{ __('ui.status.draft') }}</option>
            <option value="published">{{ __('ui.status.published') }}</option>
        </select>
    </div>
@else
    <div class="alert alert-info">
        <i data-lucide="lock" class="w-4 h-4 inline"></i>
        {{ __('rbac.fine_grained.field_readonly', ['field' => __('ui.labels.status')]) }}
    </div>
@endcanAccessColumn
```

---

### @canAccessJsonAttribute

Check JSON attribute access in Blade templates.

**Syntax**:
```blade
@canAccessJsonAttribute($permission, $model, $jsonColumn, $path)
    {{-- Content shown if access allowed --}}
@endcanAccessJsonAttribute
```

**Parameters**:
- `$permission` (string) - Permission name
- `$model` (object) - Model instance
- `$jsonColumn` (string) - JSON column name
- `$path` (string) - JSON path (dot notation)

**Example**:
```blade
@canAccessJsonAttribute('posts.edit', $post, 'metadata', 'seo.title')
    <div class="form-group">
        <label>{{ __('ui.labels.seo_title') }}</label>
        <input type="text" 
               name="metadata[seo][title]" 
               value="{{ $post->metadata['seo']['title'] ?? '' }}" 
               class="form-control">
    </div>
@endcanAccessJsonAttribute

@canAccessJsonAttribute('posts.edit', $post, 'metadata', 'featured')
    <div class="form-group">
        <label>
            <input type="checkbox" 
                   name="metadata[featured]" 
                   value="1"
                   {{ ($post->metadata['featured'] ?? false) ? 'checked' : '' }}>
            {{ __('ui.labels.featured') }}
        </label>
    </div>
@else
    <div class="alert alert-info">
        <i data-lucide="lock" class="w-4 h-4 inline"></i>
        {{ __('rbac.fine_grained.field_hidden', ['field' => __('ui.labels.featured')]) }}
    </div>
@endcanAccessJsonAttribute
```

---

## FormBuilder Integration

**Namespace**: `Canvastack\Canvastack\Components\Form\FormBuilder`

**Location**: `packages/canvastack/canvastack/src/Components/Form/FormBuilder.php`

FormBuilder with fine-grained permission support.

### Methods

#### setPermission()

Enable permission-aware rendering.

```php
public function setPermission(string $permission): self
```

**Parameters**:
- `$permission` (string) - Permission name

**Returns**: `self` - Fluent interface

**Example**:
```php
$form->setPermission('posts.edit');
$form->text('title', __('ui.labels.title'))->required();
$form->text('status', __('ui.labels.status')); // Hidden if no column access
$form->render();
```

---

#### setModel()

Set model for permission checks.

```php
public function setModel(object $model): self
```

**Parameters**:
- `$model` (object) - Model instance

**Returns**: `self` - Fluent interface

**Example**:
```php
$post = Post::find(1);

$form->setPermission('posts.edit');
$form->setModel($post);
$form->text('title', __('ui.labels.title'))->required();
$form->render();
```

---

### Complete Example

```php
public function edit(Post $post, FormBuilder $form): View
{
    $form->setContext('admin');
    $form->setPermission('posts.edit');
    $form->setModel($post);
    
    // Basic fields (always shown if user has posts.edit permission)
    $form->text('title', __('ui.labels.title'))->required();
    $form->textarea('content', __('ui.labels.content'))->required();
    
    // Column-level permission check (shown only if user can access 'status' column)
    $form->select('status', __('ui.labels.status'), [
        'draft' => __('ui.status.draft'),
        'published' => __('ui.status.published'),
    ]);
    
    // JSON attribute permission check (shown only if user can access 'metadata.featured')
    $form->checkbox('metadata[featured]', __('ui.labels.featured'));
    
    return view('posts.edit', compact('form', 'post'));
}
```

**View**:
```blade
@extends('canvastack::layouts.admin')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3>{{ __('ui.posts.edit') }}</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('posts.update', $post) }}">
                @csrf
                @method('PUT')
                
                {!! $form->render() !!}
                
                <button type="submit" class="btn btn-primary">
                    {{ __('ui.buttons.save') }}
                </button>
            </form>
        </div>
    </div>
@endsection
```

---

## TableBuilder Integration

**Namespace**: `Canvastack\Canvastack\Components\Table\TableBuilder`

**Location**: `packages/canvastack/canvastack/src/Components/Table/TableBuilder.php`

TableBuilder with fine-grained permission support.

### Methods

#### setPermission()

Enable permission-aware rendering.

```php
public function setPermission(string $permission): self
```

**Parameters**:
- `$permission` (string) - Permission name

**Returns**: `self` - Fluent interface

**Example**:
```php
$table->setPermission('posts.view');
$table->setModel(new Post());
$table->setFields([
    'title:' . __('ui.labels.title'),
    'status:' . __('ui.labels.status'), // Hidden if no column access
]);
$table->format();
```

---

### Complete Example

```php
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setPermission('posts.view');
    $table->setModel(new Post());
    
    // Define fields (columns hidden automatically based on column-level permissions)
    $table->setFields([
        'id:ID',
        'title:' . __('ui.labels.title'),
        'author.name:' . __('ui.labels.author'),
        'status:' . __('ui.labels.status'), // Hidden if no access
        'created_at:' . __('ui.labels.created'),
    ]);
    
    // Row-level filtering applied automatically
    $table->eager(['author']);
    
    // Actions with row-level permission checks
    $table->addAction('edit', route('posts.edit', ':id'), 'edit', __('ui.buttons.edit'));
    $table->addAction('delete', route('posts.destroy', ':id'), 'trash', __('ui.buttons.delete'), 'DELETE');
    
    $table->format();
    
    return view('posts.index', compact('table'));
}
```

**View**:
```blade
@extends('canvastack::layouts.admin')

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="flex items-center justify-between">
                <h3>{{ __('ui.posts.list') }}</h3>
                <a href="{{ route('posts.create') }}" class="btn btn-primary">
                    {{ __('ui.buttons.create') }}
                </a>
            </div>
        </div>
        <div class="card-body">
            {!! $table->render() !!}
        </div>
    </div>
@endsection
```

---

## Configuration

**Location**: `config/canvastack-rbac.php`

### Configuration Structure

```php
return [
    // ... existing RBAC config ...
    
    'fine_grained' => [
        // Enable/disable fine-grained permissions globally
        'enabled' => env('RBAC_FINE_GRAINED_ENABLED', true),
        
        // Cache configuration
        'cache' => [
            'enabled' => true,
            'ttl' => [
                'row' => 3600,        // 1 hour
                'column' => 3600,     // 1 hour
                'json_attribute' => 3600, // 1 hour
                'conditional' => 1800,    // 30 minutes
            ],
            'key_prefix' => 'canvastack:rbac:rules:',
            'tags' => [
                'rules' => 'rbac:rules',
                'user' => 'rbac:user:{userId}',
            ],
        ],
        
        // Row-level permissions
        'row_level' => [
            'enabled' => true,
            'template_variables' => [
                'auth.id' => fn() => auth()->id(),
                'auth.role' => fn() => auth()->user()?->role,
                'auth.department' => fn() => auth()->user()?->department_id,
                'auth.email' => fn() => auth()->user()?->email,
            ],
        ],
        
        // Column-level permissions
        'column_level' => [
            'enabled' => true,
            'default_deny' => false, // false = allow all by default
        ],
        
        // JSON attribute permissions
        'json_attribute' => [
            'enabled' => true,
            'path_separator' => '.', // metadata.seo.title
        ],
        
        // Conditional permissions
        'conditional' => [
            'enabled' => true,
            'allowed_operators' => [
                '===', '!==', '>', '<', '>=', '<=', 
                'in', 'not_in', 'AND', 'OR', 'NOT'
            ],
            'allowed_functions' => ['count', 'sum', 'avg'],
        ],
        
        // Audit logging
        'audit' => [
            'enabled' => true,
            'log_denials' => true,
            'log_channel' => 'rbac',
        ],
    ],
];
```

---

### Configuration Options

#### Global Settings

**`enabled`** (bool, default: `true`)
- Enable or disable fine-grained permissions globally
- When disabled, system falls back to basic RBAC

**Example**:
```php
'enabled' => env('RBAC_FINE_GRAINED_ENABLED', true),
```

---

#### Cache Settings

**`cache.enabled`** (bool, default: `true`)
- Enable or disable caching for rule evaluations

**`cache.ttl`** (array)
- Time to live for each rule type in seconds
- `row`: Row-level rule cache TTL (default: 3600)
- `column`: Column-level rule cache TTL (default: 3600)
- `json_attribute`: JSON attribute rule cache TTL (default: 3600)
- `conditional`: Conditional rule cache TTL (default: 1800)

**`cache.key_prefix`** (string, default: `'canvastack:rbac:rules:'`)
- Prefix for all cache keys

**`cache.tags`** (array)
- Cache tags for easy invalidation
- `rules`: Tag for all rule caches
- `user`: Tag for user-specific caches (supports {userId} placeholder)

**Example**:
```php
'cache' => [
    'enabled' => true,
    'ttl' => [
        'row' => 7200,        // 2 hours
        'column' => 7200,     // 2 hours
        'json_attribute' => 7200, // 2 hours
        'conditional' => 3600,    // 1 hour
    ],
],
```

---

#### Row-Level Settings

**`row_level.enabled`** (bool, default: `true`)
- Enable or disable row-level permissions

**`row_level.template_variables`** (array)
- Define template variables for row-level conditions
- Key: Variable name (e.g., 'auth.id')
- Value: Closure that returns the variable value

**Example**:
```php
'row_level' => [
    'enabled' => true,
    'template_variables' => [
        'auth.id' => fn() => auth()->id(),
        'auth.role' => fn() => auth()->user()?->role,
        'auth.department' => fn() => auth()->user()?->department_id,
        'auth.team' => fn() => auth()->user()?->team_id,
        'now' => fn() => now()->toDateString(),
    ],
],
```

---

#### Column-Level Settings

**`column_level.enabled`** (bool, default: `true`)
- Enable or disable column-level permissions

**`column_level.default_deny`** (bool, default: `false`)
- Default behavior when no column rules are defined
- `false`: Allow all columns by default (whitelist approach)
- `true`: Deny all columns by default (blacklist approach)

**Example**:
```php
'column_level' => [
    'enabled' => true,
    'default_deny' => false, // Allow all by default
],
```

---

#### JSON Attribute Settings

**`json_attribute.enabled`** (bool, default: `true`)
- Enable or disable JSON attribute permissions

**`json_attribute.path_separator`** (string, default: `'.'`)
- Separator for JSON path notation
- Example: `'metadata.seo.title'` uses `'.'` as separator

**Example**:
```php
'json_attribute' => [
    'enabled' => true,
    'path_separator' => '.', // Use dot notation
],
```

---

#### Conditional Settings

**`conditional.enabled`** (bool, default: `true`)
- Enable or disable conditional permissions

**`conditional.allowed_operators`** (array)
- List of allowed operators in condition expressions
- Default: `['===', '!==', '>', '<', '>=', '<=', 'in', 'not_in', 'AND', 'OR', 'NOT']`

**`conditional.allowed_functions`** (array)
- List of allowed functions in condition expressions
- Default: `['count', 'sum', 'avg']`

**Example**:
```php
'conditional' => [
    'enabled' => true,
    'allowed_operators' => [
        '===', '!==', '>', '<', '>=', '<=', 
        'in', 'not_in', 'AND', 'OR'
    ],
    'allowed_functions' => ['count'],
],
```

---

#### Audit Settings

**`audit.enabled`** (bool, default: `true`)
- Enable or disable audit logging

**`audit.log_denials`** (bool, default: `true`)
- Log all permission denials

**`audit.log_channel`** (string, default: `'rbac'`)
- Log channel for audit logs

**Example**:
```php
'audit' => [
    'enabled' => true,
    'log_denials' => true,
    'log_channel' => 'rbac',
],
```

---

## Translation Keys

**Location**: `resources/lang/en/rbac.php` and `resources/lang/id/rbac.php`

### English Translation Keys

```php
return [
    'fine_grained' => [
        // Field messages
        'field_hidden' => 'Field :field is hidden due to permissions',
        'field_readonly' => 'This field is read-only',
        
        // Column messages
        'columns_hidden' => ':count columns are hidden due to permissions',
        'no_access' => 'No access',
        
        // Rule types
        'row_level' => 'Row-Level Permission',
        'column_level' => 'Column-Level Permission',
        'json_attribute' => 'JSON Attribute Permission',
        'conditional' => 'Conditional Permission',
        
        // Rule management
        'manage_rules' => 'Manage Permission Rules',
        'rule_type' => 'Rule Type',
        'conditions' => 'Conditions',
        'allowed_columns' => 'Allowed Columns',
        'denied_columns' => 'Denied Columns',
        'allowed_paths' => 'Allowed JSON Paths',
        'denied_paths' => 'Denied JSON Paths',
        
        // Messages
        'rule_created' => 'Permission rule created successfully',
        'rule_updated' => 'Permission rule updated successfully',
        'rule_deleted' => 'Permission rule deleted successfully',
        'override_created' => 'User override created successfully',
        'override_deleted' => 'User override deleted successfully',
        
        // Errors
        'invalid_rule_type' => 'Invalid rule type',
        'invalid_condition' => 'Invalid condition expression',
        'model_not_found' => 'Model class not found',
        'permission_denied' => 'Permission denied',
    ],
];
```

### Indonesian Translation Keys

```php
return [
    'fine_grained' => [
        // Field messages
        'field_hidden' => 'Field :field disembunyikan karena izin',
        'field_readonly' => 'Field ini hanya bisa dibaca',
        
        // Column messages
        'columns_hidden' => ':count kolom disembunyikan karena izin',
        'no_access' => 'Tidak ada akses',
        
        // Rule types
        'row_level' => 'Izin Level Baris',
        'column_level' => 'Izin Level Kolom',
        'json_attribute' => 'Izin Atribut JSON',
        'conditional' => 'Izin Kondisional',
        
        // Rule management
        'manage_rules' => 'Kelola Aturan Izin',
        'rule_type' => 'Tipe Aturan',
        'conditions' => 'Kondisi',
        'allowed_columns' => 'Kolom yang Diizinkan',
        'denied_columns' => 'Kolom yang Ditolak',
        'allowed_paths' => 'Path JSON yang Diizinkan',
        'denied_paths' => 'Path JSON yang Ditolak',
        
        // Messages
        'rule_created' => 'Aturan izin berhasil dibuat',
        'rule_updated' => 'Aturan izin berhasil diperbarui',
        'rule_deleted' => 'Aturan izin berhasil dihapus',
        'override_created' => 'Override pengguna berhasil dibuat',
        'override_deleted' => 'Override pengguna berhasil dihapus',
        
        // Errors
        'invalid_rule_type' => 'Tipe aturan tidak valid',
        'invalid_condition' => 'Ekspresi kondisi tidak valid',
        'model_not_found' => 'Kelas model tidak ditemukan',
        'permission_denied' => 'Izin ditolak',
    ],
];
```

---

## Performance Considerations

### Response Time Targets

| Operation | Target | Notes |
|-----------|--------|-------|
| Row-level check | < 50ms | Per check, with cache |
| Column-level check | < 10ms | Per check, with cache |
| JSON attribute check | < 15ms | Per check, with cache |
| Conditional check | < 30ms | Per check, with cache |
| Cache hit rate | > 80% | For all operations |

### Optimization Tips

1. **Enable Caching**
   ```php
   'cache' => [
       'enabled' => true,
       'ttl' => [
           'row' => 3600,
           'column' => 3600,
       ],
   ],
   ```

2. **Warm Up Cache on Login**
   ```php
   $ruleManager->warmUpCache(auth()->id(), [
       'posts.view',
       'posts.edit',
       'users.view',
   ]);
   ```

3. **Use Eager Loading**
   ```php
   $table->eager(['author', 'category']);
   ```

4. **Clear Cache Selectively**
   ```php
   // Clear only for specific user
   $ruleManager->clearRuleCache($userId);
   
   // Clear only for specific permission
   $ruleManager->clearRuleCache(null, 'posts.edit');
   ```

---

## Security Considerations

### SQL Injection Prevention

The system automatically prevents SQL injection:
- All queries use parameterized statements
- Column names are validated against model schema
- User input is sanitized

### Code Injection Prevention

Conditional rules are sanitized:
- Only allowed operators are permitted
- Function calls are restricted
- Template variables are resolved safely

### Audit Logging

All permission denials are logged:
```php
'audit' => [
    'enabled' => true,
    'log_denials' => true,
    'log_channel' => 'rbac',
],
```

View logs:
```bash
tail -f storage/logs/rbac.log
```

---

## Testing

### Unit Tests

```php
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;

public function test_can_access_row_with_matching_condition()
{
    $ruleManager = app(PermissionRuleManager::class);
    
    $ruleManager->addRowRule(
        $this->permission->id,
        Post::class,
        ['user_id' => '{{auth.id}}']
    );
    
    $post = Post::factory()->create(['user_id' => $this->user->id]);
    
    $this->assertTrue(
        $ruleManager->canAccessRow($this->user->id, 'posts.edit', $post)
    );
}
```

### Feature Tests

```php
public function test_form_hides_fields_without_column_access()
{
    $this->actingAs($this->user);
    
    $response = $this->get(route('posts.edit', $this->post));
    
    $response->assertStatus(200);
    $response->assertSee('title'); // Accessible
    $response->assertDontSee('status'); // Not accessible
}
```

---

## Troubleshooting

### Common Issues

**Issue**: Rules not being applied

**Solution**: Check if fine-grained permissions are enabled
```php
config('canvastack-rbac.fine_grained.enabled') // Should be true
```

---

**Issue**: Cache not clearing

**Solution**: Clear cache manually
```php
$ruleManager->clearRuleCache();
// or
Cache::tags(['rbac:rules'])->flush();
```

---

**Issue**: Template variables not resolving

**Solution**: Check template variable configuration
```php
config('canvastack-rbac.fine_grained.row_level.template_variables')
```

---

**Issue**: Performance degradation

**Solution**: Enable caching and warm up cache
```php
'cache' => ['enabled' => true],

// Warm up on login
$ruleManager->warmUpCache(auth()->id(), $permissions);
```

---

## Related Documentation

- [Requirements Document](requirements.md) - Complete requirements specification
- [Design Document](design.md) - System architecture and design
- [Usage Guide](usage-guide.md) - How to use fine-grained permissions
- [Integration Guide](integration-guide.md) - Component integration
- [Migration Guide](migration-guide.md) - Migrating from basic RBAC
- [Best Practices Guide](best-practices.md) - Performance and security tips
- [Troubleshooting Guide](troubleshooting.md) - Common issues and solutions

---

**Document Version**: 1.0.0  
**Last Updated**: 2026-02-27  
**Status**: Published  
**Author**: CanvaStack Team  
**Reviewers**: TBD

