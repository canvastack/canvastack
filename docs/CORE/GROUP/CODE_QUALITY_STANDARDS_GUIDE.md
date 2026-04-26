# Code Quality Standards Guide - Group Controller

## Overview

This guide documents code quality standards for developing and maintaining the Group Controller and related components. These standards were established during the comprehensive code quality improvements implemented in 2026-04-08.

**Audience:** Developers working on GroupController, Privileges trait, MappingPage trait, or any security-critical component.

**Related Documents:**
- `SECURITY_BEST_PRACTICES.md` - Security patterns
- `TRANSACTION_MANAGEMENT_GUIDE.md` - Transaction patterns
- `DEVELOPMENT_GUIDELINES.md` - Mandatory development rules

---

## Table of Contents

1. [Type Hint Requirements](#type-hint-requirements)
2. [PHPDoc Requirements](#phpdoc-requirements)
3. [Constant Usage (PrivilegeConstants)](#constant-usage-privilegeconstants)
4. [Error Handling Patterns](#error-handling-patterns)
5. [Code Quality Checklist](#code-quality-checklist)

---

## Type Hint Requirements

### Overview

Type hints improve code safety, enable IDE support, and catch errors at compile time. PHP 7.0+ supports parameter and return type hints.

### When to Use Type Hints

**ALWAYS use type hints for:**
- All method parameters
- All method return values
- All class properties (PHP 7.4+)

**Type hints are NOT optional** - they are mandatory for all new code and refactored code.


### Pattern 1: Parameter Type Hints

**Use Case:** Enforce parameter types

```php
// ❌ BAD: No type hints
public function store($request) {
    // What type is $request? IDE doesn't know
}

// ✅ GOOD: Parameter type hints
public function store(Request $request): \Illuminate\Http\RedirectResponse {
    // IDE knows $request is Request object
    // Type errors caught at runtime
}

// ✅ GOOD: Multiple parameter types
public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse {
    // $request must be Request, $id must be int
}

// ✅ GOOD: Union types (PHP 8.0+)
private function set_data_before_insert(Request $request, int|bool $model_id = false): void {
    // $model_id can be int or bool
}

// ✅ GOOD: Nullable types
private function get_current_group(int $id): ?object {
    // Returns object or null
}
```

### Pattern 2: Return Type Hints

**Use Case:** Enforce return types

```php
// ❌ BAD: No return type hint
public function index() {
    return view('admin.system.group.index');
}

// ✅ GOOD: Return type hint
public function index(): \Illuminate\View\View {
    return view('admin.system.group.index');
}

// ✅ GOOD: Multiple return types (PHP 8.0+)
public function store(Request $request): \Illuminate\Http\RedirectResponse|mixed {
    if ($request->query('rolemapage')) {
        return $this->rolepage($request->all(), $request->query('usein'));
    }
    return redirect()->route('admin.system.group.edit', $this->stored_id);
}

// ✅ GOOD: Void return type
private function invalidateGroupCache(): void {
    Cache::forget('group_list_all');
}
```

### Pattern 3: Property Type Hints (PHP 7.4+)

**Use Case:** Enforce property types

```php
class GroupController extends Controller {
    // ❌ BAD: No property type hints
    protected $model_table;
    protected $stored_id;
    
    // ✅ GOOD: Property type hints
    protected string $model_table = 'groups';
    protected ?int $stored_id = null;
    protected array $roles = [];
    protected object $session;
}
```

### Pattern 4: Array Type Hints with PHPDoc

**Use Case:** Document array structure

```php
/**
 * Build role box with type hints
 * 
 * @param array<string, mixed> $roleData Array of role data
 * @param string $module_name Module name
 * @param object $module_data Module data object
 * @param string $icon Icon HTML
 * @param string|bool $indent Indentation or false
 * @return array<int, array<string, mixed>> Array of role rows
 */
private function buildRoleBox(
    array $roleData,
    string $module_name,
    object $module_data,
    string $icon,
    string|bool $indent = false
): array {
    // Implementation
}
```

### Type Hint Best Practices

**DO:**
- ✅ Use specific types (Request, int, string, bool, array, object)
- ✅ Use nullable types (?int, ?string) when null is valid
- ✅ Use union types (int|bool) when multiple types are valid
- ✅ Use void for methods that don't return values
- ✅ Use mixed when type is truly unknown (rare)
- ✅ Document array structures in PHPDoc

**DON'T:**
- ❌ Skip type hints (they are mandatory)
- ❌ Use mixed unnecessarily (be specific)
- ❌ Use object when specific class is known
- ❌ Use array when collection is more appropriate

---

## PHPDoc Requirements

### Overview

PHPDoc comments document method behavior, parameters, return values, exceptions, and usage examples. They improve code understanding and enable IDE support.

### When to Use PHPDoc

**ALWAYS document:**
- All public methods
- All protected methods
- All private methods (if complex)
- All class properties
- All constants

### Pattern 1: Basic PHPDoc

**Use Case:** Document simple methods

```php
/**
 * Get current session data
 * 
 * @return void
 */
protected function get_session(): void {
    $this->session = canvastack_get_session();
}

/**
 * Invalidate all group-related caches
 * 
 * @return void
 */
private function invalidateGroupCache(): void {
    Cache::forget('group_list_all');
    Cache::tags(['group_list'])->flush();
}
```

### Pattern 2: PHPDoc with Parameters

**Use Case:** Document method parameters

```php
/**
 * Update group with validation and transaction management
 * 
 * @param Request $request HTTP request object containing group data
 * @param int $id Group ID to update
 * @return \Illuminate\Http\RedirectResponse Redirect to edit page
 * @throws ControllerValidationException If ID is invalid or group not found
 * @throws PrivilegeException If non-root user tries to modify root group
 * @throws ControllerException If update fails
 */
public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse {
    // Implementation
}
```

### Pattern 3: PHPDoc with Security Tags

**Use Case:** Document security considerations

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
 * @security Logs failed validation attempts with IP and user agent
 */
private function validateAjaxCsrfToken(): void {
    // Implementation
}
```

### Pattern 4: PHPDoc with Performance Tags

**Use Case:** Document performance considerations

```php
/**
 * Get menu data with caching
 * 
 * Loads active modules and builds hierarchical menu structure (4 levels deep).
 * Results are cached for 1 hour to reduce database queries.
 * 
 * @return object Menu structure with modules hierarchy
 * @performance Caches menu data for 1 hour (3600 seconds)
 * @performance Reduces N+1 queries by eager loading relationships
 */
public function get_menu(): object {
    // Implementation
}
```

### Pattern 5: PHPDoc with Examples

**Use Case:** Document usage examples

```php
/**
 * Get role page data with input validation
 * 
 * Returns data for table names, field names, or field values based on usein parameter.
 * 
 * @param mixed $data POST data containing query parameters
 * @param string $usein Context parameter (table_name, field_name, field_value)
 * @return mixed Query results based on usein context
 * @throws ControllerValidationException If usein is invalid or data is empty
 * @throws ControllerException If database query fails
 * @security Validates usein against whitelist to prevent SQL injection
 * 
 * @example
 * // Get table names
 * $tables = $this->rolepage($postData, 'table_name');
 * 
 * @example
 * // Get field names for a table
 * $fields = $this->rolepage(['table' => 'users'], 'field_name');
 */
public function rolepage(mixed $data, string $usein): mixed {
    // Implementation
}
```

### Pattern 6: PHPDoc with Complex Array Structures

**Use Case:** Document array structures

```php
/**
 * Process mapping data before insert
 * 
 * Parses __node__ data from request and builds roles array for insert_process().
 * 
 * @param Request $request HTTP request containing mapping data
 * @param int|bool $model_id Group ID for update, false for insert
 * @return void
 * @throws ControllerValidationException If mapping data structure is invalid
 * @throws ControllerException If processing fails
 * 
 * @internal Sets $this->roles array with structure:
 * [
 *   [
 *     'group_id' => int,
 *     'module_id' => int,
 *     'table_name' => string,
 *     'field_name' => string,
 *     'field_value' => string
 *   ],
 *   ...
 * ]
 */
private function mapping_before_insert(Request $request, int|bool $model_id): void {
    // Implementation
}
```

### PHPDoc Best Practices

**DO:**
- ✅ Write clear, concise descriptions
- ✅ Document all parameters with types and descriptions
- ✅ Document return values with types and descriptions
- ✅ Document all exceptions that can be thrown
- ✅ Add @security tags for security-critical code
- ✅ Add @performance tags for performance-critical code
- ✅ Add @example tags for complex methods
- ✅ Document array structures with @internal or @param

**DON'T:**
- ❌ Skip PHPDoc (it is mandatory)
- ❌ Write obvious comments ("Get ID" for getId())
- ❌ Copy-paste PHPDoc without updating
- ❌ Document implementation details (focus on behavior)
- ❌ Use vague descriptions ("Does stuff")

---

## Constant Usage (PrivilegeConstants)

### Overview

Constants replace magic numbers with named values, improving code readability and maintainability. The PrivilegeConstants class defines privilege flags used throughout the system.

### PrivilegeConstants Class

**Location:** `vendor/canvastack/canvastack/src/Library/Constants/PrivilegeConstants.php`

```php
namespace Canvastack\Canvastack\Library\Constants;

class PrivilegeConstants {
    // Privilege flags (bitwise)
    public const READ = 8;
    public const WRITE = 4;
    public const MODIFY = 2;
    public const DELETE = 1;
    
    // Privilege names
    private const NAMES = [
        self::READ => 'read',
        self::WRITE => 'write',
        self::MODIFY => 'modify',
        self::DELETE => 'delete'
    ];
    
    // Privilege labels
    private const LABELS = [
        self::READ => 'Read',
        self::WRITE => 'Insert',
        self::MODIFY => 'Update',
        self::DELETE => 'Delete'
    ];
    
    /**
     * Get privilege name by value
     * 
     * @param int $value Privilege value (8, 4, 2, 1)
     * @return string|null Privilege name or null if invalid
     */
    public static function getName(int $value): ?string {
        return self::NAMES[$value] ?? null;
    }
    
    /**
     * Get privilege label by value
     * 
     * @param int $value Privilege value (8, 4, 2, 1)
     * @return string|null Privilege label or null if invalid
     */
    public static function getLabel(int $value): ?string {
        return self::LABELS[$value] ?? null;
    }
    
    /**
     * Check if privilege value is valid
     * 
     * @param int $value Privilege value to check
     * @return bool True if valid, false otherwise
     */
    public static function isValid(int $value): bool {
        return isset(self::NAMES[$value]);
    }
    
    /**
     * Check if user has specific privilege
     * 
     * @param int $userPrivilege User's privilege value
     * @param int $requiredPrivilege Required privilege value
     * @return bool True if user has privilege, false otherwise
     */
    public static function hasPrivilege(int $userPrivilege, int $requiredPrivilege): bool {
        return ($userPrivilege & $requiredPrivilege) === $requiredPrivilege;
    }
}
```

### Pattern 1: Using Constants Instead of Magic Numbers

**Use Case:** Replace magic numbers with constants

```php
// ❌ BAD: Magic numbers
if ($privilege === 8) {
    // What does 8 mean?
}

if ($privilege === 4) {
    // What does 4 mean?
}

// ✅ GOOD: Named constants
use Canvastack\Canvastack\Library\Constants\PrivilegeConstants;

if ($privilege === PrivilegeConstants::READ) {
    // Clear: checking for READ privilege
}

if ($privilege === PrivilegeConstants::WRITE) {
    // Clear: checking for WRITE privilege
}
```

### Pattern 2: Using Helper Methods

**Use Case:** Get privilege names and labels

```php
use Canvastack\Canvastack\Library\Constants\PrivilegeConstants;

// Get privilege name
$name = PrivilegeConstants::getName(8);  // 'read'
$name = PrivilegeConstants::getName(4);  // 'write'

// Get privilege label
$label = PrivilegeConstants::getLabel(8);  // 'Read'
$label = PrivilegeConstants::getLabel(4);  // 'Insert'

// Validate privilege value
if (PrivilegeConstants::isValid($value)) {
    // Valid privilege
}

// Check if user has privilege
if (PrivilegeConstants::hasPrivilege($userPrivilege, PrivilegeConstants::READ)) {
    // User has READ privilege
}
```

### Pattern 3: Bitwise Operations with Constants

**Use Case:** Combine multiple privileges

```php
use Canvastack\Canvastack\Library\Constants\PrivilegeConstants;

// Combine privileges (bitwise OR)
$privilege = PrivilegeConstants::READ | PrivilegeConstants::WRITE;
// $privilege = 12 (8 + 4)

// Check if user has specific privilege (bitwise AND)
if (($userPrivilege & PrivilegeConstants::READ) === PrivilegeConstants::READ) {
    // User has READ privilege
}

// Remove privilege (bitwise AND NOT)
$privilege = $privilege & ~PrivilegeConstants::WRITE;
// Removes WRITE privilege
```

### Pattern 4: Loop Through All Privileges

**Use Case:** Process all privilege types

```php
use Canvastack\Canvastack\Library\Constants\PrivilegeConstants;

$privileges = [
    PrivilegeConstants::READ,
    PrivilegeConstants::WRITE,
    PrivilegeConstants::MODIFY,
    PrivilegeConstants::DELETE
];

foreach ($privileges as $privilege) {
    $name = PrivilegeConstants::getName($privilege);
    $label = PrivilegeConstants::getLabel($privilege);
    
    echo "{$label} ({$name}): {$privilege}\n";
}
```

### Constant Usage Best Practices

**DO:**
- ✅ Use PrivilegeConstants for all privilege values
- ✅ Use helper methods (getName, getLabel, isValid)
- ✅ Import constants at top of file
- ✅ Use constants in comparisons and assignments
- ✅ Document constant usage in PHPDoc

**DON'T:**
- ❌ Use magic numbers (8, 4, 2, 1) directly
- ❌ Hardcode privilege values in code
- ❌ Create duplicate constants in other files
- ❌ Modify constant values (they are immutable)

---

## Error Handling Patterns

### Overview

Proper error handling ensures failures are caught, logged, and reported appropriately. All risky operations should be wrapped in try-catch blocks.

### Pattern 1: Try-Catch with Specific Exceptions

**Use Case:** Handle different error types differently

```php
try {
    $this->validateInput($request);
    $this->processData($request);
    
} catch (ControllerValidationException $e) {
    // Validation error - user input issue
    \Log::warning('Validation error', [
        'error' => $e->getMessage(),
        'context' => $e->getContext()
    ]);
    
    return redirect()->back()
        ->withErrors($e->getMessage())
        ->withInput();
        
} catch (PrivilegeException $e) {
    // Permission error - access denied
    \Log::warning('Permission denied', [
        'error' => $e->getMessage(),
        'user_id' => auth()->id()
    ]);
    
    abort(403, $e->getMessage());
    
} catch (ControllerException $e) {
    // Business logic error
    \Log::error('Controller error', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    return redirect()->back()
        ->withErrors('An error occurred. Please try again.');
        
} catch (\Exception $e) {
    // Unexpected error
    \Log::error('Unexpected error', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    abort(500, 'An unexpected error occurred.');
}
```

### Pattern 2: Try-Catch with Logging

**Use Case:** Log errors with context

```php
try {
    DB::transaction(function() use ($request, $id) {
        $this->update_data($request, $id);
        $this->updatePrivileges($id);
    });
    
    \Log::info('Group updated successfully', [
        'group_id' => $id,
        'updated_by' => auth()->id()
    ]);
    
} catch (\Exception $e) {
    \Log::error('Failed to update group', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'group_id' => $id,
        'user_id' => auth()->id(),
        'request' => $request->except(['password', '_token'])
    ]);
    
    throw new ControllerException(
        'Failed to update group: ' . $e->getMessage(),
        ['original_error' => $e->getMessage()]
    );
}
```

### Pattern 3: Try-Catch with Cleanup

**Use Case:** Ensure cleanup on error

```php
$tempFile = null;

try {
    $tempFile = $this->createTempFile();
    $this->processFile($tempFile);
    $this->importData($tempFile);
    
} catch (\Exception $e) {
    \Log::error('File processing failed', [
        'error' => $e->getMessage(),
        'file' => $tempFile
    ]);
    
    throw new ControllerException('Failed to process file');
    
} finally {
    // Cleanup always runs (success or failure)
    if ($tempFile && file_exists($tempFile)) {
        unlink($tempFile);
    }
}
```

### Pattern 4: Validation Before Risky Operations

**Use Case:** Fail fast with validation

```php
/**
 * Update group with validation before risky operations
 */
public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse {
    $this->get_session();
    
    // VALIDATE FIRST (fail fast, avoid unnecessary operations)
    if ($id <= 0) {
        throw new ControllerValidationException('Invalid group ID');
    }
    
    $group = Group::find($id);
    if (!$group) {
        throw new ControllerException('Group not found');
    }
    
    if ($group->group_name === 'root' && $this->session['group_name'] !== 'root') {
        throw new PrivilegeException('Cannot modify root group');
    }
    
    // THEN PERFORM RISKY OPERATIONS
    try {
        DB::transaction(function() use ($request, $id) {
            $this->update_data($request, $id);
            $this->updatePrivileges($id);
        });
        
        $this->invalidateGroupCache();
        
        return redirect()->route('admin.system.group.edit', $id);
        
    } catch (\Exception $e) {
        \Log::error('Update failed', ['error' => $e->getMessage()]);
        throw new ControllerException('Failed to update group');
    }
}
```

### Error Handling Best Practices

**DO:**
- ✅ Wrap risky operations in try-catch
- ✅ Catch specific exceptions first, generic last
- ✅ Log errors with context (user ID, request data)
- ✅ Exclude sensitive data from logs (passwords, tokens)
- ✅ Throw specific exceptions (ControllerValidationException, PrivilegeException)
- ✅ Use finally for cleanup operations
- ✅ Validate before risky operations (fail fast)

**DON'T:**
- ❌ Catch exceptions without logging
- ❌ Log sensitive data (passwords, tokens, PII)
- ❌ Return detailed error messages to client (information disclosure)
- ❌ Swallow exceptions silently (catch without re-throw)
- ❌ Use generic Exception catch for everything
- ❌ Skip validation to "save time"

---

## Code Quality Checklist

### Pre-Development Checklist

Before writing code, ensure:

- [ ] I understand the requirements
- [ ] I know what exceptions can be thrown
- [ ] I know what data needs validation
- [ ] I know what operations need transactions
- [ ] I know what caches need invalidation

### Implementation Checklist

When writing code, ensure:

**Type Hints:**
- [ ] All parameters have type hints
- [ ] All return values have type hints
- [ ] All properties have type hints (PHP 7.4+)
- [ ] Nullable types are used when appropriate
- [ ] Union types are used when appropriate

**PHPDoc:**
- [ ] All methods have PHPDoc comments
- [ ] All parameters are documented
- [ ] All return values are documented
- [ ] All exceptions are documented
- [ ] Security considerations are documented
- [ ] Performance considerations are documented
- [ ] Examples are provided for complex methods

**Constants:**
- [ ] PrivilegeConstants used instead of magic numbers
- [ ] Helper methods used (getName, getLabel, isValid)
- [ ] Constants imported at top of file
- [ ] Constant usage documented in PHPDoc

**Error Handling:**
- [ ] Risky operations wrapped in try-catch
- [ ] Specific exceptions caught first
- [ ] Errors logged with context
- [ ] Sensitive data excluded from logs
- [ ] Specific exceptions thrown
- [ ] Cleanup operations in finally block
- [ ] Validation before risky operations

### Code Review Checklist

When reviewing code, verify:

- [ ] All type hints are present and correct
- [ ] All PHPDoc is present and accurate
- [ ] Constants used instead of magic numbers
- [ ] Error handling is comprehensive
- [ ] Logging is appropriate
- [ ] No sensitive data in logs
- [ ] Code is readable and maintainable

---

## Additional Resources

**Related Documentation:**
- `SECURITY_BEST_PRACTICES.md` - Security patterns
- `TRANSACTION_MANAGEMENT_GUIDE.md` - Transaction patterns
- `CACHING_STRATEGY_GUIDE.md` - Caching patterns
- `DEVELOPMENT_GUIDELINES.md` - Mandatory development rules

**External Resources:**
- [PHP Type Declarations](https://www.php.net/manual/en/language.types.declarations.php)
- [PHPDoc Documentation](https://docs.phpdoc.org/)
- [Clean Code Principles](https://github.com/jupeter/clean-code-php)

**Training:**
- Review code quality standards regularly
- Participate in code reviews
- Learn from code quality issues

---

**Document Version:** 1.0  
**Last Updated:** 2026-04-08  
**Next Review:** 2026-07-08
