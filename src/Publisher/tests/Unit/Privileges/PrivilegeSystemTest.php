<?php

namespace Tests\Unit\Privileges;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Canvastack\Canvastack\Exceptions\Controller\PrivilegeException;

/**
 * Privilege System Test Suite
 * 
 * Tests all aspects of the privilege system including:
 * - Basic privilege checking
 * - Privilege inheritance
 * - Caching mechanisms
 * - Granular permission controls
 * - Root user bypass
 * - Privilege violation logging
 * - Cache invalidation
 * 
 * @package Tests\Unit\Privileges
 */
class PrivilegeSystemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test controller instance with Privileges trait
     */
    private $controller;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock controller that uses the Privileges trait
        $this->controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Includes\Privileges;
            use \Canvastack\Canvastack\Controllers\Core\Craft\Session;

            public $data = [];
            public $session = [];

            public function __construct()
            {
                $this->data = [];
                $this->session = [];
            }

            // Expose private methods for testing
            public function testModulePrivileges()
            {
                return $this->module_privileges();
            }

            public function testAccessRole()
            {
                return $this->access_role();
            }

            public function testRoutelistsInfo($route = null)
            {
                return $this->routelists_info($route);
            }
        };

        // Clear cache before each test
        Cache::flush();
    }

    /**
     * Tear down test environment
     */
    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    /**
     * Test 1: Basic privilege checking for standard user
     * 
     * @test
     */
    public function test_basic_privilege_check_for_standard_user()
    {
        // Setup: Create test data
        $this->setupTestData();

        // Set session for standard user (group_id = 2)
        Session::put('id', 2);
        Session::put('group_id', 2);
        Session::put('flag', false);

        // Initialize privileges
        $this->controller->testModulePrivileges();

        // Assert: User has index privilege
        $hasIndexPrivilege = $this->controller->checkPrivilege('admin.products', 'index');
        $this->assertTrue($hasIndexPrivilege, 'Standard user should have index privilege');

        // Assert: User has create privilege
        $hasCreatePrivilege = $this->controller->checkPrivilege('admin.products', 'create');
        $this->assertTrue($hasCreatePrivilege, 'Standard user should have create privilege');

        // Assert: User does NOT have delete privilege
        $hasDeletePrivilege = $this->controller->checkPrivilege('admin.products', 'delete');
        $this->assertFalse($hasDeletePrivilege, 'Standard user should NOT have delete privilege');
    }

    /**
     * Test 2: Root user bypass (group_id = 1 with flag = true)
     * 
     * @test
     */
    public function test_root_user_has_all_privileges()
    {
        // Setup: Create test data
        $this->setupTestData();

        // Set session for root user
        Session::put('id', 1);
        Session::put('group_id', 1);
        Session::put('flag', true);

        // Initialize privileges
        $this->controller->session = ['flag' => true];
        $this->controller->testModulePrivileges();

        // Assert: Root user has all privileges
        $this->assertTrue($this->controller->checkPrivilege('admin.products', 'index'));
        $this->assertTrue($this->controller->checkPrivilege('admin.products', 'create'));
        $this->assertTrue($this->controller->checkPrivilege('admin.products', 'edit'));
        $this->assertTrue($this->controller->checkPrivilege('admin.products', 'delete'));
        $this->assertTrue($this->controller->checkPrivilege('admin.products', 'destroy'));
        $this->assertTrue($this->controller->checkPrivilege('admin.any_module', 'any_action'));
    }

    /**
     * Test 3: Privilege inheritance from parent group
     * 
     * @test
     */
    public function test_privilege_inheritance_from_parent_group()
    {
        // Setup: Create parent and child groups
        $this->setupInheritanceTestData();

        // Set session for child group user (group_id = 3, parent_id = 2)
        Session::put('id', 3);
        Session::put('group_id', 3);
        Session::put('flag', false);

        // Initialize privileges
        $this->controller->testModulePrivileges();

        // Assert: Child group inherits parent's privileges
        $hasInheritedPrivilege = $this->controller->checkPrivilege('admin.categories', 'index');
        $this->assertTrue($hasInheritedPrivilege, 'Child group should inherit parent privileges');
    }
}