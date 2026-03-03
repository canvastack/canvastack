# Filter Modal Component

## 📦 Location

- **Component**: `packages/canvastack/canvastack/resources/views/components/table/filter-modal.blade.php`
- **Tests**: `packages/canvastack/canvastack/tests/Unit/Components/Table/FilterModalComponentTest.php`
- **Translations**: `packages/canvastack/canvastack/resources/lang/{locale}/ui.php`

## 🎯 Features

- Modal dialog UI with Alpine.js
- Filter button with active filter count badge
- Support for multiple filter types (selectbox, inputbox, datebox)
- Cascading filter logic (parent-child relationships)
- Auto-submit support
- Loading states for async operations
- Session persistence
- Dark mode support
- Smooth animations
- Accessibility compliant
- Mobile responsive

## 📖 Basic Usage

### In Blade Template

```blade
<x-canvastack::table.filter-modal
    :filters="$filters"
    :activeFilters="$activeFilters"
    :tableName="$tableName"
    :activeFilterCount="$activeFilterCount"
/>
```

### Filter Configuration

```php
$filters = [
    [
        'column' => 'period_string',
        'label' => 'Period',
        'type' => 'selectbox',
        'options' => [
            ['value' => '2025-01', 'label' => 'January 2025'],
            ['value' => '2025-02', 'label' => 'February 2025'],
        ],
        'loading' => false,
        'relate' => true,  // Cascade to next filter
        'autoSubmit' => true,  // Auto-submit on change
    ],
    [
        'column' => 'region',
        'label' => 'Region',
        'type' => 'selectbox',
        'options' => [],  // Will be loaded via AJAX
        'loading' => false,
        'relate' => 'cluster',  // Cascade to specific filter
        'autoSubmit' => false,
    ],
    [
        'column' => 'name',
        'label' => 'Name',
        'type' => 'inputbox',
        'options' => [],
        'loading' => false,
        'relate' => false,
        'autoSubmit' => false,
    ],
    [
        'column' => 'created_at',
        'label' => 'Created Date',
        'type' => 'datebox',
        'options' => [],
        'loading' => false,
        'relate' => false,
        'autoSubmit' => false,
    ],
];
```

## 🔧 Props

| Prop | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| filters | array | Yes | [] | Array of filter configurations |
| activeFilters | array | Yes | [] | Currently active filter values |
| tableName | string | Yes | '' | Name of the table for session storage |
| activeFilterCount | int | Yes | 0 | Number of active filters (for badge) |

## 📝 Filter Configuration Schema

Each filter in the `filters` array must have the following structure:

```php
[
    'column' => 'column_name',      // Database column name
    'label' => 'Display Label',     // User-facing label
    'type' => 'selectbox',          // Filter type: selectbox, inputbox, datebox
    'options' => [],                // Options for selectbox (array of ['value' => '', 'label' => ''])
    'loading' => false,             // Loading state (boolean)
    'relate' => false,              // Cascading relationship (boolean, string, or array)
    'autoSubmit' => false,          // Auto-submit on change (boolean)
]
```

### Filter Types

1. **selectbox**: Dropdown select
   - Requires `options` array
   - Supports cascading
   - Supports auto-submit

2. **inputbox**: Text input
   - Free-form text entry
   - No options needed

3. **datebox**: Date picker
   - HTML5 date input
   - No options needed

### Cascading Relationships

The `relate` property defines cascading behavior:

- `false`: No cascading
- `true`: Cascade to all filters after this one
- `'column_name'`: Cascade to specific filter
- `['col1', 'col2']`: Cascade to multiple specific filters

## 📚 Examples

### Example 1: Simple Filters

```php
$filters = [
    [
        'column' => 'status',
        'label' => 'Status',
        'type' => 'selectbox',
        'options' => [
            ['value' => 'active', 'label' => 'Active'],
            ['value' => 'inactive', 'label' => 'Inactive'],
        ],
        'loading' => false,
        'relate' => false,
        'autoSubmit' => false,
    ],
    [
        'column' => 'name',
        'label' => 'Name',
        'type' => 'inputbox',
        'options' => [],
        'loading' => false,
        'relate' => false,
        'autoSubmit' => false,
    ],
];

$activeFilters = [
    'status' => 'active',
];

$activeFilterCount = 1;
```

```blade
<x-canvastack::table.filter-modal
    :filters="$filters"
    :activeFilters="$activeFilters"
    tableName="users"
    :activeFilterCount="$activeFilterCount"
/>
```

### Example 2: Cascading Filters

```php
$filters = [
    [
        'column' => 'period_string',
        'label' => 'Period',
        'type' => 'selectbox',
        'options' => [
            ['value' => '2025-01', 'label' => 'January 2025'],
            ['value' => '2025-02', 'label' => 'February 2025'],
        ],
        'loading' => false,
        'relate' => true,  // Cascade to all next filters
        'autoSubmit' => true,
    ],
    [
        'column' => 'cor',
        'label' => 'COR',
        'type' => 'selectbox',
        'options' => [],  // Loaded via AJAX based on period
        'loading' => false,
        'relate' => true,
        'autoSubmit' => true,
    ],
    [
        'column' => 'region',
        'label' => 'Region',
        'type' => 'selectbox',
        'options' => [],  // Loaded via AJAX based on cor
        'loading' => false,
        'relate' => 'cluster',  // Only cascade to cluster
        'autoSubmit' => false,
    ],
    [
        'column' => 'cluster',
        'label' => 'Cluster',
        'type' => 'selectbox',
        'options' => [],  // Loaded via AJAX based on region
        'loading' => false,
        'relate' => false,
        'autoSubmit' => false,
    ],
];
```

### Example 3: With Active Filters

```php
$filters = [
    [
        'column' => 'period',
        'label' => 'Period',
        'type' => 'selectbox',
        'options' => [
            ['value' => '2025-01', 'label' => 'January 2025'],
            ['value' => '2025-02', 'label' => 'February 2025'],
        ],
        'loading' => false,
        'relate' => false,
        'autoSubmit' => false,
    ],
    [
        'column' => 'status',
        'label' => 'Status',
        'type' => 'selectbox',
        'options' => [
            ['value' => 'active', 'label' => 'Active'],
            ['value' => 'inactive', 'label' => 'Inactive'],
        ],
        'loading' => false,
        'relate' => false,
        'autoSubmit' => false,
    ],
];

$activeFilters = [
    'period' => '2025-01',
    'status' => 'active',
];

$activeFilterCount = 2;  // Badge will show "2"
```

## 🎮 JavaScript API

The component exposes the following Alpine.js methods:

### Methods

```javascript
// Open modal
open = true

// Close modal
open = false

// Apply filters
await applyFilters()

// Clear all filters
await clearFilters()

// Handle filter change (cascading)
await handleFilterChange(filter)

// Update related filters (cascading)
await updateRelatedFilters(parentFilter)

// Get related columns
getRelatedColumns(filter)

// Load initial options
await loadInitialOptions()

// Update active filter count
updateActiveCount()
```

### Properties

```javascript
{
    open: false,                    // Modal open state
    filters: [],                    // Filter configurations
    filterValues: {},               // Current filter values
    activeFilterCount: 0,           // Number of active filters
    isApplying: false,              // Applying state
}
```

## 🔍 Implementation Details

### Modal Structure

The modal consists of:

1. **Filter Button**: Triggers modal open
   - Shows filter icon
   - Displays active filter count badge
   - Animated badge appearance

2. **Modal Backdrop**: Semi-transparent overlay
   - Click to close
   - Smooth fade animation

3. **Modal Content**: Filter form container
   - Header with title and close button
   - Filter form with dynamic fields
   - Action buttons (Apply, Clear)

### Cascading Logic

When a filter with `relate` property changes:

1. Identify related filters
2. Set loading state on related filters
3. Fetch new options via AJAX
4. Update related filter options
5. Clear related filter values
6. Remove loading state

### Auto-Submit

When a filter has `autoSubmit: true`:

1. On change, automatically call `applyFilters()`
2. Save filters to session
3. Reload DataTable
4. Close modal (optional)

### Session Persistence

Filters are saved to session via AJAX:

```javascript
POST /datatable/save-filters
{
    "table": "table_name",
    "filters": {
        "period": "2025-01",
        "status": "active"
    }
}
```

## 🎨 Styling

### Tailwind Classes

The component uses DaisyUI components:

- `btn btn-primary btn-sm`: Filter button
- `badge badge-sm badge-error`: Active filter count
- `select select-bordered`: Select inputs
- `input input-bordered`: Text inputs
- `loading loading-spinner`: Loading indicators

### Dark Mode

Dark mode is supported via Tailwind's `dark:` prefix:

- `dark:bg-gray-900`: Dark background
- `dark:text-gray-100`: Dark text
- `dark:border-gray-800`: Dark borders

### Animations

Smooth transitions using Alpine.js:

- Modal fade in/out (300ms)
- Content slide up/down (300ms)
- Badge scale animation (200ms)

## 🧪 Testing

### Unit Tests

```php
// Test modal renders
public function test_filter_modal_renders_correctly()
{
    $html = View::make('canvastack::components.table.filter-modal', [
        'filters' => $filters,
        'activeFilters' => [],
        'tableName' => 'test_table',
        'activeFilterCount' => 0,
    ])->render();
    
    $this->assertStringContainsString('filter-button', $html);
    $this->assertStringContainsString('filter-modal', $html);
}

// Test filter types
public function test_filter_modal_renders_selectbox_type()
{
    // Test selectbox rendering
}

public function test_filter_modal_renders_inputbox_type()
{
    // Test inputbox rendering
}

public function test_filter_modal_renders_datebox_type()
{
    // Test datebox rendering
}
```

### Browser Tests

```php
public function test_filter_modal_opens_and_closes()
{
    $this->browse(function (Browser $browser) {
        $browser->visit('/users')
                ->click('@filter-button')
                ->pause(300)
                ->assertVisible('@filter-modal')
                ->click('@close-button')
                ->pause(300)
                ->assertMissing('@filter-modal');
    });
}

public function test_cascading_filters_work()
{
    $this->browse(function (Browser $browser) {
        $browser->visit('/reports')
                ->click('@filter-button')
                ->select('@filter-period', '2025-01')
                ->pause(500)
                ->assertSelectHasOptions('@filter-region', ['WEST', 'EAST'])
                ->select('@filter-region', 'WEST')
                ->pause(500)
                ->assertSelectHasOptions('@filter-cluster', ['JAKARTA', 'BANDUNG']);
    });
}
```

## 💡 Tips & Best Practices

1. **Filter Order**: Place parent filters before child filters for cascading
2. **Loading States**: Always show loading indicators for async operations
3. **Error Handling**: Implement proper error handling for AJAX failures
4. **Performance**: Cache filter options when possible
5. **Accessibility**: Use proper ARIA labels and keyboard navigation
6. **Mobile**: Test on mobile devices for responsive behavior
7. **Translations**: Use translation keys for all user-facing text

## 🎭 Common Patterns

### Pattern 1: Simple Status Filter

```php
$filters = [
    [
        'column' => 'status',
        'label' => __('ui.labels.status'),
        'type' => 'selectbox',
        'options' => [
            ['value' => 'active', 'label' => __('ui.status.active')],
            ['value' => 'inactive', 'label' => __('ui.status.inactive')],
        ],
        'loading' => false,
        'relate' => false,
        'autoSubmit' => false,
    ],
];
```

### Pattern 2: Date Range Filter

```php
$filters = [
    [
        'column' => 'start_date',
        'label' => __('ui.labels.start_date'),
        'type' => 'datebox',
        'options' => [],
        'loading' => false,
        'relate' => false,
        'autoSubmit' => false,
    ],
    [
        'column' => 'end_date',
        'label' => __('ui.labels.end_date'),
        'type' => 'datebox',
        'options' => [],
        'loading' => false,
        'relate' => false,
        'autoSubmit' => false,
    ],
];
```

### Pattern 3: Search with Filters

```php
$filters = [
    [
        'column' => 'search',
        'label' => __('ui.labels.search'),
        'type' => 'inputbox',
        'options' => [],
        'loading' => false,
        'relate' => false,
        'autoSubmit' => false,
    ],
    [
        'column' => 'category',
        'label' => __('ui.labels.category'),
        'type' => 'selectbox',
        'options' => $categories,
        'loading' => false,
        'relate' => false,
        'autoSubmit' => false,
    ],
];
```

## 🔗 Related Components

- [FilterManager](./filter-manager-implementation.md) - Backend filter management
- [FilterOptionsProvider](./filter-options-provider.md) - Filter options loading
- [TableBuilder](./table-builder.md) - Table component integration

## 📚 Resources

- [Alpine.js Documentation](https://alpinejs.dev)
- [DaisyUI Components](https://daisyui.com)
- [Tailwind CSS](https://tailwindcss.com)
- [Lucide Icons](https://lucide.dev)

---

**Last Updated**: 2026-03-02  
**Version**: 1.0.0  
**Status**: Complete
