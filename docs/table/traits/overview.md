# Available Traits

CanvaStack Table uses a trait-based architecture that provides modular functionality. Each trait handles specific aspects of table functionality, making the system highly extensible and maintainable.

## Table of Contents

- [Core Traits](#core-traits)
- [Feature Traits](#feature-traits)
- [Utility Traits](#utility-traits)
- [Security Traits](#security-traits)
- [Performance Traits](#performance-traits)
- [Trait Dependencies](#trait-dependencies)
- [Usage Examples](#usage-examples)
- [Creating Custom Traits](#creating-custom-traits)

## Core Traits

### Tab Trait
**File**: `src/Objects/Traits/Tab.php`  
**Purpose**: Handles tab-based navigation and multi-table views

```php
trait Tab
{
    public function setTabs($tabs)
    public function addTab($key, $label, $url)
    public function setActiveTab($key)
    public function getTabs()
    public function renderTabs()
}
```

**Usage:**
```php
$this->table->setTabs([
    'active' => ['label' => 'Active Users', 'url' => '/users?status=active'],
    'inactive' => ['label' => 'Inactive Users', 'url' => '/users?status=inactive'],
    'pending' => ['label' => 'Pending Users', 'url' => '/users?status=pending']
]);
```

### ColumnsConfigTrait
**File**: `src/Objects/Traits/ColumnsConfigTrait.php`  
**Purpose**: Manages column configuration, formatting, and display options

```php
trait ColumnsConfigTrait
{
    public function setColumns($columns)
    public function addColumn($column, $label = null)
    public function removeColumn($column)
    public function setColumnWidth($column, $width)
    public function setColumnAlignment($column, $alignment)
    public function setColumnClass($column, $class)
    public function setColumnFormat($column, $format)
    public function hideColumn($column)
    public function showColumn($column)
}
```

**Usage:**
```php
$this->table->setColumnWidth('name', '200px')
            ->setColumnAlignment('salary', 'right')
            ->setColumnClass('status', 'text-center')
            ->hideColumn('id');
```

### AlignAndStyleTrait
**File**: `src/Objects/Traits/AlignAndStyleTrait.php`  
**Purpose**: Handles alignment, styling, and visual formatting

```php
trait AlignAndStyleTrait
{
    public function setTableClass($class)
    public function addTableClass($class)
    public function setRowClass($class)
    public function setHeaderClass($class)
    public function setFooterClass($class)
    public function setResponsiveClass($class)
    public function setTheme($theme)
    public function setCustomCSS($css)
}
```

**Usage:**
```php
$this->table->setTableClass('table table-striped table-hover')
            ->setTheme('dark')
            ->setCustomCSS('
                .table th { background-color: #f8f9fa; }
                .table td { padding: 12px; }
            ');
```

## Feature Traits

### ActionsTrait
**File**: `src/Objects/Traits/ActionsTrait.php`  
**Purpose**: Manages action buttons, CRUD operations, and custom actions

```php
trait ActionsTrait
{
    public function setActions($actions)
    public function addAction($key, $config)
    public function removeAction($key)
    public function setActionColumn($config)
    public function setBulkActions($actions)
    public function setActionPermissions($permissions)
    public function setActionConditions($conditions)
    public function generateActionButtons($row)
}
```

**Usage:**
```php
$this->table->setActions([
    'edit' => [
        'label' => 'Edit',
        'url' => '/users/{id}/edit',
        'class' => 'btn btn-primary btn-sm',
        'icon' => 'fas fa-edit'
    ],
    'delete' => [
        'label' => 'Delete',
        'url' => '/users/{id}',
        'method' => 'DELETE',
        'class' => 'btn btn-danger btn-sm',
        'icon' => 'fas fa-trash',
        'confirm' => true
    ]
]);
```

### ChartRenderTrait
**File**: `src/Objects/Traits/ChartRenderTrait.php`  
**Purpose**: Integrates charts and data visualization

```php
trait ChartRenderTrait
{
    public function addChart($type, $config)
    public function setChartData($data)
    public function setChartOptions($options)
    public function renderChart($id)
    public function setChartPosition($position)
    public function enableChartExport($enabled = true)
}
```

**Usage:**
```php
$this->table->addChart('bar', [
    'title' => 'Users by Department',
    'data' => $departmentStats,
    'position' => 'top'
]);
```

### LifecycleStateTrait
**File**: `src/Objects/Traits/LifecycleStateTrait.php`  
**Purpose**: Manages table lifecycle states and events

```php
trait LifecycleStateTrait
{
    public function onBeforeRender($callback)
    public function onAfterRender($callback)
    public function onBeforeQuery($callback)
    public function onAfterQuery($callback)
    public function onDataTransform($callback)
    public function setState($state)
    public function getState()
    public function isState($state)
}
```

**Usage:**
```php
$this->table->onBeforeQuery(function($query) {
    $query->where('active', true);
})
->onDataTransform(function($data) {
    return $data->map(function($item) {
        $item->formatted_date = $item->created_at->format('M j, Y');
        return $item;
    });
});
```

### ModelQueryTrait
**File**: `src/Objects/Traits/ModelQueryTrait.php`  
**Purpose**: Handles model queries, relationships, and data retrieval

```php
trait ModelQueryTrait
{
    public function setModel($model)
    public function getModel()
    public function setQuery($query)
    public function getQuery()
    public function addWhere($column, $operator, $value = null)
    public function addWhereIn($column, $values)
    public function addWhereHas($relation, $callback)
    public function addOrderBy($column, $direction = 'asc')
    public function setLimit($limit)
    public function setOffset($offset)
}
```

**Usage:**
```php
$this->table->addWhere('active', true)
            ->addWhereIn('department_id', [1, 2, 3])
            ->addWhereHas('orders', function($query) {
                $query->where('status', 'completed');
            })
            ->addOrderBy('created_at', 'desc');
```

### ListBuilderTrait
**File**: `src/Objects/Traits/ListBuilderTrait.php`  
**Purpose**: Builds and renders table lists with various configurations

```php
trait ListBuilderTrait
{
    public function lists($table, $fields, $actions = false, $options = [])
    public function buildList($config)
    public function setListOptions($options)
    public function getListConfig()
    public function setDataSource($source)
    public function setListTemplate($template)
}
```

**Usage:**
```php
$this->table->lists('users', [
    'name:Full Name',
    'email:Email Address',
    'department.name:Department',
    'created_at:Registration Date'
], true, [
    'page_length' => 25,
    'searchable' => true,
    'sortable' => true
]);
```

### RelationsTrait
**File**: `src/Objects/Traits/RelationsTrait.php`  
**Purpose**: Manages Eloquent relationships and related data display

```php
trait RelationsTrait
{
    public function relations($model, $relation, $field)
    public function setRelations($relations)
    public function getRelations()
    public function addRelation($relation, $field)
    public function removeRelation($relation)
    public function setRelationConstraints($relation, $constraints)
    public function eagerLoadRelations($relations)
}
```

**Usage:**
```php
$this->table->relations($this->model, 'department', 'name')
            ->relations($this->model, 'role', 'title')
            ->relations($this->model, 'manager', 'name');
```

### FilterSearchTrait
**File**: `src/Objects/Traits/FilterSearchTrait.php`  
**Purpose**: Implements filtering and search functionality

```php
trait FilterSearchTrait
{
    public function searchable($config = true)
    public function sortable($config = true)
    public function clickable($config = true)
    public function filterGroups($field, $type, $enabled = true, $options = [])
    public function setGlobalSearch($enabled)
    public function setColumnSearch($column, $enabled)
    public function setSearchConfig($config)
    public function setFilterConfig($config)
    public function addCustomFilter($name, $config)
}
```

**Usage:**
```php
$this->table->searchable()
            ->sortable()
            ->clickable()
            ->filterGroups('status', 'selectbox', true)
            ->filterGroups('department_id', 'selectbox', true)
            ->filterGroups('created_at', 'daterange', true);
```

### FormattingTrait
**File**: `src/Objects/Traits/FormattingTrait.php`  
**Purpose**: Handles data formatting, display options, and transformations

```php
trait FormattingTrait
{
    public function setFieldAsImage($fields)
    public function setFieldAsDate($field, $format = 'Y-m-d')
    public function setFieldAsDateTime($field, $format = 'Y-m-d H:i:s')
    public function setFieldAsCurrency($field, $currency = 'USD')
    public function setFieldAsNumber($field, $decimals = 2)
    public function setFieldAsBoolean($field, $trueText = 'Yes', $falseText = 'No')
    public function setFieldAsLink($field, $urlPattern)
    public function setFieldAsEmail($field)
    public function setCustomFormatter($field, $callback)
}
```

**Usage:**
```php
$this->table->setFieldAsImage(['avatar', 'profile_picture'])
            ->setFieldAsDate('birth_date', 'd/m/Y')
            ->setFieldAsCurrency('salary', 'USD')
            ->setFieldAsBoolean('active', 'Active', 'Inactive')
            ->setCustomFormatter('full_name', function($value, $row) {
                return $row->first_name . ' ' . $row->last_name;
            });
```

### ColumnSetTrait
**File**: `src/Objects/Traits/ColumnSetTrait.php`  
**Purpose**: Advanced column management and configuration

```php
trait ColumnSetTrait
{
    public function setColumnSet($set)
    public function addColumnSet($name, $columns)
    public function useColumnSet($name)
    public function getColumnSet($name)
    public function mergeColumnSets($sets)
    public function setDefaultColumnSet($name)
    public function setColumnVisibility($column, $visible)
    public function setResponsiveColumns($config)
}
```

**Usage:**
```php
$this->table->addColumnSet('basic', ['name', 'email', 'created_at'])
            ->addColumnSet('detailed', ['name', 'email', 'phone', 'department', 'role', 'created_at'])
            ->addColumnSet('admin', ['id', 'name', 'email', 'phone', 'department', 'role', 'last_login', 'created_at'])
            ->useColumnSet(auth()->user()->hasRole('admin') ? 'admin' : 'basic');
```

## Utility Traits

### CachingTrait
**File**: `src/Objects/Traits/CachingTrait.php`  
**Purpose**: Implements caching strategies for improved performance

```php
trait CachingTrait
{
    public function enableCaching($ttl = 3600)
    public function disableCaching()
    public function setCacheKey($key)
    public function setCacheTags($tags)
    public function clearCache($key = null)
    public function setCacheDriver($driver)
    public function isCached($key)
    public function getCacheStats()
}
```

**Usage:**
```php
$this->table->enableCaching(1800) // 30 minutes
            ->setCacheTags(['users', 'departments'])
            ->setCacheKey('users_table_' . auth()->id());
```

### ExportTrait
**File**: `src/Objects/Traits/ExportTrait.php`  
**Purpose**: Handles data export functionality

```php
trait ExportTrait
{
    public function exportable($formats = ['excel', 'csv', 'pdf'])
    public function setExportConfig($config)
    public function addExportFormat($format, $config)
    public function setExportFilename($filename)
    public function setExportColumns($columns)
    public function setExportFilters($filters)
    public function exportData($format, $options = [])
}
```

**Usage:**
```php
$this->table->exportable(['excel', 'csv', 'pdf'])
            ->setExportFilename('users_export_' . date('Y-m-d'))
            ->setExportColumns(['name', 'email', 'department', 'created_at']);
```

### ValidationTrait
**File**: `src/Objects/Traits/ValidationTrait.php`  
**Purpose**: Provides validation for table configuration and data

```php
trait ValidationTrait
{
    public function validateConfig()
    public function validateColumns($columns)
    public function validateActions($actions)
    public function validateFilters($filters)
    public function addValidationRule($field, $rule)
    public function setValidationMessages($messages)
    public function getValidationErrors()
}
```

**Usage:**
```php
$this->table->addValidationRule('email', 'required|email')
            ->addValidationRule('name', 'required|min:2|max:100')
            ->setValidationMessages([
                'email.required' => 'Email is required',
                'name.min' => 'Name must be at least 2 characters'
            ]);
```

## Security Traits

### SecurityTrait
**File**: `src/Objects/Traits/SecurityTrait.php`  
**Purpose**: Implements security features and access control

```php
trait SecurityTrait
{
    public function setPermissions($permissions)
    public function checkPermission($permission)
    public function setRowLevelSecurity($callback)
    public function setColumnSecurity($column, $permission)
    public function enableXSSProtection($enabled = true)
    public function enableSQLInjectionProtection($enabled = true)
    public function setSecurityMode($mode)
    public function auditAccess($action, $data = [])
}
```

**Usage:**
```php
$this->table->setPermissions(['view', 'edit', 'delete'])
            ->setRowLevelSecurity(function($row) {
                return auth()->user()->can('view', $row);
            })
            ->setColumnSecurity('salary', 'view-salary')
            ->enableXSSProtection()
            ->enableSQLInjectionProtection();
```

### AuditTrait
**File**: `src/Objects/Traits/AuditTrait.php`  
**Purpose**: Provides audit logging and tracking functionality

```php
trait AuditTrait
{
    public function enableAuditLog($enabled = true)
    public function setAuditFields($fields)
    public function logTableAccess($action, $data = [])
    public function logDataExport($format, $count)
    public function logFilterUsage($filters)
    public function getAuditLog($limit = 100)
    public function setAuditRetention($days)
}
```

**Usage:**
```php
$this->table->enableAuditLog()
            ->setAuditFields(['user_id', 'ip_address', 'user_agent', 'timestamp'])
            ->setAuditRetention(90); // Keep logs for 90 days
```

## Performance Traits

### OptimizationTrait
**File**: `src/Objects/Traits/OptimizationTrait.php`  
**Purpose**: Implements performance optimizations

```php
trait OptimizationTrait
{
    public function enableQueryOptimization($enabled = true)
    public function setChunkSize($size)
    public function enableLazyLoading($enabled = true)
    public function setMemoryLimit($limit)
    public function enableCompression($enabled = true)
    public function setIndexHints($hints)
    public function optimizeQuery($query)
    public function getPerformanceMetrics()
}
```

**Usage:**
```php
$this->table->enableQueryOptimization()
            ->setChunkSize(1000)
            ->enableLazyLoading()
            ->setMemoryLimit('512M')
            ->enableCompression();
```

### MonitoringTrait
**File**: `src/Objects/Traits/MonitoringTrait.php`  
**Purpose**: Provides performance monitoring and metrics

```php
trait MonitoringTrait
{
    public function enableMonitoring($enabled = true)
    public function setMetrics($metrics)
    public function trackQueryTime($enabled = true)
    public function trackMemoryUsage($enabled = true)
    public function setPerformanceThresholds($thresholds)
    public function getMetrics()
    public function logSlowQueries($threshold = 1000)
}
```

**Usage:**
```php
$this->table->enableMonitoring()
            ->trackQueryTime()
            ->trackMemoryUsage()
            ->setPerformanceThresholds([
                'query_time' => 1000, // 1 second
                'memory_usage' => 50 * 1024 * 1024 // 50MB
            ])
            ->logSlowQueries(500); // Log queries > 500ms
```

## Trait Dependencies

### Dependency Graph

```
Objects (Main Class)
├── Tab
├── ColumnsConfigTrait
│   └── FormattingTrait
├── AlignAndStyleTrait
├── ActionsTrait
│   ├── SecurityTrait
│   └── ValidationTrait
├── ChartRenderTrait
├── LifecycleStateTrait
├── ModelQueryTrait
│   ├── RelationsTrait
│   └── OptimizationTrait
├── ListBuilderTrait
│   ├── ColumnSetTrait
│   └── CachingTrait
├── FilterSearchTrait
│   ├── SecurityTrait
│   └── ValidationTrait
├── FormattingTrait
├── ColumnSetTrait
├── ExportTrait
│   └── SecurityTrait
├── AuditTrait
└── MonitoringTrait
```

### Required Dependencies

Some traits require others to function properly:

```php
// ActionsTrait requires SecurityTrait for permission checks
trait ActionsTrait
{
    use SecurityTrait;
    
    // Action methods...
}

// FilterSearchTrait requires ValidationTrait for input validation
trait FilterSearchTrait
{
    use ValidationTrait;
    
    // Filter methods...
}

// ExportTrait requires SecurityTrait for access control
trait ExportTrait
{
    use SecurityTrait;
    
    // Export methods...
}
```

## Usage Examples

### Basic Usage

```php
<?php

namespace App\Http\Controllers;

use Canvastack\Canvastack\Controllers\Core\Controller;
use App\Models\User;

class UserController extends Controller
{
    public function __construct()
    {
        parent::__construct(User::class, 'users');
    }

    public function index()
    {
        $this->setPage();

        // Using multiple traits together
        $this->table->searchable()                    // FilterSearchTrait
                    ->sortable()                      // FilterSearchTrait
                    ->clickable()                     // FilterSearchTrait
                    ->setFieldAsImage(['avatar'])     // FormattingTrait
                    ->setFieldAsDate('created_at')    // FormattingTrait
                    ->exportable(['excel', 'csv'])    // ExportTrait
                    ->enableCaching(1800)             // CachingTrait
                    ->enableAuditLog()                // AuditTrait
                    ->enableMonitoring();             // MonitoringTrait

        // Configure relationships
        $this->table->relations($this->model, 'department', 'name')  // RelationsTrait
                    ->relations($this->model, 'role', 'title');      // RelationsTrait

        // Configure filters
        $this->table->filterGroups('status', 'selectbox', true)      // FilterSearchTrait
                    ->filterGroups('department_id', 'selectbox', true) // FilterSearchTrait
                    ->filterGroups('created_at', 'daterange', true);   // FilterSearchTrait

        // Configure actions
        $this->table->setActions([                    // ActionsTrait
            'edit' => [
                'label' => 'Edit',
                'url' => '/users/{id}/edit',
                'class' => 'btn btn-primary btn-sm'
            ],
            'delete' => [
                'label' => 'Delete',
                'url' => '/users/{id}',
                'method' => 'DELETE',
                'class' => 'btn btn-danger btn-sm',
                'confirm' => true
            ]
        ]);

        $this->table->lists('users', [               // ListBuilderTrait
            'avatar:Photo',
            'name:Full Name',
            'email:Email',
            'department.name:Department',
            'role.title:Role',
            'created_at:Registration Date'
        ], true);

        return $this->render();
    }
}
```

### Advanced Usage with Custom Configuration

```php
public function advancedIndex()
{
    $this->setPage();

    // Performance optimization
    $this->table->enableQueryOptimization()          // OptimizationTrait
                ->setChunkSize(1000)                  // OptimizationTrait
                ->enableLazyLoading()                 // OptimizationTrait
                ->enableCompression();                // OptimizationTrait

    // Security configuration
    $this->table->setSecurityMode('hardened')        // SecurityTrait
                ->enableXSSProtection()               // SecurityTrait
                ->enableSQLInjectionProtection()     // SecurityTrait
                ->setRowLevelSecurity(function($row) { // SecurityTrait
                    return auth()->user()->can('view', $row);
                });

    // Advanced formatting
    $this->table->setCustomFormatter('status', function($value, $row) { // FormattingTrait
                    $colors = [
                        'active' => 'success',
                        'inactive' => 'danger',
                        'pending' => 'warning'
                    ];
                    $color = $colors[$value] ?? 'secondary';
                    return "<span class='badge badge-{$color}'>" . ucfirst($value) . "</span>";
                })
                ->setFieldAsCurrency('salary', 'USD')  // FormattingTrait
                ->setFieldAsBoolean('verified');       // FormattingTrait

    // Lifecycle hooks
    $this->table->onBeforeQuery(function($query) {   // LifecycleStateTrait
                    $query->where('active', true);
                })
                ->onDataTransform(function($data) {   // LifecycleStateTrait
                    return $data->map(function($item) {
                        $item->full_name = $item->first_name . ' ' . $item->last_name;
                        return $item;
                    });
                });

    // Column sets for different user roles
    $this->table->addColumnSet('basic', [            // ColumnSetTrait
                    'name', 'email', 'created_at'
                ])
                ->addColumnSet('manager', [           // ColumnSetTrait
                    'name', 'email', 'department', 'role', 'created_at'
                ])
                ->addColumnSet('admin', [             // ColumnSetTrait
                    'id', 'name', 'email', 'phone', 'department', 'role', 'salary', 'last_login', 'created_at'
                ])
                ->useColumnSet(                       // ColumnSetTrait
                    auth()->user()->hasRole('admin') ? 'admin' : 
                    (auth()->user()->hasRole('manager') ? 'manager' : 'basic')
                );

    $this->table->lists('users', [], true);

    return $this->render();
}
```

## Creating Custom Traits

### Basic Custom Trait Structure

```php
<?php

namespace App\Traits\Table;

trait CustomFeatureTrait
{
    protected $customConfig = [];

    public function enableCustomFeature($config = [])
    {
        $this->customConfig = array_merge([
            'enabled' => true,
            'option1' => 'default_value',
            'option2' => []
        ], $config);

        return $this;
    }

    public function setCustomOption($key, $value)
    {
        $this->customConfig[$key] = $value;
        return $this;
    }

    public function getCustomConfig()
    {
        return $this->customConfig;
    }

    protected function processCustomFeature($data)
    {
        if (!$this->customConfig['enabled']) {
            return $data;
        }

        // Custom processing logic
        return $data->map(function($item) {
            // Transform data based on custom configuration
            return $item;
        });
    }
}
```

### Using Custom Traits

```php
<?php

namespace App\Http\Controllers;

use Canvastack\Canvastack\Controllers\Core\Controller;
use App\Traits\Table\CustomFeatureTrait;
use App\Models\User;

class CustomUserController extends Controller
{
    use CustomFeatureTrait;

    public function __construct()
    {
        parent::__construct(User::class, 'users');
    }

    public function index()
    {
        $this->setPage();

        // Use custom trait
        $this->enableCustomFeature([
            'option1' => 'custom_value',
            'option2' => ['setting1', 'setting2']
        ]);

        $this->table->lists('users', [
            'name:Full Name',
            'email:Email',
            'created_at:Registration Date'
        ], true);

        return $this->render();
    }
}
```

---

## Related Documentation

- [Custom Extensions](custom.md) - Creating custom traits and extensions
- [API Reference](../api/objects.md) - Complete method documentation
- [Basic Usage](../basic-usage.md) - Using traits in practice
- [Architecture Overview](../architecture.md) - Understanding the trait system