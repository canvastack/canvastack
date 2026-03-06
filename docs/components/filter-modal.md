# Filter Modal Component

## Overview

The Filter Modal component provides an advanced filtering interface for TableBuilder. It supports multiple filter types, bi-directional cascading filters, date ranges, and session persistence.

## Features

- Multiple filter types (select, text, daterange, number)
- Bi-directional cascading filters
- Active filter count badge
- Session persistence
- Dark mode support
- RTL support
- Accessibility compliant (WCAG 2.1 AA)
- Theme Engine integration
- i18n support

## Location

- **Component**: `resources/views/components/table/partials/filter-modal.blade.php`
- **Styles**: `resources/css/components/filter-modal.css`
- **Translations**: `resources/lang/{locale}/components.php` (table.filters section)

## Basic Usage

### In Blade Template

```blade
<x-table-filter-modal
    :filter-groups="$filterGroups"
    :active-filters="$activeFilters"
    table-id="users-table"
/>
```

### In Controller

```php
public function index(TableBuilder $table)
{
    $filterGroups = [
        'status' => [
            'label' => __('ui.labels.status'),
            'type' => 'select',
            'options' => [
                ['value' => 'active', 'label' => __('ui.status.active')],
                ['value' => 'inactive', 'label' => __('ui.status.inactive')],
            ],
        ],
        'name' => [
            'label' => __('ui.labels.name'),
            'type' => 'text',
        ],
        'created_at' => [
            'label' => __('ui.labels.created_date'),
            'type' => 'daterange',
        ],
    ];
    
    $activeFilters = session('table_filters_users-table', []);
    
    return view('users.index', [
        'table' => $table,
        'filterGroups' => $filterGroups,
        'activeFilters' => $activeFilters,
    ]);
}
```

## Filter Types

### Select Filter

```php
'category' => [
    'label' => 'Category',
    'type' => 'select',
    'options' => [
        ['value' => '1', 'label' => 'Electronics'],
        ['value' => '2', 'label' => 'Clothing'],
        ['value' => '3', 'label' => 'Books'],
    ],
]
```

### Text Filter

```php
'search' => [
    'label' => 'Search',
    'type' => 'text',
]
```

### Date Range Filter

```php
'period' => [
    'label' => 'Period',
    'type' => 'daterange',
]
```

### Number Range Filter

```php
'price' => [
    'label' => 'Price',
    'type' => 'number',
]
```

## Bi-directional Cascading Filters

Cascading filters automatically update dependent filter options when a parent filter changes.

### Example: Province → City → District

```php
$filterGroups = [
    'province_id' => [
        'label' => 'Province',
        'type' => 'select',
        'ajax' => route('api.provinces'),
        'cascade' => ['city_id'], // Update city when province changes
    ],
    'city_id' => [
        'label' => 'City',
        'type' => 'select',
        'ajax' => route('api.cities'),
        'cascade' => ['district_id'], // Update district when city changes
    ],
    'district_id' => [
        'label' => 'District',
        'type' => 'select',
        'ajax' => route('api.districts'),
    ],
];
```

### API Endpoint for Cascading Filters

```php
public function getCities(Request $request)
{
    $provinceId = $request->input('filters.province_id');
    
    $cities = City::query()
        ->when($provinceId, fn($q) => $q->where('province_id', $provinceId))
        ->get(['id as value', 'name as label']);
    
    return response()->json(['options' => $cities]);
}
```

## Session Persistence

Filters are automatically saved to session storage and restored on page load.

### Manual Session Management

```php
// Save filters
session(['table_filters_users-table' => $filters]);

// Load filters
$filters = session('table_filters_users-table', []);

// Clear filters
session()->forget('table_filters_users-table');
```

## JavaScript Events

### Listen for Filter Changes

```javascript
window.addEventListener('table-filters-applied', (event) => {
    const { tableId, filters } = event.detail;
    console.log('Filters applied:', filters);
    
    // Reload table data
    reloadTableData(tableId, filters);
});
```

### Programmatically Apply Filters

```javascript
// Trigger filter application
window.dispatchEvent(new CustomEvent('table-filters-applied', {
    detail: {
        tableId: 'users-table',
        filters: {
            status: 'active',
            name: 'John'
        }
    }
}));
```

## Styling Customization

### Using Theme Colors

The component automatically uses theme colors via CSS variables:

```css
/* Primary color for badges and buttons */
background: var(--cs-color-primary);

/* Text colors */
color: var(--cs-color-text);
```

### Custom Styling

```blade
<x-table-filter-modal
    :filter-groups="$filterGroups"
    :active-filters="$activeFilters"
    table-id="users-table"
    class="custom-filter-modal"
/>
```

```css
.custom-filter-modal .filter-button {
    /* Custom button styles */
}

.custom-filter-modal .active-filter-badge {
    /* Custom badge styles */
}
```

## Accessibility

### Keyboard Navigation

- `Tab` - Navigate between filter inputs
- `Enter` - Apply filters
- `Escape` - Close modal
- `Space` - Toggle select dropdowns

### Screen Reader Support

All interactive elements have proper ARIA labels:

```blade
:aria-label="__('components.table.filters')"
```

### Focus Management

Focus is automatically managed when opening/closing the modal.

## Dark Mode

The component automatically adapts to dark mode using Tailwind's `dark:` prefix:

```blade
class="bg-white dark:bg-gray-900 
       text-gray-900 dark:text-gray-100
       border-gray-200 dark:border-gray-800"
```

## RTL Support

The component automatically supports RTL layouts for RTL locales (ar, he, fa, ur).

## Translations

### Required Translation Keys

```php
// resources/lang/en/components.php
'table' => [
    'filters' => 'Filters',
    'filter_by' => 'Filter By',
    'active_filters' => 'Active Filters',
    'clear_filters' => 'Clear Filters',
    'clear_all' => 'Clear All',
    'apply_filters' => 'Apply Filters',
    'search_in' => 'Search in :field',
    'start_date' => 'Start Date',
    'end_date' => 'End Date',
    'min' => 'Min',
    'max' => 'Max',
    'all' => 'All',
],
```

## Integration with TableBuilder

### Complete Example

```php
public function index(TableBuilder $table)
{
    // Define filter groups
    $filterGroups = [
        'status' => [
            'label' => __('ui.labels.status'),
            'type' => 'select',
            'options' => [
                ['value' => 'active', 'label' => __('ui.status.active')],
                ['value' => 'inactive', 'label' => __('ui.status.inactive')],
            ],
        ],
        'created_at' => [
            'label' => __('ui.labels.created_date'),
            'type' => 'daterange',
        ],
    ];
    
    // Get active filters from session
    $activeFilters = session('table_filters_users-table', []);
    
    // Apply filters to table query
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Apply filters
    if (!empty($activeFilters['status'])) {
        $table->where('status', $activeFilters['status']);
    }
    
    if (!empty($activeFilters['created_at_start'])) {
        $table->whereDate('created_at', '>=', $activeFilters['created_at_start']);
    }
    
    if (!empty($activeFilters['created_at_end'])) {
        $table->whereDate('created_at', '<=', $activeFilters['created_at_end']);
    }
    
    $table->setFields(['name:Name', 'email:Email', 'status:Status', 'created_at:Created']);
    $table->format();
    
    return view('users.index', [
        'table' => $table,
        'filterGroups' => $filterGroups,
        'activeFilters' => $activeFilters,
    ]);
}
```

## Best Practices

1. **Use descriptive filter labels** - Make it clear what each filter does
2. **Limit filter count** - Too many filters can overwhelm users (max 5-7)
3. **Provide sensible defaults** - Pre-select common filter values
4. **Use cascading filters wisely** - Only for truly dependent relationships
5. **Cache filter options** - For better performance with large datasets
6. **Validate filter inputs** - Always validate on the server side
7. **Clear filters on navigation** - Reset filters when navigating away

## Performance Considerations

### Caching Filter Options

```php
$categories = Cache::remember('filter_categories', 3600, function () {
    return Category::all(['id as value', 'name as label']);
});
```

### Lazy Loading Filter Options

```php
'category' => [
    'label' => 'Category',
    'type' => 'select',
    'ajax' => route('api.categories'), // Load on demand
]
```

## Troubleshooting

### Filters Not Persisting

Check session configuration:

```php
// config/session.php
'driver' => 'file', // or 'database', 'redis'
```

### Cascading Filters Not Working

Verify AJAX endpoint returns correct format:

```json
{
    "options": [
        {"value": "1", "label": "Option 1"},
        {"value": "2", "label": "Option 2"}
    ]
}
```

### Dark Mode Not Working

Ensure Tailwind dark mode is configured:

```javascript
// tailwind.config.js
module.exports = {
    darkMode: 'class',
    // ...
}
```

## Related Components

- [TableBuilder](./table-builder.md) - Main table component
- [SearchInput](./search-input.md) - Global search component
- [Pagination](./pagination.md) - Pagination component

---

**Last Updated**: 2026-03-03  
**Version**: 1.0.0  
**Status**: Complete
