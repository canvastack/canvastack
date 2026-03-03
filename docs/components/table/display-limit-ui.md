# Display Limit UI Component

## 📦 Location

- **Component Class**: `Canvastack\Canvastack\View\Components\Table\DisplayLimit`
- **Blade Template**: `resources/views/components/table/display-limit.blade.php`
- **TableBuilder Integration**: `TableBuilder::renderDisplayLimitUI()`

## 🎯 Features

- Dropdown interface for changing table row display limits
- Session persistence for user preferences
- DataTable integration with automatic updates
- Customizable options and styling
- Alpine.js powered interactivity
- Loading states and error handling
- Multiple size options (xs, sm, md, lg)
- Optional labels for compact layouts

## 📖 Basic Usage

### Using with TableBuilder (Recommended)

```php
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new User());
    $table->setFields(['name:Name', 'email:Email']);
    
    // Set initial display limit
    $table->displayRowsLimitOnLoad(25);
    
    // Enable session persistence
    $table->sessionFilters();
    
    $table->format();
    
    return view('users.index', [
        'table' => $table,
        'displayLimitUI' => $table->renderDisplayLimitUI(),
    ]);
}
```

### Blade Template

```blade
<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-semibold">Users</h2>
    
    <!-- Display Limit UI -->
    {!! $displayLimitUI !!}
</div>

{!! $table->render() !!}
```

### Using Blade Component Directly

```blade
<x-canvastack::table.display-limit 
    table-name="users_table"
    :current-limit="25" />
```

## 🔧 Props / Parameters

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `table-name` | string | 'default' | Unique identifier for session storage |
| `current-limit` | int\|string | 10 | Current display limit (integer or 'all') |
| `options` | array | [10, 25, 50, 100, all] | Array of limit options |
| `show-label` | bool | true | Whether to show "Show:" and "entries" labels |
| `size` | string | 'sm' | Size of dropdown: 'xs', 'sm', 'md', 'lg' |

### Options Array Format

```php
$options = [
    ['value' => '10', 'label' => '10'],
    ['value' => '25', 'label' => '25'],
    ['value' => '50', 'label' => '50'],
    ['value' => '100', 'label' => '100'],
    ['value' => 'all', 'label' => 'All'],
];
```

## 📝 Examples

### Example 1: Default Configuration

```php
// Controller
$displayLimitUI = $table->renderDisplayLimitUI();

// Blade
{!! $displayLimitUI !!}
```

### Example 2: Custom Options

```php
$customOptions = [
    ['value' => '5', 'label' => '5'],
    ['value' => '20', 'label' => '20'],
    ['value' => '50', 'label' => '50'],
    ['value' => 'all', 'label' => 'Show All'],
];

$displayLimitUI = $table->renderDisplayLimitUI($customOptions);
```

### Example 3: Compact Version

```php
// No labels, extra small size
$displayLimitUI = $table->renderDisplayLimitUI([], false, 'xs');
```

### Example 4: Large Size with Custom Options

```php
$options = [
    ['value' => '25', 'label' => '25 items'],
    ['value' => '50', 'label' => '50 items'],
    ['value' => '100', 'label' => '100 items'],
    ['value' => 'all', 'label' => 'All items'],
];

$displayLimitUI = $table->renderDisplayLimitUI($options, true, 'lg');
```

### Example 5: Direct Blade Component Usage

```blade
{{-- Default --}}
<x-canvastack::table.display-limit 
    table-name="products"
    :current-limit="50" />

{{-- Custom options --}}
<x-canvastack::table.display-limit 
    table-name="orders"
    :current-limit="25"
    :options="[
        ['value' => '10', 'label' => '10'],
        ['value' => '25', 'label' => '25'],
        ['value' => '50', 'label' => '50'],
        ['value' => 'all', 'label' => 'All']
    ]" />

{{-- Compact version --}}
<x-canvastack::table.display-limit 
    table-name="reports"
    :current-limit="10"
    :show-label="false"
    size="xs" />
```

## 🎮 JavaScript API

The component exposes several JavaScript methods through Alpine.js:

### Methods

```javascript
// Change limit programmatically
$refs.displayLimit.changeLimit('50');

// Get current limit
const currentLimit = $refs.displayLimit.currentLimit;

// Check if loading
const isLoading = $refs.displayLimit.loading;
```

### Events

The component dispatches custom events:

```javascript
// Listen for limit changes
document.addEventListener('display-limit-changed', function(event) {
    console.log('New limit:', event.detail.limit);
});

// Listen for toast messages
document.addEventListener('show-toast', function(event) {
    console.log('Toast:', event.detail.type, event.detail.message);
});
```

## 🔍 Implementation Details

### Session Storage

The component uses Laravel sessions to persist user preferences:

```php
// Session key format
$sessionKey = "table_display_limit_{$tableName}";

// Saved data
session([$sessionKey => $limit]);
```

### DataTable Integration

The component automatically updates DataTables when the limit changes:

```javascript
// Update DataTables page length
if (window.dataTable) {
    const pageLength = limit === 'all' ? -1 : parseInt(limit);
    window.dataTable.page.len(pageLength).draw();
}
```

### AJAX Endpoint

Changes are saved via AJAX to `/datatable/save-display-limit`:

```javascript
fetch('/datatable/save-display-limit', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        table: tableName,
        limit: limit
    })
});
```

## 🎯 Accessibility

The component includes accessibility features:

- Proper ARIA labels
- Keyboard navigation support
- Screen reader friendly
- Focus management
- High contrast support

```blade
<select aria-label="Number of entries to display"
        class="select select-bordered"
        @change="changeLimit($event.target.value)">
    <!-- Options -->
</select>
```

## 🎨 Styling / Customization

### Size Classes

```css
/* Extra Small */
.select-xs.w-16 { /* 64px width */ }

/* Small (default) */
.select-sm.w-20 { /* 80px width */ }

/* Medium */
.select-md.w-24 { /* 96px width */ }

/* Large */
.select-lg.w-28 { /* 112px width */ }
```

### Custom Styling

```blade
{{-- Custom CSS classes --}}
<div class="flex items-center gap-2 my-custom-class">
    {!! $displayLimitUI !!}
</div>
```

### Dark Mode Support

The component automatically supports dark mode:

```css
.text-gray-600.dark:text-gray-400 /* Labels */
.select.select-bordered /* Dropdown with dark mode support */
```

## 🧪 Testing

### Unit Tests

```php
public function test_display_limit_component_renders(): void
{
    $component = new DisplayLimit(
        tableName: 'users',
        currentLimit: 25
    );
    
    $view = $component->render();
    
    $this->assertInstanceOf(View::class, $view);
    $this->assertEquals(25, $component->getCurrentLimit());
}
```

### Feature Tests

```php
public function test_display_limit_saves_to_session(): void
{
    $response = $this->post('/datatable/save-display-limit', [
        'table' => 'users',
        'limit' => 50
    ]);
    
    $response->assertJson(['success' => true]);
    $this->assertEquals(50, session('table_display_limit_users'));
}
```

### Browser Tests

```php
public function test_display_limit_dropdown_changes_table(): void
{
    $this->browse(function (Browser $browser) {
        $browser->visit('/users')
                ->select('@display-limit', '50')
                ->waitFor('.dataTables_info')
                ->assertSee('Showing 1 to 50');
    });
}
```

## 💡 Tips & Best Practices

1. **Always Enable Session Persistence** - Use `sessionFilters()` for better UX
2. **Use Appropriate Default Limits** - Consider your data size and user needs
3. **Provide 'All' Option Carefully** - Only for small to medium datasets
4. **Position Strategically** - Place near table header for easy access
5. **Consider Mobile Users** - Use compact version on small screens
6. **Test with Large Datasets** - Ensure performance with high limits

## 🎭 Common Patterns

### Pattern 1: Table Header Integration

```blade
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-semibold">{{ $title }}</h2>
        <p class="text-gray-600 text-sm">{{ $description }}</p>
    </div>
    
    <div class="flex items-center gap-4">
        <!-- Filter Button -->
        <button class="btn btn-outline btn-sm">
            <i data-lucide="filter" class="w-4 h-4"></i>
            Filter
        </button>
        
        <!-- Display Limit -->
        {!! $displayLimitUI !!}
    </div>
</div>
```

### Pattern 2: Responsive Layout

```blade
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <h2 class="text-xl font-semibold">{{ $title }}</h2>
    
    <!-- Mobile: Compact version -->
    <div class="sm:hidden">
        {!! $table->renderDisplayLimitUI([], false, 'xs') !!}
    </div>
    
    <!-- Desktop: Full version -->
    <div class="hidden sm:block">
        {!! $table->renderDisplayLimitUI() !!}
    </div>
</div>
```

### Pattern 3: Multiple Tables

```blade
{{-- Users Table --}}
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">Users</h3>
        {!! $usersTable->renderDisplayLimitUI() !!}
    </div>
    {!! $usersTable->render() !!}
</div>

{{-- Posts Table --}}
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">Posts</h3>
        {!! $postsTable->renderDisplayLimitUI() !!}
    </div>
    {!! $postsTable->render() !!}
</div>
```

## 🔗 Related Components / Documentation

- [TableBuilder](../table-builder.md) - Main table component
- [Session Manager](../session-manager.md) - Session persistence
- [Filter Modal](../filter-modal.md) - Table filtering
- [DataTable Controller](../../api/datatable-controller.md) - Backend API

## 📚 Resources

- [Alpine.js Documentation](https://alpinejs.dev)
- [Tailwind CSS Select](https://tailwindcss.com/docs/form-select)
- [DaisyUI Select](https://daisyui.com/components/select/)
- [Laravel Sessions](https://laravel.com/docs/session)

---

**Last Updated**: 2026-03-02  
**Version**: 1.0.0  
**Status**: Complete