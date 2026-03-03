<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\RBAC\Traits;

use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Auth\RBAC\Traits\HasPermissionScopes;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Mockery;

/**
 * Test for HasPermissionScopes trait.
 */
class HasPermissionScopesTest extends TestCase
{
    /**
     * Test that scopeByPermission delegates to PermissionRuleManager.
     *
     * @return void
     */
    public function test_scope_by_permission_delegates_to_rule_manager(): void
    {
        // Arrange
        $model = new class () extends Model {
            use HasPermissionScopes;

            protected $table = 'test_models';
        };

        $query = $model->newQuery();
        $userId = 1;
        $permission = 'posts.view';

        // Mock PermissionRuleManager
        $ruleManager = Mockery::mock(PermissionRuleManager::class);
        $ruleManager->shouldReceive('scopeByPermission')
            ->once()
            ->with(Mockery::type(Builder::class), $userId, $permission)
            ->andReturn($query);

        Container::getInstance()->instance(PermissionRuleManager::class, $ruleManager);

        // Act
        $result = $model->byPermission($userId, $permission);

        // Assert
        $this->assertInstanceOf(Builder::class, $result);
    }

    /**
     * Test that scopeByPermission can be chained with other scopes.
     *
     * @return void
     */
    public function test_scope_by_permission_can_be_chained(): void
    {
        // Arrange
        $model = new class () extends Model {
            use HasPermissionScopes;

            protected $table = 'test_models';

            public function scopeActive($query)
            {
                return $query->where('status', 'active');
            }
        };

        $userId = 1;
        $permission = 'posts.view';

        // Mock PermissionRuleManager
        $ruleManager = Mockery::mock(PermissionRuleManager::class);
        $ruleManager->shouldReceive('scopeByPermission')
            ->once()
            ->with(Mockery::type(Builder::class), $userId, $permission)
            ->andReturnUsing(function ($query) {
                return $query;
            });

        Container::getInstance()->instance(PermissionRuleManager::class, $ruleManager);

        // Act
        $query = $model->byPermission($userId, $permission)->active();

        // Assert
        $this->assertInstanceOf(Builder::class, $query);
        $this->assertStringContainsString('status', $query->toSql());
    }

    /**
     * Test that scopeByPermission works with different user IDs.
     *
     * @return void
     */
    public function test_scope_by_permission_works_with_different_users(): void
    {
        // Arrange
        $model = new class () extends Model {
            use HasPermissionScopes;

            protected $table = 'test_models';
        };

        $permission = 'posts.view';

        // Mock PermissionRuleManager
        $ruleManager = Mockery::mock(PermissionRuleManager::class);

        // User 1
        $ruleManager->shouldReceive('scopeByPermission')
            ->once()
            ->with(Mockery::type(Builder::class), 1, $permission)
            ->andReturnUsing(function ($query) {
                return $query->where('user_id', 1);
            });

        // User 2
        $ruleManager->shouldReceive('scopeByPermission')
            ->once()
            ->with(Mockery::type(Builder::class), 2, $permission)
            ->andReturnUsing(function ($query) {
                return $query->where('user_id', 2);
            });

        Container::getInstance()->instance(PermissionRuleManager::class, $ruleManager);

        // Act
        $query1 = $model->byPermission(1, $permission);
        $query2 = $model->byPermission(2, $permission);

        // Assert
        $this->assertStringContainsString('user_id', $query1->toSql());
        $this->assertStringContainsString('user_id', $query2->toSql());
    }

    /**
     * Test that scopeByPermission works with different permissions.
     *
     * @return void
     */
    public function test_scope_by_permission_works_with_different_permissions(): void
    {
        // Arrange
        $model = new class () extends Model {
            use HasPermissionScopes;

            protected $table = 'test_models';
        };

        $userId = 1;

        // Mock PermissionRuleManager
        $ruleManager = Mockery::mock(PermissionRuleManager::class);

        // View permission
        $ruleManager->shouldReceive('scopeByPermission')
            ->once()
            ->with(Mockery::type(Builder::class), $userId, 'posts.view')
            ->andReturnUsing(function ($query) {
                return $query;
            });

        // Edit permission
        $ruleManager->shouldReceive('scopeByPermission')
            ->once()
            ->with(Mockery::type(Builder::class), $userId, 'posts.edit')
            ->andReturnUsing(function ($query) {
                return $query->where('user_id', 1);
            });

        Container::getInstance()->instance(PermissionRuleManager::class, $ruleManager);

        // Act
        $query1 = $model->byPermission($userId, 'posts.view');
        $query2 = $model->byPermission($userId, 'posts.edit');

        // Assert
        $this->assertInstanceOf(Builder::class, $query1);
        $this->assertInstanceOf(Builder::class, $query2);
    }

    /**
     * Test that scopeByPermission returns Builder instance.
     *
     * @return void
     */
    public function test_scope_by_permission_returns_builder(): void
    {
        // Arrange
        $model = new class () extends Model {
            use HasPermissionScopes;

            protected $table = 'test_models';
        };

        $userId = 1;
        $permission = 'posts.view';

        // Mock PermissionRuleManager
        $ruleManager = Mockery::mock(PermissionRuleManager::class);
        $ruleManager->shouldReceive('scopeByPermission')
            ->once()
            ->andReturnUsing(function ($query) {
                return $query;
            });

        Container::getInstance()->instance(PermissionRuleManager::class, $ruleManager);

        // Act
        $result = $model->byPermission($userId, $permission);

        // Assert
        $this->assertInstanceOf(Builder::class, $result);
    }

    /**
     * Clean up Mockery after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
