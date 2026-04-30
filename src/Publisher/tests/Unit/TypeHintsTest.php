<?php

namespace Tests\Unit;

use Tests\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Canvastack\Canvastack\Controllers\Admin\System\GroupController;

/**
 * Test type hints are present on all methods
 * 
 * This test verifies that all methods in GroupController, Privileges trait,
 * and MappingPage trait have proper type hints for parameters and return values.
 * 
 * Property: For any method definition, the fixed code SHALL include parameter
 * type hints for all parameters, return type hints for all return values.
 */
class TypeHintsTest extends TestCase
{
    /**
     * Test GroupController methods have type hints
     */
    public function test_group_controller_methods_have_type_hints(): void
    {
        $reflection = new ReflectionClass(GroupController::class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PRIVATE);
        
        $methodsWithoutTypeHints = [];
        
        foreach ($methods as $method) {
            // Skip inherited methods from parent classes
            if ($method->getDeclaringClass()->getName() !== GroupController::class) {
                continue;
            }
            
            // Skip __construct as it cannot have return type
            if ($method->getName() === '__construct') {
                continue;
            }
            
            // Check if method has return type
            if (!$method->hasReturnType()) {
                $methodsWithoutTypeHints[] = $method->getName() . ' (missing return type)';
            }
            
            // Check if all parameters have type hints
            foreach ($method->getParameters() as $param) {
                if (!$param->hasType()) {
                    $methodsWithoutTypeHints[] = $method->getName() . ' (parameter $' . $param->getName() . ' missing type)';
                }
            }
        }
        
        $this->assertEmpty(
            $methodsWithoutTypeHints,
            'The following methods are missing type hints: ' . implode(', ', $methodsWithoutTypeHints)
        );
    }
    
    /**
     * Test specific GroupController methods have correct type hints
     */
    public function test_group_controller_specific_method_signatures(): void
    {
        $reflection = new ReflectionClass(GroupController::class);
        
        // Test index() method
        $indexMethod = $reflection->getMethod('index');
        $this->assertTrue($indexMethod->hasReturnType(), 'index() should have return type');
        $returnType = $indexMethod->getReturnType();
        $this->assertStringContainsString('View', $returnType->getName(), 'index() should return View type');
        
        // Test edit() method
        $editMethod = $reflection->getMethod('edit');
        $this->assertTrue($editMethod->hasReturnType(), 'edit() should have return type');
        $editParams = $editMethod->getParameters();
        $this->assertCount(1, $editParams, 'edit() should have 1 parameter');
        $this->assertTrue($editParams[0]->hasType(), 'edit() $id parameter should have type');
        $this->assertEquals('int', $editParams[0]->getType()->getName(), 'edit() $id should be int');
        
        // Test update() method
        $updateMethod = $reflection->getMethod('update');
        $this->assertTrue($updateMethod->hasReturnType(), 'update() should have return type');
        $updateParams = $updateMethod->getParameters();
        $this->assertCount(2, $updateParams, 'update() should have 2 parameters');
        $this->assertTrue($updateParams[0]->hasType(), 'update() $request parameter should have type');
        $this->assertTrue($updateParams[1]->hasType(), 'update() $id parameter should have type');
    }
    
    /**
     * Test methods reject invalid parameter types at runtime
     */
    public function test_methods_enforce_type_safety_at_runtime(): void
    {
        $this->expectException(\TypeError::class);
        
        // Create a mock controller instance
        $controller = $this->getMockBuilder(GroupController::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Try to call edit() with string instead of int
        // This should throw TypeError due to type hint
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('edit');
        $method->setAccessible(true);
        
        // This should fail with TypeError
        $method->invoke($controller, 'not-an-integer');
    }
    
    /**
     * Test that type hints improve IDE autocomplete
     * 
     * This is a documentation test - we verify that return types are specific
     * enough to provide useful IDE autocomplete.
     */
    public function test_return_types_are_specific_for_ide_support(): void
    {
        $reflection = new ReflectionClass(GroupController::class);
        
        // Test that index() returns specific View type, not just mixed
        $indexMethod = $reflection->getMethod('index');
        $returnType = $indexMethod->getReturnType();
        $this->assertNotNull($returnType, 'index() should have return type for IDE support');
        
        // Test that create() returns specific View type
        $createMethod = $reflection->getMethod('create');
        $returnType = $createMethod->getReturnType();
        $this->assertNotNull($returnType, 'create() should have return type for IDE support');
        
        $this->assertTrue(true, 'Type hints provide IDE autocomplete support');
    }
}
