# Configuration Type Coercion

## Overview

The CanvaStack RBAC configuration validator supports automatic type coercion for certain configuration values to improve flexibility and compatibility with environment variables.

**Location**: `packages/canvastack/canvastack/src/Auth/RBAC/ConfigValidator.php`

---

## Supported Type Coercion

### Integer Values

The validator automatically coerces numeric strings to integers for the following configuration keys:

- `cache.ttl.row`
- `cache.ttl.column`
- `cache.ttl.json_attribute`
- `cache.ttl.conditional`

### How It Works

```php
// Before validation
$value = $config['cache']['ttl']['row']; // Could be '3600' (string)

// Type coercion
if (is_string($value) && is_numeric($value)) {
    $value = (int) $value; // Converts to 3600 (integer)
}

// Then validation
if (!is_int($value) || $value < 0) {
    throw new InvalidArgumentException(...);
}
```

---

## Accepted Values

### ✅ Valid Values

```php
// Integer
'cache' => [
    'ttl' => [
        'row' => 3600,
    ],
],

// String integer (will be coerced)
'cache' => [
    'ttl' => [
        'row' => '3600',
    ],
],

// Zero
'cache' => [
    'ttl' => [
        'row' => 0,
        'column' => '0',
    ],
],

// Environment variables (return strings)
'cache' => [
    'ttl' => [
        'row' => env('RBAC_CACHE_TTL_ROW', 3600),
        'column' => env('RBAC_CACHE_TTL_COLUMN', '3600'),
    ],
],
```

### ❌ Invalid Values

```php
// Negative integer
'cache' => [
    'ttl' => [
        'row' => -100, // ❌ Error: must be non-negative
    ],
],

// Negative string integer
'cache' => [
    'ttl' => [
        'row' => '-100', // ❌ Error: must be non-negative
    ],
],

// Non-numeric string
'cache' => [
    'ttl' => [
        'row' => 'invalid', // ❌ Error: must be integer
    ],
],

// Float
'cache' => [
    'ttl' => [
        'row' => 3600.5, // ❌ Error: must be integer
    ],
],

// Null
'cache' => [
    'ttl' => [
        'row' => null, // ❌ Error: must be integer
    ],
],

// Boolean
'cache' => [
    'ttl' => [
        'row' => true, // ❌ Error: must be integer
    ],
],
```

---

## Use Cases

### 1. Environment Variables

Environment variables always return strings in PHP. Type coercion allows you to use them directly:

```php
// .env
RBAC_CACHE_TTL_ROW=3600
RBAC_CACHE_TTL_COLUMN=7200

// config/canvastack-rbac.php
return [
    'fine_grained' => [
        'cache' => [
            'ttl' => [
                'row' => env('RBAC_CACHE_TTL_ROW', 3600),        // Returns '3600' (string)
                'column' => env('RBAC_CACHE_TTL_COLUMN', 7200),  // Returns '7200' (string)
            ],
        ],
    ],
];
```

Both values will be automatically coerced to integers during validation.

### 2. Dynamic Configuration

When loading configuration from external sources (database, API, JSON files), values may be strings:

```php
// Load from JSON
$config = json_decode(file_get_contents('config.json'), true);

// config.json
{
    "cache": {
        "ttl": {
            "row": "3600",
            "column": "7200"
        }
    }
}

// Values are strings but will be coerced
$validator->validate($config['fine_grained']);
```

### 3. User Input

When accepting configuration from user input (admin panel, CLI), values are typically strings:

```php
// From form input
$ttl = $_POST['cache_ttl']; // '3600' (string)

$config['cache']['ttl']['row'] = $ttl;

// Will be coerced during validation
$validator->validate($config);
```

---

## Validation Flow

```
Input Value
    ↓
Is it a string?
    ↓ Yes
Is it numeric?
    ↓ Yes
Convert to integer
    ↓
Is it an integer?
    ↓ Yes
Is it non-negative?
    ↓ Yes
✅ Valid
```

---

## Error Messages

### Non-Numeric String

```php
$config['cache']['ttl']['row'] = 'invalid';

// Error: Cache TTL "row" must be a non-negative integer.
```

### Negative Value

```php
$config['cache']['ttl']['row'] = -100;
// or
$config['cache']['ttl']['row'] = '-100';

// Error: Cache TTL "row" must be a non-negative integer.
```

### Float Value

```php
$config['cache']['ttl']['row'] = 3600.5;

// Error: Cache TTL "row" must be a non-negative integer.
```

---

## Testing

### Unit Tests

The following tests verify type coercion behavior:

```php
// Test string integer is accepted
public function test_string_integer_cache_ttl_is_accepted(): void
{
    $config = $this->getValidConfig();
    $config['cache']['ttl']['column'] = '3600';

    $this->validator->validate($config); // Should not throw
}

// Test negative string integer is rejected
public function test_negative_string_integer_cache_ttl_throws_exception(): void
{
    $config = $this->getValidConfig();
    $config['cache']['ttl']['row'] = '-100';

    $this->expectException(InvalidArgumentException::class);
    $this->validator->validate($config);
}

// Test non-numeric string is rejected
public function test_non_integer_cache_ttl_throws_exception(): void
{
    $config = $this->getValidConfig();
    $config['cache']['ttl']['column'] = 'invalid';

    $this->expectException(InvalidArgumentException::class);
    $this->validator->validate($config);
}
```

---

## Best Practices

### 1. Use Environment Variables

```php
// ✅ Good: Use env() with default values
'cache' => [
    'ttl' => [
        'row' => env('RBAC_CACHE_TTL_ROW', 3600),
    ],
],
```

### 2. Provide Type Hints in Comments

```php
// ✅ Good: Document expected types
'cache' => [
    'ttl' => [
        'row' => env('RBAC_CACHE_TTL_ROW', 3600), // int|string (seconds)
    ],
],
```

### 3. Validate Early

```php
// ✅ Good: Validate configuration on boot
public function boot(): void
{
    $validator = new ConfigValidator();
    $validator->validate(config('canvastack-rbac.fine_grained'));
}
```

---

## Future Enhancements

Potential future type coercion support:

1. **Boolean Values**: `'true'` → `true`, `'false'` → `false`
2. **Array Values**: `'1,2,3'` → `[1, 2, 3]`
3. **Nested Values**: Recursive type coercion for nested arrays

---

## Related Documentation

- [Configuration Validation](./configuration-validation.md)
- [RBAC Configuration](../features/rbac.md#configuration)
- [Environment Variables](../getting-started/configuration.md#environment-variables)

---

**Last Updated**: 2026-03-01  
**Version**: 1.0.0  
**Status**: Published
