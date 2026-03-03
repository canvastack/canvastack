# Column Access Control Logic

## Overview

This document explains the column-level access control logic in the Fine-Grained Permissions System, including whitelist/blacklist evaluation, rule merging, and edge cases.

**Status**: Published  
**Version**: 1.0.0  
**Last Updated**: 2026-02-28

---

## 📋 Core Concepts

### Whitelist Mode

In whitelist mode, **only explicitly allowed columns** are accessible. All other columns are denied by default.

```php
// Example: Only title, content, and excerpt are accessible
$manager->addColumnRule(
    $permissionId,
    Post::class,
    ['title', 'content', 'excerpt'], // Allowed columns
    [] // No denied columns
);

// Result:
// ✅ title - ALLOWED
// ✅ content - ALLOWED
// ✅ excerpt - ALLOWED
// ❌ status - DENIED (not in whitelist)
// ❌ featured - DENIED (not in whitelist)
```

### Blacklist Mode

In blacklist mode, **all columns are accessible except explicitly denied ones**.

```php
// Example: All columns except status and featured are accessible
$manager->addColumnRule(
    $permissionId,
    Post::class,
    [], // No allowed columns (blacklist mode)
    ['status', 'featured'] // Denied columns
);

// Result:
// ✅ title - ALLOWED (not in blacklist)
// ✅ content - ALLOWED (not in blacklist)
// ✅ excerpt - ALLOWED (not in blacklist)
// ❌ status - DENIED (in blacklist)
// ❌ featured - DENIED (in blacklist)
```

---

## 🔍 Evaluation Logic

### Single Rule Evaluation

The `evaluateColumnAccess()` method follows this logic:

```php
protected function evaluateColumnAccess(string $column, array $accessibleColumns): bool
{
    // 1. If empty array, allow by default (no rules defined)
    if (empty($accessibleColumns)) {
        return true;
    }

    // 2. Check for negated columns (blacklist mode)
    $hasNegations = false;
    foreach ($accessibleColumns as $col) {
        if (is_string($col) && str_starts_with($col, '!')) {
            $hasNegations = true;
            $deniedColumn = substr($col, 1);
            if ($deniedColumn === $column) {
                return false; // Column is explicitly denied
            }
        }
    }

    // 3. If we have negations (blacklist mode), allow if not denied
    if ($hasNegations) {
        return true;
    }

    // 4. Whitelist mode: check if column is in the list
    return in_array($column, $accessibleColumns, true);
}
```

### Flow Diagram

```
┌─────────────────────────────────────┐
│ canAccessColumn(user, perm, model,  │
│                 column)             │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ Check if fine-grained enabled       │
│ If disabled → return true           │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ Check cache                         │
│ If cached → return cached result    │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ Check user overrides                │
│ If override exists → return override│
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ getAccessibleColumns()              │
│ Returns array of accessible columns │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ evaluateColumnAccess()              │
│ - Empty array → allow all           │
│ - Has negations (!) → blacklist     │
│ - No negations → whitelist          │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ Cache result                        │
│ Return boolean                      │
└─────────────────────────────────────┘
```

---

## 🔄 Multiple Rules Handling

### Whitelist + Whitelist = Merged Whitelist

When multiple whitelist rules exist, they are **merged** (union):

```php
// Rule 1
$manager->addColumnRule($permissionId, Post::class, ['title', 'content'], []);

// Rule 2
$manager->addColumnRule($permissionId, Post::class, ['excerpt', 'tags'], []);

// Result: ['title', 'content', 'excerpt', 'tags']
// All columns from both rules are accessible
```

### Blacklist + Blacklist = Merged Blacklist

When multiple blacklist rules exist, they are **merged** (union):

```php
// Rule 1
$manager->addColumnRule($permissionId, Post::class, [], ['status']);

// Rule 2
$manager->addColumnRule($permissionId, Post::class, [], ['featured']);

// Result: ['!status', '!featured']
// Both denied columns are blocked
```

### Whitelist + Blacklist = Whitelist Takes Precedence

When both whitelist and blacklist rules exist, **whitelist takes precedence**:

```php
// Rule 1 - Whitelist
$manager->addColumnRule($permissionId, Post::class, ['title', 'content'], []);

// Rule 2 - Blacklist
$manager->addColumnRule($permissionId, Post::class, [], ['status', 'featured']);

// Result: ['title', 'content']
// Only whitelist columns are accessible
// Blacklist is ignored when whitelist exists
```

**Rationale**: Whitelist is more restrictive and explicit, so it takes precedence over blacklist.

---

## 📊 getAccessibleColumns() Return Format

### Whitelist Mode

Returns array of allowed column names:

```php
$columns = $manager->getAccessibleColumns($userId, 'posts.edit', Post::class);
// ['title', 'content', 'excerpt']
```

### Blacklist Mode

Returns array of denied column names with `!` prefix:

```php
$columns = $manager->getAccessibleColumns($userId, 'posts.edit', Post::class);
// ['!status', '!featured']
```

### No Rules

Returns empty array (allow all):

```php
$columns = $manager->getAccessibleColumns($userId, 'posts.edit', Post::class);
// []
```

---

## 🎯 Edge Cases

### Case 1: No Rules Defined

**Behavior**: Allow all columns

```php
// No rules added
$result = $manager->canAccessColumn($userId, 'posts.edit', $model, 'any_column');
// true - all columns allowed by default
```

### Case 2: Empty Whitelist and Empty Blacklist

**Behavior**: Throws exception (invalid configuration)

```php
// This will throw InvalidArgumentException
$manager->addColumnRule($permissionId, Post::class, [], []);
// Exception: "Must specify either allowed or denied columns"
```

### Case 3: Column Name Case Sensitivity

**Behavior**: Column names are case-sensitive

```php
$manager->addColumnRule($permissionId, Post::class, ['title'], []);

$manager->canAccessColumn($userId, 'posts.edit', $model, 'title');  // true
$manager->canAccessColumn($userId, 'posts.edit', $model, 'Title');  // false
$manager->canAccessColumn($userId, 'posts.edit', $model, 'TITLE');  // false
```

### Case 4: Special Characters in Column Names

**Behavior**: Supported (e.g., underscores)

```php
$manager->addColumnRule($permissionId, Post::class, ['user_id', 'created_at'], []);

$manager->canAccessColumn($userId, 'posts.edit', $model, 'user_id');     // true
$manager->canAccessColumn($userId, 'posts.edit', $model, 'created_at');  // true
$manager->canAccessColumn($userId, 'posts.edit', $model, 'userid');      // false
```

### Case 5: Mixed Mode (Both Whitelist and Blacklist in Same Rule)

**Behavior**: Whitelist takes precedence

```php
$manager->addColumnRule(
    $permissionId,
    Post::class,
    ['title', 'content'], // Whitelist
    ['status'] // Blacklist (ignored)
);

// Only whitelist columns are accessible
$manager->canAccessColumn($userId, 'posts.edit', $model, 'title');   // true
$manager->canAccessColumn($userId, 'posts.edit', $model, 'content'); // true
$manager->canAccessColumn($userId, 'posts.edit', $model, 'excerpt'); // false
$manager->canAccessColumn($userId, 'posts.edit', $model, 'status');  // false
```

---

## 🚀 Performance Considerations

### Caching

Column access checks are cached for 3600 seconds (1 hour) by default:

```php
// First call - cache miss
$result1 = $manager->canAccessColumn($userId, 'posts.edit', $model, 'title');

// Second call - cache hit (much faster)
$result2 = $manager->canAccessColumn($userId, 'posts.edit', $model, 'title');
```

### Cache Keys

Cache keys include:
- User ID
- Permission name
- Model class (hashed)
- Column name

```php
$cacheKey = "canvastack:rbac:rules:can_access_column:{$userId}:{$permission}:{$modelClass}:{$column}";
```

### Cache Invalidation

Cache is automatically cleared when:
- Rules are added/updated/deleted
- User overrides are added/removed
- `clearRuleCache()` is called

---

## 🧪 Testing

### Unit Tests

Comprehensive tests are available in:
- `tests/Unit/Auth/RBAC/GateCanAccessColumnTest.php`
- `tests/Unit/Auth/RBAC/ColumnAccessControlEdgeCasesTest.php`

### Test Coverage

Tests cover:
- ✅ Whitelist mode (single and multiple columns)
- ✅ Blacklist mode (single and multiple columns)
- ✅ Mixed mode (whitelist + blacklist)
- ✅ Multiple rules merging
- ✅ Case sensitivity
- ✅ Special characters
- ✅ Caching behavior
- ✅ Edge cases

### Running Tests

```bash
# Run all column access tests
./vendor/bin/phpunit --filter=canAccessColumn

# Run edge case tests
./vendor/bin/phpunit --filter=ColumnAccessControlEdgeCasesTest

# Run all RBAC tests
./vendor/bin/phpunit --testsuite=Unit --filter=RBAC
```

---

## 💡 Best Practices

### 1. Use Whitelist for Sensitive Data

For sensitive columns, use whitelist mode to be explicit:

```php
// ✅ GOOD - Explicit whitelist
$manager->addColumnRule(
    $permissionId,
    User::class,
    ['name', 'email'], // Only these columns
    []
);

// ❌ BAD - Blacklist might miss new sensitive columns
$manager->addColumnRule(
    $permissionId,
    User::class,
    [],
    ['password', 'remember_token'] // Might forget other sensitive columns
);
```

### 2. Use Blacklist for Public Data

For mostly public data with few sensitive columns, use blacklist:

```php
// ✅ GOOD - Blacklist for mostly public data
$manager->addColumnRule(
    $permissionId,
    Post::class,
    [],
    ['internal_notes', 'admin_flags'] // Only block these
);
```

### 3. Document Your Rules

Add comments to explain why certain columns are restricted:

```php
// Editors can edit content but not publication status
$manager->addColumnRule(
    $editorPermissionId,
    Post::class,
    ['title', 'content', 'excerpt', 'tags'],
    []
);

// Admins can edit everything except system fields
$manager->addColumnRule(
    $adminPermissionId,
    Post::class,
    [],
    ['id', 'created_at', 'updated_at']
);
```

### 4. Test Your Rules

Always test your column access rules:

```php
public function test_editor_cannot_edit_status(): void
{
    $editor = User::factory()->create();
    $editorRole = Role::factory()->create(['name' => 'editor']);
    $editor->roles()->attach($editorRole->id);

    $permission = Permission::factory()->create(['name' => 'posts.edit']);
    $editor->permissions()->attach($permission->id);

    $this->manager->addColumnRule(
        $permission->id,
        Post::class,
        ['title', 'content'],
        []
    );

    $post = Post::factory()->create();

    $this->assertTrue($this->gate->canAccessColumn($editor, 'posts.edit', $post, 'title'));
    $this->assertFalse($this->gate->canAccessColumn($editor, 'posts.edit', $post, 'status'));
}
```

---

## 🔗 Related Documentation

- [Fine-Grained Permissions Overview](../features/rbac.md)
- [Gate Integration](../api/rbac.md#gate-integration)
- [Blade Directives](../blade-directives/can-access-column.md)
- [FormBuilder Integration](../components/form-builder.md#permission-filtering)
- [TableBuilder Integration](../components/table-builder.md#permission-filtering)

---

## 📚 API Reference

### PermissionRuleManager

```php
// Add column rule
public function addColumnRule(
    int $permissionId,
    string $modelClass,
    array $allowedColumns,
    array $deniedColumns = []
): PermissionRule

// Check column access
public function canAccessColumn(
    int $userId,
    string $permission,
    object $model,
    string $column
): bool

// Get accessible columns
public function getAccessibleColumns(
    int $userId,
    string $permission,
    string $modelClass
): array
```

### Gate

```php
// Check column access
public function canAccessColumn(
    ?object $user,
    string $permission,
    object $model,
    string $column
): bool
```

---

**Document Version**: 1.0.0  
**Last Updated**: 2026-02-28  
**Status**: Published  
**Author**: CanvaStack Team
