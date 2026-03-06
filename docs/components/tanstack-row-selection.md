# TanStack Table Row Selection

Row selection functionality in TanStack Table engine using TanStack Table v8 row selection API with Alpine.js state management.

## 📦 Location

- **Engine**: `src/Components/Table/Engines/TanStackEngine.php`
- **Renderer**: `src/Components/Table/Renderers/TanStackRenderer.php`
- **Tests**: `tests/Unit/Components/Table/Engines/TanStackEngineRowSelectionTest.php`

## 🎯 Features

- Single and multiple row selection modes
- Select all checkbox with indeterminate state
- Selected row count display
- Clear selection functionality
- Selected row highlighting
- Keyboard accessible
- Dark mode support
- Full i18n support
- Works with server-side processing

## 📖 Basic Usage

### Enable Row Selection

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Enable row selection
    $table->setSelectable(true);
    $table->setSelectionMode('multiple'); // or 'single'
    
    $table->setFields([
        'name:Name',
        'email:Email',
        'created_at:Created'
    ]);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

### View

```blade
{!! $table->render() !!}
```

## 🔧 Configuration

### Selection Modes

| Mode | Description | Use Case |
|------|-------------|----------|
| `single` | Only one row can be selected at a time | Radio button behavior |
| `multiple` | Multiple rows can be selected | Bulk operations |

### Enable/Disable Selection

```php
// Enable selection (default: false)
$table->setSelectable(true);

// Disable selection
$table->setSelectable(false);
```

### Set Selection Mode

```php
// Multiple selection (default)
$table->setSelectionMode('multiple');

// Single selection
$table->setSelectionMode('single');
```

## 📝 Examples

### Example 1: Multiple Selection with Bulk Actions

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Enable multiple selection
    $table->setSelectable(true);
    $table->setSelectionMode('multiple');
    
    $table->setFields([
        'name:Name',
        'email:Email',
        'status:Status'
    ]);
    
    // Add bulk actions
    $table->addBulkAction('activate', route('users.bulk-activate'), 'Activate Selected');
    $table->addBulkAction('deactivate', route('users.bulk-deactivate'), 'Deactivate Selected');
    $table->addBulkAction('delete', route('users.bulk-delete'), 'Delete Selected', 'DELETE');
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

### Example 2: Single Selection

```php
public function selectUser(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Enable single selection
    $table->setSelectable(true);
    $table->setSelectionMode('single');
    
    $table->setFields([
        'name:Name',
        'email:Email',
        'role:Role'
    ]);
    
    $table->format();
    
    return view('users.select', ['table' => $table]);
}
```

### Example 3: Selection with Server-Side Processing

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Enable selection and server-side processing
    $table->setSelectable(true);
    $table->setSelectionMode('multiple');
    $table->setServerSide(true);
    
    $table->setFields([
        'name:Name',
        'email:Email',
        'created_at:Created'
    ]);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

## 🎮 JavaScript API

The TanStack row selection provides JavaScript methods for programmatic control:

### Check if Row is Selected

```javascript
// Check if a specific row is selected
const isSelected = Alpine.$data(element).isRowSelected(rowId);
```

### Get Selected Row IDs

```javascript
// Get array of selected row IDs
const selectedIds = Alpine.$data(element).getSelectedRowIds();
// Returns: ['1', '3', '5']
```

### Get Selected Rows Data

```javascript
// Get array of selected row objects
const selectedRows = Alpine.$data(element).getSelectedRows();
// Returns: [{ id: 1, name: 'John', ... }, { id: 3, name: 'Jane', ... }]
```

### Clear Selection

```javascript
// Clear all selections
Alpine.$data(element).clearSelection();
```

### Select/Deselect Row

```javascript
// Toggle row selection
Alpine.$data(element).onRowSelectChange(rowId);
```

### Select All Rows

```javascript
// Select all rows
Alpine.$data(element).selectAll = true;
Alpine.$data(element).onSelectAllChange();
```

## 🔍 Implementation Details

### Selection State Management

The row selection state is managed using Alpine.js reactive data:

```javascript
{
    // Selection state object (key: rowId, value: true/false)
    rowSelection: {},
    
    // Count of selected rows
    selectedCount: 0,
    
    // Select all checkbox state
    selectAll: false,
    
    // Indeterminate state (some but not all selected)
    isIndeterminate: false
}
```

### Selection Update Flow

1. User clicks checkbox
2. `onRowSelectChange(rowId)` is called
3. `rowSelection` object is updated
4. `updateSelectionState()` recalculates counts and states
5. UI updates reactively via Alpine.js

### Select All Logic

- **All selected**: `selectAll = true`, `isIndeterminate = false`
- **Some selected**: `selectAll = false`, `isIndeterminate = true`
- **None selected**: `selectAll = false`, `isIndeterminate = false`

### Indeterminate State

The select all checkbox shows an indeterminate state when some (but not all) rows are selected:

```html
<input type="checkbox" 
       x-model="selectAll" 
       :indeterminate.prop="isIndeterminate" />
```

## 🎨 Styling

### CSS Classes

| Class | Description |
|-------|-------------|
| `.tanstack-table-select-column` | Selection checkbox column |
| `.tanstack-table-checkbox` | Checkbox input styling |
| `.tanstack-table-row-selected` | Selected row background |

### Custom Styling

```css
/* Customize checkbox color */
.tanstack-table-checkbox {
    accent-color: #your-color;
}

/* Customize selected row background */
.tanstack-table-row-selected {
    background: #your-color !important;
}

/* Dark mode selected row */
.dark .tanstack-table-row-selected {
    background: #your-dark-color !important;
}
```

### Selected Row Highlighting

Selected rows are highlighted with a light background color:

- **Light mode**: Light blue background (`#eef2ff`)
- **Dark mode**: Dark blue background (`#312e81`)

## 🎯 Accessibility

### Keyboard Navigation

- **Tab**: Navigate between checkboxes
- **Space**: Toggle checkbox selection
- **Shift + Click**: Range selection (future enhancement)

### ARIA Labels

All checkboxes include proper ARIA labels:

```html
<!-- Select all checkbox -->
<input type="checkbox" aria-label="Select all" />

<!-- Row checkbox -->
<input type="checkbox" aria-label="Select row 1" />
```

### Screen Reader Support

- Announces selection state changes
- Announces selected row count
- Announces indeterminate state

## 💡 Tips & Best Practices

1. **Use Multiple Selection for Bulk Actions**
   - Enable multiple selection when you have bulk operations
   - Provide clear bulk action buttons

2. **Use Single Selection for Picking**
   - Use single selection for "choose one" scenarios
   - Consider using radio buttons for better UX

3. **Show Selected Count**
   - Always display the selected row count
   - Provide a clear way to clear selection

4. **Persist Selection Across Pages**
   - Consider persisting selection in session storage
   - Clear selection when navigating away

5. **Limit Selection for Performance**
   - For very large datasets, consider limiting max selections
   - Show warning when selection limit is reached

6. **Provide Feedback**
   - Highlight selected rows clearly
   - Show loading state during bulk operations
   - Confirm destructive bulk actions

## 🎭 Common Patterns

### Pattern 1: Bulk Delete with Confirmation

```php
public function index(TableBuilder $table)
{
    $table->setSelectable(true);
    $table->setSelectionMode('multiple');
    
    // Add bulk delete action with confirmation
    $table->addBulkAction(
        'delete',
        route('users.bulk-delete'),
        'Delete Selected',
        'DELETE',
        'Are you sure you want to delete the selected users?'
    );
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

### Pattern 2: Export Selected Rows

```php
public function index(TableBuilder $table)
{
    $table->setSelectable(true);
    $table->setSelectionMode('multiple');
    
    // Add export selected action
    $table->addBulkAction(
        'export',
        route('users.export-selected'),
        'Export Selected'
    );
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

### Pattern 3: Assign to Group

```php
public function index(TableBuilder $table)
{
    $table->setSelectable(true);
    $table->setSelectionMode('multiple');
    
    // Add assign to group action
    $table->addBulkAction(
        'assign',
        route('users.bulk-assign'),
        'Assign to Group'
    );
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

## 🔗 Related Components

- [TanStack Engine](tanstack-engine.md) - Main TanStack Table engine
- [TanStack Column Pinning](tanstack-column-pinning.md) - Fixed columns feature
- [DataTables Row Selection](datatables-row-selection.md) - DataTables selection
- [Table Builder](../api/table-builder.md) - Main table API

## 📚 Resources

- [TanStack Table Row Selection](https://tanstack.com/table/v8/docs/guide/row-selection)
- [Alpine.js Reactivity](https://alpinejs.dev/essentials/reactivity)
- [WCAG Checkbox Guidelines](https://www.w3.org/WAI/ARIA/apg/patterns/checkbox/)

---

**Last Updated**: 2026-03-05  
**Version**: 1.0.0  
**Status**: Complete  
**Validates**: Requirements 16.1, 16.2, 16.3, 16.4, 16.7

