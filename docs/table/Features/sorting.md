# Sorting & Ordering

CanvaStack Table provides comprehensive sorting capabilities including column sorting, default ordering, custom sort logic, and multi-column sorting. This guide covers all aspects of table sorting functionality.

## Table of Contents

- [Basic Sorting](#basic-sorting)
- [Default Ordering](#default-ordering)
- [Custom Sort Logic](#custom-sort-logic)
- [Multi-Column Sorting](#multi-column-sorting)
- [Relationship Sorting](#relationship-sorting)
- [Advanced Sorting](#advanced-sorting)
- [Sort Performance](#sort-performance)
- [Sort UI Customization](#sort-ui-customization)

## Basic Sorting

### Enable Column Sorting

Enable sorting for all sortable columns:

```php
public function index()
{
    $this->setPage();

    // Enable sorting for all columns
    $this->table->sortable();

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email Address',
        'created_at:Registration Date'
    ]);

    return $this->render();
}
```

### Selective Column Sorting

Enable sorting for specific columns only:

```php
public function index()
{
    $this->setPage();

    $this->table->sortable();

    // Configure which columns are sortable
    $this->table->setSortableColumns([
        'name' => true,
        'email' => true,
        'created_at' => true,
        'updated_at' => false // Not sortable
    ]);

    $this->table->lists('users', [
        'name',
        'email',
        'created_at',
        'updated_at'
    ]);

    return $this->render();
}
```

## Default Ordering

### Single Column Default Order

Set default sorting for the table:

```php
public function index()
{
    $this->setPage();

    $this->table->sortable();

    // Default order by created_at descending (newest first)
    $this->table->orderby('created_at', 'DESC');

    $this->table->lists('users', [
        'name',
        'email',
        'created_at:Registration Date'
    ]);

    return $this->render();
}
```

### Multiple Default Orders

Set multiple default sort orders:

```php
public function index()
{
    $this->setPage();

    $this->table->sortable();

    // Order by department first, then by name
    $this->table->orderby([
        ['department_id', 'ASC'],
        ['name', 'ASC']
    ]);

    $this->table->lists('users', [
        'name',
        'department.name:Department',
        'created_at'
    ]);

    return $this->render();
}
```

### Conditional Default Ordering

Set default ordering based on conditions:

```php
public function index(Request $request)
{
    $this->setPage();

    $this->table->sortable();

    // Different default ordering based on user role
    if (auth()->user()->hasRole('admin')) {
        $this->table->orderby('created_at', 'DESC'); // Newest first for admins
    } else {
        $this->table->orderby('name', 'ASC'); // Alphabetical for users
    }

    // Or based on request parameters
    $sortField = $request->get('default_sort', 'created_at');
    $sortDirection = $request->get('default_direction', 'DESC');
    $this->table->orderby($sortField, $sortDirection);

    $this->table->lists('users', ['name', 'email', 'created_at']);

    return $this->render();
}
```

## Custom Sort Logic

### Custom Sort Functions

Define custom sorting logic for specific columns:

```php
public function index()
{
    $this->setPage();

    $this->table->sortable();

    // Custom sort logic for specific columns
    $this->table->setCustomSort([
        'status' => function($query, $direction) {
            // Custom order: active, pending, inactive
            return $query->orderByRaw("
                CASE status 
                    WHEN 'active' THEN 1 
                    WHEN 'pending' THEN 2 
                    WHEN 'inactive' THEN 3 
                END " . $direction
            );
        },
        'priority' => function($query, $direction) {
            // Sort by priority: high, medium, low
            return $query->orderByRaw("
                FIELD(priority, 'high', 'medium', 'low') " . 
                ($direction === 'ASC' ? 'ASC' : 'DESC')
            );
        }
    ]);

    $this->table->lists('tasks', [
        'title',
        'status',
        'priority',
        'created_at'
    ]);

    return $this->render();
}
```

### Computed Column Sorting

Sort by computed or derived values:

```php
$this->table->setCustomSort([
    'full_name' => function($query, $direction) {
        // Sort by concatenated first and last name
        return $query->orderByRaw("CONCAT(first_name, ' ', last_name) " . $direction);
    },
    'age' => function($query, $direction) {
        // Sort by calculated age from birth_date
        return $query->orderByRaw("DATEDIFF(CURDATE(), birth_date) " . $direction);
    },
    'total_orders' => function($query, $direction) {
        // Sort by count of related orders
        return $query->withCount('orders')->orderBy('orders_count', $direction);
    }
]);
```

### Complex Custom Sorting

Advanced custom sorting with multiple conditions:

```php
$this->table->setCustomSort([
    'user_score' => function($query, $direction) {
        // Complex scoring algorithm
        return $query->selectRaw('
            users.*,
            (
                (CASE WHEN email_verified_at IS NOT NULL THEN 10 ELSE 0 END) +
                (CASE WHEN profile_completed = 1 THEN 15 ELSE 0 END) +
                (DATEDIFF(CURDATE(), created_at) * 0.1) +
                (SELECT COUNT(*) FROM orders WHERE user_id = users.id) * 5
            ) as user_score
        ')->orderBy('user_score', $direction);
    },
    'activity_level' => function($query, $direction) {
        // Sort by recent activity
        return $query->leftJoin('user_activities', function($join) {
            $join->on('users.id', '=', 'user_activities.user_id')
                 ->where('user_activities.created_at', '>=', now()->subDays(30));
        })
        ->selectRaw('users.*, COUNT(user_activities.id) as recent_activity')
        ->groupBy('users.id')
        ->orderBy('recent_activity', $direction);
    }
]);
```

## Multi-Column Sorting

### Enable Multi-Column Sorting

Allow users to sort by multiple columns:

```php
public function index()
{
    $this->setPage();

    $this->table->sortable();

    // Enable multi-column sorting
    $this->table->setMultiColumnSort([
        'enabled' => true,
        'max_columns' => 3,
        'show_sort_numbers' => true,
        'clear_button' => true
    ]);

    $this->table->lists('users', [
        'department.name:Department',
        'name:Full Name',
        'salary:Salary',
        'hire_date:Hire Date'
    ]);

    return $this->render();
}
```

### Predefined Multi-Column Sorts

Provide predefined multi-column sort options:

```php
$this->table->setPredefinedSorts([
    'department_hierarchy' => [
        'label' => 'Department Hierarchy',
        'sorts' => [
            ['department.name', 'ASC'],
            ['position_level', 'DESC'],
            ['name', 'ASC']
        ]
    ],
    'performance_ranking' => [
        'label' => 'Performance Ranking',
        'sorts' => [
            ['performance_score', 'DESC'],
            ['years_experience', 'DESC'],
            ['salary', 'DESC']
        ]
    ],
    'newest_active' => [
        'label' => 'Newest Active Users',
        'sorts' => [
            ['active', 'DESC'],
            ['created_at', 'DESC']
        ]
    ]
]);
```

## Relationship Sorting

### Basic Relationship Sorting

Sort by related model fields:

```php
public function index()
{
    $this->setPage();

    // Set up relationships
    $this->table->relations($this->model, 'department', 'name')
                ->relations($this->model, 'manager', 'name');

    $this->table->sortable();

    // Default sort by department name, then employee name
    $this->table->orderby([
        ['department.name', 'ASC'],
        ['name', 'ASC']
    ]);

    $this->table->lists('users', [
        'name:Employee Name',
        'department.name:Department',
        'manager.name:Manager',
        'hire_date:Hire Date'
    ]);

    return $this->render();
}
```

### Complex Relationship Sorting

Sort by aggregated relationship data:

```php
$this->table->setCustomSort([
    'total_orders' => function($query, $direction) {
        return $query->withCount('orders')
                    ->orderBy('orders_count', $direction);
    },
    'latest_order_date' => function($query, $direction) {
        return $query->leftJoin('orders', function($join) {
            $join->on('users.id', '=', 'orders.user_id')
                 ->whereRaw('orders.created_at = (
                     SELECT MAX(created_at) 
                     FROM orders o2 
                     WHERE o2.user_id = users.id
                 )');
        })
        ->orderBy('orders.created_at', $direction);
    },
    'average_order_value' => function($query, $direction) {
        return $query->leftJoin('orders', 'users.id', '=', 'orders.user_id')
                    ->selectRaw('users.*, AVG(orders.total) as avg_order_value')
                    ->groupBy('users.id')
                    ->orderBy('avg_order_value', $direction);
    }
]);
```

### Nested Relationship Sorting

Sort by deeply nested relationships:

```php
// Sort users by their department's company name
$this->table->relations($this->model, 'department.company', 'name');

$this->table->setCustomSort([
    'company_name' => function($query, $direction) {
        return $query->join('departments', 'users.department_id', '=', 'departments.id')
                    ->join('companies', 'departments.company_id', '=', 'companies.id')
                    ->orderBy('companies.name', $direction);
    }
]);

$this->table->lists('users', [
    'name',
    'department.name:Department',
    'department.company.name:Company'
]);
```

## Advanced Sorting

### Conditional Sorting

Apply different sorting logic based on conditions:

```php
$this->table->setConditionalSort([
    'priority_sort' => function($query, $direction, $context) {
        if ($context['user_role'] === 'admin') {
            // Admins see system priority
            return $query->orderBy('system_priority', $direction);
        } else {
            // Regular users see user priority
            return $query->orderBy('user_priority', $direction);
        }
    },
    'date_sort' => function($query, $direction, $context) {
        if ($context['view_mode'] === 'archive') {
            return $query->orderBy('archived_at', $direction);
        } else {
            return $query->orderBy('created_at', $direction);
        }
    }
]);
```

### Locale-Aware Sorting

Sort text columns according to locale rules:

```php
$this->table->setLocaleSorting([
    'enabled' => true,
    'locale' => app()->getLocale(),
    'columns' => ['name', 'title', 'description'],
    'collation' => [
        'name' => 'utf8mb4_unicode_ci',
        'title' => 'utf8mb4_general_ci'
    ]
]);

// Custom locale sorting
$this->table->setCustomSort([
    'name' => function($query, $direction) {
        $locale = app()->getLocale();
        if ($locale === 'tr') {
            // Turkish-specific sorting (İ, Ğ, Ü, Ş, Ö, Ç)
            return $query->orderByRaw("name COLLATE utf8mb4_turkish_ci " . $direction);
        }
        return $query->orderBy('name', $direction);
    }
]);
```

### Natural Sorting

Implement natural sorting for alphanumeric data:

```php
$this->table->setCustomSort([
    'version' => function($query, $direction) {
        // Natural sort for version numbers (1.2, 1.10, 2.1)
        return $query->orderByRaw("
            CAST(SUBSTRING_INDEX(version, '.', 1) AS UNSIGNED) " . $direction . ",
            CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(version, '.', 2), '.', -1) AS UNSIGNED) " . $direction . ",
            CAST(SUBSTRING_INDEX(version, '.', -1) AS UNSIGNED) " . $direction
        );
    },
    'item_code' => function($query, $direction) {
        // Natural sort for codes like ITEM1, ITEM2, ITEM10
        return $query->orderByRaw("
            LENGTH(item_code) " . $direction . ",
            item_code " . $direction
        );
    }
]);
```

## Sort Performance

### Index Optimization

Ensure proper database indexes for sorted columns:

```php
// In migration
Schema::table('users', function (Blueprint $table) {
    // Single column indexes
    $table->index('created_at');
    $table->index('name');
    $table->index('status');
    
    // Composite indexes for multi-column sorts
    $table->index(['department_id', 'name']);
    $table->index(['active', 'created_at']);
    $table->index(['status', 'priority', 'created_at']);
});
```

### Sort Caching

Cache expensive sort operations:

```php
$this->table->setSortCaching([
    'enabled' => true,
    'ttl' => 3600, // 1 hour
    'cache_key_prefix' => 'table_sort_',
    'cache_expensive_sorts' => [
        'user_score',
        'activity_level',
        'total_orders'
    ]
]);
```

### Efficient Sort Queries

Optimize sort queries for large datasets:

```php
$this->table->setCustomSort([
    'optimized_total_orders' => function($query, $direction) {
        // Use subquery instead of join for better performance
        return $query->orderByRaw("(
            SELECT COUNT(*) 
            FROM orders 
            WHERE orders.user_id = users.id
        ) " . $direction);
    },
    'cached_score' => function($query, $direction) {
        // Use pre-calculated cached values
        return $query->orderBy('cached_user_score', $direction);
    }
]);
```

## Sort UI Customization

### Custom Sort Icons

Customize sort indicator icons:

```php
$this->table->setSortIcons([
    'unsorted' => 'fas fa-sort',
    'asc' => 'fas fa-sort-up',
    'desc' => 'fas fa-sort-down',
    'multi_asc' => 'fas fa-sort-numeric-up',
    'multi_desc' => 'fas fa-sort-numeric-down'
]);
```

### Sort Button Styling

Customize sort button appearance:

```php
$this->table->setSortStyling([
    'header_class' => 'sortable-header',
    'active_class' => 'sort-active',
    'asc_class' => 'sort-asc',
    'desc_class' => 'sort-desc',
    'hover_class' => 'sort-hover'
]);
```

### Sort Tooltips

Add helpful tooltips to sort headers:

```php
$this->table->setSortTooltips([
    'name' => 'Sort by employee name (A-Z or Z-A)',
    'salary' => 'Sort by monthly salary (lowest to highest or highest to lowest)',
    'created_at' => 'Sort by registration date (oldest first or newest first)',
    'user_score' => 'Sort by calculated user engagement score'
]);
```

### Custom Sort Controls

Add custom sort control interface:

```php
$this->table->setCustomSortControls([
    'enabled' => true,
    'position' => 'top', // top, bottom, both
    'template' => 'table.custom_sort_controls',
    'quick_sorts' => [
        'newest' => [
            'label' => 'Newest First',
            'icon' => 'fas fa-clock',
            'sorts' => [['created_at', 'DESC']]
        ],
        'alphabetical' => [
            'label' => 'A-Z',
            'icon' => 'fas fa-sort-alpha-down',
            'sorts' => [['name', 'ASC']]
        ],
        'most_active' => [
            'label' => 'Most Active',
            'icon' => 'fas fa-fire',
            'sorts' => [['last_login_at', 'DESC'], ['login_count', 'DESC']]
        ]
    ]
]);
```

---

## Related Documentation

- [Basic Usage](../basic-usage.md) - Basic sorting setup
- [Performance Optimization](../advanced/performance.md) - Optimizing sort performance
- [API Reference](../api/objects.md) - Complete sorting method documentation
- [Examples](../examples/basic.md) - Real-world sorting examples