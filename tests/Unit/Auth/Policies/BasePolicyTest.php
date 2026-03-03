<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\Policies;

use Canvastack\Canvastack\Auth\Policies\BasePolicy;
use Canvastack\Canvastack\Auth\RBAC\PermissionManager;
use Canvastack\Canvastack\Auth\RBAC\RoleManager;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery;

/**
 * Test for BasePolicy.
 */
class BasePolicyTest extends TestCase
{
    /**
     * Role manager mock.
     *
     * @var RoleManager
     */
    protected $roleManager;

    /**
     * Permission manager mock.
     *
     * @var PermissionManager
     */
    protected $permissionManager;

    /**
     * Test policy instance.
     *
     * @var TestPolicy
     */
    protected $policy;

    /**
     * User mock.
     *
     * @var Authenticatable
     */
    protected $user;

    /**
     * Model mock.
     *
     * @var object
     */
    protected $model;

    /**
     * Setup test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->roleManager = Mockery::mock(RoleManager::class);
        $this->permissionManager = Mockery::mock(PermissionManager::class);

        $this->policy = new TestPolicy($this->roleManager, $this->permissionManager);

        $this->user = Mockery::mock(Authenticatable::class);
        $this->user->id = 1;

        $this->model = (object) ['id' => 1, 'user_id' => 2];

        // Setup default config
        Config::set('canvastack-rbac.authorization', [
            'super_admin_bypass' => true,
            'super_admin_role' => 'super_admin',
        ]);

        Config::set('canvastack-rbac.cache', [
            'enabled' => false,
            'ttl' => 3600,
            'key_prefix' => 'canvastack:rbac:',
            'tags' => [
                'permissions' => 'canvastack:rbac:permissions',
            ],
        ]);

        Config::set('canvastack-rbac.contexts', [
            'admin' => [
                'enabled' => true,
                'fallback_to_standard' => true,
            ],
            'public' => [
                'enabled' => true,
                'fallback_to_standard' => false,
            ],
        ]);
    }

    /**
     * Teardown test environment.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that context can be set and retrieved.
     *
     * @return void
     */
    public function test_context_can_be_set_and_retrieved(): void
    {
        $this->assertNull($this->policy->getContext());

        $this->policy->setContext('admin');

        $this->assertEquals('admin', $this->policy->getContext());
    }

    /**
     * Test that super admin is detected correctly.
     *
     * @return void
     */
    public function test_super_admin_is_detected(): void
    {
        // Create a fresh policy with fresh config
        $policy = new TestPolicy($this->roleManager, $this->permissionManager);
        
        $this->roleManager->shouldReceive('userHasRole')
            ->once()
            ->with(1, 'super_admin')
            ->andReturn(true);

        $result = $policy->testIsSuperAdmin($this->user);

        $this->assertTrue($result);
    }

    /**
     * Test that non-super admin is detected correctly.
     *
     * @return void
     */
    public function test_non_super_admin_is_detected(): void
    {
        // Create a fresh policy with fresh config
        $policy = new TestPolicy($this->roleManager, $this->permissionManager);
        
        $this->roleManager->shouldReceive('userHasRole')
            ->once()
            ->with(1, 'super_admin')
            ->andReturn(false);

        $result = $policy->testIsSuperAdmin($this->user);

        $this->assertFalse($result);
    }

    /**
     * Test that super admin bypass can be disabled.
     *
     * @return void
     */
    public function test_super_admin_bypass_can_be_disabled(): void
    {
        Config::set('canvastack-rbac.authorization.super_admin_bypass', false);

        $result = $this->policy->testIsSuperAdmin($this->user);

        $this->assertFalse($result);
    }

    /**
     * Test that user role is checked correctly.
     *
     * @return void
     */
    public function test_user_role_is_checked(): void
    {
        $this->roleManager->shouldReceive('userHasRole')
            ->once()
            ->with(1, 'admin')
            ->andReturn(true);

        $result = $this->policy->testHasRole($this->user, 'admin');

        $this->assertTrue($result);
    }

    /**
     * Test that user has any role is checked correctly.
     *
     * @return void
     */
    public function test_user_has_any_role_is_checked(): void
    {
        $this->roleManager->shouldReceive('userHasRole')
            ->once()
            ->with(1, 'admin')
            ->andReturn(false);

        $this->roleManager->shouldReceive('userHasRole')
            ->once()
            ->with(1, 'editor')
            ->andReturn(true);

        $result = $this->policy->testHasAnyRole($this->user, ['admin', 'editor']);

        $this->assertTrue($result);
    }

    /**
     * Test that user has all roles is checked correctly.
     *
     * @return void
     */
    public function test_user_has_all_roles_is_checked(): void
    {
        $this->roleManager->shouldReceive('userHasRole')
            ->once()
            ->with(1, 'admin')
            ->andReturn(true);

        $this->roleManager->shouldReceive('userHasRole')
            ->once()
            ->with(1, 'editor')
            ->andReturn(true);

        $result = $this->policy->testHasAllRoles($this->user, ['admin', 'editor']);

        $this->assertTrue($result);
    }

    /**
     * Test that user permission is checked correctly.
     *
     * @return void
     */
    public function test_user_permission_is_checked(): void
    {
        // Create a fresh policy
        $policy = new TestPolicy($this->roleManager, $this->permissionManager);
        
        $this->roleManager->shouldReceive('userHasRole')
            ->once()
            ->with(1, 'super_admin')
            ->andReturn(false);

        $this->permissionManager->shouldReceive('userHasPermission')
            ->once()
            ->with(1, 'users.view')
            ->andReturn(true);

        $result = $policy->testHasPermission($this->user, 'users.view');

        $this->assertTrue($result);
    }

    /**
     * Test that super admin bypasses permission check.
     *
     * @return void
     */
    public function test_super_admin_bypasses_permission_check(): void
    {
        // Create a fresh policy
        $policy = new TestPolicy($this->roleManager, $this->permissionManager);
        
        $this->roleManager->shouldReceive('userHasRole')
            ->once()
            ->with(1, 'super_admin')
            ->andReturn(true);

        $result = $policy->testHasPermission($this->user, 'users.view');

        $this->assertTrue($result);
    }

    /**
     * Test that user has any permission is checked correctly.
     *
     * @return void
     */
    public function test_user_has_any_permission_is_checked(): void
    {
        // Create a fresh policy
        $policy = new TestPolicy($this->roleManager, $this->permissionManager);
        
        $this->roleManager->shouldReceive('userHasRole')
            ->once()
            ->with(1, 'super_admin')
            ->andReturn(false);

        $this->permissionManager->shouldReceive('userHasAnyPermission')
            ->once()
            ->with(1, ['users.view', 'users.create'])
            ->andReturn(true);

        $result = $policy->testHasAnyPermission($this->user, ['users.view', 'users.create']);

        $this->assertTrue($result);
    }

    /**
     * Test that user has all permissions is checked correctly.
     *
     * @return void
     */
    public function test_user_has_all_permissions_is_checked(): void
    {
        // Create a fresh policy
        $policy = new TestPolicy($this->roleManager, $this->permissionManager);
        
        $this->roleManager->shouldReceive('userHasRole')
            ->once()
            ->with(1, 'super_admin')
            ->andReturn(false);

        $this->permissionManager->shouldReceive('userHasAllPermissions')
            ->once()
            ->with(1, ['users.view', 'users.create'])
            ->andReturn(true);

        $result = $policy->testHasAllPermissions($this->user, ['users.view', 'users.create']);

        $this->assertTrue($result);
    }

    /**
     * Test that permission caching works.
     *
     * @return void
     */
    public function test_permission_caching_works(): void
    {
        Config::set('canvastack-rbac.cache.enabled', true);
        
        // Create a fresh policy with caching enabled
        $policy = new TestPolicy($this->roleManager, $this->permissionManager);

        Cache::shouldReceive('tags')
            ->once()
            ->with(['canvastack:rbac:permissions'])
            ->andReturnSelf();

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(true);

        $this->roleManager->shouldReceive('userHasRole')
            ->once()
            ->with(1, 'super_admin')
            ->andReturn(false);

        $result = $policy->testHasPermission($this->user, 'users.view');

        $this->assertTrue($result);
    }

    /**
     * Test that context-aware permission check works.
     *
     * @return void
     */
    public function test_context_aware_permission_check_works(): void
    {
        // Create a fresh policy
        $policy = new TestPolicy($this->roleManager, $this->permissionManager);
        $policy->setContext('admin');

        $this->roleManager->shouldReceive('userHasRole')
            ->atLeast()->once()
            ->with(1, 'super_admin')
            ->andReturn(false);

        $this->permissionManager->shouldReceive('userHasPermission')
            ->once()
            ->with(1, 'admin.users.view')
            ->andReturn(true);

        $result = $policy->testCanInContext($this->user, 'users.view');

        $this->assertTrue($result);
    }

    /**
     * Test that context-aware permission falls back to standard.
     *
     * @return void
     */
    public function test_context_aware_permission_falls_back_to_standard(): void
    {
        // Create a fresh policy
        $policy = new TestPolicy($this->roleManager, $this->permissionManager);
        $policy->setContext('admin');

        $this->roleManager->shouldReceive('userHasRole')
            ->atLeast()->once()
            ->with(1, 'super_admin')
            ->andReturn(false);

        $this->permissionManager->shouldReceive('userHasPermission')
            ->once()
            ->with(1, 'admin.users.view')
            ->andReturn(false);

        $this->permissionManager->shouldReceive('userHasPermission')
            ->once()
            ->with(1, 'users.view')
            ->andReturn(true);

        $result = $policy->testCanInContext($this->user, 'users.view');

        $this->assertTrue($result);
    }

    /**
     * Test that context-aware permission does not fall back when disabled.
     *
     * @return void
     */
    public function test_context_aware_permission_does_not_fall_back_when_disabled(): void
    {
        // Create a fresh policy
        $policy = new TestPolicy($this->roleManager, $this->permissionManager);
        $policy->setContext('public');

        $this->roleManager->shouldReceive('userHasRole')
            ->atLeast()->once()
            ->with(1, 'super_admin')
            ->andReturn(false);

        $this->permissionManager->shouldReceive('userHasPermission')
            ->once()
            ->with(1, 'public.users.view')
            ->andReturn(false);

        $result = $policy->testCanInContext($this->user, 'users.view');

        $this->assertFalse($result);
    }

    /**
     * Test that ownership check works.
     *
     * @return void
     */
    public function test_ownership_check_works(): void
    {
        $this->user->id = 2;

        $result = $this->policy->testOwns($this->user, $this->model);

        $this->assertTrue($result);
    }

    /**
     * Test that ownership check fails for non-owner.
     *
     * @return void
     */
    public function test_ownership_check_fails_for_non_owner(): void
    {
        $this->user->id = 1;

        $result = $this->policy->testOwns($this->user, $this->model);

        $this->assertFalse($result);
    }

    /**
     * Test that higher level check works.
     *
     * @return void
     */
    public function test_higher_level_check_works(): void
    {
        $this->roleManager->shouldReceive('getUserHighestPrivilegeLevel')
            ->once()
            ->with(1)
            ->andReturn(1);

        $this->roleManager->shouldReceive('getUserHighestPrivilegeLevel')
            ->once()
            ->with(2)
            ->andReturn(5);

        $result = $this->policy->testHasHigherLevel($this->user, $this->model);

        $this->assertTrue($result);
    }

    /**
     * Test that viewAny ability works.
     *
     * @return void
     */
    public function test_view_any_ability_works(): void
    {
        // Create a fresh policy
        $policy = new TestPolicy($this->roleManager, $this->permissionManager);
        
        $this->roleManager->shouldReceive('userHasRole')
            ->atLeast()->once()
            ->with(1, 'super_admin')
            ->andReturn(false);

        $this->permissionManager->shouldReceive('userHasPermission')
            ->once()
            ->with(1, 'users.view')
            ->andReturn(true);

        $result = $policy->testViewAny($this->user, 'users.view');

        $this->assertTrue($result);
    }

    /**
     * Test that view ability works for owner.
     *
     * @return void
     */
    public function test_view_ability_works_for_owner(): void
    {
        $this->user->id = 2;
        
        // Create a fresh policy
        $policy = new TestPolicy($this->roleManager, $this->permissionManager);

        $this->roleManager->shouldReceive('userHasRole')
            ->atLeast()->once()
            ->with(2, 'super_admin')
            ->andReturn(false);

        $this->permissionManager->shouldReceive('userHasPermission')
            ->once()
            ->with(2, 'users.view')
            ->andReturn(true);

        $result = $policy->testView($this->user, $this->model, 'users.view');

        $this->assertTrue($result);
    }

    /**
     * Test that view ability works for higher level user.
     *
     * @return void
     */
    public function test_view_ability_works_for_higher_level_user(): void
    {
        // Create a fresh policy
        $policy = new TestPolicy($this->roleManager, $this->permissionManager);
        
        $this->roleManager->shouldReceive('userHasRole')
            ->atLeast()->once()
            ->with(1, 'super_admin')
            ->andReturn(false);

        $this->permissionManager->shouldReceive('userHasPermission')
            ->once()
            ->with(1, 'users.view')
            ->andReturn(true);

        $this->roleManager->shouldReceive('getUserHighestPrivilegeLevel')
            ->once()
            ->with(1)
            ->andReturn(1);

        $this->roleManager->shouldReceive('getUserHighestPrivilegeLevel')
            ->once()
            ->with(2)
            ->andReturn(5);

        $result = $policy->testView($this->user, $this->model, 'users.view');

        $this->assertTrue($result);
    }

    /**
     * Test that create ability works.
     *
     * @return void
     */
    public function test_create_ability_works(): void
    {
        // Create a fresh policy
        $policy = new TestPolicy($this->roleManager, $this->permissionManager);
        
        $this->roleManager->shouldReceive('userHasRole')
            ->atLeast()->once()
            ->with(1, 'super_admin')
            ->andReturn(false);

        $this->permissionManager->shouldReceive('userHasPermission')
            ->once()
            ->with(1, 'users.create')
            ->andReturn(true);

        $result = $policy->testCreate($this->user, 'users.create');

        $this->assertTrue($result);
    }

    /**
     * Test that update ability works for owner.
     *
     * @return void
     */
    public function test_update_ability_works_for_owner(): void
    {
        $this->user->id = 2;
        
        // Create a fresh policy
        $policy = new TestPolicy($this->roleManager, $this->permissionManager);

        $this->roleManager->shouldReceive('userHasRole')
            ->atLeast()->once()
            ->with(2, 'super_admin')
            ->andReturn(false);

        $this->permissionManager->shouldReceive('userHasPermission')
            ->once()
            ->with(2, 'users.update')
            ->andReturn(true);

        $result = $policy->testUpdate($this->user, $this->model, 'users.update');

        $this->assertTrue($result);
    }

    /**
     * Test that delete ability prevents self-deletion.
     *
     * @return void
     */
    public function test_delete_ability_prevents_self_deletion(): void
    {
        $this->user->id = 2;
        
        // Create a fresh policy
        $policy = new TestPolicy($this->roleManager, $this->permissionManager);

        $this->roleManager->shouldReceive('userHasRole')
            ->atLeast()->once()
            ->with(2, 'super_admin')
            ->andReturn(false);

        $this->permissionManager->shouldReceive('userHasPermission')
            ->once()
            ->with(2, 'users.delete')
            ->andReturn(true);

        $result = $policy->testDelete($this->user, $this->model, 'users.delete');

        $this->assertFalse($result);
    }

    /**
     * Test that delete ability works for higher level user.
     *
     * @return void
     */
    public function test_delete_ability_works_for_higher_level_user(): void
    {
        // Create a fresh policy
        $policy = new TestPolicy($this->roleManager, $this->permissionManager);
        
        $this->roleManager->shouldReceive('userHasRole')
            ->atLeast()->once()
            ->with(1, 'super_admin')
            ->andReturn(false);

        $this->permissionManager->shouldReceive('userHasPermission')
            ->once()
            ->with(1, 'users.delete')
            ->andReturn(true);

        $this->roleManager->shouldReceive('getUserHighestPrivilegeLevel')
            ->once()
            ->with(1)
            ->andReturn(1);

        $this->roleManager->shouldReceive('getUserHighestPrivilegeLevel')
            ->once()
            ->with(2)
            ->andReturn(5);

        $result = $policy->testDelete($this->user, $this->model, 'users.delete');

        $this->assertTrue($result);
    }

    /**
     * Test that restore ability works.
     *
     * @return void
     */
    public function test_restore_ability_works(): void
    {
        // Create a fresh policy
        $policy = new TestPolicy($this->roleManager, $this->permissionManager);
        
        $this->roleManager->shouldReceive('userHasRole')
            ->atLeast()->once()
            ->with(1, 'super_admin')
            ->andReturn(false);

        $this->permissionManager->shouldReceive('userHasPermission')
            ->once()
            ->with(1, 'users.restore')
            ->andReturn(true);

        $result = $policy->testRestore($this->user, $this->model, 'users.restore');

        $this->assertTrue($result);
    }

    /**
     * Test that forceDelete ability prevents self-deletion.
     *
     * @return void
     */
    public function test_force_delete_ability_prevents_self_deletion(): void
    {
        $this->user->id = 2;
        
        // Create a fresh policy
        $policy = new TestPolicy($this->roleManager, $this->permissionManager);

        $this->roleManager->shouldReceive('userHasRole')
            ->atLeast()->once()
            ->with(2, 'super_admin')
            ->andReturn(false);

        $this->permissionManager->shouldReceive('userHasPermission')
            ->once()
            ->with(2, 'users.force_delete')
            ->andReturn(true);

        $result = $policy->testForceDelete($this->user, $this->model, 'users.force_delete');

        $this->assertFalse($result);
    }

    /**
     * Test that cache can be cleared for user.
     *
     * @return void
     */
    public function test_cache_can_be_cleared_for_user(): void
    {
        Config::set('canvastack-rbac.cache.enabled', true);

        $this->permissionManager->shouldReceive('clearUserCache')
            ->once()
            ->with(1)
            ->andReturn(true);

        $result = $this->policy->testClearUserPermissionCache(1);

        $this->assertTrue($result);
    }
}

/**
 * Test Policy for testing BasePolicy.
 *
 * Exposes protected methods for testing.
 */
class TestPolicy extends BasePolicy
{
    public function testIsSuperAdmin($user): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function testHasRole($user, $role): bool
    {
        return $this->hasRole($user, $role);
    }

    public function testHasAnyRole($user, array $roles): bool
    {
        return $this->hasAnyRole($user, $roles);
    }

    public function testHasAllRoles($user, array $roles): bool
    {
        return $this->hasAllRoles($user, $roles);
    }

    public function testHasPermission($user, $permission): bool
    {
        return $this->hasPermission($user, $permission);
    }

    public function testHasAnyPermission($user, array $permissions): bool
    {
        return $this->hasAnyPermission($user, $permissions);
    }

    public function testHasAllPermissions($user, array $permissions): bool
    {
        return $this->hasAllPermissions($user, $permissions);
    }

    public function testCanInContext($user, string $permission): bool
    {
        return $this->canInContext($user, $permission);
    }

    public function testOwns($user, $model): bool
    {
        return $this->owns($user, $model);
    }

    public function testHasHigherLevel($user, $model): bool
    {
        return $this->hasHigherLevel($user, $model);
    }

    public function testViewAny($user, string $permission): bool
    {
        return $this->viewAny($user, $permission);
    }

    public function testView($user, $model, string $permission): bool
    {
        return $this->view($user, $model, $permission);
    }

    public function testCreate($user, string $permission): bool
    {
        return $this->create($user, $permission);
    }

    public function testUpdate($user, $model, string $permission): bool
    {
        return $this->update($user, $model, $permission);
    }

    public function testDelete($user, $model, string $permission): bool
    {
        return $this->delete($user, $model, $permission);
    }

    public function testRestore($user, $model, string $permission): bool
    {
        return $this->restore($user, $model, $permission);
    }

    public function testForceDelete($user, $model, string $permission): bool
    {
        return $this->forceDelete($user, $model, $permission);
    }

    public function testClearUserPermissionCache(int $userId): bool
    {
        return $this->clearUserPermissionCache($userId);
    }
}
