<?php

/**
 * Canvastack Table Component Security Configuration
 * 
 * This file contains whitelisted tables, fields, and other security settings
 * to prevent SQL injection and other security vulnerabilities.
 * 
 * IMPORTANT: Add only trusted table and field names to these whitelists.
 * Any table or field not listed here will be rejected by security validation.
 * 
 * @package Canvastack\Table\Security
 * @version 1.0.0 - Phase 1 Security Implementation
 * @created 2024-12-19
 */

return [
    
    /*
    |--------------------------------------------------------------------------
    | Whitelisted Database Tables
    |--------------------------------------------------------------------------
    |
    | List all database tables that are allowed to be accessed by the
    | Canvastack Table Component. Only tables listed here will be permitted
    | in SQL queries.
    |
    */
    'allowed_tables' => [
        // Core system tables
        'users',
        'roles',
        'permissions',
        'role_user',
        'permission_role',
        
        // Application tables (add your application-specific tables here)
        'categories',
        'products',
        'orders',
        'order_items',
        'customers',
        'invoices',
        'payments',
        
        // System tables
        'settings',
        'configs',
        'logs',
        'audit_trails',
        
        // Example tables - REMOVE IN PRODUCTION
        'example_table',
        'test_data',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Whitelisted Database Fields
    |--------------------------------------------------------------------------
    |
    | List all database fields that are allowed to be used in WHERE clauses,
    | ORDER BY clauses, and other SQL operations. This provides an additional
    | layer of security beyond table validation.
    |
    */
    'allowed_fields' => [
        // Common fields across tables
        'id',
        'name',
        'title',
        'description',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'updated_by',
        
        // User-specific fields
        'email',
        'username',
        'first_name',
        'last_name',
        'phone',
        'address',
        'role_id',
        'is_active',
        
        // Business-specific fields
        'category_id',
        'product_id',
        'customer_id',
        'order_id',
        'amount',
        'quantity',
        'price',
        'total',
        'discount',
        'tax',
        
        // Metadata fields
        'slug',
        'sort_order',
        'parent_id',
        'level',
        'type',
        'code',
        'value',
        
        // Add your application-specific fields here
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Allowed SQL Operations
    |--------------------------------------------------------------------------
    |
    | Define which SQL operations are permitted in dynamic queries.
    | This helps prevent dangerous SQL operations from being executed.
    |
    */
    'allowed_operations' => [
        'SELECT',
        'COUNT',
        'SUM',
        'AVG',
        'MIN',
        'MAX',
        // Note: INSERT, UPDATE, DELETE operations are handled separately
        // and require additional authorization checks
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Allowed WHERE Operators
    |--------------------------------------------------------------------------
    |
    | Define which operators are allowed in WHERE clauses.
    | This prevents dangerous operators that could be used for SQL injection.
    |
    */
    'allowed_operators' => [
        '=',
        '!=',
        '<>',
        '>',
        '>=',
        '<',
        '<=',
        'LIKE',
        'NOT LIKE',
        'IN',
        'NOT IN',
        'BETWEEN',
        'NOT BETWEEN',
        'IS NULL',
        'IS NOT NULL',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Field Type Validation Rules
    |--------------------------------------------------------------------------
    |
    | Define validation rules for different field types to ensure
    | data integrity and prevent injection attacks.
    |
    */
    'field_validation_rules' => [
        'id' => 'integer|min:1',
        'email' => 'email|max:255',
        'name' => 'string|max:255',
        'title' => 'string|max:500',
        'description' => 'string|max:2000',
        'status' => 'in:active,inactive,pending,deleted',
        'created_at' => 'date',
        'updated_at' => 'date',
        'amount' => 'numeric|min:0',
        'quantity' => 'integer|min:0',
        'price' => 'numeric|min:0',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Security Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how security events should be logged for monitoring
    | and audit purposes.
    |
    */
    'security_logging' => [
        'enabled' => true,
        'log_channel' => 'security',
        'log_level' => 'warning',
        'log_context' => true,
        'log_ip_address' => true,
        'log_user_agent' => true,
        'log_user_id' => true,
        'max_context_length' => 1000,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | XSS Protection Settings
    |--------------------------------------------------------------------------
    |
    | Configure XSS protection settings for HTML output and JavaScript
    | generation.
    |
    */
    'xss_protection' => [
        'enabled' => true,
        'strict_mode' => true,
        'allowed_html_tags' => [],
        'allowed_html_attributes' => [],
        'escape_javascript_strings' => true,
        'validate_dom_ids' => true,
        'sanitize_urls' => true,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting to prevent abuse and brute force attacks.
    |
    */
    'rate_limiting' => [
        'enabled' => true,
        'max_requests_per_minute' => 60,
        'max_requests_per_hour' => 1000,
        'blacklist_threshold' => 10,
        'blacklist_duration_minutes' => 60,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Development Settings
    |--------------------------------------------------------------------------
    |
    | Settings that should only be enabled during development.
    | These MUST be disabled in production environments.
    |
    */
    'development' => [
        'allow_debug_mode' => env('APP_DEBUG', false),
        'show_sql_queries' => env('APP_DEBUG', false),
        'allow_test_tables' => env('APP_DEBUG', false),
        'verbose_error_messages' => env('APP_DEBUG', false),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Environment-Specific Overrides
    |--------------------------------------------------------------------------
    |
    | Allow certain settings to be overridden based on the application
    | environment (local, staging, production).
    |
    */
    'environment_overrides' => [
        'local' => [
            'security_logging.log_level' => 'debug',
            'rate_limiting.enabled' => false,
        ],
        'staging' => [
            'security_logging.log_level' => 'info',
            'rate_limiting.max_requests_per_minute' => 120,
        ],
        'production' => [
            'development.allow_debug_mode' => false,
            'development.show_sql_queries' => false,
            'development.allow_test_tables' => false,
            'development.verbose_error_messages' => false,
        ],
    ],
];