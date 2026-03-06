# Pluralization Usage in Table Component

## Overview

The table component supports pluralization using Laravel's `trans_choice()` function. This allows for proper grammatical handling of singular and plural forms across different languages.

**Validates**: Requirement 52.12 - Support trans_choice() for pluralization

---

## Available Pluralization Keys

The following pluralization keys are available in `components.table.*`:

| Key | Description | Example (en) |
|-----|-------------|--------------|
| `items_count` | Count of items | "No items", "1 item", "5 items" |
| `rows_count` | Count of rows | "No rows", "1 row", "10 rows" |
| `entries_count` | Count of entries | "No entries", "1 entry", "25 entries" |
| `selected_items` | Count of selected items | "No items selected", "1 item selected", "3 items selected" |
| `filters_active` | Count of active filters | "No filters active", "1 filter active", "4 filters active" |
| `columns_hidden` | Count of hidden columns | "No columns hidden", "1 column hidden", "5 columns hidden" |
| `results_found` | Count of search results | "No results found", "1 result found", "50 results found" |

---

## Basic Usage

### In PHP/Controllers

```php
use Illuminate\Support\Facades\App;

// Get pluralized text
$itemsText = trans_choice('components.table.items_count', $count);

// Examples:
trans_choice('components.table.items_count', 0);  // "No items"
trans_choice('components.table.items_count', 1);  // "1 item"
trans_choice('components.table.items_count', 5);  // "5 items"
```

### In Blade Templates

```blade
{{-- Display item count --}}
<p>{{ trans_choice('components.table.items_count', $items->count()) }}</p>

{{-- Display selected count --}}
<p>{{ trans_choice('components.table.selected_items', $selectedCount) }}</p>

{{-- Display active filters --}}
<p>{{ trans_choice('components.table.filters_active', count($activeFilters)) }}</p>
```

### In Alpine.js Components

```blade
<div x-data="{
    count: 0,
    translations: {
        items_0: '{{ trans_choice('components.table.items_count', 0) }}',
        items_1: '{{ trans_choice('components.table.items_count', 1) }}',
        items_many: '{{ __('components.table.items') }}'
    }
}">
    <span x-text="count === 0 ? translations.items_0 : 
                  count === 1 ? translations.items_1 : 
                  count + ' ' + translations.items_many"></span>
</div>
```

---

## Language-Specific Behavior

### English (en)

English has simple plural rules: zero/one vs. many.

```php
trans_choice('components.table.items_count', 0);   // "No items"
trans_choice('components.table.items_count', 1);   // "1 item"
trans_choice('components.table.items_count', 2);   // "2 items"
trans_choice('components.table.items_count', 100); // "100 items"
```

### Indonesian (id)

Indonesian doesn't have plural forms - same word for all counts.

```php
trans_choice('components.table.items_count', 0);   // "Tidak ada item"
trans_choice('components.table.items_count', 1);   // "1 item"
trans_choice('components.table.items_count', 2);   // "2 item"
trans_choice('components.table.items_count', 100); // "100 item"
```

### Arabic (ar)

Arabic has complex plural rules: zero, one, two, few (3-10), many (11+).

```php
trans_choice('components.table.items_count', 0);   // "لا توجد عناصر" (no items)
trans_choice('components.table.items_count', 1);   // "عنصر واحد" (one item)
trans_choice('components.table.items_count', 2);   // "عنصران" (two items)
trans_choice('components.table.items_count', 5);   // "5 عناصر" (5 items - few)
trans_choice('components.table.items_count', 15);  // "15 عنصر" (15 items - many)
```

---

## Common Use Cases

### 1. Pagination Info

```blade
<div class="pagination-info">
    {{ trans_choice('components.table.entries_count', $total) }}
</div>
```

### 2. Selection Counter

```blade
<div class="selection-counter" x-show="selectedCount > 0">
    {{ trans_choice('components.table.selected_items', $selectedCount) }}
</div>
```

### 3. Filter Badge

```blade
<button class="filter-button">
    <i data-lucide="filter"></i>
    <span>{{ __('components.table.filters') }}</span>
    @if($activeFiltersCount > 0)
        <span class="badge">
            {{ trans_choice('components.table.filters_active', $activeFiltersCount) }}
        </span>
    @endif
</button>
```

### 4. Search Results

```blade
<div class="search-results">
    <p>{{ trans_choice('components.table.results_found', $results->count()) }}</p>
</div>
```

### 5. Column Visibility

```blade
<div class="column-visibility">
    <button>
        <i data-lucide="eye-off"></i>
        {{ trans_choice('components.table.columns_hidden', $hiddenColumnsCount) }}
    </button>
</div>
```

---

## Advanced Usage

### Dynamic Pluralization in JavaScript

For dynamic content that changes without page reload:

```javascript
// Store pluralization templates
const pluralTemplates = {
    items: {
        0: '{{ trans_choice('components.table.items_count', 0) }}',
        1: '{{ trans_choice('components.table.items_count', 1) }}',
        many: '{{ __('components.table.items') }}'
    }
};

// Function to get pluralized text
function getPluralText(count) {
    if (count === 0) return pluralTemplates.items[0];
    if (count === 1) return pluralTemplates.items[1];
    return count + ' ' + pluralTemplates.items.many;
}

// Usage
document.getElementById('counter').textContent = getPluralText(5);
```

### With Alpine.js

```blade
<div x-data="tableCounter()">
    <p x-text="getCountText()"></p>
    <button @click="increment">Add Item</button>
</div>

<script>
function tableCounter() {
    return {
        count: 0,
        templates: {
            zero: '{{ trans_choice('components.table.items_count', 0) }}',
            one: '{{ trans_choice('components.table.items_count', 1) }}',
            many: '{{ __('components.table.items') }}'
        },
        
        increment() {
            this.count++;
        },
        
        getCountText() {
            if (this.count === 0) return this.templates.zero;
            if (this.count === 1) return this.templates.one;
            return this.count + ' ' + this.templates.many;
        }
    };
}
</script>
```

---

## Testing Pluralization

### Unit Test Example

```php
use Illuminate\Support\Facades\App;

public function test_pluralization_works_correctly()
{
    App::setLocale('en');
    
    // Test zero
    $this->assertEquals(
        'No items',
        trans_choice('components.table.items_count', 0)
    );
    
    // Test one
    $this->assertEquals(
        '1 item',
        trans_choice('components.table.items_count', 1)
    );
    
    // Test many
    $this->assertEquals(
        '5 items',
        trans_choice('components.table.items_count', 5)
    );
}
```

### Feature Test Example

```php
public function test_table_displays_correct_item_count()
{
    $items = Item::factory()->count(5)->create();
    
    $response = $this->get('/items');
    
    $response->assertSee(trans_choice('components.table.items_count', 5));
}
```

---

## Best Practices

### 1. Always Use trans_choice() for Counts

❌ **Bad**:
```php
echo $count . ' items';
```

✅ **Good**:
```php
echo trans_choice('components.table.items_count', $count);
```

### 2. Pass Count as Second Parameter

❌ **Bad**:
```php
trans_choice('components.table.items_count', 0, ['count' => $count]);
```

✅ **Good**:
```php
trans_choice('components.table.items_count', $count);
```

### 3. Use Appropriate Keys

Choose the most specific key for your use case:

```php
// For general items
trans_choice('components.table.items_count', $count);

// For table rows specifically
trans_choice('components.table.rows_count', $count);

// For database entries
trans_choice('components.table.entries_count', $count);
```

### 4. Handle Zero Explicitly

All pluralization keys include a zero case:

```php
// This will show "No items" instead of "0 items"
trans_choice('components.table.items_count', 0);
```

### 5. Test All Locales

When adding new pluralization keys, test with all supported locales:

```php
foreach (['en', 'id', 'ar'] as $locale) {
    App::setLocale($locale);
    $result = trans_choice('components.table.items_count', 5);
    $this->assertNotEmpty($result);
}
```

---

## Adding New Pluralization Keys

To add a new pluralization key:

### 1. Add to English (en)

```php
// resources/lang/en/components.php
'table' => [
    // ... existing keys
    'pages_count' => '{0} No pages|{1} :count page|[2,*] :count pages',
],
```

### 2. Add to Indonesian (id)

```php
// resources/lang/id/components.php
'table' => [
    // ... existing keys
    'pages_count' => '{0} Tidak ada halaman|{1} :count halaman|[2,*] :count halaman',
],
```

### 3. Add to Arabic (ar)

```php
// resources/lang/ar/components.php
'table' => [
    // ... existing keys
    'pages_count' => '{0} لا توجد صفحات|{1} صفحة واحدة|{2} صفحتان|[3,10] :count صفحات|[11,*] :count صفحة',
],
```

### 4. Add Tests

```php
public function test_pages_count_pluralization()
{
    App::setLocale('en');
    
    $this->assertEquals('No pages', trans_choice('components.table.pages_count', 0));
    $this->assertEquals('1 page', trans_choice('components.table.pages_count', 1));
    $this->assertEquals('5 pages', trans_choice('components.table.pages_count', 5));
}
```

---

## Troubleshooting

### Issue: Pluralization Not Working

**Problem**: `trans_choice()` returns the translation key instead of translated text.

**Solution**: Ensure the translation key exists in all locale files:

```bash
# Check if key exists
grep -r "items_count" resources/lang/
```

### Issue: Wrong Plural Form

**Problem**: Getting "1 items" instead of "1 item".

**Solution**: Check the pluralization rule syntax in the translation file:

```php
// Correct
'items_count' => '{0} No items|{1} :count item|[2,*] :count items',

// Wrong
'items_count' => ':count items',
```

### Issue: Arabic Pluralization Not Working

**Problem**: Arabic shows wrong plural form for numbers 3-10.

**Solution**: Ensure Arabic pluralization rules are complete:

```php
// Correct - includes all Arabic plural forms
'items_count' => '{0} لا توجد عناصر|{1} عنصر واحد|{2} عنصران|[3,10] :count عناصر|[11,*] :count عنصر',
```

---

## References

- [Laravel Localization Documentation](https://laravel.com/docs/localization#pluralization)
- [Requirement 52.12](../../.kiro/specs/dual-datatable-engine/requirements.md#requirement-52-i18n-system-compliance)
- [i18n System Standards](../../.kiro/steering/i18n-system.md)
- [Translation Files](../../resources/lang/)

---

**Last Updated**: 2026-03-05  
**Version**: 1.0.0  
**Status**: Complete

