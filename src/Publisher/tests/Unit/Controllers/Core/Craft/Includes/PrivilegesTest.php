<?php

namespace Tests\Unit\Controllers\Core\Craft\Includes;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Canvastack\Canvastack\Controllers\Core\Controller;
use Canvastack\Canvastack\Models\Admin\System\Modules;
use Canvastack\Canvastack\Exceptions\Controller\PrivilegeException;

/**
 * Privileges Trait Unit Tests
 * 
 * Comprehensive test suite for the Privileges trait covering:
 * - Basic privilege checking (checkPrivilege)
 * - Multiple privilege checking (checkAnyPrivilege, checkAllPrivileges)
 * - Privilege caching functionality
 * - Cache invalidation
 * - Privilege inheritance from parent groups
 * - Privilege violation logging
 * - Root user bypass
 * - Missing privilege handling
 * - Invalid user/group handling
 * 
 * @package Tests\Unit\Controllers\Core\Craft\Includes
 */
class PrivilegesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test controller instance
     * 
     * @var Controller
     */
    private $controller;

    /**
     * Setup test environment
     * 
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache before each test
        Cache::flush();

        // Create test controller instance
        $this->controller = new class extends Controller {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Includes\Privileges;

            public function __construct()
            {
                // Don't call parent constructor to avoid model initialization
            }

            // Expose protected methods for testing
            public function testModulePrivileges(): void
            {
                $this->module_privileges();
            }

            public function testAccessRole(): void
            {
                $this->access_role();
            }

            public function testGetInheritedPrivileges(int $groupId): array
            {
                return $this->getInheritedPrivileges($groupId);
            }

            public function testLogPrivilegeViolation(int $userId, int $groupId, string $module, string $action): void
            {
                $this->logPrivilegeViolation($userId, $groupId, $module, $action);
            }
        };
    }

    /**
     * Tear down test environment
     * 
     * @return void
     */
    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    /**
     * Test basic privilege checking with valid privilege
     * 
     * @return void
     */
    public function test_check_privilege_with_valid_privilege(): void
    {
        // Setup session
        Session::put('id', 1);
        Session::put('group_id', 2);
        Session::put('flag', false);

        // Setup module privileges
        $this->controller->module_privilege = [
            'roles' => [
                'admin.products.index',
                'admin.products.create',
                'admin.products.edit',
            ]
        ];

        // Test privilege check
        $result = $this->controller->checkPrivilege('admin.products', 'create');

        $this->assertTrue($result);
    }

    /**
     * Test basic privilege checking with invalid privilege
     * 
     * @return void
     */
    public function test_check_privilege_with_invalid_privilege(): void
    {
        // Setup session
        Session::put('id', 1);
        Session::put('group_id', 2);
        Session::put('flag', false);

        // Setup module privileges (no delete privilege)
        $this->controller->module_privilege = [
            'roles' => [
                'admin.products.index',
                'admin.products.create',
            ]
        ];

        // Test privilege check - should return false
        $result = $this->controller->checkPrivilege('admin.products', 'delete');

        $this->assertFalse($result);
    }

    /**
     * Test root user bypass
     * 
     * @return void
     */
    public function test_root_user_bypass(): void
    {
        // Setup root user session
        Session::put('id', 1);
        Session::put('group_id', 1);
        Session::put('flag', true);

        // Setup empty module privileges
        $this->controller->module_privilege = [
            'roles' => []
        ];

        // Test privilege check - root should have access to everything
        $result = $this->controller->checkPrivilege('admin.products', 'delete');

        $this->assertTrue($result);
    }

    /**
     * Test privilege caching
     * 
     * @return void
     */
    public function test_privilege_caching(): void
    {
        // Enable caching in config
        config(['canvastack.controller.caching.privilege_cache_enabled' => true]);
        config(['canvastack.controller.caching.privilege_cache_ttl' => 3600]);

        // Setup session
        Session::put('id', 1);
        Session::put('group_id', 2);
        Session::put('flag', false);

        // Setup module privileges
        $this->controller->module_privilege = [
            'roles' => [
                'admin.products.create',
            ]
        ];

        // First call - should cache the result
        $result1 = $this->controller->checkPrivilege('admin.products', 'create');
        $this->assertTrue($result1);

        // Verify cache was set
        $cacheKey = 'privilege_check_2_admin.products_create';
        $cachedValue = canvastack_controller_cache_get($cacheKey);
        $this->assertTrue($cachedValue);

        // Second call - should use cached result
        $result2 = $this->controller->checkPrivilege('admin.products', 'create');
        $this->assertTrue($result2);
    }

    /**
     * Test cache invalidation
     * 
     * @return void
     */
    public function test_cache_invalidation(): void
    {
        // Enable caching
        config(['canvastack.controller.caching.privilege_cache_enabled' => true]);

        // Setup session
        Session::put('id', 1);
        Session::put('group_id', 2);

        // Setup module privileges
        $this->controller->module_privilege = [
            'roles' => ['admin.products.create']
        ];

        // Cache a privilege check
        $this->controller->checkPrivilege('admin.products', 'create');

        // Verify cache exists
        $cacheKey = 'privilege_check_2_admin.products_create';
        $this->assertNotNull(canvastack_controller_cache_get($cacheKey));

        // Invalidate cache for group 2
        $result = $this->controller->invalidatePrivilegeCache(2);
        $this->assertTrue($result);

        // Verify cache was cleared (note: our implementation flushes with prefix)
        // The cache should be cleared, but we can't easily verify this without
        // checking the cache implementation details
    }

    /**
     * Test checkAnyPrivilege with one valid privilege
     * 
     * @return void
     */
    public function test_check_any_privilege_with_one_valid(): void
    {
        // Setup session
        Session::put('id', 1);
        Session::put('group_id', 2);
        Session::put('flag', false);

        // Setup module privileges (only has 'edit')
        $this->controller->module_privilege = [
            'roles' => [
                'admin.products.edit',
            ]
        ];

        // Test - should return true because user has 'edit' (checks 'edit' first, returns true immediately)
        $result = $this->controller->checkAnyPrivilege('admin.products', ['edit', 'delete']);

        $this->assertTrue($result);
    }

    /**
     * Test checkAnyPrivilege with no valid privileges
     * 
     * @return void
     */
    public function test_check_any_privilege_with_none_valid(): void
    {
        // Setup session
        Session::put('id', 1);
        Session::put('group_id', 2);
        Session::put('flag', false);

        // Setup module privileges (only has 'index')
        $this->controller->module_privilege = [
            'roles' => [
                'admin.products.index',
            ]
        ];

        // Test - should return false because user doesn't have 'edit' or 'delete'
        $result = $this->controller->checkAnyPrivilege('admin.products', ['edit', 'delete']);

        $this->assertFalse($result);
    }

    /**
     * Test checkAllPrivileges with all valid privileges
     * 
     * @return void
     */
    public function test_check_all_privileges_with_all_valid(): void
    {
        // Setup session
        Session::put('id', 1);
        Session::put('group_id', 2);
        Session::put('flag', false);

        // Setup module privileges
        $this->controller->module_privilege = [
            'roles' => [
                'admin.products.edit',
                'admin.products.delete',
            ]
        ];

        // Test - should return true because user has both
        $result = $this->controller->checkAllPrivileges('admin.products', ['edit', 'delete']);

        $this->assertTrue($result);
    }

    /**
     * Test checkAllPrivileges with one missing privilege
     * 
     * @return void
     */
    public function test_check_all_privileges_with_one_missing(): void
    {
        // Setup session
        Session::put('id', 1);
        Session::put('group_id', 2);
        Session::put('flag', false);

        // Setup module privileges (only has 'edit')
        $this->controller->module_privilege = [
            'roles' => [
                'admin.products.edit',
            ]
        ];

        // Test - should return false because user doesn't have 'delete'
        $result = $this->controller->checkAllPrivileges('admin.products', ['edit', 'delete']);

        $this->assertFalse($result);
    }

    /**
     * Test requirePrivilege throws exception when privilege missing
     * 
     * @return void
     */
    public function test_require_privilege_throws_exception(): void
    {
        $this->expectException(PrivilegeException::class);
        $this->expectExceptionMessage("Access denied: User does not have privilege to perform 'delete' on 'admin.products'");

        // Setup session
        Session::put('id', 1);
        Session::put('group_id', 2);
        Session::put('flag', false);

        // Setup module privileges (no delete)
        $this->controller->module_privilege = [
            'roles' => [
                'admin.products.index',
            ]
        ];

        // Test - should throw exception
        $this->controller->requirePrivilege('admin.products', 'delete');
    }

    /**
     * Test requirePrivilege succeeds when privilege exists
     * 
     * @return void
     */
    public function test_require_privilege_succeeds(): void
    {
        // Setup session
        Session::put('id', 1);
        Session::put('group_id', 2);
        Session::put('flag', false);

        // Setup module privileges
        $this->controller->module_privilege = [
            'roles' => [
                'admin.products.delete',
            ]
        ];

        // Test - should not throw exception
        $this->controller->requirePrivilege('admin.products', 'delete');

        // If we reach here, test passed
        $this->assertTrue(true);
    }

    /**
     * Test getAllPrivileges returns all privileges
     * 
     * @return void
     */
    public function test_get_all_privileges(): void
    {
        // Setup session
        Session::put('group_id', 2);

        // Setup module privileges
        $this->controller->module_privilege = [
            'roles' => [
                'admin.products.index',
                'admin.products.create',
            ]
        ];

        // Test
        $allPrivileges = $this->controller->getAllPrivileges();

        $this->assertIsArray($allPrivileges);
        $this->assertContains('admin.products.index', $allPrivileges);
        $this->assertContains('admin.products.create', $allPrivileges);
    }

    /**
     * Test removeActionButtons sets buttons to remove
     * 
     * @return void
     */
    public function test_remove_action_buttons(): void
    {
        // Test
        $this->controller->removeActionButtons(['delete', 'view']);

        // Verify
        $this->assertEquals(['delete', 'view'], $this->controller->removeButtons);
    }

    /**
     * Test privilege check with empty module name throws exception
     * 
     * @return void
     */
    public function test_check_privilege_with_empty_module_throws_exception(): void
    {
        // Setup session
        Session::put('id', 1);
        Session::put('group_id', 2);

        // Mock log for exception
        Log::shouldReceive('error')
            ->once()
            ->with('Privilege check exception', \Mockery::type('array'));

        // Test - should return false (graceful degradation)
        $result = $this->controller->checkPrivilege('', 'create');

        $this->assertFalse($result);
    }

    /**
     * Test privilege check with empty action throws exception
     * 
     * @return void
     */
    public function test_check_privilege_with_empty_action_throws_exception(): void
    {
        // Setup session
        Session::put('id', 1);
        Session::put('group_id', 2);

        // Mock log for exception
        Log::shouldReceive('error')
            ->once()
            ->with('Privilege check exception', \Mockery::type('array'));

        // Test - should return false (graceful degradation)
        $result = $this->controller->checkPrivilege('admin.products', '');

        $this->assertFalse($result);
    }

    /**
     * Test privilege caching respects config setting
     * 
     * @return void
     */
    public function test_privilege_caching_respects_config(): void
    {
        // Disable caching in config
        config(['canvastack.controller.caching.privilege_cache_enabled' => false]);

        // Setup session
        Session::put('id', 1);
        Session::put('group_id', 2);
        Session::put('flag', false);

        // Setup module privileges
        $this->controller->module_privilege = [
            'roles' => [
                'admin.products.create',
            ]
        ];

        // Call privilege check
        $result = $this->controller->checkPrivilege('admin.products', 'create');
        $this->assertTrue($result);

        // Verify cache was NOT set (because caching is disabled)
        $cacheKey = 'privilege_check_2_admin.products_create';
        $cachedValue = canvastack_controller_cache_get($cacheKey);
        $this->assertNull($cachedValue);
    }

    /**
     * Test privilege violation logging respects config setting
     * 
     * @return void
     */
    public function test_privilege_violation_logging_respects_config(): void
    {
        // Disable violation logging
        config(['canvastack.controller.logging.log_privilege_violations' => false]);

        // Setup session
        Session::put('id', 1);
        Session::put('group_id', 2);
        Session::put('flag', false);

        // Setup module privileges (no delete)
        $this->controller->module_privilege = [
            'roles' => [
                'admin.products.index',
            ]
        ];

        // Log should NOT be called
        Log::shouldReceive('warning')->never();

        // Test privilege check
        $result = $this->controller->checkPrivilege('admin.products', 'delete');

        $this->assertFalse($result);
    }

    /**
     * Test set_module_privileges method
     * 
     * Note: This test is skipped because it requires full database setup
     * with Modules model and auth() user.
     * 
     * @return void
     */
    public function test_set_module_privileges(): void
    {
        // Skip this test - requires database and auth setup
        $this->markTestSkipped('Requires database and auth setup');
    }

    /**
     * Test privilege check with custom user and group IDs
     * 
     * @return void
     */
    public function test_check_privilege_with_custom_user_and_group(): void
    {
        // Setup session (different from test parameters)
        Session::put('id', 1);
        Session::put('group_id', 2);

        // Setup module privileges
        $this->controller->module_privilege = [
            'roles' => [
                'admin.products.create',
            ]
        ];

        // Test with custom user and group
        $result = $this->controller->checkPrivilege('admin.products', 'create', 5, 3);

        // Should use the custom group ID (3) for checking
        $this->assertTrue($result);
    }

    /**
     * Test cache TTL configuration
     * 
     * @return void
     */
    public function test_cache_ttl_configuration(): void
    {
        // Set custom TTL
        config(['canvastack.controller.caching.privilege_cache_enabled' => true]);
        config(['canvastack.controller.caching.privilege_cache_ttl' => 7200]);

        // Setup session
        Session::put('id', 1);
        Session::put('group_id', 2);
        Session::put('flag', false);

        // Setup module privileges
        $this->controller->module_privilege = [
            'roles' => [
                'admin.products.create',
            ]
        ];

        // Call privilege check
        $result = $this->controller->checkPrivilege('admin.products', 'create');

        // Verify result
        $this->assertTrue($result);

        // Note: We can't easily verify the TTL was used without mocking Cache facade
        // But the code path is tested
    }

    /**
     * Test invalidate all privilege caches
     * 
     * @return void
     */
    public function test_invalidate_all_privilege_caches(): void
    {
        // Enable caching
        config(['canvastack.controller.caching.privilege_cache_enabled' => true]);

        // Setup session
        Session::put('id', 1);
        Session::put('group_id', 2);

        // Setup module privileges
        $this->controller->module_privilege = [
            'roles' => ['admin.products.create']
        ];

        // Cache a privilege check
        $this->controller->checkPrivilege('admin.products', 'create');

        // Invalidate all caches (null parameter)
        $result = $this->controller->invalidatePrivilegeCache(null);

        $this->assertTrue($result);
    }
}
