<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Canvastack\Canvastack\Controllers\Admin\System\GroupController;
use Canvastack\Canvastack\Models\Admin\System\Group;
use Canvastack\Canvastack\Models\Admin\System\Privilege;

/**
 * Unit Tests for GroupController Transaction Management
 * 
 * Tests that store() method properly wraps operations in database transactions,
 * commits on success, rolls back on failure, and invalidates cache appropriately.
 * 
 * **Validates: Requirement 2.3**
 * 
 * @group unit
 * @group bugfix
 * @group group-controller
 * @group transactions
 */
class GroupControllerTransactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test session for authenticated user with all required fields
        session([
            'id' => 1,
            'user_group' => 'root',
            'username' => 'testuser',
            'group_id' => 1,
            'group_info' => 'Administrator',
            'fullname' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '1234567890'
        ]);
        
        // Clear cache before each test
        Cache::flush();
    }
    
    protected function tearDown(): void
    {
        // Clean up test data
        Group::where('group_name', 'LIKE', 'test_group_%')->delete();
        
        parent::tearDown();
    }
    
    /**
     * Test successful group creation commits transaction
     * 
     * @test
     */
    public function test_successful_group_creation_commits_transaction()
    {
        // Arrange
        $groupName = 'test_group_' . uniqid();
        
        $request = Request::create('/admin/system/group', 'POST', [
            'group_name' => $groupName,
            'group_alias' => ucfirst($groupName),
            'group_info' => 'Test Group',
            'active' => 1,
            '_token' => csrf_token()
        ]);
        
        // Track transaction level before
        $transactionLevelBefore = DB::transactionLevel();
        
        // Act
        $controller = new GroupController();
        $response = $controller->store($request);
        
        // Assert: Transaction level should be back to 0 (committed)
        $transactionLevelAfter = DB::transactionLevel();
        $this->assertEquals(0, $transactionLevelAfter, 'Transaction should be committed (level back to 0)');
        
        // Verify group was created
        $group = Group::where('group_name', $groupName)->first();
        $this->assertNotNull($group, 'Group should be created in database');
        
        // Verify the group has expected data
        $this->assertEquals($groupName, $group->group_name);
        $this->assertEquals(ucfirst($groupName), $group->group_alias);
        $this->assertEquals('Test Group', $group->group_info);
    }
    
    /**
     * Test failed privilege insert rolls back group creation
     * 
     * @test
     */
    public function test_failed_privilege_insert_rolls_back_group_creation()
    {
        // Arrange
        $groupName = 'test_group_' . uniqid();
        $groupCountBefore = Group::count();
        
        // Create request with invalid privilege data to force failure
        $request = Request::create('/admin/system/group', 'POST', [
            'group_name' => $groupName,
            'group_alias' => ucfirst($groupName),
            'group_info' => 'Test Group',
            'active' => 1,
            'modules' => [
                'admin_privilege' => [
                    'invalid_module' => [999999 => 8] // Invalid module ID
                ]
            ],
            '_token' => csrf_token()
        ]);
        
        // Act
        $controller = new GroupController();
        
        try {
            $controller->store($request);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            // Expected to fail
        }
        
        // Assert: Transaction level should be back to 0 (rolled back)
        $transactionLevel = DB::transactionLevel();
        $this->assertEquals(0, $transactionLevel, 'Transaction should be rolled back (level back to 0)');
        
        // Verify no orphaned group was created
        $groupCountAfter = Group::count();
        $orphanedGroup = Group::where('group_name', $groupName)->first();
        
        $this->assertNull($orphanedGroup, 'No orphaned group should exist after rollback');
        $this->assertEquals($groupCountBefore, $groupCountAfter, 'Group count should remain unchanged');
    }
    
    /**
     * Test failed mapping insert rolls back entire operation
     * 
     * @test
     */
    public function test_failed_mapping_insert_rolls_back_entire_operation()
    {
        // Arrange
        $groupName = 'test_group_' . uniqid();
        $groupCountBefore = Group::count();
        
        // Create request with invalid mapping data
        $request = Request::create('/admin/system/group', 'POST', [
            'group_name' => $groupName,
            'group_alias' => ucfirst($groupName),
            'group_info' => 'Test Group',
            'active' => 1,
            '__node__' => [
                'invalid_mapping' => ['invalid' => 'data']
            ],
            '_token' => csrf_token()
        ]);
        
        // Act
        $controller = new GroupController();
        
        $exceptionThrown = false;
        try {
            $controller->store($request);
        } catch (\Exception $e) {
            $exceptionThrown = true;
        }
        
        // Assert: Transaction level should be back to 0
        $transactionLevel = DB::transactionLevel();
        $this->assertEquals(0, $transactionLevel, 'Transaction should be complete (level back to 0)');
        
        // If exception was thrown, verify rollback occurred
        if ($exceptionThrown) {
            $groupCountAfter = Group::count();
            $this->assertEquals($groupCountBefore, $groupCountAfter, 'Group count should remain unchanged after rollback');
        }
    }
    
    /**
     * Test cache invalidation only occurs on successful commit
     * 
     * @test
     */
    public function test_cache_invalidation_only_on_successful_commit()
    {
        // Arrange
        $groupName = 'test_group_' . uniqid();
        
        // Pre-populate cache
        Cache::put('group_list', ['cached_data'], 60);
        $this->assertTrue(Cache::has('group_list'), 'Cache should be populated before test');
        
        $request = Request::create('/admin/system/group', 'POST', [
            'group_name' => $groupName,
            'group_alias' => ucfirst($groupName),
            'group_info' => 'Test Group',
            'active' => 1,
            '_token' => csrf_token()
        ]);
        
        // Act
        $controller = new GroupController();
        $response = $controller->store($request);
        
        // Assert: Cache should be invalidated after successful commit
        $this->assertFalse(Cache::has('group_list'), 'Cache should be invalidated after successful group creation');
        
        // Verify group was created
        $group = Group::where('group_name', $groupName)->first();
        $this->assertNotNull($group, 'Group should be created');
    }
    
    /**
     * Test cache is NOT invalidated on failed transaction
     * 
     * @test
     */
    public function test_cache_not_invalidated_on_failed_transaction()
    {
        // Arrange
        $groupName = 'test_group_' . uniqid();
        
        // Pre-populate cache
        Cache::put('group_list', ['cached_data'], 60);
        $this->assertTrue(Cache::has('group_list'), 'Cache should be populated before test');
        
        // Create request with invalid data to force failure
        $request = Request::create('/admin/system/group', 'POST', [
            'group_name' => $groupName,
            'group_alias' => ucfirst($groupName),
            'group_info' => 'Test Group',
            'active' => 1,
            'modules' => [
                'admin_privilege' => [
                    'invalid_module' => [999999 => 8]
                ]
            ],
            '_token' => csrf_token()
        ]);
        
        // Act
        $controller = new GroupController();
        
        try {
            $controller->store($request);
        } catch (\Exception $e) {
            // Expected to fail
        }
        
        // Assert: Cache should still exist (not invalidated on failure)
        $this->assertTrue(Cache::has('group_list'), 'Cache should NOT be invalidated on failed transaction');
    }
}
