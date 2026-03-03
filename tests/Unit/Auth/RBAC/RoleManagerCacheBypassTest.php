<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\RoleManager;
use Canvastack\Canvastack\Models\Role;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

/**
 * Test for RoleManager cache bypass functionality.
 *
 * This test suite verifies that cache is properly bypassed when:
 * 1. Cache is disabled in configuration
 * 2. $useCache parameter is false
 * 3. Both conditions are met
 */
class RoleManagerCacheBypassTest extends TestCase
{
    use RefreshDatabase;

    protected RoleManager $roleManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleManager = new RoleManager();

        // Clear cache before each test
        Cache::flush();
    }

    /**
     * Test that cache is bypassed when disabled in config.
     *
     * @return void
     */
    public function test_cache_is_bypassed_when_disabled_in_config(): void
    {
        // Disable cache in config
        config(['canvastack-rbac.cache.enabled' => false]);

        // Create a new instance after config change
        $roleManager = new RoleManager();

        // Create initial role
        Role::create(['name' => 'editor', 'display_name' => 'Editor', 'level' => 3]);

        // First call - should NOT cache
        $roles1 = $roleManager->all(true);
        $this->assertCount(1, $roles1);

        // Create another role
        Role::create(['name' => 'admin', 'display_name' => 'Admin', 'level' => 1]);

        // Second call - should get fresh data (not cached)
        $roles2 = $roleManager->all(true);
        $this->assertCount(2, $roles2, 'Cache should be bypassed when disabled in config');
    }

    /**
     * Test that cache is bypassed when $useCache is false.
     *
     * @return void
     */
    public function test_cache_is_bypassed_when_use_cache_is_false(): void
    {
        // Enable cache in config
        config(['canvastack-rbac.cache.enabled' => true]);

        // Create initial role
        Role::create(['name' => 'editor', 'display_name' => 'Editor', 'level' => 3]);

        // First call with useCache=false - should NOT cache
        $roles1 = $this->roleManager->all(false);
        $this->assertCount(1, $roles1);

        // Create another role
        Role::create(['name' => 'admin', 'display_name' => 'Admin', 'level' => 1]);

        // Second call with useCache=false - should get fresh data
        $roles2 = $this->roleManager->all(false);
        $this->assertCount(2, $roles2, 'Cache should be bypassed when $useCache is false');
    }

    /**
     * Test that find() bypasses cache when disabled.
     *
     * @return void
     */
    public function test_find_bypasses_cache_when_disabled(): void
    {
        config(['canvastack-rbac.cache.enabled' => false]);

        // Create a new instance after config change
        $roleManager = new RoleManager();

        $role = Role::create(['name' => 'editor', 'display_name' => 'Editor', 'level' => 3]);

        // First call
        $found1 = $roleManager->find($role->id, true);
        $this->assertEquals('Editor', $found1->display_name);

        // Update role directly in database
        $role->update(['display_name' => 'Content Editor']);

        // Second call - should get fresh data
        $found2 = $roleManager->find($role->id, true);
        $this->assertEquals('Content Editor', $found2->display_name, 'Cache should be bypassed');
    }

    /**
     * Test that findByName() bypasses cache when disabled.
     *
     * @return void
     */
    public function test_find_by_name_bypasses_cache_when_disabled(): void
    {
        config(['canvastack-rbac.cache.enabled' => false]);

        // Create a new instance after config change
        $roleManager = new RoleManager();

        $role = Role::create(['name' => 'editor', 'display_name' => 'Editor', 'level' => 3]);

        // First call
        $found1 = $roleManager->findByName('editor', true);
        $this->assertEquals('Editor', $found1->display_name);

        // Update role directly in database
        $role->update(['display_name' => 'Content Editor']);

        // Second call - should get fresh data
        $found2 = $roleManager->findByName('editor', true);
        $this->assertEquals('Content Editor', $found2->display_name, 'Cache should be bypassed');
    }

    /**
     * Test that getUserRoles() bypasses cache when disabled.
     *
     * @return void
     */
    public function test_get_user_roles_bypasses_cache_when_disabled(): void
    {
        config(['canvastack-rbac.cache.enabled' => false]);

        $role1 = Role::create(['name' => 'editor', 'display_name' => 'Editor', 'level' => 3]);
        $role2 = Role::create(['name' => 'admin', 'display_name' => 'Admin', 'level' => 1]);

        $userId = 1;

        // Assign first role
        $this->roleManager->assignToUser($userId, $role1->id);

        // First call
        $roles1 = $this->roleManager->getUserRoles($userId, true);
        $this->assertCount(1, $roles1);

        // Assign second role
        $this->roleManager->assignToUser($userId, $role2->id);

        // Second call - should get fresh data
        $roles2 = $this->roleManager->getUserRoles($userId, true);
        $this->assertCount(2, $roles2, 'Cache should be bypassed');
    }

    /**
     * Test that getRolesByLevelRange() bypasses cache when disabled.
     *
     * @return void
     */
    public function test_get_roles_by_level_range_bypasses_cache_when_disabled(): void
    {
        config(['canvastack-rbac.cache.enabled' => false]);

        // Create a new instance after config change
        $roleManager = new RoleManager();

        Role::create(['name' => 'admin', 'display_name' => 'Admin', 'level' => 1]);
        Role::create(['name' => 'editor', 'display_name' => 'Editor', 'level' => 3]);

        // First call
        $roles1 = $roleManager->getRolesByLevelRange(1, 3, true);
        $this->assertCount(2, $roles1);

        // Create another role in range
        Role::create(['name' => 'manager', 'display_name' => 'Manager', 'level' => 2]);

        // Second call - should get fresh data
        $roles2 = $roleManager->getRolesByLevelRange(1, 3, true);
        $this->assertCount(3, $roles2, 'Cache should be bypassed');
    }

    /**
     * Test that clearCache() works when cache is disabled.
     *
     * @return void
     */
    public function test_clear_cache_works_when_cache_disabled(): void
    {
        config(['canvastack-rbac.cache.enabled' => false]);

        // Should not throw exception
        $result = $this->roleManager->clearCache();

        $this->assertTrue($result, 'clearCache() should return true even when cache is disabled');
    }

    /**
     * Test that clearUserCache() works when cache is disabled.
     *
     * @return void
     */
    public function test_clear_user_cache_works_when_cache_disabled(): void
    {
        config(['canvastack-rbac.cache.enabled' => false]);

        // Should not throw exception
        $result = $this->roleManager->clearUserCache(1);

        $this->assertTrue($result, 'clearUserCache() should return true even when cache is disabled');
    }

    /**
     * Test cache configuration handling.
     *
     * @return void
     */
    public function test_cache_configuration_is_properly_loaded(): void
    {
        // Test with cache enabled
        config(['canvastack-rbac.cache.enabled' => true]);
        $roleManager1 = new RoleManager();

        Role::create(['name' => 'editor', 'display_name' => 'Editor', 'level' => 3]);
        $roles1 = $roleManager1->all(true);
        $this->assertCount(1, $roles1);

        // Test with cache disabled
        config(['canvastack-rbac.cache.enabled' => false]);
        $roleManager2 = new RoleManager();

        Role::create(['name' => 'admin', 'display_name' => 'Admin', 'level' => 1]);
        $roles2 = $roleManager2->all(true);
        $this->assertCount(2, $roles2, 'New instance should respect updated config');
    }

    /**
     * Test that cache bypass works with useCache parameter false.
     *
     * @return void
     */
    public function test_use_cache_parameter_false_bypasses_cache(): void
    {
        config(['canvastack-rbac.cache.enabled' => true]);

        $role = Role::create(['name' => 'editor', 'display_name' => 'Editor', 'level' => 3]);

        // Call with useCache=true (should cache)
        $found1 = $this->roleManager->find($role->id, true);
        $this->assertEquals('Editor', $found1->display_name);

        // Update role
        $role->update(['display_name' => 'Content Editor']);

        // Call with useCache=false (should bypass cache)
        $found2 = $this->roleManager->find($role->id, false);
        $this->assertEquals('Content Editor', $found2->display_name, 'useCache=false should bypass cache');
    }

    /**
     * Test that cache is properly bypassed in all methods.
     *
     * @return void
     */
    public function test_all_methods_respect_cache_bypass(): void
    {
        config(['canvastack-rbac.cache.enabled' => false]);

        $role = Role::create(['name' => 'editor', 'display_name' => 'Editor', 'level' => 3]);
        $userId = 1;
        $this->roleManager->assignToUser($userId, $role->id);

        // Test all methods that accept useCache parameter
        $methods = [
            'all' => fn () => $this->roleManager->all(true),
            'find' => fn () => $this->roleManager->find($role->id, true),
            'findByName' => fn () => $this->roleManager->findByName('editor', true),
            'getUserRoles' => fn () => $this->roleManager->getUserRoles($userId, true),
            'getRolesByLevelRange' => fn () => $this->roleManager->getRolesByLevelRange(1, 5, true),
            'getRolesWithHigherOrEqualPrivilege' => fn () => $this->roleManager->getRolesWithHigherOrEqualPrivilege(3, true),
            'getRolesWithLowerOrEqualPrivilege' => fn () => $this->roleManager->getRolesWithLowerOrEqualPrivilege(3, true),
        ];

        foreach ($methods as $methodName => $method) {
            // Should not throw exception and should return fresh data
            $result = $method();
            $this->assertNotNull($result, "Method {$methodName} should work with cache disabled");
        }
    }
}

