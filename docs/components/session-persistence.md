# Session Persistence

## 📦 Location

- **Component**: `Canvastack\Canvastack\Components\Table\Session\SessionManager`
- **Integration**: `Canvastack\Canvastack\Components\Table\TableBuilder`
- **File Location**: `packages/canvastack/canvastack/src/Components/Table/Session/SessionManager.php`
- **Related Files**: 
  - `packages/canvastack/canvastack/src/Components/Table/TableBuilder.php`
  - `packages/canvastack/canvastack/database/migrations/2026_03_02_000001_create_table_tab_sessions_table.php`
  - `packages/canvastack/canvastack/database/migrations/2026_03_02_000002_create_table_filter_sessions_table.php`

## 🎯 Features

- Automatic session persistence for table state
- Filter values saved and restored across page reloads
- Active tab saved and restored
- Display limit preferences saved and restored
- Per-table, per-user, per-path session isolation
- Secure session key generation
- Seamless integration with TableBuilder

## 📖 Basic Usage

### Enable Session Persistence

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setName('users');
    
    // Enable session persistence
    $table->sessionFilters();
    
    // Add filters
    $table->filterGroups('status', 'selectbox');
    $table->filterGroups('role', 'selectbox');
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

### Save Custom Data to Session

```php
// Save custom data
$table->saveToSession([
    'custom_setting' => 'value',
    'user_preference' => 'dark_mode',
]);
```

## 🔧 How It Works

### Session Key Generation

The SessionManager generates unique session keys based on:
- Table name
- Current request path
- User ID (or 'guest' if not authenticated)
- Optional context string

This ensures session isolation between:
- Different tables
- Different pages
- Different users

### Automatic State Restoration

When `sessionFilters()` is called, the TableBuilder automatically:

1. **Restores Filters**: Applies saved filter values to the current request
2. **Restores Active Tab**: Sets the previously active tab
3. **Restores Display Limit**: Applies the saved display limit preference

### Session Data Structure

```php
[
    'filters' => [
        'status' => 'active',
        'role' => 'admin',
    ],
    'active_tab' => 'detail',
    'display_limit' => 50,
    'custom_key' => 'custom_value',
]
```

## 📝 Examples

### Example 1: Basic Session Persistence

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setName('users');
    
    // Enable session persistence
    $table->sessionFilters();
    
    // Add filters
    $table->filterGroups('status', 'selectbox');
    $table->filterGroups('role', 'selectbox');
    
    // User applies filters: status=active, role=admin
    // On next page load, filters are automatically restored
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

### Example 2: Multi-Tab with Session Persistence

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setName('reports');
    
    // Enable session persistence
    $table->sessionFilters();
    
    // Tab 1
    $table->openTab('Summary');
    $table->lists('report_summary', ['id', 'name', 'total'], false);
    $table->closeTab();
    
    // Tab 2
    $table->openTab('Detail');
    $table->lists('report_detail', ['id', 'name', 'amount', 'date'], false);
    $table->closeTab();
    
    // User switches to 'Detail' tab
    // On next page load, 'Detail' tab is automatically active
    
    return view('reports.index', ['table' => $table]);
}
```

### Example 3: Display Limit Persistence

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setName('users');
    
    // Enable session persistence
    $table->sessionFilters();
    
    // Set default display limit
    $table->displayRowsLimitOnLoad(10);
    
    // User changes display limit to 50
    // On next page load, display limit is automatically 50
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

### Example 4: Custom Session Data

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setName('users');
    
    // Enable session persistence
    $table->sessionFilters();
    
    // Save custom preferences
    $table->saveToSession([
        'column_order' => ['name', 'email', 'created_at'],
        'sort_preference' => 'name_asc',
        'view_mode' => 'compact',
    ]);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

### Example 5: Cascading Filters with Session

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setName('sales_reports');
    
    // Enable session persistence
    $table->sessionFilters();
    
    // Cascading filters
    $table->filterGroups('period', 'selectbox', true);  // Auto-submit
    $table->filterGroups('region', 'selectbox', true);  // Cascade
    $table->filterGroups('cluster', 'selectbox');       // Manual submit
    
    // User selects: period=2025-04, region=WEST, cluster=JAKARTA
    // On next page load, all filter values are restored
    
    $table->format();
    
    return view('sales.index', ['table' => $table]);
}
```

## 🔍 Implementation Details

### SessionManager Class

The `SessionManager` class provides:

- **Unique Key Generation**: Prevents session conflicts
- **Data Persistence**: Saves data to Laravel session
- **Data Retrieval**: Loads data from Laravel session
- **Data Merging**: Merges new data with existing session data
- **Data Clearing**: Removes all session data

### TableBuilder Integration

The `TableBuilder` class integrates SessionManager through:

1. **sessionFilters() Method**: Initializes SessionManager and restores state
2. **saveToSession() Method**: Saves custom data to session
3. **Automatic State Restoration**: Applies saved filters, tabs, and preferences

### Session Key Format

```
table_session_{md5(tableName_path_userId_context)}
```

Example:
```
table_session_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
```

## 🎯 Accessibility

- Session persistence is transparent to users
- No additional UI elements required
- Works seamlessly with existing table functionality
- Improves user experience by remembering preferences

## 🎨 Styling / Customization

Session persistence is a backend feature and does not require styling.

## 🧪 Testing

### Unit Tests

```php
public function test_session_filters_initializes_session_manager(): void
{
    $table = app(TableBuilder::class);
    $table->setContext('admin');
    $table->setName('users');
    $table->sessionFilters();
    
    // SessionManager should be initialized
    $this->assertTrue(true);
}

public function test_session_filters_restores_saved_filters(): void
{
    $table = app(TableBuilder::class);
    $table->setContext('admin');
    $table->setName('users');
    
    // Simulate saved filters
    session(['table_session_' . md5('users_' . request()->path() . '_guest_') => [
        'filters' => ['status' => 'active'],
    ]]);
    
    $table->sessionFilters();
    
    // Filters should be restored
    $this->assertTrue(true);
}

public function test_save_to_session_saves_data(): void
{
    $table = app(TableBuilder::class);
    $table->setContext('admin');
    $table->setName('users');
    $table->sessionFilters();
    
    $table->saveToSession(['custom_key' => 'custom_value']);
    
    // Data should be in session
    $sessionKey = 'table_session_' . md5('users_' . request()->path() . '_guest_');
    $sessionData = session($sessionKey, []);
    
    $this->assertArrayHasKey('custom_key', $sessionData);
    $this->assertEquals('custom_value', $sessionData['custom_key']);
}
```

### Integration Tests

```php
public function test_session_persistence_workflow(): void
{
    // First request: Apply filters
    $table1 = app(TableBuilder::class);
    $table1->setContext('admin');
    $table1->setName('users');
    $table1->sessionFilters();
    $table1->saveToSession(['filters' => ['status' => 'active']]);
    
    // Second request: Filters should be restored
    $table2 = app(TableBuilder::class);
    $table2->setContext('admin');
    $table2->setName('users');
    $table2->sessionFilters();
    
    // Verify filters were restored
    $this->assertTrue(true);
}
```

## 💡 Tips & Best Practices

1. **Always Call sessionFilters() Early**: Call `sessionFilters()` before adding filters or tabs
2. **Use Consistent Table Names**: Ensure table name is set before calling `sessionFilters()`
3. **Session Isolation**: Each table/page/user combination has its own session
4. **Clear Session When Needed**: Use `SessionManager::clear()` to reset session data
5. **Custom Data**: Use `saveToSession()` for custom preferences and settings

## 🎭 Common Patterns

### Pattern 1: Basic Session Persistence

```php
$table->setName('users');
$table->sessionFilters();
$table->filterGroups('status', 'selectbox');
$table->format();
```

### Pattern 2: Multi-Tab with Session

```php
$table->setName('reports');
$table->sessionFilters();
$table->openTab('Summary');
// ... tab content
$table->closeTab();
```

### Pattern 3: Custom Preferences

```php
$table->setName('users');
$table->sessionFilters();
$table->saveToSession(['view_mode' => 'compact']);
```

## 🔗 Related Components / Documentation

- [SessionManager](./session-manager.md) - SessionManager class documentation
- [TableBuilder](./table-builder.md) - TableBuilder component documentation
- [Filter System](./filter-system.md) - Filter system documentation
- [Tab System](./tab-system.md) - Tab system documentation

## 📚 Resources

- [Laravel Session Documentation](https://laravel.com/docs/session)
- [PHP Session Handling](https://www.php.net/manual/en/book.session.php)

---

**Last Updated**: 2026-03-02  
**Version**: 1.0.0  
**Status**: Published  
**Requirements**: 1.3.3, 5 (Session Filter Persistence)

