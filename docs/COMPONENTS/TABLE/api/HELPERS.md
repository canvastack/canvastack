# Helper Functions API Reference

**Version:** 2.0.0  
**Location:** `vendor/canvastack/canvastack/src/Library/Helpers/Table.php`  
**Status:** Production Ready

---

## Overview

Global helper functions for table operations, security, caching, and validation. All functions are available globally after package installation.

---

## Security Functions

### canvastack_table_log_security_event()

Logs security events for audit trails and threat detection.

**Signature:**
```php
function canvastack_table_log_security_event(
    string $eventType,
    string $message,
    array $context = []
): void
```

**Parameters:**
- `$eventType` (string) - Type of security event
- `$message` (string) - Human-readable message
- `$context` (array) - Additional context data

**Example:**
```php
canvastack_table_log_security_event(
    'invalid_operator',
    'Invalid SQL operator detected: DROP',
    [
        'operator' => 'DROP',
        'ip' => request()->ip(),
        'user_id' => auth()->id(),
    ]
);
```

**Config:**
- `canvastack.datatables.security.log_security_events` - Enable/disable logging
- `canvastack.datatables.security.security_log_channel` - Log channel

**Log Output:**
```json
{
    "event_type": "invalid_operator",
    "message": "Invalid SQL operator detected: DROP",
    "ip": "192.168.1.1",
    "user_id": 123,
    "url": "https://example.com/api/datatables",
    "user_agent": "Mozilla/5.0...",
    "timestamp": "2026-04-04 10:30:00",
    "context": {
        "operator": "DROP"
    }
}
```

---

### canvastack_table_validate_operator()

Validates SQL operator against whitelist.

**Signature:**
```php
function canvastack_table_validate_operator(string $operator): string
```

**Parameters:**
- `$operator` (string) - SQL operator to validate

**Returns:**
- `string` - Validated operator (uppercase)

**Throws:**
- `InvalidArgumentException` - If operator not in whitelist

**Example:**
```php
try {
    $operator = canvastack_table_validate_operator('=');
    // Returns: '='
    
    $operator = canvastack_table_validate_operator('DROP');
    // Throws: InvalidArgumentException
} catch (InvalidArgumentException $e) {
    // Handle invalid operator
}
```

**Config:**
- `canvastack.datatables.security.sql_injection_prevention` - Enable/disable
- `canvastack.datatables.security.allowed_operators` - Whitelist

**Default Allowed Operators:**
```php
['=', '!=', '<>', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'IS NULL', 'IS NOT NULL']
```

---

### canvastack_table_validate_sort_direction()

Validates and normalizes sort direction.

**Signature:**
```php
function canvastack_table_validate_sort_direction(string $direction): string
```

**Parameters:**
- `$direction` (string) - Sort direction (asc/desc)

**Returns:**
- `string` - Normalized direction (lowercase)

**Throws:**
- `InvalidArgumentException` - If direction not allowed

**Example:**
```php
$direction = canvastack_table_validate_sort_direction('ASC');
// Returns: 'asc'

$direction = canvastack_table_validate_sort_direction(' desc ');
// Returns: 'desc' (trimmed and normalized)

$direction = canvastack_table_validate_sort_direction('DELETE');
// Throws: InvalidArgumentException
```

**Config:**
- `canvastack.datatables.security.sql_injection_prevention` - Enable/disable
- `canvastack.datatables.security.allowed_sort_directions` - Whitelist

---

### canvastack_table_sanitize_search()

Sanitizes search term for XSS protection.

**Signature:**
```php
function canvastack_table_sanitize_search(string $search): string
```

**Parameters:**
- `$search` (string) - Search term to sanitize

**Returns:**
- `string` - Sanitized search term

**Example:**
```php
$safe = canvastack_table_sanitize_search('<script>alert("XSS")</script>');
// Returns: '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;'

$safe = canvastack_table_sanitize_search(str_repeat('a', 300));
// Returns: First 255 characters (if max_search_length = 255)
```

**Features:**
- XSS protection via `htmlspecialchars()`
- Length limit enforcement
- Automatic truncation with logging

**Config:**
- `canvastack.datatables.security.xss_protection` - Enable/disable
- `canvastack.datatables.security.max_search_length` - Maximum length

---

### canvastack_table_validate_table_name()

Validates table name against whitelist or database.

**Signature:**
```php
function canvastack_table_validate_table_name(
    string $tableName,
    ?array $allowedTables = null,
    string $connection = 'mysql'
): string
```

**Parameters:**
- `$tableName` (string) - Table name to validate
- `$allowedTables` (array|null) - Whitelist (null = check database)
- `$connection` (string) - Database connection

**Returns:**
- `string` - Validated table name

**Throws:**
- `InvalidTableNameException` - If table invalid

**Example:**
```php
// With whitelist
$table = canvastack_table_validate_table_name('users', ['users', 'posts']);
// Returns: 'users'

// Without whitelist (checks database)
$table = canvastack_table_validate_table_name('users', null, 'mysql');
// Returns: 'users' (if exists in database)

// Invalid table
$table = canvastack_table_validate_table_name('DROP TABLE users');
// Throws: InvalidTableNameException
```

**Validation Rules:**
- Alphanumeric and underscore only
- Must exist in database (if no whitelist)
- Must be in whitelist (if provided)
- Cached for performance

---

## Cache Functions

### canvastack_table_cache_monitor()

Monitors cache operations for performance tracking.

**Signature:**
```php
function canvastack_table_cache_monitor(
    string $operation,
    string $key,
    bool $hit
): void
```

**Parameters:**
- `$operation` (string) - Operation type (get, put, forget)
- `$key` (string) - Cache key
- `$hit` (bool) - Whether operation was a hit (true) or miss (false)

**Example:**
```php
$cached = Cache::get($key);
if ($cached !== null) {
    canvastack_table_cache_monitor('get', $key, true); // HIT
} else {
    canvastack_table_cache_monitor('get', $key, false); // MISS
}
```

**Features:**
- Logs hits and misses
- Tracks statistics
- Calculates hit rate
- Per-day statistics

**Config:**
- `canvastack.cache.monitoring.enabled` - Enable/disable
- `canvastack.cache.monitoring.log_hits_misses` - Log to file
- `canvastack.cache.monitoring.track_statistics` - Track stats
- `canvastack.cache.monitoring.statistics_ttl` - Stats TTL

**Statistics Format:**
```php
[
    'hits' => 1250,
    'misses' => 150,
    'operations' => [
        'get' => 1400,
        'put' => 150,
        'forget' => 50,
    ],
]
```

---

### canvastack_table_invalidate_cache()

Invalidates table cache with cascade support.

**Signature:**
```php
function canvastack_table_invalidate_cache(
    string $tableName,
    string $type = 'all',
    string $connection = 'mysql'
): bool
```

**Parameters:**
- `$tableName` (string) - Table name
- `$type` (string) - Cache type (all, schema, config, validation, relationships)
- `$connection` (string) - Database connection

**Returns:**
- `bool` - Success status

**Example:**
```php
// Invalidate all caches for table
canvastack_table_invalidate_cache('users', 'all', 'mysql');

// Invalidate specific type
canvastack_table_invalidate_cache('users', 'schema', 'mysql');

// Invalidate multiple tables
foreach (['users', 'posts'] as $table) {
    canvastack_table_invalidate_cache($table, 'all');
}
```

**Strategies:**
- `immediate` - Clear immediately (default)
- `lazy` - Mark as stale, clear on next access
- `scheduled` - Queue for scheduled clearing

**Config:**
- `canvastack.cache.invalidation.enabled` - Enable/disable
- `canvastack.cache.invalidation.strategy` - Strategy
- `canvastack.cache.invalidation.cascade_invalidation` - Cascade to related

---

### canvastack_table_get_cached_schema()

Gets cached table schema.

**Signature:**
```php
function canvastack_table_get_cached_schema(
    string $tableName,
    string $connection = 'mysql'
): ?array
```

**Parameters:**
- `$tableName` (string) - Table name
- `$connection` (string) - Database connection

**Returns:**
- `array|null` - Schema array or null if not cached

**Example:**
```php
$schema = canvastack_table_get_cached_schema('users', 'mysql');

// Returns:
[
    'id' => 'integer',
    'name' => 'string',
    'email' => 'string',
    'created_at' => 'datetime',
]
```

---

### canvastack_table_cache_schema()

Caches table schema.

**Signature:**
```php
function canvastack_table_cache_schema(
    string $tableName,
    array $schema,
    string $connection = 'mysql',
    int $ttl = 3600
): bool
```

**Parameters:**
- `$tableName` (string) - Table name
- `$schema` (array) - Schema data
- `$connection` (string) - Database connection
- `$ttl` (int) - Time to live in seconds

**Returns:**
- `bool` - Success status

**Example:**
```php
$schema = [
    'id' => 'integer',
    'name' => 'string',
    'email' => 'string',
];

canvastack_table_cache_schema('users', $schema, 'mysql', 3600);
```

---

### canvastack_table_cache_key()

Generates cache key with prefix.

**Signature:**
```php
function canvastack_table_cache_key(
    string $prefix,
    string $tableName,
    string $connection = 'mysql'
): string
```

**Parameters:**
- `$prefix` (string) - Key prefix
- `$tableName` (string) - Table name
- `$connection` (string) - Database connection

**Returns:**
- `string` - Generated cache key

**Example:**
```php
$key = canvastack_table_cache_key('schema_', 'users', 'mysql');
// Returns: 'canvastack_schema_mysql_users'

$key = canvastack_table_cache_key('config_', 'posts', 'pgsql');
// Returns: 'canvastack_config_pgsql_posts'
```

---

## Deprecation Functions

### canvastack_table_deprecated()

Marks feature as deprecated and logs usage.

**Signature:**
```php
function canvastack_table_deprecated(
    string $feature,
    string $alternative = ''
): void
```

**Parameters:**
- `$feature` (string) - Deprecated feature name
- `$alternative` (string) - Alternative feature to use

**Example:**
```php
canvastack_table_deprecated(
    'oldMethod()',
    'newMethod()'
);

// Logs:
// "Deprecated feature used: oldMethod(). Use newMethod() instead."
```

**Features:**
- Logs to configured channel
- Includes backtrace
- Triggers E_USER_DEPRECATED in development
- Can be disabled in production

**Config:**
- `canvastack.datatables.compatibility.warn_deprecated` - Enable/disable
- `canvastack.datatables.compatibility.log_deprecated` - Log to file
- `canvastack.datatables.compatibility.deprecated_log_channel` - Log channel

---

## Action Button Functions

### canvastack_table_action_button()

Generates action button HTML with privilege checking.

**Signature:**
```php
function canvastack_table_action_button(
    string $action,
    string $url,
    array $attributes = []
): string
```

**Parameters:**
- `$action` (string) - Action type (view, edit, delete, etc.)
- `$url` (string) - Button URL
- `$attributes` (array) - Additional HTML attributes

**Returns:**
- `string` - HTML button markup

**Example:**
```php
$button = canvastack_table_action_button('edit', '/users/1/edit', [
    'class' => 'btn-primary',
    'data-id' => 1,
]);

// Returns:
// <a href="/users/1/edit" class="btn btn-primary" data-id="1">
//     <i class="fa fa-edit"></i> Edit
// </a>
```

**Config:**
- `canvastack.datatables.actions.enabled` - Enable/disable
- `canvastack.datatables.actions.check_privileges` - Check user privileges
- `canvastack.datatables.actions.icons` - Icon mappings
- `canvastack.datatables.actions.labels` - Label mappings
- `canvastack.datatables.actions.classes` - CSS class mappings

---

## Usage Examples

### Complete Security Setup

```php
// Validate and sanitize all inputs
$operator = canvastack_table_validate_operator($request->operator);
$direction = canvastack_table_validate_sort_direction($request->direction);
$search = canvastack_table_sanitize_search($request->search);
$table = canvastack_table_validate_table_name($request->table);

// Log security event if needed
if ($suspiciousActivity) {
    canvastack_table_log_security_event(
        'suspicious_activity',
        'Multiple failed validation attempts',
        ['ip' => request()->ip()]
    );
}
```

### Complete Cache Setup

```php
// Try to get from cache
$cacheKey = canvastack_table_cache_key('schema_', 'users');
$schema = Cache::get($cacheKey);

if ($schema !== null) {
    // Cache hit
    canvastack_table_cache_monitor('get', $cacheKey, true);
    return $schema;
}

// Cache miss
canvastack_table_cache_monitor('get', $cacheKey, false);

// Fetch from database
$schema = DB::connection()->getSchemaBuilder()->getColumnListing('users');

// Cache for future use
canvastack_table_cache_schema('users', $schema);

return $schema;
```

### Invalidate After Update

```php
// After model update
User::updated(function ($user) {
    canvastack_table_invalidate_cache('users', 'all');
});

// After schema change
Schema::table('users', function ($table) {
    $table->string('phone')->nullable();
});
canvastack_table_invalidate_cache('users', 'schema');
```

---

## Error Handling

All helper functions include proper error handling:

```php
try {
    $operator = canvastack_table_validate_operator($input);
} catch (InvalidArgumentException $e) {
    // Log error
    Log::error('Invalid operator', [
        'input' => $input,
        'error' => $e->getMessage(),
    ]);
    
    // Return error response
    return response()->json(['error' => 'Invalid operator'], 400);
}
```

---

## Testing

### Unit Tests

```php
// Test operator validation
$this->assertEquals('=', canvastack_table_validate_operator('='));
$this->expectException(InvalidArgumentException::class);
canvastack_table_validate_operator('DROP');

// Test search sanitization
$safe = canvastack_table_sanitize_search('<script>alert("XSS")</script>');
$this->assertStringNotContainsString('<script>', $safe);

// Test cache monitoring
canvastack_table_cache_monitor('get', 'test_key', true);
$stats = Cache::get('cache_stats_' . date('Y-m-d'));
$this->assertEquals(1, $stats['hits']);
```

---

## Related Documentation

- [Security Features](../features/SECURITY.md)
- [Cache Management](../features/CACHE_MANAGEMENT.md)
- [Configuration Guide](../CONFIGURATION.md)
- [Best Practices](../guides/BEST_PRACTICES.md)

---

**Last Updated:** April 4, 2026  
**Maintained By:** CanvaStack Team
