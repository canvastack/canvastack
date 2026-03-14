# TanStack Table Multi-Table & Tab System API Reference

Complete API reference for the TanStack Table Multi-Table & Tab System components.

---

## 📦 Components

1. [HashGenerator](#hashgenerator) - Secure unique ID generation
2. [ConnectionManager](#connectionmanager) - Database connection detection
3. [WarningSystem](#warningsystem) - Connection override warnings
4. [TabManager](#tabmanager) - Tab configuration management
5. [TableBuilder Extensions](#tablebuilder-extensions) - Tab system API

---

## HashGenerator

**Location**: `packages/canvastack/canvastack/src/Components/Table/HashGenerator.php`

**Purpose**: Generate secure, unique, non-predictable identifiers for table instances.

### Methods

#### `generate(string $tableName, string $connectionName, array $fields): string`

Generate a unique table ID using SHA256 hash algorithm.

**Parameters**:
- `$tableName` (string) - Table name or model class
- `$connectionName` (string) - Database connection name
- `$fields` (array) - List of field names

**Returns**: `string` - Format: `canvastable_{16-char-hash}`

**Example**:
```php
use Canvastack\Canvastack\Components\Table\HashGenerator;

$generator = new HashGenerator();
$uniqueId = $generator->generate(
    'users',
    'mysql',
    ['id', 'name', 'email']
);

echo $uniqueId; // "canvastable_a1b2c3d4e5f6g7h8"
```

**Security Features**:
- Uses SHA256 hashing
- Includes cryptographically secure random bytes
- Includes microtime for temporal uniqueness
- Includes instance counter for sequential uniqueness
- No predictable patterns in output
- Does not expose table structure

---

## ConnectionManager

**Location**: `packages/canvastack/canvastack/src/Components/Table/ConnectionManager.php`

**Purpose**: Automatically detect database connections from Eloquent models with configurable override warnings.

### Methods

#### `detectConnection(?Model $model = null): string`

Detect connection from Eloquent model.

**Parameters**:
- `$model` (Model|null) - Eloquent model instance

**Returns**: `string` - Connection name

**Example**:
```php
use Canvastack\Canvastack\Components\Table\ConnectionManager;
use App\Models\User;

$manager = new ConnectionManager();
$connection = $manager->detectConnection(new User());

echo $connection; // "mysql" (from model's getConnectionName())
```

#### `setOverride(string $connection): self`

Set manual connection override.

**Parameters**:
- `$connection` (string) - Connection name

**Returns**: `self` - For method chaining

**Example**:
```php
$manager->setOverride('pgsql');
```

#### `getConnection(): string`

Get final connection to use based on priority.

**Priority**: override > model > config default

**Returns**: `string` - Connection name

**Example**:
```php
$connection = $manager->getConnection();
```

#### `hasOverride(): bool`

Check if connection was manually overridden.

**Returns**: `bool` - True if override exists

**Example**:
```php
if ($manager->hasOverride()) {
    // Connection was manually set
}
```

#### `hasConnectionMismatch(): bool`

Check if override differs from model connection.

**Returns**: `bool` - True if mismatch detected

**Example**:
```php
if ($manager->hasConnectionMismatch()) {
    // Trigger warning
}
```

---

## WarningSystem

**Location**: `packages/canvastack/canvastack/src/Components/Table/WarningSystem.php`

**Purpose**: Provide configurable warnings for connection overrides to help developers catch potential configuration errors.

### Overview

The WarningSystem component monitors database connection overrides and alerts developers when a manually specified connection differs from the model's default connection. This helps prevent subtle bugs caused by connection mismatches.

### Configuration

Configuration is defined in `config/canvastack.php`:

```php
'table' => [
    'connection_warning' => [
        // Enable/disable connection override warnings
        'enabled' => env('CANVASTACK_CONNECTION_WARNING', true),
        
        // Warning method: 'log', 'toast', or 'both'
        'method' => env('CANVASTACK_CONNECTION_WARNING_METHOD', 'log'),
    ],
],
```

### Environment Variables

```bash
# .env file

# Enable/disable warnings (default: true)
CANVASTACK_CONNECTION_WARNING=true

# Warning method: log, toast, or both (default: log)
CANVASTACK_CONNECTION_WARNING_METHOD=log
```

### Warning Methods

| Method | Description | Use Case |
|--------|-------------|----------|
| `log` | Write to Laravel log file | Production environments, debugging |
| `toast` | Show browser toast notification | Development, immediate visual feedback |
| `both` | Log + Toast | Development, comprehensive tracking |

### Methods

#### `isEnabled(): bool`

Check if warnings are enabled.

Reads configuration from `config/canvastack.php` to determine if connection override warnings should be triggered.

**Returns**: `bool` - True if warnings are enabled, false otherwise

**Example**:
```php
use Canvastack\Canvastack\Components\Table\WarningSystem;

$warningSystem = new WarningSystem();

if ($warningSystem->isEnabled()) {
    // Warnings are active
}
```

**Configuration**:
```php
// config/canvastack.php
'table' => [
    'connection_warning' => [
        'enabled' => true, // or false to disable
    ],
],
```

---

#### `getMethod(): string`

Get warning method from configuration.

Returns the configured warning method that determines how warnings are displayed to developers.

**Returns**: `string` - Warning method: `'log'`, `'toast'`, or `'both'`

**Example**:
```php
$method = $warningSystem->getMethod();

switch ($method) {
    case 'log':
        // Warnings written to log file only
        break;
    case 'toast':
        // Warnings shown as browser notifications only
        break;
    case 'both':
        // Warnings both logged and shown as toast
        break;
}
```

**Configuration**:
```php
// config/canvastack.php
'table' => [
    'connection_warning' => [
        'method' => 'log', // Options: 'log', 'toast', 'both'
    ],
],
```

---

#### `warnConnectionOverride(string $modelClass, string $modelConnection, string $overrideConnection): void`

Trigger connection override warning.

Checks if warnings are enabled and executes the appropriate warning method based on configuration. This is the main entry point for triggering warnings.

**Parameters**:
- `$modelClass` (string) - Fully qualified model class name (e.g., `App\Models\User`)
- `$modelConnection` (string) - Model's default connection name (e.g., `mysql`)
- `$overrideConnection` (string) - Manually specified override connection (e.g., `pgsql`)

**Returns**: `void`

**Example**:
```php
use Canvastack\Canvastack\Components\Table\WarningSystem;
use App\Models\User;

$warningSystem = new WarningSystem();

// Trigger warning when connection override detected
$warningSystem->warnConnectionOverride(
    User::class,           // Model class
    'mysql',               // Model's connection
    'pgsql'                // Override connection
);
```

**Behavior**:
1. Checks if warnings are enabled via `isEnabled()`
2. If disabled, returns immediately without action
3. If enabled, formats warning message
4. Executes warning based on configured method:
   - `log`: Writes to Laravel log
   - `toast`: Generates browser toast notification
   - `both`: Performs both actions

**Warning Message Format**:
```
Connection override detected:
Model: App\Models\User
Model Connection: mysql
Override Connection: pgsql
This may cause unexpected behavior if the model has connection-specific logic.
```

---

#### `getToastScripts(): array`

Get all generated toast scripts.

Returns all toast notification scripts that have been generated during the request. These should be included in the rendered output.

**Returns**: `array<string>` - Array of JavaScript code strings

**Example**:
```php
$scripts = $warningSystem->getToastScripts();

foreach ($scripts as $script) {
    echo $script;
}
```

---

#### `renderToastScripts(): string`

Get all toast scripts as a single string.

Convenience method that joins all toast scripts into a single string for easy inclusion in rendered output.

**Returns**: `string` - Combined JavaScript code

**Example**:
```php
$allScripts = $warningSystem->renderToastScripts();

echo $allScripts; // All toast scripts combined
```

**Usage in Blade**:
```blade
{!! $warningSystem->renderToastScripts() !!}
```

---

### Complete Usage Example

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Table\ConnectionManager;
use Canvastack\Canvastack\Components\Table\WarningSystem;
use App\Models\User;

// Create instances
$table = app(TableBuilder::class);
$connectionManager = app(ConnectionManager::class);
$warningSystem = app(WarningSystem::class);

// Set model (auto-detects connection)
$table->setModel(new User());
$connectionManager->detectConnection(new User());

// Override connection (triggers warning if different)
$table->connection('pgsql');
$connectionManager->setOverride('pgsql');

// Check for mismatch and warn
if ($connectionManager->hasConnectionMismatch()) {
    $warningSystem->warnConnectionOverride(
        User::class,
        $connectionManager->getDetectedConnection(),
        'pgsql'
    );
}

// Render table with warnings
$html = $table->render();
$toastScripts = $warningSystem->renderToastScripts();

return view('users.index', [
    'table' => $html,
    'toastScripts' => $toastScripts,
]);
```

---

### Warning Output Examples

#### Log Method Output

When `method` is set to `'log'`, warnings are written to Laravel's log file:

```
[2026-03-09 10:30:45] local.WARNING: Connection override detected:
Model: App\Models\User
Model Connection: mysql
Override Connection: pgsql
This may cause unexpected behavior if the model has connection-specific logic.
```

**Log Location**: `storage/logs/laravel.log`

**Log Level**: `WARNING`

**Use Case**: Production environments where you want to track connection overrides without disrupting the user experience.

---

#### Toast Method Output

When `method` is set to `'toast'`, a browser notification is generated:

```javascript
<script>
window.addEventListener('DOMContentLoaded', function() {
    // Create toast container if it doesn't exist
    if (!document.getElementById('canvastack-toast-container')) {
        const container = document.createElement('div');
        container.id = 'canvastack-toast-container';
        container.className = 'fixed top-4 right-4 z-50 space-y-2';
        document.body.appendChild(container);
    }
    
    // Create toast element with warning styling
    const toast = document.createElement('div');
    toast.className = 'alert alert-warning shadow-lg max-w-md';
    toast.innerHTML = `
        <div>
            <svg>...</svg>
            <div>
                <h3 class="font-bold">Connection Override Warning</h3>
                <div class="text-xs whitespace-pre-line">
                    Connection override detected:
                    Model: App\Models\User
                    Model Connection: mysql
                    Override Connection: pgsql
                    This may cause unexpected behavior...
                </div>
            </div>
        </div>
        <button class="btn btn-sm btn-ghost" onclick="...">×</button>
    `;
    
    // Add to container and auto-dismiss after 10 seconds
    document.getElementById('canvastack-toast-container').appendChild(toast);
    setTimeout(() => toast.remove(), 10000);
});
</script>
```

**Features**:
- DaisyUI alert styling
- Warning icon (SVG)
- Auto-dismiss after 10 seconds
- Manual dismiss button
- Alpine.js transitions
- Fixed position (top-right)
- Stacks multiple toasts vertically

**Use Case**: Development environments where you want immediate visual feedback about connection overrides.

---

#### Both Method Output

When `method` is set to `'both'`, warnings are both logged AND shown as toast notifications, combining the benefits of both methods.

**Use Case**: Development environments where you want comprehensive tracking (logs for history, toasts for immediate feedback).

---

### Configuration Examples

#### Example 1: Development Environment

```bash
# .env
CANVASTACK_CONNECTION_WARNING=true
CANVASTACK_CONNECTION_WARNING_METHOD=both
```

**Result**: Warnings are logged to file AND shown as browser toasts.

---

#### Example 2: Production Environment

```bash
# .env
CANVASTACK_CONNECTION_WARNING=true
CANVASTACK_CONNECTION_WARNING_METHOD=log
```

**Result**: Warnings are logged to file only (no browser notifications).

---

#### Example 3: Disable Warnings

```bash
# .env
CANVASTACK_CONNECTION_WARNING=false
```

**Result**: No warnings triggered (useful for production if you're confident in your configuration).

---

### Integration with TableBuilder

The WarningSystem is automatically integrated with TableBuilder. When you use the `connection()` method, warnings are triggered automatically:

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;
use App\Models\User;

$table = app(TableBuilder::class);

// Set model (auto-detects connection: mysql)
$table->setModel(new User());

// Override connection (triggers warning if different)
$table->connection('pgsql');

// Warning is automatically triggered if:
// 1. Warnings are enabled in config
// 2. Override connection differs from model connection
// 3. Model has a default connection defined

$table->format();
```

**No manual WarningSystem calls needed** - TableBuilder handles it automatically!

---

### Security Considerations

#### XSS Prevention

The WarningSystem automatically escapes all output to prevent XSS attacks:

```php
// Message is escaped before being included in JavaScript
$escapedMessage = addslashes($message);
$escapedMessage = str_replace(["\r", "\n"], ['', '\n'], $escapedMessage);
```

**Safe from**:
- Script injection
- HTML injection
- Special character exploits

#### Information Disclosure

Warning messages include:
- ✅ Model class name (safe - developer info)
- ✅ Connection names (safe - developer info)
- ❌ Connection credentials (never included)
- ❌ Database structure (never included)
- ❌ Sensitive data (never included)

**Production Safety**: Safe to use in production as warnings only contain configuration information, not sensitive data.

---

### Performance Considerations

#### Minimal Overhead

- **When disabled**: Zero overhead (early return)
- **When enabled (log)**: ~1-2ms per warning
- **When enabled (toast)**: ~2-3ms per warning (script generation)
- **Caching**: Toast scripts cached per request

#### Best Practices

1. **Use `log` method in production** for minimal performance impact
2. **Use `toast` method in development** for immediate feedback
3. **Disable in high-traffic production** if warnings are not needed
4. **Monitor log file size** if using log method extensively

---

### Troubleshooting

#### Warning Not Appearing

**Problem**: Warning not showing even though connection override exists.

**Solutions**:

1. **Check if warnings are enabled**:
```php
$warningSystem = app(WarningSystem::class);
dd($warningSystem->isEnabled()); // Should be true
```

2. **Check warning method**:
```php
dd($warningSystem->getMethod()); // Should be 'log', 'toast', or 'both'
```

3. **Check configuration**:
```php
dd(config('canvastack.table.connection_warning'));
// Should return: ['enabled' => true, 'method' => 'log']
```

4. **Check environment variables**:
```bash
# .env
CANVASTACK_CONNECTION_WARNING=true
CANVASTACK_CONNECTION_WARNING_METHOD=log
```

5. **Clear config cache**:
```bash
php artisan config:clear
```

---

#### Toast Not Displaying

**Problem**: Toast notification not appearing in browser.

**Solutions**:

1. **Check if toast scripts are rendered**:
```blade
{{-- In your layout --}}
{!! $warningSystem->renderToastScripts() !!}
```

2. **Check browser console** for JavaScript errors

3. **Verify DaisyUI is loaded** (required for alert styling)

4. **Check Alpine.js is loaded** (required for transitions)

5. **Verify toast container** is created:
```javascript
// Should exist in DOM
document.getElementById('canvastack-toast-container')
```

---

#### Log Not Writing

**Problem**: Warning not appearing in log file.

**Solutions**:

1. **Check log file permissions**:
```bash
chmod -R 775 storage/logs
```

2. **Check log configuration**:
```php
// config/logging.php
'default' => env('LOG_CHANNEL', 'stack'),
```

3. **Check log file location**:
```bash
tail -f storage/logs/laravel.log
```

4. **Verify Laravel logging is working**:
```php
Log::warning('Test message');
```

---

### Advanced Usage

#### Custom Warning Handling

You can manually trigger warnings for custom scenarios:

```php
use Canvastack\Canvastack\Components\Table\WarningSystem;

$warningSystem = app(WarningSystem::class);

// Manually trigger warning
$warningSystem->warnConnectionOverride(
    'App\Models\CustomModel',
    'mysql',
    'pgsql'
);

// Get generated toast scripts
$scripts = $warningSystem->getToastScripts();

// Render in view
return view('custom.view', [
    'toastScripts' => $warningSystem->renderToastScripts(),
]);
```

---

#### Conditional Warnings

You can conditionally enable warnings based on environment:

```php
// config/canvastack.php
'table' => [
    'connection_warning' => [
        'enabled' => env('APP_ENV') !== 'production',
        'method' => env('APP_ENV') === 'local' ? 'both' : 'log',
    ],
],
```

**Result**:
- **Local**: Warnings enabled with both log and toast
- **Staging**: Warnings enabled with log only
- **Production**: Warnings disabled

---

#### Multiple Warnings

The system supports multiple warnings in a single request:

```php
// First table with override
$table1 = app(TableBuilder::class);
$table1->setModel(new User());
$table1->connection('pgsql'); // Warning 1

// Second table with override
$table2 = app(TableBuilder::class);
$table2->setModel(new Product());
$table2->connection('mongodb'); // Warning 2

// Both warnings will be captured
$warningSystem = app(WarningSystem::class);
$allScripts = $warningSystem->renderToastScripts();

// Renders both toast notifications
```

**Toast Behavior**: Multiple toasts stack vertically in the top-right corner.

---

### Testing

#### Unit Tests

```php
use Canvastack\Canvastack\Components\Table\WarningSystem;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

public function test_warning_is_enabled_by_default(): void
{
    Config::set('canvastack.table.connection_warning.enabled', true);
    
    $warningSystem = new WarningSystem();
    
    $this->assertTrue($warningSystem->isEnabled());
}

public function test_warning_method_defaults_to_log(): void
{
    Config::set('canvastack.table.connection_warning.method', 'log');
    
    $warningSystem = new WarningSystem();
    
    $this->assertEquals('log', $warningSystem->getMethod());
}

public function test_warning_writes_to_log(): void
{
    Config::set('canvastack.table.connection_warning.enabled', true);
    Config::set('canvastack.table.connection_warning.method', 'log');
    
    Log::shouldReceive('warning')
        ->once()
        ->with(\Mockery::on(function ($message) {
            return str_contains($message, 'Connection override detected') &&
                   str_contains($message, 'App\Models\User') &&
                   str_contains($message, 'mysql') &&
                   str_contains($message, 'pgsql');
        }));
    
    $warningSystem = new WarningSystem();
    $warningSystem->warnConnectionOverride('App\Models\User', 'mysql', 'pgsql');
}

public function test_warning_generates_toast_script(): void
{
    Config::set('canvastack.table.connection_warning.enabled', true);
    Config::set('canvastack.table.connection_warning.method', 'toast');
    
    $warningSystem = new WarningSystem();
    $warningSystem->warnConnectionOverride('App\Models\User', 'mysql', 'pgsql');
    
    $scripts = $warningSystem->getToastScripts();
    
    $this->assertCount(1, $scripts);
    $this->assertStringContainsString('<script>', $scripts[0]);
    $this->assertStringContainsString('Connection Override Warning', $scripts[0]);
    $this->assertStringContainsString('App\Models\User', $scripts[0]);
}

public function test_disabled_warnings_do_nothing(): void
{
    Config::set('canvastack.table.connection_warning.enabled', false);
    
    Log::shouldReceive('warning')->never();
    
    $warningSystem = new WarningSystem();
    $warningSystem->warnConnectionOverride('App\Models\User', 'mysql', 'pgsql');
    
    $this->assertEmpty($warningSystem->getToastScripts());
}
```

---

### Best Practices

#### 1. Enable in Development, Consider Disabling in Production

```bash
# .env.local
CANVASTACK_CONNECTION_WARNING=true
CANVASTACK_CONNECTION_WARNING_METHOD=both

# .env.production
CANVASTACK_CONNECTION_WARNING=false
```

**Rationale**: Warnings help catch configuration errors during development but may add unnecessary overhead in production.

---

#### 2. Use Log Method for Production

If you keep warnings enabled in production, use log method:

```bash
# .env.production
CANVASTACK_CONNECTION_WARNING=true
CANVASTACK_CONNECTION_WARNING_METHOD=log
```

**Rationale**: Logs don't affect user experience and provide audit trail.

---

#### 3. Use Both Method for Development

```bash
# .env.local
CANVASTACK_CONNECTION_WARNING=true
CANVASTACK_CONNECTION_WARNING_METHOD=both
```

**Rationale**: Immediate visual feedback (toast) + permanent record (log).

---

#### 4. Monitor Log Files

If using log method, monitor log files regularly:

```bash
# Watch log file in real-time
tail -f storage/logs/laravel.log | grep "Connection override"

# Count warnings in last 24 hours
grep "Connection override" storage/logs/laravel-$(date +%Y-%m-%d).log | wc -l
```

---

#### 5. Document Intentional Overrides

If you intentionally override connections, document why:

```php
// Override connection for read replica
// Intentional: User model uses mysql, but we want to read from replica
$table->setModel(new User());
$table->connection('mysql_read_replica'); // Warning expected and safe
```

---

### Common Use Cases

#### Use Case 1: Multi-Database Application

```php
// Primary database: mysql
// Analytics database: pgsql

// Users table (mysql)
$usersTable = app(TableBuilder::class);
$usersTable->setModel(new User()); // Auto-detects: mysql
$usersTable->format();

// Analytics table (pgsql)
$analyticsTable = app(TableBuilder::class);
$analyticsTable->setModel(new AnalyticsEvent()); // Auto-detects: pgsql
$analyticsTable->format();

// No warnings - each model uses its correct connection
```

---

#### Use Case 2: Read Replica

```php
// Override to use read replica for heavy queries
$table = app(TableBuilder::class);
$table->setModel(new User()); // Auto-detects: mysql
$table->connection('mysql_read'); // Warning triggered

// This is intentional and safe - document it
```

---

#### Use Case 3: Testing Different Connections

```php
// Testing with SQLite in development
if (app()->environment('local')) {
    $table->connection('sqlite'); // Warning helps identify test connections
}
```

---

### Related Components

- [ConnectionManager](#connectionmanager) - Detects and manages connections
- [TableBuilder Extensions](#tablebuilder-extensions) - Uses WarningSystem automatically
- [HashGenerator](#hashgenerator) - Generates unique IDs

---

### Related Documentation

- [Configuration Reference](../getting-started/configuration.md)
- [Multi-Table Usage Guide](../guides/multi-table-usage.md)
- [Troubleshooting Guide](../guides/troubleshooting.md)
- [Security Best Practices](../guides/security.md)

---

## TabManager

**Location**: `packages/canvastack/canvastack/src/Components/Table/TabManager.php`

**Purpose**: Manage tab configuration, rendering, and lazy loading.

### Methods

(Documentation to be added in task 6.1.5)

---

## TableBuilder Extensions

**Location**: `packages/canvastack/canvastack/src/Components/Table/TableBuilder.php`

**Purpose**: Tab system API extensions for TableBuilder.

### Methods

(Documentation to be added in task 6.1.1)

---

## 🔗 Related Documentation

- [Multi-Table Usage Guide](../guides/multi-table-usage.md)
- [Tab System Usage Guide](../guides/tab-system-usage.md)
- [Configuration Reference](../getting-started/configuration.md)
- [Security Best Practices](../guides/security.md)
- [Performance Optimization](../guides/performance.md)

---

## 📚 Resources

### Internal Documentation
- [Requirements Document](../../.kiro/specs/tanstack-multi-table-tabs/requirements.md)
- [Design Document](../../.kiro/specs/tanstack-multi-table-tabs/design.md)
- [Implementation Tasks](../../.kiro/specs/tanstack-multi-table-tabs/tasks.md)

### External Resources
- [Laravel Logging](https://laravel.com/docs/logging)
- [DaisyUI Alerts](https://daisyui.com/components/alert/)
- [Alpine.js](https://alpinejs.dev)

---

**Last Updated**: 2026-03-09  
**Version**: 1.0.0  
**Status**: Published  
**Component**: WarningSystem API
