# TanStack Table Column Pinning

## 📦 Location

- **Engine**: `src/Components/Table/Engines/TanStackEngine.php`
- **Renderer**: `src/Components/Table/Renderers/TanStackRenderer.php`
- **Test**: `tests/Unit/Components/Table/Engines/TanStackEngineColumnPinningTest.php`

## 🎯 Features

- Pin columns to the left side of the table
- Pin columns to the right side of the table
- Pinned columns remain visible during horizontal scrolling
- Sticky positioning with shadow effects
- Dark mode support
- Smooth transitions

## 📖 Basic Usage

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setEngine('tanstack'); // Use TanStack engine
    $table->setModel(new User());
    
    $table->setFields([
        'id:ID',
        'name:Name',
        'email:Email',
        'status:Status',
        'created_at:Created',
        'actions:Actions'
    ]);
    
    // Pin first 2 columns to the left (id, name)
    // Pin last 1 column to the right (actions)
    $table->fixedColumns(2, 1);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

## 🔧 Configuration

### Pin Left Columns Only

```php
// Pin first 3 columns to the left
$table->fixedColumns(3, null);
```

### Pin Right Columns Only

```php
// Pin last 2 columns to the right
$table->fixedColumns(null, 2);
```

### Pin Both Sides

```php
// Pin first 2 columns to left, last 1 column to right
$table->fixedColumns(2, 1);
```

### No Pinning

```php
// No pinned columns
$table->fixedColumns(null, null);
// or simply don't call fixedColumns()
```

## 🎨 Styling

### CSS Classes

The renderer automatically applies these CSS classes:

- `.tanstack-table-pinned-left` - Applied to left-pinned columns
- `.tanstack-table-pinned-right` - Applied to right-pinned columns

### Sticky Positioning

Pinned columns use CSS `position: sticky` with:
- Left-pinned: `left: 0`
- Right-pinned: `right: 0`
- Z-index: `10` (above regular columns)
- Box shadow for visual separation

### Dark Mode

Dark mode is automatically supported through CSS variables:
- Background colors adapt to theme
- Border colors adapt to theme
- Shadow colors adapt to theme

## 🔍 Implementation Details

### Column Pinning Configuration

The `getColumnPinningConfig()` method in `TanStackEngine` generates the pinning configuration:

```php
protected function getColumnPinningConfig(TableBuilder $table): ?array
{
    $config = $table->getConfiguration();

    if ($config->fixedLeft === null && $config->fixedRight === null) {
        return null;
    }

    $pinnedColumns = [
        'left' => [],
        'right' => [],
    ];

    // Pin left columns
    if ($config->fixedLeft > 0) {
        $fields = array_keys($config->fields);
        $pinnedColumns['left'] = array_slice($fields, 0, $config->fixedLeft);
    }

    // Pin right columns
    if ($config->fixedRight > 0) {
        $fields = array_keys($config->fields);
        $pinnedColumns['right'] = array_slice($fields, -$config->fixedRight);
    }

    return [
        'enabled' => true,
        'left' => $pinnedColumns['left'],
        'right' => $pinnedColumns['right'],
    ];
}
```

### Alpine.js Integration

The renderer includes a `getColumnClass()` helper function in Alpine.js:

```javascript
getColumnClass(column, index) {
    const classes = {
        'sortable': column.sortable
    };
    
    // Check if column is pinned to left
    if (this.pinnedLeft.includes(column.id)) {
        classes['tanstack-table-pinned-left'] = true;
    }
    
    // Check if column is pinned to right
    if (this.pinnedRight.includes(column.id)) {
        classes['tanstack-table-pinned-right'] = true;
    }
    
    return classes;
}
```

### Rendering

The table template applies the column classes dynamically:

```html
<th :class="getColumnClass(column, index)" @click="column.sortable && onSort(column.id)">
    <span x-text="column.label"></span>
</th>
```

## 🧪 Testing

### Unit Tests

The implementation includes comprehensive unit tests:

```php
// Test column pinning configuration
test_column_pinning_config_is_generated_correctly()

// Test null when no columns pinned
test_column_pinning_returns_null_when_no_columns_pinned()

// Test left-only pinning
test_only_left_columns_can_be_pinned()

// Test right-only pinning
test_only_right_columns_can_be_pinned()

// Test sticky positioning CSS
test_pinned_columns_have_sticky_positioning()
```

Run tests:
```bash
./vendor/bin/phpunit tests/Unit/Components/Table/Engines/TanStackEngineColumnPinningTest.php
```

## 💡 Tips & Best Practices

1. **Pin Important Columns** - Pin ID or name columns to the left for easy reference
2. **Pin Actions** - Pin action columns to the right for consistent access
3. **Limit Pinned Columns** - Don't pin too many columns (max 2-3 per side)
4. **Consider Mobile** - Pinned columns work best on desktop; consider responsive design
5. **Test Scrolling** - Always test horizontal scrolling with pinned columns

## 🎭 Common Patterns

### Pattern 1: ID and Actions Pinned

```php
$table->setFields([
    'id:ID',
    'name:Name',
    'email:Email',
    'phone:Phone',
    'address:Address',
    'actions:Actions'
]);

// Pin ID to left, Actions to right
$table->fixedColumns(1, 1);
```

### Pattern 2: Multiple Left Columns

```php
$table->setFields([
    'id:ID',
    'name:Name',
    'email:Email',
    'status:Status',
    'created_at:Created'
]);

// Pin ID and Name to left
$table->fixedColumns(2, null);
```

### Pattern 3: Wide Tables

```php
$table->setFields([
    'id:ID',
    'name:Name',
    'col1:Column 1',
    'col2:Column 2',
    'col3:Column 3',
    'col4:Column 4',
    'col5:Column 5',
    'status:Status',
    'actions:Actions'
]);

// Pin ID and Name to left, Status and Actions to right
$table->fixedColumns(2, 2);
```

## 🔗 Related Components

- [TanStack Engine](tanstack-engine.md) - Main TanStack Table engine
- [DataTables Fixed Columns](datatables-fixed-columns.md) - DataTables equivalent
- [Table Builder](table-builder.md) - Main table component

## 📚 Resources

- [TanStack Table Column Pinning](https://tanstack.com/table/v8/docs/guide/column-pinning)
- [CSS Sticky Positioning](https://developer.mozilla.org/en-US/docs/Web/CSS/position#sticky)
- [Alpine.js Directives](https://alpinejs.dev/directives)

---

**Last Updated**: 2026-03-05  
**Version**: 1.0.0  
**Status**: Complete  
**Validates**: Requirements 12.1, 12.2, 12.3, 12.5, 12.6
