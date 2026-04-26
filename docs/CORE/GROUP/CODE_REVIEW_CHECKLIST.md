# Code Review Checklist - Group Controller Security & Quality

## Overview

This checklist ensures all code changes meet security, quality, and performance standards established during the Group Controller audit fixes. Use this checklist for all code reviews, especially for security-sensitive components.

**Version:** 1.0  
**Last Updated:** 2026-04-08  
**Applies To:** All controllers, traits, and security-sensitive code

---

## How to Use This Checklist

1. **Before Review:** Author completes "Author Self-Review" section
2. **During Review:** Reviewer completes all applicable sections
3. **After Review:** Both parties sign off on checklist
4. **Archive:** Attach completed checklist to pull request

**Review Levels:**
- **Level 1 (Basic):** All items in Security and Code Quality sections
- **Level 2 (Standard):** Level 1 + Transaction Management + Error Handling
- **Level 3 (Comprehensive):** All sections including Performance

---

## Section 1: Security Checklist

### 1.1 CSRF Protection

**Applies to:** All state-changing operations (POST, PUT, DELETE, PATCH)

- [ ] **CSRF-1.1:** All form submissions include `@csrf` directive in Blade templates
- [ ] **CSRF-1.2:** All AJAX requests include CSRF token in request body or headers
- [ ] **CSRF-1.3:** AJAX endpoints explicitly validate CSRF tokens using `validateAjaxCsrfToken()` or equivalent
- [ ] **CSRF-1.4:** CSRF validation failures are logged with user context (user_id, IP, route, user_agent)
- [ ] **CSRF-1.5:** CSRF token comparison uses `hash_equals()` for constant-time comparison
- [ ] **CSRF-1.6:** CSRF validation checks multiple token sources (body, X-CSRF-TOKEN, X-XSRF-TOKEN)

**Red Flags:**
- ❌ State-changing endpoint without CSRF validation
- ❌ AJAX endpoint that bypasses CSRF middleware
- ❌ CSRF token comparison using `==` or `===` (timing attack vulnerability)

**Example (Good):**
```php
public function store(Request $request) {
    if ($request->query('ajax')) {
        $this->validateAjaxCsrfToken();
    }
    // ... rest of implementation
}
```

**Example (Bad):**
```php
public function store(Request $request) {
    if (!empty($_GET['ajax'])) {
        return $this->processAjax($_POST); // NO CSRF VALIDATION!
    }
}
```

---

### 1.2 Input Validation

**Applies to:** All methods that accept user input or external data

- [ ] **INPUT-1.1:** All user input validated against expected types and values
- [ ] **INPUT-1.2:** Validation uses whitelist approach (allow known good, reject everything else)
- [ ] **INPUT-1.3:** Validation occurs on server side (client-side validation is supplementary)
- [ ] **INPUT-1.4:** Laravel's Request object used instead of superglobals ($_GET, $_POST, $_SERVER)
- [ ] **INPUT-1.5:** Invalid input throws specific exceptions (ControllerValidationException)
- [ ] **INPUT-1.6:** Validation failures are logged with context
- [ ] **INPUT-1.7:** Numeric inputs validated for range and type (positive integers, etc.)
- [ ] **INPUT-1.8:** String inputs validated for length and format
- [ ] **INPUT-1.9:** Array inputs validated for structure and content
- [ ] **INPUT-1.10:** File uploads validated for type, size, and content

**Red Flags:**
- ❌ Direct use of `$_GET`, `$_POST`, `$_SERVER`
- ❌ No validation before database operations
- ❌ Blacklist approach (reject known bad, allow everything else)
- ❌ Silent failures (no exception thrown for invalid input)

**Example (Good):**
```php
public function rolepage(mixed $data, string $usein): mixed {
    $allowedContexts = ['table_name', 'field_name', 'field_value'];
    
    if (!in_array($usein, $allowedContexts, true)) {
        throw new ControllerValidationException(
            'Invalid context parameter',
            ['usein' => $usein, 'allowed' => $allowedContexts]
        );
    }
    
    if (empty($data)) {
        throw new ControllerValidationException('Data parameter is required');
    }
    
    return $this->map()::getData($data, $usein);
}
```

**Example (Bad):**
```php
public function rolepage($data, $usein) {
    return $this->map()::getData($data, $usein); // NO VALIDATION!
}
```

---

### 1.3 Output Escaping

**Applies to:** All output that includes user-controllable data

- [ ] **OUTPUT-1.1:** All user-controllable output escaped before display
- [ ] **OUTPUT-1.2:** Blade templates use `{{ }}` for auto-escaping (not `{!! !!}`)
- [ ] **OUTPUT-1.3:** PHP code uses `htmlspecialchars()` with `ENT_QUOTES` and `UTF-8`
- [ ] **OUTPUT-1.4:** JSON output uses `json_encode()` with `JSON_HEX_TAG | JSON_HEX_AMP`
- [ ] **OUTPUT-1.5:** URL parameters use `urlencode()` or `http_build_query()`
- [ ] **OUTPUT-1.6:** JavaScript strings use proper escaping (avoid inline JS with user data)
- [ ] **OUTPUT-1.7:** Database data treated as user-controllable (escaped before output)
- [ ] **OUTPUT-1.8:** SafeHtml content properly marked after escaping

**Red Flags:**
- ❌ User data output without escaping
- ❌ Use of `{!! !!}` with user-controllable data
- ❌ Concatenation of user data into HTML without escaping
- ❌ Inline JavaScript with user data

**Example (Good):**
```php
public function buildRoleBox(array $roleData, string $module_name, ...): array {
    $escapedModuleName = htmlspecialchars($module_name, ENT_QUOTES, 'UTF-8');
    
    $identifier = SafeHtml::create('<td class="identifier">')
        ->concat($escapedModuleName)
        ->concat(SafeHtml::create('</td>'));
    
    return ['identifier' => $identifier];
}
```

**Example (Bad):**
```php
public function buildRoleBox($roleData, $module_name, ...) {
    $identifier = SafeHtml::create('<td class="identifier">')
        ->concat($module_name) // NO ESCAPING!
        ->concat(SafeHtml::create('</td>'));
    
    return ['identifier' => $identifier];
}
```

---

### 1.4 SQL Injection Prevention

**Applies to:** All database operations

- [ ] **SQL-1.1:** Eloquent ORM or Query Builder used for all database operations
- [ ] **SQL-1.2:** No string concatenation in SQL queries
- [ ] **SQL-1.3:** Raw queries use parameterized statements with bindings
- [ ] **SQL-1.4:** User input never directly concatenated into SQL
- [ ] **SQL-1.5:** Table and column names validated against whitelist (if dynamic)
- [ ] **SQL-1.6:** Database errors caught and logged (don't expose to users)

**Red Flags:**
- ❌ String concatenation in SQL queries
- ❌ User input directly in SQL without parameterization
- ❌ Dynamic table/column names without validation
- ❌ Raw SQL without bindings

**Example (Good):**
```php
// Using Query Builder with parameterized query
$groups = DB::table('groups')
    ->where('group_name', '=', $groupName) // Parameterized
    ->where('active', '=', 1)
    ->get();

// Using raw query with bindings
$groups = DB::select('SELECT * FROM groups WHERE group_name = ? AND active = ?', 
    [$groupName, 1]);
```

**Example (Bad):**
```php
// String concatenation (VULNERABLE!)
$groups = DB::select("SELECT * FROM groups WHERE group_name = '$groupName' AND active = 1");
```

---

### 1.5 Authentication & Authorization

**Applies to:** All protected routes and operations

- [ ] **AUTH-1.1:** Authentication middleware applied to all protected routes
- [ ] **AUTH-1.2:** Authorization checks performed before sensitive operations
- [ ] **AUTH-1.3:** Root group protection implemented (non-root cannot modify root)
- [ ] **AUTH-1.4:** User permissions verified before privilege modifications
- [ ] **AUTH-1.5:** Failed authorization attempts logged with context
- [ ] **AUTH-1.6:** Session validation performed for sensitive operations

**Red Flags:**
- ❌ Protected routes without authentication middleware
- ❌ No authorization checks before sensitive operations
- ❌ Non-root users can modify root group
- ❌ No logging of failed authorization attempts

**Example (Good):**
```php
public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse {
    $group = Group::findOrFail($id);
    
    // Authorization check
    if ($group->group_name === 'root' && $this->session['group_name'] !== 'root') {
        \Log::warning('Non-root user attempted to modify root group', [
            'user_id' => $this->session['id'],
            'user_group' => $this->session['group_name'],
            'target_group_id' => $id
        ]);
        
        throw new PrivilegeException('Only root users can modify the root group');
    }
    
    // ... rest of implementation
}
```

---

### 1.6 Security Logging

**Applies to:** All security-sensitive operations

- [ ] **LOG-1.1:** All security events logged (failed auth, CSRF failures, validation errors)
- [ ] **LOG-1.2:** Logs include context (user_id, IP, user_agent, route, timestamp)
- [ ] **LOG-1.3:** Appropriate log levels used (info, warning, error, critical)
- [ ] **LOG-1.4:** Sensitive data excluded from logs (passwords, tokens, credit cards)
- [ ] **LOG-1.5:** Failed operations logged before throwing exceptions
- [ ] **LOG-1.6:** Successful security operations logged for audit trail

**Red Flags:**
- ❌ Security events not logged
- ❌ Logs missing context (who, what, when, where)
- ❌ Sensitive data in logs
- ❌ No audit trail for security operations

**Example (Good):**
```php
\Log::warning('CSRF token validation failed', [
    'user_id' => auth()->id(),
    'ip' => request()->ip(),
    'route' => request()->path(),
    'user_agent' => request()->userAgent(),
    'timestamp' => now()
]);
```

---

## Section 2: Transaction Management Checklist

**Applies to:** All multi-step database operations

- [ ] **TRANS-2.1:** Multi-step operations wrapped in `DB::transaction()` or manual begin/commit/rollback
- [ ] **TRANS-2.2:** Transaction commits only on complete success
- [ ] **TRANS-2.3:** Transaction rolls back on any failure
- [ ] **TRANS-2.4:** Cache invalidation occurs after successful commit (not before)
- [ ] **TRANS-2.5:** Transaction outcomes logged (success and failure)
- [ ] **TRANS-2.6:** Nested transactions handled correctly (savepoints)
- [ ] **TRANS-2.7:** Long-running transactions avoided (performance impact)
- [ ] **TRANS-2.8:** Deadlock scenarios considered and handled

**Red Flags:**
- ❌ Multi-step operations without transactions
- ❌ Cache invalidation before commit
- ❌ No rollback on failure
- ❌ Silent transaction failures

**Example (Good):**
```php
public function store(Request $request): \Illuminate\Http\RedirectResponse {
    DB::beginTransaction();
    
    try {
        // Step 1: Create group
        $this->insert_data($request, false);
        
        if (!$this->stored_id) {
            throw new ControllerException('Failed to create group');
        }
        
        // Step 2: Set privileges
        $this->set_data_before_insert($request, $this->stored_id);
        
        // Step 3: Set mapping
        $this->set_data_after_insert($this->roles);
        
        DB::commit();
        
        // Cache invalidation AFTER commit
        $this->invalidateGroupCache();
        
        \Log::info('Group created successfully', [
            'group_id' => $this->stored_id,
            'created_by' => $this->session['id']
        ]);
        
        return self::redirect("{$this->stored_id}/edit", $request);
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        \Log::error('Failed to create group', [
            'error' => $e->getMessage(),
            'request' => $request->except(['password', '_token'])
        ]);
        
        throw new ControllerException('Failed to create group: ' . $e->getMessage());
    }
}
```

**Example (Bad):**
```php
public function store(Request $request) {
    // NO TRANSACTION!
    $this->insert_data($request, false);
    $this->set_data_before_insert($request, $this->stored_id);
    $this->set_data_after_insert($this->roles);
    
    // If set_data_after_insert fails, group is orphaned!
    
    return self::redirect("{$this->stored_id}/edit", $request);
}
```

---

## Section 3: Error Handling Checklist

**Applies to:** All methods that can fail

- [ ] **ERROR-3.1:** Risky operations wrapped in try-catch blocks
- [ ] **ERROR-3.2:** Specific exceptions thrown (not generic Exception)
- [ ] **ERROR-3.3:** Exception messages are helpful and actionable
- [ ] **ERROR-3.4:** Exceptions include context data (parameters, state)
- [ ] **ERROR-3.5:** Errors logged before throwing exceptions
- [ ] **ERROR-3.6:** Sensitive data excluded from exception messages
- [ ] **ERROR-3.7:** Database errors caught and re-thrown with context
- [ ] **ERROR-3.8:** External API errors handled gracefully
- [ ] **ERROR-3.9:** File operation errors handled (read, write, delete)
- [ ] **ERROR-3.10:** Trait method calls wrapped in try-catch

**Red Flags:**
- ❌ No error handling for risky operations
- ❌ Generic Exception thrown
- ❌ Unhelpful error messages ("Error occurred")
- ❌ Sensitive data in exception messages

**Example (Good):**
```php
private function set_data_before_insert(Request $request, int|bool $model_id = false): void {
    try {
        if (false === $model_id) {
            $getGroup = canvastack_query($this->model_table)
                ->where('group_name', $request->group_name)
                ->first();
            
            if (!$getGroup) {
                throw new ControllerException(
                    'Group not found after creation',
                    ['group_name' => $request->group_name]
                );
            }
            
            $model_id = $getGroup->id;
        }
        
        // Process privileges
        $this->privileges_before_insert($request, $model_id);
        
        // Process mapping
        $this->mapping_before_insert($request, $model_id);
        
    } catch (ControllerException $e) {
        \Log::error('Failed to set data before insert', [
            'error' => $e->getMessage(),
            'model_id' => $model_id,
            'group_name' => $request->group_name ?? 'unknown'
        ]);
        
        throw $e;
    } catch (\Exception $e) {
        \Log::error('Unexpected error in set_data_before_insert', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        throw new ControllerException(
            'Failed to prepare group data: ' . $e->getMessage(),
            ['original_error' => $e->getMessage()]
        );
    }
}
```

---

## Section 4: Code Quality Checklist

**Applies to:** All code

### 4.1 Type Hints

- [ ] **TYPE-4.1:** All parameters have type hints (int, string, bool, array, object, mixed)
- [ ] **TYPE-4.2:** All methods have return type hints (void, int, string, array, object, mixed)
- [ ] **TYPE-4.3:** Nullable types use `?Type` or `Type|null` syntax
- [ ] **TYPE-4.4:** Union types use `Type1|Type2` syntax (PHP 8.0+)
- [ ] **TYPE-4.5:** Array types documented in PHPDoc (`@param array<string, int>`)
- [ ] **TYPE-4.6:** Object types specify class name when possible (`@param Request $request`)

**Red Flags:**
- ❌ Missing parameter type hints
- ❌ Missing return type hints
- ❌ Generic `array` without PHPDoc specification

**Example (Good):**
```php
/**
 * Update group with validation and transaction management
 * 
 * @param Request $request
 * @param int $id
 * @return \Illuminate\Http\RedirectResponse
 * @throws ControllerValidationException
 * @throws ControllerException
 */
public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse {
    // ...
}
```

---

### 4.2 PHPDoc Documentation

- [ ] **DOC-4.1:** All public methods have PHPDoc comments
- [ ] **DOC-4.2:** PHPDoc includes `@param` for all parameters
- [ ] **DOC-4.3:** PHPDoc includes `@return` for return value
- [ ] **DOC-4.4:** PHPDoc includes `@throws` for all exceptions
- [ ] **DOC-4.5:** Complex methods include `@example` usage
- [ ] **DOC-4.6:** Security-sensitive methods include `@security` tag
- [ ] **DOC-4.7:** Performance-critical methods include `@performance` tag
- [ ] **DOC-4.8:** Deprecated methods include `@deprecated` tag with alternative

**Red Flags:**
- ❌ Public methods without PHPDoc
- ❌ Missing `@param` or `@return` tags
- ❌ Missing `@throws` for exceptions
- ❌ No examples for complex methods

**Example (Good):**
```php
/**
 * Validate CSRF token for AJAX requests
 * 
 * Checks for CSRF token in request body, X-CSRF-TOKEN header, and X-XSRF-TOKEN header.
 * Uses constant-time comparison to prevent timing attacks.
 * 
 * @return void
 * @throws CSRFException If token is missing or invalid
 * 
 * @security CRITICAL - This method prevents CSRF attacks on AJAX endpoints
 * 
 * @example
 * // In controller method
 * if ($request->query('ajax')) {
 *     $this->validateAjaxCsrfToken();
 * }
 */
private function validateAjaxCsrfToken(): void {
    // ...
}
```

---

### 4.3 Constants and Magic Numbers

- [ ] **CONST-4.1:** No magic numbers in code (8, 4, 2, 1, etc.)
- [ ] **CONST-4.2:** Constants defined for all numeric flags
- [ ] **CONST-4.3:** Constants use descriptive names (READ, WRITE, MODIFY, DELETE)
- [ ] **CONST-4.4:** Constants grouped in dedicated class (PrivilegeConstants)
- [ ] **CONST-4.5:** String literals extracted to constants if reused
- [ ] **CONST-4.6:** Configuration values use config files (not hardcoded)

**Red Flags:**
- ❌ Magic numbers (8, 4, 2, 1) without constants
- ❌ Repeated string literals
- ❌ Hardcoded configuration values

**Example (Good):**
```php
use App\Library\Constants\PrivilegeConstants;

if ($privilege & PrivilegeConstants::READ) {
    // User has read permission
}

if ($privilege & PrivilegeConstants::WRITE) {
    // User has write permission
}
```

**Example (Bad):**
```php
if ($privilege & 8) { // What does 8 mean?
    // ...
}

if ($privilege & 4) { // What does 4 mean?
    // ...
}
```

---

### 4.4 Code Complexity

- [ ] **COMPLEX-4.1:** Methods under 50 lines (extract if longer)
- [ ] **COMPLEX-4.2:** Cyclomatic complexity under 10 (extract if higher)
- [ ] **COMPLEX-4.3:** Nesting depth under 4 levels (extract if deeper)
- [ ] **COMPLEX-4.4:** No duplicate code (DRY principle)
- [ ] **COMPLEX-4.5:** Complex logic extracted into helper methods
- [ ] **COMPLEX-4.6:** Single Responsibility Principle followed

**Red Flags:**
- ❌ Methods over 100 lines
- ❌ Deeply nested loops (4+ levels)
- ❌ Duplicate code blocks
- ❌ Methods doing multiple unrelated things

**Example (Good):**
```php
public function buildRoleBox(...): array {
    $escapedModuleName = $this->formatModuleTitle($module_name, $module_data);
    $identifier = $this->buildIdentifierCell($escapedModuleName);
    $row = $this->buildRoleRow($module_data, $icon, $indent);
    
    return ['identifier' => $identifier, 'row' => $row];
}

private function formatModuleTitle(string $name, mixed $data): string {
    // Extracted helper method
}

private function buildIdentifierCell(string $name): SafeHtml {
    // Extracted helper method
}
```

---

## Section 5: Performance Checklist

**Applies to:** All database operations and frequently-called methods

### 5.1 Caching

- [ ] **CACHE-5.1:** Frequently-accessed data cached with appropriate TTL
- [ ] **CACHE-5.2:** Cache keys include relevant context (user_id, route, etc.)
- [ ] **CACHE-5.3:** Cache invalidation methods provided
- [ ] **CACHE-5.4:** Cache invalidation called after data modifications
- [ ] **CACHE-5.5:** Cache failures handled gracefully (fallback to database)
- [ ] **CACHE-5.6:** Cache warming considered for critical data

**Red Flags:**
- ❌ Repeated database queries for same data
- ❌ No caching for frequently-accessed data
- ❌ Cache invalidation before transaction commit
- ❌ No fallback if cache fails

**Example (Good):**
```php
public function index(): \Illuminate\View\View {
    $cacheKey = 'groups_list_' . $this->session['group_name'];
    
    $groups = Cache::remember($cacheKey, 300, function () {
        return canvastack_query($this->model_table)
            ->where('active', 1)
            ->get();
    });
    
    return view('admin.groups.index', compact('groups'));
}

private function invalidateGroupCache(): void {
    Cache::forget('groups_list_' . $this->session['group_name']);
}
```

---

### 5.2 Query Optimization

- [ ] **QUERY-5.1:** Eager loading used to prevent N+1 queries
- [ ] **QUERY-5.2:** Only required columns selected (not `SELECT *`)
- [ ] **QUERY-5.3:** Indexes exist for frequently-queried columns
- [ ] **QUERY-5.4:** Pagination used for large result sets
- [ ] **QUERY-5.5:** Query count monitored (use Laravel Debugbar)
- [ ] **QUERY-5.6:** Batch operations used instead of loops

**Red Flags:**
- ❌ N+1 query pattern (query in loop)
- ❌ `SELECT *` for large tables
- ❌ No pagination for large result sets
- ❌ Individual inserts/updates in loops

**Example (Good):**
```php
// Eager load relationships
$groups = Group::with(['privileges', 'mappings'])->get();

// Select only required columns
$groups = Group::select(['id', 'group_name', 'active'])->get();

// Batch insert
DB::table('privileges')->insert($privilegesArray);
```

---

### 5.3 Algorithm Efficiency

- [ ] **ALGO-5.1:** Early exit conditions implemented
- [ ] **ALGO-5.2:** Efficient data structures used (arrays, collections)
- [ ] **ALGO-5.3:** Unnecessary loops avoided
- [ ] **ALGO-5.4:** Array operations optimized (reduce iterations)
- [ ] **ALGO-5.5:** String concatenation optimized (use array join)

**Red Flags:**
- ❌ No early exit for empty data
- ❌ Multiple iterations over same data
- ❌ Inefficient array building
- ❌ String concatenation in loops

**Example (Good):**
```php
public function mapping_before_insert(Request $request, int $model_id): void {
    $mapPage = $this->map();
    $mapNode = $mapPage::$prefixNode;
    
    // Early exit if no mapping data
    if (!$request->has($mapNode) || empty($request->input($mapNode))) {
        $this->roles = [];
        $mapPage::insert_process($this->roles, $model_id);
        return;
    }
    
    // ... process mapping data
}
```

---

## Section 6: Testing Checklist

**Applies to:** All code changes

- [ ] **TEST-6.1:** Unit tests written for new methods
- [ ] **TEST-6.2:** Security tests written for security-sensitive code
- [ ] **TEST-6.3:** Edge cases tested (empty input, null, invalid types)
- [ ] **TEST-6.4:** Error conditions tested (exceptions thrown)
- [ ] **TEST-6.5:** Integration tests for multi-step operations
- [ ] **TEST-6.6:** Preservation tests verify no regressions
- [ ] **TEST-6.7:** All tests pass before merge
- [ ] **TEST-6.8:** Test coverage above 80% for new code

**Red Flags:**
- ❌ No tests for new code
- ❌ Security-sensitive code without security tests
- ❌ Edge cases not tested
- ❌ Failing tests merged

---

## Review Sign-Off

### Author Self-Review

**Author:** ___________________________  
**Date:** ___________________________  
**Branch:** ___________________________  
**Pull Request:** ___________________________

- [ ] I have completed all applicable checklist items
- [ ] I have written tests for all new code
- [ ] All tests pass locally
- [ ] I have updated documentation
- [ ] I have reviewed my own code for security issues

**Author Signature:** ___________________________

---

### Reviewer Sign-Off

**Reviewer:** ___________________________  
**Date:** ___________________________  
**Review Level:** [ ] Level 1  [ ] Level 2  [ ] Level 3

- [ ] I have reviewed all applicable checklist items
- [ ] I have verified all tests pass
- [ ] I have checked for security vulnerabilities
- [ ] I have verified no regressions
- [ ] I approve this code for merge

**Reviewer Signature:** ___________________________

---

## Appendix: Quick Reference

### Security Red Flags (Immediate Rejection)
1. Direct use of `$_GET`, `$_POST`, `$_SERVER`
2. String concatenation in SQL queries
3. User data output without escaping
4. AJAX endpoints without CSRF validation
5. Multi-step operations without transactions
6. No error handling for risky operations

### Code Quality Red Flags (Request Changes)
1. Missing type hints
2. Missing PHPDoc
3. Magic numbers without constants
4. Methods over 100 lines
5. Cyclomatic complexity over 15
6. No tests for new code

### Performance Red Flags (Optimization Needed)
1. N+1 query pattern
2. No caching for frequently-accessed data
3. `SELECT *` for large tables
4. Individual inserts/updates in loops
5. No early exit conditions

---

**Checklist Version:** 1.0  
**Last Updated:** 2026-04-08  
**Next Review:** 2026-07-08
