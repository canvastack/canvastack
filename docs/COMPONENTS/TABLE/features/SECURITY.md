# Security Features

**Version:** 2.0.0  
**Phase:** 1 - Critical Security  
**Status:** ✅ Completed (11/11 options)

---

## Overview

The Table Components include comprehensive security features to protect against common web vulnerabilities including XSS, SQL injection, and unauthorized access.

## Features Implemented

### 1. XSS Protection

**Config:** `canvastack.datatables.security.xss_protection`  
**Default:** `true`  
**Status:** ✅ Implemented

Automatically escapes all user-controllable data before HTML output using `htmlspecialchars()` with `ENT_QUOTES`.

#### Implementation

```php
// Automatic escaping in search terms
$search = canvastack_table_sanitize_search($userInput);

// Manual escaping when needed
$escaped = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
```

#### Configuration

```php
// config/canvastack.datatables.php
return [
    'security' => [
        'xss_protection' => true, // Enable XSS protection
    ],
];
```

#### Protected Areas
- Search input fields
- Column data output
- Filter values
- Action button labels
- Error messages

---

### 2. SQL Injection Prevention

**Config:** `canvastack.datatables.security.sql_injection_prevention`  
**Default:** `true`  
**Status:** ✅ Implemented

Validates all SQL operators and sort directions against whitelists to prevent SQL injection attacks.

#### Operator Validation

```php
// Validates operator against whitelist
$operator = canvastack_table_validate_operator($userOperator);

// Allowed operators (configurable)
$allowed = ['=', '!=', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN'];
```

#### Sort Direction Validation

```php
// Validates and normalizes sort direction
$direction = canvastack_table_validate_sort_direction($userDirection);

// Allowed: 'asc', 'desc', 'ASC', 'DESC'
// Returns: 'asc' or 'desc' (normalized)
```

#### Configuration

```php
// config/canvastack.datatables.php
return [
    'security' => [
        'sql_injection_prevention' => true,
        'allowed_operators' => [
            '=', '!=', '<>', '>', '<', '>=', '<=',
            'LIKE', 'NOT LIKE', 'IN', 'NOT IN',
            'BETWEEN', 'IS NULL', 'IS NOT NULL',
        ],
        'allowed_sort_directions' => ['asc', 'desc', 'ASC', 'DESC'],
    ],
];
```

---

### 3. Input Validation

**Config:** `canvastack.datatables.security.input_validation`  
**Default:** `true`  
**Status:** ✅ Implemented

Validates all user inputs including search terms, pagination parameters, and column names.

#### Search Term Validation

```php
// Sanitizes and validates search input
$sanitized = canvastack_table_sanitize_search($search);

// Features:
// - XSS protection
// - Length limit enforcement
// - Special character handling
```

#### Pagination Validation

```php
// Validates pagination parameters
if ($start < 0) {
    throw new InvalidPaginationException('Start must be non-negative');
}

if ($length < 1 || $length > MAX_PAGE_LENGTH) {
    throw new InvalidPaginationException('Invalid page length');
}
```

#### Column Name Validation

```php
// Validates column exists in table schema
$validated = $datatables->validateColumnName($column, $table);

// Checks against actual database schema
// Prevents unauthorized column access
```

#### Configuration

```php
// config/canvastack.datatables.php
return [
    'security' => [
        'input_validation' => true,
        'validate_column_names' => true,
        'max_search_length' => 255,
    ],
];
```

---

### 4. Security Event Logging

**Config:** `canvastack.datatables.security.log_security_events`  
**Default:** `true`  
**Status:** ✅ Implemented

Logs all security-related events for audit trails and threat detection.

#### Usage

```php
// Log security event
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

#### Logged Events
- Invalid SQL operators
- Invalid sort directions
- Invalid column names
- Search term truncation
- Unauthorized table access
- Failed validation attempts

#### Configuration

```php
// config/canvastack.datatables.php
return [
    'security' => [
        'log_security_events' => true,
        'security_log_channel' => 'daily', // Laravel log channel
    ],
];
```

#### Log Format

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
        "operator": "DROP",
        "allowed": ["=", "!=", "<", ">"]
    }
}
```

---

### 5. Table Name Validation

**Config:** `canvastack.datatables.allowed_tables`  
**Default:** `null` (validates against database)  
**Status:** ✅ Implemented

Validates table names against whitelist or database schema to prevent unauthorized table access.

#### Usage

```php
// Validates table name
$validated = canvastack_table_validate_table_name(
    $tableName,
    $allowedTables, // null = check database
    $connection
);
```

#### Configuration

```php
// config/canvastack.datatables.php
return [
    // Option 1: Whitelist specific tables
    'allowed_tables' => ['users', 'posts', 'comments'],
    
    // Option 2: Allow all tables in database (null)
    'allowed_tables' => null,
];
```

#### Validation Rules
- Alphanumeric and underscore only
- Must exist in database (if no whitelist)
- Must be in whitelist (if configured)
- Cached for performance

---

### 6. Search Length Limits

**Config:** `canvastack.datatables.security.max_search_length`  
**Default:** `255`  
**Status:** ✅ Implemented

Enforces maximum length for search terms to prevent DoS attacks and excessive resource usage.

#### Implementation

```php
// Automatically truncates long search terms
$search = canvastack_table_sanitize_search($longSearchTerm);

// Logs truncation event
if (strlen($original) > $maxLength) {
    canvastack_table_log_security_event('search_term_truncated', ...);
}
```

#### Configuration

```php
// config/canvastack.datatables.php
return [
    'security' => [
        'max_search_length' => 255, // Maximum characters
    ],
];
```

---

### 7. SafeHtml Marker System

**Config:** `canvastack.datatables.security.use_safehtml_marker`  
**Default:** `true`  
**Status:** ✅ Implemented

Prevents double-encoding of HTML while maintaining XSS protection using marker system.

#### How It Works

```php
// Mark content as safe (already escaped)
$safe = SafeHtml::MARKER . $escapedContent;

// System recognizes marker and skips re-escaping
if (str_starts_with($content, SafeHtml::MARKER)) {
    return substr($content, strlen(SafeHtml::MARKER));
}
```

#### Use Cases
- Pre-escaped HTML content
- Trusted HTML from database
- Content from other escaping systems

---

## Security Best Practices

### 1. Always Enable Core Security

```php
// config/canvastack.datatables.php
return [
    'security' => [
        'xss_protection' => true,
        'sql_injection_prevention' => true,
        'input_validation' => true,
        'log_security_events' => true,
    ],
];
```

### 2. Use Table Whitelist in Production

```php
// Restrict to specific tables
'allowed_tables' => ['users', 'posts', 'comments'],
```

### 3. Monitor Security Logs

```bash
# Check security logs regularly
tail -f storage/logs/laravel.log | grep "SECURITY"
```

### 4. Limit Search Length

```php
// Prevent DoS via long search terms
'max_search_length' => 255,
```

### 5. Validate All User Input

```php
// Never trust user input
$validated = $datatables->validateDatatablesRequest($request, $table);
```

---

## Security Testing

### Test Security Features

```bash
# Run security tests
php artisan test --group=security

# Test specific security feature
php artisan test tests/Unit/IntegratedFunctionsTest.php --filter=security
```

### Manual Security Testing

```php
// Test XSS protection
$xss = '<script>alert("XSS")</script>';
$safe = canvastack_table_sanitize_search($xss);
// Expected: &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;

// Test SQL injection
try {
    canvastack_table_validate_operator('DROP TABLE');
} catch (InvalidArgumentException $e) {
    // Expected: Exception thrown
}

// Test invalid sort direction
try {
    canvastack_table_validate_sort_direction('DELETE');
} catch (InvalidArgumentException $e) {
    // Expected: Exception thrown
}
```

---

## Threat Model

### Protected Against

✅ **XSS (Cross-Site Scripting)**
- All user input escaped before output
- HTML attributes validated
- Event handlers blocked

✅ **SQL Injection**
- Operator whitelist validation
- Sort direction validation
- Parameterized queries only

✅ **Path Traversal**
- Table name validation
- Column name validation
- File path sanitization

✅ **DoS (Denial of Service)**
- Search length limits
- Pagination limits
- Memory monitoring

✅ **Unauthorized Access**
- Table whitelist
- Column validation
- Privilege checking

### Not Protected Against

❌ **CSRF** - Use Laravel's CSRF protection
❌ **Authentication** - Implement in your application
❌ **Authorization** - Use Laravel policies/gates
❌ **Rate Limiting** - Use Laravel rate limiter

---

## Security Checklist

Before deploying to production:

- [ ] Enable all core security features
- [ ] Configure table whitelist
- [ ] Set appropriate search length limit
- [ ] Enable security event logging
- [ ] Review security logs regularly
- [ ] Test with security test suite
- [ ] Implement CSRF protection
- [ ] Add authentication/authorization
- [ ] Configure rate limiting
- [ ] Use HTTPS in production

---

## Related Documentation

- [Configuration Guide](../CONFIGURATION.md)
- [Helper Functions](../api/HELPERS.md)
- [Best Practices](../guides/BEST_PRACTICES.md)
- [Troubleshooting](../guides/TROUBLESHOOTING.md)

---

**Last Updated:** April 4, 2026  
**Maintained By:** CanvaStack Team
