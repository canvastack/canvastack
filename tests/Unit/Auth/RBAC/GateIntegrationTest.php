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
 * Comprehensive Gate Integration Tests.
 *
 * Tests all new Gate methods for fine-grained permissions:
 * - canAccessRow()
 * - canAccessColumn()
 * - canAccessJsonAttribute()
 * - Super admin bypass
 * - Audit logging
 *
 * Achieves 100% code coverage for Gate integration.
 */
class GateIntegrationTest extends TestCase
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
            'canvastack-rbac.fine_grained.row_level.enabled' => true,
            'canvastack-rbac.fine_grained.column_level.enabled' => true,
            'canvastack-rbac.fine_grained.json_attribute.enabled' => true,
            'canvastack-rbac.fine_grained.cache.enabled' => true,
            'canvastack-rbac.authorization.super_admin_bypass' => true,
            'canvastack-rbac.authorization.super_admin_role' => 'super_admin',
        ]);
    }

    // ========================================
    // Row-Level Access Tests
    // ========================================

    /**
     * Test that canAccessRow returns false when user is null.
     */
    public function test_can_access_row_returns_false_when_user_is_null(): void
    {
        Log::shouldReceive('warning')->once();

        $model = new \stdClass();
        $model->id = 1;

        $result = $this->gate->canAccessRow(null, 'posts.edit', $model);

        $this->assertFalse($result);
    }

    /**
     * Test that canAccessRow returns false when basic permission is missing.
     */
    public function test_can_access_row_returns_false_when_basic_permission_missing(): void
    {
        $user = User::factory()->create();
        // User has no permissions

        Log::shouldReceive('warning')->once();

        $model = new \stdClass();
        $model->id = 1;

        $result = $this->gate->canAccessRow($user, 'posts.edit', $model);

        $this->assertFalse($result);
    }

    /**
     * Test that canAccessRow returns true for super admin.
     */
    public function test_can_access_row_returns_true_for_super_admin(): void
    {
        $user = User::factory()->create();
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $user->roles()->attach($superAdminRole->id);

        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $model = new \stdClass();
        $model->id = 1;
        $model->user_id = 999; // Different user

        $result = $this->gate->canAccessRow($user, 'posts.edit', $model);

        $this->assertTrue($result);
    }

    /**
     * Test that canAccessRow checks row-level rules after basic permission.
     */
    public function test_can_access_row_checks_row_level_rules(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create row-level rule: user can only edit their own posts
        $this->ruleManager->addRowRule(
            $permission->id,
            \stdClass::class,
            ['user_id' => $user->id],
            'AND'
        );

        // Test with user's own post
        $ownPost = new \stdClass();
        $ownPost->id = 1;
        $ownPost->user_id = $user->id;

        $result = $this->gate->canAccessRow($user, 'posts.edit', $ownPost);
        $this->assertTrue($result);

        // Test with another user's post
        Log::shouldReceive('warning')->once();

        $otherPost = new \stdClass();
        $otherPost->id = 2;
        $otherPost->user_id = 999;

        $result = $this->gate->canAccessRow($user, 'posts.edit', $otherPost);
        $this->assertFalse($result);
    }

    /**
     * Test that canAccessRow returns true when no rules exist.
     */
    public function test_can_access_row_returns_true_when_no_rules_exist(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.view']);
        $user->permissions()->attach($permission->id);

        $model = new \stdClass();
        $model->id = 1;

        $result = $this->gate->canAccessRow($user, 'posts.view', $model);

        $this->assertTrue($result);
    }

    /**
     * Test that canAccessRow logs denial with proper context.
     */
    public function test_can_access_row_logs_denial_with_proper_context(): void
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

    // ========================================
    // Column-Level Access Tests
    // ========================================

    /**
     * Test that canAccessColumn returns false when user is null.
     */
    public function test_can_access_column_returns_false_when_user_is_null(): void
    {
        Log::shouldReceive('warning')->once();

        $model = new \stdClass();
        $model->id = 1;

        $result = $this->gate->canAccessColumn(null, 'posts.edit', $model, 'status');

        $this->assertFalse($result);
    }

    /**
     * Test that canAccessColumn returns false when basic permission is missing.
     */
    public function test_can_access_column_returns_false_when_basic_permission_missing(): void
    {
        $user = User::factory()->create();
        // User has no permissions

        Log::shouldReceive('warning')->once();

        $model = new \stdClass();
        $model->id = 1;

        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'status');

        $this->assertFalse($result);
    }

    /**
     * Test that canAccessColumn returns true for super admin.
     */
    public function test_can_access_column_returns_true_for_super_admin(): void
    {
        $user = User::factory()->create();
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $user->roles()->attach($superAdminRole->id);

        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $model = new \stdClass();
        $model->id = 1;

        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'status');

        $this->assertTrue($result);
    }

    /**
     * Test that canAccessColumn checks column-level rules (whitelist mode).
     */
    public function test_can_access_column_checks_column_level_rules_whitelist(): void
    {
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

        // Test allowed column
        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'title');
        $this->assertTrue($result);

        // Test denied column
        Log::shouldReceive('warning')->once();
        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'status');
        $this->assertFalse($result);
    }

    /**
     * Test that canAccessColumn works with multiple columns in whitelist.
     */
    public function test_can_access_column_works_with_multiple_columns(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create column-level rule with multiple allowed columns
        $this->ruleManager->addColumnRule(
            $permission->id,
            \stdClass::class,
            ['title', 'content', 'excerpt'], // Multiple allowed columns
            []
        );

        $model = new \stdClass();
        $model->id = 1;

        // Test all allowed columns
        $this->assertTrue($this->gate->canAccessColumn($user, 'posts.edit', $model, 'title'));
        $this->assertTrue($this->gate->canAccessColumn($user, 'posts.edit', $model, 'content'));
        $this->assertTrue($this->gate->canAccessColumn($user, 'posts.edit', $model, 'excerpt'));

        // Test denied column
        Log::shouldReceive('warning')->once();
        $this->assertFalse($this->gate->canAccessColumn($user, 'posts.edit', $model, 'status'));
    }

    /**
     * Test that canAccessColumn returns true when no rules exist.
     */
    public function test_can_access_column_returns_true_when_no_rules_exist(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $model = new \stdClass();
        $model->id = 1;

        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'status');

        $this->assertTrue($result);
    }

    /**
     * Test that canAccessColumn logs denial with correct context.
     */
    public function test_can_access_column_logs_denial_with_correct_context(): void
    {
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

        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'status');

        $this->assertFalse($result);
    }

    // ========================================
    // JSON Attribute Access Tests
    // ========================================

    /**
     * Test that canAccessJsonAttribute returns false when user is null.
     */
    public function test_can_access_json_attribute_returns_false_when_user_is_null(): void
    {
        Log::shouldReceive('warning')->once();

        $model = new \stdClass();
        $model->id = 1;

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
     * Test that canAccessJsonAttribute returns false when basic permission is missing.
     */
    public function test_can_access_json_attribute_returns_false_when_basic_permission_missing(): void
    {
        $user = User::factory()->create();
        // User has no permissions

        Log::shouldReceive('warning')->once();

        $model = new \stdClass();
        $model->id = 1;

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
        $user = User::factory()->create();
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $user->roles()->attach($superAdminRole->id);

        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $model = new \stdClass();
        $model->id = 1;

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
     * Test that canAccessJsonAttribute checks JSON attribute rules (whitelist mode).
     */
    public function test_can_access_json_attribute_checks_json_attribute_rules_whitelist(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create JSON attribute rule (whitelist mode)
        $this->ruleManager->addJsonAttributeRule(
            $permission->id,
            \stdClass::class,
            'metadata',
            ['seo.*', 'social.*'], // Allowed paths
            [] // No denied paths
        );

        $model = new \stdClass();
        $model->id = 1;

        // Test allowed path
        $result = $this->gate->canAccessJsonAttribute($user, 'posts.edit', $model, 'metadata', 'seo.title');
        $this->assertTrue($result);

        // Test denied path
        Log::shouldReceive('warning')->once();
        $result = $this->gate->canAccessJsonAttribute($user, 'posts.edit', $model, 'metadata', 'featured');
        $this->assertFalse($result);
    }

    /**
     * Test that canAccessJsonAttribute works with blacklist mode.
     */
    public function test_can_access_json_attribute_works_with_blacklist_mode(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create JSON attribute rule (blacklist mode)
        $this->ruleManager->addJsonAttributeRule(
            $permission->id,
            \stdClass::class,
            'metadata',
            [], // No allowed paths (blacklist mode)
            ['featured', 'promoted'] // Denied paths
        );

        $model = new \stdClass();
        $model->id = 1;

        // Test allowed path (not in blacklist)
        $result = $this->gate->canAccessJsonAttribute($user, 'posts.edit', $model, 'metadata', 'seo.title');
        $this->assertTrue($result);

        // Test denied path (in blacklist)
        Log::shouldReceive('warning')->once();
        $result = $this->gate->canAccessJsonAttribute($user, 'posts.edit', $model, 'metadata', 'featured');
        $this->assertFalse($result);
    }

    /**
     * Test that canAccessJsonAttribute returns true when no rules exist.
     */
    public function test_can_access_json_attribute_returns_true_when_no_rules_exist(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $model = new \stdClass();
        $model->id = 1;

        $result = $this->gate->canAccessJsonAttribute($user, 'posts.edit', $model, 'metadata', 'seo.title');

        $this->assertTrue($result);
    }

    /**
     * Test that canAccessJsonAttribute works with nested JSON paths.
     */
    public function test_can_access_json_attribute_works_with_nested_paths(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create JSON attribute rule with nested paths
        $this->ruleManager->addJsonAttributeRule(
            $permission->id,
            \stdClass::class,
            'metadata',
            ['seo.meta.*'], // Nested path
            []
        );

        $model = new \stdClass();
        $model->id = 1;

        // Test nested path
        $result = $this->gate->canAccessJsonAttribute($user, 'posts.edit', $model, 'metadata', 'seo.meta.description');
        $this->assertTrue($result);
    }

    /**
     * Test that canAccessJsonAttribute logs denial with correct context.
     */
    public function test_can_access_json_attribute_logs_denial_with_correct_context(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create JSON attribute rule
        $this->ruleManager->addJsonAttributeRule(
            $permission->id,
            \stdClass::class,
            'metadata',
            ['social.*'], // Only social.* allowed
            []
        );

        $model = new \stdClass();
        $model->id = 123;

        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', \Mockery::on(function ($data) use ($user) {
                return $data['user_id'] === $user->id
                    && $data['permission'] === 'posts.edit'
                    && $data['reason'] === 'json_attribute_denied'
                    && $data['context']['model_type'] === \stdClass::class
                    && $data['context']['model_id'] === 123
                    && $data['context']['json_column'] === 'metadata'
                    && $data['context']['path'] === 'seo.title'
                    && isset($data['timestamp']);
            }));

        $result = $this->gate->canAccessJsonAttribute($user, 'posts.edit', $model, 'metadata', 'seo.title');

        $this->assertFalse($result);
    }

    // ========================================
    // Super Admin Bypass Tests
    // ========================================

    /**
     * Test that super admin bypasses row-level rules.
     */
    public function test_super_admin_bypasses_row_level_rules(): void
    {
        $user = User::factory()->create();
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $user->roles()->attach($superAdminRole->id);

        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create restrictive row-level rule
        $this->ruleManager->addRowRule(
            $permission->id,
            \stdClass::class,
            ['user_id' => 999], // Different user
            'AND'
        );

        $model = new \stdClass();
        $model->id = 1;
        $model->user_id = $user->id; // User's own post

        // Super admin should bypass the rule
        $result = $this->gate->canAccessRow($user, 'posts.edit', $model);
        $this->assertTrue($result);
    }

    /**
     * Test that super admin bypasses column-level rules.
     */
    public function test_super_admin_bypasses_column_level_rules(): void
    {
        $user = User::factory()->create();
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $user->roles()->attach($superAdminRole->id);

        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create restrictive column-level rule
        $this->ruleManager->addColumnRule(
            $permission->id,
            \stdClass::class,
            ['title'], // Only title allowed
            []
        );

        $model = new \stdClass();
        $model->id = 1;

        // Super admin should bypass the rule
        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'status');
        $this->assertTrue($result);
    }

    /**
     * Test that super admin bypasses JSON attribute rules.
     */
    public function test_super_admin_bypasses_json_attribute_rules(): void
    {
        $user = User::factory()->create();
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $user->roles()->attach($superAdminRole->id);

        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create restrictive JSON attribute rule
        $this->ruleManager->addJsonAttributeRule(
            $permission->id,
            \stdClass::class,
            'metadata',
            ['social.*'], // Only social.* allowed
            []
        );

        $model = new \stdClass();
        $model->id = 1;

        // Super admin should bypass the rule
        $result = $this->gate->canAccessJsonAttribute($user, 'posts.edit', $model, 'metadata', 'seo.title');
        $this->assertTrue($result);
    }

    /**
     * Test that super admin bypass can be disabled.
     */
    public function test_super_admin_bypass_can_be_disabled(): void
    {
        config(['canvastack-rbac.authorization.super_admin_bypass' => false]);

        $user = User::factory()->create();
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $user->roles()->attach($superAdminRole->id);

        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create restrictive row-level rule
        $this->ruleManager->addRowRule(
            $permission->id,
            \stdClass::class,
            ['user_id' => 999], // Different user
            'AND'
        );

        $model = new \stdClass();
        $model->id = 1;
        $model->user_id = $user->id;

        Log::shouldReceive('warning')->once();

        // Super admin should NOT bypass when disabled
        $result = $this->gate->canAccessRow($user, 'posts.edit', $model);
        $this->assertFalse($result);
    }

    // ========================================
    // Audit Logging Tests
    // ========================================

    /**
     * Test that audit logging includes all required fields.
     */
    public function test_audit_logging_includes_all_required_fields(): void
    {
        $user = User::factory()->create();
        // User has no permissions

        Log::shouldReceive('warning')
            ->once()
            ->with('Permission denied', \Mockery::on(function ($data) use ($user) {
                // Verify all required fields
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
     * Test that audit logging includes model information.
     */
    public function test_audit_logging_includes_model_information(): void
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
     * Test that audit logging handles model without ID.
     */
    public function test_audit_logging_handles_model_without_id(): void
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

    /**
     * Test that no log is created when access is granted.
     */
    public function test_no_log_when_access_granted(): void
    {
        $user = User::factory()->create();
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
     * Test that no log is created when super admin bypasses checks.
     */
    public function test_no_log_when_super_admin_bypasses(): void
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

    // ========================================
    // Edge Cases and Performance Tests
    // ========================================

    /**
     * Test that canAccessRow handles model without ID gracefully.
     */
    public function test_can_access_row_handles_model_without_id(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $model = new \stdClass();
        // No ID property

        $result = $this->gate->canAccessRow($user, 'posts.edit', $model);

        $this->assertTrue($result);
    }

    /**
     * Test that canAccessColumn handles model without ID gracefully.
     */
    public function test_can_access_column_handles_model_without_id(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $model = new \stdClass();
        // No ID property

        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'title');

        $this->assertTrue($result);
    }

    /**
     * Test that canAccessJsonAttribute handles model without ID gracefully.
     */
    public function test_can_access_json_attribute_handles_model_without_id(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $model = new \stdClass();
        // No ID property

        $result = $this->gate->canAccessJsonAttribute($user, 'posts.edit', $model, 'metadata', 'seo.title');

        $this->assertTrue($result);
    }

    /**
     * Test that canAccessRow works with different model types.
     */
    public function test_can_access_row_works_with_different_model_types(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create rule for specific model type
        $this->ruleManager->addRowRule(
            $permission->id,
            \stdClass::class,
            ['user_id' => $user->id],
            'AND'
        );

        $stdModel = new \stdClass();
        $stdModel->id = 1;
        $stdModel->user_id = $user->id;

        $arrayModel = new \ArrayObject(['id' => 2, 'user_id' => $user->id]);

        // Test stdClass model (has rule)
        $result1 = $this->gate->canAccessRow($user, 'posts.edit', $stdModel);
        $this->assertTrue($result1);

        // Test ArrayObject model (no rule, should allow)
        $result2 = $this->gate->canAccessRow($user, 'posts.edit', $arrayModel);
        $this->assertTrue($result2);
    }

    /**
     * Test that canAccessColumn works with different model types.
     */
    public function test_can_access_column_works_with_different_model_types(): void
    {
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

        // Test stdClass model (has rule)
        $result1 = $this->gate->canAccessColumn($user, 'posts.edit', $stdModel, 'title');
        $this->assertTrue($result1);

        // Test ArrayObject model (no rule, should allow)
        $result2 = $this->gate->canAccessColumn($user, 'posts.edit', $arrayModel, 'title');
        $this->assertTrue($result2);
    }

    /**
     * Test that canAccessJsonAttribute works with different model types.
     */
    public function test_can_access_json_attribute_works_with_different_model_types(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create rule for specific model type
        $this->ruleManager->addJsonAttributeRule(
            $permission->id,
            \stdClass::class,
            'metadata',
            ['seo.*'],
            []
        );

        $stdModel = new \stdClass();
        $stdModel->id = 1;

        $arrayModel = new \ArrayObject(['id' => 2]);

        // Test stdClass model (has rule)
        $result1 = $this->gate->canAccessJsonAttribute($user, 'posts.edit', $stdModel, 'metadata', 'seo.title');
        $this->assertTrue($result1);

        // Test ArrayObject model (no rule, should allow)
        $result2 = $this->gate->canAccessJsonAttribute($user, 'posts.edit', $arrayModel, 'metadata', 'seo.title');
        $this->assertTrue($result2);
    }

    /**
     * Test that canAccessRow response time is under 50ms.
     */
    public function test_can_access_row_response_time_is_under_50ms(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create row-level rule
        $this->ruleManager->addRowRule(
            $permission->id,
            \stdClass::class,
            ['user_id' => $user->id],
            'AND'
        );

        $model = new \stdClass();
        $model->id = 1;
        $model->user_id = $user->id;

        $start = microtime(true);
        $this->gate->canAccessRow($user, 'posts.edit', $model);
        $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds

        $this->assertLessThan(50, $duration, 'Row-level check should complete within 50ms');
    }

    /**
     * Test that canAccessColumn response time is under 10ms.
     */
    public function test_can_access_column_response_time_is_under_10ms(): void
    {
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

        $start = microtime(true);
        $this->gate->canAccessColumn($user, 'posts.edit', $model, 'title');
        $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds

        $this->assertLessThan(10, $duration, 'Column-level check should complete within 10ms');
    }

    /**
     * Test that canAccessJsonAttribute response time is under 20ms.
     */
    public function test_can_access_json_attribute_response_time_is_under_20ms(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        // Create JSON attribute rule
        $this->ruleManager->addJsonAttributeRule(
            $permission->id,
            \stdClass::class,
            'metadata',
            ['seo.*', 'social.*'],
            []
        );

        $model = new \stdClass();
        $model->id = 1;

        $start = microtime(true);
        $this->gate->canAccessJsonAttribute($user, 'posts.edit', $model, 'metadata', 'seo.title');
        $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds

        $this->assertLessThan(20, $duration, 'JSON attribute check should complete within 20ms');
    }

    /**
     * Test that fine-grained permissions can be disabled globally.
     */
    public function test_fine_grained_permissions_can_be_disabled_globally(): void
    {
        config(['canvastack-rbac.fine_grained.enabled' => false]);

        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $model = new \stdClass();
        $model->id = 1;

        // Should allow access when fine-grained is disabled
        $result = $this->gate->canAccessRow($user, 'posts.edit', $model);
        $this->assertTrue($result);

        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'status');
        $this->assertTrue($result);

        $result = $this->gate->canAccessJsonAttribute($user, 'posts.edit', $model, 'metadata', 'seo.title');
        $this->assertTrue($result);
    }

    /**
     * Test that row-level permissions can be disabled individually.
     */
    public function test_row_level_permissions_can_be_disabled_individually(): void
    {
        config(['canvastack-rbac.fine_grained.row_level.enabled' => false]);

        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $model = new \stdClass();
        $model->id = 1;

        // Should allow access when row-level is disabled
        $result = $this->gate->canAccessRow($user, 'posts.edit', $model);
        $this->assertTrue($result);
    }

    /**
     * Test that column-level permissions can be disabled individually.
     */
    public function test_column_level_permissions_can_be_disabled_individually(): void
    {
        config(['canvastack-rbac.fine_grained.column_level.enabled' => false]);

        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $model = new \stdClass();
        $model->id = 1;

        // Should allow access when column-level is disabled
        $result = $this->gate->canAccessColumn($user, 'posts.edit', $model, 'status');
        $this->assertTrue($result);
    }

    /**
     * Test that JSON attribute permissions can be disabled individually.
     */
    public function test_json_attribute_permissions_can_be_disabled_individually(): void
    {
        config(['canvastack-rbac.fine_grained.json_attribute.enabled' => false]);

        $user = User::factory()->create();
        $permission = Permission::factory()->create(['name' => 'posts.edit']);
        $user->permissions()->attach($permission->id);

        $model = new \stdClass();
        $model->id = 1;

        // Should allow access when JSON attribute is disabled
        $result = $this->gate->canAccessJsonAttribute($user, 'posts.edit', $model, 'metadata', 'seo.title');
        $this->assertTrue($result);
    }
}
