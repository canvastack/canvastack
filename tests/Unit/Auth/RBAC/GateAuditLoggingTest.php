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
 * Test for Gate audit logging.
 */
class GateAuditLoggingTest extends TestCase
{
    protected Gate $gate;

    protected RoleManager $roleManager;

    protected PermissionManager $permissionManager;

    protected PolicyManager $policyManager;

    protected PermissionRuleManager $ruleManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Use real managers
        $this->roleManager = new RoleManager();
        $this->permissionManager = new PermissionManager();
        $this->policyManager = new PolicyManager($this->roleManager, $this->permissionManager);
        $this->ruleManager = app(PermissionRuleManager::class);

        $this->gate = new Gate(
            $this->roleManager,
            $this->permissionManager,
            $this->policyManager,
            $this->ruleManager
        );

        // Configure super admin settings
        config([
            'canvastack-rbac.authorization.super_admin_bypass' => true,
            'canvastack-rbac.authorization.super_admin_role' => 'super_admin',
        ]);
    }

    /**
     * Test that row-level denial is logged when user is not authenticated.
     */
    public function test_logs_row_level_denial_when_user_not_authenticated(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', \Mockery::on(function ($data) {
                return $data['user_id'] === null
                    && $data['permission'] === 'posts.edit'
                    && $data['reason'] === 'row_level_denied'
                    && $data['context']['reason'] === 'user_not_authenticated'
                    && $data['context']['model_type'] === 'stdClass'
                    && isset($data['timestamp']);
            }));

        $model = new \stdClass();
        $model->id = 1;

        $result = $this->gate->canAccessRow(null, 'posts.edit', $model);

        $this->assertFalse($result);
    }

    /**
     * Test that row-level denial is logged when basic permission is denied.
     */
    public function test_logs_row_level_denial_when_basic_permission_denied(): void
    {
        $user = User::factory()->create();
        // User has no permissions

        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', \Mockery::on(function ($data) use ($user) {
                return $data['user_id'] === $user->id
                    && $data['permission'] === 'posts.edit'
                    && $data['reason'] === 'basic_permission_denied'
                    && $data['context']['model_type'] === 'stdClass'
                    && $data['context']['model_id'] === 1
                    && isset($data['timestamp']);
            }));

        $model = new \stdClass();
        $model->id = 1;

        $result = $this->gate->canAccessRow($user, 'posts.edit', $model);

        $this->assertFalse($result);
    }

    /**
     * Test that row-level denial is logged when row-level rules deny access.
     */
    public function test_logs_row_level_denial_when_rules_deny_access(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create row-level rule that will deny access
        $this->ruleManager->addRowRule(
            $permission->id,
            \stdClass::class,
            ['user_id' => 999], // Different user ID
            'AND'
        );

        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', \Mockery::on(function ($data) use ($user) {
                return $data['user_id'] === $user->id
                    && $data['permission'] === 'posts.edit'
                    && $data['reason'] === 'row_level_denied'
                    && $data['context']['model_type'] === 'stdClass'
                    && $data['context']['model_id'] === 1
                    && isset($data['timestamp']);
            }));

        $model = new \stdClass();
        $model->id = 1;
        $model->user_id = $user->id; // Different from rule

        $result = $this->gate->canAccessRow($user, 'posts.edit', $model);

        $this->assertFalse($result);
    }

    /**
     * Test that column-level denial is logged when user is not authenticated.
     */
    public function test_logs_column_level_denial_when_user_not_authenticated(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', \Mockery::on(function ($data) {
                return $data['user_id'] === null
                    && $data['permission'] === 'posts.edit'
                    && $data['reason'] === 'column_level_denied'
                    && $data['context']['reason'] === 'user_not_authenticated'
                    && $data['context']['model_type'] === 'stdClass'
                    && $data['context']['column'] === 'status'
                    && isset($data['timestamp']);
            }));

        $model = new \stdClass();
        $model->id = 1;

        $result = $this->gate->canAccessColumn(null, 'posts.edit', $model, 'status');

        $this->assertFalse($result);
    }

    /**
     * Test that column-level denial is logged when basic permission is denied.
     */
    public function test_logs_column_level_denial_when_basic_permission_denied(): void
    {
        $user = User::factory()->create();
        // User has no permissions

        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', \Mockery::on(function ($data) use ($user) {
                return $data['user_id'] === $user->id
                    && $data['permission'] === 'posts.edit'
                    && $data['reason'] === 'basic_permission_denied'
                    && $data['context']['model_type'] === 'stdClass'
                    && $data['context']['model_id'] === 1
                    && $data['context']['column'] === 'status'
                    && isset($data['timestamp']);
            }));

        $model = new \stdClass();
        $model->id = 1;

        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'status');

        $this->assertFalse($result);
    }

    /**
     * Test that column-level denial is logged when column-level rules deny access.
     */
    public function test_logs_column_level_denial_when_rules_deny_access(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create column-level rule (whitelist mode - only title allowed)
        $this->ruleManager->addColumnRule(
            $permission->id,
            \stdClass::class,
            ['title'], // Only title allowed
            []
        );

        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', \Mockery::on(function ($data) use ($user) {
                return $data['user_id'] === $user->id
                    && $data['permission'] === 'posts.edit'
                    && $data['reason'] === 'column_level_denied'
                    && $data['context']['model_type'] === 'stdClass'
                    && $data['context']['model_id'] === 1
                    && $data['context']['column'] === 'status'
                    && isset($data['timestamp']);
            }));

        $model = new \stdClass();
        $model->id = 1;

        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'status');

        $this->assertFalse($result);
    }

    /**
     * Test that JSON attribute denial is logged when user is not authenticated.
     */
    public function test_logs_json_attribute_denial_when_user_not_authenticated(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', \Mockery::on(function ($data) {
                return $data['user_id'] === null
                    && $data['permission'] === 'posts.edit'
                    && $data['reason'] === 'json_attribute_denied'
                    && $data['context']['reason'] === 'user_not_authenticated'
                    && $data['context']['model_type'] === 'stdClass'
                    && $data['context']['json_column'] === 'metadata'
                    && $data['context']['path'] === 'seo.title'
                    && isset($data['timestamp']);
            }));

        $model = new \stdClass();
        $model->id = 1;

        $result = $this->gate->canAccessJsonAttribute(null, 'posts.edit', $model, 'metadata', 'seo.title');

        $this->assertFalse($result);
    }

    /**
     * Test that JSON attribute denial is logged when basic permission is denied.
     */
    public function test_logs_json_attribute_denial_when_basic_permission_denied(): void
    {
        $user = User::factory()->create();
        // User has no permissions

        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', \Mockery::on(function ($data) use ($user) {
                return $data['user_id'] === $user->id
                    && $data['permission'] === 'posts.edit'
                    && $data['reason'] === 'basic_permission_denied'
                    && $data['context']['model_type'] === 'stdClass'
                    && $data['context']['model_id'] === 1
                    && $data['context']['json_column'] === 'metadata'
                    && $data['context']['path'] === 'seo.title'
                    && isset($data['timestamp']);
            }));

        $model = new \stdClass();
        $model->id = 1;

        $result = $this->gate->canAccessJsonAttribute($user, 'posts.edit', $model, 'metadata', 'seo.title');

        $this->assertFalse($result);
    }

    /**
     * Test that JSON attribute denial is logged when JSON attribute rules deny access.
     */
    public function test_logs_json_attribute_denial_when_rules_deny_access(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create JSON attribute rule (whitelist mode - only social.* allowed)
        $this->ruleManager->addJsonAttributeRule(
            $permission->id,
            \stdClass::class,
            'metadata',
            ['social.*'], // Only social.* allowed
            []
        );

        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', \Mockery::on(function ($data) use ($user) {
                return $data['user_id'] === $user->id
                    && $data['permission'] === 'posts.edit'
                    && $data['reason'] === 'json_attribute_denied'
                    && $data['context']['model_type'] === 'stdClass'
                    && $data['context']['model_id'] === 1
                    && $data['context']['json_column'] === 'metadata'
                    && $data['context']['path'] === 'seo.title'
                    && isset($data['timestamp']);
            }));

        $model = new \stdClass();
        $model->id = 1;

        $result = $this->gate->canAccessJsonAttribute($user, 'posts.edit', $model, 'metadata', 'seo.title');

        $this->assertFalse($result);
    }

    /**
     * Test that no log is created when access is granted.
     */
    public function test_does_not_log_when_access_granted(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // No rules, so access should be granted

        // Should NOT receive any log call
        Log::shouldReceive('warning')->never();

        $model = new \stdClass();
        $model->id = 1;

        $result = $this->gate->canAccessRow($user, 'posts.edit', $model);

        $this->assertTrue($result);
    }

    /**
     * Test that no log is created when super admin bypasses checks.
     */
    public function test_does_not_log_when_super_admin_bypasses(): void
    {
        $user = User::factory()->create();
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $user->roles()->attach($superAdminRole->id);

        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Should NOT receive any log call
        Log::shouldReceive('warning')->never();

        $model = new \stdClass();
        $model->id = 1;

        $result = $this->gate->canAccessRow($user, 'posts.edit', $model);

        $this->assertTrue($result);
    }

    /**
     * Test that log includes all required fields.
     */
    public function test_log_includes_all_required_fields(): void
    {
        $user = User::factory()->create();
        // User has no permissions

        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', \Mockery::on(function ($data) use ($user) {
                // Verify all required fields are present
                $this->assertArrayHasKey('user_id', $data);
                $this->assertArrayHasKey('permission', $data);
                $this->assertArrayHasKey('reason', $data);
                $this->assertArrayHasKey('context', $data);
                $this->assertArrayHasKey('timestamp', $data);

                // Verify field values
                $this->assertEquals($user->id, $data['user_id']);
                $this->assertEquals('posts.edit', $data['permission']);
                $this->assertEquals('basic_permission_denied', $data['reason']);
                $this->assertIsArray($data['context']);
                $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $data['timestamp']);

                return true;
            }));

        $model = new \stdClass();
        $model->id = 1;

        $this->gate->canAccessRow($user, 'posts.edit', $model);
    }

    /**
     * Test that log includes model information in context.
     */
    public function test_log_includes_model_information(): void
    {
        $user = User::factory()->create();
        // User has no permissions

        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', \Mockery::on(function ($data) {
                $this->assertArrayHasKey('context', $data);
                $this->assertArrayHasKey('model_type', $data['context']);
                $this->assertArrayHasKey('model_id', $data['context']);
                $this->assertEquals('stdClass', $data['context']['model_type']);
                $this->assertEquals(1, $data['context']['model_id']);

                return true;
            }));

        $model = new \stdClass();
        $model->id = 1;

        $this->gate->canAccessRow($user, 'posts.edit', $model);
    }

    /**
     * Test that log handles model without ID gracefully.
     */
    public function test_log_handles_model_without_id(): void
    {
        $user = User::factory()->create();
        // User has no permissions

        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', \Mockery::on(function ($data) {
                $this->assertArrayHasKey('context', $data);
                $this->assertArrayHasKey('model_type', $data['context']);
                $this->assertArrayHasKey('model_id', $data['context']);
                $this->assertEquals('stdClass', $data['context']['model_type']);
                $this->assertNull($data['context']['model_id']);

                return true;
            }));

        $model = new \stdClass();
        // No ID property

        $this->gate->canAccessRow($user, 'posts.edit', $model);
    }
}
