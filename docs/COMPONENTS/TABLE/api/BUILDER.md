# Table Builder API Reference

## Overview

The `Builder` class is responsible for generating HTML table structures with DataTables integration. It handles table rendering, column configuration, accessibility features, and ARIA attributes.

**Namespace:** `Canvastack\Canvastack\Library\Components\Table\Craft\Builder`

**Used By:** `Objects` class (extends Builder)

---

## Table of Contents

- [Overview](#overview)
- [Core Rendering Methods](#core-rendering-methods)
- [Accessibility Features](#accessibility-features)
- [Column Configuration](#column-configuration)
- [ARIA Attributes](#aria-attributes)
- [Security Features](#security-features)

---

## Core Rendering Methods

### table()

Generates complete HTML table structure.

**Signature:**
```php
protected function table(
    string $name,
    array $columns = [],
    array $attributes = [],
    ?string $label = null
): string
```

**Parameters:**
- `$name` (string): Table identifier
- `$columns` (array): Column definitions
- `$attributes` (array): HTML attributes
- `$label` (string|null): Table caption

**Returns:** HTML string

**Internal Method:** Used by Objects class, not called directly.

---

### header()

Generates table header HTML.

**Signature:**
```php
private function header(array $data = []): string
```

**Parameters:**
- `$data` (array): Header configuration including columns, alignment, colors

**Returns:** HTML `<thead>` element

**Features:**
- Automatic ARIA attributes
- Sortable column indicators
- Column merging support
- Custom styling support

---

### body()

Generates table body configuration for DataTables.

**Signature:**
```php
private function body(array $data = []): string
```

**Parameters:**
- `$data` (array): Body configuration including columns, formatting, actions

**Returns:** JSON configuration for DataTables

**Features:**
- Server-side processing configuration
- Column definitions
- Sorting and searching configuration
- Action button configuration

---

## Accessibility Features

### Automatic ARIA Attributes

The Builder automatically adds ARIA attributes to all table elements:

**Table Element:**
```html
<table role="table" aria-label="Users table">
```

**Header Cells:**
```html
<th role="columnheader" aria-sort="ascending">Name</th>
```

**Body Cells:**
```html
<td role="cell">John Doe</td>
```

**Rows:**
```html
<tr role="row">
```

### buildAriaAttributes()

Generates ARIA attributes for table elements.

**Signature:**
```php
private function buildAriaAttributes(array $config): array
```

**Parameters:**
- `$config` (array): Element configuration

**Returns:** Array of ARIA attributes

**Generated Attributes:**
- `role`: Element role (table, row, cell, columnheader)
- `aria-label`: Descriptive label
- `aria-sort`: Sort state (ascending, descending, none)
- `aria-busy`: Loading state
- `aria-live`: Live region for updates

---

### buildKeyboardAttributes()

Generates keyboard navigation attributes.

**Signature:**
```php
private function buildKeyboardAttributes(string $elementType, array $config = []): array
```

**Parameters:**
- `$elementType` (string): Type of element (header, cell, button)
- `$config` (array): Element configuration

**Returns:** Array of keyboard attributes

**Generated Attributes:**
- `tabindex`: Tab order
- `data-keyboard-shortcut`: Keyboard shortcuts
- Focus management attributes

---

### generateAccessibleCaption()

Generates accessible table caption.

**Signature:**
```php
private function generateTableCaption(
    string $name,
    array $data,
    array $attributes
): string
```

**Parameters:**
- `$name` (string): Table name
- `$data` (array): Table data
- `$attributes` (array): Table attributes

**Returns:** HTML `<caption>` element

**Example Output:**
```html
<caption class="sr-only">Users table with 150 records</caption>
```

---

### buildAriaLiveRegions()

Generates ARIA live regions for screen reader announcements.

**Signature:**
```php
private function buildAriaLiveRegions(string $tableID): string
```

**Parameters:**
- `$tableID` (string): Table identifier

**Returns:** HTML for live regions

**Example Output:**
```html
<div id="users-table-status" role="status" aria-live="polite" aria-atomic="true" class="sr-only"></div>
<div id="users-table-alert" role="alert" aria-live="assertive" aria-atomic="true" class="sr-only"></div>
```

**Usage:** These regions announce table state changes to screen readers:
- Loading states
- Filter applications
- Sort changes
- Pagination updates

---

## Column Configuration

### renderStandardColumnHeader()

Renders a standard column header cell.

**Signature:**
```php
private function renderStandardColumnHeader(string $column, array $config): string
```

**Parameters:**
- `$column` (string): Column name
- `$config` (array): Column configuration

**Returns:** HTML `<th>` element

**Configuration Options:**
- `sortable`: Enable sorting
- `width`: Column width
- `align`: Text alignment
- `color`: Background color
- `aria-label`: Custom ARIA label

**Example Output:**
```html
<th role="columnheader" 
    aria-sort="none" 
    class="sortable text-left" 
    style="width: 200px"
    tabindex="0">
    Name
</th>
```

---

### buildStandardColumnClass()

Builds CSS classes for column headers.

**Signature:**
```php
private function buildStandardColumnClass(string $column, array $config): string
```

**Parameters:**
- `$column` (string): Column name
- `$config` (array): Column configuration

**Returns:** Space-separated CSS class string

**Generated Classes:**
- `sortable`: For sortable columns
- `searchable`: For searchable columns
- `text-left`, `text-center`, `text-right`: Alignment
- `fixed-column`: For fixed columns
- Custom classes from configuration

---

### buildHeaderAriaAttributes()

Builds ARIA attributes for column headers.

**Signature:**
```php
private function buildHeaderAriaAttributes(string $column, array $config): string
```

**Parameters:**
- `$column` (string): Column name
- `$config` (array): Column configuration

**Returns:** HTML attribute string

**Generated Attributes:**
- `role="columnheader"`: Column header role
- `aria-sort`: Sort state (ascending, descending, none)
- `aria-label`: Descriptive label
- `tabindex`: Keyboard navigation

---

### mergeColumns()

Renders merged column headers.

**Signature:**
```php
private function mergeColumns(
    array $mergeColumn = [],
    array $columns = [],
    array $attributes = []
): string
```

**Parameters:**
- `$mergeColumn` (array): Merge configuration
- `$columns` (array): Column list
- `$attributes` (array): Table attributes

**Returns:** HTML for merged header rows

**Example Configuration:**
```php
[
    'label' => 'Contact Information',
    'columns' => ['email', 'phone', 'address'],
    'position' => 'top'
]
```

**Example Output:**
```html
<tr role="row">
    <th colspan="3" role="columnheader" class="merged-header">Contact Information</th>
</tr>
<tr role="row">
    <th role="columnheader">Email</th>
    <th role="columnheader">Phone</th>
    <th role="columnheader">Address</th>
</tr>
```

---

## ARIA Attributes

### Table-Level ARIA

**Automatically Applied:**
```html
<table role="table" 
       aria-label="[Table Name] table" 
       aria-describedby="[tableID]-caption"
       aria-rowcount="[total rows]">
```

### Header ARIA

**Sortable Columns:**
```html
<th role="columnheader" 
    aria-sort="ascending"
    aria-label="Name, sortable column, currently sorted ascending"
    tabindex="0">
```

**Non-Sortable Columns:**
```html
<th role="columnheader" 
    aria-label="Email address">
```

### Body ARIA

**Rows:**
```html
<tr role="row" aria-rowindex="[row number]">
```

**Cells:**
```html
<td role="cell" headers="[header-id]">
```

### Interactive Elements ARIA

**Action Buttons:**
```html
<button role="button" 
        aria-label="Edit user John Doe"
        tabindex="0">
```

**Pagination:**
```html
<nav role="navigation" aria-label="Table pagination">
    <button aria-label="Go to page 1" aria-current="page">1</button>
    <button aria-label="Go to page 2">2</button>
</nav>
```

---

## Security Features

### Input Validation

All inputs are validated before rendering:

**Table ID Validation:**
```php
private function validateTableID(string $tableID): string
```
- Removes special characters
- Prevents XSS in element IDs

**Attribute Validation:**
```php
private function validateAttributeKeys(array $attributes): array
```
- Validates attribute names
- Prevents malicious attributes

**Color Validation:**
```php
private function validateColor(string $color): ?string
```
- Validates color format
- Prevents CSS injection

---

### Output Escaping

All user-controllable data is escaped:

**Column Labels:**
```php
$escapedLabel = canvastack_escape_html($label);
```

**Attribute Values:**
```php
$escapedValue = canvastack_escape_attribute($value);
```

**JavaScript Strings:**
```php
$escapedString = canvastack_escape_js($string);
```

---

### SafeHtml Marking

The Builder uses SafeHtml marking system:

**Marking Safe HTML:**
```php
$safeHtml = canvastack_mark_safe_html($html);
```

**Checking Safe HTML:**
```php
if (canvastack_is_safe_html($html)) {
    // Already safe, don't double-escape
}
```

**Usage:** Prevents double-escaping of already-safe HTML while ensuring all user input is escaped.

---

## Column Width Management

### getColumnWidth()

Gets column width from configuration.

**Signature:**
```php
private function getColumnWidth(string $column, array $attributes): string
```

**Parameters:**
- `$column` (string): Column name
- `$attributes` (array): Table attributes

**Returns:** Width style string

**Example:**
```php
// Returns: "width: 200px"
// Or: "width: 30%"
```

---

### getColumnWidthFromConfig()

Extracts width from column configuration.

**Signature:**
```php
private function getColumnWidthFromConfig(string $column, array $widthColumn): string
```

**Parameters:**
- `$column` (string): Column name
- `$widthColumn` (array): Width configuration

**Returns:** Width value with unit

---

## Color Management

### backgroundColor()

Processes background color configuration.

**Signature:**
```php
private function backgroundColor(array $attributes = []): ?array
```

**Parameters:**
- `$attributes` (array): Table attributes with color configuration

**Returns:** Processed color configuration array

**Configuration Format:**
```php
[
    'background_color' => [
        'color' => '#f0f0f0',
        'text_color' => '#333',
        'columns' => ['status', 'priority'],
        'header' => true,
        'body' => true
    ]
]
```

---

### getColumnColorStyle()

Generates inline color styles for columns.

**Signature:**
```php
private function getColumnColorStyle(string $column, array $columnColor): string
```

**Parameters:**
- `$column` (string): Column name
- `$columnColor` (array): Color configuration

**Returns:** Inline style string

**Example Output:**
```css
background-color: #f0f0f0; color: #333;
```

---

## Filter Section

### buildFilterSection()

Generates filter UI section.

**Signature:**
```php
private function buildFilterSection(string $tableID): string
```

**Parameters:**
- `$tableID` (string): Table identifier

**Returns:** HTML for filter section

**Features:**
- Advanced filter modal
- Quick filter inputs
- Filter reset button
- ARIA attributes for accessibility

**Example Output:**
```html
<div class="table-filter-section" role="search" aria-label="Table filters">
    <button class="btn-filter" aria-label="Open advanced filters" aria-expanded="false">
        <i class="fa fa-filter"></i> Filters
    </button>
    <div class="quick-filters" role="group" aria-label="Quick filters">
        <!-- Quick filter inputs -->
    </div>
</div>
```

---

## Container Wrapping

### wrapTableInContainer()

Wraps table in responsive container.

**Signature:**
```php
private function wrapTableInContainer(
    string $tableTitle,
    string $tableHTML,
    string $datatableColumns,
    string $tableID
): string
```

**Parameters:**
- `$tableTitle` (string): Table title HTML
- `$tableHTML` (string): Table HTML
- `$datatableColumns` (string): DataTables configuration
- `$tableID` (string): Table identifier

**Returns:** Complete wrapped HTML

**Structure:**
```html
<div class="table-container">
    <div class="table-header">
        <h3>Table Title</h3>
        <div class="table-actions"><!-- Export, etc. --></div>
    </div>
    <div class="table-responsive">
        <table>...</table>
    </div>
    <div class="table-footer">
        <!-- Pagination, info -->
    </div>
</div>
```

---

## Performance Considerations

### Caching

The Builder uses caching for:
- Table configurations
- Column definitions
- Schema information

**Cache Check:**
```php
private function shouldUseCache(string $cacheType = 'config'): bool
```

---

### Efficient Rendering

**Optimizations:**
- Minimal DOM manipulation
- Efficient string concatenation
- Lazy loading of DataTables features
- Chunked rendering for large tables

---

## Best Practices

### Use Semantic HTML

The Builder generates semantic HTML:
```html
<table>
    <caption>Table description</caption>
    <thead>
        <tr><th>Header</th></tr>
    </thead>
    <tbody>
        <tr><td>Data</td></tr>
    </tbody>
</table>
```

### Leverage ARIA Attributes

ARIA attributes are automatically added - no configuration needed.

### Responsive Design

Tables are wrapped in responsive containers:
```html
<div class="table-responsive">
    <table>...</table>
</div>
```

### Keyboard Navigation

All interactive elements support keyboard navigation:
- Tab through headers
- Enter/Space to sort
- Arrow keys for navigation

---

## See Also

- [Objects API Reference](OBJECTS.md)
- [Datatables API Reference](DATATABLES.md)
- [Accessibility Guidelines](../guides/ACCESSIBILITY.md)
- [Security Best Practices](../features/SECURITY.md)
