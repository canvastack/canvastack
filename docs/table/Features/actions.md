# Actions & Buttons

CanvaStack Table provides a comprehensive action button system that allows you to add CRUD operations, custom actions, and interactive elements to your tables. The system supports various button types, conditions, permissions, and advanced features like modals and AJAX operations.

## Table of Contents

- [Basic Action Configuration](#basic-action-configuration)
- [Built-in Actions](#built-in-actions)
- [Custom Actions](#custom-actions)
- [Action Conditions](#action-conditions)
- [Modal Integration](#modal-integration)
- [AJAX Actions](#ajax-actions)
- [Bulk Actions](#bulk-actions)
- [Action Permissions](#action-permissions)
- [Styling and Icons](#styling-and-icons)
- [Advanced Examples](#advanced-examples)

## Basic Action Configuration

### Enabling Default Actions

The simplest way to add actions is to enable the default CRUD operations:

```php
public function index()
{
    $this->setPage();

    // Enable default actions (view, edit, delete)
    $this->table->lists('users', ['name', 'email', 'created_at'], true);

    return $this->render();
}
```

### Selective Action Enabling

Enable only specific default actions:

```php
public function index()
{
    $this->setPage();

    // Enable only view and edit actions
    $this->table->lists('users', ['name', 'email'], [
        'view' => true,
        'edit' => true,
        'delete' => false
    ]);

    return $this->render();
}
```

## Built-in Actions

### Standard CRUD Actions

CanvaStack provides these built-in actions:

```php
// All built-in actions
$this->table->lists('users', ['name', 'email'], [
    'view' => true,      // View record details
    'edit' => true,      // Edit record
    'delete' => true,    // Delete record (with confirmation)
    'duplicate' => true, // Duplicate record
    'restore' => true,   // Restore soft-deleted record
    'force_delete' => true // Permanently delete record
]);
```

### Action URLs and Routes

Built-in actions automatically generate URLs based on your resource routes:

```php
// Assumes these routes exist:
Route::resource('users', UserController::class);

// Generated URLs:
// View: /users/{id}
// Edit: /users/{id}/edit
// Delete: DELETE /users/{id}
// Duplicate: POST /users/{id}/duplicate
```

## Custom Actions

### Basic Custom Actions

Add custom action buttons with specific functionality:

```php
public function index()
{
    $this->setPage();

    // Define custom actions
    $this->table->setActions([
        'send_email' => [
            'label' => 'Send Email',
            'url' => '/users/{id}/send-email',
            'class' => 'btn btn-primary btn-sm',
            'icon' => 'fas fa-envelope'
        ],
        'view_profile' => [
            'label' => 'Profile',
            'url' => '/users/{id}/profile',
            'class' => 'btn btn-info btn-sm',
            'icon' => 'fas fa-user'
        ]
    ]);

    // Enable custom actions along with built-in ones
    $this->table->lists('users', ['name', 'email'], [
        'view' => true,
        'edit' => true,
        'send_email' => true,
        'view_profile' => true
    ]);

    return $this->render();
}
```

### Advanced Custom Actions

Custom actions with multiple configuration options:

```php
$this->table->setActions([
    'approve_user' => [
        'label' => 'Approve',
        'url' => '/users/{id}/approve',
        'class' => 'btn btn-success btn-sm',
        'icon' => 'fas fa-check',
        'method' => 'POST',
        'confirm' => [
            'enabled' => true,
            'title' => 'Approve User',
            'message' => 'Are you sure you want to approve this user?',
            'confirm_text' => 'Yes, Approve',
            'cancel_text' => 'Cancel'
        ],
        'ajax' => [
            'enabled' => true,
            'reload_table' => true,
            'success_message' => 'User approved successfully!'
        ]
    ],
    'generate_report' => [
        'label' => 'Report',
        'url' => '/users/{id}/report',
        'class' => 'btn btn-secondary btn-sm',
        'icon' => 'fas fa-file-pdf',
        'target' => '_blank',
        'download' => true
    ]
]);
```

## Action Conditions

### Conditional Actions

Show actions based on record data or user permissions:

```php
$this->table->setActions([
    'activate' => [
        'label' => 'Activate',
        'url' => '/users/{id}/activate',
        'class' => 'btn btn-success btn-sm',
        'icon' => 'fas fa-power-off',
        'condition' => function($row) {
            return !$row->active; // Only show for inactive users
        }
    ],
    'deactivate' => [
        'label' => 'Deactivate',
        'url' => '/users/{id}/deactivate',
        'class' => 'btn btn-warning btn-sm',
        'icon' => 'fas fa-power-off',
        'condition' => function($row) {
            return $row->active; // Only show for active users
        }
    ],
    'edit_admin' => [
        'label' => 'Admin Edit',
        'url' => '/users/{id}/admin-edit',
        'class' => 'btn btn-danger btn-sm',
        'icon' => 'fas fa-user-shield',
        'condition' => function($row) {
            return auth()->user()->hasRole('super-admin') && $row->role !== 'admin';
        }
    ]
]);
```

### Dynamic Action Properties

Change action properties based on record data:

```php
$this->table->setActions([
    'status_toggle' => [
        'label' => function($row) {
            return $row->active ? 'Deactivate' : 'Activate';
        },
        'url' => '/users/{id}/toggle-status',
        'class' => function($row) {
            return $row->active ? 'btn btn-warning btn-sm' : 'btn btn-success btn-sm';
        },
        'icon' => function($row) {
            return $row->active ? 'fas fa-eye-slash' : 'fas fa-eye';
        },
        'method' => 'POST'
    ]
]);
```

## Modal Integration

### Basic Modal Actions

Open actions in modal windows:

```php
$this->table->setActions([
    'quick_edit' => [
        'label' => 'Quick Edit',
        'url' => '/users/{id}/quick-edit',
        'class' => 'btn btn-primary btn-sm',
        'icon' => 'fas fa-edit',
        'modal' => [
            'enabled' => true,
            'size' => 'modal-lg',
            'title' => 'Quick Edit User'
        ]
    ],
    'view_details' => [
        'label' => 'Details',
        'url' => '/users/{id}/details',
        'class' => 'btn btn-info btn-sm',
        'icon' => 'fas fa-info-circle',
        'modal' => [
            'enabled' => true,
            'size' => 'modal-xl',
            'title' => 'User Details',
            'backdrop' => 'static',
            'keyboard' => false
        ]
    ]
]);
```

### Advanced Modal Configuration

Modals with custom behavior and events:

```php
$this->table->setActions([
    'advanced_modal' => [
        'label' => 'Advanced',
        'url' => '/users/{id}/advanced',
        'class' => 'btn btn-primary btn-sm',
        'modal' => [
            'enabled' => true,
            'size' => 'modal-lg',
            'title' => function($row) {
                return 'Advanced Settings for ' . $row->name;
            },
            'backdrop' => 'static',
            'keyboard' => false,
            'centered' => true,
            'scrollable' => true,
            'fade' => true,
            'events' => [
                'onShow' => 'function(modal, data) { console.log("Modal showing", data); }',
                'onShown' => 'function(modal, data) { initAdvancedForm(data.id); }',
                'onHide' => 'function(modal, data) { cleanupForm(); }'
            ]
        ]
    ]
]);
```

## AJAX Actions

### Basic AJAX Actions

Perform actions without page reload:

```php
$this->table->setActions([
    'toggle_status' => [
        'label' => 'Toggle Status',
        'url' => '/users/{id}/toggle-status',
        'class' => 'btn btn-warning btn-sm',
        'icon' => 'fas fa-toggle-on',
        'method' => 'POST',
        'ajax' => [
            'enabled' => true,
            'reload_table' => true,
            'success_message' => 'Status updated successfully!',
            'error_message' => 'Failed to update status.'
        ]
    ],
    'send_notification' => [
        'label' => 'Notify',
        'url' => '/users/{id}/notify',
        'class' => 'btn btn-info btn-sm',
        'icon' => 'fas fa-bell',
        'method' => 'POST',
        'ajax' => [
            'enabled' => true,
            'reload_table' => false,
            'success_message' => 'Notification sent!',
            'confirm' => [
                'enabled' => true,
                'message' => 'Send notification to this user?'
            ]
        ]
    ]
]);
```

### Advanced AJAX Configuration

AJAX actions with custom handlers and callbacks:

```php
$this->table->setActions([
    'complex_ajax' => [
        'label' => 'Process',
        'url' => '/users/{id}/process',
        'class' => 'btn btn-primary btn-sm',
        'method' => 'POST',
        'ajax' => [
            'enabled' => true,
            'reload_table' => true,
            'loading_text' => 'Processing...',
            'success_callback' => 'function(response, row) { 
                showSuccessToast(response.message); 
                updateRowData(row.id, response.data);
            }',
            'error_callback' => 'function(xhr, row) { 
                showErrorToast("Process failed: " + xhr.responseJSON.message); 
            }',
            'before_send' => 'function(xhr, row) { 
                return confirm("Process user " + row.name + "?"); 
            }',
            'complete' => 'function(xhr, row) { 
                hideLoadingIndicator(); 
            }'
        ]
    ]
]);
```

## Bulk Actions

### Enabling Bulk Actions

Add bulk operations for multiple records:

```php
public function index()
{
    $this->setPage();

    // Enable bulk selection
    $this->table->bulkActions(true);

    // Define bulk actions
    $this->table->setBulkActions([
        'bulk_delete' => [
            'label' => 'Delete Selected',
            'url' => '/users/bulk-delete',
            'class' => 'btn btn-danger',
            'icon' => 'fas fa-trash',
            'method' => 'POST',
            'confirm' => [
                'enabled' => true,
                'message' => 'Are you sure you want to delete selected users?'
            ]
        ],
        'bulk_activate' => [
            'label' => 'Activate Selected',
            'url' => '/users/bulk-activate',
            'class' => 'btn btn-success',
            'icon' => 'fas fa-check',
            'method' => 'POST'
        ],
        'bulk_export' => [
            'label' => 'Export Selected',
            'url' => '/users/bulk-export',
            'class' => 'btn btn-info',
            'icon' => 'fas fa-download',
            'method' => 'POST',
            'target' => '_blank'
        ]
    ]);

    $this->table->lists('users', ['name', 'email', 'status'], true);

    return $this->render();
}
```

### Bulk Action Handlers

Handle bulk actions in your controller:

```php
public function bulkDelete(Request $request)
{
    $ids = $request->input('ids', []);
    
    if (empty($ids)) {
        return response()->json(['error' => 'No items selected'], 400);
    }

    try {
        User::whereIn('id', $ids)->delete();
        
        return response()->json([
            'success' => true,
            'message' => count($ids) . ' users deleted successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to delete users'], 500);
    }
}

public function bulkActivate(Request $request)
{
    $ids = $request->input('ids', []);
    
    User::whereIn('id', $ids)->update(['active' => true]);
    
    return response()->json([
        'success' => true,
        'message' => count($ids) . ' users activated'
    ]);
}
```

## Action Permissions

### Permission-Based Actions

Integrate with Laravel's authorization system:

```php
$this->table->setActions([
    'edit' => [
        'label' => 'Edit',
        'url' => '/users/{id}/edit',
        'class' => 'btn btn-primary btn-sm',
        'permission' => 'users.edit',
        'condition' => function($row) {
            return auth()->user()->can('update', $row);
        }
    ],
    'delete' => [
        'label' => 'Delete',
        'url' => '/users/{id}',
        'class' => 'btn btn-danger btn-sm',
        'method' => 'DELETE',
        'permission' => 'users.delete',
        'condition' => function($row) {
            return auth()->user()->can('delete', $row) && $row->id !== auth()->id();
        }
    ]
]);
```

### Role-Based Actions

Show different actions based on user roles:

```php
$this->table->setActions([
    'admin_edit' => [
        'label' => 'Admin Edit',
        'url' => '/users/{id}/admin-edit',
        'class' => 'btn btn-warning btn-sm',
        'roles' => ['admin', 'super-admin']
    ],
    'manager_approve' => [
        'label' => 'Approve',
        'url' => '/users/{id}/approve',
        'class' => 'btn btn-success btn-sm',
        'roles' => ['manager', 'admin'],
        'condition' => function($row) {
            return $row->status === 'pending';
        }
    ]
]);
```

## Styling and Icons

### Custom Styling

Customize action button appearance:

```php
$this->table->setActions([
    'premium_action' => [
        'label' => 'Premium',
        'url' => '/users/{id}/premium',
        'class' => 'btn btn-gradient-primary btn-sm shadow-lg',
        'icon' => 'fas fa-crown text-warning',
        'style' => 'border-radius: 20px; font-weight: bold;',
        'attributes' => [
            'data-toggle' => 'tooltip',
            'data-placement' => 'top',
            'title' => 'Upgrade to Premium'
        ]
    ]
]);
```

### Icon Libraries

Support for multiple icon libraries:

```php
$this->table->setActions([
    'fontawesome' => [
        'label' => 'FontAwesome',
        'icon' => 'fas fa-star',
        'url' => '/action1'
    ],
    'bootstrap_icons' => [
        'label' => 'Bootstrap Icons',
        'icon' => 'bi bi-heart-fill',
        'url' => '/action2'
    ],
    'material_icons' => [
        'label' => 'Material Icons',
        'icon' => 'material-icons favorite',
        'url' => '/action3'
    ],
    'custom_svg' => [
        'label' => 'Custom SVG',
        'icon' => '<svg width="16" height="16">...</svg>',
        'url' => '/action4'
    ]
]);
```

## Advanced Examples

### Multi-Step Actions

Actions that require multiple steps:

```php
$this->table->setActions([
    'multi_step_process' => [
        'label' => 'Process',
        'url' => '/users/{id}/process',
        'class' => 'btn btn-primary btn-sm',
        'steps' => [
            [
                'title' => 'Confirm Process',
                'message' => 'This will process the user data. Continue?',
                'confirm_text' => 'Yes, Continue'
            ],
            [
                'title' => 'Select Options',
                'url' => '/users/{id}/process-options',
                'modal' => true
            ],
            [
                'title' => 'Final Confirmation',
                'message' => 'Ready to process with selected options?',
                'confirm_text' => 'Process Now'
            ]
        ]
    ]
]);
```

### Context-Aware Actions

Actions that change based on context:

```php
$this->table->setActions([
    'context_action' => [
        'label' => function($row, $context) {
            switch($context['view_mode']) {
                case 'admin':
                    return 'Admin Action';
                case 'manager':
                    return 'Manager Action';
                default:
                    return 'User Action';
            }
        },
        'url' => function($row, $context) {
            return "/users/{$row->id}/action?mode={$context['view_mode']}";
        },
        'class' => function($row, $context) {
            return $context['view_mode'] === 'admin' 
                ? 'btn btn-danger btn-sm' 
                : 'btn btn-primary btn-sm';
        }
    ]
]);
```

### Action Groups

Group related actions together:

```php
$this->table->setActionGroups([
    'user_management' => [
        'label' => 'User Management',
        'icon' => 'fas fa-users',
        'class' => 'btn btn-secondary btn-sm dropdown-toggle',
        'actions' => [
            'edit' => [
                'label' => 'Edit Profile',
                'url' => '/users/{id}/edit',
                'icon' => 'fas fa-edit'
            ],
            'change_password' => [
                'label' => 'Change Password',
                'url' => '/users/{id}/password',
                'icon' => 'fas fa-key'
            ],
            'permissions' => [
                'label' => 'Permissions',
                'url' => '/users/{id}/permissions',
                'icon' => 'fas fa-shield-alt'
            ]
        ]
    ],
    'communication' => [
        'label' => 'Communication',
        'icon' => 'fas fa-comments',
        'actions' => [
            'send_email' => [
                'label' => 'Send Email',
                'url' => '/users/{id}/email',
                'icon' => 'fas fa-envelope'
            ],
            'send_sms' => [
                'label' => 'Send SMS',
                'url' => '/users/{id}/sms',
                'icon' => 'fas fa-sms'
            ]
        ]
    ]
]);
```

---

## Related Documentation

- [Basic Usage](../basic-usage.md) - Basic table setup with actions
- [API Reference](../api/objects.md) - Complete action method documentation
- [Security Features](../advanced/security.md) - Action security and permissions
- [Examples](../examples/basic.md) - Real-world action examples