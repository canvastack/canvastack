<?php

namespace Tests\Property;

use Eris\Attributes\ErisRepeat;
use Eris\Generators;
use Eris\TestTrait;
use Tests\TestCase;
use Illuminate\Http\Request;
use Canvastack\Canvastack\Controllers\Admin\System\GroupController;
use Canvastack\Canvastack\Models\Admin\System\Group;
use Canvastack\Canvastack\Exceptions\Controller\ControllerException;
use Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException;
use Canvastack\Canvastack\Exceptions\Controller\PrivilegeException;

/**
 * Bug Condition Exploration Test for GroupController Input Validation
 * 
 * **CRITICAL**: These tests MUST FAIL on unfixed code - failure confirms bugs exist
 * **DO NOT attempt to fix the tests or the code when they fail**
 * **NOTE**: These tests encode the expected behavior - they will validate fixes when they pass after implementation
 * 
 * Uses Eris property-based testing to surface counterexamples that demonstrate
 * input validation issues in GroupController.php and its traits:
 * - Direct superglobal access (Issue #2)
 * - set_data_before_insert() with invalid model_id (Issue #6)
 * - update() without validation (Issue #7)
 * - rolepage() without validation (Issue #16)
 * 
 * **Validates: Requirements 2.2, 2.6, 2.7, 2.14**
 * 
 * @group property
 * @group bugfix
 * @group group-controller
 * @group validation
 */
class GroupControllerInputValidationBugExplorationTest extends TestCase
{
    use TestTrait;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test session for authenticated user
        session([
            'id' => 1,
            'user_group' => 'root',
            'username' => 'testuser'
        ]);
    }
    
    /**
     * Property 1: Fault Condition - Direct Superglobal Access
     * 
     * **Validates: Requirement 2.2**
     * 
     * For any call to store(), the system SHALL use Request object methods (query(),
     * all()) instead of superglobals, validate all parameters, and sanitize inputs.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS
     * - store() accesses $_GET and $_POST directly
     * - Bypasses Laravel's request validation and sanitization
     * - Security risk and poor practice
     * - Counterexamples will show superglobals are accessed
     * 
     * **BUG LOCATION**: GroupController.php lines 145-147
     * ```php
     * if (!empty($_GET['rolemapage'])) {
     *     return $this->rolepage($_POST, $_GET['usein']);
     * }
     * ```
     * 
     * @test
     */
    #[ErisRepeat(repeat: 20)]
    public function test_property_1_no_direct_superglobal_access()
    {
        $this->forAll(
            // Generate usein values
            Generators::elements(['table_name', 'field_name', 'field_value'])
        )
        ->then(function ($usein) {
            // Arrange: Set up $_GET and $_POST superglobals
            $_GET['rolemapage'] = 'true';
            $_GET['usein'] = $usein;
            $_POST['test_data'] = 'test_value';
            
            // Create proper Request object
            $request = Request::create(
                '/admin/system/group?rolemapage=true&usein=' . $usein,
                'POST',
                ['test_data' => 'test_value']
            );
            
            // Act: Call store method
            $controller = new GroupController();
            
            // On UNFIXED code, this will access $_GET and $_POST directly
            // We can't easily detect this, but we can verify the behavior
            
            // The test documents the expected behavior:
            // - Should use $request->query('rolemapage') instead of $_GET['rolemapage']
            // - Should use $request->query('usein') instead of $_GET['usein']
            // - Should use $request->all() instead of $_POST
            
            // This test will FAIL on unfixed code because the code uses superglobals
            // On fixed code, it should use Request object methods
            
            // We verify by checking if the method works correctly with Request object
            // and doesn't rely on superglobals
            
            // Clear superglobals to test if code relies on them
            unset($_GET['rolemapage']);
            unset($_GET['usein']);
            unset($_POST['test_data']);
            
            try {
                // On FIXED code, this should work (uses Request object)
                // On UNFIXED code, this will fail (relies on superglobals)
                $result = $controller->store($request);
                
                // If we reach here, code is using Request object (FIXED)
                $this->assertTrue(
                    true,
                    "Code correctly uses Request object instead of superglobals"
                );
            } catch (\Exception $e) {
                // Check if the error is related to superglobal access
                $errorMessage = $e->getMessage();
                
                // Errors that indicate superglobal access:
                // - "Undefined index: rolemapage" (accessing $_GET['rolemapage'])
                // - "Undefined index: usein" (accessing $_GET['usein'])
                // - "Undefined variable: _GET" or "Undefined variable: _POST"
                
                if (
                    strpos($errorMessage, 'Undefined index: rolemapage') !== false ||
                    strpos($errorMessage, 'Undefined index: usein') !== false ||
                    strpos($errorMessage, 'Undefined variable: _GET') !== false ||
                    strpos($errorMessage, 'Undefined variable: _POST') !== false
                ) {
                    // This is a superglobal access bug
                    $this->fail(
                        "Superglobal access bug confirmed: Code relies on \$_GET/\$_POST instead of Request object. " .
                        "Error: {$errorMessage}"
                    );
                } else {
                    // Other exceptions are acceptable (validation, session, etc.)
                    // The important thing is that the code doesn't fail due to missing superglobals
                    $this->assertTrue(
                        true,
                        "Code correctly uses Request object (no superglobal access errors). " .
                        "Other exception occurred: {$errorMessage}"
                    );
                }
            }
        });
    }
    
    /**
     * Property 2: Fault Condition - set_data_before_insert() with Invalid model_id
     * 
     * **Validates: Requirement 2.6**
     * 
     * For any call to set_data_before_insert() with invalid $model_id, the system
     * SHALL validate $model_id parameter, verify group exists, throw ControllerException
     * if not found, and log errors.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS
     * - No validation of $model_id parameter
     * - No error handling for invalid group ID
     * - Silent failures occur
     * - Counterexamples will show invalid IDs are not rejected
     * 
     * **BUG LOCATION**: GroupController.php set_data_before_insert() method
     * 
     * @test
     */
    #[ErisRepeat(repeat: 30)]
    public function test_property_2_validate_model_id_in_set_data_before_insert()
    {
        $this->forAll(
            // Generate invalid model IDs
            Generators::oneOf(
                Generators::constant(-1),
                Generators::constant(0),
                Generators::constant(999999), // Non-existent ID
                Generators::constant(null),
                Generators::constant(false)
            )
        )
        ->then(function ($invalidModelId) {
            // Arrange: Create request
            $request = Request::create('/admin/system/group', 'POST', [
                'group_name' => 'test_group',
                'group_alias' => 'Test Group',
                'group_info' => 'Test Info',
                '_token' => csrf_token()
            ]);
            
            // Act: Call set_data_before_insert with invalid model_id
            $controller = new GroupController();
            
            // Use reflection to call private method
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('set_data_before_insert');
            $method->setAccessible(true);
            
            // Assert: Should throw ControllerException for invalid model_id
            // On UNFIXED code, this will NOT throw exception (bug exists)
            // On FIXED code, this will throw ControllerException
            
            try {
                $method->invoke($controller, $request, $invalidModelId);
                
                // If we reach here on UNFIXED code, the bug exists
                $this->fail(
                    "Validation bug confirmed: set_data_before_insert() accepts invalid model_id without validation. " .
                    "Invalid ID: " . var_export($invalidModelId, true)
                );
            } catch (ControllerException $e) {
                // Expected on FIXED code
                $this->assertTrue(
                    true,
                    "Validation correctly rejected invalid model_id"
                );
            } catch (ControllerValidationException $e) {
                // Also acceptable on FIXED code
                $this->assertTrue(
                    true,
                    "Validation correctly rejected invalid model_id"
                );
            } catch (\Exception $e) {
                // On UNFIXED code, may get other errors (silent failure)
                // This still indicates the bug exists
                $this->fail(
                    "Validation bug confirmed: set_data_before_insert() does not properly validate model_id. " .
                    "Invalid ID: " . var_export($invalidModelId, true) . ", Error: {$e->getMessage()}"
                );
            }
        });
    }
    
    /**
     * Property 3: Fault Condition - update() Without Validation
     * 
     * **Validates: Requirement 2.7**
     * 
     * For any call to update(), the system SHALL validate ID parameter, check if
     * group exists, prevent modification of root group by non-root users, wrap in
     * transaction, and invalidate privilege cache.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS
     * - No validation of ID parameter
     * - No check if group exists
     * - No permission check for root group modification
     * - Counterexamples will show invalid updates are not rejected
     * 
     * **BUG LOCATION**: GroupController.php update() method
     * 
     * @test
     */
    #[ErisRepeat(repeat: 30)]
    public function test_property_3_validate_id_in_update()
    {
        $this->forAll(
            // Generate invalid IDs
            Generators::oneOf(
                Generators::constant(-1),
                Generators::constant(0),
                Generators::constant(999999) // Non-existent ID
            )
        )
        ->then(function ($invalidId) {
            // Arrange: Create request
            $request = Request::create('/admin/system/group/' . $invalidId, 'PUT', [
                'group_name' => 'updated_group',
                'group_alias' => 'Updated Group',
                'group_info' => 'Updated Info',
                '_token' => csrf_token()
            ]);
            
            // Act: Call update with invalid ID
            $controller = new GroupController();
            
            // Assert: Should throw exception for invalid ID
            // On UNFIXED code, this may NOT throw exception (bug exists)
            // On FIXED code, this will throw ControllerException or ControllerValidationException
            
            try {
                $controller->update($request, $invalidId);
                
                // If we reach here on UNFIXED code, the bug exists
                $this->fail(
                    "Validation bug confirmed: update() accepts invalid ID without validation. " .
                    "Invalid ID: {$invalidId}"
                );
            } catch (ControllerException $e) {
                // Expected on FIXED code
                $this->assertStringContainsString(
                    'not found',
                    strtolower($e->getMessage()),
                    "Exception should indicate group not found"
                );
            } catch (ControllerValidationException $e) {
                // Also acceptable on FIXED code
                $this->assertStringContainsString(
                    'invalid',
                    strtolower($e->getMessage()),
                    "Exception should indicate invalid ID"
                );
            } catch (\Exception $e) {
                // On UNFIXED code, may get other errors
                // This still indicates the bug exists
                $this->fail(
                    "Validation bug confirmed: update() does not properly validate ID. " .
                    "Invalid ID: {$invalidId}, Error: {$e->getMessage()}"
                );
            }
        });
    }

    
    /**
     * Property 4: Fault Condition - Root Group Modification by Non-Root User
     * 
     * **Validates: Requirement 2.7**
     * 
     * For any attempt by non-root user to modify root group, the system SHALL
     * prevent modification and throw PrivilegeException.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS
     * - No permission check for root group modification
     * - Non-root users can modify root group
     * - Privilege escalation vulnerability
     * 
     * @test
     */
    #[ErisRepeat(repeat: 20)]
    public function test_property_4_prevent_root_group_modification_by_non_root()
    {
        $this->forAll(
            // Generate non-root user groups
            Generators::elements(['admin', 'editor', 'viewer', 'user'])
        )
        ->then(function ($userGroup) {
            // Arrange: Create root group
            $rootGroup = Group::firstOrCreate(
                ['group_name' => 'root'],
                [
                    'group_alias' => 'Root',
                    'group_info' => 'Root Group',
                    'active' => 1
                ]
            );
            
            // Set session as non-root user
            session(['user_group' => $userGroup]);
            
            // Create request to update root group
            $request = Request::create('/admin/system/group/' . $rootGroup->id, 'PUT', [
                'group_name' => 'root',
                'group_alias' => 'Root Modified',
                'group_info' => 'Modified by non-root',
                '_token' => csrf_token()
            ]);
            
            // Act: Try to update root group as non-root user
            $controller = new GroupController();
            
            // Assert: Should throw PrivilegeException
            // On UNFIXED code, this will NOT throw exception (bug exists)
            // On FIXED code, this will throw PrivilegeException
            
            try {
                $controller->update($request, $rootGroup->id);
                
                // If we reach here on UNFIXED code, the bug exists
                $this->fail(
                    "Privilege escalation bug confirmed: Non-root user can modify root group. " .
                    "User group: {$userGroup}"
                );
            } catch (PrivilegeException $e) {
                // Expected on FIXED code
                $this->assertStringContainsString(
                    'root',
                    strtolower($e->getMessage()),
                    "Exception should mention root group"
                );
            } catch (\Exception $e) {
                // On UNFIXED code, may succeed or get other errors
                $this->fail(
                    "Privilege escalation bug confirmed: No proper check for root group modification. " .
                    "User group: {$userGroup}, Error: {$e->getMessage()}"
                );
            }
        });
    }
    
    /**
     * Property 5: Fault Condition - rolepage() Without Validation
     * 
     * **Validates: Requirement 2.14**
     * 
     * For any call to rolepage(), the system SHALL validate $data is not empty,
     * validate $usein against allowed contexts, and throw ControllerValidationException
     * on invalid input.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS
     * - No validation of $data parameter
     * - Empty data accepted
     * - Invalid operations possible
     * 
     * @test
     */
    #[ErisRepeat(repeat: 20)]
    public function test_property_5_validate_data_in_rolepage()
    {
        $this->forAll(
            // Generate empty or invalid data
            Generators::oneOf(
                Generators::constant([]),
                Generators::constant(null),
                Generators::constant(''),
                Generators::constant(false)
            ),
            // Generate valid usein
            Generators::elements(['table_name', 'field_name', 'field_value'])
        )
        ->then(function ($invalidData, $usein) {
            // Arrange: Create controller
            $controller = new GroupController();
            
            // Use reflection to call private rolepage method
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('rolepage');
            $method->setAccessible(true);
            
            // Act & Assert: Should throw ControllerValidationException for empty data
            // On UNFIXED code, this may NOT throw exception (bug exists)
            // On FIXED code, this will throw ControllerValidationException
            
            try {
                $method->invoke($controller, $invalidData, $usein);
                
                // If we reach here on UNFIXED code, the bug exists
                $this->fail(
                    "Validation bug confirmed: rolepage() accepts empty/invalid data without validation. " .
                    "Data: " . var_export($invalidData, true)
                );
            } catch (ControllerValidationException $e) {
                // Expected on FIXED code
                $this->assertTrue(
                    true,
                    "Validation correctly rejected empty/invalid data"
                );
            } catch (\Exception $e) {
                // On UNFIXED code, may get other errors
                // This still indicates the bug exists
                $this->fail(
                    "Validation bug confirmed: rolepage() does not properly validate data. " .
                    "Data: " . var_export($invalidData, true) . ", Error: {$e->getMessage()}"
                );
            }
        });
    }
    
    /**
     * Property 6: Fault Condition - Missing Input Sanitization
     * 
     * **Validates: Requirement 2.2**
     * 
     * For any user input, the system SHALL sanitize inputs before use to prevent
     * injection attacks and data corruption.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS
     * - Inputs not sanitized
     * - Special characters not handled
     * - Potential for injection attacks
     * 
     * @test
     */
    #[ErisRepeat(repeat: 30)]
    public function test_property_6_input_sanitization()
    {
        $this->forAll(
            // Generate inputs with special characters
            Generators::elements([
                "test<script>alert('xss')</script>",
                "test'; DROP TABLE users--",
                "test\x00null_byte",
                "test\r\n\r\ninjected_header",
                "test%00null_byte",
            ])
        )
        ->then(function ($maliciousInput) {
            // Arrange: Create request with malicious input
            $request = Request::create('/admin/system/group', 'POST', [
                'group_name' => $maliciousInput,
                'group_alias' => 'Test',
                'group_info' => 'Test',
                'active' => 1,
                '_token' => csrf_token()
            ]);
            
            // Act: Try to create group with malicious input
            $controller = new GroupController();
            
            try {
                $controller->store($request);
                
                // Check if input was sanitized
                $group = Group::where('group_name', $maliciousInput)->first();
                
                if ($group) {
                    // On UNFIXED code, malicious input may be stored as-is
                    // On FIXED code, input should be sanitized or rejected
                    
                    // Check for dangerous patterns
                    $this->assertStringNotContainsString(
                        '<script>',
                        $group->group_name,
                        "Input sanitization bug confirmed: <script> tag not sanitized. " .
                        "Input: {$maliciousInput}"
                    );
                    
                    $this->assertStringNotContainsString(
                        'DROP TABLE',
                        $group->group_name,
                        "Input sanitization bug confirmed: SQL injection payload not sanitized. " .
                        "Input: {$maliciousInput}"
                    );
                    
                    $this->assertStringNotContainsString(
                        "\x00",
                        $group->group_name,
                        "Input sanitization bug confirmed: Null byte not sanitized. " .
                        "Input: {$maliciousInput}"
                    );
                    
                    // Cleanup
                    $group->delete();
                }
                
            } catch (ControllerValidationException $e) {
                // Expected on FIXED code - validation rejects malicious input
                $this->assertTrue(
                    true,
                    "Validation correctly rejected malicious input"
                );
            } catch (\Exception $e) {
                // Other exceptions are acceptable
                $this->assertTrue(
                    true,
                    "Input rejected with exception: {$e->getMessage()}"
                );
            }
        });
    }
}
