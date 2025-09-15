# Relationships

CanvaStack Table provides powerful relationship handling capabilities that allow you to display and work with related data from Eloquent models. This includes one-to-one, one-to-many, many-to-many, and nested relationships.

## Table of Contents

- [Basic Relationships](#basic-relationships)
- [Relationship Types](#relationship-types)
- [Nested Relationships](#nested-relationships)
- [Relationship Filtering](#relationship-filtering)
- [Relationship Sorting](#relationship-sorting)
- [Relationship Aggregation](#relationship-aggregation)
- [Performance Optimization](#performance-optimization)
- [Advanced Relationship Features](#advanced-relationship-features)

## Basic Relationships

### Setting Up Basic Relationships

Configure relationships to display related data:

```php
public function index()
{
    $this->setPage();

    // Set up relationships
    $this->table->relations($this->model, 'department', 'name')
                ->relations($this->model, 'role', 'title');

    $this->table->lists('users', [
        'name:Employee Name',
        'email:Email',
        'department.name:Department',
        'role.title:Job Title',
        'created_at:Hire Date'
    ]);

    return $this->render();
}
```

### Multiple Field Relationships

Display multiple fields from related models:

```php
public function index()
{
    $this->setPage();

    // Set up relationships with multiple fields
    $this->table->relations($this->model, 'department', 'name')
                ->relations($this->model, 'department', 'code')
                ->relations($this->model, 'manager', 'name')
                ->relations($this->model, 'manager', 'email');

    $this->table->lists('users', [
        'name:Employee Name',
        'department.name:Department',
        'department.code:Dept Code',
        'manager.name:Manager',
        'manager.email:Manager Email',
        'created_at:Hire Date'
    ]);

    return $this->render();
}
```

## Relationship Types

### One-to-One Relationships

Handle one-to-one relationships:

```php
// User Model
class User extends Model
{
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
    
    public function address()
    {
        return $this->hasOne(Address::class);
    }
}

// Controller
public function index()
{
    $this->setPage();

    // Set up one-to-one relationships
    $this->table->relations($this->model, 'profile', 'bio')
                ->relations($this->model, 'profile', 'phone')
                ->relations($this->model, 'address', 'street')
                ->relations($this->model, 'address', 'city');

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email',
        'profile.bio:Biography',
        'profile.phone:Phone',
        'address.street:Street',
        'address.city:City'
    ]);

    return $this->render();
}
```

### One-to-Many Relationships

Display data from one-to-many relationships:

```php
// User Model
class User extends Model
{
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

// Controller
public function index()
{
    $this->setPage();

    // Use relationship aggregation for one-to-many
    $this->table->setRelationshipAggregation([
        'orders_count' => [
            'relationship' => 'orders',
            'function' => 'count'
        ],
        'total_order_value' => [
            'relationship' => 'orders',
            'function' => 'sum',
            'column' => 'total'
        ],
        'latest_order_date' => [
            'relationship' => 'orders',
            'function' => 'max',
            'column' => 'created_at'
        ]
    ]);

    $this->table->lists('users', [
        'name:Customer Name',
        'email:Email',
        'orders_count:Total Orders',
        'total_order_value:Order Value',
        'latest_order_date:Last Order'
    ]);

    return $this->render();
}
```

### Many-to-Many Relationships

Handle many-to-many relationships:

```php
// User Model
class User extends Model
{
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
    
    public function skills()
    {
        return $this->belongsToMany(Skill::class)->withPivot('level', 'years_experience');
    }
}

// Controller
public function index()
{
    $this->setPage();

    // Set up many-to-many relationships
    $this->table->setManyToManyDisplay([
        'roles' => [
            'display_field' => 'name',
            'separator' => ', ',
            'max_display' => 3,
            'show_count' => true,
            'link_template' => '<span class="badge badge-primary">{value}</span>'
        ],
        'skills' => [
            'display_field' => 'name',
            'separator' => ', ',
            'include_pivot' => ['level'],
            'pivot_template' => '{name} ({level})'
        ]
    ]);

    $this->table->lists('users', [
        'name:Employee Name',
        'email:Email',
        'roles:Roles',
        'skills:Skills'
    ]);

    return $this->render();
}
```

### Belongs-to Relationships

Handle belongs-to relationships:

```php
// Order Model
class Order extends Model
{
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

// Controller
public function index()
{
    $this->setPage();

    // Set up belongs-to relationships
    $this->table->relations($this->model, 'customer', 'name')
                ->relations($this->model, 'customer', 'email')
                ->relations($this->model, 'product', 'name')
                ->relations($this->model, 'product', 'sku');

    $this->table->lists('orders', [
        'order_number:Order #',
        'customer.name:Customer',
        'customer.email:Email',
        'product.name:Product',
        'product.sku:SKU',
        'total:Total',
        'created_at:Order Date'
    ]);

    return $this->render();
}
```

## Nested Relationships

### Deep Nested Relationships

Access deeply nested relationships:

```php
// Models
class User extends Model
{
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
}

class Profile extends Model
{
    public function address()
    {
        return $this->hasOne(Address::class);
    }
}

class Address extends Model
{
    public function city()
    {
        return $this->belongsTo(City::class);
    }
}

class City extends Model
{
    public function state()
    {
        return $this->belongsTo(State::class);
    }
}

// Controller
public function index()
{
    $this->setPage();

    // Set up nested relationships
    $this->table->relations($this->model, 'profile.address.city', 'name')
                ->relations($this->model, 'profile.address.city.state', 'name');

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email',
        'profile.address.city.name:City',
        'profile.address.city.state.name:State'
    ]);

    return $this->render();
}
```

### Complex Nested Relationships

Handle complex nested relationship scenarios:

```php
public function index()
{
    $this->setPage();

    // Complex nested relationships
    $this->table->relations($this->model, 'orders.items.product', 'name')
                ->relations($this->model, 'department.manager', 'name')
                ->relations($this->model, 'department.company.headquarters', 'city');

    // Custom nested relationship display
    $this->table->setNestedRelationshipDisplay([
        'department_hierarchy' => function($row) {
            return $row->department->company->name . ' > ' . 
                   $row->department->name . ' > ' . 
                   $row->position;
        },
        'order_summary' => function($row) {
            $orders = $row->orders;
            $totalOrders = $orders->count();
            $totalValue = $orders->sum('total');
            return "{$totalOrders} orders ($" . number_format($totalValue, 2) . ")";
        }
    ]);

    $this->table->lists('users', [
        'name:Employee Name',
        'department_hierarchy:Department Hierarchy',
        'order_summary:Order Summary'
    ]);

    return $this->render();
}
```

## Relationship Filtering

### Filter by Related Data

Enable filtering on relationship fields:

```php
public function index()
{
    $this->setPage();

    // Set up relationships
    $this->table->relations($this->model, 'department', 'name')
                ->relations($this->model, 'role', 'title')
                ->relations($this->model, 'manager', 'name');

    // Enable filtering on relationship fields
    $this->table->filterGroups('department.name', 'selectbox', true)
                ->filterGroups('role.title', 'selectbox', true)
                ->filterGroups('manager.name', 'selectbox', true);

    $this->table->lists('users', [
        'name:Employee Name',
        'department.name:Department',
        'role.title:Role',
        'manager.name:Manager'
    ]);

    return $this->render();
}
```

### Advanced Relationship Filtering

Implement advanced filtering on relationships:

```php
$this->table->setAdvancedRelationshipFilters([
    'has_orders' => [
        'type' => 'boolean',
        'label' => 'Has Orders',
        'query' => function($query, $value) {
            if ($value) {
                return $query->has('orders');
            } else {
                return $query->doesntHave('orders');
            }
        }
    ],
    'order_value_range' => [
        'type' => 'numberrange',
        'label' => 'Total Order Value',
        'query' => function($query, $min, $max) {
            return $query->whereHas('orders', function($q) use ($min, $max) {
                $q->havingRaw('SUM(total) BETWEEN ? AND ?', [$min, $max]);
            });
        }
    ],
    'department_type' => [
        'type' => 'selectbox',
        'label' => 'Department Type',
        'options' => ['technical', 'business', 'support'],
        'query' => function($query, $value) {
            return $query->whereHas('department', function($q) use ($value) {
                $q->where('type', $value);
            });
        }
    ]
]);
```

## Relationship Sorting

### Sort by Related Fields

Enable sorting on relationship fields:

```php
public function index()
{
    $this->setPage();

    $this->table->relations($this->model, 'department', 'name')
                ->relations($this->model, 'role', 'title');

    $this->table->sortable();

    // Default sort by department name, then employee name
    $this->table->orderby([
        ['department.name', 'ASC'],
        ['name', 'ASC']
    ]);

    $this->table->lists('users', [
        'name:Employee Name',
        'department.name:Department',
        'role.title:Role',
        'created_at:Hire Date'
    ]);

    return $this->render();
}
```

### Custom Relationship Sorting

Implement custom sorting logic for relationships:

```php
$this->table->setCustomRelationshipSort([
    'department_priority' => function($query, $direction) {
        return $query->join('departments', 'users.department_id', '=', 'departments.id')
                    ->orderBy('departments.priority', $direction)
                    ->orderBy('departments.name', 'ASC');
    },
    'manager_hierarchy' => function($query, $direction) {
        return $query->leftJoin('users as managers', 'users.manager_id', '=', 'managers.id')
                    ->orderBy('managers.name', $direction);
    },
    'total_orders' => function($query, $direction) {
        return $query->withCount('orders')
                    ->orderBy('orders_count', $direction);
    }
]);
```

## Relationship Aggregation

### Count Relationships

Display relationship counts:

```php
public function index()
{
    $this->setPage();

    // Set up relationship counts
    $this->table->setRelationshipCounts([
        'orders_count' => 'orders',
        'posts_count' => 'posts',
        'comments_count' => 'comments',
        'active_orders_count' => function($query) {
            return $query->where('status', 'active');
        }
    ]);

    $this->table->lists('users', [
        'name:Customer Name',
        'email:Email',
        'orders_count:Total Orders',
        'posts_count:Posts',
        'comments_count:Comments',
        'active_orders_count:Active Orders'
    ]);

    return $this->render();
}
```

### Aggregate Relationship Data

Perform aggregations on relationship data:

```php
$this->table->setRelationshipAggregations([
    'total_order_value' => [
        'relationship' => 'orders',
        'function' => 'sum',
        'column' => 'total',
        'format' => function($value) {
            return '$' . number_format($value, 2);
        }
    ],
    'average_order_value' => [
        'relationship' => 'orders',
        'function' => 'avg',
        'column' => 'total',
        'format' => function($value) {
            return '$' . number_format($value, 2);
        }
    ],
    'latest_order_date' => [
        'relationship' => 'orders',
        'function' => 'max',
        'column' => 'created_at',
        'format' => function($value) {
            return $value ? $value->format('M j, Y') : 'Never';
        }
    ],
    'order_frequency' => [
        'relationship' => 'orders',
        'custom' => function($user) {
            $orders = $user->orders;
            if ($orders->count() < 2) return 'N/A';
            
            $firstOrder = $orders->min('created_at');
            $lastOrder = $orders->max('created_at');
            $daysBetween = $firstOrder->diffInDays($lastOrder);
            
            return $daysBetween > 0 ? 
                round($orders->count() / ($daysBetween / 30), 1) . ' orders/month' : 
                'N/A';
        }
    ]
]);
```

## Performance Optimization

### Eager Loading

Optimize relationship queries with eager loading:

```php
public function index()
{
    $this->setPage();

    // Configure eager loading
    $this->table->setEagerLoading([
        'department',
        'role',
        'manager',
        'profile.address.city.state'
    ]);

    // Or use conditional eager loading
    $this->table->setConditionalEagerLoading([
        'orders' => function($query) {
            // Only load recent orders
            return $query->where('created_at', '>=', now()->subMonths(6));
        },
        'posts' => function($query) {
            // Only load published posts
            return $query->where('status', 'published');
        }
    ]);

    $this->table->relations($this->model, 'department', 'name')
                ->relations($this->model, 'role', 'title');

    $this->table->lists('users', [
        'name:Employee Name',
        'department.name:Department',
        'role.title:Role'
    ]);

    return $this->render();
}
```

### Relationship Caching

Cache relationship data for better performance:

```php
$this->table->setRelationshipCaching([
    'enabled' => true,
    'ttl' => 3600, // 1 hour
    'cache_key_prefix' => 'table_relationships_',
    'cache_relationships' => [
        'department.name',
        'role.title',
        'manager.name'
    ],
    'invalidate_on_update' => true
]);
```

### Optimized Relationship Queries

Use optimized queries for relationship data:

```php
$this->table->setOptimizedRelationshipQueries([
    'department_info' => function() {
        return DB::table('departments')
                 ->select('id', 'name', 'code')
                 ->where('active', true)
                 ->get()
                 ->keyBy('id');
    },
    'role_hierarchy' => function() {
        return DB::table('roles')
                 ->select('id', 'title', 'level', 'parent_id')
                 ->orderBy('level')
                 ->get()
                 ->keyBy('id');
    }
]);
```

## Advanced Relationship Features

### Polymorphic Relationships

Handle polymorphic relationships:

```php
// Models
class Comment extends Model
{
    public function commentable()
    {
        return $this->morphTo();
    }
}

class Post extends Model
{
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

// Controller
public function index()
{
    $this->setPage();

    $this->table->setPolymorphicRelationships([
        'commentable' => [
            'display_field' => function($commentable) {
                if ($commentable instanceof Post) {
                    return 'Post: ' . $commentable->title;
                } elseif ($commentable instanceof Product) {
                    return 'Product: ' . $commentable->name;
                }
                return 'Unknown';
            }
        ]
    ]);

    $this->table->lists('comments', [
        'content:Comment',
        'commentable:Related To',
        'created_at:Posted'
    ]);

    return $this->render();
}
```

### Conditional Relationships

Display relationships conditionally:

```php
$this->table->setConditionalRelationships([
    'manager' => function($row) {
        // Only show manager for non-manager roles
        return !$row->hasRole('manager');
    },
    'subordinates' => function($row) {
        // Only show subordinates for managers
        return $row->hasRole('manager');
    },
    'department_budget' => function($row) {
        // Only show budget for department heads
        return $row->position === 'department_head';
    }
]);
```

### Relationship Actions

Add actions specific to relationships:

```php
$this->table->setRelationshipActions([
    'view_orders' => [
        'label' => 'View Orders',
        'url' => '/users/{id}/orders',
        'condition' => function($row) {
            return $row->orders_count > 0;
        },
        'class' => 'btn btn-info btn-sm'
    ],
    'manage_department' => [
        'label' => 'Manage Department',
        'url' => '/departments/{department.id}/manage',
        'condition' => function($row) {
            return $row->hasRole('department_manager');
        },
        'class' => 'btn btn-primary btn-sm'
    ]
]);
```

---

## Related Documentation

- [Basic Usage](../basic-usage.md) - Basic relationship setup
- [Data Sources](../data-sources.md) - Working with Eloquent models
- [Performance Optimization](../advanced/performance.md) - Optimizing relationship queries
- [API Reference](../api/objects.md) - Complete relationship method documentation