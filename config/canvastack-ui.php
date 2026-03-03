<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Theme Configuration
    |--------------------------------------------------------------------------
    |
    | UI theme settings including colors, fonts, and layout options
    |
    */
    'theme' => [
        'active' => env('CANVASTACK_THEME', 'gradient'),
        'default' => 'gradient',
        'path' => resource_path('themes'),
        'cache_enabled' => env('CANVASTACK_THEME_CACHE', true),
        'cache_ttl' => 3600, // 1 hour
        'cache_store' => env('CANVASTACK_THEME_CACHE_STORE', 'redis'),
        'hot_reload' => env('CANVASTACK_THEME_HOT_RELOAD', false),

        /*
        |----------------------------------------------------------------------
        | Theme Registry
        |----------------------------------------------------------------------
        |
        | Register available themes. Each theme must have a unique name and
        | can be defined inline or loaded from a separate file.
        |
        */
        'registry' => [
            'gradient' => [
                'name' => 'gradient',
                'display_name' => 'Gradient',
                'version' => '1.0.0',
                'author' => 'CanvaStack Team',
                'description' => 'Modern gradient theme with indigo, purple, and fuchsia colors',
                'config' => [
                    'colors' => [
                        'primary' => '#6366f1',    // Indigo 500
                        'secondary' => '#8b5cf6',  // Purple 500
                        'accent' => '#a855f7',     // Fuchsia 500

                        // Gradient definition
                        'gradient' => [
                            'start' => '#6366f1',
                            'mid' => '#8b5cf6',
                            'end' => '#a855f7',
                            'direction' => '135deg',
                        ],

                        // Semantic colors
                        'success' => '#059669',    // Emerald 600
                        'warning' => '#d97706',    // Amber 600
                        'error' => '#dc2626',      // Red 600
                        'info' => '#2563eb',       // Blue 600
                    ],
                    'fonts' => [
                        'sans' => 'Inter, system-ui, -apple-system, sans-serif',
                        'mono' => 'JetBrains Mono, Fira Code, monospace',
                    ],
                    'radius' => [
                        'card' => '1.5rem',      // 24px
                        'button' => '1rem',      // 16px
                        'input' => '1rem',       // 16px
                        'badge' => '9999px',     // full
                        'modal' => '1.5rem',     // 24px
                    ],
                    'spacing' => [
                        'container_max_width' => '80rem', // 1280px
                        'container_padding' => '1rem',
                    ],
                    'dark_mode' => [
                        'enabled' => true,
                    ],
                ],
            ],

            'ocean' => [
                'name' => 'ocean',
                'display_name' => 'Ocean',
                'version' => '1.0.0',
                'author' => 'CanvaStack Team',
                'description' => 'Cool ocean theme with blue and teal colors',
                'config' => [
                    'colors' => [
                        'primary' => '#0ea5e9',    // Sky 500
                        'secondary' => '#06b6d4',  // Cyan 500
                        'accent' => '#14b8a6',     // Teal 500

                        'gradient' => [
                            'start' => '#0ea5e9',
                            'mid' => '#06b6d4',
                            'end' => '#14b8a6',
                            'direction' => '135deg',
                        ],

                        'success' => '#10b981',    // Emerald 500
                        'warning' => '#f59e0b',    // Amber 500
                        'error' => '#ef4444',      // Red 500
                        'info' => '#3b82f6',       // Blue 500
                    ],
                    'fonts' => [
                        'sans' => 'Inter, system-ui, -apple-system, sans-serif',
                        'mono' => 'JetBrains Mono, Fira Code, monospace',
                    ],
                    'radius' => [
                        'card' => '1.5rem',
                        'button' => '1rem',
                        'input' => '1rem',
                        'badge' => '9999px',
                        'modal' => '1.5rem',
                    ],
                    'spacing' => [
                        'container_max_width' => '80rem',
                        'container_padding' => '1rem',
                    ],
                    'dark_mode' => [
                        'enabled' => true,
                    ],
                ],
            ],

            'sunset' => [
                'name' => 'sunset',
                'display_name' => 'Sunset',
                'version' => '1.0.0',
                'author' => 'CanvaStack Team',
                'description' => 'Warm sunset theme with orange, red, and pink colors',
                'config' => [
                    'colors' => [
                        'primary' => '#f97316',    // Orange 500
                        'secondary' => '#ef4444',  // Red 500
                        'accent' => '#ec4899',     // Pink 500

                        'gradient' => [
                            'start' => '#f97316',
                            'mid' => '#ef4444',
                            'end' => '#ec4899',
                            'direction' => '135deg',
                        ],

                        'success' => '#22c55e',    // Green 500
                        'warning' => '#eab308',    // Yellow 500
                        'error' => '#dc2626',      // Red 600
                        'info' => '#3b82f6',       // Blue 500
                    ],
                    'fonts' => [
                        'sans' => 'Inter, system-ui, -apple-system, sans-serif',
                        'mono' => 'JetBrains Mono, Fira Code, monospace',
                    ],
                    'radius' => [
                        'card' => '1.5rem',
                        'button' => '1rem',
                        'input' => '1rem',
                        'badge' => '9999px',
                        'modal' => '1.5rem',
                    ],
                    'spacing' => [
                        'container_max_width' => '80rem',
                        'container_padding' => '1rem',
                    ],
                    'dark_mode' => [
                        'enabled' => true,
                    ],
                ],
            ],

            'forest' => [
                'name' => 'forest',
                'display_name' => 'Forest',
                'version' => '1.0.0',
                'author' => 'CanvaStack Team',
                'description' => 'Natural forest theme with green and emerald colors',
                'config' => [
                    'colors' => [
                        'primary' => '#22c55e',    // Green 500
                        'secondary' => '#10b981',  // Emerald 500
                        'accent' => '#14b8a6',     // Teal 500

                        'gradient' => [
                            'start' => '#22c55e',
                            'mid' => '#10b981',
                            'end' => '#14b8a6',
                            'direction' => '135deg',
                        ],

                        'success' => '#16a34a',    // Green 600
                        'warning' => '#ca8a04',    // Yellow 600
                        'error' => '#dc2626',      // Red 600
                        'info' => '#0284c7',       // Sky 600
                    ],
                    'fonts' => [
                        'sans' => 'Inter, system-ui, -apple-system, sans-serif',
                        'mono' => 'JetBrains Mono, Fira Code, monospace',
                    ],
                    'radius' => [
                        'card' => '1.5rem',
                        'button' => '1rem',
                        'input' => '1rem',
                        'badge' => '9999px',
                        'modal' => '1.5rem',
                    ],
                    'spacing' => [
                        'container_max_width' => '80rem',
                        'container_padding' => '1rem',
                    ],
                    'dark_mode' => [
                        'enabled' => true,
                    ],
                ],
            ],

            'midnight' => [
                'name' => 'midnight',
                'display_name' => 'Midnight',
                'version' => '1.0.0',
                'author' => 'CanvaStack Team',
                'description' => 'Dark midnight theme with slate and blue colors',
                'config' => [
                    'colors' => [
                        'primary' => '#475569',    // Slate 600
                        'secondary' => '#64748b',  // Slate 500
                        'accent' => '#3b82f6',     // Blue 500

                        'gradient' => [
                            'start' => '#475569',
                            'mid' => '#64748b',
                            'end' => '#3b82f6',
                            'direction' => '135deg',
                        ],

                        'success' => '#10b981',    // Emerald 500
                        'warning' => '#f59e0b',    // Amber 500
                        'error' => '#ef4444',      // Red 500
                        'info' => '#06b6d4',       // Cyan 500
                    ],
                    'fonts' => [
                        'sans' => 'Inter, system-ui, -apple-system, sans-serif',
                        'mono' => 'JetBrains Mono, Fira Code, monospace',
                    ],
                    'radius' => [
                        'card' => '1.5rem',
                        'button' => '1rem',
                        'input' => '1rem',
                        'badge' => '9999px',
                        'modal' => '1.5rem',
                    ],
                    'spacing' => [
                        'container_max_width' => '80rem',
                        'container_padding' => '1rem',
                    ],
                    'dark_mode' => [
                        'enabled' => true,
                    ],
                ],
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Theme Validation
        |----------------------------------------------------------------------
        |
        | Validation rules for theme configuration
        |
        */
        'validation' => [
            'required_fields' => ['name', 'display_name', 'version', 'author', 'config'],
            'required_config' => ['colors', 'fonts'],
            'required_colors' => ['primary', 'secondary', 'accent'],
        ],

        /*
        |----------------------------------------------------------------------
        | Shared Typography Configuration
        |----------------------------------------------------------------------
        |
        | Typography settings shared across all themes
        |
        */
        'typography' => [
            'sizes' => [
                'xs' => '0.75rem',      // 12px
                'sm' => '0.875rem',     // 14px
                'base' => '1rem',       // 16px
                'lg' => '1.125rem',     // 18px
                'xl' => '1.25rem',      // 20px
                '2xl' => '1.5rem',      // 24px
                '3xl' => '1.875rem',    // 30px
                '4xl' => '2.25rem',     // 36px
                '5xl' => '3rem',        // 48px
                '6xl' => '3.75rem',     // 60px
                '7xl' => '4.5rem',      // 72px
            ],
            'weights' => [
                'light' => 300,
                'normal' => 400,
                'medium' => 500,
                'semibold' => 600,
                'bold' => 700,
                'extrabold' => 800,
                'black' => 900,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dark Mode Configuration
    |--------------------------------------------------------------------------
    |
    | Dark mode settings and preferences
    |
    */
    'dark_mode' => [
        'enabled' => env('CANVASTACK_DARK_MODE_ENABLED', true),
        'default' => env('CANVASTACK_DARK_MODE_DEFAULT', 'light'), // 'light' or 'dark'
        'storage' => 'localStorage', // Storage method for user preference
        'class_strategy' => 'class', // 'class' or 'media'
    ],

    /*
    |--------------------------------------------------------------------------
    | Layout Configuration
    |--------------------------------------------------------------------------
    |
    | Layout settings for admin and public interfaces
    |
    */
    'layout' => [
        'admin' => [
            'sidebar' => [
                'width' => '16rem',           // 256px
                'collapsed_width' => '4rem',  // 64px
                'collapsible' => true,
                'position' => 'left',         // 'left' or 'right'
            ],
            'navbar' => [
                'height' => '4rem',           // 64px
                'sticky' => true,
                'blur' => true,
            ],
            'content' => [
                'max_width' => '100%',
                'padding' => '1.5rem',
            ],
        ],
        'public' => [
            'navbar' => [
                'height' => '4rem',           // 64px
                'sticky' => true,
                'transparent' => true,
                'blur' => true,
            ],
            'content' => [
                'max_width' => '80rem',       // 1280px
                'padding' => '1rem',
            ],
            'footer' => [
                'enabled' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Component Configuration
    |--------------------------------------------------------------------------
    |
    | Default settings for UI components
    |
    */
    'components' => [
        'button' => [
            'default_variant' => 'primary',
            'default_size' => 'md',
            'variants' => ['primary', 'secondary', 'outline', 'ghost'],
            'sizes' => ['sm', 'md', 'lg'],
        ],
        'card' => [
            'default_shadow' => 'sm',
            'hover_effect' => true,
        ],
        'badge' => [
            'default_variant' => 'primary',
            'variants' => ['success', 'warning', 'error', 'info'],
        ],
        'modal' => [
            'backdrop_blur' => true,
            'close_on_backdrop' => true,
            'animation' => 'scale', // 'scale', 'fade', 'slide'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Configuration
    |--------------------------------------------------------------------------
    |
    | Asset paths and template settings merged from canvas.templates.php
    |
    */
    'assets' => [
        'base_template' => 'assets/templates',
        'base_resources' => 'assets/resources',
        'template' => env('CANVASTACK_TEMPLATE', 'default'),

        /*
        |----------------------------------------------------------------------
        | Template Assets
        |----------------------------------------------------------------------
        |
        | Asset loading configuration for different contexts
        |
        */
        'templates' => [
            'admin' => [
                'default' => [
                    'position' => [
                        'top' => [
                            'js' => [
                                // Core JS loaded in head
                            ],
                            'css' => [
                                // Core CSS loaded in head
                            ],
                        ],
                        'bottom' => [
                            'first' => [
                                'js' => [
                                    // First JS loaded before body close
                                ],
                                'css' => [
                                    // Additional CSS
                                ],
                            ],
                            'last' => [
                                'js' => [
                                    // Last JS loaded before body close
                                ],
                                'css' => [
                                    // Final CSS
                                ],
                            ],
                        ],
                    ],

                    // Component-specific assets
                    'datatable' => [
                        'js' => [],
                        'css' => [],
                    ],
                    'form' => [
                        'js' => [],
                        'css' => [],
                    ],
                    'chart' => [
                        'js' => [],
                        'css' => [],
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Icon Configuration
    |--------------------------------------------------------------------------
    |
    | Icon library settings
    |
    */
    'icons' => [
        'library' => 'lucide', // 'lucide', 'heroicons', 'fontawesome'
        'default_size' => '1.25rem', // 20px
    ],

    /*
    |--------------------------------------------------------------------------
    | Animation Configuration
    |--------------------------------------------------------------------------
    |
    | Animation and transition settings
    |
    */
    'animations' => [
        'enabled' => env('CANVASTACK_ANIMATIONS_ENABLED', true),
        'duration' => [
            'fast' => '150ms',
            'normal' => '300ms',
            'slow' => '500ms',
        ],
        'easing' => 'ease-in-out',
        'gsap' => [
            'enabled' => true,
            'page_transitions' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Responsive Breakpoints
    |--------------------------------------------------------------------------
    |
    | Responsive design breakpoints
    |
    */
    'breakpoints' => [
        'sm' => '640px',
        'md' => '768px',
        'lg' => '1024px',
        'xl' => '1280px',
        '2xl' => '1536px',
    ],
];
