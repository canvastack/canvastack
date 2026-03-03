# Display Limit DataTables Integration

## 📦 Location

- **TableBuilder**: `src/Components/Table/TableBuilder.php`
- **AdminRenderer**: `src/Components/Table/Renderers/AdminRenderer.php`
- **PublicRenderer**: `src/Components/Table/Renderers/PublicRenderer.php`
- **DisplayLimit Component**: `src/View/Components/Table/DisplayLimit.php`
- **DisplayLimit Template**: `resources/views/components/table/display-limit.blade.php`

## 🎯 Features

- Dynamic DataTables page length updates
- Event-driven communication between components
- Session persistence via AJAX
- Support for both AdminRenderer and PublicRenderer
- Performance optimized pagination
- Seamless integration with existing TableBuilder API

## 📖 Basic Usage

### Setting Display Limit

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function index(TableBuilder $table): View
{
    $table->setContext('admin')
          ->setModel(new User())
          ->setFields(['id', 'name', 'email'])
          ->displayRowsLimitOnLoad(25)  // Set initial limit to 25
          ->sessionFilters()            // Enable session persistence
          ->format();
    
    return view('users.index', ['table' => $table]);
}
```

### Rendering Display Limit UI

```php
// In your controller
$displayLimitHtml = $table->renderDisplayLimitUI([
    ['value' => '10', 'label' => '10'],
    ['value' => '25', 'label' => '25'],
    ['value' => '50', 'label' => '50'],
    ['value' => '100', 'label' => '100'],
    ['value' => 'all', 'label' => 'All'],
]);

return view('users.index', [
    'table' => $table,
    'displayLimitHtml' => $displayLimitHtml
]);
```

```blade
{{-- In your Blade template --}}
<div class="flex items-center gap-4 mb-4">
    {!! $displayLimitHtml !!}
</div>

{!! $table->render() !!}
```

## 🔧 Configuration Options

### Display Limit Values

| Value | DataTables pageLength | Description |
|-------|----------------------|-------------|
| `10` | `10` | Show 10 rows per page |
| `25` | `25` | Show 25 rows per page |
| `50` | `50` | Show 50 rows per page |
| `100` | `100` | Show 100 rows per page |
| `'all'` | `-1` | Show all rows (no pagination) |
| `'*'` | `-1` | Show all rows (alternative syntax) |

### DisplayLimit Component Options

```php
$table->renderDisplayLimitUI(
    $options = [
        ['value' => '10', 'label' => '10'],
        ['value' => '25', 'label' => '25'],
        ['value' => '50', 'label' => '50'],
        ['value' => '100', 'label' => '100'],
        ['value' => 'all', 'label' => 'All'],
    ],
    $showLabel = true,    // Show "Show:" label
    $size = 'sm'          // Size: 'xs', 'sm', 'md', 'lg'
);
```

## 📝 Examples

### Example 1: Basic Integration

```php
public function index(TableBuilder $table): View
{
    $table->setContext('admin')
          ->setModel(new User())
          ->setFields(['id:ID', 'name:Name', 'email:Email'])
          ->displayRowsLimitOnLoad(25)
          ->format();
    
    return view('users.index', ['table' => $table]);
}
```

### Example 2: Custom Display Limit Options

```php
public function index(TableBuilder $table): View
{
    $table->setContext('admin')
          ->setModel(new Product())
          ->setFields(['id', 'name', 'price', 'category'])
          ->displayRowsLimitOnLoad(50)
          ->sessionFilters()
          ->format();
    
    // Custom options for product catalog
    $displayLimitHtml = $table->renderDisplayLimitUI([
        ['value' => '12', 'label' => '12'],
        ['value' => '24', 'label' => '24'],
        ['value' => '48', 'label' => '48'],
        ['value' => 'all', 'label' => 'All Products'],
    ]);
    
    return view('products.index', [
        'table' => $table,
        'displayLimitHtml' => $displayLimitHtml
    ]);
}
```

### Example 3: Session Persistence

```php
public function index(TableBuilder $table): View
{
    // Enable session persistence
    $table->sessionFilters()
          ->setModel(new Order())
          ->setFields(['id', 'customer', 'total', 'status', 'created_at'])
          ->displayRowsLimitOnLoad(20)  // Default limit
          ->format();
    
    // User's preferred limit will be restored from session
    $currentLimit = $table->getDisplayLimit();
    
    return view('orders.index', [
        'table' => $table,
        'currentLimit' => $currentLimit
    ]);
}
```

## 🎮 JavaScript API

### Event Listening

```javascript
// Listen for display limit changes
document.addEventListener('display-limit-changed', function(event) {
    console.log('New limit:', event.detail.limit);
    
    // Custom handling
    if (event.detail.limit === 'all') {
        console.log('Showing all records');
    }
});
```

### Programmatic Updates

```javascript
// Update display limit programmatically
if (window.dataTable && window.dataTable.updateDisplayLimit) {
    window.dataTable.updateDisplayLimit(50);
}

// Alternative: Direct DataTables API
if (window.dataTable) {
    window.dataTable.page.len(50).draw();
}
```

### Custom Integration

```javascript
// Custom display limit component
function customDisplayLimit() {
    return {
        currentLimit: 10,
        
        changeLimit(newLimit) {
            // Update DataTables
            if (window.dataTable) {
                const pageLength = newLimit === 'all' ? -1 : parseInt(newLimit);
                window.dataTable.page.len(pageLength).draw();
            }
            
            // Dispatch event
            this.$dispatch('display-limit-changed', { limit: newLimit });
            
            // Save to session
            this.saveToSession(newLimit);
        },
        
        async saveToSession(limit) {
            await fetch('/datatable/save-display-limit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    table: 'my_table',
                    limit: limit
                })
            });
        }
    }
}
```

## 🔍 Implementation Details

### DataTables Configuration

The integration automatically configures DataTables with the correct `pageLength`:

```javascript
// Generated DataTables config
$('#table').DataTable({
    pageLength: 25,  // From displayRowsLimitOnLoad(25)
    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
    
    // Event listener for display limit changes
    // ... (automatically added)
});
```

### Event-Driven Updates

When the display limit changes:

1. **DisplayLimit Component** dispatches `display-limit-changed` event
2. **DataTables Script** listens for the event and updates `pageLength`
3. **Session Storage** saves the new limit via AJAX
4. **Table Redraws** with new pagination

### Session Persistence

Display limits are saved to session using a unique key:

```php
// Session key format
$sessionKey = 'table_display_limit_' . md5($tableName . '_' . request()->path());

// Saved data
session([$sessionKey => $limit]);
```

### Performance Optimization

- **No Page Reloads**: Updates happen via JavaScript
- **Efficient Pagination**: DataTables handles large datasets efficiently
- **Session Caching**: Preferences cached in session storage
- **Event Debouncing**: Prevents excessive AJAX calls

## 🎯 Accessibility

The DisplayLimit component includes accessibility features:

- **Semantic HTML**: Uses proper `<select>` and `<label>` elements
- **ARIA Labels**: Screen reader friendly
- **Keyboard Navigation**: Full keyboard support
- **Focus Management**: Proper focus handling
- **Loading States**: Visual feedback during updates

## 🎨 Styling

### Tailwind CSS Classes

```html
<!-- Display limit dropdown -->
<select class="select select-bordered select-sm w-20">
    <option value="10">10</option>
    <option value="25">25</option>
    <option value="50">50</option>
    <option value="100">100</option>
    <option value="all">All</option>
</select>
```

### Custom Styling

```css
/* Custom display limit styles */
.display-limit-container {
    @apply flex items-center gap-2;
}

.display-limit-select {
    @apply select select-bordered min-w-0;
}

.display-limit-label {
    @apply text-sm text-gray-600 dark:text-gray-400;
}

.display-limit-loading {
    @apply loading loading-spinner loading-sm;
}
```

## 🧪 Testing

### Unit Tests

```php
public function test_display_limit_passed_to_datatables_config(): void
{
    $this->table->displayRowsLimitOnLoad(25);
    $this->table->setData([['id' => 1, 'name' => 'Test']]);
    $this->table->setFields(['id', 'name']);
    $this->table->format();

    $html = $this->table->render();

    $this->assertStringContainsString('pageLength: 25', $html);
}

public function test_all_display_limit_converted_to_negative_one(): void
{
    $this->table->displayRowsLimitOnLoad('all');
    $this->table->setData([['id' => 1, 'name' => 'Test']]);
    $this->table->setFields(['id', 'name']);
    $this->table->format();

    $html = $this->table->render();

    $this->assertStringContainsString('pageLength: -1', $html);
}
```

### Browser Tests

```php
public function test_display_limit_updates_datatables_pagination(): void
{
    $this->browse(function (Browser $browser) {
        $browser->visit('/users')
                ->assertSee('Show:')
                ->select('[x-model="currentLimit"]', '50')
                ->pause(1000)
                ->assertSeeIn('.dataTables_info', 'Showing 1 to 50');
    });
}
```

## 💡 Tips & Best Practices

1. **Always Enable Session Persistence** - Use `sessionFilters()` for better UX
2. **Provide Reasonable Options** - Don't overwhelm users with too many choices
3. **Consider Performance** - Be careful with 'All' option on large datasets
4. **Test Edge Cases** - Verify behavior with 0 records, 1 record, etc.
5. **Mobile Optimization** - Ensure dropdown works well on mobile devices

## 🎭 Common Patterns

### Pattern 1: Standard CRUD Table

```php
public function index(TableBuilder $table): View
{
    $table->setContext('admin')
          ->setModel(new User())
          ->setFields(['id:ID', 'name:Name', 'email:Email', 'created_at:Created'])
          ->displayRowsLimitOnLoad(25)
          ->sessionFilters()
          ->addAction('edit', route('users.edit', ':id'), 'edit', 'Edit')
          ->addAction('delete', route('users.destroy', ':id'), 'trash', 'Delete', 'DELETE')
          ->format();
    
    return view('users.index', ['table' => $table]);
}
```

### Pattern 2: Report Table with Custom Limits

```php
public function report(TableBuilder $table): View
{
    $table->setContext('admin')
          ->setModel(new SalesReport())
          ->setFields(['date', 'sales', 'profit', 'orders'])
          ->displayRowsLimitOnLoad(100)  // Reports often need more rows
          ->sessionFilters()
          ->format();
    
    $displayLimitHtml = $table->renderDisplayLimitUI([
        ['value' => '50', 'label' => '50'],
        ['value' => '100', 'label' => '100'],
        ['value' => '250', 'label' => '250'],
        ['value' => 'all', 'label' => 'All Records'],
    ]);
    
    return view('reports.sales', [
        'table' => $table,
        'displayLimitHtml' => $displayLimitHtml
    ]);
}
```

### Pattern 3: Public Table with Simple Options

```php
public function catalog(TableBuilder $table): View
{
    $table->setContext('public')
          ->setModel(new Product())
          ->setFields(['name', 'price', 'category'])
          ->displayRowsLimitOnLoad(12)  // Good for product grids
          ->format();
    
    $displayLimitHtml = $table->renderDisplayLimitUI([
        ['value' => '12', 'label' => '12'],
        ['value' => '24', 'label' => '24'],
        ['value' => '48', 'label' => '48'],
    ], false, 'sm');  // No label, small size
    
    return view('catalog.index', [
        'table' => $table,
        'displayLimitHtml' => $displayLimitHtml
    ]);
}
```

## 🔗 Related Components

- [TableBuilder](../table-builder.md) - Main table component
- [Session Management](../session-management.md) - Session persistence
- [DataTables Integration](../datatables-integration.md) - DataTables configuration
- [Display Limit Component](../display-limit.md) - UI component details

## 📚 Resources

- [DataTables Documentation](https://datatables.net/reference/option/pageLength)
- [Alpine.js Events](https://alpinejs.dev/directives/on)
- [Tailwind CSS Select](https://tailwindcss.com/docs/form-select)
- [DaisyUI Select](https://daisyui.com/components/select/)

---

**Last Updated**: 2026-03-02  
**Version**: 1.0.0  
**Status**: Implemented  
**Task**: 3.1.3 Integrate with DataTables