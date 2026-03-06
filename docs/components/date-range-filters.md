# Date Range Filters

## Overview

Date range filters allow users to filter table data by selecting a start and end date. The implementation uses Flatpickr for a rich date picking experience with dark mode support, localization, and theme integration.

## Features

- **Flatpickr Integration**: Rich date picker with calendar UI
- **Dark Mode Support**: Automatic dark mode styling
- **Theme Integration**: Uses CanvaStack theme colors
- **i18n Support**: Localized date formats and labels
- **Range Selection**: Select start and end dates in one picker
- **Keyboard Accessible**: Full keyboard navigation support
- **Mobile Friendly**: Touch-optimized interface

## Basic Usage

### Controller

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new Order());
    
    $table->setFields([
        'id:ID',
        'customer_name:Customer',
        'total:Total',
        'created_at:Order Date',
    ]);
    
    // Add date range filter
    $table->filterGroups('created_at', 'daterangebox', false, false);
    
    $table->format();
    
    return view('orders.index', ['table' => $table]);
}
```

### View

```blade
@extends('canvastack::layouts.admin')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="card">
            <div class="card-body">
                {!! $table->render() !!}
            </div>
        </div>
    </div>
@endsection

@push('styles')
    {{-- Flatpickr CSS is loaded automatically --}}
@endpush

@push('scripts')
    {{-- Flatpickr JS is loaded automatically --}}
@endpush
```

## Advanced Usage

### Multiple Date Range Filters

```php
// Filter by order date
$table->filterGroups('created_at', 'daterangebox', false, false);

// Filter by delivery date
$table->filterGroups('delivered_at', 'daterangebox', false, false);

// Filter by payment date
$table->filterGroups('paid_at', 'daterangebox', false, false);
```

### Cascading Date Range Filters

```php
// Start date affects end date options
$table->filterGroups('start_date', 'daterangebox', 'end_date', true);
```

### Programmatic Filter Setting

```php
// Set date range filter from request
$filters = [
    'created_at_start' => $request->input('start_date'),
    'created_at_end' => $request->input('end_date'),
];

$table->setActiveFilters($filters);
```

### Custom Date Format

The date format is automatically handled by Flatpickr. The internal format is always `Y-m-d` (2024-01-15), but the display format can be customized:

```javascript
// In your custom JavaScript
flatpickr('.date-range-input', {
    mode: 'range',
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'F j, Y', // Display as "January 15, 2024"
});
```

## Query Logic

### How It Works

When a date range filter is applied, the system automatically adds WHERE clauses to the query:

```php
// User selects: 2024-01-01 to 2024-12-31
// Generated SQL:
WHERE DATE(created_at) >= '2024-01-01' 
  AND DATE(created_at) <= '2024-12-31'
```

### Filter Format

The filter accepts two formats:

**Format 1: Array with start/end keys**
```php
$filters = [
    'created_at' => [
        'start' => '2024-01-01',
        'end' => '2024-12-31',
    ],
];
```

**Format 2: Separate _start and _end keys** (Flatpickr format)
```php
$filters = [
    'created_at_start' => '2024-01-01',
    'created_at_end' => '2024-12-31',
];
```

Both formats are automatically handled by the `applyFiltersToQuery` method.

## Flatpickr Configuration

### Default Configuration

```javascript
{
    mode: 'range',
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'F j, Y',
    allowInput: true,
    clickOpens: true,
    locale: document.documentElement.lang || 'en',
    theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
}
```

### Custom Configuration

You can customize Flatpickr by modifying the Alpine.js component:

```blade
<div x-data="{
    ...flatpickrDateRange(),
    config: {
        ...flatpickrDateRange().config,
        minDate: 'today',
        maxDate: new Date().fp_incr(365), // 1 year from now
        disable: [
            function(date) {
                // Disable weekends
                return (date.getDay() === 0 || date.getDay() === 6);
            }
        ],
    }
}">
    <!-- Date range input -->
</div>
```

## Dark Mode

Dark mode is automatically applied based on the `dark` class on the `<html>` element:

```css
/* Automatically applied when dark mode is active */
.flatpickr-dark.flatpickr-calendar {
    background: rgb(17 24 39);
    border-color: rgb(55 65 81);
}

.flatpickr-dark .flatpickr-day.selected {
    background: var(--cs-color-primary);
    color: white;
}
```

## Theme Integration

The date picker uses CanvaStack theme colors:

```css
/* Primary color for selected dates */
.flatpickr-day.selected {
    background: var(--cs-color-primary, rgb(99 102 241));
    border-color: var(--cs-color-primary, rgb(99 102 241));
}

/* Primary dark color for hover */
.flatpickr-day.selected:hover {
    background: var(--cs-color-primary-dark, rgb(79 70 229));
}
```

## Localization

### Supported Locales

Flatpickr supports 50+ locales. The locale is automatically detected from the HTML lang attribute:

```html
<html lang="id"> <!-- Indonesian -->
<html lang="ar"> <!-- Arabic -->
<html lang="ja"> <!-- Japanese -->
```

### Translation Keys

All user-facing text uses the i18n system:

```php
// resources/lang/en/components.php
'table' => [
    'select_date_range' => 'Select date range',
    'start_date' => 'Start Date',
    'end_date' => 'End Date',
    'from_date' => 'From',
    'to_date' => 'To',
],
```

## Accessibility

### Keyboard Navigation

- **Tab**: Navigate between inputs
- **Enter**: Open/close calendar
- **Arrow Keys**: Navigate dates
- **Escape**: Close calendar
- **Space**: Select date

### Screen Reader Support

```html
<input type="text"
       aria-label="Select date range"
       aria-describedby="date-range-help"
       role="combobox"
       aria-expanded="false">
```

## Performance

### Lazy Loading

Flatpickr is only loaded when a date range filter is present:

```php
// Automatically loads Flatpickr assets
$table->filterGroups('created_at', 'daterangebox');
```

### Caching

Filter options are cached to improve performance:

```php
// Cache filter results for 5 minutes
$table->cache(300);
```

## Troubleshooting

### Flatpickr Not Loading

**Problem**: Date picker doesn't appear

**Solution**: Ensure Flatpickr assets are loaded:

```blade
@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
@endpush
```

### Dark Mode Not Working

**Problem**: Dark mode styling not applied

**Solution**: Ensure dark mode CSS is loaded:

```blade
@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/canvastack/css/components/flatpickr-dark.css') }}">
@endpush
```

### Date Format Issues

**Problem**: Dates not saving correctly

**Solution**: Ensure date format is `Y-m-d`:

```javascript
dateFormat: 'Y-m-d', // Required format
altFormat: 'F j, Y', // Display format (optional)
```

## Examples

### Example 1: Order Date Filter

```php
public function orders(TableBuilder $table)
{
    $table->setModel(new Order());
    $table->filterGroups('created_at', 'daterangebox');
    $table->format();
    
    return view('orders.index', ['table' => $table]);
}
```

### Example 2: Multiple Date Filters

```php
public function reports(TableBuilder $table)
{
    $table->setModel(new Report());
    
    // Filter by creation date
    $table->filterGroups('created_at', 'daterangebox');
    
    // Filter by publication date
    $table->filterGroups('published_at', 'daterangebox');
    
    $table->format();
    
    return view('reports.index', ['table' => $table]);
}
```

### Example 3: With Other Filters

```php
public function sales(TableBuilder $table)
{
    $table->setModel(new Sale());
    
    // Status filter
    $table->filterGroups('status', 'selectbox');
    
    // Date range filter
    $table->filterGroups('sale_date', 'daterangebox');
    
    // Amount range filter
    $table->filterGroups('amount', 'number');
    
    $table->format();
    
    return view('sales.index', ['table' => $table]);
}
```

## API Reference

### TableBuilder Methods

#### `filterGroups(string $column, string $type, $relate = false, bool $bidirectional = false): self`

Add a date range filter to the table.

**Parameters:**
- `$column` - Column name to filter
- `$type` - Filter type (use 'daterangebox')
- `$relate` - Related filters for cascading (optional)
- `$bidirectional` - Enable bi-directional cascade (optional)

**Returns:** `self` for method chaining

### FilterBuilder Methods

#### `applyDateRange(Builder $query, string $column, string $startDate, string $endDate): Builder`

Apply date range filter to query.

**Parameters:**
- `$query` - Query builder instance
- `$column` - Column name
- `$startDate` - Start date (Y-m-d format)
- `$endDate` - End date (Y-m-d format)

**Returns:** `Builder` with date range applied

## Related Documentation

- [Table Builder](./table-builder.md)
- [Filter System](./filters.md)
- [Theme Integration](../features/theming.md)
- [i18n System](../features/i18n.md)
- [Flatpickr Documentation](https://flatpickr.js.org/)

---

**Last Updated**: 2026-03-05  
**Version**: 1.0.0  
**Status**: Complete
