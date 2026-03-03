# Table Component Internationalization

## Overview

This document describes the internationalization (i18n) implementation for the TableBuilder component and its renderers in the CanvaStack package.

**Date**: 2024-02-26  
**Version**: 1.0.0  
**Status**: Completed

---

## Changes Summary

All hardcoded English text strings in the table components have been replaced with Laravel's `__()` translation helper function, making the components fully translatable.

### Files Modified

1. **Translation Files**:
   - `resources/lang/en/components.php` - Added table-specific translation keys
   - `resources/lang/id/components.php` - Added Indonesian translations

2. **Component Files**:
   - `src/Components/Table/Renderers/AdminRenderer.php` - Replaced hardcoded text
   - `src/Components/Table/Renderers/PublicRenderer.php` - Replaced hardcoded text
   - `src/Components/Table/Renderers/BaseRenderer.php` - Replaced hardcoded text

---

## Translation Keys Added

### English (`resources/lang/en/components.php`)

```php
'table' => [
    // ... existing keys ...
    'empty_state' => 'No data available',
    'view' => 'View',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'showing_entries' => 'Showing',
    'to' => 'to',
    'of' => 'of',
    'results' => 'results',
    'total' => 'Total',
    'items' => 'items',
    'datatables' => [
        'processing' => 'Loading...',
        'empty_table' => 'No data available',
        'zero_records' => 'No matching records found',
        'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
        'info_empty' => 'Showing 0 to 0 of 0 entries',
        'info_filtered' => '(filtered from _MAX_ total entries)',
        'search' => 'Search:',
        'paginate' => [
            'first' => 'First',
            'last' => 'Last',
            'next' => 'Next',
            'previous' => 'Previous',
        ],
        'buttons' => [
            'copy' => 'Copy',
            'csv' => 'CSV',
            'excel' => 'Excel',
            'pdf' => 'PDF',
            'print' => 'Print',
        ],
        'length_menu' => 'All',
        'ajax_error' => 'Failed to load table data. Please refresh the page.',
    ],
    'yes' => 'Yes',
    'no' => 'No',
    'na' => 'N/A',
],
```

### Indonesian (`resources/lang/id/components.php`)

```php
'table' => [
    // ... existing keys ...
    'empty_state' => 'Tidak ada data tersedia',
    'view' => 'Lihat',
    'edit' => 'Edit',
    'delete' => 'Hapus',
    'showing_entries' => 'Menampilkan',
    'to' => 'sampai',
    'of' => 'dari',
    'results' => 'hasil',
    'total' => 'Total',
    'items' => 'item',
    'datatables' => [
        'processing' => 'Memuat...',
        'empty_table' => 'Tidak ada data tersedia',
        'zero_records' => 'Tidak ada catatan yang cocok ditemukan',
        'info' => 'Menampilkan _START_ sampai _END_ dari _TOTAL_ entri',
        'info_empty' => 'Menampilkan 0 sampai 0 dari 0 entri',
        'info_filtered' => '(difilter dari _MAX_ total entri)',
        'search' => 'Cari:',
        'paginate' => [
            'first' => 'Pertama',
            'last' => 'Terakhir',
            'next' => 'Selanjutnya',
            'previous' => 'Sebelumnya',
        ],
        'buttons' => [
            'copy' => 'Salin',
            'csv' => 'CSV',
            'excel' => 'Excel',
            'pdf' => 'PDF',
            'print' => 'Cetak',
        ],
        'length_menu' => 'Semua',
        'ajax_error' => 'Gagal memuat data tabel. Silakan refresh halaman.',
    ],
    'yes' => 'Ya',
    'no' => 'Tidak',
    'na' => 'T/A',
],
```

---

## Component Changes

### AdminRenderer.php

**Replaced Strings**:

1. **Search Bar**:
   - `"Search..."` → `__('components.table.search_placeholder')`
   - `"Filter"` → `__('components.table.filter')`

2. **Table Header**:
   - `"Actions"` → `__('components.table.actions')`

3. **Empty State**:
   - `"No data available"` → `__('components.table.empty_state')`

4. **Pagination Footer**:
   - `"Showing"` → `__('components.table.showing_entries')`
   - `"to"` → `__('components.table.to')`
   - `"of"` → `__('components.table.of')`
   - `"results"` → `__('components.table.results')`

5. **Action Buttons**:
   - `"View"` → `__('components.table.view')`
   - `"Edit"` → `__('components.table.edit')`
   - `"Delete"` → `__('components.table.delete')`

6. **DataTables Configuration**:
   - `"Loading..."` → `__('components.table.datatables.processing')`
   - `"No data available"` → `__('components.table.datatables.empty_table')`
   - `"No matching records found"` → `__('components.table.datatables.zero_records')`
   - `"Showing _START_ to _END_ of _TOTAL_ entries"` → `__('components.table.datatables.info')`
   - `"Showing 0 to 0 of 0 entries"` → `__('components.table.datatables.info_empty')`
   - `"(filtered from _MAX_ total entries)"` → `__('components.table.datatables.info_filtered')`
   - `"Search:"` → `__('components.table.datatables.search')`
   - `"First"` → `__('components.table.datatables.paginate.first')`
   - `"Last"` → `__('components.table.datatables.paginate.last')`
   - `"Next"` → `__('components.table.datatables.paginate.next')`
   - `"Previous"` → `__('components.table.datatables.paginate.previous')`
   - `"All"` → `__('components.table.datatables.length_menu')`
   - `"Failed to load table data. Please refresh the page."` → `__('components.table.datatables.ajax_error')`

7. **Boolean Values**:
   - `"Yes"` → `__('components.table.yes')`
   - `"No"` → `__('components.table.no')`

### PublicRenderer.php

**Replaced Strings**:

1. **Empty State**:
   - `"No data available"` → `__('components.table.empty_state')`

2. **Pagination Footer**:
   - `"Total:"` → `__('components.table.total')`
   - `"items"` → `__('components.table.items')`
   - `"Previous"` → `__('components.pagination.previous')`
   - `"Next"` → `__('components.pagination.next')`

3. **DataTables Configuration**: (Same as AdminRenderer)

4. **Formatting**:
   - `"N/A"` → `__('components.table.na')`

### BaseRenderer.php

**Replaced Strings**:

1. **Formatting**:
   - `"N/A"` → `__('components.table.na')`

---

## Usage Examples

### Basic Table with Translations

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    $table->setFields(['name:Name', 'email:Email']);
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

All text in the rendered table will automatically use the current application locale.

### Changing Locale

```php
// In a controller or middleware
App::setLocale('id'); // Switch to Indonesian

// Or in a route
Route::get('/users', function (TableBuilder $table) {
    App::setLocale('id');
    // ... table setup
})->middleware('web');
```

### Adding Custom Translations

To add a new language (e.g., Spanish):

1. Create directory: `resources/lang/es/`
2. Copy `components.php` from `en/` to `es/`
3. Translate all strings to Spanish
4. Set locale: `App::setLocale('es')`

---

## Testing

### Manual Testing

1. **English (Default)**:
   ```php
   App::setLocale('en');
   // Visit table page - should show English text
   ```

2. **Indonesian**:
   ```php
   App::setLocale('id');
   // Visit table page - should show Indonesian text
   ```

3. **Verify All Components**:
   - Search bar placeholder
   - Filter button
   - Column headers (Actions)
   - Empty state message
   - Pagination text
   - Action button tooltips
   - DataTables messages
   - Boolean values (Yes/No)

### Automated Testing

```php
use Tests\TestCase;

class TableI18nTest extends TestCase
{
    public function test_table_uses_english_translations()
    {
        App::setLocale('en');
        
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setData([]);
        $table->setFields(['name:Name']);
        $table->format();
        
        $html = $table->render();
        
        $this->assertStringContainsString('No data available', $html);
        $this->assertStringContainsString('Search...', $html);
        $this->assertStringContainsString('Filter', $html);
    }
    
    public function test_table_uses_indonesian_translations()
    {
        App::setLocale('id');
        
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setData([]);
        $table->setFields(['name:Name']);
        $table->format();
        
        $html = $table->render();
        
        $this->assertStringContainsString('Tidak ada data tersedia', $html);
        $this->assertStringContainsString('Cari...', $html);
        $this->assertStringContainsString('Filter', $html);
    }
}
```

---

## Migration Guide

### For Existing Applications

No code changes required! The table components will automatically use translations based on the current application locale.

### For Custom Renderers

If you have custom renderers extending `AdminRenderer` or `PublicRenderer`, update any hardcoded text to use translation keys:

**Before**:
```php
$html .= '<p>No data found</p>';
```

**After**:
```php
$html .= '<p>' . __('components.table.empty_state') . '</p>';
```

---

## Best Practices

1. **Always use translation keys** for user-facing text
2. **Test with multiple locales** to ensure proper translation
3. **Keep translation keys consistent** across components
4. **Document custom translation keys** if adding new ones
5. **Use fallback locale** (English) for missing translations

---

## Future Enhancements

1. **RTL Support**: Add right-to-left layout support for Arabic, Hebrew, etc.
2. **Date/Time Localization**: Format dates and times according to locale
3. **Number Formatting**: Format numbers, currency according to locale
4. **Pluralization**: Use Laravel's pluralization features for dynamic counts
5. **Translation Management**: Add admin interface for managing translations

---

## Related Documentation

- [Laravel Localization](https://laravel.com/docs/localization)
- [CanvaStack Components](../components.md)
- [TableBuilder API Reference](../api/table.md)
- [Internationalization System](../features/i18n.md)

---

**Last Updated**: 2024-02-26  
**Version**: 1.0.0  
**Status**: Completed  
**Author**: CanvaStack Team
