# Table Components Usage Examples

## Overview

This guide provides practical examples for common table scenarios using the Canvastack Origin Table Components system.

---

## Table of Contents

- [Basic Tables](#basic-tables)
- [Server-Side Processing](#server-side-processing)
- [Relationships](#relationships)
- [Filtering and Searching](#filtering-and-searching)
- [Formatting](#formatting)
- [Actions](#actions)
- [Advanced Features](#advanced-features)
- [Security Examples](#security-examples)

---

## Basic Tables

### Simple Table

```php
use Canvastack\Canvastack\Library\Components\Table\Objects;

$table = new Objects();
$table->lists('users', ['id', 'name', 'email'], true);
```

**Output:** Basic table with ID, Name, Email columns and default actions.

---

### Table with Custom Labels

```php
$table = new Objects();
$table->setName('users');
$table->setFields([
    'id' => 'User ID',
    'name' => 'Full Name',
    'email' => 'Email Address',
    'created_at' => 'Registration Date'
]);
$table->lists();
```

---

### Table Without Actions

```php
$table = new Objects();
$table->lists('users', ['id', 'name', 'email'], false);
```

---

### Table with Custom Attributes

```php
$table = new Objects();
$table->setName('users');
$table->setFields(['id', 'name', 'email']);
$table->addAttributes([
    'class' => 'table-striped table-hover',
    'data-custom' => 'value'
]);
$table->setWidth(100, '%');
$table->lists();
```

---

## Server-Side Processing

### Large Dataset Table

```php
$table = new Objects();
$table->setName('orders');
$table->setFields(['id', 'order_number', 'customer', 'total', 'status']);
$table->setServerSide(true); // Enable server-side processing
$table->displayRowsLimitOnLoad(25); // 25 rows per page
$table->lists();
```

**Performance:** Handles millions of rows efficiently.

---

### Table with Eloquent Model

```php
use App\Models\User;

$table = new Objects();
$table->model(User::class);
$table->setFields(['id', 'name', 'email', 'role']);
$table->setServerSide(true);
$table->lists();
```

**Benefit:** Automatic relationship handling and query optimization.

---

### Custom AJAX URL

```php
$table = new Objects();
$table->setName('products');
$table->setFields(['id', 'name', 'price', 'stock']);
$table->setServerSide(true);
$table->lists(null, [], true, true, true, [], '/api/custom/products-data');
```

---

## Relationships

### BelongsTo Relationship

```php
use App\Models\Post;

$table = new Objects();
$table->model(Post::class);
$table->relations(Post::class, 'user', 'name', [], 'Author');
$table->setFields(['id', 'title', 'user.name', 'created_at']);
$table->lists();
```

**Output:** Posts table with author name from related User model.

---

### Multiple Relationships

```php
use App\Models\Order;

$table = new Objects();
$table->model(Order::class);
$table->relations(Order::class, 'customer', 'name', [], 'Customer');
$table->relations(Order::class, 'product', 'name', [], 'Product');
$table->setFields([
    'id',
    'order_number',
    'customer.name',
    'product.name',
    'quantity',
    'total'
]);
$table->lists();
```

---

### Nested Relationships

```php
use App\Models\Comment;

$table = new Objects();
$table->model(Comment::class);
$table->relations(Comment::class, 'post.user', 'name', [], 'Post Author');
$table->setFields([
    'id',
    'content',
    'post.title',
    'post.user.name',
    'created_at'
]);
$table->lists();
```

**Output:** Comments with post title and post author name.

---

### Field Replacement

```php
use App\Models\Product;

$table = new Objects();
$table->model(Product::class);
$table->fieldReplacementValue(Product::class, 'category', 'name', 'Category');
$table->setFields(['id', 'name', 'category_id', 'price']);
$table->lists();
```

**Output:** Category ID replaced with category name.

---

## Filtering and Searching

### Basic WHERE Conditions

```php
$table = new Objects();
$table->setName('users');
$table->where('status', '=', 'active');
$table->where('role', '!=', 'admin');
$table->setFields(['id', 'name', 'email', 'status']);
$table->lists();
```

---

### Multiple Conditions

```php
$table = new Objects();
$table->setName('orders');
$table->where([
    ['status', '=', 'completed'],
    ['total', '>', 100],
    ['created_at', '>=', '2024-01-01']
]);
$table->setFields(['id', 'order_number', 'total', 'status']);
$table->lists();
```

---

### Searchable Columns

```php
$table = new Objects();
$table->setName('products');
$table->setFields(['id', 'name', 'description', 'price', 'sku']);
$table->searchable(['name', 'description', 'sku']);
$table->lists();
```

**Feature:** Global search box searches across specified columns.

---

### Advanced Filters

```php
$table = new Objects();
$table->setName('orders');
$table->setFields(['id', 'order_number', 'customer', 'total', 'status', 'created_at']);

// Enable advanced filtering
$table->filterGroups('status', 'select', [
    'pending' => 'Pending',
    'processing' => 'Processing',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
]);
$table->filterGroups('total', 'number', false);
$table->filterGroups('created_at', 'date', false);

$table->lists();
```

**Feature:** Modal-based advanced filter UI.

---

### Filter Conditions

```php
$table = new Objects();
$table->setName('products');
$table->filterConditions([
    ['field' => 'in_stock', 'operator' => '=', 'value' => true],
    ['field' => 'price', 'operator' => '>', 'value' => 0],
    ['field' => 'discount', 'operator' => '>=', 'value' => 10]
]);
$table->setFields(['id', 'name', 'price', 'discount', 'in_stock']);
$table->lists();
```

---

## Formatting

### Number Formatting

```php
$table = new Objects();
$table->setName('products');
$table->setFields(['id', 'name', 'price', 'quantity', 'total']);
$table->format('price', 2, '.', 'number');
$table->format('quantity', 0, '.', 'number');
$table->lists();
```

**Output:** Price with 2 decimals, quantity as integer.

---

### Currency Formatting

```php
$table = new Objects();
$table->setName('orders');
$table->setFields(['id', 'order_number', 'subtotal', 'tax', 'total']);
$table->formatCurrency(['subtotal', 'tax', 'total'], 2, '$', 'before', ',');
$table->lists();
```

**Output:** $1,234.56

---

### Percentage Formatting

```php
$table = new Objects();
$table->setName('products');
$table->setFields(['id', 'name', 'price', 'discount', 'tax_rate']);
$table->formatPercentage(['discount', 'tax_rate'], 1);
$table->lists();
```

**Output:** 15.5%

---

### Date Formatting

```php
$table = new Objects();
$table->setName('users');
$table->setFields(['id', 'name', 'email', 'created_at', 'updated_at']);
$table->formatDate('created_at', 'F j, Y');
$table->formatDateTime('updated_at', 'M j, Y g:i A');
$table->lists();
```

**Output:** 
- created_at: January 15, 2024
- updated_at: Jan 15, 2024 3:45 PM

---

### Boolean Formatting

```php
$table = new Objects();
$table->setName('users');
$table->setFields(['id', 'name', 'email', 'is_active', 'email_verified']);
$table->formatBoolean('is_active', 'Active', 'Inactive');
$table->formatBoolean('email_verified', 'Verified', 'Not Verified');
$table->lists();
```

---

### Multiple Format Types

```php
$table = new Objects();
$table->setName('orders');
$table->setFields([
    'id',
    'order_number',
    'customer',
    'total',
    'discount',
    'is_paid',
    'created_at'
]);

$table->formatCurrency('total', 2, '$');
$table->formatPercentage('discount', 1);
$table->formatBoolean('is_paid', 'Paid', 'Unpaid');
$table->formatDate('created_at', 'M j, Y');

$table->lists();
```

---

## Actions

### Default Actions

```php
$table = new Objects();
$table->lists('users', ['id', 'name', 'email'], true);
```

**Actions:** View, Edit, Delete (default)

---

### Custom Actions Only

```php
$table = new Objects();
$table->setName('orders');
$table->setFields(['id', 'order_number', 'status', 'total']);
$table->setActions([
    'approve' => [
        'label' => 'Approve',
        'icon' => 'fa-check',
        'url' => '/admin/orders/approve/{id}',
        'class' => 'btn-success'
    ],
    'reject' => [
        'label' => 'Reject',
        'icon' => 'fa-times',
        'url' => '/admin/orders/reject/{id}',
        'class' => 'btn-danger'
    ]
], false); // false = no default actions
$table->lists();
```

---

### Mix Default and Custom Actions

```php
$table = new Objects();
$table->setName('users');
$table->setFields(['id', 'name', 'email', 'status']);
$table->setActions([
    'activate' => [
        'label' => 'Activate',
        'icon' => 'fa-check-circle',
        'url' => '/admin/users/activate/{id}',
        'class' => 'btn-success'
    ]
], ['view', 'edit']); // Include view and edit, exclude delete
$table->lists();
```

---

### Remove Specific Actions

```php
$table = new Objects();
$table->setName('users');
$table->setFields(['id', 'name', 'email']);
$table->removeButtons(['delete']); // Remove delete button
$table->lists();
```

---

### Conditional Actions

```php
$table = new Objects();
$table->setName('orders');
$table->setFields(['id', 'order_number', 'status', 'total']);
$table->setActions([
    'ship' => [
        'label' => 'Ship',
        'icon' => 'fa-truck',
        'url' => '/admin/orders/ship/{id}',
        'condition' => function($row) {
            return $row->status === 'paid';
        }
    ],
    'refund' => [
        'label' => 'Refund',
        'icon' => 'fa-undo',
        'url' => '/admin/orders/refund/{id}',
        'condition' => function($row) {
            return $row->status === 'completed';
        }
    ]
]);
$table->lists();
```

---

## Advanced Features

### Sortable Columns

```php
$table = new Objects();
$table->setName('users');
$table->setFields(['id', 'name', 'email', 'created_at']);
$table->sortable(['name', 'email', 'created_at']);
$table->orderby('created_at', 'desc'); // Default sort
$table->lists();
```

---

### Column Widths

```php
$table = new Objects();
$table->setName('users');
$table->setFields(['id', 'name', 'email', 'status', 'created_at']);
$table->setColumnWidths([
    'id' => 50,
    'name' => 200,
    'email' => 250,
    'status' => 100,
    'created_at' => 150
]);
$table->lists();
```

---

### Column Alignment

```php
$table = new Objects();
$table->setName('products');
$table->setFields(['id', 'name', 'price', 'quantity', 'total']);
$table->setRightColumns(['price', 'quantity', 'total']);
$table->setCenterColumns(['id']);
$table->lists();
```

---

### Column Colors

```php
$table = new Objects();
$table->setName('orders');
$table->setFields(['id', 'order_number', 'status', 'total']);
$table->setBackgroundColor('#d4edda', '#155724', ['status'], true, true);
$table->lists();
```

---

### Hidden Columns

```php
$table = new Objects();
$table->setName('users');
$table->setFields(['id', 'name', 'email', 'password', 'remember_token', 'created_at']);
$table->setHiddenColumns(['password', 'remember_token']);
$table->lists();
```

---

### Fixed Columns

```php
$table = new Objects();
$table->setName('products');
$table->setFields(['id', 'sku', 'name', 'description', 'price', 'quantity', 'category', 'supplier']);
$table->fixedColumns(2, 1); // Fix first 2 columns (id, sku) and last 1 (supplier)
$table->lists();
```

---

### Merged Columns

```php
$table = new Objects();
$table->setName('users');
$table->setFields(['id', 'name', 'email', 'phone', 'address', 'city', 'country']);
$table->mergeColumns('Contact Information', ['email', 'phone', 'address']);
$table->mergeColumns('Location', ['city', 'country']);
$table->lists();
```

---

### Formula Columns

```php
$table = new Objects();
$table->setName('order_items');
$table->setFields(['id', 'product', 'price', 'quantity', 'discount']);

// Calculate total
$table->formula('total', 'Total', ['price', 'quantity'], 'price * quantity');

// Calculate discounted price
$table->formula(
    'final_price',
    'Final Price',
    ['price', 'quantity', 'discount'],
    '(price * quantity) - ((price * quantity) * discount / 100)'
);

$table->lists();
```

---

### Column Grouping

```php
$table = new Objects();
$table->setName('employees');
$table->setFields([
    'id',
    'name',
    'email',
    'phone',
    'department',
    'position',
    'salary',
    'bonus'
]);

$table->groupColumns('personal', 'Personal Information', ['name', 'email', 'phone']);
$table->groupColumns('employment', 'Employment Details', ['department', 'position']);
$table->groupColumns('compensation', 'Compensation', ['salary', 'bonus']);

$table->lists();
```

---

## Security Examples

### Safe User Input Handling

```php
// User input from request
$status = $_GET['status'] ?? 'active';
$search = $_GET['search'] ?? '';

$table = new Objects();
$table->setName('users');
$table->setFields(['id', 'name', 'email', 'status']);

// Safe - automatically parameterized
$table->where('status', '=', $status);

// Safe - search term sanitized
$table->searchable(['name', 'email']);

$table->lists();
```

**Security:** All values are automatically parameterized and escaped.

---

### Column Name Validation

```php
// User-provided sort column
$sortColumn = $_GET['sort'] ?? 'created_at';

$table = new Objects();
$table->setName('users');
$table->setFields(['id', 'name', 'email', 'created_at']);

// Safe - column name validated against schema
try {
    $table->orderby($sortColumn, 'desc');
} catch (\InvalidArgumentException $e) {
    // Invalid column name - use default
    $table->orderby('created_at', 'desc');
}

$table->lists();
```

---

### XSS Prevention

```php
// User-provided labels
$nameLabel = $_GET['name_label'] ?? 'Name';
$emailLabel = $_GET['email_label'] ?? 'Email';

$table = new Objects();
$table->setName('users');
$table->setFields([
    'id' => 'ID',
    'name' => $nameLabel,    // Automatically escaped
    'email' => $emailLabel   // Automatically escaped
]);
$table->lists();
```

**Security:** All labels are automatically HTML-escaped.

---

### Safe Custom HTML

```php
use function Canvastack\Canvastack\Library\Helpers\canvastack_mark_safe_html;

$table = new Objects();
$table->setName('products');
$table->setFields(['id', 'name', 'description']);

// Mark trusted HTML as safe
$safeDescription = canvastack_mark_safe_html('<strong>Featured</strong>');

$table->lists();
```

**Warning:** Only mark HTML as safe if you control the content. Never mark user input as safe.

---

## Complete Example

### E-commerce Orders Table

```php
use App\Models\Order;
use Canvastack\Canvastack\Library\Components\Table\Objects;

$table = new Objects();

// Set model
$table->model(Order::class);

// Configure relationships
$table->relations(Order::class, 'customer', 'name', [], 'Customer');
$table->relations(Order::class, 'product', 'name', [], 'Product');

// Set fields
$table->setFields([
    'id' => 'Order ID',
    'order_number' => 'Order #',
    'customer.name' => 'Customer',
    'product.name' => 'Product',
    'quantity' => 'Qty',
    'price' => 'Unit Price',
    'total' => 'Total',
    'discount' => 'Discount',
    'status' => 'Status',
    'is_paid' => 'Paid',
    'created_at' => 'Order Date'
]);

// Add formula column
$table->formula('final_total', 'Final Total', ['total', 'discount'], 
    'total - (total * discount / 100)');

// Configure formatting
$table->formatCurrency(['price', 'total', 'final_total'], 2, '$');
$table->formatPercentage('discount', 1);
$table->formatBoolean('is_paid', 'Paid', 'Unpaid');
$table->formatDate('created_at', 'M j, Y');

// Configure columns
$table->setColumnWidths([
    'id' => 80,
    'order_number' => 120,
    'quantity' => 80,
    'status' => 100
]);
$table->setRightColumns(['quantity', 'price', 'total', 'discount', 'final_total']);
$table->setCenterColumns(['id', 'status', 'is_paid']);

// Configure search and filters
$table->searchable(['order_number', 'customer.name', 'product.name']);
$table->sortable(['order_number', 'customer.name', 'total', 'created_at']);
$table->orderby('created_at', 'desc');

$table->filterGroups('status', 'select', [
    'pending' => 'Pending',
    'processing' => 'Processing',
    'shipped' => 'Shipped',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled'
]);
$table->filterGroups('is_paid', 'select', [
    '1' => 'Paid',
    '0' => 'Unpaid'
]);

// Configure actions
$table->setActions([
    'view' => [
        'label' => 'View',
        'icon' => 'fa-eye',
        'url' => '/admin/orders/view/{id}'
    ],
    'invoice' => [
        'label' => 'Invoice',
        'icon' => 'fa-file-invoice',
        'url' => '/admin/orders/invoice/{id}'
    ],
    'ship' => [
        'label' => 'Ship',
        'icon' => 'fa-truck',
        'url' => '/admin/orders/ship/{id}',
        'class' => 'btn-success',
        'condition' => function($row) {
            return $row->status === 'processing' && $row->is_paid;
        }
    ],
    'cancel' => [
        'label' => 'Cancel',
        'icon' => 'fa-times',
        'url' => '/admin/orders/cancel/{id}',
        'class' => 'btn-danger',
        'confirm' => true,
        'condition' => function($row) {
            return in_array($row->status, ['pending', 'processing']);
        }
    ]
]);

// Configure table attributes
$table->addAttributes([
    'class' => 'table-striped table-hover'
]);
$table->setWidth(100, '%');

// Enable server-side processing
$table->setServerSide(true);
$table->displayRowsLimitOnLoad(25);

// Render table
$table->lists();
```

---

## See Also

- [Objects API Reference](../api/OBJECTS.md)
- [Builder API Reference](../api/BUILDER.md)
- [Datatables API Reference](../api/DATATABLES.md)
- [Search System](../api/SEARCH.md)
- [Security Guidelines](../features/SECURITY.md)
- [Accessibility Guidelines](ACCESSIBILITY.md)
- [Getting Started Guide](GETTING_STARTED.md)
