# Security Features

CanvaStack Table is built with security as a top priority, implementing multiple layers of protection against common web vulnerabilities and attacks.

## Table of Contents

- [Security Architecture](#security-architecture)
- [Input Validation](#input-validation)
- [SQL Injection Prevention](#sql-injection-prevention)
- [XSS Protection](#xss-protection)
- [CSRF Protection](#csrf-protection)
- [Access Control](#access-control)
- [Security Monitoring](#security-monitoring)
- [Configuration](#configuration)
- [Best Practices](#best-practices)

## Security Architecture

CanvaStack Table implements a multi-layered security architecture:

```
┌─────────────────────────────────────────────────────────────┐
│                    Application Layer                        │
├─────────────────────────────────────────────────────────────┤
│                  Access Control Layer                       │
├─────────────────────────────────────────────────────────────┤
│                 Security Middleware Layer                   │
├─────────────────────────────────────────────────────────────┤
│                Input Validation Layer                       │
├─────────────────────────────────────────────────────────────┤
│                Parameter Binding Layer                      │
├─────────────────────────────────────────────────────────────┤
│                   Database Layer                            │
└─────────────────────────────────────────────────────────────┘
```

### Security Modes

CanvaStack offers different security levels:

```php
// config/canvastack-security.php
'mode' => env('CANVASTACK_SECURITY_MODE', 'hardened'),
```

**Available Modes:**
- **`full`** - All security features enabled (recommended for production)
- **`hardened`** - Core security + monitoring (balanced approach)
- **`basic`** - Core security only (minimum protection)
- **`custom`** - Use custom configuration
- **`disabled`** - Disable security features (NOT RECOMMENDED)

## Input Validation

### Table Name Validation

All table names are validated against strict patterns:

```php
// SecurityInputValidator.php
private const TABLE_NAME_PATTERN = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';
private const MAX_TABLE_NAME_LENGTH = 64;

public function validateTableName(string $tableName): bool
{
    // Length validation
    if (strlen($tableName) > self::MAX_TABLE_NAME_LENGTH) {
        throw new SecurityException('Table name exceeds maximum length');
    }
    
    // Pattern validation
    if (!preg_match(self::TABLE_NAME_PATTERN, $tableName)) {
        throw new SecurityException('Table name contains invalid characters');
    }
    
    // SQL injection check
    if ($this->containsSqlInjection($tableName)) {
        throw new SecurityException('SQL injection attempt detected');
    }
    
    return true;
}
```

### Column Name Validation

Column names are validated and whitelisted:

```php
private const COLUMN_NAME_PATTERN = '/^[a-zA-Z_][a-zA-Z0-9_\.]*$/';
private const MAX_COLUMN_NAME_LENGTH = 64;

// Whitelisted column names
private array $whitelistedColumns = [
    'id', 'name', 'email', 'created_at', 'updated_at', 'deleted_at',
    'title', 'description', 'status', 'type', 'category_id', 'user_id',
    'slug', 'content', 'price', 'quantity', 'active', 'published'
];

public function validateColumnName(string $columnName, array $additionalWhitelist = []): bool
{
    $cleanColumnName = $this->extractColumnName($columnName);
    
    // Length check
    if (strlen($cleanColumnName) > self::MAX_COLUMN_NAME_LENGTH) {
        throw new SecurityException('Column name exceeds maximum length');
    }
    
    // Pattern check
    if (!preg_match(self::COLUMN_NAME_PATTERN, $columnName)) {
        throw new SecurityException('Column name contains invalid characters');
    }
    
    // Whitelist check
    $allWhitelist = array_merge($this->whitelistedColumns, $additionalWhitelist);
    if (!in_array($cleanColumnName, $allWhitelist)) {
        throw new SecurityException('Column name not in whitelist');
    }
    
    return true;
}
```

### Value Sanitization

All input values are sanitized based on their type:

```php
public function sanitizeValue($value, string $type = 'string', ?int $maxLength = null)
{
    if ($value === null || $value === '') {
        return $value;
    }
    
    $stringValue = (string) $value;
    
    // Length validation
    $maxLen = $maxLength ?? self::MAX_LENGTHS[$type] ?? self::MAX_LENGTHS['string'];
    if (strlen($stringValue) > $maxLen) {
        throw new SecurityException('Input value exceeds maximum length');
    }
    
    // SQL injection check
    if ($this->containsSqlInjection($stringValue)) {
        throw new SecurityException('SQL injection attempt detected');
    }
    
    // XSS check
    if ($this->containsXss($stringValue)) {
        throw new SecurityException('XSS attempt detected');
    }
    
    return $this->applySanitization($value, $type);
}
```

## SQL Injection Prevention

### Detection Patterns

CanvaStack uses comprehensive patterns to detect SQL injection attempts:

```php
private const SQL_INJECTION_PATTERNS = [
    '/(\s|^)(union|select|insert|update|delete|drop|create|alter|exec|execute)\s/i',
    '/(\s|^)(or|and)\s+\d+\s*=\s*\d+/i',
    '/(\s|^)(or|and)\s+[\'"].*[\'"](\s*=\s*[\'"].*[\'"])?/i',
    '/--\s*.*$/m',
    '/\/\*.*\*\//s',
    '/;\s*(union|select|insert|update|delete|drop)/i',
    '/\b(script|javascript|vbscript|onload|onerror|onclick)\b/i'
];
```

### Parameter Binding

All database queries use parameter binding:

```php
// In Search.php
private function selectSecure(string $query, array $bindings = []): array
{
    try {
        // Validate query structure
        $this->validateQueryStructure($query);
        
        // Use parameter binding
        $result = DB::select($query, $bindings);
        
        // Log query for monitoring
        $this->logSecureQuery($query, $bindings);
        
        return $result;
        
    } catch (\Exception $e) {
        $this->logSecurityViolation('sql_execution_error', [
            'query' => $query,
            'bindings' => $bindings,
            'error' => $e->getMessage()
        ]);
        throw new SecurityException('Database query failed security validation');
    }
}
```

### Query Structure Validation

Queries are validated before execution:

```php
private function validateQueryStructure(string $query): void
{
    // Check for dangerous SQL keywords in wrong context
    $dangerousPatterns = [
        '/;\s*(drop|alter|create|truncate)/i',
        '/union\s+select/i',
        '/into\s+outfile/i',
        '/load_file\s*\(/i'
    ];
    
    foreach ($dangerousPatterns as $pattern) {
        if (preg_match($pattern, $query)) {
            throw new SecurityException('Dangerous SQL pattern detected');
        }
    }
}
```

## XSS Protection

### Output Encoding

All output is automatically encoded:

```php
// In Datatables.php
private function sanitizeOutput($data): array
{
    if (is_array($data)) {
        return array_map([$this, 'sanitizeOutput'], $data);
    }
    
    if (is_string($data)) {
        // HTML entity encoding
        $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Additional XSS protection
        $data = $this->removeXssPatterns($data);
    }
    
    return $data;
}

private function removeXssPatterns(string $input): string
{
    $xssPatterns = [
        '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
        '/javascript:/i',
        '/on\w+\s*=/i',
        '/<iframe\b[^>]*>/i',
        '/<object\b[^>]*>/i',
        '/<embed\b[^>]*>/i',
        '/expression\s*\(/i',
        '/vbscript:/i'
    ];
    
    foreach ($xssPatterns as $pattern) {
        $input = preg_replace($pattern, '', $input);
    }
    
    return $input;
}
```

### JavaScript Sanitization

JavaScript output is properly escaped:

```php
// In Post.php
private function sanitizeJavaScript($value): string
{
    if (is_string($value)) {
        // Escape quotes and special characters
        $value = addslashes($value);
        $value = str_replace(["\r", "\n", "\t"], ['\\r', '\\n', '\\t'], $value);
    }
    
    return $value;
}

private function sanitizeJsonForJavaScript($data): string
{
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
}
```

## CSRF Protection

### Token Validation

All POST requests include CSRF token validation:

```php
// In DatatablesSecurityMiddleware.php
public function handle($request, Closure $next)
{
    if ($request->isMethod('POST')) {
        // Verify CSRF token
        if (!$this->verifyCsrfToken($request)) {
            $this->logSecurityViolation('csrf_token_mismatch', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl()
            ]);
            
            throw new SecurityException('CSRF token mismatch');
        }
    }
    
    return $next($request);
}

private function verifyCsrfToken($request): bool
{
    $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');
    
    if (!$token) {
        return false;
    }
    
    return hash_equals(session()->token(), $token);
}
```

### Automatic Token Inclusion

CSRF tokens are automatically included in AJAX requests:

```javascript
// In generated JavaScript
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
```

## Access Control

### Permission-Based Access

Integration with Laravel's authorization system:

```php
// In your controller
public function index()
{
    // Check table access permission
    $this->authorize('viewAny', User::class);
    
    $this->table->setAccessControl([
        'view' => function($record) {
            return auth()->user()->can('view', $record);
        },
        'edit' => function($record) {
            return auth()->user()->can('update', $record);
        },
        'delete' => function($record) {
            return auth()->user()->can('delete', $record) 
                && $record->id !== auth()->id();
        }
    ]);
    
    $this->table->lists('users', ['name', 'email'], true);
    
    return $this->render();
}
```

### Row-Level Security

Control access at the row level:

```php
$this->table->setRowLevelSecurity([
    'filter' => function($query) {
        // Users can only see records from their department
        if (!auth()->user()->hasRole('admin')) {
            $query->where('department_id', auth()->user()->department_id);
        }
        return $query;
    },
    
    'actions' => [
        'edit' => function($record) {
            return auth()->user()->can('update', $record);
        },
        'delete' => function($record) {
            return auth()->user()->hasRole('admin') 
                && $record->id !== auth()->id();
        }
    ]
]);
```

### Column-Level Security

Hide sensitive columns based on permissions:

```php
$this->table->setColumnSecurity([
    'salary' => function() {
        return auth()->user()->hasPermission('view-salary');
    },
    'ssn' => function() {
        return auth()->user()->hasRole('hr-admin');
    },
    'internal_notes' => function() {
        return auth()->user()->hasRole('manager');
    }
]);
```

## Security Monitoring

### Anomaly Detection

CanvaStack includes built-in anomaly detection:

```php
// In AnomalyDetectionEngine.php
public function detectAnomalies($request): array
{
    $anomalies = [];
    
    // Rate limiting anomalies
    if ($this->isRateLimitExceeded($request)) {
        $anomalies[] = [
            'type' => 'rate_limit_exceeded',
            'severity' => 'high',
            'details' => $this->getRateLimitDetails($request)
        ];
    }
    
    // Suspicious patterns
    if ($this->hasSuspiciousPatterns($request)) {
        $anomalies[] = [
            'type' => 'suspicious_patterns',
            'severity' => 'medium',
            'patterns' => $this->getDetectedPatterns($request)
        ];
    }
    
    // Geographic anomalies
    if ($this->isGeographicAnomaly($request)) {
        $anomalies[] = [
            'type' => 'geographic_anomaly',
            'severity' => 'medium',
            'location' => $this->getLocationDetails($request)
        ];
    }
    
    return $anomalies;
}
```

### Security Logging

Comprehensive security event logging:

```php
private function logSecurityViolation(string $type, array $context = []): void
{
    $logData = [
        'type' => $type,
        'timestamp' => now()->toISOString(),
        'ip_address' => request()->ip(),
        'user_agent' => request()->userAgent(),
        'user_id' => auth()->id(),
        'session_id' => session()->getId(),
        'url' => request()->fullUrl(),
        'method' => request()->method(),
        'context' => $context
    ];
    
    // Log to security channel
    Log::channel('security')->warning('Security violation detected', $logData);
    
    // Send alert for critical violations
    if (in_array($type, $this->criticalViolationTypes)) {
        $this->sendSecurityAlert($logData);
    }
}
```

### Real-time Monitoring

Monitor security events in real-time:

```php
// In SecurityMonitoringService.php
public function monitorSecurityEvents(): void
{
    $recentViolations = $this->getRecentSecurityViolations();
    
    foreach ($recentViolations as $violation) {
        $this->analyzeViolation($violation);
        
        if ($this->isAttackPattern($violation)) {
            $this->triggerSecurityResponse($violation);
        }
    }
}

private function triggerSecurityResponse($violation): void
{
    // Block IP if necessary
    if ($this->shouldBlockIp($violation)) {
        $this->blockIpAddress($violation['ip_address']);
    }
    
    // Send immediate alert
    $this->sendImmediateAlert($violation);
    
    // Log to security incident system
    $this->createSecurityIncident($violation);
}
```

## Configuration

### Security Configuration

Configure security settings in `config/canvastack-security.php`:

```php
return [
    'mode' => env('CANVASTACK_SECURITY_MODE', 'hardened'),
    
    'core' => [
        'input_validation' => [
            'enabled' => true,
            'table_name_max_length' => 64,
            'column_name_max_length' => 64,
            'sql_injection_protection' => true,
            'xss_protection' => true,
        ],
        
        'parameter_binding' => [
            'enabled' => true,
            'force_prepared_statements' => true,
            'validate_field_names' => true,
        ],
        
        'output_encoding' => [
            'enabled' => true,
            'html_entities' => true,
            'javascript_encoding' => true,
        ]
    ],
    
    'monitoring' => [
        'enabled' => true,
        'log_violations' => true,
        'block_suspicious_patterns' => true,
        'rate_limiting' => [
            'enabled' => true,
            'max_attempts' => 100,
            'decay_minutes' => 1
        ]
    ]
];
```

### Environment Variables

Set security-related environment variables:

```env
# Security Mode
CANVASTACK_SECURITY_MODE=hardened

# Advanced Security Features
CANVASTACK_ADVANCED_SECURITY=false

# Monitoring
CANVASTACK_LOG_SECURITY_EVENTS=true
CANVASTACK_BLOCK_SUSPICIOUS_IPS=true

# Rate Limiting
CANVASTACK_RATE_LIMIT_ENABLED=true
CANVASTACK_MAX_REQUESTS_PER_MINUTE=100

# Alerts
CANVASTACK_SECURITY_ALERTS_EMAIL=security@company.com
CANVASTACK_SLACK_WEBHOOK_URL=https://hooks.slack.com/...
```

## Best Practices

### 1. Use Appropriate Security Mode

```php
// Production
'mode' => 'full',

// Staging
'mode' => 'hardened',

// Development (with caution)
'mode' => 'basic',
```

### 2. Implement Proper Authorization

```php
// Always check permissions
public function index()
{
    $this->authorize('viewAny', User::class);
    
    // Set row-level security
    $this->table->setRowLevelSecurity([
        'filter' => function($query) {
            return $this->applyUserScopeFilter($query);
        }
    ]);
}
```

### 3. Validate All Inputs

```php
// Custom validation for specific use cases
$this->table->setCustomValidation([
    'email' => function($value) {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    },
    'phone' => function($value) {
        return preg_match('/^\+?[1-9]\d{1,14}$/', $value);
    }
]);
```

### 4. Monitor Security Events

```php
// Set up monitoring
$this->table->enableSecurityMonitoring([
    'log_all_queries' => app()->environment('production'),
    'alert_on_violations' => true,
    'block_suspicious_ips' => true
]);
```

### 5. Regular Security Audits

```php
// Audit security configuration
public function auditSecurity()
{
    $audit = [
        'security_mode' => config('canvastack-security.mode'),
        'input_validation' => config('canvastack-security.core.input_validation.enabled'),
        'monitoring_enabled' => config('canvastack-security.monitoring.enabled'),
        'rate_limiting' => config('canvastack-security.monitoring.security_middleware.rate_limiting.enabled')
    ];
    
    Log::info('Security audit completed', $audit);
    
    return $audit;
}
```

### 6. Handle Security Exceptions

```php
try {
    $this->table->lists('users', ['name', 'email']);
} catch (SecurityException $e) {
    // Log security violation
    Log::channel('security')->error('Security violation', [
        'exception' => $e->getMessage(),
        'user_id' => auth()->id(),
        'ip' => request()->ip()
    ]);
    
    // Return safe error response
    return response()->json([
        'error' => 'Access denied'
    ], 403);
}
```

### 7. Secure Configuration Management

```php
// Use environment-specific configurations
if (app()->environment('production')) {
    config(['canvastack-security.mode' => 'full']);
    config(['canvastack-security.monitoring.enabled' => true]);
} else {
    config(['canvastack-security.mode' => 'hardened']);
}
```

## Security Checklist

### Pre-deployment Checklist

- [ ] Security mode set to `full` or `hardened` for production
- [ ] All input validation enabled
- [ ] SQL injection protection active
- [ ] XSS protection enabled
- [ ] CSRF protection configured
- [ ] Access control implemented
- [ ] Security monitoring enabled
- [ ] Logging configured properly
- [ ] Rate limiting enabled
- [ ] Security alerts configured

### Regular Maintenance

- [ ] Review security logs weekly
- [ ] Update security patterns monthly
- [ ] Audit user permissions quarterly
- [ ] Test security measures regularly
- [ ] Update dependencies for security patches
- [ ] Review and update whitelist configurations
- [ ] Monitor for new vulnerability patterns

---

## Related Documentation

- [Configuration](../configuration.md) - Security configuration details
- [API Reference](../api/objects.md) - Security-related methods
- [Troubleshooting](troubleshooting.md) - Security issue resolution
- [Performance](performance.md) - Security vs performance considerations