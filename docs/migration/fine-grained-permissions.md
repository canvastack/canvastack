# Fine-Grained Permissions System - Migration Guide

## 📋 Overview

This guide provides step-by-step instructions for migrating from the basic CanvaStack RBAC system to the Fine-Grained Permissions System. The migration is designed to be **non-breaking** and can be done incrementally.

**Migration Time**: 2-4 hours (depending on complexity)  
**Downtime Required**: None (zero-downtime migration)  
**Backward Compatibility**: 100% maintained

---

## 🎯 Migration Strategy

### Zero-Downtime Approach

The Fine-Grained Permissions System is designed as an **enhancement** to the existing RBAC system, not a replacement. This means:

✅ **Existing code continues to work** - No changes required to existing permission checks  
✅ **Gradual adoption** - Add fine-grained rules only where needed  
✅ **Fallback behavior** - If no fine-grained rules exist, basic RBAC applies  
✅ **No breaking changes** - All existing APIs remain functional

### Migration Phases

```
Phase 1: Preparation (30 min)
    ↓
Phase 2: Database Migration (15 min)
    ↓
Phase 3: Configuration (15 min)
    ↓
Phase 4: Testing (30 min)
    ↓
Phase 5: Gradual Rollout (1-2 hours)
    ↓
Phase 6: Optimization (30 min)
```

---

## 📦 Phase 1: Preparation (30 minutes)

### Step 1.1: Backup Database

**CRITICAL**: Always backup before migration.

```bash
# MySQL backup
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# PostgreSQL backup
pg_dump -U username database_name > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Step 1.2: Check Current RBAC Usage

Audit your current RBAC implementation:

```bash
# Find all Gate::allows() calls
grep -r "Gate::allows" app/

# Find all @can directives
grep -r "@can" resources/views/

# Find all $this->authorize() calls
grep -r "->authorize(" app/
```

**Document findings**:
- Which permissions are most frequently checked?
- Which models need row-level access control?
- Which fields need column-level restrictions?

### Step 1.3: Review Requirements

Identify which features you need:

- [ ] **Row-Level Permissions** - Users can only access their own data
- [ ] **Column-Level Permissions** - Hide sensitive fields from certain roles
- [ ] **JSON Attribute Permissions** - Control access to nested JSON fields
- [ ] **Conditional Permissions** - Dynamic access based on model state
- [ ] **User Overrides** - Individual user exceptions

### Step 1.4: Update Dependencies

Ensure you have the latest CanvaStack version:

```bash
cd packages/canvastack/canvastack
composer update
```

Verify version:

```bash
composer show canvastack/canvastack
```

**Required version**: 1.0.0 or higher

---

## 🗄️ Phase 2: Database Migration (15 minutes)

### Step 2.1: Run Migrations

```bash
php artisan migrate
```

This creates two new tables:
- `permission_rules` - Stores fine-grained permission rules
- `user_permission_overrides` - Stores user-specific overrides

### Step 2.2: Verify Tables

```bash
php artisan tinker
```

```php
// Check tables exist
Schema::hasTable('permission_rules'); // Should return true
Schema::hasTable('user_permission_overrides'); // Should return true

// Check columns
Schema::getColumnListing('permission_rules');
// ['id', 'permission_id', 'rule_type', 'rule_config', 'priority', 'created_at', 'updated_at']

Schema::getColumnListing('user_permission_overrides');
// ['id', 'user_id', 'permission_id', 'model_type', 'model_id', 'field_name', 'rule_config', 'allowed', 'created_at', 'updated_at']
```

### Step 2.3: Verify Indexes

```sql
-- MySQL
SHOW INDEX FROM permission_rules;
SHOW INDEX FROM user_permission_overrides;

-- PostgreSQL
\d permission_rules
\d user_permission_overrides
```

Expected indexes:
- `permission_rules`: `idx_permission_rule_type` on `(permission_id, rule_type)`
- `user_permission_overrides`: `idx_user_permission_model` on `(user_id, permission_id, model_type, model_id)`

### Step 2.4: Test Foreign Keys

```php
use Canvastack\Canvastack\Models\PermissionRule;
use Canvastack\Canvastack\Models\Permission;

// Test cascade delete
$permission = Permission::first();
$rule = PermissionRule::create([
    'permission_id' => $permission->id,
    'rule_type' => 'row',
    'rule_config' => ['test' => true],
]);

$permission->delete(); // Should cascade delete the rule
PermissionRule::find($rule->id); // Should return null
```

---

## ⚙️ Phase 3: Configuration (15 minutes)

### Step 3.1: Publish Configuration

```bash
php artisan vendor:publish --tag=canvastack-rbac-config --force
```

This updates `config/canvastack-rbac.php` with the `fine_grained` section.

### Step 3.2: Review Configuration

Open `config/canvastack-rbac.php` and review the `fine_grained` section:

```php
'fine_grained' => [
    // Enable/disable globally
    'enabled' => env('RBAC_FINE_GRAINED_ENABLED', true),
    
    // Cache configuration
    'cache' => [
        'enabled' => true,
        'ttl' => [
            'row' => 3600,
            'column' => 3600,
            'json_attribute' => 3600,
            'conditional' => 1800,
        ],
    ],
    
    // Row-level permissions
    'row_level' => [
        'enabled' => true,
        'template_variables' => [
            'auth.id' => fn() => auth()->id(),
            'auth.role' => fn() => auth()->user()?->role,
            'auth.department' => fn() => auth()->user()?->department_id,
        ],
    ],
    
    // Column-level permissions
    'column_level' => [
        'enabled' => true,
        'default_deny' => false,
    ],
    
    // JSON attribute permissions
    'json_attribute' => [
        'enabled' => true,
        'path_separator' => '.',
    ],
    
    // Conditional permissions
    'conditional' => [
        'enabled' => true,
        'allowed_operators' => ['===', '!==', '>', '<', '>=', '<=', 'in', 'not_in', 'AND', 'OR', 'NOT'],
    ],
    
    // Audit logging
    'audit' => [
        'enabled' => true,
        'log_denials' => true,
        'log_channel' => 'rbac',
    ],
],
```

### Step 3.3: Configure Environment

Add to `.env`:

```env
# Fine-Grained Permissions
RBAC_FINE_GRAINED_ENABLED=true

# Cache (if using Redis)
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Step 3.4: Configure Logging

Add RBAC log channel to `config/logging.php`:

```php
'channels' => [
    // ... existing channels
    
    'rbac' => [
        'driver' => 'daily',
        'path' => storage_path('logs/rbac.log'),
        'level' => 'info',
        'days' => 14,
    ],
],
```

### Step 3.5: Clear Configuration Cache

```bash
php artisan config:clear
php artisan cache:clear
```

---

## 🧪 Phase 4: Testing (30 minutes)

### Step 4.1: Test Basic Functionality

Create a test file `tests/Feature/FineGrainedPermissionsTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Auth\RBAC\Gate;
use Canvastack\Canvastack\Models\PermissionRule;
use App\Models\User;
use App\Models\Post;

class FineGrainedPermissionsTest extends TestCase
{
    public function test_row_level_permission()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        
        $ruleManager = app(PermissionRuleManager::class);
        $gate = app(Gate::class);
        
        // Add row-level rule
        $ruleManager->addRowRule(
            permissionId: 1,
            modelClass: Post::class,
            conditions: ['user_id' => '{{auth.id}}']
        );
        
        // Test access
        $this->actingAs($user);
        $this->assertTrue($gate->canAccessRow($user->id, 'posts.edit', $post));
    }
    
    public function test_column_level_permission()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        
        $ruleManager = app(PermissionRuleManager::class);
        $gate = app(Gate::class);
        
        // Add column-level rule
        $ruleManager->addColumnRule(
            permissionId: 1,
            modelClass: Post::class,
            allowedColumns: ['title', 'content'],
            deniedColumns: ['status', 'featured']
        );
        
        // Test access
        $this->actingAs($user);
        $this->assertTrue($gate->canAccessColumn($user->id, 'posts.edit', $post, 'title'));
        $this->assertFalse($gate->canAccessColumn($user->id, 'posts.edit', $post, 'status'));
    }
}
```

Run tests:

```bash
php artisan test --filter=FineGrainedPermissionsTest
```

### Step 4.2: Test Cache

```php
use Illuminate\Support\Facades\Cache;
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;

$ruleManager = app(PermissionRuleManager::class);

// First call - cache miss
$start = microtime(true);
$result1 = $ruleManager->canAccessRow($userId, 'posts.edit', $post);
$time1 = microtime(true) - $start;

// Second call - cache hit
$start = microtime(true);
$result2 = $ruleManager->canAccessRow($userId, 'posts.edit', $post);
$time2 = microtime(true) - $start;

echo "First call: {$time1}s\n";
echo "Second call: {$time2}s\n";
echo "Cache speedup: " . round($time1 / $time2, 2) . "x\n";
```

Expected output:
```
First call: 0.045s
Second call: 0.002s
Cache speedup: 22.5x
```

### Step 4.3: Test Backward Compatibility

Verify existing code still works:

```php
use Canvastack\Canvastack\Auth\RBAC\Gate;

$gate = app(Gate::class);

// Old API - should still work
$this->assertTrue($gate->allows('posts.edit', $userId));
$this->assertFalse($gate->denies('posts.delete', $userId));

// New API - should also work
$this->assertTrue($gate->canAccessRow($userId, 'posts.edit', $post));
```

---

## 🚀 Phase 5: Gradual Rollout (1-2 hours)

### Step 5.1: Start with One Feature

Choose a simple use case to start with. Example: **Users can only edit their own posts**.

#### Before (Basic RBAC):

```php
// Controller
public function edit(Post $post)
{
    // Manual check
    if ($post->user_id !== auth()->id()) {
        abort(403);
    }
    
    return view('posts.edit', compact('post'));
}
```

#### After (Fine-Grained Permissions):

```php
// Add rule (one-time setup)
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;

$ruleManager = app(PermissionRuleManager::class);
$ruleManager->addRowRule(
    permissionId: Permission::where('name', 'posts.edit')->first()->id,
    modelClass: Post::class,
    conditions: ['user_id' => '{{auth.id}}']
);

// Controller - simplified
public function edit(Post $post)
{
    $this->authorize('canAccessRow', ['posts.edit', $post]);
    
    return view('posts.edit', compact('post'));
}
```

### Step 5.2: Add Column-Level Restrictions

Example: **Editors cannot edit the 'status' field**.

```php
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;

$ruleManager = app(PermissionRuleManager::class);

// Get permission ID for 'posts.edit'
$permission = Permission::where('name', 'posts.edit')->first();

// Add column-level rule
$ruleManager->addColumnRule(
    permissionId: $permission->id,
    modelClass: Post::class,
    allowedColumns: ['title', 'content', 'excerpt', 'tags'],
    deniedColumns: ['status', 'featured', 'published_at']
);
```

Update FormBuilder:

```php
// Before
$this->form->text('title', 'Title');
$this->form->select('status', 'Status', $options);

// After - FormBuilder automatically filters fields
$this->form->setPermission('posts.edit');
$this->form->text('title', 'Title');
$this->form->select('status', 'Status', $options); // Automatically hidden if denied
```

### Step 5.3: Add JSON Attribute Restrictions

Example: **Editors cannot edit SEO metadata**.

```php
$ruleManager->addJsonAttributeRule(
    permissionId: $permission->id,
    modelClass: Post::class,
    jsonColumn: 'metadata',
    allowedPaths: ['layout.*', 'social.*'],
    deniedPaths: ['seo.*', 'featured', 'promoted']
);
```

### Step 5.4: Add Conditional Permissions

Example: **Users can only edit posts with status='draft'**.

```php
$ruleManager->addConditionalRule(
    permissionId: $permission->id,
    modelClass: Post::class,
    condition: "status === 'draft' AND user_id === {{auth.id}}"
);
```

### Step 5.5: Add User Overrides

Example: **Give specific user access to edit a specific post**.

```php
$ruleManager->addUserOverride(
    userId: $user->id,
    permissionId: $permission->id,
    modelType: Post::class,
    modelId: $post->id,
    allowed: true
);
```

### Step 5.6: Update Views

#### Blade Directives

```blade
{{-- Before --}}
@can('posts.edit')
    <a href="{{ route('posts.edit', $post) }}">Edit</a>
@endcan

{{-- After - with row-level check --}}
@canAccessRow('posts.edit', $post)
    <a href="{{ route('posts.edit', $post) }}">Edit</a>
@endcanAccessRow

{{-- Column-level check --}}
@canAccessColumn('posts.edit', $post, 'status')
    <select name="status">...</select>
@endcanAccessColumn

{{-- JSON attribute check --}}
@canAccessJsonAttribute('posts.edit', $post, 'metadata', 'seo.title')
    <input name="metadata[seo][title]" />
@endcanAccessJsonAttribute
```

#### FormBuilder Integration

```php
// Automatically filters fields based on permissions
$this->form->setPermission('posts.edit');
$this->form->text('title', 'Title');
$this->form->text('status', 'Status'); // Hidden if denied
```

#### TableBuilder Integration

```php
// Automatically filters columns and rows
$this->table->setPermission('posts.view');
$this->table->setFields([
    'title:Title',
    'status:Status', // Hidden if denied
    'created_at:Created'
]);
```

---

## 🔧 Phase 6: Optimization (30 minutes)

### Step 6.1: Warm Up Cache

```php
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;

$ruleManager = app(PermissionRuleManager::class);

// Warm up cache for all users
$users = User::all();
$permissions = ['posts.view', 'posts.edit', 'posts.delete'];

foreach ($users as $user) {
    $ruleManager->warmUpUserCache($user->id, $permissions);
}
```

### Step 6.2: Monitor Performance

Add monitoring to `app/Http/Middleware/MonitorRBAC.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class MonitorRBAC
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = microtime(true) - $start;
        
        if ($duration > 0.1) { // Log slow permission checks
            Log::channel('rbac')->warning('Slow RBAC check', [
                'duration' => $duration,
                'url' => $request->url(),
                'user_id' => auth()->id(),
            ]);
        }
        
        return $response;
    }
}
```

Register middleware in `app/Http/Kernel.php`:

```php
protected $middleware = [
    // ...
    \App\Http\Middleware\MonitorRBAC::class,
];
```

### Step 6.3: Optimize Queries

Check for N+1 queries:

```bash
# Install Laravel Debugbar
composer require barryvdh/laravel-debugbar --dev

# Enable query logging
DB::enableQueryLog();

# Make request
// ...

# Check queries
dd(DB::getQueryLog());
```

Add eager loading where needed:

```php
// Before
$posts = Post::all();
foreach ($posts as $post) {
    if ($gate->canAccessRow($userId, 'posts.edit', $post)) {
        // ...
    }
}

// After - use query scope
$posts = Post::query()
    ->scopeByPermission($userId, 'posts.edit')
    ->get();
```

### Step 6.4: Review Cache Hit Rates

```php
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

// Get cache statistics
$stats = Redis::info('stats');

echo "Cache hits: " . $stats['keyspace_hits'] . "\n";
echo "Cache misses: " . $stats['keyspace_misses'] . "\n";
echo "Hit rate: " . round($stats['keyspace_hits'] / ($stats['keyspace_hits'] + $stats['keyspace_misses']) * 100, 2) . "%\n";
```

Target: > 80% hit rate

---

## 📊 Migration Checklist

### Pre-Migration

- [ ] Database backup completed
- [ ] Current RBAC usage documented
- [ ] Requirements identified
- [ ] Dependencies updated

### Migration

- [ ] Migrations run successfully
- [ ] Tables and indexes verified
- [ ] Foreign keys tested
- [ ] Configuration published
- [ ] Environment variables set
- [ ] Logging configured

### Testing

- [ ] Unit tests passing
- [ ] Feature tests passing
- [ ] Cache working correctly
- [ ] Backward compatibility verified
- [ ] Performance acceptable

### Rollout

- [ ] First feature migrated
- [ ] Column-level restrictions added
- [ ] JSON attribute restrictions added
- [ ] Conditional permissions added
- [ ] User overrides tested
- [ ] Views updated
- [ ] Components integrated

### Optimization

- [ ] Cache warmed up
- [ ] Monitoring enabled
- [ ] Queries optimized
- [ ] Cache hit rate > 80%

---

## 🚨 Troubleshooting

### Issue 1: Migration Fails

**Error**: `SQLSTATE[42S01]: Base table or view already exists`

**Solution**:
```bash
# Check if tables exist
php artisan tinker
Schema::hasTable('permission_rules');

# If true, skip migration or drop tables
php artisan migrate:rollback --step=1
php artisan migrate
```

### Issue 2: Cache Not Working

**Error**: Cache always misses

**Solution**:
```bash
# Check Redis connection
php artisan tinker
Cache::put('test', 'value', 60);
Cache::get('test'); // Should return 'value'

# Check Redis is running
redis-cli ping # Should return PONG

# Clear cache
php artisan cache:clear
```

### Issue 3: Permission Denied Unexpectedly

**Error**: User cannot access resource they should be able to

**Solution**:
```php
// Enable debug logging
config(['canvastack-rbac.fine_grained.audit.enabled' => true]);

// Check logs
tail -f storage/logs/rbac.log

// Check rules
$rules = PermissionRule::where('permission_id', $permissionId)->get();
dd($rules);

// Check cache
$key = "canvastack:rbac:rules:can_access_row:{$userId}:{$permission}:{$modelType}:{$modelId}";
$cached = Cache::get($key);
dd($cached);
```

### Issue 4: Slow Performance

**Error**: Permission checks taking > 100ms

**Solution**:
```php
// Check cache hit rate
$stats = Redis::info('stats');
$hitRate = $stats['keyspace_hits'] / ($stats['keyspace_hits'] + $stats['keyspace_misses']);

if ($hitRate < 0.8) {
    // Warm up cache
    $ruleManager->warmUpCache($userId, $permissions);
}

// Check for N+1 queries
DB::enableQueryLog();
// ... make request ...
dd(DB::getQueryLog());

// Add eager loading
$table->eager(['user', 'category']);
```

### Issue 5: FormBuilder Not Filtering Fields

**Error**: Denied fields still showing in form

**Solution**:
```php
// Ensure permission is set
$this->form->setPermission('posts.edit');

// Check if rule exists
$rules = PermissionRule::where('permission_id', $permissionId)
    ->where('rule_type', 'column')
    ->get();
dd($rules);

// Clear cache
Cache::tags(['rbac:rules'])->flush();
```

---

## 📚 Common Migration Patterns

### Pattern 1: Migrate CRUD Operations

**Before**:
```php
public function update(Request $request, Post $post)
{
    if ($post->user_id !== auth()->id()) {
        abort(403);
    }
    
    if (auth()->user()->role !== 'admin') {
        unset($request['status']);
    }
    
    $post->update($request->all());
}
```

**After**:
```php
// Setup (one-time)
$ruleManager->addRowRule($permissionId, Post::class, ['user_id' => '{{auth.id}}']);
$ruleManager->addColumnRule($permissionId, Post::class, ['title', 'content'], ['status']);

// Controller
public function update(Request $request, Post $post)
{
    $this->authorize('canAccessRow', ['posts.edit', $post]);
    
    $allowedFields = $this->gate->getAccessibleColumns(
        auth()->id(),
        'posts.edit',
        Post::class
    );
    
    $post->update($request->only($allowedFields));
}
```

### Pattern 2: Migrate List Views

**Before**:
```php
public function index()
{
    $posts = Post::where('user_id', auth()->id())->get();
    return view('posts.index', compact('posts'));
}
```

**After**:
```php
// Setup (one-time)
$ruleManager->addRowRule($permissionId, Post::class, ['user_id' => '{{auth.id}}']);

// Controller
public function index(TableBuilder $table)
{
    $table->setPermission('posts.view');
    $table->setModel(new Post());
    $table->format(); // Automatically applies row-level filtering
    
    return view('posts.index', compact('table'));
}
```

### Pattern 3: Migrate API Endpoints

**Before**:
```php
public function show(Post $post)
{
    if ($post->user_id !== auth()->id()) {
        return response()->json(['error' => 'Forbidden'], 403);
    }
    
    return response()->json($post);
}
```

**After**:
```php
// Setup (one-time)
$ruleManager->addRowRule($permissionId, Post::class, ['user_id' => '{{auth.id}}']);

// Controller
public function show(Post $post)
{
    if (!$this->gate->canAccessRow(auth()->id(), 'posts.view', $post)) {
        return response()->json(['error' => 'Forbidden'], 403);
    }
    
    return response()->json($post);
}
```

---

## 🎓 Best Practices

### 1. Start Simple

Begin with row-level permissions, then add column-level and conditional as needed.

### 2. Use Query Scopes

Always use `scopeByPermission()` for list queries to avoid N+1 problems.

### 3. Cache Aggressively

Enable caching for all permission checks. The system handles cache invalidation automatically.

### 4. Monitor Performance

Use Laravel Debugbar or Telescope to monitor query counts and response times.

### 5. Test Thoroughly

Write tests for all permission rules before deploying to production.

### 6. Document Rules

Keep a document of all permission rules for reference.

### 7. Use User Overrides Sparingly

User overrides should be exceptions, not the rule.

### 8. Audit Regularly

Review audit logs regularly to identify permission issues.

---

## 📞 Support

### Getting Help

- **Documentation**: `.kiro/specs/fine-grained-permissions/`
- **API Reference**: `docs/api/rbac.md`
- **Examples**: `tests/Feature/FineGrainedPermissionsTest.php`

### Reporting Issues

- Use GitHub issues for bugs
- Tag with `fine-grained-permissions` label
- Include error logs and steps to reproduce

---

## 🔄 Rollback Plan

If you need to rollback:

### Step 1: Disable Fine-Grained Permissions

```env
RBAC_FINE_GRAINED_ENABLED=false
```

```bash
php artisan config:clear
php artisan cache:clear
```

### Step 2: Revert Code Changes

```bash
git revert <commit-hash>
```

### Step 3: Drop Tables (Optional)

```bash
php artisan migrate:rollback --step=2
```

### Step 4: Restore Backup

```bash
# MySQL
mysql -u username -p database_name < backup.sql

# PostgreSQL
psql -U username database_name < backup.sql
```

---

## ✅ Success Criteria

Migration is successful when:

- [ ] All tests passing
- [ ] No performance degradation
- [ ] Cache hit rate > 80%
- [ ] No errors in logs
- [ ] Existing functionality works
- [ ] New fine-grained rules work
- [ ] Users can access appropriate resources
- [ ] Denied access is logged

---

**Document Version**: 1.0.0  
**Last Updated**: 2026-02-28  
**Status**: Published  
**Author**: CanvaStack Team
