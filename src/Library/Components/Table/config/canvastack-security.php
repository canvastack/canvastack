<?php

/**
 * Canvastack Security Configuration
 * 
 * Comprehensive configuration for all security components
 * Supports modular operation and custom integrations
 */

return [
    
    /*
    |--------------------------------------------------------------------------
    | Security Mode
    |--------------------------------------------------------------------------
    |
    | Controls the overall security behavior of Canvastack Tables
    | 
    | Options:
    | - 'full': All security features enabled (Phase 1-3)
    | - 'hardened': Core security + monitoring (Phase 1-2) 
    | - 'basic': Core security only (Phase 1)
    | - 'custom': Use custom configuration
    | - 'disabled': Disable all security features (NOT RECOMMENDED)
    |
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
                'security-alerts' => 'slack', // or 'mail', 'database'
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
                ],
                'font-src' => [
                    'https://fonts.gstatic.com'
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
                'engine' => 'clamav',
                'endpoint' => 'http://localhost:3310'
            ],
            'quarantine' => [
                'enabled' => true,
                'auto_delete_days' => 30,
                'storage_disk' => 'quarantine'
            ],
            'integrity_checks' => true
        ],
        
        'data_encryption' => [
            'enabled' => false,
            'algorithm' => 'AES-256-GCM',
            'key_rotation_days' => 90,
            'field_level_encryption' => [
                'enabled' => true,
                'sensitivity_mapping' => [
                    'email' => 'confidential',
                    'phone' => 'confidential',
                    'ssn' => 'secret',
                    'salary' => 'restricted'
                ]
            ]
        ],
        
        'access_control' => [
            'mode' => 'disabled', // 'disabled', 'basic', 'rbac', 'abac', 'hybrid', 'custom'
            'cache_ttl' => 30, // minutes
            
            'role_hierarchy' => [
                'super_admin' => ['admin', 'manager', 'user', 'guest'],
                'admin' => ['manager', 'user', 'guest'],
                'manager' => ['user', 'guest'],
                'user' => ['guest'],
                'guest' => []
            ],
            
            'table_permissions' => [
                // 'users' => [
                //     'super_admin' => ['read', 'write', 'delete', 'export', 'admin'],
                //     'admin' => ['read', 'write', 'export'],
                //     'manager' => ['read', 'export'],
                //     'user' => ['read'],
                //     'guest' => []
                // ]
            ],
            
            'auth_provider' => null, // YourCustomAuthProvider::class
            
            'abac_rules' => [
                'department_access' => false,
                'time_restrictions' => false,
                'location_restrictions' => false,
                'data_sensitivity' => false,
                'project_access' => false
            ]
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    */
    'integration' => [
        'laravel_auth' => [
            'enabled' => true,
            'user_model' => App\Models\User::class,
            'role_method' => 'getRoles', // method on user model to get roles
            'permissions_method' => 'getPermissions' // method to get permissions
        ],
        
        'custom_auth' => [
            'enabled' => false,
            'provider_class' => null, // YourCustomAuthProvider::class
            'user_resolver' => null, // callable to resolve current user
            'permission_resolver' => null // callable to check permissions
        ],
        
        'database' => [
            'connection' => null, // use default connection
            'tables' => [
                'users' => 'users',
                'roles' => 'roles',
                'permissions' => 'permissions',
                'encryption_keys' => 'canvastack_encryption_keys',
                'audit_trail' => 'canvastack_audit_trail',
                'quarantine' => 'canvastack_quarantine'
            ]
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'caching' => [
            'enabled' => true,
            'store' => null, // use default cache store
            'ttl' => [
                'permissions' => 1800, // 30 minutes
                'validation_rules' => 3600, // 1 hour
                'encryption_keys' => 86400, // 24 hours
            ]
        ],
        
        'optimization' => [
            'lazy_loading' => true,
            'batch_processing' => true,
            'async_logging' => false,
            'compression' => [
                'enabled' => false,
                'algorithm' => 'gzip'
            ]
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'channels' => [
            'email' => [
                'enabled' => true,
                'recipients' => [
                    'critical' => ['security@company.com', 'cto@company.com'],
                    'high' => ['security@company.com'],
                    'medium' => ['security@company.com'],
                    'low' => ['security@company.com']
                ]
            ],
            
            'slack' => [
                'enabled' => false,
                'webhook_url' => env('CANVASTACK_SLACK_WEBHOOK'),
                'channel' => '#security-alerts'
            ],
            
            'sms' => [
                'enabled' => false,
                'provider' => 'twilio', // or 'nexmo'
                'recipients' => [
                    'critical' => ['+1234567890']
                ]
            ]
        ],
        
        'throttling' => [
            'enabled' => true,
            'max_alerts_per_hour' => [
                'critical' => 10,
                'high' => 5,
                'medium' => 3,
                'low' => 1
            ]
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Development Settings
    |--------------------------------------------------------------------------
    */
    'development' => [
        'debug_mode' => env('APP_DEBUG', false),
        'log_all_queries' => false,
        'simulate_attacks' => false, // for testing security features
        'bypass_security' => false, // NEVER enable in production
        'profiling' => [
            'enabled' => false,
            'log_performance' => false,
            'memory_tracking' => false
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Modular Configuration Examples
    |--------------------------------------------------------------------------
    |
    | Example configurations for different use cases:
    |
    */
    'presets' => [
        // Standalone mode (no user management integration)
        'standalone' => [
            'mode' => 'hardened',
            'advanced.access_control.mode' => 'disabled',
            'integration.laravel_auth.enabled' => false,
            'core' => ['*' => true],
            'monitoring' => ['enabled' => true]
        ],
        
        // High security mode (all features enabled)
        'high_security' => [
            'mode' => 'full',
            'advanced.enabled' => true,
            'advanced.content_security_policy.enabled' => true,
            'advanced.content_security_policy.security_level' => 'strict',
            'advanced.file_security.enabled' => true,
            'advanced.data_encryption.enabled' => true,
            'advanced.access_control.mode' => 'hybrid'
        ],
        
        // Custom integration mode
        'custom_auth' => [
            'mode' => 'full',
            'advanced.access_control.mode' => 'custom',
            'integration.custom_auth.enabled' => true,
            'integration.laravel_auth.enabled' => false
        ],
        
        // Performance optimized mode
        'performance' => [
            'mode' => 'hardened',
            'advanced.enabled' => false,
            'performance.caching.enabled' => true,
            'performance.optimization.lazy_loading' => true,
            'monitoring.anomaly_detection.enabled' => false
        ]
    ]
];