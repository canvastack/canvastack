# Filtering & Search

CanvaStack Table provides a powerful filtering and search system with modal interface, dependency chains, and advanced filter types. The system supports global search, column-specific filters, and complex filter relationships.

## Table of Contents

- [Global Search](#global-search)
- [Column Filters](#column-filters)
- [Filter Types](#filter-types)
- [Filter Modal Interface](#filter-modal-interface)
- [Filter Dependencies](#filter-dependencies)
- [Custom Filters](#custom-filters)
- [Advanced Filtering](#advanced-filtering)
- [Filter Persistence](#filter-persistence)
- [Performance Optimization](#performance-optimization)

## Global Search

### Basic Global Search

Enable global search across all searchable columns:

```php
public function index()
{
    $this->setPage();

    // Enable global search
    $this->table->searchable();

    $this->table->lists('users', ['name', 'email', 'phone', 'created_at']);

    return $this->render();
}
```

### Custom Searchable Columns

Specify which columns are searchable:

```php
public function index()
{
    $this->setPage();

    $this->table->searchable();

    // Only name and email are searchable
    $this->table->setSearchableColumns(['name', 'email']);

    $this->table->lists('users', ['name', 'email', 'phone', 'created_at']);

    return $this->render();
}
```

### Search Configuration

Advanced search configuration:

```php
public function index()
{
    $this->setPage();

    $this->table->searchable([
        'placeholder' => 'Search users...',
        'min_length' => 3,
        'delay' => 500, // milliseconds
        'case_sensitive' => false,
        'regex' => false,
        'smart' => true // Smart search with word boundaries
    ]);

    $this->table->lists('users', ['name', 'email']);

    return $this->render();
}
```

## Column Filters

### Basic Column Filters

Add filters for specific columns:

```php
public function index()
{
    $this->setPage();

    // Add column filters
    $this->table->filterGroups('status', 'selectbox', true)
                ->filterGroups('department', 'selectbox', true)
                ->filterGroups('created_at', 'date', true);

    $this->table->lists('users', [
        'name',
        'email',
        'status',
        'department',
        'created_at'
    ]);

    return $this->render();
}
```

### Filter with Relationships

Filter on related model fields:

```php
public function index()
{
    $this->setPage();

    // Set up relationships
    $this->table->relations($this->model, 'department', 'name')
                ->relations($this->model, 'role', 'title');

    // Filter on relationship fields
    $this->table->filterGroups('department.name', 'selectbox', true)
                ->filterGroups('role.title', 'selectbox', true)
                ->filterGroups('active', 'selectbox', true);

    $this->table->lists('users', [
        'name',
        'email',
        'department.name:Department',
        'role.title:Role',
        'active:Status'
    ]);

    return $this->render();
}
```

## Filter Types

### Selectbox Filter

Dropdown filter with predefined options:

```php
// Auto-generated options from database
$this->table->filterGroups('status', 'selectbox', true);

// Custom options
$this->table->filterGroups('priority', 'selectbox', true, [
    'options' => [
        'high' => 'High Priority',
        'medium' => 'Medium Priority',
        'low' => 'Low Priority'
    ]
]);

// With empty option
$this->table->filterGroups('category', 'selectbox', true, [
    'empty_option' => 'All Categories',
    'empty_value' => ''
]);
```

### Date Filter

Date range and single date filters:

```php
// Date range filter
$this->table->filterGroups('created_at', 'daterange', true);

// Single date filter
$this->table->filterGroups('birth_date', 'date', true);

// Date filter with custom format
$this->table->filterGroups('event_date', 'date', true, [
    'format' => 'Y-m-d',
    'display_format' => 'd/m/Y'
]);
```

### Number Filter

Numeric range and comparison filters:

```php
// Number range filter
$this->table->filterGroups('salary', 'numberrange', true);

// Number comparison filter
$this->table->filterGroups('age', 'number', true, [
    'operators' => ['=', '>', '<', '>=', '<=', '!=']
]);

// Currency filter
$this->table->filterGroups('price', 'currency', true, [
    'currency' => 'USD',
    'min' => 0,
    'max' => 10000
]);
```

### Text Filter

Text input filters with various matching options:

```php
// Basic text filter
$this->table->filterGroups('description', 'text', true);

// Text filter with operators
$this->table->filterGroups('title', 'text', true, [
    'operators' => ['contains', 'starts_with', 'ends_with', 'exact']
]);

// Case-sensitive text filter
$this->table->filterGroups('code', 'text', true, [
    'case_sensitive' => true,
    'placeholder' => 'Enter product code...'
]);
```

### Boolean Filter

True/false filters:

```php
// Boolean filter
$this->table->filterGroups('active', 'boolean', true);

// Boolean filter with custom labels
$this->table->filterGroups('verified', 'boolean', true, [
    'true_label' => 'Verified',
    'false_label' => 'Not Verified',
    'empty_label' => 'All Users'
]);
```

### Multi-select Filter

Multiple selection filters:

```php
// Multi-select filter
$this->table->filterGroups('tags', 'multiselect', true);

// Multi-select with custom options
$this->table->filterGroups('skills', 'multiselect', true, [
    'options' => [
        'php' => 'PHP',
        'javascript' => 'JavaScript',
        'python' => 'Python',
        'java' => 'Java'
    ],
    'max_selections' => 5
]);
```

## Filter Modal Interface

### Basic Modal Configuration

Configure the filter modal appearance and behavior:

```php
public function index()
{
    $this->setPage();

    // Configure filter modal
    $this->table->setFilterModal([
        'title' => 'Filter Users',
        'size' => 'modal-lg',
        'backdrop' => 'static',
        'keyboard' => false,
        'show_clear_button' => true,
        'show_reset_button' => true,
        'auto_apply' => false
    ]);

    $this->table->filterGroups('status', 'selectbox', true)
                ->filterGroups('department', 'selectbox', true)
                ->filterGroups('created_at', 'daterange', true);

    $this->table->lists('users', ['name', 'email', 'status']);

    return $this->render();
}
```

### Modal Layout and Grouping

Organize filters in groups within the modal:

```php
$this->table->setFilterGroups([
    'basic_info' => [
        'title' => 'Basic Information',
        'icon' => 'fas fa-user',
        'filters' => [
            'name' => ['type' => 'text', 'label' => 'Name'],
            'email' => ['type' => 'text', 'label' => 'Email'],
            'status' => ['type' => 'selectbox', 'label' => 'Status']
        ]
    ],
    'dates' => [
        'title' => 'Date Filters',
        'icon' => 'fas fa-calendar',
        'filters' => [
            'created_at' => ['type' => 'daterange', 'label' => 'Registration Date'],
            'last_login' => ['type' => 'daterange', 'label' => 'Last Login']
        ]
    ],
    'advanced' => [
        'title' => 'Advanced Filters',
        'icon' => 'fas fa-cog',
        'collapsible' => true,
        'collapsed' => true,
        'filters' => [
            'login_count' => ['type' => 'numberrange', 'label' => 'Login Count'],
            'verified' => ['type' => 'boolean', 'label' => 'Email Verified']
        ]
    ]
]);
```

## Filter Dependencies

### Basic Dependencies

Create dependent filters where one filter's options depend on another:

```php
public function index()
{
    $this->setPage();

    // Set up relationships
    $this->table->relations($this->model, 'country', 'name')
                ->relations($this->model, 'state', 'name')
                ->relations($this->model, 'city', 'name');

    // Create filter dependency chain
    $this->table->filterGroups('country.name', 'selectbox', true)
                ->filterGroups('state.name', 'selectbox', true, [
                    'depends_on' => 'country.name',
                    'dependency_url' => '/api/states-by-country'
                ])
                ->filterGroups('city.name', 'selectbox', true, [
                    'depends_on' => 'state.name',
                    'dependency_url' => '/api/cities-by-state'
                ]);

    $this->table->lists('users', [
        'name',
        'email',
        'country.name:Country',
        'state.name:State',
        'city.name:City'
    ]);

    return $this->render();
}
```

### Complex Dependencies

Multiple dependencies and conditional logic:

```php
$this->table->setFilterDependencies([
    'product_category' => [
        'type' => 'selectbox',
        'options_url' => '/api/categories'
    ],
    'product_subcategory' => [
        'type' => 'selectbox',
        'depends_on' => ['product_category'],
        'options_url' => '/api/subcategories',
        'condition' => function($dependencies) {
            return !empty($dependencies['product_category']);
        }
    ],
    'product_brand' => [
        'type' => 'selectbox',
        'depends_on' => ['product_category', 'product_subcategory'],
        'options_url' => '/api/brands',
        'condition' => function($dependencies) {
            return !empty($dependencies['product_category']) && 
                   !empty($dependencies['product_subcategory']);
        }
    ]
]);
```

### Dependency API Endpoints

Handle dependency requests in your controller:

```php
public function getStatesByCountry(Request $request)
{
    $countryId = $request->get('country_id');
    
    $states = State::where('country_id', $countryId)
                   ->orderBy('name')
                   ->get(['id', 'name']);
    
    return response()->json([
        'options' => $states->pluck('name', 'id')->toArray()
    ]);
}

public function getCitiesByState(Request $request)
{
    $stateId = $request->get('state_id');
    
    $cities = City::where('state_id', $stateId)
                  ->orderBy('name')
                  ->get(['id', 'name']);
    
    return response()->json([
        'options' => $cities->pluck('name', 'id')->toArray()
    ]);
}
```

## Custom Filters

### Custom Filter Types

Create custom filter types for specific needs:

```php
$this->table->setCustomFilters([
    'user_type' => [
        'type' => 'custom',
        'label' => 'User Type',
        'template' => 'filters.user_type',
        'javascript' => 'initUserTypeFilter',
        'validation' => function($value) {
            return in_array($value, ['admin', 'user', 'guest']);
        },
        'query' => function($value, $query) {
            switch($value) {
                case 'admin':
                    return $query->whereHas('roles', function($q) {
                        $q->where('name', 'admin');
                    });
                case 'user':
                    return $query->whereDoesntHave('roles');
                case 'guest':
                    return $query->where('email_verified_at', null);
            }
            return $query;
        }
    ]
]);
```

### Advanced Custom Filters

Complex custom filters with multiple inputs:

```php
$this->table->setCustomFilters([
    'location_radius' => [
        'type' => 'location_radius',
        'label' => 'Location & Radius',
        'template' => 'filters.location_radius',
        'fields' => [
            'latitude' => ['type' => 'number', 'step' => 'any'],
            'longitude' => ['type' => 'number', 'step' => 'any'],
            'radius' => ['type' => 'number', 'min' => 1, 'max' => 100]
        ],
        'validation' => function($values) {
            return isset($values['latitude']) && 
                   isset($values['longitude']) && 
                   isset($values['radius']);
        },
        'query' => function($values, $query) {
            $lat = $values['latitude'];
            $lng = $values['longitude'];
            $radius = $values['radius'];
            
            return $query->whereRaw("
                ST_Distance_Sphere(
                    POINT(longitude, latitude),
                    POINT(?, ?)
                ) <= ? * 1000
            ", [$lng, $lat, $radius]);
        }
    ]
]);
```

## Advanced Filtering

### Saved Filters

Allow users to save and reuse filter combinations:

```php
$this->table->setSavedFilters([
    'enabled' => true,
    'user_specific' => true,
    'max_saved_filters' => 10,
    'default_filters' => [
        'active_users' => [
            'name' => 'Active Users',
            'filters' => [
                'active' => true,
                'email_verified_at' => ['operator' => 'not_null']
            ]
        ],
        'recent_signups' => [
            'name' => 'Recent Signups',
            'filters' => [
                'created_at' => [
                    'operator' => 'between',
                    'value' => [now()->subDays(7), now()]
                ]
            ]
        ]
    ]
]);
```

### Filter Presets

Provide quick filter presets for common scenarios:

```php
$this->table->setFilterPresets([
    'today' => [
        'label' => 'Today',
        'icon' => 'fas fa-calendar-day',
        'filters' => [
            'created_at' => [
                'operator' => 'between',
                'value' => [now()->startOfDay(), now()->endOfDay()]
            ]
        ]
    ],
    'this_week' => [
        'label' => 'This Week',
        'icon' => 'fas fa-calendar-week',
        'filters' => [
            'created_at' => [
                'operator' => 'between',
                'value' => [now()->startOfWeek(), now()->endOfWeek()]
            ]
        ]
    ],
    'active_verified' => [
        'label' => 'Active & Verified',
        'icon' => 'fas fa-check-circle',
        'filters' => [
            'active' => true,
            'email_verified_at' => ['operator' => 'not_null']
        ]
    ]
]);
```

### Dynamic Filter Options

Generate filter options dynamically based on data:

```php
$this->table->setDynamicFilterOptions([
    'department' => function() {
        return Department::where('active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray();
    },
    'manager' => function($filters = []) {
        $query = User::whereHas('roles', function($q) {
            $q->where('name', 'manager');
        });
        
        // Filter managers by department if department filter is active
        if (!empty($filters['department'])) {
            $query->where('department_id', $filters['department']);
        }
        
        return $query->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray();
    }
]);
```

## Filter Persistence

### Session-Based Persistence

Persist filters across page reloads:

```php
$this->table->setFilterPersistence([
    'enabled' => true,
    'method' => 'session',
    'key' => 'user_table_filters',
    'expire' => 3600 // seconds
]);
```

### URL-Based Persistence

Store filters in URL parameters:

```php
$this->table->setFilterPersistence([
    'enabled' => true,
    'method' => 'url',
    'update_url' => true,
    'encode_values' => true
]);
```

### Database Persistence

Store user-specific filter preferences:

```php
$this->table->setFilterPersistence([
    'enabled' => true,
    'method' => 'database',
    'table' => 'user_filter_preferences',
    'user_column' => 'user_id',
    'identifier_column' => 'table_identifier',
    'filters_column' => 'filters_json'
]);
```

## Performance Optimization

### Filter Indexing

Ensure proper database indexes for filtered columns:

```php
// In migration
Schema::table('users', function (Blueprint $table) {
    $table->index('status');
    $table->index('department_id');
    $table->index(['active', 'created_at']);
    $table->index(['department_id', 'status']);
});
```

### Filter Caching

Cache filter options for better performance:

```php
$this->table->setFilterCaching([
    'enabled' => true,
    'ttl' => 3600, // 1 hour
    'cache_key_prefix' => 'table_filters_',
    'cache_options' => true,
    'cache_dependencies' => true
]);
```

### Optimized Filter Queries

Use efficient queries for filter options:

```php
$this->table->setFilterQueries([
    'department' => function() {
        return DB::table('departments')
                 ->select('id', 'name')
                 ->where('active', 1)
                 ->orderBy('name')
                 ->get()
                 ->pluck('name', 'id');
    },
    'status' => function() {
        return DB::table('users')
                 ->select('status')
                 ->distinct()
                 ->whereNotNull('status')
                 ->orderBy('status')
                 ->pluck('status', 'status');
    }
]);
```

---

## Related Documentation

- [Basic Usage](../basic-usage.md) - Basic filtering setup
- [API Reference](../api/objects.md) - Complete filtering method documentation
- [Performance Optimization](../advanced/performance.md) - Optimizing filter performance
- [Examples](../examples/basic.md) - Real-world filtering examples