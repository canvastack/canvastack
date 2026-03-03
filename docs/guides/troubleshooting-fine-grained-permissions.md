# Fine-Grained Permissions System - Troubleshooting Guide

## Overview

This guide provides solutions to common issues encountered when implementing and using the Fine-Grained Permissions System in CanvaStack.

**Status**: Published  
**Version**: 1.0.0  
**Last Updated**: 2026-02-28

---

## Table of Contents

1. [Permission Check Issues](#permission-check-issues)
2. [Cache-Related Problems](#cache-related-problems)
3. [Performance Issues](#performance-issues)
4. [Database Issues](#database-issues)
5. [Integration Issues](#integration-issues)
6. [Configuration Problems](#configuration-problems)
7. [Testing Issues](#testing-issues)
8. [Migration Issues](#migration-issues)

---

## Permission Check Issues

### Issue 1: Permission Always Returns False

**Symptoms:**
- `Gate::canAccessRow()` always returns `false`
- User cannot access resources they should have access to
- No error messages in logs

**Possible Causes:**

1. **Basic permission not granted**
   ```php
   // Check if user has basic permission first
   if (!Gate::allows('posts.edit', $userId)) {
       // User doesn't have basic permission
   }
   ```

2. **Template variables not resolving**
   ```php
   // Check template variable resolution
   $resolver = app(TemplateVariableResolver::class);
   $resolved = $resolver->resolve('{{auth.id}}');
   dd($resolved); // Should show actual user ID
   ```

3. **Rule configuration incorrect**
   ```php
   // Verify rule configuration
   $rule = PermissionRule::find($ruleId);
   dd($rule->rule_config);
   ```

**Solutions:**

1. **Verify basic permission:**
   ```php
   // In your controller
   if (!Gate::allows('posts.edit')) {
       abort(403, 'No basic permission');
   }
   
   // Then check fine-grained
   if (!Gate::canAccessRow('posts.edit', $post)) {
       abort(403, 'No row-level access');
   }
   ```

2. **Check rule evaluation:**
   ```php
   // Enable debug logging
   Log::debug('Rule evaluation', [
       'user_id' => $userId,
       'permission' => $permission,
       'model' => get_class($model),
       'rule_config' => $rule->rule_config,
   ]);
   ```

3. **Verify template variables:**
   ```php
   // Register custom variables if needed
   $resolver->register('auth.department', fn() => auth()->user()->department_id);
   ```

---

### Issue 2: Super Admin Bypass Not Working

**Symptoms:**
- Super admin users are being denied access
- Super admin should bypass all checks

**Possible Causes:**

1. **Super admin check not implemented**
2. **Wrong user role check**
3. **Cache returning old results**

**Solutions:**

1. **Verify super admin check in Gate:**
   ```php
   // In Gate::canAccessRow()
   if ($this->isSuperAdmin($userId)) {
       return true; // Bypass all checks
   }
   ```

2. **Check super admin role:**
   ```php
   // Verify user has super admin role
   $user = User::find($userId);
   dd($user->role); // Should be 'super_admin' or similar
   ```

3. **Clear cache:**
   ```bash
   php artisan cache:clear
   php artisan cache:forget "canvastack:rbac:rules:*"
   ```

---

### Issue 3: User Overrides Not Applied

**Symptoms:**
- User-specific overrides are ignored
- Override exists in database but not working

**Possible Causes:**

1. **Override not checked before rules**
2. **Cache not invalidated after creating override**
3. **Override configuration incorrect**

**Solutions:**

1. **Verify override check order:**
   ```php
   // In PermissionRuleManager::canAccessRow()
   // Check overrides FIRST
   $override = $this->getUserOverride($userId, $permissionId, $model);
   if ($override) {
       return $override->allowed;
   }
   
   // Then check rules
   return $this->evaluateRules($userId, $permission, $model);
   ```

2. **Clear cache after creating override:**
   ```php
   $ruleManager->addUserOverride($userId, $permissionId, $modelType, $modelId);
   $ruleManager->clearRuleCache($userId, $permission);
   ```

3. **Verify override configuration:**
   ```php
   $override = UserPermissionOverride::where('user_id', $userId)
       ->where('permission_id', $permissionId)
       ->where('model_type', get_class($model))
       ->where('model_id', $model->id)
       ->first();
   
   dd($override); // Should exist and have correct 'allowed' value
   ```

---

## Cache-Related Problems

### Issue 4: Stale Cache Data

**Symptoms:**
- Permission changes not reflected immediately
- Old permission results returned
- Cache hit rate too high (100%)

**Possible Causes:**

1. **Cache not invalidated on rule changes**
2. **Cache TTL too long**
3. **Cache tags not working**

**Solutions:**

1. **Clear cache after rule changes:**
   ```php
   // After creating/updating/deleting rule
   $ruleManager->clearRuleCache();
   
   // Or clear specific user cache
   $ruleManager->clearRuleCache($userId, $permission);
   ```

2. **Adjust cache TTL:**
   ```php
   // In config/canvastack-rbac.php
   'fine_grained' => [
       'cache' => [
           'ttl' => [
               'row' => 1800,        // 30 minutes instead of 1 hour
               'column' => 1800,
               'json_attribute' => 1800,
               'conditional' => 900, // 15 minutes
           ],
       ],
   ],
   ```

3. **Verify cache tags:**
   ```php
   // Check if Redis supports tags
   $cache = Cache::store('redis');
   $cache->tags(['rbac:rules'])->flush();
   ```

---

### Issue 5: Cache Not Working

**Symptoms:**
- Cache hit rate is 0%
- Performance is slow
- Every request hits database

**Possible Causes:**

1. **Redis not configured**
2. **Cache disabled in config**
3. **Cache driver not supporting tags**

**Solutions:**

1. **Verify Redis configuration:**
   ```php
   // Test Redis connection
   try {
       Cache::store('redis')->put('test', 'value', 60);
       $value = Cache::store('redis')->get('test');
       if ($value !== 'value') {
           throw new Exception('Redis not working');
       }
   } catch (Exception $e) {
       Log::error('Redis error: ' . $e->getMessage());
   }
   ```

2. **Enable cache in config:**
   ```php
   // In config/canvastack-rbac.php
   'fine_grained' => [
       'cache' => [
           'enabled' => true, // Make sure this is true
       ],
   ],
   ```

3. **Use Redis for cache:**
   ```php
   // In .env
   CACHE_DRIVER=redis
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

---

## Performance Issues

### Issue 6: Slow Permission Checks

**Symptoms:**
- Permission checks take > 100ms
- Page load time increased significantly
- High database query count

**Possible Causes:**

1. **N+1 query problem**
2. **Cache not enabled**
3. **Too many rules to evaluate**
4. **No indexes on database**

**Solutions:**

1. **Enable eager loading:**
   ```php
   // In PermissionRuleManager
   $rules = PermissionRule::with('permission')
       ->where('permission_id', $permissionId)
       ->get();
   ```

2. **Enable caching:**
   ```php
   $ruleManager->cache(true); // Enable caching
   ```

3. **Optimize rule evaluation:**
   ```php
   // Use priority to evaluate most common rules first
   $rules = PermissionRule::where('permission_id', $permissionId)
       ->orderBy('priority', 'desc')
       ->get();
   
   foreach ($rules as $rule) {
       if ($rule->evaluate($model)) {
           return true; // Stop at first match
       }
   }
   ```

4. **Add database indexes:**
   ```sql
   CREATE INDEX idx_permission_rule_type ON permission_rules(permission_id, rule_type);
   CREATE INDEX idx_user_override ON user_permission_overrides(user_id, permission_id, model_type, model_id);
   ```

---

### Issue 7: High Memory Usage

**Symptoms:**
- Memory usage > 128MB
- Out of memory errors
- Slow performance

**Possible Causes:**

1. **Loading too many rules at once**
2. **Not using chunk processing**
3. **Cache storing too much data**

**Solutions:**

1. **Use lazy loading:**
   ```php
   // Instead of loading all rules
   $rules = PermissionRule::where('permission_id', $permissionId)
       ->cursor(); // Use cursor for lazy loading
   
   foreach ($rules as $rule) {
       // Process one at a time
   }
   ```

2. **Limit cache size:**
   ```php
   // Store only essential data in cache
   $cacheData = [
       'result' => $result,
       'timestamp' => time(),
   ];
   // Don't store entire model or large objects
   ```

3. **Use chunk processing:**
   ```php
   PermissionRule::where('permission_id', $permissionId)
       ->chunk(100, function ($rules) {
           foreach ($rules as $rule) {
               // Process in chunks
           }
       });
   ```

---

## Database Issues

### Issue 8: Migration Fails

**Symptoms:**
- Migration error during `php artisan migrate`
- Foreign key constraint errors
- Table already exists errors

**Possible Causes:**

1. **Permissions table doesn't exist**
2. **Foreign key constraints fail**
3. **Migration already run**

**Solutions:**

1. **Verify permissions table exists:**
   ```bash
   php artisan tinker
   Schema::hasTable('permissions'); // Should return true
   ```

2. **Run migrations in order:**
   ```bash
   # Run RBAC migrations first
   php artisan migrate --path=packages/canvastack/canvastack/database/migrations/rbac
   
   # Then run fine-grained migrations
   php artisan migrate --path=packages/canvastack/canvastack/database/migrations/fine-grained
   ```

3. **Rollback and retry:**
   ```bash
   php artisan migrate:rollback --step=1
   php artisan migrate
   ```

---

### Issue 9: Foreign Key Constraint Errors

**Symptoms:**
- Cannot delete permission
- Foreign key constraint fails
- Cascade delete not working

**Possible Causes:**

1. **CASCADE not set on foreign keys**
2. **Related records exist**
3. **Database doesn't support foreign keys**

**Solutions:**

1. **Verify CASCADE is set:**
   ```php
   // In migration
   $table->foreign('permission_id')
       ->references('id')
       ->on('permissions')
       ->onDelete('cascade'); // Make sure this is set
   ```

2. **Manually delete related records:**
   ```php
   // Before deleting permission
   PermissionRule::where('permission_id', $permissionId)->delete();
   UserPermissionOverride::where('permission_id', $permissionId)->delete();
   
   // Then delete permission
   Permission::find($permissionId)->delete();
   ```

3. **Check database engine:**
   ```sql
   -- MySQL: Verify using InnoDB (supports foreign keys)
   SHOW TABLE STATUS WHERE Name = 'permission_rules';
   -- Engine should be InnoDB
   ```

---

## Integration Issues

### Issue 10: FormBuilder Not Filtering Fields

**Symptoms:**
- All form fields shown regardless of permissions
- Hidden fields still visible
- Permission filtering not working

**Possible Causes:**

1. **Permission not set on FormBuilder**
2. **Column-level rules not defined**
3. **FormBuilder not calling PermissionRuleManager**

**Solutions:**

1. **Set permission on FormBuilder:**
   ```php
   $form->setPermission('posts.edit'); // Must call this
   ```

2. **Define column-level rules:**
   ```php
   $ruleManager->addColumnRule(
       $permissionId,
       Post::class,
       ['title', 'content', 'excerpt'], // Allowed columns
       ['status', 'featured'] // Denied columns
   );
   ```

3. **Verify FormBuilder integration:**
   ```php
   // In FormBuilder::render()
   if ($this->permission) {
       $accessibleColumns = $this->ruleManager->getAccessibleColumns(
           auth()->id(),
           $this->permission,
           $this->modelClass
       );
       
       // Filter fields
       $this->fields = array_filter($this->fields, function($field) use ($accessibleColumns) {
           return in_array($field->name, $accessibleColumns);
       });
   }
   ```

---

### Issue 11: TableBuilder Not Filtering Rows

**Symptoms:**
- All rows shown regardless of permissions
- Row-level filtering not working
- Users see data they shouldn't

**Possible Causes:**

1. **Permission not set on TableBuilder**
2. **Row-level rules not defined**
3. **Query scope not applied**

**Solutions:**

1. **Set permission on TableBuilder:**
   ```php
   $table->setPermission('posts.view'); // Must call this
   ```

2. **Define row-level rules:**
   ```php
   $ruleManager->addRowRule(
       $permissionId,
       Post::class,
       ['user_id' => '{{auth.id}}'], // Conditions
       'AND' // Operator
   );
   ```

3. **Apply query scope:**
   ```php
   // In TableBuilder::format()
   if ($this->permission) {
       $this->query = $this->ruleManager->scopeByPermission(
           $this->query,
           auth()->id(),
           $this->permission
       );
   }
   ```

---

## Configuration Problems

### Issue 12: Configuration Not Loaded

**Symptoms:**
- Default configuration used instead of custom
- Config changes not reflected
- Fine-grained permissions disabled

**Possible Causes:**

1. **Config not published**
2. **Config cache not cleared**
3. **Wrong config file**

**Solutions:**

1. **Publish config:**
   ```bash
   php artisan vendor:publish --tag=canvastack-rbac-config --force
   ```

2. **Clear config cache:**
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

3. **Verify config file:**
   ```php
   // Check if config exists
   dd(config('canvastack-rbac.fine_grained'));
   
   // Should return array with configuration
   ```

---

### Issue 13: Template Variables Not Working

**Symptoms:**
- `{{auth.id}}` not replaced with actual value
- Template variables remain as strings
- Permission checks fail

**Possible Causes:**

1. **Template variables not registered**
2. **Variable resolver not called**
3. **Wrong variable syntax**

**Solutions:**

1. **Register template variables:**
   ```php
   // In config/canvastack-rbac.php
   'fine_grained' => [
       'row_level' => [
           'template_variables' => [
               'auth.id' => fn() => auth()->id(),
               'auth.role' => fn() => auth()->user()?->role,
               'auth.department' => fn() => auth()->user()?->department_id,
           ],
       ],
   ],
   ```

2. **Verify variable resolution:**
   ```php
   $resolver = app(TemplateVariableResolver::class);
   $resolved = $resolver->resolve('{{auth.id}}');
   dd($resolved); // Should show actual user ID, not "{{auth.id}}"
   ```

3. **Use correct syntax:**
   ```php
   // Correct
   'user_id' => '{{auth.id}}'
   
   // Wrong
   'user_id' => '{auth.id}'
   'user_id' => '{{ auth.id }}'
   ```

---

## Testing Issues

### Issue 14: Tests Failing

**Symptoms:**
- Unit tests fail
- Feature tests fail
- Permission checks not working in tests

**Possible Causes:**

1. **Database not seeded**
2. **Cache not cleared between tests**
3. **User not authenticated**

**Solutions:**

1. **Seed database in tests:**
   ```php
   public function setUp(): void
   {
       parent::setUp();
       
       // Seed permissions
       Permission::create(['name' => 'posts.edit']);
       
       // Seed rules
       PermissionRule::create([
           'permission_id' => 1,
           'rule_type' => 'row',
           'rule_config' => ['user_id' => '{{auth.id}}'],
       ]);
   }
   ```

2. **Clear cache between tests:**
   ```php
   public function tearDown(): void
   {
       Cache::flush();
       parent::tearDown();
   }
   ```

3. **Authenticate user:**
   ```php
   public function test_user_can_access_own_post()
   {
       $user = User::factory()->create();
       $this->actingAs($user); // Authenticate
       
       $post = Post::factory()->create(['user_id' => $user->id]);
       
       $this->assertTrue(Gate::canAccessRow('posts.edit', $post));
   }
   ```

---

### Issue 15: Cache Tests Failing

**Symptoms:**
- Cache hit rate tests fail
- Cache not working in tests
- Redis connection errors

**Possible Causes:**

1. **Redis not running**
2. **Test using array cache**
3. **Cache not configured for tests**

**Solutions:**

1. **Use array cache for tests:**
   ```php
   // In phpunit.xml
   <env name="CACHE_DRIVER" value="array"/>
   ```

2. **Mock cache in tests:**
   ```php
   public function test_permission_check_uses_cache()
   {
       Cache::shouldReceive('remember')
           ->once()
           ->andReturn(true);
       
       $result = Gate::canAccessRow('posts.edit', $post);
       
       $this->assertTrue($result);
   }
   ```

3. **Start Redis for tests:**
   ```bash
   # Start Redis
   redis-server
   
   # Or use Docker
   docker run -d -p 6379:6379 redis:7
   ```

---

## Migration Issues

### Issue 16: Migrating from Basic RBAC

**Symptoms:**
- Existing permissions not working
- Users lose access after migration
- Rules not applied to existing permissions

**Possible Causes:**

1. **Rules not created for existing permissions**
2. **User roles not migrated**
3. **Cache not cleared after migration**

**Solutions:**

1. **Create rules for existing permissions:**
   ```php
   // Migration script
   $permissions = Permission::all();
   
   foreach ($permissions as $permission) {
       // Create default row-level rule
       PermissionRule::create([
           'permission_id' => $permission->id,
           'rule_type' => 'row',
           'rule_config' => ['user_id' => '{{auth.id}}'],
           'priority' => 0,
       ]);
   }
   ```

2. **Verify user roles:**
   ```php
   // Check if users still have roles
   $users = User::with('roles')->get();
   foreach ($users as $user) {
       if ($user->roles->isEmpty()) {
           // Assign default role
           $user->roles()->attach($defaultRoleId);
       }
   }
   ```

3. **Clear all caches:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   php artisan route:clear
   ```

---

## Debugging Tips

### Enable Debug Logging

```php
// In PermissionRuleManager
Log::debug('Permission check', [
    'user_id' => $userId,
    'permission' => $permission,
    'model_type' => get_class($model),
    'model_id' => $model->id ?? null,
    'rules_count' => $rules->count(),
    'cache_hit' => $cacheHit,
]);
```

### Check Cache Keys

```php
// View cache keys
$keys = Cache::getRedis()->keys('canvastack:rbac:rules:*');
dd($keys);

// View cache value
$value = Cache::get('canvastack:rbac:rules:user:1:posts.edit:Post:123');
dd($value);
```

### Monitor Performance

```php
// Add timing
$start = microtime(true);

$result = Gate::canAccessRow('posts.edit', $post);

$duration = (microtime(true) - $start) * 1000;
Log::info("Permission check took {$duration}ms");
```

### Verify Database State

```php
// Check rules
$rules = PermissionRule::where('permission_id', $permissionId)->get();
dd($rules->toArray());

// Check overrides
$overrides = UserPermissionOverride::where('user_id', $userId)->get();
dd($overrides->toArray());
```

---

## Getting Help

### Before Asking for Help

1. Check this troubleshooting guide
2. Review the [Best Practices Guide](best-practices.md)
3. Check the [Migration Guide](migration-guide.md)
4. Enable debug logging
5. Verify configuration

### When Asking for Help

Provide:
1. Error message (full stack trace)
2. Code snippet (controller, model, config)
3. Database state (rules, overrides)
4. Cache state (keys, values)
5. Laravel version, PHP version
6. Steps to reproduce

### Support Channels

- GitHub Issues: [canvastack/canvastack/issues](https://github.com/canvastack/canvastack/issues)
- Documentation: [docs/features/rbac.md](../docs/features/rbac.md)
- Team Discussions: Internal team chat

---

## Common Error Messages

### "Target class [PermissionRuleManager] does not exist"

**Solution:**
```php
// Make sure service provider is registered
// In config/app.php
'providers' => [
    Canvastack\Canvastack\CanvastackServiceProvider::class,
],
```

### "Call to undefined method scopeByPermission()"

**Solution:**
```php
// Add trait to model
use Canvastack\Canvastack\Traits\HasPermissionScopes;

class Post extends Model
{
    use HasPermissionScopes;
}
```

### "Cache store [redis] is not defined"

**Solution:**
```php
// In .env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1

// Or use array cache
CACHE_DRIVER=array
```

### "Foreign key constraint fails"

**Solution:**
```bash
# Run migrations in correct order
php artisan migrate --path=packages/canvastack/canvastack/database/migrations/rbac
php artisan migrate --path=packages/canvastack/canvastack/database/migrations/fine-grained
```

---

## Performance Benchmarks

### Expected Performance

| Operation | Target | Acceptable | Poor |
|-----------|--------|------------|------|
| Row-level check | < 50ms | < 100ms | > 100ms |
| Column-level check | < 10ms | < 20ms | > 20ms |
| JSON attribute check | < 15ms | < 30ms | > 30ms |
| Conditional check | < 30ms | < 60ms | > 60ms |
| Cache hit rate | > 80% | > 60% | < 60% |

### Measuring Performance

```php
// Add to PermissionRuleManager
private function measurePerformance(string $operation, callable $callback)
{
    $start = microtime(true);
    $result = $callback();
    $duration = (microtime(true) - $start) * 1000;
    
    Log::info("Performance: {$operation} took {$duration}ms");
    
    if ($duration > 100) {
        Log::warning("Slow operation: {$operation} took {$duration}ms");
    }
    
    return $result;
}
```

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2026-02-28 | Initial troubleshooting guide |

---

**Document Version**: 1.0.0  
**Last Updated**: 2026-02-28  
**Status**: Published  
**Author**: CanvaStack Team

