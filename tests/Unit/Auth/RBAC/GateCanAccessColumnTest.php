<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\Gate;
use Canvastack\Canvastack\Auth\RBAC\PermissionManager;
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Auth\RBAC\PolicyManager;
use Canvastack\Canvastack\Auth\RBAC\RoleManager;
use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\Role;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Log;

/**
 * Test for Gate::canAccessColumn() method.
 */
class GateCanAccessColumnTest extends TestCase
{
    protected Gate $gate;

    protected RoleManager $roleManager;

    protected PermissionManager $permissionManager;

    protected PolicyManager $policyManager;

    protected PermissionRuleManager $ruleManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Create managers
        $this->roleManager = new RoleManager();
        $this->permissionManager = new PermissionManager();
        $this->policyManager = new PolicyManager($this->roleManager, $this->permissionManager);
        $this->ruleManager = app(PermissionRuleManager::class);

        // Create Gate instance
        $this->gate = new Gate(
            $this->roleManager,
            $this->permissionManager,
            $this->policyManager,
            $this->ruleManager
        );

        // Enable fine-grained permissions
        config([
            'canvastack-rbac.fine_grained.enabled' => true,
            'canvastack-rbac.fine_grained.column_level.enabled' => true,
            'canvastack-rbac.fine_grained.cache.enabled' => true,
            'canvastack-rbac.fine_grained.cache.ttl.column' => 3600,
            'canvastack-rbac.authorization.super_admin_bypass' => true,
            'canvastack-rbac.authorization.super_admin_role' => 'super_admin',
        ]);
    }

    /**
     * Test that canAccessColumn returns false when user is null.
     */
    public function test_can_access_column_returns_false_when_user_is_null(): void
    {
        // Arrange
        $model = new \stdClass();
        $model->id = 1;

        // Expect log
        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', \Mockery::on(function ($data) {
                return $data['user_id'] === null
                    && $data['permission'] === 'posts.edit'
                    && $data['reason'] === 'column_level_denied'
                    && $data['context']['reason'] === 'user_not_authenticated'
                    && $data['context']['column'] === 'status';
            }));

        // Act
        $result = $this->gate->canAccessColumn(null, 'posts.edit', $model, 'status');

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Test that canAccessColumn returns false when user doesn't have basic permission.
     */
    public function test_can_access_column_returns_false_when_user_lacks_basic_permission(): void
    {
        // Arrange
        $user = User::factory()->create();
        $model = new \stdClass();
        $model->id = 1;

        // Expect log
        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', \Mockery::on(function ($data) use ($user) {
                return $data['user_id'] === $user->id
                    && $data['permission'] === 'posts.edit'
                    && $data['reason'] === 'basic_permission_denied'
                    && $data['context']['column'] === 'status';
            }));

        // Act
        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'status');

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Test that canAccessColumn returns true for super admin.
     */
    public function test_can_access_column_returns_true_for_super_admin(): void
    {
        // Arrange
        $user = User::factory()->create();
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $user->roles()->attach($superAdminRole->id);

        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $model = new \stdClass();
        $model->id = 1;

        // Act
        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'status');

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test that canAccessColumn checks column-level rules.
     */
    public function test_can_access_column_checks_column_level_rules(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create column-level rule (whitelist mode)
        $this->ruleManager->addColumnRule(
            $permission->id,
            \stdClass::class,
            ['title', 'content', 'excerpt'], // Allowed columns
            [] // No denied columns
        );

        $model = new \stdClass();
        $model->id = 1;

        // Act & Assert - Allowed column
        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'title');
        $this->assertTrue($result);

        // Act & Assert - Denied column
        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', \Mockery::on(function ($data) use ($user) {
                return $data['user_id'] === $user->id
                    && $data['permission'] === 'posts.edit'
                    && $data['reason'] === 'column_level_denied'
                    && $data['context']['column'] === 'status';
            }));

        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'status');
        $this->assertFalse($result);
    }

    /**
     * Test that canAccessColumn works with blacklist mode.
     */
    public function test_can_access_column_works_with_blacklist_mode(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create column-level rule (blacklist mode)
        $this->ruleManager->addColumnRule(
            $permission->id,
            \stdClass::class,
            [], // No allowed columns (blacklist mode)
            ['status', 'featured'] // Denied columns
        );

        $model = new \stdClass();
        $model->id = 1;

        // Act & Assert - Denied column
        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', \Mockery::on(function ($data) use ($user) {
                return $data['user_id'] === $user->id
                    && $data['permission'] === 'posts.edit'
                    && $data['reason'] === 'column_level_denied'
                    && $data['context']['column'] === 'status';
            }));

        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'status');
        $this->assertFalse($result);
    }

    /**
     * Test that canAccessColumn returns true when no rules exist.
     */
    public function test_can_access_column_returns_true_when_no_rules_exist(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $model = new \stdClass();
        $model->id = 1;

        // Act
        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'status');

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test that canAccessColumn returns true when fine-grained permissions are disabled.
     */
    public function test_can_access_column_returns_true_when_fine_grained_disabled(): void
    {
        // Arrange
        config(['canvastack-rbac.fine_grained.enabled' => false]);

        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $model = new \stdClass();
        $model->id = 1;

        // Act
        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'status');

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test that canAccessColumn returns true when column-level permissions are disabled.
     */
    public function test_can_access_column_returns_true_when_column_level_disabled(): void
    {
        // Arrange
        config(['canvastack-rbac.fine_grained.column_level.enabled' => false]);

        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $model = new \stdClass();
        $model->id = 1;

        // Act
        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'status');

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test that canAccessColumn logs denial with correct context.
     */
    public function test_can_access_column_logs_denial_with_correct_context(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create column-level rule
        $this->ruleManager->addColumnRule(
            $permission->id,
            \stdClass::class,
            ['title'], // Only title allowed
            []
        );

        $model = new \stdClass();
        $model->id = 123;

        // Expect log with correct context
        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', \Mockery::on(function ($data) use ($user) {
                return $data['user_id'] === $user->id
                    && $data['permission'] === 'posts.edit'
                    && $data['reason'] === 'column_level_denied'
                    && $data['context']['model_type'] === \stdClass::class
                    && $data['context']['model_id'] === 123
                    && $data['context']['column'] === 'status'
                    && isset($data['timestamp']);
            }));

        // Act
        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'status');

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Test that canAccessColumn works with multiple columns.
     */
    public function test_can_access_column_works_with_multiple_columns(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create column-level rule
        $this->ruleManager->addColumnRule(
            $permission->id,
            \stdClass::class,
            ['title', 'content', 'excerpt'],
            []
        );

        $model = new \stdClass();
        $model->id = 1;

        // Act & Assert - Check multiple columns
        $this->assertTrue($this->gate->canAccessColumn($user, 'posts.edit', $model, 'title'));
        $this->assertTrue($this->gate->canAccessColumn($user, 'posts.edit', $model, 'content'));
        $this->assertTrue($this->gate->canAccessColumn($user, 'posts.edit', $model, 'excerpt'));

        // Expect log for denied column
        Log::shouldReceive('warning')->once();
        $this->assertFalse($this->gate->canAccessColumn($user, 'posts.edit', $model, 'status'));
    }

    /**
     * Test that canAccessColumn uses cache.
     */
    public function test_can_access_column_uses_cache(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create column-level rule
        $this->ruleManager->addColumnRule(
            $permission->id,
            \stdClass::class,
            ['title'],
            []
        );

        $model = new \stdClass();
        $model->id = 1;

        // Act - First call (cache miss)
        $result1 = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'title');

        // Act - Second call (cache hit)
        $result2 = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'title');

        // Assert
        $this->assertTrue($result1);
        $this->assertTrue($result2);
    }

    /**
     * Test that canAccessColumn handles model without ID.
     */
    public function test_can_access_column_handles_model_without_id(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $model = new \stdClass(); // No ID

        // Act
        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'title');

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test that canAccessColumn works with different model types.
     */
    public function test_can_access_column_works_with_different_model_types(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create rule for specific model type
        $this->ruleManager->addColumnRule(
            $permission->id,
            \stdClass::class,
            ['title'],
            []
        );

        $stdModel = new \stdClass();
        $stdModel->id = 1;

        $arrayModel = new \ArrayObject(['id' => 2]);

        // Act & Assert - stdClass model (has rule)
        $result1 = $this->gate->canAccessColumn($user, 'posts.edit', $stdModel, 'title');
        $this->assertTrue($result1);

        // Act & Assert - ArrayObject model (no rule, should allow)
        $result2 = $this->gate->canAccessColumn($user, 'posts.edit', $arrayModel, 'title');
        $this->assertTrue($result2);
    }

    /**
     * Test that canAccessColumn response time is under 10ms.
     */
    public function test_can_access_column_response_time_is_under_10ms(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create column-level rule
        $this->ruleManager->addColumnRule(
            $permission->id,
            \stdClass::class,
            ['title', 'content'],
            []
        );

        $model = new \stdClass();
        $model->id = 1;

        // Act
        $start = microtime(true);
        $this->gate->canAccessColumn($user, 'posts.edit', $model, 'title');
        $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds

        // Assert
        $this->assertLessThan(10, $duration, 'Column-level check should complete within 10ms');
    }
}
