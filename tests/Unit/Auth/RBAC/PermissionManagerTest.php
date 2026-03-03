<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\PermissionManager;
use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\Role;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PermissionManagerTest extends TestCase
{
    protected PermissionManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new PermissionManager();

        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_can_get_all_permissions()
    {
        Permission::factory()->count(3)->create();

        $permissions = $this->manager->all(false);

        $this->assertCount(3, $permissions);
    }

    /** @test */
    public function it_can_find_permission_by_id()
    {
        $permission = Permission::factory()->create(['name' => 'test.permission']);

        $found = $this->manager->find($permission->id, false);

        $this->assertNotNull($found);
        $this->assertEquals('test.permission', $found->name);
    }

    /** @test */
    public function it_can_find_permission_by_name()
    {
        Permission::factory()->create(['name' => 'test.permission']);

        $found = $this->manager->findByName('test.permission', false);

        $this->assertNotNull($found);
        $this->assertEquals('test.permission', $found->name);
    }

    /** @test */
    public function it_can_get_permissions_by_module()
    {
        Permission::factory()->create(['name' => 'users.view', 'module' => 'users']);
        Permission::factory()->create(['name' => 'users.create', 'module' => 'users']);
        Permission::factory()->create(['name' => 'posts.view', 'module' => 'posts']);

        $permissions = $this->manager->getByModule('users', false);

        $this->assertCount(2, $permissions);
        $this->assertTrue($permissions->every(fn ($p) => $p->module === 'users'));
    }

    /** @test */
    public function it_can_create_permission()
    {
        $data = [
            'name' => 'test.permission',
            'display_name' => 'Test Permission',
            'description' => 'Test description',
            'module' => 'test',
        ];

        $permission = $this->manager->create($data);

        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertEquals('test.permission', $permission->name);
        $this->assertEquals('Test Permission', $permission->display_name);
        $this->assertEquals('Test description', $permission->description);
        $this->assertEquals('test', $permission->module);
    }

    /** @test */
    public function it_can_create_permission_with_roles()
    {
        $role1 = Role::factory()->create();
        $role2 = Role::factory()->create();

        $data = [
            'name' => 'test.permission',
            'display_name' => 'Test Permission',
            'roles' => [$role1->id, $role2->id],
        ];

        $permission = $this->manager->create($data);

        $this->assertCount(2, $permission->roles);
        $this->assertTrue($permission->roles->contains($role1));
        $this->assertTrue($permission->roles->contains($role2));
    }

    /** @test */
    public function it_throws_exception_when_creating_permission_without_name()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Permission name is required');

        $this->manager->create([
            'display_name' => 'Test Permission',
        ]);
    }

    /** @test */
    public function it_throws_exception_when_creating_duplicate_permission()
    {
        Permission::factory()->create(['name' => 'test.permission']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Permission name already exists');

        $this->manager->create([
            'name' => 'test.permission',
            'display_name' => 'Test Permission',
        ]);
    }

    /** @test */
    public function it_can_update_permission()
    {
        $permission = Permission::factory()->create([
            'name' => 'test.permission',
            'display_name' => 'Old Name',
        ]);

        $result = $this->manager->update($permission->id, [
            'display_name' => 'New Name',
            'description' => 'New description',
        ]);

        $this->assertTrue($result);

        $permission->refresh();
        $this->assertEquals('New Name', $permission->display_name);
        $this->assertEquals('New description', $permission->description);
    }

    /** @test */
    public function it_can_update_permission_roles()
    {
        $permission = Permission::factory()->create();
        $role1 = Role::factory()->create();
        $role2 = Role::factory()->create();

        $permission->roles()->attach($role1->id);

        $result = $this->manager->update($permission->id, [
            'roles' => [$role2->id],
        ]);

        $this->assertTrue($result);

        $permission->refresh();
        $this->assertCount(1, $permission->roles);
        $this->assertTrue($permission->roles->contains($role2));
        $this->assertFalse($permission->roles->contains($role1));
    }

    /** @test */
    public function it_can_delete_permission()
    {
        $permission = Permission::factory()->create();
        $role = Role::factory()->create();

        $permission->roles()->attach($role->id);

        $result = $this->manager->delete($permission->id);

        $this->assertTrue($result);

        // Verify permission is deleted
        $this->assertNull(Permission::find($permission->id));

        // Verify pivot record is deleted
        $pivotExists = DB::table('permission_role')
            ->where('permission_id', $permission->id)
            ->where('role_id', $role->id)
            ->exists();
        $this->assertFalse($pivotExists);
    }

    /** @test */
    public function it_can_assign_permission_to_role()
    {
        $permission = Permission::factory()->create(['name' => 'test.permission']);
        $role = Role::factory()->create();

        $result = $this->manager->assignToRole($role->id, $permission->id);

        $this->assertTrue($result);

        // Verify pivot record exists
        $pivotExists = DB::table('permission_role')
            ->where('permission_id', $permission->id)
            ->where('role_id', $role->id)
            ->exists();
        $this->assertTrue($pivotExists);
    }

    /** @test */
    public function it_can_assign_permission_to_role_by_name()
    {
        $permission = Permission::factory()->create(['name' => 'test.permission']);
        $role = Role::factory()->create();

        $result = $this->manager->assignToRole($role->id, 'test.permission');

        $this->assertTrue($result);

        // Verify pivot record exists
        $pivotExists = DB::table('permission_role')
            ->where('permission_id', $permission->id)
            ->where('role_id', $role->id)
            ->exists();
        $this->assertTrue($pivotExists);
    }

    /** @test */
    public function it_can_remove_permission_from_role()
    {
        $permission = Permission::factory()->create();
        $role = Role::factory()->create();

        $permission->roles()->attach($role->id);

        $result = $this->manager->removeFromRole($role->id, $permission->id);

        $this->assertTrue($result);

        // Verify pivot record is deleted
        $pivotExists = DB::table('permission_role')
            ->where('permission_id', $permission->id)
            ->where('role_id', $role->id)
            ->exists();
        $this->assertFalse($pivotExists);
    }

    /** @test */
    public function it_can_assign_permission_to_user()
    {
        $permission = Permission::factory()->create(['name' => 'test.permission']);
        $userId = 1;

        $result = $this->manager->assignToUser($userId, $permission->id);

        $this->assertTrue($result);

        // Verify pivot record exists
        $pivotExists = DB::table('permission_user')
            ->where('permission_id', $permission->id)
            ->where('user_id', $userId)
            ->exists();
        $this->assertTrue($pivotExists);
    }

    /** @test */
    public function it_can_remove_permission_from_user()
    {
        $permission = Permission::factory()->create();
        $userId = 1;

        DB::table('permission_user')->insert([
            'permission_id' => $permission->id,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->manager->removeFromUser($userId, $permission->id);

        $this->assertTrue($result);

        // Verify pivot record is deleted
        $pivotExists = DB::table('permission_user')
            ->where('permission_id', $permission->id)
            ->where('user_id', $userId)
            ->exists();
        $this->assertFalse($pivotExists);
    }

    /** @test */
    public function it_can_get_role_permissions()
    {
        $role = Role::factory()->create();
        $permission1 = Permission::factory()->create(['name' => 'test.permission1']);
        $permission2 = Permission::factory()->create(['name' => 'test.permission2']);

        $role->permissions()->attach([$permission1->id, $permission2->id]);

        $permissions = $this->manager->getRolePermissions($role->id, false);

        $this->assertCount(2, $permissions);
        $this->assertTrue($permissions->contains($permission1));
        $this->assertTrue($permissions->contains($permission2));
    }

    /** @test */
    public function it_can_get_user_permissions_from_roles()
    {
        $role = Role::factory()->create();
        $permission = Permission::factory()->create(['name' => 'test.permission']);
        $userId = 1;

        $role->permissions()->attach($permission->id);

        DB::table('role_user')->insert([
            'role_id' => $role->id,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissions = $this->manager->getUserPermissions($userId, false);

        $this->assertCount(1, $permissions);
        $this->assertTrue($permissions->contains($permission));
    }

    /** @test */
    public function it_can_get_user_permissions_from_direct_assignment()
    {
        $permission = Permission::factory()->create(['name' => 'test.permission']);
        $userId = 1;

        DB::table('permission_user')->insert([
            'permission_id' => $permission->id,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissions = $this->manager->getUserPermissions($userId, false);

        $this->assertCount(1, $permissions);
        $this->assertTrue($permissions->contains($permission));
    }

    /** @test */
    public function it_can_get_user_permissions_from_both_roles_and_direct()
    {
        $role = Role::factory()->create();
        $permission1 = Permission::factory()->create(['name' => 'test.permission1']);
        $permission2 = Permission::factory()->create(['name' => 'test.permission2']);
        $userId = 1;

        // Permission from role
        $role->permissions()->attach($permission1->id);
        DB::table('role_user')->insert([
            'role_id' => $role->id,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Direct permission
        DB::table('permission_user')->insert([
            'permission_id' => $permission2->id,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissions = $this->manager->getUserPermissions($userId, false);

        $this->assertCount(2, $permissions);
        $this->assertTrue($permissions->contains($permission1));
        $this->assertTrue($permissions->contains($permission2));
    }

    /** @test */
    public function it_removes_duplicate_permissions_when_getting_user_permissions()
    {
        $role = Role::factory()->create();
        $permission = Permission::factory()->create(['name' => 'test.permission']);
        $userId = 1;

        // Permission from role
        $role->permissions()->attach($permission->id);
        DB::table('role_user')->insert([
            'role_id' => $role->id,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Same permission directly
        DB::table('permission_user')->insert([
            'permission_id' => $permission->id,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissions = $this->manager->getUserPermissions($userId, false);

        $this->assertCount(1, $permissions);
    }

    /** @test */
    public function it_can_check_if_role_has_permission()
    {
        $role = Role::factory()->create();
        $permission = Permission::factory()->create(['name' => 'test.permission']);

        $role->permissions()->attach($permission->id);

        $this->assertTrue($this->manager->roleHasPermission($role->id, $permission->id));
        $this->assertTrue($this->manager->roleHasPermission($role->id, 'test.permission'));
    }

    /** @test */
    public function it_can_check_if_user_has_permission()
    {
        $role = Role::factory()->create();
        $permission = Permission::factory()->create(['name' => 'test.permission']);
        $userId = 1;

        $role->permissions()->attach($permission->id);
        DB::table('role_user')->insert([
            'role_id' => $role->id,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertTrue($this->manager->userHasPermission($userId, $permission->id));
        $this->assertTrue($this->manager->userHasPermission($userId, 'test.permission'));
    }

    /** @test */
    public function it_can_check_if_user_has_any_permission()
    {
        $role = Role::factory()->create();
        $permission1 = Permission::factory()->create(['name' => 'test.permission1']);
        $permission2 = Permission::factory()->create(['name' => 'test.permission2']);
        $permission3 = Permission::factory()->create(['name' => 'test.permission3']);
        $userId = 1;

        $role->permissions()->attach($permission1->id);
        DB::table('role_user')->insert([
            'role_id' => $role->id,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertTrue($this->manager->userHasAnyPermission($userId, [
            'test.permission1',
            'test.permission2',
        ]));

        $this->assertFalse($this->manager->userHasAnyPermission($userId, [
            'test.permission2',
            'test.permission3',
        ]));
    }

    /** @test */
    public function it_can_check_if_user_has_all_permissions()
    {
        $role = Role::factory()->create();
        $permission1 = Permission::factory()->create(['name' => 'test.permission1']);
        $permission2 = Permission::factory()->create(['name' => 'test.permission2']);
        $permission3 = Permission::factory()->create(['name' => 'test.permission3']);
        $userId = 1;

        $role->permissions()->attach([$permission1->id, $permission2->id]);
        DB::table('role_user')->insert([
            'role_id' => $role->id,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertTrue($this->manager->userHasAllPermissions($userId, [
            'test.permission1',
            'test.permission2',
        ]));

        $this->assertFalse($this->manager->userHasAllPermissions($userId, [
            'test.permission1',
            'test.permission3',
        ]));
    }

    /** @test */
    public function it_can_sync_role_permissions()
    {
        $role = Role::factory()->create();
        $permission1 = Permission::factory()->create();
        $permission2 = Permission::factory()->create();
        $permission3 = Permission::factory()->create();

        $role->permissions()->attach([$permission1->id, $permission2->id]);

        $result = $this->manager->syncRolePermissions($role->id, [$permission2->id, $permission3->id]);

        $this->assertTrue($result);

        $role->refresh();
        $this->assertCount(2, $role->permissions);
        $this->assertFalse($role->permissions->contains($permission1));
        $this->assertTrue($role->permissions->contains($permission2));
        $this->assertTrue($role->permissions->contains($permission3));
    }

    /** @test */
    public function it_can_sync_user_permissions()
    {
        $permission1 = Permission::factory()->create();
        $permission2 = Permission::factory()->create();
        $permission3 = Permission::factory()->create();
        $userId = 1;

        DB::table('permission_user')->insert([
            ['permission_id' => $permission1->id, 'user_id' => $userId, 'created_at' => now(), 'updated_at' => now()],
            ['permission_id' => $permission2->id, 'user_id' => $userId, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $result = $this->manager->syncUserPermissions($userId, [$permission2->id, $permission3->id]);

        $this->assertTrue($result);

        // Verify permission1 is removed
        $pivot1Exists = DB::table('permission_user')
            ->where('permission_id', $permission1->id)
            ->where('user_id', $userId)
            ->exists();
        $this->assertFalse($pivot1Exists);

        // Verify permission2 still exists
        $pivot2Exists = DB::table('permission_user')
            ->where('permission_id', $permission2->id)
            ->where('user_id', $userId)
            ->exists();
        $this->assertTrue($pivot2Exists);

        // Verify permission3 is added
        $pivot3Exists = DB::table('permission_user')
            ->where('permission_id', $permission3->id)
            ->where('user_id', $userId)
            ->exists();
        $this->assertTrue($pivot3Exists);
    }

    /** @test */
    public function it_can_get_all_modules()
    {
        Permission::factory()->create(['module' => 'users']);
        Permission::factory()->create(['module' => 'posts']);
        Permission::factory()->create(['module' => 'users']);
        Permission::factory()->create(['module' => null]);

        $modules = $this->manager->getModules();

        $this->assertCount(2, $modules);
        $this->assertTrue($modules->contains('users'));
        $this->assertTrue($modules->contains('posts'));
    }

    /** @test */
    public function it_caches_all_permissions()
    {
        Permission::factory()->count(3)->create();

        // First call - should cache
        $permissions1 = $this->manager->all(true);

        // Second call - should use cache
        $permissions2 = $this->manager->all(true);

        $this->assertEquals($permissions1->count(), $permissions2->count());
    }

    /** @test */
    public function it_caches_permission_by_id()
    {
        $permission = Permission::factory()->create();

        // First call - should cache
        $found1 = $this->manager->find($permission->id, true);

        // Second call - should use cache
        $found2 = $this->manager->find($permission->id, true);

        $this->assertEquals($found1->id, $found2->id);
    }

    /** @test */
    public function it_clears_cache_after_create()
    {
        $this->manager->all(true); // Cache all permissions

        $this->manager->create([
            'name' => 'test.permission',
            'display_name' => 'Test Permission',
        ]);

        // Cache should be cleared, so this should fetch fresh data
        $permissions = $this->manager->all(true);

        $this->assertCount(1, $permissions);
    }

    /** @test */
    public function it_clears_cache_after_update()
    {
        $permission = Permission::factory()->create();

        $this->manager->find($permission->id, true); // Cache permission

        $this->manager->update($permission->id, [
            'display_name' => 'Updated Name',
        ]);

        // Cache should be cleared
        $found = $this->manager->find($permission->id, true);

        $this->assertEquals('Updated Name', $found->display_name);
    }

    /** @test */
    public function it_clears_cache_after_delete()
    {
        $permission = Permission::factory()->create();

        $this->manager->all(true); // Cache all permissions

        $this->manager->delete($permission->id);

        // Cache should be cleared
        $permissions = $this->manager->all(true);

        $this->assertCount(0, $permissions);
    }
}
