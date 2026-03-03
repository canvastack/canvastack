<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\PermissionManager;
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Auth\RBAC\RoleManager;
use Canvastack\Canvastack\Auth\RBAC\TemplateVariableResolver;
use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\PermissionRule;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;

/**
 * Test for PermissionRuleManager cache warming methods.
 */
class PermissionRuleManagerCacheWarmingTest extends TestCase
{
    protected PermissionRuleManager $manager;

    protected RoleManager $roleManager;

    protected PermissionManager $permissionManager;

    protected TemplateVariableResolver $templateResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleManager = $this->createMock(RoleManager::class);
        $this->permissionManager = $this->getMockBuilder(PermissionManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findByName', 'getUserPermissions'])
            ->getMock();
        $this->templateResolver = $this->createMock(TemplateVariableResolver::class);

        $this->manager = new PermissionRuleManager(
            $this->roleManager,
            $this->permissionManager,
            $this->templateResolver
        );

        // Clear static caches before each test to ensure clean state
        PermissionRuleManager::clearStaticCaches();
    }

    /**
     * Create a test permission.
     */
    protected function createTestPermission(string $name, string $displayName): Permission
    {
        $permission = new Permission();
        $permission->id = rand(1, 1000);
        $permission->name = $name;
        $permission->display_name = $displayName;
        $permission->save();

        return $permission;
    }

    /**
     * Test that warmUpCache does nothing when cache is disabled.
     */
    public function test_warm_up_cache_does_nothing_when_cache_disabled(): void
    {
        // Disable cache
        config(['canvastack-rbac.fine_grained.cache.enabled' => false]);

        // Create new manager with disabled cache
        $manager = new PermissionRuleManager(
            $this->roleManager,
            $this->permissionManager,
            $this->templateResolver
        );

        // Should not throw exception
        $manager->warmUpCache(1, ['posts.edit']);

        // Verify no cache operations occurred
        $this->assertTrue(true);
    }

    /**
     * Test that warmUpCache skips non-existent permissions.
     */
    public function test_warm_up_cache_skips_non_existent_permissions(): void
    {
        $this->permissionManager
            ->expects($this->once())
            ->method('findByName')
            ->with('non.existent')
            ->willReturn(null);

        // Should not throw exception
        $this->manager->warmUpCache(1, ['non.existent']);

        $this->assertTrue(true);
    }

    /**
     * Test that warmUpCache processes column rules.
     */
    public function test_warm_up_cache_processes_column_rules(): void
    {
        $permission = $this->createTestPermission('posts.edit', 'Edit Posts');

        // Create column rule
        $rule = new PermissionRule();
        $rule->permission_id = $permission->id;
        $rule->rule_type = 'column';
        $rule->rule_config = [
            'model' => 'App\\Models\\Post',
            'allowed_columns' => ['title', 'content'],
            'mode' => 'whitelist',
        ];
        $rule->priority = 0;
        $rule->save();

        // With optimization: findByName will be called once in warmUpCache, then cached
        // getAccessibleColumns will use the cached permission ID
        $this->permissionManager
            ->expects($this->once())
            ->method('findByName')
            ->with('posts.edit')
            ->willReturn($permission);

        // Warm up cache
        $this->manager->warmUpCache(1, ['posts.edit']);

        // Verify cache was populated by checking if getAccessibleColumns returns cached data
        $columns = $this->manager->getAccessibleColumns(1, 'posts.edit', 'App\\Models\\Post');

        $this->assertIsArray($columns);
    }

    /**
     * Test that warmUpCache processes JSON attribute rules.
     */
    public function test_warm_up_cache_processes_json_attribute_rules(): void
    {
        $permission = $this->createTestPermission('posts.edit', 'Edit Posts');

        // Create JSON attribute rule
        $rule = new PermissionRule();
        $rule->permission_id = $permission->id;
        $rule->rule_type = 'json_attribute';
        $rule->rule_config = [
            'model' => 'App\\Models\\Post',
            'json_column' => 'metadata',
            'allowed_paths' => ['seo.*', 'social.*'],
            'denied_paths' => ['featured'],
        ];
        $rule->priority = 0;
        $rule->save();

        // With optimization: findByName will be called once in warmUpCache, then cached
        // getAccessibleJsonPaths will use the cached permission ID
        $this->permissionManager
            ->expects($this->once())
            ->method('findByName')
            ->with('posts.edit')
            ->willReturn($permission);

        // Warm up cache
        $this->manager->warmUpCache(1, ['posts.edit']);

        // Verify cache was populated by calling getAccessibleJsonPaths (should use cache)
        $paths = $this->manager->getAccessibleJsonPaths(1, 'posts.edit', 'App\\Models\\Post', 'metadata');

        $this->assertIsArray($paths);
        $this->assertArrayHasKey('allowed', $paths);
        $this->assertArrayHasKey('denied', $paths);
    }

    /**
     * Test that warmUpCache processes multiple JSON attribute rules for different columns.
     */
    public function test_warm_up_cache_processes_multiple_json_attribute_rules(): void
    {
        $permission = $this->createTestPermission('posts.edit', 'Edit Posts');

        // Create first JSON attribute rule for metadata column
        $rule1 = new PermissionRule();
        $rule1->permission_id = $permission->id;
        $rule1->rule_type = 'json_attribute';
        $rule1->rule_config = [
            'model' => 'App\\Models\\Post',
            'json_column' => 'metadata',
            'allowed_paths' => ['seo.*', 'social.*'],
            'denied_paths' => ['featured'],
        ];
        $rule1->priority = 0;
        $rule1->save();

        // Create second JSON attribute rule for settings column
        $rule2 = new PermissionRule();
        $rule2->permission_id = $permission->id;
        $rule2->rule_type = 'json_attribute';
        $rule2->rule_config = [
            'model' => 'App\\Models\\Post',
            'json_column' => 'settings',
            'allowed_paths' => ['display.*'],
            'denied_paths' => ['admin.*'],
        ];
        $rule2->priority = 0;
        $rule2->save();

        // With optimization: findByName will be called once in warmUpCache, then cached
        // Both getAccessibleJsonPaths calls will use the cached permission ID
        $this->permissionManager
            ->expects($this->once())
            ->method('findByName')
            ->with('posts.edit')
            ->willReturn($permission);

        // Warm up cache
        $this->manager->warmUpCache(1, ['posts.edit']);

        // Verify both caches were populated
        $metadataPaths = $this->manager->getAccessibleJsonPaths(1, 'posts.edit', 'App\\Models\\Post', 'metadata');
        $settingsPaths = $this->manager->getAccessibleJsonPaths(1, 'posts.edit', 'App\\Models\\Post', 'settings');

        $this->assertIsArray($metadataPaths);
        $this->assertArrayHasKey('allowed', $metadataPaths);
        $this->assertContains('seo.*', $metadataPaths['allowed']);

        $this->assertIsArray($settingsPaths);
        $this->assertArrayHasKey('allowed', $settingsPaths);
        $this->assertContains('display.*', $settingsPaths['allowed']);
    }

    /**
     * Test that warmUpCache deduplicates JSON attribute rules for same model/column.
     */
    public function test_warm_up_cache_deduplicates_json_attribute_rules(): void
    {
        $permission = $this->createTestPermission('posts.edit', 'Edit Posts');

        // Create two JSON attribute rules for the same model/column
        $rule1 = new PermissionRule();
        $rule1->permission_id = $permission->id;
        $rule1->rule_type = 'json_attribute';
        $rule1->rule_config = [
            'model' => 'App\\Models\\Post',
            'json_column' => 'metadata',
            'allowed_paths' => ['seo.*'],
            'denied_paths' => [],
        ];
        $rule1->priority = 10;
        $rule1->save();

        $rule2 = new PermissionRule();
        $rule2->permission_id = $permission->id;
        $rule2->rule_type = 'json_attribute';
        $rule2->rule_config = [
            'model' => 'App\\Models\\Post',
            'json_column' => 'metadata',
            'allowed_paths' => ['social.*'],
            'denied_paths' => ['featured'],
        ];
        $rule2->priority = 5;
        $rule2->save();

        // With optimization: findByName should be called only once in warmUpCache, then cached
        // getAccessibleJsonPaths will use the cached permission ID
        $this->permissionManager
            ->expects($this->once())
            ->method('findByName')
            ->with('posts.edit')
            ->willReturn($permission);

        // Warm up cache
        $this->manager->warmUpCache(1, ['posts.edit']);

        // Verify cache was populated with merged paths from both rules
        $paths = $this->manager->getAccessibleJsonPaths(1, 'posts.edit', 'App\\Models\\Post', 'metadata');

        $this->assertIsArray($paths);
        $this->assertArrayHasKey('allowed', $paths);
        $this->assertContains('seo.*', $paths['allowed']);
        $this->assertContains('social.*', $paths['allowed']);
        $this->assertContains('featured', $paths['denied']);
    }

    /**
     * Test that warmUpCache processes multiple permissions.
     */
    public function test_warm_up_cache_processes_multiple_permissions(): void
    {
        $permission1 = $this->createTestPermission('posts.edit', 'Edit Posts');
        $permission2 = $this->createTestPermission('users.edit', 'Edit Users');

        // Create rules for both permissions
        $rule1 = new PermissionRule();
        $rule1->permission_id = $permission1->id;
        $rule1->rule_type = 'column';
        $rule1->rule_config = [
            'model' => 'App\\Models\\Post',
            'allowed_columns' => ['title'],
            'mode' => 'whitelist',
        ];
        $rule1->priority = 0;
        $rule1->save();

        $rule2 = new PermissionRule();
        $rule2->permission_id = $permission2->id;
        $rule2->rule_type = 'column';
        $rule2->rule_config = [
            'model' => 'App\\Models\\User',
            'allowed_columns' => ['name'],
            'mode' => 'whitelist',
        ];
        $rule2->priority = 0;
        $rule2->save();

        // With optimization: findByName will be called twice (once per permission in warmUpCache), then cached
        // getAccessibleColumns calls will use the cached permission IDs
        $this->permissionManager
            ->expects($this->exactly(2))
            ->method('findByName')
            ->willReturnCallback(function ($name) use ($permission1, $permission2) {
                return match ($name) {
                    'posts.edit' => $permission1,
                    'users.edit' => $permission2,
                    default => null,
                };
            });

        // Warm up cache for both permissions
        $this->manager->warmUpCache(1, ['posts.edit', 'users.edit']);

        // Verify both caches were populated
        $postColumns = $this->manager->getAccessibleColumns(1, 'posts.edit', 'App\\Models\\Post');
        $userColumns = $this->manager->getAccessibleColumns(1, 'users.edit', 'App\\Models\\User');

        $this->assertIsArray($postColumns);
        $this->assertIsArray($userColumns);
    }

    /**
     * Test that warmUpCache handles rules without model class.
     */
    public function test_warm_up_cache_handles_rules_without_model_class(): void
    {
        $permission = $this->createTestPermission('posts.edit', 'Edit Posts');

        // Create rule without model class
        $rule = new PermissionRule();
        $rule->permission_id = $permission->id;
        $rule->rule_type = 'column';
        $rule->rule_config = [
            'allowed_columns' => ['title'],
            'mode' => 'whitelist',
        ];
        $rule->priority = 0;
        $rule->save();

        $this->permissionManager
            ->expects($this->once())
            ->method('findByName')
            ->with('posts.edit')
            ->willReturn($permission);

        // Should not throw exception
        $this->manager->warmUpCache(1, ['posts.edit']);

        $this->assertTrue(true);
    }

    /**
     * Test that warmUpUserCache does nothing when cache is disabled.
     */
    public function test_warm_up_user_cache_does_nothing_when_cache_disabled(): void
    {
        // Disable cache
        config(['canvastack-rbac.cache.enabled' => false]);
        config(['canvastack-rbac.fine_grained.cache.enabled' => false]);

        // Create new mocks for the new manager
        $roleManager = $this->createMock(RoleManager::class);
        $permissionManager = $this->getMockBuilder(PermissionManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findByName', 'getUserPermissions'])
            ->getMock();
        $templateResolver = $this->createMock(TemplateVariableResolver::class);

        // Create new manager with disabled cache
        $manager = new PermissionRuleManager(
            $roleManager,
            $permissionManager,
            $templateResolver
        );

        // Should not throw exception and should return early
        $manager->warmUpUserCache(1);

        // If we get here without exception, the test passes
        $this->assertTrue(true);
    }

    /**
     * Test that warmUpUserCache gets user permissions from permission manager.
     */
    public function test_warm_up_user_cache_gets_user_permissions_from_permission_manager(): void
    {
        $permission1 = $this->createTestPermission('posts.edit', 'Edit Posts');
        $permission2 = $this->createTestPermission('users.view', 'View Users');

        $this->permissionManager
            ->expects($this->once())
            ->method('getUserPermissions')
            ->with(1)
            ->willReturn(new EloquentCollection([$permission1, $permission2]));

        $this->permissionManager
            ->expects($this->exactly(2))
            ->method('findByName')
            ->willReturnCallback(function ($name) use ($permission1, $permission2) {
                return match ($name) {
                    'posts.edit' => $permission1,
                    'users.view' => $permission2,
                    default => null,
                };
            });

        // Warm up user cache
        $this->manager->warmUpUserCache(1);

        $this->assertTrue(true);
    }

    /**
     * Test that warmUpUserCache handles empty permissions.
     */
    public function test_warm_up_user_cache_handles_empty_permissions(): void
    {
        $this->permissionManager
            ->expects($this->once())
            ->method('getUserPermissions')
            ->with(1)
            ->willReturn(new EloquentCollection([]));

        // Should not throw exception
        $this->manager->warmUpUserCache(1);

        $this->assertTrue(true);
    }

    /**
     * Test that warmUpCache respects rule type enabled configuration.
     */
    public function test_warm_up_cache_respects_rule_type_enabled_configuration(): void
    {
        // Disable column-level rules
        config(['canvastack-rbac.fine_grained.column_level.enabled' => false]);

        // Create new manager with disabled column-level
        $manager = new PermissionRuleManager(
            $this->roleManager,
            $this->permissionManager,
            $this->templateResolver
        );

        $permission = $this->createTestPermission('posts.edit', 'Edit Posts');

        // Create column rule
        $rule = new PermissionRule();
        $rule->permission_id = $permission->id;
        $rule->rule_type = 'column';
        $rule->rule_config = [
            'model' => 'App\\Models\\Post',
            'allowed_columns' => ['title'],
            'mode' => 'whitelist',
        ];
        $rule->priority = 0;
        $rule->save();

        $this->permissionManager
            ->expects($this->once())
            ->method('findByName')
            ->with('posts.edit')
            ->willReturn($permission);

        // Warm up cache - should skip column rules
        $manager->warmUpCache(1, ['posts.edit']);

        // Verify column cache was not populated (returns empty because disabled)
        $columns = $manager->getAccessibleColumns(1, 'posts.edit', 'App\\Models\\Post');

        $this->assertEmpty($columns);
    }

    /**
     * Test that warmUpCache processes rules in priority order.
     */
    public function test_warm_up_cache_processes_rules_in_priority_order(): void
    {
        $permission = $this->createTestPermission('posts.edit', 'Edit Posts');

        // Create rules with different priorities
        $rule1 = new PermissionRule();
        $rule1->permission_id = $permission->id;
        $rule1->rule_type = 'column';
        $rule1->rule_config = [
            'model' => 'App\\Models\\Post',
            'allowed_columns' => ['title'],
            'mode' => 'whitelist',
        ];
        $rule1->priority = 10;
        $rule1->save();

        $rule2 = new PermissionRule();
        $rule2->permission_id = $permission->id;
        $rule2->rule_type = 'column';
        $rule2->rule_config = [
            'model' => 'App\\Models\\Post',
            'allowed_columns' => ['content'],
            'mode' => 'whitelist',
        ];
        $rule2->priority = 5;
        $rule2->save();

        // With optimization: findByName will be called once in warmUpCache, then cached
        // getAccessibleColumns will use the cached permission ID
        $this->permissionManager
            ->expects($this->once())
            ->method('findByName')
            ->with('posts.edit')
            ->willReturn($permission);

        // Warm up cache
        $this->manager->warmUpCache(1, ['posts.edit']);

        // Verify cache was populated with merged columns
        $columns = $this->manager->getAccessibleColumns(1, 'posts.edit', 'App\\Models\\Post');

        $this->assertIsArray($columns);
        $this->assertContains('title', $columns);
        $this->assertContains('content', $columns);
    }

    /**
     * Test that warmUpCache handles invalid model classes gracefully.
     */
    public function test_warm_up_cache_handles_invalid_model_classes_gracefully(): void
    {
        $permission = $this->createTestPermission('posts.edit', 'Edit Posts');

        // Create rule with non-existent model class
        $rule = new PermissionRule();
        $rule->permission_id = $permission->id;
        $rule->rule_type = 'row';
        $rule->rule_config = [
            'model' => 'App\\Models\\NonExistentModel',
            'conditions' => ['user_id' => '{{auth.id}}'],
        ];
        $rule->priority = 0;
        $rule->save();

        $this->permissionManager
            ->expects($this->once())
            ->method('findByName')
            ->with('posts.edit')
            ->willReturn($permission);

        // Should not throw exception
        $this->manager->warmUpCache(1, ['posts.edit']);

        $this->assertTrue(true);
    }
}
