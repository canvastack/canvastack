# Basic Usage

This guide covers the fundamental patterns and techniques for using CanvaStack Table in your Laravel applications.

## Table of Contents

- [Controller Setup](#controller-setup)
- [Basic Table Configuration](#basic-table-configuration)
- [Column Configuration](#column-configuration)
- [Action Buttons](#action-buttons)
- [Filtering and Search](#filtering-and-search)
- [Relationships](#relationships)
- [Common Patterns](#common-patterns)
- [Best Practices](#best-practices)

## Controller Setup

### Extending CanvaStack Controller

The recommended approach is to extend the CanvaStack base controller:

```php
<?php

namespace App\Http\Controllers;

use Canvastack\Canvastack\Controllers\Core\Controller;
use App\Models\User;

class UserController extends Controller
{
    public function __construct()
    {
        // Initialize with model and route prefix
        parent::__construct(User::class, 'users');
        
        // Set validation rules for forms
        $this->setValidations([
            'name' => 'required|min:2',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);
    }

    public function index()
    {
        $this->setPage(); // Initialize page metadata

        // Configure and render table
        $this->table->lists('users', ['name', 'email', 'created_at']);

        return $this->render();
    }
}
```

### Using Trait in Existing Controller

If you prefer to use your existing controller structure:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Controller;
use Canvastack\Canvastack\Traits\TableTrait;
use App\Models\User;

class UserController extends Controller
{
    use TableTrait;

    public function index()
    {
        $this->initializeTable(User::class);

        $this->table->searchable()
                    ->sortable()
                    ->lists('users', ['name', 'email', 'created_at']);

        return view('users.index', [
            'table' => $this->table->render()
        ]);
    }
}
```

## Basic Table Configuration

### Simple Table

The most basic table requires only a table name and fields:

```php
public function index()
{
    $this->setPage();
    
    $this->table->lists('users', ['name', 'email', 'created_at']);
    
    return $this->render();
}
```

### Enhanced Table

Add common features like search, sorting, and clickable rows:

```php
public function index()
{
    $this->setPage();
    
    $this->table->searchable()      // Enable global search
                ->sortable()        // Enable column sorting
                ->clickable();      // Make rows clickable
    
    $this->table->lists('users', ['name', 'email', 'created_at']);
    
    return $this->render();
}
```

### Server-Side Processing

For better performance with large datasets:

```php
public function index()
{
    $this->setPage();
    
    $this->table->method('POST')    // Use POST for server-side
                ->searchable()
                ->sortable()
                ->clickable();
    
    $this->table->lists('users', ['name', 'email', 'created_at']);
    
    return $this->render();
}
```

## Column Configuration

### Custom Column Labels

Use colon notation to specify custom labels:

```php
$this->table->lists('users', [
    'name:Full Name',
    'email:Email Address',
    'created_at:Join Date',
    'active:Status'
]);
```

### Image Columns

Display image fields as thumbnails:

```php
$this->table->setFieldAsImage(['avatar', 'profile_picture']);

$this->table->lists('users', [
    'avatar:Profile Picture',
    'name:Full Name',
    'email'
]);
```

### Hidden Columns

Hide sensitive or unnecessary columns:

```php
$this->table->setHiddenColumns(['password', 'remember_token', 'api_token']);

$this->table->lists('users', [
    'name',
    'email',
    'password',        // Will be hidden
    'remember_token'   // Will be hidden
]);
```

### Fixed Columns

Keep important columns visible during horizontal scrolling:

```php
// Fix first column (name) and last column (actions)
$this->table->fixedColumns(1, 1);

$this->table->lists('users', [
    'name',           // Fixed left
    'email',
    'phone',
    'address',
    'department',
    'created_at'      // Actions column will be fixed right
], true);
```

### Column Merging

Merge related columns under a single header:

```php
$this->table->mergeColumns('Full Name', ['first_name', 'last_name'])
            ->mergeColumns('Address', ['street', 'city', 'state', 'zip']);

$this->table->lists('users', [
    'first_name',     // Part of "Full Name" group
    'last_name',      // Part of "Full Name" group
    'email',
    'street',         // Part of "Address" group
    'city',           // Part of "Address" group
    'state',          // Part of "Address" group
    'zip'             // Part of "Address" group
]);
```

## Action Buttons

### Default Actions

Enable default CRUD actions (view, edit, delete):

```php
$this->table->lists('users', ['name', 'email'], true); // true = enable actions
```

### Custom Actions

Remove or customize specific actions:

```php
// Remove delete button for safety
$this->table->removeButtons(['delete']);

$this->table->lists('users', ['name', 'email'], true);
```

### Advanced Custom Actions

Add completely custom action buttons:

```php
$this->table->setActions([
    'activate' => [
        'label' => 'Activate',
        'url' => '/users/{id}/activate',
        'class' => 'btn btn-success btn-sm',
        'icon' => 'fas fa-check',
        'confirm' => 'Activate this user?'
    ],
    'suspend' => [
        'label' => 'Suspend',
        'url' => '/users/{id}/suspend',
        'class' => 'btn btn-warning btn-sm',
        'icon' => 'fas fa-pause',
        'method' => 'POST'
    ]
]);

$this->table->lists('users', ['name', 'email'], [
    'view' => true,
    'edit' => true,
    'delete' => false,
    'activate' => true,
    'suspend' => true
]);
```

## Filtering and Search

### Basic Filters

Add filter controls for specific columns:

```php
$this->table->filterGroups('status', 'selectbox', true)
            ->filterGroups('department', 'selectbox', true)
            ->filterGroups('created_at', 'date', true);

$this->table->lists('users', ['name', 'email', 'status', 'department']);
```

### Filter Types

Different filter types for different data:

```php
// Dropdown for categories
$this->table->filterGroups('category', 'selectbox', true);

// Text input for names
$this->table->filterGroups('name', 'text', true);

// Date picker for dates
$this->table->filterGroups('created_at', 'date', true);

// Date range for periods
$this->table->filterGroups('date_range', 'daterange', true);

// Checkboxes for multiple selection
$this->table->filterGroups('tags', 'checkbox', true);

// Radio buttons for single selection
$this->table->filterGroups('priority', 'radiobox', true);
```

### Dependent Filters

Create filter dependencies (cascading filters):

```php
$this->table->filterGroups('country', 'selectbox', true)
            ->filterGroups('state', 'selectbox', true)    // Depends on country
            ->filterGroups('city', 'selectbox', true);    // Depends on state

$this->table->lists('users', ['name', 'country', 'state', 'city']);
```

## Relationships

### Basic Relationships

Display related data instead of foreign keys:

```php
// In your User model
public function group()
{
    return $this->belongsTo(Group::class);
}

// In your controller
$this->table->relations($this->model, 'group', 'name');

$this->table->lists('users', [
    'name',
    'email',
    'group.name:Group'  // Display group name instead of group_id
]);
```

### Multiple Relationships

Handle multiple relationships:

```php
// In your User model
public function group()
{
    return $this->belongsTo(Group::class);
}

public function department()
{
    return $this->belongsTo(Department::class);
}

public function role()
{
    return $this->belongsTo(Role::class);
}

// In your controller
$this->table->relations($this->model, 'group', 'name')
            ->relations($this->model, 'department', 'department_name')
            ->relations($this->model, 'role', 'title');

$this->table->lists('users', [
    'name',
    'email',
    'group.name:Group',
    'department.department_name:Department',
    'role.title:Role'
]);
```

### Filtering by Related Data

Filter by relationship fields:

```php
$this->table->relations($this->model, 'group', 'name')
            ->filterGroups('group.name', 'selectbox', true);

$this->table->lists('users', [
    'name',
    'email',
    'group.name:Group'
]);
```

## Common Patterns

### Pattern 1: Simple List

Basic read-only table for displaying data:

```php
public function index()
{
    $this->setPage();
    
    $this->table->searchable()
                ->sortable();
    
    $this->table->lists('products', [
        'name:Product Name',
        'price:Price',
        'category:Category',
        'stock:In Stock'
    ]);
    
    return $this->render();
}
```

### Pattern 2: Management Table

Full CRUD operations with actions:

```php
public function index()
{
    $this->setPage();
    
    $this->table->method('POST')
                ->searchable()
                ->sortable()
                ->clickable();
    
    $this->table->filterGroups('status', 'selectbox', true)
                ->filterGroups('category', 'selectbox', true);
    
    $this->table->lists('products', [
        'name:Product Name',
        'price:Price',
        'category:Category',
        'status:Status',
        'created_at:Created'
    ], true); // Enable actions
    
    return $this->render();
}
```

### Pattern 3: Report Table

Read-only table with advanced filtering for reports:

```php
public function reports()
{
    $this->setPage();
    
    $this->table->searchable()
                ->sortable();
    
    $this->table->filterGroups('date_range', 'daterange', true)
                ->filterGroups('department', 'selectbox', true)
                ->filterGroups('status', 'selectbox', true);
    
    $this->table->orderby('created_at', 'DESC');
    
    $this->table->lists('sales_reports', [
        'date:Date',
        'customer:Customer',
        'product:Product',
        'quantity:Qty',
        'total:Total Amount',
        'status:Status'
    ], false); // No actions for reports
    
    return $this->render();
}
```

### Pattern 4: User Management

Complete user management with relationships and security:

```php
public function index()
{
    $this->setPage();
    
    // Security check
    if (!$this->is_root && !canvastack_string_contained($this->session['user_group'], 'admin')) {
        return redirect("{$this->session['id']}/edit");
    }
    
    $this->table->method('POST')
                ->searchable()
                ->clickable()
                ->sortable();
    
    // Prevent duplicate users with multiple groups
    $this->table->conditions['groupBy'] = ['users.id'];
    
    // Set up relationships
    $this->table->relations($this->model, 'group', 'group_info');
    
    // Add filters
    $this->table->filterGroups('username', 'selectbox', true)
                ->filterGroups('group_info', 'selectbox', true);
    
    // Default ordering
    $this->table->orderby('id', 'DESC');
    
    // Image field
    $this->table->setFieldAsImage(['photo']);
    
    $this->table->lists($this->model_table, [
        'username:User',
        'email',
        'photo',
        'group_info',
        'address',
        'phone',
        'expire_date',
        'active'
    ]);
    
    return $this->render();
}
```

## Best Practices

### 1. Performance Optimization

```php
// Use server-side processing for large datasets
if ($recordCount > 1000) {
    $this->table->method('POST');
}

// Limit columns to what's necessary
$this->table->lists('users', ['name', 'email']); // Not all columns

// Use proper indexing on filtered/sorted columns
$this->table->orderby('indexed_column', 'DESC');
```

### 2. Security Considerations

```php
// Always validate user permissions
if (!auth()->user()->can('view-users')) {
    abort(403);
}

// Hide sensitive data
$this->table->setHiddenColumns(['password', 'api_token', 'remember_token']);

// Use proper validation
$this->setValidations([
    'email' => 'required|email|unique:users',
    'password' => 'required|min:8',
]);
```

### 3. User Experience

```php
// Make tables responsive
$this->table->responsive(true);

// Use meaningful column labels
$this->table->lists('users', [
    'name:Full Name',           // Not just 'name'
    'email:Email Address',      // Clear labels
    'created_at:Join Date'      // User-friendly
]);

// Provide helpful filters
$this->table->filterGroups('status', 'selectbox', true)
            ->filterGroups('created_at', 'daterange', true);
```

### 4. Code Organization

```php
// Group related configurations
private function configureUserTable()
{
    $this->table->method('POST')
                ->searchable()
                ->sortable()
                ->clickable();
    
    $this->setupRelationships();
    $this->setupFilters();
    $this->setupColumns();
}

private function setupRelationships()
{
    $this->table->relations($this->model, 'group', 'group_info')
                ->relations($this->model, 'department', 'dept_name');
}

private function setupFilters()
{
    $this->table->filterGroups('group_info', 'selectbox', true)
                ->filterGroups('dept_name', 'selectbox', true);
}

private function setupColumns()
{
    $this->table->setFieldAsImage(['photo'])
                ->setHiddenColumns(['password'])
                ->fixedColumns(1, 1);
}

public function index()
{
    $this->setPage();
    $this->configureUserTable();
    
    $this->table->lists('users', [
        'photo:Profile',
        'name:Full Name',
        'email',
        'group_info:Group',
        'dept_name:Department'
    ], true);
    
    return $this->render();
}
```

### 5. Error Handling

```php
public function index()
{
    try {
        $this->setPage();
        
        $this->table->searchable()
                    ->sortable()
                    ->lists('users', ['name', 'email']);
        
        return $this->render();
        
    } catch (\Exception $e) {
        Log::error('Table rendering error: ' . $e->getMessage());
        
        return redirect()->back()
                        ->withErrors(['error' => 'Unable to load table data']);
    }
}
```

### 6. Testing

```php
// In your test file
public function test_user_table_displays_correctly()
{
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
                     ->get('/users');
    
    $response->assertStatus(200)
             ->assertSee($user->name)
             ->assertSee($user->email);
}

public function test_user_table_filters_work()
{
    $activeUser = User::factory()->create(['active' => true]);
    $inactiveUser = User::factory()->create(['active' => false]);
    
    $response = $this->actingAs($activeUser)
                     ->post('/users', [
                         'filter' => ['active' => true]
                     ]);
    
    $response->assertJsonFragment(['name' => $activeUser->name])
             ->assertJsonMissing(['name' => $inactiveUser->name]);
}
```

## Troubleshooting Common Issues

### Issue 1: Table Not Loading

```php
// Check if model is properly set
if (!$this->model) {
    throw new \Exception('Model not set for table');
}

// Verify database connection
if (!Schema::hasTable('users')) {
    throw new \Exception('Table does not exist');
}
```

### Issue 2: Filters Not Working

```php
// Ensure relationships are properly defined
$this->table->relations($this->model, 'group', 'name');

// Check filter field names match database columns
$this->table->filterGroups('group.name', 'selectbox', true); // Use relationship notation
```

### Issue 3: Actions Not Appearing

```php
// Make sure actions parameter is set
$this->table->lists('users', ['name', 'email'], true); // true for actions

// Check if buttons are removed
$this->table->removeButtons([]); // Don't remove all buttons
```

## Next Steps

Now that you understand basic usage:

1. [API Reference](api/objects.md) - Complete method documentation
2. [Features](features/actions.md) - Explore advanced features
3. [Examples](examples/basic.md) - See more practical examples
4. [Advanced Topics](advanced/security.md) - Security and performance

---

## Related Documentation

- [Quick Start Guide](quick-start.md) - Get started quickly
- [Configuration](configuration.md) - Detailed configuration options
- [API Reference](api/objects.md) - Complete method documentation
- [Security Features](advanced/security.md) - Security implementation