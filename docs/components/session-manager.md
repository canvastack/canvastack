# SessionManager - Session Persistence for TableBuilder

## 📦 Location

- **File Location**: `packages/canvastack/canvastack/src/Components/Table/Session/SessionManager.php`
- **Test Location**: `packages/canvastack/canvastack/tests/Unit/Components/Table/Session/SessionManagerTest.php`
- **Related Files**: 
  - `TableBuilder.php` - Uses SessionManager for persistence
  - `FilterManager.php` - Uses SessionManager for filter state
  - `TabManager.php` - Uses SessionManager for tab state

## 🎯 Features

- Unique session key generation per table/user/context
- Save and load table state (filters, tabs, display limits)
- Merge data with existing session data
- Clear all or specific session data
- Check if key exists in session
- Support for arrays and nested data structures
- Isolated sessions per table
- User-specific sessions

## 📖 Basic Usage

```php
use Canvastack\Canvastack\Components\Table\Session\SessionManager;

// Create session manager
$session = new SessionManager('users_table');

// Save data
$session->save([
    'filters' => ['status' => 'active'],
    'active_tab' => 'summary',
    'display_limit' => 25
]);

// Load data
$data = $session->load();

// Get specific value
$filters = $session->get('filters', []);

// Check if key exists
if ($session->has('filters')) {
    // ...
}

// Clear all data
$session->clear();
```

## 🔧 Session Key Generation

### How It Works

The session key is generated using:
1. **Table name** - Identifies which table the session belongs to
2. **Request path** - Isolates sessions per page/route
3. **User ID** - Separates sessions per user (or 'guest' for unauthenticated)
4. **Context** - Additional context for further isolation (optional)

### Key Format

```
table_session_{md5(tableName_path_userId_context)}
```

### Examples

```php
// Basic usage
$session = new SessionManager('users');
// Key: table_session_abc123... (includes table name, path, user ID)

// With context
$session = new SessionManager('users', 'admin_panel');
// Key: table_session_def456... (includes context for isolation)

// Different users get different keys
// User 1: table_session_abc123...
// User 2: table_session_xyz789...
```

## 🔍 Implementation Details

### Constructor

```php
public function __construct(string $tableName, string $context = '')
{
    $this->sessionKey = $this->generateKey($tableName, $context);
    $this->load();
}
```

- Automatically generates session key
- Loads existing session data on instantiation

### Generate Key Method

```php
protected function generateKey(string $tableName, string $context): string
{
    $path = request()->path();
    $userId = auth()->id() ?? 'guest';

    return 'table_session_' . md5($tableName . '_' . $path . '_' . $userId . '_' . $context);
}
```

**Key Components**:
- `request()->path()` - Current request path (e.g., `/admin/users`)
- `auth()->id()` - Authenticated user ID or 'guest'
- `md5()` - Hashes the combined string for a unique key

### Save Method

```php
public function save(array $data): void
{
    $this->data = array_merge($this->data, $data);
    session([$this->sessionKey => $this->data]);
}
```

- Merges new data with existing data
- Persists to Laravel session

### Load Method

```php
public function load(): array
{
    $this->data = session($this->sessionKey, []);
    return $this->data;
}
```

- Loads data from Laravel session
- Returns empty array if no data exists

## 📝 Examples

### Example 1: Filter Persistence

```php
// Save filters
$session = new SessionManager('users_table');
$session->save([
    'filters' => [
        'status' => 'active',
        'role' => 'admin',
        'created_after' => '2024-01-01'
    ]
]);

// Load filters on next request
$session = new SessionManager('users_table');
$filters = $session->get('filters', []);
// Returns: ['status' => 'active', 'role' => 'admin', 'created_after' => '2024-01-01']
```

### Example 2: Tab State Persistence

```php
// Save active tab
$session = new SessionManager('reports_table');
$session->save(['active_tab' => 'monthly']);

// Load active tab on next request
$session = new SessionManager('reports_table');
$activeTab = $session->get('active_tab', 'summary');
// Returns: 'monthly'
```

### Example 3: Display Limit Persistence

```php
// Save display limit
$session = new SessionManager('products_table');
$session->save(['display_limit' => 50]);

// Load display limit on next request
$session = new SessionManager('products_table');
$limit = $session->get('display_limit', 10);
// Returns: 50
```

### Example 4: Multiple Data Types

```php
$session = new SessionManager('dashboard');
$session->save([
    'filters' => ['period' => '2024-Q1'],
    'sort' => ['column' => 'created_at', 'direction' => 'desc'],
    'display_limit' => 25,
    'show_archived' => false
]);

// Load all data
$data = $session->all();
// Returns: ['filters' => [...], 'sort' => [...], 'display_limit' => 25, 'show_archived' => false]
```

### Example 5: Isolated Sessions

```php
// Different tables have isolated sessions
$usersSession = new SessionManager('users');
$usersSession->save(['filters' => ['status' => 'active']]);

$productsSession = new SessionManager('products');
$productsSession->save(['filters' => ['category' => 'electronics']]);

// Each table maintains its own session
$usersFilters = $usersSession->get('filters');
// Returns: ['status' => 'active']

$productsFilters = $productsSession->get('filters');
// Returns: ['category' => 'electronics']
```

## 🎮 API Reference

### Constructor

```php
public function __construct(string $tableName, string $context = '')
```

**Parameters**:
- `$tableName` (string) - Table name for session key generation
- `$context` (string, optional) - Additional context for isolation

### save()

```php
public function save(array $data): void
```

**Parameters**:
- `$data` (array) - Data to save to session

**Description**: Merges new data with existing session data and persists to session.

### load()

```php
public function load(): array
```

**Returns**: Array of session data

**Description**: Loads session data into internal cache.

### get()

```php
public function get(string $key, $default = null)
```

**Parameters**:
- `$key` (string) - Data key
- `$default` (mixed, optional) - Default value if key doesn't exist

**Returns**: Value from session or default

### has()

```php
public function has(string $key): bool
```

**Parameters**:
- `$key` (string) - Data key

**Returns**: True if key exists

### clear()

```php
public function clear(): void
```

**Description**: Removes all data from session and internal cache.

### all()

```php
public function all(): array
```

**Returns**: All session data

### forget()

```php
public function forget(string $key): void
```

**Parameters**:
- `$key` (string) - Data key to remove

**Description**: Removes specific key from session.

### set()

```php
public function set(string $key, $value): void
```

**Parameters**:
- `$key` (string) - Data key
- `$value` (mixed) - Value to set

**Description**: Sets specific value in session.

### getSessionKey()

```php
public function getSessionKey(): string
```

**Returns**: Current session key

## 🧪 Testing

### Unit Tests

All tests are located in `tests/Unit/Components/Table/Session/SessionManagerTest.php`.

**Test Coverage**: 27 tests, 44 assertions, 100% passing

**Key Tests**:
- Session key generation
- Data save and load
- Get with default values
- Has key checking
- Clear all data
- Forget specific keys
- Set specific values
- Session persistence across instances
- Isolated sessions per table
- User-specific sessions
- Large data sets

### Running Tests

```bash
cd packages/canvastack/canvastack
./vendor/bin/phpunit tests/Unit/Components/Table/Session/SessionManagerTest.php
```

**Expected Output**:
```
PHPUnit 11.5.55 by Sebastian Bergmann and contributors.

...........................                                       27 / 27 (100%)

Time: 00:00.898, Memory: 24.00 MB

OK (27 tests, 44 assertions)
```

## 💡 Tips & Best Practices

1. **Use Descriptive Table Names** - Use clear, unique table names to avoid session conflicts
   ```php
   // Good
   $session = new SessionManager('admin_users_table');
   
   // Bad
   $session = new SessionManager('table1');
   ```

2. **Use Context for Isolation** - Use context parameter when you need additional isolation
   ```php
   // Separate sessions for admin and public views
   $adminSession = new SessionManager('users', 'admin');
   $publicSession = new SessionManager('users', 'public');
   ```

3. **Clear Sessions When Needed** - Clear sessions when filters are reset or user logs out
   ```php
   // Clear all filters
   $session->clear();
   
   // Clear specific filter
   $session->forget('filters');
   ```

4. **Provide Default Values** - Always provide sensible defaults when getting values
   ```php
   // Good
   $limit = $session->get('display_limit', 10);
   
   // Bad
   $limit = $session->get('display_limit'); // Could be null
   ```

5. **Merge Data Carefully** - Remember that `save()` merges data, use `set()` for single values
   ```php
   // Merge multiple values
   $session->save(['key1' => 'value1', 'key2' => 'value2']);
   
   // Set single value
   $session->set('key1', 'value1');
   ```

## 🎭 Common Patterns

### Pattern 1: Filter Persistence in TableBuilder

```php
public function sessionFilters(): self
{
    if (!$this->sessionManager) {
        $this->sessionManager = new SessionManager($this->tableName ?? 'default');
    }
    
    // Load filters from session
    $savedFilters = $this->sessionManager->get('filters', []);
    if (!empty($savedFilters)) {
        $this->filterManager->setActiveFilters($savedFilters);
    }
    
    return $this;
}
```

### Pattern 2: Tab State Persistence

```php
public function restoreTabState(): void
{
    $session = new SessionManager($this->tableName);
    
    // Load active tab from session
    $savedTab = $session->get('active_tab');
    if ($savedTab) {
        $this->tabManager->setActiveTab($savedTab);
    }
}

public function saveTabState(string $activeTab): void
{
    $session = new SessionManager($this->tableName);
    $session->save(['active_tab' => $activeTab]);
}
```

### Pattern 3: Display Limit Persistence

```php
public function getDisplayLimit()
{
    // Check session first
    if ($this->sessionManager && $this->sessionManager->has('display_limit')) {
        return $this->sessionManager->get('display_limit');
    }
    
    return $this->displayLimit;
}

public function saveDisplayLimit($limit): void
{
    if ($this->sessionManager) {
        $this->sessionManager->save(['display_limit' => $limit]);
    }
}
```

## 🔗 Related Components

- [TableBuilder](./table-builder.md) - Main table component that uses SessionManager
- [FilterManager](./filter-manager.md) - Filter management with session persistence
- [TabManager](./tab-manager.md) - Tab management with session persistence
- [StateManager](./state-manager.md) - State management for configuration isolation

## 📚 Resources

- [Laravel Session Documentation](https://laravel.com/docs/session)
- [PHP Session Handling](https://www.php.net/manual/en/book.session.php)

---

**Last Updated**: 2026-03-02  
**Version**: 1.0.0  
**Status**: Complete  
**Test Coverage**: 100% (27 tests, 44 assertions)
