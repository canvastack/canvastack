<?php

return [
    /*
    |--------------------------------------------------------------------------
    | RBAC Configuration
    |--------------------------------------------------------------------------
    |
    | Role-Based Access Control settings for CanvaStack
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Context Configuration
    |--------------------------------------------------------------------------
    |
    | Define different contexts for authorization (admin, public, api)
    |
    */
    'contexts' => [
        'admin' => [
            'enabled' => true,
            'middleware' => ['web', 'auth'],
            'guard' => 'web',
        ],
        'public' => [
            'enabled' => true,
            'middleware' => ['web'],
            'guard' => 'web',
        ],
        'api' => [
            'enabled' => false,
            'middleware' => ['api', 'auth:sanctum'],
            'guard' => 'sanctum',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Configuration
    |--------------------------------------------------------------------------
    |
    | Default roles and their hierarchy levels
    |
    */
    'roles' => [
        'super_admin' => [
            'name' => 'Super Admin',
            'level' => 1,
            'description' => 'Full system access',
            'is_system' => true,
        ],
        'admin' => [
            'name' => 'Admin',
            'level' => 2,
            'description' => 'Administrative access',
            'is_system' => true,
        ],
        'manager' => [
            'name' => 'Manager',
            'level' => 3,
            'description' => 'Management access',
            'is_system' => false,
        ],
        'user' => [
            'name' => 'User',
            'level' => 4,
            'description' => 'Standard user access',
            'is_system' => false,
        ],
        'guest' => [
            'name' => 'Guest',
            'level' => 5,
            'description' => 'Guest access',
            'is_system' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Configuration
    |--------------------------------------------------------------------------
    |
    | Permission structure and naming conventions
    |
    */
    'permissions' => [
        /*
        |----------------------------------------------------------------------
        | Permission Naming Convention
        |----------------------------------------------------------------------
        |
        | Format: {module}.{action}
        | Example: users.create, posts.edit, reports.view
        |
        */
        'naming_convention' => '{module}.{action}',

        /*
        |----------------------------------------------------------------------
        | Default Actions
        |----------------------------------------------------------------------
        |
        | Standard CRUD actions available for all modules
        |
        */
        'default_actions' => [
            'view',
            'create',
            'edit',
            'delete',
            'export',
        ],

        /*
        |----------------------------------------------------------------------
        | Module Permissions
        |----------------------------------------------------------------------
        |
        | Define permissions for each module
        |
        */
        'modules' => [
            'users' => [
                'label' => 'User Management',
                'permissions' => [
                    'view' => 'View users',
                    'create' => 'Create users',
                    'edit' => 'Edit users',
                    'delete' => 'Delete users',
                    'export' => 'Export users',
                    'impersonate' => 'Impersonate users',
                ],
            ],
            'roles' => [
                'label' => 'Role Management',
                'permissions' => [
                    'view' => 'View roles',
                    'create' => 'Create roles',
                    'edit' => 'Edit roles',
                    'delete' => 'Delete roles',
                    'assign' => 'Assign roles',
                ],
            ],
            'permissions' => [
                'label' => 'Permission Management',
                'permissions' => [
                    'view' => 'View permissions',
                    'create' => 'Create permissions',
                    'edit' => 'Edit permissions',
                    'delete' => 'Delete permissions',
                    'assign' => 'Assign permissions',
                ],
            ],
            'settings' => [
                'label' => 'System Settings',
                'permissions' => [
                    'view' => 'View settings',
                    'edit' => 'Edit settings',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Policy Configuration
    |--------------------------------------------------------------------------
    |
    | Policy mappings for model authorization
    |
    */
    'policies' => [
        /*
        |----------------------------------------------------------------------
        | Auto-Discovery
        |----------------------------------------------------------------------
        |
        | Automatically discover and register policies
        |
        */
        'auto_discover' => true,
        'namespace' => 'App\\Policies',

        /*
        |----------------------------------------------------------------------
        | Policy Mappings
        |----------------------------------------------------------------------
        |
        | Manual policy mappings (Model => Policy)
        |
        */
        'mappings' => [
            // Example:
            // App\Models\User::class => App\Policies\UserPolicy::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Caching settings for permissions and roles
    |
    */
    'cache' => [
        'enabled' => env('CANVASTACK_RBAC_CACHE_ENABLED', true),
        'ttl' => 3600, // 1 hour
        'key_prefix' => 'canvastack:rbac:',
        'tags' => [
            'roles' => 'canvastack:rbac:roles',
            'permissions' => 'canvastack:rbac:permissions',
            'user_permissions' => 'canvastack:rbac:user_permissions',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization Configuration
    |--------------------------------------------------------------------------
    |
    | Authorization behavior settings
    |
    */
    'authorization' => [
        /*
        |----------------------------------------------------------------------
        | Super Admin Bypass
        |----------------------------------------------------------------------
        |
        | Allow super admin to bypass all permission checks
        |
        */
        'super_admin_bypass' => true,
        'super_admin_role' => 'super_admin',

        /*
        |----------------------------------------------------------------------
        | Context-Aware Authorization
        |----------------------------------------------------------------------
        |
        | Enable context-aware permission checking
        |
        */
        'context_aware' => true,

        /*
        |----------------------------------------------------------------------
        | Strict Mode
        |----------------------------------------------------------------------
        |
        | Deny access if permission is not explicitly granted
        |
        */
        'strict_mode' => true,

        /*
        |----------------------------------------------------------------------
        | Default Permissions
        |----------------------------------------------------------------------
        |
        | Permissions granted to all authenticated users
        |
        */
        'default_permissions' => [
            // Example: 'profile.view', 'profile.edit'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Group Configuration
    |--------------------------------------------------------------------------
    |
    | User group settings
    |
    */
    'groups' => [
        'enabled' => true,
        'table' => 'groups',
        'model' => 'App\\Models\\Admin\\System\\Group',

        /*
        |----------------------------------------------------------------------
        | Group Hierarchy
        |----------------------------------------------------------------------
        |
        | Enable hierarchical group structure
        |
        */
        'hierarchical' => false,
        'parent_column' => 'parent_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity Logging
    |--------------------------------------------------------------------------
    |
    | Log authorization attempts and permission changes
    |
    */
    'activity_log' => [
        'enabled' => env('CANVASTACK_RBAC_LOG_ENABLED', true),
        'log_failed_attempts' => true,
        'log_permission_changes' => true,
        'log_role_changes' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    |
    | Database table names for RBAC
    |
    */
    'tables' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'role_user' => 'role_user',
        'permission_role' => 'permission_role',
        'permission_user' => 'permission_user',
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Model classes for RBAC entities
    |
    */
    'models' => [
        'role' => 'Canvastack\\Models\\Role',
        'permission' => 'Canvastack\\Models\\Permission',
        'user' => 'App\\Models\\User',
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Configuration
    |--------------------------------------------------------------------------
    |
    | Middleware settings for RBAC
    |
    */
    'middleware' => [
        'role' => 'role',
        'permission' => 'permission',
        'role_or_permission' => 'role_or_permission',
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    |
    | UI-related RBAC settings
    |
    */
    'ui' => [
        /*
        |----------------------------------------------------------------------
        | Show Unauthorized Actions
        |----------------------------------------------------------------------
        |
        | Show disabled buttons/links for unauthorized actions
        |
        */
        'show_unauthorized' => false,

        /*
        |----------------------------------------------------------------------
        | Permission Tooltips
        |----------------------------------------------------------------------
        |
        | Show tooltips explaining why action is unauthorized
        |
        */
        'show_tooltips' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fine-Grained Permissions Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for fine-grained permission system including row-level,
    | column-level, JSON attribute, and conditional permissions.
    |
    */
    'fine_grained' => [
        /*
        |----------------------------------------------------------------------
        | Enable Fine-Grained Permissions
        |----------------------------------------------------------------------
        |
        | Enable or disable fine-grained permissions globally
        |
        */
        'enabled' => env('CANVASTACK_RBAC_FINE_GRAINED_ENABLED', true),

        /*
        |----------------------------------------------------------------------
        | Cache Configuration
        |----------------------------------------------------------------------
        |
        | Caching settings for fine-grained permission evaluations
        |
        */
        'cache' => [
            'enabled' => true,
            'ttl' => [
                'row' => 3600,              // 1 hour
                'column' => 3600,           // 1 hour
                'json_attribute' => 3600,   // 1 hour
                'conditional' => 1800,      // 30 minutes
            ],
            'key_prefix' => 'canvastack:rbac:rules:',
            'tags' => [
                'rules' => 'rbac:rules',
                'user' => 'rbac:user:{userId}',
                'permission' => 'rbac:permission:{permissionId}',
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Row-Level Permissions
        |----------------------------------------------------------------------
        |
        | Configuration for row-level access control
        |
        */
        'row_level' => [
            'enabled' => true,

            /*
            |------------------------------------------------------------------
            | Template Variables
            |------------------------------------------------------------------
            |
            | Define template variables for row-level conditions
            | Format: 'variable_name' => callable
            |
            | Examples:
            | 'auth.id' => fn() => auth()->id(),
            | 'auth.role' => fn() => auth()->user()?->role,
            | 'custom.company' => fn() => auth()->user()?->company_id,
            | 'custom.region' => fn() => auth()->user()?->region,
            |
            */
            'template_variables' => [
                'auth.id' => fn () => auth()->id(),
                'auth.role' => fn () => auth()->user()?->role,
                'auth.department' => fn () => auth()->user()?->department_id,
                'auth.email' => fn () => auth()->user()?->email,

                // Add your custom variables here:
                // 'custom.company' => fn() => auth()->user()?->company_id,
                // 'custom.region' => fn() => auth()->user()?->region,
                // 'custom.is_manager' => fn() => auth()->user()?->is_manager ?? false,
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Column-Level Permissions
        |----------------------------------------------------------------------
        |
        | Configuration for column-level access control
        |
        */
        'column_level' => [
            'enabled' => true,

            /*
            |------------------------------------------------------------------
            | Default Deny
            |------------------------------------------------------------------
            |
            | If true, deny access to all columns by default (whitelist mode)
            | If false, allow access to all columns by default (blacklist mode)
            |
            */
            'default_deny' => false,
        ],

        /*
        |----------------------------------------------------------------------
        | JSON Attribute Permissions
        |----------------------------------------------------------------------
        |
        | Configuration for JSON attribute access control
        |
        */
        'json_attribute' => [
            'enabled' => true,

            /*
            |------------------------------------------------------------------
            | Path Separator
            |------------------------------------------------------------------
            |
            | Character used to separate JSON path segments
            | Example: 'metadata.seo.title' uses '.' as separator
            |
            */
            'path_separator' => '.',
        ],

        /*
        |----------------------------------------------------------------------
        | Conditional Permissions
        |----------------------------------------------------------------------
        |
        | Configuration for conditional access control
        |
        */
        'conditional' => [
            'enabled' => true,

            /*
            |------------------------------------------------------------------
            | Allowed Operators
            |------------------------------------------------------------------
            |
            | List of operators allowed in conditional expressions
            |
            */
            'allowed_operators' => [
                '===', '!==', '>', '<', '>=', '<=',
                'in', 'not_in', 'AND', 'OR', 'NOT',
            ],

            /*
            |------------------------------------------------------------------
            | Allowed Functions
            |------------------------------------------------------------------
            |
            | List of functions allowed in conditional expressions
            |
            */
            'allowed_functions' => ['count', 'sum', 'avg'],
        ],

        /*
        |----------------------------------------------------------------------
        | Audit Logging
        |----------------------------------------------------------------------
        |
        | Configuration for fine-grained permission audit logging
        |
        */
        'audit' => [
            'enabled' => true,
            'log_denials' => true,
            'log_channel' => 'rbac',
        ],
    ],
];
