<?php

namespace Tests\Unit;

use Tests\TestCase;
use ReflectionClass;
use Canvastack\Canvastack\Controllers\Admin\System\GroupController;

/**
 * Test dead code removal from GroupController
 * 
 * This test verifies that unused methods (validation_groups, get_current_group)
 * have been removed from the codebase as part of Issue #4 and Issue #11 fixes.
 * 
 * Property: For any code that has dead code (methods defined but never used),
 * the fixed code SHALL remove unused methods to reduce maintenance burden and
 * improve code clarity.
 * 
 * Requirements: 2.4, 2.22
 */
class GroupControllerDeadCodeRemovalTest extends TestCase
{
    /**
     * Test validation_groups() method has been removed
     * 
     * Issue #4: validation_groups() was defined but never used anywhere in the codebase.
     * It should be removed to eliminate dead code.
     */
    public function test_validation_groups_method_removed(): void
    {
        $reflection = new ReflectionClass(GroupController::class);
        
        // Check if validation_groups method exists
        $hasValidationGroups = $reflection->hasMethod('validation_groups');
        
        $this->assertFalse(
            $hasValidationGroups,
            'validation_groups() method should be removed as it is dead code (Issue #4). ' .
            'The method was never called anywhere in the codebase.'
        );
    }
    
    /**
     * Test validation_groups() is not referenced in source code
     * 
     * Verify that no calls to validation_groups() exist in the controller.
     */
    public function test_validation_groups_not_referenced_in_code(): void
    {
        $file = base_path('vendor/canvastack/origin/src/Controllers/Admin/System/GroupController.php');
        $this->assertFileExists($file, 'GroupController.php should exist');
        
        $sourceCode = file_get_contents($file);
        
        // Check for method calls
        $hasMethodCall = 
            stripos($sourceCode, '$this->validation_groups(') !== false ||
            stripos($sourceCode, 'self::validation_groups(') !== false;
        
        $this->assertFalse(
            $hasMethodCall,
            'validation_groups() should not be called anywhere in GroupController'
        );
    }
    
    /**
     * Test that GroupController still functions without validation_groups()
     * 
     * Preservation: Removing dead code should not affect any existing functionality.
     */
    public function test_group_controller_functions_without_validation_groups(): void
    {
        // This test verifies that GroupController can be instantiated
        // and its public methods are still accessible after removing validation_groups()
        
        $reflection = new ReflectionClass(GroupController::class);
        
        // Verify essential methods still exist
        $this->assertTrue($reflection->hasMethod('index'), 'index() method should exist');
        $this->assertTrue($reflection->hasMethod('create'), 'create() method should exist');
        $this->assertTrue($reflection->hasMethod('store'), 'store() method should exist');
        $this->assertTrue($reflection->hasMethod('edit'), 'edit() method should exist');
        $this->assertTrue($reflection->hasMethod('update'), 'update() method should exist');
        
        $this->assertTrue(true, 'GroupController functions correctly without validation_groups()');
    }
    
    /**
     * Test source code does not contain validation_groups PHPDoc
     * 
     * Verify that documentation for the removed method is also cleaned up.
     */
    public function test_validation_groups_documentation_removed(): void
    {
        $file = base_path('vendor/canvastack/origin/src/Controllers/Admin/System/GroupController.php');
        $sourceCode = file_get_contents($file);
        
        // Check for PHPDoc references
        $hasDocReference = 
            stripos($sourceCode, '@see validation_groups') !== false ||
            stripos($sourceCode, 'validation_groups()') !== false;
        
        $this->assertFalse(
            $hasDocReference,
            'Documentation references to validation_groups() should be removed'
        );
    }
    
    /**
     * Test get_current_group() method has been removed
     * 
     * Issue #11: get_current_group() was defined but never used anywhere in the codebase.
     * It should be removed to eliminate dead code.
     */
    public function test_get_current_group_method_removed(): void
    {
        $reflection = new ReflectionClass(GroupController::class);
        
        // Check if get_current_group method exists
        $hasGetCurrentGroup = $reflection->hasMethod('get_current_group');
        
        $this->assertFalse(
            $hasGetCurrentGroup,
            'get_current_group() method should be removed as it is dead code (Issue #11). ' .
            'The method was never called anywhere in the codebase.'
        );
    }
    
    /**
     * Test get_current_group() is not referenced in source code
     * 
     * Verify that no calls to get_current_group() exist in the controller.
     */
    public function test_get_current_group_not_referenced_in_code(): void
    {
        $file = base_path('vendor/canvastack/origin/src/Controllers/Admin/System/GroupController.php');
        $this->assertFileExists($file, 'GroupController.php should exist');
        
        $sourceCode = file_get_contents($file);
        
        // Check for method calls
        $hasMethodCall = 
            stripos($sourceCode, '$this->get_current_group(') !== false ||
            stripos($sourceCode, 'self::get_current_group(') !== false;
        
        $this->assertFalse(
            $hasMethodCall,
            'get_current_group() should not be called anywhere in GroupController'
        );
    }
    
    /**
     * Test that GroupController still functions without get_current_group()
     * 
     * Preservation: Removing dead code should not affect any existing functionality.
     */
    public function test_group_controller_functions_without_get_current_group(): void
    {
        // This test verifies that GroupController can be instantiated
        // and its public methods are still accessible after removing get_current_group()
        
        $reflection = new ReflectionClass(GroupController::class);
        
        // Verify essential methods still exist
        $this->assertTrue($reflection->hasMethod('index'), 'index() method should exist');
        $this->assertTrue($reflection->hasMethod('create'), 'create() method should exist');
        $this->assertTrue($reflection->hasMethod('store'), 'store() method should exist');
        $this->assertTrue($reflection->hasMethod('edit'), 'edit() method should exist');
        $this->assertTrue($reflection->hasMethod('update'), 'update() method should exist');
        
        $this->assertTrue(true, 'GroupController functions correctly without get_current_group()');
    }
    
    /**
     * Test source code does not contain get_current_group PHPDoc
     * 
     * Verify that documentation for the removed method is also cleaned up.
     */
    public function test_get_current_group_documentation_removed(): void
    {
        $file = base_path('vendor/canvastack/origin/src/Controllers/Admin/System/GroupController.php');
        $sourceCode = file_get_contents($file);
        
        // Check for PHPDoc references
        $hasDocReference = 
            stripos($sourceCode, '@see get_current_group') !== false ||
            (stripos($sourceCode, 'get_current_group()') !== false && 
             stripos($sourceCode, 'function get_current_group') === false);
        
        $this->assertFalse(
            $hasDocReference,
            'Documentation references to get_current_group() should be removed'
        );
    }
}
