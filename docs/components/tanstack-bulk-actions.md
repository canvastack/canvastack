# TanStack Table Bulk Actions

Bulk actions functionality in TanStack Table engine for performing operations on multiple selected rows simultaneously.

## 📦 Location

- **Engine**: `src/Components/Table/Engines/TanStackEngine.php`
- **Renderer**: `src/Components/Table/Renderers/TanStackRenderer.php`
- **TableBuilder**: `src/Components/Table/TableBuilder.php`
- **Tests**: `tests/Unit/Components/Table/Engines/TanStackEngineBulkActionsTest.php`

## 🎯 Features

- Multiple bulk action buttons
- Confirmation dialogs for destructive actions
- AJAX-based execution
- Loading states during execution
- Success/error feedback
- Automatic table refresh after action
- CSRF protection
- HTTP method support (POST, PUT, DELETE)
- Icon support via Lucide Icons
- Dark mode support
- Full i18n support

## 📖 Basic Usage

### Enable Row Selection and Add Bulk Actions

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Enable row selection (required for bulk actions)
    $table->setSelectable(true);
    $table->setSelectionMode('multiple');
    
    $table->setFields([
        'name:Name',
        'email:Email',
        'status:Status'
    ]);
    
    // Add bulk actions
    $table->addBulkAction(
        'activate',
        route('users.bulk-activate'),
        'Activate Selected',
        'POST',
        'check-circle',
        null
    );
    
    $table->addBulkAction(
        'delete',
        route('users.bulk-delete'),
        'Delete Selected',
        'DELETE',
        'trash',
        'Are you sure you want to delete the selected users?'
    );
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

### View

```blade
{!! $table->render() !!}
```

## 🔧 API Reference

### addBulkAction()

Add a bulk action button to the table.

```php
public function addBulkAction(
    string $name,
    string $url,
    string $label,
    string $method = 'POST',
    ?string $icon = null,
    ?string $confirm = null
): self
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `$name` | string | Yes | Unique identifier for the action |
| `$url` | string | Yes | URL endpoint for the action |
| `$label` | string | Yes | Button label text |
| `$method` | string | No | HTTP method (POST, PUT, DELETE). Default: 'POST' |
| `$icon` | string | No | Lucide icon name. Default: null |
| `$confirm` | string | No | Confirmation message. Default: null |

**Returns:** `self` for method chaining

### getBulkActions()

Get all configured bulk actions.

```php
public function getBulkActions(): array
```

**Returns:** Array of bulk action configurations

### hasBulkActions()

Check if any bulk actions are configured.

```php
public function hasBulkActions(): bool
```

**Returns:** `true` if bulk actions exist, `false` otherwise

### clearBulkActions()

Remove all bulk actions.

```php
public function clearBulkActions(): self
```

**Returns:** `self` for method chaining

## 📝 Examples

### Example 1: Basic Bulk Delete

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    $table->setSelectable(true);
    $table->setSelectionMode('multiple');
    
    $table->setFields([
        'name:Name',
        'email:Email'
    ]);
    
    // Simple bulk delete with confirmation
    $table->addBulkAction(
        'delete',
        route('users.bulk-delete'),
        __('ui.buttons.delete_selected'),
        'DELETE',
        'trash',
        __('ui.messages.confirm_bulk_delete')
    );
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

### Example 2: Multiple Bulk Actions

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    $table->setSelectable(true);
    $table->setSelectionMode('multiple');
    
    $table->setFields([
        'name:Name',
        'email:Email',
        'status:Status'
    ]);
    
    // Activate users
    $table->addBulkAction(
        'activate',
        route('users.bulk-activate'),
        'Activate',
        'POST',
        'check-circle'
    );
    
    // Deactivate users
    $table->addBulkAction(
        'deactivate',
        route('users.bulk-deactivate'),
        'Deactivate',
        'POST',
        'x-circle'
    );
    
    // Export selected
    $table->addBulkAction(
        'export',
        route('users.bulk-export'),
        'Export',
        'POST',
        'download'
    );
    
    // Delete with confirmation
    $table->addBulkAction(
        'delete',
        route('users.bulk-delete'),
        'Delete',
        'DELETE',
        'trash',
        'Are you sure you want to delete the selected users?'
    );
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

### Example 3: Bulk Action with Custom Confirmation

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    $table->setSelectable(true);
    $table->setSelectionMode('multiple');
    
    $table->setFields([
        'name:Name',
        'email:Email',
        'role:Role'
    ]);
    
    // Assign to admin role with confirmation
    $table->addBulkAction(
        'assign-admin',
        route('users.bulk-assign-role', ['role' => 'admin']),
        'Make Admin',
        'POST',
        'shield',
        'Are you sure you want to assign admin role to the selected users? This will grant them full access to the system.'
    );
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

## 🎮 Controller Implementation

### Basic Bulk Action Handler

```php
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

public function bulkDelete(Request $request): JsonResponse
{
    // Validate request
    $validated = $request->validate([
        'ids' => 'required|array',
        'ids.*' => 'required|integer|exists:users,id'
    ]);
    
    try {
        // Perform bulk delete
        User::whereIn('id', $validated['ids'])->delete();
        
        return response()->json([
            'success' => true,
            'message' => __('ui.messages.users_deleted', [
                'count' => count($validated['ids'])
            ])
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => __('ui.messages.bulk_action_failed')
        ], 500);
    }
}
```

### Bulk Action with Authorization

```php
public function bulkActivate(Request $request): JsonResponse
{
    // Validate request
    $validated = $request->validate([
        'ids' => 'required|array',
        'ids.*' => 'required|integer|exists:users,id'
    ]);
    
    // Check authorization
    if (!auth()->user()->can('activate-users')) {
        return response()->json([
            'success' => false,
            'message' => __('ui.messages.unauthorized')
        ], 403);
    }
    
    try {
        // Perform bulk activation
        $count = User::whereIn('id', $validated['ids'])
            ->update(['status' => 'active']);
        
        return response()->json([
            'success' => true,
            'message' => __('ui.messages.users_activated', ['count' => $count])
        ]);
    } catch (\Exception $e) {
        \Log::error('Bulk activation failed', [
            'error' => $e->getMessage(),
            'ids' => $validated['ids']
        ]);
        
        return response()->json([
            'success' => false,
            'message' => __('ui.messages.bulk_action_failed')
        ], 500);
    }
}
```

### Bulk Action with Transaction

```php
use Illuminate\Support\Facades\DB;

public function bulkAssignRole(Request $request): JsonResponse
{
    $validated = $request->validate([
        'ids' => 'required|array',
        'ids.*' => 'required|integer|exists:users,id',
        'role' => 'required|string|exists:roles,name'
    ]);
    
    DB::beginTransaction();
    
    try {
        $users = User::whereIn('id', $validated['ids'])->get();
        
        foreach ($users as $user) {
            // Remove existing roles
            $user->roles()->detach();
            
            // Assign new role
            $user->assignRole($validated['role']);
        }
        
        DB::commit();
        
        return response()->json([
            'success' => true,
            'message' => __('ui.messages.role_assigned', [
                'count' => $users->count(),
                'role' => $validated['role']
            ])
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        
        return response()->json([
            'success' => false,
            'message' => __('ui.messages.bulk_action_failed')
        ], 500);
    }
}
```

## 🔍 Implementation Details

### Request Format

Bulk actions send a POST request with the following data:

```javascript
{
    _method: 'POST|PUT|DELETE',
    _token: 'csrf-token',
    ids: ['1', '2', '3', ...]
}
```

### Response Format

Controllers should return JSON responses:

**Success:**
```json
{
    "success": true,
    "message": "3 users deleted successfully"
}
```

**Error:**
```json
{
    "success": false,
    "message": "Failed to delete users"
}
```

### Confirmation Modal

When a confirmation message is provided, a modal dialog is displayed with:
- Title: "Confirm Action"
- Message: The provided confirmation text
- Cancel button
- Confirm button (red for destructive actions)

### Loading States

During bulk action execution:
1. Table shows loading overlay
2. Bulk action buttons are disabled
3. User cannot interact with the table
4. After completion, table automatically refreshes

### Error Handling

The system handles errors gracefully:
- Network errors: Shows generic error message
- Server errors: Shows server-provided error message
- Validation errors: Shows validation error message
- No selection: Shows "No rows selected" message

## 🎨 Styling

### Button Colors

Bulk action buttons are automatically colored based on action type:

| Action Type | Color | Use Case |
|-------------|-------|----------|
| Delete | Red | Destructive actions |
| Export | Green | Export/download actions |
| Default | Blue | All other actions |

### Custom Button Styling

You can customize button appearance via CSS:

```css
/* Customize bulk action buttons */
.tanstack-bulk-action-button {
    /* Your custom styles */
}

/* Customize specific action */
.tanstack-bulk-action-button[data-action="delete"] {
    background: #your-color;
}
```

### Dark Mode

Bulk action buttons automatically adapt to dark mode:

```css
.dark .tanstack-bulk-action-button {
    /* Dark mode styles applied automatically */
}
```

## 🎯 Accessibility

### Keyboard Navigation

- **Tab**: Navigate between bulk action buttons
- **Enter/Space**: Activate button
- **Escape**: Close confirmation modal

### ARIA Labels

All bulk action buttons include proper ARIA labels:

```html
<button aria-label="Delete selected users">
    Delete Selected
</button>
```

### Screen Reader Support

- Announces button labels
- Announces confirmation dialogs
- Announces success/error messages
- Announces loading states

## 💡 Tips & Best Practices

1. **Always Require Confirmation for Destructive Actions**
   - Use confirmation messages for delete, deactivate, etc.
   - Make confirmation messages clear and specific

2. **Validate on Server Side**
   - Always validate IDs exist
   - Check user permissions
   - Validate business rules

3. **Use Transactions for Complex Operations**
   - Wrap multiple operations in DB transactions
   - Rollback on any failure
   - Log errors for debugging

4. **Provide Clear Feedback**
   - Show success messages with counts
   - Show specific error messages
   - Log errors for troubleshooting

5. **Limit Selection for Performance**
   - Consider limiting max selections (e.g., 100 items)
   - Show warning for large selections
   - Use queue jobs for very large operations

6. **Handle Edge Cases**
   - No selection: Show appropriate message
   - Partial failures: Report which items failed
   - Concurrent modifications: Handle optimistic locking

## 🎭 Common Patterns

### Pattern 1: Soft Delete with Restore

```php
// Bulk soft delete
$table->addBulkAction(
    'delete',
    route('users.bulk-delete'),
    'Delete',
    'DELETE',
    'trash',
    'Move selected users to trash?'
);

// Bulk restore
$table->addBulkAction(
    'restore',
    route('users.bulk-restore'),
    'Restore',
    'POST',
    'rotate-ccw'
);
```

### Pattern 2: Status Changes

```php
// Activate
$table->addBulkAction(
    'activate',
    route('users.bulk-status', ['status' => 'active']),
    'Activate',
    'POST',
    'check-circle'
);

// Suspend
$table->addBulkAction(
    'suspend',
    route('users.bulk-status', ['status' => 'suspended']),
    'Suspend',
    'POST',
    'pause-circle',
    'Suspend selected users?'
);
```

### Pattern 3: Batch Processing

```php
// Send email to selected
$table->addBulkAction(
    'email',
    route('users.bulk-email'),
    'Send Email',
    'POST',
    'mail'
);

// Export selected
$table->addBulkAction(
    'export',
    route('users.bulk-export'),
    'Export',
    'POST',
    'download'
);
```

## 🔗 Related Components

- [TanStack Row Selection](tanstack-row-selection.md) - Row selection feature
- [TanStack Engine](tanstack-engine.md) - Main TanStack Table engine
- [DataTables Bulk Actions](datatables-bulk-actions.md) - DataTables bulk actions
- [Table Builder](../api/table-builder.md) - Main table API

## 📚 Resources

- [TanStack Table Documentation](https://tanstack.com/table/v8)
- [Alpine.js Documentation](https://alpinejs.dev)
- [Lucide Icons](https://lucide.dev)
- [Laravel Validation](https://laravel.com/docs/validation)

---

**Last Updated**: 2026-03-05  
**Version**: 1.0.0  
**Status**: Complete  
**Validates**: Requirement 16.5
