<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\RoleManager;
use Canvastack\Canvastack\Models\Role;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class RoleManagerLevelFilterTest extends TestCase
{
    use RefreshDatabase;

    protected RoleManager $roleManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleManager = new RoleManager();

        // Clear cache before each test
        Cache::flush();

        // Create test roles with different levels
        Role::create(['name' => 'super_admin', 'display_name' => 'Super Admin', 'level' => 1]);
        Role::create(['name' => 'admin', 'display_name' => 'Admin', 'level' => 3]);
        Role::create(['name' => 'manager', 'display_name' => 'Manager', 'level' => 5]);
        Role::create(['name' => 'editor', 'display_name' => 'Editor', 'level' => 7]);
        Role::create(['name' => 'user', 'display_name' => 'User', 'level' => 10]);
    }

    /** @test */
    public function it_can_get_roles_by_level_range(): void
    {
        // Get roles from level 3 to 7
        $roles = $this->roleManager->getRolesByLevelRange(3, 7);

        $this->assertCount(3, $roles);
        $this->assertEquals(['admin', 'manager', 'editor'], $roles->pluck('name')->toArray());
    }

    /** @test */
    public function it_can_get_roles_by_level_range_without_max(): void
    {
        // Get roles from level 5 and above (no max limit)
        $roles = $this->roleManager->getRolesByLevelRange(5, null);

        $this->assertCount(3, $roles);
        $this->assertEquals(['manager', 'editor', 'user'], $roles->pluck('name')->toArray());
    }

    /** @test */
    public function it_can_get_roles_with_higher_or_equal_privilege(): void
    {
        // Get roles with privilege >= level 5 (level number <= 5)
        $roles = $this->roleManager->getRolesWithHigherOrEqualPrivilege(5);

        $this->assertCount(3, $roles);
        $this->assertEquals(['super_admin', 'admin', 'manager'], $roles->pluck('name')->toArray());
    }

    /** @test */
    public function it_can_get_roles_with_lower_or_equal_privilege(): void
    {
        // Get roles with privilege <= level 5 (level number >= 5)
        $roles = $this->roleManager->getRolesWithLowerOrEqualPrivilege(5);

        $this->assertCount(3, $roles);
        $this->assertEquals(['manager', 'editor', 'user'], $roles->pluck('name')->toArray());
    }

    /** @test */
    public function it_can_get_user_highest_privilege_level(): void
    {
        $userId = 1;

        // Assign multiple roles
        $adminRole = Role::where('name', 'admin')->first();
        $editorRole = Role::where('name', 'editor')->first();

        $this->roleManager->assignToUser($userId, $adminRole->id);
        $this->roleManager->assignToUser($userId, $editorRole->id);

        // Should return the lowest level number (highest privilege)
        $level = $this->roleManager->getUserHighestPrivilegeLevel($userId);

        $this->assertEquals(3, $level); // admin level
    }

    /** @test */
    public function it_returns_null_for_user_with_no_roles(): void
    {
        $userId = 999;

        $level = $this->roleManager->getUserHighestPrivilegeLevel($userId);

        $this->assertNull($level);
    }

    /** @test */
    public function it_can_check_if_user_can_manage_another_user(): void
    {
        $adminId = 1;
        $editorId = 2;

        $adminRole = Role::where('name', 'admin')->first();
        $editorRole = Role::where('name', 'editor')->first();

        $this->roleManager->assignToUser($adminId, $adminRole->id);
        $this->roleManager->assignToUser($editorId, $editorRole->id);

        // Admin (level 3) can manage Editor (level 7)
        $this->assertTrue($this->roleManager->canManageUser($adminId, $editorId));

        // Editor (level 7) cannot manage Admin (level 3)
        $this->assertFalse($this->roleManager->canManageUser($editorId, $adminId));
    }

    /** @test */
    public function it_returns_false_when_users_have_same_level(): void
    {
        $editor1Id = 1;
        $editor2Id = 2;

        $editorRole = Role::where('name', 'editor')->first();

        $this->roleManager->assignToUser($editor1Id, $editorRole->id);
        $this->roleManager->assignToUser($editor2Id, $editorRole->id);

        // Same level cannot manage each other
        $this->assertFalse($this->roleManager->canManageUser($editor1Id, $editor2Id));
    }

    /** @test */
    public function it_can_get_managed_user_ids(): void
    {
        $managerId = 1;
        $user1Id = 2;
        $user2Id = 3;
        $user3Id = 4;

        $adminRole = Role::where('name', 'admin')->first(); // level 3
        $editorRole = Role::where('name', 'editor')->first(); // level 7
        $userRole = Role::where('name', 'user')->first(); // level 10

        $this->roleManager->assignToUser($managerId, $adminRole->id);
        $this->roleManager->assignToUser($user1Id, $editorRole->id);
        $this->roleManager->assignToUser($user2Id, $userRole->id);
        $this->roleManager->assignToUser($user3Id, $userRole->id);

        // Admin (level 3) can manage users with level > 3
        $managedIds = $this->roleManager->getManagedUserIds($managerId);

        $this->assertCount(3, $managedIds);
        $this->assertContains($user1Id, $managedIds);
        $this->assertContains($user2Id, $managedIds);
        $this->assertContains($user3Id, $managedIds);
        $this->assertNotContains($managerId, $managedIds);
    }

    /** @test */
    public function it_returns_empty_array_for_user_with_no_roles(): void
    {
        $userId = 999;

        $managedIds = $this->roleManager->getManagedUserIds($userId);

        $this->assertEmpty($managedIds);
    }

    /** @test */
    public function it_returns_empty_array_for_lowest_privilege_user(): void
    {
        $userId = 1;

        $userRole = Role::where('name', 'user')->first(); // level 10 (lowest)
        $this->roleManager->assignToUser($userId, $userRole->id);

        $managedIds = $this->roleManager->getManagedUserIds($userId);

        $this->assertEmpty($managedIds);
    }

    /** @test */
    public function it_caches_roles_by_level_range(): void
    {
        config(['canvastack-rbac.cache.enabled' => true]);

        // First call - should cache
        $roles1 = $this->roleManager->getRolesByLevelRange(3, 7, true);
        $this->assertCount(3, $roles1);

        // Create a new role in the range
        Role::create(['name' => 'supervisor', 'display_name' => 'Supervisor', 'level' => 4]);

        // Second call with cache disabled - should get fresh data (4 roles)
        $roles2 = $this->roleManager->getRolesByLevelRange(3, 7, false);
        $this->assertCount(4, $roles2);

        // Clear cache and fetch again - should get fresh data (4 roles)
        $this->roleManager->clearCache();
        $roles3 = $this->roleManager->getRolesByLevelRange(3, 7, true);
        $this->assertCount(4, $roles3);
    }

    /** @test */
    public function it_bypasses_cache_when_disabled(): void
    {
        config(['canvastack-rbac.cache.enabled' => false]);

        // Create a new instance after config change
        $roleManager = new RoleManager();

        $roles1 = $roleManager->getRolesByLevelRange(3, 7, true);
        $this->assertCount(3, $roles1);

        Role::create(['name' => 'supervisor', 'display_name' => 'Supervisor', 'level' => 4]);

        $roles2 = $roleManager->getRolesByLevelRange(3, 7, true);
        $this->assertCount(4, $roles2);
    }

    /** @test */
    public function it_orders_roles_by_level_ascending(): void
    {
        $roles = $this->roleManager->getRolesByLevelRange(1, 10);

        $levels = $roles->pluck('level')->toArray();

        $this->assertEquals([1, 3, 5, 7, 10], $levels);
    }
}
