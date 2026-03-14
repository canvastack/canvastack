# Connection Detection Guide

This guide explains how the TableBuilder automatically detects database connections from Eloquent models, how to manually override connections, and how to configure connection override warnings.

## 📦 Location

- **Component**: `Canvastack\Canvastack\Components\Table\ConnectionManager`
- **File**: `packages/canvastack/canvastack/src/Components/Table/ConnectionManager.php`
- **Configuration**: `config/canvastack.php`

## 🎯 Features

- Automatic connection detection from Eloquent models
- Priority-based connection resolution (override > model > default)
- Configurable connection override warnings
- Debug logging for connection detection
- Support for multiple database connections

## 📖 Basic Usage

### Automatic Connection Detection

The TableBuilder automatically detects the database connection from your Eloquent model:

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;
use App\Models\User;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User()); // Connection auto-detected from User model
    
    $table->setFields([
        'name:' . __('ui.labels.name'),
        'email:' . __('ui.labels.email'),
    ]);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

**How it works:**
1. When you call `setModel()`, the TableBuilder calls `getConnectionName()` on the model
2. The detected connection is stored and used for all queries
3. If the model doesn't specify a connection, the default connection from `config/database.php` is used

## 🔧 Connection Priority Resolution

The TableBuilder uses the following priority when determining which connection to use:

```
1. Manual Override (via connection() method)
   ↓ If not set
2. Model Connection (via $model->getConnectionName())
   ↓ If not set
3. Config Default (config('database.default'))
```

### Example: Model with Custom Connection

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsData extends Model
{
    /**
     * The database connection to use.
     */
    protected $connection = 'analytics';
    
    protected $table = 'analytics_data';
}
```

```php
// In controller
public function analytics(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new AnalyticsData()); // Uses 'analytics' connection automatically
    
    $table->setFields([
        'event:' . __('ui.labels.event'),
        'count:' . __('ui.labels.count'),
    ]);
    
    $table->format();
    
    return view('analytics.index', ['table' => $table]);
}
```

## 📝 Manual Connection Override

### Basic Override

You can manually override the connection using the `connection()` method:

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    $table->connection('secondary'); // Override to use 'secondary' connection
    
    $table->setFields([
        'name:' . __('ui.labels.name'),
        'email:' . __('ui.labels.email'),
    ]);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

### When to Use Manual Override

**Use manual override when:**
- Reading from a replica database for reporting
- Using a read-only connection for performance
- Accessing archived data in a separate database
- Testing with a different database

**Example: Read Replica**

```php
public function reports(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Order());
    $table->connection('mysql_read_replica'); // Use read replica for heavy reports
    
    $table->setFields([
        'order_number:' . __('ui.labels.order_number'),
        'total:' . __('ui.labels.total'),
        'created_at:' . __('ui.labels.date'),
    ]);
    
    $table->format();
    
    return view('reports.orders', ['table' => $table]);
}
```

## 🔔 Connection Override Warnings

### Why Warnings Matter

When you manually override a connection that differs from your model's connection, it may cause issues if:
- The model has connection-specific logic
- The database schemas differ
- The model uses connection-specific features

The warning system helps you catch these potential configuration errors.

### Configuring Warnings

Add to your `.env` file:

```bash
# Enable/disable connection override warnings
CANVASTACK_CONNECTION_WARNING=true

# Warning method: log, toast, or both
CANVASTACK_CONNECTION_WARNING_METHOD=log
```

Or configure in `config/canvastack.php`:

```php
'table' => [
    'connection_warning' => [
        'enabled' => env('CANVASTACK_CONNECTION_WARNING', true),
        'method' => env('CANVASTACK_CONNECTION_WARNING_METHOD', 'log'),
        // Options: 'log', 'toast', 'both'
    ],
],
```

### Warning Methods

#### 1. Log Method

Writes warnings to Laravel log file:

```bash
# .env
CANVASTACK_CONNECTION_WARNING_METHOD=log
```

**Log output:**
```
[2026-03-09 10:30:45] local.WARNING: Connection override detected:
Model: App\Models\User
Model Connection: mysql
Override Connection: pgsql
This may cause unexpected behavior if the model has connection-specific logic.
```

#### 2. Toast Method

Shows browser notification using Alpine.js:

```bash
# .env
CANVASTACK_CONNECTION_WARNING_METHOD=toast
```

**Browser output:**
A toast notification appears in the browser with the warning message.

#### 3. Both Method

Combines log and toast:

```bash
# .env
CANVASTACK_CONNECTION_WARNING_METHOD=both
```

### Disabling Warnings

For production environments, you may want to disable warnings:

```bash
# .env
CANVASTACK_CONNECTION_WARNING=false
```

## 📝 Examples

### Example 1: Auto-Detection (No Override)

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;
use App\Models\User;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User()); // Auto-detects 'mysql' connection
    
    $table->setFields([
        'name:' . __('ui.labels.name'),
        'email:' . __('ui.labels.email'),
    ]);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

**Result:**
- Connection detected: `mysql` (from User model)
- No warnings triggered
- Queries run on `mysql` connection

### Example 2: Manual Override with Warning

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User()); // Model uses 'mysql'
    $table->connection('pgsql'); // Override to 'pgsql'
    
    $table->setFields([
        'name:' . __('ui.labels.name'),
        'email:' . __('ui.labels.email'),
    ]);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

**Result:**
- Connection detected: `mysql` (from User model)
- Connection used: `pgsql` (manual override)
- Warning triggered (if enabled)
- Log message written (if method is 'log' or 'both')
- Toast notification shown (if method is 'toast' or 'both')

### Example 3: Read Replica (Valid Override)

```php
public function reports(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Order()); // Model uses 'mysql'
    $table->connection('mysql_read_replica'); // Valid override for reporting
    
    $table->setFields([
        'order_number:' . __('ui.labels.order_number'),
        'customer:' . __('ui.labels.customer'),
        'total:' . __('ui.labels.total'),
        'created_at:' . __('ui.labels.date'),
    ]);
    
    // Optimize for large reports
    $table->cache(300); // Cache for 5 minutes
    $table->eager(['customer', 'items']); // Prevent N+1
    
    $table->format();
    
    return view('reports.orders', ['table' => $table]);
}
```

**Result:**
- Connection detected: `mysql` (from Order model)
- Connection used: `mysql_read_replica` (read replica)
- Warning triggered (expected - you can ignore this for read replicas)
- Queries run on read replica (reduces load on primary database)

### Example 4: Multiple Tables with Different Connections

```php
public function dashboard(TableBuilder $table1, TableBuilder $table2)
{
    // Table 1: Users from primary database
    $table1->setContext('admin');
    $table1->setModel(new User()); // Uses 'mysql'
    $table1->setFields(['name:' . __('ui.labels.name'), 'email:' . __('ui.labels.email')]);
    $table1->format();
    
    // Table 2: Analytics from analytics database
    $table2->setContext('admin');
    $table2->setModel(new AnalyticsData()); // Uses 'analytics' connection
    $table2->setFields(['event:' . __('ui.labels.event'), 'count:' . __('ui.labels.count')]);
    $table2->format();
    
    return view('dashboard', [
        'usersTable' => $table1,
        'analyticsTable' => $table2,
    ]);
}
```

**Result:**
- Table 1 uses `mysql` connection
- Table 2 uses `analytics` connection
- Each table maintains separate connection
- No warnings (no overrides)

### Example 5: Tabs with Different Connections

```php
public function multiDatabase(TableBuilder $table)
{
    $table->setContext('admin');
    
    // Tab 1: Users from primary database
    $table->openTab(__('ui.tabs.users'));
    $table->setModel(new User()); // Uses 'mysql'
    $table->setFields(['name:' . __('ui.labels.name'), 'email:' . __('ui.labels.email')]);
    $table->closeTab();
    
    // Tab 2: Analytics from analytics database
    $table->openTab(__('ui.tabs.analytics'));
    $table->setModel(new AnalyticsData()); // Uses 'analytics'
    $table->setFields(['event:' . __('ui.labels.event'), 'count:' . __('ui.labels.count')]);
    $table->closeTab();
    
    // Tab 3: Logs from logs database
    $table->openTab(__('ui.tabs.logs'));
    $table->setModel(new SystemLog()); // Uses 'logs'
    $table->setFields(['level:' . __('ui.labels.level'), 'message:' . __('ui.labels.message')]);
    $table->closeTab();
    
    $table->format();
    
    return view('admin.multi-database', ['table' => $table]);
}
```

**Result:**
- Each tab uses its model's connection
- Tab 1: `mysql` connection
- Tab 2: `analytics` connection
- Tab 3: `logs` connection
- No warnings (no overrides)
- Lazy loading works across different connections

## 🔍 Implementation Details

### Connection Detection Flow

```
1. Developer calls setModel($model)
   ↓
2. TableBuilder calls ConnectionManager->detectConnection($model)
   ↓
3. ConnectionManager calls $model->getConnectionName()
   ↓
4. Connection name stored in $detectedConnection
   ↓
5. Log detection at debug level
   ↓
6. Developer optionally calls connection($name)
   ↓
7. ConnectionManager stores override in $overrideConnection
   ↓
8. ConnectionManager checks hasConnectionMismatch()
   ↓
9. If mismatch detected, WarningSystem->warnConnectionOverride()
   ↓
10. Warning executed based on configured method
```

### Debug Logging

Enable debug logging to see connection detection:

```bash
# .env
LOG_LEVEL=debug
```

**Log output:**
```
[2026-03-09 10:30:45] local.DEBUG: Connection detected from model:
Model: App\Models\User
Connection: mysql
```

## 🎮 Programmatic Control

### Getting Connection Information

```php
// Get the connection manager
$connectionManager = $table->getConnectionManager();

// Get detected connection
$detected = $connectionManager->getDetectedConnection();

// Get final connection (after priority resolution)
$final = $connectionManager->getConnection();

// Check if override exists
if ($connectionManager->hasOverride()) {
    // Override is set
}

// Check for mismatch
if ($connectionManager->hasConnectionMismatch()) {
    // Override differs from model connection
}
```

### Resetting Connection

```php
// Reset connection detection
$connectionManager->reset();

// Now you can detect again
$connectionManager->detectConnection($newModel);
```

## 🧪 Testing

### Unit Tests

```php
use Canvastack\Canvastack\Components\Table\ConnectionManager;
use Canvastack\Canvastack\Components\Table\WarningSystem;
use Illuminate\Database\Eloquent\Model;

public function test_connection_detected_from_model()
{
    $model = new class extends Model {
        protected $connection = 'mysql';
    };
    
    $warningSystem = $this->createMock(WarningSystem::class);
    $manager = new ConnectionManager($warningSystem);
    
    $connection = $manager->detectConnection($model);
    
    $this->assertEquals('mysql', $connection);
}

public function test_override_connection_triggers_warning()
{
    $model = new class extends Model {
        protected $connection = 'mysql';
    };
    
    $warningSystem = $this->createMock(WarningSystem::class);
    $warningSystem->expects($this->once())
        ->method('warnConnectionOverride');
    
    $manager = new ConnectionManager($warningSystem);
    $manager->detectConnection($model);
    $manager->setOverride('pgsql');
    
    $this->assertTrue($manager->hasConnectionMismatch());
}
```

### Feature Tests

```php
public function test_table_uses_model_connection()
{
    $table = app(TableBuilder::class);
    $table->setContext('admin');
    $table->setModel(new User()); // User model uses 'mysql'
    $table->setFields(['name:Name']);
    $table->format();
    
    $html = $table->render();
    
    // Verify table renders correctly
    $this->assertStringContainsString('canvastable_', $html);
}

public function test_table_uses_override_connection()
{
    $table = app(TableBuilder::class);
    $table->setContext('admin');
    $table->setModel(new User());
    $table->connection('secondary'); // Override connection
    $table->setFields(['name:Name']);
    $table->format();
    
    // Verify override is used
    $this->assertEquals('secondary', $table->getConnectionManager()->getConnection());
}
```

## 💡 Tips & Best Practices

1. **Let Auto-Detection Work** - Don't override connections unless necessary. The auto-detection is designed to work correctly in most cases.

2. **Use Read Replicas for Reports** - Override to read replicas for heavy reporting queries to reduce load on primary database.

3. **Configure Warnings in Development** - Enable warnings in development to catch configuration errors early:
   ```bash
   # .env.local
   CANVASTACK_CONNECTION_WARNING=true
   CANVASTACK_CONNECTION_WARNING_METHOD=both
   ```

4. **Disable Warnings in Production** - Disable warnings in production to avoid performance overhead:
   ```bash
   # .env.production
   CANVASTACK_CONNECTION_WARNING=false
   ```

5. **Document Connection Overrides** - Add comments explaining why you're overriding connections:
   ```php
   // Override to read replica for heavy reporting query
   $table->connection('mysql_read_replica');
   ```

6. **Test with Multiple Connections** - Always test your code with the actual connections you'll use in production.

## 🎭 Common Patterns

### Pattern 1: Multi-Tenant Application

```php
public function index(TableBuilder $table)
{
    $tenant = auth()->user()->tenant;
    
    $table->setContext('admin');
    $table->setModel(new User());
    $table->connection($tenant->database_connection); // Dynamic connection per tenant
    
    $table->setFields([
        'name:' . __('ui.labels.name'),
        'email:' . __('ui.labels.email'),
    ]);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

### Pattern 2: Archive Database

```php
public function archived(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Order());
    $table->connection('archive'); // Use archive database for old orders
    
    $table->setFields([
        'order_number:' . __('ui.labels.order_number'),
        'customer:' . __('ui.labels.customer'),
        'total:' . __('ui.labels.total'),
        'archived_at:' . __('ui.labels.archived_date'),
    ]);
    
    $table->format();
    
    return view('orders.archived', ['table' => $table]);
}
```

### Pattern 3: Cross-Database Reporting

```php
public function crossDatabase(TableBuilder $table1, TableBuilder $table2)
{
    // Table 1: Users from primary database
    $table1->setContext('admin');
    $table1->setModel(new User()); // Uses 'mysql'
    $table1->setFields(['name:' . __('ui.labels.name')]);
    $table1->format();
    
    // Table 2: Analytics from analytics database
    $table2->setContext('admin');
    $table2->setModel(new AnalyticsData()); // Uses 'analytics'
    $table2->setFields(['event:' . __('ui.labels.event')]);
    $table2->format();
    
    return view('reports.cross-database', [
        'usersTable' => $table1,
        'analyticsTable' => $table2,
    ]);
}
```

### Pattern 4: Conditional Connection Based on Environment

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Use read replica in production, primary in development
    if (app()->environment('production')) {
        $table->connection('mysql_read_replica');
    }
    
    $table->setFields([
        'name:' . __('ui.labels.name'),
        'email:' . __('ui.labels.email'),
    ]);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

## 🔧 Troubleshooting

### Issue 1: Connection Not Detected

**Symptom:**
```
SQLSTATE[HY000]: General error: Connection not found
```

**Cause:**
- Model doesn't specify connection
- Default connection not configured

**Solution:**
```php
// Option 1: Set connection in model
class User extends Model
{
    protected $connection = 'mysql';
}

// Option 2: Set default in config/database.php
'default' => env('DB_CONNECTION', 'mysql'),

// Option 3: Override manually
$table->connection('mysql');
```

### Issue 2: Warning Not Showing

**Symptom:**
- Override connection but no warning appears

**Cause:**
- Warnings disabled in configuration
- Wrong warning method configured

**Solution:**
```bash
# Check .env
CANVASTACK_CONNECTION_WARNING=true
CANVASTACK_CONNECTION_WARNING_METHOD=log

# Clear config cache
php artisan config:clear

# Check logs
tail -f storage/logs/laravel.log
```

### Issue 3: Wrong Connection Used

**Symptom:**
- Table shows data from wrong database

**Cause:**
- Connection priority not understood
- Override set incorrectly

**Solution:**
```php
// Debug connection resolution
$connectionManager = $table->getConnectionManager();

// Check detected connection
dd($connectionManager->getDetectedConnection());

// Check final connection
dd($connectionManager->getConnection());

// Check if override exists
dd($connectionManager->hasOverride());
```

### Issue 4: Connection Mismatch in Multi-Table

**Symptom:**
- Multiple tables on same page use wrong connections

**Cause:**
- Reusing same TableBuilder instance
- Connection state not reset

**Solution:**
```php
// ❌ WRONG - Reusing same instance
public function dashboard(TableBuilder $table)
{
    $table->setModel(new User());
    $table->format();
    $usersTable = $table->render();
    
    $table->setModel(new Order()); // Connection not reset!
    $table->format();
    $ordersTable = $table->render();
}

// ✅ CORRECT - Use separate instances
public function dashboard(TableBuilder $table1, TableBuilder $table2)
{
    $table1->setModel(new User());
    $table1->format();
    
    $table2->setModel(new Order());
    $table2->format();
    
    return view('dashboard', [
        'usersTable' => $table1,
        'ordersTable' => $table2,
    ]);
}
```

### Issue 5: Performance Issues with Connection Detection

**Symptom:**
- Slow page load with multiple tables

**Cause:**
- Connection detection happens on every request
- No caching enabled

**Solution:**
```php
// Enable caching for table data
$table->cache(300); // Cache for 5 minutes

// Use eager loading to prevent N+1
$table->eager(['relation1', 'relation2']);

// Use read replica for heavy queries
$table->connection('mysql_read_replica');
```

### Issue 6: Connection Override in Tabs

**Symptom:**
- Tabs use wrong connection after lazy loading

**Cause:**
- Connection state not preserved in tab configuration

**Solution:**
```php
// The system automatically preserves connection per tab
$table->openTab(__('ui.tabs.users'));
$table->setModel(new User());
$table->connection('mysql_read_replica'); // Preserved for this tab
$table->setFields(['name:Name']);
$table->closeTab();

$table->openTab(__('ui.tabs.analytics'));
$table->setModel(new AnalyticsData());
$table->connection('analytics'); // Different connection for this tab
$table->setFields(['event:Event']);
$table->closeTab();

$table->format();
```

## 🔍 Advanced Topics

### Custom Connection Resolution

If you need custom connection resolution logic:

```php
use Canvastack\Canvastack\Components\Table\ConnectionManager;

class CustomConnectionManager extends ConnectionManager
{
    public function detectConnection(?Model $model = null): string
    {
        // Custom logic here
        if ($model && $model instanceof TenantModel) {
            return $this->getTenantConnection($model);
        }
        
        return parent::detectConnection($model);
    }
    
    protected function getTenantConnection(TenantModel $model): string
    {
        return "tenant_{$model->tenant_id}";
    }
}

// Register in service provider
$this->app->bind(ConnectionManager::class, CustomConnectionManager::class);
```

### Connection Pooling

For high-traffic applications, consider connection pooling:

```php
// config/database.php
'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'forge'),
        'username' => env('DB_USERNAME', 'forge'),
        'password' => env('DB_PASSWORD', ''),
        'options' => [
            PDO::ATTR_PERSISTENT => true, // Enable persistent connections
        ],
    ],
],
```

### Monitoring Connection Usage

```php
use Illuminate\Support\Facades\DB;

// Enable query logging
DB::enableQueryLog();

// Render table
$table->render();

// Get queries
$queries = DB::getQueryLog();

// Analyze connections used
foreach ($queries as $query) {
    Log::debug('Query on connection: ' . $query['connection']);
}
```

## 🔗 Related Documentation

- [TableBuilder API Reference](../api/table-multi-tab.md) - Complete API documentation
- [Multi-Table Usage Guide](multi-table-usage.md) - Multiple tables on same page
- [Tab System Usage Guide](tab-system-usage.md) - Tab navigation system
- [Performance Optimization Guide](performance-optimization.md) - Caching and optimization
- [Configuration Reference](../configuration/table-config.md) - All configuration options

## 📚 Resources

- [Laravel Database Connections](https://laravel.com/docs/database#configuration)
- [Eloquent Model Connections](https://laravel.com/docs/eloquent#database-connections)
- [Laravel Logging](https://laravel.com/docs/logging)
- [Read Replicas Best Practices](https://dev.mysql.com/doc/refman/8.0/en/replication.html)

---

**Last Updated**: 2026-03-09  
**Version**: 1.0.0  
**Status**: Published
