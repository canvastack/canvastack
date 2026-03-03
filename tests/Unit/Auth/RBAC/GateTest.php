<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\Gate;
use Canvastack\Canvastack\Auth\RBAC\GateCheck;
use Canvastack\Canvastack\Auth\RBAC\PermissionManager;
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Auth\RBAC\PolicyManager;
use Canvastack\Canvastack\Auth\RBAC\RoleManager;
use Canvastack\Canvastack\Models\PermissionRule;
use Canvastack\Canvastack\Models\Role;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class GateTest extends TestCase
{
    use RefreshDatabase;

    protected Gate $gate;

    protected RoleManager $roleManager;

    protected PermissionManager $permissionManager;

    protected PolicyManager $policyManager;

    protected PermissionRuleManager $ruleManager;

    protected static $authGuard = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup auth guard mock ONCE for all tests
        if (self::$authGuard === null) {
            self::$authGuard = new class () {
                protected $user = null;

                public function user()
                {
                    return $this->user;
                }

                public function id()
                {
                    return $this->user?->id;
                }

                public function setUser($user)
                {
                    $this->user = $user;
                }

                public function check()
                {
                    return $this->user !== null;
                }
            };
        }

        // Bind to container
        $app = \Illuminate\Container\Container::getInstance();
        $app->singleton('auth', function () {
            return self::$authGuard;
        });

        $this->roleManager = app(RoleManager::class);
        $this->permissionManager = app(PermissionManager::class);
        $this->policyManager = app(PolicyManager::class);
        $this->ruleManager = app(PermissionRuleManager::class);
        $this->gate = new Gate(
            $this->roleManager,
            $this->permissionManager,
            $this->policyManager,
            $this->ruleManager
        );
    }

    /**
     * Set authenticated user.
     */
    protected function actingAs($user): void
    {
        self::$authGuard->setUser($user);
    }

    /** @test */
    public function it_can_set_and_get_context(): void
    {
        $this->assertNull($this->gate->getContext());

        $this->gate->setContext('admin');
        $this->assertEquals('admin', $this->gate->getContext());

        $this->gate->setContext('public');
        $this->assertEquals('public', $this->gate->getContext());
    }

    /** @test */
    public function it_returns_false_for_unauthenticated_user(): void
    {
        $this->assertFalse($this->gate->allows(null, 'view-users'));
        $this->assertTrue($this->gate->denies(null, 'view-users'));
    }

    /** @test */
    public function it_allows_super_admin_to_perform_any_ability(): void
    {
        Config::set('canvastack-rbac.authorization.super_admin_bypass', true);
        Config::set('canvastack-rbac.authorization.super_admin_role', 'super_admin');

        $role = $this->roleManager->create([
            'name' => 'super_admin',
            'display_name' => 'Super Admin',
            'level' => 1,
        ]);

        $user = $this->createUser();
        $this->roleManager->assignToUser($user->id, $role->id);

        // Debug: Check if role is assigned
        $userRoles = $this->roleManager->getUserRoles($user->id);
        $this->assertCount(1, $userRoles, 'User should have 1 role assigned');
        $this->assertEquals('super_admin', $userRoles->first()->name, 'Role should be super_admin');

        // Debug: Check if super admin is detected
        $isSuperAdmin = $this->gate->isSuperAdmin($user);
        $this->assertTrue($isSuperAdmin, 'User should be detected as super admin');

        $this->assertTrue($this->gate->allows($user, 'any-ability'));
        $this->assertTrue($this->gate->allows($user, 'another-ability'));
        $this->assertFalse($this->gate->denies($user, 'any-ability'));
    }

    /** @test */
    public function it_checks_permission_for_regular_user(): void
    {
        $permission = $this->permissionManager->create([
            'name' => 'view-users',
            'display_name' => 'View Users',
        ]);

        $user = $this->createUser();
        $this->permissionManager->assignToUser($user->id, $permission->id);

        $this->assertTrue($this->gate->allows($user, 'view-users'));
        $this->assertFalse($this->gate->denies($user, 'view-users'));
        $this->assertFalse($this->gate->allows($user, 'edit-users'));
    }

    /** @test */
    public function it_can_authorize_ability(): void
    {
        $permission = $this->permissionManager->create([
            'name' => 'edit-users',
            'display_name' => 'Edit Users',
        ]);

        $user = $this->createUser();
        $this->permissionManager->assignToUser($user->id, $permission->id);

        // Should not throw exception
        $this->gate->authorize($user, 'edit-users');

        $this->expectException(AuthorizationException::class);
        $this->gate->authorize($user, 'delete-users');
    }

    /** @test */
    public function it_can_authorize_with_custom_message(): void
    {
        $user = $this->createUser();

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Custom error message');

        $this->gate->authorize($user, 'delete-users', null, 'Custom error message');
    }

    /** @test */
    public function it_checks_if_user_has_role(): void
    {
        $role = $this->roleManager->create([
            'name' => 'admin',
            'display_name' => 'Admin',
        ]);

        $user = $this->createUser();
        $this->roleManager->assignToUser($user->id, $role->id);

        $this->assertTrue($this->gate->hasRole($user, 'admin'));
        $this->assertTrue($this->gate->hasRole($user, $role->id));
        $this->assertFalse($this->gate->hasRole($user, 'editor'));
    }

    /** @test */
    public function it_checks_if_user_has_any_role(): void
    {
        $adminRole = $this->roleManager->create([
            'name' => 'admin',
            'display_name' => 'Admin',
        ]);

        $user = $this->createUser();
        $this->roleManager->assignToUser($user->id, $adminRole->id);

        $this->assertTrue($this->gate->hasAnyRole($user, ['admin', 'editor']));
        $this->assertTrue($this->gate->hasAnyRole($user, ['editor', 'admin']));
        $this->assertFalse($this->gate->hasAnyRole($user, ['editor', 'viewer']));
    }

    /** @test */
    public function it_checks_if_user_has_all_roles(): void
    {
        $adminRole = $this->roleManager->create([
            'name' => 'admin',
            'display_name' => 'Admin',
        ]);

        $editorRole = $this->roleManager->create([
            'name' => 'editor',
            'display_name' => 'Editor',
        ]);

        $user = $this->createUser();
        $this->roleManager->assignToUser($user->id, $adminRole->id);
        $this->roleManager->assignToUser($user->id, $editorRole->id);

        $this->assertTrue($this->gate->hasAllRoles($user, ['admin', 'editor']));
        $this->assertFalse($this->gate->hasAllRoles($user, ['admin', 'editor', 'viewer']));
    }

    /** @test */
    public function it_checks_if_user_has_permission(): void
    {
        $permission = $this->permissionManager->create([
            'name' => 'view-users',
            'display_name' => 'View Users',
        ]);

        $user = $this->createUser();
        $this->permissionManager->assignToUser($user->id, $permission->id);

        $this->assertTrue($this->gate->hasPermission($user, 'view-users'));
        $this->assertTrue($this->gate->hasPermission($user, $permission->id));
        $this->assertFalse($this->gate->hasPermission($user, 'edit-users'));
    }

    /** @test */
    public function it_checks_if_user_has_any_permission(): void
    {
        $viewPermission = $this->permissionManager->create([
            'name' => 'view-users',
            'display_name' => 'View Users',
        ]);

        $user = $this->createUser();
        $this->permissionManager->assignToUser($user->id, $viewPermission->id);

        $this->assertTrue($this->gate->hasAnyPermission($user, ['view-users', 'edit-users']));
        $this->assertTrue($this->gate->hasAnyPermission($user, ['edit-users', 'view-users']));
        $this->assertFalse($this->gate->hasAnyPermission($user, ['edit-users', 'delete-users']));
    }

    /** @test */
    public function it_checks_if_user_has_all_permissions(): void
    {
        $viewPermission = $this->permissionManager->create([
            'name' => 'view-users',
            'display_name' => 'View Users',
        ]);

        $editPermission = $this->permissionManager->create([
            'name' => 'edit-users',
            'display_name' => 'Edit Users',
        ]);

        $user = $this->createUser();
        $this->permissionManager->assignToUser($user->id, $viewPermission->id);
        $this->permissionManager->assignToUser($user->id, $editPermission->id);

        $this->assertTrue($this->gate->hasAllPermissions($user, ['view-users', 'edit-users']));
        $this->assertFalse($this->gate->hasAllPermissions($user, ['view-users', 'edit-users', 'delete-users']));
    }

    /** @test */
    public function it_checks_if_user_is_super_admin(): void
    {
        Config::set('canvastack-rbac.authorization.super_admin_bypass', true);
        Config::set('canvastack-rbac.authorization.super_admin_role', 'super_admin');

        $role = $this->roleManager->create([
            'name' => 'super_admin',
            'display_name' => 'Super Admin',
            'level' => 1,
        ]);

        $user = $this->createUser();
        $this->roleManager->assignToUser($user->id, $role->id);

        $this->assertTrue($this->gate->isSuperAdmin($user));

        $regularUser = $this->createUser();
        $this->assertFalse($this->gate->isSuperAdmin($regularUser));
    }

    /** @test */
    public function it_checks_if_user_has_higher_level(): void
    {
        $adminRole = $this->roleManager->create([
            'name' => 'admin',
            'display_name' => 'Admin',
            'level' => 1,
        ]);

        $editorRole = $this->roleManager->create([
            'name' => 'editor',
            'display_name' => 'Editor',
            'level' => 5,
        ]);

        $admin = $this->createUser();
        $editor = $this->createUser();

        $this->roleManager->assignToUser($admin->id, $adminRole->id);
        $this->roleManager->assignToUser($editor->id, $editorRole->id);

        $this->assertTrue($this->gate->hasHigherLevel($admin, $editor));
        $this->assertFalse($this->gate->hasHigherLevel($editor, $admin));
    }

    /** @test */
    public function it_checks_ability_in_context(): void
    {
        Config::set('canvastack-rbac.contexts.admin.enabled', true);

        $permission = $this->permissionManager->create([
            'name' => 'admin.view-users',
            'display_name' => 'View Users (Admin)',
        ]);

        $user = $this->createUser();
        $this->permissionManager->assignToUser($user->id, $permission->id);

        $this->assertTrue($this->gate->allowsInContext($user, 'view-users', 'admin'));
        $this->assertFalse($this->gate->allowsInContext($user, 'view-users', 'public'));
    }

    /** @test */
    public function it_checks_if_user_allows_any_ability(): void
    {
        $permission = $this->permissionManager->create([
            'name' => 'view-users',
            'display_name' => 'View Users',
        ]);

        $user = $this->createUser();
        $this->permissionManager->assignToUser($user->id, $permission->id);

        $this->assertTrue($this->gate->allowsAny($user, ['view-users', 'edit-users']));
        $this->assertTrue($this->gate->allowsAny($user, ['edit-users', 'view-users']));
        $this->assertFalse($this->gate->allowsAny($user, ['edit-users', 'delete-users']));
    }

    /** @test */
    public function it_checks_if_user_allows_all_abilities(): void
    {
        $viewPermission = $this->permissionManager->create([
            'name' => 'view-users',
            'display_name' => 'View Users',
        ]);

        $editPermission = $this->permissionManager->create([
            'name' => 'edit-users',
            'display_name' => 'Edit Users',
        ]);

        $user = $this->createUser();
        $this->permissionManager->assignToUser($user->id, $viewPermission->id);
        $this->permissionManager->assignToUser($user->id, $editPermission->id);

        $this->assertTrue($this->gate->allowsAll($user, ['view-users', 'edit-users']));
        $this->assertFalse($this->gate->allowsAll($user, ['view-users', 'edit-users', 'delete-users']));
    }

    /** @test */
    public function it_gets_user_roles(): void
    {
        $role = $this->roleManager->create([
            'name' => 'admin',
            'display_name' => 'Admin',
        ]);

        $user = $this->createUser();
        $this->roleManager->assignToUser($user->id, $role->id);

        $roles = $this->gate->getRoles($user);

        $this->assertInstanceOf(Collection::class, $roles);
        $this->assertCount(1, $roles);
        $this->assertEquals('admin', $roles->first()->name);
    }

    /** @test */
    public function it_gets_user_permissions(): void
    {
        $permission = $this->permissionManager->create([
            'name' => 'view-users',
            'display_name' => 'View Users',
        ]);

        $user = $this->createUser();
        $this->permissionManager->assignToUser($user->id, $permission->id);

        $permissions = $this->gate->getPermissions($user);

        $this->assertInstanceOf(Collection::class, $permissions);
        $this->assertCount(1, $permissions);
        $this->assertEquals('view-users', $permissions->first()->name);
    }

    /** @test */
    public function it_checks_resource_authorization(): void
    {
        $permission = $this->permissionManager->create([
            'name' => 'users.view',
            'display_name' => 'View Users',
        ]);

        $user = $this->createUser();
        $this->permissionManager->assignToUser($user->id, $permission->id);

        $this->assertTrue($this->gate->allowsResource($user, 'users', 'view'));
        $this->assertFalse($this->gate->allowsResource($user, 'users', 'edit'));
    }

    /** @test */
    public function it_authorizes_resource_action(): void
    {
        $permission = $this->permissionManager->create([
            'name' => 'users.edit',
            'display_name' => 'Edit Users',
        ]);

        $user = $this->createUser();
        $this->permissionManager->assignToUser($user->id, $permission->id);

        // Should not throw exception
        $this->gate->authorizeResource($user, 'users', 'edit');

        $this->expectException(AuthorizationException::class);
        $this->gate->authorizeResource($user, 'users', 'delete');
    }

    /** @test */
    public function it_provides_manager_access(): void
    {
        $this->assertInstanceOf(RoleManager::class, $this->gate->roles());
        $this->assertInstanceOf(PermissionManager::class, $this->gate->permissions());
        $this->assertInstanceOf(PolicyManager::class, $this->gate->policies());
    }

    /** @test */
    public function it_creates_fluent_gate_check(): void
    {
        $user = $this->createUser();
        $check = $this->gate->forUser($user);

        $this->assertInstanceOf(GateCheck::class, $check);
    }

    /** @test */
    public function it_checks_if_context_is_enabled(): void
    {
        Config::set('canvastack-rbac.contexts.admin.enabled', true);
        Config::set('canvastack-rbac.contexts.public.enabled', false);

        $this->assertTrue($this->gate->isContextEnabled('admin'));
        $this->assertFalse($this->gate->isContextEnabled('public'));
    }

    /** @test */
    public function it_gets_enabled_contexts(): void
    {
        Config::set('canvastack-rbac.contexts', [
            'admin' => ['enabled' => true],
            'public' => ['enabled' => false],
            'api' => ['enabled' => true],
        ]);

        $contexts = $this->gate->getEnabledContexts();

        $this->assertCount(2, $contexts);
        $this->assertContains('admin', $contexts);
        $this->assertContains('api', $contexts);
        $this->assertNotContains('public', $contexts);
    }

    /** @test */
    public function fluent_gate_check_can_check_abilities(): void
    {
        $permission = $this->permissionManager->create([
            'name' => 'view-users',
            'display_name' => 'View Users',
        ]);

        $user = $this->createUser();
        $this->permissionManager->assignToUser($user->id, $permission->id);

        $check = $this->gate->forUser($user);

        $this->assertTrue($check->can('view-users'));
        $this->assertFalse($check->cannot('view-users'));
        $this->assertFalse($check->can('edit-users'));
        $this->assertTrue($check->cannot('edit-users'));
    }

    /** @test */
    public function fluent_gate_check_can_check_roles(): void
    {
        $role = $this->roleManager->create([
            'name' => 'admin',
            'display_name' => 'Admin',
        ]);

        $user = $this->createUser();
        $this->roleManager->assignToUser($user->id, $role->id);

        $check = $this->gate->forUser($user);

        $this->assertTrue($check->hasRole('admin'));
        $this->assertFalse($check->hasRole('editor'));
    }

    /** @test */
    public function fluent_gate_check_can_check_permissions(): void
    {
        $permission = $this->permissionManager->create([
            'name' => 'view-users',
            'display_name' => 'View Users',
        ]);

        $user = $this->createUser();
        $this->permissionManager->assignToUser($user->id, $permission->id);

        $check = $this->gate->forUser($user);

        $this->assertTrue($check->hasPermission('view-users'));
        $this->assertFalse($check->hasPermission('edit-users'));
    }

    /** @test */
    public function fluent_gate_check_can_check_super_admin(): void
    {
        Config::set('canvastack-rbac.authorization.super_admin_bypass', true);
        Config::set('canvastack-rbac.authorization.super_admin_role', 'super_admin');

        $role = $this->roleManager->create([
            'name' => 'super_admin',
            'display_name' => 'Super Admin',
            'level' => 1,
        ]);

        $user = $this->createUser();
        $this->roleManager->assignToUser($user->id, $role->id);

        $check = $this->gate->forUser($user);

        $this->assertTrue($check->isSuperAdmin());
    }

    /** @test */
    public function it_denies_row_access_for_unauthenticated_user(): void
    {
        Log::shouldReceive('warning')->once();

        $model = new class () {
            public int $id = 1;

            public int $user_id = 1;
        };

        $result = $this->gate->canAccessRow(null, 'posts.edit', $model);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_denies_row_access_when_basic_permission_is_missing(): void
    {
        Log::shouldReceive('warning')->once();

        $user = $this->createUser();
        $model = new class () {
            public int $id = 1;

            public int $user_id = 1;
        };

        $result = $this->gate->canAccessRow($user, 'posts.edit', $model);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_allows_row_access_for_super_admin(): void
    {
        Config::set('canvastack-rbac.authorization.super_admin_bypass', true);
        Config::set('canvastack-rbac.authorization.super_admin_role', 'super_admin');

        $role = $this->roleManager->create([
            'name' => 'super_admin',
            'display_name' => 'Super Admin',
            'level' => 1,
        ]);

        $permission = $this->permissionManager->create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
        ]);

        $user = $this->createUser();
        $this->roleManager->assignToUser($user->id, $role->id);
        $this->permissionManager->assignToUser($user->id, $permission->id);

        $model = new class () {
            public int $id = 1;

            public int $user_id = 999;
        };

        $result = $this->gate->canAccessRow($user, 'posts.edit', $model);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_checks_row_level_rules_after_basic_permission(): void
    {
        Config::set('canvastack-rbac.fine_grained.enabled', true);
        Config::set('canvastack-rbac.fine_grained.row_level.enabled', true);

        $permission = $this->permissionManager->create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
        ]);

        $user = $this->createUser();
        $this->permissionManager->assignToUser($user->id, $permission->id);

        // Set the authenticated user for template variable resolution
        $this->actingAs($user);

        // Create a test model class
        $testModel = new class () {
            public int $id = 1;

            public int $user_id = 1;
        };
        $modelClass = get_class($testModel);

        // Create row-level rule: user can only edit their own posts
        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => $modelClass,
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                ],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        // Test with user's own post (user_id matches auth.id)
        $ownPost = new $modelClass();
        $ownPost->id = 1;
        $ownPost->user_id = $user->id; // Use the actual user ID

        $result = $this->gate->canAccessRow($user, 'posts.edit', $ownPost);
        $this->assertTrue($result, 'User should be able to access their own post');

        // Test with another user's post
        Log::shouldReceive('warning')->once();

        $otherPost = new $modelClass();
        $otherPost->id = 2;
        $otherPost->user_id = 999;

        $result = $this->gate->canAccessRow($user, 'posts.edit', $otherPost);
        $this->assertFalse($result, 'User should not be able to access another user\'s post');
    }

    /** @test */
    public function it_allows_row_access_when_no_rules_exist(): void
    {
        Config::set('canvastack-rbac.fine_grained.enabled', true);
        Config::set('canvastack-rbac.fine_grained.row_level.enabled', true);

        $permission = $this->permissionManager->create([
            'name' => 'posts.view',
            'display_name' => 'View Posts',
        ]);

        $user = $this->createUser();
        $this->permissionManager->assignToUser($user->id, $permission->id);

        $model = new class () {
            public int $id = 1;

            public int $user_id = 999;
        };

        // No rules exist, should allow access
        $result = $this->gate->canAccessRow($user, 'posts.view', $model);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_logs_denial_with_proper_context(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', \Mockery::on(function ($data) {
                return isset($data['user_id'])
                    && isset($data['permission'])
                    && isset($data['reason'])
                    && isset($data['context'])
                    && isset($data['timestamp'])
                    && $data['reason'] === 'basic_permission_denied';
            }));

        $user = $this->createUser();
        $model = new class () {
            public int $id = 1;
        };

        $result = $this->gate->canAccessRow($user, 'posts.edit', $model);

        $this->assertFalse($result, 'Should deny access when permission is missing');
    }

    /**
     * User counter for generating unique IDs.
     */
    protected static int $userCounter = 1;

    /**
     * Create a test user.
     */
    protected function createUser(): Authenticatable
    {
        $userId = self::$userCounter++;

        return new class ($userId) implements Authenticatable {
            public function __construct(public int $id)
            {
            }

            public function getAuthIdentifierName(): string
            {
                return 'id';
            }

            public function getAuthIdentifier(): int
            {
                return $this->id;
            }

            public function getAuthPassword(): string
            {
                return '';
            }

            public function getRememberToken(): string
            {
                return '';
            }

            public function setRememberToken($value): void
            {
            }

            public function getRememberTokenName(): string
            {
                return '';
            }

            public function getAuthPasswordName(): string
            {
                return 'password';
            }
        };
    }
}
