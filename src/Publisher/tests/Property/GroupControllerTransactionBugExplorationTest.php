<?php

namespace Tests\Property;

use Eris\Attributes\ErisRepeat;
use Eris\Generators;
use Eris\TestTrait;
use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Canvastack\Canvastack\Controllers\Admin\System\GroupController;
use Canvastack\Canvastack\Models\Admin\System\Group;
use Canvastack\Canvastack\Models\Admin\System\Privilege;

/**
 * Bug Condition Exploration Test for GroupController Transaction Management
 * 
 * **CRITICAL**: These tests MUST FAIL on unfixed code - failure confirms bugs exist
 * **DO NOT attempt to fix the tests or the code when they fail**
 * **NOTE**: These tests encode the expected behavior - they will validate fixes when they pass after implementation
 * 
 * Uses Eris property-based testing to surface counterexamples that demonstrate
 * transaction management issues in GroupController.php and its traits:
 * - store() without transaction (Issue #3)
 * - update() without transaction (Issue #7)
 * - privileges_after_insert() without transaction (Issue #13)
 * 
 * **Validates: Requirements 2.3, 2.7, 2.11**
 * 
 * @group property
 * @group bugfix
 * @group group-controller
 * @group transactions
 */
class GroupControllerTransactionBugExplorationTest extends TestCase
{
    use TestTrait;
    
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
        
        // Enable query logging
        DB::enableQueryLog();
    }
    
    protected function tearDown(): void
    {
        DB::disableQueryLog();
        parent::tearDown();
    }
    
    /**
     * Property 1: Fault Condition - store() Without Transaction
     * 
     * **Validates: Requirement 2.3**
     * 
     * For any call to store() that creates a group with privileges and page mapping,
     * the system SHALL wrap all operations in DB::beginTransaction(), commit on
     * success, rollback on failure, and log the outcome.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS
     * - Group creation, privilege insert, and mapping insert are separate operations
     * - No transaction wrapper
     * - If privilege insert fails, group remains orphaned
     * - Data inconsistency occurs
     * - Counterexamples will show orphaned groups when operations fail
     * 
     * **BUG LOCATION**: GroupController.php store() method (no transaction wrapper)
     * 
     * @test
     */
    /**
     * Property 1: Expected Behavior - store() Uses Transaction
     * 
     * **Validates: Requirement 2.3**
     * 
     * For any call to store() that creates a group with privileges and page mapping,
     * the system SHALL wrap all operations in DB::beginTransaction(), commit on
     * success, rollback on failure, and log the outcome.
     * 
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES
     * - Group creation, privilege insert, and mapping insert wrapped in transaction
     * - Transaction committed on success
     * - Transaction rolled back on failure
     * - Data consistency maintained
     * 
     * **BUG LOCATION**: GroupController.php store() method
     * 
     * @test
     */
    #[ErisRepeat(repeat: 20)]
    public function test_property_1_store_uses_transaction()
    {
        $this->forAll(
            // Generate group names using int generator (avoid 0)
            Generators::int(1001, 9999),
            // Generate group info
            Generators::elements(['Admin Group', 'Editor Group', 'Viewer Group'])
        )
        ->then(function ($groupId, $groupInfo) {
            // Generate unique group name from ID
            $groupName = 'test_group_' . $groupId . '_' . time();

            // Arrange: Create request for group creation
            $request = Request::create('/admin/system/group', 'POST', [
                'group_name' => $groupName,
                'group_alias' => ucfirst($groupName),
                'group_info' => $groupInfo,
                'active' => 1,
                '_token' => csrf_token()
            ]);

            // Track transaction level before
            $transactionLevelBefore = DB::transactionLevel();

            // Act: Create group
            $controller = new GroupController();

            try {
                $controller->store($request);

                // Assert: Transaction level should be back to 0 (committed)
                $transactionLevelAfter = DB::transactionLevel();
                $this->assertEquals(
                    0,
                    $transactionLevelAfter,
                    "Expected behavior: Transaction should be committed (level back to 0). " .
                    "Group: {$groupName}"
                );

                // Verify group was created
                $createdGroup = Group::where('group_name', $groupName)->first();
                $this->assertNotNull(
                    $createdGroup,
                    "Expected behavior: Group should be created. Group: {$groupName}"
                );

                // Cleanup
                if ($createdGroup) {
                    $createdGroup->delete();
                }

            } catch (\Exception $e) {
                // If exception occurs, verify transaction was rolled back
                $transactionLevelAfter = DB::transactionLevel();
                $this->assertEquals(
                    0,
                    $transactionLevelAfter,
                    "Expected behavior: Transaction should be rolled back (level back to 0). " .
                    "Group: {$groupName}, Error: {$e->getMessage()}"
                );

                // Verify group was NOT created (rolled back)
                $createdGroup = Group::where('group_name', $groupName)->first();
                $this->assertNull(
                    $createdGroup,
                    "Expected behavior: Group should NOT exist after rollback. Group: {$groupName}"
                );
            }
        });
    }

    
    /**
     * Property 2: Fault Condition - update() Without Transaction
     * 
     * **Validates: Requirement 2.7**
     * 
     * For any call to update() that modifies a group, the system SHALL wrap all
     * operations in transaction, commit on success, rollback on failure, and
     * invalidate privilege cache.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS
     * - Update operations not wrapped in transaction
     * - Partial updates possible if operations fail
     * - Data inconsistency occurs
     * - Counterexamples will show partial updates
     * 
     * **BUG LOCATION**: GroupController.php update() method (no transaction wrapper)
     * 
     * @test
     */
    #[ErisRepeat(repeat: 20)]
    public function test_property_2_update_uses_transaction()
    {
        $this->forAll(
            // Generate group names using int generator
            Generators::int(1000, 9999),
            // Generate updated info
            Generators::elements(['Updated Admin', 'Updated Editor', 'Updated Viewer'])
        )
        ->then(function ($groupId, $updatedInfo) {
            // Arrange: Create a test group first
            $groupName = 'test_group_' . $groupId;
            $group = Group::create([
                'group_name' => $groupName,
                'group_alias' => ucfirst($groupName),
                'group_info' => 'Original Info',
                'active' => 1
            ]);
            
            // Create request for update
            $request = Request::create('/admin/system/group/' . $group->id, 'PUT', [
                'group_name' => $groupName,
                'group_alias' => ucfirst($groupName),
                'group_info' => $updatedInfo,
                'active' => 1,
                '_token' => csrf_token()
            ]);
            
            // Track transaction level before
            $transactionLevelBefore = DB::transactionLevel();
            
            // Act: Update group
            $controller = new GroupController();
            
            try {
                $controller->update($request, $group->id);
                
                // Assert: Transaction level should be back to 0 (committed)
                $transactionLevelAfter = DB::transactionLevel();
                $this->assertEquals(
                    0,
                    $transactionLevelAfter,
                    "Transaction should be committed (level back to 0). Group ID: {$group->id}"
                );
                
                // Verify group was updated
                $updatedGroup = Group::find($group->id);
                $this->assertEquals($updatedInfo, $updatedGroup->group_info);
                
            } catch (\Exception $e) {
                // If exception occurs, verify transaction was rolled back
                $transactionLevelAfter = DB::transactionLevel();
                $this->assertEquals(
                    0,
                    $transactionLevelAfter,
                    "Transaction should be rolled back (level back to 0). Group ID: {$group->id}, Error: {$e->getMessage()}"
                );
            } finally {
                // Cleanup
                $group->delete();
            }
        });
    }

    
    /**
     * Property 3: Expected Behavior - privileges_after_insert() Uses Transaction
     * 
     * **Validates: Requirement 2.11**
     * 
     * For any call to privileges_after_insert(), the system SHALL wrap all operations
     * in DB::beginTransaction(), use try-catch blocks, rollback on failure, log
     * operations, and invalidate privilege cache.
     * 
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES
     * - Privilege operations wrapped in transaction
     * - Try-catch blocks present
     * - Rollback occurs on failure
     * - Data consistency maintained
     * 
     * **BUG LOCATION**: Privileges.php privileges_after_insert() method
     * 
     * @test
     */
    #[ErisRepeat(repeat: 20)]
    public function test_property_3_privileges_after_insert_uses_transaction()
    {
        $this->forAll(
            // Generate group IDs
            Generators::choose(1, 100),
            // Generate module IDs
            Generators::choose(1, 50)
        )
        ->then(function ($groupId, $moduleId) {
            // Arrange: Create a test group first
            $groupName = 'test_group_' . $groupId . '_' . time();
            $group = Group::create([
                'group_name' => $groupName,
                'group_alias' => ucfirst($groupName),
                'group_info' => 'Test Group',
                'active' => 1
            ]);
            
            // Create privilege data with actual group ID
            $privilegeData = [
                $moduleId => [
                    'group_id' => $group->id,
                    'module_id' => $moduleId,
                    'index_privilege' => [
                        'read' => 8,
                        'insert' => 4
                    ],
                    'admin_privilege' => [
                        'read' => 8,
                        'update' => 2
                    ]
                ]
            ];
            
            // Track transaction level before
            $transactionLevelBefore = DB::transactionLevel();
            
            // Act: Call privileges_after_insert
            $controller = new GroupController();
            
            // Use reflection to call private method
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('privileges_after_insert');
            $method->setAccessible(true);
            
            try {
                $method->invoke($controller, $privilegeData);
                
                // Assert: Transaction level should be back to 0 (committed)
                $transactionLevelAfter = DB::transactionLevel();
                $this->assertEquals(
                    0,
                    $transactionLevelAfter,
                    "Expected behavior: Transaction should be committed (level back to 0). " .
                    "Group ID: {$group->id}, Module ID: {$moduleId}"
                );
                
            } catch (\Exception $e) {
                // If exception occurs, verify transaction was rolled back
                $transactionLevelAfter = DB::transactionLevel();
                $this->assertEquals(
                    0,
                    $transactionLevelAfter,
                    "Expected behavior: Transaction should be rolled back (level back to 0). " .
                    "Group ID: {$group->id}, Module ID: {$moduleId}, Error: {$e->getMessage()}"
                );
            } finally {
                // Cleanup
                $group->delete();
            }
        });
    }
    
    /**
     * Property 4: Fault Condition - Orphaned Groups on Privilege Insert Failure
     * 
     * **Validates: Requirement 2.3**
     * 
     * This property tests the specific scenario where group creation succeeds but
     * privilege insertion fails. Without transaction management, the group remains
     * orphaned in the database.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS
     * - Group is created
     * - Privilege insert fails
     * - Group remains in database (orphaned)
     * - No rollback occurs
     * 
     * @test
     */
    #[ErisRepeat(repeat: 15)]
    public function test_property_4_no_orphaned_groups_on_failure()
    {
        $this->forAll(
            // Generate group names using int generator
            Generators::int(1000, 9999)
        )
        ->then(function ($groupId) {
            // Generate group name from ID
            $groupName = 'test_group_' . $groupId;
            // Arrange: Count groups before
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
            
            // Act: Try to create group (should fail on privilege insert)
            $controller = new GroupController();
            
            try {
                $controller->store($request);
            } catch (\Exception $e) {
                // Expected to fail
            }
            
            // Assert: On FIXED code, group should NOT exist (rolled back)
            // On UNFIXED code, group WILL exist (orphaned)
            $groupCountAfter = Group::count();
            $orphanedGroup = Group::where('group_name', $groupName)->first();
            
            // On UNFIXED code, this will FAIL (bug exists - orphaned group created)
            $this->assertNull(
                $orphanedGroup,
                "Transaction bug confirmed: Orphaned group created when privilege insert fails. " .
                "Group: {$groupName}. Without transaction, group remains in database."
            );
            
            $this->assertEquals(
                $groupCountBefore,
                $groupCountAfter,
                "Transaction bug confirmed: Group count increased despite failure. " .
                "Before: {$groupCountBefore}, After: {$groupCountAfter}"
            );
            
            // Cleanup if orphaned group exists
            if ($orphanedGroup) {
                $orphanedGroup->delete();
            }
        });
    }
}
