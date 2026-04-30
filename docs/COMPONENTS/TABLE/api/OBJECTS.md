# Table Objects API Reference

## Overview

The `Objects` class is the main entry point for creating and managing tables in CanvaStack. It extends the `Builder` class and provides a fluent interface for configuring tables with DataTables integration, server-side processing, relationships, and advanced features.

**Namespace:** `Canvastack\Canvastack\Library\Components\Table\Objects`

**Extends:** `Builder`

---

## Table of Contents

- [Basic Usage](#basic-usage)
- [Core Methods](#core-methods)
- [Configuration Methods](#configuration-methods)
- [Column Management](#column-management)
- [Data Filtering](#data-filtering)
- [Relationships](#relationships)
- [Formatting](#formatting)
- [Actions](#actions)
- [Security Considerations](#security-considerations)

---

## Basic Usage

### Creating a Simple Table

```php
use Canvastack\Canvastack\Library\Components\Table\Objects;

$table = new Objects();
$table->lists('users', ['id', 'name', 'email'], true);
```

### Creating a Table with Custom Configuration

```php
$table = new Objects();
$table->setName('users')
      ->setFields(['id', 'name', 'email', 'created_at'])
      ->setServerSide(true)
      ->sortable(['name', 'created_at'])
      ->searchable(['name', 'email'])
      ->setActions(['view', 'edit', 'delete'])
      ->lists();
```

---

## Core Methods

### lists()

Renders a complete table with all configured options.

**Signature:**
```php
public function lists(
    ?string $table_name = null,
    array $fields = [],
    bool|string|array $actions = true,
    bool $server_side = true,
    bool $numbering = true,
    array $attributes = [],
    bool|string $server_side_custom_url = false
): void
```

**Parameters:**
- `$table_name` (string|null): Database table name
- `$fields` (array): Column names to display
- `$actions` (bool|string|array): Action buttons configuration
- `$server_side` (bool): Enable server-side processing
- `$numbering` (bool): Show row numbers
- `$attributes` (array): HTML attributes for table element
- `$server_side_custom_url` (bool|string): Custom AJAX URL for server-side processing

**Example:**
```php
$table->lists('users', ['id', 'name', 'email'], ['view', 'edit'], true, true);
```

**Security Note:** Table name is validated against whitelist. Column names are validated against schema.

---

### setName()

Sets the database table name.

**Signature:**
```php
public function setName(string $table_name): void
```

**Parameters:**
- `$table_name` (string): Database table name

**Example:**
```php
$table->setName('users');
```

**Security Note:** Table name is validated to prevent SQL injection. Only alphanumeric characters and underscores are allowed.

---

### setFields()

Sets the columns to display in the table.

**Signature:**
```php
public function setFields(array $fields): void
```

**Parameters:**
- `$fields` (array): Array of column names or column => label pairs

**Example:**
```php
// Simple column names
$table->setFields(['id', 'name', 'email']);

// With custom labels
$table->setFields([
    'id' => 'User ID',
    'name' => 'Full Name',
    'email' => 'Email Address'
]);
```

**Security Note:** Column names are validated against table schema to prevent SQL injection.

---

### setServerSide()

Enables or disables server-side processing for large datasets.

**Signature:**
```php
public function setServerSide(bool $server_side = true): void
```

**Parameters:**
- `$server_side` (bool): Enable server-side processing

**Example:**
```php
$table->setServerSide(true);
```

**Performance Note:** Server-side processing is recommended for tables with more than 1000 rows.

---

### model()

Sets an Eloquent model for the table.

**Signature:**
```php
public function model(mixed $model): void
```

**Parameters:**
- `$model` (mixed): Eloquent model instance or class name

**Example:**
```php
use App\Models\User;

$table->model(User::class);
$table->lists(null, ['id', 'name', 'email']);
```

**Performance Note:** When using models, relationships are automatically eager-loaded to prevent N+1 queries.

---

### query()

Sets a custom SQL query for the table data.

**Signature:**
```php
public function query(string $sql): void
```

**Parameters:**
- `$sql` (string): SQL query string

**Example:**
```php
$table->query("SELECT id, name, email FROM users WHERE status = 'active'");
$table->lists();
```

**Security Warning:** Use parameterized queries or query builder instead of raw SQL when possible. Never concatenate user input into SQL queries.

---

## Configuration Methods

### connection()

Sets the database connection to use.

**Signature:**
```php
public function connection(?string $db_connection): void
```

**Parameters:**
- `$db_connection` (string|null): Database connection name

**Example:**
```php
$table->connection('mysql_secondary');
```

---

### addAttributes()

Adds HTML attributes to the table element.

**Signature:**
```php
public function addAttributes(array $attributes = []): void
```

**Parameters:**
- `$attributes` (array): Key-value pairs of HTML attributes

**Example:**
```php
$table->addAttributes([
    'class' => 'table-striped table-hover',
    'data-custom' => 'value'
]);
```

**Security Note:** Attribute values are automatically escaped to prevent XSS attacks.

---

### setWidth()

Sets the table width.

**Signature:**
```php
public function setWidth(int $width, string $measurement = 'px'): void
```

**Parameters:**
- `$width` (int): Width value
- `$measurement` (string): Unit of measurement ('px', '%', 'em', etc.)

**Example:**
```php
$table->setWidth(100, '%');
```

---

### displayRowsLimitOnLoad()

Sets the number of rows to display per page.

**Signature:**
```php
public function displayRowsLimitOnLoad(int|string $limit = 10): void
```

**Parameters:**
- `$limit` (int|string): Number of rows per page

**Example:**
```php
$table->displayRowsLimitOnLoad(25);
```

---

## Column Management

### setColumnWidth()

Sets the width of a specific column.

**Signature:**
```php
public function setColumnWidth(string $field_name, int|string|false $width = false): self
```

**Parameters:**
- `$field_name` (string): Column name
- `$width` (int|string|false): Width value (pixels or percentage)

**Example:**
```php
$table->setColumnWidth('id', 50)
      ->setColumnWidth('name', 200)
      ->setColumnWidth('email', '30%');
```

**Returns:** `self` for method chaining

---

### setColumnWidths()

Sets widths for multiple columns at once.

**Signature:**
```php
public function setColumnWidths(array $widths): self
```

**Parameters:**
- `$widths` (array): Column name => width pairs

**Example:**
```php
$table->setColumnWidths([
    'id' => 50,
    'name' => 200,
    'email' => '30%'
]);
```

**Returns:** `self` for method chaining

---

### setHiddenColumns()

Hides specific columns from display.

**Signature:**
```php
public function setHiddenColumns(array $fields = []): self
```

**Parameters:**
- `$fields` (array): Column names to hide

**Example:**
```php
$table->setHiddenColumns(['password', 'remember_token']);
```

**Returns:** `self` for method chaining

---

### setVisibleColumns()

Shows only specific columns, hiding all others.

**Signature:**
```php
public function setVisibleColumns(array $fields, array $allFields): self
```

**Parameters:**
- `$fields` (array): Column names to show
- `$allFields` (array): All available column names

**Example:**
```php
$table->setVisibleColumns(['id', 'name', 'email'], ['id', 'name', 'email', 'password', 'created_at']);
```

**Returns:** `self` for method chaining

---

### toggleColumnVisibility()

Toggles visibility of a specific column.

**Signature:**
```php
public function toggleColumnVisibility(string $field, bool $visible): self
```

**Parameters:**
- `$field` (string): Column name
- `$visible` (bool): Visibility state

**Example:**
```php
$table->toggleColumnVisibility('email', false);
```

**Returns:** `self` for method chaining

---

### setColumnOrder()

Sets the display order of columns.

**Signature:**
```php
public function setColumnOrder(array $orderedFields): self
```

**Parameters:**
- `$orderedFields` (array): Column names in desired order

**Example:**
```php
$table->setColumnOrder(['name', 'email', 'id', 'created_at']);
```

**Returns:** `self` for method chaining

---

### mergeColumns()

Merges multiple columns under a single header.

**Signature:**
```php
public function mergeColumns(
    string $label,
    array $merged_columns = [],
    string $label_position = 'top'
): void
```

**Parameters:**
- `$label` (string): Header label for merged columns
- `$merged_columns` (array): Column names to merge
- `$label_position` (string): Position of label ('top' or 'bottom')

**Example:**
```php
$table->mergeColumns('Contact Information', ['email', 'phone', 'address']);
```

---

### groupColumns()

Groups columns under a common header with advanced options.

**Signature:**
```php
public function groupColumns(
    string $groupName,
    string $groupLabel,
    array $columns,
    array $options = []
): void
```

**Parameters:**
- `$groupName` (string): Unique group identifier
- `$groupLabel` (string): Display label for group
- `$columns` (array): Column names in group
- `$options` (array): Additional options (colspan, styling, etc.)

**Example:**
```php
$table->groupColumns('personal', 'Personal Info', ['name', 'age', 'gender'], [
    'style' => 'background-color: #f0f0f0'
]);
```

---

### fixedColumns()

Fixes columns to left or right side of table.

**Signature:**
```php
public function fixedColumns(?int $left_pos = null, ?int $right_pos = null): void
```

**Parameters:**
- `$left_pos` (int|null): Number of columns to fix on left
- `$right_pos` (int|null): Number of columns to fix on right

**Example:**
```php
$table->fixedColumns(2, 1); // Fix first 2 columns on left, last 1 on right
```

---

### setAlignColumns()

Sets text alignment for specific columns.

**Signature:**
```php
public function setAlignColumns(
    string $align,
    array $columns = [],
    bool $header = true,
    bool $body = true
): self
```

**Parameters:**
- `$align` (string): Alignment ('left', 'center', 'right')
- `$columns` (array): Column names to align
- `$header` (bool): Apply to header
- `$body` (bool): Apply to body

**Example:**
```php
$table->setAlignColumns('right', ['price', 'quantity', 'total']);
```

**Returns:** `self` for method chaining

---

### setBackgroundColor()

Sets background color for columns.

**Signature:**
```php
public function setBackgroundColor(
    string $color,
    ?string $text_color = null,
    array|null $columns = null,
    bool $header = true,
    bool $body = false
): void
```

**Parameters:**
- `$color` (string): Background color (hex, rgb, or color name)
- `$text_color` (string|null): Text color
- `$columns` (array|null): Column names (null = all columns)
- `$header` (bool): Apply to header
- `$body` (bool): Apply to body

**Example:**
```php
$table->setBackgroundColor('#f0f0f0', '#333', ['status'], true, true);
```

---

## Data Filtering

### where()

Adds WHERE conditions to filter data.

**Signature:**
```php
public function where(
    string|array $field_name,
    string|false $logic_operator = false,
    mixed $value = false
): void
```

**Parameters:**
- `$field_name` (string|array): Column name or array of conditions
- `$logic_operator` (string|false): Comparison operator ('=', '!=', '>', '<', 'LIKE', etc.)
- `$value` (mixed): Value to compare

**Example:**
```php
// Simple condition
$table->where('status', '=', 'active');

// Multiple conditions
$table->where('status', '=', 'active');
$table->where('role', '!=', 'admin');

// Array syntax
$table->where([
    ['status', '=', 'active'],
    ['role', '!=', 'admin']
]);
```

**Security Note:** All values are automatically parameterized to prevent SQL injection.

---

### filterConditions()

Applies multiple filter conditions at once.

**Signature:**
```php
public function filterConditions(array $filters = []): void
```

**Parameters:**
- `$filters` (array): Array of filter conditions

**Example:**
```php
$table->filterConditions([
    ['field' => 'status', 'operator' => '=', 'value' => 'active'],
    ['field' => 'created_at', 'operator' => '>=', 'value' => '2024-01-01']
]);
```

---

### orderby()

Sets the default sort order.

**Signature:**
```php
public function orderby(string $column, string $order = 'asc'): void
```

**Parameters:**
- `$column` (string): Column name to sort by
- `$order` (string): Sort direction ('asc' or 'desc')

**Example:**
```php
$table->orderby('created_at', 'desc');
```

**Security Note:** Column name is validated against schema to prevent SQL injection.

---

### sortable()

Makes columns sortable by clicking headers.

**Signature:**
```php
public function sortable(array|string|null $columns = null): void
```

**Parameters:**
- `$columns` (array|string|null): Column names (null = all columns)

**Example:**
```php
// Make specific columns sortable
$table->sortable(['name', 'email', 'created_at']);

// Make all columns sortable
$table->sortable();
```

---

### searchable()

Makes columns searchable via global search.

**Signature:**
```php
public function searchable(array|string|null $columns = null): void
```

**Parameters:**
- `$columns` (array|string|null): Column names (null = all columns)

**Example:**
```php
$table->searchable(['name', 'email', 'description']);
```

---

## Relationships

### relations()

Displays data from related tables.

**Signature:**
```php
public function relations(
    mixed $model,
    string $relation_function,
    string $field_display,
    array $filter_foreign_keys = [],
    ?string $label = null
): void
```

**Parameters:**
- `$model` (mixed): Eloquent model
- `$relation_function` (string): Relationship method name
- `$field_display` (string): Field to display from related model
- `$filter_foreign_keys` (array): Foreign key filters
- `$label` (string|null): Custom column label

**Example:**
```php
use App\Models\Post;

$table->model(Post::class);
$table->relations(Post::class, 'user', 'name', [], 'Author');
$table->lists(null, ['id', 'title', 'user.name']);
```

**Performance Note:** Relationships are automatically eager-loaded to prevent N+1 query problems.

---

### fieldReplacementValue()

Replaces field value with related model data.

**Signature:**
```php
public function fieldReplacementValue(
    mixed $model,
    string $relation_function,
    string $field_display,
    ?string $label = null,
    ?string $field_connect = null
): void
```

**Parameters:**
- `$model` (mixed): Eloquent model
- `$relation_function` (string): Relationship method name
- `$field_display` (string): Field to display
- `$label` (string|null): Custom label
- `$field_connect` (string|null): Connection field

**Example:**
```php
$table->fieldReplacementValue(Post::class, 'category', 'name', 'Category');
```

---

## Formatting

### format()

Applies formatting to column values.

**Signature:**
```php
public function format(
    string|array $fields,
    int $decimal_endpoint = 0,
    string $separator = '.',
    string $format = 'number',
    array $options = []
): void
```

**Parameters:**
- `$fields` (string|array): Column names to format
- `$decimal_endpoint` (int): Number of decimal places
- `$separator` (string): Decimal separator
- `$format` (string): Format type ('number', 'currency', 'percentage', 'date', etc.)
- `$options` (array): Additional format options

**Example:**
```php
$table->format('price', 2, '.', 'currency', ['symbol' => '$']);
$table->format('discount', 1, '.', 'percentage');
```

---

### formatCurrency()

Formats columns as currency.

**Signature:**
```php
public function formatCurrency(
    string|array $fields,
    int $decimals = 2,
    string $symbol = '$',
    string $position = 'before',
    string $thousands = ','
): void
```

**Parameters:**
- `$fields` (string|array): Column names
- `$decimals` (int): Decimal places
- `$symbol` (string): Currency symbol
- `$position` (string): Symbol position ('before' or 'after')
- `$thousands` (string): Thousands separator

**Example:**
```php
$table->formatCurrency('price', 2, '$', 'before', ',');
```

---

### formatPercentage()

Formats columns as percentages.

**Signature:**
```php
public function formatPercentage(
    string|array $fields,
    int $decimals = 1,
    string $symbol = '%',
    string $position = 'after'
): void
```

**Parameters:**
- `$fields` (string|array): Column names
- `$decimals` (int): Decimal places
- `$symbol` (string): Percentage symbol
- `$position` (string): Symbol position

**Example:**
```php
$table->formatPercentage('discount', 1);
```

---

### formatDate()

Formats columns as dates.

**Signature:**
```php
public function formatDate(
    string|array $fields,
    string $dateFormat = 'Y-m-d',
    ?string $timezone = null
): void
```

**Parameters:**
- `$fields` (string|array): Column names
- `$dateFormat` (string): PHP date format
- `$timezone` (string|null): Timezone

**Example:**
```php
$table->formatDate('created_at', 'F j, Y');
```

---

### formatBoolean()

Formats boolean columns with custom labels.

**Signature:**
```php
public function formatBoolean(
    string|array $fields,
    string $trueLabel = 'Yes',
    string $falseLabel = 'No'
): void
```

**Parameters:**
- `$fields` (string|array): Column names
- `$trueLabel` (string): Label for true values
- `$falseLabel` (string): Label for false values

**Example:**
```php
$table->formatBoolean('is_active', 'Active', 'Inactive');
```

---

### formula()

Adds calculated columns based on formulas.

**Signature:**
```php
public function formula(
    string $name,
    string $label = null,
    array $field_lists,
    string $logic,
    string $node_location = null,
    bool $node_after_node_location = true
): void
```

**Parameters:**
- `$name` (string): Formula column name
- `$label` (string|null): Display label
- `$field_lists` (array): Fields used in calculation
- `$logic` (string): Formula expression
- `$node_location` (string|null): Position relative to another column
- `$node_after_node_location` (bool): Insert after or before

**Example:**
```php
$table->formula('total', 'Total Price', ['price', 'quantity'], 'price * quantity');
$table->formula('discount_price', 'After Discount', ['price', 'discount'], 'price - (price * discount / 100)');
```

**Security Note:** Formula expressions are validated to prevent code injection.

---

## Actions

### setActions()

Configures action buttons for each row.

**Signature:**
```php
public function setActions(array $actions = [], bool|array $default_actions = true): void
```

**Parameters:**
- `$actions` (array): Custom action configurations
- `$default_actions` (bool|array): Include default actions (view, edit, delete)

**Example:**
```php
// Use default actions
$table->setActions();

// Custom actions only
$table->setActions([
    'approve' => [
        'label' => 'Approve',
        'icon' => 'fa-check',
        'url' => '/admin/users/approve/{id}',
        'class' => 'btn-success'
    ]
], false);

// Mix default and custom
$table->setActions([
    'approve' => [...]
], ['view', 'edit']);
```

**Security Note:** Action URLs are automatically escaped. Privilege checking is applied based on user permissions.

---

### removeButtons()

Removes specific action buttons.

**Signature:**
```php
public function removeButtons(string|array $remove): void
```

**Parameters:**
- `$remove` (string|array): Action names to remove

**Example:**
```php
$table->removeButtons(['delete']);
$table->removeButtons('edit');
```

---

## Security Considerations

### XSS Protection

All user-controllable data is automatically escaped before rendering:
- Column labels
- Cell values
- Action button labels
- Filter values
- Custom HTML attributes

**Example of safe usage:**
```php
// User input is automatically escaped
$table->setFields([
    'name' => $_GET['column_label'] // Automatically escaped
]);
```

### SQL Injection Prevention

All database queries use parameterized queries or query builder:
- Table names are validated against whitelist
- Column names are validated against schema
- WHERE conditions use parameter binding
- Sort columns are validated

**Example of safe usage:**
```php
// Automatically parameterized
$table->where('status', '=', $_GET['status']); // Safe

// Column name validated
$table->orderby($_GET['sort_column']); // Validated against schema
```

### Input Validation

All inputs are validated:
- Table names: alphanumeric and underscore only
- Column names: validated against table schema
- Operators: validated against whitelist
- Pagination parameters: validated as positive integers
- Sort directions: validated as 'asc' or 'desc'

---

## Performance Best Practices

### Use Server-Side Processing for Large Datasets

```php
// For tables with > 1000 rows
$table->setServerSide(true);
```

### Eager Load Relationships

```php
// Relationships are automatically eager-loaded
$table->relations(Post::class, 'user', 'name');
```

### Select Only Required Columns

```php
// Only select needed columns
$table->setFields(['id', 'name', 'email']); // Better than selecting all
```

### Use Caching

Schema and validation results are automatically cached. Configure cache TTL in config:

```php
// config/cache.php
'table_schema_ttl' => 3600, // 1 hour
```

---

## Accessibility Features

The table component automatically includes:
- ARIA role attributes (`role="table"`, `role="columnheader"`, etc.)
- ARIA labels for interactive elements
- ARIA sort indicators for sortable columns
- Keyboard navigation support
- Screen reader announcements
- Focus indicators

No additional configuration required - accessibility features are built-in.

---

## See Also

- [Builder API Reference](BUILDER.md)
- [Datatables API Reference](DATATABLES.md)
- [Search System](SEARCH.md)
- [Security Guidelines](../features/SECURITY.md)
- [Getting Started Guide](../guides/GETTING_STARTED.md)
