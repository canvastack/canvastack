# Fine-Grained Permissions System - Best Practices Guide

## 📋 Overview

This guide provides best practices, performance tips, security considerations, and common patterns for using the Fine-Grained Permissions System effectively. Following these guidelines will help you build secure, performant, and maintainable applications.

**Audience**: Developers, System Administrators, Security Engineers  
**Difficulty**: Intermediate to Advanced  
**Last Updated**: 2026-02-28

---

## 🎯 Core Principles

### 1. Principle of Least Privilege

**Always grant the minimum permissions necessary** for users to perform their tasks.

✅ **Good**:
```php
// Editor can only edit title and content
$ruleManager->addColumnRule(
    $permissionId,
    Post::class,
    allowedColumns: ['title', 'content', 'excerpt'],
    deniedColumns: ['status', 'featured', 'published_at']
);
```

❌ **Bad**:
```php
// Editor can edit everything
$ruleManager->addColumnRule(
    $permissionId,
    Post::class,
    allowedColumns: ['*'], // Too permissive
    deniedColumns: []
);
```

### 2. Defense in Depth

**Layer multiple permission checks** for critical operations.

✅ **Good**:
```php
public function update(Request $request, Post $post)
{
    // Layer 1: Basic permission
    $this->authorize('posts.edit');
    
    // Layer 2: Row-level permission
    if (!$this->gate->canAccessRow(auth()->id(), 'posts.edit', $post)) {
        abort(403);
    }
    
    // Layer 3: Column-level filtering
    $allowedFields = $this->gate->getAccessibleColumns(
        auth()->id(),
        'posts.edit',
        Post::class
    );
    
    $post->update($request->only($allowedFields));
}
```


### 3. Fail Secure

**Default to denying access** when in doubt.

✅ **Good**:
```php
// Default deny
'column_level' => [
    'default_deny' => true, // Deny by default, allow explicitly
],
```

❌ **Bad**:
```php
// Default allow
'column_level' => [
    'default_deny' => false, // Allow by default, deny explicitly
],
```

### 4. Explicit Over Implicit

**Be explicit about permissions** rather than relying on implicit behavior.

✅ **Good**:
```php
// Explicitly define allowed columns
$ruleManager->addColumnRule(
    $permissionId,
    Post::class,
    allowedColumns: ['title', 'content', 'excerpt', 'tags'],
    deniedColumns: []
);
```

❌ **Bad**:
```php
// Implicitly allow all except denied
$ruleManager->addColumnRule(
    $permissionId,
    Post::class,
    allowedColumns: [],
    deniedColumns: ['status'] // Everything else allowed implicitly
);
```

### 5. Audit Everything

**Log all permission denials** for security monitoring.

✅ **Good**:
```php
'audit' => [
    'enabled' => true,
    'log_denials' => true,
    'log_channel' => 'rbac',
],
```

---

## ⚡ Performance Best Practices

### 1. Always Use Caching

**Enable caching for all permission checks** to avoid repeated database queries.

✅ **Good**:
```php
'cache' => [
    'enabled' => true,
    'ttl' => [
        'row' => 3600,        // 1 hour
        'column' => 3600,     // 1 hour
        'json_attribute' => 3600, // 1 hour
        'conditional' => 1800,    // 30 minutes
    ],
],
```

**Performance Impact**:
- Without cache: ~50ms per permission check
- With cache: ~2ms per permission check
- **25x faster** with caching enabled


### 2. Use Query Scopes for Lists

**Always use `scopeByPermission()`** when displaying lists to avoid N+1 queries.

✅ **Good**:
```php
// Single query with proper filtering
$posts = Post::query()
    ->scopeByPermission(auth()->id(), 'posts.view')
    ->with(['user', 'category']) // Eager load relationships
    ->paginate(20);
```

❌ **Bad**:
```php
// N+1 query problem
$posts = Post::all();
$filtered = $posts->filter(function ($post) {
    return $this->gate->canAccessRow(auth()->id(), 'posts.view', $post);
});
```

**Performance Impact**:
- Bad approach: 1 + N queries (1 for posts, N for permission checks)
- Good approach: 1 query total
- **100x faster** for 100 records

### 3. Warm Up Cache for Frequent Users

**Pre-warm cache** for users who frequently access the system.

```php
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;

// In a scheduled job or login event
$ruleManager = app(PermissionRuleManager::class);

$ruleManager->warmUpUserCache(
    userId: auth()->id(),
    permissions: ['posts.view', 'posts.edit', 'posts.delete']
);
```

**When to warm up**:
- After user login
- During off-peak hours (scheduled job)
- After permission changes
- Before bulk operations

### 4. Batch Permission Checks

**Check multiple permissions at once** instead of one by one.

✅ **Good**:
```php
// Batch check
$permissions = ['posts.view', 'posts.edit', 'posts.delete'];
$results = [];

foreach ($permissions as $permission) {
    $results[$permission] = $this->gate->canAccessRow(
        auth()->id(),
        $permission,
        $post
    );
}
```

✅ **Even Better**:
```php
// Use a custom batch method
$results = $this->gate->canAccessRowBatch(
    auth()->id(),
    ['posts.view', 'posts.edit', 'posts.delete'],
    $post
);
```


### 5. Optimize Rule Priority

**Order rules by frequency** to minimize evaluation time.

✅ **Good**:
```php
// Most common rules first (higher priority)
$ruleManager->addRowRule(
    $permissionId,
    Post::class,
    ['user_id' => '{{auth.id}}'],
    priority: 100 // Checked first
);

// Less common rules later (lower priority)
$ruleManager->addConditionalRule(
    $permissionId,
    Post::class,
    "status === 'draft' AND comments_count === 0",
    priority: 50 // Checked second
);
```

### 6. Use Eager Loading

**Always eager load relationships** when checking permissions on collections.

✅ **Good**:
```php
$posts = Post::with(['user', 'category', 'tags'])
    ->scopeByPermission(auth()->id(), 'posts.view')
    ->get();
```

❌ **Bad**:
```php
$posts = Post::scopeByPermission(auth()->id(), 'posts.view')->get();
// Lazy loading causes N+1 queries when accessing relationships
```

### 7. Monitor Cache Hit Rates

**Regularly monitor cache performance** to ensure optimal configuration.

```php
use Illuminate\Support\Facades\Redis;

// Get cache statistics
$stats = Redis::info('stats');
$hitRate = $stats['keyspace_hits'] / 
           ($stats['keyspace_hits'] + $stats['keyspace_misses']);

if ($hitRate < 0.8) {
    Log::warning('Low RBAC cache hit rate', [
        'hit_rate' => $hitRate,
        'hits' => $stats['keyspace_hits'],
        'misses' => $stats['keyspace_misses'],
    ]);
}
```

**Target Metrics**:
- Cache hit rate: > 80%
- Permission check time: < 50ms (without cache), < 5ms (with cache)
- Memory usage: < 10MB for rule storage

---

## 🔒 Security Best Practices

### 1. Validate Template Variables

**Always validate template variable values** to prevent injection attacks.

✅ **Good**:
```php
'row_level' => [
    'template_variables' => [
        'auth.id' => fn() => (int) auth()->id(), // Cast to int
        'auth.role' => fn() => preg_replace('/[^a-z_]/', '', auth()->user()?->role),
        'auth.department' => fn() => (int) auth()->user()?->department_id,
    ],
],
```

❌ **Bad**:
```php
'row_level' => [
    'template_variables' => [
        'auth.id' => fn() => auth()->id(), // No validation
        'auth.role' => fn() => auth()->user()?->role, // No sanitization
    ],
],
```


### 2. Sanitize Conditional Expressions

**Validate and sanitize all conditional expressions** to prevent code injection.

✅ **Good**:
```php
// System validates operators
'conditional' => [
    'allowed_operators' => ['===', '!==', '>', '<', '>=', '<=', 'in', 'not_in', 'AND', 'OR', 'NOT'],
],

// Use safe conditions
$ruleManager->addConditionalRule(
    $permissionId,
    Post::class,
    "status === 'draft' AND user_id === {{auth.id}}" // Safe
);
```

❌ **Bad**:
```php
// Dangerous - allows arbitrary code
$condition = $request->input('condition'); // User input
$ruleManager->addConditionalRule(
    $permissionId,
    Post::class,
    $condition // NEVER do this!
);
```

### 3. Use Prepared Statements

**The system automatically uses prepared statements**, but verify in custom queries.

✅ **Good**:
```php
// Automatic prepared statements
$posts = Post::query()
    ->scopeByPermission(auth()->id(), 'posts.view')
    ->where('status', $status) // Safe
    ->get();
```

❌ **Bad**:
```php
// Raw SQL - vulnerable to injection
$posts = DB::select("
    SELECT * FROM posts 
    WHERE user_id = {$userId} 
    AND status = '{$status}'
"); // NEVER do this!
```

### 4. Implement Rate Limiting

**Rate limit permission checks** to prevent abuse.

```php
use Illuminate\Support\Facades\RateLimiter;

public function canAccessRow($userId, $permission, $model)
{
    $key = "rbac:check:{$userId}:{$permission}";
    
    if (RateLimiter::tooManyAttempts($key, 100)) {
        Log::warning('RBAC rate limit exceeded', [
            'user_id' => $userId,
            'permission' => $permission,
        ]);
        
        return false;
    }
    
    RateLimiter::hit($key, 60); // 100 checks per minute
    
    // ... normal permission check
}
```

### 5. Audit Sensitive Operations

**Log all access to sensitive resources** for security monitoring.

```php
use Illuminate\Support\Facades\Log;

public function canAccessColumn($userId, $permission, $model, $column)
{
    $canAccess = $this->evaluateColumnAccess($userId, $permission, $model, $column);
    
    // Log access to sensitive columns
    if (in_array($column, ['password', 'ssn', 'credit_card'])) {
        Log::channel('rbac')->info('Sensitive column access', [
            'user_id' => $userId,
            'permission' => $permission,
            'model' => get_class($model),
            'column' => $column,
            'allowed' => $canAccess,
            'ip' => request()->ip(),
            'timestamp' => now(),
        ]);
    }
    
    return $canAccess;
}
```


### 6. Encrypt Sensitive Rule Configurations

**Encrypt sensitive data in rule configurations** at rest.

```php
use Illuminate\Support\Facades\Crypt;

// When storing sensitive rule config
$ruleConfig = [
    'api_key' => Crypt::encryptString($apiKey),
    'secret' => Crypt::encryptString($secret),
];

$rule = PermissionRule::create([
    'permission_id' => $permissionId,
    'rule_type' => 'conditional',
    'rule_config' => $ruleConfig,
]);

// When retrieving
$apiKey = Crypt::decryptString($rule->rule_config['api_key']);
```

### 7. Implement Permission Versioning

**Track changes to permission rules** for audit and rollback.

```php
use Canvastack\Canvastack\Models\PermissionRule;

// Add version tracking
class PermissionRule extends Model
{
    protected static function boot()
    {
        parent::boot();
        
        static::updating(function ($rule) {
            // Store previous version
            PermissionRuleHistory::create([
                'rule_id' => $rule->id,
                'old_config' => $rule->getOriginal('rule_config'),
                'new_config' => $rule->rule_config,
                'changed_by' => auth()->id(),
                'changed_at' => now(),
            ]);
        });
    }
}
```

### 8. Validate User Overrides

**Require approval for user overrides** to prevent privilege escalation.

```php
public function addUserOverride($userId, $permissionId, $modelType, $modelId, $allowed)
{
    // Require admin approval
    if (!auth()->user()->isAdmin()) {
        throw new UnauthorizedException('User overrides require admin approval');
    }
    
    // Log the override
    Log::channel('rbac')->warning('User override created', [
        'target_user_id' => $userId,
        'permission_id' => $permissionId,
        'model_type' => $modelType,
        'model_id' => $modelId,
        'allowed' => $allowed,
        'created_by' => auth()->id(),
    ]);
    
    return UserPermissionOverride::create([
        'user_id' => $userId,
        'permission_id' => $permissionId,
        'model_type' => $modelType,
        'model_id' => $modelId,
        'allowed' => $allowed,
    ]);
}
```

---

## 🎨 Common Patterns

### Pattern 1: User Can Only Access Own Resources

**Use case**: Users can only view/edit their own posts, comments, etc.

```php
// Setup (one-time)
$ruleManager->addRowRule(
    permissionId: $permission->id,
    modelClass: Post::class,
    conditions: ['user_id' => '{{auth.id}}'],
    operator: 'AND'
);

// Controller
public function index(TableBuilder $table)
{
    $table->setPermission('posts.view');
    $table->setModel(new Post());
    $table->format(); // Automatically filters by user_id
    
    return view('posts.index', compact('table'));
}

// View
@canAccessRow('posts.edit', $post)
    <a href="{{ route('posts.edit', $post) }}">Edit</a>
@endcanAccessRow
```


### Pattern 2: Department-Based Access

**Use case**: Users can only access resources from their department.

```php
// Setup (one-time)
$ruleManager->addRowRule(
    permissionId: $permission->id,
    modelClass: Project::class,
    conditions: ['department_id' => '{{auth.department}}'],
    operator: 'AND'
);

// Controller
public function index()
{
    $projects = Project::query()
        ->scopeByPermission(auth()->id(), 'projects.view')
        ->with(['department', 'owner'])
        ->paginate(20);
    
    return view('projects.index', compact('projects'));
}
```

### Pattern 3: Role-Based Column Access

**Use case**: Different roles see different columns.

```php
// Setup for Editors
$editorPermission = Permission::where('name', 'posts.edit')
    ->whereHas('roles', fn($q) => $q->where('name', 'editor'))
    ->first();

$ruleManager->addColumnRule(
    permissionId: $editorPermission->id,
    modelClass: Post::class,
    allowedColumns: ['title', 'content', 'excerpt', 'tags'],
    deniedColumns: ['status', 'featured', 'published_at']
);

// Setup for Admins
$adminPermission = Permission::where('name', 'posts.edit')
    ->whereHas('roles', fn($q) => $q->where('name', 'admin'))
    ->first();

$ruleManager->addColumnRule(
    permissionId: $adminPermission->id,
    modelClass: Post::class,
    allowedColumns: ['*'], // All columns
    deniedColumns: []
);

// FormBuilder automatically filters
$this->form->setPermission('posts.edit');
$this->form->text('title', 'Title');
$this->form->select('status', 'Status', $options); // Hidden for editors
```

### Pattern 4: Status-Based Access

**Use case**: Users can only edit drafts, not published posts.

```php
// Setup
$ruleManager->addConditionalRule(
    permissionId: $permission->id,
    modelClass: Post::class,
    condition: "status === 'draft' AND user_id === {{auth.id}}"
);

// Controller
public function edit(Post $post)
{
    if (!$this->gate->canAccessRow(auth()->id(), 'posts.edit', $post)) {
        return redirect()
            ->back()
            ->with('error', __('rbac.fine_grained.cannot_edit_published'));
    }
    
    return view('posts.edit', compact('post'));
}
```

### Pattern 5: Time-Based Access

**Use case**: Users can only edit posts within 24 hours of creation.

```php
// Setup
$ruleManager->addConditionalRule(
    permissionId: $permission->id,
    modelClass: Post::class,
    condition: "user_id === {{auth.id}} AND created_at > {{now.minus.24h}}"
);

// Note: You'll need to add a custom template variable for time-based checks
'row_level' => [
    'template_variables' => [
        'now.minus.24h' => fn() => now()->subHours(24)->toDateTimeString(),
    ],
],
```


### Pattern 6: Hierarchical Access

**Use case**: Managers can access their team's resources.

```php
// Setup
$ruleManager->addRowRule(
    permissionId: $permission->id,
    modelClass: Task::class,
    conditions: [
        'assigned_to' => '{{auth.team_members}}', // Custom template variable
    ],
    operator: 'OR'
);

// Custom template variable
'row_level' => [
    'template_variables' => [
        'auth.team_members' => function() {
            return auth()->user()
                ->teamMembers()
                ->pluck('id')
                ->toArray();
        },
    ],
],
```

### Pattern 7: JSON Attribute Filtering

**Use case**: Different roles can edit different metadata fields.

```php
// Setup for Editors
$ruleManager->addJsonAttributeRule(
    permissionId: $editorPermission->id,
    modelClass: Post::class,
    jsonColumn: 'metadata',
    allowedPaths: ['layout.*', 'social.*'],
    deniedPaths: ['seo.*', 'featured', 'promoted']
);

// Setup for SEO Specialists
$ruleManager->addJsonAttributeRule(
    permissionId: $seoPermission->id,
    modelClass: Post::class,
    jsonColumn: 'metadata',
    allowedPaths: ['seo.*'],
    deniedPaths: ['layout.*', 'social.*']
);

// FormBuilder automatically filters JSON fields
$this->form->setPermission('posts.edit');
$this->form->json('metadata', 'Metadata'); // Only allowed paths shown
```

### Pattern 8: Temporary Access Grant

**Use case**: Grant temporary access to a specific resource.

```php
// Grant temporary access
$override = $ruleManager->addUserOverride(
    userId: $user->id,
    permissionId: $permission->id,
    modelType: Post::class,
    modelId: $post->id,
    allowed: true
);

// Schedule revocation
$expiresAt = now()->addHours(24);

// Using Laravel scheduler
Schedule::call(function () use ($override) {
    $override->delete();
})->at($expiresAt);

// Or store expiration in override
UserPermissionOverride::create([
    'user_id' => $user->id,
    'permission_id' => $permission->id,
    'model_type' => Post::class,
    'model_id' => $post->id,
    'allowed' => true,
    'expires_at' => $expiresAt,
]);
```

### Pattern 9: Multi-Condition Access

**Use case**: Complex access rules with multiple conditions.

```php
// Setup
$ruleManager->addConditionalRule(
    permissionId: $permission->id,
    modelClass: Post::class,
    condition: "(status === 'draft' OR status === 'pending') AND " .
               "user_id === {{auth.id}} AND " .
               "comments_count < 10"
);
```

### Pattern 10: Cascading Permissions

**Use case**: Parent resource access grants child resource access.

```php
// If user can access project, they can access project tasks
$ruleManager->addRowRule(
    permissionId: $taskPermission->id,
    modelClass: Task::class,
    conditions: [
        'project.user_id' => '{{auth.id}}', // Relationship check
    ]
);

// Ensure relationship is eager loaded
$tasks = Task::with('project')
    ->scopeByPermission(auth()->id(), 'tasks.view')
    ->get();
```

---

## 🚀 Optimization Techniques

### 1. Rule Consolidation

**Combine similar rules** to reduce evaluation overhead.

✅ **Good**:
```php
// Single rule with multiple conditions
$ruleManager->addRowRule(
    $permissionId,
    Post::class,
    conditions: [
        'user_id' => '{{auth.id}}',
        'department_id' => '{{auth.department}}',
    ],
    operator: 'OR' // User owns OR same department
);
```

❌ **Bad**:
```php
// Multiple separate rules
$ruleManager->addRowRule($permissionId, Post::class, ['user_id' => '{{auth.id}}']);
$ruleManager->addRowRule($permissionId, Post::class, ['department_id' => '{{auth.department}}']);
```


### 2. Selective Cache Invalidation

**Invalidate only affected cache entries** instead of clearing all cache.

✅ **Good**:
```php
// Invalidate specific user's cache
$ruleManager->clearRuleCache(
    userId: $user->id,
    permission: 'posts.edit'
);
```

❌ **Bad**:
```php
// Clear all RBAC cache
Cache::tags(['rbac:rules'])->flush();
```

### 3. Lazy Rule Loading

**Load rules only when needed** instead of preloading all rules.

```php
// Load rules on-demand
public function canAccessRow($userId, $permission, $model)
{
    // Check cache first
    $cacheKey = $this->getCacheKey($userId, $permission, $model);
    
    if ($cached = Cache::get($cacheKey)) {
        return $cached;
    }
    
    // Load rules only if cache miss
    $rules = $this->getRulesForPermission($permission);
    
    // Evaluate and cache
    $result = $this->evaluateRules($rules, $model);
    Cache::put($cacheKey, $result, $this->getTtl('row'));
    
    return $result;
}
```

### 4. Database Query Optimization

**Use indexes and optimize queries** for rule evaluation.

```sql
-- Add composite indexes for common queries
CREATE INDEX idx_permission_rules_lookup 
ON permission_rules(permission_id, rule_type, priority);

CREATE INDEX idx_user_overrides_lookup 
ON user_permission_overrides(user_id, permission_id, model_type, model_id);

-- Analyze query performance
EXPLAIN SELECT * FROM permission_rules 
WHERE permission_id = 1 AND rule_type = 'row' 
ORDER BY priority DESC;
```

### 5. Batch Cache Warming

**Warm cache in batches** during off-peak hours.

```php
// In a scheduled job
use Illuminate\Console\Command;

class WarmRbacCache extends Command
{
    protected $signature = 'rbac:warm-cache';
    
    public function handle(PermissionRuleManager $ruleManager)
    {
        $users = User::whereNotNull('last_login_at')
            ->where('last_login_at', '>', now()->subDays(7))
            ->get();
        
        $permissions = ['posts.view', 'posts.edit', 'posts.delete'];
        
        $this->info("Warming cache for {$users->count()} users...");
        
        $users->chunk(100)->each(function ($chunk) use ($ruleManager, $permissions) {
            foreach ($chunk as $user) {
                $ruleManager->warmUpUserCache($user->id, $permissions);
            }
        });
        
        $this->info('Cache warming completed!');
    }
}
```

---

## 🧪 Testing Best Practices

### 1. Test All Permission Scenarios

**Write comprehensive tests** for all permission rules.

```php
public function test_user_can_access_own_posts()
{
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);
    
    $this->actingAs($user);
    
    $this->assertTrue(
        $this->gate->canAccessRow($user->id, 'posts.edit', $post)
    );
}

public function test_user_cannot_access_others_posts()
{
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $otherUser->id]);
    
    $this->actingAs($user);
    
    $this->assertFalse(
        $this->gate->canAccessRow($user->id, 'posts.edit', $post)
    );
}

public function test_admin_can_access_all_posts()
{
    $admin = User::factory()->admin()->create();
    $post = Post::factory()->create();
    
    $this->actingAs($admin);
    
    $this->assertTrue(
        $this->gate->canAccessRow($admin->id, 'posts.edit', $post)
    );
}
```

### 2. Test Cache Behavior

**Verify caching works correctly** and improves performance.

```php
public function test_permission_check_is_cached()
{
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);
    
    // First call - cache miss
    $start = microtime(true);
    $result1 = $this->gate->canAccessRow($user->id, 'posts.edit', $post);
    $time1 = microtime(true) - $start;
    
    // Second call - cache hit
    $start = microtime(true);
    $result2 = $this->gate->canAccessRow($user->id, 'posts.edit', $post);
    $time2 = microtime(true) - $start;
    
    $this->assertEquals($result1, $result2);
    $this->assertLessThan($time1, $time2);
}
```

### 3. Test Edge Cases

**Test boundary conditions** and edge cases.

```php
public function test_permission_with_null_values()
{
    $post = Post::factory()->create(['user_id' => null]);
    
    $this->assertFalse(
        $this->gate->canAccessRow(auth()->id(), 'posts.edit', $post)
    );
}

public function test_permission_with_deleted_user()
{
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);
    
    $user->delete();
    
    $this->assertFalse(
        $this->gate->canAccessRow($user->id, 'posts.edit', $post)
    );
}
```

---

## 📊 Monitoring and Maintenance

### 1. Monitor Permission Denials

**Track permission denials** to identify issues.

```php
// In a scheduled job
use Illuminate\Support\Facades\Log;

class MonitorRbacDenials extends Command
{
    protected $signature = 'rbac:monitor-denials';
    
    public function handle()
    {
        $logs = File::get(storage_path('logs/rbac.log'));
        
        $denials = collect(explode("\n", $logs))
            ->filter(fn($line) => str_contains($line, 'Permission denied'))
            ->count();
        
        if ($denials > 100) {
            Log::alert('High number of RBAC denials', [
                'count' => $denials,
                'period' => 'last 24 hours',
            ]);
        }
    }
}
```

### 2. Regular Cache Cleanup

**Clean up stale cache entries** periodically.

```php
// In a scheduled job
class CleanRbacCache extends Command
{
    protected $signature = 'rbac:clean-cache';
    
    public function handle()
    {
        // Remove cache for inactive users
        $inactiveUsers = User::where('last_login_at', '<', now()->subMonths(3))
            ->pluck('id');
        
        foreach ($inactiveUsers as $userId) {
            Cache::tags(["rbac:user:{$userId}"])->flush();
        }
        
        $this->info("Cleaned cache for {$inactiveUsers->count()} inactive users");
    }
}
```

### 3. Audit Rule Changes

**Track all changes to permission rules** for compliance.

```php
use Illuminate\Support\Facades\Event;

Event::listen('eloquent.created: ' . PermissionRule::class, function ($rule) {
    Log::channel('rbac')->info('Permission rule created', [
        'rule_id' => $rule->id,
        'permission_id' => $rule->permission_id,
        'rule_type' => $rule->rule_type,
        'created_by' => auth()->id(),
    ]);
});

Event::listen('eloquent.updated: ' . PermissionRule::class, function ($rule) {
    Log::channel('rbac')->info('Permission rule updated', [
        'rule_id' => $rule->id,
        'old_config' => $rule->getOriginal('rule_config'),
        'new_config' => $rule->rule_config,
        'updated_by' => auth()->id(),
    ]);
});
```

---

## ⚠️ Common Pitfalls

### 1. Over-Caching

**Problem**: Cache TTL too long, stale permissions.

❌ **Bad**:
```php
'cache' => [
    'ttl' => [
        'row' => 86400, // 24 hours - too long
    ],
],
```

✅ **Good**:
```php
'cache' => [
    'ttl' => [
        'row' => 3600, // 1 hour - reasonable
    ],
],
```

### 2. Circular Dependencies

**Problem**: Rules that reference each other cause infinite loops.

❌ **Bad**:
```php
// Rule A depends on Rule B
$ruleManager->addConditionalRule($permA, Post::class, "has_permission('posts.delete')");

// Rule B depends on Rule A
$ruleManager->addConditionalRule($permB, Post::class, "has_permission('posts.edit')");
```

✅ **Good**:
```php
// Independent rules
$ruleManager->addRowRule($permA, Post::class, ['user_id' => '{{auth.id}}']);
$ruleManager->addRowRule($permB, Post::class, ['status' => 'draft']);
```

### 3. Missing Indexes

**Problem**: Slow queries due to missing database indexes.

❌ **Bad**:
```sql
-- No indexes on foreign keys
CREATE TABLE permission_rules (
    id BIGINT PRIMARY KEY,
    permission_id BIGINT,
    rule_type VARCHAR(50)
);
```

✅ **Good**:
```sql
-- Proper indexes
CREATE TABLE permission_rules (
    id BIGINT PRIMARY KEY,
    permission_id BIGINT,
    rule_type VARCHAR(50),
    INDEX idx_permission_rule_type (permission_id, rule_type)
);
```

### 4. Ignoring Super Admin

**Problem**: Applying fine-grained rules to super admins.

❌ **Bad**:
```php
public function canAccessRow($userId, $permission, $model)
{
    // Always check rules, even for super admin
    return $this->evaluateRules($userId, $permission, $model);
}
```

✅ **Good**:
```php
public function canAccessRow($userId, $permission, $model)
{
    // Super admin bypass
    if ($this->isSuperAdmin($userId)) {
        return true;
    }
    
    return $this->evaluateRules($userId, $permission, $model);
}
```

### 5. Not Using Query Scopes

**Problem**: N+1 queries when filtering lists.

❌ **Bad**:
```php
$posts = Post::all()->filter(function ($post) {
    return $this->gate->canAccessRow(auth()->id(), 'posts.view', $post);
});
```

✅ **Good**:
```php
$posts = Post::query()
    ->scopeByPermission(auth()->id(), 'posts.view')
    ->get();
```

---

## 📚 Documentation Best Practices

### 1. Document All Rules

**Maintain documentation** for all permission rules.

```php
/**
 * Permission Rules Documentation
 * 
 * posts.edit:
 * - Row-level: Users can only edit their own posts
 * - Column-level: Editors cannot edit 'status' field
 * - Conditional: Can only edit drafts
 * 
 * posts.delete:
 * - Row-level: Users can only delete their own posts
 * - Conditional: Can only delete posts with no comments
 */
```

### 2. Use Descriptive Rule Names

**Name rules clearly** to indicate their purpose.

✅ **Good**:
```php
// Clear naming
$ruleManager->addRowRule(
    $permissionId,
    Post::class,
    ['user_id' => '{{auth.id}}'],
    priority: 100,
    name: 'user_owns_post' // Descriptive name
);
```

### 3. Comment Complex Conditions

**Add comments** to explain complex conditional rules.

```php
// Users can edit posts if:
// 1. They own the post
// 2. Post is in draft status
// 3. Post was created within last 24 hours
// 4. Post has no comments
$ruleManager->addConditionalRule(
    $permissionId,
    Post::class,
    "user_id === {{auth.id}} AND " .
    "status === 'draft' AND " .
    "created_at > {{now.minus.24h}} AND " .
    "comments_count === 0"
);
```

---

## 🎯 Quick Reference

### Performance Checklist

- [ ] Caching enabled for all rule types
- [ ] Query scopes used for list filtering
- [ ] Eager loading configured for relationships
- [ ] Cache warmed for frequent users
- [ ] Cache hit rate > 80%
- [ ] Permission checks < 50ms

### Security Checklist

- [ ] Template variables validated
- [ ] Conditional expressions sanitized
- [ ] Prepared statements used
- [ ] Rate limiting implemented
- [ ] Sensitive operations audited
- [ ] User overrides require approval

### Code Quality Checklist

- [ ] All rules documented
- [ ] Tests cover all scenarios
- [ ] Edge cases tested
- [ ] Cache behavior tested
- [ ] Monitoring configured
- [ ] Audit logging enabled

---

## 📞 Support

### Getting Help

- **Documentation**: `.kiro/specs/fine-grained-permissions/`
- **API Reference**: `docs/api/rbac.md`
- **Examples**: `tests/Feature/FineGrainedPermissionsTest.php`
- **Migration Guide**: `migration-guide.md`

### Reporting Issues

- Use GitHub issues for bugs
- Tag with `fine-grained-permissions` label
- Include error logs and steps to reproduce
- Provide rule configuration

---

## 🔄 Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2026-02-28 | Initial best practices guide |

---

**Document Version**: 1.0.0  
**Last Updated**: 2026-02-28  
**Status**: Published  
**Author**: CanvaStack Team

