# Basic Examples

This section provides practical, real-world examples of using CanvaStack Table for common scenarios. Each example includes complete code and explanations.

## Table of Contents

- [Simple User List](#simple-user-list)
- [Product Catalog](#product-catalog)
- [Order Management](#order-management)
- [Employee Directory](#employee-directory)
- [Blog Posts](#blog-posts)
- [Customer Management](#customer-management)
- [Inventory System](#inventory-system)
- [Event Management](#event-management)

## Simple User List

A basic user management table with search, sorting, and actions.

### Controller

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
        
        $this->setValidations([
            'name' => 'required|min:2',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);
    }

    public function index()
    {
        $this->setPage();

        // Basic table configuration
        $this->table->searchable()      // Enable global search
                    ->sortable()        // Enable column sorting
                    ->clickable();      // Make rows clickable

        // Simple column list with custom labels
        $this->table->lists('users', [
            'name:Full Name',
            'email:Email Address',
            'created_at:Join Date',
            'active:Status'
        ], true); // true = enable default actions

        return $this->render();
    }

    public function create()
    {
        $this->setPage();

        $this->form->text('name', null, ['required']);
        $this->form->email('email', null, ['required']);
        $this->form->password('password', ['required']);
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
        $this->form->selectbox('active', ['1' => 'Active', '0' => 'Inactive'], $this->model_data->active);

        $this->form->close('Update User');

        return $this->render();
    }
}
```

### Model

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'name', 'email', 'password', 'active'
    ];

    protected $hidden = [
        'password', 'remember_token'
    ];

    protected $casts = [
        'active' => 'boolean',
        'email_verified_at' => 'datetime'
    ];
}
```

### View

```blade
{{-- resources/views/users/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Users')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Users</h3>
                    <a href="{{ route('users.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add User
                    </a>
                </div>
                <div class="card-body">
                    {!! $table !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

## Product Catalog

A product listing with categories, images, and filtering.

### Controller

```php
<?php

namespace App\Http\Controllers;

use Canvastack\Canvastack\Controllers\Core\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function __construct()
    {
        parent::__construct(Product::class, 'products');
    }

    public function index()
    {
        $this->setPage();

        // Use server-side processing for better performance
        $this->table->method('POST')
                    ->searchable()
                    ->sortable()
                    ->clickable();

        // Set up relationship with category
        $this->table->relations($this->model, 'category', 'name');

        // Add filters
        $this->table->filterGroups('category.name', 'selectbox', true)
                    ->filterGroups('active', 'selectbox', true)
                    ->filterGroups('price', 'numberrange', true);

        // Configure image display
        $this->table->setFieldAsImage(['image']);

        // Default ordering by newest first
        $this->table->orderby('created_at', 'DESC');

        $this->table->lists('products', [
            'image:Product Image',
            'name:Product Name',
            'category.name:Category',
            'price:Price ($)',
            'stock:Stock',
            'active:Status',
            'created_at:Added'
        ], true);

        return $this->render();
    }
}
```

### Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'description', 'price', 'stock', 'image', 'category_id', 'active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'active' => 'boolean'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Accessor for formatted price
    public function getPriceFormattedAttribute()
    {
        return '$' . number_format($this->price, 2);
    }
}
```

## Order Management

Order listing with customer information and status tracking.

### Controller

```php
<?php

namespace App\Http\Controllers;

use Canvastack\Canvastack\Controllers\Core\Controller;
use App\Models\Order;

class OrderController extends Controller
{
    public function __construct()
    {
        parent::__construct(Order::class, 'orders');
    }

    public function index()
    {
        $this->setPage();

        $this->table->method('POST')
                    ->searchable()
                    ->sortable()
                    ->clickable();

        // Set up relationships
        $this->table->relations($this->model, 'customer', 'name')
                    ->relations($this->model, 'customer', 'email');

        // Add filters
        $this->table->filterGroups('status', 'selectbox', true)
                    ->filterGroups('created_at', 'daterange', true)
                    ->filterGroups('total', 'numberrange', true);

        // Custom actions for orders
        $this->table->setActions([
            'view_details' => [
                'label' => 'Details',
                'url' => '/orders/{id}/details',
                'class' => 'btn btn-info btn-sm',
                'icon' => 'fas fa-eye',
                'modal' => [
                    'enabled' => true,
                    'size' => 'modal-lg',
                    'title' => 'Order Details'
                ]
            ],
            'print_invoice' => [
                'label' => 'Invoice',
                'url' => '/orders/{id}/invoice',
                'class' => 'btn btn-secondary btn-sm',
                'icon' => 'fas fa-print',
                'target' => '_blank'
            ],
            'update_status' => [
                'label' => 'Update Status',
                'url' => '/orders/{id}/status',
                'class' => 'btn btn-warning btn-sm',
                'icon' => 'fas fa-edit',
                'modal' => [
                    'enabled' => true,
                    'size' => 'modal-md',
                    'title' => 'Update Order Status'
                ]
            ]
        ]);

        $this->table->orderby('created_at', 'DESC');

        $this->table->lists('orders', [
            'order_number:Order #',
            'customer.name:Customer',
            'customer.email:Email',
            'total:Total ($)',
            'status:Status',
            'created_at:Order Date'
        ], [
            'view' => true,
            'edit' => true,
            'view_details' => true,
            'print_invoice' => true,
            'update_status' => true
        ]);

        return $this->render();
    }
}
```

### Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'customer_id', 'total', 'status', 'notes'
    ];

    protected $casts = [
        'total' => 'decimal:2'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Generate order number
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($order) {
            $order->order_number = 'ORD-' . date('Y') . '-' . str_pad(static::count() + 1, 6, '0', STR_PAD_LEFT);
        });
    }
}
```

## Employee Directory

Employee listing with department hierarchy and contact information.

### Controller

```php
<?php

namespace App\Http\Controllers;

use Canvastack\Canvastack\Controllers\Core\Controller;
use App\Models\Employee;

class EmployeeController extends Controller
{
    public function __construct()
    {
        parent::__construct(Employee::class, 'employees');
    }

    public function index()
    {
        $this->setPage();

        $this->table->method('POST')
                    ->searchable()
                    ->sortable()
                    ->clickable();

        // Set up relationships
        $this->table->relations($this->model, 'department', 'name')
                    ->relations($this->model, 'position', 'title')
                    ->relations($this->model, 'manager', 'name');

        // Add filters with dependencies
        $this->table->filterGroups('department.name', 'selectbox', true)
                    ->filterGroups('position.title', 'selectbox', true)
                    ->filterGroups('active', 'selectbox', true)
                    ->filterGroups('hire_date', 'daterange', true);

        // Configure image display for photos
        $this->table->setFieldAsImage(['photo']);

        // Fixed columns for better UX
        $this->table->fixedColumns(2, 1); // Fix name and photo, and actions

        // Custom actions
        $this->table->setActions([
            'view_profile' => [
                'label' => 'Profile',
                'url' => '/employees/{id}/profile',
                'class' => 'btn btn-info btn-sm',
                'icon' => 'fas fa-user'
            ],
            'send_email' => [
                'label' => 'Email',
                'url' => 'mailto:{email}',
                'class' => 'btn btn-primary btn-sm',
                'icon' => 'fas fa-envelope'
            ],
            'org_chart' => [
                'label' => 'Org Chart',
                'url' => '/employees/{id}/org-chart',
                'class' => 'btn btn-secondary btn-sm',
                'icon' => 'fas fa-sitemap'
            ]
        ]);

        $this->table->lists('employees', [
            'photo:Photo',
            'name:Full Name',
            'employee_id:ID',
            'department.name:Department',
            'position.title:Position',
            'manager.name:Manager',
            'email:Email',
            'phone:Phone',
            'hire_date:Hire Date',
            'active:Status'
        ], [
            'view' => true,
            'edit' => true,
            'view_profile' => true,
            'send_email' => true,
            'org_chart' => true
        ]);

        return $this->render();
    }
}
```

## Blog Posts

Blog post management with categories, tags, and publishing status.

### Controller

```php
<?php

namespace App\Http\Controllers;

use Canvastack\Canvastack\Controllers\Core\Controller;
use App\Models\Post;

class PostController extends Controller
{
    public function __construct()
    {
        parent::__construct(Post::class, 'posts');
    }

    public function index()
    {
        $this->setPage();

        $this->table->method('POST')
                    ->searchable()
                    ->sortable()
                    ->clickable();

        // Relationships
        $this->table->relations($this->model, 'category', 'name')
                    ->relations($this->model, 'author', 'name');

        // Filters
        $this->table->filterGroups('category.name', 'selectbox', true)
                    ->filterGroups('status', 'selectbox', true)
                    ->filterGroups('author.name', 'selectbox', true)
                    ->filterGroups('published_at', 'daterange', true);

        // Configure featured image
        $this->table->setFieldAsImage(['featured_image']);

        // Custom actions
        $this->table->setActions([
            'preview' => [
                'label' => 'Preview',
                'url' => '/posts/{slug}/preview',
                'class' => 'btn btn-info btn-sm',
                'icon' => 'fas fa-eye',
                'target' => '_blank'
            ],
            'publish' => [
                'label' => function($row) {
                    return $row->status === 'published' ? 'Unpublish' : 'Publish';
                },
                'url' => '/posts/{id}/toggle-publish',
                'class' => function($row) {
                    return $row->status === 'published' 
                        ? 'btn btn-warning btn-sm' 
                        : 'btn btn-success btn-sm';
                },
                'icon' => function($row) {
                    return $row->status === 'published' ? 'fas fa-eye-slash' : 'fas fa-globe';
                },
                'method' => 'POST',
                'ajax' => ['enabled' => true, 'reload_table' => true]
            ],
            'duplicate' => [
                'label' => 'Duplicate',
                'url' => '/posts/{id}/duplicate',
                'class' => 'btn btn-secondary btn-sm',
                'icon' => 'fas fa-copy',
                'method' => 'POST'
            ]
        ]);

        $this->table->orderby('created_at', 'DESC');

        $this->table->lists('posts', [
            'featured_image:Image',
            'title:Title',
            'category.name:Category',
            'author.name:Author',
            'status:Status',
            'published_at:Published',
            'views:Views',
            'created_at:Created'
        ], [
            'view' => true,
            'edit' => true,
            'preview' => true,
            'publish' => true,
            'duplicate' => true,
            'delete' => true
        ]);

        return $this->render();
    }
}
```

## Customer Management

Customer relationship management with contact history and segmentation.

### Controller

```php
<?php

namespace App\Http\Controllers;

use Canvastack\Canvastack\Controllers\Core\Controller;
use App\Models\Customer;

class CustomerController extends Controller
{
    public function __construct()
    {
        parent::__construct(Customer::class, 'customers');
    }

    public function index()
    {
        $this->setPage();

        $this->table->method('POST')
                    ->searchable()
                    ->sortable()
                    ->clickable();

        // Relationships
        $this->table->relations($this->model, 'segment', 'name')
                    ->relations($this->model, 'assignedTo', 'name');

        // Filters
        $this->table->filterGroups('segment.name', 'selectbox', true)
                    ->filterGroups('status', 'selectbox', true)
                    ->filterGroups('assignedTo.name', 'selectbox', true)
                    ->filterGroups('created_at', 'daterange', true);

        // Custom filters
        $this->table->setCustomFilters([
            'lifetime_value' => [
                'type' => 'selectbox',
                'label' => 'Lifetime Value',
                'options' => [
                    'high' => 'High Value ($10,000+)',
                    'medium' => 'Medium Value ($1,000-$9,999)',
                    'low' => 'Low Value (<$1,000)'
                ],
                'query' => function($value) {
                    switch($value) {
                        case 'high':
                            return 'lifetime_value >= 10000';
                        case 'medium':
                            return 'lifetime_value BETWEEN 1000 AND 9999';
                        case 'low':
                            return 'lifetime_value < 1000';
                    }
                }
            ]
        ]);

        // Custom actions
        $this->table->setActions([
            'view_profile' => [
                'label' => 'Profile',
                'url' => '/customers/{id}/profile',
                'class' => 'btn btn-info btn-sm',
                'icon' => 'fas fa-user'
            ],
            'contact_history' => [
                'label' => 'History',
                'url' => '/customers/{id}/history',
                'class' => 'btn btn-secondary btn-sm',
                'icon' => 'fas fa-history',
                'modal' => [
                    'enabled' => true,
                    'size' => 'modal-xl',
                    'title' => 'Contact History'
                ]
            ],
            'send_email' => [
                'label' => 'Email',
                'url' => '/customers/{id}/send-email',
                'class' => 'btn btn-primary btn-sm',
                'icon' => 'fas fa-envelope',
                'modal' => [
                    'enabled' => true,
                    'size' => 'modal-lg',
                    'title' => 'Send Email'
                ]
            ],
            'create_order' => [
                'label' => 'New Order',
                'url' => '/orders/create?customer_id={id}',
                'class' => 'btn btn-success btn-sm',
                'icon' => 'fas fa-shopping-cart'
            ]
        ]);

        // Merge contact columns
        $this->table->mergeColumns('Contact Info', ['email', 'phone']);

        $this->table->lists('customers', [
            'name:Customer Name',
            'company:Company',
            'email:Email',
            'phone:Phone',
            'segment.name:Segment',
            'assignedTo.name:Assigned To',
            'lifetime_value:LTV ($)',
            'last_contact:Last Contact',
            'status:Status'
        ], [
            'view' => true,
            'edit' => true,
            'view_profile' => true,
            'contact_history' => true,
            'send_email' => true,
            'create_order' => true
        ]);

        return $this->render();
    }
}
```

## Inventory System

Inventory management with stock levels, suppliers, and reorder alerts.

### Controller

```php
<?php

namespace App\Http\Controllers;

use Canvastack\Canvastack\Controllers\Core\Controller;
use App\Models\InventoryItem;

class InventoryController extends Controller
{
    public function __construct()
    {
        parent::__construct(InventoryItem::class, 'inventory');
    }

    public function index()
    {
        $this->setPage();

        $this->table->method('POST')
                    ->searchable()
                    ->sortable()
                    ->clickable();

        // Relationships
        $this->table->relations($this->model, 'category', 'name')
                    ->relations($this->model, 'supplier', 'name')
                    ->relations($this->model, 'location', 'name');

        // Filters
        $this->table->filterGroups('category.name', 'selectbox', true)
                    ->filterGroups('supplier.name', 'selectbox', true)
                    ->filterGroups('location.name', 'selectbox', true);

        // Custom filters for stock levels
        $this->table->setCustomFilters([
            'stock_status' => [
                'type' => 'selectbox',
                'label' => 'Stock Status',
                'options' => [
                    'in_stock' => 'In Stock',
                    'low_stock' => 'Low Stock',
                    'out_of_stock' => 'Out of Stock',
                    'reorder_needed' => 'Reorder Needed'
                ],
                'query' => function($value) {
                    switch($value) {
                        case 'in_stock':
                            return 'current_stock > reorder_level';
                        case 'low_stock':
                            return 'current_stock <= reorder_level AND current_stock > 0';
                        case 'out_of_stock':
                            return 'current_stock = 0';
                        case 'reorder_needed':
                            return 'current_stock <= reorder_level';
                    }
                }
            ]
        ]);

        // Custom actions
        $this->table->setActions([
            'adjust_stock' => [
                'label' => 'Adjust Stock',
                'url' => '/inventory/{id}/adjust',
                'class' => 'btn btn-warning btn-sm',
                'icon' => 'fas fa-edit',
                'modal' => [
                    'enabled' => true,
                    'size' => 'modal-md',
                    'title' => 'Stock Adjustment'
                ]
            ],
            'reorder' => [
                'label' => 'Reorder',
                'url' => '/inventory/{id}/reorder',
                'class' => 'btn btn-success btn-sm',
                'icon' => 'fas fa-shopping-cart',
                'condition' => function($row) {
                    return $row->current_stock <= $row->reorder_level;
                }
            ],
            'stock_history' => [
                'label' => 'History',
                'url' => '/inventory/{id}/history',
                'class' => 'btn btn-info btn-sm',
                'icon' => 'fas fa-history',
                'modal' => [
                    'enabled' => true,
                    'size' => 'modal-lg',
                    'title' => 'Stock Movement History'
                ]
            ]
        ]);

        // Configure image display
        $this->table->setFieldAsImage(['image']);

        $this->table->lists('inventory', [
            'image:Image',
            'sku:SKU',
            'name:Item Name',
            'category.name:Category',
            'supplier.name:Supplier',
            'location.name:Location',
            'current_stock:Current Stock',
            'reorder_level:Reorder Level',
            'unit_cost:Unit Cost ($)',
            'last_updated:Last Updated'
        ], [
            'view' => true,
            'edit' => true,
            'adjust_stock' => true,
            'reorder' => true,
            'stock_history' => true
        ]);

        return $this->render();
    }
}
```

## Event Management

Event management system with attendees, venues, and scheduling.

### Controller

```php
<?php

namespace App\Http\Controllers;

use Canvastack\Canvastack\Controllers\Core\Controller;
use App\Models\Event;

class EventController extends Controller
{
    public function __construct()
    {
        parent::__construct(Event::class, 'events');
    }

    public function index()
    {
        $this->setPage();

        $this->table->method('POST')
                    ->searchable()
                    ->sortable()
                    ->clickable();

        // Relationships
        $this->table->relations($this->model, 'venue', 'name')
                    ->relations($this->model, 'organizer', 'name')
                    ->relations($this->model, 'category', 'name');

        // Filters
        $this->table->filterGroups('category.name', 'selectbox', true)
                    ->filterGroups('venue.name', 'selectbox', true)
                    ->filterGroups('status', 'selectbox', true)
                    ->filterGroups('start_date', 'daterange', true);

        // Custom filters
        $this->table->setCustomFilters([
            'event_period' => [
                'type' => 'selectbox',
                'label' => 'Event Period',
                'options' => [
                    'upcoming' => 'Upcoming Events',
                    'ongoing' => 'Ongoing Events',
                    'past' => 'Past Events',
                    'this_week' => 'This Week',
                    'this_month' => 'This Month'
                ],
                'query' => function($value) {
                    $now = now();
                    switch($value) {
                        case 'upcoming':
                            return "start_date > '{$now}'";
                        case 'ongoing':
                            return "start_date <= '{$now}' AND end_date >= '{$now}'";
                        case 'past':
                            return "end_date < '{$now}'";
                        case 'this_week':
                            return "start_date BETWEEN '{$now->startOfWeek()}' AND '{$now->endOfWeek()}'";
                        case 'this_month':
                            return "start_date BETWEEN '{$now->startOfMonth()}' AND '{$now->endOfMonth()}'";
                    }
                }
            ]
        ]);

        // Custom actions
        $this->table->setActions([
            'view_details' => [
                'label' => 'Details',
                'url' => '/events/{id}/details',
                'class' => 'btn btn-info btn-sm',
                'icon' => 'fas fa-info-circle'
            ],
            'manage_attendees' => [
                'label' => 'Attendees',
                'url' => '/events/{id}/attendees',
                'class' => 'btn btn-primary btn-sm',
                'icon' => 'fas fa-users'
            ],
            'check_in' => [
                'label' => 'Check-in',
                'url' => '/events/{id}/checkin',
                'class' => 'btn btn-success btn-sm',
                'icon' => 'fas fa-check',
                'condition' => function($row) {
                    return $row->start_date <= now() && $row->end_date >= now();
                }
            ],
            'duplicate' => [
                'label' => 'Duplicate',
                'url' => '/events/{id}/duplicate',
                'class' => 'btn btn-secondary btn-sm',
                'icon' => 'fas fa-copy',
                'method' => 'POST'
            ]
        ]);

        // Configure image display
        $this->table->setFieldAsImage(['banner_image']);

        $this->table->orderby('start_date', 'ASC');

        $this->table->lists('events', [
            'banner_image:Banner',
            'title:Event Title',
            'category.name:Category',
            'venue.name:Venue',
            'organizer.name:Organizer',
            'start_date:Start Date',
            'end_date:End Date',
            'max_attendees:Capacity',
            'registered_count:Registered',
            'status:Status'
        ], [
            'view' => true,
            'edit' => true,
            'view_details' => true,
            'manage_attendees' => true,
            'check_in' => true,
            'duplicate' => true
        ]);

        return $this->render();
    }
}
```

## Common Patterns Summary

### Pattern 1: Basic CRUD
```php
$this->table->searchable()
            ->sortable()
            ->clickable()
            ->lists('table_name', ['field1', 'field2'], true);
```

### Pattern 2: With Relationships
```php
$this->table->relations($this->model, 'relation', 'display_field')
            ->lists('table_name', ['field1', 'relation.display_field'], true);
```

### Pattern 3: With Filtering
```php
$this->table->filterGroups('field1', 'selectbox', true)
            ->filterGroups('field2', 'date', true)
            ->lists('table_name', ['field1', 'field2'], true);
```

### Pattern 4: With Custom Actions
```php
$this->table->setActions([...])
            ->lists('table_name', ['field1', 'field2'], ['custom_action' => true]);
```

### Pattern 5: Server-Side Processing
```php
$this->table->method('POST')
            ->searchable()
            ->sortable()
            ->lists('table_name', ['field1', 'field2'], true);
```

---

## Related Documentation

- [Quick Start Guide](../quick-start.md) - Getting started quickly
- [Basic Usage](../basic-usage.md) - Fundamental patterns
- [API Reference](../api/objects.md) - Complete method documentation
- [Advanced Examples](real-world.md) - Complex real-world scenarios