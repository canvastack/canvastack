# Transaction Management Guide - Group Controller

## Overview

This guide documents transaction management patterns for ensuring data consistency and atomicity in the Group Controller and related components. These patterns were established during the comprehensive audit and fixes implemented in 2026-04-08.

**Audience:** Developers working on GroupController, Privileges trait, MappingPage trait, or any component that performs multi-step database operations.

**Related Documents:**
- `SECURITY_BEST_PRACTICES.md` - Security patterns
- `CACHING_STRATEGY_GUIDE.md` - Cache invalidation timing
- `DEVELOPMENT_GUIDELINES.md` - Mandatory development rules

---

## Table of Contents

1. [When to Use Transactions](#when-to-use-transactions)
2. [Transaction Patterns](#transaction-patterns)
3. [Error Handling Within Transactions](#error-handling-within-transactions)
4. [Cache Invalidation Timing](#cache-invalidation-timing)
5. [Transaction Checklist](#transaction-checklist)

---

## When to Use Transactions

### Overview

Database transactions ensure atomicity - either ALL operations succeed and are committed, or ALL operations fail and are rolled back. This prevents partial updates and data inconsistency.

### Use Transactions When:

**Multi-Step Operations:**
- Creating a group with privileges and page mapping
- Updating a group with privilege modifications
- Deleting a group with cascade operations
- Batch operations that must succeed or fail together

**Data Consistency Requirements:**
- Operations that create related records (parent-child relationships)
- Operations that modify multiple tables
- Operations where partial success would corrupt data
- Operations that require rollback on any failure

**Examples Requiring Transactions:**

```php
// ✅ REQUIRES TRANSACTION: Group creation with privileges
// If privilege insert fails, group should not exist
DB::transaction(function() {
    $group = Group::create($data);           // Step 1
    $this->insertPrivileges($group->id);     // Step 2
    $this->insertMapping($group->id);        // Step 3
});

// ✅ REQUIRES TRANSACTION: Privilege update with clearing
// If insert fails, old privileges should remain
DB::transaction(function() {
    Privilege::where('group_id', $id)->delete();  // Step 1
    Privilege::insert($newPrivileges);             // Step 2
});

// ✅ REQUIRES TRANSACTION: Batch operations
// All or nothing
DB::transaction(function() {
    foreach ($items as $item) {
        Item::create($item);
    }
});
```

### Do NOT Use Transactions When:

**Single Operations:**
- Single INSERT, UPDATE, or DELETE (already atomic)
- Read-only operations (SELECT queries)
- Operations that don't modify data

**Long-Running Operations:**
- File uploads (transaction should not span I/O)
- External API calls (transaction should not span network)
- Email sending (transaction should not span SMTP)

**Examples NOT Requiring Transactions:**

```php
// ❌ NO TRANSACTION NEEDED: Single operation
$group = Group::find($id);

// ❌ NO TRANSACTION NEEDED: Read-only
$groups = Group::where('status', 1)->get();

// ❌ NO TRANSACTION NEEDED: Single update
Group::where('id', $id)->update(['status' => 1]);

// ❌ WRONG: Transaction spanning external operations
DB::transaction(function() {
    $group = Group::create($data);
    Mail::send($notification);  // ❌ Don't include in transaction
    Storage::put($file);        // ❌ Don't include in transaction
});

// ✅ CORRECT: Transaction only for database operations
DB::transaction(function() use ($data) {
    $group = Group::create($data);
    return $group;
});
// External operations after transaction
Mail::send($notification);
Storage::put($file);
```

---

## Transaction Patterns

### Pattern 1: Basic Transaction Wrapper

**Use Case:** Simple multi-step operation

```php
/**
 * Store group with transaction management
 * 
 * @param Request $request
 * @return \Illuminate\Http\RedirectResponse
 * @throws ControllerException
 */
public function store(Request $request): \Illuminate\Http\RedirectResponse {
    $this->get_session();
    
    DB::beginTransaction();
    
    try {
        // Step 1: Insert group
        $this->insert_data($request, false);
        
        if (!$this->stored_id) {
            throw new ControllerException('Failed to create group');
        }
        
        // Step 2: Set privileges
        $this->set_data_before_insert($request, $this->stored_id);
        
        // Step 3: Process privileges
        $this->set_data_after_insert($this->roles);
        
        // All operations succeeded - commit
        DB::commit();
        
        \Log::info('Group created successfully', [
            'group_id' => $this->stored_id,
            'group_name' => $request->group_name,
            'created_by' => $this->session['id']
        ]);
        
        // Invalidate cache AFTER commit
        $this->invalidateGroupCache();
        
        return self::redirect("{$this->stored_id}/edit", $request);
        
    } catch (\Exception $e) {
        // Any operation failed - rollback
        DB::rollBack();
        
        \Log::error('Failed to create group', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request' => $request->except(['password', '_token'])
        ]);
        
        throw new ControllerException(
            'Failed to create group: ' . $e->getMessage(),
            ['original_error' => $e->getMessage()]
        );
    }
}
```

### Pattern 2: Closure-Based Transaction

**Use Case:** Cleaner syntax with automatic rollback

```php
/**
 * Update group with transaction management
 * 
 * @param Request $request
 * @param int $id
 * @return \Illuminate\Http\RedirectResponse
 * @throws ControllerException
 */
public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse {
    $this->get_session();
    
    // Validate before transaction
    $this->validateUpdate($request, $id);
    
    try {
        // Closure-based transaction (automatic rollback on exception)
        $result = DB::transaction(function() use ($request, $id) {
            // Step 1: Update group
            $this->update_data($request, $id);
            
            // Step 2: Update privileges
            $this->set_data_before_insert($request, $id);
            
            // Step 3: Process privileges
            $this->set_data_after_insert($this->roles);
            
            return $id;
        });
        
        \Log::info('Group updated successfully', [
            'group_id' => $result,
            'updated_by' => $this->session['id']
        ]);
        
        // Invalidate cache AFTER transaction
        $this->invalidateGroupCache();
        canvastack_invalidate_privilege_cache($result);
        
        return self::redirect("{$result}/edit", $request);
        
    } catch (\Exception $e) {
        \Log::error('Failed to update group', [
            'error' => $e->getMessage(),
            'group_id' => $id
        ]);
        
        throw new ControllerException(
            'Failed to update group: ' . $e->getMessage()
        );
    }
}
```

### Pattern 3: Nested Transactions (Savepoints)

**Use Case:** Transaction within transaction

```php
/**
 * Process privileges with nested transaction support
 * 
 * @param array $roles
 * @param int $groupId
 * @return void
 * @throws ControllerException
 */
public static function insert_process(array $roles, int $groupId): void {
    // Check if already in transaction
    $inTransaction = DB::transactionLevel() > 0;
    
    if (!$inTransaction) {
        DB::beginTransaction();
    }
    
    try {
        // Clear existing privileges
        DB::table('group_privileges')
            ->where('group_id', $groupId)
            ->delete();
        
        // Insert new privileges
        if (!empty($roles)) {
            DB::table('group_privileges')->insert($roles);
        }
        
        if (!$inTransaction) {
            DB::commit();
        }
        
    } catch (\Exception $e) {
        if (!$inTransaction) {
            DB::rollBack();
        }
        
        throw new ControllerException(
            'Failed to process privileges: ' . $e->getMessage()
        );
    }
}
```

### Pattern 4: Transaction with Return Value

**Use Case:** Return data from transaction

```php
/**
 * Create group and return ID
 * 
 * @param array $data
 * @return int Group ID
 * @throws ControllerException
 */
private function createGroupWithPrivileges(array $data): int {
    return DB::transaction(function() use ($data) {
        // Create group
        $group = Group::create([
            'group_name' => $data['group_name'],
            'group_alias' => $data['group_alias'],
            'group_info' => $data['group_info']
        ]);
        
        // Create privileges
        foreach ($data['privileges'] as $privilege) {
            Privilege::create([
                'group_id' => $group->id,
                'module_id' => $privilege['module_id'],
                'privilege' => $privilege['privilege']
            ]);
        }
        
        // Return group ID
        return $group->id;
    });
}
```

---

## Error Handling Within Transactions

### Overview

Proper error handling ensures transactions are rolled back on failure and errors are logged with sufficient context for debugging.

### Pattern 1: Try-Catch with Rollback

**Use Case:** Manual transaction control

```php
DB::beginTransaction();

try {
    // Database operations
    $group = Group::create($data);
    $this->insertPrivileges($group->id);
    
    // Commit on success
    DB::commit();
    
    \Log::info('Transaction completed', ['group_id' => $group->id]);
    
} catch (\Exception $e) {
    // Rollback on failure
    DB::rollBack();
    
    \Log::error('Transaction failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'data' => $data  // Exclude sensitive data
    ]);
    
    // Re-throw or throw new exception
    throw new ControllerException(
        'Failed to create group: ' . $e->getMessage(),
        ['original_error' => $e->getMessage()]
    );
}
```

### Pattern 2: Automatic Rollback with Closure

**Use Case:** Cleaner syntax

```php
try {
    $groupId = DB::transaction(function() use ($data) {
        $group = Group::create($data);
        $this->insertPrivileges($group->id);
        return $group->id;
    });
    
    \Log::info('Transaction completed', ['group_id' => $groupId]);
    
} catch (\Exception $e) {
    // Rollback happens automatically
    \Log::error('Transaction failed', [
        'error' => $e->getMessage()
    ]);
    
    throw new ControllerException('Failed to create group');
}
```

### Pattern 3: Specific Exception Handling

**Use Case:** Different handling for different errors

```php
DB::beginTransaction();

try {
    $group = Group::create($data);
    $this->insertPrivileges($group->id);
    
    DB::commit();
    
} catch (QueryException $e) {
    DB::rollBack();
    
    // Database-specific error
    \Log::error('Database error in transaction', [
        'error' => $e->getMessage(),
        'sql' => $e->getSql(),
        'bindings' => $e->getBindings()
    ]);
    
    throw new ControllerException('Database error occurred');
    
} catch (ControllerValidationException $e) {
    DB::rollBack();
    
    // Validation error
    \Log::warning('Validation error in transaction', [
        'error' => $e->getMessage(),
        'context' => $e->getContext()
    ]);
    
    throw $e;  // Re-throw validation exception
    
} catch (\Exception $e) {
    DB::rollBack();
    
    // Generic error
    \Log::error('Unexpected error in transaction', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    throw new ControllerException('Unexpected error occurred');
}
```

### Pattern 4: Validation Before Transaction

**Use Case:** Avoid unnecessary transactions

```php
/**
 * Update group with validation before transaction
 * 
 * @param Request $request
 * @param int $id
 * @return \Illuminate\Http\RedirectResponse
 */
public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse {
    $this->get_session();
    
    // VALIDATE BEFORE TRANSACTION (avoid unnecessary rollbacks)
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
    
    // START TRANSACTION (validation passed)
    DB::beginTransaction();
    
    try {
        $this->update_data($request, $id);
        $this->set_data_before_insert($request, $id);
        $this->set_data_after_insert($this->roles);
        
        DB::commit();
        
        $this->invalidateGroupCache();
        
        return self::redirect("{$id}/edit", $request);
        
    } catch (\Exception $e) {
        DB::rollBack();
        throw new ControllerException('Failed to update group');
    }
}
```

---

## Cache Invalidation Timing

### Overview

Cache invalidation must occur AFTER transaction commit to ensure cache reflects committed data. Invalidating before commit can cause cache misses or stale data.

### Pattern 1: Invalidate After Commit

**Use Case:** Standard cache invalidation

```php
DB::beginTransaction();

try {
    // Database operations
    $group = Group::create($data);
    $this->insertPrivileges($group->id);
    
    // Commit transaction
    DB::commit();
    
    // INVALIDATE CACHE AFTER COMMIT
    $this->invalidateGroupCache();
    canvastack_invalidate_privilege_cache($group->id);
    
    \Log::info('Group created and cache invalidated', [
        'group_id' => $group->id
    ]);
    
} catch (\Exception $e) {
    DB::rollBack();
    
    // DO NOT invalidate cache on rollback
    \Log::error('Transaction failed, cache not invalidated');
    
    throw new ControllerException('Failed to create group');
}
```

### Pattern 2: Conditional Cache Invalidation

**Use Case:** Invalidate only if data changed

```php
DB::beginTransaction();

try {
    $group = Group::find($id);
    $oldName = $group->group_name;
    
    // Update group
    $group->update($data);
    
    DB::commit();
    
    // Invalidate cache only if name changed
    if ($oldName !== $data['group_name']) {
        $this->invalidateGroupCache();
        \Log::info('Group name changed, cache invalidated');
    }
    
} catch (\Exception $e) {
    DB::rollBack();
    throw new ControllerException('Failed to update group');
}
```

### Pattern 3: Multiple Cache Invalidations

**Use Case:** Invalidate related caches

```php
DB::beginTransaction();

try {
    // Update group and privileges
    $group = Group::find($id);
    $group->update($data);
    $this->updatePrivileges($id, $privileges);
    
    DB::commit();
    
    // Invalidate all related caches AFTER commit
    $this->invalidateGroupCache();           // Group list cache
    canvastack_invalidate_privilege_cache($id);  // Privilege cache
    $this->invalidateMenuCache();            // Menu cache (if privileges changed)
    
    \Log::info('Group updated, all caches invalidated', [
        'group_id' => $id
    ]);
    
} catch (\Exception $e) {
    DB::rollBack();
    throw new ControllerException('Failed to update group');
}
```

### Pattern 4: Cache Invalidation with Closure Transaction

**Use Case:** Automatic transaction with cache invalidation

```php
try {
    $groupId = DB::transaction(function() use ($data) {
        $group = Group::create($data);
        $this->insertPrivileges($group->id);
        return $group->id;
    });
    
    // Transaction committed automatically
    // Invalidate cache AFTER transaction
    $this->invalidateGroupCache();
    canvastack_invalidate_privilege_cache($groupId);
    
    \Log::info('Transaction and cache invalidation complete');
    
} catch (\Exception $e) {
    // Transaction rolled back automatically
    // Cache NOT invalidated
    \Log::error('Transaction failed, cache not invalidated');
    throw new ControllerException('Failed to create group');
}
```

### Cache Invalidation Rules

**DO:**
- ✅ Invalidate cache AFTER `DB::commit()`
- ✅ Invalidate all related caches (group, privilege, menu)
- ✅ Log cache invalidation for debugging
- ✅ Use specific cache keys (include IDs)
- ✅ Invalidate cache only on successful commit

**DON'T:**
- ❌ Invalidate cache BEFORE commit (data not yet persisted)
- ❌ Invalidate cache in catch block (transaction rolled back)
- ❌ Forget to invalidate related caches
- ❌ Use generic cache keys (invalidate too much)
- ❌ Invalidate cache on validation failures (no data changed)

---

## Transaction Checklist

### Pre-Development Checklist

Before writing code, determine:

- [ ] Does this operation modify multiple tables?
- [ ] Does this operation create related records?
- [ ] Would partial success corrupt data?
- [ ] What should happen if any step fails?
- [ ] What caches need invalidation?

### Implementation Checklist

When implementing transactions, ensure:

**Transaction Scope:**
- [ ] Transaction wraps ALL related database operations
- [ ] Transaction does NOT include external operations (I/O, network)
- [ ] Transaction is as short as possible (minimize lock time)
- [ ] Validation occurs BEFORE transaction (when possible)

**Error Handling:**
- [ ] Try-catch block wraps transaction
- [ ] `DB::rollBack()` called in catch block (or use closure)
- [ ] Errors are logged with context
- [ ] Specific exceptions are thrown
- [ ] Sensitive data is excluded from logs

**Cache Invalidation:**
- [ ] Cache invalidation occurs AFTER `DB::commit()`
- [ ] Cache invalidation does NOT occur in catch block
- [ ] All related caches are invalidated
- [ ] Cache invalidation is logged

**Logging:**
- [ ] Successful commit is logged with context
- [ ] Failed transaction is logged with error details
- [ ] Cache invalidation is logged
- [ ] Log levels are appropriate (info, warning, error)

### Testing Checklist

Before deployment, test:

- [ ] Successful transaction commits all operations
- [ ] Failed transaction rolls back all operations
- [ ] No orphaned records after rollback
- [ ] Cache is invalidated after commit
- [ ] Cache is NOT invalidated after rollback
- [ ] Errors are logged correctly
- [ ] Nested transactions work correctly (if used)

---

## Common Mistakes

### Mistake 1: Cache Invalidation Before Commit

```php
// ❌ WRONG: Cache invalidated before commit
DB::beginTransaction();
try {
    $group = Group::create($data);
    $this->invalidateGroupCache();  // ❌ Too early!
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
}

// ✅ CORRECT: Cache invalidated after commit
DB::beginTransaction();
try {
    $group = Group::create($data);
    DB::commit();
    $this->invalidateGroupCache();  // ✅ After commit
} catch (\Exception $e) {
    DB::rollBack();
}
```

### Mistake 2: External Operations in Transaction

```php
// ❌ WRONG: External operations in transaction
DB::transaction(function() {
    $group = Group::create($data);
    Mail::send($notification);  // ❌ Network operation
    Storage::put($file);        // ❌ I/O operation
});

// ✅ CORRECT: External operations after transaction
$groupId = DB::transaction(function() use ($data) {
    $group = Group::create($data);
    return $group->id;
});
Mail::send($notification);  // ✅ After transaction
Storage::put($file);        // ✅ After transaction
```

### Mistake 3: Missing Rollback

```php
// ❌ WRONG: No rollback on exception
DB::beginTransaction();
try {
    $group = Group::create($data);
    DB::commit();
} catch (\Exception $e) {
    // ❌ Missing DB::rollBack()
    throw $e;
}

// ✅ CORRECT: Rollback on exception
DB::beginTransaction();
try {
    $group = Group::create($data);
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();  // ✅ Rollback
    throw $e;
}
```

### Mistake 4: Validation Inside Transaction

```php
// ❌ WRONG: Validation inside transaction (unnecessary rollbacks)
DB::beginTransaction();
try {
    if ($id <= 0) {
        throw new ControllerValidationException('Invalid ID');
    }
    $group = Group::create($data);
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();  // ❌ Rollback for validation error
}

// ✅ CORRECT: Validation before transaction
if ($id <= 0) {
    throw new ControllerValidationException('Invalid ID');
}
DB::beginTransaction();
try {
    $group = Group::create($data);
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
}
```

---

## Additional Resources

**Related Documentation:**
- `SECURITY_BEST_PRACTICES.md` - Security patterns
- `CACHING_STRATEGY_GUIDE.md` - Cache invalidation patterns
- `DEVELOPMENT_GUIDELINES.md` - Mandatory development rules
- `REGRESSION_PREVENTION.md` - Critical behaviors checklist

**External Resources:**
- [Laravel Database Transactions](https://laravel.com/docs/database#database-transactions)
- [ACID Properties](https://en.wikipedia.org/wiki/ACID)
- [Database Transaction Best Practices](https://www.postgresql.org/docs/current/tutorial-transactions.html)

**Training:**
- Review transaction failures and lessons learned
- Practice transaction patterns in development environment
- Understand database isolation levels and locking

---

**Document Version:** 1.0  
**Last Updated:** 2026-04-08  
**Next Review:** 2026-07-08
