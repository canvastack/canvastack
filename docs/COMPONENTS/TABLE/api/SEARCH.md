# Table Search System API Reference

## Overview

The Search system provides advanced filtering capabilities for tables, including a modal-based filter UI, query building, and form generation. It consists of multiple components working together to provide a comprehensive search experience.

---

## Table of Contents

- [Architecture](#architecture)
- [Search Class](#search-class)
- [SearchConfig](#searchconfig)
- [FormGenerator](#formgenerator)
- [ModalRenderer](#modalrenderer)
- [QueryBuilder](#querybuilder)
- [ScriptGenerator](#scriptgenerator)
- [Usage Examples](#usage-examples)

---

## Architecture

### Component Overview

```
Search System
├── Search (Main coordinator)
├── SearchConfig (Configuration management)
├── FormGenerator (Filter form HTML generation)
├── ModalRenderer (Modal UI rendering)
├── QueryBuilder (Database query building)
└── ScriptGenerator (JavaScript generation)
```

### Data Flow

```
User Input → FormGenerator → ModalRenderer → User Interaction
                                                    ↓
                                            ScriptGenerator
                                                    ↓
                                            QueryBuilder
                                                    ↓
                                            Database Query
```

---

## Search Class

**Namespace:** `Canvastack\Canvastack\Library\Components\Table\Craft\Search`

Main coordinator for the search system.

### Constructor

**Signature:**
```php
public function __construct(
    string $info,
    ?string $model = null,
    array $filters = [],
    ?string $sql = null,
    ?string $connection = null,
    array $filterQuery = []
)
```

**Parameters:**
- `$info` (string): Table identifier
- `$model` (string|null): Eloquent model class
- `$filters` (array): Initial filter conditions
- `$sql` (string|null): Custom SQL query
- `$connection` (string|null): Database connection
- `$filterQuery` (array): Additional query filters

**Example:**
```php
use Canvastack\Canvastack\Library\Components\Table\Craft\Search;

$search = new Search(
    'users-table',
    User::class,
    ['status' => 'active']
);
```

---

### render()

Renders the search interface.

**Signature:**
```php
public function render(string $info, string $table, array $fields): ?array
```

**Parameters:**
- `$info` (string): Table identifier
- `$table` (string): Table name
- `$fields` (array): Searchable fields

**Returns:** Array with search configuration

**Example:**
```php
$searchConfig = $search->render('users-table', 'users', [
    'name' => 'Name',
    'email' => 'Email',
    'status' => 'Status'
]);
```

---

### getColumnInfo()

Retrieves column information for search fields.

**Signature:**
```php
private function getColumnInfo(string $table, array $fields): array
```

**Parameters:**
- `$table` (string): Table name
- `$fields` (array): Field names

**Returns:** Array of column metadata

**Column Information:**
```php
[
    'name' => [
        'type' => 'string',
        'nullable' => false,
        'default' => null,
        'length' => 255
    ],
    'age' => [
        'type' => 'integer',
        'nullable' => true,
        'default' => null,
        'min' => 0,
        'max' => 150
    ]
]
```

---

### getColumnType()

Gets the data type of a column.

**Signature:**
```php
private function getColumnType(string $table, string $column): string
```

**Parameters:**
- `$table` (string): Table name
- `$column` (string): Column name

**Returns:** Column type string

**Supported Types:**
- `string`, `text`
- `integer`, `bigint`, `smallint`
- `decimal`, `float`, `double`
- `date`, `datetime`, `timestamp`
- `boolean`
- `enum`

---

## SearchConfig

**Namespace:** `Canvastack\Canvastack\Library\Components\Table\Craft\Search\Config\SearchConfig`

Manages search configuration and settings.

### Configuration Structure

```php
[
    'table' => 'users',
    'fields' => [
        'name' => [
            'label' => 'Name',
            'type' => 'text',
            'operators' => ['=', 'LIKE', '!='],
            'searchable' => true
        ],
        'age' => [
            'label' => 'Age',
            'type' => 'number',
            'operators' => ['=', '>', '<', '>=', '<=', 'BETWEEN'],
            'searchable' => true
        ],
        'status' => [
            'label' => 'Status',
            'type' => 'select',
            'operators' => ['=', '!=', 'IN'],
            'options' => ['active', 'inactive', 'pending'],
            'searchable' => true
        ]
    ],
    'defaultOperators' => [
        'string' => ['=', 'LIKE', '!='],
        'number' => ['=', '>', '<', '>=', '<=', 'BETWEEN'],
        'date' => ['=', '>', '<', '>=', '<=', 'BETWEEN'],
        'boolean' => ['=', '!=']
    ]
]
```

---

## FormGenerator

**Namespace:** `Canvastack\Canvastack\Library\Components\Table\Craft\Search\FormGenerator`

Generates HTML forms for search filters.

### generateFilterForm()

Generates complete filter form HTML.

**Signature:**
```php
public function generateFilterForm(array $config): string
```

**Parameters:**
- `$config` (array): Search configuration

**Returns:** HTML form string

**Generated Form Structure:**
```html
<form class="table-search-form" role="search" aria-label="Table filters">
    <div class="filter-row">
        <div class="filter-field">
            <label for="filter-name">Name</label>
            <select name="operator[name]" aria-label="Name filter operator">
                <option value="=">Equals</option>
                <option value="LIKE">Contains</option>
            </select>
            <input type="text" 
                   name="value[name]" 
                   id="filter-name"
                   aria-label="Name filter value">
        </div>
    </div>
    <div class="filter-actions">
        <button type="submit" class="btn-apply">Apply Filters</button>
        <button type="reset" class="btn-reset">Reset</button>
    </div>
</form>
```

---

### generateFieldInput()

Generates input field based on column type.

**Signature:**
```php
private function generateFieldInput(string $fieldName, array $fieldConfig): string
```

**Parameters:**
- `$fieldName` (string): Field name
- `$fieldConfig` (array): Field configuration

**Returns:** HTML input element

**Input Types by Column Type:**

**Text Fields:**
```html
<input type="text" 
       name="value[name]" 
       class="form-control"
       aria-label="Name filter value">
```

**Number Fields:**
```html
<input type="number" 
       name="value[age]" 
       class="form-control"
       min="0"
       aria-label="Age filter value">
```

**Date Fields:**
```html
<input type="date" 
       name="value[created_at]" 
       class="form-control"
       aria-label="Created date filter value">
```

**Select Fields:**
```html
<select name="value[status]" 
        class="form-control"
        aria-label="Status filter value">
    <option value="">-- Select --</option>
    <option value="active">Active</option>
    <option value="inactive">Inactive</option>
</select>
```

**Boolean Fields:**
```html
<select name="value[is_active]" 
        class="form-control"
        aria-label="Active status filter">
    <option value="">-- Select --</option>
    <option value="1">Yes</option>
    <option value="0">No</option>
</select>
```

---

### generateOperatorSelect()

Generates operator dropdown for field.

**Signature:**
```php
private function generateOperatorSelect(string $fieldName, array $operators): string
```

**Parameters:**
- `$fieldName` (string): Field name
- `$operators` (array): Available operators

**Returns:** HTML select element

**Example Output:**
```html
<select name="operator[name]" 
        class="operator-select"
        aria-label="Name filter operator">
    <option value="=">Equals</option>
    <option value="!=">Not Equals</option>
    <option value="LIKE">Contains</option>
    <option value="NOT LIKE">Does Not Contain</option>
</select>
```

---

## ModalRenderer

**Namespace:** `Canvastack\Canvastack\Library\Components\Table\Craft\Search\ModalRenderer`

Renders modal UI for advanced filters.

### renderModal()

Renders complete filter modal.

**Signature:**
```php
public function renderModal(string $tableId, string $formHtml): string
```

**Parameters:**
- `$tableId` (string): Table identifier
- `$formHtml` (string): Generated form HTML

**Returns:** Complete modal HTML

**Modal Structure:**
```html
<div class="modal fade" 
     id="filter-modal-users-table" 
     tabindex="-1" 
     role="dialog"
     aria-labelledby="filter-modal-title"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filter-modal-title">
                    Advanced Filters
                </h5>
                <button type="button" 
                        class="close" 
                        data-dismiss="modal"
                        aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Form HTML -->
            </div>
            <div class="modal-footer">
                <button type="button" 
                        class="btn btn-secondary" 
                        data-dismiss="modal">
                    Cancel
                </button>
                <button type="button" 
                        class="btn btn-primary"
                        id="apply-filters">
                    Apply Filters
                </button>
            </div>
        </div>
    </div>
</div>
```

---

### Accessibility Features

**Keyboard Navigation:**
- Tab through filter fields
- Enter to submit
- Escape to close modal

**ARIA Attributes:**
- `role="dialog"` on modal
- `aria-labelledby` for modal title
- `aria-label` on all inputs
- `aria-hidden` for modal state

**Focus Management:**
- Focus trapped in modal when open
- Focus returns to trigger button on close
- First input focused on modal open

---

## QueryBuilder

**Namespace:** `Canvastack\Canvastack\Library\Components\Table\Craft\Search\QueryBuilder`

Builds database queries from filter conditions.

### buildQuery()

Builds query from filter conditions.

**Signature:**
```php
public function buildQuery(mixed $baseQuery, array $filters): mixed
```

**Parameters:**
- `$baseQuery` (mixed): Base query builder
- `$filters` (array): Filter conditions

**Returns:** Query builder with filters applied

**Example:**
```php
$query = DB::table('users');
$filters = [
    ['field' => 'name', 'operator' => 'LIKE', 'value' => 'John'],
    ['field' => 'age', 'operator' => '>', 'value' => 25]
];

$filteredQuery = $queryBuilder->buildQuery($query, $filters);
```

---

### applyFilter()

Applies single filter condition.

**Signature:**
```php
private function applyFilter(mixed $query, array $filter): mixed
```

**Parameters:**
- `$query` (mixed): Query builder
- `$filter` (array): Single filter condition

**Returns:** Query builder with filter applied

**Filter Structure:**
```php
[
    'field' => 'name',
    'operator' => 'LIKE',
    'value' => 'John',
    'logic' => 'AND'  // Optional: AND/OR
]
```

---

### Operator Handling

**Equality Operators:**
```php
// = operator
$query->where('status', '=', 'active');

// != operator
$query->where('status', '!=', 'inactive');
```

**Comparison Operators:**
```php
// > operator
$query->where('age', '>', 25);

// >= operator
$query->where('age', '>=', 18);
```

**Pattern Matching:**
```php
// LIKE operator
$query->where('name', 'LIKE', '%John%');

// NOT LIKE operator
$query->where('name', 'NOT LIKE', '%spam%');
```

**Range Operators:**
```php
// BETWEEN operator
$query->whereBetween('age', [18, 65]);

// NOT BETWEEN operator
$query->whereNotBetween('age', [0, 17]);
```

**NULL Checks:**
```php
// IS NULL operator
$query->whereNull('deleted_at');

// IS NOT NULL operator
$query->whereNotNull('email_verified_at');
```

**IN Operators:**
```php
// IN operator
$query->whereIn('status', ['active', 'pending']);

// NOT IN operator
$query->whereNotIn('status', ['deleted', 'banned']);
```

---

### Security Features

**SQL Injection Prevention:**
- All values are parameterized
- Operators validated against whitelist
- Column names validated against schema
- Table names validated

**Example:**
```php
// Safe - automatically parameterized
$query->where('name', '=', $_GET['name']);

// Column name validated
$query->where($_GET['column'], '=', 'value'); // Validated against schema
```

---

## ScriptGenerator

**Namespace:** `Canvastack\Canvastack\Library\Components\Table\Craft\Search\ScriptGenerator`

Generates JavaScript for search functionality.

### generateSearchScript()

Generates complete search JavaScript.

**Signature:**
```php
public function generateSearchScript(string $tableId, array $config): string
```

**Parameters:**
- `$tableId` (string): Table identifier
- `$config` (array): Search configuration

**Returns:** JavaScript code string

**Generated Script Features:**
- Modal open/close handling
- Form submission
- AJAX request handling
- Table refresh
- Error handling
- Loading states

---

### Example Generated Script

```javascript
(function() {
    const tableId = 'users-table';
    const modal = document.getElementById('filter-modal-' + tableId);
    const form = modal.querySelector('.table-search-form');
    const table = $('#' + tableId).DataTable();
    
    // Open modal
    document.querySelector('[data-filter-table="' + tableId + '"]')
        .addEventListener('click', function() {
            $(modal).modal('show');
        });
    
    // Apply filters
    document.getElementById('apply-filters')
        .addEventListener('click', function() {
            const formData = new FormData(form);
            const filters = {};
            
            // Build filter object
            for (let [key, value] of formData.entries()) {
                if (value) {
                    const match = key.match(/(\w+)\[(\w+)\]/);
                    if (match) {
                        const type = match[1]; // 'operator' or 'value'
                        const field = match[2];
                        
                        if (!filters[field]) {
                            filters[field] = {};
                        }
                        filters[field][type] = value;
                    }
                }
            }
            
            // Apply to DataTable
            table.ajax.url(buildFilterUrl(filters)).load();
            $(modal).modal('hide');
        });
    
    // Reset filters
    form.querySelector('.btn-reset')
        .addEventListener('click', function() {
            form.reset();
            table.ajax.url(getBaseUrl()).load();
            $(modal).modal('hide');
        });
    
    function buildFilterUrl(filters) {
        const params = new URLSearchParams();
        params.append('filters', JSON.stringify(filters));
        return getBaseUrl() + '?' + params.toString();
    }
    
    function getBaseUrl() {
        return '/api/datatables/' + tableId;
    }
})();
```

---

## Usage Examples

### Basic Search Setup

```php
use Canvastack\Canvastack\Library\Components\Table\Objects;

$table = new Objects();
$table->setName('users');
$table->setFields(['id', 'name', 'email', 'status']);

// Enable search on specific columns
$table->searchable(['name', 'email']);

// Enable advanced filters
$table->filterGroups('status', 'select', ['active', 'inactive', 'pending']);

$table->lists();
```

---

### Advanced Filter Configuration

```php
$table = new Objects();
$table->setName('orders');

// Configure searchable columns
$table->searchable(['order_number', 'customer_name']);

// Add filter groups
$table->filterGroups('status', 'select', [
    'pending' => 'Pending',
    'processing' => 'Processing',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
]);

$table->filterGroups('total', 'number', false);
$table->filterGroups('created_at', 'date', false);

$table->lists();
```

---

### Custom Filter Logic

```php
$table = new Objects();
$table->setName('products');

// Add custom where conditions
$table->where('category', '=', 'electronics');
$table->where('price', '>', 100);

// Add filter conditions
$table->filterConditions([
    ['field' => 'in_stock', 'operator' => '=', 'value' => true],
    ['field' => 'discount', 'operator' => '>', 'value' => 0]
]);

$table->lists();
```

---

### Relationship Filtering

```php
use App\Models\Order;

$table = new Objects();
$table->model(Order::class);

// Filter by related model
$table->relations(Order::class, 'customer', 'name', [], 'Customer');
$table->where('customer.status', '=', 'active');

$table->lists(null, ['id', 'order_number', 'customer.name', 'total']);
```

---

## Security Best Practices

### Input Validation

```php
// All inputs are automatically validated
// Column names checked against schema
// Operators validated against whitelist
// Values parameterized in queries
```

### XSS Prevention

```php
// All output is automatically escaped
// Form labels escaped
// Filter values escaped
// Error messages sanitized
```

### SQL Injection Prevention

```php
// Parameterized queries used throughout
// No string concatenation in SQL
// Column names validated
// Table names validated
```

---

## Performance Considerations

### Caching

```php
// Column information is cached
// Schema data is cached
// Filter configurations are cached
```

### Query Optimization

```php
// Filters applied at database level
// Indexes used where available
// Efficient query building
```

---

## Accessibility Guidelines

### Form Accessibility

- All inputs have labels
- ARIA labels on all controls
- Keyboard navigation support
- Focus management
- Error announcements

### Modal Accessibility

- Focus trapped in modal
- Escape key closes modal
- Focus returns to trigger
- ARIA attributes for state

---

## See Also

- [Objects API Reference](OBJECTS.md)
- [Datatables API Reference](DATATABLES.md)
- [Security Guidelines](../features/SECURITY.md)
- [Accessibility Guidelines](../guides/ACCESSIBILITY.md)
