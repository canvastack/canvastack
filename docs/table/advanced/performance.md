# Performance Optimization

CanvaStack Table is designed for high performance, but with large datasets and complex requirements, optimization becomes crucial. This guide covers comprehensive performance optimization strategies.

## Table of Contents

- [Query Optimization](#query-optimization)
- [Caching Strategies](#caching-strategies)
- [Memory Management](#memory-management)
- [Database Optimization](#database-optimization)
- [Frontend Performance](#frontend-performance)
- [Server-Side Processing](#server-side-processing)
- [Monitoring and Profiling](#monitoring-and-profiling)
- [Best Practices](#best-practices)

## Query Optimization

### Efficient Query Building

Optimize database queries for better performance:

```php
public function index()
{
    $this->setPage();

    // Use server-side processing for large datasets
    if (User::count() > 1000) {
        $this->table->method('POST');
    }

    // Select only needed columns
    $this->table->setSelectColumns([
        'id', 'name', 'email', 'department_id', 'created_at'
    ]);

    // Use raw SQL for complex queries
    if (request()->has('complex_filter')) {
        $sql = "
            SELECT u.id, u.name, u.email, d.name as department_name,
                   COUNT(o.id) as orders_count
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            LEFT JOIN orders o ON u.id = o.user_id
            WHERE u.active = 1
            GROUP BY u.id, u.name, u.email, d.name
        ";
        $this->table->query($sql);
    } else {
        $this->table->relations($this->model, 'department', 'name');
    }

    $this->table->lists('users', [
        'name:Full Name',
        'email:Email',
        'department.name:Department',
        'created_at:Join Date'
    ]);

    return $this->render();
}
```

### Optimized Relationship Loading

Implement efficient relationship loading:

```php
public function index()
{
    $this->setPage();

    // Configure optimized eager loading
    $this->table->setOptimizedEagerLoading([
        'strategy' => 'selective', // selective, lazy, eager
        'relationships' => [
            'department' => [
                'select' => ['id', 'name', 'code'],
                'condition' => function($query) {
                    return $query->where('active', true);
                }
            ],
            'role' => [
                'select' => ['id', 'title', 'level'],
                'cache' => true,
                'cache_ttl' => 3600
            ]
        ],
        'batch_size' => 100,
        'lazy_load_threshold' => 50
    ]);

    // Use subqueries for aggregations instead of joins
    $this->table->setSubqueryAggregations([
        'orders_count' => [
            'query' => 'SELECT COUNT(*) FROM orders WHERE user_id = users.id',
            'cache' => true
        ],
        'total_spent' => [
            'query' => 'SELECT COALESCE(SUM(total), 0) FROM orders WHERE user_id = users.id AND status = "completed"',
            'format' => function($value) {
                return '$' . number_format($value, 2);
            }
        ]
    ]);

    $this->table->lists('users', [
        'name',
        'department.name:Department',
        'orders_count:Orders',
        'total_spent:Total Spent'
    ]);

    return $this->render();
}
```

### Query Result Optimization

Optimize query results processing:

```php
$this->table->setQueryOptimization([
    'chunk_size' => 1000,
    'memory_limit' => '512M',
    'execution_time_limit' => 300,
    'result_buffering' => false,
    'streaming' => true,
    'compression' => [
        'enabled' => true,
        'algorithm' => 'gzip',
        'level' => 6
    ]
]);
```

## Caching Strategies

### Multi-Level Caching

Implement comprehensive caching strategy:

```php
public function index()
{
    $this->setPage();

    // Configure multi-level caching
    $this->table->setCachingStrategy([
        'levels' => [
            'query_cache' => [
                'enabled' => true,
                'driver' => 'redis',
                'ttl' => 3600,
                'key_prefix' => 'table_query_',
                'invalidate_on' => ['user_updated', 'user_created', 'user_deleted']
            ],
            'result_cache' => [
                'enabled' => true,
                'driver' => 'memcached',
                'ttl' => 1800,
                'key_prefix' => 'table_result_',
                'compress' => true
            ],
            'metadata_cache' => [
                'enabled' => true,
                'driver' => 'file',
                'ttl' => 86400,
                'key_prefix' => 'table_meta_'
            ]
        ],
        'cache_warming' => [
            'enabled' => true,
            'schedule' => '0 */6 * * *', // Every 6 hours
            'popular_queries' => 10
        ]
    ]);

    $this->table->lists('users', ['name', 'email', 'created_at']);

    return $this->render();
}
```

### Smart Cache Invalidation

Implement intelligent cache invalidation:

```php
$this->table->setSmartCacheInvalidation([
    'strategies' => [
        'time_based' => [
            'ttl' => 3600,
            'refresh_threshold' => 0.8 // Refresh when 80% of TTL elapsed
        ],
        'event_based' => [
            'events' => [
                'user.created' => ['pattern' => 'users_*'],
                'user.updated' => ['pattern' => 'users_*', 'selective' => true],
                'user.deleted' => ['pattern' => 'users_*']
            ]
        ],
        'dependency_based' => [
            'dependencies' => [
                'users_table' => ['departments', 'roles'],
                'filters' => ['user_statuses', 'departments']
            ]
        ]
    ],
    'background_refresh' => true,
    'stale_while_revalidate' => true
]);
```

### Cache Partitioning

Partition cache for better performance:

```php
$this->table->setCachePartitioning([
    'enabled' => true,
    'partition_by' => [
        'user_id' => auth()->id(),
        'department' => auth()->user()->department_id,
        'role' => auth()->user()->roles->pluck('name')->implode(',')
    ],
    'partition_size' => 1000,
    'cleanup_strategy' => 'lru', // lru, lfu, fifo
    'max_partitions' => 100
]);
```

## Memory Management

### Memory-Efficient Processing

Optimize memory usage for large datasets:

```php
public function index()
{
    $this->setPage();

    // Configure memory management
    $this->table->setMemoryManagement([
        'chunk_processing' => [
            'enabled' => true,
            'chunk_size' => 1000,
            'memory_threshold' => '256M',
            'gc_frequency' => 100 // Run garbage collection every 100 chunks
        ],
        'streaming' => [
            'enabled' => true,
            'buffer_size' => 8192,
            'flush_threshold' => 50
        ],
        'object_pooling' => [
            'enabled' => true,
            'pool_size' => 100,
            'cleanup_interval' => 1000
        ]
    ]);

    // Use generators for large datasets
    $this->table->setDataGenerator(function($query) {
        foreach ($query->cursor() as $record) {
            yield $record;
        }
    });

    $this->table->method('POST'); // Essential for large datasets

    $this->table->lists('users', ['name', 'email', 'created_at']);

    return $this->render();
}
```

### Memory Monitoring

Monitor and control memory usage:

```php
$this->table->setMemoryMonitoring([
    'enabled' => true,
    'warning_threshold' => '400M',
    'critical_threshold' => '500M',
    'actions' => [
        'warning' => function($usage) {
            Log::warning("High memory usage: {$usage}");
            // Reduce chunk size
            $this->table->setChunkSize(500);
        },
        'critical' => function($usage) {
            Log::error("Critical memory usage: {$usage}");
            // Switch to streaming mode
            $this->table->enableStreaming();
        }
    ],
    'cleanup_strategies' => [
        'force_gc' => true,
        'clear_opcache' => false,
        'reduce_chunk_size' => true
    ]
]);
```

## Database Optimization

### Index Optimization

Ensure proper database indexing:

```php
// In migration files
Schema::table('users', function (Blueprint $table) {
    // Single column indexes for frequently filtered/sorted columns
    $table->index('created_at');
    $table->index('updated_at');
    $table->index('status');
    $table->index('department_id');
    
    // Composite indexes for multi-column operations
    $table->index(['department_id', 'status']);
    $table->index(['status', 'created_at']);
    $table->index(['department_id', 'created_at']);
    
    // Covering indexes for frequently accessed column combinations
    $table->index(['department_id', 'status', 'name', 'email']);
    
    // Partial indexes for specific conditions
    $table->index(['created_at'], 'users_active_created_at')
          ->where('status', 'active');
});

// Monitor index usage
$this->table->setIndexMonitoring([
    'enabled' => true,
    'log_unused_indexes' => true,
    'suggest_new_indexes' => true,
    'analyze_query_plans' => true
]);
```

### Query Plan Analysis

Analyze and optimize query execution plans:

```php
$this->table->setQueryPlanAnalysis([
    'enabled' => true,
    'log_slow_queries' => true,
    'slow_query_threshold' => 1000, // milliseconds
    'explain_queries' => true,
    'optimize_suggestions' => true,
    'auto_optimization' => [
        'enabled' => false, // Be careful with auto-optimization
        'confidence_threshold' => 0.9
    ]
]);
```

### Connection Optimization

Optimize database connections:

```php
// In config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        PDO::ATTR_PERSISTENT => true,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]) : [],
    
    // Connection pooling
    'pool' => [
        'min_connections' => 5,
        'max_connections' => 20,
        'idle_timeout' => 300,
        'validation_query' => 'SELECT 1'
    ]
],
```

## Frontend Performance

### JavaScript Optimization

Optimize client-side performance:

```php
public function index()
{
    $this->setPage();

    // Configure frontend optimization
    $this->table->setFrontendOptimization([
        'lazy_loading' => [
            'enabled' => true,
            'threshold' => 100, // pixels
            'placeholder' => 'Loading...'
        ],
        'virtual_scrolling' => [
            'enabled' => true,
            'row_height' => 40,
            'buffer_size' => 10,
            'overscan' => 5
        ],
        'debouncing' => [
            'search' => 300,
            'filter' => 500,
            'sort' => 100
        ],
        'compression' => [
            'enabled' => true,
            'algorithm' => 'gzip'
        ]
    ]);

    // Optimize DataTables configuration
    $this->table->setDataTablesOptimization([
        'deferRender' => true,
        'scroller' => [
            'enabled' => true,
            'displayBuffer' => 9,
            'boundaryScale' => 0.5
        ],
        'processing' => true,
        'serverSide' => true,
        'stateSave' => false, // Disable if not needed
        'searchDelay' => 300,
        'orderCellsTop' => true,
        'fixedHeader' => false // Disable if not needed
    ]);

    $this->table->method('POST');

    $this->table->lists('users', ['name', 'email', 'created_at']);

    return $this->render();
}
```

### Asset Optimization

Optimize CSS and JavaScript assets:

```php
$this->table->setAssetOptimization([
    'minification' => [
        'enabled' => true,
        'css' => true,
        'js' => true
    ],
    'compression' => [
        'enabled' => true,
        'level' => 6
    ],
    'caching' => [
        'enabled' => true,
        'ttl' => 86400,
        'versioning' => true
    ],
    'cdn' => [
        'enabled' => false,
        'base_url' => 'https://cdn.example.com'
    ],
    'preloading' => [
        'critical_css' => true,
        'fonts' => true,
        'images' => false
    ]
]);
```

## Server-Side Processing

### Optimized AJAX Processing

Optimize server-side AJAX processing:

```php
public function ajaxData(Request $request)
{
    // Optimize AJAX processing
    $this->table->setAjaxOptimization([
        'response_compression' => true,
        'json_optimization' => [
            'remove_null_values' => true,
            'compact_arrays' => true,
            'optimize_numbers' => true
        ],
        'caching' => [
            'enabled' => true,
            'ttl' => 300,
            'vary_by' => ['user_id', 'filters', 'sort']
        ],
        'pagination_optimization' => [
            'smart_counting' => true,
            'count_cache_ttl' => 1800,
            'estimate_large_counts' => true
        ]
    ]);

    // Use optimized query building
    $query = $this->buildOptimizedQuery($request);
    
    return $this->table->processAjaxRequest($query, $request);
}

private function buildOptimizedQuery($request)
{
    $query = User::query();
    
    // Apply filters efficiently
    if ($request->has('filters')) {
        $query = $this->applyFiltersOptimized($query, $request->filters);
    }
    
    // Apply sorting efficiently
    if ($request->has('order')) {
        $query = $this->applySortingOptimized($query, $request->order);
    }
    
    // Select only needed columns
    $query->select(['id', 'name', 'email', 'department_id', 'created_at']);
    
    return $query;
}
```

### Background Processing

Implement background processing for heavy operations:

```php
$this->table->setBackgroundProcessing([
    'enabled' => true,
    'queue' => 'table-processing',
    'operations' => [
        'large_exports' => [
            'threshold' => 10000,
            'job_class' => 'App\Jobs\TableExportJob'
        ],
        'complex_aggregations' => [
            'threshold' => 5000,
            'job_class' => 'App\Jobs\TableAggregationJob'
        ]
    ],
    'progress_tracking' => [
        'enabled' => true,
        'storage' => 'redis',
        'ttl' => 3600
    ]
]);
```

## Monitoring and Profiling

### Performance Monitoring

Monitor table performance in real-time:

```php
$this->table->setPerformanceMonitoring([
    'enabled' => true,
    'metrics' => [
        'query_time' => true,
        'memory_usage' => true,
        'cache_hit_rate' => true,
        'response_time' => true,
        'error_rate' => true
    ],
    'thresholds' => [
        'slow_query' => 1000, // milliseconds
        'high_memory' => '256M',
        'low_cache_hit_rate' => 0.8
    ],
    'alerts' => [
        'email' => ['admin@example.com'],
        'slack' => '#alerts',
        'log' => true
    ],
    'sampling_rate' => 0.1 // Monitor 10% of requests
]);
```

### Profiling Integration

Integrate with profiling tools:

```php
$this->table->setProfilingIntegration([
    'enabled' => app()->environment('local', 'staging'),
    'tools' => [
        'xdebug' => [
            'enabled' => extension_loaded('xdebug'),
            'profile_queries' => true,
            'profile_memory' => true
        ],
        'blackfire' => [
            'enabled' => class_exists('BlackfireProbe'),
            'auto_profile' => false,
            'profile_threshold' => 1000
        ],
        'telescope' => [
            'enabled' => class_exists('Laravel\Telescope\TelescopeServiceProvider'),
            'record_queries' => true,
            'record_cache' => true
        ]
    ]
]);
```

## Best Practices

### Performance Checklist

Implement performance best practices:

```php
class PerformanceOptimizedController extends Controller
{
    public function index()
    {
        $this->setPage();

        // 1. Use server-side processing for large datasets
        if ($this->shouldUseServerSide()) {
            $this->table->method('POST');
        }

        // 2. Implement proper caching
        $this->setupCaching();

        // 3. Optimize database queries
        $this->optimizeQueries();

        // 4. Configure memory management
        $this->setupMemoryManagement();

        // 5. Enable compression
        $this->enableCompression();

        // 6. Set up monitoring
        $this->setupMonitoring();

        $this->table->lists('users', [
            'name:Full Name',
            'email:Email',
            'created_at:Join Date'
        ]);

        return $this->render();
    }

    private function shouldUseServerSide()
    {
        $recordCount = User::count();
        $userRole = auth()->user()->role;
        
        // Use server-side for large datasets or specific roles
        return $recordCount > 1000 || in_array($userRole, ['admin', 'manager']);
    }

    private function setupCaching()
    {
        $this->table->setCaching([
            'enabled' => true,
            'ttl' => 3600,
            'tags' => ['users', 'departments'],
            'vary_by' => ['user_id', 'role']
        ]);
    }

    private function optimizeQueries()
    {
        // Select only needed columns
        $this->table->setSelectColumns(['id', 'name', 'email', 'created_at']);
        
        // Use efficient eager loading
        $this->table->setEagerLoading(['department:id,name']);
        
        // Apply query optimizations
        $this->table->setQueryOptimizations([
            'use_indexes' => true,
            'avoid_n_plus_one' => true,
            'optimize_joins' => true
        ]);
    }

    private function setupMemoryManagement()
    {
        $this->table->setMemoryManagement([
            'chunk_size' => 1000,
            'memory_limit' => '512M',
            'gc_frequency' => 100
        ]);
    }

    private function enableCompression()
    {
        $this->table->setCompression([
            'response' => true,
            'assets' => true,
            'cache' => true
        ]);
    }

    private function setupMonitoring()
    {
        $this->table->setMonitoring([
            'performance' => true,
            'errors' => true,
            'usage_patterns' => true
        ]);
    }
}
```

### Performance Testing

Implement performance testing:

```php
class TablePerformanceTest extends TestCase
{
    public function testLargeDatasetPerformance()
    {
        // Create test data
        User::factory()->count(10000)->create();

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Test table rendering
        $controller = new UserController();
        $response = $controller->index();

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        // Assert performance metrics
        $this->assertLessThan(2.0, $endTime - $startTime, 'Response time should be under 2 seconds');
        $this->assertLessThan(50 * 1024 * 1024, $endMemory - $startMemory, 'Memory usage should be under 50MB');
    }

    public function testCacheEffectiveness()
    {
        // First request (cache miss)
        $startTime = microtime(true);
        $controller = new UserController();
        $response1 = $controller->index();
        $firstRequestTime = microtime(true) - $startTime;

        // Second request (cache hit)
        $startTime = microtime(true);
        $response2 = $controller->index();
        $secondRequestTime = microtime(true) - $startTime;

        // Cache should make second request significantly faster
        $this->assertLessThan($firstRequestTime * 0.5, $secondRequestTime, 'Cached request should be at least 50% faster');
    }
}
```

---

## Related Documentation

- [Basic Usage](../basic-usage.md) - Basic table setup
- [Security Features](security.md) - Security considerations
- [Troubleshooting](troubleshooting.md) - Performance troubleshooting
- [API Reference](../api/objects.md) - Performance-related methods