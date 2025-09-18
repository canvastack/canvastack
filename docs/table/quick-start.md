# Quick Start Guide

Get up and running with CanvaStack Table in minutes! This guide assumes you have already completed the [Installation & Setup](installation.md).

## 🚀 Your First Table

Let's create a simple user management table in just a few steps.

### Step 1: Create a Controller

```php
<?php

namespace App\Http\Controllers;

use Canvastack\Canvastack\Core\Controller;
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

        $this->table->lists('users', ['name', 'email', 'created_at']);

        return $this->render();
    }
}
```

### Step 2: Add Routes

```php
// routes/web.php
Route::resource('users', UserController::class);
```

### Step 3: Create View

```blade
{{-- resources/views/users/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Users</h1>
    {!! $table !!}
</div>
@endsection
```

**That's it!** Visit `/users` and you'll see a fully functional DataTable.

## 🎯 Adding Features

Now let's enhance your table with common features:

### Make it Searchable and Sortable

```php
public function index()
{
    $this->setPage();

    $this->table->searchable()    // Enable global search
                ->sortable()      // Enable column sorting
                ->clickable();    // Make rows clickable

    $this->table->lists('users', ['name', 'email', 'created_at']);

    return $this->render();
}
```

### Add Server-Side Processing

For better performance with large datasets:

```php
public function index()
{
    $this->setPage();

    $this->table->method('POST')     // Use POST for server-side
                ->searchable()
                ->sortable()
                ->clickable();

    $this->table->lists('users', ['name', 'email', 'created_at']);

    return $this->render();
}
```

### Add Action Buttons

```php
public function index()
{
    $this->setPage();

    $this->table->searchable()
                ->sortable()
                ->clickable();

    // Add default actions (view, edit, delete)
    $this->table->lists('users', ['name', 'email', 'created_at'], true);

    return $this->render();
}
```

### Custom Column Labels

```php
$this->table->lists('users', [
    'name:Full Name',           // Custom label
    'email:Email Address',      // Custom label
    'created_at:Joined Date'    // Custom label
]);
```

## 🔍 Adding Filters

### Basic Filtering

```php
public function index()
{
    $this->setPage();

    $this->table->searchable()
                ->sortable()
                ->clickable();

    // Add filter for name field as selectbox
    $this->table->filterGroups('name', 'selectbox', true);

    $this->table->lists('users', ['name', 'email', 'created_at']);

    return $this->render();
}
```

### Multiple Filters

```php
$this->table->filterGroups('name', 'selectbox', true)
            ->filterGroups('email', 'text', true)
            ->filterGroups('created_at', 'date', true);
```

## 🔗 Working with Relationships

### Basic Relationship

```php
public function index()
{
    $this->setPage();

    $this->table->searchable()
                ->sortable()
                ->clickable();

    // Display related data
    $this->table->relations($this->model, 'group', 'group_name');

    $this->table->lists('users', [
        'name:Full Name',
        'email',
        'group_name:Group',  // Display group name instead of group_id
        'created_at'
    ]);

    return $this->render();
}
```

## 🎨 Styling and Layout

### Set Column as Image

```php
$this->table->setFieldAsImage(['avatar', 'photo']);

$this->table->lists('users', [
    'name',
    'email', 
    'avatar:Profile Picture',
    'created_at'
]);
```

### Hide Columns

```php
$this->table->setHiddenColumns(['password', 'remember_token']);
```

### Fixed Columns

```php
$this->table->fixedColumns(1, 1); // Fix first and last column

$this->table->lists('users', ['name', 'email', 'phone', 'address', 'actions']);
```

## 📊 Complete Example

Here's a comprehensive example combining all features:

```php
<?php

namespace App\Http\Controllers;

use Canvastack\Canvastack\Core\Controller;
use App\Models\User;

class UserController extends Controller
{
    public function __construct()
    {
        parent::__construct(User::class, 'users');
        
        // Set validation rules
        $this->setValidations([
            'name' => 'required|min:2',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);
    }

    public function index()
    {
        $this->setPage();

        // Configure table behavior
        $this->table->method('POST')                    // Server-side processing
                    ->searchable()                      // Global search
                    ->clickable()                       // Clickable rows
                    ->sortable();                       // Sortable columns

        // Set up relationships
        $this->table->relations($this->model, 'group', 'group_name');

        // Configure filters
        $this->table->filterGroups('name', 'selectbox', true)
                    ->filterGroups('group_name', 'selectbox', true)
                    ->filterGroups('created_at', 'date', true);

        // Set ordering
        $this->table->orderby('created_at', 'DESC');

        // Configure image fields
        $this->table->setFieldAsImage(['avatar']);

        // Hide sensitive fields
        $this->table->setHiddenColumns(['password', 'remember_token']);

        // Generate table with actions
        $this->table->lists('users', [
            'avatar:Profile',
            'name:Full Name',
            'email:Email Address',
            'group_name:Group',
            'created_at:Joined Date',
            'active:Status'
        ], true); // true = include default actions

        return $this->render();
    }

    public function create()
    {
        $this->setPage();

        $this->form->text('name', null, ['required']);
        $this->form->email('email', null, ['required']);
        $this->form->password('password', ['required']);
        $this->form->selectbox('group_id', $this->getGroups(), false, ['required']);
        $this->form->file('avatar', ['imagepreview']);
        $this->form->selectbox('active', ['1' => 'Active', '0' => 'Inactive'], '1');

        $this->form->close('Create User');

        return $this->render();
    }

    public function edit($id)
    {
        $this->setPage();

        $this->form->text('name', $this->model_data->name, ['required']);
        $this->form->email('email', $this->model_data->email, ['required']);
        $this->form->password('password', ['placeholder' => 'Leave blank to keep current']);
        $this->form->selectbox('group_id', $this->getGroups(), $this->model_data->group_id);
        $this->form->file('avatar', ['imagepreview']);
        $this->form->selectbox('active', ['1' => 'Active', '0' => 'Inactive'], $this->model_data->active);

        $this->form->close('Update User');

        return $this->render();
    }

    private function getGroups()
    {
        return \App\Models\Group::pluck('name', 'id')->toArray();
    }
}
```

## 🎯 Common Patterns

### Pattern 1: Simple List Table

```php
// Just display data, no actions
$this->table->lists('products', ['name', 'price', 'category']);
```

### Pattern 2: Management Table

```php
// Full CRUD operations
$this->table->searchable()
            ->sortable()
            ->clickable()
            ->lists('products', ['name', 'price', 'category'], true);
```

### Pattern 3: Report Table

```php
// Read-only with filters and export
$this->table->searchable()
            ->filterGroups('category', 'selectbox', true)
            ->filterGroups('date_range', 'daterange', true)
            ->lists('sales_report', ['date', 'product', 'quantity', 'total'], false);
```

### Pattern 4: Relationship Table

```php
// Display related data
$this->table->relations($this->model, 'category', 'category_name')
            ->relations($this->model, 'supplier', 'supplier_name')
            ->lists('products', [
                'name',
                'category_name:Category',
                'supplier_name:Supplier',
                'price'
            ]);
```

## 🔧 Configuration Options

### Table Method

```php
$this->table->method('GET');    // Client-side processing (default)
$this->table->method('POST');   // Server-side processing
```

### Pagination

```php
$this->table->paginate(25);     // 25 items per page
$this->table->paginate(false);  // Disable pagination
```

### Ordering

```php
$this->table->orderby('created_at', 'DESC');    // Default sort
$this->table->orderby('name', 'ASC');           // Sort by name
```

### Custom Attributes

```php
$this->table->lists('users', ['name', 'email'], true, true, true, [
    'class' => 'table-striped table-hover',
    'id' => 'custom-users-table'
]);
```

## 🚨 Security Notes

CanvaStack Table includes built-in security features:

- **SQL Injection Protection**: All queries use parameter binding
- **XSS Prevention**: Output is automatically escaped
- **CSRF Protection**: All AJAX requests include CSRF tokens
- **Input Validation**: All inputs are validated and sanitized

## 📱 Responsive Design

Tables are automatically responsive:

```php
$this->table->responsive(true);  // Enable responsive mode (default)
$this->table->responsive(false); // Disable responsive mode
```

## 🎨 Styling

### Custom CSS Classes

```php
$this->table->setTableClass('table table-striped table-hover');
$this->table->setContainerClass('table-responsive');
```

### Column Alignment

```php
$this->table->setColumnAlignment([
    'price' => 'right',
    'quantity' => 'center',
    'actions' => 'center'
]);
```

## 🔄 AJAX Handling

CanvaStack automatically handles AJAX requests, but you can customize:

```php
$this->table->setAjaxUrl('/custom/ajax/endpoint');
$this->table->setAjaxMethod('POST');
```

## 📈 Performance Tips

1. **Use Server-Side Processing** for large datasets:
   ```php
   $this->table->method('POST');
   ```

2. **Limit Columns** - only display what's necessary:
   ```php
   $this->table->lists('users', ['name', 'email']); // Not all columns
   ```

3. **Use Indexes** on filtered/sorted columns in your database

4. **Enable Caching** for relationship data:
   ```php
   $this->table->cacheRelations(true);
   ```

## 🐛 Debugging

Enable debug mode to see generated queries:

```php
$this->table->debug(true);
```

Check browser console for JavaScript errors and network requests.

## 📚 Next Steps

Now that you have the basics:

1. [Basic Usage](basic-usage.md) - Learn more patterns and techniques
2. [API Reference](api/objects.md) - Complete method documentation  
3. [Features](features/actions.md) - Explore advanced features
4. [Examples](examples/basic.md) - See real-world implementations

---

## 🤔 Need Help?

- [Configuration](configuration.md) - Detailed configuration options
- [Troubleshooting](advanced/troubleshooting.md) - Common issues and solutions
- [API Reference](api/objects.md) - Complete method documentation