# Configuration

This guide covers all configuration options available in CanvaStack Table, from basic settings to advanced security configurations.

## Table of Contents

- [Basic Configuration](#basic-configuration)
- [Security Configuration](#security-configuration)
- [Performance Configuration](#performance-configuration)
- [UI Configuration](#ui-configuration)
- [Database Configuration](#database-configuration)
- [Asset Configuration](#asset-configuration)
- [Environment Variables](#environment-variables)
- [Runtime Configuration](#runtime-configuration)

## Basic Configuration

### Main Configuration File

The primary configuration file is `config/canvastack.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Table Configuration
    |--------------------------------------------------------------------------
    */
    'table' => [
        // Default HTTP method for DataTables requests
        'default_method' => env('CANVASTACK_DEFAULT_METHOD', 'GET'),
        
        // Enable server-side processing by default
        'server_side' => env('CANVASTACK_SERVER_SIDE', true),
        
        // Default pagination settings
        'pagination' => [
            'enabled' => true,
            'per_page' => 25,
            'options' => [10, 25, 50, 100, 250, 500, 1000, -1],
            'labels' => ['10', '25', '50', '100', '250', '500', '1000', 'Show All']
        ],
        
        // Search configuration
        'searching' => [
            'enabled' => true,
            'delay' => 1000, // milliseconds
            'min_length' => 1
        ],
        
        // Ordering configuration
        'ordering' => [
            'enabled' => true,
            'multi_column' => true
        ],
        
        // Responsive design
        'responsive' => [
            'enabled' => true,
            'breakpoints' => [
                'tablet' => 1024,
                'mobile' => 768
            ]
        ],
        
        // Default column configuration
        'columns' => [
            'auto_width' => false,
            'defer_render' => true,
            'processing' => true
        ],
        
        // Export buttons configuration
        'export' => [
            'enabled' => true,
            'buttons' => ['excel', 'csv', 'pdf', 'copy', 'print'],
            'filename' => null, // Auto-generate from table name
            'title' => null     // Auto-generate from page title
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'prefix' => env('CANVASTACK_ROUTE_PREFIX', 'canvastack'),
        'middleware' => ['web', 'auth'],
        'namespace' => 'Canvastack\\Canvastack\\Controllers',
        'as' => 'canvastack.'
    ],

    /*
    |--------------------------------------------------------------------------
    | View Configuration
    |--------------------------------------------------------------------------
    */
    'views' => [
        'namespace' => 'canvastack',
        'path' => resource_path('views/vendor/canvastack'),
        'theme' => env('CANVASTACK_THEME', 'default'),
        'layout' => env('CANVASTACK_LAYOUT', 'layouts.app')
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Configuration
    |--------------------------------------------------------------------------
    */
    'assets' => [
        'css_path' => 'vendor/canvastack/css',
        'js_path' => 'vendor/canvastack/js',
        'image_path' => 'vendor/canvastack/images',
        'version' => env('CANVASTACK_ASSET_VERSION', '1.0.0'),
        'cdn' => [
            'enabled' => env('CANVASTACK_CDN_ENABLED', false),
            'url' => env('CANVASTACK_CDN_URL', '')
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    */
    'database' => [
        'default_connection' => env('CANVASTACK_DB_CONNECTION', null),
        'query_timeout' => env('CANVASTACK_QUERY_TIMEOUT', 30),
        'max_records' => env('CANVASTACK_MAX_RECORDS', 10000),
        'chunk_size' => env('CANVASTACK_CHUNK_SIZE', 1000)
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('CANVASTACK_CACHE_ENABLED', true),
        'store' => env('CANVASTACK_CACHE_STORE', null),
        'ttl' => [
            'table_config' => 3600,    // 1 hour
            'filter_options' => 1800,  // 30 minutes
            'relationships' => 7200,   // 2 hours
            'metadata' => 86400        // 24 hours
        ],
        'tags' => [
            'tables' => 'canvastack_tables',
            'filters' => 'canvastack_filters',
            'relations' => 'canvastack_relations'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('CANVASTACK_LOGGING_ENABLED', true),
        'channel' => env('CANVASTACK_LOG_CHANNEL', 'daily'),
        'level' => env('CANVASTACK_LOG_LEVEL', 'info'),
        'queries' => env('CANVASTACK_LOG_QUERIES', false),
        'performance' => env('CANVASTACK_LOG_PERFORMANCE', false)
    ]
];
```

## Security Configuration

### Security Configuration File

The security configuration is in `config/canvastack-security.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Mode
    |--------------------------------------------------------------------------
    | Controls the overall security behavior
    | Options: 'full', 'hardened', 'basic', 'custom', 'disabled'
    */
    'mode' => env('CANVASTACK_SECURITY_MODE', 'hardened'),

    /*
    |--------------------------------------------------------------------------
    | Core Security Settings (Phase 1)
    |--------------------------------------------------------------------------
    */
    'core' => [
        'input_validation' => [
            'enabled' => true,
            'table_name_max_length' => 64,
            'column_name_max_length' => 64,
            'value_max_length' => [
                'string' => 255,
                'text' => 65535,
                'search' => 100,
                'filter' => 50
            ],
            'sql_injection_protection' => true,
            'xss_protection' => true,
            'path_traversal_protection' => true
        ],
        
        'parameter_binding' => [
            'enabled' => true,
            'force_prepared_statements' => true,
            'validate_field_names' => true,
            'validate_table_names' => true
        ],
        
        'output_encoding' => [
            'enabled' => true,
            'html_entities' => true,
            'json_escape_flags' => JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP,
            'javascript_encoding' => true
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Monitoring (Phase 2)
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => true,
        
        'input_validator' => [
            'enabled' => true,
            'log_violations' => true,
            'block_suspicious_patterns' => true,
            'whitelist_columns' => [
                // Add your custom column names here
                'custom_field_1',
                'custom_field_2'
            ]
        ],
        
        'security_middleware' => [
            'enabled' => true,
            'rate_limiting' => [
                'enabled' => true,
                'max_attempts' => 100, // per minute
                'strict_max_attempts' => 20, // for suspicious requests
                'decay_minutes' => 1
            ],
            'malicious_pattern_detection' => true,
            'user_agent_validation' => true,
            'geographic_analysis' => false
        ],
        
        'anomaly_detection' => [
            'enabled' => true,
            'accuracy_target' => 0.95,
            'false_positive_threshold' => 0.02,
            'behavioral_analysis' => true,
            'pattern_learning' => true
        ],
        
        'logging' => [
            'enabled' => true,
            'channels' => [
                'security' => 'daily',
                'security-alerts' => 'slack',
                'security-critical' => 'mail'
            ],
            'retention_days' => [
                'critical' => 365,
                'high' => 180,
                'medium' => 90,
                'low' => 30
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Advanced Features (Phase 3)
    |--------------------------------------------------------------------------
    */
    'advanced' => [
        'enabled' => env('CANVASTACK_ADVANCED_SECURITY', false),
        
        'content_security_policy' => [
            'enabled' => false,
            'security_level' => 'moderate', // 'strict', 'moderate', 'permissive'
            'nonce_based_scripts' => true,
            'violation_reporting' => true,
            'trusted_domains' => [
                'script-src' => [
                    'https://cdnjs.cloudflare.com',
                    'https://cdn.jsdelivr.net'
                ],
                'style-src' => [
                    'https://fonts.googleapis.com'
                ]
            ]
        ],
        
        'file_security' => [
            'enabled' => false,
            'allowed_extensions' => [
                'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt',
                'jpg', 'jpeg', 'png', 'gif', 'zip'
            ],
            'max_file_size' => '10MB',
            'virus_scanning' => [
                'enabled' => false,
                'engine' => 'clamav'
            ]
        ],
        
        'data_encryption' => [
            'enabled' => false,
            'algorithm' => 'AES-256-GCM',
            'key_rotation_days' => 90
        ],
        
        'access_control' => [
            'mode' => 'disabled', // 'disabled', 'basic', 'rbac', 'abac'
            'cache_ttl' => 30, // minutes
            'role_hierarchy' => [
                'super_admin' => ['admin', 'manager', 'user', 'guest'],
                'admin' => ['manager', 'user', 'guest'],
                'manager' => ['user', 'guest'],
                'user' => ['guest']
            ]
        ]
    ]
];
```

### Security Presets

You can use predefined security presets:

```php
// High security for sensitive applications
'mode' => 'full',

// Balanced security for most applications
'mode' => 'hardened',

// Basic security for internal tools
'mode' => 'basic',

// Custom configuration
'mode' => 'custom',

// Disable security (NOT RECOMMENDED)
'mode' => 'disabled'
```

## Performance Configuration

### Query Optimization

```php
'performance' => [
    'query_optimization' => [
        'enabled' => true,
        'use_indexes' => true,
        'optimize_joins' => true,
        'limit_subqueries' => true,
        'cache_query_plans' => true
    ],
    
    'memory_management' => [
        'enabled' => true,
        'max_memory_usage' => '256M',
        'chunk_processing' => true,
        'garbage_collection' => true
    ],
    
    'caching' => [
        'enabled' => true,
        'store' => null, // use default cache store
        'ttl' => [
            'permissions' => 1800,     // 30 minutes
            'validation_rules' => 3600, // 1 hour
            'table_metadata' => 86400   // 24 hours
        ]
    ],
    
    'optimization' => [
        'lazy_loading' => true,
        'batch_processing' => true,
        'async_loading' => false,
        'compression' => [
            'enabled' => false,
            'algorithm' => 'gzip'
        ]
    ]
]
```

### Database Connection Pooling

```php
'database' => [
    'connections' => [
        'primary' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_TIMEOUT => 30
            ]
        ],
        
        'reports' => [
            'driver' => 'mysql',
            'host' => env('REPORTS_DB_HOST', '127.0.0.1'),
            'database' => env('REPORTS_DB_DATABASE', 'reports'),
            'username' => env('REPORTS_DB_USERNAME', 'forge'),
            'password' => env('REPORTS_DB_PASSWORD', ''),
            'read' => [
                'host' => [
                    env('REPORTS_DB_READ_HOST_1', '127.0.0.1'),
                    env('REPORTS_DB_READ_HOST_2', '127.0.0.1')
                ]
            ]
        ]
    ]
]
```

## UI Configuration

### Theme Configuration

```php
'ui' => [
    'theme' => [
        'name' => env('CANVASTACK_THEME', 'default'),
        'path' => 'themes',
        'custom_css' => env('CANVASTACK_CUSTOM_CSS', ''),
        'custom_js' => env('CANVASTACK_CUSTOM_JS', '')
    ],
    
    'table' => [
        'default_classes' => 'table table-striped table-hover',
        'container_classes' => 'table-responsive',
        'header_classes' => 'thead-dark',
        'row_classes' => '',
        'cell_classes' => ''
    ],
    
    'buttons' => [
        'default_classes' => 'btn btn-sm',
        'primary_classes' => 'btn btn-primary btn-sm',
        'secondary_classes' => 'btn btn-secondary btn-sm',
        'success_classes' => 'btn btn-success btn-sm',
        'danger_classes' => 'btn btn-danger btn-sm',
        'warning_classes' => 'btn btn-warning btn-sm',
        'info_classes' => 'btn btn-info btn-sm'
    ],
    
    'icons' => [
        'view' => 'fas fa-eye',
        'edit' => 'fas fa-edit',
        'delete' => 'fas fa-trash',
        'add' => 'fas fa-plus',
        'search' => 'fas fa-search',
        'filter' => 'fas fa-filter',
        'export' => 'fas fa-download',
        'print' => 'fas fa-print'
    ],
    
    'modal' => [
        'filter_modal_size' => 'modal-lg',
        'confirm_modal_size' => 'modal-sm',
        'backdrop' => 'static',
        'keyboard' => false
    ]
]
```

### Responsive Configuration

```php
'responsive' => [
    'enabled' => true,
    'breakpoints' => [
        'xs' => 0,
        'sm' => 576,
        'md' => 768,
        'lg' => 992,
        'xl' => 1200,
        'xxl' => 1400
    ],
    'column_priorities' => [
        'id' => 1,
        'name' => 2,
        'email' => 3,
        'created_at' => 4,
        'actions' => 5
    ],
    'hidden_columns' => [
        'mobile' => ['created_at', 'updated_at'],
        'tablet' => ['updated_at']
    ]
]
```

## Database Configuration

### Connection Configuration

```php
'database' => [
    'connections' => [
        'canvastack' => [
            'driver' => env('CANVASTACK_DB_DRIVER', 'mysql'),
            'host' => env('CANVASTACK_DB_HOST', '127.0.0.1'),
            'port' => env('CANVASTACK_DB_PORT', '3306'),
            'database' => env('CANVASTACK_DB_DATABASE', 'canvastack'),
            'username' => env('CANVASTACK_DB_USERNAME', 'root'),
            'password' => env('CANVASTACK_DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => env('CANVASTACK_DB_PREFIX', ''),
            'strict' => true,
            'engine' => null,
            'options' => [
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
            ]
        ]
    ],
    
    'query_settings' => [
        'timeout' => 30,
        'retry_attempts' => 3,
        'retry_delay' => 1000, // milliseconds
        'max_execution_time' => 60,
        'memory_limit' => '256M'
    ],
    
    'optimization' => [
        'use_prepared_statements' => true,
        'enable_query_cache' => true,
        'optimize_joins' => true,
        'use_indexes' => true,
        'limit_result_sets' => true
    ]
]
```

### Migration Configuration

```php
'migrations' => [
    'table' => 'canvastack_migrations',
    'path' => database_path('migrations/canvastack'),
    'auto_run' => env('CANVASTACK_AUTO_MIGRATE', false),
    'backup_before_migrate' => true
]
```

## Asset Configuration

### CSS and JavaScript

```php
'assets' => [
    'css' => [
        'datatables' => 'vendor/datatables/datatables.min.css',
        'canvastack' => 'vendor/canvastack/css/canvastack.min.css',
        'theme' => 'vendor/canvastack/themes/default.css',
        'custom' => env('CANVASTACK_CUSTOM_CSS', '')
    ],
    
    'js' => [
        'jquery' => 'vendor/jquery/jquery.min.js',
        'datatables' => 'vendor/datatables/datatables.min.js',
        'canvastack' => 'vendor/canvastack/js/canvastack.min.js',
        'custom' => env('CANVASTACK_CUSTOM_JS', '')
    ],
    
    'images' => [
        'path' => 'vendor/canvastack/images',
        'placeholder' => 'placeholder.png',
        'loading' => 'loading.gif',
        'error' => 'error.png'
    ],
    
    'cdn' => [
        'enabled' => env('CANVASTACK_CDN_ENABLED', false),
        'base_url' => env('CANVASTACK_CDN_URL', ''),
        'version' => env('CANVASTACK_ASSET_VERSION', '1.0.0'),
        'fallback' => true
    ]
]
```

### Build Configuration

```php
'build' => [
    'minify' => env('CANVASTACK_MINIFY_ASSETS', true),
    'combine' => env('CANVASTACK_COMBINE_ASSETS', true),
    'version' => env('CANVASTACK_VERSION_ASSETS', true),
    'cache_bust' => env('CANVASTACK_CACHE_BUST', true),
    'source_maps' => env('CANVASTACK_SOURCE_MAPS', false)
]
```

## Environment Variables

### Required Variables

```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# CanvaStack Configuration
CANVASTACK_SECURITY_MODE=hardened
CANVASTACK_DEFAULT_METHOD=POST
CANVASTACK_SERVER_SIDE=true
```

### Optional Variables

```env
# Performance
CANVASTACK_CACHE_ENABLED=true
CANVASTACK_CACHE_STORE=redis
CANVASTACK_QUERY_TIMEOUT=30
CANVASTACK_MAX_RECORDS=10000

# Security
CANVASTACK_ADVANCED_SECURITY=false
CANVASTACK_LOG_SECURITY_EVENTS=true
CANVASTACK_RATE_LIMIT_ENABLED=true

# UI/UX
CANVASTACK_THEME=default
CANVASTACK_RESPONSIVE=true
CANVASTACK_CUSTOM_CSS=
CANVASTACK_CUSTOM_JS=

# Assets
CANVASTACK_CDN_ENABLED=false
CANVASTACK_CDN_URL=
CANVASTACK_ASSET_VERSION=1.0.0
CANVASTACK_MINIFY_ASSETS=true

# Logging
CANVASTACK_LOGGING_ENABLED=true
CANVASTACK_LOG_CHANNEL=daily
CANVASTACK_LOG_LEVEL=info
CANVASTACK_LOG_QUERIES=false

# Routes
CANVASTACK_ROUTE_PREFIX=canvastack
CANVASTACK_LAYOUT=layouts.app

# Development
CANVASTACK_DEBUG_MODE=false
CANVASTACK_SOURCE_MAPS=false
```

## Runtime Configuration

### Dynamic Configuration

You can override configuration at runtime:

```php
// In your controller
public function index()
{
    // Override default method
    config(['canvastack.table.default_method' => 'POST']);
    
    // Override security settings
    config(['canvastack-security.mode' => 'basic']);
    
    // Override UI settings
    config(['canvastack.ui.theme.name' => 'dark']);
    
    $this->table->lists('users', ['name', 'email']);
    
    return $this->render();
}
```

### Per-Table Configuration

```php
// Configure specific table instance
$this->table->setConfig([
    'server_side' => true,
    'pagination' => ['per_page' => 50],
    'searching' => ['delay' => 500],
    'export' => ['buttons' => ['excel', 'pdf']]
]);
```

### Conditional Configuration

```php
// Environment-based configuration
if (app()->environment('production')) {
    config(['canvastack-security.mode' => 'full']);
    config(['canvastack.logging.level' => 'warning']);
} else {
    config(['canvastack-security.mode' => 'basic']);
    config(['canvastack.logging.level' => 'debug']);
}

// User-based configuration
if (auth()->user()->isAdmin()) {
    config(['canvastack.table.export.enabled' => true]);
} else {
    config(['canvastack.table.export.enabled' => false]);
}
```

## Configuration Validation

### Validate Configuration

```php
// In a service provider or middleware
public function validateConfiguration()
{
    $requiredConfigs = [
        'canvastack.table.default_method',
        'canvastack-security.mode',
        'canvastack.database.default_connection'
    ];
    
    foreach ($requiredConfigs as $config) {
        if (is_null(config($config))) {
            throw new \Exception("Required configuration missing: {$config}");
        }
    }
}
```

### Configuration Testing

```php
// Test configuration in your tests
public function test_configuration_is_valid()
{
    $this->assertNotNull(config('canvastack.table.default_method'));
    $this->assertContains(config('canvastack-security.mode'), [
        'full', 'hardened', 'basic', 'custom', 'disabled'
    ]);
}
```

## Best Practices

### 1. Environment-Specific Configuration

```php
// Use different configurations for different environments
if (app()->environment('production')) {
    // Production settings
    config(['canvastack-security.mode' => 'full']);
    config(['canvastack.performance.caching.enabled' => true]);
} elseif (app()->environment('staging')) {
    // Staging settings
    config(['canvastack-security.mode' => 'hardened']);
} else {
    // Development settings
    config(['canvastack-security.mode' => 'basic']);
    config(['canvastack.logging.queries' => true]);
}
```

### 2. Security Configuration

```php
// Always use appropriate security level
'security_mode' => env('CANVASTACK_SECURITY_MODE', 'hardened'),

// Enable logging in production
'logging' => [
    'enabled' => !app()->environment('testing'),
    'level' => app()->environment('production') ? 'warning' : 'debug'
]
```

### 3. Performance Configuration

```php
// Enable caching in production
'cache' => [
    'enabled' => !app()->environment('local'),
    'store' => app()->environment('production') ? 'redis' : 'file'
]
```

## Troubleshooting Configuration

### Common Issues

1. **Configuration not loading**: Check file permissions and syntax
2. **Environment variables not working**: Verify `.env` file and `php artisan config:cache`
3. **Security settings too strict**: Adjust security mode or whitelist columns
4. **Performance issues**: Enable caching and optimize database connections

### Debug Configuration

```php
// Debug current configuration
dd(config('canvastack'));
dd(config('canvastack-security'));

// Check specific configuration
if (config('canvastack.table.server_side')) {
    // Server-side processing enabled
}
```

---

## Related Documentation

- [Installation & Setup](installation.md) - Initial setup and configuration
- [Security Features](advanced/security.md) - Detailed security configuration
- [Performance Optimization](advanced/performance.md) - Performance tuning
- [API Reference](api/objects.md) - Runtime configuration methods