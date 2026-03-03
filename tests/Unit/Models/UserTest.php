<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Models;

use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\Role;
use Canvastack\Canvastack\Models\User;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Test for User Model.
 */
class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that user can be created.
     *
     * @return void
     */
    public function test_user_can_be_created(): void
    {
        // Arrange
        $name = 'John Doe';
        $email = 'john@example.com';
        $password = 'hashed_password';

        // Act
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'active' => true,
        ]);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($name, $user->name);
        $this->assertEquals($email, $user->email);
        $this->assertTrue($user->active);
    }

    /**
     * Test that user has roles relationship.
     *
     * @return void
     */
    public function test_user_has_roles_relationship(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'hashed_password',
            'active' => true,
        ]);
        $role = Role::factory()->create(['name' => 'admin']);

        // Act
        $user->roles()->attach($role->id);

        // Assert
        $this->assertCount(1, $user->fresh()->roles);
        $this->assertEquals('admin', $user->roles->first()->name);
    }

    /**
     * Test that user has permissions relationship.
     *
     * @return void
     */
    public function test_user_has_permissions_relationship(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test2@example.com',
            'password' => 'hashed_password',
            'active' => true,
        ]);
        $permission = Permission::factory()->create(['name' => 'users.create']);

        // Act
        $user->permissions()->attach($permission->id);

        // Assert
        $this->assertCount(1, $user->fresh()->permissions);
        $this->assertEquals('users.create', $user->permissions->first()->name);
    }

    /**
     * Test that user can check if has role.
     *
     * @return void
     */
    public function test_user_can_check_if_has_role(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test3@example.com',
            'password' => 'hashed_password',
            'active' => true,
        ]);
        $role = Role::factory()->create(['name' => 'admin']);
        $user->roles()->attach($role->id);

        // Act & Assert
        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('editor'));
    }

    /**
     * Test that user can check if has any role.
     *
     * @return void
     */
    public function test_user_can_check_if_has_any_role(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test4@example.com',
            'password' => 'hashed_password',
            'active' => true,
        ]);
        $role = Role::factory()->create(['name' => 'admin']);
        $user->roles()->attach($role->id);

        // Act & Assert
        $this->assertTrue($user->hasAnyRole(['admin', 'editor']));
        $this->assertFalse($user->hasAnyRole(['editor', 'viewer']));
    }

    /**
     * Test that user can check if has all roles.
     *
     * @return void
     */
    public function test_user_can_check_if_has_all_roles(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test5@example.com',
            'password' => 'hashed_password',
            'active' => true,
        ]);
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $editorRole = Role::factory()->create(['name' => 'editor']);
        $user->roles()->attach([$adminRole->id, $editorRole->id]);

        // Act & Assert
        $this->assertTrue($user->hasAllRoles(['admin', 'editor']));
        $this->assertFalse($user->hasAllRoles(['admin', 'editor', 'viewer']));
    }

    /**
     * Test that user can check if has permission directly.
     *
     * @return void
     */
    public function test_user_can_check_if_has_permission_directly(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test6@example.com',
            'password' => 'hashed_password',
            'active' => true,
        ]);
        $permission = Permission::factory()->create(['name' => 'users.create']);
        $user->permissions()->attach($permission->id);

        // Act & Assert
        $this->assertTrue($user->hasPermission('users.create'));
        $this->assertFalse($user->hasPermission('users.delete'));
    }

    /**
     * Test that user can check if has permission through role.
     *
     * @return void
     */
    public function test_user_can_check_if_has_permission_through_role(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test7@example.com',
            'password' => 'hashed_password',
            'active' => true,
        ]);
        $role = Role::factory()->create(['name' => 'admin']);
        $permission = Permission::factory()->create(['name' => 'users.create']);
        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id);

        // Act & Assert
        $this->assertTrue($user->hasPermission('users.create'));
    }

    /**
     * Test that user can assign role.
     *
     * @return void
     */
    public function test_user_can_assign_role(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test8@example.com',
            'password' => 'hashed_password',
            'active' => true,
        ]);
        $role = Role::factory()->create(['name' => 'admin']);

        // Act
        $user->assignRole('admin');

        // Assert
        $this->assertTrue($user->fresh()->hasRole('admin'));
    }

    /**
     * Test that user can assign role by model.
     *
     * @return void
     */
    public function test_user_can_assign_role_by_model(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test9@example.com',
            'password' => 'hashed_password',
            'active' => true,
        ]);
        $role = Role::factory()->create(['name' => 'admin']);

        // Act
        $user->assignRole($role);

        // Assert
        $this->assertTrue($user->fresh()->hasRole('admin'));
    }

    /**
     * Test that user can remove role.
     *
     * @return void
     */
    public function test_user_can_remove_role(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test10@example.com',
            'password' => 'hashed_password',
            'active' => true,
        ]);
        $role = Role::factory()->create(['name' => 'admin']);
        $user->roles()->attach($role->id);

        // Act
        $user->removeRole('admin');

        // Assert
        $this->assertFalse($user->fresh()->hasRole('admin'));
    }

    /**
     * Test that user can sync roles.
     *
     * @return void
     */
    public function test_user_can_sync_roles(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test11@example.com',
            'password' => 'hashed_password',
            'active' => true,
        ]);
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $editorRole = Role::factory()->create(['name' => 'editor']);
        $viewerRole = Role::factory()->create(['name' => 'viewer']);
        $user->roles()->attach([$adminRole->id, $viewerRole->id]);

        // Act
        $user->syncRoles(['admin', 'editor']);

        // Assert
        $user = $user->fresh();
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('editor'));
        $this->assertFalse($user->hasRole('viewer'));
    }

    /**
     * Test that user can assign permission.
     *
     * @return void
     */
    public function test_user_can_assign_permission(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test12@example.com',
            'password' => 'hashed_password',
            'active' => true,
        ]);
        $permission = Permission::factory()->create(['name' => 'users.create']);

        // Act
        $user->assignPermission('users.create');

        // Assert
        $this->assertTrue($user->fresh()->hasPermission('users.create'));
    }

    /**
     * Test that user can remove permission.
     *
     * @return void
     */
    public function test_user_can_remove_permission(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test13@example.com',
            'password' => 'hashed_password',
            'active' => true,
        ]);
        $permission = Permission::factory()->create(['name' => 'users.create']);
        $user->permissions()->attach($permission->id);

        // Act
        $user->removePermission('users.create');

        // Assert
        $this->assertFalse($user->fresh()->hasPermission('users.create'));
    }

    /**
     * Test that user can sync permissions.
     *
     * @return void
     */
    public function test_user_can_sync_permissions(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test14@example.com',
            'password' => 'hashed_password',
            'active' => true,
        ]);
        $createPermission = Permission::factory()->create(['name' => 'users.create']);
        $updatePermission = Permission::factory()->create(['name' => 'users.update']);
        $deletePermission = Permission::factory()->create(['name' => 'users.delete']);
        $user->permissions()->attach([$createPermission->id, $deletePermission->id]);

        // Act
        $user->syncPermissions(['users.create', 'users.update']);

        // Assert
        $user = $user->fresh();
        $this->assertTrue($user->hasPermission('users.create'));
        $this->assertTrue($user->hasPermission('users.update'));
        $this->assertFalse($user->hasPermission('users.delete'));
    }

    /**
     * Test that user can get all permissions.
     *
     * @return void
     */
    public function test_user_can_get_all_permissions(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test15@example.com',
            'password' => 'hashed_password',
            'active' => true,
        ]);
        $role = Role::factory()->create(['name' => 'admin']);
        $directPermission = Permission::factory()->create(['name' => 'users.create']);
        $rolePermission = Permission::factory()->create(['name' => 'users.update']);

        $user->permissions()->attach($directPermission->id);
        $role->permissions()->attach($rolePermission->id);
        $user->roles()->attach($role->id);

        // Act
        $allPermissions = $user->getAllPermissions();

        // Assert
        $this->assertCount(2, $allPermissions);
        $this->assertTrue($allPermissions->contains('name', 'users.create'));
        $this->assertTrue($allPermissions->contains('name', 'users.update'));
    }

    /**
     * Test that active scope works.
     *
     * @return void
     */
    public function test_active_scope_works(): void
    {
        // Arrange
        User::create([
            'name' => 'Active User',
            'email' => 'active@example.com',
            'password' => 'hashed_password',
            'active' => true,
        ]);
        User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => 'hashed_password',
            'active' => false,
        ]);

        // Act
        $activeUsers = User::active()->get();

        // Assert
        $this->assertCount(1, $activeUsers);
        $this->assertTrue($activeUsers->first()->active);
    }

    /**
     * Test that inactive scope works.
     *
     * @return void
     */
    public function test_inactive_scope_works(): void
    {
        // Arrange
        User::create([
            'name' => 'Active User',
            'email' => 'active2@example.com',
            'password' => 'hashed_password',
            'active' => true,
        ]);
        User::create([
            'name' => 'Inactive User',
            'email' => 'inactive2@example.com',
            'password' => 'hashed_password',
            'active' => false,
        ]);

        // Act
        $inactiveUsers = User::inactive()->get();

        // Assert
        $this->assertCount(1, $inactiveUsers);
        $this->assertFalse($inactiveUsers->first()->active);
    }

    /**
     * Test that is active method works.
     *
     * @return void
     */
    public function test_is_active_method_works(): void
    {
        // Arrange
        $activeUser = User::create([
            'name' => 'Active User',
            'email' => 'active3@example.com',
            'password' => 'hashed_password',
            'active' => true,
        ]);
        $inactiveUser = User::create([
            'name' => 'Inactive User',
            'email' => 'inactive3@example.com',
            'password' => 'hashed_password',
            'active' => false,
        ]);

        // Act & Assert
        $this->assertTrue($activeUser->isActive());
        $this->assertFalse($inactiveUser->isActive());
    }

    /**
     * Test that activate method works.
     *
     * @return void
     */
    public function test_activate_method_works(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test16@example.com',
            'password' => 'hashed_password',
            'active' => false,
        ]);

        // Act
        $user->activate();

        // Assert
        $this->assertTrue($user->fresh()->active);
    }

    /**
     * Test that deactivate method works.
     *
     * @return void
     */
    public function test_deactivate_method_works(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test17@example.com',
            'password' => 'hashed_password',
            'active' => true,
        ]);

        // Act
        $user->deactivate();

        // Assert
        $this->assertFalse($user->fresh()->active);
    }

    /**
     * Test that password is hidden.
     *
     * @return void
     */
    public function test_password_is_hidden(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test18@example.com',
            'password' => 'hashed_password',
            'active' => true,
        ]);

        // Act
        $array = $user->toArray();

        // Assert
        $this->assertArrayNotHasKey('password', $array);
    }

    /**
     * Test that user uses soft deletes.
     *
     * @return void
     */
    public function test_user_uses_soft_deletes(): void
    {
        // Arrange
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test19@example.com',
            'password' => 'hashed_password',
            'active' => true,
        ]);

        // Act
        $user->delete();

        // Assert
        $this->assertNotNull($user->fresh()->deleted_at);
        $this->assertTrue($user->trashed());
    }
}
