# CanvaStack Table Component - Troubleshooting Guide

## Table of Contents

1. [Common Issues](#common-issues)
2. [Error Messages](#error-messages)
3. [Performance Issues](#performance-issues)
4. [Security Issues](#security-issues)
5. [Configuration Issues](#configuration-issues)
6. [Data Display Issues](#data-display-issues)
7. [Debugging Tips](#debugging-tips)
8. [Performance Optimization](#performance-optimization)

---

## Common Issues

### Issue: Table Not Rendering

**Symptoms:**
- Blank page or empty table container
- No error messages in browser console
- DataTables not initializing

**Possible Causes & Solutions:**

1. **Model not set**
   ```php
   // ❌ Wrong - model not set
   $table = new TableBuilder();
   $table->render();
   
   // ✅ Correct - set model first
   $table = new TableBuilder();
   $table->model(User::class);
   $table->render();
   ```

2. **Table name not set**
   ```php
   // ❌ Wrong - table name missing
   $table->setFields(['id', 'name']);
   
   // ✅ Correct - set table name first
   $table->setName('users')->setFields(['id', 'name']);
   ```

3. **Invalid columns specified**
   ```php
   // ❌ Wrong - column doesn't exist
   $table->setFields(['id', 'nonexistent_column']);
   
   // ✅ Correct - use valid columns
   $table->setFields(['id', 'name', 'email']);
   ```


### Issue: N+1 Query Problem

**Symptoms:**
- Slow table loading with relationships
- Hundreds or thousands of database queries
- High database CPU usage

**Solution:**

```php
// ❌ Wrong - causes N+1 queries
$table->relations(User::class, 'posts', 'title');
// This will execute 1 query for users + N queries for posts

// ✅ Correct - use eager loading
$table->relations(User::class, 'posts', 'title');
// TableBuilder automatically adds 'posts' to eager load array
// Result: Only 2 queries (1 for users, 1 for all posts)
```

**Verification:**
```php
// Enable query logging
DB::enableQueryLog();

$html = $table->render();

// Check query count
$queries = DB::getQueryLog();
echo "Total queries: " . count($queries);
// Should be ≤ 2 for single relationship
```

### Issue: Memory Exhausted Error

**Symptoms:**
- Fatal error: Allowed memory size exhausted
- Server crashes when loading large datasets
- Slow performance with 10K+ rows

**Solution:**

```php
// ❌ Wrong - loads all data into memory
$table->setServerSide(false);
$table->displayRowsLimitOnLoad('all');

// ✅ Correct - use server-side processing
$table->setServerSide(true);
$table->displayRowsLimitOnLoad(100);

// ✅ Also correct - use chunk processing
$table->setServerSide(true);
$table->config(['chunk_size' => 100]);
```

### Issue: DataTables Not Initializing

**Symptoms:**
- Table displays but no sorting/searching/pagination
- JavaScript console shows "DataTable is not a function"
- Static HTML table instead of interactive DataTable

**Solution:**

1. **Check DataTables library is loaded**
   ```html
   <!-- Required in your layout -->
   <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
   <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
   <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
   ```

2. **Check table has correct ID**
   ```php
   // TableBuilder automatically generates unique ID
   // Ensure your JavaScript targets the correct ID
   ```

3. **Check for JavaScript errors**
   ```javascript
   // Open browser console (F12) and check for errors
   // Common error: "$ is not defined" means jQuery not loaded
   ```


---

## Error Messages

### InvalidArgumentException: Column 'X' does not exist in table 'Y'

**Cause:** Attempting to use a column that doesn't exist in the database table schema.

**Solution:**
```php
// Check your table schema
Schema::hasColumn('users', 'column_name'); // Returns true/false

// Use only existing columns
$table->setFields(['id', 'name', 'email']); // ✅ Valid columns
$table->setFields(['id', 'invalid_col']); // ❌ Throws exception
```

**Prevention:**
```php
// Get all columns from schema
$columns = Schema::getColumnListing('users');
// Use only columns from this list
```

### InvalidArgumentException: SQL query contains dangerous statement

**Cause:** Attempting to use raw SQL with dangerous statements (DROP, DELETE, UPDATE, etc.).

**Solution:**
```php
// ❌ Wrong - dangerous SQL
$table->query("SELECT * FROM users; DROP TABLE users;");

// ✅ Correct - only SELECT allowed
$table->query("SELECT id, name, email FROM users WHERE active = 1");

// ✅ Better - use Query Builder instead
$table->model(User::where('active', 1));
```

### BadMethodCallException: Method 'X' does not exist on Model

**Cause:** Calling a relationship method that doesn't exist on the model.

**Solution:**
```php
// ❌ Wrong - method doesn't exist
$table->relations(User::class, 'nonexistentRelation', 'name');

// ✅ Correct - use existing relationship
$table->relations(User::class, 'posts', 'title');

// Check if method exists first
if (method_exists(User::class, 'posts')) {
    $table->relations(User::class, 'posts', 'title');
}
```

### RuntimeException: Model not set. Call model() before render()

**Cause:** Attempting to render table without setting a model or data source.

**Solution:**
```php
// ❌ Wrong - no model set
$table = new TableBuilder();
$table->render(); // Throws exception

// ✅ Correct - set model first
$table = new TableBuilder();
$table->model(User::class);
$table->render();
```

### InvalidArgumentException: Invalid measurement unit

**Cause:** Using unsupported measurement unit for table width.

**Solution:**
```php
// ❌ Wrong - invalid unit
$table->setWidth(100, 'pt'); // 'pt' not supported

// ✅ Correct - use supported units
$table->setWidth(100, 'px');  // Pixels
$table->setWidth(100, '%');   // Percentage
$table->setWidth(100, 'em');  // Em units
$table->setWidth(100, 'rem'); // Rem units
$table->setWidth(100, 'vw');  // Viewport width
```


### InvalidArgumentException: Invalid HTML attribute

**Cause:** Attempting to add malicious HTML attributes (XSS prevention).

**Solution:**
```php
// ❌ Wrong - event handlers not allowed
$table->addAttributes(['onclick' => 'alert("XSS")']);
$table->addAttributes(['onload' => 'maliciousCode()']);

// ❌ Wrong - javascript: URLs not allowed
$table->addAttributes(['data-url' => 'javascript:alert("XSS")']);

// ✅ Correct - safe attributes
$table->addAttributes(['class' => 'table-striped']);
$table->addAttributes(['data-page' => '1']);
$table->addAttributes(['id' => 'my-table']);
```

### InvalidArgumentException: Invalid color format

**Cause:** Using invalid hex color format.

**Solution:**
```php
// ❌ Wrong - invalid formats
$table->setBackgroundColor('red'); // Named colors not supported
$table->setBackgroundColor('#FFF'); // Must be 6 digits
$table->setBackgroundColor('rgb(255,0,0)'); // RGB not supported

// ✅ Correct - hex format with 6 digits
$table->setBackgroundColor('#FF0000'); // Red
$table->setBackgroundColor('#00FF00'); // Green
$table->setBackgroundColor('#0000FF'); // Blue
```

---

## Performance Issues

### Issue: Slow Table Loading (> 2 seconds)

**Diagnosis:**
```php
// Measure execution time
$start = microtime(true);
$html = $table->render();
$duration = microtime(true) - $start;
echo "Render time: " . round($duration * 1000) . "ms";
```

**Solutions:**

1. **Enable Caching**
   ```php
   // Cache for 5 minutes (300 seconds)
   $table->config(['cache_seconds' => 300]);
   ```

2. **Use Server-Side Processing**
   ```php
   // For large datasets (> 1000 rows)
   $table->setServerSide(true);
   ```

3. **Optimize Eager Loading**
   ```php
   // Load only required relationships
   $table->relations(User::class, 'posts', 'title');
   // Don't load unnecessary relationships
   ```

4. **Select Only Required Columns**
   ```php
   // ❌ Wrong - loads all columns
   $table->model(User::class);
   
   // ✅ Correct - loads only required columns
   $table->model(User::select(['id', 'name', 'email']));
   ```

### Issue: High Memory Usage

**Diagnosis:**
```php
// Measure memory usage
$memBefore = memory_get_usage(true);
$html = $table->render();
$memAfter = memory_get_usage(true);
$memUsed = ($memAfter - $memBefore) / 1024 / 1024;
echo "Memory used: " . round($memUsed, 2) . " MB";
```

**Solutions:**

1. **Enable Chunk Processing**
   ```php
   // Process data in chunks of 100 rows
   $table->config(['chunk_size' => 100]);
   ```

2. **Use Server-Side Processing**
   ```php
   // Load data on-demand via AJAX
   $table->setServerSide(true);
   ```

3. **Limit Initial Load**
   ```php
   // Show only 10 rows initially
   $table->displayRowsLimitOnLoad(10);
   ```


### Issue: Too Many Database Queries

**Diagnosis:**
```php
// Enable query logging
DB::enableQueryLog();

$html = $table->render();

// Check queries
$queries = DB::getQueryLog();
echo "Total queries: " . count($queries) . "\n";

// Print all queries
foreach ($queries as $query) {
    echo $query['query'] . "\n";
}
```

**Solutions:**

1. **Use Eager Loading for Relationships**
   ```php
   // ❌ Wrong - N+1 queries
   $table->model(User::class);
   $table->relations(User::class, 'posts', 'title');
   // Without eager loading: 1 + N queries
   
   // ✅ Correct - eager loading automatic
   // TableBuilder automatically adds to eager load
   // Result: Only 2 queries
   ```

2. **Avoid Multiple Relationship Calls**
   ```php
   // ❌ Wrong - multiple calls
   $table->relations(User::class, 'posts', 'title');
   $table->relations(User::class, 'comments', 'body');
   $table->relations(User::class, 'likes', 'count');
   // Could cause 4 queries (1 + 3 relationships)
   
   // ✅ Correct - all relationships loaded together
   // TableBuilder optimizes to load all at once
   ```

3. **Use Query Result Caching**
   ```php
   // Cache query results for 5 minutes
   $table->config(['cache_seconds' => 300]);
   ```

---

## Security Issues

### Issue: SQL Injection Vulnerability

**Symptoms:**
- User input directly in SQL queries
- Raw SQL concatenation
- Unvalidated column names

**Prevention:**

```php
// ❌ NEVER do this - SQL injection risk
$column = $_GET['sort'];
$table->orderby($column); // Dangerous if not validated

// ✅ Correct - validation automatic
// TableBuilder validates all column names against schema
$table->orderby($column); // Throws exception if invalid

// ✅ Also correct - whitelist approach
$allowedColumns = ['id', 'name', 'email'];
if (in_array($column, $allowedColumns)) {
    $table->orderby($column);
}
```

### Issue: XSS (Cross-Site Scripting) Vulnerability

**Symptoms:**
- Unescaped HTML in output
- JavaScript execution in table cells
- Malicious attributes in HTML

**Prevention:**

```php
// ❌ Wrong - unescaped output
echo $table->render(); // If data contains <script>, it will execute

// ✅ Correct - TableBuilder escapes all output automatically
// All cell values are escaped using Laravel's e() helper
$html = $table->render(); // Safe to output

// ✅ For custom HTML in conditions
$table->columnCondition('status', 'cell', '==', 'active', 'css style', 'color: green');
// Action text is escaped automatically
```

### Issue: Unauthorized Data Access

**Symptoms:**
- Users can access data they shouldn't see
- No permission checks
- Direct table access

**Prevention:**

```php
// ❌ Wrong - no authorization
$table->model(User::class);

// ✅ Correct - apply authorization filters
$table->model(User::where('company_id', auth()->user()->company_id));

// ✅ Also correct - use policies
if (auth()->user()->can('viewAny', User::class)) {
    $table->model(User::class);
} else {
    abort(403);
}
```


---

## Configuration Issues

### Issue: Cache Not Working

**Symptoms:**
- Same queries executed repeatedly
- No performance improvement with caching enabled
- Cache hit ratio is 0%

**Diagnosis:**
```php
// Check if Redis is configured
$redis = Redis::connection();
$redis->ping(); // Should return "PONG"

// Check cache configuration
$cacheDriver = config('cache.default');
echo "Cache driver: " . $cacheDriver; // Should be 'redis'
```

**Solutions:**

1. **Configure Redis in .env**
   ```env
   CACHE_DRIVER=redis
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

2. **Install Redis PHP Extension**
   ```bash
   # Ubuntu/Debian
   sudo apt-get install php-redis
   
   # macOS
   brew install php-redis
   
   # Windows
   # Download from https://pecl.php.net/package/redis
   ```

3. **Enable Caching in TableBuilder**
   ```php
   // Set cache duration (seconds)
   $table->config(['cache_seconds' => 300]); // 5 minutes
   ```

### Issue: Wrong Database Connection

**Symptoms:**
- Table not found errors
- Data from wrong database
- Connection refused errors

**Solution:**
```php
// ❌ Wrong - using default connection
$table->model(User::class);

// ✅ Correct - specify connection
$table->connection('mysql_secondary');
$table->model(User::class);

// ✅ Or set on model
$table->model(User::on('mysql_secondary'));

// Reset to default connection
$table->resetConnection();
```

### Issue: Server-Side Processing Not Working

**Symptoms:**
- All data loads at once instead of paginated
- No AJAX requests in network tab
- Pagination doesn't work

**Solution:**

1. **Enable Server-Side Processing**
   ```php
   $table->setServerSide(true);
   ```

2. **Configure AJAX URL**
   ```php
   $table->config([
       'ajax_url' => route('users.datatable'),
       'server_side' => true
   ]);
   ```

3. **Create DataTable Endpoint**
   ```php
   // routes/web.php
   Route::get('/users/datatable', [UserController::class, 'datatable']);
   
   // UserController.php
   public function datatable(Request $request)
   {
       $table = new TableBuilder();
       $table->model(User::class);
       $table->setServerSide(true);
       return $table->ajax($request);
   }
   ```


---

## Data Display Issues

### Issue: Relationships Not Displaying

**Symptoms:**
- Foreign key IDs shown instead of related data
- Empty cells where related data should be
- "Trying to get property of non-object" errors

**Solution:**

1. **Define Relationship on Model**
   ```php
   // User.php
   public function posts()
   {
       return $this->hasMany(Post::class);
   }
   
   public function role()
   {
       return $this->belongsTo(Role::class);
   }
   ```

2. **Use relations() Method**
   ```php
   // ❌ Wrong - relationship not loaded
   $table->setFields(['id', 'name', 'role_id']);
   // Shows: 1, 2, 3 (role IDs)
   
   // ✅ Correct - load relationship
   $table->relations(User::class, 'role', 'name');
   // Shows: Admin, Editor, User (role names)
   ```

3. **Use fieldReplacementValue() for Direct Replacement**
   ```php
   // Replace role_id with role name
   $table->fieldReplacementValue(
       User::class,
       'role',
       'name',
       'Role',
       'role_id'
   );
   ```

### Issue: Formula Columns Not Calculating

**Symptoms:**
- Formula column shows empty or wrong values
- JavaScript errors in console
- Division by zero errors

**Solution:**

```php
// ❌ Wrong - invalid operators
$table->formula('total', 'Total', ['price', 'qty'], 'price ** qty');

// ✅ Correct - use valid operators
$table->formula('total', 'Total', ['price', 'qty'], 'price * qty');

// ✅ Handle division by zero
$table->formula(
    'average',
    'Average',
    ['total', 'count'],
    'count > 0 ? total / count : 0'
);

// ✅ Use parentheses for complex formulas
$table->formula(
    'profit',
    'Profit',
    ['revenue', 'cost', 'tax'],
    '(revenue - cost) * (1 - tax)'
);
```

### Issue: Conditional Formatting Not Applied

**Symptoms:**
- Styles not showing in table
- Conditions not evaluated
- All rows look the same

**Solution:**

```php
// ❌ Wrong - invalid operator
$table->columnCondition('status', 'cell', 'equals', 'active', 'css style', 'color: green');

// ✅ Correct - use valid operator
$table->columnCondition('status', 'cell', '==', 'active', 'css style', 'color: green');

// ✅ Multiple conditions
$table->columnCondition('amount', 'cell', '>', '1000', 'css style', 'color: green; font-weight: bold');
$table->columnCondition('amount', 'cell', '<', '100', 'css style', 'color: red');

// ✅ Row-level formatting
$table->columnCondition('status', 'row', '==', 'inactive', 'css style', 'background-color: #f0f0f0');
```

### Issue: Date/Number Formatting Not Working

**Symptoms:**
- Dates show as timestamps
- Numbers show without thousand separators
- Currency symbols missing

**Solution:**

```php
// ❌ Wrong - no formatting
$table->setFields(['id', 'amount', 'created_at']);
// Shows: 1, 1234567.89, 2024-01-15 10:30:00

// ✅ Correct - apply formatting
$table->format(['amount'], 2, ',', 'currency');
$table->format(['created_at'], 0, '', 'date');

// ✅ Multiple formats
$table->format(['price', 'total'], 2, ',', 'currency');
$table->format(['percentage'], 1, '.', 'percentage');
$table->format(['created_at', 'updated_at'], 0, '', 'date');
```


### Issue: Hidden Columns Still Visible

**Symptoms:**
- Columns marked as hidden still appear in table
- setHiddenColumns() not working

**Solution:**

```php
// ❌ Wrong - hidden columns not set before fields
$table->setFields(['id', 'name', 'email', 'password']);
$table->setHiddenColumns(['password']);
// Password column still visible

// ✅ Correct - set hidden columns after fields
$table->setFields(['id', 'name', 'email', 'password']);
$table->setHiddenColumns(['password']);
// Or better: don't include in setFields at all
$table->setFields(['id', 'name', 'email']);
```

### Issue: Action Buttons Not Showing

**Symptoms:**
- No edit/delete buttons in table
- Action column is empty
- Custom actions not appearing

**Solution:**

```php
// ❌ Wrong - actions disabled
$table->setActions(false);

// ✅ Correct - enable default actions
$table->setActions(true);

// ✅ Custom actions
$table->setActions([
    'view' => ['label' => 'View', 'icon' => 'eye', 'url' => '/users/{id}'],
    'edit' => ['label' => 'Edit', 'icon' => 'edit', 'url' => '/users/{id}/edit'],
    'delete' => ['label' => 'Delete', 'icon' => 'trash', 'method' => 'DELETE']
]);

// ✅ Remove specific buttons
$table->setActions(true);
$table->removeButtons(['delete']); // Show only view and edit
```

---

## Debugging Tips

### Enable Query Logging

```php
// Enable query logging
DB::enableQueryLog();

// Render table
$html = $table->render();

// Get all queries
$queries = DB::getQueryLog();

// Display queries
foreach ($queries as $i => $query) {
    echo "Query " . ($i + 1) . ": " . $query['query'] . "\n";
    echo "Bindings: " . json_encode($query['bindings']) . "\n";
    echo "Time: " . $query['time'] . "ms\n\n";
}

// Count queries
echo "Total queries: " . count($queries);
```

### Measure Performance

```php
// Measure execution time
$start = microtime(true);
$html = $table->render();
$duration = (microtime(true) - $start) * 1000;
echo "Execution time: " . round($duration, 2) . "ms\n";

// Measure memory usage
$memBefore = memory_get_usage(true);
$html = $table->render();
$memAfter = memory_get_usage(true);
$memUsed = ($memAfter - $memBefore) / 1024 / 1024;
echo "Memory used: " . round($memUsed, 2) . "MB\n";

// Peak memory
$peakMem = memory_get_peak_usage(true) / 1024 / 1024;
echo "Peak memory: " . round($peakMem, 2) . "MB\n";
```

### Debug Configuration

```php
// Dump current configuration
dd($table->getConfig());

// Check specific settings
echo "Server-side: " . ($table->isServerSide() ? 'Yes' : 'No') . "\n";
echo "Cache enabled: " . ($table->isCacheEnabled() ? 'Yes' : 'No') . "\n";
echo "Columns: " . implode(', ', $table->getColumns()) . "\n";
echo "Hidden columns: " . implode(', ', $table->getHiddenColumns()) . "\n";
```

### Validate Schema

```php
use Illuminate\Support\Facades\Schema;

// Check if table exists
if (!Schema::hasTable('users')) {
    echo "Table 'users' does not exist\n";
}

// Get all columns
$columns = Schema::getColumnListing('users');
echo "Available columns: " . implode(', ', $columns) . "\n";

// Check specific column
if (!Schema::hasColumn('users', 'email')) {
    echo "Column 'email' does not exist in 'users' table\n";
}

// Get column type
$type = Schema::getColumnType('users', 'email');
echo "Column 'email' type: " . $type . "\n";
```

### Test Cache

```php
use Illuminate\Support\Facades\Cache;

// Check if cache is working
Cache::put('test_key', 'test_value', 60);
$value = Cache::get('test_key');
echo "Cache test: " . ($value === 'test_value' ? 'Working' : 'Not working') . "\n";

// Check table cache
$cacheKey = $table->getCacheKey();
echo "Cache key: " . $cacheKey . "\n";

if (Cache::has($cacheKey)) {
    echo "Table data is cached\n";
    $ttl = Cache::get($cacheKey . ':ttl');
    echo "Cache expires in: " . $ttl . " seconds\n";
} else {
    echo "Table data is not cached\n";
}

// Clear table cache
Cache::forget($cacheKey);
echo "Cache cleared\n";
```


---

## Performance Optimization

### Optimization Checklist

Use this checklist to optimize table performance:

- [ ] **Enable caching** - Set cache duration for query results
- [ ] **Use server-side processing** - For datasets > 1000 rows
- [ ] **Enable eager loading** - Load relationships upfront
- [ ] **Select only required columns** - Don't use SELECT *
- [ ] **Use chunk processing** - For large datasets
- [ ] **Add database indexes** - On sorted/filtered columns
- [ ] **Optimize relationships** - Avoid N+1 queries
- [ ] **Limit initial load** - Show 10-50 rows initially
- [ ] **Use Redis caching** - Configure Redis for better performance
- [ ] **Minimize formulas** - Calculate in database when possible

### Quick Wins (5 minutes)

```php
// Before optimization
$table = new TableBuilder();
$table->model(User::class);
$table->setFields(['id', 'name', 'email', 'role_id', 'created_at']);
$table->render();

// After optimization (5 simple changes)
$table = new TableBuilder();
$table->model(User::select(['id', 'name', 'email', 'role_id', 'created_at'])); // 1. Select specific columns
$table->setFields(['id', 'name', 'email', 'role_id', 'created_at']);
$table->setServerSide(true); // 2. Enable server-side processing
$table->displayRowsLimitOnLoad(25); // 3. Limit initial load
$table->config(['cache_seconds' => 300]); // 4. Enable caching (5 minutes)
$table->relations(User::class, 'role', 'name'); // 5. Use eager loading for relationships
$table->render();

// Expected improvement: 50-70% faster
```

### Medium Optimizations (15 minutes)

```php
// 1. Add database indexes
Schema::table('users', function (Blueprint $table) {
    $table->index('email'); // For searching
    $table->index('created_at'); // For sorting
    $table->index('role_id'); // For filtering
});

// 2. Configure Redis caching
// .env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

// 3. Optimize query with scopes
class User extends Model
{
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    public function scopeWithRole($query)
    {
        return $query->with('role');
    }
}

// Use optimized query
$table->model(User::active()->withRole());

// Expected improvement: 70-85% faster
```

### Advanced Optimizations (30+ minutes)

```php
// 1. Implement query result caching
class UserRepository
{
    public function getTableData($filters = [])
    {
        $cacheKey = 'users_table_' . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, 300, function () use ($filters) {
            return User::with('role')
                ->when($filters['search'] ?? null, function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->select(['id', 'name', 'email', 'role_id', 'created_at'])
                ->get();
        });
    }
}

// 2. Use database views for complex queries
DB::statement('
    CREATE OR REPLACE VIEW users_with_stats AS
    SELECT 
        users.id,
        users.name,
        users.email,
        roles.name as role_name,
        COUNT(posts.id) as post_count
    FROM users
    LEFT JOIN roles ON users.role_id = roles.id
    LEFT JOIN posts ON users.id = posts.user_id
    GROUP BY users.id
');

// Use view in table
$table->model(DB::table('users_with_stats'));

// 3. Implement chunk processing for exports
$table->config([
    'chunk_size' => 100,
    'chunk_callback' => function ($chunk) {
        // Process each chunk
        foreach ($chunk as $row) {
            // Custom processing
        }
    }
]);

// Expected improvement: 85-95% faster
```

### Caching Strategy

```php
// Multi-layer caching strategy

// Layer 1: Query result caching (5 minutes)
$table->config(['cache_seconds' => 300]);

// Layer 2: Rendered HTML caching (10 minutes)
$cacheKey = 'table_html_' . md5($table->getCacheKey());
$html = Cache::remember($cacheKey, 600, function () use ($table) {
    return $table->render();
});

// Layer 3: Redis caching for relationships
class User extends Model
{
    public function role()
    {
        return Cache::remember('user_role_' . $this->id, 3600, function () {
            return $this->belongsTo(Role::class)->first();
        });
    }
}

// Cache invalidation on data change
class UserObserver
{
    public function updated(User $user)
    {
        // Clear user-specific cache
        Cache::forget('user_role_' . $user->id);
        
        // Clear table cache
        Cache::tags(['users_table'])->flush();
    }
}
```


### Database Optimization

```sql
-- Add indexes for commonly sorted/filtered columns
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_created_at ON users(created_at);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_role_id ON users(role_id);

-- Composite index for common filter combinations
CREATE INDEX idx_users_status_role ON users(status, role_id);

-- Full-text index for search
CREATE FULLTEXT INDEX idx_users_search ON users(name, email);

-- Analyze tables for query optimization
ANALYZE TABLE users;
ANALYZE TABLE roles;

-- Check query execution plan
EXPLAIN SELECT * FROM users WHERE email LIKE '%@example.com%';
```

### Memory Optimization

```php
// For very large datasets (100K+ rows)

// 1. Use cursor instead of get()
$table->config([
    'use_cursor' => true,
    'chunk_size' => 1000
]);

// 2. Stream results to response
return response()->stream(function () use ($table) {
    echo $table->renderHeader();
    
    $table->chunk(1000, function ($rows) {
        echo $table->renderRows($rows);
    });
    
    echo $table->renderFooter();
}, 200, [
    'Content-Type' => 'text/html',
    'X-Accel-Buffering' => 'no'
]);

// 3. Use generator for data processing
function getTableData()
{
    foreach (User::cursor() as $user) {
        yield $user;
    }
}

$table->setDataGenerator(getTableData());
```

### Frontend Optimization

```javascript
// 1. Lazy load DataTables
$(document).ready(function() {
    // Only initialize when table is visible
    if ($('#myTable').is(':visible')) {
        $('#myTable').DataTable({
            deferRender: true,
            processing: true,
            serverSide: true
        });
    }
});

// 2. Debounce search input
let searchTimeout;
$('#search').on('keyup', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        table.search($('#search').val()).draw();
    }, 500); // Wait 500ms after user stops typing
});

// 3. Use virtual scrolling for large datasets
$('#myTable').DataTable({
    scrollY: '500px',
    scrollCollapse: true,
    scroller: true,
    deferRender: true
});
```

### Monitoring and Profiling

```php
// 1. Log slow queries
DB::listen(function ($query) {
    if ($query->time > 1000) { // Queries taking > 1 second
        Log::warning('Slow query detected', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time . 'ms'
        ]);
    }
});

// 2. Profile table rendering
$profiler = new TableProfiler();
$profiler->start();

$html = $table->render();

$stats = $profiler->stop();
Log::info('Table render stats', [
    'duration' => $stats['duration'] . 'ms',
    'memory' => $stats['memory'] . 'MB',
    'queries' => $stats['query_count'],
    'cache_hits' => $stats['cache_hits'],
    'cache_misses' => $stats['cache_misses']
]);

// 3. Monitor cache hit ratio
$cacheStats = Cache::getStats();
$hitRatio = $cacheStats['hits'] / ($cacheStats['hits'] + $cacheStats['misses']) * 100;
echo "Cache hit ratio: " . round($hitRatio, 2) . "%\n";

// Target: > 80% cache hit ratio
if ($hitRatio < 80) {
    Log::warning('Low cache hit ratio', ['ratio' => $hitRatio]);
}
```

---

## Best Practices

### Security Best Practices

1. **Always validate column names**
   ```php
   // TableBuilder does this automatically
   $table->setFields(['id', 'name']); // Validated against schema
   ```

2. **Never trust user input**
   ```php
   // ❌ Wrong
   $table->orderby($_GET['sort']);
   
   // ✅ Correct
   $allowedColumns = ['id', 'name', 'email', 'created_at'];
   $sortColumn = in_array($_GET['sort'], $allowedColumns) ? $_GET['sort'] : 'id';
   $table->orderby($sortColumn);
   ```

3. **Use parameter binding**
   ```php
   // TableBuilder uses Query Builder with parameter binding automatically
   $table->where('status', '=', $userInput); // Safe
   ```

4. **Escape all output**
   ```php
   // TableBuilder escapes all output automatically
   // No need to manually escape
   ```

5. **Implement authorization**
   ```php
   // Check permissions before displaying table
   if (!auth()->user()->can('viewAny', User::class)) {
       abort(403);
   }
   
   // Filter data based on user permissions
   $table->model(User::where('company_id', auth()->user()->company_id));
   ```

### Performance Best Practices

1. **Use eager loading for relationships**
   ```php
   $table->relations(User::class, 'role', 'name');
   $table->relations(User::class, 'posts', 'count');
   // Both loaded in single query
   ```

2. **Enable caching for static data**
   ```php
   // Cache for 1 hour for rarely changing data
   $table->config(['cache_seconds' => 3600]);
   ```

3. **Use server-side processing for large datasets**
   ```php
   if ($rowCount > 1000) {
       $table->setServerSide(true);
   }
   ```

4. **Select only required columns**
   ```php
   $table->model(User::select(['id', 'name', 'email']));
   ```

5. **Add database indexes**
   ```php
   // In migration
   $table->index('email');
   $table->index(['status', 'role_id']);
   ```

### Code Organization Best Practices

1. **Use repository pattern**
   ```php
   class UserTableRepository
   {
       public function getTableBuilder(): TableBuilder
       {
           $table = new TableBuilder();
           $table->model(User::with('role'));
           $table->setFields(['id', 'name', 'email', 'role_id']);
           $table->setServerSide(true);
           $table->config(['cache_seconds' => 300]);
           return $table;
       }
   }
   ```

2. **Create reusable table configurations**
   ```php
   trait UserTableConfiguration
   {
       protected function configureUserTable(TableBuilder $table)
       {
           $table->setFields(['id', 'name', 'email', 'created_at']);
           $table->setActions(true);
           $table->setServerSide(true);
           $table->relations(User::class, 'role', 'name');
           return $table;
       }
   }
   ```

3. **Use service classes**
   ```php
   class TableService
   {
       public function createUserTable(array $options = []): string
       {
           $table = new TableBuilder();
           $this->applyDefaultConfiguration($table);
           $this->applyCustomConfiguration($table, $options);
           return $table->render();
       }
   }
   ```

---

## Getting Help

### Check Documentation

1. **API Documentation** - `.kiro/specs/canvastack-table-complete/API-DOCUMENTATION.md`
2. **Migration Guide** - `.kiro/specs/canvastack-table-complete/MIGRATION-GUIDE.md`
3. **Requirements** - `.kiro/specs/canvastack-table-complete/requirements.md`
4. **Design Document** - `.kiro/specs/canvastack-table-complete/design.md`

### Enable Debug Mode

```php
// In .env
APP_DEBUG=true
LOG_LEVEL=debug

// In code
$table->config(['debug' => true]);
$html = $table->render();
// Will output debug information to log
```

### Common Log Locations

- Laravel logs: `storage/logs/laravel.log`
- Query logs: Enable with `DB::enableQueryLog()`
- Cache logs: Check Redis logs if using Redis

### Report Issues

When reporting issues, include:

1. **Error message** - Full error message and stack trace
2. **Code snippet** - Minimal code that reproduces the issue
3. **Environment** - PHP version, Laravel version, database type
4. **Expected behavior** - What you expected to happen
5. **Actual behavior** - What actually happened
6. **Debug output** - Query logs, cache stats, profiling data

---

**Last Updated**: 2026-02-26  
**Version**: 1.0.0  
**Status**: Complete
