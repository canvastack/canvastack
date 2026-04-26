# Development Guidelines - Group Controller & Privileges System

**Document Version:** 1.0  
**Last Updated:** 2026-04-08  
**Applies To:** GroupController.php, Privileges.php, MappingPage.php

---

## ⚠️ CRITICAL: Read Before Modifying

This document contains **mandatory guidelines** for modifying the Group Controller and Privileges system. These guidelines exist to prevent regression of critical bugfixes and security improvements implemented in 2026-04-08.

**If you modify these files without following these guidelines, you WILL break critical functionality.**

---

## 📋 Pre-Modification Checklist

Before making ANY changes to these files, you MUST:

- [ ] Read `GROUP_PRIVILEGES_BEHAVIOR_GUIDE.md` to understand current behavior
- [ ] Read PHPDoc comments in the methods you plan to modify
- [ ] Identify which tests cover the code you're modifying
- [ ] Run existing tests to establish baseline (all must pass)
- [ ] Understand the "clear all" functionality and how it works

---

## 🚫 NEVER Do These Things

### 1. Never Add Early Returns to Data Processing Methods

**❌ WRONG:**
```php
public function mapping_before_insert(Request $requests, object $group): void {
    $reqs = $requests->all();
    $roles = [];
    
    if (!isset($reqs[$this->map()::$prefixNode])) {
        return;  // BUG! This prevents "clear all" from working
    }
    
    // ... build roles ...
    $this->map()->insert_process($roles, $group);
}
```

**✅ CORRECT:**
```php
public function mapping_before_insert(Request $requests, object $group): void {
    $reqs = $requests->all();
    $roles = [];
    
    if (isset($reqs[$this->map()::$prefixNode])) {
        // ... build roles ...
    }
    
    // ALWAYS call insert_process(), even with empty $roles
    // Empty array triggers DELETE of existing records ("clear all")
    $this->map()->insert_process($roles, $group);
}
```

**Why:** Empty data is valid data that means "clear all privileges". Early returns prevent deletion logic from running.

**Affected Methods:**
- `mapping_before_insert()` in MappingPage.php
- `privileges_before_insert()` in Privileges.php (uses "setnull" marker instead)


### 2. Never Skip Transaction Wrapping

**❌ WRONG:**
```php
public function update(Request $request, int $id) {
    $group = Group::find($id);
    $group->update($request->all());
    
    $this->set_data_before_insert($request, $id);
    $this->set_data_after_insert($this->roles);
    
    return redirect()->back();
}
```

**✅ CORRECT:**
```php
public function update(Request $request, int $id) {
    DB::transaction(function() use ($request, $id) {
        $group = Group::find($id);
        $group->update($request->all());
        
        $this->set_data_before_insert($request, $id);
        $this->set_data_after_insert($this->roles);
    });
    
    // Cache invalidation AFTER transaction commits
    $this->invalidateGroupCache();
    
    return redirect()->back();
}
```

**Why:** Without transactions, partial failures leave orphaned records and inconsistent data.

**Affected Methods:**
- `store()` in GroupController.php
- `update()` in GroupController.php
- `privileges_after_insert()` in Privileges.php

### 3. Never Use Superglobals Directly

**❌ WRONG:**
```php
public function store(Request $request) {
    if (isset($_GET['rolemapage'])) {
        $usein = $_GET['usein'];
        $data = $_POST;
        // ...
    }
}
```

**✅ CORRECT:**
```php
public function store(Request $request) {
    if ($request->query('rolemapage')) {
        $usein = $request->query('usein');
        $data = $request->all();
        // ...
    }
}
```

**Why:** Direct superglobal access bypasses Laravel's input validation and sanitization.

**Affected Methods:**
- `store()` in GroupController.php
- Any method that reads request data


### 4. Never Skip Input Validation

**❌ WRONG:**
```php
public function rolepage($data, $usein) {
    return $this->map()::getData($data, $usein, '__node__');
}
```

**✅ CORRECT:**
```php
public function rolepage(mixed $data, string $usein): mixed {
    // Validate usein parameter
    $validUsein = ['table_name', 'field_name', 'field_value'];
    if (!in_array($usein, $validUsein)) {
        throw new ControllerValidationException("Invalid usein parameter: {$usein}");
    }
    
    // Validate data is not empty
    if (empty($data)) {
        throw new ControllerValidationException("Data parameter cannot be empty");
    }
    
    return $this->map()::getData($data, $usein, '__node__');
}
```

**Why:** Unvalidated input can lead to SQL injection, XSS, and other security vulnerabilities.

**Affected Methods:**
- `rolepage()` in MappingPage.php
- `ajax_urli()` in MappingPage.php
- `update()` in GroupController.php
- `set_data_before_insert()` in GroupController.php

### 5. Never Skip Output Escaping

**❌ WRONG:**
```php
private function buildRoleBox(array $roleData, string $module_name, ...): array {
    $output[] = "<td>{$module_name}</td>";  // XSS vulnerability!
    return $output;
}
```

**✅ CORRECT:**
```php
private function buildRoleBox(array $roleData, string $module_name, ...): array {
    $escapedName = htmlspecialchars($module_name, ENT_QUOTES, 'UTF-8');
    $output[] = "<td>{$escapedName}</td>";
    return $output;
}
```

**Why:** Unescaped output allows XSS attacks through malicious module names.

**Affected Methods:**
- `buildRoleBox()` in MappingPage.php
- `formatModuleTitle()` in MappingPage.php
- Any method that generates HTML output


### 6. Never Remove Error Handling

**❌ WRONG:**
```php
private function set_data_before_insert(Request $request, int|bool $model_id = false): void {
    $group = Group::find($model_id);
    $this->privileges_before_insert($request, $group);
    $this->mapping_before_insert($request, $group);
}
```

**✅ CORRECT:**
```php
private function set_data_before_insert(Request $request, int|bool $model_id = false): void {
    try {
        $group = Group::find($model_id);
        
        if (!$group) {
            throw new ControllerException('Group not found');
        }
        
        try {
            $this->privileges_before_insert($request, $group);
        } catch (\Exception $e) {
            \Log::error('Failed to process privileges', ['error' => $e->getMessage()]);
            throw new ControllerException('Failed to process privileges: ' . $e->getMessage());
        }
        
        try {
            $this->mapping_before_insert($request, $group);
        } catch (\Exception $e) {
            \Log::error('Failed to process mapping', ['error' => $e->getMessage()]);
            throw new ControllerException('Failed to process mapping: ' . $e->getMessage());
        }
        
    } catch (\Exception $e) {
        \Log::error('Failed to prepare group data', ['error' => $e->getMessage()]);
        throw $e;
    }
}
```

**Why:** Without error handling, failures are silent and debugging is impossible.

**Affected Methods:**
- `set_data_before_insert()` in GroupController.php
- `set_data_after_insert()` in GroupController.php
- `privileges_after_insert()` in Privileges.php
- `mapping_before_insert()` in MappingPage.php

---

## ✅ ALWAYS Do These Things

### 1. Always Add Type Hints

**All methods must have:**
- Parameter type hints
- Return type hints
- Nullable types where appropriate

```php
// ✅ CORRECT
public function check_data(int $group_id, int $module_id): ?object {
    return canvastack_query($this->table_privilege)
        ->where('group_id', $group_id)
        ->where('module_id', $module_id)
        ->first();
}
```

### 2. Always Add Comprehensive PHPDoc

**All methods must have:**
- @param tags for each parameter
- @return tag for return value
- @throws tags for exceptions
- Description of what the method does
- Examples for complex methods
- @security tag for security-sensitive methods

```php
/**
 * Check if privilege record exists for group and module
 * 
 * @param int $group_id The group ID to check
 * @param int $module_id The module ID to check
 * @return object|null Privilege record if exists, null otherwise
 */
public function check_data(int $group_id, int $module_id): ?object {
    // ...
}
```


### 3. Always Use Constants Instead of Magic Numbers

**❌ WRONG:**
```php
$privileges = ['read' => 8, 'write' => 4, 'modify' => 2, 'delete' => 1];
```

**✅ CORRECT:**
```php
use Canvastack\Canvastack\Library\Constants\PrivilegeConstants;

$privileges = [
    'read' => PrivilegeConstants::READ,
    'write' => PrivilegeConstants::WRITE,
    'modify' => PrivilegeConstants::MODIFY,
    'delete' => PrivilegeConstants::DELETE
];
```

**Available Constants:**
- `PrivilegeConstants::READ` = 8
- `PrivilegeConstants::WRITE` = 4
- `PrivilegeConstants::MODIFY` = 2
- `PrivilegeConstants::DELETE` = 1
- `PrivilegeConstants::INDEX_PRIVILEGE` = 'index_privilege'
- `PrivilegeConstants::ADMIN_PRIVILEGE` = 'admin_privilege'

### 4. Always Invalidate Cache After Modifications

**Cache invalidation must happen AFTER transaction commits:**

```php
public function update(Request $request, int $id) {
    DB::transaction(function() use ($request, $id) {
        // ... update operations ...
    });
    
    // Cache invalidation AFTER commit
    $this->invalidateGroupCache();
    Cache::forget("group_privileges_{$id}");
    Cache::forget("mapping_privileges_{$id}");
    
    return redirect()->back();
}
```

**Available Cache Invalidation Methods:**
- `invalidateGroupCache()` - Clear group list caches
- `invalidateMenuCache(?int $userId)` - Clear menu caches
- `invalidateMappingCache(?int $userId)` - Clear mapping caches
- `canvastack_invalidate_privilege_cache(int $groupId)` - Clear privilege caches

### 5. Always Run Tests Before Committing

**Required tests that MUST pass:**

```bash
# Run all Group Controller tests
php artisan test --filter=GroupController

# Run all Privilege tests
php artisan test --filter=Privilege

# Run all Mapping tests
php artisan test --filter=Mapping

# Run specific critical tests
php artisan test tests/Unit/MappingPageClearAllBugfixTest.php
php artisan test tests/Unit/MappingBeforeInsertOptimizationTest.php
php artisan test tests/Unit/GroupControllerDeadCodeRemovalTest.php
```

**If ANY test fails, DO NOT commit your changes.**


---

## 🎯 Common Modification Scenarios

### Scenario 1: Adding New Privilege Type

**Steps:**
1. Add constant to `PrivilegeConstants.php`
2. Update `PRIVILEGE_NAMES` and `PRIVILEGE_LABELS` arrays
3. Update form UI to include new checkbox
4. Update `privileges_before_insert()` to handle new type
5. Update `privileges_after_insert()` to save new type
6. Add tests for new privilege type
7. Update `GROUP_PRIVILEGES_BEHAVIOR_GUIDE.md`

**Example:**
```php
// In PrivilegeConstants.php
const EXPORT = 16;  // New privilege type

const PRIVILEGE_NAMES = [
    self::READ => 'read',
    self::WRITE => 'insert',
    self::MODIFY => 'update',
    self::DELETE => 'delete',
    self::EXPORT => 'export',  // Add new
];
```

### Scenario 2: Modifying Privilege Processing Logic

**Steps:**
1. Read PHPDoc in `privileges_before_insert()` and `privileges_after_insert()`
2. Understand current "clear all" behavior (setnull marker)
3. Write tests for your new logic FIRST
4. Modify the methods
5. Ensure "clear all" still works (test with empty 'modules' key)
6. Run all existing tests
7. Update PHPDoc if behavior changes

**Critical Check:**
```php
// Test "clear all" still works
$request = Request::create('/groups/1', 'PUT', [
    'group_name' => 'test',
    // NO 'modules' key - should clear all privileges
]);

// After processing, all privileges should be NULL
```

### Scenario 3: Modifying Mapping Processing Logic

**Steps:**
1. Read PHPDoc in `mapping_before_insert()`
2. Understand current "clear all" behavior (empty array triggers DELETE)
3. Write tests for your new logic FIRST
4. Modify the method
5. Ensure "clear all" still works (test with empty 'rolePages' key)
6. Ensure `insert_process()` is ALWAYS called
7. Run all existing tests
8. Update PHPDoc if behavior changes

**Critical Check:**
```php
// Test "clear all" still works
$request = Request::create('/groups/1', 'PUT', [
    'group_name' => 'test',
    // NO 'rolePages' key - should delete all mappings
]);

// After processing, all mappings should be deleted
```


### Scenario 4: Adding New Validation

**Steps:**
1. Identify where validation should occur
2. Add validation logic with clear error messages
3. Throw appropriate exception type:
   - `ControllerValidationException` for invalid input
   - `ControllerException` for business logic errors
   - `PrivilegeException` for access control errors
4. Add logging for validation failures
5. Add tests for valid and invalid cases
6. Update PHPDoc with @throws tag

**Example:**
```php
public function update(Request $request, int $id) {
    // Validate ID
    if ($id <= 0) {
        throw new ControllerValidationException("Invalid group ID: {$id}");
    }
    
    // Validate group exists
    $group = Group::find($id);
    if (!$group) {
        throw new ControllerException("Group not found: {$id}");
    }
    
    // Validate access
    if ($group->group_name === 'root' && auth()->user()->group_name !== 'root') {
        \Log::warning('Unauthorized root group modification attempt', [
            'user_id' => auth()->id(),
            'group_id' => $id
        ]);
        throw new PrivilegeException("Cannot modify root group");
    }
    
    // ... proceed with update ...
}
```

---

## 📚 Required Reading

Before modifying these files, you MUST read:

1. **`GROUP_PRIVILEGES_BEHAVIOR_GUIDE.md`** - Complete behavior documentation
   - Understand Module Privileges vs Mapping Page Privileges
   - Understand "clear all" functionality
   - Review all 15 scenarios in the matrix

2. **PHPDoc in the methods** - Each method has detailed documentation
   - Read @param, @return, @throws tags
   - Read examples in PHPDoc
   - Understand the "why" behind the implementation

3. **Test files** - Understand what behavior is being tested
   - `tests/Unit/MappingPageClearAllBugfixTest.php` - Critical bugfix tests
   - `tests/Unit/MappingBeforeInsertOptimizationTest.php` - Optimization tests
   - `tests/Unit/GroupControllerDeadCodeRemovalTest.php` - Dead code tests

4. **`REGRESSION_PREVENTION.md`** - Critical behaviors checklist
   - List of behaviors that MUST NOT change
   - Common mistakes to avoid

---

## 🔍 Code Review Checklist

When reviewing changes to these files, verify:

- [ ] No early returns added to data processing methods
- [ ] All database operations wrapped in transactions
- [ ] No direct superglobal access ($_GET, $_POST)
- [ ] All input validated before use
- [ ] All output escaped before rendering
- [ ] Error handling present with try-catch blocks
- [ ] Type hints added to all parameters and returns
- [ ] PHPDoc added/updated with @param, @return, @throws
- [ ] Constants used instead of magic numbers
- [ ] Cache invalidation called after successful commits
- [ ] All existing tests still pass
- [ ] New tests added for new functionality
- [ ] "Clear all" functionality still works
- [ ] No regressions in existing behavior

---

## 🆘 Getting Help

If you're unsure about a modification:

1. **Read the documentation first** - Most questions are answered in:
   - `GROUP_PRIVILEGES_BEHAVIOR_GUIDE.md`
   - PHPDoc comments in the code
   - This document

2. **Check the tests** - Tests show expected behavior:
   - Look for similar test cases
   - Run tests to see what breaks

3. **Ask for review** - Before committing:
   - Get code review from team lead
   - Explain what you changed and why
   - Show test results

4. **When in doubt, don't change it** - If you're not sure:
   - Ask first
   - Don't guess
   - Don't "try and see"

---

## 📝 Change Log Template

When modifying these files, document your changes:

```markdown
## [Date] - [Your Name]

### Changed
- Modified `method_name()` in `FileName.php`
- Reason: [Why you made this change]
- Impact: [What behavior changed]

### Added
- Added `new_method()` in `FileName.php`
- Purpose: [What this method does]
- Tests: [Test file name]

### Fixed
- Fixed bug in `method_name()`
- Issue: [What was broken]
- Solution: [How you fixed it]
- Tests: [Test that verifies the fix]

### Tests
- All existing tests: PASS ✅
- New tests added: [List test names]
- Test coverage: [Percentage]
```

---

**Remember: These guidelines exist to protect critical functionality. Follow them strictly.**

**Last Updated:** 2026-04-08  
**Next Review:** 2026-07-08 (quarterly)
