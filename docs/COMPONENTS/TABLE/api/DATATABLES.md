# Datatables Server-Side Processing API Reference

## Overview

The `Datatables` class handles server-side processing for DataTables, including pagination, sorting, filtering, searching, and data transformation. It optimizes database queries and ensures secure data handling.

**Namespace:** `Canvastack\Canvastack\Library\Components\Table\Craft\Datatables`

---

## Table of Contents

- [Overview](#overview)
- [Core Processing Methods](#core-processing-methods)
- [Query Optimization](#query-optimization)
- [Security Features](#security-features)
- [Data Transformation](#data-transformation)
- [Performance Monitoring](#performance-monitoring)
- [Error Handling](#error-handling)

---

## Core Processing Methods

### process()

Main entry point for server-side DataTables processing.

**Signature:**
```php
public function process(
    array $method,
    object $data,
    array $filters = [],
    array $filter_page = []
): mixed
```

**Parameters:**
- `$method` (array): Request method data (GET/POST parameters)
- `$data` (object): Table configuration object
- `$filters` (array): Additional filter conditions
- `$filter_page` (array): Page-specific filters

**Returns:** JSON response array for DataTables

**Response Format:**
```php
[
    'draw' => 1,                    // Request counter
    'recordsTotal' => 1000,         // Total records
    'recordsFiltered' => 250,       // Filtered records
    'data' => [...]                 // Table rows
]
```

**Example Usage:**
```php
$datatables = new Datatables();
$response = $datatables->process($_GET, $tableConfig);
echo json_encode($response);
```

**Security Note:** All inputs are validated and sanitized before processing.

---

### processPost()

Processes POST requests for DataTables.

**Signature:**
```php
public function processPost(
    array $postData,
    object $data,
    array $filters = [],
    array $filter_page = []
): mixed
```

**Parameters:**
- `$postData` (array): POST request data
- `$data` (object): Table configuration
- `$filters` (array): Additional filters
- `$filter_page` (array): Page filters

**Returns:** JSON response array

**Usage:** Handles POST-based DataTables requests (alternative to GET).

---

## Query Optimization

### applyEagerLoading()

Applies eager loading to prevent N+1 query problems.

**Signature:**
```php
private function applyEagerLoading(
    mixed $model_data,
    object $data,
    string $table_name
): mixed
```

**Parameters:**
- `$model_data` (mixed): Eloquent query builder
- `$data` (object): Table configuration with relationships
- `$table_name` (string): Base table name

**Returns:** Query builder with eager loading applied

**How It Works:**
1. Extracts relationship definitions from configuration
2. Identifies nested relationships
3. Applies `with()` to load all relationships in single query
4. Optimizes nested relations to minimize queries

**Example:**
```php
// Without eager loading: N+1 queries
// Query 1: SELECT * FROM posts
// Query 2-101: SELECT * FROM users WHERE id = ? (for each post)

// With eager loading: 2 queries
// Query 1: SELECT * FROM posts
// Query 2: SELECT * FROM users WHERE id IN (1,2,3,...)
```

**Performance Impact:** Reduces queries from O(n) to O(1) for relationships.

---

### selectRequiredColumns()

Selects only required columns to reduce data transfer.

**Signature:**
```php
private function selectRequiredColumns(
    mixed $model_data,
    object $data,
    string $table_name
): mixed
```

**Parameters:**
- `$model_data` (mixed): Query builder
- `$data` (object): Table configuration
- `$table_name` (string): Table name

**Returns:** Query builder with column selection

**Optimization:**
- Only selects columns needed for display
- Includes foreign keys for relationships
- Includes columns needed for formulas
- Avoids SELECT * queries

**Example:**
```php
// Instead of: SELECT * FROM users
// Generates: SELECT id, name, email, role_id FROM users
```

---

### applyPagination()

Applies efficient pagination to query.

**Signature:**
```php
private function applyPagination(mixed $model, int $limitTotal): array
```

**Parameters:**
- `$model` (mixed): Query builder
- `$limitTotal` (int): Total records before pagination

**Returns:** Array with paginated query and total count

**Implementation:**
```php
[
    'model' => $query->skip($start)->take($length),
    'total' => $totalRecords
]
```

**Performance Note:** Uses database-level LIMIT/OFFSET for efficiency.

---

### applyOrdering()

Applies sorting at database level.

**Signature:**
```php
private function applyOrdering(
    mixed $datatables,
    object $data,
    string $table_name
): void
```

**Parameters:**
- `$datatables` (mixed): Query builder
- `$data` (object): Table configuration with order settings
- `$table_name` (string): Table name

**Features:**
- Validates column names against schema
- Validates sort direction (asc/desc)
- Supports multi-column sorting
- Handles relationship column sorting

**Security Note:** Column names are validated to prevent SQL injection.

---

### shouldUseChunking()

Determines if chunking should be used for large datasets.

**Signature:**
```php
private function shouldUseChunking(int $totalRows): bool
```

**Parameters:**
- `$totalRows` (int): Total number of rows

**Returns:** Boolean indicating if chunking should be used

**Threshold:** Returns true if totalRows > 1000

---

### processLargeDatasetChunked()

Processes large datasets using chunking to manage memory.

**Signature:**
```php
private function processLargeDatasetChunked(
    mixed $query,
    int $totalRows,
    callable $callback
): array
```

**Parameters:**
- `$query` (mixed): Query builder
- `$totalRows` (int): Total rows to process
- `$callback` (callable): Processing function for each chunk

**Returns:** Array of processed rows

**Chunk Size:** 1000 rows per chunk

**Memory Management:** Processes data in chunks to avoid memory exhaustion.

---

## Security Features

### validateDatatablesRequest()

Validates and sanitizes DataTables request parameters.

**Signature:**
```php
private function validateDatatablesRequest(array $request, string $tableName): array
```

**Parameters:**
- `$request` (array): Raw request data
- `$tableName` (string): Table name for column validation

**Returns:** Validated request array

**Validations:**
- `draw`: Integer, positive
- `start`: Integer, >= 0
- `length`: Integer, 1-100
- `search[value]`: Sanitized string
- `order[*][column]`: Valid column index
- `order[*][dir]`: 'asc' or 'desc'
- `columns[*][name]`: Valid column name

**Security Note:** Throws InvalidArgumentException for invalid inputs.

---

### validateTableName()

Validates table name to prevent SQL injection.

**Signature:**
```php
private function validateTableName(string $table, ?string $connection = null): string
```

**Parameters:**
- `$table` (string): Table name to validate
- `$connection` (string|null): Database connection

**Returns:** Validated table name

**Validation Rules:**
- Alphanumeric and underscore only
- Must exist in database
- Checked against whitelist (if configured)

**Throws:** `InvalidTableNameException` if validation fails

---

### validateColumnName()

Validates column name against table schema.

**Signature:**
```php
private function validateColumnName(string $columnName, string $tableName): string
```

**Parameters:**
- `$columnName` (string): Column name to validate
- `$tableName` (string): Table name

**Returns:** Validated column name

**Validation:**
- Checks column exists in table schema
- Prevents SQL injection via column names

**Throws:** `InvalidColumnException` if column doesn't exist

---

### validateOperator()

Validates SQL operators against whitelist.

**Signature:**
```php
private function validateOperator(string $operator): string
```

**Parameters:**
- `$operator` (string): SQL operator

**Returns:** Validated operator

**Allowed Operators:**
- `=`, `!=`, `<>`, `<`, `>`, `<=`, `>=`
- `LIKE`, `NOT LIKE`
- `IN`, `NOT IN`
- `BETWEEN`, `NOT BETWEEN`
- `IS NULL`, `IS NOT NULL`

**Throws:** `InvalidArgumentException` for invalid operators

---

### sanitizeSearchTerm()

Sanitizes search terms to prevent SQL injection.

**Signature:**
```php
private function sanitizeSearchTerm(string $search): string
```

**Parameters:**
- `$search` (string): Raw search term

**Returns:** Sanitized search term

**Sanitization:**
- Escapes special SQL characters
- Removes dangerous patterns
- Preserves legitimate wildcards

---

### escapeData()

Escapes data for HTML output to prevent XSS.

**Signature:**
```php
private function escapeData($value): string
```

**Parameters:**
- `$value` (mixed): Value to escape

**Returns:** Escaped string safe for HTML output

**Usage:** Applied to all cell values before sending to client.

---

## Data Transformation

### processRows()

Processes and transforms table rows.

**Signature:**
```php
private function processRows(
    $model,
    $datatables,
    $data,
    $table_name,
    $joinFields,
    int $totalRows = 0
)
```

**Parameters:**
- `$model` (mixed): Base model
- `$datatables` (mixed): Query results
- `$data` (object): Table configuration
- `$table_name` (string): Table name
- `$joinFields` (array): Joined field definitions
- `$totalRows` (int): Total row count

**Transformations Applied:**
1. Relationship data processing
2. Formula calculations
3. Data formatting
4. Status column processing
5. Image column rendering
6. Action button generation
7. XSS escaping

---

### applyFormulas()

Applies formula calculations to rows.

**Signature:**
```php
private function applyFormulas(
    mixed $datatables,
    object $data,
    string $table_name
): void
```

**Parameters:**
- `$datatables` (mixed): Row data
- `$data` (object): Table configuration with formulas
- `$table_name` (string): Table name

**Formula Processing:**
1. Parses formula expression
2. Extracts required fields
3. Evaluates formula for each row
4. Adds calculated column to row

**Example Formula:**
```php
// Formula: "price * quantity"
// Result: Adds 'total' column with calculated value
```

**Security Note:** Formula expressions are validated to prevent code injection.

---

### applyDataFormatting()

Applies formatting to column values.

**Signature:**
```php
private function applyDataFormatting(
    mixed $datatables,
    object $data,
    string $table_name
): void
```

**Parameters:**
- `$datatables` (mixed): Row data
- `$data` (object): Table configuration with format settings
- `$table_name` (string): Table name

**Supported Formats:**
- Number formatting (decimals, thousands separator)
- Currency formatting
- Percentage formatting
- Date/time formatting
- Boolean formatting

---

### formatColumnValue()

Formats a single column value.

**Signature:**
```php
private function formatColumnValue($value, string $type): string
```

**Parameters:**
- `$value` (mixed): Raw value
- `$type` (string): Format type

**Returns:** Formatted string

**Format Types:**
- `number`: Number with decimals
- `currency`: Currency with symbol
- `percentage`: Percentage with symbol
- `date`: Date formatting
- `datetime`: Date and time formatting
- `boolean`: Yes/No or custom labels

---

### processRelations()

Processes relationship data for display.

**Signature:**
```php
private function processRelations(
    mixed $datatables,
    object $data,
    string $table_name
): void
```

**Parameters:**
- `$datatables` (mixed): Row data
- `$data` (object): Table configuration with relationships
- `$table_name` (string): Table name

**Features:**
- Handles simple relationships (belongsTo, hasOne)
- Handles nested relationships (user.profile.avatar)
- Extracts display fields from related models
- Handles missing relationships gracefully

---

### addActionColumn()

Adds action buttons column to rows.

**Signature:**
```php
private function addActionColumn(
    mixed $datatables,
    mixed $model,
    array $actionConfig,
    object $data
): void
```

**Parameters:**
- `$datatables` (mixed): Row data
- `$model` (mixed): Model instance
- `$actionConfig` (array): Action button configuration
- `$data` (object): Table configuration

**Features:**
- Generates action buttons based on configuration
- Applies privilege checking
- Escapes URLs and labels
- Adds ARIA labels for accessibility

**Security Note:** All action URLs and labels are escaped to prevent XSS.

---

## Performance Monitoring

### logQueryPerformance()

Logs query performance metrics.

**Signature:**
```php
private function logQueryPerformance(string $tableName, float $startTime): void
```

**Parameters:**
- `$tableName` (string): Table name
- `$startTime` (float): Query start time

**Logged Metrics:**
- Query execution time
- Number of queries executed
- Memory usage
- Slow query warnings

---

### getQueryMetrics()

Retrieves query performance metrics.

**Signature:**
```php
public function getQueryMetrics(): array
```

**Returns:** Array of performance metrics

**Metrics Included:**
```php
[
    'total_queries' => 5,
    'total_time' => 0.234,
    'average_time' => 0.047,
    'slow_queries' => 1,
    'memory_peak' => '12MB'
]
```

---

### checkMemoryUsage()

Monitors memory usage and warns if approaching limit.

**Signature:**
```php
private function checkMemoryUsage(string $context): void
```

**Parameters:**
- `$context` (string): Context description for logging

**Behavior:**
- Checks current memory usage
- Compares to memory limit
- Logs warning if > 80% of limit
- Throws MemoryLimitException if > 95%

---

## Error Handling

### generateErrorResponse()

Generates error response for DataTables.

**Signature:**
```php
private function generateErrorResponse(
    int $draw,
    string $errorMessage,
    array $context = []
): array
```

**Parameters:**
- `$draw` (int): Request counter
- `$errorMessage` (string): Error message
- `$context` (array): Additional context for logging

**Returns:** DataTables error response

**Response Format:**
```php
[
    'draw' => 1,
    'recordsTotal' => 0,
    'recordsFiltered' => 0,
    'data' => [],
    'error' => 'Error message'
]
```

**Security Note:** Error messages are sanitized to prevent information disclosure.

---

### isOutOfMemoryError()

Checks if exception is out-of-memory error.

**Signature:**
```php
private function isOutOfMemoryError(\Throwable $e): bool
```

**Parameters:**
- `$e` (Throwable): Exception to check

**Returns:** Boolean indicating if error is memory-related

**Usage:** Helps identify memory issues for appropriate handling.

---

## Filter Processing

### init_filter_datatables()

Initializes filtered DataTables query.

**Signature:**
```php
public function init_filter_datatables(
    array $get = [],
    array $post = [],
    ?string $connection = null
): mixed
```

**Parameters:**
- `$get` (array): GET parameters
- `$post` (array): POST parameters with filter conditions
- `$connection` (string|null): Database connection

**Returns:** Query builder with filters applied

**Usage:** Used by advanced filter system.

---

### applyFilterConditions()

Applies filter conditions to query.

**Signature:**
```php
private function applyFilterWhereConditions(mixed $query, array $post): mixed
```

**Parameters:**
- `$query` (mixed): Query builder
- `$post` (array): Filter conditions

**Returns:** Query builder with filters applied

**Supported Filters:**
- Equality filters
- Range filters (between)
- Pattern matching (LIKE)
- IN filters
- NULL checks

**Security Note:** All filter values are parameterized.

---

## Image Handling

### imageViewColumn()

Processes image columns for display.

**Signature:**
```php
private function imageViewColumn(object $model, mixed $datatables): void
```

**Parameters:**
- `$model` (object): Model instance
- `$datatables` (mixed): Row data

**Features:**
- Detects image fields automatically
- Validates image paths
- Generates thumbnail HTML
- Falls back to filename for non-images

---

### checkValidImage()

Validates if file is a valid image.

**Signature:**
```php
private function checkValidImage(?string $string, bool $local_path = true): bool|string
```

**Parameters:**
- `$string` (string|null): File path
- `$local_path` (bool): Whether path is local

**Returns:** Boolean or image path

**Validation:**
- Checks file exists
- Validates image extensions
- Checks file is readable
- Results are cached for performance

---

## Best Practices

### Use Server-Side Processing for Large Tables

```php
// For tables with > 1000 rows
$table->setServerSide(true);
```

### Enable Eager Loading

```php
// Relationships are automatically eager-loaded
// No additional configuration needed
```

### Monitor Performance

```php
$datatables = new Datatables();
$response = $datatables->process($_GET, $config);

// Check metrics
$metrics = $datatables->getQueryMetrics();
if ($metrics['slow_queries'] > 0) {
    // Log slow queries for optimization
}
```

### Handle Errors Gracefully

```php
try {
    $response = $datatables->process($_GET, $config);
} catch (TableComponentException $e) {
    $response = $datatables->generateErrorResponse(
        $_GET['draw'],
        'An error occurred processing your request'
    );
}
```

---

## Security Best Practices

### Always Validate Inputs

```php
// Validation is automatic, but be aware:
// - Table names are validated
// - Column names are validated
// - Operators are validated
// - All values are parameterized
```

### Escape All Outputs

```php
// Escaping is automatic for all cell values
// Custom HTML should use SafeHtml marking
```

### Use Privilege Checking

```php
// Action buttons respect user privileges
// Configure in action settings
```

---

## See Also

- [Objects API Reference](OBJECTS.md)
- [Builder API Reference](BUILDER.md)
- [Security Guidelines](../features/SECURITY.md)
- [Performance Optimization](../guides/PERFORMANCE.md)
- [Cache Management](../features/CACHE_MANAGEMENT.md)
