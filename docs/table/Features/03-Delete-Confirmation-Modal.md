# 🗑️ **DELETE CONFIRMATION MODAL SYSTEM**

## 📋 **TABLE OF CONTENTS**
1. [Feature Overview](#feature-overview)
2. [Security & Safety Features](#security--safety-features)
3. [Technical Implementation](#technical-implementation)
4. [Component Architecture](#component-architecture)
5. [Data Flow](#data-flow)
6. [Configuration Options](#configuration-options)
7. [Usage Examples](#usage-examples)
8. [Troubleshooting](#troubleshooting)

---

## 🎯 **FEATURE OVERVIEW**

The Delete Confirmation Modal System provides a secure, user-friendly interface for confirming destructive actions. It prevents accidental deletions by requiring explicit user confirmation through a modal dialog with clear messaging and visual warnings.

### **Key Features:**
✅ **Two-Step Confirmation** - Prevents accidental deletions  
✅ **Dynamic Content** - Shows specific record information  
✅ **Visual Safety Warnings** - Clear danger indicators  
✅ **Restore Functionality** - Support for soft delete restoration  
✅ **Mobile Responsive** - Works seamlessly on all devices  
✅ **Fallback Protection** - Browser confirm dialog as backup  
✅ **CSRF Protection** - Secure form submission  
✅ **Unique Modal IDs** - Prevents conflicts with multiple records  

---

## 🔒 **SECURITY & SAFETY FEATURES**

### **1. CSRF Protection**
```php
// Hidden form with CSRF token
$delete_action = '<form id="'.$formId.'" action="'.action($deleteURL, $delete_id).'" method="post" style="display:none;">'.
                 csrf_field().
                 '<input name="_method" type="hidden" value="DELETE">'.
                 '</form>';
```

### **2. Method Spoofing**
```html
<!-- Proper HTTP DELETE method -->
<input name="_method" type="hidden" value="DELETE">
```

### **3. Unique Modal IDs**
```php
// Prevents modal conflicts
$modalId = 'deleteModal_' . md5($deleteURL . $delete_id);
$formId = 'deleteForm_' . md5($deleteURL . $delete_id);
```

### **4. Visual Safety Indicators**
```html
<div class="alert alert-warning">
    <i class="fa fa-exclamation-triangle"></i>
    Anda akan menghapus data dari tabel <strong>user</strong> dengan ID <strong>943</strong>. 
    Apakah Anda yakin ingin menghapusnya?
</div>
```

---

## 🏗️ **TECHNICAL IMPLEMENTATION**

### **Core Components:**

#### **1. Delete Button Generation**
**File**: `Library/Components/Utility/Html/TableUi.php`
```php
if (!empty($deleteURL)) {
    // Generate unique modal ID for this delete action
    $modalId = 'deleteModal_' . md5($deleteURL . $delete_id);
    $formId = 'deleteForm_' . md5($deleteURL . $delete_id);
    
    // Create hidden form for actual deletion
    $delete_action = '<form id="'.$formId.'" action="'.action($deleteURL, $delete_id).'" method="post" style="display:none;">'.
                     csrf_field().
                     '<input name="_method" type="hidden" value="DELETE">'.
                     '</form>';
    
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
    
    $buttonDelete = $delete_action.'<button '.$buttonDeleteAttribute.' type="button">'.
                    '<i class="'.$iconDeleteAttribute.'"></i>'.
                    '</button>';
}
```

#### **2. Modal HTML Generation**
**File**: `Library/Components/Utility/Html/TableUi.php`
```php
public static function generateDeleteConfirmationModal(string $modalId, string $formId, string $tableName, string $recordId, bool $isRestore = false): string
{
    $action = $isRestore ? 'restore' : 'delete';
    $actionText = $isRestore ? 'Restore' : 'Delete';
    $actionIcon = $isRestore ? 'fa-recycle' : 'fa-trash-o';
    $actionColor = $isRestore ? 'btn-warning' : 'btn-danger';
    $actionMessage = $isRestore 
        ? "Anda akan memulihkan data dari tabel <strong>{$tableName}</strong> dengan ID <strong>{$recordId}</strong>. Apakah Anda yakin ingin memulihkannya?"
        : "Anda akan menghapus data dari tabel <strong>{$tableName}</strong> dengan ID <strong>{$recordId}</strong>. Apakah Anda yakin ingin menghapusnya?";

    // Generate modal HTML with proper z-index and body append via JavaScript
    $modalHtml = '<div id="' . $modalId . '" class="modal fade" role="dialog" tabindex="-1" ' .
            'aria-hidden="true" data-backdrop="static" data-keyboard="true" style="z-index: 1060;">' .
            '<div class="modal-dialog modal-md" role="document">' .
                '<div class="modal-content">' .
                    '<div class="modal-header">' .
                        '<h5 class="modal-title">' .
                            '<i class="fa ' . $actionIcon . '"></i> &nbsp; Confirm ' . $actionText .
                        '</h5>' .
                        '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' .
                            '<span aria-hidden="true">×</span>' .
                        '</button>' .
                    '</div>' .
                    '<div class="modal-body">' .
                        '<div class="alert alert-warning">' .
                            '<i class="fa fa-exclamation-triangle"></i> ' .
                            $actionMessage .
                        '</div>' .
                    '</div>' .
                    '<div class="modal-footer">' .
                        '<button type="button" class="btn btn-secondary" data-dismiss="modal">' .
                            '<i class="fa fa-times"></i> No, Cancel' .
                        '</button>' .
                        '<button type="button" class="btn ' . $actionColor . '" onclick="document.getElementById(\'' . $formId . '\').submit(); $(\'#' . $modalId . '\').modal(\'hide\');">' .
                            '<i class="fa ' . $actionIcon . '"></i> Yes, ' . $actionText .
                        '</button>' .
                    '</div>' .
                '</div>' .
            '</div>' .
        '</div>';

    // JavaScript to append modal to body and handle z-index properly
    $script = '<script type="text/javascript">
        $(document).ready(function() {
            // Remove existing modal if exists
            $("#' . $modalId . '").remove();
            
            // Append modal to body to fix z-index issues
            $("body").append(\'' . addslashes($modalHtml) . '\');
            
            console.log("Delete modal appended to body: ' . $modalId . '");
        });
    </script>';

    return $script;
}
```

#### **3. JavaScript Event Handlers**
**File**: `Library/Components/Table/Craft/Scripts.php`
```php
// Simple Delete Confirmation Modal Handler
$(document).on('click', '.btn_delete_modal', function(e) {
    e.preventDefault();
    var $btn = $(this);
    var modalTarget = $btn.data('target');
    
    console.log('Delete button clicked, target modal:', modalTarget);
    
    // Show the modal that was already appended to body
    if ($(modalTarget).length > 0) {
        $(modalTarget).modal('show');
        console.log('Modal shown:', modalTarget);
    } else {
        console.error('Modal not found:', modalTarget);
        // Fallback: show browser confirm dialog
        var recordId = $btn.data('record-id');
        var tableName = $btn.data('table-name');
        if (confirm('Anda akan menghapus data dari tabel ' + tableName + ' dengan ID ' + recordId + '. Apakah Anda yakin?')) {
            var formId = $btn.data('form-id');
            var form = document.getElementById(formId);
            if (form) {
                form.submit();
            }
        }
    }
});

// Handle modal cleanup on hide
$(document).on('hidden.bs.modal', '[id^="deleteModal_"]', function() {
    console.log('Delete modal hidden:', $(this).attr('id'));
});
```

---

## 🏛️ **COMPONENT ARCHITECTURE**

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    DELETE CONFIRMATION ARCHITECTURE                        │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────────────┐ │
│  │   DELETE        │    │   CONFIRMATION  │    │   FORM SUBMISSION       │ │
│  │   TRIGGER       │    │   MODAL         │    │   COMPONENT             │ │
│  └─────────────────┘    └─────────────────┘    └─────────────────────────┘ │
│           │                       │                         │               │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────────────┐ │
│  │ • Delete Button │    │ • Modal HTML    │    │ • Hidden Form           │ │
│  │ • Icon & Style  │    │ • Warning Alert │    │ • CSRF Token            │ │
│  │ • Event Binding │    │ • Action Buttons│    │ • DELETE Method         │ │
│  │ • Data Attrs    │    │ • Dynamic Text  │    │ • Form Submission       │ │
│  └─────────────────┘    └─────────────────┘    └─────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘
```

### **Component Interaction Flow:**

#### **1. Button → Modal → Form**
```
Delete Button Click
        ↓
JavaScript Event Handler
        ↓
Modal Display (with record info)
        ↓
User Confirmation
        ↓
Hidden Form Submission
        ↓
Server Processing
        ↓
Response & Redirect
```

#### **2. Safety Mechanisms:**
```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           SAFETY MECHANISMS                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────────────┐ │
│  │   PREVENTION    │    │   VALIDATION    │    │   FALLBACK              │ │
│  │   LAYER         │    │   LAYER         │    │   LAYER                 │ │
│  └─────────────────┘    └─────────────────┘    └─────────────────────────┘ │
│           │                       │                         │               │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────────────┐ │
│  │ • Modal Dialog  │    │ • CSRF Token    │    │ • Browser Confirm       │ │
│  │ • Two-Step      │    │ • Method Check  │    │ • Console Logging       │ │
│  │ • Visual Warn   │    │ • Auth Check    │    │ • Error Handling        │ │
│  │ • Clear Message │    │ • Permission    │    │ • Graceful Degradation  │ │
│  └─────────────────┘    └─────────────────┘    └─────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 🔄 **DATA FLOW**

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        DELETE CONFIRMATION DATA FLOW                       │
└─────────────────────────────────────────────────────────────────────────────┘

1. INITIAL SETUP (Page Load)
   ┌─────────────┐
   │   Table     │ ──── Renders with Delete Buttons ────┐
   │  Rendering  │                                       │
   └─────────────┘                                       ▼
                                                ┌─────────────────┐
                                                │ For Each Row:   │
                                                │ • Generate      │
                                                │   Unique IDs    │
                                                │ • Create Button │
                                                │ • Create Modal  │
                                                │ • Create Form   │
                                                └─────────────────┘
                                                         │
2. MODAL GENERATION                                      ▼
   ┌──────────────────────────────────────────────────────────────────────────┐
   │ Per-row modal generation with unique identifiers                        │
   │ • modalId = 'deleteModal_' + md5(url + id)                              │
   │ • formId = 'deleteForm_' + md5(url + id)                                │
   │ • Modal HTML with record-specific content                               │
   │ • JavaScript appends modal to <body>                                    │
   └──────────────────────────────────────────────┬───────────────────────────┘
                                                  │
3. USER INTERACTION                               ▼
   ┌─────────────┐                       ┌─────────────────┐
   │    User     │ ──── Clicks Delete ──▶│   JavaScript    │
   │   Clicks    │       Button          │ Event Handler   │
   └─────────────┘                       └─────────────────┘
                                                  │
4. MODAL DISPLAY                                  ▼
   ┌──────────────────────────────────────────────────────────────────────────┐
   │                        MODAL PRESENTATION                                │
   ├──────────────────────────────────────────────────────────────────────────┤
   │                                                                          │
   │  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌───────────┐ │
   │  │   Check     │    │   Show      │    │   Display   │    │  Wait for │ │
   │  │   Modal     │───▶│   Modal     │───▶│   Warning   │───▶│   User    │ │
   │  │   Exists    │    │   Dialog    │    │   Message   │    │  Decision │ │
   │  └─────────────┘    └─────────────┘    └─────────────┘    └───────────┘ │
   │         │                   │                   │               │       │
   │         ▼                   ▼                   ▼               ▼       │
   │  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌───────────┐ │
   │  │ Modal Found │    │ Z-index:    │    │ "Anda akan  │    │ Cancel or │ │
   │  │ in DOM      │    │ 1060        │    │ menghapus   │    │ Confirm   │ │
   │  │             │    │ Backdrop    │    │ data..."    │    │ Button    │ │
   │  └─────────────┘    └─────────────┘    └─────────────┘    └───────────┘ │
   └──────────────────────────────────────────────┬───────────────────────────┘
                                                  │
5. USER DECISION                                  ▼
   ┌─────────────────────────────────────────────────────────────────────────┐
   │                          DECISION BRANCH                                │
   ├─────────────────────────────────────────────────────────────────────────┤
   │                                                                         │
   │  ┌─────────────┐                                    ┌─────────────────┐ │
   │  │   CANCEL    │                                    │    CONFIRM      │ │
   │  │   BUTTON    │                                    │    BUTTON       │ │
   │  └─────────────┘                                    └─────────────────┘ │
   │         │                                                    │         │
   │         ▼                                                    ▼         │
   │  ┌─────────────┐                                    ┌─────────────────┐ │
   │  │ Close Modal │                                    │ Submit Hidden   │ │
   │  │ No Action   │                                    │ Form with CSRF  │ │
   │  │ Taken       │                                    │ & DELETE Method │ │
   │  └─────────────┘                                    └─────────────────┘ │
   └─────────────────────────────────────────────────────────────┬───────────┘
                                                                 │
6. FORM SUBMISSION (If Confirmed)                                ▼
   ┌─────────────────────────────────────────────────────────────────────────┐
   │                         SERVER PROCESSING                               │
   ├─────────────────────────────────────────────────────────────────────────┤
   │                                                                         │
   │  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌───────────┐ │
   │  │   CSRF      │    │   Method    │    │   Auth      │    │  Delete   │ │
   │  │Validation   │───▶│ Validation  │───▶│ Check       │───▶│ Record    │ │
   │  └─────────────┘    └─────────────┘    └─────────────┘    └───────────┘ │
   │         │                   │                   │               │       │
   │         ▼                   ▼                   ▼               ▼       │
   │  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌───────────┐ │
   │  │ Token       │    │ DELETE      │    │ User Can    │    │ Soft or   │ │
   │  │ Matches     │    │ Method      │    │ Delete      │    │ Hard      │ │
   │  │ Session     │    │ Required    │    │ This Record │    │ Delete    │ │
   │  └─────────────┘    └─────────────┘    └─────────────┘    └───────────┘ │
   └─────────────────────────────────────────────┬───────────────────────────┘
                                                 │
7. RESPONSE & FEEDBACK                           ▼
   ┌─────────────┐                      ┌─────────────────┐
   │   Browser   │ ◄──── Redirect ──────│   Laravel       │
   │  Redirects  │       with Flash     │   Response      │
   │  to Index   │       Message        │   (Success)     │
   └─────────────┘                      └─────────────────┘
         │
8. SUCCESS FEEDBACK                      
   ┌─────────────────────────────────────────────────────────────────────────┐
   │                         SUCCESS INDICATORS                              │
   ├─────────────────────────────────────────────────────────────────────────┤
   │ • Flash message: "Record deleted successfully"                          │
   │ • Table refreshes without deleted record                                │
   │ • Modal closes automatically                                            │
   │ • Page redirects to table index                                         │
   │ • Console logs success (for debugging)                                  │
   └─────────────────────────────────────────────────────────────────────────┘
```

---

## ⚙️ **CONFIGURATION OPTIONS**

### **Basic Configuration:**
```php
$deleteConfig = [
    'enabled' => true,                    // Enable/disable delete functionality
    'soft_delete' => true,               // Use soft deletes
    'confirm_modal' => true,             // Show confirmation modal
    'button_text' => '',                 // Button text (empty for icon only)
    'button_icon' => 'fa-trash-o',       // Button icon
    'button_class' => 'btn-danger',      // Button styling
    'modal_size' => 'modal-md',          // Modal size
    'show_record_info' => true,          // Show record details in modal
];
```

### **Restore Configuration:**
```php
$restoreConfig = [
    'enabled' => true,                   // Enable restore functionality
    'button_icon' => 'fa-recycle',       // Restore button icon
    'button_class' => 'btn-warning',     // Restore button styling
    'confirm_message' => 'custom message', // Custom confirmation message
];
```

### **Security Configuration:**
```php
$securityConfig = [
    'csrf_protection' => true,           // Enable CSRF protection
    'method_spoofing' => true,           // Use DELETE method
    'permission_check' => 'delete',      // Required permission
    'rate_limiting' => true,             // Prevent spam deletions
    'audit_log' => true,                 // Log deletion attempts
];
```

### **UI Configuration:**
```php
$uiConfig = [
    'modal_backdrop' => 'static',        // Modal backdrop behavior
    'modal_keyboard' => true,            // Allow ESC to close
    'auto_close' => true,                // Close modal after action
    'animation' => 'fade',               // Modal animation
    'z_index' => 1060,                   // Modal z-index
    'button_tooltip' => true,            // Show button tooltips
];
```

---

## 💻 **USAGE EXAMPLES**

### **1. Basic Delete Implementation:**
```php
// Controller
public function destroy(User $user)
{
    try {
        $user->delete();
        
        return redirect()->route('users.index')
                        ->with('success', 'User deleted successfully.');
    } catch (\Exception $e) {
        return redirect()->back()
                        ->with('error', 'Failed to delete user: ' . $e->getMessage());
    }
}
```

### **2. Soft Delete with Restore:**
```php
// Model
class User extends Model
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];
}

// Controller
public function destroy(User $user)
{
    $user->delete(); // Soft delete
    return redirect()->route('users.index')
                    ->with('success', 'User moved to trash.');
}

public function restore($id)
{
    $user = User::withTrashed()->findOrFail($id);
    $user->restore();
    
    return redirect()->route('users.index')
                    ->with('success', 'User restored successfully.');
}
```

### **3. Custom Delete Logic:**
```php
// Controller with custom logic
public function destroy(Order $order)
{
    // Check if order can be deleted
    if ($order->status === 'completed') {
        return redirect()->back()
                        ->with('error', 'Cannot delete completed orders.');
    }
    
    // Delete related records
    $order->orderItems()->delete();
    $order->payments()->delete();
    
    // Delete main record
    $order->delete();
    
    // Log the deletion
    Log::info('Order deleted', [
        'order_id' => $order->id,
        'user_id' => auth()->id(),
        'timestamp' => now()
    ]);
    
    return redirect()->route('orders.index')
                    ->with('success', 'Order and related data deleted successfully.');
}
```

### **4. Permission-Based Delete:**
```php
// Controller with permission check
public function destroy(User $user)
{
    // Check permissions
    if (!auth()->user()->can('delete', $user)) {
        abort(403, 'Unauthorized to delete this user.');
    }
    
    // Prevent self-deletion
    if ($user->id === auth()->id()) {
        return redirect()->back()
                        ->with('error', 'You cannot delete your own account.');
    }
    
    $user->delete();
    
    return redirect()->route('users.index')
                    ->with('success', 'User deleted successfully.');
}
```

---

## 🎨 **UI CUSTOMIZATION**

### **Modal Styling:**
```css
/* Custom delete modal styles */
.delete-modal .modal-content {
    border: 2px solid #dc3545;
}

.delete-modal .modal-header {
    background-color: #dc3545;
    color: white;
}

.delete-modal .alert-warning {
    border-left: 4px solid #ffc107;
    background-color: #fff3cd;
}

.delete-modal .btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
}

.delete-modal .btn-danger:hover {
    background-color: #c82333;
    border-color: #bd2130;
}
```

### **Button Customization:**
```css
/* Delete button variations */
.btn_delete_modal {
    transition: all 0.3s ease;
}

.btn_delete_modal:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
}

/* Restore button styling */
.btn_delete_modal[title="Restore"] {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #212529;
}

.btn_delete_modal[title="Restore"]:hover {
    background-color: #e0a800;
    border-color: #d39e00;
}
```

### **Responsive Design:**
```css
/* Mobile optimizations */
@media (max-width: 768px) {
    .delete-modal .modal-dialog {
        margin: 10px;
        max-width: calc(100% - 20px);
    }
    
    .delete-modal .modal-footer {
        flex-direction: column;
    }
    
    .delete-modal .modal-footer .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .delete-modal .modal-footer .btn:last-child {
        margin-bottom: 0;
    }
}
```

---

## 🐛 **TROUBLESHOOTING**

### **Common Issues:**

#### **1. Modal Not Appearing**
```javascript
// Debug: Check if modal exists
console.log('Modal exists:', $('#deleteModal_xxx').length > 0);

// Debug: Check button attributes
$('.btn_delete_modal').each(function() {
    console.log('Button target:', $(this).data('target'));
});

// Solution: Ensure modal is appended to body
$(document).ready(function() {
    $('body').append(modalHtml);
});
```

#### **2. Form Not Submitting**
```javascript
// Debug: Check form existence
$('.btn_delete_modal').on('click', function() {
    var formId = $(this).data('form-id');
    console.log('Form exists:', $('#' + formId).length > 0);
});

// Debug: Check form action
$('form[id^="deleteForm_"]').each(function() {
    console.log('Form action:', $(this).attr('action'));
});
```

#### **3. CSRF Token Issues**
```php
// Debug: Check CSRF token
<script>
console.log('CSRF Token:', $('meta[name="csrf-token"]').attr('content'));
</script>

// Solution: Ensure token is included
<form>
    {{ csrf_field() }}
    <!-- or -->
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
</form>
```

#### **4. Z-Index Problems**
```css
/* Solution: Ensure proper z-index */
.modal {
    z-index: 1060 !important;
}

.modal-backdrop {
    z-index: 1050 !important;
}
```

### **Performance Issues:**

#### **1. Too Many Modals in DOM**
```javascript
// Solution: Remove modal after use
$(document).on('hidden.bs.modal', '[id^="deleteModal_"]', function() {
    $(this).remove();
});
```

#### **2. Memory Leaks**
```javascript
// Solution: Proper event cleanup
$(document).on('hidden.bs.modal', '[id^="deleteModal_"]', function() {
    var modalId = $(this).attr('id');
    $('#' + modalId + ' button').off('click');
    $(this).remove();
});
```

---

## 📊 **PERFORMANCE METRICS**

### **Expected Performance:**
- **Modal Generation**: < 100ms per modal
- **Modal Display**: < 200ms
- **Form Submission**: < 500ms
- **Page Redirect**: < 1 second

### **Optimization Tips:**
1. **Lazy Modal Generation**: Create modals only when needed
2. **Event Delegation**: Use document-level event handlers
3. **Modal Cleanup**: Remove modals after use
4. **Batch Operations**: Handle multiple deletions efficiently
5. **Caching**: Cache frequently accessed data

---

## 🔐 **SECURITY BEST PRACTICES**

### **1. Server-Side Validation:**
```php
public function destroy(Request $request, User $user)
{
    // Validate CSRF token
    $request->validate([]);
    
    // Check permissions
    $this->authorize('delete', $user);
    
    // Validate method
    if (!$request->isMethod('DELETE')) {
        abort(405, 'Method not allowed');
    }
    
    // Additional business logic validation
    if ($user->hasActiveOrders()) {
        return redirect()->back()
                        ->with('error', 'Cannot delete user with active orders.');
    }
    
    $user->delete();
    
    return redirect()->route('users.index')
                    ->with('success', 'User deleted successfully.');
}
```

### **2. Audit Logging:**
```php
// Log deletion attempts
Log::info('User deletion attempt', [
    'target_user_id' => $user->id,
    'actor_user_id' => auth()->id(),
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent(),
    'timestamp' => now(),
    'success' => true
]);
```

### **3. Rate Limiting:**
```php
// Apply rate limiting to delete routes
Route::delete('/users/{user}', [UserController::class, 'destroy'])
     ->middleware('throttle:10,1'); // 10 deletions per minute
```

---

*This documentation covers the complete Delete Confirmation Modal System. The system provides secure, user-friendly deletion functionality with comprehensive safety mechanisms and extensive customization options.*