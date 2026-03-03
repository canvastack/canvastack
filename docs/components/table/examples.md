# Code Examples

This document provides comprehensive code examples for using the CanvaStack Table Component.

## Table of Contents

1. [Simple Table Rendering](#simple-table-rendering)
2. [Advanced Table Configuration](#advanced-table-configuration)
3. [Relational Data](#relational-data)
4. [Conditional Formatting](#conditional-formatting)
5. [Formula Columns](#formula-columns)
6. [Custom Actions](#custom-actions)
7. [Legacy vs Enhanced API](#legacy-vs-enhanced-api)
8. [Admin vs Public Context](#admin-vs-public-context)

---

## Simple Table Rendering

### Example 1: Basic Table with Default Settings

```php
use Canvastack\Components\Table\TableBuilder;
use App\Models\User;

// Create table with default settings
$table = app(TableBuilder::class);
$html = $table->model(new User())
    ->render();

echo $html;
```

**Output:**
- All columns from users table
- Default sorting (id ascending)
- Search enabled on all columns
- Pagination (10 rows per page)
- Default actions (view, edit, delete)

### Example 2: Table with Custom Columns

```php
// Specify which columns to display
$html = $table->model(new User())
    ->setFields(['id', 'name', 'email', 'created_at'])
    ->render();
```

**Output:**
- Only id, name, email, created_at columns
- Auto-generated labels (Id, Name, Email, Created At)
- All other settings remain default

### Example 3: Table with Custom Labels

```php
// Using colon format
$html = $table->model(new User())
    ->setFields([
        'id:User ID',
        'name:Full Name',
        'email:Email Address',
        'created_at:Registration Date'
    ])
    ->render();

// Or using associative array
$html = $table->model(new User())
    ->setFields([
        'id' => 'User ID',
        'name' => 'Full Name',
        'email' => 'Email Address',
        'created_at' => 'Registration Date'
    ])
    ->render();
```


### Example 4: Table with Sorting and Searching

```php
// Enable sorting on specific columns
$html = $table->model(new User())
    ->setFields(['id', 'name', 'email', 'status', 'created_at'])
    ->sortable(['name', 'email', 'created_at']) // Only these columns sortable
    ->orderby('created_at', 'desc') // Default sort
    ->render();

// Enable searching on specific columns
$html = $table->model(new User())
    ->setFields(['id', 'name', 'email', 'status'])
    ->searchable(['name', 'email']) // Only search in name and email
    ->render();

// Disable sorting and searching
$html = $table->model(new User())
    ->setFields(['id', 'name', 'email'])
    ->sortable(false) // No sorting
    ->searchable(false) // No searching
    ->render();
```

### Example 5: Table with Pagination

```php
// Show 25 rows per page
$html = $table->model(new User())
    ->displayRowsLimitOnLoad(25)
    ->render();

// Show 50 rows per page
$html = $table->model(new User())
    ->displayRowsLimitOnLoad(50)
    ->render();

// Show all rows (no pagination)
$html = $table->model(new User())
    ->displayRowsLimitOnLoad('all')
    ->render();
```

### Example 6: Table with Filters

```php
// Filter active users only
$html = $table->model(new User())
    ->where('status', '=', 'active')
    ->render();

// Multiple filters
$html = $table->model(new User())
    ->where('status', '=', 'active')
    ->where('role', '=', 'admin')
    ->where('created_at', '>', '2024-01-01')
    ->render();

// Using filterConditions
$html = $table->model(new User())
    ->filterConditions([
        'status' => 'active',
        'role' => 'admin'
    ])
    ->render();
```

---

## Advanced Table Configuration

### Example 1: Table with Conditional Formatting

```php
// Highlight inactive users in red
$html = $table->model(new User())
    ->setFields(['id', 'name', 'email', 'status'])
    ->columnCondition(
        'status',           // Field to check
        'row',              // Apply to entire row
        '==',               // Operator
        'inactive',         // Value to compare
        'css style',        // Rule type
        'background-color: #fee; color: #c00;' // CSS to apply
    )
    ->render();

// Add prefix to premium users
$html = $table->model(new User())
    ->columnCondition(
        'subscription',
        'cell',
        '==',
        'premium',
        'prefix',
        '⭐ ' // Add star before name
    )
    ->render();
```


### Example 2: Table with Formula Columns

```php
// Calculate total price (quantity * price)
$html = $table->model(new Order())
    ->setFields(['id', 'product', 'quantity', 'price'])
    ->formula(
        'total',                    // Formula name
        'Total',                    // Column label
        ['quantity', 'price'],      // Fields to use
        'quantity * price',         // Formula logic
        'price',                    // Insert after price column
        true                        // Insert after (not before)
    )
    ->render();

// Calculate discount percentage
$html = $table->model(new Product())
    ->formula(
        'discount_pct',
        'Discount %',
        ['original_price', 'sale_price'],
        '((original_price - sale_price) / original_price) * 100',
        'sale_price',
        true
    )
    ->render();
```

### Example 3: Table with Data Formatting

```php
// Format numbers with decimals
$html = $table->model(new Product())
    ->setFields(['id', 'name', 'price', 'weight'])
    ->format(['price'], 2, '.', 'currency')  // $1,234.56
    ->format(['weight'], 2, '.', 'number')   // 1,234.56
    ->render();

// Format percentages
$html = $table->model(new Report())
    ->format(['conversion_rate'], 2, '.', 'percentage') // 12.34%
    ->render();

// Format dates
$html = $table->model(new User())
    ->format(['created_at'], 0, '', 'date') // 2024-01-15
    ->render();
```

### Example 4: Table with Column Styling

```php
// Set column widths
$html = $table->model(new User())
    ->setFields(['id', 'name', 'email', 'status'])
    ->setColumnWidth('id', 80)
    ->setColumnWidth('status', 120)
    ->render();

// Set table width
$html = $table->model(new User())
    ->setWidth(100, '%')  // Full width
    ->render();

// Align columns
$html = $table->model(new Order())
    ->setFields(['id', 'product', 'quantity', 'price', 'total'])
    ->setRightColumns(['quantity', 'price', 'total']) // Right align numbers
    ->setCenterColumns(['status'])                     // Center align status
    ->render();

// Set background colors
$html = $table->model(new User())
    ->setBackgroundColor(
        '#e3f2fd',          // Background color
        '#1565c0',          // Text color
        ['status'],         // Columns to apply
        true,               // Apply to header
        false               // Don't apply to body
    )
    ->render();
```

### Example 5: Table with Fixed Columns

```php
// Fix first 2 columns (id, name) on left
$html = $table->model(new User())
    ->fixedColumns(2, null)
    ->render();

// Fix last column (actions) on right
$html = $table->model(new User())
    ->fixedColumns(null, 1)
    ->render();

// Fix columns on both sides
$html = $table->model(new User())
    ->fixedColumns(2, 1) // Fix 2 left, 1 right
    ->render();
```


### Example 6: Table with All Configuration Options

```php
use Canvastack\Components\Table\TableBuilder;
use App\Models\Order;

$table = app(TableBuilder::class);

$html = $table
    // Model and data source
    ->model(new Order())
    ->setFields([
        'id:Order ID',
        'customer_name:Customer',
        'product_name:Product',
        'quantity:Qty',
        'price:Unit Price',
        'status:Status',
        'created_at:Order Date'
    ])
    
    // Sorting and searching
    ->orderby('created_at', 'desc')
    ->sortable(['customer_name', 'product_name', 'created_at'])
    ->searchable(['customer_name', 'product_name'])
    
    // Filtering
    ->where('status', '!=', 'cancelled')
    ->filterGroups('status', 'selectbox', false)
    
    // Display options
    ->displayRowsLimitOnLoad(25)
    ->setServerSide(true)
    
    // Column styling
    ->setColumnWidth('id', 80)
    ->setColumnWidth('quantity', 100)
    ->setRightColumns(['quantity', 'price'])
    ->setCenterColumns(['status'])
    
    // Conditional formatting
    ->columnCondition('status', 'cell', '==', 'completed', 'css style', 
        'background-color: #d4edda; color: #155724;')
    ->columnCondition('status', 'cell', '==', 'pending', 'css style',
        'background-color: #fff3cd; color: #856404;')
    
    // Formula columns
    ->formula('total', 'Total', ['quantity', 'price'], 
        'quantity * price', 'price', true)
    
    // Data formatting
    ->format(['price'], 2, '.', 'currency')
    ->format(['created_at'], 0, '', 'date')
    
    // Performance optimization
    ->cache(300)  // Cache for 5 minutes
    ->chunk(500)  // Process in chunks
    
    // Render
    ->render();

echo $html;
```

---

## Relational Data

### Example 1: Display Related Data

```php
use App\Models\Order;
use App\Models\Customer;

// Display customer name instead of customer_id
$html = $table->model(new Order())
    ->setFields(['id', 'customer_id', 'product', 'total'])
    ->relations(
        new Order(),
        'customer',         // Relationship method
        'name',             // Field to display
        [],                 // Filter foreign keys (empty = all)
        'Customer Name'     // Column label
    )
    ->render();
```

### Example 2: Multiple Relationships

```php
// Display customer and product names
$html = $table->model(new Order())
    ->setFields(['id', 'customer_id', 'product_id', 'quantity', 'total'])
    ->relations(new Order(), 'customer', 'name', [], 'Customer')
    ->relations(new Order(), 'product', 'name', [], 'Product')
    ->render();
```

### Example 3: Field Replacement

```php
// Replace customer_id with customer name
$html = $table->model(new Order())
    ->setFields(['id', 'customer_id', 'product', 'total'])
    ->fieldReplacementValue(
        new Order(),
        'customer',         // Relationship method
        'name',             // Field to display
        'Customer',         // Column label
        'customer_id'       // Field to replace
    )
    ->render();
```


### Example 4: Eager Loading for Performance

```php
// Without eager loading (N+1 problem - slow)
$html = $table->model(new Order())
    ->setFields(['id', 'customer.name', 'product.name', 'total'])
    ->render();
// Generates: 1 query for orders + N queries for customers + N queries for products

// With eager loading (fast)
$html = $table->model(new Order())
    ->setFields(['id', 'customer_id', 'product_id', 'total'])
    ->relations(new Order(), 'customer', 'name')
    ->relations(new Order(), 'product', 'name')
    ->render();
// Generates: 1 query for orders + 1 query for customers + 1 query for products = 3 queries total
```

### Example 5: Nested Relationships

```php
// Display customer's country
$html = $table->model(new Order())
    ->setFields(['id', 'customer_id', 'total'])
    ->relations(new Order(), 'customer.country', 'name', [], 'Country')
    ->render();
```

---

## Conditional Formatting

### Example 1: Cell-Level Formatting

```php
// Highlight high-value orders
$html = $table->model(new Order())
    ->setFields(['id', 'customer', 'total', 'status'])
    ->columnCondition(
        'total',
        'cell',             // Apply to cell only
        '>',
        '1000',
        'css style',
        'background-color: #fff3cd; font-weight: bold;'
    )
    ->render();
```

### Example 2: Row-Level Formatting

```php
// Highlight entire row for cancelled orders
$html = $table->model(new Order())
    ->columnCondition(
        'status',
        'row',              // Apply to entire row
        '==',
        'cancelled',
        'css style',
        'background-color: #f8d7da; text-decoration: line-through;'
    )
    ->render();
```

### Example 3: Multiple Conditions

```php
// Different colors for different statuses
$html = $table->model(new Order())
    ->setFields(['id', 'customer', 'total', 'status'])
    ->columnCondition('status', 'cell', '==', 'completed', 'css style',
        'background-color: #d4edda; color: #155724;')
    ->columnCondition('status', 'cell', '==', 'pending', 'css style',
        'background-color: #fff3cd; color: #856404;')
    ->columnCondition('status', 'cell', '==', 'cancelled', 'css style',
        'background-color: #f8d7da; color: #721c24;')
    ->render();
```

### Example 4: Prefix and Suffix

```php
// Add icons based on status
$html = $table->model(new Order())
    ->columnCondition('status', 'cell', '==', 'completed', 'prefix', '✓ ')
    ->columnCondition('status', 'cell', '==', 'pending', 'prefix', '⏳ ')
    ->columnCondition('status', 'cell', '==', 'cancelled', 'prefix', '✗ ')
    ->render();

// Add currency symbol
$html = $table->model(new Product())
    ->columnCondition('price', 'cell', '>', '0', 'prefix', '$')
    ->render();

// Add both prefix and suffix
$html = $table->model(new Product())
    ->columnCondition('discount', 'cell', '>', '0', 'prefix&suffix', ['Save ', '%'])
    ->render();
```

### Example 5: Value Replacement

```php
// Replace numeric status with text
$html = $table->model(new Order())
    ->columnCondition('status', 'cell', '==', '1', 'replace', 'Active')
    ->columnCondition('status', 'cell', '==', '0', 'replace', 'Inactive')
    ->render();
```

---

## Formula Columns

### Example 1: Simple Arithmetic

```php
// Calculate total = quantity * price
$html = $table->model(new Order())
    ->setFields(['id', 'product', 'quantity', 'price'])
    ->formula('total', 'Total', ['quantity', 'price'], 
        'quantity * price', 'price', true)
    ->render();
```


### Example 2: Complex Formulas

```php
// Calculate profit = revenue - cost
$html = $table->model(new Product())
    ->formula('profit', 'Profit', ['revenue', 'cost'],
        'revenue - cost', 'cost', true)
    ->render();

// Calculate discount percentage
$html = $table->model(new Product())
    ->formula('discount_pct', 'Discount %', ['original_price', 'sale_price'],
        '((original_price - sale_price) / original_price) * 100',
        'sale_price', true)
    ->render();

// Calculate average
$html = $table->model(new Student())
    ->formula('average', 'Average', ['math', 'english', 'science'],
        '(math + english + science) / 3', 'science', true)
    ->render();
```

### Example 3: Formulas with Logical Operators

```php
// Calculate bonus (if sales > 10000, bonus = sales * 0.1, else 0)
$html = $table->model(new Employee())
    ->formula('bonus', 'Bonus', ['sales'],
        'sales > 10000 ? sales * 0.1 : 0', 'sales', true)
    ->render();
```

### Example 4: Multiple Formula Columns

```php
// Calculate subtotal, tax, and total
$html = $table->model(new Order())
    ->setFields(['id', 'product', 'quantity', 'price'])
    ->formula('subtotal', 'Subtotal', ['quantity', 'price'],
        'quantity * price', 'price', true)
    ->formula('tax', 'Tax (10%)', ['quantity', 'price'],
        'quantity * price * 0.1', 'subtotal', true)
    ->formula('total', 'Total', ['quantity', 'price'],
        'quantity * price * 1.1', 'tax', true)
    ->render();
```

### Example 5: Formula with Formatting

```php
// Calculate and format total
$html = $table->model(new Order())
    ->formula('total', 'Total', ['quantity', 'price'],
        'quantity * price', 'price', true)
    ->format(['total'], 2, '.', 'currency')
    ->render();
```

---

## Custom Actions

### Example 1: Default Actions

```php
// Show default actions (view, edit, delete)
$html = $table->model(new User())
    ->setActions(true)
    ->render();

// Or using legacy lists() method
$html = $table->lists('users', ['id', 'name', 'email'], true);
```

### Example 2: No Actions

```php
// Hide all action buttons
$html = $table->model(new User())
    ->setActions(false)
    ->render();
```

### Example 3: Custom Action Buttons

```php
// Add custom actions
$html = $table->model(new User())
    ->setActions([
        [
            'label' => 'View Profile',
            'icon' => 'eye',
            'url' => '/users/{id}/profile',
            'class' => 'btn-primary'
        ],
        [
            'label' => 'Send Email',
            'icon' => 'mail',
            'url' => '/users/{id}/email',
            'class' => 'btn-secondary'
        ],
        [
            'label' => 'Delete',
            'icon' => 'trash',
            'url' => '/users/{id}/delete',
            'class' => 'btn-danger',
            'method' => 'DELETE',
            'confirm' => 'Are you sure you want to delete this user?'
        ]
    ])
    ->render();
```

### Example 4: Merge Custom with Default Actions

```php
// Add custom actions alongside default actions
$html = $table->model(new User())
    ->setActions([
        [
            'label' => 'Reset Password',
            'icon' => 'key',
            'url' => '/users/{id}/reset-password',
            'class' => 'btn-warning'
        ]
    ], true) // true = include default actions
    ->render();
```


### Example 5: Remove Specific Buttons

```php
// Show default actions but remove delete button
$html = $table->model(new User())
    ->setActions(true)
    ->removeButtons(['delete'])
    ->render();

// Remove multiple buttons
$html = $table->model(new User())
    ->setActions(true)
    ->removeButtons(['edit', 'delete']) // Only show view button
    ->render();
```

### Example 6: Custom URL Value Field

```php
// Use 'uuid' instead of 'id' for action URLs
$html = $table->model(new User())
    ->setUrlValue('uuid')
    ->setActions(true)
    ->render();
// URLs will be: /users/{uuid}/view, /users/{uuid}/edit, etc.
```

---

## Legacy vs Enhanced API

### Example 1: Legacy API (Backward Compatible)

```php
// Legacy lists() method - still works
$html = $table->lists(
    'users',                    // Table name
    ['id', 'name', 'email'],   // Fields
    true,                       // Actions
    true,                       // Server-side
    true,                       // Numbering
    ['class' => 'table-striped'], // Attributes
    false                       // Custom URL
);

// Legacy method chaining
$html = $table->model(new User())
    ->setFields(['id', 'name', 'email'])
    ->orderby('name', 'asc')
    ->sortable(true)
    ->searchable(true)
    ->setActions(true)
    ->render();
```

### Example 2: Enhanced API (New Features)

```php
// Enhanced API with performance optimizations
$html = $table->model(new User())
    ->setFields(['id', 'name', 'email', 'role_id'])
    ->orderby('name', 'asc')
    ->sortable(['name', 'email'])
    ->searchable(['name', 'email'])
    ->relations(new User(), 'role', 'name') // Eager loading
    ->cache(300)                             // Caching
    ->chunk(500)                             // Chunk processing
    ->setActions(true)
    ->render();
```

### Example 3: Side-by-Side Comparison

```php
// LEGACY: Basic table
$legacyHtml = $table->lists('users', ['id', 'name', 'email'], true);

// ENHANCED: Same table with optimizations
$enhancedHtml = $table->model(new User())
    ->setFields(['id', 'name', 'email'])
    ->setActions(true)
    ->cache(300)    // NEW: Caching
    ->chunk(500)    // NEW: Chunk processing
    ->render();

// LEGACY: Table with relationships (N+1 problem)
$legacyHtml = $table->model(new Order())
    ->setFields(['id', 'customer.name', 'product.name', 'total'])
    ->render();

// ENHANCED: Same table with eager loading (no N+1)
$enhancedHtml = $table->model(new Order())
    ->setFields(['id', 'customer_id', 'product_id', 'total'])
    ->relations(new Order(), 'customer', 'name')
    ->relations(new Order(), 'product', 'name')
    ->cache(300)
    ->render();
```

---

## Admin vs Public Context

### Example 1: Admin Context (Bootstrap)

```php
use Canvastack\Components\Table\TableBuilder;
use App\Models\User;

// Create table for admin panel
$table = app(TableBuilder::class);
$table->setContext('admin'); // Bootstrap styling

$html = $table->model(new User())
    ->setFields(['id', 'name', 'email', 'role', 'status'])
    ->setActions(true) // Show CRUD actions
    ->addAttributes(['class' => 'table table-striped table-hover'])
    ->render();

echo $html;
```

**Output:**
- Bootstrap 5 classes
- CRUD action buttons (view, edit, delete)
- Admin-specific styling
- DataTables with Bootstrap theme


### Example 2: Public Context (Tailwind)

```php
// Create table for public website
$table = app(TableBuilder::class);
$table->setContext('public'); // Tailwind styling

$html = $table->model(new Product())
    ->setFields(['name', 'description', 'price', 'availability'])
    ->setActions(false) // No CRUD actions for public
    ->addAttributes(['class' => 'w-full'])
    ->render();

echo $html;
```

**Output:**
- Tailwind CSS classes
- No action buttons
- Public-friendly styling
- Responsive design
- Dark mode support

### Example 3: Context Switching

```php
// Admin table
$adminTable = app(TableBuilder::class);
$adminTable->setContext('admin');
$adminHtml = $adminTable->model(new User())
    ->setFields(['id', 'name', 'email', 'role'])
    ->setActions(true)
    ->render();

// Public table (same data, different presentation)
$publicTable = app(TableBuilder::class);
$publicTable->setContext('public');
$publicHtml = $publicTable->model(new User())
    ->setFields(['name', 'bio', 'joined_date'])
    ->setActions(false)
    ->render();
```

### Example 4: Admin Table with All Features

```php
// Full-featured admin table
$html = $table->setContext('admin')
    ->model(new Order())
    ->setFields([
        'id:Order ID',
        'customer_name:Customer',
        'product_name:Product',
        'quantity:Qty',
        'price:Price',
        'status:Status'
    ])
    
    // Sorting and searching
    ->orderby('created_at', 'desc')
    ->sortable(['customer_name', 'product_name', 'created_at'])
    ->searchable(['customer_name', 'product_name'])
    
    // Relationships
    ->relations(new Order(), 'customer', 'name')
    ->relations(new Order(), 'product', 'name')
    
    // Conditional formatting
    ->columnCondition('status', 'cell', '==', 'completed', 'css style',
        'background-color: #d4edda; color: #155724;')
    
    // Formula columns
    ->formula('total', 'Total', ['quantity', 'price'],
        'quantity * price', 'price', true)
    
    // Formatting
    ->format(['price'], 2, '.', 'currency')
    
    // Actions
    ->setActions(true)
    ->removeButtons(['delete'])
    
    // Performance
    ->cache(300)
    ->chunk(500)
    
    // Styling
    ->addAttributes(['class' => 'table table-striped table-hover'])
    
    ->render();
```

### Example 5: Public Table with Dark Mode

```php
// Public table with dark mode support
$html = $table->setContext('public')
    ->model(new Product())
    ->setFields([
        'name:Product Name',
        'description:Description',
        'price:Price',
        'rating:Rating'
    ])
    
    // Styling for dark mode
    ->addAttributes([
        'class' => 'w-full bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100'
    ])
    
    // Formatting
    ->format(['price'], 2, '.', 'currency')
    ->format(['rating'], 1, '.', 'number')
    
    // No actions for public
    ->setActions(false)
    
    // Performance
    ->cache(600) // Cache longer for public
    
    ->render();
```

### Example 6: Responsive Public Table

```php
// Mobile-friendly public table
$html = $table->setContext('public')
    ->model(new Event())
    ->setFields([
        'title:Event',
        'date:Date',
        'location:Location',
        'available_seats:Seats'
    ])
    
    // Responsive classes
    ->addAttributes([
        'class' => 'w-full overflow-x-auto'
    ])
    
    // Hide less important columns on mobile
    ->setHiddenColumns(['location']) // Can be shown via responsive plugin
    
    // Formatting
    ->format(['date'], 0, '', 'date')
    
    // No actions
    ->setActions(false)
    
    ->render();
```

---

## Complete Real-World Examples

### Example 1: E-commerce Order Management (Admin)

```php
use Canvastack\Components\Table\TableBuilder;
use App\Models\Order;

$table = app(TableBuilder::class);

$html = $table->setContext('admin')
    ->model(new Order())
    ->setFields([
        'id:Order #',
        'customer_id',
        'product_id',
        'quantity:Qty',
        'price:Unit Price',
        'status:Status',
        'created_at:Order Date'
    ])
    
    // Relationships (prevent N+1)
    ->relations(new Order(), 'customer', 'name', [], 'Customer')
    ->relations(new Order(), 'product', 'name', [], 'Product')
    
    // Sorting and searching
    ->orderby('created_at', 'desc')
    ->sortable(['customer.name', 'product.name', 'created_at'])
    ->searchable(['customer.name', 'product.name'])
    
    // Filtering
    ->filterGroups('status', 'selectbox', false)
    ->where('status', '!=', 'cancelled')
    
    // Formula columns
    ->formula('subtotal', 'Subtotal', ['quantity', 'price'],
        'quantity * price', 'price', true)
    ->formula('tax', 'Tax', ['quantity', 'price'],
        'quantity * price * 0.1', 'subtotal', true)
    ->formula('total', 'Total', ['quantity', 'price'],
        'quantity * price * 1.1', 'tax', true)
    
    // Formatting
    ->format(['price', 'subtotal', 'tax', 'total'], 2, '.', 'currency')
    ->format(['created_at'], 0, '', 'date')
    
    // Conditional formatting
    ->columnCondition('status', 'cell', '==', 'completed', 'css style',
        'background-color: #d4edda; color: #155724; font-weight: bold;')
    ->columnCondition('status', 'cell', '==', 'pending', 'css style',
        'background-color: #fff3cd; color: #856404;')
    ->columnCondition('status', 'cell', '==', 'processing', 'css style',
        'background-color: #d1ecf1; color: #0c5460;')
    
    // Column styling
    ->setRightColumns(['quantity', 'price', 'subtotal', 'tax', 'total'])
    ->setCenterColumns(['status'])
    ->setColumnWidth('id', 100)
    ->setColumnWidth('quantity', 80)
    
    // Actions
    ->setActions([
        [
            'label' => 'View',
            'icon' => 'eye',
            'url' => '/admin/orders/{id}',
            'class' => 'btn-sm btn-primary'
        ],
        [
            'label' => 'Invoice',
            'icon' => 'file-text',
            'url' => '/admin/orders/{id}/invoice',
            'class' => 'btn-sm btn-secondary'
        ],
        [
            'label' => 'Cancel',
            'icon' => 'x-circle',
            'url' => '/admin/orders/{id}/cancel',
            'class' => 'btn-sm btn-danger',
            'method' => 'POST',
            'confirm' => 'Cancel this order?'
        ]
    ])
    
    // Performance optimization
    ->setServerSide(true)
    ->displayRowsLimitOnLoad(25)
    ->cache(60) // Cache for 1 minute
    ->chunk(500)
    
    // Styling
    ->addAttributes(['class' => 'table table-striped table-hover'])
    
    ->render();

echo $html;
```


### Example 2: Product Catalog (Public)

```php
use Canvastack\Components\Table\TableBuilder;
use App\Models\Product;

$table = app(TableBuilder::class);

$html = $table->setContext('public')
    ->model(new Product())
    ->setFields([
        'image:',
        'name:Product',
        'description:Description',
        'price:Price',
        'rating:Rating',
        'stock:In Stock'
    ])
    
    // Sorting
    ->orderby('rating', 'desc')
    ->sortable(['name', 'price', 'rating'])
    
    // Searching
    ->searchable(['name', 'description'])
    
    // Filtering
    ->where('active', '=', true)
    ->where('stock', '>', 0)
    ->filterGroups('category_id', 'selectbox', false)
    
    // Relationships
    ->relations(new Product(), 'category', 'name', [], 'Category')
    
    // Formatting
    ->format(['price'], 2, '.', 'currency')
    ->format(['rating'], 1, '.', 'number')
    
    // Conditional formatting
    ->columnCondition('stock', 'cell', '<', '10', 'css style',
        'color: #dc3545; font-weight: bold;')
    ->columnCondition('stock', 'cell', '<', '10', 'suffix', ' (Low Stock)')
    
    // Column styling
    ->setRightColumns(['price', 'rating'])
    ->setCenterColumns(['stock'])
    
    // No actions for public
    ->setActions(false)
    
    // Performance
    ->setServerSide(false) // Client-side for better UX
    ->displayRowsLimitOnLoad(12)
    ->cache(600) // Cache for 10 minutes
    
    // Responsive styling
    ->addAttributes([
        'class' => 'w-full bg-white dark:bg-gray-800 rounded-lg shadow'
    ])
    
    ->render();

echo $html;
```

### Example 3: User Activity Log (Admin)

```php
use Canvastack\Components\Table\TableBuilder;
use App\Models\ActivityLog;

$table = app(TableBuilder::class);

$html = $table->setContext('admin')
    ->model(new ActivityLog())
    ->setFields([
        'id:ID',
        'user_id',
        'action:Action',
        'description:Description',
        'ip_address:IP',
        'created_at:Timestamp'
    ])
    
    // Relationships
    ->relations(new ActivityLog(), 'user', 'name', [], 'User')
    
    // Sorting
    ->orderby('created_at', 'desc')
    ->sortable(['user.name', 'action', 'created_at'])
    
    // Searching
    ->searchable(['user.name', 'action', 'description', 'ip_address'])
    
    // Filtering
    ->filterGroups('action', 'selectbox', false)
    ->filterGroups('created_at', 'daterangebox', false)
    
    // Formatting
    ->format(['created_at'], 0, '', 'date')
    
    // Conditional formatting (highlight critical actions)
    ->columnCondition('action', 'row', '==', 'delete', 'css style',
        'background-color: #f8d7da;')
    ->columnCondition('action', 'row', '==', 'login_failed', 'css style',
        'background-color: #fff3cd;')
    
    // Column styling
    ->setColumnWidth('id', 80)
    ->setColumnWidth('ip_address', 150)
    
    // Actions
    ->setActions([
        [
            'label' => 'View Details',
            'icon' => 'eye',
            'url' => '/admin/logs/{id}',
            'class' => 'btn-sm btn-info'
        ]
    ])
    
    // Performance
    ->setServerSide(true)
    ->displayRowsLimitOnLoad(50)
    ->cache(30) // Short cache for real-time data
    ->chunk(1000)
    
    ->render();

echo $html;
```

---

## Performance Optimization Examples

### Example 1: Large Dataset with Caching

```php
// Handle 100,000+ records efficiently
$html = $table->model(new Transaction())
    ->setFields(['id', 'date', 'amount', 'status'])
    ->setServerSide(true)      // Load only current page
    ->displayRowsLimitOnLoad(50)
    ->cache(300)                // Cache for 5 minutes
    ->chunk(1000)               // Process in chunks
    ->render();
```

### Example 2: Complex Relationships with Eager Loading

```php
// Prevent N+1 queries with multiple relationships
$html = $table->model(new Order())
    ->setFields(['id', 'customer_id', 'product_id', 'shipper_id', 'total'])
    ->relations(new Order(), 'customer', 'name')
    ->relations(new Order(), 'product', 'name')
    ->relations(new Order(), 'shipper', 'company_name')
    ->cache(300)
    ->render();
// Only 4 queries: 1 for orders + 3 for relationships
```

### Example 3: Static Data with Long Cache

```php
// Reference data that rarely changes
$html = $table->model(new Country())
    ->setFields(['code', 'name', 'continent', 'population'])
    ->cache(86400) // Cache for 24 hours
    ->setServerSide(false) // Load all at once
    ->render();
```

---

## Additional Tips

### Tip 1: Method Chaining

All configuration methods return `$this`, allowing for clean method chaining:

```php
$html = $table
    ->model(new User())
    ->setFields(['id', 'name', 'email'])
    ->orderby('name', 'asc')
    ->sortable(true)
    ->searchable(true)
    ->cache(300)
    ->render();
```

### Tip 2: Reusable Configuration

Create reusable table configurations:

```php
// Base configuration
$baseTable = $table->model(new User())
    ->setFields(['id', 'name', 'email', 'status'])
    ->sortable(true)
    ->searchable(true)
    ->cache(300);

// Admin version
$adminHtml = clone $baseTable;
$adminHtml->setContext('admin')
    ->setActions(true)
    ->render();

// Public version
$publicHtml = clone $baseTable;
$publicHtml->setContext('public')
    ->setActions(false)
    ->render();
```

### Tip 3: Debugging

Enable query logging to debug performance issues:

```php
DB::enableQueryLog();

$html = $table->model(new User())
    ->setFields(['id', 'name', 'email'])
    ->render();

$queries = DB::getQueryLog();
dd($queries); // See all executed queries
```

---

## Server-Side Processing Examples

### Example 1: Basic Server-Side Table with Default POST

The most common use case - secure server-side processing with POST method and CSRF protection.

```php
use Canvastack\Components\Table\TableBuilder;
use App\Models\User;

// In your controller view method
public function index()
{
    $table = app(TableBuilder::class);
    
    $html = $table->model(User::class)
        ->setFields(['id', 'name', 'email', 'created_at'])
        ->setServerSide(true)  // Enable server-side processing
        // POST method used by default (secure)
        ->render();
    
    return view('admin.users.index', compact('html'));
}

// In your controller datatable method
public function datatable(Request $request)
{
    $table = app(TableBuilde
r::class);
    
    $table->model(User::class)
        ->setFields(['id', 'name', 'email', 'created_at'])
        ->setServerSide(true);
    
    return $table->ajax($request);
}
```

**Generated JavaScript:**
```javascript
$('#table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: '/admin/users/datatable',
        type: 'POST',  // Default method
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    }
});
```

**Route Configuration:**
```php
// routes/web.php
Route::post('/admin/users/datatable', [UserController::class, 'datatable'])
    ->name('users.datatable');
```

**Security Features:**
- ✅ CSRF token automatically included
- ✅ Request parameters not visible in URLs/logs
- ✅ No URL length limitations
- ✅ Secure by default

**Use Case:** Most admin tables with sensitive user data

---

### Example 2: Server-Side Table with Explicit GET

Use GET method for public, cacheable data that doesn't require CSRF protection.

```php
use Canvastack\Components\Table\TableBuilder;
use App\Models\Product;

// In your controller view method
public function catalog()
{
    $table = app(TableBuilder::class);
    
    $html = $table->model(Product::where('published', true))
        ->setFields(['name', 'description', 'price', 'category'])
        ->setServerSide(true)
        ->setHttpMethod('GET')  // Explicitly use GET method
        ->render();
    
    return view('public.catalog', compact('html'));
}

// In your controller datatable method
public function catalogData(Request $request)
{
    $table = app(TableBuilder::class);
    
    $table->model(Product::where('published', true))
        ->setFields(['name', 'description', 'price', 'category'])
        ->setServerSide(true)
        ->setHttpMethod('GET');
    
    return $table->ajax($request);
}
```

**Generated JavaScript:**
```javascript
$('#table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: '/catalog/data',
        type: 'GET'  // No CSRF token needed
    }
});
```

**Route Configuration:**
```php
// routes/web.php
Route::get('/catalog/data', [ProductController::class, 'catalogData'])
    ->name('catalog.data');
```

**Benefits:**
- ✅ Browser/CDN caching enabled
- ✅ Bookmarkable URLs with filters
- ✅ Easier debugging (parameters visible in URL)
- ✅ No CSRF token required

**Use Case:** Public product catalogs, reference data, read-only tables

---

### Example 3: Server-Side Table with Custom AJAX URL

Configure a custom endpoint for data loading, useful for API integration or microservices.

```php
use Canvastack\Components\Table\TableBuilder;
use App\Models\Order;

// In your controller view method
public function orders()
{
    $table = app(TableBuilder::class);
    
    $html = $table->model(Order::class)
        ->setFields(['id', 'customer', 'product', 'status', 'total'])
        ->setServerSide(true)
        ->setHttpMethod('POST')
        ->setAjaxUrl(route('api.orders.datatable'))  // Custom URL
        ->render();
    
    return view('admin.orders.index', compact('html'));
}

// In your API controller
public function datatable(Request $request)
{
    $table = app(TableBuilder::class);
    
    $table->model(Order::class)
        ->setFields(['id', 'customer', 'product', 'status', 'total'])
        ->setServerSide(true);
    
    return $table->ajax($request);
}
```

**Generated JavaScript:**
```javascript
$('#table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: 'https://example.com/api/orders/datatable',  // Custom URL
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    }
});
```

**Route Configuration:**
```php
// routes/api.php
Route::post('/orders/datatable', [ApiOrderController::class, 'datatable'])
    ->name('api.orders.datatable')
    ->middleware('auth:sanctum');
```

**Use Cases:**
- API endpoints
- Microservices architecture
- Separate API server
- Custom authentication

---

### Example 4: Server-Side Table with Filter Groups

Complex filtering with POST method to avoid URL length limitations.

```php
use Canvastack\Components\Table\TableBuilder;
use App\Models\Transaction;

// In your controller view method
public function transactions()
{
    $table = app(TableBuilder::class);
    
    $html = $table->model(Transaction::class)
        ->setFields(['id', 'date', 'amount', 'status', 'category', 'user_id'])
        ->setServerSide(true)
        ->setHttpMethod('POST')  // POST recommended for complex filters
        ->filterGroups('status', 'selectbox', false)
        ->filterGroups('category', 'selectbox', false)
        ->filterGroups('date', 'daterangebox', false)
        ->filterGroups('amount', 'inputbox', false)
        ->render();
    
    return view('admin.transactions.index', compact('html'));
}

// In your controller datatable method
public function datatable(Request $request)
{
    $table = app(TableBuilder::class);
    
    $table->model(Transaction::class)
        ->setFields(['id', 'date', 'amount', 'status', 'category', 'user_id'])
        ->setServerSide(true)
        ->filterGroups('status', 'selectbox', false)
        ->filterGroups('category', 'selectbox', false)
        ->filterGroups('date', 'daterangebox', false)
        ->filterGroups('amount', 'inputbox', false);
    
    return $table->ajax($request);
}
```

**Generated JavaScript:**
```javascript
$('#table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: '/admin/transactions/datatable',
        type: 'POST',  // POST handles complex filter data
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: function(d) {
            // Add filter values to request
            d.status_filter = $('#status-filter').val();
            d.category_filter = $('#category-filter').val();
            d.date_range = $('#date-range').val();
            d.amount_filter = $('#amount-filter').val();
            return d;
        }
    }
});
```

**Why POST for Filters:**
- Complex filter data can exceed URL length limits (2000 chars)
- Filter values remain private (not in browser history)
- Better security for sensitive filter criteria
- CSRF protection for filter operations

**Use Case:** Admin dashboards with multiple filters, reporting tools, analytics tables

---

### Example 5: Client-Side Table (No AJAX)

For small datasets, load all data at once without AJAX requests.

```php
use Canvastack\Components\Table\TableBuilder;
use App\Models\Country;

// In your controller view method
public function countries()
{
    $table = app(TableBuilder::class);
    
    $html = $table->model(Country::class)
        ->setFields(['code', 'name', 'continent', 'population'])
        ->setServerSide(false)  // Client-side processing
        // HTTP method not used (no AJAX)
        ->displayRowsLimitOnLoad(25)
        ->render();
    
    return view('admin.countries.index', compact('html'));
}
```

**Generated JavaScript:**
```javascript
$('#table').DataTable({
    processing: false,
    serverSide: false,
    // No AJAX configuration
    // All data loaded in HTML
});
```

**Behavior:**
- All data loaded at once in HTML table
- No AJAX requests made
- HTTP method configuration ignored
- Sorting/searching/pagination done in browser
- Suitable for small datasets (< 1000 rows)

**Benefits:**
- ✅ Instant sorting/searching (no server round-trip)
- ✅ Works offline after initial load
- ✅ Simpler implementation
- ✅ No server-side endpoint needed

**Use Case:** Reference data, small lookup tables, static data

---

### Example 6: Troubleshooting AJAX Errors

Common AJAX issues and how to debug them.

#### Issue 1: 419 CSRF Token Mismatch (POST)

**Symptoms:**
```
POST /admin/users/datatable 419 (unknown status)
```

**Solution:**
```php
// 1. Ensure CSRF meta tag is present in layout
// resources/views/layouts/app.blade.php
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

// 2. Verify CSRF middleware is enabled
// app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\VerifyCsrfToken::class,
    ],
];

// 3. Or switch to GET method if CSRF is problematic
$table->setHttpMethod('GET');
```

#### Issue 2: 404 Not Found

**Symptoms:**
```
GET /admin/users/datatable 404 (Not Found)
```

**Solution:**
```php
// 1. Check route exists
php artisan route:list | grep datatable

// 2. Verify HTTP method matches
// If table uses GET:
$table->setHttpMethod('GET');

// Route must also be GET:
Route::get('/admin/users/datatable', [UserController::class, 'datatable']);

// 3. Use named routes for reliability
Route::post('/admin/users/datatable', [UserController::class, 'datatable'])
    ->name('users.datatable');

$table->setAjaxUrl(route('users.datatable'));
```

#### Issue 3: 500 Internal Server Error

**Symptoms:**
```
POST /admin/users/datatable 500 (Internal Server Error)
```

**Solution:**
```php
// 1. Check Laravel logs
tail -f storage/logs/laravel.log

// 2. Common causes:

// ❌ Model not set
public function datatable(Request $request)
{
    $table = app(TableBuilder::class);
    // Missing: $table->model(User::class);
    return $table->ajax($request);
}

// ✅ Correct
public function datatable(Request $request)
{
    $table = app(TableBuilder::class);
    $table->model(User::class);  // Set model first
    $table->setFields(['id', 'name', 'email']);
    return $table->ajax($request);
}

// 3. Add error handling
public function datatable(Request $request)
{
    try {
        $table = app(TableBuilder::class);
        $table->model(User::class)
            ->setFields(['id', 'name', 'email'])
            ->setServerSide(true);
        
        return $table->ajax($request);
    } catch (\Exception $e) {
        \Log::error('DataTable error: ' . $e->getMessage());
        
        return response()->json([
            'error' => 'Failed to load data'
        ], 500);
    }
}
```

#### Issue 4: Table Shows "Loading..." Forever

**Symptoms:**
- Table shows "Processing..." message indefinitely
- No data appears
- No errors in console

**Solution:**
```php
// 1. Check server-side processing is enabled
$table->setServerSide(true);  // Must be true

// 2. Verify AJAX URL is correct
// Open browser console (F12) → Network tab
// Check if request is being made to correct URL

// 3. Verify controller returns JSON
public function datatable(Request $request)
{
    $table = app(TableBuilder::class);
    $table->model(User::class)
        ->setFields(['id', 'name', 'email'])
        ->setServerSide(true);
    
    // Must return JSON response
    return $table->ajax($request);
}

// 4. Check response format
// Response should be JSON with structure:
{
    "draw": 1,
    "recordsTotal": 100,
    "recordsFiltered": 100,
    "data": [...]
}
```

#### Issue 5: Slow AJAX Requests (> 2 seconds)

**Symptoms:**
- Table takes long time to load
- "Processing..." shows for several seconds
- Poor user experience

**Solution:**
```php
// 1. Enable caching
$table->config(['cache_seconds' => 300]);  // 5 minutes

// 2. Use eager loading for relationships
$table->relations(User::class, 'role', 'name');  // Prevent N+1

// 3. Optimize query
$table->model(User::select(['id', 'name', 'email']));  // Select only needed columns

// 4. Add database indexes
Schema::table('users', function (Blueprint $table) {
    $table->index('email');
    $table->index('created_at');
});

// 5. Monitor query count
DB::enableQueryLog();
$response = $table->ajax($request);
$queries = DB::getQueryLog();
\Log::info('Query count: ' . count($queries));  // Should be ≤ 5
```

#### Debugging Checklist

Use this checklist to debug AJAX issues:

- [ ] **CSRF token present** - Check `<meta name="csrf-token">` in HTML head
- [ ] **Route exists** - Run `php artisan route:list | grep datatable`
- [ ] **HTTP method matches** - GET in table = GET in route, POST = POST
- [ ] **Model is set** - Call `$table->model()` before `ajax()`
- [ ] **Server-side enabled** - Call `$table->setServerSide(true)`
- [ ] **Controller returns JSON** - Use `return $table->ajax($request)`
- [ ] **Check browser console** - Look for JavaScript errors (F12)
- [ ] **Check network tab** - Verify AJAX request is being made
- [ ] **Check Laravel logs** - Look for PHP errors in `storage/logs/laravel.log`
- [ ] **Test endpoint directly** - Visit AJAX URL in browser to see raw response

---

## HTTP Method Configuration Summary

### Quick Reference

```php
// Default (POST with CSRF) - Recommended for most cases
$table->setServerSide(true)->render();

// Explicit POST
$table->setServerSide(true)
    ->setHttpMethod('POST')
    ->render();

// Use GET for public/cacheable data
$table->setServerSide(true)
    ->setHttpMethod('GET')
    ->render();

// Custom AJAX URL
$table->setServerSide(true)
    ->setHttpMethod('POST')
    ->setAjaxUrl(route('custom.datatable'))
    ->render();

// Client-side (no AJAX)
$table->setServerSide(false)
    ->render();
```

### When to Use Each Method

| Scenario | Method | Reason |
|----------|--------|--------|
| Admin tables with sensitive data | POST | CSRF protection, secure |
| Public product catalogs | GET | Cacheable, bookmarkable |
| Complex filters | POST | No URL length limits |
| API integration | POST | Custom URL, auth headers |
| Small datasets (< 1000 rows) | None | Client-side processing |
| Reference data | GET | Long cache duration |

### Security Comparison

| Feature | POST | GET |
|---------|------|-----|
| CSRF Protection | ✅ Yes | ❌ No |
| URL Length Limit | ✅ No limit | ⚠️ ~2000 chars |
| Browser Caching | ❌ Not cached | ✅ Cached |
| Visible in Logs | ✅ Hidden | ❌ Visible |
| Bookmarkable | ❌ No | ✅ Yes |
| Security Level | ✅ Higher | ⚠️ Lower |

---

**For more information:**
- [HTTP Method Configuration Guide](./HTTP-METHOD-CONFIGURATION.md)
- [API Documentation](./API-DOCUMENTATION.md)
- [Troubleshooting Guide](./TROUBLESHOOTING.md)
- [Performance Tuning](./PERFORMANCE-TUNING.md)

