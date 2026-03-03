<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Configuration
    |--------------------------------------------------------------------------
    |
    | Core application settings merged from canvas.settings.php
    |
    */
    'app' => [
        'name' => env('CANVASTACK_APP_NAME', 'CanvaStack'),
        'description' => env('CANVASTACK_APP_DESC', 'CanvaStack Application'),
        'version' => '1.0.0',
        'base_url' => env('APP_URL', 'http://localhost'),
        'index_folder' => 'public',
        'lang' => env('APP_LOCALE', 'en'),
        'charset' => 'UTF-8',
        'maintenance' => env('CANVASTACK_MAINTENANCE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform Configuration
    |--------------------------------------------------------------------------
    |
    | Multi-platform support settings
    |
    */
    'platform' => [
        'type' => env('CANVASTACK_PLATFORM_TYPE', 'single'), // 'single' or 'multiple'
        'table' => env('CANVASTACK_PLATFORM_TABLE', null),
        'key' => env('CANVASTACK_PLATFORM_KEY', null),
        'name' => env('CANVASTACK_PLATFORM_NAME', null),
        'label' => env('CANVASTACK_PLATFORM_LABEL', null),
        'route' => env('CANVASTACK_PLATFORM_ROUTE', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Additional database sources merged from canvas.connections.php
    |
    */
    'database' => [
        'sources' => [
            // Additional database connections can be defined here
            // Example:
            // 'mysql_secondary' => [
            //     'label' => 'Secondary Database',
            //     'connection_name' => 'mysql_secondary',
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Caching strategy for various components
    |
    */
    'cache' => [
        'enabled' => env('CANVASTACK_CACHE_ENABLED', true),
        'driver' => env('CANVASTACK_CACHE_DRIVER', 'redis'),
        'ttl' => [
            'forms' => 3600,        // 1 hour
            'tables' => 300,        // 5 minutes
            'permissions' => 3600,  // 1 hour
            'views' => 3600,        // 1 hour
            'queries' => 300,       // 5 minutes
        ],
        'tags' => [
            'forms' => 'canvastack:forms',
            'tables' => 'canvastack:tables',
            'permissions' => 'canvastack:permissions',
            'views' => 'canvastack:views',
        ],
        
        // Filter options caching configuration
        'filter_options' => [
            'enabled' => env('CANVASTACK_FILTER_CACHE_ENABLED', true),
            'ttl' => env('CANVASTACK_FILTER_CACHE_TTL', 300), // 5 minutes
            'driver' => env('CANVASTACK_FILTER_CACHE_DRIVER', null), // null = use default
            'prefix' => 'filter_options',
            'tags' => ['canvastack:filters'],
            
            // Cache invalidation settings
            'auto_invalidate' => [
                'enabled' => env('CANVASTACK_FILTER_AUTO_INVALIDATE', true),
                'events' => ['created', 'updated', 'deleted'], // Model events that trigger cache clear
            ],
            
            // Cache warming settings
            'warming' => [
                'enabled' => env('CANVASTACK_FILTER_CACHE_WARMING', false),
                'schedule' => '0 */6 * * *', // Every 6 hours (cron format)
                'batch_size' => 10, // Number of tables to warm at once
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Performance optimization settings
    |
    */
    'performance' => [
        'chunk_size' => 100,
        'eager_load' => true,
        'query_cache' => true,
        'lazy_load_components' => true,
        'optimize_queries' => true,
        
        // Filter-specific performance settings
        'filter_optimization' => env('CANVASTACK_FILTER_OPTIMIZATION', true),
        'max_filter_options' => env('CANVASTACK_MAX_FILTER_OPTIONS', 1000),
        'filter_query_timeout' => env('CANVASTACK_FILTER_QUERY_TIMEOUT', 30), // seconds
        'filter_memory_limit' => env('CANVASTACK_FILTER_MEMORY_LIMIT', '128M'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Configuration
    |--------------------------------------------------------------------------
    |
    | TableBuilder and filter-related settings
    |
    */
    'table' => [
        'filters' => [
            // Enable bi-directional cascade globally
            'bidirectional_cascade' => env('CANVASTACK_BIDIRECTIONAL_CASCADE', false),
            
            // Debounce delay for filter changes (ms) - Task 3.1
            'debounce_delay' => env('CANVASTACK_FILTER_DEBOUNCE', 300),
            
            // Frontend cache TTL (seconds)
            'frontend_cache_ttl' => env('CANVASTACK_FILTER_CACHE_TTL', 300),
            
            // Max cascade depth (prevent infinite loops)
            'max_cascade_depth' => env('CANVASTACK_MAX_CASCADE_DEPTH', 10),
            
            // Show cascade indicators
            'show_cascade_indicators' => env('CANVASTACK_SHOW_CASCADE_INDICATORS', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Registration
    |--------------------------------------------------------------------------
    |
    | Registered plugins/modules merged from canvas.registers.php
    |
    */
    'modules' => [
        'plugins' => [
            'MetaTags',
            'Template',
            'Form',
            'Table',
            'Chart',
            'Email',
        ],
        'auto_discover' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | User Configuration
    |--------------------------------------------------------------------------
    |
    | User-related settings
    |
    */
    'user' => [
        'group_alias_key' => 'region',
        'group_alias_field' => 'group_alias',
        'alias_label' => 'Group Location',
        'alias_placeholder' => ':filterName|value (separated by [,]colon)',
        'alias_session_name' => 'user_locations',
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity Logging
    |--------------------------------------------------------------------------
    |
    | User activity logging configuration
    |
    */
    'log_activity' => [
        'enabled' => env('CANVASTACK_LOG_ACTIVITY', true),
        'run_status' => 'unexceptions', // 'all', 'unexceptions', 'none'
        'exceptions' => [
            'controllers' => [
                // Add controller classes to exclude from logging
            ],
            'groups' => [
                'admin', // Exclude admin group from logging
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    |
    | Email settings for notifications
    |
    */
    'email' => [
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'noreply@canvastack.com'),
            'name' => env('MAIL_FROM_NAME', 'CanvaStack'),
        ],
        'cc' => [
            'address' => env('CANVASTACK_MAIL_CC_ADDRESS', null),
            'name' => env('CANVASTACK_MAIL_CC_NAME', null),
        ],
        'footer' => [
            'text' => 'Best Regards',
            'signature' => env('CANVASTACK_MAIL_SIGNATURE', 'CanvaStack Team'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Configuration
    |--------------------------------------------------------------------------
    |
    | Role and group display settings
    |
    */
    'role' => [
        'group' => [
            'format_identity' => [
                'view' => 'group_info|group_alias',
                'separator' => ', ',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Meta Tags Configuration
    |--------------------------------------------------------------------------
    |
    | Default meta tags for SEO
    |
    */
    'meta' => [
        'author' => env('CANVASTACK_META_AUTHOR', 'CanvaStack'),
        'title' => env('CANVASTACK_META_TITLE', 'CanvaStack'),
        'keywords' => env('CANVASTACK_META_KEYWORDS', 'CanvaStack, Laravel, CMS'),
        'description' => env('CANVASTACK_META_DESCRIPTION', 'CanvaStack Application'),
        'viewport' => 'width=device-width, initial-scale=1.0, maximum-scale=1.0',
        'http_equiv' => [
            'type' => 'X-UA-Compatible',
            'content' => 'IE=edge,chrome=1',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Copyright Information
    |--------------------------------------------------------------------------
    |
    | Copyright and company information
    |
    */
    'copyright' => [
        'text' => env('CANVASTACK_COPYRIGHT', 'CanvaStack'),
        'location' => env('CANVASTACK_LOCATION', 'Jakarta'),
        'location_abbr' => env('CANVASTACK_LOCATION_ABBR', 'ID'),
        'year_start' => 2017,
        'year_current' => date('Y'),
        'email' => env('CANVASTACK_EMAIL', 'info@canvastack.com'),
        'website' => env('CANVASTACK_WEBSITE', 'canvastack.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Localization Configuration
    |--------------------------------------------------------------------------
    |
    | Internationalization (i18n) settings for multi-language support
    |
    */
    'localization' => [
        // Default locale
        'default_locale' => env('CANVASTACK_DEFAULT_LOCALE', 'en'),

        // Fallback locale (used when translation is missing)
        'fallback_locale' => env('CANVASTACK_FALLBACK_LOCALE', 'en'),

        // Available locales
        'available_locales' => [
            'en' => [
                'name' => 'English',
                'native' => 'English',
                'flag' => '🇺🇸',
                'direction' => 'ltr',
            ],
            'id' => [
                'name' => 'Indonesian',
                'native' => 'Bahasa Indonesia',
                'flag' => '🇮🇩',
                'direction' => 'ltr',
            ],
        ],

        // RTL (Right-to-Left) locales
        'rtl_locales' => ['ar', 'he', 'fa', 'ur'],

        // Storage driver for locale persistence
        // Options: 'session', 'cookie', 'both'
        'storage' => env('CANVASTACK_LOCALE_STORAGE', 'session'),

        // Detect locale from browser Accept-Language header
        'detect_browser' => env('CANVASTACK_DETECT_BROWSER_LOCALE', true),

        // Translation caching
        'cache_enabled' => env('CANVASTACK_TRANSLATION_CACHE', true),
        'cache_ttl' => 3600, // 1 hour

        // Custom translation paths (in addition to package paths)
        'paths' => [
            // resource_path('lang/vendor/canvastack'),
        ],

        // Translation key naming convention
        'naming_convention' => [
            // Format: {group}.{section}.{key}
            // Example: ui.buttons.save, components.form.required_field
            'separator' => '.',
            'case' => 'snake_case', // 'snake_case', 'camelCase', 'kebab-case'
        ],

        // Missing translation handling
        'missing_translation' => [
            'log' => env('CANVASTACK_LOG_MISSING_TRANSLATIONS', false),
            'return_key' => true, // Return key if translation not found
        ],

        // Date and time localization
        'date_format' => [
            'en' => 'Y-m-d',
            'id' => 'd-m-Y',
        ],
        'time_format' => [
            'en' => 'H:i:s',
            'id' => 'H:i:s',
        ],
        'datetime_format' => [
            'en' => 'Y-m-d H:i:s',
            'id' => 'd-m-Y H:i:s',
        ],
        'long_date_format' => [
            'en' => 'F j, Y',
            'id' => 'j F Y',
        ],
        'short_date_format' => [
            'en' => 'M j, Y',
            'id' => 'j M Y',
        ],

        // Number formatting
        'number_format' => [
            'en' => [
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'decimals' => 2,
            ],
            'id' => [
                'decimal_separator' => ',',
                'thousands_separator' => '.',
                'decimals' => 2,
            ],
        ],

        // Currency formatting
        'currency_format' => [
            'en' => [
                'symbol' => '$',
                'position' => 'before', // 'before' or 'after'
                'space' => false,
                'decimals' => 2,
            ],
            'id' => [
                'symbol' => 'Rp',
                'position' => 'before',
                'space' => true,
                'decimals' => 0,
            ],
        ],

        // Default currency per locale
        'default_currency' => [
            'en' => 'USD',
            'id' => 'IDR',
        ],

        // Currency definitions
        'currencies' => [
            'USD' => [
                'default' => [
                    'symbol' => '$',
                    'name' => 'US Dollar',
                    'position' => 'before',
                    'space' => false,
                    'decimals' => 2,
                ],
                'id' => [
                    'symbol' => '$',
                    'name' => 'Dolar AS',
                    'position' => 'before',
                    'space' => false,
                    'decimals' => 2,
                ],
            ],
            'IDR' => [
                'default' => [
                    'symbol' => 'Rp',
                    'name' => 'Indonesian Rupiah',
                    'position' => 'before',
                    'space' => true,
                    'decimals' => 0,
                ],
                'en' => [
                    'symbol' => 'Rp',
                    'name' => 'Indonesian Rupiah',
                    'position' => 'before',
                    'space' => true,
                    'decimals' => 0,
                ],
                'id' => [
                    'symbol' => 'Rp',
                    'name' => 'Rupiah',
                    'position' => 'before',
                    'space' => true,
                    'decimals' => 0,
                ],
            ],
            'EUR' => [
                'default' => [
                    'symbol' => '€',
                    'name' => 'Euro',
                    'position' => 'before',
                    'space' => false,
                    'decimals' => 2,
                ],
            ],
            'GBP' => [
                'default' => [
                    'symbol' => '£',
                    'name' => 'British Pound',
                    'position' => 'before',
                    'space' => false,
                    'decimals' => 2,
                ],
            ],
            'JPY' => [
                'default' => [
                    'symbol' => '¥',
                    'name' => 'Japanese Yen',
                    'position' => 'before',
                    'space' => false,
                    'decimals' => 0,
                ],
            ],
        ],
    ],
];
