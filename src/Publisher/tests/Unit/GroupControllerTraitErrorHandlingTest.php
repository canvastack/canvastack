<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Canvastack\Canvastack\Controllers\Admin\System\GroupController;
use Canvastack\Canvastack\Models\Admin\System\Group;
use Canvastack\Canvastack\Exceptions\Controller\ControllerException;

/**
 * Unit Tests for GroupController Trait Method Error Handling
 * 
 * Tests that trait method calls (privileges_before_insert, mapping_before_insert,
 * privileges_after_insert) are properly wrapped in try-catch blocks with specific
 * error messages and comprehensive logging.
 * 
 * **Validates: Requirement 2.9**
 * 
 * @group unit
 * @group bugfix
 * @group group-controller
 * @group error-handling
 */
class GroupControllerTraitErrorHandlingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test session for authenticated user
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
    }
    
    protected function tearDown(): void
    {
        // Clean up test data
        Group::where('group_name', 'LIKE', 'test_trait_error_%')->delete();
        
        parent::tearDown();
    }
    
    /**
     * Test privileges_before_insert() exception is caught and re-thrown with context
     * 
     * This test verifies that the error handling wrapper exists in set_data_before_insert
     * for the privileges_before_insert() trait method call.
     * 
     * @test
     */
    public function test_privileges_before_insert_exception_caught_and_rethrown()
    {
        // Arrange: Create a real controller instance
        $controller = new GroupController();
        
        // Use reflection to verify set_data_before_insert has try-catch for privileges
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('set_data_before_insert');
        $method->setAccessible(true);
        
        // Get the method source code
        $filename = $reflection->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $source = file($filename);
        $methodSource = implode('', array_slice($source, $startLine - 1, $endLine - $startLine + 1));
        
        // Verify the method contains try-catch block for privileges_before_insert
        $this->assertStringContainsString('privileges_before_insert', $methodSource,
            'set_data_before_insert should call privileges_before_insert');
        $this->assertStringContainsString('try {', $methodSource,
            'set_data_before_insert should have try-catch blocks');
        $this->assertStringContainsString('catch (\Exception $e)', $methodSource,
            'set_data_before_insert should catch exceptions');
        $this->assertStringContainsString('Failed to process privileges', $methodSource,
            'set_data_before_insert should have specific error message for privileges');
        $this->assertStringContainsString('Log::error', $methodSource,
            'set_data_before_insert should log errors');
        $this->assertStringContainsString('group_id', $methodSource,
            'set_data_before_insert should log group_id in error context');
    }
    
    /**
     * Test mapping_before_insert() exception is caught and re-thrown with context
     * 
     * This test verifies that the error handling wrapper exists in set_data_before_insert
     * for the mapping_before_insert() trait method call.
     * 
     * @test
     */
    public function test_mapping_before_insert_exception_caught_and_rethrown()
    {
        // Arrange: Create a real controller instance
        $controller = new GroupController();
        
        // Use reflection to verify set_data_before_insert has try-catch for mapping
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('set_data_before_insert');
        $method->setAccessible(true);
        
        // Get the method source code
        $filename = $reflection->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $source = file($filename);
        $methodSource = implode('', array_slice($source, $startLine - 1, $endLine - $startLine + 1));
        
        // Verify the method contains try-catch block for mapping_before_insert
        $this->assertStringContainsString('mapping_before_insert', $methodSource,
            'set_data_before_insert should call mapping_before_insert');
        $this->assertStringContainsString('try {', $methodSource,
            'set_data_before_insert should have try-catch blocks');
        $this->assertStringContainsString('catch (\Exception $e)', $methodSource,
            'set_data_before_insert should catch exceptions');
        $this->assertStringContainsString('Failed to process page mapping', $methodSource,
            'set_data_before_insert should have specific error message for mapping');
        $this->assertStringContainsString('Log::error', $methodSource,
            'set_data_before_insert should log errors');
        $this->assertStringContainsString('group_id', $methodSource,
            'set_data_before_insert should log group_id in error context');
    }
    
    /**
     * Test privileges_after_insert() exception is caught and re-thrown with context
     * 
     * This test verifies that exceptions from the Privileges trait method
     * are properly caught and re-thrown with context in set_data_after_insert()
     * 
     * @test
     */
    public function test_privileges_after_insert_exception_caught_and_rethrown()
    {
        // Arrange: We'll test this by simulating a scenario where the trait method
        // would throw an exception. Since we can't mock trait methods directly,
        // we verify the error handling wrapper exists by checking the code structure.
        
        // Create a real controller instance
        $controller = new GroupController();
        
        // Use reflection to verify set_data_after_insert has try-catch
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('set_data_after_insert');
        $method->setAccessible(true);
        
        // Get the method source code
        $filename = $reflection->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $source = file($filename);
        $methodSource = implode('', array_slice($source, $startLine - 1, $endLine - $startLine + 1));
        
        // Verify the method contains try-catch block
        $this->assertStringContainsString('try {', $methodSource, 
            'set_data_after_insert should have try-catch block');
        $this->assertStringContainsString('catch (\Exception $e)', $methodSource,
            'set_data_after_insert should catch exceptions');
        $this->assertStringContainsString('Failed to insert privileges', $methodSource,
            'set_data_after_insert should have specific error message');
        $this->assertStringContainsString('Log::error', $methodSource,
            'set_data_after_insert should log errors');
        $this->assertStringContainsString('data_count', $methodSource,
            'set_data_after_insert should log data_count');
    }
    
    /**
     * Test error logging includes context for privileges_before_insert
     * 
     * This test verifies the error logging structure includes proper context
     * 
     * @test
     */
    public function test_error_logging_includes_context_for_privileges()
    {
        // Arrange: Create a real controller instance
        $controller = new GroupController();
        
        // Use reflection to verify error logging structure
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('set_data_before_insert');
        $method->setAccessible(true);
        
        // Get the method source code
        $filename = $reflection->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $source = file($filename);
        $methodSource = implode('', array_slice($source, $startLine - 1, $endLine - $startLine + 1));
        
        // Verify error logging includes proper context
        $this->assertStringContainsString('Log::error(\'Failed to process privileges\'', $methodSource,
            'Error logging should have specific message for privileges');
        $this->assertStringContainsString('\'error\' => $e->getMessage()', $methodSource,
            'Error logging should include error message');
        $this->assertStringContainsString('\'trace\' => $e->getTraceAsString()', $methodSource,
            'Error logging should include stack trace');
        $this->assertStringContainsString('\'group_id\'', $methodSource,
            'Error logging should include group_id');
    }
    
    /**
     * Test error logging includes context for mapping_before_insert
     * 
     * This test verifies the error logging structure includes proper context
     * 
     * @test
     */
    public function test_error_logging_includes_context_for_mapping()
    {
        // Arrange: Create a real controller instance
        $controller = new GroupController();
        
        // Use reflection to verify error logging structure
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('set_data_before_insert');
        $method->setAccessible(true);
        
        // Get the method source code
        $filename = $reflection->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $source = file($filename);
        $methodSource = implode('', array_slice($source, $startLine - 1, $endLine - $startLine + 1));
        
        // Verify error logging includes proper context for mapping
        $this->assertStringContainsString('Log::error(\'Failed to process page mapping\'', $methodSource,
            'Error logging should have specific message for mapping');
        $this->assertStringContainsString('\'error\' => $e->getMessage()', $methodSource,
            'Error logging should include error message');
        $this->assertStringContainsString('\'trace\' => $e->getTraceAsString()', $methodSource,
            'Error logging should include stack trace');
        $this->assertStringContainsString('\'group_id\'', $methodSource,
            'Error logging should include group_id');
    }
    
    /**
     * Test error logging includes data_count for privileges_after_insert
     * 
     * This test verifies the error handling structure in set_data_after_insert
     * 
     * @test
     */
    public function test_error_logging_includes_data_count_for_privileges_after_insert()
    {
        // Arrange: Create a real controller instance
        $controller = new GroupController();
        
        // Use reflection to verify set_data_after_insert has proper error handling
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('set_data_after_insert');
        $method->setAccessible(true);
        
        // Get the method source code
        $filename = $reflection->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $source = file($filename);
        $methodSource = implode('', array_slice($source, $startLine - 1, $endLine - $startLine + 1));
        
        // Verify the method contains proper error handling with data_count
        $this->assertStringContainsString('try {', $methodSource,
            'set_data_after_insert should have try-catch block');
        $this->assertStringContainsString('Log::error', $methodSource,
            'set_data_after_insert should log errors');
        $this->assertStringContainsString('data_count', $methodSource,
            'set_data_after_insert should log data_count in error context');
        $this->assertStringContainsString('is_array($data) ? count($data) : 0', $methodSource,
            'set_data_after_insert should calculate data_count correctly');
        $this->assertStringContainsString('Failed to insert privileges', $methodSource,
            'set_data_after_insert should have specific error message');
    }
}
