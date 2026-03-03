<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\Gate;
use Canvastack\Canvastack\Auth\RBAC\PermissionManager;
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Auth\RBAC\PolicyManager;
use Canvastack\Canvastack\Auth\RBAC\RoleManager;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Test for Gate::canAccessJsonAttribute().
 */
class GateCanAccessJsonAttributeTest extends TestCase
{
    protected Gate $gate;

    protected RoleManager $roleManager;

    protected PermissionManager $permissionManager;

    protected PolicyManager $policyManager;

    protected PermissionRuleManager $ruleManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleManager = Mockery::mock(RoleManager::class);
        $this->permissionManager = Mockery::mock(PermissionManager::class);
        $this->policyManager = Mockery::mock(PolicyManager::class);
        $this->ruleManager = Mockery::mock(PermissionRuleManager::class);

        $this->gate = new Gate(
            $this->roleManager,
            $this->permissionManager,
            $this->policyManager,
            $this->ruleManager
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that canAccessJsonAttribute returns false when user is null.
     */
    public function test_can_access_json_attribute_returns_false_when_user_is_null(): void
    {
        Log::shouldReceive('warning')->once();

        $model = new class () {
            public int $id = 1;
        };

        $result = $this->gate->canAccessJsonAttribute(
            null,
            'posts.edit',
            $model,
            'metadata',
            'seo.title'
        );

        $this->assertFalse($result);
    }

    /**
     * Test that canAccessJsonAttribute returns false when basic permission is denied.
     */
    public function test_can_access_json_attribute_returns_false_when_basic_permission_denied(): void
    {
        $user = Mockery::mock(Authenticatable::class);
        $user->id = 1;

        $model = new class () {
            public int $id = 1;
        };

        // Mock basic permission check - denied
        $this->permissionManager
            ->shouldReceive('userHasPermission')
            ->once()
            ->with(1, 'posts.edit')
            ->andReturn(false);

        // Mock super admin check
        config(['canvastack-rbac.authorization.super_admin_bypass' => false]);

        Log::shouldReceive('warning')->once();

        $result = $this->gate->canAccessJsonAttribute(
            $user,
            'posts.edit',
            $model,
            'metadata',
            'seo.title'
        );

        $this->assertFalse($result);
    }

    /**
     * Test that canAccessJsonAttribute returns true for super admin.
     */
    public function test_can_access_json_attribute_returns_true_for_super_admin(): void
    {
        $user = Mockery::mock(Authenticatable::class);
        $user->id = 1;

        $model = new class () {
            public int $id = 1;
        };

        // Mock super admin check (called twice: once in hasPermission, once in canAccessJsonAttribute)
        config(['canvastack-rbac.authorization.super_admin_bypass' => true]);
        config(['canvastack-rbac.authorization.super_admin_role' => 'super_admin']);

        $this->roleManager
            ->shouldReceive('userHasRole')
            ->twice()
            ->with(1, 'super_admin')
            ->andReturn(true);

        // hasPermission returns true for super admin, so userHasPermission is not called

        $result = $this->gate->canAccessJsonAttribute(
            $user,
            'posts.edit',
            $model,
            'metadata',
            'seo.title'
        );

        $this->assertTrue($result);
    }

    /**
     * Test that canAccessJsonAttribute checks rule manager when not super admin.
     */
    public function test_can_access_json_attribute_checks_rule_manager_when_not_super_admin(): void
    {
        $user = Mockery::mock(Authenticatable::class);
        $user->id = 1;

        $model = new class () {
            public int $id = 1;
        };

        // Mock basic permission check - allowed
        $this->permissionManager
            ->shouldReceive('userHasPermission')
            ->once()
            ->with(1, 'posts.edit')
            ->andReturn(true);

        // Mock super admin check - not super admin (called twice: once in hasPermission, once in canAccessJsonAttribute)
        config(['canvastack-rbac.authorization.super_admin_bypass' => true]);
        config(['canvastack-rbac.authorization.super_admin_role' => 'super_admin']);

        $this->roleManager
            ->shouldReceive('userHasRole')
            ->twice()
            ->with(1, 'super_admin')
            ->andReturn(false);

        // Mock rule manager check - allowed
        $this->ruleManager
            ->shouldReceive('canAccessJsonAttribute')
            ->once()
            ->with(1, 'posts.edit', $model, 'metadata', 'seo.title')
            ->andReturn(true);

        $result = $this->gate->canAccessJsonAttribute(
            $user,
            'posts.edit',
            $model,
            'metadata',
            'seo.title'
        );

        $this->assertTrue($result);
    }

    /**
     * Test that canAccessJsonAttribute returns false when rule manager denies.
     */
    public function test_can_access_json_attribute_returns_false_when_rule_manager_denies(): void
    {
        $user = Mockery::mock(Authenticatable::class);
        $user->id = 1;

        $model = new class () {
            public int $id = 1;
        };

        // Mock basic permission check - allowed
        $this->permissionManager
            ->shouldReceive('userHasPermission')
            ->once()
            ->with(1, 'posts.edit')
            ->andReturn(true);

        // Mock super admin check - not super admin (called twice: once in hasPermission, once in canAccessJsonAttribute)
        config(['canvastack-rbac.authorization.super_admin_bypass' => true]);
        config(['canvastack-rbac.authorization.super_admin_role' => 'super_admin']);

        $this->roleManager
            ->shouldReceive('userHasRole')
            ->twice()
            ->with(1, 'super_admin')
            ->andReturn(false);

        // Mock rule manager check - denied
        $this->ruleManager
            ->shouldReceive('canAccessJsonAttribute')
            ->once()
            ->with(1, 'posts.edit', $model, 'metadata', 'seo.title')
            ->andReturn(false);

        Log::shouldReceive('warning')->once();

        $result = $this->gate->canAccessJsonAttribute(
            $user,
            'posts.edit',
            $model,
            'metadata',
            'seo.title'
        );

        $this->assertFalse($result);
    }

    /**
     * Test that canAccessJsonAttribute logs denial when user is null.
     */
    public function test_can_access_json_attribute_logs_denial_when_user_is_null(): void
    {
        $model = new class () {
            public int $id = 1;
        };

        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', Mockery::on(function ($data) use ($model) {
                return $data['user_id'] === null
                    && $data['permission'] === 'posts.edit'
                    && $data['reason'] === 'json_attribute_denied'
                    && $data['context']['reason'] === 'user_not_authenticated'
                    && $data['context']['json_column'] === 'metadata'
                    && $data['context']['path'] === 'seo.title';
            }));

        $this->gate->canAccessJsonAttribute(
            null,
            'posts.edit',
            $model,
            'metadata',
            'seo.title'
        );

        $this->assertTrue(true); // Assert to avoid risky test
    }

    /**
     * Test that canAccessJsonAttribute logs denial when basic permission denied.
     */
    public function test_can_access_json_attribute_logs_denial_when_basic_permission_denied(): void
    {
        $user = Mockery::mock(Authenticatable::class);
        $user->id = 1;

        $model = new class () {
            public int $id = 1;
        };

        // Mock basic permission check - denied
        $this->permissionManager
            ->shouldReceive('userHasPermission')
            ->once()
            ->with(1, 'posts.edit')
            ->andReturn(false);

        // Mock super admin check
        config(['canvastack-rbac.authorization.super_admin_bypass' => false]);

        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', Mockery::on(function ($data) use ($model) {
                return $data['user_id'] === 1
                    && $data['permission'] === 'posts.edit'
                    && $data['reason'] === 'basic_permission_denied'
                    && $data['context']['json_column'] === 'metadata'
                    && $data['context']['path'] === 'seo.title';
            }));

        $this->gate->canAccessJsonAttribute(
            $user,
            'posts.edit',
            $model,
            'metadata',
            'seo.title'
        );

        $this->assertTrue(true); // Assert to avoid risky test
    }

    /**
     * Test that canAccessJsonAttribute logs denial when rule manager denies.
     */
    public function test_can_access_json_attribute_logs_denial_when_rule_manager_denies(): void
    {
        $user = Mockery::mock(Authenticatable::class);
        $user->id = 1;

        $model = new class () {
            public int $id = 1;
        };

        // Mock basic permission check - allowed
        $this->permissionManager
            ->shouldReceive('userHasPermission')
            ->once()
            ->with(1, 'posts.edit')
            ->andReturn(true);

        // Mock super admin check - not super admin (called twice: once in hasPermission, once in canAccessJsonAttribute)
        config(['canvastack-rbac.authorization.super_admin_bypass' => true]);
        config(['canvastack-rbac.authorization.super_admin_role' => 'super_admin']);

        $this->roleManager
            ->shouldReceive('userHasRole')
            ->twice()
            ->with(1, 'super_admin')
            ->andReturn(false);

        // Mock rule manager check - denied
        $this->ruleManager
            ->shouldReceive('canAccessJsonAttribute')
            ->once()
            ->with(1, 'posts.edit', $model, 'metadata', 'seo.title')
            ->andReturn(false);

        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', Mockery::on(function ($data) use ($model) {
                return $data['user_id'] === 1
                    && $data['permission'] === 'posts.edit'
                    && $data['reason'] === 'json_attribute_denied'
                    && $data['context']['json_column'] === 'metadata'
                    && $data['context']['path'] === 'seo.title';
            }));

        $this->gate->canAccessJsonAttribute(
            $user,
            'posts.edit',
            $model,
            'metadata',
            'seo.title'
        );

        $this->assertTrue(true); // Assert to avoid risky test
    }

    /**
     * Test that canAccessJsonAttribute works with nested JSON paths.
     */
    public function test_can_access_json_attribute_works_with_nested_paths(): void
    {
        $user = Mockery::mock(Authenticatable::class);
        $user->id = 1;

        $model = new class () {
            public int $id = 1;
        };

        // Mock basic permission check - allowed
        $this->permissionManager
            ->shouldReceive('userHasPermission')
            ->once()
            ->with(1, 'posts.edit')
            ->andReturn(true);

        // Mock super admin check - not super admin (called twice: once in hasPermission, once in canAccessJsonAttribute)
        config(['canvastack-rbac.authorization.super_admin_bypass' => true]);
        config(['canvastack-rbac.authorization.super_admin_role' => 'super_admin']);

        $this->roleManager
            ->shouldReceive('userHasRole')
            ->twice()
            ->with(1, 'super_admin')
            ->andReturn(false);

        // Mock rule manager check with nested path
        $this->ruleManager
            ->shouldReceive('canAccessJsonAttribute')
            ->once()
            ->with(1, 'posts.edit', $model, 'metadata', 'seo.meta.description')
            ->andReturn(true);

        $result = $this->gate->canAccessJsonAttribute(
            $user,
            'posts.edit',
            $model,
            'metadata',
            'seo.meta.description'
        );

        $this->assertTrue($result);
    }

    /**
     * Test that canAccessJsonAttribute works with different JSON columns.
     */
    public function test_can_access_json_attribute_works_with_different_json_columns(): void
    {
        $user = Mockery::mock(Authenticatable::class);
        $user->id = 1;

        $model = new class () {
            public int $id = 1;
        };

        // Mock basic permission check - allowed
        $this->permissionManager
            ->shouldReceive('userHasPermission')
            ->once()
            ->with(1, 'posts.edit')
            ->andReturn(true);

        // Mock super admin check - not super admin (called twice: once in hasPermission, once in canAccessJsonAttribute)
        config(['canvastack-rbac.authorization.super_admin_bypass' => true]);
        config(['canvastack-rbac.authorization.super_admin_role' => 'super_admin']);

        $this->roleManager
            ->shouldReceive('userHasRole')
            ->twice()
            ->with(1, 'super_admin')
            ->andReturn(false);

        // Mock rule manager check with different column
        $this->ruleManager
            ->shouldReceive('canAccessJsonAttribute')
            ->once()
            ->with(1, 'posts.edit', $model, 'settings', 'notifications.email')
            ->andReturn(true);

        $result = $this->gate->canAccessJsonAttribute(
            $user,
            'posts.edit',
            $model,
            'settings',
            'notifications.email'
        );

        $this->assertTrue($result);
    }

    /**
     * Test that canAccessJsonAttribute includes model info in log context.
     */
    public function test_can_access_json_attribute_includes_model_info_in_log_context(): void
    {
        $user = Mockery::mock(Authenticatable::class);
        $user->id = 1;

        $model = new class () {
            public int $id = 123;
        };

        // Mock basic permission check - denied
        $this->permissionManager
            ->shouldReceive('userHasPermission')
            ->once()
            ->with(1, 'posts.edit')
            ->andReturn(false);

        // Mock super admin check
        config(['canvastack-rbac.authorization.super_admin_bypass' => false]);

        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', Mockery::on(function ($data) use ($model) {
                return $data['context']['model_type'] === get_class($model)
                    && $data['context']['model_id'] === 123;
            }));

        $this->gate->canAccessJsonAttribute(
            $user,
            'posts.edit',
            $model,
            'metadata',
            'seo.title'
        );

        $this->assertTrue(true); // Assert to avoid risky test
    }
}
