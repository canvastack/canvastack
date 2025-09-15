# Objects Class API Reference

The `Objects` class is the main entry point and orchestrator for CanvaStack Table system. It provides a fluent API for configuring and rendering DataTables with advanced features.

## Class Overview

```php
namespace Canvastack\Canvastack\Library\Components\Table;

class Objects extends Builder
{
    // Main orchestrator for table functionality
}
```

**Extends**: [`Builder`](builder.md)  
**Uses Traits**: [See Traits Overview](../traits/overview.md)

## Core Methods

### lists()

The primary method for generating tables. This is the main method you'll use in most cases.

```php
public function lists(
    string $table_name,
    array $fields,
    bool|array $actions = false,
    bool $server_side = false,
    bool $numbering = true,
    array $attributes = [],
    string|null $server_side_custom_url = null
): string
```

#### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$table_name` | `string` | - | **Required.** The database table name or model name |
| `$fields` | `array` | - | **Required.** Array of column names to display |
| `$actions` | `bool\|array` | `false` | Action buttons configuration |
| `$server_side` | `bool` | `false` | Enable server-side processing |
| `$numbering` | `bool` | `true` | Show row numbering |
| `$attributes` | `array` | `[]` | HTML attributes for table element |
| `$server_side_custom_url` | `string\|null` | `null` | Custom AJAX URL for server-side |

#### Field Format

Fields can be specified in several formats:

```php
// Simple field names
['name', 'email', 'created_at']

// With custom labels using colon separator
['name:Full Name', 'email:Email Address', 'created_at:Join Date']

// Mixed format
['id', 'name:Full Name', 'email', 'status:Account Status']
```

#### Actions Configuration

```php
// Boolean - use default actions (view, edit, delete)
$actions = true;

// Array - custom actions
$actions = [
    'view' => true,
    'edit' => true, 
    'delete' => false,
    'custom' => [
        'label' => 'Archive',
        'url' => '/users/{id}/archive',
        'class' => 'btn btn-warning',
        'icon' => 'fas fa-archive'
    ]
];
```

#### Examples

**Basic Table:**
```php
$this->table->lists('users', ['name', 'email', 'created_at']);
```

**With Custom Labels:**
```php
$this->table->lists('users', [
    'name:Full Name',
    'email:Email Address', 
    'created_at:Join Date'
]);
```

**With Actions:**
```php
$this->table->lists('users', ['name', 'email'], true);
```

**Server-Side Processing:**
```php
$this->table->method('POST')
            ->lists('users', ['name', 'email'], true, true);
```

**Complete Configuration:**
```php
$this->table->searchable()
            ->sortable()
            ->clickable()
            ->lists('users', [
                'name:Full Name',
                'email:Email Address',
                'group_name:Group',
                'created_at:Join Date'
            ], true, true, true, [
                'class' => 'table-striped table-hover',
                'id' => 'users-table'
            ]);
```

## Configuration Methods

### method()

Set the HTTP method for DataTables requests.

```php
public function method(string $method): self
```

#### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$method` | `string` | HTTP method: `'GET'` or `'POST'` |

#### Examples

```php
// Use GET method (client-side processing)
$this->table->method('GET');

// Use POST method (server-side processing)
$this->table->method('POST');
```

**When to use POST:**
- Large datasets (1000+ records)
- Complex filtering requirements
- Better security for sensitive data
- Server-side processing needed

### searchable()

Enable global search functionality.

```php
public function searchable(bool $enabled = true): self
```

#### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$enabled` | `bool` | `true` | Enable/disable search |

#### Examples

```php
// Enable search (default)
$this->table->searchable();

// Explicitly enable
$this->table->searchable(true);

// Disable search
$this->table->searchable(false);
```

### clickable()

Make table rows clickable for navigation.

```php
public function clickable(bool $enabled = true): self
```

#### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$enabled` | `bool` | `true` | Enable/disable clickable rows |

#### Examples

```php
// Enable clickable rows
$this->table->clickable();

// Disable clickable rows
$this->table->clickable(false);
```

**Note:** Clickable rows typically navigate to the edit page of the record.

### sortable()

Enable column sorting functionality.

```php
public function sortable(bool $enabled = true): self
```

#### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$enabled` | `bool` | `true` | Enable/disable sorting |

#### Examples

```php
// Enable sorting
$this->table->sortable();

// Disable sorting
$this->table->sortable(false);
```

### orderby()

Set default ordering for the table.

```php
public function orderby(string $column, string $direction = 'ASC'): self
```

#### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$column` | `string` | - | Column name to sort by |
| `$direction` | `string` | `'ASC'` | Sort direction: `'ASC'` or `'DESC'` |

#### Examples

```php
// Sort by ID descending (newest first)
$this->table->orderby('id', 'DESC');

// Sort by name ascending
$this->table->orderby('name', 'ASC');

// Default direction is ASC
$this->table->orderby('name');
```

## Column Configuration Methods

### setFieldAsImage()

Configure fields to display as images.

```php
public function setFieldAsImage(array $fields): self
```

#### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fields` | `array` | Array of field names to display as images |

#### Examples

```php
// Single image field
$this->table->setFieldAsImage(['avatar']);

// Multiple image fields
$this->table->setFieldAsImage(['avatar', 'cover_photo', 'thumbnail']);
```

**Image Display Features:**
- Automatic thumbnail generation
- Lightbox integration
- Fallback for missing images
- Responsive image sizing

### setHiddenColumns()

Hide specific columns from display.

```php
public function setHiddenColumns(array $columns): self
```

#### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$columns` | `array` | Array of column names to hide |

#### Examples

```php
// Hide sensitive fields
$this->table->setHiddenColumns(['password', 'remember_token']);

// Hide internal fields
$this->table->setHiddenColumns(['created_by', 'updated_by', 'deleted_at']);
```

### fixedColumns()

Set columns to remain fixed during horizontal scrolling.

```php
public function fixedColumns(int $left = 0, int $right = 0): self
```

#### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$left` | `int` | `0` | Number of columns to fix on the left |
| `$right` | `int` | `0` | Number of columns to fix on the right |

#### Examples

```php
// Fix first column (usually name/ID)
$this->table->fixedColumns(1, 0);

// Fix first and last columns (name and actions)
$this->table->fixedColumns(1, 1);

// Fix first two columns
$this->table->fixedColumns(2, 0);
```

**Use Cases:**
- Wide tables with many columns
- Keep important columns visible
- Maintain context while scrolling

### mergeColumns()

Merge multiple columns under a single header.

```php
public function mergeColumns(string $label, array $columns, string $position = 'top'): self
```

#### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$label` | `string` | - | Header label for merged columns |
| `$columns` | `array` | - | Array of column names to merge |
| `$position` | `string` | `'top'` | Position: `'top'` or `'bottom'` |

#### Examples

```php
// Merge name columns
$this->table->mergeColumns('Full Name', ['first_name', 'last_name']);

// Merge address columns
$this->table->mergeColumns('Address', ['street', 'city', 'state', 'zip']);

// Merge with bottom position
$this->table->mergeColumns('Contact Info', ['email', 'phone'], 'bottom');
```

## Relationship Methods

### relations()

Configure relationships to display related data.

```php
public function relations(
    $model, 
    string $relation_name, 
    string $display_field, 
    array $additional_relations = []
): self
```

#### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$model` | `Model\|string` | Eloquent model or model class name |
| `$relation_name` | `string` | Name of the relationship method |
| `$display_field` | `string` | Field to display from related model |
| `$additional_relations` | `array` | Additional relationship configurations |

#### Examples

**Basic Relationship:**
```php
// Display group name instead of group_id
$this->table->relations($this->model, 'group', 'name');
```

**Multiple Relationships:**
```php
$this->table->relations($this->model, 'group', 'name')
            ->relations($this->model, 'department', 'department_name')
            ->relations($this->model, 'role', 'role_title');
```

**Complex Relationship:**
```php
$this->table->relations($this->model, 'group', 'group_info', [
    'join_type' => 'left',
    'foreign_key' => 'group_id',
    'owner_key' => 'id'
]);
```

**In your Model:**
```php
class User extends Model
{
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
    
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
```

**Then in your table:**
```php
$this->table->relations($this->model, 'group', 'name')
            ->lists('users', [
                'name:Full Name',
                'email',
                'group.name:Group',  // Access related field
                'created_at'
            ]);
```

## Filtering Methods

### filterGroups()

Add filter controls for specific columns.

```php
public function filterGroups(
    string $field, 
    string $type = 'selectbox', 
    bool $relate = false
): self
```

#### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$field` | `string` | - | Field name to filter |
| `$type` | `string` | `'selectbox'` | Filter type |
| `$relate` | `bool` | `false` | Enable field relationships |

#### Filter Types

| Type | Description | Use Case |
|------|-------------|----------|
| `selectbox` | Dropdown selection | Categories, status, groups |
| `text` | Text input | Names, descriptions |
| `date` | Date picker | Created dates, deadlines |
| `daterange` | Date range picker | Date ranges |
| `checkbox` | Multiple checkboxes | Multiple selections |
| `radiobox` | Radio buttons | Single selection |

#### Examples

**Basic Filters:**
```php
// Dropdown filter for status
$this->table->filterGroups('status', 'selectbox', true);

// Text filter for name
$this->table->filterGroups('name', 'text', true);

// Date filter for created date
$this->table->filterGroups('created_at', 'date', true);
```

**Multiple Filters:**
```php
$this->table->filterGroups('category', 'selectbox', true)
            ->filterGroups('status', 'selectbox', true)
            ->filterGroups('created_at', 'daterange', true)
            ->filterGroups('name', 'text', true);
```

**Related Field Filters:**
```php
// Filter by related group name
$this->table->relations($this->model, 'group', 'group_name')
            ->filterGroups('group_name', 'selectbox', true);
```

**Filter Dependencies:**
When `$relate = true`, filters can depend on each other:
```php
$this->table->filterGroups('country', 'selectbox', true)
            ->filterGroups('state', 'selectbox', true)    // Depends on country
            ->filterGroups('city', 'selectbox', true);    // Depends on state
```

## Action Methods

### removeButtons()

Remove specific action buttons.

```php
public function removeButtons(array $buttons): self
```

#### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$buttons` | `array` | Array of button names to remove |

#### Examples

```php
// Remove delete button
$this->table->removeButtons(['delete']);

// Remove multiple buttons
$this->table->removeButtons(['view', 'delete']);

// Keep only edit button
$this->table->removeButtons(['view', 'delete']);
```

**Default Buttons:**
- `view` - View record details
- `edit` - Edit record
- `delete` - Delete record

### setActions()

Configure custom action buttons.

```php
public function setActions(array $actions): self
```

#### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$actions` | `array` | Array of action configurations |

#### Examples

**Custom Actions:**
```php
$this->table->setActions([
    'archive' => [
        'label' => 'Archive',
        'url' => '/users/{id}/archive',
        'class' => 'btn btn-warning btn-sm',
        'icon' => 'fas fa-archive',
        'confirm' => 'Are you sure you want to archive this user?'
    ],
    'activate' => [
        'label' => 'Activate',
        'url' => '/users/{id}/activate',
        'class' => 'btn btn-success btn-sm',
        'icon' => 'fas fa-check',
        'method' => 'POST'
    ]
]);
```

**Action Configuration Options:**

| Option | Type | Description |
|--------|------|-------------|
| `label` | `string` | Button text |
| `url` | `string` | Action URL (use `{id}` placeholder) |
| `class` | `string` | CSS classes |
| `icon` | `string` | Icon class |
| `confirm` | `string` | Confirmation message |
| `method` | `string` | HTTP method (`GET`, `POST`, `PUT`, `DELETE`) |
| `target` | `string` | Link target (`_blank`, `_self`) |

## Advanced Configuration

### setDatatableType()

Set the DataTable processing type.

```php
public function setDatatableType(string $type): self
```

#### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | `string` | Type: `'client'` or `'server'` |

#### Examples

```php
// Client-side processing
$this->table->setDatatableType('client');

// Server-side processing  
$this->table->setDatatableType('server');
```

### connection()

Set database connection for the table.

```php
public function connection(string $connection): self
```

#### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$connection` | `string` | Database connection name |

#### Examples

```php
// Use specific database connection
$this->table->connection('mysql_reports');

// Use secondary database
$this->table->connection('analytics');
```

### model()

Set the Eloquent model for the table.

```php
public function model($model): self
```

#### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$model` | `Model\|string` | Eloquent model instance or class name |

#### Examples

```php
// Using model instance
$this->table->model(new User());

// Using model class
$this->table->model(User::class);

// Using string
$this->table->model('App\Models\User');
```

### query()

Set custom SQL query for the table.

```php
public function query(string $sql): self
```

#### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$sql` | `string` | Raw SQL query |

#### Examples

```php
// Custom query
$this->table->query("
    SELECT u.*, g.name as group_name 
    FROM users u 
    LEFT JOIN groups g ON u.group_id = g.id 
    WHERE u.active = 1
");

// Complex reporting query
$this->table->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as total_users,
        SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) as active_users
    FROM users 
    GROUP BY DATE(created_at)
    ORDER BY date DESC
");
```

## Utility Methods

### debug()

Enable debug mode for troubleshooting.

```php
public function debug(bool $enabled = true): self
```

#### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$enabled` | `bool` | `true` | Enable/disable debug mode |

#### Examples

```php
// Enable debug mode
$this->table->debug(true);

// Disable debug mode
$this->table->debug(false);
```

**Debug Information Includes:**
- Generated SQL queries
- AJAX request/response data
- JavaScript configuration
- Performance metrics

### render()

Render the table HTML (usually called automatically).

```php
public function render(): string
```

#### Examples

```php
// Manual rendering (rarely needed)
$tableHtml = $this->table->render();
```

## Method Chaining

All configuration methods return `$this`, enabling fluent method chaining:

```php
$this->table->method('POST')
            ->searchable()
            ->sortable()
            ->clickable()
            ->relations($this->model, 'group', 'group_name')
            ->filterGroups('group_name', 'selectbox', true)
            ->filterGroups('status', 'selectbox', true)
            ->orderby('created_at', 'DESC')
            ->setFieldAsImage(['avatar'])
            ->setHiddenColumns(['password'])
            ->fixedColumns(1, 1)
            ->lists('users', [
                'avatar:Profile',
                'name:Full Name',
                'email:Email Address',
                'group_name:Group',
                'status:Status',
                'created_at:Join Date'
            ], true);
```

## Error Handling

The Objects class includes comprehensive error handling:

```php
try {
    $this->table->lists('users', ['name', 'email']);
} catch (\Canvastack\Canvastack\Library\Components\Table\Exceptions\SecurityException $e) {
    // Handle security violations
    Log::error('Table security violation: ' . $e->getMessage());
} catch (\Exception $e) {
    // Handle general errors
    Log::error('Table error: ' . $e->getMessage());
}
```

## Performance Considerations

### Client-Side vs Server-Side

**Use Client-Side (GET) when:**
- Dataset < 1000 records
- Simple filtering requirements
- Fast database queries
- Minimal server load

**Use Server-Side (POST) when:**
- Dataset > 1000 records
- Complex filtering/searching
- Large result sets
- Need better performance

### Optimization Tips

```php
// Limit columns to improve performance
$this->table->lists('users', ['name', 'email']); // Not all columns

// Use indexes on filtered/sorted columns
$this->table->orderby('indexed_column', 'DESC');

// Cache relationship data
$this->table->cacheRelations(true);

// Use specific database connection for reports
$this->table->connection('reports_db');
```

## Security Features

The Objects class includes built-in security:

- **SQL Injection Prevention**: All queries use parameter binding
- **XSS Protection**: Output is automatically escaped
- **CSRF Protection**: AJAX requests include CSRF tokens
- **Input Validation**: All inputs are validated and sanitized
- **Access Control**: Integration with Laravel's authorization

## Related Documentation

- [Builder Class](builder.md) - HTML and configuration builder
- [Datatables Class](datatables.md) - Server-side processing engine
- [Search & Filtering](search.md) - Advanced filtering system
- [Security Features](../advanced/security.md) - Security implementation details
- [Performance Optimization](../advanced/performance.md) - Performance tuning guide

---

## Examples

- [Basic Usage](../examples/basic.md) - Simple table examples
- [Advanced Filtering](../examples/filtering.md) - Complex filtering scenarios
- [Custom Actions](../examples/actions.md) - Custom action button examples
- [Real-world Examples](../examples/real-world.md) - Production use cases