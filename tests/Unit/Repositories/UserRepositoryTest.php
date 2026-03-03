<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Repositories;

use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\Role;
use Canvastack\Canvastack\Models\User;
use Canvastack\Canvastack\Repositories\UserRepository;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Hash;

/**
 * Test for UserRepository.
 */
class UserRepositoryTest extends TestCase
{

    /**
     * The user repository instance.
     *
     * @var UserRepository
     */
    protected UserRepository $repository;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new UserRepository(new User());
    }

    /**
     * Clean up the test environment.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Clean up all test data after each test
        User::query()->forceDelete(); // Use forceDelete to bypass soft deletes
        Role::query()->delete();
        Permission::query()->delete();

        parent::tearDown();
    }

    /**
     * Test that repository can be instantiated.
     *
     * @return void
     */
    public function test_repository_can_be_instantiated(): void
    {
        $this->assertInstanceOf(UserRepository::class, $this->repository);
    }

    /**
     * Test that find by email returns user.
     *
     * @return void
     */
    public function test_find_by_email_returns_user(): void
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'active' => true,
        ]);

        $found = $this->repository->findByEmail('john@example.com');

        $this->assertInstanceOf(User::class, $found);
        $this->assertEquals($user->id, $found->id);
        $this->assertEquals('john@example.com', $found->email);
    }

    /**
     * Test that find by email returns null for non-existent email.
     *
     * @return void
     */
    public function test_find_by_email_returns_null_for_non_existent_email(): void
    {
        $found = $this->repository->findByEmail('nonexistent@example.com');

        $this->assertNull($found);
    }

    /**
     * Test that get active returns only active users.
     *
     * @return void
     */
    public function test_get_active_returns_only_active_users(): void
    {
        User::create([
            'name' => 'Active User',
            'email' => 'active@example.com',
            'password' => Hash::make('password'),
            'active' => true,
        ]);

        User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => Hash::make('password'),
            'active' => false,
        ]);

        $activeUsers = $this->repository->getActive();

        $this->assertCount(1, $activeUsers);
        $this->assertTrue($activeUsers->first()->active);
    }

    /**
     * Test that get inactive returns only inactive users.
     *
     * @return void
     */
    public function test_get_inactive_returns_only_inactive_users(): void
    {
        User::create([
            'name' => 'Active User',
            'email' => 'active@example.com',
            'password' => Hash::make('password'),
            'active' => true,
        ]);

        User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => Hash::make('password'),
            'active' => false,
        ]);

        $inactiveUsers = $this->repository->getInactive();

        $this->assertCount(1, $inactiveUsers);
        $this->assertFalse($inactiveUsers->first()->active);
    }

    /**
     * Test that get verified returns only verified users.
     *
     * @return void
     */
    public function test_get_verified_returns_only_verified_users(): void
    {
        User::create([
            'name' => 'Verified User',
            'email' => 'verified@example.com',
            'password' => Hash::make('password'),
            'active' => true,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'password' => Hash::make('password'),
            'active' => true,
            'email_verified_at' => null,
        ]);

        $verifiedUsers = $this->repository->getVerified();

        $this->assertCount(1, $verifiedUsers);
        $this->assertNotNull($verifiedUsers->first()->email_verified_at);
    }

    /**
     * Test that get unverified returns only unverified users.
     *
     * @return void
     */
    public function test_get_unverified_returns_only_unverified_users(): void
    {
        User::create([
            'name' => 'Verified User',
            'email' => 'verified@example.com',
            'password' => Hash::make('password'),
            'active' => true,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'password' => Hash::make('password'),
            'active' => true,
            'email_verified_at' => null,
        ]);

        $unverifiedUsers = $this->repository->getUnverified();

        $this->assertCount(1, $unverifiedUsers);
        $this->assertNull($unverifiedUsers->first()->email_verified_at);
    }

    /**
     * Test that get by role returns users with specific role.
     *
     * @return void
     */
    public function test_get_by_role_returns_users_with_specific_role(): void
    {
        $adminRole = Role::create(['name' => 'admin', 'display_name' => 'Administrator']);
        $userRole = Role::create(['name' => 'user', 'display_name' => 'User']);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'active' => true,
        ]);
        $admin->roles()->attach($adminRole->id);

        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'active' => true,
        ]);
        $user->roles()->attach($userRole->id);

        $admins = $this->repository->getByRole('admin');

        $this->assertCount(1, $admins);
        $this->assertEquals('admin@example.com', $admins->first()->email);
    }

    /**
     * Test that get by permission returns users with specific permission.
     *
     * @return void
     */
    public function test_get_by_permission_returns_users_with_specific_permission(): void
    {
        $permission = Permission::create([
            'name' => 'users.create',
            'display_name' => 'Create Users',
            'context' => 'admin',
        ]);

        $user1 = User::create([
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'password' => Hash::make('password'),
            'active' => true,
        ]);
        $user1->permissions()->attach($permission->id);

        $user2 = User::create([
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'password' => Hash::make('password'),
            'active' => true,
        ]);

        $usersWithPermission = $this->repository->getByPermission('users.create');

        $this->assertCount(1, $usersWithPermission);
        $this->assertEquals('user1@example.com', $usersWithPermission->first()->email);
    }

    /**
     * Test that create hashes password.
     *
     * @return void
     */
    public function test_create_hashes_password(): void
    {
        $user = $this->repository->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'plain-password',
            'active' => true,
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotEquals('plain-password', $user->password);
        $this->assertTrue(Hash::check('plain-password', $user->password));
    }

    /**
     * Test that update hashes password.
     *
     * @return void
     */
    public function test_update_hashes_password(): void
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('old-password'),
            'active' => true,
        ]);

        $this->repository->update($user->id, [
            'password' => 'new-password',
        ]);

        $user->refresh();

        $this->assertNotEquals('new-password', $user->password);
        $this->assertTrue(Hash::check('new-password', $user->password));
    }

    /**
     * Test that activate sets user to active.
     *
     * @return void
     */
    public function test_activate_sets_user_to_active(): void
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'active' => false,
        ]);

        $this->repository->activate($user->id);

        $user->refresh();

        $this->assertTrue($user->active);
    }

    /**
     * Test that deactivate sets user to inactive.
     *
     * @return void
     */
    public function test_deactivate_sets_user_to_inactive(): void
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'active' => true,
        ]);

        $this->repository->deactivate($user->id);

        $user->refresh();

        $this->assertFalse($user->active);
    }

    /**
     * Test that assign role adds role to user.
     *
     * @return void
     */
    public function test_assign_role_adds_role_to_user(): void
    {
        $role = Role::create(['name' => 'admin', 'display_name' => 'Administrator']);

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'active' => true,
        ]);

        $this->repository->assignRole($user->id, 'admin');

        $this->assertTrue($user->hasRole('admin'));
    }

    /**
     * Test that remove role removes role from user.
     *
     * @return void
     */
    public function test_remove_role_removes_role_from_user(): void
    {
        $role = Role::create(['name' => 'admin', 'display_name' => 'Administrator']);

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'active' => true,
        ]);
        $user->roles()->attach($role->id);

        $this->repository->removeRole($user->id, 'admin');

        $this->assertFalse($user->hasRole('admin'));
    }

    /**
     * Test that sync roles syncs roles for user.
     *
     * @return void
     */
    public function test_sync_roles_syncs_roles_for_user(): void
    {
        $adminRole = Role::create(['name' => 'admin', 'display_name' => 'Administrator']);
        $editorRole = Role::create(['name' => 'editor', 'display_name' => 'Editor']);
        $userRole = Role::create(['name' => 'user', 'display_name' => 'User']);

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'active' => true,
        ]);
        $user->roles()->attach($userRole->id);

        $this->repository->syncRoles($user->id, ['admin', 'editor']);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('editor'));
        $this->assertFalse($user->hasRole('user'));
    }

    /**
     * Test that assign permission adds permission to user.
     *
     * @return void
     */
    public function test_assign_permission_adds_permission_to_user(): void
    {
        $permission = Permission::create([
            'name' => 'users.create',
            'display_name' => 'Create Users',
            'context' => 'admin',
        ]);

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'active' => true,
        ]);

        $this->repository->assignPermission($user->id, 'users.create');

        $this->assertTrue($user->hasPermission('users.create'));
    }

    /**
     * Test that remove permission removes permission from user.
     *
     * @return void
     */
    public function test_remove_permission_removes_permission_from_user(): void
    {
        $permission = Permission::create([
            'name' => 'users.create',
            'display_name' => 'Create Users',
            'context' => 'admin',
        ]);

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'active' => true,
        ]);
        $user->permissions()->attach($permission->id);

        $this->repository->removePermission($user->id, 'users.create');

        $this->assertFalse($user->hasPermission('users.create'));
    }

    /**
     * Test that sync permissions syncs permissions for user.
     *
     * @return void
     */
    public function test_sync_permissions_syncs_permissions_for_user(): void
    {
        $createPermission = Permission::create([
            'name' => 'users.create',
            'display_name' => 'Create Users',
            'context' => 'admin',
        ]);

        $editPermission = Permission::create([
            'name' => 'users.edit',
            'display_name' => 'Edit Users',
            'context' => 'admin',
        ]);

        $deletePermission = Permission::create([
            'name' => 'users.delete',
            'display_name' => 'Delete Users',
            'context' => 'admin',
        ]);

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'active' => true,
        ]);
        $user->permissions()->attach($deletePermission->id);

        $this->repository->syncPermissions($user->id, ['users.create', 'users.edit']);

        $this->assertTrue($user->hasPermission('users.create'));
        $this->assertTrue($user->hasPermission('users.edit'));
        $this->assertFalse($user->hasPermission('users.delete'));
    }

    /**
     * Test that has role checks if user has role.
     *
     * @return void
     */
    public function test_has_role_checks_if_user_has_role(): void
    {
        $role = Role::create(['name' => 'admin', 'display_name' => 'Administrator']);

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'active' => true,
        ]);
        $user->roles()->attach($role->id);

        $this->assertTrue($this->repository->hasRole($user->id, 'admin'));
        $this->assertFalse($this->repository->hasRole($user->id, 'editor'));
    }

    /**
     * Test that has permission checks if user has permission.
     *
     * @return void
     */
    public function test_has_permission_checks_if_user_has_permission(): void
    {
        $permission = Permission::create([
            'name' => 'users.create',
            'display_name' => 'Create Users',
            'context' => 'admin',
        ]);

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'active' => true,
        ]);
        $user->permissions()->attach($permission->id);

        $this->assertTrue($this->repository->hasPermission($user->id, 'users.create'));
        $this->assertFalse($this->repository->hasPermission($user->id, 'users.edit'));
    }

    /**
     * Test that get all permissions returns all user permissions.
     *
     * @return void
     */
    public function test_get_all_permissions_returns_all_user_permissions(): void
    {
        $directPermission = Permission::create([
            'name' => 'users.create',
            'display_name' => 'Create Users',
            'context' => 'admin',
        ]);

        $rolePermission = Permission::create([
            'name' => 'users.edit',
            'display_name' => 'Edit Users',
            'context' => 'admin',
        ]);

        $role = Role::create(['name' => 'editor', 'display_name' => 'Editor']);
        $role->permissions()->attach($rolePermission->id);

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'active' => true,
        ]);
        $user->permissions()->attach($directPermission->id);
        $user->roles()->attach($role->id);

        $permissions = $this->repository->getAllPermissions($user->id);

        $this->assertCount(2, $permissions);
        $this->assertTrue($permissions->contains('name', 'users.create'));
        $this->assertTrue($permissions->contains('name', 'users.edit'));
    }

    /**
     * Test that search finds users by name or email.
     *
     * @return void
     */
    public function test_search_finds_users_by_name_or_email(): void
    {
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'active' => true,
        ]);

        User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('password'),
            'active' => true,
        ]);

        $results = $this->repository->search('john');

        $this->assertCount(1, $results);
        $this->assertEquals('john@example.com', $results->first()->email);
    }

    /**
     * Test that get by date range returns users created within range.
     *
     * @return void
     */
    public function test_get_by_date_range_returns_users_created_within_range(): void
    {
        User::create([
            'name' => 'Old User',
            'email' => 'old@example.com',
            'password' => Hash::make('password'),
            'active' => true,
            'created_at' => now()->subDays(10),
        ]);

        User::create([
            'name' => 'Recent User',
            'email' => 'recent@example.com',
            'password' => Hash::make('password'),
            'active' => true,
            'created_at' => now()->subDays(2),
        ]);

        $startDate = now()->subDays(5)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $users = $this->repository->getByDateRange($startDate, $endDate);

        $this->assertCount(1, $users);
        $this->assertEquals('recent@example.com', $users->first()->email);
    }

    /**
     * Test that count by status returns correct counts.
     *
     * @return void
     */
    public function test_count_by_status_returns_correct_counts(): void
    {
        User::create([
            'name' => 'Active Verified',
            'email' => 'active-verified@example.com',
            'password' => Hash::make('password'),
            'active' => true,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Active Unverified',
            'email' => 'active-unverified@example.com',
            'password' => Hash::make('password'),
            'active' => true,
            'email_verified_at' => null,
        ]);

        User::create([
            'name' => 'Inactive',
            'email' => 'inactive@example.com',
            'password' => Hash::make('password'),
            'active' => false,
            'email_verified_at' => null,
        ]);

        $counts = $this->repository->countByStatus();

        $this->assertEquals(3, $counts['total']);
        $this->assertEquals(2, $counts['active']);
        $this->assertEquals(1, $counts['inactive']);
        $this->assertEquals(1, $counts['verified']);
        $this->assertEquals(2, $counts['unverified']);
    }

    /**
     * Test that get recent returns recently created users.
     *
     * @return void
     */
    public function test_get_recent_returns_recently_created_users(): void
    {
        User::create([
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'password' => Hash::make('password'),
            'active' => true,
            'created_at' => now()->subDays(3),
        ]);

        User::create([
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'password' => Hash::make('password'),
            'active' => true,
            'created_at' => now()->subDays(1),
        ]);

        $recent = $this->repository->getRecent(1);

        $this->assertCount(1, $recent);
        $this->assertEquals('user2@example.com', $recent->first()->email);
    }

    /**
     * Test that paginate returns paginated results.
     *
     * @return void
     */
    public function test_paginate_returns_paginated_results(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => Hash::make('password'),
                'active' => true,
            ]);
        }

        $paginated = $this->repository->paginate(10);

        $this->assertEquals(10, $paginated->perPage());
        $this->assertEquals(20, $paginated->total());
        $this->assertEquals(2, $paginated->lastPage());
    }

    /**
     * Test that paginate applies filters.
     *
     * @return void
     */
    public function test_paginate_applies_filters(): void
    {
        User::create([
            'name' => 'Active User',
            'email' => 'active@example.com',
            'password' => Hash::make('password'),
            'active' => true,
        ]);

        User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => Hash::make('password'),
            'active' => false,
        ]);

        $paginated = $this->repository->paginate(10, ['active' => true]);

        $this->assertEquals(1, $paginated->total());
        $this->assertTrue($paginated->first()->active);
    }

    /**
     * Test that get all with relations eager loads relationships.
     *
     * @return void
     */
    public function test_get_all_with_relations_eager_loads_relationships(): void
    {
        $role = Role::create(['name' => 'admin', 'display_name' => 'Administrator']);

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'active' => true,
        ]);
        $user->roles()->attach($role->id);

        $users = $this->repository->getAllWithRelations(['roles']);

        $this->assertCount(1, $users);
        $this->assertTrue($users->first()->relationLoaded('roles'));
    }
}
