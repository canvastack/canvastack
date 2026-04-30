<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Canvastack\Canvastack\Controllers\Admin\System\GroupController;
use Canvastack\Canvastack\Models\Admin\System\Group;
use Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException;
use Canvastack\Canvastack\Exceptions\Controller\ControllerException;
use Canvastack\Canvastack\Exceptions\Controller\PrivilegeException;

/**
 * Unit Tests for GroupController update() Method
 * 
 * Tests that update() method properly validates input, protects root group,
 * wraps operations in transactions, and invalidates cache appropriately.
 * 
 * **Validates: Requirement 2.7**
 * 
 * @group unit
 * @group bugfix
 * @group group-controller
 * @group update
 */
class GroupControllerUpdateTest extends TestCase
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
     * Test update() with invalid ID throws ControllerValidationException
     * 
     * @test
     */
    public function test_update_with_invalid_id_throws_validation_exception()
    {
        // Arrange
        $request = Request::create('/admin/system/group/-1', 'PUT', [
            'group_name' => 'test_group',
            'group_alias' => 'Test Group',
            'group_info' => 'Test',
            '_token' => csrf_token()
        ]);
        
        // Act & Assert
        $this->expectException(ControllerValidationException::class);
        $this->expectExceptionMessage('Invalid group ID');
        
        $controller = new GroupController();
        $controller->update($request, -1);
    }
    
    /**
     * Test update() with zero ID throws ControllerValidationException
     * 
     * @test
     */
    public function test_update_with_zero_id_throws_validation_exception()
    {
        // Arrange
        $request = Request::create('/admin/system/group/0', 'PUT', [
            'group_name' => 'test_group',
            '_token' => csrf_token()
        ]);
        
        // Act & Assert
        $this->expectException(ControllerValidationException::class);
        
        $controller = new GroupController();
        $controller->update($request, 0);
    }
    
    /**
     * Test update() with non-numeric ID throws ControllerValidationException
     * 
     * @test
     */
    public function test_update_with_non_numeric_id_throws_validation_exception()
    {
        // Arrange
        $request = Request::create('/admin/system/group/abc', 'PUT', [
            'group_name' => 'test_group',
            '_token' => csrf_token()
        ]);
        
        // Act & Assert
        $this->expectException(ControllerValidationException::class);
        
        $controller = new GroupController();
        $controller->update($request, 'abc');
    }
    
    /**
     * Test update() with non-existent group throws ControllerException
     * 
     * @test
     */
    public function test_update_with_non_existent_group_throws_exception()
    {
        // Arrange
        $nonExistentId = 999999;
        
        $request = Request::create('/admin/system/group/' . $nonExistentId, 'PUT', [
            'group_name' => 'test_group',
            'group_alias' => 'Test Group',
            'group_info' => 'Test',
            '_token' => csrf_token()
        ]);
        
        // Act & Assert
        $this->expectException(ControllerException::class);
        $this->expectExceptionMessage('Group not found');
        
        $controller = new GroupController();
        $controller->update($request, $nonExistentId);
    }
    
    /**
     * Test non-root user cannot modify root group (throws PrivilegeException)
     * 
     * @test
     */
    public function test_non_root_user_cannot_modify_root_group()
    {
        // Arrange: Create root group
        $rootGroup = Group::firstOrCreate(
            ['group_name' => 'root'],
            [
                'group_alias' => 'Root',
                'group_info' => 'Root Group',
                'active' => 1
            ]
        );
        
        // Set session to non-root user
        session(['user_group' => 'editor']);
        
        $request = Request::create('/admin/system/group/' . $rootGroup->id, 'PUT', [
            'group_name' => 'root',
            'group_alias' => 'Root',
            'group_info' => 'Modified Root Group',
            '_token' => csrf_token()
        ]);
        
        // Act & Assert
        $this->expectException(PrivilegeException::class);
        $this->expectExceptionMessage('Non-root users cannot modify root group');
        
        $controller = new GroupController();
        $controller->update($request, $rootGroup->id);
    }
    
    /**
     * Test root user can modify root group
     * 
     * @test
     */
    public function test_root_user_can_modify_root_group()
    {
        // Arrange: Create root group
        $rootGroup = Group::firstOrCreate(
            ['group_name' => 'root'],
            [
                'group_alias' => 'Root',
                'group_info' => 'Root Group',
                'active' => 1
            ]
        );
        
        // Ensure session is root user
        session(['user_group' => 'root']);
        
        $request = Request::create('/admin/system/group/' . $rootGroup->id, 'PUT', [
            'group_name' => 'root',
            'group_alias' => 'Root',
            'group_info' => 'Modified Root Group',
            'active' => 1,
            '_token' => csrf_token()
        ]);
        
        // Act
        $controller = new GroupController();
        $response = $controller->update($request, $rootGroup->id);
        
        // Assert: Should not throw exception
        $this->assertNotNull($response);
        
        // Verify group was updated
        $updatedGroup = Group::find($rootGroup->id);
        $this->assertEquals('Modified Root Group', $updatedGroup->group_info);
    }
    
    /**
     * Test failed update rolls back all changes
     * 
     * @test
     */
    public function test_failed_update_rolls_back_all_changes()
    {
        // Arrange: Create test group
        $group = Group::create([
            'group_name' => 'test_group_' . uniqid(),
            'group_alias' => 'Test Group',
            'group_info' => 'Original Info',
            'active' => 1
        ]);
        
        $originalInfo = $group->group_info;
        
        // Create request with invalid privilege data to force failure
        $request = Request::create('/admin/system/group/' . $group->id, 'PUT', [
            'group_name' => $group->group_name,
            'group_alias' => 'Updated Group',
            'group_info' => 'Updated Info',
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
            $controller->update($request, $group->id);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            // Expected to fail
        }
        
        // Assert: Transaction level should be back to 0 (rolled back)
        $transactionLevel = DB::transactionLevel();
        $this->assertEquals(0, $transactionLevel, 'Transaction should be rolled back');
        
        // Verify group data was NOT updated (rollback occurred)
        $unchangedGroup = Group::find($group->id);
        $this->assertEquals($originalInfo, $unchangedGroup->group_info, 'Group info should remain unchanged after rollback');
    }
    
    /**
     * Test cache invalidation occurs on successful update
     * 
     * @test
     */
    public function test_cache_invalidation_occurs_on_successful_update()
    {
        // Arrange: Create test group
        $group = Group::create([
            'group_name' => 'test_group_' . uniqid(),
            'group_alias' => 'Test Group',
            'group_info' => 'Original Info',
            'active' => 1
        ]);
        
        // Pre-populate cache
        Cache::put('group_list', ['cached_data'], 60);
        $this->assertTrue(Cache::has('group_list'), 'Cache should be populated before test');
        
        $request = Request::create('/admin/system/group/' . $group->id, 'PUT', [
            'group_name' => $group->group_name,
            'group_alias' => 'Updated Group',
            'group_info' => 'Updated Info',
            'active' => 1,
            '_token' => csrf_token()
        ]);
        
        // Act
        $controller = new GroupController();
        $response = $controller->update($request, $group->id);
        
        // Assert: Cache should be invalidated after successful update
        $this->assertFalse(Cache::has('group_list'), 'Cache should be invalidated after successful group update');
        
        // Verify group was updated
        $updatedGroup = Group::find($group->id);
        $this->assertEquals('Updated Info', $updatedGroup->group_info);
    }
    
    /**
     * Test successful update commits transaction
     * 
     * @test
     */
    public function test_successful_update_commits_transaction()
    {
        // Arrange: Create test group
        $group = Group::create([
            'group_name' => 'test_group_' . uniqid(),
            'group_alias' => 'Test Group',
            'group_info' => 'Original Info',
            'active' => 1
        ]);
        
        $request = Request::create('/admin/system/group/' . $group->id, 'PUT', [
            'group_name' => $group->group_name,
            'group_alias' => 'Updated Group',
            'group_info' => 'Updated Info',
            'active' => 1,
            '_token' => csrf_token()
        ]);
        
        // Act
        $controller = new GroupController();
        $response = $controller->update($request, $group->id);
        
        // Assert: Transaction level should be back to 0 (committed)
        $transactionLevel = DB::transactionLevel();
        $this->assertEquals(0, $transactionLevel, 'Transaction should be committed');
        
        // Verify group was updated
        $updatedGroup = Group::find($group->id);
        $this->assertEquals('Updated Info', $updatedGroup->group_info);
    }
}
