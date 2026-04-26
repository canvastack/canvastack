# Caching Strategy Guide - Group Controller

## Overview

This guide documents caching patterns and strategies for improving performance in the Group Controller and related components. These patterns were established during the comprehensive performance optimization implemented in 2026-04-08.

**Audience:** Developers working on GroupController, Privileges trait, MappingPage trait, or any component that queries frequently accessed data.

**Related Documents:**
- `TRANSACTION_MANAGEMENT_GUIDE.md` - Cache invalidation timing
- `SECURITY_BEST_PRACTICES.md` - Security patterns
- `DEVELOPMENT_GUIDELINES.md` - Mandatory development rules

---

## Table of Contents

1. [Caching Patterns](#caching-patterns)
2. [Cache Key Naming Conventions](#cache-key-naming-conventions)
3. [Cache TTL Guidelines](#cache-ttl-guidelines)
4. [Cache Invalidation Patterns](#cache-invalidation-patterns)
5. [Caching Checklist](#caching-checklist)

---

## Caching Patterns

### Overview

Caching reduces database load by storing frequently accessed data in memory. Laravel provides a unified caching API that supports multiple backends (Redis, Memcached, file, database).

### When to Use Caching

**Cache data that is:**
- Frequently accessed (multiple times per request or across requests)
- Expensive to compute or query (complex joins, aggregations)
- Relatively static (changes infrequently)
- Not user-specific (or can be keyed by user ID)

**Do NOT cache data that is:**
- User-specific sensitive data (passwords, tokens, PII)
- Frequently changing (real-time data)
- Large datasets (use pagination instead)
- Already cached by database query cache

### Pattern 1: Basic Cache Remember

**Use Case:** Cache expensive query results

```php
/**
 * Get menu data with caching
 * 
 * @return object Menu structure
 * @performance Caches menu data for 1 hour to reduce database queries
 */
public function get_menu(): object {
    $cacheKey = 'group_menu_structure';
    $cacheTTL = 3600; // 1 hour in seconds
    
    return Cache::remember($cacheKey, $cacheTTL, function() {
        // Expensive query - only runs on cache miss
        $modules = canvastack_query('modules')
            ->where('module_status', 1)
            ->orderBy('module_order')
            ->get();
        
        // Build hierarchical menu structure
        $menu = $this->buildMenuHierarchy($modules);
        
        \Log::info('Menu data cached', [
            'cache_key' => $cacheKey,
            'ttl' => $cacheTTL,
            'module_count' => count($modules)
        ]);
        
        return $menu;
    });
}
```

### Pattern 2: User-Specific Caching

**Use Case:** Cache data specific to a user

```php
/**
 * Get group list with user-specific caching
 * 
 * @return \Illuminate\Support\Collection
 * @performance Caches group list for 5 minutes per user
 */
public function index(): \Illuminate\View\View|\Illuminate\Http\JsonResponse {
    $this->get_session();
    
    $userId = $this->session['id'];
    $userGroup = $this->session['group_name'];
    
    // User-specific cache key
    $cacheKey = "group_list_user_{$userId}";
    $cacheTTL = 300; // 5 minutes
    
    $groups = Cache::remember($cacheKey, $cacheTTL, function() use ($userGroup) {
        $query = canvastack_query($this->model_table);
        
        // Non-root users don't see root group
        if ($userGroup !== 'root') {
            $query->where('group_name', '!=', 'root');
        }
        
        return $query->orderBy('group_name')->get();
    });
    
    return view('admin.system.group.index', compact('groups'));
}
```

### Pattern 3: Cache with Tags

**Use Case:** Invalidate related caches together

```php
/**
 * Get mapping page data with tagged caching
 * 
 * @param int $moduleId
 * @return array
 * @performance Caches mapping data for 5 minutes with tags for easy invalidation
 */
public function get_data_mapping_page(int $moduleId): array {
    $userId = auth()->id();
    $route = request()->route()->getName();
    
    // Cache key with context
    $cacheKey = "mapping_page_{$userId}_{$route}_{$moduleId}";
    $cacheTTL = 300; // 5 minutes
    
    // Use tags for grouped invalidation
    return Cache::tags(['mapping', "user_{$userId}"])->remember(
        $cacheKey,
        $cacheTTL,
        function() use ($moduleId) {
            return canvastack_query('mapping_pages')
                ->where('module_id', $moduleId)
                ->get()
                ->toArray();
        }
    );
}

/**
 * Invalidate all mapping caches for a user
 * 
 * @param int $userId
 * @return void
 */
private function invalidateMappingCacheForUser(int $userId): void {
    Cache::tags(["user_{$userId}"])->flush();
    
    \Log::info('Mapping cache invalidated for user', [
        'user_id' => $userId
    ]);
}
```

### Pattern 4: Cache with Fallback

**Use Case:** Graceful degradation on cache failure

```php
/**
 * Get menu data with cache fallback
 * 
 * @return object
 * @performance Attempts to use cache, falls back to database on failure
 */
public function get_menu(): object {
    $cacheKey = 'group_menu_structure';
    $cacheTTL = 3600;
    
    try {
        // Try to get from cache
        $menu = Cache::get($cacheKey);
        
        if ($menu !== null) {
            return $menu;
        }
        
        // Cache miss - query database
        $menu = $this->buildMenuFromDatabase();
        
        // Store in cache
        Cache::put($cacheKey, $menu, $cacheTTL);
        
        return $menu;
        
    } catch (\Exception $e) {
        // Cache failure - fall back to database
        \Log::warning('Cache failure, using database', [
            'error' => $e->getMessage(),
            'cache_key' => $cacheKey
        ]);
        
        return $this->buildMenuFromDatabase();
    }
}
```

### Pattern 5: Conditional Caching

**Use Case:** Cache only in production

```php
/**
 * Get group list with conditional caching
 * 
 * @return \Illuminate\Support\Collection
 * @performance Caches in production, always fresh in development
 */
private function getGroupList(): \Illuminate\Support\Collection {
    // Skip cache in development for easier debugging
    if (app()->environment('local', 'development')) {
        return canvastack_query($this->model_table)->get();
    }
    
    // Use cache in production
    $cacheKey = 'group_list_all';
    $cacheTTL = 300;
    
    return Cache::remember($cacheKey, $cacheTTL, function() {
        return canvastack_query($this->model_table)->get();
    });
}
```

---

## Cache Key Naming Conventions

### Overview

Consistent cache key naming prevents collisions, makes debugging easier, and enables targeted invalidation.

### Naming Convention Rules

**Format:** `{component}_{entity}_{context}_{identifier}`

**Components:**
- `group` - Group-related data
- `privilege` - Privilege-related data
- `menu` - Menu structure data
- `mapping` - Mapping page data

**Examples:**

```php
// ✅ GOOD: Descriptive, hierarchical, specific
'group_list_all'                          // All groups
'group_list_user_123'                     // Groups for user 123
'group_menu_structure'                    // Menu structure
'privilege_cache_group_5'                 // Privileges for group 5
'mapping_page_user_10_route_admin_123'    // Mapping for user 10, route admin, module 123

// ❌ BAD: Generic, unclear, collision-prone
'groups'                                  // Too generic
'cache_123'                               // Unclear what 123 is
'data'                                    // Too vague
'temp'                                    // Unclear purpose
```

### Pattern 1: Static Cache Keys

**Use Case:** Global data (same for all users)

```php
// Menu structure (same for all users)
$cacheKey = 'group_menu_structure';

// All groups (admin view)
$cacheKey = 'group_list_all';

// Privilege constants (never changes)
$cacheKey = 'privilege_constants';
```

### Pattern 2: User-Specific Cache Keys

**Use Case:** Data specific to a user

```php
// Group list for specific user
$userId = auth()->id();
$cacheKey = "group_list_user_{$userId}";

// User privileges
$cacheKey = "privilege_cache_user_{$userId}";

// User menu (filtered by privileges)
$cacheKey = "menu_user_{$userId}";
```

### Pattern 3: Context-Specific Cache Keys

**Use Case:** Data specific to a context (route, module, etc.)

```php
// Mapping page data for specific context
$userId = auth()->id();
$route = request()->route()->getName();
$moduleId = $request->query('module_id');
$cacheKey = "mapping_page_user_{$userId}_route_{$route}_module_{$moduleId}";

// Group privileges for specific module
$groupId = $this->session['group_id'];
$moduleId = $request->query('module_id');
$cacheKey = "privilege_group_{$groupId}_module_{$moduleId}";
```

### Pattern 4: Versioned Cache Keys

**Use Case:** Invalidate all caches on schema change

```php
// Include version in cache key
$version = config('app.cache_version', 'v1');
$cacheKey = "{$version}_group_list_all";

// When schema changes, increment version in config
// Old caches become unreachable (auto-expire)
```

---

## Cache TTL Guidelines

### Overview

Time-To-Live (TTL) determines how long cached data remains valid. Shorter TTL means fresher data but more cache misses. Longer TTL means better performance but potentially stale data.

### TTL Recommendations

**Very Short (1-5 minutes):**
- User-specific data that changes frequently
- Data that must be relatively fresh
- High-traffic endpoints with acceptable staleness

```php
// Group list (changes when groups are created/updated)
$cacheTTL = 300; // 5 minutes

// Mapping page data (changes when mappings are updated)
$cacheTTL = 300; // 5 minutes
```

**Short (5-15 minutes):**
- Shared data that changes occasionally
- Data where slight staleness is acceptable
- Medium-traffic endpoints

```php
// User privileges (changes when group privileges are updated)
$cacheTTL = 600; // 10 minutes

// Module list (changes when modules are added/updated)
$cacheTTL = 900; // 15 minutes
```

**Medium (15-60 minutes):**
- Relatively static data
- Data where staleness is acceptable
- Low-traffic endpoints

```php
// Menu structure (changes when modules are added/removed)
$cacheTTL = 3600; // 1 hour

// System configuration (changes rarely)
$cacheTTL = 3600; // 1 hour
```

**Long (1-24 hours):**
- Very static data
- Data that rarely changes
- Reference data

```php
// Privilege constants (never changes)
$cacheTTL = 86400; // 24 hours

// System settings (changes very rarely)
$cacheTTL = 43200; // 12 hours
```

**Permanent (until invalidated):**
- Data that only changes on deployment
- Data that is explicitly invalidated

```php
// Use Cache::forever() for permanent caching
Cache::forever('privilege_constants', $constants);

// Must be explicitly invalidated
Cache::forget('privilege_constants');
```

### TTL Selection Criteria

**Consider these factors:**

1. **Update Frequency:** How often does the data change?
   - Frequently → Short TTL (1-5 minutes)
   - Occasionally → Medium TTL (15-60 minutes)
   - Rarely → Long TTL (1-24 hours)

2. **Staleness Tolerance:** How critical is data freshness?
   - Critical → Short TTL + explicit invalidation
   - Important → Medium TTL + explicit invalidation
   - Acceptable → Long TTL

3. **Query Cost:** How expensive is the query?
   - Very expensive → Longer TTL (offset by explicit invalidation)
   - Moderate → Medium TTL
   - Cheap → Short TTL or no cache

4. **Traffic Volume:** How often is the data accessed?
   - High traffic → Longer TTL (more benefit)
   - Low traffic → Shorter TTL (less benefit)

---

## Cache Invalidation Patterns

### Overview

Cache invalidation ensures cached data stays consistent with database. Invalidate caches when underlying data changes.

### Pattern 1: Explicit Invalidation After Update

**Use Case:** Invalidate cache after data modification

```php
/**
 * Update group with cache invalidation
 * 
 * @param Request $request
 * @param int $id
 * @return \Illuminate\Http\RedirectResponse
 */
public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse {
    $this->get_session();
    
    DB::transaction(function() use ($request, $id) {
        $this->update_data($request, $id);
        $this->set_data_before_insert($request, $id);
        $this->set_data_after_insert($this->roles);
    });
    
    // Invalidate all related caches AFTER transaction
    $this->invalidateGroupCache();
    canvastack_invalidate_privilege_cache($id);
    $this->invalidateMenuCache();
    
    \Log::info('Group updated and caches invalidated', [
        'group_id' => $id
    ]);
    
    return self::redirect("{$id}/edit", $request);
}
```

### Pattern 2: Centralized Invalidation Methods

**Use Case:** Consistent invalidation across codebase

```php
/**
 * Invalidate all group-related caches
 * 
 * @return void
 * @performance Clears group list cache for all users
 */
private function invalidateGroupCache(): void {
    // Invalidate global group list
    Cache::forget('group_list_all');
    
    // Invalidate user-specific caches (if using tags)
    Cache::tags(['group_list'])->flush();
    
    \Log::info('Group cache invalidated');
}

/**
 * Invalidate menu cache
 * 
 * @return void
 * @performance Clears menu structure cache
 */
private function invalidateMenuCache(): void {
    Cache::forget('group_menu_structure');
    
    \Log::info('Menu cache invalidated');
}

/**
 * Invalidate mapping cache for specific user
 * 
 * @param int $userId
 * @return void
 * @performance Clears mapping page cache for specific user
 */
private function invalidateMappingCache(int $userId): void {
    // Invalidate all mapping caches for user
    Cache::tags(["user_{$userId}", 'mapping'])->flush();
    
    \Log::info('Mapping cache invalidated', ['user_id' => $userId]);
}
```

### Pattern 3: Conditional Invalidation

**Use Case:** Invalidate only if data actually changed

```php
/**
 * Update group with conditional cache invalidation
 * 
 * @param Request $request
 * @param int $id
 * @return \Illuminate\Http\RedirectResponse
 */
public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse {
    $this->get_session();
    
    $group = Group::find($id);
    $oldName = $group->group_name;
    $oldPrivileges = $group->privileges->pluck('id')->toArray();
    
    DB::transaction(function() use ($request, $id) {
        $this->update_data($request, $id);
        $this->set_data_before_insert($request, $id);
        $this->set_data_after_insert($this->roles);
    });
    
    // Conditional invalidation
    $group->refresh();
    
    if ($oldName !== $group->group_name) {
        $this->invalidateGroupCache();
        \Log::info('Group name changed, cache invalidated');
    }
    
    $newPrivileges = $group->privileges->pluck('id')->toArray();
    if ($oldPrivileges !== $newPrivileges) {
        canvastack_invalidate_privilege_cache($id);
        $this->invalidateMenuCache();
        \Log::info('Privileges changed, cache invalidated');
    }
    
    return self::redirect("{$id}/edit", $request);
}
```

### Pattern 4: Cascade Invalidation

**Use Case:** Invalidate related caches

```php
/**
 * Delete group with cascade cache invalidation
 * 
 * @param int $id
 * @return \Illuminate\Http\RedirectResponse
 */
public function destroy(int $id): \Illuminate\Http\RedirectResponse {
    DB::transaction(function() use ($id) {
        // Delete privileges
        Privilege::where('group_id', $id)->delete();
        
        // Delete mapping
        MappingPage::where('group_id', $id)->delete();
        
        // Delete group
        Group::destroy($id);
    });
    
    // Cascade invalidation - invalidate all related caches
    $this->invalidateGroupCache();           // Group list
    canvastack_invalidate_privilege_cache($id);  // Privileges
    $this->invalidateMenuCache();            // Menu
    $this->invalidateMappingCache(auth()->id()); // Mapping
    
    \Log::info('Group deleted and all related caches invalidated', [
        'group_id' => $id
    ]);
    
    return redirect()->route('admin.system.group.index');
}
```

### Pattern 5: Event-Based Invalidation

**Use Case:** Automatic invalidation on model events

```php
// In Group model
protected static function booted(): void {
    // Invalidate cache when group is created
    static::created(function ($group) {
        Cache::forget('group_list_all');
        Cache::tags(['group_list'])->flush();
    });
    
    // Invalidate cache when group is updated
    static::updated(function ($group) {
        Cache::forget('group_list_all');
        Cache::tags(['group_list'])->flush();
        Cache::forget("group_detail_{$group->id}");
    });
    
    // Invalidate cache when group is deleted
    static::deleted(function ($group) {
        Cache::forget('group_list_all');
        Cache::tags(['group_list'])->flush();
        Cache::forget("group_detail_{$group->id}");
    });
}
```

---

## Caching Checklist

### Pre-Development Checklist

Before implementing caching, determine:

- [ ] Is this data accessed frequently?
- [ ] Is the query expensive (time, resources)?
- [ ] How often does the data change?
- [ ] How critical is data freshness?
- [ ] What is the appropriate TTL?
- [ ] What caches need invalidation when data changes?

### Implementation Checklist

When implementing caching, ensure:

**Cache Key:**
- [ ] Cache key follows naming convention
- [ ] Cache key is specific and descriptive
- [ ] Cache key includes necessary context (user ID, route, etc.)
- [ ] Cache key avoids collisions with other caches

**Cache TTL:**
- [ ] TTL is appropriate for data update frequency
- [ ] TTL balances freshness and performance
- [ ] TTL is documented in code comments

**Cache Invalidation:**
- [ ] Invalidation occurs AFTER transaction commit
- [ ] All related caches are invalidated
- [ ] Invalidation is logged for debugging
- [ ] Invalidation methods are centralized

**Error Handling:**
- [ ] Cache failures are handled gracefully
- [ ] Fallback to database on cache failure
- [ ] Cache errors are logged

**Logging:**
- [ ] Cache hits/misses are logged (in development)
- [ ] Cache invalidation is logged
- [ ] Cache errors are logged

### Testing Checklist

Before deployment, test:

- [ ] Cache is populated on first access
- [ ] Cache is returned on subsequent access
- [ ] Cache expires after TTL
- [ ] Cache is invalidated when data changes
- [ ] Cache invalidation doesn't occur on rollback
- [ ] Cache failures fall back to database
- [ ] Performance improvement is measurable

---

## Performance Monitoring

### Metrics to Track

**Cache Hit Rate:**
```php
// Log cache hits and misses
$cacheKey = 'group_list_all';

if (Cache::has($cacheKey)) {
    \Log::debug('Cache hit', ['key' => $cacheKey]);
    return Cache::get($cacheKey);
} else {
    \Log::debug('Cache miss', ['key' => $cacheKey]);
    $data = $this->queryDatabase();
    Cache::put($cacheKey, $data, $cacheTTL);
    return $data;
}
```

**Query Count Reduction:**
```php
// Before caching
\DB::enableQueryLog();
$menu = $this->get_menu();
$queries = \DB::getQueryLog();
\Log::info('Queries without cache', ['count' => count($queries)]);

// After caching
\DB::enableQueryLog();
$menu = $this->get_menu(); // Uses cache
$queries = \DB::getQueryLog();
\Log::info('Queries with cache', ['count' => count($queries)]);
```

**Response Time Improvement:**
```php
$start = microtime(true);
$data = $this->getCachedData();
$duration = (microtime(true) - $start) * 1000;

\Log::info('Cache performance', [
    'duration_ms' => $duration,
    'cache_key' => $cacheKey
]);
```

---

## Common Mistakes

### Mistake 1: Caching User-Specific Data with Global Key

```php
// ❌ WRONG: Global cache key for user-specific data
$cacheKey = 'group_list';  // Same for all users!
$groups = Cache::remember($cacheKey, 300, function() {
    // Query filtered by current user
    return $this->getGroupsForUser(auth()->id());
});

// ✅ CORRECT: User-specific cache key
$userId = auth()->id();
$cacheKey = "group_list_user_{$userId}";
$groups = Cache::remember($cacheKey, 300, function() use ($userId) {
    return $this->getGroupsForUser($userId);
});
```

### Mistake 2: Invalidating Cache Before Commit

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

### Mistake 3: Forgetting to Invalidate Related Caches

```php
// ❌ WRONG: Only invalidates group cache
public function update(Request $request, int $id) {
    DB::transaction(function() use ($request, $id) {
        $this->update_data($request, $id);
        $this->updatePrivileges($id);  // Privileges changed!
    });
    
    $this->invalidateGroupCache();  // ❌ Forgot privilege cache!
}

// ✅ CORRECT: Invalidates all related caches
public function update(Request $request, int $id) {
    DB::transaction(function() use ($request, $id) {
        $this->update_data($request, $id);
        $this->updatePrivileges($id);
    });
    
    $this->invalidateGroupCache();
    canvastack_invalidate_privilege_cache($id);  // ✅ Invalidate privileges
    $this->invalidateMenuCache();                // ✅ Invalidate menu
}
```

### Mistake 4: Caching Sensitive Data

```php
// ❌ WRONG: Caching sensitive data
$cacheKey = "user_credentials_{$userId}";
Cache::put($cacheKey, [
    'password' => $user->password,  // ❌ Never cache passwords!
    'token' => $user->api_token     // ❌ Never cache tokens!
], 3600);

// ✅ CORRECT: Cache only non-sensitive data
$cacheKey = "user_profile_{$userId}";
Cache::put($cacheKey, [
    'name' => $user->name,
    'email' => $user->email,
    'group_id' => $user->group_id
], 3600);
```

---

## Additional Resources

**Related Documentation:**
- `TRANSACTION_MANAGEMENT_GUIDE.md` - Cache invalidation timing
- `SECURITY_BEST_PRACTICES.md` - Security patterns
- `DEVELOPMENT_GUIDELINES.md` - Mandatory development rules

**External Resources:**
- [Laravel Cache Documentation](https://laravel.com/docs/cache)
- [Redis Best Practices](https://redis.io/docs/manual/patterns/)
- [Cache Invalidation Strategies](https://martinfowler.com/bliki/TwoHardThings.html)

**Training:**
- Review cache performance metrics regularly
- Monitor cache hit rates and adjust TTL
- Profile queries to identify caching opportunities

---

**Document Version:** 1.0  
**Last Updated:** 2026-04-08  
**Next Review:** 2026-07-08
