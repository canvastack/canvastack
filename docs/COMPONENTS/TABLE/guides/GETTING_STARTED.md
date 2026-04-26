# Getting Started Guide

**Version:** 2.0.0  
**Difficulty:** Beginner  
**Time:** 15 minutes

---

## Prerequisites

- PHP 8.0 or higher
- Laravel 9.x or higher
- Composer
- Database (MySQL, PostgreSQL, SQLite, SQL Server)

---

## Installation

### 1. Install Package

```bash
composer require canvastack/canvastack
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --provider="Canvastack\Canvastack\CanvastackServiceProvider"
```

This will publish:
- `config/canvastack.cache.php` - Cache configuration
- `config/canvastack.datatables.php` - DataTables configuration
- `config/canvastack.templates.php` - Template configuration

### 3. Configure Database

Ensure your `.env` file has correct database settings:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Configure Cache (Optional)

For better performance, use Redis or Memcached:

```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## Basic Usage

### 1. Create Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Canvastack\Canvastack\Library\Components\Table\Craft\Datatables;

class UserController extends Controller
{
    public function datatables(Request $request)
    {
        $datatables = new Datatables();
        
        $result = $datatables->process(
            $request->all(),
            $this->getTableConfig(),
            $this->getFilters()
        );
        
        return response()->json($result);
    }
    
    protected function getTableConfig()
    {
        return (object) [
            'datatables' => (object) [
                'table_name' => 'users',
                'connection' => 'mysql',
                'columns' => ['id', 'name', 'email', 'created_at'],
            ],
        ];
    }
    
    protected function getFilters()
    {
        return [];
    }
}
```

### 2. Create Route

```php
// routes/web.php
Route::post('/users/datatables', [UserController::class, 'datatables'])
    ->name('users.datatables');
```

### 3. Create View

```html
<!-- resources/views/users/index.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Users</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
</head>
<body>
    <table id="users-table" class="display">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Created At</th>
            </tr>
        </thead>
    </table>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#users-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("users.datatables") }}',
                    type: 'POST',
                    data: function(d) {
                        d._token = '{{ csrf_token() }}';
                        d.difta = {
                            name: 'users',
                            connection: 'mysql'
                        };
                    }
                },
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'name', name: 'name' },
                    { data: 'email', name: 'email' },
                    { data: 'created_at', name: 'created_at' }
                ]
            });
        });
    </script>
</body>
</html>
```

---

## Enable Security Features

### 1. Update Configuration

```php
// config/canvastack.datatables.php
return [
    'security' => [
        'xss_protection' => true,
        'sql_injection_prevention' => true,
        'input_validation' => true,
        'log_security_events' => true,
    ],
];
```

### 2. Security is Automatic

All security features are automatically applied:
- XSS protection on all outputs
- SQL injection prevention on operators
- Input validation on all parameters
- Security event logging

---

## Enable Caching

### 1. Update Configuration

```php
// config/canvastack.cache.php
return [
    'enabled' => true,
    'store' => 'redis', // or 'memcached', 'file'
    'ttl' => 3600,
    
    'monitoring' => [
        'enabled' => true,
        'log_hits_misses' => true,
        'track_statistics' => true,
    ],
];
```

### 2. Warm Cache

```bash
# Warm cache for specific tables
php artisan canvastack:warm-cache --tables=users,posts

# Or configure automatic warming
```

```php
// config/canvastack.cache.php
'warming' => [
    'enabled' => true,
    'on_boot' => true,
    'scheduled' => true,
    'schedule' => '0 */6 * * *',
    'tables' => ['users', 'posts', 'comments'],
],
```

---

## Add Search Features

### 1. Enable Advanced Search

```php
// config/canvastack.datatables.php
return [
    'search' => [
        'global_search' => true,
        'wildcard_search' => true,
        'partial_matching' => true,
        'persist_search_state' => true,
        'search_history' => true,
        'highlight_results' => true,
    ],
];
```

### 2. Search is Automatic

Users can now use:
- Wildcard search: `john*` or `j?hn`
- Partial matching: `john` matches `johnson`
- Search history: Previous searches saved
- Highlighted results: Matches highlighted

---

## Add Action Buttons

### 1. Update Controller

```php
protected function getTableConfig()
{
    return (object) [
        'datatables' => (object) [
            'table_name' => 'users',
            'connection' => 'mysql',
            'columns' => ['id', 'name', 'email', 'created_at', 'actions'],
            'actions' => ['view', 'edit', 'delete'],
        ],
    ];
}
```

### 2. Update View

```javascript
columns: [
    { data: 'id', name: 'id' },
    { data: 'name', name: 'name' },
    { data: 'email', name: 'email' },
    { data: 'created_at', name: 'created_at' },
    { 
        data: 'actions', 
        name: 'actions',
        orderable: false,
        searchable: false
    }
]
```

---

## Add Relationships

### 1. Define Relationships in Model

```php
// app/Models/User.php
class User extends Model
{
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
    
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
```

### 2. Update Controller

```php
protected function getTableConfig()
{
    return (object) [
        'datatables' => (object) [
            'table_name' => 'users',
            'connection' => 'mysql',
            'columns' => ['id', 'name', 'email', 'profile.bio', 'created_at'],
            'relationships' => ['profile', 'posts'],
        ],
    ];
}
```

### 3. Enable Relationship Caching

```php
// config/canvastack.datatables.php
return [
    'relationships' => [
        'nested_eager_loading' => true,
        'lazy_loading_threshold' => 100,
        'relationship_cache_ttl' => 1800,
    ],
];
```

---

## Add Export Functionality

### 1. Enable Export

```php
// config/canvastack.datatables.php
return [
    'export' => [
        'enabled' => true,
        'formats' => ['csv', 'excel', 'pdf'],
        'max_rows' => 10000,
        'include_headers' => true,
    ],
];
```

### 2. Create Export Route

```php
// routes/web.php
Route::get('/users/export', [UserController::class, 'export'])
    ->name('users.export');
```

### 3. Create Export Method

```php
use Canvastack\Canvastack\Library\Components\Table\Craft\Export;

public function export(Request $request)
{
    $export = new Export();
    
    return $export->streamExport(
        'users',
        'csv',
        $this->getTableConfig(),
        $this->getFilters()
    );
}
```

---

## Enable Accessibility

### 1. Update Configuration

```php
// config/canvastack.datatables.php
return [
    'accessibility' => [
        'aria_enabled' => true,
        'add_aria_labels' => true,
        'add_aria_sort' => true,
        'add_captions' => true,
        'keyboard_navigation' => true,
        'screen_reader_support' => true,
        'focus_indicators' => true,
    ],
];
```

### 2. Accessibility is Automatic

All accessibility features are automatically applied:
- ARIA attributes on all elements
- Keyboard navigation support
- Screen reader announcements
- Focus indicators
- Table captions

---

## Monitor Performance

### 1. Enable Monitoring

```php
// config/canvastack.cache.php
return [
    'monitoring' => [
        'enabled' => true,
        'log_hits_misses' => true,
        'track_statistics' => true,
    ],
];

// config/canvastack.datatables.php
return [
    'performance' => [
        'log_slow_queries' => true,
        'slow_query_threshold' => 1000,
        'monitor_memory' => true,
    ],
    
    'development' => [
        'log_queries' => true,
        'log_cache_operations' => true,
        'log_performance_metrics' => true,
    ],
];
```

### 2. View Logs

```bash
# View security logs
tail -f storage/logs/laravel.log | grep "SECURITY"

# View cache logs
tail -f storage/logs/laravel.log | grep "Cache"

# View slow queries
tail -f storage/logs/laravel.log | grep "Slow query"
```

### 3. View Cache Statistics

```php
// Get today's cache statistics
$stats = Cache::get('cache_stats_' . date('Y-m-d'));

// Calculate hit rate
$hitRate = ($stats['hits'] / ($stats['hits'] + $stats['misses'])) * 100;
echo "Cache hit rate: {$hitRate}%";
```

---

## Testing

### 1. Run Tests

```bash
# Run all table tests
php artisan test tests/Unit/IntegratedFunctionsTest.php
php artisan test tests/Unit/CacheManagementTest.php
php artisan test tests/Unit/RelationshipsAdvancedTest.php

# Run specific test group
php artisan test --group=security
php artisan test --group=cache
```

### 2. Manual Testing

```php
// Test security features
$safe = canvastack_table_sanitize_search('<script>alert("XSS")</script>');
// Should return escaped HTML

// Test cache
$schema = canvastack_table_get_cached_schema('users');
// Should return cached schema

// Test validation
$operator = canvastack_table_validate_operator('=');
// Should return '='
```

---

## Next Steps

Now that you have a basic setup, explore:

1. **Advanced Features**
   - [Search & Filter](../features/SEARCH.md)
   - [Cache Management](../features/CACHE_MANAGEMENT.md)
   - [Export Features](../features/EXPORT.md)

2. **Configuration**
   - [Complete Configuration Guide](../CONFIGURATION.md)
   - [Security Configuration](../SECURITY.md)
   - [Performance Configuration](../PERFORMANCE.md)

3. **API Reference**
   - [Helper Functions](../api/HELPERS.md)
   - [Datatables Class](../api/DATATABLES.md)
   - [Builder Class](../api/BUILDER.md)

4. **Best Practices**
   - [Best Practices Guide](./BEST_PRACTICES.md)
   - [Troubleshooting](./TROUBLESHOOTING.md)

---

## Common Issues

### Cache Not Working

```bash
# Clear cache
php artisan cache:clear

# Check Redis connection
redis-cli ping
# Should return: PONG
```

### Slow Performance

```php
// Enable query optimization
'performance' => [
    'query_optimization' => true,
    'select_required_only' => true,
    'eager_loading' => true,
],

// Warm cache
php artisan canvastack:warm-cache
```

### Security Errors

```php
// Check security logs
tail -f storage/logs/laravel.log | grep "SECURITY"

// Verify configuration
config('canvastack.datatables.security.xss_protection'); // Should be true
```

---

## Support

- **Documentation:** `vendor/canvastack/canvastack/docs/COMPONENTS/TABLE/`
- **Issues:** Report on GitHub
- **Email:** support@canvastack.com

---

**Last Updated:** April 4, 2026  
**Maintained By:** CanvaStack Team
