# Column Management

CanvaStack Table provides comprehensive column management capabilities including formatting, styling, alignment, visibility control, and advanced display options. This guide covers all aspects of working with table columns.

## Table of Contents

- [Basic Column Configuration](#basic-column-configuration)
- [Column Labels and Aliases](#column-labels-and-aliases)
- [Column Formatting](#column-formatting)
- [Column Alignment and Styling](#column-alignment-and-styling)
- [Column Visibility](#column-visibility)
- [Conditional Columns](#conditional-columns)
- [Custom Column Rendering](#custom-column-rendering)
- [Column Grouping](#column-grouping)
- [Responsive Columns](#responsive-columns)
- [Advanced Column Features](#advanced-column-features)

## Basic Column Configuration

### Simple Column List

Define basic columns with field names:

```php
public function index()
{
    $this->setPage();

    // Basic column configuration
    $this->table->lists('users', [
        'id',
        'name',
        'email',
        'created_at'
    ]);

    return $this->render();
}
```

### Column with Database Table Prefix

Specify table prefix for ambiguous column names:

```php
$this->table->lists('users', [
    'users.id',
    'users.name',
    'users.email',
    'profiles.bio'
]);
```

## Column Labels and Aliases

### Custom Column Labels

Provide user-friendly column headers:

```php
$this->table->lists('users', [
    'id:User ID',
    'name:Full Name',
    'email:Email Address',
    'created_at:Registration Date',
    'updated_at:Last Modified'
]);
```

### Relationship Column Labels

Display related model data with custom labels:

```php
// Set up relationships first
$this->table->relations($this->model, 'department', 'name')
            ->relations($this->model, 'role', 'title');

$this->table->lists('users', [
    'name:Employee Name',
    'email:Contact Email',
    'department.name:Department',
    'role.title:Job Title',
    'salary:Monthly Salary'
]);
```

### Multi-level Relationship Labels

Access nested relationships:

```php
$this->table->relations($this->model, 'profile', 'bio')
            ->relations($this->model, 'profile.address', 'street')
            ->relations($this->model, 'profile.address.city', 'name');

$this->table->lists('users', [
    'name:Full Name',
    'profile.bio:Biography',
    'profile.address.street:Street Address',
    'profile.address.city.name:City'
]);
```

## Column Formatting

### Date and Time Formatting

Format date columns with custom formats:

```php
public function index()
{
    $this->setPage();

    // Set date format for specific columns
    $this->table->setDateFormat([
        'created_at' => 'd/m/Y H:i',
        'updated_at' => 'M j, Y',
        'birth_date' => 'd-m-Y'
    ]);

    $this->table->lists('users', [
        'name',
        'email',
        'birth_date:Date of Birth',
        'created_at:Registered',
        'updated_at:Last Update'
    ]);

    return $this->render();
}
```

### Number Formatting

Format numeric columns:

```php
// Set number formatting
$this->table->setNumberFormat([
    'salary' => [
        'decimals' => 2,
        'decimal_separator' => '.',
        'thousands_separator' => ',',
        'prefix' => '$',
        'suffix' => ''
    ],
    'percentage' => [
        'decimals' => 1,
        'suffix' => '%'
    ],
    'quantity' => [
        'decimals' => 0,
        'thousands_separator' => ','
    ]
]);

$this->table->lists('employees', [
    'name',
    'salary:Monthly Salary',
    'commission:Commission %',
    'sales_count:Total Sales'
]);
```

### Currency Formatting

Format currency columns:

```php
$this->table->setCurrencyFormat([
    'price' => 'USD',
    'cost' => 'EUR',
    'budget' => ['currency' => 'IDR', 'position' => 'after']
]);

$this->table->lists('products', [
    'name',
    'price:Price (USD)',
    'cost:Cost (EUR)',
    'budget:Budget'
]);
```

### Boolean Formatting

Format boolean columns with custom labels:

```php
$this->table->setBooleanFormat([
    'active' => [
        'true' => '<span class="badge badge-success">Active</span>',
        'false' => '<span class="badge badge-danger">Inactive</span>'
    ],
    'verified' => [
        'true' => '✓ Verified',
        'false' => '✗ Not Verified'
    ]
]);

$this->table->lists('users', [
    'name',
    'email',
    'active:Status',
    'verified:Email Verified'
]);
```

## Column Alignment and Styling

### Column Alignment

Set text alignment for columns:

```php
$this->table->setColumnAlignment([
    'id' => 'center',
    'name' => 'left',
    'salary' => 'right',
    'status' => 'center'
]);

$this->table->lists('employees', [
    'id:ID',
    'name:Employee Name',
    'salary:Salary',
    'status:Status'
]);
```

### Column Width

Set specific column widths:

```php
$this->table->setColumnWidth([
    'id' => '80px',
    'name' => '200px',
    'email' => '250px',
    'actions' => '150px'
]);

$this->table->lists('users', ['id', 'name', 'email'], true);
```

### Column Styling

Apply custom CSS classes to columns:

```php
$this->table->setColumnClass([
    'id' => 'text-center font-weight-bold',
    'name' => 'text-primary',
    'salary' => 'text-right text-success',
    'status' => 'text-center'
]);

$this->table->lists('employees', [
    'id:ID',
    'name:Name',
    'salary:Salary',
    'status:Status'
]);
```

### Conditional Styling

Apply styles based on column values:

```php
$this->table->setConditionalStyling([
    'status' => [
        'active' => 'text-success font-weight-bold',
        'inactive' => 'text-danger',
        'pending' => 'text-warning'
    ],
    'salary' => function($value) {
        if ($value > 100000) return 'text-success font-weight-bold';
        if ($value < 30000) return 'text-danger';
        return 'text-dark';
    }
]);
```

## Column Visibility

### Hidden Columns

Hide specific columns from display:

```php
$this->table->setHiddenColumns(['password', 'remember_token', 'api_token']);

$this->table->lists('users', [
    'id',
    'name',
    'email',
    'password', // Hidden
    'remember_token', // Hidden
    'created_at'
]);
```

### Responsive Column Visibility

Hide columns on specific screen sizes:

```php
$this->table->setResponsiveColumns([
    'id' => ['hidden' => ['xs', 'sm']], // Hide on mobile
    'created_at' => ['hidden' => ['xs']], // Hide on extra small screens
    'updated_at' => ['hidden' => ['xs', 'sm', 'md']] // Hide on tablet and below
]);
```

### User-Controlled Column Visibility

Allow users to show/hide columns:

```php
$this->table->setColumnVisibilityControl([
    'enabled' => true,
    'button_text' => 'Columns',
    'button_class' => 'btn btn-secondary btn-sm',
    'save_preferences' => true,
    'default_hidden' => ['id', 'updated_at']
]);
```

## Conditional Columns

### Show Columns Based on Conditions

Display columns based on user permissions or data:

```php
$columns = ['name', 'email'];

// Add salary column only for managers and admins
if (auth()->user()->hasAnyRole(['manager', 'admin'])) {
    $columns[] = 'salary:Salary';
}

// Add admin-only columns
if (auth()->user()->hasRole('admin')) {
    $columns[] = 'created_at:Registered';
    $columns[] = 'last_login:Last Login';
}

$this->table->lists('users', $columns);
```

### Dynamic Column Building

Build columns dynamically based on data:

```php
public function index(Request $request)
{
    $this->setPage();

    $columns = ['name', 'email'];

    // Add department column if filtering by department
    if ($request->has('department')) {
        $columns[] = 'department.name:Department';
    }

    // Add date columns based on date range
    if ($request->has('date_range')) {
        $columns[] = 'created_at:Created';
        $columns[] = 'updated_at:Updated';
    }

    $this->table->lists('users', $columns);

    return $this->render();
}
```

## Custom Column Rendering

### Custom Column Content

Render custom content for specific columns:

```php
$this->table->setCustomColumns([
    'avatar' => function($row) {
        return '<img src="' . $row->avatar_url . '" class="rounded-circle" width="40" height="40">';
    },
    'full_name' => function($row) {
        return $row->first_name . ' ' . $row->last_name;
    },
    'status_badge' => function($row) {
        $class = $row->active ? 'success' : 'danger';
        $text = $row->active ? 'Active' : 'Inactive';
        return '<span class="badge badge-' . $class . '">' . $text . '</span>';
    }
]);

$this->table->lists('users', [
    'avatar:Photo',
    'full_name:Name',
    'email',
    'status_badge:Status'
]);
```

### Complex Custom Columns

Create complex custom columns with multiple data points:

```php
$this->table->setCustomColumns([
    'user_info' => function($row) {
        return '
            <div class="d-flex align-items-center">
                <img src="' . $row->avatar_url . '" class="rounded-circle me-2" width="32" height="32">
                <div>
                    <div class="fw-bold">' . $row->name . '</div>
                    <small class="text-muted">' . $row->email . '</small>
                </div>
            </div>
        ';
    },
    'contact_info' => function($row) {
        $html = '<div>';
        if ($row->phone) {
            $html .= '<div><i class="fas fa-phone"></i> ' . $row->phone . '</div>';
        }
        if ($row->address) {
            $html .= '<div><i class="fas fa-map-marker-alt"></i> ' . $row->address . '</div>';
        }
        $html .= '</div>';
        return $html;
    }
]);
```

### Computed Columns

Create columns with computed values:

```php
$this->table->setComputedColumns([
    'age' => function($row) {
        return $row->birth_date ? $row->birth_date->age : 'N/A';
    },
    'days_since_login' => function($row) {
        return $row->last_login_at ? $row->last_login_at->diffInDays(now()) : 'Never';
    },
    'total_orders' => function($row) {
        return $row->orders()->count();
    },
    'lifetime_value' => function($row) {
        return '$' . number_format($row->orders()->sum('total'), 2);
    }
]);

$this->table->lists('users', [
    'name',
    'age:Age',
    'days_since_login:Days Since Login',
    'total_orders:Orders',
    'lifetime_value:LTV'
]);
```

## Column Grouping

### Group Related Columns

Group related columns under common headers:

```php
$this->table->setColumnGroups([
    'personal_info' => [
        'title' => 'Personal Information',
        'columns' => ['name', 'email', 'phone']
    ],
    'work_info' => [
        'title' => 'Work Information',
        'columns' => ['department', 'position', 'salary']
    ],
    'dates' => [
        'title' => 'Important Dates',
        'columns' => ['hire_date', 'created_at', 'updated_at']
    ]
]);

$this->table->lists('employees', [
    'name:Full Name',
    'email:Email',
    'phone:Phone',
    'department:Department',
    'position:Position',
    'salary:Salary',
    'hire_date:Hire Date',
    'created_at:Created',
    'updated_at:Updated'
]);
```

### Collapsible Column Groups

Create collapsible column groups:

```php
$this->table->setColumnGroups([
    'basic' => [
        'title' => 'Basic Info',
        'columns' => ['name', 'email'],
        'collapsible' => false
    ],
    'details' => [
        'title' => 'Details',
        'columns' => ['phone', 'address', 'bio'],
        'collapsible' => true,
        'collapsed' => true
    ],
    'system' => [
        'title' => 'System Info',
        'columns' => ['created_at', 'updated_at', 'last_login'],
        'collapsible' => true,
        'collapsed' => true,
        'permission' => 'view-system-info'
    ]
]);
```

## Responsive Columns

### Responsive Breakpoints

Configure column visibility for different screen sizes:

```php
$this->table->setResponsiveBreakpoints([
    'xs' => 576,   // Extra small devices
    'sm' => 768,   // Small devices
    'md' => 992,   // Medium devices
    'lg' => 1200,  // Large devices
    'xl' => 1400   // Extra large devices
]);

$this->table->setResponsiveColumns([
    'id' => [
        'priority' => 1,
        'hidden' => ['xs', 'sm']
    ],
    'name' => [
        'priority' => 1,
        'min_width' => '150px'
    ],
    'email' => [
        'priority' => 2,
        'hidden' => ['xs']
    ],
    'phone' => [
        'priority' => 3,
        'hidden' => ['xs', 'sm']
    ],
    'created_at' => [
        'priority' => 4,
        'hidden' => ['xs', 'sm', 'md']
    ]
]);
```

### Mobile-First Column Design

Design columns with mobile-first approach:

```php
$this->table->setMobileColumns([
    'primary' => ['name', 'status'], // Always visible
    'secondary' => ['email', 'phone'], // Hidden on mobile
    'tertiary' => ['created_at', 'updated_at'] // Hidden on mobile and tablet
]);

$this->table->setMobileCardView([
    'enabled' => true,
    'breakpoint' => 'md',
    'template' => 'table.mobile_card'
]);
```

## Advanced Column Features

### Column Sorting Configuration

Configure sorting behavior for columns:

```php
$this->table->setColumnSorting([
    'name' => [
        'sortable' => true,
        'default_direction' => 'asc'
    ],
    'salary' => [
        'sortable' => true,
        'default_direction' => 'desc',
        'sort_field' => 'monthly_salary' // Different database field
    ],
    'status' => [
        'sortable' => true,
        'custom_sort' => function($query, $direction) {
            return $query->orderByRaw("FIELD(status, 'active', 'pending', 'inactive') " . $direction);
        }
    ]
]);
```

### Column Search Configuration

Configure search behavior for individual columns:

```php
$this->table->setColumnSearch([
    'name' => [
        'searchable' => true,
        'search_type' => 'like',
        'case_sensitive' => false
    ],
    'email' => [
        'searchable' => true,
        'search_type' => 'exact'
    ],
    'description' => [
        'searchable' => true,
        'search_type' => 'fulltext'
    ]
]);
```

### Column Export Configuration

Configure how columns appear in exports:

```php
$this->table->setColumnExport([
    'avatar' => [
        'exportable' => false // Don't include in exports
    ],
    'salary' => [
        'export_format' => function($value) {
            return number_format($value, 2);
        }
    ],
    'created_at' => [
        'export_label' => 'Registration Date',
        'export_format' => 'd/m/Y H:i'
    ]
]);
```

### Column Validation

Add validation for editable columns:

```php
$this->table->setColumnValidation([
    'email' => [
        'rules' => 'required|email|unique:users,email',
        'messages' => [
            'email.unique' => 'This email is already taken.'
        ]
    ],
    'salary' => [
        'rules' => 'required|numeric|min:0|max:1000000',
        'messages' => [
            'salary.max' => 'Salary cannot exceed $1,000,000.'
        ]
    ]
]);
```

---

## Related Documentation

- [Basic Usage](basic-usage.md) - Basic column setup
- [Data Sources](data-sources.md) - Working with different data sources
- [API Reference](api/objects.md) - Complete column method documentation
- [Examples](examples/basic.md) - Real-world column examples