# Fixed Columns

CanvaStack Table supports fixed columns functionality that keeps specified columns visible while scrolling horizontally through wide tables. This feature is essential for tables with many columns where users need to maintain context.

## Table of Contents

- [Basic Fixed Columns](#basic-fixed-columns)
- [Left Fixed Columns](#left-fixed-columns)
- [Right Fixed Columns](#right-fixed-columns)
- [Both Side Fixed Columns](#both-side-fixed-columns)
- [Responsive Fixed Columns](#responsive-fixed-columns)
- [Fixed Column Styling](#fixed-column-styling)
- [Performance Considerations](#performance-considerations)
- [Advanced Configuration](#advanced-configuration)

## Basic Fixed Columns

### Enable Fixed Columns

Enable basic fixed columns functionality:

```php
public function index()
{
    $this->setPage();

    // Fix the first 2 columns from the left
    $this->table->fixedColumns(2);

    $this->table->lists('users', [
        'name:Full Name',        // Fixed
        'email:Email',           // Fixed
        'phone:Phone',           // Scrollable
        'address:Address',       // Scrollable
        'department:Department', // Scrollable
        'position:Position',     // Scrollable
        'salary:Salary',         // Scrollable
        'created_at:Joined'      // Scrollable
    ], true);

    return $this->render();
}
```

### Fixed Columns with Actions

Fix columns and action buttons:

```php
public function index()
{
    $this->setPage();

    // Fix first column (name) and last column (actions)
    $this->table->fixedColumns(1, 1);

    $this->table->lists('users', [
        'name:Full Name',        // Fixed left
        'email:Email',           // Scrollable
        'phone:Phone',           // Scrollable
        'department:Department', // Scrollable
        'position:Position',     // Scrollable
        'salary:Salary',         // Scrollable
        'created_at:Joined'      // Scrollable
        // Actions column will be fixed right
    ], true);

    return $this->render();
}
```

## Left Fixed Columns

### Single Left Fixed Column

Fix one column on the left side:

```php
public function index()
{
    $this->setPage();

    // Fix only the name column
    $this->table->fixedColumns(1);

    $this->table->lists('employees', [
        'name:Employee Name',    // Fixed
        'employee_id:ID',        // Scrollable
        'email:Email',           // Scrollable
        'phone:Phone',           // Scrollable
        'department:Department', // Scrollable
        'position:Position',     // Scrollable
        'manager:Manager',       // Scrollable
        'hire_date:Hire Date',   // Scrollable
        'salary:Salary'          // Scrollable
    ], true);

    return $this->render();
}
```

### Multiple Left Fixed Columns

Fix multiple columns on the left side:

```php
public function index()
{
    $this->setPage();

    // Fix first 3 columns (name, ID, email)
    $this->table->fixedColumns(3);

    $this->table->lists('employees', [
        'name:Employee Name',    // Fixed
        'employee_id:ID',        // Fixed
        'email:Email',           // Fixed
        'phone:Phone',           // Scrollable
        'department:Department', // Scrollable
        'position:Position',     // Scrollable
        'manager:Manager',       // Scrollable
        'hire_date:Hire Date',   // Scrollable
        'salary:Salary'          // Scrollable
    ], true);

    return $this->render();
}
```

## Right Fixed Columns

### Single Right Fixed Column

Fix one column on the right side (usually actions):

```php
public function index()
{
    $this->setPage();

    // Fix only the actions column on the right
    $this->table->fixedColumns(0, 1);

    $this->table->lists('products', [
        'name:Product Name',     // Scrollable
        'sku:SKU',              // Scrollable
        'category:Category',     // Scrollable
        'price:Price',          // Scrollable
        'stock:Stock',          // Scrollable
        'supplier:Supplier',     // Scrollable
        'created_at:Added'       // Scrollable
        // Actions column will be fixed right
    ], true);

    return $this->render();
}
```

### Multiple Right Fixed Columns

Fix multiple columns on the right side:

```php
public function index()
{
    $this->setPage();

    // Fix last 2 columns plus actions
    $this->table->fixedColumns(0, 2);

    $this->table->lists('orders', [
        'order_number:Order #',  // Scrollable
        'customer:Customer',     // Scrollable
        'items:Items',          // Scrollable
        'subtotal:Subtotal',    // Scrollable
        'tax:Tax',              // Scrollable
        'total:Total',          // Fixed right
        'status:Status'         // Fixed right
        // Actions column will also be fixed right
    ], true);

    return $this->render();
}
```

## Both Side Fixed Columns

### Fixed Columns on Both Sides

Fix columns on both left and right sides:

```php
public function index()
{
    $this->setPage();

    // Fix 2 columns on left, 1 column on right, plus actions
    $this->table->fixedColumns(2, 1);

    $this->table->lists('transactions', [
        'id:Transaction ID',     // Fixed left
        'date:Date',            // Fixed left
        'customer:Customer',     // Scrollable
        'description:Description', // Scrollable
        'category:Category',     // Scrollable
        'reference:Reference',   // Scrollable
        'notes:Notes',          // Scrollable
        'amount:Amount'         // Fixed right
        // Actions column will also be fixed right
    ], true);

    return $this->render();
}
```

### Complex Fixed Column Layout

Create complex layouts with multiple fixed columns:

```php
public function index()
{
    $this->setPage();

    // Fix 3 columns on left, 2 columns on right
    $this->table->fixedColumns(3, 2);

    $this->table->lists('financial_records', [
        'account_id:Account ID',     // Fixed left
        'account_name:Account',      // Fixed left
        'date:Date',                // Fixed left
        'transaction_type:Type',     // Scrollable
        'description:Description',   // Scrollable
        'reference:Reference',       // Scrollable
        'category:Category',         // Scrollable
        'subcategory:Subcategory',   // Scrollable
        'debit:Debit',              // Fixed right
        'credit:Credit'             // Fixed right
        // Actions column will also be fixed right
    ], true);

    return $this->render();
}
```

## Responsive Fixed Columns

### Disable Fixed Columns on Mobile

Disable fixed columns on smaller screens:

```php
public function index()
{
    $this->setPage();

    // Configure responsive behavior
    $this->table->setFixedColumnsResponsive([
        'enabled' => true,
        'breakpoints' => [
            'mobile' => 768,   // Disable below 768px
            'tablet' => 992    // Reduce fixed columns below 992px
        ],
        'mobile_behavior' => 'disable', // disable, reduce, or maintain
        'tablet_behavior' => 'reduce'   // reduce fixed columns count
    ]);

    $this->table->fixedColumns(3, 1);

    $this->table->lists('users', [
        'name:Name',
        'email:Email',
        'phone:Phone',
        'department:Department',
        'position:Position',
        'salary:Salary'
    ], true);

    return $this->render();
}
```

### Adaptive Fixed Columns

Adapt fixed columns based on screen size:

```php
$this->table->setAdaptiveFixedColumns([
    'xl' => ['left' => 3, 'right' => 2], // Large screens
    'lg' => ['left' => 2, 'right' => 1], // Medium screens
    'md' => ['left' => 1, 'right' => 1], // Small screens
    'sm' => ['left' => 1, 'right' => 0], // Mobile landscape
    'xs' => ['left' => 0, 'right' => 0]  // Mobile portrait
]);
```

## Fixed Column Styling

### Custom Fixed Column Styling

Customize the appearance of fixed columns:

```php
public function index()
{
    $this->setPage();

    $this->table->setFixedColumnStyling([
        'left_shadow' => true,          // Add shadow to right edge
        'right_shadow' => true,         // Add shadow to left edge
        'background_color' => '#f8f9fa', // Background color
        'border_color' => '#dee2e6',    // Border color
        'z_index' => 1000,              // Z-index for layering
        'transition' => 'all 0.3s ease' // CSS transition
    ]);

    $this->table->fixedColumns(2, 1);

    $this->table->lists('users', [
        'name:Name',
        'email:Email',
        'phone:Phone',
        'department:Department',
        'salary:Salary'
    ], true);

    return $this->render();
}
```

### Column-Specific Styling

Apply different styles to different fixed columns:

```php
$this->table->setFixedColumnStyles([
    'left' => [
        0 => ['background' => '#e3f2fd', 'font_weight' => 'bold'], // First column
        1 => ['background' => '#f3e5f5']  // Second column
    ],
    'right' => [
        0 => ['background' => '#e8f5e8', 'text_align' => 'right'] // Last fixed column
    ]
]);
```

### Hover Effects

Add hover effects to fixed columns:

```php
$this->table->setFixedColumnHover([
    'enabled' => true,
    'highlight_row' => true,
    'highlight_color' => '#fff3cd',
    'transition_duration' => '0.2s'
]);
```

## Performance Considerations

### Optimize Fixed Columns Performance

Configure performance settings for fixed columns:

```php
$this->table->setFixedColumnsPerformance([
    'virtual_scrolling' => true,        // Enable virtual scrolling
    'row_height' => 40,                 // Fixed row height for better performance
    'buffer_size' => 10,                // Number of rows to buffer
    'debounce_scroll' => 16,            // Debounce scroll events (ms)
    'use_transform3d' => true,          // Use 3D transforms for better performance
    'will_change' => 'transform'        // CSS will-change property
]);
```

### Memory Management

Manage memory usage with large datasets:

```php
$this->table->setFixedColumnsMemory([
    'max_rendered_rows' => 100,         // Maximum rows to keep in DOM
    'cleanup_threshold' => 200,         // Clean up after this many rows
    'gc_interval' => 1000,              // Garbage collection interval (ms)
    'preload_rows' => 5                 // Rows to preload outside viewport
]);
```

## Advanced Configuration

### Custom Fixed Column Implementation

Implement custom fixed column behavior:

```php
$this->table->setCustomFixedColumns([
    'implementation' => 'custom',
    'left_columns' => [
        [
            'field' => 'name',
            'width' => '200px',
            'resizable' => true,
            'sortable' => true
        ],
        [
            'field' => 'email',
            'width' => '250px',
            'resizable' => true,
            'sortable' => true
        ]
    ],
    'right_columns' => [
        [
            'field' => 'actions',
            'width' => '120px',
            'resizable' => false,
            'sortable' => false
        ]
    ]
]);
```

### Fixed Columns with Grouping

Combine fixed columns with column grouping:

```php
$this->table->setFixedColumnGroups([
    'left_group' => [
        'title' => 'Employee Info',
        'columns' => ['name', 'employee_id'],
        'fixed' => true,
        'collapsible' => false
    ],
    'middle_group' => [
        'title' => 'Contact Details',
        'columns' => ['email', 'phone', 'address'],
        'fixed' => false,
        'collapsible' => true
    ],
    'right_group' => [
        'title' => 'Financial',
        'columns' => ['salary', 'bonus'],
        'fixed' => true,
        'collapsible' => false
    ]
]);
```

### Dynamic Fixed Columns

Change fixed columns dynamically based on user preferences:

```php
$this->table->setDynamicFixedColumns([
    'user_preferences' => true,         // Save user preferences
    'preference_key' => 'table_fixed_columns',
    'allow_user_control' => true,       // Let users change fixed columns
    'control_position' => 'top-right',  // Position of control buttons
    'max_left_fixed' => 5,              // Maximum left fixed columns
    'max_right_fixed' => 3              // Maximum right fixed columns
]);
```

### Fixed Columns with Filtering

Ensure fixed columns work properly with filtering:

```php
public function index()
{
    $this->setPage();

    // Enable filtering
    $this->table->filterGroups('department', 'selectbox', true)
                ->filterGroups('status', 'selectbox', true);

    // Fixed columns work with filtered data
    $this->table->fixedColumns(2, 1);

    // Ensure filter controls don't interfere with fixed columns
    $this->table->setFilterModalPosition('center'); // Avoid overlap

    $this->table->lists('employees', [
        'name:Name',            // Fixed left
        'employee_id:ID',       // Fixed left
        'email:Email',          // Scrollable
        'department:Department', // Scrollable
        'position:Position',    // Scrollable
        'status:Status'         // Fixed right
    ], true);

    return $this->render();
}
```

---

## Related Documentation

- [Basic Usage](../basic-usage.md) - Basic table setup
- [Column Management](../columns.md) - Column configuration
- [Performance Optimization](../advanced/performance.md) - Performance considerations
- [API Reference](../api/objects.md) - Complete fixed columns method documentation