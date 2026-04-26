# Regression Prevention Checklist

**Document Version:** 1.0  
**Last Updated:** 2026-04-08  
**Purpose:** Prevent regression of critical bugfixes and security improvements

---

## ⚠️ CRITICAL BEHAVIORS - DO NOT BREAK

These behaviors are **MANDATORY** and must be preserved in all future modifications. Breaking any of these will cause critical bugs or security vulnerabilities.

---

## 🔒 Security Behaviors (MUST NOT CHANGE)

### 1. CSRF Validation for AJAX Requests

**Behavior:** All AJAX requests to `rolemapage` endpoint must validate CSRF token

**Test:** `tests/Unit/GroupControllerCSRFValidationTest.php`

**Verification:**
```php
// This MUST fail with 419 status
$response = $this->post('/groups', [
    'rolemapage' => true,
    'usein' => 'table_name'
    // NO CSRF token
]);
// Expected: 419 Unauthorized
```

**Why Critical:** Without CSRF validation, attackers can perform unauthorized actions

**Bugfix Date:** 2026-04-08 (Issue #1, CVSS 8.8)

---

### 2. Input Validation in rolepage()

**Behavior:** `rolepage()` must validate `usein` parameter against whitelist

**Test:** `tests/Unit/RolepageSQLInjectionPreventionTest.php`

**Verification:**
```php
// This MUST throw ControllerValidationException
$this->rolepage($data, "table'; DROP TABLE users--");
// Expected: ControllerValidationException
```

**Why Critical:** Prevents SQL injection attacks

**Bugfix Date:** 2026-04-08 (Issue #16, CVSS 9.8)

---

### 3. Output Escaping in buildRoleBox()

**Behavior:** All user-controllable output must be escaped with `htmlspecialchars()`

**Test:** `tests/Unit/BuildRoleBoxXSSPreventionTest.php`

**Verification:**
```php
// This MUST escape the script tag
$output = $this->buildRoleBox($data, "<script>alert('XSS')</script>", ...);
// Expected: Output contains "&lt;script&gt;" not "<script>"
```

**Why Critical:** Prevents XSS attacks

**Bugfix Date:** 2026-04-08 (Issue #17, CVSS 7.3)

---


## 💾 Data Integrity Behaviors (MUST NOT CHANGE)

### 4. "Clear All" Functionality - Module Privileges

**Behavior:** When user unchecks all module checkboxes, all privileges must be set to NULL

**Test:** `tests/Unit/MappingPageClearAllBugfixTest.php::test_clear_both_module_and_mapping_privileges`

**Verification:**
```php
// Submit form WITHOUT 'modules' key
$request = Request::create('/groups/1', 'PUT', [
    'group_name' => 'test',
    // NO 'modules' key
]);

// After processing:
// - Module privilege records MUST exist (not deleted)
// - admin_privilege MUST be NULL
// - index_privilege MUST be NULL
```

**Why Critical:** Users must be able to remove all privileges from a group

**Bugfix Date:** 2026-04-08 (Consistent with mapping privileges)

---

### 5. "Clear All" Functionality - Mapping Page Privileges

**Behavior:** When user clears all mapping selections, all mapping records must be DELETED

**Test:** `tests/Unit/MappingPageClearAllBugfixTest.php::test_clear_mapping_only_keep_module`

**Verification:**
```php
// Submit form WITHOUT 'rolePages' key
$request = Request::create('/groups/1', 'PUT', [
    'group_name' => 'test',
    // NO 'rolePages' key
]);

// After processing:
// - All mapping records MUST be deleted
// - Count of mappings for group MUST be 0
```

**Why Critical:** This was a critical bug (Issue #21) - mappings were not being cleared

**Bugfix Date:** 2026-04-08 (Issue #21 - Critical Bugfix)

**Implementation Detail:** `mapping_before_insert()` MUST ALWAYS call `insert_process()`, even with empty `$roles` array

---

### 6. Transaction Atomicity in store()

**Behavior:** Group creation, privilege insert, and mapping insert must be atomic

**Test:** `tests/Unit/GroupControllerTransactionTest.php::test_store_rollback_on_privilege_failure`

**Verification:**
```php
// If privilege insert fails:
// - Group creation MUST be rolled back
// - No orphaned group record
// - Database state unchanged
```

**Why Critical:** Prevents orphaned records and data inconsistency

**Bugfix Date:** 2026-04-08 (Issue #3)

---

### 7. Transaction Atomicity in update()

**Behavior:** Group update, privilege update, and mapping update must be atomic

**Test:** `tests/Unit/GroupControllerTransactionTest.php::test_update_rollback_on_mapping_failure`

**Verification:**
```php
// If mapping update fails:
// - Group update MUST be rolled back
// - Privilege update MUST be rolled back
// - Database state unchanged
```

**Why Critical:** Prevents partial updates and data inconsistency

**Bugfix Date:** 2026-04-08 (Issue #7)

---


## 🎯 Functional Behaviors (MUST NOT CHANGE)

### 8. Always Call insert_process() in mapping_before_insert()

**Behavior:** `insert_process()` must be called even when `$roles` array is empty

**Test:** `tests/Unit/MappingPageClearAllBugfixTest.php` (all 4 tests)

**Verification:**
```php
// In mapping_before_insert():
$roles = [];  // Empty array

// This line MUST ALWAYS execute:
$this->map()->insert_process($roles, $group);

// Empty $roles triggers DELETE of existing records
```

**Why Critical:** This is the core of the "clear all" bugfix. Without this, mappings cannot be cleared.

**Common Mistake:**
```php
// ❌ WRONG - DO NOT DO THIS
if (empty($roles)) {
    return;  // BUG! Prevents deletion
}
$this->map()->insert_process($roles, $group);
```

**Bugfix Date:** 2026-04-08 (Issue #21 - Critical Bugfix)

---

### 9. Always Use "setnull" Marker in privileges_before_insert()

**Behavior:** When no modules selected, set "setnull" marker in `$this->roles`

**Test:** `tests/Unit/PrivilegesBeforeInsertTest.php::test_setnull_marker_when_no_modules`

**Verification:**
```php
// When 'modules' key is missing:
$this->roles = [
    'setnull' => [
        'group_id' => $group->id
    ]
];

// This triggers UPDATE to NULL in privileges_after_insert()
```

**Why Critical:** This is how module privileges are cleared (different from mapping which uses DELETE)

**Bugfix Date:** 2026-04-08 (Consistent behavior with mapping)

---

### 10. Root Group Protection

**Behavior:** Non-root users cannot modify root group

**Test:** `tests/Unit/GroupControllerAccessControlTest.php::test_non_root_cannot_modify_root_group`

**Verification:**
```php
// Non-root user attempts to modify root group
Auth::login($nonRootUser);
$response = $this->put('/groups/1', ['group_name' => 'root', ...]);

// Expected: PrivilegeException thrown
// Expected: Log entry created
```

**Why Critical:** Prevents privilege escalation

**Bugfix Date:** 2026-04-08 (Issue #7)

---

### 11. Cache Invalidation After Commit

**Behavior:** Cache must be invalidated AFTER transaction commits, not before

**Test:** `tests/Unit/GroupControllerCacheTest.php::test_cache_invalidation_after_commit`

**Verification:**
```php
DB::transaction(function() {
    // ... update operations ...
});

// Cache invalidation MUST happen here (after commit)
$this->invalidateGroupCache();

// NOT inside transaction
```

**Why Critical:** Prevents stale cache when transaction rolls back

**Bugfix Date:** 2026-04-08 (Issue #3, #7, #13)

---


## 📊 Performance Behaviors (SHOULD NOT DEGRADE)

### 12. Array Building Optimization in mapping_before_insert()

**Behavior:** Array building should use 3 nested loops, not 6

**Test:** `tests/Unit/MappingBeforeInsertOptimizationTest.php`

**Verification:**
```php
// Optimized version (3 loops):
foreach ($request['field_name'] as $route_path => $mdata) {
    foreach ($mdata as $table_name => $tdata) {
        foreach ($tdata as $field_name) {
            // Build role entry directly
        }
    }
}

// NOT the old version (6 loops with intermediate $role array)
```

**Why Important:** 50% performance improvement on large datasets

**Optimization Date:** 2026-04-08 (Issue #21)

---

### 13. Menu Caching in get_menu()

**Behavior:** Menu data should be cached to prevent N+1 queries

**Test:** `tests/Unit/GetMenuCachingTest.php`

**Verification:**
```php
// First call: Queries database
$menu1 = $this->get_menu();

// Second call: Uses cache (no queries)
$menu2 = $this->get_menu();

// Query count should not increase on second call
```

**Why Important:** Reduces 50+ queries to 1 query

**Optimization Date:** 2026-04-08 (Issue #14)

---

## 🧪 Test Requirements

### Required Tests That MUST Pass

Before any commit, these tests MUST pass:

```bash
# Critical bugfix tests
php artisan test tests/Unit/MappingPageClearAllBugfixTest.php
# Expected: 4/4 passing

# Optimization tests
php artisan test tests/Unit/MappingBeforeInsertOptimizationTest.php
# Expected: 3/3 passing

# Security tests
php artisan test --filter=CSRF
php artisan test --filter=SQLInjection
php artisan test --filter=XSS
# Expected: All passing

# Transaction tests
php artisan test --filter=Transaction
# Expected: All passing

# Access control tests
php artisan test --filter=AccessControl
# Expected: All passing
```

### Test Coverage Requirements

- **Minimum coverage:** 80% for modified files
- **Critical methods:** 100% coverage required for:
  - `mapping_before_insert()`
  - `privileges_before_insert()`
  - `privileges_after_insert()`
  - `store()`
  - `update()`

---

## 🚨 Common Mistakes to Avoid

### Mistake 1: Adding Early Returns

```php
// ❌ WRONG
if (empty($data)) {
    return;  // Prevents "clear all" from working
}
```

**Impact:** "Clear all" functionality breaks  
**Affected:** Issues #21 (mapping), consistent behavior for privileges

---

### Mistake 2: Skipping Transaction Wrapper

```php
// ❌ WRONG
public function update(Request $request, int $id) {
    $group->update(...);
    $this->set_data_before_insert(...);
    // No transaction!
}
```

**Impact:** Orphaned records, data inconsistency  
**Affected:** Issues #3, #7, #13

---

### Mistake 3: Cache Invalidation Inside Transaction

```php
// ❌ WRONG
DB::transaction(function() {
    $group->update(...);
    Cache::forget('group_list');  // Inside transaction!
});
```

**Impact:** Stale cache when transaction rolls back  
**Affected:** Issues #3, #7, #10, #20

---

### Mistake 4: Using Superglobals

```php
// ❌ WRONG
$usein = $_GET['usein'];
$data = $_POST;
```

**Impact:** Bypasses Laravel validation and sanitization  
**Affected:** Issue #2

---

### Mistake 5: Skipping Input Validation

```php
// ❌ WRONG
public function rolepage($data, $usein) {
    return $this->map()::getData($data, $usein, '__node__');
    // No validation!
}
```

**Impact:** SQL injection vulnerability  
**Affected:** Issue #16 (CVSS 9.8)

---

### Mistake 6: Skipping Output Escaping

```php
// ❌ WRONG
$output[] = "<td>{$module_name}</td>";  // Not escaped!
```

**Impact:** XSS vulnerability  
**Affected:** Issue #17 (CVSS 7.3)

---


## 📋 Pre-Commit Checklist

Before committing changes to GroupController, Privileges, or MappingPage:

### Code Quality
- [ ] All methods have type hints (parameters and return)
- [ ] All methods have comprehensive PHPDoc
- [ ] Constants used instead of magic numbers (PrivilegeConstants)
- [ ] No direct superglobal access ($_GET, $_POST)
- [ ] All input validated before use
- [ ] All output escaped before rendering

### Security
- [ ] CSRF validation present for AJAX endpoints
- [ ] Input validation with whitelist checking
- [ ] Output escaping with htmlspecialchars()
- [ ] No SQL injection vulnerabilities
- [ ] No XSS vulnerabilities
- [ ] Access control checks present

### Data Integrity
- [ ] All multi-step operations wrapped in transactions
- [ ] Cache invalidation after transaction commits
- [ ] Error handling with try-catch blocks
- [ ] Logging for errors and security events
- [ ] "Clear all" functionality works correctly

### Testing
- [ ] All existing tests pass (100%)
- [ ] New tests added for new functionality
- [ ] Test coverage ≥ 80% for modified files
- [ ] Manual testing performed for UI changes
- [ ] "Clear all" scenarios tested manually

### Documentation
- [ ] PHPDoc updated for modified methods
- [ ] DEVELOPMENT_GUIDELINES.md reviewed
- [ ] REGRESSION_PREVENTION.md reviewed
- [ ] Change log entry added (if significant change)

---

## 🔍 Code Review Checklist

When reviewing PRs that modify these files:

### Critical Checks
- [ ] No early returns in `mapping_before_insert()` or `privileges_before_insert()`
- [ ] `insert_process()` always called in `mapping_before_insert()`
- [ ] "setnull" marker used correctly in `privileges_before_insert()`
- [ ] All database operations wrapped in transactions
- [ ] Cache invalidation after commit, not inside transaction
- [ ] No superglobal access
- [ ] Input validation present
- [ ] Output escaping present

### Quality Checks
- [ ] Type hints present and correct
- [ ] PHPDoc present and comprehensive
- [ ] Constants used instead of magic numbers
- [ ] Error handling comprehensive
- [ ] Logging appropriate
- [ ] Code follows existing patterns

### Test Checks
- [ ] All tests pass
- [ ] New tests added
- [ ] Test coverage adequate
- [ ] Critical tests not skipped or removed

---

## 📚 Reference Documentation

**Must Read Before Modifying:**
1. `GROUP_PRIVILEGES_BEHAVIOR_GUIDE.md` - Complete behavior documentation
2. `DEVELOPMENT_GUIDELINES.md` - Development rules and patterns
3. PHPDoc in the code - Method-level documentation

**Test Files:**
- `tests/Unit/MappingPageClearAllBugfixTest.php` - Critical bugfix verification
- `tests/Unit/MappingBeforeInsertOptimizationTest.php` - Performance optimization
- `tests/Unit/GroupControllerDeadCodeRemovalTest.php` - Dead code removal

**Related Issues:**
- Issue #1: CSRF validation (CVSS 8.8)
- Issue #2: Superglobal access
- Issue #3: Transaction management in store()
- Issue #7: Transaction management in update()
- Issue #13: Transaction management in privileges_after_insert()
- Issue #16: SQL injection (CVSS 9.8)
- Issue #17: XSS vulnerability (CVSS 7.3)
- Issue #21: Inefficient array operations + "clear all" bugfix

---

## 🆘 Emergency Rollback

If a regression is discovered in production:

### Immediate Actions
1. **Identify the breaking change** - Check recent commits
2. **Verify the regression** - Run affected tests
3. **Rollback the change** - Revert the commit
4. **Verify fix** - Run all tests again
5. **Deploy rollback** - Push to production

### Post-Rollback
1. **Root cause analysis** - Why did this happen?
2. **Update tests** - Add test to catch this regression
3. **Update documentation** - Add to common mistakes
4. **Fix properly** - Implement correct solution
5. **Review process** - Improve code review checklist

---

## 📞 Contact

**Questions about these behaviors?**
- Check documentation first (links above)
- Review test files for examples
- Ask team lead for clarification

**Found a regression?**
- Report immediately
- Include test case that demonstrates the issue
- Reference this document

---

**Last Updated:** 2026-04-08  
**Next Review:** 2026-07-08 (quarterly)  
**Maintained By:** Development Team
