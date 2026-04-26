# Security Best Practices Guide - Group Controller

## Overview

This guide documents security best practices for developing and maintaining the Group Controller and related components. These patterns were established during the comprehensive security audit and fixes implemented in 2026-04-08.

**Audience:** Developers working on GroupController, Privileges trait, MappingPage trait, or similar security-critical components.

**Related Documents:**
- `DEVELOPMENT_GUIDELINES.md` - Mandatory development rules
- `REGRESSION_PREVENTION.md` - Critical behaviors checklist
- `GROUP_PRIVILEGES_BEHAVIOR_GUIDE.md` - Complete behavior documentation

---

## Table of Contents

1. [CSRF Validation Requirements](#csrf-validation-requirements)
2. [Input Validation Patterns](#input-validation-patterns)
3. [Output Escaping Requirements](#output-escaping-requirements)
4. [SQL Injection Prevention](#sql-injection-prevention)
5. [Security Checklist](#security-checklist)

---

## CSRF Validation Requirements

### Overview

Cross-Site Request Forgery (CSRF) attacks force authenticated users to execute unwanted actions. Laravel provides built-in CSRF protection for form submissions, but AJAX endpoints require explicit validation.

### When CSRF Validation is Required

**ALWAYS validate CSRF tokens for:**
- AJAX requests that modify data (POST, PUT, DELETE)
- AJAX requests that perform privileged operations
- Any endpoint that bypasses Laravel's CSRF middleware

**CSRF validation is NOT required for:**
- Normal form submissions (handled by Laravel middleware)
- Read-only GET requests
- Public API endpoints with token authentication

### Pattern 1: AJAX CSRF Validation

**Use Case:** AJAX endpoint that modifies data

```php
/**
 * Store group with CSRF validation for AJAX requests
 * 
 * @param Request $request
 * @return \Illuminate\Http\RedirectResponse|mixed
 * @throws CSRFException
 * @security CSRF validation required for AJAX rolemapage requests
 */
public function store(Request $request): \Illuminate\Http\RedirectResponse|mixed {
    $this->get_session();
    
    // CRITICAL: Validate CSRF for AJAX rolemapage requests
    if ($request->query('rolemapage')) {
        $this->validateAjaxCsrfToken();
        
        // Validate usein parameter
        $usein = $request->query('usein');
        $allowedContexts = ['table_name', 'field_name', 'field_value'];
        
        if (!in_array($usein, $allowedContexts)) {
            throw new ControllerValidationException(
                'Invalid AJAX context',
                ['usein' => $usein, 'allowed' => $allowedContexts]
            );
        }
        
        // Validate POST data
        $postData = $request->all();
        if (empty($postData)) {
            throw new ControllerValidationException('POST data is required');
        }
        
        \Log::info('AJAX rolemapage request validated', [
            'user_id' => $this->session['id'],
            'usein' => $usein
        ]);
        
        return $this->rolepage($postData, $usein);
    }
    
    // Normal form submission continues below...
}
```

### Pattern 2: CSRF Token Validation Method

**Implementation:**

```php
/**
 * Validate CSRF token for AJAX requests
 * 
 * Checks for CSRF token in request body, X-CSRF-TOKEN header, and X-XSRF-TOKEN header.
 * Uses constant-time comparison to prevent timing attacks.
 * 
 * @return void
 * @throws CSRFException If token is missing or invalid
 * @security Uses hash_equals() for constant-time comparison
 */
private function validateAjaxCsrfToken(): void {
    $token = request()->input('_token') 
        ?? request()->header('X-CSRF-TOKEN') 
        ?? request()->header('X-XSRF-TOKEN');
    
    if (!$token || !hash_equals(session()->token(), $token)) {
        \Log::warning('CSRF token validation failed', [
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'route' => request()->path(),
            'user_agent' => request()->userAgent()
        ]);
        
        throw new CSRFException('CSRF token mismatch');
    }
}
```

### Pattern 3: Frontend CSRF Token Inclusion

**JavaScript Example:**

```javascript
// Include CSRF token in AJAX requests
$.ajax({
    url: '/admin/system/group',
    method: 'POST',
    data: {
        _token: $('meta[name="csrf-token"]').attr('content'),
        rolemapage: true,
        usein: 'table_name',
        // ... other data
    },
    success: function(response) {
        // Handle response
    }
});

// Or use headers
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
```

### Security Considerations

**DO:**
- ✅ Use `hash_equals()` for constant-time comparison (prevents timing attacks)
- ✅ Check multiple token sources (body, headers)
- ✅ Log failed validation attempts with context (IP, user agent, route)
- ✅ Throw specific `CSRFException` for clear error handling
- ✅ Validate CSRF token BEFORE processing any request data

**DON'T:**
- ❌ Use `==` or `===` for token comparison (vulnerable to timing attacks)
- ❌ Skip CSRF validation for "internal" AJAX endpoints
- ❌ Log the actual token value (security risk)
- ❌ Process request data before validating CSRF token
- ❌ Return detailed error messages to client (information disclosure)

---

## Input Validation Patterns

### Overview

Input validation prevents malicious data from entering the system. All user input must be validated before use in database queries, file operations, or business logic.

### Pattern 1: Whitelist Validation

**Use Case:** Validate parameter against allowed values

```php
/**
 * Get role page data with input validation
 * 
 * @param mixed $data POST data
 * @param string $usein Context parameter (table_name, field_name, field_value)
 * @return mixed
 * @throws ControllerValidationException
 * @security Validates usein against whitelist to prevent SQL injection
 */
public function rolepage(mixed $data, string $usein): mixed {
    // Validate usein parameter against whitelist
    $allowedContexts = ['table_name', 'field_name', 'field_value'];
    
    if (!in_array($usein, $allowedContexts, true)) {
        \Log::warning('Invalid usein parameter', [
            'usein' => $usein,
            'allowed' => $allowedContexts,
            'user_id' => auth()->id(),
            'ip' => request()->ip()
        ]);
        
        throw new ControllerValidationException(
            'Invalid context parameter',
            ['usein' => $usein, 'allowed' => $allowedContexts]
        );
    }
    
    // Validate data is not empty
    if (empty($data)) {
        throw new ControllerValidationException('Data parameter is required');
    }
    
    // Process validated data
    try {
        return $this->map()::getData($data, $usein);
    } catch (\Exception $e) {
        \Log::error('Failed to get role page data', [
            'error' => $e->getMessage(),
            'usein' => $usein,
            'user_id' => auth()->id()
        ]);
        
        throw new ControllerException(
            'Failed to retrieve role page data: ' . $e->getMessage(),
            ['usein' => $usein]
        );
    }
}
```

### Pattern 2: Type and Range Validation

**Use Case:** Validate ID parameter

```php
/**
 * Update group with validation
 * 
 * @param Request $request
 * @param int $id Group ID
 * @return \Illuminate\Http\RedirectResponse
 * @throws ControllerValidationException
 * @throws ControllerException
 */
public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse {
    $this->get_session();
    
    // Validate ID is positive integer
    if ($id <= 0) {
        throw new ControllerValidationException(
            'Invalid group ID',
            ['id' => $id]
        );
    }
    
    // Check if group exists
    $group = canvastack_query($this->model_table)->find($id);
    
    if (!$group) {
        throw new ControllerException(
            'Group not found',
            ['id' => $id]
        );
    }
    
    // Check root group protection
    if ($group->group_name === 'root' && $this->session['group_name'] !== 'root') {
        \Log::warning('Unauthorized attempt to modify root group', [
            'user_id' => $this->session['id'],
            'user_group' => $this->session['group_name'],
            'target_group_id' => $id
        ]);
        
        throw new PrivilegeException(
            'Only root users can modify the root group',
            ['user_group' => $this->session['group_name']]
        );
    }
    
    // Continue with update...
}
```

### Pattern 3: Request Object Validation

**Use Case:** Replace superglobal access with validated Request object

```php
// ❌ BAD: Direct superglobal access (bypasses validation)
if (!empty($_GET['rolemapage'])) {
    return $this->rolepage($_POST, $_GET['usein']);
}

// ✅ GOOD: Use Request object with validation
if ($request->query('rolemapage')) {
    $this->validateAjaxCsrfToken();
    
    $usein = $request->query('usein');
    $postData = $request->all();
    
    // Validate parameters
    $allowedContexts = ['table_name', 'field_name', 'field_value'];
    if (!in_array($usein, $allowedContexts)) {
        throw new ControllerValidationException('Invalid context');
    }
    
    if (empty($postData)) {
        throw new ControllerValidationException('POST data is required');
    }
    
    return $this->rolepage($postData, $usein);
}
```

### Pattern 4: Array Structure Validation

**Use Case:** Validate complex array structures

```php
/**
 * Process mapping data before insert with validation
 * 
 * @param Request $request
 * @param int|bool $model_id
 * @return void
 * @throws ControllerValidationException
 */
private function mapping_before_insert(Request $request, int|bool $model_id): void {
    $mapPage = $this->map();
    $mapNode = $mapPage::$prefixNode;
    
    // Early exit if no mapping data
    if (!$request->has($mapNode)) {
        \Log::info('No mapping data to process', ['group_id' => $model_id]);
        $this->roles = [];
        $mapPage::insert_process($this->roles, $model_id);
        return;
    }
    
    $data = $request->input($mapNode);
    
    // Validate array structure
    if (!is_array($data)) {
        throw new ControllerValidationException(
            'Mapping data must be an array',
            ['type' => gettype($data)]
        );
    }
    
    // Process validated data...
}
```

### Security Considerations

**DO:**
- ✅ Use whitelist validation (allowed values) instead of blacklist (forbidden values)
- ✅ Validate data type, range, and format
- ✅ Use Laravel's Request object instead of superglobals
- ✅ Throw specific exceptions with context (but not sensitive data)
- ✅ Log validation failures with user context
- ✅ Use strict comparison (`===`, `in_array($val, $arr, true)`)

**DON'T:**
- ❌ Trust any user input without validation
- ❌ Use blacklist validation (easy to bypass)
- ❌ Access `$_GET`, `$_POST`, `$_REQUEST` directly
- ❌ Log sensitive data (passwords, tokens, PII)
- ❌ Return detailed validation errors to client (information disclosure)
- ❌ Skip validation for "internal" or "trusted" sources

---

## Output Escaping Requirements

### Overview

Output escaping prevents Cross-Site Scripting (XSS) attacks by ensuring user-controllable data is properly encoded before display in HTML, JavaScript, or other contexts.

### Pattern 1: HTML Context Escaping

**Use Case:** Escape user-controllable data in HTML

```php
/**
 * Build role box with XSS prevention
 * 
 * @param array $roleData
 * @param string $module_name
 * @param object $module_data
 * @param string $icon
 * @param string|bool $indent
 * @return array
 * @security Escapes module_name to prevent XSS
 */
private function buildRoleBox(
    array $roleData,
    string $module_name,
    object $module_data,
    string $icon,
    string|bool $indent = false
): array {
    // Escape module name before use in HTML
    $escapedModuleName = htmlspecialchars($module_name, ENT_QUOTES, 'UTF-8');
    
    // Build identifier column with escaped content
    $identifierColumn = $this->safeHtml->p(
        $icon . ' ' . $escapedModuleName,
        ['class' => 'identifier']
    );
    
    // Escape module_data->module_name for row ID
    $escapedDataName = htmlspecialchars($module_data->module_name, ENT_QUOTES, 'UTF-8');
    
    $roleData[] = [
        'DT_RowId' => 'row_' . $escapedDataName,
        'identifier' => $identifierColumn,
        // ... other columns
    ];
    
    return $roleData;
}
```

### Pattern 2: Helper Method for Consistent Escaping

**Use Case:** Centralize escaping logic

```php
/**
 * Format and escape module title for safe HTML output
 * 
 * @param string $name Module name
 * @param mixed $data Module data object
 * @return string Escaped and formatted module title
 * @security Always escapes output to prevent XSS
 */
private function formatModuleTitle(string $name, mixed $data): string {
    // Get title from name or data
    $title = $name;
    
    if (is_object($data) && isset($data->module_name)) {
        $title = $data->module_name;
    }
    
    // Escape for HTML context
    return htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
}

// Usage
$escapedTitle = $this->formatModuleTitle($module_name, $module_data);
$html = $this->safeHtml->p($icon . ' ' . $escapedTitle);
```

### Pattern 3: URL Context Escaping

**Use Case:** Build safe URLs with encoded parameters

```php
/**
 * Generate AJAX URL with safe parameter encoding
 * 
 * @param string $usein Context parameter
 * @param bool $return_data
 * @return string|null
 * @throws ControllerValidationException
 * @security Uses http_build_query for proper URL encoding
 */
private function ajax_urli(string $usein, bool $return_data = false): ?string {
    // Validate usein parameter
    $allowedContexts = ['table_name', 'field_name', 'field_value', 'rolemapage'];
    
    if (!in_array($usein, $allowedContexts)) {
        throw new ControllerValidationException(
            'Invalid AJAX context',
            ['usein' => $usein, 'allowed' => $allowedContexts]
        );
    }
    
    // Build URL with proper encoding
    $baseUrl = url()->current();
    
    $params = [
        'rolemapage' => 'true',
        'usein' => $usein,
        '_token' => csrf_token()
    ];
    
    // Use http_build_query for proper encoding
    $queryString = http_build_query($params);
    
    return $baseUrl . '?' . $queryString;
}
```

### Pattern 4: JavaScript Context Escaping

**Use Case:** Escape data for use in JavaScript

```php
// ❌ BAD: Unescaped data in JavaScript
$html = "<script>var moduleName = '{$module_name}';</script>";

// ✅ GOOD: JSON encode for JavaScript context
$escapedData = json_encode($module_name, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$html = "<script>var moduleName = {$escapedData};</script>";

// ✅ BETTER: Use data attributes and parse in JavaScript
$html = "<div data-module-name='" . htmlspecialchars($module_name, ENT_QUOTES, 'UTF-8') . "'></div>";
// JavaScript: const moduleName = element.dataset.moduleName;
```

### Security Considerations

**DO:**
- ✅ Escape ALL user-controllable data before output
- ✅ Use context-appropriate escaping (HTML, URL, JavaScript, CSS)
- ✅ Use `htmlspecialchars()` with `ENT_QUOTES` and `'UTF-8'`
- ✅ Use `http_build_query()` for URL parameters
- ✅ Use `json_encode()` with flags for JavaScript context
- ✅ Escape data from database (it may contain user input)

**DON'T:**
- ❌ Trust data from database without escaping
- ❌ Use `htmlentities()` instead of `htmlspecialchars()` (overkill)
- ❌ Manually build URLs with string concatenation
- ❌ Use `addslashes()` for escaping (insufficient)
- ❌ Skip escaping for "trusted" or "admin" data
- ❌ Double-escape data (check if already escaped)

---

## SQL Injection Prevention

### Overview

SQL injection occurs when user input is included in SQL queries without proper sanitization. Laravel's query builder and Eloquent ORM provide protection, but developers must use them correctly.

### Pattern 1: Use Query Builder with Parameter Binding

**Use Case:** Safe database queries

```php
// ❌ BAD: String concatenation (SQL injection risk)
$query = "SELECT * FROM groups WHERE group_name = '" . $groupName . "'";
$result = DB::select($query);

// ✅ GOOD: Query builder with parameter binding
$result = DB::table('groups')
    ->where('group_name', $groupName)
    ->get();

// ✅ GOOD: Named parameter binding
$result = DB::select('SELECT * FROM groups WHERE group_name = :name', ['name' => $groupName]);

// ✅ GOOD: Positional parameter binding
$result = DB::select('SELECT * FROM groups WHERE group_name = ?', [$groupName]);
```

### Pattern 2: Validate Before Query

**Use Case:** Whitelist validation before database operations

```php
/**
 * Get role page data with SQL injection prevention
 * 
 * @param mixed $data
 * @param string $usein
 * @return mixed
 * @throws ControllerValidationException
 * @security Validates usein against whitelist before passing to getData()
 */
public function rolepage(mixed $data, string $usein): mixed {
    // CRITICAL: Validate usein against whitelist
    $allowedContexts = ['table_name', 'field_name', 'field_value'];
    
    if (!in_array($usein, $allowedContexts, true)) {
        \Log::warning('SQL injection attempt detected', [
            'usein' => $usein,
            'user_id' => auth()->id(),
            'ip' => request()->ip()
        ]);
        
        throw new ControllerValidationException(
            'Invalid context parameter',
            ['usein' => $usein]
        );
    }
    
    // Safe to pass validated parameter to getData()
    try {
        return $this->map()::getData($data, $usein);
    } catch (\Exception $e) {
        \Log::error('Database query failed', [
            'error' => $e->getMessage(),
            'usein' => $usein
        ]);
        
        throw new ControllerException('Failed to retrieve data');
    }
}
```

### Pattern 3: Use Eloquent ORM

**Use Case:** Safe model operations

```php
// ❌ BAD: Raw query with user input
$groupId = $_GET['id'];
$group = DB::select("SELECT * FROM groups WHERE id = $groupId")[0];

// ✅ GOOD: Eloquent find (automatic parameter binding)
$groupId = $request->query('id');
$group = Group::find($groupId);

// ✅ GOOD: Eloquent where (automatic parameter binding)
$groupName = $request->input('group_name');
$group = Group::where('group_name', $groupName)->first();

// ✅ GOOD: Query builder with parameter binding
$group = canvastack_query('groups')
    ->where('id', $groupId)
    ->first();
```

### Pattern 4: Avoid Raw Queries

**Use Case:** When raw queries are necessary

```php
// ❌ BAD: Raw query with string concatenation
$sql = "SELECT * FROM groups WHERE group_name = '" . $groupName . "' AND status = 1";
$results = DB::select($sql);

// ✅ GOOD: Raw query with parameter binding
$sql = "SELECT * FROM groups WHERE group_name = ? AND status = ?";
$results = DB::select($sql, [$groupName, 1]);

// ✅ BETTER: Use query builder instead
$results = DB::table('groups')
    ->where('group_name', $groupName)
    ->where('status', 1)
    ->get();
```

### Security Considerations

**DO:**
- ✅ Use Laravel's query builder or Eloquent ORM
- ✅ Use parameter binding for all user input
- ✅ Validate input against whitelist before queries
- ✅ Use prepared statements for raw queries
- ✅ Log suspicious input patterns
- ✅ Use strict type checking for IDs and numeric values

**DON'T:**
- ❌ Concatenate user input into SQL queries
- ❌ Use `DB::raw()` with user input
- ❌ Trust "sanitized" input (validate instead)
- ❌ Use blacklist filtering (easy to bypass)
- ❌ Skip validation for "internal" queries
- ❌ Use `addslashes()` or `mysql_real_escape_string()` (insufficient)

---

## Security Checklist

### Pre-Development Checklist

Before writing code, ensure you understand:

- [ ] What user input does this code accept?
- [ ] What database operations does this code perform?
- [ ] What output does this code generate?
- [ ] What privileges are required to access this code?
- [ ] What are the security implications of this feature?

### Code Review Checklist

When reviewing code, verify:

**CSRF Protection:**
- [ ] AJAX endpoints validate CSRF tokens
- [ ] CSRF validation uses `hash_equals()` for constant-time comparison
- [ ] Failed CSRF validation is logged with context
- [ ] CSRF exceptions are thrown (not silent failures)

**Input Validation:**
- [ ] All user input is validated before use
- [ ] Whitelist validation is used (not blacklist)
- [ ] Request object is used (not superglobals)
- [ ] Type hints enforce expected types
- [ ] Validation failures throw specific exceptions

**Output Escaping:**
- [ ] All user-controllable output is escaped
- [ ] Context-appropriate escaping is used (HTML, URL, JS)
- [ ] Database data is escaped (may contain user input)
- [ ] SafeHtml is used correctly (not bypassed)

**SQL Injection Prevention:**
- [ ] Query builder or Eloquent is used
- [ ] Parameter binding is used for all user input
- [ ] No string concatenation in SQL queries
- [ ] Raw queries use prepared statements
- [ ] Input is validated before database operations

**Error Handling:**
- [ ] Try-catch blocks wrap risky operations
- [ ] Errors are logged with context (not sensitive data)
- [ ] Specific exceptions are thrown
- [ ] User-facing errors don't reveal system details

**Logging:**
- [ ] Security events are logged (CSRF failures, validation failures)
- [ ] Logs include context (user ID, IP, route)
- [ ] Sensitive data is NOT logged (passwords, tokens)
- [ ] Log levels are appropriate (info, warning, error)

### Testing Checklist

Before deployment, test:

- [ ] CSRF validation blocks requests without tokens
- [ ] Input validation rejects invalid data
- [ ] Output escaping prevents XSS
- [ ] SQL injection attempts are blocked
- [ ] Error messages don't reveal sensitive information
- [ ] Security events are logged correctly

---

## Additional Resources

**Related Documentation:**
- `DEVELOPMENT_GUIDELINES.md` - Mandatory development rules
- `REGRESSION_PREVENTION.md` - Critical behaviors checklist
- `TRANSACTION_MANAGEMENT_GUIDE.md` - Transaction patterns
- `CACHING_STRATEGY_GUIDE.md` - Caching patterns

**External Resources:**
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)

**Training:**
- Complete security training before modifying security-critical code
- Review all security incidents and lessons learned
- Stay updated on new vulnerabilities and attack patterns

---

**Document Version:** 1.0  
**Last Updated:** 2026-04-08  
**Next Review:** 2026-07-08
