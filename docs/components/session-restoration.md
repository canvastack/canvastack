# Session Restoration

## Overview

Session restoration is a feature that automatically saves and restores table state across page reloads. This includes filters, active tabs, display limits, sort settings, and more.

## Features

- **Automatic State Persistence**: Saves table state to session storage
- **Seamless Restoration**: Automatically restores state on page reload
- **Multi-State Support**: Handles filters, tabs, display limits, sort settings, search terms, fixed columns, and hidden columns
- **User-Specific**: Session keys are unique per user and table
- **Path-Aware**: Different pages can have different session states

## Basic Usage

### Enable Session Persistence

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Enable session persistence
    $table->sessionFilters();
    
    // Configure table
    $table->setFields(['name:Name', 'email:Email', 'created_at:Created']);
    $table->filterGroups('status', 'selectbox');
    $table->filterGroups('role', 'selectbox');
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

## What Gets Restored

When `sessionFilters()` is enabled, the following state is automatically restored:

### 1. Filters

All active filter values are restored:

```php
// User applies filters
$table->filterGroups('status', 'selectbox');
$table->filterGroups('category', 'selectbox');

// On page reload, filter values are automatically restored
$table->sessionFilters();
```

### 2. Active Tab

The currently active tab is restored:

```php
$table->openTab('Summary');
// ... tab content ...
$table->closeTab();

$table->openTab('Detail');
// ... tab content ...
$table->closeTab();

// On page reload, the active tab is restored
$table->sessionFilters();
```

### 3. Display Limit

The number of rows to display is restored:

```php
$table->displayRowsLimitOnLoad(25);

// On page reload, display limit is restored
$table->sessionFilters();
```

### 4. Sort Settings

Sort column and direction are restored:

```php
$table->orderBy('created_at', 'desc');

// On page reload, sort settings are restored
$table->sessionFilters();
```

### 5. Fixed Columns

Fixed column configuration is restored:

```php
$table->fixedColumns(2, 1); // 2 left, 1 right

// On page reload, fixed columns are restored
$table->sessionFilters();
```

### 6. Hidden Columns

Hidden column configuration is restored:

```php
$table->setHiddenColumns(['id', 'password']);

// On page reload, hidden columns are restored
$table->sessionFilters();
```

## Manual State Management

### Save Current State

You can manually save the current table state:

```php
// Save all current state
$table->saveCurrentStateToSession();

// Or save specific data
$table->saveToSession([
    'filters' => ['status' => 'active'],
    'display_limit' => 50,
]);
```

### Clear Session

Clear all saved session data:

```php
$table->clearSession();
```

## Session Key Generation

Session keys are automatically generated based on:

- Table name
- Current request path
- User ID (or 'guest' if not authenticated)
- Optional context

This ensures that:
- Different tables have different session states
- Different pages have different session states
- Different users have different session states

## Advanced Usage

### Custom Table Name

```php
$table->setName('custom_table');
$table->sessionFilters();

// Session key will include 'custom_table'
```

### Multiple Tables on Same Page

Each table instance has its own session state:

```php
// Table 1
$table1 = app(TableBuilder::class);
$table1->setName('users');
$table1->sessionFilters();

// Table 2
$table2 = app(TableBuilder::class);
$table2->setName('posts');
$table2->sessionFilters();

// Each table has independent session state
```

## Implementation Details

### SessionManager Class

The `SessionManager` class handles all session operations:

```php
use Canvastack\Canvastack\Components\Table\Session\SessionManager;

$sessionManager = new SessionManager('table_name');

// Save data
$sessionManager->save(['filters' => ['status' => 'active']]);

// Get data
$filters = $sessionManager->get('filters', []);

// Check if key exists
if ($sessionManager->has('filters')) {
    // ...
}

// Clear all data
$sessionManager->clear();
```

### Session Key Format

Session keys follow this format:

```
table_session_{md5(tableName_path_userId_context)}
```

Example:
```
table_session_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
```

## Best Practices

### 1. Enable Early

Enable session persistence before configuring the table:

```php
// ✅ Good
$table->sessionFilters();
$table->setFields([...]);
$table->filterGroups(...);

// ❌ Bad
$table->setFields([...]);
$table->filterGroups(...);
$table->sessionFilters(); // Too late, state already set
```

### 2. Use with Filters

Session persistence is most useful with filters:

```php
$table->sessionFilters();
$table->filterGroups('status', 'selectbox');
$table->filterGroups('category', 'selectbox');
$table->filterGroups('date_range', 'datebox');
```

### 3. Clear When Needed

Clear session when user explicitly resets:

```php
if ($request->has('reset')) {
    $table->clearSession();
}
```

### 4. Combine with Tabs

Session persistence works great with tabs:

```php
$table->sessionFilters();

$table->openTab('Active Users');
$table->filterGroups('status', 'selectbox');
$table->closeTab();

$table->openTab('Inactive Users');
$table->filterGroups('status', 'selectbox');
$table->closeTab();

// Active tab and filters are restored
```

## Testing

### Unit Tests

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Illuminate\Support\Facades\Session;

public function test_filters_are_restored_from_session()
{
    // Save filters to session
    $sessionKey = 'table_session_' . md5('default_' . request()->path() . '_guest_');
    Session::put($sessionKey, [
        'filters' => ['status' => 'active'],
    ]);

    // Create table and enable session
    $table = app(TableBuilder::class);
    $table->sessionFilters();

    // Verify filters are restored
    $reflection = new \ReflectionClass($table);
    $property = $reflection->getProperty('filters');
    $property->setAccessible(true);
    $filters = $property->getValue($table);

    $this->assertEquals('active', $filters['status']);
}
```

## Troubleshooting

### Filters Not Restoring

**Problem**: Filters are not being restored from session.

**Solution**: Make sure `sessionFilters()` is called before setting filters:

```php
// ✅ Correct order
$table->sessionFilters();
$table->filterGroups('status', 'selectbox');

// ❌ Wrong order
$table->filterGroups('status', 'selectbox');
$table->sessionFilters();
```

### Tab Not Restoring

**Problem**: Active tab is not being restored.

**Solution**: Ensure tabs are created before calling `sessionFilters()`:

```php
// ✅ Correct
$table->openTab('Summary');
$table->closeTab();
$table->openTab('Detail');
$table->closeTab();
$table->sessionFilters();
```

### Session Conflicts

**Problem**: Session state from one table affects another table.

**Solution**: Use unique table names:

```php
$table1->setName('users_table');
$table2->setName('posts_table');
```

## Related Documentation

- [SessionManager](./session-manager.md) - Session management class
- [FilterManager](./filter-manager.md) - Filter management
- [TabManager](./tab-manager.md) - Tab management
- [TableBuilder API](../api/table.md) - Complete API reference

---

**Last Updated**: 2026-03-02  
**Version**: 1.0.0  
**Status**: Published
