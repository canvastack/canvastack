# ⚡ **ACTION BUTTON SYSTEM**

## 📋 **TABLE OF CONTENTS**
1. [Feature Overview](#feature-overview)
2. [Button Types & Functionality](#button-types--functionality)
3. [Technical Implementation](#technical-implementation)
4. [Component Architecture](#component-architecture)
5. [Responsive Design](#responsive-design)
6. [Configuration Options](#configuration-options)
7. [Usage Examples](#usage-examples)
8. [Customization Guide](#customization-guide)

---

## 🎯 **FEATURE OVERVIEW**

The Action Button System provides a comprehensive, flexible interface for table row actions. It supports multiple button types, responsive design, and extensive customization options while maintaining consistent styling and behavior across the application.

### **Key Features:**
✅ **Multiple Button Types** - View, Edit, Delete, Custom actions  
✅ **Responsive Design** - Desktop buttons + mobile dropdown  
✅ **Icon Integration** - Font Awesome icons with tooltips  
✅ **Permission-Based** - Show/hide based on user permissions  
✅ **Customizable Styling** - Flexible CSS classes and colors  
✅ **Tooltip Support** - Helpful hover information  
✅ **Mobile Optimization** - Dropdown menu for small screens  
✅ **Extensible Architecture** - Easy to add custom actions  

---

## 🔘 **BUTTON TYPES & FUNCTIONALITY**

### **1. View Button**
```php
// Purpose: Navigate to record detail view
// Icon: fa-eye
// Color: btn-info (blue)
// Action: GET request to show route
```

### **2. Edit Button**
```php
// Purpose: Navigate to record edit form
// Icon: fa-edit
// Color: btn-warning (yellow)
// Action: GET request to edit route
```

### **3. Delete Button**
```php
// Purpose: Delete record with confirmation
// Icon: fa-trash-o / fa-recycle (restore)
// Color: btn-danger (red) / btn-warning (restore)
// Action: Modal confirmation → DELETE request
```

### **4. Custom Buttons**
```php
// Purpose: Custom business logic actions
// Icon: Configurable
// Color: Configurable
// Action: Configurable (GET/POST/custom)
```

---

## 🏗️ **TECHNICAL IMPLEMENTATION**

### **Core Button Generation Logic:**

#### **File**: `Library/Components/Utility/Html/TableUi.php`

```php
public static function tableRowAction(
    $view = false, 
    $edit = false, 
    $delete = false, 
    $new = false, 
    $delete_id = null, 
    $restoreDeleted = false, 
    $table = null
): string {
    // Initialize button variables
    $buttonView = false;
    $buttonEdit = false;
    $buttonDelete = false;
    $buttonNew = false;
    
    // Mobile button variants
    $buttonViewMobile = false;
    $buttonEditMobile = false;
    $buttonDeleteMobile = false;
    $buttonNewMobile = false;

    // VIEW BUTTON GENERATION
    if (false != $view) {
        if (true === $restoreDeleted) {
            $attrs = [
                'readonly' => true,
                'disabled' => true,
                'class' => 'btn btn-info btn-xs',
                'data-toggle' => 'tooltip',
                'data-placement' => 'top',
                'data-original-title' => 'View (Read Only)',
            ];
        } else {
            $attrs = [
                'class' => 'btn btn-info btn-xs',
                'data-toggle' => 'tooltip',
                'data-placement' => 'top',
                'data-original-title' => 'View',
            ];
        }
        
        $buttonViewAttribute = \Canvastack\Canvastack\Library\Components\Utility\Canvatility::attributesToString($attrs);
        $buttonView = '<a href="'.$view.'" '.$buttonViewAttribute.'><i class="fa fa-eye"></i></a>';
        $buttonViewMobile = '<li><a href="'.$view.'" class="tooltip-info" data-rel="tooltip" title="View"><span class="blue"><i class="fa fa-eye bigger-120"></i></span></a></li>';
    }

    // EDIT BUTTON GENERATION
    if (false != $edit) {
        if (true === $restoreDeleted) {
            $attrs = [
                'readonly' => true,
                'disabled' => true,
                'class' => 'btn btn-warning btn-xs',
                'data-toggle' => 'tooltip',
                'data-placement' => 'top',
                'data-original-title' => 'Edit (Disabled)',
            ];
        } else {
            $attrs = [
                'class' => 'btn btn-warning btn-xs',
                'data-toggle' => 'tooltip',
                'data-placement' => 'top',
                'data-original-title' => 'Edit',
            ];
        }
        
        $buttonEditAttribute = \Canvastack\Canvastack\Library\Components\Utility\Canvatility::attributesToString($attrs);
        $buttonEdit = '<a href="'.$edit.'" '.$buttonEditAttribute.'><i class="fa fa-edit"></i></a>';
        $buttonEditMobile = '<li><a href="'.$edit.'" class="tooltip-info" data-rel="tooltip" title="Edit"><span class="green"><i class="fa fa-edit bigger-120"></i></span></a></li>';
    }

    // DELETE BUTTON GENERATION (with modal confirmation)
    if (false != $delete && null != $delete_id) {
        $deleteURL = is_array($delete) ? $delete[0] : $delete;
        
        if (true === $restoreDeleted) {
            $buttonDeleteAttribute = \Canvastack\Canvastack\Library\Components\Utility\Canvatility::attributesToString([
                'class' => 'btn btn-warning btn-xs',
                'data-toggle' => 'tooltip',
                'data-placement' => 'top',
                'data-original-title' => 'Restore',
            ]);
            $iconDeleteAttribute = 'fa fa-recycle';
        }

        if (!empty($deleteURL)) {
            // Generate unique modal ID for this delete action
            $modalId = 'deleteModal_' . md5($deleteURL . $delete_id);
            $formId = 'deleteForm_' . md5($deleteURL . $delete_id);
            
            // Create hidden form for actual deletion
            $delete_action = '<form id="'.$formId.'" action="'.action($deleteURL, $delete_id).'" method="post" style="display:none;">'.csrf_field().'<input name="_method" type="hidden" value="DELETE"></form>';
            
            // Create button that triggers modal instead of direct submission
            $buttonDeleteAttribute = \Canvastack\Canvastack\Library\Components\Utility\Canvatility::attributesToString([
                'class' => 'btn btn-danger btn-xs btn_delete_modal',
                'data-toggle' => 'modal',
                'data-target' => '#'.$modalId,
                'data-form-id' => $formId,
                'data-record-id' => $delete_id,
                'data-table-name' => $table ?? 'record',
                'title' => $restoreDeleted ? 'Restore' : 'Delete',
            ]);
            
            $buttonDelete = $delete_action.'<button '.$buttonDeleteAttribute.' type="button"><i class="'.$iconDeleteAttribute.'"></i></button>';
        } else {
            $buttonDelete = '<button '.$buttonDeleteAttribute.' type="button" disabled><i class="'.$iconDeleteAttribute.'"></i></button>';
        }
        $buttonDeleteMobile = '<li><a href="'.$delete.'" class="tooltip-error btn_delete" data-rel="tooltip" title="Delete"><span class="red"><i class="fa fa-trash-o bigger-120"></i></span></a></li>';
    }

    // CUSTOM BUTTONS GENERATION
    if (false != $new && is_array($new)) {
        foreach ($new as $row_url => $row_config) {
            $row_title = $row_config['title'] ?? 'Action';
            $row_icon = $row_config['icon'] ?? 'fa-cog';
            $row_class = $row_config['class'] ?? 'btn-primary';
            
            $buttonNew .= '<a href="'.$row_url.'" class="btn '.$row_class.' btn-xs" data-toggle="tooltip" data-placement="top" data-original-title="'.$row_title.'"><i class="fa '.$row_icon.'"></i></a>';
            $buttonNewMobile .= '<li><a href="'.$row_url.'" class="tooltip-error" data-rel="tooltip" title="'.$row_title.'"><span class="red"><i class="fa fa-'.$row_icon.' bigger-120"></i></span></a></li>';
        }
    }

    $buttons = $buttonView.$buttonEdit.$buttonDelete.$buttonNew;
    $buttonsMobile = $buttonViewMobile.$buttonEditMobile.$buttonDeleteMobile.$buttonNewMobile;

    // Generate delete confirmation modal if delete button exists
    $modalHtml = '';
    if ($buttonDelete && !empty($deleteURL) && $delete_id) {
        $modalId = 'deleteModal_' . md5($deleteURL . $delete_id);
        $formId = 'deleteForm_' . md5($deleteURL . $delete_id);
        
        // Try to get table name from current route or use default
        $tableName = 'record';
        try {
            $currentRoute = function_exists('canvastack_current_route') ? \canvastack_current_route() : null;
            if ($currentRoute && isset($currentRoute->uri)) {
                $pathParts = explode('/', trim($currentRoute->uri, '/'));
                if (count($pathParts) >= 2) {
                    $tableName = end($pathParts) === 'index' ? prev($pathParts) : end($pathParts);
                }
            }
        } catch (\Throwable $e) {
            $tableName = 'record';
        }
        
        $modalHtml = self::generateDeleteConfirmationModal($modalId, $formId, $tableName, (string)$delete_id, $restoreDeleted);
    }

    // Follow legacy wrapper exactly + append modal
    return '<div class="action-buttons-box"><div class="hidden-sm hidden-xs action-buttons">'.$buttons.'</div><div class="hidden-md hidden-lg"><div class="inline pos-rel"><button class="btn btn-minier btn-yellow dropdown-toggle" data-toggle="dropdown" data-position="auto"><i class="fa fa-caret-down icon-only bigger-120"></i></button><ul class="dropdown-menu dropdown-only-icon dropdown-yellow dropdown-menu-right dropdown-caret dropdown-close">'.$buttonsMobile.'</ul></div></div></div>' . $modalHtml;
}
```

---

## 🏛️ **COMPONENT ARCHITECTURE**

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        ACTION BUTTON ARCHITECTURE                          │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────────────┐ │
│  │   DESKTOP       │    │   MOBILE        │    │   MODAL                 │ │
│  │   BUTTONS       │    │   DROPDOWN      │    │   INTEGRATION           │ │
│  └─────────────────┘    └─────────────────┘    └─────────────────────────┘ │
│           │                       │                         │               │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────────────┐ │
│  │ • Inline Buttons│    │ • Dropdown Menu │    │ • Delete Confirmation   │ │
│  │ • Icon + Tooltip│    │ • Touch-Friendly│    │ • Filter Modal          │ │
│  │ • Color Coding  │    │ • Compact View  │    │ • Custom Modals         │ │
│  │ • Hover Effects │    │ • Icon + Text   │    │ • Form Integration      │ │
│  └─────────────────┘    └─────────────────┘    └─────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘
```

### **Responsive Layout Structure:**

```html
<div class="action-buttons-box">
    <!-- DESKTOP VIEW (hidden on mobile) -->
    <div class="hidden-sm hidden-xs action-buttons">
        <a href="/view/1" class="btn btn-info btn-xs" title="View">
            <i class="fa fa-eye"></i>
        </a>
        <a href="/edit/1" class="btn btn-warning btn-xs" title="Edit">
            <i class="fa fa-edit"></i>
        </a>
        <button class="btn btn-danger btn-xs btn_delete_modal" title="Delete">
            <i class="fa fa-trash-o"></i>
        </button>
    </div>
    
    <!-- MOBILE VIEW (hidden on desktop) -->
    <div class="hidden-md hidden-lg">
        <div class="inline pos-rel">
            <button class="btn btn-minier btn-yellow dropdown-toggle" 
                    data-toggle="dropdown">
                <i class="fa fa-caret-down icon-only bigger-120"></i>
            </button>
            <ul class="dropdown-menu dropdown-only-icon dropdown-yellow 
                       dropdown-menu-right dropdown-caret dropdown-close">
                <li>
                    <a href="/view/1" title="View">
                        <span class="blue">
                            <i class="fa fa-eye bigger-120"></i>
                        </span>
                    </a>
                </li>
                <li>
                    <a href="/edit/1" title="Edit">
                        <span class="green">
                            <i class="fa fa-edit bigger-120"></i>
                        </span>
                    </a>
                </li>
                <li>
                    <a href="/delete/1" class="btn_delete" title="Delete">
                        <span class="red">
                            <i class="fa fa-trash-o bigger-120"></i>
                        </span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
```

---

## 📱 **RESPONSIVE DESIGN**

### **Desktop Layout (≥768px):**
```css
.hidden-sm.hidden-xs.action-buttons {
    display: flex;
    gap: 2px;
    align-items: center;
}

.action-buttons .btn {
    min-width: 30px;
    height: 30px;
    padding: 5px 8px;
    font-size: 12px;
}

.action-buttons .btn i {
    font-size: 14px;
}
```

### **Mobile Layout (<768px):**
```css
.hidden-md.hidden-lg {
    display: block;
}

.dropdown-toggle {
    min-width: 35px;
    height: 35px;
    padding: 8px;
}

.dropdown-menu {
    min-width: 120px;
    padding: 5px 0;
}

.dropdown-menu li a {
    padding: 8px 15px;
    display: flex;
    align-items: center;
}

.dropdown-menu li a span {
    margin-right: 8px;
}
```

### **Responsive Breakpoints:**
```css
/* Extra Small Devices (phones, <576px) */
@media (max-width: 575.98px) {
    .action-buttons-box {
        text-align: center;
    }
    
    .dropdown-menu {
        right: 0 !important;
        left: auto !important;
    }
}

/* Small Devices (landscape phones, 576px-767.98px) */
@media (min-width: 576px) and (max-width: 767.98px) {
    .dropdown-toggle {
        min-width: 40px;
        height: 40px;
    }
}

/* Medium Devices (tablets, 768px-991.98px) */
@media (min-width: 768px) and (max-width: 991.98px) {
    .action-buttons .btn {
        min-width: 32px;
        height: 32px;
        padding: 6px 9px;
    }
}

/* Large Devices (desktops, ≥992px) */
@media (min-width: 992px) {
    .action-buttons {
        justify-content: flex-end;
    }
    
    .action-buttons .btn {
        margin-left: 2px;
    }
}
```

---

## ⚙️ **CONFIGURATION OPTIONS**

### **Basic Button Configuration:**
```php
$buttonConfig = [
    'view' => [
        'enabled' => true,
        'url' => '/users/{id}',
        'icon' => 'fa-eye',
        'class' => 'btn-info',
        'tooltip' => 'View Details',
        'permission' => 'view',
    ],
    'edit' => [
        'enabled' => true,
        'url' => '/users/{id}/edit',
        'icon' => 'fa-edit',
        'class' => 'btn-warning',
        'tooltip' => 'Edit Record',
        'permission' => 'edit',
    ],
    'delete' => [
        'enabled' => true,
        'url' => 'UserController@destroy',
        'icon' => 'fa-trash-o',
        'class' => 'btn-danger',
        'tooltip' => 'Delete Record',
        'permission' => 'delete',
        'confirm' => true,
    ],
];
```

### **Custom Button Configuration:**
```php
$customButtons = [
    '/users/{id}/activate' => [
        'title' => 'Activate User',
        'icon' => 'fa-check-circle',
        'class' => 'btn-success',
        'method' => 'POST',
        'confirm' => 'Are you sure you want to activate this user?',
        'permission' => 'activate',
    ],
    '/users/{id}/suspend' => [
        'title' => 'Suspend User',
        'icon' => 'fa-ban',
        'class' => 'btn-warning',
        'method' => 'POST',
        'confirm' => true,
        'permission' => 'suspend',
    ],
    '/users/{id}/export' => [
        'title' => 'Export Data',
        'icon' => 'fa-download',
        'class' => 'btn-info',
        'method' => 'GET',
        'target' => '_blank',
        'permission' => 'export',
    ],
];
```

### **Responsive Configuration:**
```php
$responsiveConfig = [
    'mobile_dropdown' => true,          // Enable mobile dropdown
    'mobile_threshold' => 768,          // Breakpoint for mobile view
    'desktop_inline' => true,           // Show buttons inline on desktop
    'button_spacing' => '2px',          // Space between buttons
    'tooltip_placement' => 'top',       // Tooltip position
    'dropdown_direction' => 'right',    // Dropdown menu direction
];
```

### **Permission Configuration:**
```php
$permissionConfig = [
    'check_permissions' => true,        // Enable permission checking
    'permission_method' => 'can',       // Laravel authorization method
    'hide_unauthorized' => true,        // Hide buttons without permission
    'show_disabled' => false,           // Show disabled buttons
    'permission_cache' => true,         // Cache permission results
];
```

---

## 💻 **USAGE EXAMPLES**

### **1. Basic Action Buttons:**
```php
// In your table configuration
$table = canvastack_table([
    'model' => User::class,
    'columns' => ['name', 'email', 'created_at'],
    'actions' => [
        'view' => '/users/{id}',
        'edit' => '/users/{id}/edit',
        'delete' => 'UserController@destroy',
    ],
]);
```

### **2. Custom Action Buttons:**
```php
// Custom buttons with specific configuration
$table = canvastack_table([
    'model' => Order::class,
    'columns' => ['id', 'customer', 'total', 'status'],
    'actions' => [
        'view' => '/orders/{id}',
        'edit' => '/orders/{id}/edit',
        'delete' => 'OrderController@destroy',
        'custom' => [
            '/orders/{id}/invoice' => [
                'title' => 'Generate Invoice',
                'icon' => 'fa-file-pdf-o',
                'class' => 'btn-success',
                'target' => '_blank',
            ],
            '/orders/{id}/ship' => [
                'title' => 'Mark as Shipped',
                'icon' => 'fa-truck',
                'class' => 'btn-primary',
                'method' => 'POST',
                'confirm' => 'Mark this order as shipped?',
            ],
        ],
    ],
]);
```

### **3. Permission-Based Buttons:**
```php
// Controller method
public function index()
{
    $table = canvastack_table([
        'model' => User::class,
        'columns' => ['name', 'email', 'role', 'created_at'],
        'actions' => function($record) {
            $actions = [];
            
            // View button (always available)
            $actions['view'] = '/users/' . $record->id;
            
            // Edit button (only if user can edit)
            if (auth()->user()->can('update', $record)) {
                $actions['edit'] = '/users/' . $record->id . '/edit';
            }
            
            // Delete button (only if user can delete)
            if (auth()->user()->can('delete', $record)) {
                $actions['delete'] = 'UserController@destroy';
            }
            
            // Admin-only buttons
            if (auth()->user()->isAdmin()) {
                $actions['custom'] = [
                    '/users/' . $record->id . '/impersonate' => [
                        'title' => 'Impersonate',
                        'icon' => 'fa-user-secret',
                        'class' => 'btn-warning',
                    ],
                ];
            }
            
            return $actions;
        },
    ]);
    
    return view('users.index', compact('table'));
}
```

### **4. Conditional Button Display:**
```php
// Dynamic button configuration based on record state
$table = canvastack_table([
    'model' => Order::class,
    'columns' => ['id', 'status', 'total', 'created_at'],
    'actions' => function($record) {
        $actions = [
            'view' => '/orders/' . $record->id,
        ];
        
        // Edit only for pending orders
        if ($record->status === 'pending') {
            $actions['edit'] = '/orders/' . $record->id . '/edit';
        }
        
        // Delete only for cancelled orders
        if ($record->status === 'cancelled') {
            $actions['delete'] = 'OrderController@destroy';
        }
        
        // Status-specific actions
        switch ($record->status) {
            case 'pending':
                $actions['custom']['/orders/' . $record->id . '/confirm'] = [
                    'title' => 'Confirm Order',
                    'icon' => 'fa-check',
                    'class' => 'btn-success',
                ];
                break;
                
            case 'confirmed':
                $actions['custom']['/orders/' . $record->id . '/ship'] = [
                    'title' => 'Ship Order',
                    'icon' => 'fa-truck',
                    'class' => 'btn-primary',
                ];
                break;
                
            case 'shipped':
                $actions['custom']['/orders/' . $record->id . '/deliver'] = [
                    'title' => 'Mark Delivered',
                    'icon' => 'fa-check-circle',
                    'class' => 'btn-success',
                ];
                break;
        }
        
        return $actions;
    },
]);
```

---

## 🎨 **CUSTOMIZATION GUIDE**

### **1. Custom Button Styling:**
```css
/* Custom button colors */
.btn-custom-primary {
    background-color: #6f42c1;
    border-color: #6f42c1;
    color: white;
}

.btn-custom-primary:hover {
    background-color: #5a32a3;
    border-color: #5a32a3;
}

/* Button size variations */
.btn-xs-custom {
    padding: 3px 6px;
    font-size: 10px;
    line-height: 1.2;
    border-radius: 2px;
}

.btn-sm-custom {
    padding: 5px 10px;
    font-size: 12px;
    line-height: 1.4;
    border-radius: 3px;
}
```

### **2. Custom Icons:**
```php
// Using different icon libraries
$customIcons = [
    'view' => 'fas fa-eye',           // Font Awesome 5
    'edit' => 'material-icons edit',  // Material Icons
    'delete' => 'ion-trash-a',        // Ionicons
    'custom' => 'feather-settings',   // Feather Icons
];
```

### **3. Custom Tooltips:**
```javascript
// Enhanced tooltip configuration
$(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip({
        placement: 'top',
        trigger: 'hover',
        delay: { show: 500, hide: 100 },
        html: true,
        template: '<div class="tooltip custom-tooltip" role="tooltip">' +
                  '<div class="arrow"></div>' +
                  '<div class="tooltip-inner"></div>' +
                  '</div>'
    });
});
```

### **4. Custom Mobile Layout:**
```css
/* Alternative mobile layout - horizontal scroll */
@media (max-width: 767.98px) {
    .action-buttons-mobile-scroll {
        display: flex;
        overflow-x: auto;
        gap: 5px;
        padding: 5px 0;
    }
    
    .action-buttons-mobile-scroll .btn {
        flex-shrink: 0;
        min-width: 40px;
        height: 40px;
    }
}
```

### **5. Animation Effects:**
```css
/* Button hover animations */
.action-buttons .btn {
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.action-buttons .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.action-buttons .btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255,255,255,0.3);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.3s, height 0.3s;
}

.action-buttons .btn:hover::before {
    width: 100%;
    height: 100%;
}
```

---

## 🔧 **ADVANCED FEATURES**

### **1. Bulk Actions:**
```php
// Add bulk action support
$table = canvastack_table([
    'model' => User::class,
    'bulk_actions' => [
        'delete' => [
            'title' => 'Delete Selected',
            'icon' => 'fa-trash',
            'class' => 'btn-danger',
            'confirm' => 'Delete selected records?',
            'url' => '/users/bulk-delete',
        ],
        'export' => [
            'title' => 'Export Selected',
            'icon' => 'fa-download',
            'class' => 'btn-info',
            'url' => '/users/bulk-export',
        ],
    ],
]);
```

### **2. Context Menu:**
```javascript
// Right-click context menu
$(document).on('contextmenu', '.action-buttons-box', function(e) {
    e.preventDefault();
    
    var contextMenu = $('<div class="context-menu">');
    var actions = $(this).find('.action-buttons a, .action-buttons button');
    
    actions.each(function() {
        var title = $(this).attr('title') || $(this).data('original-title');
        var href = $(this).attr('href') || '#';
        var item = $('<div class="context-menu-item">').text(title);
        
        item.on('click', function() {
            if (href !== '#') {
                window.location.href = href;
            } else {
                $(this).trigger('click');
            }
            contextMenu.remove();
        });
        
        contextMenu.append(item);
    });
    
    contextMenu.css({
        position: 'absolute',
        left: e.pageX,
        top: e.pageY,
        zIndex: 9999
    });
    
    $('body').append(contextMenu);
    
    $(document).one('click', function() {
        contextMenu.remove();
    });
});
```

### **3. Keyboard Shortcuts:**
```javascript
// Keyboard shortcuts for actions
$(document).on('keydown', function(e) {
    if (e.ctrlKey || e.metaKey) {
        var selectedRow = $('.table tbody tr.selected');
        if (selectedRow.length) {
            var actions = selectedRow.find('.action-buttons');
            
            switch(e.key) {
                case 'v': // Ctrl+V for View
                    e.preventDefault();
                    actions.find('[title="View"]')[0]?.click();
                    break;
                case 'e': // Ctrl+E for Edit
                    e.preventDefault();
                    actions.find('[title="Edit"]')[0]?.click();
                    break;
                case 'd': // Ctrl+D for Delete
                    e.preventDefault();
                    actions.find('[title="Delete"]')[0]?.click();
                    break;
            }
        }
    }
});
```

---

## 📊 **PERFORMANCE OPTIMIZATION**

### **1. Lazy Loading:**
```javascript
// Lazy load action buttons for large tables
function loadActionButtons(row) {
    var recordId = $(row).data('record-id');
    
    $.ajax({
        url: '/api/table/actions/' + recordId,
        method: 'GET',
        success: function(response) {
            $(row).find('.action-buttons-placeholder').html(response.buttons);
        }
    });
}

// Load buttons when row becomes visible
$(window).on('scroll', function() {
    $('.action-buttons-placeholder:in-viewport').each(function() {
        if (!$(this).hasClass('loaded')) {
            $(this).addClass('loaded');
            loadActionButtons($(this).closest('tr'));
        }
    });
});
```

### **2. Button Caching:**
```php
// Cache button HTML for frequently accessed records
class ActionButtonCache
{
    public static function getButtons($recordId, $recordType, $permissions)
    {
        $cacheKey = "action_buttons_{$recordType}_{$recordId}_" . md5(serialize($permissions));
        
        return Cache::remember($cacheKey, 3600, function() use ($recordId, $recordType, $permissions) {
            return self::generateButtons($recordId, $recordType, $permissions);
        });
    }
    
    private static function generateButtons($recordId, $recordType, $permissions)
    {
        // Generate button HTML based on permissions
        return TableUi::tableRowAction(
            $permissions['view'] ? "/records/{$recordId}" : false,
            $permissions['edit'] ? "/records/{$recordId}/edit" : false,
            $permissions['delete'] ? 'RecordController@destroy' : false,
            null,
            $recordId
        );
    }
}
```

---

*This documentation covers the complete Action Button System. The system provides flexible, responsive, and highly customizable action buttons for table rows with extensive configuration options and performance optimizations.*