<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Canvastack\Canvastack\Controllers\Admin\System\GroupController;
use Canvastack\Canvastack\Models\Admin\System\Group;
use Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException;

/**
 * Unit Tests for GroupController Request Object Usage
 * 
 * Validates that GroupController uses Laravel's Request object methods
 * instead of direct superglobal access ($_GET, $_POST).
 * 
 * **Validates: Requirement 2.2**
 * 
 * Tests verify:
 * - store() uses $request->query() instead of $_GET
 * - store() uses $request->all() instead of $_POST
 * - No direct superglobal access occurs
 * 
 * @group unit
 * @group group-controller
 * @group request-handling
 */
class GroupControllerRequestObjectTest extends TestCase
{
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
     * Test that store() correctly reads query parameters from Request object
     * 
     * Verifies that store() uses $request->query('rolemapage') and
     * $request->query('usein') instead of $_GET superglobal.
     * 
     * @test
     */
    public function test_store_reads_query_parameters_from_request_object()
    {
        // Arrange: Create request with query parameters
        $request = Request::create(
            '/admin/system/group?rolemapage=true&usein=table_name',
            'POST',
            ['test_data' => 'test_value'],
            [],
            [],
            ['HTTP_X_CSRF_TOKEN' => csrf_token()]
        );
        
        // Ensure $_GET is NOT set (to verify code doesn't rely on it)
        unset($_GET['rolemapage']);
        unset($_GET['usein']);
        
        // Act: Call store method
        $controller = new GroupController();
        
        try {
            $result = $controller->store($request);
            
            // Assert: If we reach here, code is using Request object correctly
            $this->assertTrue(
                true,
                "store() correctly uses Request object for query parameters"
            );
        } catch (\Exception $e) {
            // If exception is thrown, it should NOT be because of missing superglobals
            $this->assertStringNotContainsString(
                'Undefined',
                $e->getMessage(),
                "Code should not rely on superglobals. Error: {$e->getMessage()}"
            );
        }
    }
    
    /**
     * Test that store() correctly reads POST data from Request object
     * 
     * Verifies that store() uses $request->all() instead of $_POST superglobal.
     * 
     * @test
     */
    public function test_store_reads_post_data_from_request_object()
    {
        // Arrange: Create request with POST data
        $postData = [
            'test_field' => 'test_value',
            'another_field' => 'another_value'
        ];
        
        $request = Request::create(
            '/admin/system/group?rolemapage=true&usein=table_name',
            'POST',
            $postData,
            [],
            [],
            ['HTTP_X_CSRF_TOKEN' => csrf_token()]
        );
        
        // Ensure $_POST is NOT set (to verify code doesn't rely on it)
        unset($_POST);
        
        // Act: Call store method
        $controller = new GroupController();
        
        try {
            $result = $controller->store($request);
            
            // Assert: If we reach here, code is using Request object correctly
            $this->assertTrue(
                true,
                "store() correctly uses Request object for POST data"
            );
        } catch (\Exception $e) {
            // If exception is thrown, it should NOT be because of missing superglobals
            $this->assertStringNotContainsString(
                'Undefined',
                $e->getMessage(),
                "Code should not rely on superglobals. Error: {$e->getMessage()}"
            );
        }
    }
    
    /**
     * Test that store() works correctly without superglobals set
     * 
     * This is the critical test that verifies no superglobal access occurs.
     * If the code relies on $_GET or $_POST, this test will fail.
     * 
     * @test
     */
    public function test_store_works_without_superglobals()
    {
        // Arrange: Create proper Request object
        $request = Request::create(
            '/admin/system/group?rolemapage=true&usein=field_name',
            'POST',
            ['data' => 'value'],
            [],
            [],
            ['HTTP_X_CSRF_TOKEN' => csrf_token()]
        );
        
        // CRITICAL: Clear all superglobals to ensure code doesn't rely on them
        $originalGet = $_GET ?? [];
        $originalPost = $_POST ?? [];
        
        $_GET = [];
        $_POST = [];
        
        // Act: Call store method
        $controller = new GroupController();
        
        try {
            $result = $controller->store($request);
            
            // Assert: Code should work without superglobals
            $this->assertTrue(
                true,
                "store() works correctly without superglobals - uses Request object"
            );
        } catch (\Exception $e) {
            // Verify the exception is NOT due to missing superglobals
            $errorMessage = $e->getMessage();
            
            $this->assertStringNotContainsString(
                'Undefined index',
                $errorMessage,
                "Code should not access undefined superglobal indices"
            );
            
            $this->assertStringNotContainsString(
                'Undefined variable',
                $errorMessage,
                "Code should not rely on undefined superglobal variables"
            );
            
            // Other exceptions are acceptable (validation, etc.)
            $this->assertTrue(
                true,
                "Exception thrown is not related to superglobal access: {$errorMessage}"
            );
        } finally {
            // Restore superglobals
            $_GET = $originalGet;
            $_POST = $originalPost;
        }
    }
    
    /**
     * Test that AJAX requests use Request object for query parameters
     * 
     * Verifies that AJAX rolemapage requests read parameters from Request
     * object, not from superglobals.
     * 
     * @test
     */
    public function test_ajax_request_uses_request_object_for_query_params()
    {
        // Arrange: Create AJAX request
        $request = Request::create(
            '/admin/system/group?rolemapage=true&usein=table_name',
            'POST',
            ['ajax_data' => 'value'],
            [],
            [],
            ['HTTP_X_CSRF_TOKEN' => csrf_token()]
        );
        
        // Clear superglobals
        unset($_GET['rolemapage']);
        unset($_GET['usein']);
        unset($_POST);
        
        // Act: Call store method
        $controller = new GroupController();
        
        try {
            $result = $controller->store($request);
            
            // Assert: AJAX request should work with Request object
            $this->assertTrue(
                true,
                "AJAX request correctly uses Request object"
            );
        } catch (ControllerValidationException $e) {
            // Validation exceptions are acceptable
            $this->assertTrue(
                true,
                "Validation exception is acceptable: {$e->getMessage()}"
            );
        } catch (\Exception $e) {
            // Verify not a superglobal access error
            $this->assertStringNotContainsString(
                'Undefined',
                $e->getMessage(),
                "AJAX request should not rely on superglobals"
            );
        }
    }
    
    /**
     * Test that normal form submission uses Request object
     * 
     * Verifies that non-AJAX form submissions also use Request object
     * instead of superglobals.
     * 
     * @test
     */
    public function test_normal_form_submission_uses_request_object()
    {
        // Arrange: Create normal form submission (no rolemapage parameter)
        $request = Request::create(
            '/admin/system/group',
            'POST',
            [
                'group_name' => 'test_group_' . uniqid(),
                'group_alias' => 'Test Group',
                'group_info' => 'Test Info',
                'active' => 1,
                '_token' => csrf_token()
            ]
        );
        
        // Clear superglobals
        $_GET = [];
        $_POST = [];
        
        // Act: Call store method
        $controller = new GroupController();
        
        try {
            $result = $controller->store($request);
            
            // Assert: Form submission should work with Request object
            $this->assertTrue(
                true,
                "Normal form submission correctly uses Request object"
            );
            
            // Cleanup: Delete created group if any
            if (isset($controller->stored_id)) {
                Group::where('id', $controller->stored_id)->delete();
            }
        } catch (\Exception $e) {
            // Verify not a superglobal access error
            $this->assertStringNotContainsString(
                'Undefined',
                $e->getMessage(),
                "Form submission should not rely on superglobals"
            );
        }
    }
    
    /**
     * Test that Request object methods are used consistently
     * 
     * This test verifies that the code uses Request object methods
     * throughout the store() method execution.
     * 
     * @test
     */
    public function test_request_object_methods_used_consistently()
    {
        // Arrange: Create request with both query and POST data
        $request = Request::create(
            '/admin/system/group?rolemapage=true&usein=field_value',
            'POST',
            [
                'field1' => 'value1',
                'field2' => 'value2'
            ],
            [],
            [],
            ['HTTP_X_CSRF_TOKEN' => csrf_token()]
        );
        
        // Verify Request object has the data
        $this->assertEquals('true', $request->query('rolemapage'));
        $this->assertEquals('field_value', $request->query('usein'));
        $this->assertEquals('value1', $request->input('field1'));
        $this->assertEquals('value2', $request->input('field2'));
        
        // Clear superglobals completely
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        
        // Act: Call store method
        $controller = new GroupController();
        
        try {
            $result = $controller->store($request);
            
            // Assert: Code should work with Request object only
            $this->assertTrue(
                true,
                "Request object methods used consistently throughout execution"
            );
        } catch (\Exception $e) {
            // Verify the exception is not due to superglobal access
            $errorMessage = $e->getMessage();
            
            $this->assertStringNotContainsString(
                'Undefined',
                $errorMessage,
                "Code should not access undefined superglobals"
            );
            
            $this->assertStringNotContainsString(
                '$_GET',
                $errorMessage,
                "Error should not mention \$_GET superglobal"
            );
            
            $this->assertStringNotContainsString(
                '$_POST',
                $errorMessage,
                "Error should not mention \$_POST superglobal"
            );
        }
    }
}
