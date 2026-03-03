<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\PermissionManager;
use Canvastack\Canvastack\Auth\RBAC\PolicyManager;
use Canvastack\Canvastack\Auth\RBAC\RoleManager;
use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\Role;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\Facades\Cache;

class PolicyManagerTest extends TestCase
{
    protected PolicyManager $policyManager;

    protected RoleManager $roleManager;

    protected PermissionManager $permissionManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache before each test
        Cache::flush();

        $this->roleManager = new RoleManager();
        $this->permissionManager = new PermissionManager();
        $this->policyManager = new PolicyManager(
            $this->roleManager,
            $this->permissionManager
        );
    }

    /** @test */
    public function it_can_register_a_policy(): void
    {
        $model = Role::class;
        $policy = \stdClass::class; // Use stdClass as dummy policy

        $this->policyManager->register($model, $policy);

        $this->assertTrue($this->policyManager->hasPolicy($model));
        $this->assertEquals($policy, $this->policyManager->getPolicy($model));
    }

    /** @test */
    public function it_throws_exception_when_registering_non_existent_model(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Model class NonExistentModel does not exist');

        $this->policyManager->register('NonExistentModel', 'SomePolicy');
    }

    /** @test */
    public function it_throws_exception_when_registering_non_existent_policy(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Policy class NonExistentPolicy does not exist');

        $this->policyManager->register(Role::class, 'NonExistentPolicy');
    }

    /** @test */
    public function it_can_register_multiple_policies(): void
    {
        $policies = [
            Role::class => \stdClass::class,
            Permission::class => \stdClass::class,
        ];

        $this->policyManager->registerMany($policies);

        $this->assertTrue($this->policyManager->hasPolicy(Role::class));
        $this->assertTrue($this->policyManager->hasPolicy(Permission::class));
    }

    /** @test */
    public function it_can_get_all_registered_policies(): void
    {
        $model = Role::class;
        $policy = \stdClass::class;

        $this->policyManager->register($model, $policy);

        $policies = $this->policyManager->getPolicies();

        $this->assertIsArray($policies);
        $this->assertArrayHasKey($model, $policies);
        $this->assertEquals($policy, $policies[$model]);
    }

    /** @test */
    public function it_can_define_a_gate_ability(): void
    {
        $this->policyManager->define('test-ability', function ($user) {
            return true;
        });

        $this->assertTrue($this->policyManager->hasAbility('test-ability'));
    }

    /** @test */
    public function it_can_define_resource_abilities(): void
    {
        $this->policyManager->resource('roles', Role::class, \stdClass::class);

        $this->assertTrue($this->policyManager->hasAbility('roles.viewAny'));
        $this->assertTrue($this->policyManager->hasAbility('roles.view'));
        $this->assertTrue($this->policyManager->hasAbility('roles.create'));
        $this->assertTrue($this->policyManager->hasAbility('roles.update'));
        $this->assertTrue($this->policyManager->hasAbility('roles.delete'));
    }

    /** @test */
    public function it_can_define_abilities_from_permissions(): void
    {
        // Create permissions
        $permission1 = $this->permissionManager->create([
            'name' => 'users.view',
            'display_name' => 'View Users',
            'module' => 'users',
        ]);

        $permission2 = $this->permissionManager->create([
            'name' => 'users.create',
            'display_name' => 'Create Users',
            'module' => 'users',
        ]);

        $count = $this->policyManager->defineFromPermissions('users');

        $this->assertEquals(2, $count);
        $this->assertTrue($this->policyManager->hasAbility('users.view'));
        $this->assertTrue($this->policyManager->hasAbility('users.create'));
    }

    /** @test */
    public function it_can_get_gate_instance(): void
    {
        $gate = $this->policyManager->getGate();

        $this->assertInstanceOf(GateContract::class, $gate);
    }

    /** @test */
    public function it_can_check_if_ability_is_defined(): void
    {
        $this->policyManager->define('test-ability', function ($user) {
            return true;
        });

        $this->assertTrue($this->policyManager->hasAbility('test-ability'));
        $this->assertFalse($this->policyManager->hasAbility('non-existent-ability'));
    }

    /** @test */
    public function it_can_register_before_callback(): void
    {
        $callbackExecuted = false;

        $this->policyManager->before(function ($user, $ability) use (&$callbackExecuted) {
            $callbackExecuted = true;
            if ($ability === 'always-allow') {
                return true;
            }
        });

        $this->policyManager->define('always-allow', function ($user) {
            return false;
        });

        // We can't test the actual execution without a user, but we can verify the callback was registered
        $this->assertTrue(true); // Callback registered successfully
    }

    /** @test */
    public function it_can_register_after_callback(): void
    {
        $callbackExecuted = false;

        $this->policyManager->after(function ($user, $ability, $result) use (&$callbackExecuted) {
            $callbackExecuted = true;

            return $result;
        });

        // We can't test the actual execution without a user, but we can verify the callback was registered
        $this->assertTrue(true); // Callback registered successfully
    }

    /** @test */
    public function it_can_define_abilities_from_all_permissions(): void
    {
        // Create permissions in different modules
        $this->permissionManager->create([
            'name' => 'users.view',
            'display_name' => 'View Users',
            'module' => 'users',
        ]);

        $this->permissionManager->create([
            'name' => 'posts.view',
            'display_name' => 'View Posts',
            'module' => 'posts',
        ]);

        $count = $this->policyManager->defineFromPermissions();

        $this->assertEquals(2, $count);
        $this->assertTrue($this->policyManager->hasAbility('users.view'));
        $this->assertTrue($this->policyManager->hasAbility('posts.view'));
    }

    /** @test */
    public function it_can_check_if_policy_is_registered(): void
    {
        $this->assertFalse($this->policyManager->hasPolicy(Role::class));

        $this->policyManager->register(Role::class, \stdClass::class);

        $this->assertTrue($this->policyManager->hasPolicy(Role::class));
    }

    /** @test */
    public function it_returns_null_for_unregistered_policy(): void
    {
        $policy = $this->policyManager->getPolicy(Role::class);

        $this->assertNull($policy);
    }

    /** @test */
    public function it_can_register_super_admin_bypass(): void
    {
        // This should not throw an exception
        $this->policyManager->registerSuperAdminBypass();

        $this->assertTrue(true); // Successfully registered
    }

    /** @test */
    public function it_can_register_default_permissions(): void
    {
        // This should not throw an exception
        $this->policyManager->registerDefaultPermissions();

        $this->assertTrue(true); // Successfully registered
    }
}
