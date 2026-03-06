<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Table Engine
    |--------------------------------------------------------------------------
    |
    | This option controls the default table rendering engine used by the
    | TableBuilder component. You can override this per-table using the
    | setEngine() method.
    |
    | Supported: "datatables", "tanstack"
    |
    */

    'engine' => env('CANVASTACK_TABLE_ENGINE', 'datatables'),

    /*
    |--------------------------------------------------------------------------
    | Registered Engines
    |--------------------------------------------------------------------------
    |
    | This array contains all registered table engines. Each engine must
    | implement the TableEngineInterface. You can register custom engines
    | by adding them to this array.
    |
    */

    'engines' => [
        'datatables' => [
            'class' => \Canvastack\Canvastack\Components\Table\Engines\DataTablesEngine::class,
            'enabled' => true,
            'description' => 'DataTables.js engine with Yajra integration',
        ],
        'tanstack' => [
            'class' => \Canvastack\Canvastack\Components\Table\Engines\TanStackEngine::class,
            'enabled' => true,
            'description' => 'TanStack Table v8 engine with Alpine.js',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features for table rendering. These flags
    | allow you to control which features are available across all engines.
    |
    */

    'features' => [
        'sorting' => true,
        'pagination' => true,
        'searching' => true,
        'filtering' => true,
        'fixed_columns' => true,
        'row_selection' => true,
        'export' => true,
        'column_resizing' => true,
        'virtual_scrolling' => true,
        'lazy_loading' => true,
        'responsive' => true,
        'dark_mode' => true,
        'auto_detection' => true, // Auto-detect best engine based on requirements
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configure performance-related settings for table rendering, including
    | caching, chunk processing, and query optimization.
    |
    */

    'performance' => [
        // Cache configuration
        'cache' => [
            'enabled' => true,
            'ttl' => 300, // 5 minutes default
            'driver' => env('CACHE_DRIVER', 'redis'),
            'prefix' => 'canvastack_table',
            'tags' => ['canvastack', 'table'],
        ],

        // Query optimization
        'query' => [
            'chunk_size' => 1000, // Chunk size for large datasets
            'eager_load' => true, // Enable eager loading by default
            'auto_optimize' => true, // Automatically optimize queries
        ],

        // Virtual scrolling
        'virtual_scrolling' => [
            'enabled' => true,
            'buffer_size' => 10, // Number of rows to render outside viewport
            'row_height' => 48, // Default row height in pixels
        ],

        // Lazy loading
        'lazy_loading' => [
            'enabled' => true,
            'threshold' => 200, // Pixels from bottom to trigger load
            'page_size' => 50, // Items per lazy load
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Configuration
    |--------------------------------------------------------------------------
    |
    | Configure CDN URLs and asset paths for table engines. These URLs are
    | used to load required CSS and JavaScript files for each engine.
    |
    */

    'assets' => [
        'datatables' => [
            'css' => [
                'https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css',
                'https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.bootstrap5.min.css',
                'https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css',
            ],
            'js' => [
                'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
                'https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js',
                'https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js',
                'https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js',
                'https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js',
                'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js',
                'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js',
                'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js',
                'https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js',
                'https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js',
            ],
        ],
        'tanstack' => [
            'css' => [
                // TanStack Table uses custom CSS (included in canvastack.css)
            ],
            'js' => [
                'https://cdn.jsdelivr.net/npm/@tanstack/table-core@8.11.2/build/lib/index.min.js',
                // Alpine.js is loaded globally by CanvaStack
            ],
        ],
        'flatpickr' => [
            'css' => [
                'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css',
                'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/themes/material_blue.css',
            ],
            'js' => [
                'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | DataTables Configuration
    |--------------------------------------------------------------------------
    |
    | Default configuration options for DataTables.js engine. These options
    | are passed to DataTables initialization.
    |
    */

    'datatables' => [
        'dom' => 'Bfrtip', // DataTables DOM layout
        'page_length' => 25,
        'length_menu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        'language' => [
            'search' => '_INPUT_',
            'searchPlaceholder' => 'Search...',
            'lengthMenu' => 'Show _MENU_ entries',
            'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
            'infoEmpty' => 'No entries available',
            'infoFiltered' => '(filtered from _MAX_ total entries)',
            'zeroRecords' => 'No matching records found',
            'emptyTable' => 'No data available in table',
            'paginate' => [
                'first' => 'First',
                'last' => 'Last',
                'next' => 'Next',
                'previous' => 'Previous',
            ],
        ],
        'processing' => true,
        'serverSide' => true,
        'stateSave' => true,
        'responsive' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | TanStack Configuration
    |--------------------------------------------------------------------------
    |
    | Default configuration options for TanStack Table engine. These options
    | control the behavior of TanStack Table and Alpine.js integration.
    |
    */

    'tanstack' => [
        'page_size' => 25,
        'page_size_options' => [10, 25, 50, 100],
        'enable_sorting' => true,
        'enable_filtering' => true,
        'enable_pagination' => true,
        'enable_column_resizing' => true,
        'enable_column_pinning' => true,
        'enable_row_selection' => true,
        'enable_virtual_scrolling' => false, // Enable per-table as needed
        'debounce_search' => 300, // Milliseconds
        'state_persistence' => true,
        'state_key_prefix' => 'tanstack_table',
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Configuration
    |--------------------------------------------------------------------------
    |
    | Configure export functionality for both engines. These settings control
    | how data is exported to Excel, CSV, PDF, and print formats.
    |
    */

    'export' => [
        'enabled' => true,
        'formats' => ['excel', 'csv', 'pdf', 'print'],
        'excel' => [
            'library' => 'phpspreadsheet', // PhpSpreadsheet for Excel export
            'extension' => 'xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
        'csv' => [
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'extension' => 'csv',
            'mime_type' => 'text/csv',
        ],
        'pdf' => [
            'library' => 'dompdf', // DomPDF for PDF export
            'orientation' => 'portrait', // portrait or landscape
            'paper_size' => 'A4',
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Filter Configuration
    |--------------------------------------------------------------------------
    |
    | Configure advanced filtering options including bi-directional cascading
    | filters, date range filters, and filter persistence.
    |
    */

    'filters' => [
        'enabled' => true,
        'cascading' => true, // Enable bi-directional cascading filters
        'persistence' => true, // Persist filters in session
        'date_range' => [
            'enabled' => true,
            'library' => 'flatpickr', // Flatpickr for date range selection
            'format' => 'Y-m-d',
            'mode' => 'range',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | State Management Configuration
    |--------------------------------------------------------------------------
    |
    | Configure state persistence for table settings including sort, filters,
    | page size, column visibility, and column widths.
    |
    */

    'state' => [
        'enabled' => true,
        'driver' => 'session', // session, cookie, or url
        'ttl' => 3600, // 1 hour for session/cookie
        'persist' => [
            'sort' => true,
            'filters' => true,
            'page_size' => true,
            'column_visibility' => true,
            'column_widths' => true, // TanStack only
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Integration
    |--------------------------------------------------------------------------
    |
    | Configure theme integration for table rendering. These settings ensure
    | tables use theme colors, fonts, and support dark mode.
    |
    */

    'theme' => [
        'enabled' => true,
        'use_theme_colors' => true, // Use theme colors instead of hardcoded
        'use_theme_fonts' => true, // Use theme fonts instead of hardcoded
        'dark_mode' => true, // Enable dark mode support
        'transitions' => true, // Enable smooth transitions
        'transition_duration' => 200, // Milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Dark Mode Configuration
    |--------------------------------------------------------------------------
    |
    | Configure dark mode detection, toggle, and persistence settings.
    | Validates Requirements 15.4.
    |
    */

    'dark_mode' => [
        'enabled' => true,
        'sync_with_system' => true, // Sync with system dark mode preference
        'show_toggle_button' => true, // Show dark mode toggle button
        'persist_preference' => true, // Persist user preference in localStorage
        'storage_key' => 'canvastack_dark_mode', // localStorage key
        'show_system_indicator' => false, // Show indicator when using system preference
        'auto_init' => true, // Automatically initialize on page load
        'transition_duration' => 200, // Milliseconds for smooth transitions
    ],

    /*
    |--------------------------------------------------------------------------
    | Internationalization (i18n) Configuration
    |--------------------------------------------------------------------------
    |
    | Configure internationalization settings for table UI elements. All text
    | should use translation keys instead of hardcoded strings.
    |
    */

    'i18n' => [
        'enabled' => true,
        'translation_prefix' => 'components.table', // Translation key prefix
        'rtl_support' => true, // Enable RTL layout support
        'rtl_locales' => ['ar', 'he', 'fa', 'ur'], // RTL locales
        'date_localization' => true, // Localize dates with Carbon
        'number_localization' => true, // Localize numbers with NumberFormatter
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configure security settings for table rendering including SQL injection
    | prevention, XSS protection, and input validation.
    |
    */

    'security' => [
        'sql_injection_prevention' => true, // Use parameterized queries
        'xss_protection' => true, // Escape output
        'csrf_protection' => true, // Require CSRF tokens
        'validate_column_names' => true, // Validate sort/filter columns
        'validate_input' => true, // Validate all user input
        'allowed_sort_columns' => [], // Empty = all columns allowed
        'allowed_filter_columns' => [], // Empty = all columns allowed
    ],

    /*
    |--------------------------------------------------------------------------
    | Accessibility Configuration
    |--------------------------------------------------------------------------
    |
    | Configure accessibility settings to ensure WCAG 2.1 AA compliance.
    |
    */

    'accessibility' => [
        'enabled' => true,
        'wcag_level' => 'AA', // AA or AAA
        'keyboard_navigation' => true,
        'screen_reader_support' => true,
        'aria_labels' => true,
        'focus_indicators' => true,
        'color_contrast' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Configuration
    |--------------------------------------------------------------------------
    |
    | Configure debug settings for development and troubleshooting.
    | Validates Requirements 44.1-44.7.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configure performance monitoring and metrics collection for table
    | rendering. Helps identify bottlenecks and optimize performance.
    | Validates Requirements 44.2, 49.1-49.7.
    |
    */

    'performance' => [
        // Performance monitoring
        'monitoring' => env('TABLE_PERFORMANCE_MONITORING', env('APP_ENV') === 'local'),
        'log_metrics' => env('TABLE_LOG_METRICS', false),
        'log_channel' => 'performance', // Laravel log channel for metrics
        
        // Performance targets (for validation)
        'targets' => [
            'render_time_ms' => 500, // Target: < 500ms for 1K rows
            'memory_mb' => 128, // Target: < 128MB peak memory
            'speed_multiplier_min' => 2.0, // TanStack should be 2-5x faster
            'speed_multiplier_max' => 5.0,
        ],
        
        // Debug panel
        'debug_panel' => [
            'enabled' => env('TABLE_DEBUG_PANEL', env('APP_ENV') === 'local'),
            'position' => 'bottom-right', // bottom-right, bottom-left, top-right, top-left
            'auto_open' => false, // Automatically open on page load
        ],
        
        // Cache configuration
        'cache' => [
            'enabled' => true,
            'ttl' => 300, // 5 minutes default
            'driver' => env('CACHE_DRIVER', 'redis'),
            'prefix' => 'canvastack_table',
            'tags' => ['canvastack', 'table'],
        ],

        // Query optimization
        'query' => [
            'chunk_size' => 1000, // Chunk size for large datasets
            'eager_load' => true, // Enable eager loading by default
            'auto_optimize' => true, // Automatically optimize queries
        ],

        // Virtual scrolling
        'virtual_scrolling' => [
            'enabled' => true,
            'buffer_size' => 10, // Number of rows to render outside viewport
            'row_height' => 48, // Default row height in pixels
        ],

        // Lazy loading
        'lazy_loading' => [
            'enabled' => true,
            'threshold' => 200, // Pixels from bottom to trigger load
            'page_size' => 50, // Items per lazy load
        ],
    ],

];
