# DataTables Export Functionality

## 📦 Location

- **Engine**: `src/Components/Table/Engines/DataTablesEngine.php`
- **Method**: `getButtonsConfig()`
- **Assets**: Loaded via `getAssets()`

## 🎯 Features

- Excel export (.xlsx)
- CSV export (.csv)
- PDF export (.pdf)
- Print functionality
- Copy to clipboard
- Respects non-exportable columns
- Automatic filename generation with timestamps
- Internationalization support

## 📖 Basic Usage

### Enable Export Buttons

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    $table->setFields([
        'name:Name',
        'email:Email',
        'created_at:Created At',
    ]);
    
    // Enable export buttons
    $table->setButtons(['excel', 'csv', 'pdf', 'print']);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

### Exclude Columns from Export

```php
// Mark columns as non-exportable
$table->setNonExportableColumns(['password', 'remember_token', 'api_token']);

// These columns will not appear in exported files
$table->setButtons(['excel', 'csv', 'pdf']);
```

## 🔧 Configuration

### Available Export Formats

| Format | Extension | Description |
|--------|-----------|-------------|
| `excel` | .xlsx | Microsoft Excel format |
| `csv` | .csv | Comma-separated values |
| `pdf` | .pdf | Portable Document Format |
| `print` | - | Browser print dialog |
| `copy` | - | Copy to clipboard |

### Button Configuration

The export buttons are configured automatically with:

- **Styling**: Bootstrap 5 button classes
- **Translations**: i18n support via `__()` helper
- **Filenames**: Auto-generated with table name and timestamp
- **Column Exclusion**: Respects `setNonExportableColumns()`

### Required Assets

The following assets are automatically loaded:

**JavaScript Libraries:**
- DataTables Buttons extension
- JSZip (for Excel export)
- pdfMake (for PDF export)
- buttons.html5.js (HTML5 export)
- buttons.print.js (Print functionality)

**CSS:**
- buttons.bootstrap5.css

## 📝 Examples

### Example 1: Basic Export

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    $table->setFields([
        'id:ID',
        'name:Name',
        'email:Email',
        'created_at:Created',
    ]);
    
    // Enable all export formats
    $table->setButtons(['excel', 'csv', 'pdf', 'print']);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

**Result**: Export buttons appear above the table with Excel, CSV, PDF, and Print options.

### Example 2: Selective Export

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    $table->setFields([
        'id:ID',
        'name:Name',
        'email:Email',
        'password:Password',
        'created_at:Created',
    ]);
    
    // Only enable Excel and CSV
    $table->setButtons(['excel', 'csv']);
    
    // Exclude sensitive columns
    $table->setNonExportableColumns(['password']);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

**Result**: Only Excel and CSV buttons appear, and the password column is excluded from exports.

### Example 3: Custom Table Name for Export

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    $table->setTableName('users_report'); // Used in filename
    
    $table->setFields([
        'name:Name',
        'email:Email',
        'status:Status',
    ]);
    
    $table->setButtons(['excel', 'pdf']);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

**Result**: Exported files will be named `users_report_2026-03-05_143022.xlsx` and `users_report_2026-03-05_143022.pdf`.

### Example 4: Export with Server-Side Processing

```php
public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    $table->setServerSide(true); // Enable server-side processing
    
    $table->setFields([
        'name:Name',
        'email:Email',
        'department:Department',
        'created_at:Created',
    ]);
    
    // Export works with server-side processing
    $table->setButtons(['excel', 'csv', 'pdf']);
    
    $table->format();
    
    return view('users.index', ['table' => $table]);
}
```

**Result**: Export buttons work seamlessly with server-side processing, exporting only visible/filtered data.

## 🎮 Export Options

### Excel Export

- **Format**: .xlsx (Excel 2007+)
- **Features**:
  - Preserves column formatting
  - Includes table headers
  - Auto-sized columns
  - Respects column exclusions

### CSV Export

- **Format**: .csv (Comma-separated)
- **Features**:
  - Field separator: `,`
  - Field boundary: `"`
  - UTF-8 encoding
  - Compatible with Excel and Google Sheets

### PDF Export

- **Format**: .pdf
- **Features**:
  - Landscape orientation (default)
  - A4 page size
  - Includes table title
  - Respects column exclusions
  - Professional formatting

### Print

- **Features**:
  - Opens browser print dialog
  - Optimized print layout
  - Includes table title
  - Respects column exclusions

## 🔍 Implementation Details

### Button Configuration Method

```php
protected function getButtonsConfig(TableBuilder $table): ?array
{
    $buttons = $table->getExportButtons();
    
    if (empty($buttons)) {
        return null;
    }
    
    $config = [];
    $nonExportableColumns = $table->getNonExportableColumns();
    $columns = $table->getColumns();
    
    // Build column indices to exclude
    $excludeIndices = [];
    if (!empty($nonExportableColumns)) {
        $columnKeys = array_keys($columns);
        foreach ($nonExportableColumns as $nonExportableColumn) {
            $index = array_search($nonExportableColumn, $columnKeys);
            if ($index !== false) {
                $excludeIndices[] = $index;
            }
        }
    }
    
    // Configure each button
    foreach ($buttons as $button) {
        $buttonConfig = [
            'extend' => $button,
            'className' => 'btn btn-sm btn-primary me-1',
            'text' => $this->getButtonText($button),
            'exportOptions' => [
                'columns' => $this->getExportColumns($excludeIndices, $columns),
            ],
        ];
        
        // Add format-specific configuration
        $config[] = $this->configureButton($buttonConfig, $button, $table);
    }
    
    return $config;
}
```

### Filename Generation

```php
protected function generateFilename(TableBuilder $table, string $extension): string
{
    $tableName = $table->getTableName() ?? 'export';
    $timestamp = date('Y-m-d_His');
    
    return "{$tableName}_{$timestamp}.{$extension}";
}
```

**Example filenames:**
- `users_2026-03-05_143022.xlsx`
- `orders_2026-03-05_143022.csv`
- `reports_2026-03-05_143022.pdf`

## 🎯 Accessibility

- **Keyboard Navigation**: All export buttons are keyboard accessible
- **ARIA Labels**: Buttons have descriptive labels
- **Screen Reader Support**: Button text is announced correctly
- **Focus Indicators**: Visible focus states for keyboard navigation

## 🎨 Styling

Export buttons use Bootstrap 5 classes:

```html
<button class="btn btn-sm btn-primary me-1">
    Excel
</button>
```

**Customization:**

You can customize button styling via CSS:

```css
.dt-buttons .btn {
    background: var(--cs-color-primary);
    border-color: var(--cs-color-primary);
    color: white;
}

.dt-buttons .btn:hover {
    background: var(--cs-color-primary-dark);
    border-color: var(--cs-color-primary-dark);
}
```

## 💡 Tips & Best Practices

1. **Exclude Sensitive Data**: Always use `setNonExportableColumns()` for sensitive fields like passwords, tokens, etc.

2. **Meaningful Table Names**: Set a descriptive table name for better export filenames:
   ```php
   $table->setTableName('monthly_sales_report');
   ```

3. **Limit Export Formats**: Only enable formats your users need:
   ```php
   // Most users only need Excel and CSV
   $table->setButtons(['excel', 'csv']);
   ```

4. **Server-Side Processing**: Export works with server-side processing but only exports visible/filtered data.

5. **Large Datasets**: For very large datasets, consider implementing custom export logic with background jobs.

6. **Column Order**: Export respects the column order defined in `setFields()`.

7. **Hidden Columns**: Hidden columns (via `setHiddenColumns()`) are still exported unless marked as non-exportable.

## 🔗 Related Components

- [DataTables Engine](datatables-engine.md) - Main engine documentation
- [TableBuilder API](../api/table.md) - Complete API reference
- [Server-Side Processing](datatables-server-side.md) - Server-side processing guide
- [Column Configuration](table-columns.md) - Column configuration guide

## 📚 Resources

- [DataTables Buttons Extension](https://datatables.net/extensions/buttons/)
- [JSZip Documentation](https://stuk.github.io/jszip/)
- [pdfMake Documentation](http://pdfmake.org/)

---

**Last Updated**: 2026-03-05  
**Version**: 1.0.0  
**Status**: Complete  
**Validates**: Requirements 17.1-17.6, 34.1
