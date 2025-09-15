# Data Sources

CanvaStack Table supports multiple data sources, allowing you to work with Eloquent Models, Query Builder, Raw SQL, and even external APIs. This flexibility makes it suitable for various application architectures and data requirements.

## Table of Contents

- [Eloquent Models](#eloquent-models)
- [Query Builder](#query-builder)
- [Raw SQL Queries](#raw-sql-queries)
- [External APIs](#external-apis)
- [Mixed Data Sources](#mixed-data-sources)
- [Dynamic Data Sources](#dynamic-data-sources)
- [Performance Considerations](#performance-considerations)
- [Best Practices](#best-practices)

## Eloquent Models

### Basic Model Usage

The most common and recommended approach is using Eloquent models:

```php
<?php

namespace App\Http\Controllers;

use Canvastack\Canvastack\Controllers\Core\Controller;
use App\Models\User;

class UserController extends Controller
{
    public function __construct()
    {
        // Initialize with Eloquent model
        parent::__construct(User::class, 'users');
    }

    public function index()
    {
        $this->setPage();

        // The model is automatically used as data source
        $this->table->lists('users', ['name', 'email', 'created_at']);

        return $this->render();
    }
}
```

### Model with Relationships

Leverage Eloquent relationships for complex data:

```php
// User Model
class User extends Model
{
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
    
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}

// Controller
public function index()
{
    $this->setPage();

    // Set up relationships
    $this->table->relations($this->model, 'group', 'name')
                ->relations($this->model, 'department', 'dept_name');

    $this->table->lists('users', [
        'name:Full Name',
        'email',
        'group.name:Group',
        'department.dept_name:Department',
        'created_at:Join Date'
    ]);

    return $this->render();
}
```

### Model Scopes and Constraints

Apply model scopes and constraints:

```php
// User Model with scopes
class User extends Model
{
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
    
    public function scopeInDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }
}

// Controller
public function index()
{
    $this->setPage();

    // Apply model constraints
    $this->table->model(User::active()->inDepartment(auth()->user()->department_id));

    $this->table->lists('users', ['name', 'email', 'department']);

    return $this->render();
}
```

### Custom Model Methods

Use custom model methods for computed fields:

```php
// User Model
class User extends Model
{
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    
    public function getAgeAttribute()
    {
        return $this->birth_date ? $this->birth_date->age : null;
    }
    
    public function getTotalOrdersAttribute()
    {
        return $this->orders()->count();
    }
}

// Controller
$this->table->lists('users', [
    'full_name:Full Name',    // Uses accessor
    'age:Age',                // Computed field
    'total_orders:Orders',    // Relationship count
    'email'
]);
```

## Query Builder

### Basic Query Builder

Use Laravel's Query Builder for more control:

```php
public function index()
{
    $this->setPage();

    // Create query builder instance
    $query = DB::table('users')
               ->select('users.*', 'groups.name as group_name')
               ->leftJoin('groups', 'users.group_id', '=', 'groups.id')
               ->where('users.active', true);

    // Set query as data source
    $this->table->query($query);

    $this->table->lists('users', [
        'name:Full Name',
        'email',
        'group_name:Group',
        'created_at:Join Date'
    ]);

    return $this->render();
}
```

### Complex Query Builder

Build complex queries with multiple joins and conditions:

```php
public function salesReport()
{
    $this->setPage();

    $query = DB::table('orders')
               ->select([
                   'orders.id',
                   'orders.order_number',
                   'customers.name as customer_name',
                   'customers.email as customer_email',
                   'products.name as product_name',
                   'order_items.quantity',
                   'order_items.price',
                   DB::raw('(order_items.quantity * order_items.price) as total'),
                   'orders.created_at'
               ])
               ->join('customers', 'orders.customer_id', '=', 'customers.id')
               ->join('order_items', 'orders.id', '=', 'order_items.order_id')
               ->join('products', 'order_items.product_id', '=', 'products.id')
               ->where('orders.status', 'completed')
               ->whereBetween('orders.created_at', [
                   now()->startOfMonth(),
                   now()->endOfMonth()
               ]);

    $this->table->query($query);

    $this->table->lists('sales_report', [
        'order_number:Order #',
        'customer_name:Customer',
        'product_name:Product',
        'quantity:Qty',
        'price:Unit Price',
        'total:Total',
        'created_at:Date'
    ], false); // No actions for reports

    return $this->render();
}
```

### Conditional Query Building

Build queries based on user input or conditions:

```php
public function index(Request $request)
{
    $this->setPage();

    $query = DB::table('users')->select('*');

    // Apply conditional filters
    if ($request->has('department')) {
        $query->where('department_id', $request->department);
    }

    if ($request->has('status')) {
        $query->where('active', $request->status === 'active');
    }

    if ($request->has('date_from')) {
        $query->where('created_at', '>=', $request->date_from);
    }

    // Apply user-specific constraints
    if (!auth()->user()->hasRole('admin')) {
        $query->where('department_id', auth()->user()->department_id);
    }

    $this->table->query($query);

    $this->table->lists('users', ['name', 'email', 'department', 'active']);

    return $this->render();
}
```

## Raw SQL Queries

### Basic Raw SQL

Use raw SQL for maximum control and performance:

```php
public function index()
{
    $this->setPage();

    $sql = "
        SELECT 
            u.id,
            u.name,
            u.email,
            g.name as group_name,
            d.name as department_name,
            COUNT(o.id) as total_orders,
            COALESCE(SUM(o.total), 0) as total_spent,
            u.created_at
        FROM users u
        LEFT JOIN groups g ON u.group_id = g.id
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN orders o ON u.id = o.customer_id
        WHERE u.active = 1
        GROUP BY u.id, u.name, u.email, g.name, d.name, u.created_at
        ORDER BY u.created_at DESC
    ";

    $this->table->query($sql);

    $this->table->lists('user_summary', [
        'name:Full Name',
        'email',
        'group_name:Group',
        'department_name:Department',
        'total_orders:Orders',
        'total_spent:Total Spent ($)',
        'created_at:Join Date'
    ]);

    return $this->render();
}
```

### Parameterized Raw SQL

Use parameter binding for security:

```php
public function departmentReport($departmentId)
{
    $this->setPage();

    $sql = "
        SELECT 
            u.name,
            u.email,
            p.title as position,
            u.salary,
            u.hire_date,
            DATEDIFF(CURDATE(), u.hire_date) as days_employed
        FROM users u
        JOIN positions p ON u.position_id = p.id
        WHERE u.department_id = ?
        AND u.active = 1
        ORDER BY u.hire_date ASC
    ";

    // Use parameter binding for security
    $this->table->query($sql, [$departmentId]);

    $this->table->lists('department_employees', [
        'name:Employee Name',
        'email',
        'position:Position',
        'salary:Salary ($)',
        'hire_date:Hire Date',
        'days_employed:Days Employed'
    ]);

    return $this->render();
}
```

### Complex Analytical Queries

Build complex analytical queries:

```php
public function analyticsReport()
{
    $this->setPage();

    $sql = "
        WITH monthly_stats AS (
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as new_users,
                COUNT(CASE WHEN active = 1 THEN 1 END) as active_users
            FROM users 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ),
        order_stats AS (
            SELECT 
                DATE_FORMAT(o.created_at, '%Y-%m') as month,
                COUNT(*) as total_orders,
                SUM(o.total) as total_revenue,
                AVG(o.total) as avg_order_value
            FROM orders o
            WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
        )
        SELECT 
            ms.month,
            ms.new_users,
            ms.active_users,
            COALESCE(os.total_orders, 0) as total_orders,
            COALESCE(os.total_revenue, 0) as total_revenue,
            COALESCE(os.avg_order_value, 0) as avg_order_value,
            CASE 
                WHEN ms.new_users > 0 
                THEN ROUND((COALESCE(os.total_orders, 0) / ms.new_users), 2)
                ELSE 0 
            END as orders_per_user
        FROM monthly_stats ms
        LEFT JOIN order_stats os ON ms.month = os.month
        ORDER BY ms.month DESC
    ";

    $this->table->query($sql);

    $this->table->lists('analytics_report', [
        'month:Month',
        'new_users:New Users',
        'active_users:Active Users',
        'total_orders:Total Orders',
        'total_revenue:Revenue ($)',
        'avg_order_value:Avg Order ($)',
        'orders_per_user:Orders/User'
    ], false);

    return $this->render();
}
```

## External APIs

### REST API Data Source

Integrate with external REST APIs:

```php
class ApiDataSource
{
    protected $apiClient;
    
    public function __construct()
    {
        $this->apiClient = new GuzzleHttp\Client([
            'base_uri' => config('services.external_api.base_url'),
            'headers' => [
                'Authorization' => 'Bearer ' . config('services.external_api.token'),
                'Accept' => 'application/json'
            ]
        ]);
    }
    
    public function getUsers($page = 1, $limit = 25, $search = null)
    {
        $params = [
            'page' => $page,
            'limit' => $limit
        ];
        
        if ($search) {
            $params['search'] = $search;
        }
        
        $response = $this->apiClient->get('/users', [
            'query' => $params
        ]);
        
        return json_decode($response->getBody(), true);
    }
}

// Controller
public function index()
{
    $this->setPage();
    
    $apiDataSource = new ApiDataSource();
    
    // Set custom data source
    $this->table->setCustomDataSource($apiDataSource);
    
    $this->table->lists('api_users', [
        'name:Full Name',
        'email',
        'company:Company',
        'role:Role',
        'last_login:Last Login'
    ]);
    
    return $this->render();
}
```

### GraphQL Data Source

Integrate with GraphQL APIs:

```php
class GraphQLDataSource
{
    protected $client;
    
    public function __construct()
    {
        $this->client = new \Lighthouse\GraphQL\Client([
            'endpoint' => config('services.graphql.endpoint'),
            'headers' => [
                'Authorization' => 'Bearer ' . config('services.graphql.token')
            ]
        ]);
    }
    
    public function getUsers($filters = [])
    {
        $query = '
            query GetUsers($filters: UserFilters) {
                users(filters: $filters) {
                    data {
                        id
                        name
                        email
                        department {
                            name
                        }
                        createdAt
                    }
                    pagination {
                        total
                        currentPage
                        lastPage
                    }
                }
            }
        ';
        
        $result = $this->client->query($query, ['filters' => $filters]);
        
        return $result['data']['users'];
    }
}
```

## Mixed Data Sources

### Combining Multiple Sources

Combine data from multiple sources:

```php
public function dashboardReport()
{
    $this->setPage();

    // Get data from multiple sources
    $localUsers = User::with('department')->get();
    $apiUsers = $this->getApiUsers();
    $externalData = $this->getExternalData();

    // Combine and normalize data
    $combinedData = collect();

    // Add local users
    foreach ($localUsers as $user) {
        $combinedData->push([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'department' => $user->department->name ?? 'N/A',
            'source' => 'Local',
            'last_activity' => $user->last_login_at,
            'status' => $user->active ? 'Active' : 'Inactive'
        ]);
    }

    // Add API users
    foreach ($apiUsers as $user) {
        $combinedData->push([
            'id' => 'api_' . $user['id'],
            'name' => $user['full_name'],
            'email' => $user['email'],
            'department' => $user['department'],
            'source' => 'External API',
            'last_activity' => $user['last_seen'],
            'status' => $user['is_active'] ? 'Active' : 'Inactive'
        ]);
    }

    // Set combined data as source
    $this->table->setCollectionDataSource($combinedData);

    $this->table->lists('combined_users', [
        'name:Full Name',
        'email',
        'department:Department',
        'source:Data Source',
        'last_activity:Last Activity',
        'status:Status'
    ]);

    return $this->render();
}
```

## Dynamic Data Sources

### Runtime Data Source Selection

Choose data source based on runtime conditions:

```php
public function index(Request $request)
{
    $this->setPage();

    $dataSource = $request->get('source', 'database');

    switch ($dataSource) {
        case 'database':
            $this->table->model(User::class);
            break;
            
        case 'api':
            $this->table->setCustomDataSource(new ApiDataSource());
            break;
            
        case 'cache':
            $cachedData = Cache::get('users_data', collect());
            $this->table->setCollectionDataSource($cachedData);
            break;
            
        case 'file':
            $fileData = $this->loadFromFile('users.json');
            $this->table->setCollectionDataSource($fileData);
            break;
            
        default:
            $this->table->model(User::class);
    }

    $this->table->lists('users', [
        'name:Full Name',
        'email',
        'department:Department',
        'created_at:Created'
    ]);

    return $this->render();
}
```

### Conditional Data Sources

Use different data sources based on user permissions:

```php
public function index()
{
    $this->setPage();

    if (auth()->user()->hasRole('admin')) {
        // Admins see all data from main database
        $this->table->model(User::class);
    } elseif (auth()->user()->hasRole('manager')) {
        // Managers see department data
        $this->table->model(
            User::where('department_id', auth()->user()->department_id)
        );
    } else {
        // Regular users see limited data from cache
        $limitedData = Cache::remember('user_limited_data', 3600, function() {
            return User::select('id', 'name', 'email')
                      ->where('public_profile', true)
                      ->get();
        });
        
        $this->table->setCollectionDataSource($limitedData);
    }

    $this->table->lists('users', [
        'name:Full Name',
        'email',
        'department:Department'
    ]);

    return $this->render();
}
```

## Performance Considerations

### Data Source Optimization

Choose the right data source for performance:

```php
public function index()
{
    $this->setPage();

    $recordCount = User::count();

    if ($recordCount < 1000) {
        // Small dataset: use Eloquent with relationships
        $this->table->model(User::with(['group', 'department']));
    } elseif ($recordCount < 10000) {
        // Medium dataset: use Query Builder with joins
        $query = DB::table('users')
                   ->leftJoin('groups', 'users.group_id', '=', 'groups.id')
                   ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
                   ->select('users.*', 'groups.name as group_name', 'departments.name as dept_name');
        
        $this->table->query($query);
    } else {
        // Large dataset: use raw SQL with server-side processing
        $this->table->method('POST');
        
        $sql = "
            SELECT u.id, u.name, u.email, g.name as group_name, d.name as dept_name
            FROM users u
            LEFT JOIN groups g ON u.group_id = g.id
            LEFT JOIN departments d ON u.department_id = d.id
        ";
        
        $this->table->query($sql);
    }

    $this->table->lists('users', [
        'name:Full Name',
        'email',
        'group_name:Group',
        'dept_name:Department'
    ]);

    return $this->render();
}
```

### Caching Strategies

Implement caching for expensive data sources:

```php
public function expensiveReport()
{
    $this->setPage();

    $cacheKey = 'expensive_report_' . auth()->id();
    
    $data = Cache::remember($cacheKey, 3600, function() {
        // Expensive query or API call
        return DB::select("
            SELECT 
                complex_calculation_1,
                complex_calculation_2,
                aggregated_data
            FROM very_large_table
            WHERE complex_conditions
            GROUP BY multiple_fields
            HAVING complex_having_conditions
        ");
    });

    $this->table->setCollectionDataSource(collect($data));

    $this->table->lists('expensive_report', [
        'complex_calculation_1:Metric 1',
        'complex_calculation_2:Metric 2',
        'aggregated_data:Summary'
    ]);

    return $this->render();
}
```

## Best Practices

### 1. Choose the Right Data Source

```php
// Use Eloquent for:
// - Simple CRUD operations
// - When you need model features (accessors, mutators, relationships)
// - Small to medium datasets (< 10,000 records)

// Use Query Builder for:
// - Complex joins and aggregations
// - When you need more control than Eloquent provides
// - Medium to large datasets (10,000 - 100,000 records)

// Use Raw SQL for:
// - Complex analytical queries
// - Maximum performance requirements
// - Very large datasets (> 100,000 records)
// - Database-specific features
```

### 2. Security Considerations

```php
// Always use parameter binding for user input
$sql = "SELECT * FROM users WHERE department_id = ? AND active = ?";
$this->table->query($sql, [$departmentId, true]);

// Validate and sanitize external API data
$apiData = $this->sanitizeApiData($rawApiData);
$this->table->setCollectionDataSource($apiData);

// Apply access control
if (!auth()->user()->can('view-all-users')) {
    $query->where('department_id', auth()->user()->department_id);
}
```

### 3. Performance Optimization

```php
// Use appropriate indexes
Schema::table('users', function (Blueprint $table) {
    $table->index(['department_id', 'active']);
    $table->index('created_at');
});

// Limit data selection
$query->select(['id', 'name', 'email', 'created_at']); // Only needed columns

// Use server-side processing for large datasets
if ($recordCount > 1000) {
    $this->table->method('POST');
}
```

### 4. Error Handling

```php
try {
    $this->table->query($complexQuery);
} catch (\Exception $e) {
    Log::error('Data source error: ' . $e->getMessage());
    
    // Fallback to simpler data source
    $this->table->model(User::class);
}
```

### 5. Testing Data Sources

```php
// Test different data sources
public function testDataSources()
{
    // Test Eloquent
    $eloquentData = User::take(10)->get();
    $this->assertCount(10, $eloquentData);
    
    // Test Query Builder
    $builderData = DB::table('users')->take(10)->get();
    $this->assertCount(10, $builderData);
    
    // Test Raw SQL
    $rawData = DB::select("SELECT * FROM users LIMIT 10");
    $this->assertCount(10, $rawData);
}
```

---

## Related Documentation

- [Basic Usage](basic-usage.md) - How to use different data sources
- [API Reference](api/objects.md) - Data source method documentation
- [Performance Optimization](advanced/performance.md) - Optimizing data source performance
- [Security Features](advanced/security.md) - Securing data sources